<?php

namespace Modules\PaymentModule\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\PaymentModule\Services\RazorpayCheckoutCompletionService;

class RazorpayWebhookController extends Controller
{
    public function __construct(
        private readonly RazorpayCheckoutCompletionService $completionService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        if (! $this->completionService->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('Razorpay webhook rejected: invalid or missing signature');

            return response()->json(['status' => 'invalid_signature'], 400);
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            return response()->json(['status' => 'invalid_payload'], 400);
        }

        $result = $this->completionService->handleWebhookPayload($payload);

        if ($result === RazorpayCheckoutCompletionService::STATUS_FAILED) {
            return response()->json(['status' => 'failed'], 500);
        }

        return response()->json([
            'status' => 'ok',
            'result' => $result,
        ]);
    }
}
