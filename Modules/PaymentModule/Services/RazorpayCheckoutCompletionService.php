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

    public const STATUS_FULFILLMENT_FAILED = 'fulfillment_failed';

    private ?string $lastCompletionError = null;

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

        $mode = ($config->mode ?? 'test') === 'live' ? 'live' : 'test';
        $credentialsColumn = $mode . '_values';
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
        if (! config('razor_config')) {
            $this->configureApiFromGatewaySettings();
        }

        $secret = config('razor_config.webhook_secret') ?: env('RAZORPAY_WEBHOOK_SECRET');

        return is_string($secret) && trim($secret) !== '' ? trim($secret) : null;
    }

    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        $secret = $this->webhookSecret();
        if ($secret === null || $signature === null || trim($signature) === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, trim($signature));
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

            return $this->resolvePaymentRequestById($orderId);
        }

        $paymentRequestId = (string) ($orderArray['notes']['payment_request_id'] ?? '');
        if ($paymentRequestId === '' && ! empty($orderArray['receipt'])) {
            $paymentRequestId = (string) $orderArray['receipt'];
        }

        return $this->resolvePaymentRequestById($paymentRequestId);
    }

    /**
     * @param  array<string, mixed>  $paymentEntity
     */
    public function resolvePaymentRequestFromWebhook(array $paymentEntity, ?Api $api = null): ?PaymentRequest
    {
        $notes = is_array($paymentEntity['notes'] ?? null) ? $paymentEntity['notes'] : [];
        $paymentRequestId = (string) ($notes['payment_request_id'] ?? '');

        if ($paymentRequestId !== '') {
            $resolved = $this->resolvePaymentRequestById($paymentRequestId);
            if ($resolved) {
                return $resolved;
            }
        }

        $orderId = (string) ($paymentEntity['order_id'] ?? '');
        if ($orderId !== '') {
            $resolved = $this->resolvePaymentRequestById($orderId);
            if ($resolved) {
                return $resolved;
            }

            if ($api) {
                $resolved = $this->resolvePaymentRequestFromRazorpayOrder($api, $orderId);
                if ($resolved) {
                    return $resolved;
                }
            }
        }

        $paymentId = (string) ($paymentEntity['id'] ?? '');
        if ($paymentId !== '') {
            return PaymentRequest::query()
                ->where('transaction_id', $paymentId)
                ->latest()
                ->first();
        }

        return null;
    }

    public function resolvePaymentRequestById(string $paymentRequestId): ?PaymentRequest
    {
        $paymentRequestId = trim($paymentRequestId);
        if ($paymentRequestId === '') {
            return null;
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $paymentRequestId)) {
            return PaymentRequest::query()->find($paymentRequestId);
        }

        return null;
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

        $completionService = app(PaymentRequestCompletionService::class);
        $result = $completionService->complete(
            $paymentRequest,
            $razorpayPaymentId,
            'razor_pay',
            round((int) $razorpayPayment['amount'] / 100, 2)
        );

        $this->lastCompletionError = $completionService->lastError();

        if ($result === PaymentRequestCompletionService::STATUS_FULFILLMENT_FAILED) {
            return self::STATUS_FULFILLMENT_FAILED;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, payment_request_id: string|null, error: string|null}
     */
    public function handleWebhookPayload(array $payload): array
    {
        $event = (string) ($payload['event'] ?? '');

        if ($event !== 'payment.captured') {
            return [
                'status' => 'ignored',
                'payment_request_id' => null,
                'error' => null,
            ];
        }

        $entity = $payload['payload']['payment']['entity'] ?? null;
        if (! is_array($entity)) {
            return [
                'status' => 'invalid_payload',
                'payment_request_id' => null,
                'error' => 'Missing payment entity',
            ];
        }

        $paymentId = (string) ($entity['id'] ?? '');
        if ($paymentId === '') {
            return [
                'status' => 'invalid_payload',
                'payment_request_id' => null,
                'error' => 'Missing Razorpay payment id',
            ];
        }

        $api = $this->configureApiFromGatewaySettings();
        $paymentRequest = $this->resolvePaymentRequestFromWebhook($entity, $api);

        if (! $paymentRequest) {
            $notes = is_array($entity['notes'] ?? null) ? $entity['notes'] : [];
            $hintPaymentRequestId = trim((string) ($notes['payment_request_id'] ?? ''));

            Log::warning('Razorpay webhook: payment request not found', [
                'order_id' => $entity['order_id'] ?? null,
                'payment_id' => $paymentId,
                'payment_request_id_hint' => $hintPaymentRequestId !== '' ? $hintPaymentRequestId : null,
            ]);

            return [
                'status' => self::STATUS_NOT_FOUND,
                'payment_request_id' => $hintPaymentRequestId !== '' ? $hintPaymentRequestId : null,
                'error' => $hintPaymentRequestId !== ''
                    ? 'Payment request ID from Razorpay order was not found in database (record may be missing or from another environment)'
                    : 'Payment request not found for Razorpay order',
            ];
        }

        $result = $this->completeIfValid($paymentRequest, $paymentId, $entity, $api, false);
        $completionError = $this->lastCompletionError;

        return [
            'status' => $result,
            'payment_request_id' => (string) $paymentRequest->id,
            'error' => $completionError ?? $this->defaultErrorForStatus($result),
        ];
    }

    private function defaultErrorForStatus(string $status): ?string
    {
        return match ($status) {
            self::STATUS_FULFILLMENT_FAILED => 'Payment captured but booking was not created (check cart / checkout data)',
            self::STATUS_FAILED => 'Payment completion failed',
            self::STATUS_AMOUNT_MISMATCH => 'Razorpay captured amount is less than expected checkout amount',
            self::STATUS_NOT_FOUND => 'Payment request not found for Razorpay order',
            default => null,
        };
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
