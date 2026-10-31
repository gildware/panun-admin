<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadChangeLog;
use Modules\LeadManagement\Entities\LeadComment;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadHuntingInterest;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\LeadManagement\Entities\LeadProviderChecklist;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\PartyAlternatePhone;
use Modules\LeadManagement\Entities\SuperfoneCall;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupDispatch;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\WhatsAppBooking;
use Modules\WhatsAppModule\Entities\WhatsAppConversation;

class LeadOtherPhoneLinkService
{
    public const ACTION_KEEP_LEAD = 'keep_lead';

    public const ACTION_MERGE_LEAD = 'merge_lead';

    public const ACTION_ATTACH_BOOKING = 'attach_booking';

    public function __construct(
        private readonly PartyPhoneDirectory $phones,
        private readonly LeadOpenStatusService $openStatus,
        private readonly LeadChangeLogService $changeLogs,
    ) {}

    /**
     * @return array{redirect_lead_id: int|null, message: string}
     */
    public function link(Lead $sourceLead, array $input): array
    {
        $ownerType = (string) ($input['owner_type'] ?? '');
        $ownerId = (string) ($input['owner_id'] ?? '');
        $action = (string) ($input['action'] ?? '');
        $targetLeadId = isset($input['target_lead_id']) ? (int) $input['target_lead_id'] : null;
        $bookingId = isset($input['booking_id']) ? (string) $input['booking_id'] : null;

        if (! in_array($ownerType, [
            PartyAlternatePhone::OWNER_CUSTOMER,
            PartyAlternatePhone::OWNER_PROVIDER,
            PartyAlternatePhone::OWNER_PROVIDER_LEAD,
        ], true)) {
            throw new \InvalidArgumentException(translate('Select_customer_or_provider') ?: 'Select customer or provider');
        }
        if ($ownerId === '') {
            throw new \InvalidArgumentException(translate('Select_the_person') ?: 'Select the person');
        }
        if (! in_array($action, [self::ACTION_KEEP_LEAD, self::ACTION_MERGE_LEAD, self::ACTION_ATTACH_BOOKING], true)) {
            throw new \InvalidArgumentException(translate('Select_what_this_call_is_about') ?: 'Select what this contact is about');
        }

        $this->assertOwnerExists($ownerType, $ownerId);

        $phone = (string) $sourceLead->phone_number;
        $digits = PartyPhoneDirectory::normalizeDigits($phone);
        if ($digits === null) {
            throw new \InvalidArgumentException(translate('Invalid_phone_number') ?: 'Invalid phone number');
        }

        return DB::transaction(function () use (
            $sourceLead,
            $ownerType,
            $ownerId,
            $action,
            $targetLeadId,
            $bookingId,
            $phone,
            $digits
        ) {
            $this->phones->addAlternate(
                $ownerType,
                $ownerId,
                $phone,
                (int) $sourceLead->id,
                Auth::id() ? (string) Auth::id() : null
            );

            $ownerLabel = $this->ownerDisplayLabel($ownerType, $ownerId);
            $phoneDisplay = $this->phones->formatDigitsDisplay($digits);

            if ($action === self::ACTION_KEEP_LEAD) {
                $this->changeLogs->record((int) $sourceLead->id, [
                    'other_phone_link' => [
                        'label' => 'Other_phone_link',
                        'old' => '—',
                        'new' => ($phoneDisplay.' → '.$ownerLabel.' ('.(translate('Kept_as_new_enquiry') ?: 'kept as new enquiry').')'),
                    ],
                ]);
                foreach ($this->openLeadsForOwner($ownerType, $ownerId) as $openLead) {
                    if ((int) $openLead->id === (int) $sourceLead->id) {
                        continue;
                    }
                    $this->changeLogs->record((int) $openLead->id, [
                        'other_phone_added' => [
                            'label' => 'Other_phone_added',
                            'old' => '—',
                            'new' => $phoneDisplay.' ('.(translate('From_lead') ?: 'from lead').' #'.$sourceLead->id.')',
                        ],
                    ]);
                }

                return [
                    'redirect_lead_id' => (int) $sourceLead->id,
                    'message' => translate('Other_phone_linked_lead_kept')
                        ?: 'Other phone linked. This lead was kept as a new enquiry.',
                ];
            }

            if ($action === self::ACTION_ATTACH_BOOKING) {
                if ($bookingId === null || $bookingId === '') {
                    throw new \InvalidArgumentException(translate('Select_a_booking') ?: 'Select a booking');
                }
                $booking = Booking::query()->find($bookingId);
                if (! $booking) {
                    throw new \InvalidArgumentException(translate('Booking_was_not_found') ?: 'Booking was not found');
                }
                $this->assertBookingBelongsToOwner($booking, $ownerType, $ownerId);
                $this->reattachCallsToBooking($sourceLead, $booking, $ownerType, $ownerId);
                $bookingLabel = 'Booking #'.($booking->readable_id ?: $booking->id);
                $this->changeLogs->record((int) $sourceLead->id, [
                    'other_phone_link' => [
                        'label' => 'Other_phone_link',
                        'old' => '—',
                        'new' => $phoneDisplay.' → '.$ownerLabel.' / '.$bookingLabel,
                    ],
                ]);
                if ($ownerType === PartyAlternatePhone::OWNER_PROVIDER_LEAD) {
                    $this->changeLogs->record((int) $ownerId, [
                        'other_phone_added' => [
                            'label' => 'Other_phone_added',
                            'old' => '—',
                            'new' => $phoneDisplay.' ('.(translate('From_lead') ?: 'from lead').' #'.$sourceLead->id.' → '.$bookingLabel.')',
                        ],
                    ]);
                }
                $this->softDeleteSourceLead(
                    $sourceLead,
                    'Merged as other phone → Booking #'.($booking->readable_id ?: $booking->id)
                );

                return [
                    'redirect_lead_id' => null,
                    'message' => translate('Other_phone_linked_attached_to_booking')
                        ?: 'Other phone linked. Call attached to booking and this lead was moved to deleted leads.',
                ];
            }

            // merge_lead
            $target = $this->resolveMergeTargetLead($ownerType, $ownerId, $targetLeadId, $digits);
            if (! $target) {
                throw new \InvalidArgumentException(
                    translate('Select_an_open_lead_to_merge_into') ?: 'Select an open lead to merge into'
                );
            }
            if ((int) $target->id === (int) $sourceLead->id) {
                throw new \InvalidArgumentException(translate('Cannot_merge_lead_into_itself') ?: 'Cannot merge a lead into itself');
            }

            $this->mergeIntoAndArchive(
                $sourceLead,
                $target,
                $phoneDisplay.' → '.$ownerLabel
            );

            return [
                'redirect_lead_id' => (int) $target->id,
                'message' => translate('Other_phone_linked_merged_into_lead')
                    ?: 'Other phone linked. Data moved into the selected lead and this lead was moved to deleted leads.',
            ];
        });
    }

