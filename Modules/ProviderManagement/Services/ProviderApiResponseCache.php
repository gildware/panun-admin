<?php

namespace Modules\ProviderManagement\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class ProviderApiResponseCache
{
    public const DASHBOARD_BUNDLE_TTL = 120;

    public static function remember(string $key, Closure $callback, int $ttl = self::DASHBOARD_BUNDLE_TTL): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }
}
