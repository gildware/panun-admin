<?php

namespace Modules\ProviderManagement\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class ProviderBookingTabCountCache
{
    public const TTL = 30;

    public static function remember(string $providerId, string $filtersKey, Closure $callback): array
    {
        $result = Cache::remember(
            "provider_booking_tab_counts:{$providerId}:{$filtersKey}",
            self::TTL,
            $callback
        );

        return is_array($result) ? $result : [];
    }

    public static function forgetForProvider(string $providerId): void
    {
        // File cache has no tag support; bumping is handled via short TTL.
        Cache::forget("provider_booking_tab_counts:{$providerId}:all");
    }
}
