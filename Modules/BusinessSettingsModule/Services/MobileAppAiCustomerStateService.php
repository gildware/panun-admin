<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\UserManagement\Entities\User;

/**
 * Unified per-turn customer state — single source of truth for handlers.
 */
class MobileAppAiCustomerStateService
{
    public function __construct(
        protected MobileAppAiCustomerSnapshotService $snapshot,
        protected MobileAppAiConversationStateService $conversationState,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user, ?MobileAppAiConversation $conversation = null): array
    {
        $draft = $conversation && is_array($conversation->booking_draft)
            ? $conversation->booking_draft
            : [];
        $snapshot = $this->snapshot->build($user);
        $convState = $this->conversationState->read($conversation);

        return [
            'profile' => ['line' => $snapshot['profile_line'] ?? ''],
            'cart' => [
                'item_count' => (int) ($snapshot['cart_count'] ?? 0),
                'cart_total' => (float) ($snapshot['cart_total'] ?? 0),
                'items' => $snapshot['items'] ?? [],
                'line' => $snapshot['cart_line'] ?? '',
            ],
            'bookings' => $snapshot['bookings'] ?? [],
            'addresses' => $snapshot['addresses'] ?? [],
            'bids' => ['count' => 0],
            'active_wizard' => [
                'step' => (string) ($draft['step'] ?? 'idle'),
                'choices' => is_array($draft['choices'] ?? null) ? $draft['choices'] : [],
            ],
            'pending_confirmation' => $this->pendingConfirmationType($draft),
            'conversation_state' => $convState,
            'ai_session' => is_array($draft['ai_session'] ?? null) ? $draft['ai_session'] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    private function pendingConfirmationType(array $draft): ?string
    {
        $step = (string) ($draft['step'] ?? '');
        if (in_array($step, [
            'cart_confirm',
            'coupon_confirm',
            'bid_confirm',
            'booking_cancel_confirm',
            'qty_confirm',
        ], true)) {
            return $step;
        }

        return null;
    }
}