    /**
     * Finish an incomplete other-phone merge when this lead's phone already belongs
     * to another open provider lead (moves remaining history, then archives source).
     *
     * @return array{redirect_lead_id: int, message: string}
     */
    public function finishMergeIntoLinkedProviderLead(Lead $source): array
    {
        $info = $this->phones->displayForLead($source);
        $linked = $info['linked_as'] ?? null;
        if (! is_array($linked)
            || ($linked['owner_type'] ?? '') !== PartyAlternatePhone::OWNER_PROVIDER_LEAD
            || empty($linked['owner_id'])
        ) {
            throw new \InvalidArgumentException(
                translate('Lead_is_not_linked_as_other_phone_of_provider_lead')
                    ?: 'This lead is not linked as another phone of a provider lead.'
            );
        }

        $target = Lead::query()->find((int) $linked['owner_id']);
        if (! $target) {
            throw new \InvalidArgumentException(translate('Target_lead_not_found') ?: 'Target lead was not found');
        }
        if ((int) $target->id === (int) $source->id) {
            throw new \InvalidArgumentException(translate('Cannot_merge_lead_into_itself') ?: 'Cannot merge a lead into itself');
        }

        return DB::transaction(function () use ($source, $target, $linked) {
            $phoneDisplay = $this->phones->formatDigitsDisplay(
                (string) (PartyPhoneDirectory::normalizeDigits((string) $source->phone_number) ?? '')
            );
            $this->mergeIntoAndArchive(
                $source,
                $target,
                $phoneDisplay.' → '.($linked['owner_label'] ?? ('Lead #'.$target->id))
            );

            return [
                'redirect_lead_id' => (int) $target->id,
                'message' => translate('Other_phone_linked_merged_into_lead')
                    ?: 'All data moved into the linked lead and this lead was moved to deleted leads.',
            ];
        });
    }

