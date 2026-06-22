<?php

namespace Modules\PaymentModule\Services;

use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\PaymentModule\Entities\PaymentRequest;

final class PaymentRequestFulfillmentService
{
    public function isComplete(PaymentRequest $data): bool
    {
        $fresh = PaymentRequest::query()->find($data->id);
        if (! $fresh) {
            return false;
        }

        $hook = (string) ($fresh->success_hook ?? '');

        if ($hook === 'digital_payment_success') {
            return $fresh->attribute === 'booking'
                && ! empty($fresh->attribute_id)
                && Booking::query()->where('readable_id', $fresh->attribute_id)->exists();
        }

        if ($hook === 'repeat_booking_payment_success') {
            $additional = json_decode($fresh->additional_data, true);
            $repeatId = is_array($additional) ? ($additional['booking_repeat_id'] ?? null) : null;
            if (! $repeatId) {
                return false;
            }

            return (int) (BookingRepeat::query()->find($repeatId)?->is_paid ?? 0) === 1;
        }

        if ($hook === 'switch_offline_to_digital_payment_success') {
            $additional = json_decode($fresh->additional_data, true);
            $bookingId = is_array($additional) ? ($additional['booking_id'] ?? null) : null;
            $transactionId = trim((string) ($fresh->transaction_id ?? ''));
            if (! $bookingId || $transactionId === '') {
                return false;
            }

            $booking = Booking::query()->find($bookingId);

            return $booking && $this->bookingPartialPaymentExistsForTransaction($booking, $transactionId);
        }

        if ($hook === 'subscription_success') {
            return ! empty($fresh->transaction_id);
        }

        return ! empty($fresh->transaction_id);
    }

    public function needsFulfillment(PaymentRequest $data): bool
    {
        return ! $this->isComplete($data);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function bookingFulfillmentResponse(PaymentRequest $data): ?array
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

    /**
     * @param  array<string, mixed>|null  $additionalData
     */
    public function resolveCheckoutPaymentAmountType(PaymentRequest $data, ?array $additionalData = null): string
    {
        $additionalData = $additionalData ?? (json_decode($data->additional_data, true) ?: []);
        $paymentAmountType = $additionalData['payment_amount_type'] ?? null;

        if (in_array($paymentAmountType, ['confirmation', 'full'], true)) {
            return $paymentAmountType;
        }

        $customerUserId = (string) ($data->payer_id ?? '');
        $fullAmount = function_exists('booking_full_checkout_amount_for_customer') && $customerUserId !== ''
            ? \booking_full_checkout_amount_for_customer($customerUserId)
            : 0.0;
        $chargedAmount = round((float) ($data->payment_amount ?? 0), 2);

        if ($fullAmount > 0 && $chargedAmount > 0 && $chargedAmount < $fullAmount - 0.009) {
            return 'confirmation';
        }

        return 'full';
    }

    public function bookingPartialPaymentExistsForTransaction(Booking $booking, string $transactionId): bool
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
