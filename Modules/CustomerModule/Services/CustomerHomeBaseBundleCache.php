<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\ZoneManagement\Entities\Zone;

/**
 * Shared (guest) home-bundle Redis cache.
 *
 * Request path NEVER composes. Cold misses used to call Cache::remember() and
 * rebuild inline (~10–15s). Compose only runs from warm jobs / artisan / admin reset.
 */
class CustomerHomeBaseBundleCache
{
    public const BASE_VERSION = 'v18';

    /** Soft TTL for the current versioned key (version bumps already change the key). */
    public const TTL = 86400;

    /** Longer TTL for the zone "latest good" alias used after version bumps. */
    public const LATEST_TTL = 604800;

    public function __construct(
        private CustomerHomeBundleComposer $composer,
    ) {}

    /**
     * Serve cache only. Never builds on the customer request path.
     *
     * @return array{bundle: array<string, mixed>, fresh: bool, source: string}
     */
    public function remember(Request $request, string $layoutHash): array
    {
        $zoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? '');
        $locale = $this->resolveLocale($request);

        if ($zoneId === '') {
            // No zone → nothing useful to cache; never block on compose.
            return [
                'bundle' => self::emptyPayload(),
                'fresh' => false,
                'source' => 'no_zone',
            ];
        }

        $versionedKey = self::cacheKey($zoneId, $locale, $layoutHash);
        $cached = Cache::get($versionedKey);
        if (is_array($cached)) {
            return [
                'bundle' => $cached,
                'fresh' => true,
                'source' => 'versioned',
            ];
        }

        // After content-version bumps the versioned key is empty, but the previous
        // successful warm is still under the latest alias — serve that immediately.
        $latestKey = self::latestCacheKey($zoneId, $locale, $layoutHash);
        $latest = Cache::get($latestKey);
        if (is_array($latest)) {
            self::scheduleMissWarm($zoneId);

            return [
                'bundle' => $latest,
                'fresh' => false,
                'source' => 'latest',
            ];
        }

        self::scheduleMissWarm($zoneId);

        return [
            'bundle' => self::emptyPayload(),
            'fresh' => false,
            'source' => 'miss',
        ];
    }

    /**
     * Compose + store for one zone/locale. Warm jobs / artisan only.
     *
     * @return array<string, mixed>
     */
    public function buildAndStore(Request $request, string $layoutHash): array
    {
        $zoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? '');
        $locale = $this->resolveLocale($request);

        $payload = CustomerHomeBundlePayloadSlimmer::slim(
            $this->composer->buildSharedBase($request)
        );

        if ($zoneId === '') {
            return $payload;
        }

        $versionedKey = self::cacheKey($zoneId, $locale, $layoutHash);
        $latestKey = self::latestCacheKey($zoneId, $locale, $layoutHash);

        Cache::put($versionedKey, $payload, self::TTL);
        Cache::put($latestKey, $payload, self::LATEST_TTL);

        return $payload;
    }

    public static function cacheKey(string $zoneId, string $locale, string $layoutHash): string
    {
        return 'customer_home_base:'.self::BASE_VERSION.':'
            .CustomerHomeContentVersion::global().':'
            .$layoutHash.':'
            .$zoneId.':'
            .$locale;
    }

    /**
     * Non-versioned alias so customers keep getting the last good payload while
     * the warm job rebuilds the new versioned key after a content bump.
     */
    public static function latestCacheKey(string $zoneId, string $locale, string $layoutHash): string
    {
        return 'customer_home_base_latest:'.self::BASE_VERSION.':'
            .$layoutHash.':'
            .$zoneId.':'
            .$locale;
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyPayload(): array
    {
        $emptyList = [
            'data' => [],
            'total' => 0,
            'per_page' => 10,
            'current_page' => 1,
        ];

        return [
            'banners' => $emptyList,
            'categories' => $emptyList,
            'popular_services' => $emptyList,
            'trending_services' => $emptyList,
            'recommended_services' => $emptyList,
            'providers' => $emptyList,
            'nearby_providers' => $emptyList,
            'campaigns' => $emptyList,
            'advertisements' => $emptyList,
            'curated_sections' => [],
        ];
    }

    /**
     * @return list<string>
     */
    public static function supportedLocales(): array
    {
        $locales = [strtolower((string) app()->getLocale()), 'en'];

        $countryData = business_config('system_language', 'business_information')?->live_values ?? [];
        if (is_array($countryData)) {
            foreach ($countryData as $item) {
                if (is_array($item) && ! empty($item['code'])) {
                    $locales[] = strtolower((string) $item['code']);
                }
            }
        }

        return array_values(array_unique(array_filter($locales)));
    }

    public static function estimateRebuildTotal(?string $zoneId = null): int
    {
        $localeCount = max(1, count(self::supportedLocales()));

        if ($zoneId !== null && $zoneId !== '') {
            return $localeCount;
        }

        $zoneCount = (int) Zone::query()->where('is_active', 1)->count();

        return max(1, $zoneCount * $localeCount);
    }

    /**
     * @param  callable(int $done): void|null  $onProgress
     */
    public static function warmZone(string $zoneId, ?string $locale = null, ?callable $onProgress = null, int $progressOffset = 0): int
    {
        $service = app(CustomerHomeBundleService::class);
        $layoutHash = $service->layoutHash();
        $cache = app(self::class);
        $locales = $locale !== null && $locale !== '' ? [$locale] : self::supportedLocales();
        $warmed = 0;

        foreach ($locales as $loc) {
            $request = Request::create('/api/v1/customer/home-bundle', 'GET');
            $request->headers->set('zoneId', $zoneId);
            $request->headers->set('X-localization', $loc);
            $cache->buildAndStore($request, $layoutHash);
            $warmed++;

            if ($onProgress !== null) {
                $onProgress($progressOffset + $warmed);
            }
        }

        return $warmed;
    }

    public static function warmAll(?string $zoneId = null): int
    {
        $total = self::estimateRebuildTotal($zoneId);
        $warmed = 0;

        $onProgress = static function (int $done) use ($total): void {
            CustomerHomeCacheWarmState::markRebuildProgress($done, $total);
        };

        CustomerHomeCacheWarmState::markRebuildProgress(0, $total);

        if ($zoneId !== null && $zoneId !== '') {
            $warmed = self::warmZone($zoneId, null, $onProgress, 0);
            CustomerHomeCacheWarmState::markWarmed();
            CustomerHomeCacheWarmState::markRebuildComplete();

            return $warmed;
        }

        $zoneIds = Zone::query()->where('is_active', 1)->pluck('id');

        foreach ($zoneIds as $id) {
            $warmed += self::warmZone((string) $id, null, $onProgress, $warmed);
        }

        // Even with zero zones, treat a successful rebuild pass as warmed so admin UI can clear.
        CustomerHomeCacheWarmState::markWarmed();
        CustomerHomeCacheWarmState::markRebuildComplete();

        return $warmed;
    }

    private static function scheduleMissWarm(string $zoneId): void
    {
        CustomerHomeCacheManager::ensureZoneWarm($zoneId);
    }

    private function resolveLocale(Request $request): string
    {
        return strtolower((string) $request->header('X-localization', app()->getLocale()));
    }
}
