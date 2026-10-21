<?php

namespace Modules\LeadManagement\Services;

use App\Support\AdminMenuCounts;
use App\Support\StoragePathPrefix;
use Modules\AdminModule\Entities\UserNotification;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadChangeLog;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\Source;
use Modules\LeadManagement\Entities\SuperfoneCall;
use Modules\LeadManagement\Jobs\DownloadSuperfoneRecording;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class SuperfoneCallService
{
    public function __construct(private readonly SuperfoneApiLogger $apiLogs)
    {
    }

    private ?Lead $createdLead = null;

    private bool $announceMissedCall = false;
    /** Superfone sends call instants as UTC ISO-8601. Admin times are shown in IST. */
    public const DISPLAY_TIMEZONE = 'Asia/Kolkata';

    public const EVENT_ALL_CALLS = 'ALL_CALLS';

    public const EVENT_MISSED_CALL = 'MISSED_CALL';

    public const EVENT_RECORDING = 'CDR_RECORDING_AVAILABLE';

    public const EVENT_SUMMARY = 'CDR_SUMMARY_READY';

    /** @var list<string> */
    public const HANDLED_EVENTS = [
        self::EVENT_ALL_CALLS,
        self::EVENT_MISSED_CALL,
        self::EVENT_RECORDING,
        self::EVENT_SUMMARY,
    ];

    /** @var list<string> */
    private const PROVISIONAL_DISPOSITIONS = [
        'ACTIVE',
        'RINGING',
        'CREATED',
        'BACKUP_RINGING',
        'BACKUP_ACTIVE',
        'UNKNOWN',
        'UNKNOWN_BY_SYSTEM',
    ];

    /** @var list<string> */
    private const ANSWERED_DISPOSITIONS = [
        'ANSWER',
        'IVR_ANSWERED',
        'BACKUP_ANSWER',
        'BACKUP_ENDED',
    ];

    /** @var list<string> */
    private const MISSED_DISPOSITIONS = [
        'NOANSWER',
        'BUSY',
        'CANCEL',
        'MISSED',
        'REJECTED',
        'IVR_DROPPED',
        'BACKUP_NOANSWER',
        'BACKUP_BUSY',
        'BACKUP_MISSED',
        'BACKUP_CANCEL',
        'USER_BUSY',
        'NO_USER_RESPONSE',
        'CALL_REJECTED',
        'ORIGINATOR_CANCEL',
    ];

    /**
     * Store one Superfone webhook. Later events for the same cdr_uuid update the same row.
     */
    public function receive(array $payload): ?SuperfoneCall
    {
        $event = strtoupper(trim((string) ($payload['event'] ?? '')));
        $uuid = trim((string) ($payload['cdr_uuid'] ?? ''));

        if ($uuid === '' || ! in_array($event, self::HANDLED_EVENTS, true)) {
            Log::info('Superfone webhook ignored', ['event' => $event !== '' ? $event : null, 'has_uuid' => $uuid !== '']);

            return null;
        }

        $this->createdLead = null;
        $this->announceMissedCall = false;
        try {
            $call = $this->persistCall($payload, $event, $uuid);
        } catch (UniqueConstraintViolationException) {
            $call = $this->persistCall($payload, $event, $uuid);
        }
        $this->notifyCreatedLead();
        if ($call && $this->announceMissedCall) {
            $this->notifyMissedCall($call);
        }

        if ($call) {
            AdminMenuCounts::forget();
        }

        if ($call && $this->shouldDownloadRecording($call)) {
            DownloadSuperfoneRecording::dispatch($call->id)->afterResponse();
        }

        return $call;
    }

    /**
     * A finished call and its recording can arrive together. Lock the existing row,
     * and if both requests still insert, the loser retries and updates that row.
     */
    private function persistCall(array $payload, string $event, string $uuid): SuperfoneCall
    {
        return DB::transaction(function () use ($payload, $event, $uuid) {
            $call = SuperfoneCall::query()->where('cdr_uuid', $uuid)->lockForUpdate()->first();
            if (! $call) {
                $call = new SuperfoneCall(['cdr_uuid' => $uuid]);
            }
            $previousOutcome = $call->exists ? $call->outcome : null;
            $this->applyPayload($call, $payload, $event);
            $call->save();
            $this->matchLead($call);
            $this->matchEmployee($call);
            $call->save();
            $this->syncFollowup($call);
            $this->syncBookingFollowup($call);
            $this->announceMissedCall = self::shouldNotifyMissed($previousOutcome, $call->outcome);

            return $call->fresh() ?? $call;
        });
    }

    public function downloadRecording(SuperfoneCall $call): SuperfoneCall
    {
        if ($call->hasRecording() || empty($call->recording_url)) {
            return $call;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'sfrec');
        if ($tmp === false) {
            $call->recording_error = 'Could not create a temporary file for the recording.';
            $call->save();

            return $call;
        }

        $started = microtime(true);
        $logged = false;
        try {
            $response = Http::timeout(90)
                ->withOptions(['allow_redirects' => true])
                ->sink($tmp)
                ->get($call->recording_url);
            $durationMs = (int) round((microtime(true) - $started) * 1000);

            if (! $response->successful()) {
                $call->recording_error = 'Recording download failed with HTTP '.$response->status().'.';
                $call->save();
                $this->logRecording($call, $response->status(), 'failed', $call->recording_error, $durationMs);
                $logged = true;

                return $call;
            }

            $size = filesize($tmp);
            if ($size === false || $size < 64) {
                $call->recording_error = 'Recording download was empty.';
                $call->save();
                $this->logRecording($call, $response->status(), 'failed', $call->recording_error, $durationMs);
                $logged = true;

                return $call;
            }

            if ($size > 40_000_000) {
                $call->recording_error = 'Recording is larger than 40 MB and was not stored.';
                $call->save();
                $this->logRecording($call, $response->status(), 'failed', $call->recording_error, $durationMs);
                $logged = true;

                return $call;
            }

            $mime = strtolower(trim(strtok((string) $response->header('Content-Type'), ';') ?: ''));
            $extension = $this->extensionFor($mime, $call->recording_url);
            $disk = getDisk();
            $dir = StoragePathPrefix::apply('lead-followups/');
            $filename = now()->toDateString().'-'.uniqid().'.'.$extension;

            if (! Storage::disk($disk)->exists($dir)) {
                Storage::disk($disk)->makeDirectory($dir);
            }

            Storage::disk($disk)->put($dir.$filename, (string) file_get_contents($tmp));

            $call->recording_path = $filename;
            $call->recording_disk = $disk;
            $call->recording_mime = $mime !== '' ? $mime : 'audio/mpeg';
            $call->recording_original_name = $call->cdr_uuid.'.'.$extension;
            $call->recording_error = null;
            $call->save();
            $this->logRecording($call, $response->status(), 'success', null, $durationMs);
            $logged = true;
            $this->syncFollowup($call->fresh());
        } catch (\Throwable $exception) {
            Log::warning('Superfone recording download failed', [
                'cdr_uuid' => $call->cdr_uuid,
                'message' => $exception->getMessage(),
            ]);
            $call->recording_error = 'Recording download failed.';
            $call->save();
            if (! $logged) {
                $this->logRecording(
                    $call,
                    null,
                    'failed',
                    $exception->getMessage(),
                    (int) round((microtime(true) - $started) * 1000)
                );
            }
        } finally {
            @unlink($tmp);
        }

        return $call->fresh() ?? $call;
    }

    private function logRecording(SuperfoneCall $call, ?int $status, string $result, ?string $message, int $durationMs): void
    {
        $this->apiLogs->write([
            'direction' => 'outbound',
            'kind' => 'recording',
            'event' => 'RECORDING_DOWNLOAD',
            'cdr_uuid' => $call->cdr_uuid,
            'method' => 'GET',
            'url' => $call->recording_url,
            'http_status' => $status,
            'result' => $result,
            'message' => $message,
            'duration_ms' => $durationMs,
        ]);
    }

    public static function lastTenDigits(?string $phone): string
    {
        $digits = User::normalizeContactPhoneDigits((string) $phone);
        if (strlen($digits) <= 10) {
            return $digits;
        }

        return substr($digits, -10);
    }

    public static function shouldNotifyMissed(?string $previousOutcome, ?string $outcome): bool
    {
        return $outcome === 'missed' && $previousOutcome !== 'missed';
    }

    /**
     * Calls stored in the last few minutes, for the admin toast.
     *
     * @return array<int, array{id: string, title: string, phone: string, party: string, meta: string, message: string, tone: string, icon: string, url: string}>
     */
    public function recentAdminToasts(): array
    {
        return SuperfoneCall::query()
            ->with(['lead:id,name', 'customer:id,first_name,last_name'])
            ->where('created_at', '>=', now()->subMinutes(3))
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(function (SuperfoneCall $call) {
                $copy = self::adminToastCopy(
                    $call->direction,
                    $call->outcome,
                    $call->customer_phone ?: $call->customer_phone_digits,
                    $call->staff_name,
                    $this->toastPartyName($call),
                    ((int) $call->duration_seconds) > 0 ? $call->durationLabel() : null,
                );

                return $copy + [
                    'id' => (string) $call->id,
                    'url' => route('admin.calls.show', $call),
                ];
            })
            ->all();
    }

    /**
     * @return array{title: string, phone: string, party: string, meta: string, message: string, tone: string, icon: string}
     */
    public static function adminToastCopy(
        ?string $direction,
        ?string $outcome,
        ?string $phone,
        ?string $staffName,
        ?string $partyName = null,
        ?string $durationLabel = null,
    ): array {
        $direction = strtoupper((string) $direction);
        $outcome = (string) $outcome;
        $phone = trim((string) $phone) !== '' ? trim((string) $phone) : translate('Unknown');
        $party = trim((string) $partyName);
        $staff = trim((string) $staffName);
        $duration = trim((string) $durationLabel);

        $outboundAnswered = $direction === 'OUTBOUND' && $outcome === 'answered';
        $outboundUnanswered = $direction === 'OUTBOUND' && in_array($outcome, ['unanswered', 'missed'], true);
        $employee = $staff !== '' ? $staff : translate('Employee');
        $who = $party !== '' ? $party : $phone;
        $resultWord = $outboundAnswered
            ? translate('answered')
            : ($outboundUnanswered ? translate('did_not_answer') : '');
        $sentence = '';
        if ($direction === 'OUTBOUND') {
            $sentence = trim(str_replace(
                [':employee', ':phone', ':who', ':result'],
                [$employee, $phone, $who, $resultWord],
                $resultWord !== ''
                    ? translate('Employee_made_outbound_call_to_and_result')
                    : translate('Employee_made_outbound_call_to'),
            ));
        }

        $title = match (true) {
            $direction === 'OUTBOUND' => $sentence,
            $outcome === 'missed' => translate('Missed_call'),
            $outcome === 'answered' => translate('Incoming_call_answered'),
            default => translate('New_call_received'),
        };

        $tone = match (true) {
            $outcome === 'missed' && $direction !== 'OUTBOUND' => 'missed',
            $direction === 'OUTBOUND' && $outcome === 'answered' => 'outbound',
            $direction === 'OUTBOUND' => 'outbound-quiet',
            default => 'inbound',
        };

        $icon = match (true) {
            $tone === 'missed' => 'phone_missed',
            $direction === 'OUTBOUND' => 'call_made',
            default => 'call_received',
        };

        $meta = $direction === 'OUTBOUND'
            ? ''
            : implode(' · ', array_values(array_filter([$staff, $duration])));
        $message = $sentence !== ''
            ? $sentence
            : implode(' · ', array_values(array_filter([
                $party !== '' ? $party : null,
                $phone,
                $meta !== '' ? $meta : null,
            ])));

        return [
            'title' => $title,
            'phone' => $phone,
            'party' => $party,
            'meta' => $meta,
            'message' => $message,
            'tone' => $tone,
            'icon' => $icon,
        ];
    }

    private function toastPartyName(SuperfoneCall $call): ?string
    {
        $leadName = trim((string) ($call->lead?->name ?? ''));
        if ($leadName !== '' && strcasecmp($leadName, 'Unknown') !== 0) {
            return $leadName;
        }

        $customerName = trim(implode(' ', array_filter([
            $call->customer?->first_name,
            $call->customer?->last_name,
        ])));

        return $customerName !== '' ? $customerName : null;
    }

    public static function outcomeForDirection(?string $outcome, ?string $direction): ?string
    {
        if ($outcome === 'missed' && strtoupper((string) $direction) === 'OUTBOUND') {
            return 'unanswered';
        }

        return $outcome;
    }

    public const LINK_PROVIDER = 'provider';

    public const LINK_LEAD = 'lead';

    public const LINK_CUSTOMER = 'customer';

    public const LINK_UNKNOWN = 'unknown';

    public const LINK_UNMATCHED = 'unmatched';

    /** @var list<string> */
    private const CLOSED_BOOKING_STATUSES = [
        'completed',
        'canceled',
        'cancelled',
        'refund_request',
    ];

    /**
     * @return array{party: string, booking: 'one'|'choose'|null, needs_lead_link: bool}
     */
    public static function decideLink(bool $hasProvider, bool $hasLead, bool $hasCustomer, ?string $direction, int $openBookings): array
    {
        if ($hasProvider) {
            return ['party' => self::LINK_PROVIDER, 'booking' => null, 'needs_lead_link' => true];
        }

        $booking = $openBookings === 1 ? 'one' : ($openBookings > 1 ? 'choose' : null);

        if ($hasLead) {
            return ['party' => self::LINK_LEAD, 'booking' => $booking, 'needs_lead_link' => false];
        }

        if ($hasCustomer) {
            return ['party' => self::LINK_CUSTOMER, 'booking' => $booking, 'needs_lead_link' => false];
        }

        if (strtoupper((string) $direction) === 'INBOUND') {
            return ['party' => self::LINK_UNKNOWN, 'booking' => null, 'needs_lead_link' => false];
        }

        return ['party' => self::LINK_UNMATCHED, 'booking' => null, 'needs_lead_link' => true];
    }

    public static function isProvisionalDisposition(?string $disposition): bool
    {
        return $disposition !== null && in_array(strtoupper($disposition), self::PROVISIONAL_DISPOSITIONS, true);
    }

    public static function shouldReplaceDisposition(?string $current, ?string $incoming): bool
    {
        $incoming = self::clean($incoming);
        if ($incoming === null) {
            return false;
        }

        if ($current && ! self::isProvisionalDisposition($current) && self::isProvisionalDisposition($incoming)) {
            return false;
        }

        return true;
    }

    public static function shouldReplaceDuration(?int $current, ?int $incoming): bool
    {
        if ($incoming === null) {
            return false;
        }

        if ($current !== null && $current > 0 && $incoming === 0) {
            return false;
        }

        return true;
    }

    private function applyPayload(SuperfoneCall $call, array $payload, string $event): void
    {
        $call->last_event = $event;

        $payloads = is_array($call->payloads) ? $call->payloads : [];
        $payloads[$event] = $payload;
        $call->payloads = $payloads;

        $this->fillIfPresent($call, 'cdr_id', $this->nullableInt($payload['cdr_id'] ?? null));
        $this->fillIfPresent($call, 'cdr_request_uuid', self::clean($payload['cdr_request_uuid'] ?? null));
        $this->fillIfPresent($call, 'superfone_number', self::clean($payload['superfone_number'] ?? null));
        $this->fillIfPresent($call, 'organization_name', self::clean($payload['organization_name'] ?? null));

        $phone = self::clean($payload['cdr_phone'] ?? null);
        if ($phone !== null) {
            $call->customer_phone = $phone;
            $digits = self::lastTenDigits($phone);
            $call->customer_phone_digits = $digits !== '' ? $digits : null;
        }

        $direction = strtoupper((string) self::clean($payload['cdr_call_type'] ?? null));
        if (in_array($direction, ['INBOUND', 'OUTBOUND'], true)) {
            $call->direction = $direction;
        }

        $incomingDisposition = self::clean($payload['cdr_disposition'] ?? null);
        $keepAnswered = $event === self::EVENT_MISSED_CALL && $call->outcome === 'answered';
        if (! $keepAnswered && $event !== self::EVENT_SUMMARY && self::shouldReplaceDisposition($call->disposition, $incomingDisposition)) {
            $call->disposition = strtoupper($incomingDisposition);
        }

        $incomingDuration = $this->nullableInt($payload['cdr_duration'] ?? null);
        if (self::shouldReplaceDuration($call->duration_seconds, $incomingDuration)) {
            $call->duration_seconds = $incomingDuration;
        }

        $ringing = $this->nullableInt($payload['cdr_ringing_duration'] ?? null);
        if ($ringing !== null) {
            $call->ringing_seconds = $ringing;
        }

        $this->fillDate($call, 'started_at', $payload['cdr_start'] ?? null);
        $this->fillDate($call, 'answered_at', $payload['cdr_answer'] ?? null);
        $this->fillDate($call, 'ended_at', $payload['cdr_end'] ?? null);

        $this->fillIfPresent($call, 'staff_first_name', self::clean($payload['staff_first_name'] ?? null));
        $this->fillIfPresent($call, 'staff_last_name', self::clean($payload['staff_last_name'] ?? null));
        $this->fillIfPresent($call, 'staff_phone', self::clean($payload['staff_phone'] ?? null));
        $staffName = trim(implode(' ', array_filter([
            $call->staff_first_name,
            $call->staff_last_name,
        ])));
        if ($staffName !== '') {
            $call->staff_name = $staffName;
        }

        if ($event === self::EVENT_RECORDING) {
            $this->fillIfPresent($call, 'recording_url', self::clean($payload['cdr_recording_url'] ?? null));
            $this->fillDate($call, 'recording_url_expires_at', $payload['cdr_recording_url_expires_at'] ?? null);
            $this->fillDate($call, 'recording_file_expires_at', $payload['cdr_recording_expire_at'] ?? null);
        }

        if ($event === self::EVENT_SUMMARY) {
            $summary = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];
            $text = self::clean($payload['summary_text'] ?? ($summary['summary_text'] ?? null));
            if ($text !== null) {
                $call->summary_text = $text;
            }
            if (isset($summary['insights']) && is_array($summary['insights'])) {
                $call->summary_insights = $summary['insights'];
            }
        }

        $call->outcome = self::outcomeForDirection($this->resolveOutcome($call, $event), $call->direction);
    }

    private function resolveOutcome(SuperfoneCall $call, string $event): ?string
    {
        if ($event === self::EVENT_MISSED_CALL && $call->outcome !== 'answered') {
            return 'missed';
        }

        if (self::isProvisionalDisposition($call->disposition) && $event === self::EVENT_RECORDING) {
            return $call->outcome;
        }

        $disposition = strtoupper((string) $call->disposition);
        if ($disposition === '') {
            return $call->outcome;
        }

        if (in_array($disposition, self::ANSWERED_DISPOSITIONS, true)) {
            return 'answered';
        }

        if (in_array($disposition, self::MISSED_DISPOSITIONS, true)) {
            return 'missed';
        }

        if (self::isProvisionalDisposition($disposition)) {
            return $call->outcome;
        }

        return 'other';
    }

    public function unmatch(SuperfoneCall $call): SuperfoneCall
    {
        return DB::transaction(function () use ($call) {
            if ($call->lead_followup_id) {
                $followup = LeadFollowup::query()->find($call->lead_followup_id);
                if ($followup && $this->remarksAreManaged($followup->remarks)) {
                    $followup->delete();
                }
            }

            if ($call->booking_followup_id) {
                $followup = BookingFollowup::query()->find($call->booking_followup_id);
                if ($followup && ($followup->reason === 'Superfone call' || $this->remarksAreManaged($followup->remarks))) {
                    $followup->delete();
                }
            }

            $call->lead_id = null;
            $call->lead_followup_id = null;
            $call->booking_id = null;
            $call->booking_followup_id = null;
            $call->manual_unmatched = true;
            $call->save();

            return $call->fresh();
        });
    }

    private function matchLead(SuperfoneCall $call): void
    {
        if ($call->manual_unmatched) {
            return;
        }

        if ($call->lead_id || $call->provider_id || $call->customer_user_id) {
            if (! $call->booking_id && ($call->lead_id || $call->customer_user_id)) {
                $this->attachBookingIfSingle($call);
            }

            return;
        }

        $digits = (string) $call->customer_phone_digits;
        if (strlen($digits) < 8) {
            return;
        }

        $provider = $this->findProviderByDigits($digits);
        $leadMatches = $provider ? collect() : $this->leadsByDigits($digits);
        $lead = $this->firstOpenLead($leadMatches);
        $closedLeadOnly = self::freshLeadForClosedMatch($lead !== null, $leadMatches->isNotEmpty());
        $customer = $provider ? null : $this->findUserByPhoneDigits($digits, CUSTOMER_USER_TYPES);
        $openBookings = $customer
            ? $this->openBookingsForCustomer((string) $customer->id)->count()
            : 0;
        $decision = self::decideLink(
            $provider !== null,
            $lead !== null,
            $customer !== null,
            $call->direction,
            $openBookings
        );

        if ($decision['party'] === self::LINK_PROVIDER && $provider) {
            $call->provider_id = $provider->id;
            $call->called_party_type = LeadFollowup::CALLED_PARTY_PROVIDER;

            return;
        }

        if ($decision['party'] === self::LINK_LEAD && $lead) {
            $call->lead_id = $lead->id;
            $call->called_party_type = in_array($lead->lead_type, [Lead::TYPE_PROVIDER, Lead::TYPE_FUTURE_PROVIDER], true)
                ? LeadFollowup::CALLED_PARTY_PROVIDER
                : LeadFollowup::CALLED_PARTY_CUSTOMER;
            if ($customer) {
                $call->customer_user_id = $customer->id;
            }
            $this->attachBookingIfSingle($call);

            return;
        }

        if ($decision['party'] === self::LINK_CUSTOMER && $customer && ! $closedLeadOnly) {
            $call->customer_user_id = $customer->id;
            $call->called_party_type = LeadFollowup::CALLED_PARTY_CUSTOMER;
            $this->attachBookingIfSingle($call);

            return;
        }

        if ($decision['party'] === self::LINK_UNKNOWN || $closedLeadOnly) {
            $created = $this->createUnknownLead($call);
            $call->lead_id = $created->id;
            $call->called_party_type = LeadFollowup::CALLED_PARTY_CUSTOMER;
            if ($customer) {
                $call->customer_user_id = $customer->id;
                $this->attachBookingIfSingle($call);
            }
        }
    }

    /**
     * A closed lead must not absorb the next call. Create a new lead instead.
     */
    public static function freshLeadForClosedMatch(bool $hasOpenLead, bool $hasAnyLead): bool
    {
        return ! $hasOpenLead && $hasAnyLead;
    }

    public function linkToLead(SuperfoneCall $call, Lead $lead): SuperfoneCall
    {
        return DB::transaction(function () use ($call, $lead) {
            $call->manual_unmatched = false;
            $call->lead_id = $lead->id;
            if (! $call->provider_id) {
                $call->called_party_type = in_array($lead->lead_type, [Lead::TYPE_PROVIDER, Lead::TYPE_FUTURE_PROVIDER], true)
                    ? LeadFollowup::CALLED_PARTY_PROVIDER
                    : LeadFollowup::CALLED_PARTY_CUSTOMER;
            }

            $digits = self::lastTenDigits($lead->phone_number);
            if (! $call->customer_user_id && strlen($digits) >= 8) {
                $customer = $this->findUserByPhoneDigits($digits, CUSTOMER_USER_TYPES);
                if ($customer) {
                    $call->customer_user_id = $customer->id;
                }
            }

            $call->booking_id = null;
            $call->save();
            $this->attachBookingIfSingle($call);
            $call->save();
            $this->syncFollowup($call);
            $this->syncBookingFollowup($call);

            return $call->fresh();
        });
    }

    public function linkToBooking(SuperfoneCall $call, string $bookingId): SuperfoneCall
    {
        $booking = Booking::query()->find($bookingId);
        if (! $booking) {
            throw new \InvalidArgumentException('Booking was not found.');
        }

        return DB::transaction(function () use ($call, $booking) {
            $call->manual_unmatched = false;
            $call->booking_id = $booking->id;
            if (! $call->customer_user_id) {
                $call->customer_user_id = $booking->customer_id;
            }
            $call->save();
            $this->syncBookingFollowup($call);

            return $call->fresh();
        });
    }

    public function openBookings(SuperfoneCall $call): Collection
    {
        $customerId = $call->customer_user_id;
        if (! $customerId && $call->lead) {
            $digits = self::lastTenDigits($call->lead->phone_number);
            if (strlen($digits) >= 8) {
                $customerId = $this->findUserByPhoneDigits($digits, CUSTOMER_USER_TYPES)?->id;
            }
        }

        if (! $customerId) {
            return collect();
        }

        return $this->openBookingsForCustomer((string) $customerId);
    }

    /**
     * Leads and bookings already stored for this call's phone, including closed ones.
     *
     * @return array{leads: Collection, bookings: Collection}
     */
    public function existingLinks(SuperfoneCall $call): array
    {
        $digits = (string) $call->customer_phone_digits;
        $leads = strlen($digits) >= 8
            ? $this->openLeadsByDigits($digits)
            : collect();

        $customerId = $call->customer_user_id;
        if (! $customerId && strlen($digits) >= 8) {
            $customerId = $this->findUserByPhoneDigits($digits, CUSTOMER_USER_TYPES)?->id;
        }

        $bookings = collect();
        if ($customerId) {
            $bookings = $bookings->merge($this->openBookingsDetailed('customer_id', (string) $customerId));
        }
        if ($call->provider_id) {
            $bookings = $bookings->merge($this->openBookingsDetailed('provider_id', (string) $call->provider_id));
        }

        return [
            'leads' => $leads,
            'bookings' => $bookings->unique('id')->sortByDesc('created_at')->take(12)->values(),
        ];
    }

    private function openLeadsByDigits(string $digits): Collection
    {
        $query = Lead::query()
            ->with('source:id,name')
            ->where('lead_type', '!=', Lead::TYPE_INVALID)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(12);
        $this->wherePhoneEndsWith($query, 'phone_number', $digits);

        return $query->get([
            'id',
            'name',
            'phone_number',
            'lead_type',
            'lead_temperature',
            'source_id',
            'date_time_of_lead_received',
            'next_followup_at',
            'remarks',
        ]);
    }

    private function openBookingsDetailed(string $column, string $id): Collection
    {
        return Booking::query()
            ->with([
                'customer:id,first_name,last_name,phone',
                'provider:id,company_name,contact_person_name',
                'category:id,name',
                'subCategory:id,name',
            ])
            ->where($column, $id)
            ->whereNotIn('booking_status', self::CLOSED_BOOKING_STATUSES)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get([
                'id',
                'readable_id',
                'booking_status',
                'customer_id',
                'provider_id',
                'category_id',
                'sub_category_id',
                'service_schedule',
                'total_booking_amount',
                'is_paid',
                'created_at',
            ]);
    }

    private function createUnknownLead(SuperfoneCall $call): Lead
    {
        $lead = Lead::query()->create([
            'name' => 'Unknown',
            'phone_number' => $call->customer_phone ?: $call->customer_phone_digits,
            'source_id' => Source::ensureSuperfoneSource()->id,
            'lead_type' => Lead::TYPE_UNKNOWN,
            'date_time_of_lead_received' => $call->started_at ?? now(),
            'remarks' => 'Created from an unmatched Superfone call.',
            'next_followup_at' => app(LeadFollowupService::class)->defaultNextFollowupAt(),
            'created_by' => null,
        ]);

        LeadChangeLog::query()->create([
            'lead_id' => $lead->id,
            'changed_by' => null,
            'changes' => [
                'lead_added' => [
                    'label' => 'Lead_Added',
                    'old' => '—',
                    'new' => 'Created from an unmatched Superfone call.',
                ],
            ],
        ]);

        $this->createdLead = $lead;

        return $lead;
    }

    private function notifyMissedCall(SuperfoneCall $call): void
    {
        if (! function_exists('admin_inbox_notify_all')) {
            return;
        }

        $who = $call->customer_phone ?: $call->customer_phone_digits ?: translate('Unknown');
        $body = implode(' · ', array_filter([
            $call->directionLabel(),
            $who,
            $call->staff_name,
        ]));

        try {
            admin_inbox_notify_all(
                UserNotification::TYPE_MISSED_CALL,
                translate('Missed_call'),
                $body,
                route('admin.calls.show', $call),
                'superfone_call_missed',
                (string) $call->id,
            );
        } catch (\Throwable $e) {
            Log::warning('Superfone missed call notification failed', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyCreatedLead(): void
    {
        $lead = $this->createdLead;
        $this->createdLead = null;
        if (! $lead || ! function_exists('admin_inbox_notify_lead_created')) {
            return;
        }

        try {
            admin_inbox_notify_lead_created($lead);
        } catch (\Throwable $e) {
            Log::warning('Superfone unknown lead notification failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Link a stored call that arrived before unknown-lead creation existed.
     */
    public function linkUnmatchedCall(SuperfoneCall $call): SuperfoneCall
    {
        if ($call->lead_id) {
            return $call;
        }

        $this->createdLead = null;
        $fresh = DB::transaction(function () use ($call) {
            $this->matchLead($call);
            $call->save();
            $this->syncFollowup($call);
            $this->syncBookingFollowup($call);

            return $call->fresh();
        });
        $this->notifyCreatedLead();

        return $fresh;
    }

    private function findProviderByDigits(string $digits): ?Provider
    {
        $user = $this->findUserByPhoneDigits($digits, PROVIDER_USER_TYPES);
        if ($user) {
            $provider = $user->provider;
            if (! $provider && ! empty($user->provider_id)) {
                $provider = Provider::query()->find($user->provider_id);
            }
            if (! $provider && $user->serviceman?->provider_id) {
                $provider = Provider::query()->find($user->serviceman->provider_id);
            }
            if ($provider) {
                return $provider;
            }
        }

        return Provider::query()
            ->where(function ($query) use ($digits) {
                $this->wherePhoneEndsWith($query, 'company_phone', $digits);
                $query->orWhere(function ($inner) use ($digits) {
                    $this->wherePhoneEndsWith($inner, 'contact_person_phone', $digits);
                });
            })
            ->first();
    }

    private function leadsByDigits(string $digits): Collection
    {
        $leadQuery = Lead::query()
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
        $this->wherePhoneEndsWith($leadQuery, 'phone_number', $digits);

        return $leadQuery->get();
    }

    private function firstOpenLead(Collection $leads): ?Lead
    {
        if ($leads->isEmpty()) {
            return null;
        }

        $meta = app(LeadOpenStatusService::class)->buildLeadStatusMeta($leads);

        return $leads->first(
            fn (Lead $lead) => (bool) ($meta[(int) $lead->id]['is_open'] ?? false)
        );
    }

    private function findUserByPhoneDigits(string $digits, array $userTypes): ?User
    {
        $query = User::query()->whereIn('user_type', $userTypes);
        $this->wherePhoneEndsWith($query, 'phone', $digits);

        return $query->first();
    }

    private function wherePhoneEndsWith($query, string $column, string $digits): void
    {
        $query->where(function ($inner) use ($column, $digits) {
            if (DB::connection()->getDriverName() === 'mysql') {
                $inner->whereRaw(
                    "RIGHT(REGEXP_REPLACE(COALESCE({$column}, ''), '[^0-9]', ''), ?) = ?",
                    [strlen($digits), $digits]
                );

                return;
            }

            $inner->where($column, $digits)->orWhere($column, 'like', '%'.$digits);
        });
    }

    private function openBookingsForCustomer(string $customerId): Collection
    {
        return Booking::query()
            ->where('customer_id', $customerId)
            ->whereNotIn('booking_status', self::CLOSED_BOOKING_STATUSES)
            ->orderByDesc('created_at')
            ->get(['id', 'readable_id', 'booking_status', 'customer_id']);
    }

    private function attachBookingIfSingle(SuperfoneCall $call): void
    {
        if ($call->booking_id || ($call->provider_id && ! $call->lead_id && ! $call->customer_user_id)) {
            return;
        }

        $open = $this->openBookings($call);
        if ($open->count() === 1) {
            $call->booking_id = $open->first()->id;
            if (! $call->customer_user_id) {
                $call->customer_user_id = $open->first()->customer_id;
            }
        }
    }

    private function syncBookingFollowup(SuperfoneCall $call): void
    {
        if (! $call->booking_id) {
            return;
        }

        $call->loadMissing('provider');
        $remarks = $this->buildRemarks($call);
        $providerName = trim((string) ($call->provider?->company_name ?: $call->provider?->contact_person_name));
        $party = $call->provider_id ? BookingFollowup::CALLED_PARTY_PROVIDER : BookingFollowup::CALLED_PARTY_CUSTOMER;
        $when = $call->ended_at ?? $call->started_at ?? now();
        $attributes = [
            'booking_id' => $call->booking_id,
            'date' => $when,
            'followup_at' => $when,
            'reason' => 'Superfone call',
            'remarks' => $remarks,
            'for' => $call->provider_id ? 'provider' : 'customer',
            'status' => 'completed',
            'contact_channel' => BookingFollowup::CHANNEL_CALL,
            'called_party_type' => $party,
            'called_name' => $providerName !== '' ? $providerName : null,
            'called_number' => $call->customer_phone,
            'called_provider_id' => $call->provider_id,
            'recording_summary' => $call->summary_text,
        ];

        try {
            $followup = $call->booking_followup_id
                ? BookingFollowup::query()->find($call->booking_followup_id)
                : null;
            if ($followup) {
                $followup->update($attributes);
            } else {
                $followup = BookingFollowup::query()->create($attributes);
                $call->booking_followup_id = $followup->id;
                $call->save();
            }
        } catch (\Throwable $e) {
            Log::warning('Superfone booking follow-up was not saved', [
                'call_id' => $call->id,
                'booking_id' => $call->booking_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function matchEmployee(SuperfoneCall $call): void
    {
        if ($call->employee_user_id || empty($call->staff_phone)) {
            return;
        }

        $user = User::findByContactPhoneScoped($call->staff_phone, ADMIN_USER_TYPES);
        $staffDigits = self::lastTenDigits($call->staff_phone);
        if (! $user && strlen($staffDigits) >= 10 && DB::connection()->getDriverName() === 'mysql') {
            $user = User::query()
                ->whereIn('user_type', ADMIN_USER_TYPES)
                ->whereRaw(
                    "RIGHT(REGEXP_REPLACE(COALESCE(phone, ''), '[^0-9]', ''), 10) = ?",
                    [$staffDigits]
                )
                ->first();
        }
        if (! $user) {
            return;
        }

        $call->employee_user_id = (string) $user->id;
        if (empty($call->staff_name)) {
            $call->staff_name = trim($user->first_name.' '.$user->last_name) ?: null;
        }
    }

    private function syncFollowup(SuperfoneCall $call): void
    {
        if (! $call->lead_id) {
            return;
        }

        $lead = Lead::query()->find($call->lead_id);
        if (! $lead) {
            return;
        }

        $followup = $call->lead_followup_id
            ? LeadFollowup::query()->find($call->lead_followup_id)
            : null;

        $call->loadMissing('provider');
        $providerName = trim((string) ($call->provider?->company_name ?: $call->provider?->contact_person_name));
        $remarks = $this->buildRemarks($call);
        $attributes = [
            'lead_id' => $lead->id,
            'followup_at' => $call->ended_at ?? $call->started_at ?? now(),
            'contact_channel' => LeadFollowup::CHANNEL_CALL,
            'followup_status' => LeadFollowup::STATUS_TAKEN,
            'called_party_type' => $call->provider_id
                ? LeadFollowup::CALLED_PARTY_PROVIDER
                : ($call->called_party_type ?: LeadFollowup::CALLED_PARTY_CUSTOMER),
            'called_name' => $providerName !== '' ? $providerName : $lead->name,
            'called_number' => $call->customer_phone ?: $lead->phone_number,
            'called_provider_id' => $call->provider_id,
            'recording_summary' => $call->summary_text,
        ];

        if ($call->hasRecording()) {
            $attributes['recording_path'] = $call->recording_path;
            $attributes['recording_disk'] = $call->recordingDisk();
            $attributes['recording_mime'] = $call->recording_mime;
            $attributes['recording_original_name'] = $call->recording_original_name;
        }

        if (! $followup) {
            $followup = LeadFollowup::query()->create(array_merge($attributes, [
                'due_followup_at' => $lead->next_followup_at,
                'remarks' => $remarks,
                'created_by' => $call->employee_user_id,
            ]));
            $call->lead_followup_id = $followup->id;
            $call->save();
        } else {
            if ($this->remarksAreManaged($followup->remarks)) {
                $attributes['remarks'] = $remarks;
            }
            $followup->update($attributes);
        }

        $leadUpdates = [];
        if (in_array($call->outcome, ['missed', 'unanswered'], true) && $lead->next_followup_at === null) {
            $leadUpdates['next_followup_at'] = now();
        }
        if (($lead->handled_by === null || $lead->handled_by === '') && $call->employee_user_id) {
            $leadUpdates['handled_by'] = $call->employee_user_id;
        }
        if ($leadUpdates !== []) {
            $lead->update($leadUpdates);
        }
    }

    private function buildRemarks(SuperfoneCall $call): string
    {
        $direction = match ($call->direction) {
            'INBOUND' => 'inbound',
            'OUTBOUND' => 'outbound',
            default => '',
        };
        $lines = [trim('Superfone '.$direction.' call')];

        if ($call->outcome === 'answered') {
            $lines[] = 'Answered'.($call->duration_seconds !== null ? ' · '.$call->durationLabel() : '');
        } elseif ($call->outcome === 'unanswered') {
            $lines[] = 'Unanswered'.($call->disposition ? ' · '.$call->disposition : '');
        } elseif ($call->outcome === 'missed') {
            $lines[] = 'Missed'.($call->disposition ? ' · '.$call->disposition : '');
        } elseif ($call->disposition && ! self::isProvisionalDisposition($call->disposition)) {
            $lines[] = $call->disposition;
        }

        if ($call->staff_name) {
            $lines[] = 'Agent: '.$call->staff_name;
        }

        $call->loadMissing('provider');
        $providerName = trim((string) ($call->provider?->company_name ?: $call->provider?->contact_person_name));
        if ($providerName !== '') {
            $lines[] = 'Provider: '.$providerName;
        }

        if (trim((string) $call->summary_text) !== '') {
            $lines[] = '';
            $lines[] = trim((string) $call->summary_text);
        }

        return implode("\n", $lines);
    }

    private function remarksAreManaged(?string $remarks): bool
    {
        $remarks = trim((string) $remarks);

        return $remarks === '' || str_starts_with($remarks, 'Superfone ');
    }

    private function shouldDownloadRecording(SuperfoneCall $call): bool
    {
        return ! $call->hasRecording() && ! empty($call->recording_url);
    }

    private function fillIfPresent(SuperfoneCall $call, string $attribute, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $call->{$attribute} = $value;
    }

    /**
     * Superfone CDR times are absolute instants (usually `...Z`). Store the IST wall clock
     * so the calls list matches India Standard Time, the same way other admin timestamps are kept.
     */
    public static function parseSuperfoneInstant(mixed $value): ?Carbon
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->timezone(self::DISPLAY_TIMEZONE);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payloads
     */
    public static function instantFromPayloads(array $payloads, string $key): ?Carbon
    {
        foreach (array_reverse($payloads) as $payload) {
            if (! is_array($payload) || ! array_key_exists($key, $payload)) {
                continue;
            }

            $parsed = self::parseSuperfoneInstant($payload[$key]);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    private function fillDate(SuperfoneCall $call, string $attribute, mixed $value): void
    {
        $parsed = self::parseSuperfoneInstant($value);
        if ($parsed === null) {
            return;
        }

        $call->{$attribute} = $parsed;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    private function extensionFor(string $mime, string $url): string
    {
        $fromMime = match ($mime) {
            'audio/mpeg', 'audio/mp3' => 'mp3',
            'audio/wav', 'audio/x-wav', 'audio/wave' => 'wav',
            'audio/mp4', 'audio/m4a', 'audio/x-m4a' => 'm4a',
            'audio/ogg' => 'ogg',
            'video/mp4' => 'mp4',
            default => null,
        };

        if ($fromMime) {
            return $fromMime;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo(is_string($path) ? $path : '', PATHINFO_EXTENSION));

        return in_array($ext, ['mp3', 'wav', 'm4a', 'ogg', 'mp4'], true) ? $ext : 'mp3';
    }

    private static function clean(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
