<?php

namespace Modules\ServiceManagement\Services;

use Closure;
use Illuminate\Http\Request;
use Modules\CustomerModule\Services\CustomerApiResponseCache;

/**
 * File-cache friendly service-details payloads (uses Laravel CACHE_DRIVER=file on Hostinger).
 */
class CustomerServiceDetailsCache
{
    public const CACHE_VERSION = 'v6';

    public const DETAIL_TTL = 300;

    public const REVIEWS_TTL = 300;

    /**
     * @return array<string, mixed>|null
     */
    public static function rememberDetail(string $cacheKey, Closure $callback): ?array
    {
        /** @var array<string, mixed>|null $payload */
        return CustomerApiResponseCache::remember($cacheKey, $callback, self::DETAIL_TTL);
    }

    /**
     * @return array{reviews: array<string, mixed>, rating: array<string, mixed>}|null
     */
    public static function rememberReviews(string $cacheKey, Closure $callback): ?array
    {
        /** @var array{reviews: array<string, mixed>, rating: array<string, mixed>}|null $payload */
        return CustomerApiResponseCache::remember($cacheKey, $callback, self::REVIEWS_TTL);
    }

    public static function detailCacheKey(
        Request $request,
        string $slug,
        string|int|null $customerUserId
    ): string {
        $zoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? 'no_zone');
        $locale = strtolower((string) app()->getLocale());
        $userKey = $customerUserId !== null && $customerUserId !== '' ? (string) $customerUserId : 'guest';

        return 'service_details_detail:'.self::CACHE_VERSION.':'
            .$slug.':'
            .$zoneId.':'
            .$locale.':'
            .$userKey;
    }

    public static function reviewsCacheKey(
        Request $request,
        string $serviceId,
        int $limit,
        int $offset
    ): string {
        $zoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? 'no_zone');
        $locale = strtolower((string) app()->getLocale());

        return 'service_details_reviews:'.self::CACHE_VERSION.':'
            .$serviceId.':'
            .$zoneId.':'
            .$locale.':'
            .'l'.$limit
            .':o'.$offset;
    }
}
