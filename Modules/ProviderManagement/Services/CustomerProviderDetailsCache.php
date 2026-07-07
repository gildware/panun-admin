<?php

namespace Modules\ProviderManagement\Services;

use Closure;
use Illuminate\Http\Request;
use Modules\CustomerModule\Services\CustomerApiResponseCache;

/**
 * File-cache friendly provider-details payloads (uses Laravel CACHE_DRIVER=file on Hostinger).
 */
class CustomerProviderDetailsCache
{
    public const CACHE_VERSION = 'v6';

    public const SERVICES_TTL = 300;

    public const SUMMARY_TTL = 300;

    public const SHOWCASE_TTL = 300;

    public const REVIEWS_TTL = 300;

    public static function rememberServices(string $cacheKey, Closure $callback): array
    {
        /** @var array{sub_categories: list<array<string, mixed>>}|null $payload */
        $payload = CustomerApiResponseCache::remember($cacheKey, $callback, self::SERVICES_TTL);

        return $payload ?? ['sub_categories' => []];
    }

    /**
     * @return array{provider: array<string, mixed>, rating: array<string, mixed>}|null
     */
    public static function rememberSummary(string $cacheKey, Closure $callback): ?array
    {
        /** @var array{provider: array<string, mixed>, rating: array<string, mixed>}|null $payload */
        return CustomerApiResponseCache::remember($cacheKey, $callback, self::SUMMARY_TTL);
    }

    /**
     * @return array{showcase_items: list<array<string, mixed>>}
     */
    public static function rememberShowcase(string $cacheKey, Closure $callback): array
    {
        /** @var array{showcase_items: list<array<string, mixed>>}|null $payload */
        $payload = CustomerApiResponseCache::remember($cacheKey, $callback, self::SHOWCASE_TTL);

        return $payload ?? ['showcase_items' => []];
    }

    /**
     * @return array{reviews: array<string, mixed>, rating: array<string, mixed>}|null
     */
    public static function rememberReviews(string $cacheKey, Closure $callback): ?array
    {
        /** @var array{reviews: array<string, mixed>, rating: array<string, mixed>}|null $payload */
        return CustomerApiResponseCache::remember($cacheKey, $callback, self::REVIEWS_TTL);
    }

    public static function reviewsCacheKey(
        Request $request,
        string $providerId,
        int $limit,
        int $offset
    ): string {
        $zoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? 'no_zone');
        $locale = strtolower((string) app()->getLocale());

        return 'provider_details_reviews:'.self::CACHE_VERSION.':'
            .$providerId.':'
            .$zoneId.':'
            .$locale.':'
            .'l'.$limit
            .':o'.$offset;
    }

    public static function showcaseCacheKey(Request $request, string $providerId): string
    {
        $zoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? 'no_zone');
        $locale = strtolower((string) app()->getLocale());

        return 'provider_details_showcase:'.self::CACHE_VERSION.':'
            .$providerId.':'
            .$zoneId.':'
            .$locale;
    }

    public static function servicesCacheKey(
        Request $request,
        string $providerId,
        string|int|null $customerUserId,
        ?int $limitPerCategory
    ): string {
        $zoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? 'no_zone');
        $locale = strtolower((string) app()->getLocale());
        $userKey = $customerUserId !== null && $customerUserId !== '' ? (string) $customerUserId : 'guest';
        $limit = max(0, (int) ($limitPerCategory ?? 0));

        return 'provider_details_services:'.self::CACHE_VERSION.':'
            .$providerId.':'
            .$zoneId.':'
            .$locale.':'
            .$userKey.':'
            .'l'.$limit;
    }

    public static function summaryCacheKey(
        Request $request,
        string $providerId,
        string|int|null $customerUserId
    ): string {
        $zoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? 'no_zone');
        $locale = strtolower((string) app()->getLocale());
        $userKey = $customerUserId !== null && $customerUserId !== '' ? (string) $customerUserId : 'guest';

        return 'provider_details_summary:'.self::CACHE_VERSION.':'
            .$providerId.':'
            .$zoneId.':'
            .$locale.':'
            .$userKey;
    }
}
