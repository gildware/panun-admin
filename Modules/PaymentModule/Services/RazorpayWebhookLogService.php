<?php

namespace Modules\PaymentModule\Services;

use Modules\BookingModule\Entities\Booking;
use Modules\CartModule\Entities\Cart;
use Modules\PaymentModule\Entities\PaymentRequest;
use Modules\PaymentModule\Entities\RazorpayWebhookLog;

final class RazorpayWebhookLogService
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function log(
        ?array $payload,
        bool $signatureValid,
        string $result,
        int $httpStatus,
        ?string $paymentRequestId = null,
        ?string $errorMessage = null
    ): RazorpayWebhookLog {
        $entity = is_array($payload['payload']['payment']['entity'] ?? null)
            ? $payload['payload']['payment']['entity']
            : [];

        $notes = is_array($entity['notes'] ?? null) ? $entity['notes'] : [];
        $paymentRequestIdFromPayload = trim((string) ($notes['payment_request_id'] ?? ''));
        if ($paymentRequestId === null && $paymentRequestIdFromPayload !== '') {
            $paymentRequestId = $paymentRequestIdFromPayload;
        }

        $bookingReadableId = null;
        if ($paymentRequestId) {
            $paymentRequest = PaymentRequest::query()->find($paymentRequestId);
            if ($paymentRequest && $paymentRequest->attribute === 'booking' && ! empty($paymentRequest->attribute_id)) {
                $bookingReadableId = (string) $paymentRequest->attribute_id;
            }
        }

        $paidAmount = isset($entity['amount']) ? round((int) $entity['amount'] / 100, 2) : null;

        return RazorpayWebhookLog::query()->create([
            'event' => (string) ($payload['event'] ?? ''),
            'razorpay_payment_id' => (string) ($entity['id'] ?? ''),
            'razorpay_order_id' => (string) ($entity['order_id'] ?? ''),
            'payment_request_id' => $paymentRequestId,
            'signature_valid' => $signatureValid,
            'result' => $result,
            'http_status' => $httpStatus,
            'booking_readable_id' => $bookingReadableId,
            'paid_amount' => $paidAmount,
            'error_message' => $errorMessage,
            'payload' => $payload,
        ]);
    }

    public function resolveBookingReadableId(?string $paymentRequestId): ?string
    {
        if (! $paymentRequestId) {
            return null;
        }

        $paymentRequest = PaymentRequest::query()->find($paymentRequestId);
        if (! $paymentRequest || $paymentRequest->attribute !== 'booking' || empty($paymentRequest->attribute_id)) {
            return null;
        }

        return Booking::query()->where('readable_id', $paymentRequest->attribute_id)->exists()
            ? (string) $paymentRequest->attribute_id
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildAdminDetail(RazorpayWebhookLog $log): array
    {
        $entity = is_array($log->payload['payload']['payment']['entity'] ?? null)
            ? $log->payload['payload']['payment']['entity']
            : [];

        $notes = is_array($entity['notes'] ?? null) ? $entity['notes'] : [];
        $paymentRequestIdFromPayload = trim((string) ($notes['payment_request_id'] ?? ''));
        $resolvedPaymentRequestId = $log->payment_request_id ?: ($paymentRequestIdFromPayload !== '' ? $paymentRequestIdFromPayload : null);

        $paymentRequest = $resolvedPaymentRequestId
            ? PaymentRequest::query()->find($resolvedPaymentRequestId)
            : null;

        $paidAmount = isset($entity['amount']) ? round((int) $entity['amount'] / 100, 2) : null;
        $expectedAmount = $paymentRequest ? round((float) $paymentRequest->payment_amount, 2) : null;
        $cartItems = null;

        if ($paymentRequest && $paymentRequest->payer_id) {
            $cartItems = Cart::query()->where('customer_id', $paymentRequest->payer_id)->count();
        }

        $bookingExists = null;
        if ($paymentRequest && $paymentRequest->attribute === 'booking' && ! empty($paymentRequest->attribute_id)) {
            $bookingExists = Booking::query()
                ->where('readable_id', $paymentRequest->attribute_id)
                ->exists();
        }

        $errorMessage = $log->error_message ?: $this->defaultErrorForResult((string) $log->result);

        return [
            'payment_request_id_from_payload' => $paymentRequestIdFromPayload !== '' ? $paymentRequestIdFromPayload : null,
            'resolved_payment_request_id' => $resolvedPaymentRequestId,
            'payment_request_exists' => $paymentRequest !== null,
            'payment_request_is_paid' => $paymentRequest ? (int) $paymentRequest->is_paid === 1 : null,
            'paid_amount' => $paidAmount,
            'expected_amount' => $expectedAmount,
            'cart_items' => $cartItems,
            'booking_exists' => $bookingExists,
            'error_message' => $errorMessage,
            'diagnosis' => $this->buildDiagnosis(
                (string) $log->result,
                $errorMessage,
                $paymentRequest,
                $paidAmount,
                $expectedAmount,
                $cartItems
            ),
        ];
    }

    private function defaultErrorForResult(string $result): ?string
    {
        return match ($result) {
            'not_found' => 'Payment request not found for Razorpay order',
            'failed' => 'Payment completion failed',
            'fulfillment_failed' => 'Payment captured but booking was not created (check cart / checkout data)',
            'amount_mismatch' => 'Razorpay captured amount is less than expected checkout amount',
            'invalid_signature' => 'Invalid webhook signature',
            'invalid_payload' => 'Invalid webhook payload',
            default => null,
        };
    }

    private function buildDiagnosis(
        string $result,
        ?string $errorMessage,
        ?PaymentRequest $paymentRequest,
        ?float $paidAmount,
        ?float $expectedAmount,
        ?int $cartItems
    ): string {
        if (in_array($result, ['completed', 'already_completed'], true)) {
            return 'Webhook processed successfully. Payment and booking are in sync.';
        }

        if ($result === 'invalid_signature') {
            return 'Razorpay signature verification failed. Check that RAZORPAY_WEBHOOK_SECRET matches the secret in the Razorpay dashboard webhook settings.';
        }

        if ($result === 'not_found') {
            return 'Razorpay captured the payment, but this server could not find the checkout payment_request record. The order notes may point to a missing ID or a different environment/database.';
        }

        if ($result === 'amount_mismatch') {
            return sprintf(
                'Razorpay captured Rs %.2f but checkout expected Rs %.2f.',
                $paidAmount ?? 0,
                $expectedAmount ?? 0
            );
        }

        if ($result === 'fulfillment_failed' || ($result === 'failed' && $cartItems === 0)) {
            return 'Razorpay captured the payment, but booking creation failed. The most common cause is an empty cart when the webhook ran (user paid in UPI and did not return to the app).';
        }

        return $errorMessage ?: 'Razorpay captured the payment, but server-side checkout completion failed.';
    }
}
