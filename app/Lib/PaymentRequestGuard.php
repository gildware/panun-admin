<?php

namespace App\Lib;

use Illuminate\Http\Request;
use Modules\PaymentModule\Entities\PaymentRequest;
use Modules\ProviderManagement\Entities\Provider;

class PaymentRequestGuard
{
    public static function tokenMatchesPayer(?string $token, string $payerId): bool
    {
        if ($token === null || $token === '' || $payerId === '') {
            return false;
        }

        $subject = PaymentAccessToken::resolve($token);

        return $subject !== null && (string) $subject === $payerId;
    }

    public static function requestAccessToken(Request $request): ?string
    {
        $token = $request->input('access_token') ?? $request->input('payment_access_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    public static function storedAccessToken(PaymentRequest $paymentRequest): ?string
    {
        $additional = json_decode($paymentRequest->additional_data ?? '{}', true);
        if (! is_array($additional)) {
            return null;
        }

        $token = $additional['access_token'] ?? null;

        return is_string($token) && $token !== '' ? $token : null;
    }

    public static function assertCanAccessPaymentRequest(Request $request, PaymentRequest $paymentRequest): bool
    {
        $payerId = (string) $paymentRequest->payer_id;
        $requestToken = self::requestAccessToken($request);

        if (self::tokenMatchesPayer($requestToken, $payerId)) {
            return true;
        }

        // Web checkout page: token was stored at payment initiation and embedded in the pay view.
        $storedToken = self::storedAccessToken($paymentRequest);

        return $storedToken !== null
            && $requestToken !== null
            && hash_equals($storedToken, $requestToken)
            && self::tokenMatchesPayer($storedToken, $payerId);
    }

    /**
     * Resolve provider pay-to-admin subject from payment access token or provider web session.
     */
    public static function resolvePayToAdminSubjectId(Request $request, ?Provider $provider): ?string
    {
        if (! $provider) {
            return null;
        }

        $expectedUserId = (string) $provider->user_id;
        $requestToken = self::requestAccessToken($request);

        if ($requestToken && self::tokenMatchesPayer($requestToken, $expectedUserId)) {
            return $expectedUserId;
        }

        $user = auth()->user();
        if ($user && in_array($user->user_type, PROVIDER_USER_TYPES, true)
            && (string) $user->id === $expectedUserId) {
            return $expectedUserId;
        }

        return null;
    }
}
