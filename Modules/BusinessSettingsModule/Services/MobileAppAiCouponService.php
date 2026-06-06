<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\Config;
use Modules\BookingModule\Entities\Booking;
use Modules\CartModule\Entities\Cart;
use Modules\PromotionManagement\Entities\Coupon;
use Modules\PromotionManagement\Entities\CouponCustomer;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;

class MobileAppAiCouponService
{
    public function __construct(
        protected MobileAppAiCatalogSearchService $catalogSearch,
    ) {}

    public static function looksLikeCouponIntent(string $text): bool
    {
        $t = mb_strtolower(trim($text));

        return (bool) preg_match(
            '/\b(apply\s+coupon|use\s+coupon|coupon\s+code|promo\s+code|voucher|remove\s+coupon|clear\s+coupon|my\s+coupons|list\s+coupons)\b/iu',
            $t
        ) || (bool) preg_match('/\b(apply|use)\s+[A-Z0-9]{3,}\b/u', $text);
    }

    public static function extractCouponCode(string $text): string
    {
        if (preg_match('/\b(?:apply|use)\s+(?:coupon\s+)?([A-Za-z0-9_-]{3,24})\b/iu', $text, $m)) {
            return strtoupper(trim($m[1]));
        }
        if (preg_match('/\bcode\s+([A-Za-z0-9_-]{3,24})\b/iu', $text, $m)) {
            return strtoupper(trim($m[1]));
        }

        return '';
    }

    public static function wantsRemoveCoupon(string $text): bool
    {
        return (bool) preg_match('/\b(remove|clear|delete)\s+(?:the\s+)?coupon\b/iu', $text);
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: array<string, mixed>}
     */
    public function listCoupons(User $user): array
    {
        $zoneId = $this->catalogSearch->resolveCustomerZoneId($user);
        if ($zoneId === null) {
            return [
                'ok' => false,
                'customer_message' => 'Add a saved address with a service zone first, then I can show coupons for your area.',
            ];
        }

        $lines = [];
        $this->withZone($zoneId, function () use ($zoneId, &$lines): void {
            $coupons = Coupon::query()
                ->with(['discount'])
                ->whereHas('discount', function ($q): void {
                    $q->where('promotion_type', 'coupon')
                        ->where('is_active', 1)
                        ->whereDate('start_date', '<=', now())
                        ->whereDate('end_date', '>=', now());
                })
                ->whereHas('discount.discount_types', function ($q) use ($zoneId): void {
                    $q->where(['discount_type' => 'zone', 'type_wise_id' => $zoneId]);
                })
                ->limit(12)
                ->get();

            foreach ($coupons as $c) {
                $code = (string) ($c->coupon_code ?? '');
                $title = (string) ($c->discount?->title ?? 'Coupon');
                $lines[] = '• **'.$code.'** — '.$title;
            }
        });

        if ($lines === []) {
            return [
                'ok' => true,
                'customer_message' => 'No active coupons for your zone right now. Check **Vouchers** in the menu for updates.',
                'ui' => MobileAppAiConfirmUi::agentMenu(),
            ];
        }

        return [
            'ok' => true,
            'customer_message' => "Active coupons you can try:\n\n".implode("\n", $lines)."\n\nSay **apply coupon CODE** to use one on your cart.",
            'ui' => MobileAppAiConfirmUi::agentMenu(),
        ];
    }

