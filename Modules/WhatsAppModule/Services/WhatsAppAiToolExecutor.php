<?php

namespace Modules\WhatsAppModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Services\BookingReadableIdAllocator;
use Modules\LeadManagement\Entities\Lead;
use Modules\UserManagement\Entities\User;
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
        protected WhatsAppAiSupportKnowledgeService $supportKnowledge,
        protected WhatsAppZoneAddressMatcher $zoneAddressMatcher,
        protected WhatsAppServiceAreaChecker $serviceAreaChecker,
        protected WhatsAppAiSettingsService $aiSettings,
        protected WhatsAppAiContactProfileResolver $contactProfile,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function execute(string $name, array $args, string $phone): array
    {
        return match ($name) {
            'get_public_business_info' => $this->getPublicBusinessInfo(),
            'match_zone_from_address' => $this->matchZoneFromAddress($args),
            'search_support_knowledge' => $this->searchSupportKnowledge($args),
            'report_unclear_user_intent' => $this->reportUnclearUserIntent($phone, $args),
            'list_my_booking_summaries' => $this->listMyBookingSummaries($phone, $args),
            'get_my_booking_details' => $this->getMyBookingDetails($phone, $args),
            'get_booking_status_by_reference' => $this->getBookingStatusByReference($phone, $args),
            'list_my_system_bookings' => $this->listMySystemBookings($phone, $args),
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
                'description' => 'REQUIRED before you mention any rupee amount, visiting charge, support phone number, or support hours in your reply: call this tool in the same turn and use ONLY fields from the result (visiting_charge_note, customer_message_placeholders.phone, customer_message_placeholders.schedule, service_coverage_policy_note). Never invent prices, waivers, or contact details.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => new \stdClass,
                ],
            ],
            [
                'name' => 'match_zone_from_address',
                'description' => 'Server-side match of customer free-text address to one active zone using zone name + zone description (areas covered). Returns high confidence only when one zone clearly wins — otherwise low/none (leave zone empty; never ask the customer to pick a region).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'address' => ['type' => 'string', 'description' => 'Full address as the customer gave it'],
                    ],
                    'required' => ['address'],
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
                'name' => 'report_unclear_user_intent',
                'description' => 'Use ONLY when the last customer message is genuinely unintelligible (random characters, no discernible intent). Do NOT use for normal questions, “I don’t know” situations, missing data you can explain in text, or requests you could handle with tools. Do NOT use because you lack an answer — reply politely in text instead (apologize, suggest contacting support, ask if anything else you can help with). The server may ask you for ONE short clarifying question (Roman English/Hinglish) up to a limit; after that it sends a short closing message.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'brief_reason' => ['type' => 'string', 'description' => 'Very short internal note (e.g. random characters, no topic)'],
                    ],
                ],
            ],
            [
                'name' => 'list_my_booking_summaries',
                'description' => 'Lists WhatsApp booking *requests* saved for this number (reference id + status). For completed jobs in our main system, use list_my_system_bookings when the number matches a customer profile.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'integer', 'description' => 'Max rows, default 8'],
                    ],
                ],
            ],
            [
                'name' => 'get_my_booking_details',
                'description' => 'Returns one WhatsApp booking request row for this phone only (by its reference id).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'booking_id' => ['type' => 'string'],
                    ],
                    'required' => ['booking_id'],
                ],
            ],
            [
                'name' => 'get_booking_status_by_reference',
                'description' => 'Use when the customer asks for booking status and has given a booking id (e.g. PK07MAR26001). Looks up **first** in WhatsApp booking *requests* for this number; if not found, looks up a **system** booking by readable id for this number\'s customer profile. If they ask status without an id, ask once for the booking id before calling this.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'booking_reference' => ['type' => 'string', 'description' => 'The id the customer quoted (with or without #, any common spacing)'],
                    ],
                    'required' => ['booking_reference'],
                ],
            ],
            [
                'name' => 'list_my_system_bookings',
                'description' => 'Lists confirmed bookings in the main system for this WhatsApp number when it matches a customer profile (readable id, status, schedule, assigned provider name, bill total summary). Use after the customer asks about past jobs or invoices.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'integer', 'description' => 'Max rows, default 8'],
                    ],
                ],
            ],
            [
                'name' => 'upsert_my_draft_booking',
                'description' => 'Create or update a DRAFT booking for this customer (not confirmed until human confirms in admin).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'booking_id' => ['type' => 'string', 'description' => 'Existing draft id if continuing'],
                        'name' => ['type' => 'string', 'description' => 'Contact person for the booking — not job type (e.g. plastering belongs in `service`). If session context has `saved_name`, pass that value and do **not** ask the customer to confirm. Pass a new value only when the customer explicitly asks to change or correct their name.'],
                        'service' => ['type' => 'string'],
                        'service_description' => ['type' => 'string', 'description' => 'Extra job details for staff (symptoms, model, photos described in text) — maps to admin booking "service info"'],
                        'address' => ['type' => 'string', 'description' => 'Full service address. If session context lists a **default service address on file**, do **not** type it all again unless they chose a **different** address — use **use_saved_service_address** true after they confirm **same**, or paste the saved line here. For a **new** customer, collect the full address once.'],
                        'district' => ['type' => 'string', 'description' => 'Internal only: auto-filled from zone match when confident — do not ask the customer. Leave unset unless copying from match_zone_from_address high-confidence result.'],
                        'alternate_phone' => ['type' => 'string'],
                        'email' => ['type' => 'string', 'description' => 'Customer email when known or collected — stored for admin prefill and WhatsApp profile; do not re-ask if session context already lists Known email.'],
                        'use_saved_service_address' => ['type' => 'boolean', 'description' => 'Set true only after the customer confirms the visit is at the **saved** address in session context (same address on file). Server fills `address` from profile when the draft address is still empty.'],
                        'location_hint' => ['type' => 'string', 'description' => 'Landmark, floor, gate, pin on map text — helps staff find the customer'],
                        'preferred_datetime_text' => ['type' => 'string', 'description' => 'ISO-8601 or clear datetime the customer agreed to (e.g. 2026-04-05 17:00)'],
                        'zone_id' => ['type' => 'string', 'description' => 'Optional zone UUID from match_zone_from_address (high confidence) or service_hints — never ask the customer to choose a zone'],
                        'category_id' => ['type' => 'string', 'description' => 'Optional category UUID from service_hints when known'],
                        'sub_category_id' => ['type' => 'string', 'description' => 'Optional subcategory UUID from service_hints when known'],
                        'service_id' => ['type' => 'string', 'description' => 'Optional service UUID from service_hints when known'],
                        'variant_key' => ['type' => 'string', 'description' => 'Optional variant key from service_hints when known'],
                    ],
                ],
            ],
            [
                'name' => 'submit_my_booking_for_human_confirmation',
                'description' => 'Marks the active draft booking as submitted for human review. The response includes **booking_id** — your next customer message MUST quote that exact id (see assistant_instruction). Do not claim the job is fully confirmed until staff confirms.',
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
                'description' => 'Use ONLY when the customer clearly asks to speak to a human, agent, representative, or real person. Do NOT use when you do not understand them, lack information, or cannot answer — reply in normal text first (sorry, suggest support contact, ask if anything else you can help with). The server sends the full handoff message (hours + phone from templates). Do not add another paragraph with hours or phone.',
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
        $data = $this->catalog->buildPublicSnapshot();
        $data['customer_message_placeholders'] = $this->aiSettings->resolvedMessagePlaceholders();

        return ['ok' => true, 'data' => $data];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function matchZoneFromAddress(array $args): array
    {
        $addr = trim((string) ($args['address'] ?? ''));
        if ($addr === '') {
            return ['ok' => false, 'error' => 'missing_address'];
        }

        $match = $this->zoneAddressMatcher->match($addr);
        $area = $this->serviceAreaChecker->assess($addr, $match);

        return [
            'ok' => true,
            'match' => $match,
            'service_area' => $area,
            'assistant_hint' => (($area['in_service_area'] ?? true) ? null : 'This address does not appear to be in our Jammu & Kashmir service area. Do not use upsert_my_draft_booking to save it; explain we currently serve only Kashmir / J&K and ask for a local address if they are in the region.'),
        ];
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
     * Tracks unclear messages; after max clarify rounds, signals orchestrator to send handoff text.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function reportUnclearUserIntent(string $phone, array $args): array
    {
        $maxClarify = (int) config('whatsappmodule.ai_unclear_max_clarify_rounds', 2);
        if ($maxClarify < 1) {
            $maxClarify = 1;
        }

        $conv = WhatsAppConversation::firstOrNew(['phone' => $phone]);
        $n = (int) ($conv->ai_unclear_attempts ?? 0);
        $n++;
        $conv->ai_unclear_attempts = $n;
        $conv->save();

        $brief = trim((string) ($args['brief_reason'] ?? ''));

        if ($n <= $maxClarify) {
            return [
                'ok' => true,
                'action' => 'clarify',
                'attempt' => $n,
                'max_clarify_rounds' => $maxClarify,
                'brief_reason_echo' => $brief,
                'assistant_instruction' => 'Reply with exactly ONE short, polite message in English or Roman Hinglish only (Roman letters — no Devanagari, no Arabic script, never Kashmiri). Ask one clarifying question — what they need (booking, job status, provider signup, etc.). Use Please / Thanks in English spelling. Do not call request_human_support_handoff. Do not call this tool again until the customer sends their next message.',
            ];
        }

        WhatsAppUser::markHumanSupportRequested($phone);

        return [
            'ok' => true,
            'action' => 'unclear_handoff',
            'attempt' => $n,
            'max_clarify_rounds' => $maxClarify,
            'brief_reason_echo' => $brief,
            'assistant_instruction' => 'Do not send any further questions or call request_human_support_handoff. The system will send a short closing message (apologize, suggest support contact, ask if anything else you can help with).',
            'orchestrator_finalize' => [
                'send_unclear_handoff_message' => true,
            ],
        ];
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
                'preferred_at' => $this->toIso8601($b->prefered_datetime),
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
                'service_description' => $b->service_description,
                'address' => $b->address,
                'district' => $b->district,
                'alternate_phone' => $b->alt_phone,
                'location_hint' => $b->location_hint,
                'preferred_at' => $this->toIso8601($b->prefered_datetime),
                'status' => $b->status,
            ],
        ];
    }

    /**
     * Status lookup: WhatsApp booking request (this phone) first, then main Booking by readable_id.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function getBookingStatusByReference(string $phone, array $args): array
    {
        $ref = $this->normalizeBookingReference((string) ($args['booking_reference'] ?? ''));
        if ($ref === '') {
            return ['ok' => false, 'error' => 'missing_booking_reference'];
        }

        $wa = WhatsAppBooking::query()
            ->where('phone', $phone)
            ->whereRaw('LOWER(TRIM(booking_id)) = LOWER(?)', [$ref])
            ->first();

        if ($wa) {
            $linked = trim((string) ($wa->system_booking_id ?? ''));

            return [
                'ok' => true,
                'lookup_source' => 'whatsapp_booking_request',
                'booking_reference' => $wa->booking_id,
                'status_code' => $wa->status,
                'status_meaning' => $this->whatsappBookingStatusMeaning((string) ($wa->status ?? '')),
                'service' => $wa->service,
                'preferred_at' => $this->toIso8601($wa->prefered_datetime),
                'linked_system_booking_id' => $linked !== '' ? $linked : null,
                'assistant_instruction' => $linked !== ''
                    ? 'Explain this is their WhatsApp request; status above. A system booking is linked — you may mention the team can see it in the main system.'
                    : 'Explain this is their WhatsApp booking request and what the status means; if still with team for confirmation, say so clearly.',
            ];
        }

        $customer = User::findByContactPhone($phone);
        if (!$customer || $customer->user_type !== 'customer') {
            return [
                'ok' => false,
                'error' => 'not_found',
                'message' => 'No request with this id on WhatsApp for this number, and no customer profile is linked to check system bookings.',
                'assistant_instruction' => 'Say the id was not found for this number. Suggest they double-check the id or that they may have used another phone when booking.',
            ];
        }

        $sys = Booking::query()
            ->where('customer_id', $customer->id)
            ->whereRaw('LOWER(TRIM(readable_id)) = LOWER(?)', [$ref])
            ->with(['provider'])
            ->first();

        if (!$sys) {
            return [
                'ok' => false,
                'error' => 'not_found',
                'message' => 'Not found in WhatsApp requests or in system bookings for this number.',
                'assistant_instruction' => 'Politely say we could not find this booking id for their number. Ask them to confirm the id from their message or call support if needed.',
            ];
        }

        $providerLabel = trim((string) ($sys->provider?->company_name ?? ''));
        if ($providerLabel === '') {
            $providerLabel = trim((string) ($sys->provider?->contact_person_name ?? ''));
        }

        return [
            'ok' => true,
            'lookup_source' => 'system_booking',
            'booking_reference' => $sys->readable_id,
            'status_code' => $sys->booking_status,
            'service_schedule' => $this->toIso8601($sys->service_schedule),
            'provider_name' => $providerLabel !== '' ? $providerLabel : null,
            'payment_method' => $sys->payment_method,
            'is_paid' => (bool) $sys->is_paid,
            'total_service_amount' => $sys->total_booking_amount,
            'extra_fee' => $sys->extra_fee,
            'assistant_instruction' => 'Summarize status and next steps in the customer\'s language. Do not share internal commission or staff-only fields.',
        ];
    }

    private function normalizeBookingReference(string $raw): string
    {
        $s = trim($raw);
        $s = ltrim($s, '#');
        $s = preg_replace('/\s+/', '', $s) ?? $s;

        return trim($s);
    }

    private function whatsappBookingStatusMeaning(string $status): string
    {
        return match (strtoupper(trim($status))) {
            WhatsAppBooking::STATUS_DRAFT => 'Draft — details still being collected or not yet submitted.',
            WhatsAppBooking::STATUS_TENTATIVE_PENDING_HUMAN => 'Submitted — waiting for our team to confirm.',
            WhatsAppBooking::STATUS_HUMAN_CONFIRMED => 'Handled by team (confirmed or converted in system as applicable).',
            WhatsAppBooking::STATUS_CANCELLED => 'Cancelled.',
            default => 'Status: '.$status,
        };
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function listMySystemBookings(string $phone, array $args): array
    {
        $limit = min(20, max(1, (int) ($args['limit'] ?? 8)));

        $customer = User::findByContactPhone($phone);
        if (!$customer) {
            return [
                'ok' => true,
                'customer_linked' => false,
                'bookings' => [],
                'hint' => 'No customer account matches this WhatsApp number yet — use list_my_booking_summaries for requests saved here.',
            ];
        }

        $rows = Booking::query()
            ->where('customer_id', $customer->id)
            ->with(['provider'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $bookings = $rows->map(function (Booking $b) {
            $providerLabel = trim((string) ($b->provider?->company_name ?? ''));
            if ($providerLabel === '') {
                $providerLabel = trim((string) ($b->provider?->contact_person_name ?? ''));
            }

            return [
                'readable_id' => $b->readable_id,
                'status' => $b->booking_status,
                'service_schedule' => $this->toIso8601($b->service_schedule),
                'provider_name' => $providerLabel !== '' ? $providerLabel : null,
                'total_service_amount' => $b->total_booking_amount,
                'extra_fee' => $b->extra_fee,
                'payment_method' => $b->payment_method,
                'is_paid' => (bool) $b->is_paid,
                'invoice_note' => 'Detailed invoice PDF is shared by our team on request or via booking updates — share booking id when you speak to them.',
            ];
        })->values()->all();

        return [
            'ok' => true,
            'customer_linked' => true,
            'bookings' => $bookings,
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
                return [
                    'ok' => false,
                    'error' => 'booking_not_editable',
                    'booking_id' => $existing->booking_id,
                    'current_status' => $existing->status,
                    'assistant_instruction' => 'This WhatsApp booking request is no longer in draft (already submitted or processed). You cannot change date/time/address via tools. Tell the customer to call or WhatsApp our support team using get_public_business_info for the number and hours — staff will reschedule or update the request. Do not promise you can edit it yourself.',
                ];
            }
        } else {
            try {
                $bookingId = BookingReadableIdAllocator::allocateNext();
            } catch (\Throwable) {
                $bookingId = 'PK-' . strtoupper(bin2hex(random_bytes(4)));
            }
        }

        $booking = WhatsAppBooking::firstOrNew(['booking_id' => $bookingId]);
        if ($booking->exists && $booking->phone !== $phone) {
            return ['ok' => false, 'error' => 'forbidden'];
        }

        $booking->phone = $phone;

        $this->contactProfile->prefillDraftBookingFromKnownProfile($phone, $booking, $args);

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

        if (trim((string) ($booking->name ?? '')) === '' && (!array_key_exists('name', $args) || $args['name'] !== null)) {
            $profile = WhatsAppUser::query()->where('phone', $phone)->first();
            if ($profile && trim((string) $profile->name) !== '') {
                $booking->name = Str::limit(trim((string) $profile->name), 255, '');
            }
        }

        if (isset($args['service'])) {
            $booking->service = $args['service'] === null ? null : Str::limit((string) $args['service'], 255, '');
        }
        if (isset($args['service_description'])) {
            $booking->service_description = $args['service_description'] === null ? null : Str::limit((string) $args['service_description'], 2000, '');
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
        if (isset($args['email']) && $args['email'] !== null && trim((string) $args['email']) !== '') {
            $em = Str::lower(trim((string) $args['email']));
            if (filter_var($em, FILTER_VALIDATE_EMAIL)) {
                $prefill = is_array($booking->admin_prefill_json) ? $booking->admin_prefill_json : [];
                $prefill['contact_email'] = Str::limit($em, 191, '');
                $booking->admin_prefill_json = $prefill;
            }
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

        $prefill = is_array($booking->admin_prefill_json) ? $booking->admin_prefill_json : [];
        if (! isset($args['email']) && empty($prefill['contact_email'])) {
            $snap = $this->contactProfile->snapshot($phone);
            $em = Str::lower(trim((string) ($snap['merged']['email'] ?? '')));
            if ($em !== '' && filter_var($em, FILTER_VALIDATE_EMAIL)) {
                $prefill['contact_email'] = Str::limit($em, 191, '');
            }
        }
        foreach (['zone_id', 'category_id', 'sub_category_id', 'service_id', 'variant_key'] as $pk) {
            if (!array_key_exists($pk, $args)) {
                continue;
            }
            $v = $args[$pk];
            if ($v === null || trim((string) $v) === '') {
                unset($prefill[$pk]);
                continue;
            }
            $prefill[$pk] = Str::limit(trim((string) $v), 191, '');
        }

        $districtExplicit = array_key_exists('district', $args) && $args['district'] !== null && trim((string) $args['district']) !== '';
        $zoneIdExplicit = array_key_exists('zone_id', $args) && $args['zone_id'] !== null && trim((string) $args['zone_id']) !== '';
        $addressNow = trim((string) ($booking->address ?? ''));
        if (!$districtExplicit && !$zoneIdExplicit && $addressNow !== '') {
            $zm = $this->zoneAddressMatcher->match($addressNow);
            if (($zm['confidence'] ?? '') === 'high' && !empty($zm['zone_id'])) {
                $dLabel = trim((string) ($zm['district_for_booking_row'] ?? $zm['zone_name'] ?? ''));
                if ($dLabel !== '') {
                    $booking->district = Str::limit($dLabel, 191, '');
                }
                if (empty($prefill['zone_id'])) {
                    $prefill['zone_id'] = Str::limit((string) $zm['zone_id'], 191, '');
                }
            }
        }

        $booking->admin_prefill_json = $prefill !== [] ? $prefill : null;

        $finalAddress = trim((string) ($booking->address ?? ''));
        if ($finalAddress !== '') {
            $zmFinal = $this->zoneAddressMatcher->match($finalAddress);
            $areaCheck = $this->serviceAreaChecker->assess($finalAddress, $zmFinal);
            if (!($areaCheck['in_service_area'] ?? true)) {
                return [
                    'ok' => false,
                    'error' => 'outside_service_area',
                    'service_area' => $areaCheck,
                    'match' => $zmFinal,
                    'assistant_instruction' => 'Do not save this booking. Tell the customer clearly that we currently serve only Jammu & Kashmir (Kashmir region operations). Do not call upsert_my_draft_booking again with this address. If they are local, ask for a full address that matches our coverage (or a landmark in Kashmir). Use get_public_business_info only if they ask how to reach support — never invent phone or hours.',
                ];
            }
        }

        $booking->status = WhatsAppBooking::STATUS_DRAFT;
        $booking->save();

        $crmLead = $this->leadLifecycle->ensureLeadTypeForPhone($phone, Lead::TYPE_CUSTOMER, $booking->name);
        if ($crmLead) {
            $booking->lead_id = $crmLead->id;
            $booking->save();
        }

        $conv = WhatsAppConversation::firstOrNew(['phone' => $phone]);
        $conv->active_booking_id = $booking->booking_id;
        $conv->active_module = 'BOOK_SERVICE';
        $conv->current_step = null;
        $conv->save();

        $this->contactProfile->persistAfterDraftUpsert($phone, $booking, $args);

        $out = ['ok' => true, 'booking_id' => $booking->booking_id, 'status' => $booking->status];
        if ($nameRejectedAsService) {
            $out['name_rejected_likely_service'] = true;
            $out['assistant_hint'] = 'The customer probably repeated the work type (e.g. mistary/plastering) instead of a person name. Save that text in `service` (or align with get_public_business_info), and politely ask again for their real name — the name we should use for the booking / to address them.';
        }

        $missingAfter = $this->bookingDraftMissingFields($booking);
        if ($missingAfter === [] && !$nameRejectedAsService) {
            $out['assistant_instruction'] = 'Booking draft is complete. Before calling submit_my_booking_for_human_confirmation, send one customer message in their language. '
                .'Opening: short acknowledgment that details are updated/saved + ask if they want to confirm this booking request — do NOT cram the full time or full address into this opening. '
                .'Then a blank line, then exactly these three lines (use => as shown):'."\n"
                ."Service => …\n"
                ."Time => …\n"
                ."Address => …\n"
                .'Then a line like "please confirm karo" (or a natural equivalent in their language). Keep Service/Time/Address on separate lines; do not fold them into one paragraph.';
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

        $addrSubmit = trim((string) ($booking->address ?? ''));
        if ($addrSubmit !== '') {
            $zmSubmit = $this->zoneAddressMatcher->match($addrSubmit);
            $areaSubmit = $this->serviceAreaChecker->assess($addrSubmit, $zmSubmit);
            if (!($areaSubmit['in_service_area'] ?? true)) {
                return [
                    'ok' => false,
                    'error' => 'outside_service_area',
                    'service_area' => $areaSubmit,
                    'match' => $zmSubmit,
                    'assistant_instruction' => 'Cannot submit: address appears outside our Jammu & Kashmir service area. Ask for a valid local address or explain we only operate in Kashmir / J&K. Clear the wrong address with upsert_my_draft_booking (correct address) before submitting.',
                ];
            }
        }

        $crmLead = $this->leadLifecycle->ensureLeadTypeForPhone($phone, Lead::TYPE_CUSTOMER, $booking->name);
        if ($crmLead && $booking->lead_id === null) {
            $booking->lead_id = $crmLead->id;
        }

        $booking->status = WhatsAppBooking::STATUS_TENTATIVE_PENDING_HUMAN;
        $booking->save();

        $conv = WhatsAppConversation::firstOrNew(['phone' => $phone]);
        $conv->current_step = 'COMPLETED';
        $conv->active_booking_id = $booking->booking_id;
        $conv->save();

        $bid = (string) $booking->booking_id;

        return [
            'ok' => true,
            'booking_id' => $bid,
            'status' => $booking->status,
            'message' => 'Your reply MUST include this exact booking request id so the customer can save it: '.$bid.'. Also say our team will review and confirm; the request is not final until staff confirms.',
            'assistant_instruction' => 'In your next customer-visible message you MUST include the booking request id exactly as returned in booking_id ('.$bid.'). Use WhatsApp bold for the label, e.g. a line like: *Booking request ID:* '.$bid.' — then briefly repeat that staff will confirm and it is pending until then.',
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

        $leadAddr = trim((string) ($lead->address ?? ''));
        if ($leadAddr !== '') {
            $zmLead = $this->zoneAddressMatcher->match($leadAddr);
            $areaLead = $this->serviceAreaChecker->assess($leadAddr, $zmLead);
            if (!($areaLead['in_service_area'] ?? true)) {
                return [
                    'ok' => false,
                    'error' => 'outside_service_area',
                    'service_area' => $areaLead,
                    'match' => $zmLead,
                    'assistant_instruction' => 'Do not save this provider lead with this address. Explain we currently onboard providers for Jammu & Kashmir only. Ask for a local address or use get_public_business_info if they need support contact — never invent phone or hours.',
                ];
            }
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
        $p = $this->aiSettings->resolvedMessagePlaceholders();
        $customerText = $this->aiSettings->handoffMessageForCustomer($inHours);
        $meta = $this->aiSettings->metaButtonsForContext($inHours ? 'handoff_in' : 'handoff_out');
        $finalize = [
            'send_exact_customer_text' => $customerText,
        ];
        if ($meta !== []) {
            $finalize['session_meta_buttons'] = $meta;
        }

        return [
            'ok' => true,
            'within_support_hours' => $inHours,
            'schedule' => $p['schedule'],
            'public_support_phone' => $p['phone'],
            'topic' => isset($args['topic']) ? (string) $args['topic'] : '',
            'assistant_instruction' => 'The customer-visible message is sent by the server. Do not send another reply about hours or phone in this turn.',
            'orchestrator_finalize' => $finalize,
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
        if ($booking->prefered_datetime === null) {
            $missing[] = 'preferred_datetime_text';
        }

        return $missing;
    }

    /**
     * ISO-8601 for tool payloads. `Booking::service_schedule` is a string column (no datetime cast).
     *
     * @param  \DateTimeInterface|string|null  $value
     */
    private function toIso8601(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('c');
        }
        try {
            return Carbon::parse((string) $value)->format('c');
        } catch (\Throwable) {
            return null;
        }
    }
}
