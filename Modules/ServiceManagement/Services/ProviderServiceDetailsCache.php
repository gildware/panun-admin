<?php

namespace Modules\ServiceManagement\Services;

use Closure;
use Illuminate\Http\Request;
use Modules\CustomerModule\Services\CustomerApiResponseCache;

/**
 * File-cache friendly provider-app service detail payloads.
 */
class ProviderServiceDetailsCache
{
    public const CACHE_VERSION = 'v4';

    public const DETAIL_TTL = 300;

    public const FAQ_TTL = 300;

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
     * @return array<string, mixed>|null
     */
    public static function rememberFaq(string $cacheKey, Closure $callback): ?array
    {
        /** @var array<string, mixed>|null $payload */
        return CustomerApiResponseCache::remember($cacheKey, $callback, self::FAQ_TTL);
    }

    /**
     * @return array{reviews: array<string, mixed>, rating: array<string, mixed>}|null
     */
    public static function rememberReviews(string $cacheKey, Closure $callback): ?array
    {
        /** @var array{reviews: array<string, mixed>, rating: array<string, mixed>}|null $payload */
        return CustomerApiResponseCache::remember($cacheKey, $callback, self::REVIEWS_TTL);
    }

    public static function detailCacheKey(Request $request, string $serviceId, string $providerId): string
    {
        $locale = strtolower((string) app()->getLocale());

        return 'provider_service_details:'.self::CACHE_VERSION.':'
            .$serviceId.':'
            .$providerId.':'
            .$locale;
    }

    public static function faqCacheKey(string $serviceId, int $limit, int $offset): string
    {
        $locale = strtolower((string) app()->getLocale());

        return 'provider_service_faq:'.self::CACHE_VERSION.':'
            .$serviceId.':'
            .$locale.':'
            .'l'.$limit
            .':o'.$offset;
    }

    public static function reviewsCacheKey(
        string $serviceId,
        string $providerId,
        string $status,
        int $limit,
        int $offset
    ): string {
        $locale = strtolower((string) app()->getLocale());

        return 'provider_service_reviews:'.self::CACHE_VERSION.':'
            .$serviceId.':'
            .$providerId.':'
            .$status.':'
            .$locale.':'
            .'l'.$limit
            .':o'.$offset;
    }
}
