<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\UserManagement\Entities\User;

/**
 * Fast-path intents before Gemini (booking, status, support, troubleshoot).
 */
class MobileAppAiIntentRouter
{
    public function __construct(
        protected MobileAppAiBookingSessionService $bookingSession,
        protected MobileAppAiBookingUiPresenter $bookingUi,
        protected MobileAppAiCustomerBookingService $customerBookings,
        protected MobileAppAiHandoffService $handoff,
        protected MobileAppAiSupportKnowledgeService $supportKnowledge,
        protected MobileAppAiServiceTriageService $serviceTriage,
        protected MobileAppAiPricingReply $pricingReply,
        protected MobileAppAiCartScheduleReply $cartScheduleReply,
        protected MobileAppAiCatalogSearchService $catalogSearch,
    ) {}

    /**
     * @return array{handled: bool, reply: string, ui: mixed, cart_updated: bool}|null
     */
    public function tryHandleMessage(User $user, MobileAppAiConversation $conversation, string $text): ?array
    {
        $intent = $this->detectIntent($text, $conversation);
        if ($intent === null) {
            return null;
        }

        $payload = match ($intent['type']) {
            'greeting' => [
                'ok' => true,
                'customer_message' => (string) ($intent['reply'] ?? MobileAppAiConversationalResponder::greetingMessage()),
                'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
            ],
            'confusion' => [
                'ok' => true,
                'customer_message' => (string) (MobileAppAiConversationalResponder::tryRespond('?', [])['reply'] ?? MobileAppAiStepCopy::troubleshootIntro()),
                'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
            ],
            'start_booking' => $this->runBookingStart($user),
            'bulk_booking' => $this->runBulkBookingApply($user, $intent['message'] ?? $text),
            'service_triage' => $this->runServiceTriage($user, (string) ($intent['query'] ?? $text), (string) ($intent['message'] ?? $text)),
            'service_search' => $this->runServiceSearch(
                $user,
                (string) ($intent['query'] ?? $text),
                (string) ($intent['message'] ?? $text),
            ),
            'unsupported_service' => $this->runUnsupportedService((string) ($intent['message'] ?? $text), (string) ($intent['label'] ?? '')),
            'booking_status' => $this->handleBookingStatus(
                $user,
                $intent['reference'] ?? null,
                (string) ($intent['mode'] ?? 'list'),
            ),
            'human_support' => $this->handoff->buildHandoffResult($intent['topic'] ?? null),
            'pricing' => $this->pricingReply->build($user, $text),
            'cart_schedule' => $this->cartScheduleReply->build($user),
            'saved_addresses' => $this->runSavedAddresses($user),
            'troubleshoot' => $this->formatKnowledge($this->supportKnowledge->search($intent['query'] ?? $text)),
            default => null,
        };

        if ($payload === null) {
            return null;
        }

        return $this->toHandled($payload);
    }

    /**
     * @return array{handled: bool, reply: string, ui: mixed, cart_updated: bool, user_label?: string}
     */
    public function handleQuickIntent(User $user, string $intent, ?string $query = null): array
    {
        $userLabel = match ($intent) {
            'start_booking' => 'Book a service',
            'booking_status' => 'My booking status',
            'human_support' => 'Talk to support',
            'troubleshoot' => $query ?? 'Help with the app',
            default => $query ?? $intent,
        };

        $payload = match ($intent) {
            'start_booking' => $this->runBookingStart($user),
            'booking_status' => $this->handleBookingStatus($user, $query, 'list'),
            'human_support' => $this->handoff->buildHandoffResult($query),
            'troubleshoot' => $this->handleTroubleshootQuickIntent($user, $query),
            default => ['ok' => false, 'customer_message' => 'Unknown action.'],
        };

        $handled = $this->toHandled($payload);
        $handled['user_label'] = $userLabel;

        return $handled;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function detectIntent(string $text, MobileAppAiConversation $conversation): ?array
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return null;
        }

        $draft = $conversation->booking_draft;
        $step = is_array($draft) ? (string) ($draft['step'] ?? '') : '';

        if (MobileAppAiBookingMessageDetector::looksLikeBulkBookingDetails($text)) {
            return ['type' => 'bulk_booking', 'message' => $text];
        }

        if (MobileAppAiBookingMessageDetector::isActiveBookingWizardStep($step)) {
            return null;
        }

        if (MobileAppAiConversationalResponder::isGreeting($text)) {
            return [
                'type' => 'greeting',
                'reply' => MobileAppAiConversationalResponder::greetingMessage(),
            ];
        }

        if (MobileAppAiConversationalResponder::isConfusion($text)) {
            return ['type' => 'confusion'];
        }

        if (preg_match('/\b(pk[a-z0-9]{6,})\b/i', $text, $m)) {
            return ['type' => 'booking_status', 'reference' => $m[1]];
        }

        if (MobileAppAiBookingMessageDetector::looksLikeBookingCountQuery($text)) {
            return ['type' => 'booking_status', 'mode' => 'count'];
        }

