<?php

namespace Modules\CustomerModule\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class CustomerApiResponseCache
{
    public const CONFIG_TTL = 600;

    public const HOME_BUNDLE_TTL = 300;

    public static function remember(string $key, Closure $callback, int $ttl = self::CONFIG_TTL): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }
}
