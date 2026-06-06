<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\BusinessSettingsModule\Entities\MobileAppAiMessage;
use Modules\UserManagement\Entities\User;

/**
 * Hybrid pipeline: server handlers for known flows (booking, cart, coupon, status),
 * Gemini for everything else. Confirm steps handled server-side.
 */
class MobileAppAiOrchestrator
{
    public function __construct(
        protected MobileAppAiRuntimeResolver $runtime,
        protected MobileAppAiGeminiRunner $runner,
        protected MobileAppAiBookingUiPresenter $bookingUi,
        protected MobileAppAiCartManageService $cartManage,
        protected MobileAppAiCustomerAgentService $customerAgent,
        protected MobileAppAiCustomerSnapshotService $customerSnapshot,
        protected MobileAppAiEscalationPolicy $escalation,
        protected MobileAppAiCostGuardService $costGuard,
        protected MobileAppAiAnalyticsService $analytics,
        protected MobileAppAiPricingReply $pricingReply,
        protected MobileAppAiIntentRouter $intentRouter,
        protected MobileAppAiChatBookingService $chatBooking,
        protected MobileAppAiGeminiHealthService $geminiHealth,
    ) {}

    /**
     * @return array{reply: string, messages: list<array<string, mixed>>, cart_updated: bool, ui?: mixed}
     */
    public function handleUserMessage(User $user, MobileAppAiConversation $conversation, string $text): array
    {
        $text = MobileAppAiInputNormalizer::forMatching($text);
        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
        $bookingStep = (string) ($draft['step'] ?? '');

        if (! $this->runtime->enabled()) {
            return $this->finalize($user, $conversation, __('mobile_app_ai.disabled'), null, false);
        }

        if ($blocked = $this->costGuard->checkMessageAllowed($user)) {
            return $this->finalize($user, $conversation, $blocked, null, false);
        }

        if ($this->escalation->shouldEscalate($text, $conversation)) {
            $handoff = $this->escalation->buildHandoff($user, $conversation, 'repeated difficulty');
            $this->analytics->escalationTriggered($user, 'auto_policy');
            $this->escalation->resetSessionCounters($conversation);

            return $this->finalize($user, $conversation, $handoff['reply'], $handoff['ui'], false);
        }

        if ($bookingStep === 'cart_confirm') {
            $this->persistUserMessage($conversation, $text);
            $cartResult = $this->cartManage->tryHandle($user, $conversation->fresh() ?? $conversation, $text);
            if ($cartResult !== null) {
                MobileAppAiChatLogger::turn($user->id, $text, 'cart_confirm', 1.0, 'gemini_tools', 'cart', 'cart_confirm', MobileAppAiTurnPlan::ROUTE_GEMINI, false, false);

                return $this->finalize(
                    $user,
                    $conversation->fresh() ?? $conversation,
                    $cartResult['reply'],
                    $cartResult['ui'] ?? null,
                    $cartResult['cart_updated']
                );
            }
        }

        if (in_array($bookingStep, ['coupon_confirm', 'bid_confirm', 'booking_cancel_confirm', 'qty_confirm'], true)) {
            $this->persistUserMessage($conversation, $text);
            $agentResult = $this->customerAgent->tryHandle($user, $conversation->fresh() ?? $conversation, $text);
            if ($agentResult !== null) {
                MobileAppAiChatLogger::turn($user->id, $text, $bookingStep, 1.0, 'gemini_tools', 'agent', $bookingStep, MobileAppAiTurnPlan::ROUTE_GEMINI, false, false);

                return $this->finalize(
                    $user,
                    $conversation->fresh() ?? $conversation,
                    $agentResult['reply'],
                    $agentResult['ui'] ?? null,
                    $agentResult['cart_updated']
                );
            }
        }

        $this->persistUserMessage($conversation, $text);
        $fresh = $conversation->fresh() ?? $conversation;
        $draft = is_array($fresh->booking_draft) ? $fresh->booking_draft : [];

        if ($server = $this->tryServerHandlers($user, $fresh, $text, $draft)) {
            MobileAppAiChatLogger::turn(
                $user->id,
                $text,
                (string) ($server['intent'] ?? 'server'),
                1.0,
                (string) ($server['source'] ?? 'server'),
                (string) ($server['domain'] ?? 'agent'),
                (string) ($server['handler'] ?? 'server'),
                MobileAppAiTurnPlan::ROUTE_EXECUTE,
                false,
                false
            );

            return $this->finalize(
                $user,
                $fresh,
                MobileAppAiReplyStyle::sanitizeCustomerFacing(MobileAppAiReplyStyle::clampReply((string) ($server['reply'] ?? ''))),
                $server['ui'] ?? null,
                ($server['cart_updated'] ?? false) === true
            );
        }

        return $this->handleGeminiAgentTurn($user, $fresh, $text, true);
    }