    /**
     * Move every attached CRM record from $source into $target, log the merge, soft-delete $source.
     */
    public function mergeIntoAndArchive(Lead $source, Lead $target, string $linkSummary = ''): void
    {
        $this->mergeLeadData($source, $target);

        $summary = $linkSummary !== ''
            ? $linkSummary.'; '.(translate('Merged_into_lead') ?: 'merged into lead').' #'.$target->id
            : ((translate('Merged_into_lead') ?: 'Merged into lead').' #'.$target->id);

        $this->changeLogs->record((int) $source->id, [
            'other_phone_link' => [
                'label' => 'Other_phone_link',
                'old' => '—',
                'new' => $summary,
            ],
        ]);
        $this->changeLogs->record((int) $target->id, [
            'other_phone_added' => [
                'label' => 'Other_phone_added',
                'old' => '—',
                'new' => ($linkSummary !== '' ? $linkSummary.' · ' : '')
                    .(translate('From_lead') ?: 'from lead').' #'.$source->id,
            ],
        ]);

        $this->softDeleteSourceLead(
            $source,
            'Merged as other phone → Lead #'.$target->id
        );
    }

    private function ownerDisplayLabel(string $ownerType, string $ownerId): string
    {
        if ($ownerType === PartyAlternatePhone::OWNER_CUSTOMER) {
            $user = User::query()->find($ownerId);
            $name = trim((string) (($user->first_name ?? '').' '.($user->last_name ?? '')));

            return (translate('Customer') ?: 'Customer').' '.($name !== '' ? $name : $ownerId);
        }
        if ($ownerType === PartyAlternatePhone::OWNER_PROVIDER_LEAD) {
            $lead = Lead::query()->find($ownerId);
            $name = trim((string) ($lead->name ?? ''));

            return (translate('Provider_lead') ?: 'Provider lead').' #'.$ownerId.($name !== '' ? ' — '.$name : '');
        }
        $provider = Provider::query()->find($ownerId);
        $name = trim((string) ($provider?->company_name ?: $provider?->contact_person_name ?: ''));

        return (translate('Provider') ?: 'Provider').' '.($name !== '' ? $name : $ownerId);
    }

    /**
     * Open leads for this owner (by all known phones), for the merge picker.
     *
     * @return \Illuminate\Support\Collection<int, Lead>
     */
    public function openLeadsForOwner(string $ownerType, string $ownerId): \Illuminate\Support\Collection
    {
        if ($ownerType === PartyAlternatePhone::OWNER_PROVIDER_LEAD) {
            $lead = Lead::query()->find($ownerId);
            if (! $lead || ! $this->openStatus->isLeadOpen($lead)) {
                return collect();
            }

            return collect([$lead]);
        }

        $phones = $this->phones->phonesForOwner($ownerType, $ownerId);
        if ($phones === []) {
            return collect();
        }

        $leads = Lead::query()
            ->where(function ($query) use ($phones) {
                foreach ($phones as $digits) {
                    $query->orWhere(function ($inner) use ($digits) {
                        $this->wherePhoneEndsWith($inner, 'phone_number', $digits);
                    });
                }
            })
            ->orderByDesc('id')
            ->get();

        return $leads->filter(fn (Lead $lead) => $this->openStatus->isLeadOpen($lead))->values();
    }

