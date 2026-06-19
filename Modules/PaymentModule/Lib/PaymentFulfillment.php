<?php

use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\PaymentModule\Entities\PaymentRequest;
use Modules\PaymentModule\Services\PaymentRequestFulfillmentService;

if (! function_exists('payment_request_fulfillment_complete')) {
    function payment_request_fulfillment_complete(PaymentRequest $data): bool
    {
        return app(PaymentRequestFulfillmentService::class)->isComplete($data);
    }
}

if (! function_exists('payment_request_needs_fulfillment')) {
    function payment_request_needs_fulfillment(PaymentRequest $data): bool
    {
        return app(PaymentRequestFulfillmentService::class)->needsFulfillment($data);
    }
}

if (! function_exists('payment_request_booking_fulfillment_response')) {
    /**
     * @return array<string, mixed>|null
     */
    function payment_request_booking_fulfillment_response(PaymentRequest $data): ?array
    {
        return app(PaymentRequestFulfillmentService::class)->bookingFulfillmentResponse($data);
    }
}

if (! function_exists('resolve_checkout_payment_amount_type_from_request')) {
    /**
     * @param  array<string, mixed>|null  $additionalData
     */
    function resolve_checkout_payment_amount_type_from_request(PaymentRequest $data, ?array $additionalData = null): string
    {
        return app(PaymentRequestFulfillmentService::class)
            ->resolveCheckoutPaymentAmountType($data, $additionalData);
    }
}

if (! function_exists('booking_partial_payment_exists_for_transaction')) {
    function booking_partial_payment_exists_for_transaction(Booking $booking, string $transactionId): bool
    {
        return app(PaymentRequestFulfillmentService::class)
            ->bookingPartialPaymentExistsForTransaction($booking, $transactionId);
    }
}

if (! function_exists('build_payment_request_cart_snapshot')) {
    /**
     * @return list<array<string, mixed>>
     */
    function build_payment_request_cart_snapshot(string $customerUserId): array
    {
        return app(\Modules\PaymentModule\Services\PaymentRequestCartSnapshotService::class)
            ->buildSnapshot($customerUserId);
    }
}

if (! function_exists('payment_request_cart_lines_for_booking')) {
    function payment_request_cart_lines_for_booking(string $customerUserId, mixed $request): Collection
    {
        return app(\Modules\PaymentModule\Services\PaymentRequestCartSnapshotService::class)
            ->cartLinesForBooking($customerUserId, $request);
    }
}
