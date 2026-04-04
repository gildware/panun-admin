<?php

namespace Modules\WhatsAppModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\LeadManagement\Entities\Lead;
use Modules\WhatsAppModule\Entities\ProviderLead;
use Modules\WhatsAppModule\Entities\WhatsAppBooking;
use Modules\WhatsAppModule\Entities\WhatsAppConversation;
use Modules\WhatsAppModule\Entities\WhatsAppUser;

/**
 * Server-side tools for the WhatsApp AI agent. Every query is scoped to the active WhatsApp phone.
 */
class WhatsAppAiToolExecutor
{
    public function __construct(
        protected WhatsAppPublicCatalogService $catalog,
        protected WhatsAppSupportWorkHours $workHours,
        protected WhatsAppLeadLifecycleService $leadLifecycle,
        protected WhatsAppAiSupportKnowledgeService $supportKnowledge
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function execute(string $name, array $args, string $phone): array
    {
        return match ($name) {
            'get_public_business_info' => $this->getPublicBusinessInfo(),
            'search_support_knowledge' => $this->searchSupportKnowledge($args),
            'list_my_booking_summaries' => $this->listMyBookingSummaries($phone, $args),
            'get_my_booking_details' => $this->getMyBookingDetails($phone, $args),
            'upsert_my_draft_booking' => $this->upsertMyDraftBooking($phone, $args),
            'submit_my_booking_for_human_confirmation' => $this->submitMyBookingForHuman($phone, $args),
            'upsert_my_draft_provider_lead' => $this->upsertMyDraftProviderLead($phone, $args),
            'submit_my_provider_lead_for_human_confirmation' => $this->submitMyProviderLeadForHuman($phone, $args),
            'request_human_support_handoff' => $this->requestHumanHandoff($phone, $args),
            default => ['ok' => false, 'error' => 'unknown_tool'],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function functionDeclarations(): array
    {
        return [
            [
                'name' => 'get_public_business_info',
                'description' => 'Returns public company info, sample service names, zones, and any configured visiting-charge notes. Use for FAQs. Never invent prices.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => new \stdClass,
                ],
            ],
            [
                'name' => 'search_support_knowledge',
                'description' => 'Search curated FAQs, safety/troubleshooting hints, and onboarding tips (config-driven). Use before booking when the user has a problem, confusion, or "how does this work" questions. Pass the user\'s concern in their words.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Short phrase or keywords from the customer message (may be empty to list general tips only)'],
                    ],
                ],
            ],
            [
                'name' => 'list_my_booking_summaries',
                'description' => 'Lists this customer\'s own WhatsApp booking requests (reference ids and status only).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'integer', 'description' => 'Max rows, default 8'],
                    ],
                ],
            ],
            [
                'name' => 'get_my_booking_details',
                'description' => 'Returns one booking row for this phone only (by public booking reference).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'booking_id' => ['type' => 'string'],
                    ],
                    'required' => ['booking_id'],
                ],
            ],
            [
                'name' => 'upsert_my_draft_booking',
                'description' => 'Create or update a DRAFT booking for this customer (not confirmed until human confirms in admin).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'booking_id' => ['type' => 'string', 'description' => 'Existing draft id if continuing'],
                        'name' => ['type' => 'string', 'description' => 'Contact person name for the booking only — not the type of work (e.g. mistary/mistry/plastering belongs in `service`, not here).'],
                        'service' => ['type' => 'string'],
                        'address' => ['type' => 'string'],
                        'district' => ['type' => 'string'],
                        'alternate_phone' => ['type' => 'string'],
                        'location_hint' => ['type' => 'string', 'description' => 'Landmark, floor, gate, pin on map text — helps staff find the customer'],
                        'preferred_datetime_text' => ['type' => 'string', 'description' => 'ISO-8601 or clear datetime the customer agreed to (e.g. 2026-04-05 17:00)'],
                    ],
                ],
            ],
            [
                'name' => 'submit_my_booking_for_human_confirmation',
                'description' => 'Marks the active draft booking as submitted for human review. Do not claim the booking is fully confirmed.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'booking_id' => ['type' => 'string'],
                    ],
                ],
            ],
            [
                'name' => 'upsert_my_draft_provider_lead',
                'description' => 'Create or update a draft provider registration lead for this phone.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'lead_id' => ['type' => 'string'],
                        'name' => ['type' => 'string'],
                        'services' => ['type' => 'string'],
                        'address' => ['type' => 'string'],
                    ],
                ],
            ],
            [
                'name' => 'submit_my_provider_lead_for_human_confirmation',
                'description' => 'Submit provider lead for staff to verify and complete registration.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'lead_id' => ['type' => 'string'],
                    ],
                ],
            ],
            [
                'name' => 'request_human_support_handoff',
                'description' => 'Use when the customer wants a human. Returns whether we are inside configured support hours.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'topic' => ['type' => 'string'],
                    ],
                ],
            ],
        ];
    }

    private function getPublicBusinessInfo(): array
    {
        return ['ok' => true, 'data' => $this->catalog->buildPublicSnapshot()];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function searchSupportKnowledge(array $args): array
    {
        return $this->supportKnowledge->search((string) ($args['query'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function listMyBookingSummaries(string $phone, array $args): array
    {
        $limit = min(20, max(1, (int) ($args['limit'] ?? 8)));

        $rows = WhatsAppBooking::query()
            ->where('phone', $phone)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['booking_id', 'service', 'status', 'prefered_datetime', 'created_at']);

        $out = $rows->map(function (WhatsAppBooking $b) {
            return [
                'booking_id' => $b->booking_id,
                'service' => $b->service,
                'status' => $b->status,
                'preferred_at' => $b->prefered_datetime?->format('c'),
            ];
        })->values()->all();

        return ['ok' => true, 'bookings' => $out];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function getMyBookingDetails(string $phone, array $args): array
    {
        $bookingId = trim((string) ($args['booking_id'] ?? ''));
        if ($bookingId === '') {
            return ['ok' => false, 'error' => 'missing_booking_id'];
        }

        $b = WhatsAppBooking::query()->where('booking_id', $bookingId)->where('phone', $phone)->first();
        if (!$b) {
            return ['ok' => false, 'error' => 'not_found_or_denied'];
        }

        return [
            'ok' => true,
            'booking' => [
                'booking_id' => $b->booking_id,
                'name' => $b->name,
                'service' => $b->service,
                'address' => $b->address,
                'district' => $b->district,
                'alternate_phone' => $b->alt_phone,
                'location_hint' => $b->location_hint,
                'preferred_at' => $b->prefered_datetime?->format('c'),
                'status' => $b->status,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function upsertMyDraftBooking(string $phone, array $args): array
    {
        $bookingId = isset($args['booking_id']) ? trim((string) $args['booking_id']) : '';
        if ($bookingId !== '') {
            $existing = WhatsAppBooking::query()->where('booking_id', $bookingId)->where('phone', $phone)->first();
            if (!$existing) {
                return ['ok' => false, 'error' => 'invalid_booking_id'];
            }
            if ($existing->status !== null && $existing->status !== '' && $existing->status !== WhatsAppBooking::STATUS_DRAFT) {
                return ['ok' => false, 'error' => 'booking_not_editable'];
            }
        } else {
            $bookingId = 'PK-' . strtoupper(bin2hex(random_bytes(4)));
        }

        $booking = WhatsAppBooking::firstOrNew(['booking_id' => $bookingId]);
        if ($booking->exists && $booking->phone !== $phone) {
            return ['ok' => false, 'error' => 'forbidden'];
        }

        $booking->phone = $phone;

        $nameRejectedAsService = false;
        if (isset($args['name']) && $args['name'] !== null && trim((string) $args['name']) !== '') {
            $nameCandidate = trim((string) $args['name']);
            if (WhatsAppAiBookingNameHeuristics::looksLikeServiceNotPersonName($nameCandidate)) {
                $nameRejectedAsService = true;
            } else {
                $booking->name = Str::limit($nameCandidate, 255, '');
            }
        } elseif (isset($args['name'])) {
            $booking->name = $args['name'] === null ? null : Str::limit((string) $args['name'], 255, '');
        }
        if (isset($args['service'])) {
            $booking->service = $args['service'] === null ? null : Str::limit((string) $args['service'], 255, '');
        }
        if (isset($args['address'])) {
            $booking->address = $args['address'] === null ? null : (string) $args['address'];
        }
        if (isset($args['district'])) {
            $booking->district = $args['district'] === null ? null : Str::limit((string) $args['district'], 191, '');
        }
        if (isset($args['alternate_phone'])) {
            $booking->alt_phone = $args['alternate_phone'] === null ? null : Str::limit((string) $args['alternate_phone'], 50, '');
        }
        if (isset($args['location_hint'])) {
            $booking->location_hint = $args['location_hint'] === null ? null : Str::limit((string) $args['location_hint'], 500, '');
        }
        if (!empty($args['preferred_datetime_text'])) {
            try {
                $booking->prefered_datetime = Carbon::parse((string) $args['preferred_datetime_text']);
            } catch (\Throwable) {
                // keep previous / null
            }
        }

        $booking->status = WhatsAppBooking::STATUS_DRAFT;
        $booking->save();

        $this->leadLifecycle->ensureLeadTypeForPhone($phone, Lead::TYPE_CUSTOMER, $booking->name);

        $conv = WhatsAppConversation::firstOrNew(['phone' => $phone]);
        $conv->active_booking_id = $booking->booking_id;
        $conv->active_module = 'BOOK_SERVICE';
        $conv->current_step = null;
        $conv->save();

        if (!empty($args['name']) && ! $nameRejectedAsService) {
            $u = WhatsAppUser::firstOrNew(['phone' => $phone]);
            if (empty($u->name)) {
                $u->name = Str::limit((string) $args['name'], 255, '');
                $u->handled_by = $u->handled_by ?: 'AI';
                $u->save();
            }
        }

        $out = ['ok' => true, 'booking_id' => $booking->booking_id, 'status' => $booking->status];
        if ($nameRejectedAsService) {
            $out['name_rejected_likely_service'] = true;
            $out['assistant_hint'] = 'The customer probably repeated the work type (e.g. mistary/plastering) instead of a person name. Save that text in `service` (or align with get_public_business_info), and politely ask again for their real name — the name we should use for the booking / to address them.';
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function submitMyBookingForHuman(string $phone, array $args): array
    {
        $bookingId = isset($args['booking_id']) ? trim((string) $args['booking_id']) : '';
        $booking = $bookingId !== ''
            ? WhatsAppBooking::query()->where('booking_id', $bookingId)->where('phone', $phone)->first()
            : WhatsAppBooking::query()
                ->where('phone', $phone)
                ->where('status', WhatsAppBooking::STATUS_DRAFT)
                ->orderByDesc('updated_at')
                ->first();

        if (!$booking) {
            return ['ok' => false, 'error' => 'no_draft_booking'];
        }

        $missing = $this->bookingDraftMissingFields($booking);
        if ($missing !== []) {
            return [
                'ok' => false,
                'error' => 'incomplete_draft',
                'missing_fields' => $missing,
                'message' => 'Ask the customer for missing items, call upsert_my_draft_booking, then submit again.',
            ];
        }

        $booking->status = WhatsAppBooking::STATUS_TENTATIVE_PENDING_HUMAN;
        $booking->save();

        $conv = WhatsAppConversation::firstOrNew(['phone' => $phone]);
        $conv->current_step = 'COMPLETED';
        $conv->active_booking_id = $booking->booking_id;
        $conv->save();

        return [
            'ok' => true,
            'booking_id' => $booking->booking_id,
            'status' => $booking->status,
            'message' => 'Tell the customer our team will confirm this request; it is not final until staff confirms.',
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function upsertMyDraftProviderLead(string $phone, array $args): array
    {
        $leadId = isset($args['lead_id']) ? trim((string) $args['lead_id']) : '';
        if ($leadId !== '') {
            $existing = ProviderLead::query()->where('lead_id', $leadId)->where('phone', $phone)->first();
            if (!$existing) {
                return ['ok' => false, 'error' => 'invalid_lead_id'];
            }
            if ($existing->status !== null && $existing->status !== '' && $existing->status !== ProviderLead::STATUS_DRAFT) {
                return ['ok' => false, 'error' => 'lead_not_editable'];
            }
        } else {
            $leadId = 'PL-' . strtoupper(bin2hex(random_bytes(4)));
        }

        $lead = ProviderLead::firstOrNew(['lead_id' => $leadId]);
        if ($lead->exists && $lead->phone !== $phone) {
            return ['ok' => false, 'error' => 'forbidden'];
        }

        $lead->phone = $phone;
        if (isset($args['name'])) {
            $lead->name = $args['name'] === null ? null : Str::limit((string) $args['name'], 255, '');
        }
        if (isset($args['services'])) {
            $lead->service = $args['services'] === null ? null : (string) $args['services'];
        }
        if (isset($args['address'])) {
            $lead->address = $args['address'] === null ? null : (string) $args['address'];
        }
        $lead->status = ProviderLead::STATUS_DRAFT;
        $lead->save();

        $this->leadLifecycle->ensureLeadTypeForPhone($phone, Lead::TYPE_PROVIDER, $lead->name);

        $conv = WhatsAppConversation::firstOrNew(['phone' => $phone]);
        $conv->active_lead_id = $lead->lead_id;
        $conv->active_module = 'JOIN_PROVIDER';
        $conv->save();

        return ['ok' => true, 'lead_id' => $lead->lead_id, 'status' => $lead->status];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function submitMyProviderLeadForHuman(string $phone, array $args): array
    {
        $leadId = isset($args['lead_id']) ? trim((string) $args['lead_id']) : '';
        $lead = $leadId !== ''
            ? ProviderLead::query()->where('lead_id', $leadId)->where('phone', $phone)->first()
            : ProviderLead::query()
                ->where('phone', $phone)
                ->where('status', ProviderLead::STATUS_DRAFT)
                ->orderByDesc('created_at')
                ->first();

        if (!$lead) {
            return ['ok' => false, 'error' => 'no_draft_lead'];
        }

        $missing = [];
        if (trim((string) $lead->name) === '') {
            $missing[] = 'name';
        }
        if (trim((string) $lead->service) === '') {
            $missing[] = 'services';
        }
        if (trim((string) $lead->address) === '') {
            $missing[] = 'address';
        }
        if ($missing !== []) {
            return [
                'ok' => false,
                'error' => 'incomplete_draft',
                'missing_fields' => $missing,
                'message' => 'Collect missing provider details, call upsert_my_draft_provider_lead, then submit again.',
            ];
        }

        $lead->status = ProviderLead::STATUS_TENTATIVE_PENDING_HUMAN;
        $lead->save();

        $conv = WhatsAppConversation::firstOrNew(['phone' => $phone]);
        $conv->current_step = 'COMPLETED';
        $conv->active_lead_id = $lead->lead_id;
        $conv->save();

        return [
            'ok' => true,
            'lead_id' => $lead->lead_id,
            'status' => $lead->status,
            'message' => 'Tell the customer staff will complete registration after review.',
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function requestHumanHandoff(string $phone, array $args): array
    {
        WhatsAppUser::markHumanSupportRequested($phone);

        $inHours = $this->workHours->isWithinSupportHours();
        $schedule = $this->workHours->humanReadableSchedule();
        $displayPhone = (string) config('whatsappmodule.support_phone_display');

        return [
            'ok' => true,
            'within_support_hours' => $inHours,
            'schedule' => $schedule,
            'public_support_phone' => $displayPhone,
            'topic' => isset($args['topic']) ? (string) $args['topic'] : '',
        ];
    }

    /**
     * @return list<string>
     */
    private function bookingDraftMissingFields(WhatsAppBooking $booking): array
    {
        $missing = [];
        if (trim((string) $booking->name) === '') {
            $missing[] = 'name';
        }
        if (trim((string) $booking->service) === '') {
            $missing[] = 'service';
        }
        if (trim((string) $booking->address) === '') {
            $missing[] = 'address';
        }
        if (trim((string) $booking->district) === '') {
            $missing[] = 'district';
        }
        if ($booking->prefered_datetime === null) {
            $missing[] = 'preferred_datetime_text';
        }

        return $missing;
    }
}
