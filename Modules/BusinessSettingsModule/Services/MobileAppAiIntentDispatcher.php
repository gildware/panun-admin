<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\UserManagement\Entities\User;

/**
 * Execute deterministic handlers from classified intent (not keyword order).
 */
class MobileAppAiIntentDispatcher
{
    public function __construct(
        protected MobileAppAiPricingReply $pricingReply,
        protected MobileAppAiCartScheduleReply $cartScheduleReply,
        protected MobileAppAiCartManageService $cartManage,
        protected MobileAppAiCustomerAgentService $customerAgent,
        protected MobileAppAiServiceTriageService $serviceTriage,
        protected MobileAppAiIntentRouter $intentRouter,
        protected MobileAppAiChatBookingService $chatBooking,
        protected MobileAppAiHandoffService $handoff,
        protected MobileAppAiConversationStateService $conversationState,
        protected MobileAppAiAccountSummaryService $accountSummary,
    ) {}

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}|null null → use Gemini/fallback
     */
    /**
     * Execute up to N intents in order (read-only before mutations).
     *
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}|null
     */
    public function dispatchPlan(
        User $user,
        MobileAppAiConversation $conversation,
        string $text,
        MobileAppAiTurnPlan $plan,
    ): ?array {
        $replies = [];
        $ui = null;
        $cartUpdated = false;
        $handlers = [];

        foreach ($plan->orderedIntents() as $classification) {
            $result = $this->dispatch($user, $conversation, $text, $classification);
            if ($result === null) {
                continue;
            }
            $part = trim((string) ($result['reply'] ?? ''));
            if ($part !== '') {
                $replies[] = $part;
            }
            if (($result['cart_updated'] ?? false) === true) {
                $cartUpdated = true;
            }
            if (($result['ui'] ?? null) !== null) {
                $ui = $result['ui'];
            }
            $handlers[] = (string) ($result['handler'] ?? 'dispatcher');
            if (MobileAppAiActionImpactCatalog::requiresConfirmation($classification->intent)) {
                break;
            }
        }

        if ($replies === []) {
            return null;
        }

        return $this->wrap(
            MobileAppAiReplyStyle::clampReply(implode("\n\n", $replies)),
            $ui,
            $cartUpdated,
            implode('+', $handlers)
        );
    }

    public function dispatch(
        User $user,
        MobileAppAiConversation $conversation,
        string $text,
        MobileAppAiIntentClassification $classification,
    ): ?array {
        $intent = $classification->intent;
        $executionText = $this->executionTextForIntent($text, $classification);

        return match ($intent) {
            MobileAppAiIntentCatalog::GREETING => $this->wrap(
                MobileAppAiConversationalResponder::greetingMessage(),
                MobileAppAiConversationalResponder::homeActionsUi(),
                false,
                'conversational_greeting'
            ),
            MobileAppAiIntentCatalog::THANKS => $this->wrap(
                "You're welcome! Tell me anytime if you need another booking or help with the app.",
                MobileAppAiConversationalResponder::homeActionsUi(),
                false,
                'conversational_thanks'
            ),
            MobileAppAiIntentCatalog::VIEW_CART => $this->fromAccountSummary($user, $classification, 'cart_summary'),
            MobileAppAiIntentCatalog::CART_SUMMARY => $this->fromAccountSummary($user, $classification, 'cart_summary'),
            MobileAppAiIntentCatalog::PRICING_QUERY => $this->fromAccountSummary($user, $classification, 'cart_total'),
            MobileAppAiIntentCatalog::BOOKING_SUMMARY => $this->fromAccountSummary($user, $classification, 'booking_summary'),
            MobileAppAiIntentCatalog::BIDDING_SUMMARY => $this->fromAccountSummary($user, $classification, 'bidding_summary'),
            MobileAppAiIntentCatalog::ADDRESS_SUMMARY => $this->fromAccountSummary($user, $classification, 'address_summary'),
            MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY => $this->fromSchedule($user),
            MobileAppAiIntentCatalog::CART_CLEAR,
            MobileAppAiIntentCatalog::CART_REMOVE_ITEM,
            MobileAppAiIntentCatalog::CART_RESCHEDULE
                => $this->fromCartManage($user, $conversation, $executionText, $classification),
            MobileAppAiIntentCatalog::CART_QTY_CHANGE,
            MobileAppAiIntentCatalog::COUPON_APPLY,
            MobileAppAiIntentCatalog::COUPON_REMOVE,
            MobileAppAiIntentCatalog::COUPON_LIST,
            MobileAppAiIntentCatalog::BIDDING_LIST,
            MobileAppAiIntentCatalog::BIDDING_CREATE,
            MobileAppAiIntentCatalog::BIDDING_ACCEPT,
            MobileAppAiIntentCatalog::BIDDING_DECLINE,
            MobileAppAiIntentCatalog::BOOKING_CANCEL,
            MobileAppAiIntentCatalog::BOOKING_REBOOK,
            MobileAppAiIntentCatalog::SERVICE_DETAILS => $this->fromAgent($user, $conversation, $executionText),
            MobileAppAiIntentCatalog::BOOKING_STATUS => $this->fromIntentRouter($user, $text, 'booking_status'),
            MobileAppAiIntentCatalog::HUMAN_SUPPORT => $this->fromHandoff($classification),
            MobileAppAiIntentCatalog::APP_TROUBLESHOOT => $this->fromTroubleshoot($user, $text),
            MobileAppAiIntentCatalog::SERVICE_TRIAGE => $this->fromTriage($user, $conversation, $text, $classification),
            MobileAppAiIntentCatalog::BOOKING_START => $this->fromBookingStart($user, $text, $conversation),
            MobileAppAiIntentCatalog::BOOKING_WIZARD_CONTINUE => null,
            MobileAppAiIntentCatalog::UNKNOWN => null,
            default => null,
        };
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}
     */
    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}
     */
    private function fromAccountSummary(User $user, MobileAppAiIntentClassification $c, string $handler): array
    {
        $mode = $c->entityString('mode');
        if ($handler === 'cart_total') {
            $mode = 'total';
        }

        $payload = match ($c->intent) {
            MobileAppAiIntentCatalog::CART_SUMMARY => $this->accountSummary->cartSummary($user, $mode !== '' ? $mode : 'items'),
            MobileAppAiIntentCatalog::BOOKING_SUMMARY => $this->accountSummary->bookingSummary($user, $mode !== '' ? $mode : 'list'),
            MobileAppAiIntentCatalog::BIDDING_SUMMARY => $this->accountSummary->biddingSummary($user, $mode !== '' ? $mode : 'list'),
            MobileAppAiIntentCatalog::ADDRESS_SUMMARY => $this->accountSummary->addressSummary($user, $mode !== '' ? $mode : 'list'),
            default => $this->accountSummary->cartSummary($user, 'items'),
        };

        return $this->wrap(
            (string) ($payload['customer_message'] ?? ''),
            $payload['ui'] ?? null,
            false,
            $handler
        );
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}
     */
    private function fromPricing(User $user, string $handler): array
    {
        $priced = $this->pricingReply->build($user);

        return $this->wrap(
            (string) ($priced['customer_message'] ?? ''),
            $priced['ui'] ?? null,
            false,
            $handler
        );
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}
     */
    private function fromSchedule(User $user): array
    {
        $scheduled = $this->cartScheduleReply->build($user);

        return $this->wrap(
            (string) ($scheduled['customer_message'] ?? ''),
            $scheduled['ui'] ?? null,
            false,
            'cart_schedule_reply'
        );
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}|null
     */
    private function fromCartManage(
        User $user,
        MobileAppAiConversation $conversation,
        string $text,
        MobileAppAiIntentClassification $classification,
    ): ?array {
        $result = $this->cartManage->tryHandle($user, $conversation, $text, $classification);
        if ($result === null) {
            return null;
        }

        return $this->wrap(
            (string) ($result['reply'] ?? ''),
            $result['ui'] ?? null,
            ($result['cart_updated'] ?? false) === true,
            'cart_manage'
        );
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}|null
     */
    private function fromAgent(User $user, MobileAppAiConversation $conversation, string $text): ?array
    {
        $result = $this->customerAgent->tryHandle($user, $conversation, $text);
        if ($result === null) {
            return null;
        }

        return $this->wrap(
            (string) ($result['reply'] ?? ''),
            $result['ui'] ?? null,
            ($result['cart_updated'] ?? false) === true,
            'customer_agent'
        );
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}|null
     */
    private function fromIntentRouter(User $user, string $text, string $handler): ?array
    {
        $conversation = MobileAppAiConversation::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['last_message_at' => now()]
        );
        $routed = $this->intentRouter->tryHandleMessage($user, $conversation, $text);
        if ($routed === null) {
            return null;
        }

        return $this->wrap(
            (string) ($routed['reply'] ?? ''),
            $routed['ui'] ?? null,
            ($routed['cart_updated'] ?? false) === true,
            $handler
        );
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}
     */
    private function fromHandoff(MobileAppAiIntentClassification $classification): array
    {
        $topic = $classification->entityString('service_query');
        $handoff = $this->handoff->buildHandoffResult($topic !== '' ? $topic : null);

        return $this->wrap(
            (string) ($handoff['customer_message'] ?? ''),
            $handoff['ui'] ?? null,
            false,
            'human_support'
        );
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}
     */
    private function fromTroubleshoot(User $user, string $text): array
    {
        $handled = $this->intentRouter->handleQuickIntent($user, 'troubleshoot', $text);

        return $this->wrap(
            (string) ($handled['reply'] ?? ''),
            $handled['ui'] ?? null,
            ($handled['cart_updated'] ?? false) === true,
            'app_troubleshoot'
        );
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}
     */
    private function fromTriage(
        User $user,
        MobileAppAiConversation $conversation,
        string $text,
        MobileAppAiIntentClassification $classification,
    ): array {
        if (MobileAppAiBookingMessageDetector::looksLikeServiceBookingRequest($text)) {
            $booking = $this->fromBookingStart($user, $text, $conversation);
            if ($booking !== null) {
                return $booking;
            }
        }

        $query = $classification->entityString('service_query');
        if ($query === '') {
            $query = MobileAppAiServiceQueryNormalizer::normalize($text);
        }
        if ($query === '' || MobileAppAiBookingMessageDetector::looksLikeServiceBookingRequest($text)) {
            $state = $this->conversationState->read($conversation);
            $active = trim((string) ($state['active_service'] ?? ''));
            if ($active !== '') {
                $booking = $this->fromBookingStart($user, $text, $conversation, $active);
                if ($booking !== null) {
                    return $booking;
                }
            }
        }
        $triage = $this->serviceTriage->startTriage($conversation, $text, $query);
        $this->conversationState->write($conversation, [
            'active_problem' => $text,
            'active_service' => $query,
            'pending_question' => 'triage_issue_detail',
        ]);

        return $this->wrap(
            (string) ($triage['customer_message'] ?? ''),
            $triage['ui'] ?? null,
            false,
            'service_triage'
        );
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}|null
     */
    private function fromBookingStart(User $user, string $text, ?MobileAppAiConversation $conversation = null, ?string $contextService = null): ?array
    {
        $nlu = ['persist_chat_messages' => false, 'message' => $text];
        $contextService = trim((string) ($contextService ?? ''));
        if ($contextService === '' && $conversation !== null) {
            $state = $this->conversationState->read($conversation);
            $contextService = trim((string) ($state['active_service'] ?? ''));
            if ($contextService === '') {
                $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
                $contextService = trim((string) ($draft['choices']['service_query'] ?? $draft['choices']['service_name'] ?? ''));
            }
        }

        if ($contextService !== ''
            && (MobileAppAiBookingMessageDetector::looksLikeServiceBookingRequest($text)
                || MobileAppAiServiceQueryNormalizer::isGenericBookingPhrase($text))) {
            $draft = $conversation && is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
            if ((string) ($draft['step'] ?? '') === 'service_triage') {
                $payload = ['action' => 'proceed_booking', 'persist_chat_messages' => false];
            } else {
                $payload = [
                    'action' => 'search',
                    'query' => $contextService,
                    'persist_chat_messages' => false,
                ];
            }
        } elseif (MobileAppAiServiceQueryNormalizer::isGenericBookingPhrase($text)) {
            $payload = array_merge(['action' => 'start'], $nlu);
        } elseif (MobileAppAiBookingMessageDetector::looksLikeBulkBookingDetails($text)
            || MobileAppAiBookingMessageDetector::hasTimeHint($text)) {
            $payload = array_merge(['action' => 'apply'], $nlu);
        } elseif (MobileAppAiServiceQueryNormalizer::looksLikeServiceRequest($text)) {
            $resolved = MobileAppAiServiceIntentResolver::resolve($text);
            $payload = array_merge([
                'action' => 'search',
                'query' => $resolved['catalog_query'] !== '' ? $resolved['catalog_query'] : MobileAppAiServiceQueryNormalizer::normalize($text),
            ], $nlu);
        } elseif (MobileAppAiBookingMessageDetector::hasBookingIntent($text)
            || MobileAppAiBookingMessageDetector::looksLikeTechnicianRequest($text)) {
            $payload = array_merge(['action' => 'start'], $nlu);
        } else {
            return null;
        }

        $bookingResult = $this->chatBooking->handleAction($user, $payload);
        $reply = trim((string) ($bookingResult['reply'] ?? ''));
        if ($reply === '') {
            return null;
        }

        return $this->wrap(
            $reply,
            $bookingResult['ui'] ?? null,
            ($bookingResult['cart_updated'] ?? false) === true,
            'booking_start'
        );
    }

    private function executionTextForIntent(string $text, MobileAppAiIntentClassification $c): string
    {
        $remove = $c->entityString('remove_target');
        $keep = $c->entityString('keep_target');
        $schedule = $c->entityString('schedule_text');
        $coupon = $c->entityString('coupon_code');

        return match ($c->intent) {
            MobileAppAiIntentCatalog::CART_REMOVE_ITEM => ($c->entityString('cart_filter') !== ''
                || $c->entityStringList('cart_line_ids') !== [])
                ? $text
                : ($remove !== '' && $keep !== ''
                    ? 'remove '.$remove.' and keep only '.$keep
                    : ($keep !== '' && $remove === ''
                        ? 'keep only '.$keep
                        : ($remove !== '' ? 'remove '.$remove : $text))),
            MobileAppAiIntentCatalog::CART_RESCHEDULE => $schedule !== ''
                ? 'reschedule cart visit to '.$schedule
                : $text,
            MobileAppAiIntentCatalog::COUPON_APPLY => $coupon !== ''
                ? 'apply coupon '.$coupon
                : $text,
            MobileAppAiIntentCatalog::SERVICE_DETAILS => $c->entityString('service_query') !== ''
                ? 'tell me about '.$c->entityString('service_query')
                : $text,
            default => $text,
        };
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}
     */
    private function wrap(string $reply, mixed $ui, bool $cartUpdated, string $handler): array
    {
        return [
            'reply' => $reply,
            'ui' => $ui,
            'cart_updated' => $cartUpdated,
            'handler' => $handler,
        ];
    }
}
