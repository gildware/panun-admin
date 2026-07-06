<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Tracks whether server home-bundle cache has been rebuilt since the last content change.
 */
class CustomerHomeCacheWarmState
{
    private const LAST_WARMED_VERSION_KEY = 'customer_home_cache_last_warmed_global_version';

    public static function markWarmed(): void
    {
        Cache::forever(self::LAST_WARMED_VERSION_KEY, CustomerHomeContentVersion::global());
    }

    public static function lastWarmedVersion(): int
    {
        return (int) Cache::get(self::LAST_WARMED_VERSION_KEY, 0);
    }

    public static function currentVersion(): int
    {
        return (int) CustomerHomeContentVersion::global();
    }

    public static function needsAdminReminder(): bool
    {
        return self::currentVersion() > self::lastWarmedVersion();
    }
}
