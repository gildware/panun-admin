<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\UserManagement\Entities\User;

/**
 * Automatic human handoff when frustration or repeated failure detected.
 */
class MobileAppAiEscalationPolicy
{
    public function __construct(
        protected MobileAppAiHandoffService $handoff,
        protected MobileAppAiCustomerStateService $customerState,
    ) {}

    public function shouldEscalate(string $text, ?MobileAppAiConversation $conversation): bool
    {
        if (! config('mobile_app_ai_production.escalation.enabled', true)) {
            return false;
        }

        if ($this->looksAngry($text)) {
            return true;
        }

        $session = $this->sessionMetrics($conversation);

        return ($session['fallback_count'] ?? 0) >= (int) config('mobile_app_ai_production.escalation.max_fallbacks', 3)
            || ($session['failed_intent_count'] ?? 0) >= (int) config('mobile_app_ai_production.escalation.max_failed_intents', 3);
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, handler: string}
     */
    public function buildHandoff(User $user, MobileAppAiConversation $conversation, string $reason): array
    {
        $topic = 'AI chat — '.$reason;
        $result = $this->handoff->buildHandoffResult($topic);
        $state = $this->customerState->build($user, $conversation);

        $prefix = 'I\'m connecting you with our team so you don\'t have to repeat yourself. ';
        $context = '';
        $cartCount = (int) ($state['cart']['item_count'] ?? 0);
        if ($cartCount > 0) {
            $context .= 'Your cart has '.$cartCount.' item(s). ';
        }

        return [
            'reply' => MobileAppAiReplyStyle::clampReply($prefix.$context.(string) ($result['customer_message'] ?? '')),
            'ui' => $result['ui'] ?? null,
            'cart_updated' => false,
            'handler' => 'escalation_handoff',
        ];
    }

    public function recordFallback(MobileAppAiConversation $conversation): void
    {
        $this->bumpSession($conversation, 'fallback_count');
    }

    public function recordFailedIntent(MobileAppAiConversation $conversation): void
    {
        $this->bumpSession($conversation, 'failed_intent_count');
    }

    public function resetSessionCounters(MobileAppAiConversation $conversation): void
    {
        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
        $session = is_array($draft['ai_session'] ?? null) ? $draft['ai_session'] : [];
        $session['fallback_count'] = 0;
        $session['failed_intent_count'] = 0;
        $draft['ai_session'] = $session;
        $conversation->booking_draft = $draft;
        $conversation->save();
    }

    private function looksAngry(string $text): bool
    {
        $t = mb_strtolower($text);
        foreach ((array) config('mobile_app_ai_production.escalation.angry_keywords', []) as $kw) {
            if ($kw !== '' && str_contains($t, mb_strtolower((string) $kw))) {
                return true;
            }
        }

        return (bool) preg_match('/\b(refund|chargeback|fraud|police|sue|lawyer)\b/iu', $t);
    }

    /**
     * @return array<string, int>
     */
    private function sessionMetrics(?MobileAppAiConversation $conversation): array
    {
        if ($conversation === null) {
            return ['fallback_count' => 0, 'failed_intent_count' => 0];
        }

        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
        $session = is_array($draft['ai_session'] ?? null) ? $draft['ai_session'] : [];

        return [
            'fallback_count' => (int) ($session['fallback_count'] ?? 0),
            'failed_intent_count' => (int) ($session['failed_intent_count'] ?? 0),
        ];
    }

    private function bumpSession(MobileAppAiConversation $conversation, string $key): void
    {
        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
        $session = is_array($draft['ai_session'] ?? null) ? $draft['ai_session'] : [];
        $session[$key] = (int) ($session[$key] ?? 0) + 1;
        $draft['ai_session'] = $session;
        $conversation->booking_draft = $draft;
        $conversation->save();
    }
}
