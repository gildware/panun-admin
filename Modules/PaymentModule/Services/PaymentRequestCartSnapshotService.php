<?php

namespace Modules\PaymentModule\Services;

use Illuminate\Support\Collection;
use Modules\CartModule\Entities\Cart;

final class PaymentRequestCartSnapshotService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function buildSnapshot(string $customerUserId): array
    {
        if ($customerUserId === '') {
            return [];
        }

        return Cart::query()
            ->where('customer_id', $customerUserId)
            ->get()
            ->map(static fn (Cart $item) => $item->toArray())
            ->values()
            ->all();
    }

    /**
     * Use live cart first; fall back to checkout snapshot stored on the payment request.
     */
    public function cartLinesForBooking(string $customerUserId, mixed $request): Collection
    {
        $cartData = Cart::with(['service.category', 'service.subCategory'])
            ->where('customer_id', $customerUserId)
            ->get();

        if ($cartData->isNotEmpty()) {
            return $cartData;
        }

        $snapshot = match (true) {
            $request instanceof Collection => $request->get('cart_snapshot'),
            is_array($request) => $request['cart_snapshot'] ?? null,
            default => null,
        };

        if (! is_array($snapshot) || $snapshot === []) {
            return $cartData;
        }

        return collect($snapshot)->map(static function (array $row) {
            $cart = new Cart();
            $cart->forceFill($row);
            $cart->exists = true;

            return $cart;
        });
    }
}
