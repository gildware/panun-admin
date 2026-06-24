<?php

namespace Modules\PaymentModule\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Modules\PaymentModule\Http\Controllers\RazorPayController;
use Illuminate\Contracts\Foundation\Application;

trait NativeRazorpayRedirect
{
    protected function shouldUseNativeRazorpay(Request $request): bool
    {
        return $request->boolean('native_sdk')
            && $request->input('payment_method') === 'razor_pay';
    }

    protected function respondPaymentRedirect(Request $request, string $redirectLink): JsonResponse|Redirector|RedirectResponse|Application
    {
        if (!$this->shouldUseNativeRazorpay($request)) {
            return redirect($redirectLink);
        }

        $paymentId = $this->extractPaymentIdFromRedirectLink($redirectLink);
        if (!$paymentId) {
            return response()->json([
                'status' => false,
                'message' => translate('Invalid payment link'),
            ], 400);
        }

        return app(RazorPayController::class)->buildNativePrepareResponse($request, $paymentId);
    }

    protected function extractPaymentIdFromRedirectLink(string $redirectLink): ?string
    {
        $query = parse_url($redirectLink, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            if (!empty($params['payment_id'])) {
                return $params['payment_id'];
            }
        }

        if (preg_match('/payment_id=([^&]+)/', $redirectLink, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