        if (MobileAppAiBookingMessageDetector::looksLikeBookingStatusQuery($text)) {
            return ['type' => 'booking_status', 'mode' => 'list'];
        }

        if (MobileAppAiCartScheduleReply::looksLikeCartScheduleQuery($text)) {
            return ['type' => 'cart_schedule'];
        }

        if (MobileAppAiCartRequestParser::looksLikeViewCart($text)) {
            return ['type' => 'pricing'];
        }

        if (MobileAppAiPricingReply::looksLikePricingQuery($text)) {
            return ['type' => 'pricing'];
        }

        if (preg_match('/\b(my\s+addresses|saved\s+addresses|address\s+list)\b/iu', $t)) {
            return ['type' => 'saved_addresses'];
        }

        if ($this->matchesPattern($t, [
            'book a service', 'book service', 'i want to book', 'need to book', 'help me book',
            'schedule a service', 'make a booking', 'new booking',
        ]) || (MobileAppAiBookingMessageDetector::hasBookingIntent($text)
            && ! MobileAppAiServiceQueryNormalizer::looksLikeProblemOrService($text))) {
            return ['type' => 'start_booking'];
        }

        if (MobileAppAiServiceQueryNormalizer::isGenericBookingPhrase($text)) {
            return ['type' => 'start_booking'];
        }

        $resolved = MobileAppAiServiceIntentResolver::resolve($text);
        if ($resolved['unsupported'] !== null) {
            return [
                'type' => 'unsupported_service',
                'message' => $text,
                'label' => $resolved['unsupported'],
            ];
        }

        if (MobileAppAiServiceQueryNormalizer::looksLikeServiceRequest($text)) {
            if (MobileAppAiServiceTriageService::shouldStartTriage($text, is_array($draft) ? $draft : [])) {
                return [
                    'type' => 'service_triage',
                    'query' => MobileAppAiServiceQueryNormalizer::normalize($text),
                    'message' => $text,
                ];
            }

            return [
                'type' => 'service_search',
                'query' => $resolved['catalog_query'] !== ''
                    ? $resolved['catalog_query']
                    : MobileAppAiServiceQueryNormalizer::normalize($text),
                'message' => $text,
            ];
        }

        if ($this->matchesPattern($t, [
            'talk to human', 'speak to agent', 'real person', 'customer care', 'call support',
            'human support', 'live agent', 'representative', 'talk to someone',
        ])) {
            return ['type' => 'human_support', 'topic' => $text];
        }

