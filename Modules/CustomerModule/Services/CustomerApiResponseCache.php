<?php

namespace Modules\CustomerModule\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Modules\BusinessSettingsModule\Services\BusinessConfigCache;

class CustomerApiResponseCache
{
    public const CONFIG_TTL = 600;

    public const HOME_BUNDLE_TTL = 300;

    public static function remember(string $key, Closure $callback, int $ttl = self::CONFIG_TTL): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Clear cached customer/provider config payloads (e.g. after mobile app icons or home layout change).
     */
    public static function forgetConfigCaches(): void
    {
        BusinessConfigCache::forgetAll();

        $locales = [strtolower((string) app()->getLocale()), 'en'];

        $countryData = business_config('system_language', 'business_information')?->live_values ?? [];
        if (is_array($countryData)) {
            foreach ($countryData as $item) {
                if (is_array($item) && ! empty($item['code'])) {
                    $locales[] = strtolower((string) $item['code']);
                }
            }
        }

        foreach (array_unique($locales) as $locale) {
            Cache::forget('customer_api_config:v2:'.$locale);
            Cache::forget('provider_api_config:v1:'.$locale);
        }
    }
}
