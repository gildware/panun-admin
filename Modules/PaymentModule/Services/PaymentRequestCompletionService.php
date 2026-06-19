<?php

namespace Modules\PaymentModule\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\PaymentModule\Entities\PaymentRequest;

final class PaymentRequestCompletionService
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ALREADY_COMPLETED = 'already_completed';

    public const STATUS_AMOUNT_MISMATCH = 'amount_mismatch';

    public const STATUS_NOT_FOUND = 'not_found';

    public const STATUS_FAILED = 'failed';

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
     * Idempotently mark payment paid and run success hook.
     */
    public function complete(
        PaymentRequest $paymentRequest,
        string $transactionId,
        string $paymentMethod,
        ?float $paidAmount = null
    ): string {
        if ($paidAmount !== null && ! $this->amountMatches($paymentRequest, $paidAmount)) {
            return self::STATUS_AMOUNT_MISMATCH;
        }

        try {
            return DB::transaction(function () use ($paymentRequest, $transactionId, $paymentMethod) {
                $locked = PaymentRequest::query()
                    ->whereKey($paymentRequest->id)
                    ->lockForUpdate()
                    ->first();

                if (! $locked) {
                    return self::STATUS_NOT_FOUND;
                }

                if ((int) $locked->is_paid === 1) {
                    return self::STATUS_ALREADY_COMPLETED;
                }

                $locked->payment_method = $paymentMethod;
                $locked->is_paid = 1;
                $locked->transaction_id = $transactionId;
                $locked->save();

                $fresh = PaymentRequest::query()->find($locked->id);
                if ($fresh && is_string($fresh->success_hook) && $fresh->success_hook !== '' && function_exists($fresh->success_hook)) {
                    call_user_func($fresh->success_hook, $fresh);
                }

                return self::STATUS_COMPLETED;
            });
        } catch (\Throwable $e) {
            Log::error('Payment request completion failed', [
                'payment_request_id' => $paymentRequest->id,
                'transaction_id' => $transactionId,
                'payment_method' => $paymentMethod,
                'message' => $e->getMessage(),
            ]);

            return self::STATUS_FAILED;
        }
    }

    public function isSuccessStatus(string $status): bool
    {
        return in_array($status, [self::STATUS_COMPLETED, self::STATUS_ALREADY_COMPLETED], true);
    }
}
