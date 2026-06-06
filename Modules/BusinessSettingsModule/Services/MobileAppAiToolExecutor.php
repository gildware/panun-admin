<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\BusinessSettingsModule\Entities\MobileAppAiMessage;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Services\WhatsAppAiToolExecutor;

/**
 * Gemini tools for customer mobile app AI.
 */
class MobileAppAiToolExecutor
{
    public function __construct(
        protected WhatsAppAiToolExecutor $whatsappTools,
        protected MobileAppAiCartService $cartService,
        protected MobileAppAiCatalogSearchService $catalogSearch,
        protected MobileAppAiBookingSessionService $bookingSession,
        protected MobileAppAiCustomerBookingService $customerBookings,
        protected MobileAppAiSupportKnowledgeService $supportKnowledge,
        protected MobileAppAiHandoffService $handoff,
        protected MobileAppAiSupportToolPolicy $policy,
        protected MobileAppAiCustomerSnapshotService $customerSnapshot,
        protected MobileAppAiCartToolService $cartTool,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function execute(string $name, array $args, User $user, string $phone): array
    {
        if (! $this->policy->isAllowed($name)) {
            return ['ok' => false, 'customer_message' => 'That action is not available right now.'];
        }

        return match ($name) {
            'manage_app_booking' => $this->sanitizeBookingToolResult(
                $this->bookingSession->handle($user, $args)
            ),
            'search_catalog_services' => $this->customerServiceBrowse(
                $this->catalogSearch->searchServices(
                    (string) ($args['query'] ?? ''),
                    (int) ($args['limit'] ?? 40),
                    isset($args['category_id']) ? (string) $args['category_id'] : null,
                    isset($args['sub_category_id']) ? (string) $args['sub_category_id'] : null,
                )
            ),
            'list_full_service_catalog' => $this->customerServiceBrowse(
                $this->catalogSearch->listFullCatalog(
                    (int) ($args['offset'] ?? 0),
                    (int) ($args['limit'] ?? 50),
                    isset($args['category_id']) ? (string) $args['category_id'] : null,
                )
            ),
            'list_service_categories' => $this->customerCategoryBrowse(
                $this->catalogSearch->listCategories((int) ($args['limit'] ?? 50))
            ),
            'list_service_areas' => $this->customerAreasBrowse(
                $this->catalogSearch->listServiceAreas((int) ($args['limit'] ?? 40))
            ),
            'get_customer_cart_summary' => $this->customerCartSummary($this->cartService->cartSummaryForUser($user), $user),
            'get_customer_account_snapshot' => $this->customerAccountSnapshot($user),
            'manage_customer_cart' => $this->cartTool->handle($user, $args),
            'list_my_saved_addresses' => $this->customerSavedAddresses($this->catalogSearch->listCustomerAddresses($user)),
            'search_support_knowledge' => $this->customerSupportKnowledge(
                $this->supportKnowledge->search((string) ($args['query'] ?? ''))
            ),
            'list_my_system_bookings' => $this->customerBookings->listForUser($user, $args),
            'get_booking_status_by_reference' => $this->customerBookings->statusByReference($user, $args),
            'request_human_support_handoff' => $this->handoff->buildHandoffResult(
                isset($args['topic']) ? (string) $args['topic'] : null
            ),
            'report_unclear_user_intent' => $this->mobileReportUnclear($user),
            default => $this->sanitizeWhatsAppResult($this->whatsappTools->execute($name, $args, $phone)),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function mobileReportUnclear(User $user): array
    {
        $lastText = $this->lastUserMessageText($user);
        if ($lastText !== null && MobileAppAiBookingMessageDetector::looksLikeBulkBookingDetails($lastText)) {
            $result = $this->bookingSession->handle($user, [
                'action' => 'apply',
                'message' => $lastText,
            ]);

            return array_merge($result, [
                '_instruction' => 'Relay customer_message only. The customer is booking — do not say you did not understand.',
            ]);
        }

        return [
            'ok' => true,
            'customer_message' => $this->customerSnapshot->buildFallbackHint($user),
            '_instruction' => 'Relay customer_message. Do not call report_unclear again for normal booking questions.',
        ];
    }

    private function lastUserMessageText(User $user): ?string
    {
        $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
        if (! $conversation) {
            return null;
        }

        $row = MobileAppAiMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('source', MobileAppAiMessage::SOURCE_MOBILE_APP)
            ->where('role', 'user')
            ->orderByDesc('id')
            ->first();

        $body = trim((string) ($row?->body ?? ''));

        return $body !== '' ? $body : null;
    }

    /**
     * @param  array<string, mixed>  $knowledge
     * @return array<string, mixed>
     */
    private function customerSupportKnowledge(array $knowledge): array
    {
        if (! ($knowledge['ok'] ?? false)) {
            return ['ok' => false, 'customer_message' => 'I could not load help articles right now. Try Help & Support in the menu.'];
        }

        $parts = [];
        foreach ($knowledge['troubleshooting'] ?? [] as $pack) {
            $title = (string) ($pack['topic'] ?? 'Steps');
            $steps = $pack['steps'] ?? [];
            if (is_array($steps) && $steps !== []) {
                $parts[] = "**{$title}**\n".implode("\n", array_map(
                    static fn ($s, $i) => ($i + 1).'. '.$s,
                    $steps,
                    array_keys($steps)
                ));
            }
        }
        foreach ($knowledge['faqs'] ?? [] as $faq) {
            $q = (string) ($faq['question'] ?? '');
            $a = (string) ($faq['answer'] ?? '');
            if ($q !== '' && $a !== '') {
                $parts[] = "**{$q}**\n{$a}";
            }
        }
        if ($parts === []) {
            foreach ($knowledge['general_tips'] ?? [] as $tip) {
                $parts[] = '• '.(string) $tip;
            }
        }

        $msg = $parts === []
            ? 'Tell me a bit more about the issue (payment, cart, login, address, or a booking) and I will guide you.'
            : implode("\n\n", array_slice($parts, 0, 4));

        return [
            'ok' => true,
            'customer_message' => $msg,
            '_instruction' => 'Relay help in friendly short form. No internal ids.',
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function customerServiceBrowse(array $result): array
    {
        if (! ($result['ok'] ?? false)) {
            return ['ok' => false, 'customer_message' => 'I could not load services right now. Please try again.'];
        }

        $lines = [];
        foreach ($result['selectable_options'] ?? [] as $row) {
            $display = (string) ($row['display'] ?? '');
            if ($display !== '') {
                $lines[] = preg_replace('/^\d+\.\s*/', '', $display) !== $display
                    ? $display
                    : ((string) ($row['option'] ?? '')).'. '.(string) ($row['name'] ?? $display);
            }
        }

        if ($lines === []) {
            foreach ($result['catalog_by_category'] ?? [] as $group) {
                $cat = (string) ($group['category_name'] ?? '');
                foreach ($group['services'] ?? [] as $s) {
                    $lines[] = '• '.(string) ($s['name'] ?? '');
                    if ($cat !== '') {
                        $lines[count($lines) - 1] .= ' ('.$cat.')';
                    }
                }
            }
        }

        $count = (int) ($result['count'] ?? count($lines));
        $hasMore = ($result['has_more'] ?? false) === true;

        $msg = $count === 0
            ? 'I did not find matching services. Tell me what you need and I can help you book it.'
            : "Here are some services we offer:\n\n".implode("\n", array_slice($lines, 0, 20))
            .($count > 20 ? "\n\n…and more." : '')
            .($hasMore ? "\n\nAsk for more if you need another page." : '')
            ."\n\nTo **book**, tell me what you need (e.g. AC repair) and I'll guide you step by step.";

        return [
            'ok' => true,
            'customer_message' => $msg,
            '_instruction' => 'Relay in friendly words. No ids. To book use manage_app_booking action=start.',
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function customerCategoryBrowse(array $result): array
    {
        if (! ($result['ok'] ?? false)) {
            return ['ok' => false, 'customer_message' => 'Could not load categories.'];
        }

        $lines = [];
        foreach ($result['categories'] ?? [] as $c) {
            $n = (int) ($c['active_service_count'] ?? 0);
            if ($n > 0) {
                $lines[] = '• '.(string) ($c['name'] ?? '').' ('.$n.' services)';
            }
        }

        return [
            'ok' => true,
            'customer_message' => "Our main categories:\n\n".implode("\n", $lines)
                ."\n\nTell me which type of service you need and I'll help you book.",
            '_instruction' => 'Names only — no category ids.',
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function customerAreasBrowse(array $result): array
    {
        if (! ($result['ok'] ?? false)) {
            return ['ok' => false, 'customer_message' => 'Could not load service areas.'];
        }

        $lines = [];
        foreach ($result['zones'] ?? [] as $z) {
            $name = (string) ($z['name'] ?? '');
            $areas = trim((string) ($z['areas_covered'] ?? ''));
            if ($name === '') {
                continue;
            }
            $lines[] = '• **'.$name.'**'.($areas !== '' ? ' — '.$this->shorten($areas, 200) : '');
        }

        return [
            'ok' => true,
            'customer_message' => "We provide services in these areas:\n\n".implode("\n\n", $lines),
            '_instruction' => 'Area names and descriptions only — never zone_id.',
        ];
    }

    private function shorten(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max).'…';
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    private function customerAccountSnapshot(User $user): array
    {
        $snap = $this->customerSnapshot->build($user);

        return [
            'ok' => true,
            'customer_message' => $this->customerSnapshot->promptBlockForUser($user),
            'cart_count' => $snap['cart_count'] ?? 0,
            'cart_total' => $snap['cart_total'] ?? 0,
            'booking_count' => $snap['booking_count'] ?? 0,
            '_instruction' => 'Use this live data. Summarize briefly for the customer — no internal ids.',
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function customerSavedAddresses(array $result): array
    {
        if (! ($result['ok'] ?? false) || (int) ($result['count'] ?? 0) === 0) {
            return [
                'ok' => true,
                'customer_message' => (string) ($result['new_address_hint'] ?? 'No saved addresses yet. Add one from **Home → location bar → Add new address**.'),
            ];
        }

        $lines = [];
        foreach ($result['selectable_options'] ?? [] as $opt) {
            if (! is_array($opt)) {
                continue;
            }
            $lines[] = (string) ($opt['display'] ?? '');
        }

        return [
            'ok' => true,
            'customer_message' => "Your saved addresses:\n\n".implode("\n", array_filter($lines)),
            '_instruction' => 'Show addresses only — no numeric ids to customer.',
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function customerCartSummary(array $result, User $user): array
    {
        $userText = (string) ($this->lastUserMessageText($user) ?? '');

        return array_merge(
            MobileAppAiCartUiPresenter::buildSummaryResponse($result, $userText),
            ['_instruction' => 'Relay customer_message in customer language. Cart line cards are shown in the app.']
        );
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function sanitizeWhatsAppResult(array $result): array
    {
        if (isset($result['customer_message'])) {
            return $result;
        }

        if (($result['ok'] ?? false) && isset($result['data']) && is_array($result['data'])) {
            return $result;
        }

        $result['_instruction'] = ($result['_instruction'] ?? '')
            .' Summarize for the customer without ids or raw JSON.';

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function functionDeclarations(): array
    {
        return [
            [
                'name' => 'manage_app_booking',
                'description' => 'Booking wizard tool. Server returns customer_message + buttons/cards in the app UI. Use for all booking — never guess service ids. Triage only when customer describes a problem (leak, not cooling); if they want booking (service chahiye, karwani hai) use confirm_service or proceed_booking. Actions: start, search, apply, pick, time, confirm, confirm_service, proceed_booking, cancel, status.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'action' => ['type' => 'string', 'description' => 'start | search | apply | pick | time | confirm | confirm_service | proceed_booking | cancel | status'],
                        'query' => ['type' => 'string', 'description' => 'For search — what they need'],
                        'message' => ['type' => 'string', 'description' => 'For apply — full customer message if fields are combined in one sentence'],
                        'service' => ['type' => 'string', 'description' => 'For apply — service name or need (e.g. AC repair, electrician)'],
                        'when' => ['type' => 'string', 'description' => 'For apply/time — date/time text or ASAP'],
                        'address' => ['type' => 'string', 'description' => 'For apply — saved address text to match'],
                        'variation' => ['type' => 'string', 'description' => 'For apply — service type/variation if mentioned'],
                        'provider' => ['type' => 'string', 'description' => 'For apply — provider preference or leave empty for auto-assign'],
                        'choice' => ['type' => 'string', 'description' => 'For pick — selection from cards'],
                        'asap' => ['type' => 'boolean', 'description' => 'For time/apply — earliest visit'],
                        'confirm' => ['type' => 'boolean', 'description' => 'For apply — add to cart immediately when true and all fields resolved'],
                    ],
                    'required' => ['action'],
                ],
            ],
            [
                'name' => 'get_public_business_info',
                'description' => 'Official phone, support hours, service coverage, visiting_charge_note. Use visiting_charge_note only when the customer asked about price/cost/charges. Call before quoting contact details or any rupee amount.',
                'parameters' => ['type' => 'object', 'properties' => new \stdClass],
            ],
            [
                'name' => 'list_service_categories',
                'description' => 'Category names for browsing (not booking). No ids in reply.',
                'parameters' => ['type' => 'object', 'properties' => ['limit' => ['type' => 'integer']]],
            ],
            [
                'name' => 'list_full_service_catalog',
                'description' => 'Browse service names (not booking). Customer-friendly list only.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'offset' => ['type' => 'integer'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'search_catalog_services',
                'description' => 'Search services by keyword for browsing. To book use manage_app_booking.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => ['query' => ['type' => 'string'], 'limit' => ['type' => 'integer']],
                ],
            ],
            [
                'name' => 'list_service_areas',
                'description' => 'Where we provide services — area names only.',
                'parameters' => ['type' => 'object', 'properties' => ['limit' => ['type' => 'integer']]],
            ],
            [
                'name' => 'match_zone_from_address',
                'description' => 'Check if an address is in service area. Plain yes/no to customer only.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => ['address' => ['type' => 'string']],
                    'required' => ['address'],
                ],
            ],
            [
                'name' => 'get_customer_cart_summary',
                'description' => 'Items in the customer cart — names, visit time, address snippet, totals.',
                'parameters' => ['type' => 'object', 'properties' => new \stdClass],
            ],
            [
                'name' => 'get_customer_account_snapshot',
                'description' => 'Full live snapshot: cart lines, recent bookings, saved addresses for this logged-in customer.',
                'parameters' => ['type' => 'object', 'properties' => new \stdClass],
            ],
            [
                'name' => 'manage_customer_cart',
                'description' => 'Cart agent for logged-in customer. YOU understand Hinglish/English; pass structured op + ids/filter when possible. Server confirms destructive changes (shows Yes/Cancel buttons). Call action=view or get_customer_cart_summary to read cart. For remove/clear/reschedule: call WITHOUT confirmed first; after yes/button, call confirmed=true with same op/message.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'action' => ['type' => 'string', 'description' => 'view | clear_all | remove | keep_only | keep_one | reschedule | confirm_pending | cancel_pending'],
                        'op' => ['type' => 'string', 'description' => 'Same as action — remove | keep_only | keep_one | clear_all | reschedule | view'],
                        'message' => ['type' => 'string', 'description' => 'Customer original words (always pass for language context)'],
                        'cart_line_ids' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Exact line ids from session cart catalog when known'],
                        'cart_filter' => ['type' => 'string', 'description' => 'visit_before_now | visit_after_now | no_schedule'],
                        'remove_target' => ['type' => 'string', 'description' => 'Service scope to remove, e.g. AC'],
                        'keep_target' => ['type' => 'string', 'description' => 'For keep_only — service to keep'],
                        'scope_target' => ['type' => 'string', 'description' => 'For keep_one — service family, e.g. AC'],
                        'schedule_text' => ['type' => 'string', 'description' => 'New visit time phrase'],
                        'confirmed' => ['type' => 'boolean', 'description' => 'true only after customer yes or confirm button'],
                    ],
                ],
            ],
            [
                'name' => 'list_my_saved_addresses',
                'description' => 'Saved delivery/service addresses on this account (labels and text only).',
                'parameters' => ['type' => 'object', 'properties' => new \stdClass],
            ],
            [
                'name' => 'search_support_knowledge',
                'description' => 'Troubleshooting and app how-to: payment, OTP, cart, address, booking tab, notifications. Pass the user concern in their words.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => ['query' => ['type' => 'string']],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'list_my_system_bookings',
                'description' => 'Recent bookings on this logged-in account — references and status labels only.',
                'parameters' => ['type' => 'object', 'properties' => ['limit' => ['type' => 'integer']]],
            ],
            [
                'name' => 'get_booking_status_by_reference',
                'description' => 'Look up one booking by reference id (PK…) on this account.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => ['booking_reference' => ['type' => 'string']],
                    'required' => ['booking_reference'],
                ],
            ],
            [
                'name' => 'report_unclear_user_intent',
                'description' => 'Only if the message is unintelligible after trying to understand.',
                'parameters' => ['type' => 'object', 'properties' => ['brief_reason' => ['type' => 'string']]],
            ],
            [
                'name' => 'request_human_support_handoff',
                'description' => 'Only when the customer clearly asks for a human agent. Server sends official handoff text.',
                'parameters' => ['type' => 'object', 'properties' => ['topic' => ['type' => 'string']]],
            ],
        ];
    }

    /**
     * Strip internal wizard hints from tool JSON so Gemini cannot leak them to the customer.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function sanitizeBookingToolResult(array $result): array
    {
        unset(
            $result['assistant_instruction'],
            $result['_instruction'],
            $result['missing_steps'],
            $result['wizard'],
            $result['wizard_steps'],
        );

        if (! ($result['ok'] ?? false)) {
            $msg = trim((string) ($result['customer_message'] ?? ''));
            if ($msg === '') {
                $result['customer_message'] = __('mobile_app_ai.fallback_reply');
            }
        }

        return $result;
    }
}
