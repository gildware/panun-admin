<?php

use Modules\BookingModule\Entities\Booking;
use Modules\PaymentModule\Entities\PaymentRequest;

if (! function_exists('payment_request_booking_fulfillment_response')) {
    /**
     * When a payment request already created a booking, return success without re-running hooks.
     *
     * @return array<string, mixed>|null
     */
    function payment_request_booking_fulfillment_response(PaymentRequest $data): ?array
    {
        $fresh = PaymentRequest::query()->find($data->id);
        if (! $fresh || (int) $fresh->is_paid !== 1) {
            return null;
        }

        if ($fresh->attribute !== 'booking' || empty($fresh->attribute_id)) {
            return null;
        }

        $additional = json_decode($fresh->additional_data, true);
        $additional = is_array($additional) ? $additional : [];

        return [
            'flag' => 'success',
            'booking_id' => Booking::query()->where('readable_id', $fresh->attribute_id)->value('id'),
            'readable_id' => $fresh->attribute_id,
            'callback' => $additional['callback'] ?? null,
        ];
    }
}

if (! function_exists('resolve_checkout_payment_amount_type_from_request')) {
    /**
     * @param  array<string, mixed>|null  $additionalData
     */
    function resolve_checkout_payment_amount_type_from_request(PaymentRequest $data, ?array $additionalData = null): string
    {
        $additionalData = $additionalData ?? (json_decode($data->additional_data, true) ?: []);
        $paymentAmountType = $additionalData['payment_amount_type'] ?? null;

        if (in_array($paymentAmountType, ['confirmation', 'full'], true)) {
            return $paymentAmountType;
        }

        $customerUserId = (string) ($data->payer_id ?? '');
        $fullAmount = function_exists('booking_full_checkout_amount_for_customer') && $customerUserId !== ''
            ? booking_full_checkout_amount_for_customer($customerUserId)
            : 0.0;
        $chargedAmount = round((float) ($data->payment_amount ?? 0), 2);

        if ($fullAmount > 0 && $chargedAmount > 0 && $chargedAmount < $fullAmount - 0.009) {
            return 'confirmation';
        }

        return 'full';
    }
}

if (! function_exists('booking_partial_payment_exists_for_transaction')) {
    function booking_partial_payment_exists_for_transaction(Booking $booking, string $transactionId): bool
    {
        $transactionId = trim($transactionId);
        if ($transactionId === '') {
            return false;
        }

        return $booking->booking_partial_payments()
            ->where('transaction_id', $transactionId)
            ->exists();
    }
}