    /**
     * Bookings for this owner (open + closed), newest first.
     *
     * @return \Illuminate\Support\Collection<int, Booking>
     */
    public function bookingsForOwner(string $ownerType, string $ownerId, int $limit = 25): \Illuminate\Support\Collection
    {
        $query = Booking::query()
            ->with(['customer:id,first_name,last_name,phone', 'provider:id,company_name,contact_person_name'])
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($ownerType === PartyAlternatePhone::OWNER_CUSTOMER) {
            $query->where('customer_id', $ownerId);
        } elseif ($ownerType === PartyAlternatePhone::OWNER_PROVIDER) {
            $query->where('provider_id', $ownerId);
        } elseif ($ownerType === PartyAlternatePhone::OWNER_PROVIDER_LEAD) {
            // Unregistered provider lead: only bookings already tied to that lead (usually none).
            $query->where('lead_id', (int) $ownerId);
        } else {
            return collect();
        }

        return $query->get();
    }

    private function assertOwnerExists(string $ownerType, string $ownerId): void
    {
        if ($ownerType === PartyAlternatePhone::OWNER_CUSTOMER) {
            $exists = User::query()
                ->where('id', $ownerId)
                ->whereIn('user_type', CUSTOMER_USER_TYPES)
                ->exists();
            if (! $exists) {
                throw new \InvalidArgumentException(translate('Customer_not_found') ?: 'Customer not found');
            }

            return;
        }

        if ($ownerType === PartyAlternatePhone::OWNER_PROVIDER_LEAD) {
            $lead = Lead::query()->find($ownerId);
            if (! $lead || $lead->lead_type !== Lead::TYPE_PROVIDER) {
                throw new \InvalidArgumentException(translate('Provider_lead_not_found') ?: 'Provider lead not found');
            }
            if (! $this->openStatus->isLeadOpen($lead)) {
                throw new \InvalidArgumentException(translate('Provider_lead_is_not_open') ?: 'Provider lead is not open');
            }

            return;
        }

        if (! Provider::query()->where('id', $ownerId)->exists()) {
            throw new \InvalidArgumentException(translate('Provider_not_found') ?: 'Provider not found');
        }
    }

    private function assertBookingBelongsToOwner(Booking $booking, string $ownerType, string $ownerId): void
    {
        if ($ownerType === PartyAlternatePhone::OWNER_CUSTOMER
            && (string) $booking->customer_id !== $ownerId
        ) {
            throw new \InvalidArgumentException(translate('Booking_does_not_belong_to_this_customer') ?: 'Booking does not belong to this customer');
        }
        if ($ownerType === PartyAlternatePhone::OWNER_PROVIDER
            && (string) $booking->provider_id !== $ownerId
        ) {
            throw new \InvalidArgumentException(translate('Booking_does_not_belong_to_this_provider') ?: 'Booking does not belong to this provider');
        }
        if ($ownerType === PartyAlternatePhone::OWNER_PROVIDER_LEAD
            && (int) $booking->lead_id !== (int) $ownerId
        ) {
            throw new \InvalidArgumentException(translate('Booking_does_not_belong_to_this_provider_lead') ?: 'Booking does not belong to this provider lead');
        }
    }

