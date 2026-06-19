<?php

namespace App\Services\BookingTestMatrix;

use Illuminate\Http\Request;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Http\Traits\BookingTrait;
use Modules\CartModule\Entities\Cart;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;

/**
 * Places bookings through {@see BookingTrait::placeBookingRequest()} — same path as customer checkout API.
 */
class BookingPlacementBridge
{
    use BookingTrait;

    /**
     * @param  array<string, mixed>  $checkout
     */
    public function place(
        string $customerId,
        array $serviceCtx,
        array $checkout,
        string $tag,
    ): Booking {
        $this->clearCart($customerId);
        $this->addCartLine($customerId, $serviceCtx, $checkout);

        $paymentMethod = (string) ($checkout['payment_method'] ?? 'cash_after_service');
        $transactionId = match ($paymentMethod) {
            'cash_after_service' => 'cash-payment',
            'wallet_payment' => 'wallet-payment',
            'offline_payment' => 'offline-payment',
            default => 'test-digital-' . uniqid(),
        };

        $request = new Request(array_merge([
            'payment_method' => $paymentMethod,
            'zone_id' => $serviceCtx['zone_id'],
            'service_schedule' => $checkout['service_schedule'] ?? now()->format('Y-m-d H:i:s'),
            'service_address_id' => $serviceCtx['service_address_id'],
            'service_location' => 'customer',
            'payment_amount_type' => 'full',
            'is_partial' => 0,
        ], $checkout));

        $result = $this->placeBookingRequest($customerId, $request, $transactionId, null, 0);
        if (($result['flag'] ?? '') !== 'success') {
            throw new \RuntimeException('placeBookingRequest failed: ' . json_encode($result));
        }

        $ids = $result['booking_id'] ?? [];
        $bookingId = is_array($ids) ? ($ids[0] ?? null) : $ids;
        if (! $bookingId) {
            throw new \RuntimeException('placeBookingRequest returned no booking_id');
        }

        $booking = Booking::query()->findOrFail($bookingId);
        $booking->service_description = $tag;
        $booking->save();

        return $booking->fresh();
    }

    public function clearCart(string $customerId): void
    {
        Cart::query()->where('customer_id', $customerId)->delete();
    }

    /**
     * @param  array{service_id: string, category_id: string, sub_category_id: string, variant_key: string, zone_id: string, service_address_id: int|string}  $serviceCtx
     * @param  array<string, mixed>  $checkout
     */
    public function addCartLine(string $customerId, array $serviceCtx, array $checkout): void
    {
        $service = Service::query()->findOrFail($serviceCtx['service_id']);
        $variationQuery = Variation::query()
            ->where('service_id', $service->id)
            ->where('variant_key', $serviceCtx['variant_key'])
            ->where('price', '>', 0);

        if (! empty($serviceCtx['variation_id'])) {
            $variationQuery->where('id', $serviceCtx['variation_id']);
        }

        $variation = $variationQuery->orderByDesc('price')->firstOrFail();

        $qty = (int) ($checkout['quantity'] ?? 1);
        $basicDiscount = basic_discount_calculation($service, $variation->price * $qty);
        $campaignDiscount = campaign_discount_calculation($service, $variation->price * $qty);
        $subtotal = round($variation->price * $qty, 2);
        $applicableDiscount = ($campaignDiscount >= $basicDiscount) ? $campaignDiscount : $basicDiscount;
        $tax = round((($variation->price * $qty - $applicableDiscount) * effective_service_tax_percentage($service)) / 100, 2);
        $basicDiscount = $basicDiscount > $campaignDiscount ? $basicDiscount : 0;
        $campaignDiscount = $campaignDiscount >= $basicDiscount ? $campaignDiscount : 0;

        $cart = new Cart;
        $cart->provider_id = $checkout['provider_id'] ?? null;
        $cart->customer_id = $customerId;
        $cart->service_id = $service->id;
        $cart->category_id = $service->category_id;
        $cart->sub_category_id = $service->sub_category_id;
        $cart->variant_key = $variation->variant_key;
        $cart->quantity = $qty;
        $cart->service_cost = $variation->price;
        $cart->discount_amount = $basicDiscount;
        $cart->campaign_discount = $campaignDiscount;
        $cart->coupon_discount = 0;
        $cart->coupon_code = null;
        $cart->is_guest = 0;
        $cart->tax_amount = round($tax, 2);
        $cart->total_cost = round($subtotal - $basicDiscount - $campaignDiscount + $tax, 2);
        $cart->zone_id = $serviceCtx['zone_id'];
        $cart->service_address_id = $serviceCtx['service_address_id'];
        if (! empty($checkout['service_schedule'])) {
            $cart->service_schedule = date('Y-m-d H:i:s', strtotime((string) $checkout['service_schedule']));
        }
        $cart->save();
    }
}