    /**
     * Deterministic server paths — no Gemini latency or API dependency.
     *
     * @param  array<string, mixed>  $draft
     * @return array{reply: string, ui?: mixed, cart_updated: bool, handler: string, intent?: string, source?: string, domain?: string}|null
     */
    private function tryServerHandlers(
        User $user,
        MobileAppAiConversation $conversation,
        string $text,
        array $draft,
    ): ?array {
        if (MobileAppAiBookingMessageDetector::shouldTryRuleBasedApply($text, $draft)) {
            $payload = MobileAppAiBookingMessageDetector::resolveWizardPayload($text, $draft);
            if ($payload === null && MobileAppAiBookingMessageDetector::looksLikeBulkBookingDetails($text)) {
                $payload = ['action' => 'apply', 'message' => $text];
            }
            if ($payload !== null) {
                $payload['persist_chat_messages'] = false;
                $payload['message'] = (string) ($payload['message'] ?? $text);
                $booking = $this->chatBooking->handleAction($user, $payload);
                $reply = trim((string) ($booking['reply'] ?? ''));
                if ($reply !== '') {
                    return [
                        'reply' => $reply,
                        'ui' => $booking['ui'] ?? null,
                        'cart_updated' => ($booking['cart_updated'] ?? false) === true,
                        'handler' => 'booking_wizard',
                        'intent' => 'booking_wizard_continue',
                        'source' => 'server_booking',
                        'domain' => 'booking',
                    ];
                }
            }
        }

        $agent = $this->customerAgent->tryHandle($user, $conversation, $text);
        if ($agent !== null) {
            return [
                'reply' => (string) ($agent['reply'] ?? ''),
                'ui' => $agent['ui'] ?? null,
                'cart_updated' => ($agent['cart_updated'] ?? false) === true,
                'handler' => 'customer_agent',
                'intent' => 'customer_agent',
                'source' => 'server_agent',
                'domain' => 'agent',
            ];
        }

        if (MobileAppAiCartRequestParser::looksLikeViewCart($text)) {
            $priced = $this->pricingReply->build($user, $text);
            $reply = trim((string) ($priced['customer_message'] ?? ''));
            if ($reply === '') {
                $reply = $this->customerSnapshot->buildAccountAwareFallback($user);
            }

            return [
                'reply' => $reply,
                'ui' => $priced['ui'] ?? null,
                'cart_updated' => false,
                'handler' => 'cart_view_fast',
                'intent' => 'cart_summary',
                'source' => 'server_cart',
                'domain' => 'cart',
            ];
        }

        $cartParsed = MobileAppAiCartRequestParser::parse($text);
        if (is_array($cartParsed) && ($cartParsed['op'] ?? '') !== '' && ($cartParsed['op'] ?? '') !== 'view') {
            $result = $this->cartManage->executeParsed($user, $conversation, $cartParsed, $text);
            if ($result !== null) {
                return [
                    'reply' => (string) ($result['reply'] ?? ''),
                    'ui' => $result['ui'] ?? null,
                    'cart_updated' => ($result['cart_updated'] ?? false) === true,
                    'handler' => 'cart_action_fast',
                    'intent' => 'cart_action',
                    'source' => 'server_cart',
                    'domain' => 'cart',
                ];
            }
        }

        $routed = $this->intentRouter->tryHandleMessage($user, $conversation, $text);
        if ($routed !== null && trim((string) ($routed['reply'] ?? '')) !== '') {
            return [
                'reply' => (string) $routed['reply'],
                'ui' => $routed['ui'] ?? null,
                'cart_updated' => ($routed['cart_updated'] ?? false) === true,
                'handler' => 'intent_router',
                'intent' => 'intent_router',
                'source' => 'server_router',
                'domain' => 'catalog',
            ];
        }

        return null;
    }