    private function resolveMergeTargetLead(
        string $ownerType,
        string $ownerId,
        ?int $targetLeadId,
        string $sourceDigits
    ): ?Lead {
        $open = $this->openLeadsForOwner($ownerType, $ownerId);
        if ($targetLeadId) {
            $picked = $open->first(fn (Lead $lead) => (int) $lead->id === $targetLeadId);
            if ($picked) {
                return $picked;
            }
            // Allow explicit pick of a still-open lead even if phone list was stale.
            $lead = Lead::query()->find($targetLeadId);
            if ($lead && $this->openStatus->isLeadOpen($lead)) {
                return $lead;
            }

            return null;
        }

        if ($open->count() === 1) {
            return $open->first();
        }

        return null;
    }

    private function mergeLeadData(Lead $source, Lead $target): void
    {
        SuperfoneCall::query()
            ->where('lead_id', $source->id)
            ->update(['lead_id' => $target->id]);

        LeadFollowup::query()
            ->where('lead_id', $source->id)
            ->update(['lead_id' => $target->id]);

        LeadComment::query()
            ->where('lead_id', $source->id)
            ->update(['lead_id' => $target->id]);

        LeadChangeLog::query()
            ->where('lead_id', $source->id)
            ->update(['lead_id' => $target->id]);

        $this->mergeTypeHistories($source, $target);
        $this->mergeProviderChecklist($source, $target);
        $this->mergeCustomerTags($source, $target);

        LeadHuntingInterest::query()
            ->where('lead_id', $source->id)
            ->update(['lead_id' => $target->id]);

        LeadOutboundEnquiry::query()
            ->where('lead_id', $source->id)
            ->update(['lead_id' => $target->id]);

        LeadOutboundEnquiry::query()
            ->where('related_lead_id', $source->id)
            ->update(['related_lead_id' => $target->id]);

        try {
            if (Schema::hasTable((new WhatsAppVoiceFollowupDispatch)->getTable())) {
                WhatsAppVoiceFollowupDispatch::query()
                    ->where('lead_id', $source->id)
                    ->update(['lead_id' => $target->id]);
            }
        } catch (\Throwable) {
            // Optional table / alternate connection — skip.
        }

        Booking::query()
            ->where('lead_id', $source->id)
            ->update(['lead_id' => $target->id]);

        if (class_exists(WhatsAppBooking::class)) {
            try {
                WhatsAppBooking::query()
                    ->where('lead_id', $source->id)
                    ->update(['lead_id' => $target->id]);
            } catch (\Throwable) {
                // Optional WhatsApp DB — skip.
            }
        }

        if (class_exists(WhatsAppConversation::class)) {
            try {
                $conversationTable = (new WhatsAppConversation)->getTable();
                if (Schema::hasTable($conversationTable) && Schema::hasColumn($conversationTable, 'active_lead_id')) {
                    WhatsAppConversation::query()
                        ->where('active_lead_id', $source->id)
                        ->update(['active_lead_id' => $target->id]);
                }
            } catch (\Throwable) {
                // Optional WhatsApp DB — skip.
            }
        }

        // Remarks: append once (idempotent if re-run after a partial merge).
        $sourceRemarks = trim((string) ($source->remarks ?? ''));
        $targetRemarks = trim((string) ($target->remarks ?? ''));
        $fromMarker = '[From lead #'.$source->id.']';
        if ($sourceRemarks !== '' && ! str_contains($targetRemarks, $fromMarker)) {
            $target->remarks = $targetRemarks === ''
                ? $sourceRemarks
                : $targetRemarks."\n\n".$fromMarker."\n".$sourceRemarks;
        }

        $sourceName = trim((string) ($source->name ?? ''));
        $targetName = trim((string) ($target->name ?? ''));
        if (($targetName === '' || str_starts_with($targetName, 'WhatsApp ') || $targetName === 'Unknown')
            && $sourceName !== ''
            && ! str_starts_with($sourceName, 'WhatsApp ')
            && $sourceName !== 'Unknown'
        ) {
            $target->name = $sourceName;
        }

        if ($target->source_id === null && $source->source_id) {
            $target->source_id = $source->source_id;
        }
        if ($target->ad_source_id === null && $source->ad_source_id) {
            $target->ad_source_id = $source->ad_source_id;
        }

        if (($target->lead_type === Lead::TYPE_UNKNOWN || $target->lead_type === null || $target->lead_type === '')
            && in_array($source->lead_type, [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER], true)
        ) {
            $target->lead_type = $source->lead_type;
        }

        if ($this->temperatureRank($source->lead_temperature) > $this->temperatureRank($target->lead_temperature)) {
            $target->lead_temperature = $source->lead_temperature;
        }

        if ($target->handled_by === null || $target->handled_by === '' || $target->handled_by === Lead::HANDLED_BY_AI) {
            if (Lead::assigneeIsHuman($source->handled_by)) {
                $target->handled_by = $source->handled_by;
            }
        }

        if ($source->next_followup_at) {
            if (! $target->next_followup_at || $source->next_followup_at->lt($target->next_followup_at)) {
                $target->next_followup_at = $source->next_followup_at;
            }
        }

        if (! $target->hasInitialCallRecording() && $source->hasInitialCallRecording()) {
            $target->initial_call_recording_path = $source->initial_call_recording_path;
            $target->initial_call_recording_disk = $source->initial_call_recording_disk;
            $target->initial_call_recording_mime = $source->initial_call_recording_mime;
            $target->initial_call_recording_original_name = $source->initial_call_recording_original_name;
            $target->initial_call_recording_transcript = $source->initial_call_recording_transcript;
            $target->initial_call_recording_summary = $source->initial_call_recording_summary;
            $target->initial_call_recording_transcribed_at = $source->initial_call_recording_transcribed_at;
        }

        if (($target->hunting_status ?? Lead::HUNTING_OFF) === Lead::HUNTING_OFF
            && ($source->hunting_status ?? Lead::HUNTING_OFF) !== Lead::HUNTING_OFF
        ) {
            $target->hunting_status = $source->hunting_status;
            $target->hunting_started_at = $source->hunting_started_at;
            $target->hunting_started_by = $source->hunting_started_by;
            $target->hunting_unpublished_at = $source->hunting_unpublished_at;
            $target->hunting_unpublished_by = $source->hunting_unpublished_by;
            $target->hunting_unpublish_reason = $source->hunting_unpublish_reason;
            $target->hunting_unpublish_notes = $source->hunting_unpublish_notes;
        }

        $target->save();
    }

