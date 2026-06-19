<?php

namespace Modules\PaymentModule\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\PaymentModule\Entities\PaymentRequest;
use Razorpay\Api\Api;

final class RazorpayCheckoutCompletionService
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ALREADY_COMPLETED = 'already_completed';

    public const STATUS_AMOUNT_MISMATCH = 'amount_mismatch';

    public const STATUS_INVALID_STATUS = 'invalid_status';

    public const STATUS_NOT_FOUND = 'not_found';

    public const STATUS_FAILED = 'failed';

    /**
     * @return array<string, mixed>
     */
    public function buildOrderCreatePayload(PaymentRequest $paymentRequest): array
    {
        return [
            'receipt' => (string) $paymentRequest->id,
            'amount' => (int) (round((float) $paymentRequest->payment_amount, 2) * 100),
            'currency' => strtoupper((string) $paymentRequest->currency_code),
            'payment_capture' => 1,
            'notes' => [
                'payment_request_id' => (string) $paymentRequest->id,
            ],
        ];
    }

    public function configureApiFromGatewaySettings(): ?Api
    {
        $config = DB::table('addon_settings')
            ->where('key_name', 'razor_pay')
            ->where('settings_type', 'payment_config')
            ->first();

        if (! $config) {
            return null;
        }

        $env = env('APP_ENV') === 'live' ? 'live' : 'test';
        $credentialsColumn = $env . '_values';
        $razor = json_decode($config->{$credentialsColumn} ?? '{}');

        if (! $razor || empty($razor->api_key) || empty($razor->api_secret)) {
            return null;
        }

        Config::set('razor_config', [
            'api_key' => $razor->api_key,
            'api_secret' => $razor->api_secret,
            'webhook_secret' => $razor->webhook_secret ?? env('RAZORPAY_WEBHOOK_SECRET'),
        ]);

        return new Api($razor->api_key, $razor->api_secret);
    }

    public function webhookSecret(): ?string
    {
        $secret = config('razor_config.webhook_secret') ?: env('RAZORPAY_WEBHOOK_SECRET');

        return is_string($secret) && trim($secret) !== '' ? trim($secret) : null;
    }

    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        $secret = $this->webhookSecret();
        if ($secret === null || $signature === null || trim($signature) === '') {
            return false;
        }

        try {
            $api = $this->configureApiFromGatewaySettings();
            if (! $api) {
                return false;
            }

            $api->utility->verifyWebhookSignature($payload, $signature, $secret);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Razorpay webhook signature verification failed', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function resolvePaymentRequestFromRazorpayOrder(Api $api, string $orderId): ?PaymentRequest
    {
        try {
            $order = $api->order->fetch($orderId);
            $orderArray = $order->toArray();
        } catch (\Throwable $e) {
            Log::warning('Razorpay order fetch failed during checkout completion', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        $paymentRequestId = (string) ($orderArray['notes']['payment_request_id'] ?? '');
        if ($paymentRequestId === '' && ! empty($orderArray['receipt'])) {
            $paymentRequestId = (string) $orderArray['receipt'];
        }

        if ($paymentRequestId === '') {
            return null;
        }

        return PaymentRequest::query()->find($paymentRequestId);
    }

    /**
     * Idempotently mark payment paid and run success hook (creates booking + clears cart).
     */
    public function completeIfValid(
        PaymentRequest $paymentRequest,
        string $razorpayPaymentId,
        array $razorpayPayment,
        ?Api $api = null,
        bool $attemptCapture = false
    ): string {
        $status = (string) ($razorpayPayment['status'] ?? '');

        if (! in_array($status, ['captured', 'authorized'], true)) {
            return self::STATUS_INVALID_STATUS;
        }

        if (! $this->razorpayPaidAmountMatchesRequest($paymentRequest, $razorpayPayment)) {
            return self::STATUS_AMOUNT_MISMATCH;
        }

        if ($status === 'authorized' && $attemptCapture && $api) {
            try {
                $api->payment->fetch($razorpayPaymentId)->capture([
                    'amount' => (int) $razorpayPayment['amount'],
                ]);
            } catch (\Throwable $e) {
                Log::error('Razorpay capture failed', [
                    'payment_request_id' => $paymentRequest->id,
                    'razorpay_payment_id' => $razorpayPaymentId,
                    'message' => $e->getMessage(),
                ]);

                return self::STATUS_FAILED;
            }
        }

        return app(PaymentRequestCompletionService::class)->complete(
            $paymentRequest,
            $razorpayPaymentId,
            'razor_pay',
            round((int) $razorpayPayment['amount'] / 100, 2)
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhookPayload(array $payload): string
    {
        $event = (string) ($payload['event'] ?? '');

        if ($event !== 'payment.captured') {
            return 'ignored';
        }

        $entity = $payload['payload']['payment']['entity'] ?? null;
        if (! is_array($entity)) {
            return 'invalid_payload';
        }

        $paymentId = (string) ($entity['id'] ?? '');
        $orderId = (string) ($entity['order_id'] ?? '');

        if ($paymentId === '' || $orderId === '') {
            return 'invalid_payload';
        }

        $api = $this->configureApiFromGatewaySettings();
        if (! $api) {
            return 'api_unavailable';
        }

        $paymentRequest = $this->resolvePaymentRequestFromRazorpayOrder($api, $orderId);
        if (! $paymentRequest) {
            Log::warning('Razorpay webhook: payment request not found for order', [
                'order_id' => $orderId,
                'payment_id' => $paymentId,
            ]);

            return self::STATUS_NOT_FOUND;
        }

        if ((string) ($paymentRequest->payment_method ?? '') !== 'razor_pay') {
            // Payment request stores gateway key before redirect; allow completion regardless.
        }

        return $this->completeIfValid($paymentRequest, $paymentId, $entity, $api, false);
    }

    public function razorpayPaidAmountMatchesRequest(PaymentRequest $paymentRequest, array $razorpayPayment): bool
    {
        if (! isset($razorpayPayment['amount'])) {
            return false;
        }

        $expectedPaise = (int) round((float) $paymentRequest->payment_amount * 100);

        return (int) $razorpayPayment['amount'] >= $expectedPaise;
    }
}