    /**
     * @return array{reply: string, messages: list<array<string, mixed>>, cart_updated: bool, ui?: mixed}
     */
    private function handleGeminiAgentTurn(
        User $user,
        MobileAppAiConversation $conversation,
        string $text,
        bool $userMessageAlreadyPersisted = false,
    ): array {
        if (! $userMessageAlreadyPersisted) {
            $this->persistUserMessage($conversation, $text);
        }

        if (! $this->costGuard->checkGeminiAllowed($user)) {
            $limitReply = 'You\'ve reached today\'s AI assistant limit. Please try again tomorrow or use **Cart** and **Bookings** in the app.';

            return $this->finalize($user, $conversation->fresh() ?? $conversation, $limitReply, null, false);
        }

        $this->analytics->geminiTriggered($user, 'gemini_agent');
        $generated = $this->runner->generateReply($user, $conversation->fresh() ?? $conversation, '');
        $reply = trim((string) ($generated['reply'] ?? ''));
        $ui = $generated['ui'] ?? null;
        $serviceFailed = $reply === ''
            || $reply === __('mobile_app_ai.fallback_reply')
            || $reply === __('mobile_app_ai.service_unavailable');

        if ($serviceFailed) {
            $this->geminiHealth->markUnhealthy();
            $reply = __('mobile_app_ai.service_unavailable');
            $ui = null;
        } else {
            $this->geminiHealth->markHealthy();
        }

        $this->escalation->resetSessionCounters($conversation);
        MobileAppAiChatLogger::turn(
            $user->id,
            $text,
            'gemini_agent',
            1.0,
            'gemini_tools',
            'agent',
            'gemini_runner',
            MobileAppAiTurnPlan::ROUTE_GEMINI,
            true,
            $serviceFailed
        );

        return $this->finalize(
            $user,
            $conversation->fresh() ?? $conversation,
            MobileAppAiReplyStyle::sanitizeCustomerFacing(MobileAppAiReplyStyle::clampReply($reply)),
            $ui,
            ($generated['cart_updated'] ?? false) === true
        );
    }

    /**
     * @return array{reply: string, messages: list<array<string, mixed>>, cart_updated: bool, ui?: mixed}
     */
    private function finalize(
        User $user,
        MobileAppAiConversation $conversation,
        string $reply,
        mixed $ui,
        bool $cartUpdated,
    ): array {
        $meta = $this->buildAssistantMeta($conversation->fresh() ?? $conversation, $cartUpdated, $ui);

        $reply = MobileAppAiReplyStyle::sanitizeCustomerFacing($reply);

        if ($reply !== '') {
            MobileAppAiMessage::query()->create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'source' => MobileAppAiMessage::SOURCE_MOBILE_APP,
                'body' => $reply,
                'meta' => $meta,
            ]);
            $conversation->update(['last_message_at' => now()]);
        }

        return [
            'reply' => $reply,
            'messages' => $this->formatMessages($user),
            'cart_updated' => $cartUpdated,
            'ui' => $meta['ui'] ?? null,
        ];
    }

    private function persistUserMessage(MobileAppAiConversation $conversation, string $text): void
    {
        MobileAppAiMessage::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'source' => MobileAppAiMessage::SOURCE_MOBILE_APP,
            'body' => $text,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function formatMessages(User $user): array
    {
        $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
        if (! $conversation) {
            return [];
        }

        $limit = $this->runtime->maxHistoryMessages();

        return MobileAppAiMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('source', MobileAppAiMessage::SOURCE_MOBILE_APP)
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(static function (MobileAppAiMessage $m): array {
                $row = [
                    'id' => $m->id,
                    'role' => $m->role,
                    'body' => $m->body,
                    'created_at' => $m->created_at?->toIso8601String(),
                ];
                if (is_array($m->meta)) {
                    if (isset($m->meta['ui'])) {
                        $row['ui'] = $m->meta['ui'];
                    }
                    if (! empty($m->meta['awaiting_input'])) {
                        $row['awaiting_input'] = true;
                    }
                }

                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildAssistantMeta(MobileAppAiConversation $conversation, bool $cartUpdated, mixed $explicitUi): ?array
    {
        $meta = [];
        if ($explicitUi !== null) {
            $meta['ui'] = $explicitUi;
        } elseif ($cartUpdated) {
            $ui = $this->bookingUi->buildForDraft(['step' => 'done']);
            if ($ui !== null) {
                $meta['ui'] = $ui;
            }
        } else {
            $draft = $conversation->booking_draft;
            if (is_array($draft)) {
                $step = (string) ($draft['step'] ?? '');
                if ($step !== '' && $step !== 'idle') {
                    $ui = $this->bookingUi->buildForDraft($draft);
                    if ($ui !== null) {
                        $meta['ui'] = $ui;
                    }
                }
                if ($step === 'service_query') {
                    $meta['awaiting_input'] = true;
                }
            }
        }

        return $meta !== [] ? $meta : null;
    }
}