        if (MobileAppAiBookingMessageDetector::looksLikeAppTroubleshoot($text)) {
            return ['type' => 'troubleshoot', 'query' => $text];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function runUnsupportedService(string $message, string $label): array
    {
        $label = $label !== '' ? $label : 'that service';

        return [
            'ok' => true,
            'customer_message' => MobileAppAiServiceIntentResolver::unsupportedMessage($message, $label),
            'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
            'cart_updated' => false,
        ];
    }

    /**
     * @param  list<string>  $needles
     */
    private function matchesPattern(string $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            if (str_contains($haystack, $n)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function runBulkBookingApply(User $user, string $message): array
    {
        $result = $this->bookingSession->handle($user, [
            'action' => 'apply',
            'message' => $message,
        ]);
        $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
        $draft = is_array($conversation?->booking_draft) ? $conversation->booking_draft : [];
        $ui = ($result['ok'] ?? false) ? $this->bookingUi->buildForDraft($draft) : null;
        if (($result['cart_updated'] ?? false) === true) {
            $ui = $this->bookingUi->buildForDraft(['step' => 'done']);
        }

        return [
            'ok' => $result['ok'] ?? false,
            'customer_message' => (string) ($result['customer_message'] ?? ''),
            'ui' => $ui,
            'cart_updated' => ($result['cart_updated'] ?? false) === true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runServiceTriage(User $user, string $query, string $rawText): array
    {
        $conversation = MobileAppAiConversation::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['last_message_at' => now()]
        );
        $result = $this->serviceTriage->startTriage($conversation, $rawText, $query);

        return [
            'ok' => $result['ok'] ?? false,
            'customer_message' => (string) ($result['customer_message'] ?? ''),
            'ui' => $result['ui'] ?? null,
            'cart_updated' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runServiceSearch(User $user, string $query, string $message): array
    {
        $result = $this->bookingSession->handle($user, [
            'action' => 'search',
            'query' => $query,
            'message' => $message,
        ]);
        $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
        $draft = is_array($conversation?->booking_draft) ? $conversation->booking_draft : [];
        $ui = ($result['ok'] ?? false) ? $this->bookingUi->buildForDraft($draft) : null;

        return [
            'ok' => $result['ok'] ?? false,
            'customer_message' => (string) ($result['customer_message'] ?? ''),
            'ui' => $ui,
            'cart_updated' => ($result['cart_updated'] ?? false) === true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runBookingStart(User $user): array
    {
        $result = $this->bookingSession->handle($user, ['action' => 'start']);
        $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
        $draft = is_array($conversation?->booking_draft) ? $conversation->booking_draft : [];
        $ui = ($result['ok'] ?? false) ? $this->bookingUi->buildForDraft($draft) : null;

        return [
            'ok' => $result['ok'] ?? false,
            'customer_message' => (string) ($result['customer_message'] ?? ''),
            'ui' => $ui,
            'cart_updated' => ($result['cart_updated'] ?? false) === true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleBookingStatus(User $user, ?string $reference, string $mode = 'list'): array
    {
        if ($reference !== null && trim($reference) !== '') {
            return $this->customerBookings->statusByReference($user, ['booking_reference' => $reference]);
        }

        if ($mode === 'count') {
            return $this->customerBookings->countSummaryForUser($user);
        }

        return $this->customerBookings->listForUser($user);
    }

    /**
     * App help only — home-service queries stay in service triage.
     *
     * @return array<string, mixed>
     */
    private function handleTroubleshootQuickIntent(User $user, ?string $query): array
    {
        $q = trim((string) $query);
        if ($q !== ''
            && (MobileAppAiServiceQueryNormalizer::looksLikeProblemOrService($q)
                || MobileAppAiServiceIntentResolver::resolve($q)['trade_id'] !== '')) {
            $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
            $draft = is_array($conversation?->booking_draft) ? $conversation->booking_draft : [];
            if (($draft['step'] ?? '') === 'service_triage') {
                return $this->serviceTriage->moreTips($conversation);
            }

            return $this->runServiceTriage($user, MobileAppAiServiceQueryNormalizer::normalize($q), $q);
        }

        return $this->formatKnowledge($this->supportKnowledge->search($q !== '' ? $q : 'app help'));
    }

    /**
     * @param  array<string, mixed>  $knowledge
     * @return array<string, mixed>
     */
    private function formatKnowledge(array $knowledge): array
    {
        $query = trim((string) ($knowledge['query'] ?? ''));
        if ($query !== '' && MobileAppAiReplyStyle::isVagueIssue($query)) {
            return [
                'ok' => true,
                'customer_message' => 'What exactly is the problem? (e.g. payment failed, cart empty, OTP)',
                'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
            ];
        }

        $lines = [];
        foreach ($knowledge['troubleshooting'] ?? [] as $pack) {
            if (! is_array($pack)) {
                continue;
            }
            foreach ($pack['steps'] ?? [] as $step) {
                $lines[] = (string) $step;
                if (count($lines) >= MobileAppAiReplyStyle::MAX_TIP_LINES) {
                    break 2;
                }
            }
        }
        if ($lines === []) {
            foreach ($knowledge['faqs'] ?? [] as $faq) {
                if (! is_array($faq)) {
                    continue;
                }
                $a = trim((string) ($faq['answer'] ?? ''));
                if ($a !== '') {
                    $lines[] = MobileAppAiReplyStyle::shorten($a, 120);
                }
                if (count($lines) >= MobileAppAiReplyStyle::MAX_TIP_LINES) {
                    break;
                }
            }
        }

        $tips = MobileAppAiReplyStyle::formatTipLines($lines);
        $msg = $tips !== '' ? $tips : MobileAppAiStepCopy::troubleshootIntro();

        return [
            'ok' => true,
            'customer_message' => MobileAppAiReplyStyle::clampReply($msg),
            'ui' => [
                'type' => 'assistant_actions',
                'layout' => 'actions',
                'actions' => [
                    ['action' => 'start_booking', 'label' => 'Book a service', 'style' => 'primary', 'icon' => 'home_repair_service'],
                    ['action' => 'booking_status', 'label' => 'My bookings', 'style' => 'outline', 'icon' => 'event'],
                    ['action' => 'open_support', 'label' => 'Help & Support', 'style' => 'text', 'icon' => 'support'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runSavedAddresses(User $user): array
    {
        $result = $this->catalogSearch->listCustomerAddresses($user);
        if ((int) ($result['count'] ?? 0) === 0) {
            return [
                'ok' => true,
                'customer_message' => 'You have no saved addresses yet. In the app: **Home → tap the location bar → Add new address**, then tell me when done.',
                'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
            ];
        }

        $lines = [];
        foreach ($result['selectable_options'] ?? [] as $opt) {
            if (is_array($opt)) {
                $lines[] = (string) ($opt['display'] ?? '');
            }
        }

        return [
            'ok' => true,
            'customer_message' => MobileAppAiReplyStyle::clampReply(
                "Your saved addresses:\n\n".implode("\n", array_filter($lines))
            ),
            'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{handled: bool, reply: string, ui: mixed, cart_updated: bool}
     */
    private function toHandled(array $payload): array
    {
        return [
            'handled' => true,
            'reply' => (string) ($payload['customer_message'] ?? ''),
            'ui' => $payload['ui'] ?? null,
            'cart_updated' => ($payload['cart_updated'] ?? false) === true,
        ];
    }
}
