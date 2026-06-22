<?php

namespace Modules\PaymentModule\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\PaymentModule\Entities\PaymentRequest;

final class PaymentRequestCompletionService
{
    private ?string $lastError = null;

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ALREADY_COMPLETED = 'already_completed';

    public const STATUS_AMOUNT_MISMATCH = 'amount_mismatch';

    public const STATUS_NOT_FOUND = 'not_found';

    public const STATUS_FAILED = 'failed';

    public const STATUS_FULFILLMENT_FAILED = 'fulfillment_failed';

    public function amountMatches(PaymentRequest $paymentRequest, float $paidAmount): bool
    {
        $expected = round((float) $paymentRequest->payment_amount, 2);
        $paidAmount = round(max(0.0, $paidAmount), 2);

        if ($expected <= 0) {
            return false;
        }

        return $paidAmount + 0.009 >= $expected;
    }

    /**
     * Idempotently run success hook and mark payment paid only after fulfillment succeeds.
     */
    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function complete(
        PaymentRequest $paymentRequest,
        string $transactionId,
        string $paymentMethod,
        ?float $paidAmount = null
    ): string {
        $this->lastError = null;

        try {
            return DB::transaction(function () use ($paymentRequest, $transactionId, $paymentMethod, $paidAmount) {
                $locked = PaymentRequest::query()
                    ->whereKey($paymentRequest->id)
                    ->lockForUpdate()
                    ->first();

                if (! $locked) {
                    $this->lastError = 'Payment request record not found in database';

                    return self::STATUS_NOT_FOUND;
                }

                if ((int) $locked->is_paid === 1 && app(PaymentRequestFulfillmentService::class)->isComplete($locked)) {
                    return self::STATUS_ALREADY_COMPLETED;
                }

                if ((int) $locked->is_paid !== 1 && $paidAmount !== null && ! $this->amountMatches($locked, $paidAmount)) {
                    $this->lastError = sprintf(
                        'Paid amount (%.2f) is less than expected checkout amount (%.2f)',
                        $paidAmount,
                        (float) $locked->payment_amount
                    );

                    return self::STATUS_AMOUNT_MISMATCH;
                }

                $locked->payment_method = $paymentMethod;
                $locked->transaction_id = $transactionId;
                $locked->save();

                $this->runSuccessHook($locked);

                $fresh = PaymentRequest::query()->find($locked->id);
                if (! $fresh || ! app(PaymentRequestFulfillmentService::class)->isComplete($fresh)) {
                    $this->lastError = 'Payment captured but booking was not created (check cart / checkout data)';

                    Log::error('Payment fulfillment incomplete after success hook', [
                        'payment_request_id' => $locked->id,
                        'success_hook' => $locked->success_hook,
                        'transaction_id' => $transactionId,
                    ]);

                    throw new \RuntimeException('Payment fulfillment incomplete');
                }

                if ((int) $fresh->is_paid !== 1) {
                    $fresh->is_paid = 1;
                    $fresh->save();
                }

                return self::STATUS_COMPLETED;
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Payment fulfillment incomplete') {
                return self::STATUS_FULFILLMENT_FAILED;
            }

            $this->lastError = $e->getMessage();

            Log::error('Payment request completion failed', [
                'payment_request_id' => $paymentRequest->id,
                'transaction_id' => $transactionId,
                'payment_method' => $paymentMethod,
                'message' => $e->getMessage(),
            ]);

            return self::STATUS_FAILED;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();

            Log::error('Payment request completion failed', [
                'payment_request_id' => $paymentRequest->id,
                'transaction_id' => $transactionId,
                'payment_method' => $paymentMethod,
                'message' => $e->getMessage(),
            ]);

            return self::STATUS_FAILED;
        }
    }

    private function runSuccessHook(PaymentRequest $paymentRequest): void
    {
        $fresh = PaymentRequest::query()->find($paymentRequest->id);
        if (! $fresh) {
            return;
        }

        $hook = $fresh->success_hook ?? '';
        if (! is_string($hook) || $hook === '' || ! function_exists($hook)) {
            return;
        }

        call_user_func($hook, $fresh);
    }

    public function isSuccessStatus(string $status): bool
    {
        return in_array($status, [self::STATUS_COMPLETED, self::STATUS_ALREADY_COMPLETED], true);
    }
}
