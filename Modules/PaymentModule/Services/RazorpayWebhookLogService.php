<?php

namespace Modules\PaymentModule\Services;

use Modules\BookingModule\Entities\Booking;
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

        $bookingReadableId = null;
        if ($paymentRequestId) {
            $paymentRequest = PaymentRequest::query()->find($paymentRequestId);
            if ($paymentRequest && $paymentRequest->attribute === 'booking' && ! empty($paymentRequest->attribute_id)) {
                $bookingReadableId = (string) $paymentRequest->attribute_id;
            }
        }

        return RazorpayWebhookLog::query()->create([
            'event' => (string) ($payload['event'] ?? ''),
            'razorpay_payment_id' => (string) ($entity['id'] ?? ''),
            'razorpay_order_id' => (string) ($entity['order_id'] ?? ''),
            'payment_request_id' => $paymentRequestId,
            'signature_valid' => $signatureValid,
            'result' => $result,
            'http_status' => $httpStatus,
            'booking_readable_id' => $bookingReadableId,
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
}
