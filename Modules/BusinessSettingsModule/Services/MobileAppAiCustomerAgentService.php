<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\CartModule\Entities\Cart;
use Modules\CartModule\Traits\CartTrait;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;

/**
 * Booking, bidding, coupons, service details, and cart quantity — with confirm-before-change.
 */
class MobileAppAiCustomerAgentService
{
    use CartTrait;

    public function __construct(
        protected MobileAppAiCouponService $couponService,
        protected MobileAppAiBookingManageService $bookingManage,
        protected MobileAppAiBiddingService $biddingService,
        protected MobileAppAiServiceDetailsService $serviceDetails,
        protected MobileAppAiPricingReply $pricingReply,
    ) {}

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool}|null
     */
    public function tryHandle(User $user, MobileAppAiConversation $conversation, string $text): ?array
    {
        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
        $step = (string) ($draft['step'] ?? '');

        if (in_array($step, ['coupon_confirm', 'bid_confirm', 'booking_cancel_confirm', 'qty_confirm'], true)) {
            return $this->handleConfirmStep($user, $conversation, $text, $draft);
        }

        if (MobileAppAiCouponService::looksLikeCouponIntent($text)) {
            return $this->wrap($this->handleCoupon($user, $conversation, $text));
        }

        if (MobileAppAiBiddingService::looksLikeBiddingIntent($text)) {
            return $this->wrap($this->handleBidding($user, $conversation, $text));
        }

        if (MobileAppAiBookingManageService::looksLikeCancelBooking($text)) {
            $built = $this->bookingManage->buildCancelConfirm($user, $text);
            if (($built['ok'] ?? false) && isset($built['pending'])) {
                $this->saveConfirmDraft($conversation, 'booking_cancel_confirm', $built['pending'], 'cancel this booking', $text);

                return $this->wrap([
                    'customer_message' => (string) $built['customer_message'],
                    'ui' => $built['ui'] ?? MobileAppAiConfirmUi::confirmCancel('booking_cancel_confirm', 'booking_cancel', $text),
                ]);
            }

            return $this->wrap($built);
        }

        if (MobileAppAiBookingManageService::looksLikeRebook($text)) {
            return $this->wrap($this->bookingManage->rebookToCart($user, $text), true);
        }

        if (MobileAppAiServiceDetailsService::looksLikeServiceDetailsIntent($text)) {
            $query = self::extractDetailsQuery($text);

            return $this->wrap($this->serviceDetails->describeService($user, $query));
        }

        $qtyParsed = $this->parseQuantityChangeForUser($user, $text);
        if ($qtyParsed !== null) {
            return $this->wrap($this->beginQtyConfirm($user, $conversation, $qtyParsed, $text));
        }

        return null;
    }

    public function confirmCoupon(User $user, MobileAppAiConversation $conversation): array
    {
        $pending = $this->pending($conversation);
        $this->resetDraft($conversation);
        if (($pending['op'] ?? '') === 'apply_coupon') {
            return $this->couponService->applyCoupon($user, (string) ($pending['code'] ?? ''));
        }

        return $this->couponService->removeCoupon($user);
    }

    public function cancelPending(MobileAppAiConversation $conversation): array
    {
        $this->resetDraft($conversation);

        return [
            'ok' => true,
            'customer_message' => 'No problem — nothing was changed.',
            'ui' => MobileAppAiConfirmUi::agentMenu(),
        ];
    }

    public function confirmBid(User $user, MobileAppAiConversation $conversation): array
    {
        $pending = $this->pending($conversation);
        $this->resetDraft($conversation);

        return $this->biddingService->executePending($user, $pending);
    }

    public function confirmBookingCancel(User $user, MobileAppAiConversation $conversation): array
    {
        $pending = $this->pending($conversation);
        $this->resetDraft($conversation);

        return $this->bookingManage->executeCancel($user, $pending);
    }

    public function confirmQty(User $user, MobileAppAiConversation $conversation): array
    {
        $pending = $this->pending($conversation);
        $this->resetDraft($conversation);
        $cartId = (string) ($pending['cart_id'] ?? '');
        $qty = (int) ($pending['quantity'] ?? 0);
        if ($cartId === '' || $qty < 1) {
            return ['ok' => false, 'customer_message' => 'Invalid quantity change.'];
        }

        $cart = Cart::query()
            ->where('id', $cartId)
            ->where('customer_id', (string) $user->id)
            ->where('is_guest', false)
            ->first();
        if (! $cart) {
            return ['ok' => false, 'customer_message' => 'Cart line not found.'];
        }

        if (! $this->updateCartQuantity($cartId, $qty)) {
            return ['ok' => false, 'customer_message' => 'Could not update quantity.'];
        }

        return [
            'ok' => true,
            'customer_message' => 'Updated quantity to **'.$qty.'**. Say **show my cart** for the new total.',
            'cart_updated' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array{reply: string, ui: mixed, cart_updated: bool}
     */
    private function handleConfirmStep(User $user, MobileAppAiConversation $conversation, string $text, array $draft): array
    {
        if (MobileAppAiBookingMessageDetector::isNegative($text)) {
            $r = $this->cancelPending($conversation);

            return ['reply' => (string) $r['customer_message'], 'ui' => $r['ui'] ?? null, 'cart_updated' => false];
        }

        if (MobileAppAiBookingMessageDetector::isAffirmative($text)
            || MobileAppAiBookingMessageDetector::wantsProceedServiceConfirm($text)) {
            $step = (string) ($draft['step'] ?? '');
            $result = match ($step) {
                'coupon_confirm' => $this->confirmCoupon($user, $conversation),
                'bid_confirm' => $this->confirmBid($user, $conversation),
                'booking_cancel_confirm' => $this->confirmBookingCancel($user, $conversation),
                'qty_confirm' => $this->confirmQty($user, $conversation),
                default => ['ok' => false, 'customer_message' => 'Nothing to confirm.'],
            };

            return [
                'reply' => (string) ($result['customer_message'] ?? ''),
                'ui' => $result['ui'] ?? MobileAppAiConfirmUi::agentMenu(),
                'cart_updated' => ($result['cart_updated'] ?? false) === true,
            ];
        }

        return [
            'reply' => MobileAppAiReplyStyle::localize(
                'Please confirm with **Yes** or tap the button, or **Cancel** to keep things as they are.',
                '**Haan, kar do** tap karein ya **yes** likhein. **Cancel** agar mann badal gaya.',
                $text
            ),
            'ui' => $draft['choices']['confirm_ui'] ?? null,
            'cart_updated' => false,
        ];
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: mixed, cart_updated?: bool}
     */
    private function handleCoupon(User $user, MobileAppAiConversation $conversation, string $text): array
    {
        if (preg_match('/\b(list|show|my)\s+coupons?\b/iu', $text)) {
            return $this->couponService->listCoupons($user);
        }

        if (MobileAppAiCouponService::wantsRemoveCoupon($text)) {
            $this->saveConfirmDraft($conversation, 'coupon_confirm', ['op' => 'remove_coupon'], 'remove the coupon from your cart', $text);

            return [
                'ok' => true,
                'customer_message' => MobileAppAiReplyStyle::localize(
                    'Remove the coupon from your cart?',
                    'Cart se coupon hata du?',
                    $text
                ),
                'ui' => MobileAppAiConfirmUi::confirmCancel('coupon_confirm', 'coupon', $text),
            ];
        }

        $code = MobileAppAiCouponService::extractCouponCode($text);
        if ($code === '') {
            return ['ok' => false, 'customer_message' => 'Which coupon code should I apply? Say **apply coupon SAVE10** or **list coupons**.'];
        }

        $this->saveConfirmDraft($conversation, 'coupon_confirm', ['op' => 'apply_coupon', 'code' => $code], 'apply coupon '.$code, $text);

        return [
            'ok' => true,
            'customer_message' => MobileAppAiReplyStyle::localize(
                'Apply coupon **'.$code.'** to your cart?',
                'Coupon **'.$code.'** cart par apply kar du?',
                $text
            ),
            'ui' => MobileAppAiConfirmUi::confirmCancel('coupon_confirm', 'coupon', $text),
        ];
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: mixed, cart_updated?: bool}
     */
    private function handleBidding(User $user, MobileAppAiConversation $conversation, string $text): array
    {
        if (MobileAppAiBiddingService::looksLikeAcceptBid($text)) {
            $built = $this->biddingService->buildAcceptConfirm($user, $text);
            if (($built['ok'] ?? false) && isset($built['pending'])) {
                $this->saveConfirmDraft($conversation, 'bid_confirm', $built['pending'], 'accept this bid', $text);

                return $built;
            }

            return $built;
        }

        if (MobileAppAiBiddingService::looksLikeDenyBid($text)) {
            $built = $this->biddingService->buildDenyConfirm($user, $text);
            if (($built['ok'] ?? false) && isset($built['pending'])) {
                $this->saveConfirmDraft($conversation, 'bid_confirm', $built['pending'], 'decline this bid', $text);

                return $built;
            }

            return $built;
        }

        if (preg_match('/\b(show|list|my)\s+bids?\b/iu', $text) || preg_match('/\bshow\s+bids?\b/iu', $text)) {
            return $this->biddingService->listBidsForLatestPost($user);
        }

        if (preg_match('/\b(create|post|new)\b.*\b(bid|bidding|post)\b/iu', $text)
            || preg_match('/\bbidding\s+post\b/iu', $text)) {
            return $this->biddingService->createPostFromDescription($user, $text);
        }

        if (preg_match('/\b(my\s+)?bids?\b/iu', $text) && ! preg_match('/\baccept|deny|decline\b/iu', $text)) {
            return $this->biddingService->listPosts($user);
        }

        return $this->biddingService->listPosts($user);
    }

    /**
     * @param  array{cart_id: string, quantity: int, label: string}  $parsed
     * @return array{ok: bool, customer_message: string, ui?: mixed}
     */
    private function beginQtyConfirm(User $user, MobileAppAiConversation $conversation, array $parsed, string $userText = ''): array
    {
        $cart = Cart::query()
            ->where('id', $parsed['cart_id'])
            ->where('customer_id', (string) $user->id)
            ->first();
        if (! $cart) {
            return ['ok' => false, 'customer_message' => 'I could not find that cart item.'];
        }

        $conversation->booking_draft = [
            'step' => 'qty_confirm',
            'choices' => [
                'agent_pending' => [
                    'cart_id' => $parsed['cart_id'],
                    'quantity' => $parsed['quantity'],
                ],
                'cart_confirm_summary' => 'set quantity to '.$parsed['quantity'],
                'confirm_ui' => MobileAppAiConfirmUi::confirmCancel('qty_confirm', 'cart_qty', $userText),
            ],
        ];
        $conversation->save();

        return [
            'ok' => true,
            'customer_message' => MobileAppAiReplyStyle::localize(
                'Set **'.$parsed['label'].'** quantity to **'.$parsed['quantity'].'**?',
                '**'.$parsed['label'].'** ki quantity **'.$parsed['quantity'].'** kar du?',
                $userText
            ),
            'ui' => MobileAppAiConfirmUi::confirmCancel('qty_confirm', 'cart_qty', $userText),
        ];
    }

    /**
     * @return array{cart_id: string, quantity: int, label: string}|null
     */
    public function parseQuantityChangeForUser(User $user, string $text): ?array
    {
        if (! preg_match('/\b(qty|quantity|set)\b/iu', $text) && ! preg_match('/\b(\d{1,4})\s*x\b/iu', $text)) {
            return null;
        }

        $qty = 0;
        if (preg_match('/\b(?:qty|quantity)\s*(?:to|=)?\s*(\d{1,4})\b/iu', $text, $m)) {
            $qty = (int) $m[1];
        } elseif (preg_match('/\b(\d{1,4})\s*x\b/iu', $text, $m)) {
            $qty = (int) $m[1];
        }
        if ($qty < 1 || $qty > 1000) {
            return null;
        }

        $target = '';
        if (preg_match('/\b(?:for|of)\s+(.+?)(?:\s+(?:qty|quantity|to|=|\d+\s*x)\b|$)/iu', $text, $m)) {
            $target = trim((string) ($m[1] ?? ''));
        }

        $lines = Cart::query()
            ->where('customer_id', (string) $user->id)
            ->where('is_guest', false)
            ->get();

        if ($lines->isEmpty()) {
            return null;
        }

        $cart = $lines->first();
        if ($target !== '') {
            foreach ($lines as $line) {
                $name = (string) (Service::query()->where('id', $line->service_id)->value('name') ?? '');
                if ($name !== '' && stripos($name, $target) !== false) {
                    $cart = $line;
                    break;
                }
            }
        }

        $label = (string) (Service::query()->where('id', $cart->service_id)->value('name') ?? 'item');

        return [
            'cart_id' => (string) $cart->id,
            'quantity' => $qty,
            'label' => $label,
        ];
    }

    private static function extractDetailsQuery(string $text): string
    {
        if (preg_match('/\b(?:about|for|of)\s+(.+)$/iu', $text, $m)) {
            return trim((string) ($m[1] ?? ''));
        }

        return trim((string) preg_replace('/\b(service\s+details?|tell\s+me|what\s+is|price\s+of|how\s+much\s+for)\b/iu', '', $text));
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array{reply: string, ui: mixed, cart_updated: bool}
     */
    private function wrap(array $result, bool $forceCartUpdated = false): array
    {
        return [
            'reply' => (string) ($result['customer_message'] ?? ''),
            'ui' => $result['ui'] ?? null,
            'cart_updated' => $forceCartUpdated || (($result['cart_updated'] ?? false) === true),
        ];
    }

    /**
     * @param  array<string, mixed>  $pending
     */
    private function saveConfirmDraft(
        MobileAppAiConversation $conversation,
        string $step,
        array $pending,
        string $summary,
        string $userText = '',
    ): void {
        $actionKey = str_replace('_confirm', '', $step);
        $conversation->booking_draft = [
            'step' => $step,
            'choices' => [
                'agent_pending' => $pending,
                'cart_confirm_summary' => $summary,
                'confirm_ui' => MobileAppAiConfirmUi::confirmCancel($step, $actionKey, $userText),
            ],
        ];
        $conversation->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function pending(MobileAppAiConversation $conversation): array
    {
        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];

        return is_array($draft['choices']['agent_pending'] ?? null) ? $draft['choices']['agent_pending'] : [];
    }

    private function resetDraft(MobileAppAiConversation $conversation): void
    {
        $conversation->booking_draft = null;
        $conversation->save();
    }
}
