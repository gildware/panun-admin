<?php

namespace Modules\PaymentModule\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\PaymentModule\Services\RazorpayCheckoutCompletionService;
use Modules\PaymentModule\Services\RazorpayWebhookLogService;

class RazorpayWebhookController extends Controller
{
    public function __construct(
        private readonly RazorpayCheckoutCompletionService $completionService,
        private readonly RazorpayWebhookLogService $logService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        $payload = json_decode($rawBody, true);
        $payload = is_array($payload) ? $payload : null;

        $signatureValid = $this->completionService->verifyWebhookSignature($rawBody, $signature);

        if (! $signatureValid) {
            Log::warning('Razorpay webhook rejected: invalid or missing signature');

            $this->safeLog($payload, false, 'invalid_signature', 400, null, 'Invalid webhook signature');

            return response()->json(['status' => 'invalid_signature'], 400);
        }

        if ($payload === null) {
            $this->safeLog(null, true, 'invalid_payload', 400, null, 'Invalid JSON payload');

            return response()->json(['status' => 'invalid_payload'], 400);
        }

        $result = $this->completionService->handleWebhookPayload($payload);
        $status = (string) ($result['status'] ?? 'failed');
        $paymentRequestId = $result['payment_request_id'] ?? null;
        $error = $result['error'] ?? null;

        $httpStatus = match ($status) {
            RazorpayCheckoutCompletionService::STATUS_FAILED,
            RazorpayCheckoutCompletionService::STATUS_FULFILLMENT_FAILED => 500,
            RazorpayCheckoutCompletionService::STATUS_NOT_FOUND,
            RazorpayCheckoutCompletionService::STATUS_AMOUNT_MISMATCH => 422,
            'invalid_payload' => 400,
            default => 200,
        };

        $this->safeLog($payload, true, $status, $httpStatus, $paymentRequestId, $error);

        if ($httpStatus >= 500) {
            return response()->json([
                'status' => 'failed',
                'result' => $status,
                'error' => $error,
            ], $httpStatus);
        }

        return response()->json([
            'status' => 'ok',
            'result' => $status,
            'payment_request_id' => $paymentRequestId,
        ], $httpStatus);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function safeLog(
        ?array $payload,
        bool $signatureValid,
        string $result,
        int $httpStatus,
        ?string $paymentRequestId,
        ?string $errorMessage
    ): void {
        try {
            $this->logService->log(
                $payload,
                $signatureValid,
                $result,
                $httpStatus,
                $paymentRequestId,
                $errorMessage
            );
        } catch (\Throwable $e) {
            Log::error('Failed to persist Razorpay webhook log', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