    /**
     * @return array{ok: bool, customer_message: string, cart_updated?: bool}
     */
    public function applyCoupon(User $user, string $code): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return ['ok' => false, 'customer_message' => 'Which coupon code should I apply?'];
        }

        $zoneId = $this->catalogSearch->resolveCustomerZoneId($user);
        if ($zoneId === null) {
            return ['ok' => false, 'customer_message' => 'I need a saved address with a zone before applying a coupon.'];
        }

        $customerId = (string) $user->id;
        $cartItems = Cart::query()->where('customer_id', $customerId)->where('is_guest', false)->get();
        if ($cartItems->isEmpty()) {
            return ['ok' => false, 'customer_message' => 'Your cart is empty — add a service first, then apply a coupon.'];
        }

        return $this->withZone($zoneId, function () use ($user, $code, $cartItems, $customerId): array {
            $typeWiseId = ['service_ids' => [], 'category_ids' => []];
            foreach ($cartItems as $item) {
                $typeWiseId['service_ids'][] = $item->service_id;
                $typeWiseId['category_ids'][] = $item->category_id;
            }

            $coupon = Coupon::query()
                ->withoutGlobalScope('zone_wise_data')
                ->with(['discount.discount_types'])
                ->where('coupon_code', $code)
                ->whereHas('discount', function ($q): void {
                    $q->where('promotion_type', 'coupon')->where('is_active', 1)
                        ->whereDate('start_date', '<=', now())->whereDate('end_date', '>=', now());
                })
                ->whereHas('discount.discount_types', function ($q) use ($zoneId): void {
                    $q->where(['discount_type' => 'zone', 'type_wise_id' => $zoneId]);
                })
                ->first();

            if (! $coupon) {
                return ['ok' => false, 'customer_message' => 'Coupon **'.$code.'** is not valid for your area or has expired.'];
            }

            if (! $this->couponEligibleForUser($coupon, $user)) {
                return ['ok' => false, 'customer_message' => 'Coupon **'.$code.'** cannot be used on your account.'];
            }

            $discountedIds = $coupon->discount->discount_types->pluck('type_wise_id')->toArray();
            $applied = 0;
            foreach ($cartItems as $item) {
                if (! in_array($item->service_id, $discountedIds, true) && ! in_array($item->category_id, $discountedIds, true)) {
                    continue;
                }
                $this->recalculateCartLineWithCoupon($item, $coupon);
                $applied++;
            }

            if ($applied === 0) {
                return ['ok' => false, 'customer_message' => 'Coupon **'.$code.'** does not apply to the services currently in your cart.'];
            }

            return [
                'ok' => true,
                'customer_message' => 'Applied coupon **'.$code.'** to your cart. Say **show my cart** to see the updated total.',
                'cart_updated' => true,
            ];
        });
    }

    /**
     * @return array{ok: bool, customer_message: string, cart_updated?: bool}
     */
    public function removeCoupon(User $user): array
    {
        $customerId = (string) $user->id;
        $cartItems = Cart::query()->where('customer_id', $customerId)->where('is_guest', false)->get();
        if ($cartItems->isEmpty()) {
            return ['ok' => true, 'customer_message' => 'Your cart is empty — no coupon to remove.'];
        }

        foreach ($cartItems as $cart) {
            $service = Service::with(['category', 'subCategory'])->find($cart->service_id);
            if (! $service) {
                continue;
            }
            $basicDiscount = $cart->discount_amount;
            $campaignDiscount = $cart->campaign_discount;
            $subtotal = round($cart->service_cost * $cart->quantity, 2);
            $applicableDiscount = ($campaignDiscount >= $basicDiscount) ? $campaignDiscount : $basicDiscount;
            $tax = round(((($cart->service_cost * $cart->quantity) - $applicableDiscount) * effective_service_tax_percentage($service)) / 100, 2);
            $cart->coupon_discount = 0;
            $cart->coupon_code = null;
            $cart->coupon_id = null;
            $cart->tax_amount = $tax;
            $cart->total_cost = round($subtotal - $applicableDiscount + $tax, 2);
            $cart->save();
        }

        return [
            'ok' => true,
            'customer_message' => 'Removed coupon from your cart.',
            'cart_updated' => true,
        ];
    }

    private function couponEligibleForUser(Coupon $coupon, User $user): bool
    {
        $customerId = (string) $user->id;
        if ($coupon->coupon_type === 'customer_wise') {
            return CouponCustomer::query()
                ->where('coupon_id', $coupon->id)
                ->where('customer_user_id', $customerId)
                ->exists();
        }
        if ($coupon->coupon_type === 'first_booking') {
            return Booking::query()->where('customer_id', $customerId)->count() <= 1;
        }

        $used = Booking::query()->where('customer_id', $customerId)->where('coupon_code', $coupon->coupon_code)->count();

        return $used < (int) ($coupon->discount->limit_per_user ?? 1);
    }

    private function recalculateCartLineWithCoupon(Cart $cartItem, Coupon $coupon): void
    {
        $service = Service::with(['category', 'subCategory'])->find($cartItem->service_id);
        if (! $service) {
            return;
        }
        $basicDiscount = $cartItem->discount_amount;
        $campaignDiscount = $cartItem->campaign_discount;
        $applicableDiscount = ($campaignDiscount >= $basicDiscount) ? $campaignDiscount : $basicDiscount;
        $couponDiscountAmount = booking_discount_calculator(
            $coupon->discount,
            (($cartItem->service_cost * $cartItem->quantity) - $applicableDiscount)
        );
        $subtotal = round($cartItem->service_cost * $cartItem->quantity, 2);
        $tax = round((((($cartItem->service_cost * $cartItem->quantity) - $applicableDiscount - $couponDiscountAmount)
            * effective_service_tax_percentage($service)) / 100), 2);
        $cartItem->coupon_discount = $couponDiscountAmount;
        $cartItem->coupon_code = $coupon->coupon_code;
        $cartItem->coupon_id = $coupon->id;
        $cartItem->tax_amount = $tax;
        $cartItem->total_cost = round($subtotal - $applicableDiscount - $couponDiscountAmount + $tax, 2);
        $cartItem->save();
    }

    /**
     * @template T
     * @param  callable(): T  $fn
     * @return T
     */
    private function withZone(string $zoneId, callable $fn)
    {
        $previous = Config::get('zone_id');
        Config::set('zone_id', $zoneId);
        try {
            return $fn();
        } finally {
            if ($previous !== null) {
                Config::set('zone_id', $previous);
            }
        }
    }
}