    private function mergeTypeHistories(Lead $source, Lead $target): void
    {
        $sourceHistories = LeadTypeHistory::query()
            ->where('lead_id', $source->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($sourceHistories->isEmpty()) {
            return;
        }

        $sourceLatestByType = [];
        foreach ($sourceHistories as $history) {
            $sourceLatestByType[(string) $history->type] = $history;
        }

        $targetLatestByType = [];
        $targetHistories = LeadTypeHistory::query()
            ->where('lead_id', $target->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        foreach ($targetHistories as $history) {
            $targetLatestByType[(string) $history->type] = $history;
        }

        LeadTypeHistory::query()
            ->where('lead_id', $source->id)
            ->update(['lead_id' => $target->id]);

        foreach ($sourceLatestByType as $type => $sourceHistory) {
            $targetLatest = $targetLatestByType[$type] ?? null;
            $sourceData = is_array($sourceHistory->data) ? $sourceHistory->data : [];
            $targetData = is_array($targetLatest?->data) ? $targetLatest->data : [];
            $merged = $this->mergeAssocPreferFilled($targetData, $sourceData);
            if ($merged === $targetData) {
                continue;
            }

            LeadTypeHistory::query()->create([
                'lead_id' => $target->id,
                'type' => $type,
                'data' => $merged,
                'created_by' => Auth::id(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $fill
     * @return array<string, mixed>
     */
    private function mergeAssocPreferFilled(array $base, array $fill): array
    {
        $out = $base;
        foreach ($fill as $key => $value) {
            if (is_array($value) && is_array($out[$key] ?? null)) {
                if ($key === 'zone_ids' || array_is_list($value)) {
                    $out[$key] = array_values(array_unique(array_merge(
                        array_map('strval', $out[$key] ?? []),
                        array_map('strval', $value)
                    )));
                } else {
                    $out[$key] = $this->mergeAssocPreferFilled($out[$key], $value);
                }

                continue;
            }

            $existing = $out[$key] ?? null;
            $existingEmpty = $existing === null || $existing === '' || $existing === [];
            if ($existingEmpty && $value !== null && $value !== '' && $value !== []) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function mergeProviderChecklist(Lead $source, Lead $target): void
    {
        $sourceRows = LeadProviderChecklist::query()
            ->where('lead_id', $source->id)
            ->get();

        if ($sourceRows->isEmpty()) {
            return;
        }

        $targetByItem = LeadProviderChecklist::query()
            ->where('lead_id', $target->id)
            ->get()
            ->keyBy('provider_checklist_item_id');

        foreach ($sourceRows as $row) {
            $existing = $targetByItem->get($row->provider_checklist_item_id);
            if ($existing) {
                if ($row->is_done && ! $existing->is_done) {
                    $existing->is_done = true;
                    $existing->save();
                }
                $row->delete();

                continue;
            }

            $row->lead_id = $target->id;
            $row->save();
        }
    }

    private function mergeCustomerTags(Lead $source, Lead $target): void
    {
        if (! Schema::hasTable('lead_customer_tag')) {
            return;
        }

        $sourceTagIds = DB::table('lead_customer_tag')
            ->where('lead_id', $source->id)
            ->pluck('customer_lead_tag_id')
            ->all();

        if ($sourceTagIds === []) {
            return;
        }

        $targetTagIds = DB::table('lead_customer_tag')
            ->where('lead_id', $target->id)
            ->pluck('customer_lead_tag_id')
            ->all();

        $toAttach = array_values(array_diff($sourceTagIds, $targetTagIds));
        foreach ($toAttach as $tagId) {
            DB::table('lead_customer_tag')->insert([
                'lead_id' => $target->id,
                'customer_lead_tag_id' => $tagId,
            ]);
        }

        DB::table('lead_customer_tag')->where('lead_id', $source->id)->delete();
    }

    private function temperatureRank(?string $temperature): int
    {
        return match ($temperature) {
            Lead::TEMPERATURE_HOTTER => 4,
            Lead::TEMPERATURE_HOT => 3,
            Lead::TEMPERATURE_WARM => 2,
            Lead::TEMPERATURE_COLD => 1,
            default => 0,
        };
    }

    private function reattachCallsToBooking(
        Lead $source,
        Booking $booking,
        string $ownerType,
        string $ownerId
    ): void {
        $payload = [
            'booking_id' => $booking->id,
            'lead_id' => null,
        ];
        if ($ownerType === PartyAlternatePhone::OWNER_CUSTOMER) {
            $payload['customer_user_id'] = $ownerId;
        } elseif ($ownerType === PartyAlternatePhone::OWNER_PROVIDER) {
            $payload['provider_id'] = $ownerId;
        } elseif ($ownerType === PartyAlternatePhone::OWNER_PROVIDER_LEAD) {
            // Keep call on the target provider lead until a registered provider exists.
            $payload['lead_id'] = (int) $ownerId;
        }

        SuperfoneCall::query()
            ->where('lead_id', $source->id)
            ->update($payload);
    }

    private function softDeleteSourceLead(Lead $lead, string $reason): void
    {
        $lead->deleted_by = Auth::id();
        $lead->deletion_reason = $reason;
        $lead->save();
        if (! $lead->trashed()) {
            $lead->delete();
        }
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
}
