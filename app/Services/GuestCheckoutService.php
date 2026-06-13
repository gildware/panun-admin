<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;

class GuestCheckoutService
{
    public static function isEnabled(): bool
    {
        return (int) (business_config('guest_checkout', 'service_setup'))?->live_values === 1;
    }

    public static function rejectIfRequiresLogin(bool $isLoggedIn): ?JsonResponse
    {
        if ($isLoggedIn || self::isEnabled()) {
            return null;
        }

        return response()->json(response_formatter(DEFAULT_401), 401);
    }
}
