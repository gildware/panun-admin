<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\ZoneManagement\Entities\Zone;

/**
 * Shared (guest) home-bundle file/cache store.
 *
 * Manual rebuild only:
 * - /home-bundle NEVER composes; it only reads the last built payload.
 * - Admin "Reset home cache" (or artisan warm) writes a new payload.
 * - Content edits do not invalidate or rebuild — apps keep getting the last build
 *   until an admin rebuilds.
 */
class CustomerHomeBaseBundleCache
{
    /** Bump only when the key shape itself changes. */
    public const BASE_VERSION = 'manual-v1';

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
            return [
                'bundle' => self::emptyPayload(),
                'fresh' => false,
                'source' => 'no_zone',
            ];
        }

        $key = self::cacheKey($zoneId, $locale);
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return [
                'bundle' => $cached,
                'fresh' => true,
                'source' => 'hit',
            ];
        }

        // One-time legacy read from the previous "latest" key shape (pre manual-v1).
        $legacy = Cache::get(self::legacyLatestCacheKey($zoneId, $locale, $layoutHash));
        if (is_array($legacy)) {
            Cache::forever($key, $legacy);

            return [
                'bundle' => $legacy,
                'fresh' => true,
                'source' => 'legacy',
            ];
        }

        // Never auto-warm. Admin must click Rebuild / run artisan warm.
        return [
            'bundle' => self::emptyPayload(),
            'fresh' => false,
            'source' => 'miss',
        ];
    }

    /**
     * Compose + store. Admin reset / artisan warm only.
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

        Cache::forever(self::cacheKey($zoneId, $locale), $payload);

        return $payload;
    }

    /**
     * Stable key: zone + locale only. Not tied to content version or layout hash,
     * so admin edits keep serving the previous rebuild until Reset is clicked.
     */
    public static function cacheKey(string $zoneId, string $locale): string
    {
        return 'customer_home_base:'.self::BASE_VERSION.':'.$zoneId.':'.$locale;
    }

    /**
     * @deprecated Pre-manual key used only as a one-time migration read.
     */
    public static function legacyLatestCacheKey(string $zoneId, string $locale, string $layoutHash): string
    {
        return 'customer_home_base_latest:v18:'.$layoutHash.':'.$zoneId.':'.$locale;
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

        CustomerHomeCacheWarmState::markWarmed();
        CustomerHomeCacheWarmState::markRebuildComplete();

        return $warmed;
    }

    private function resolveLocale(Request $request): string
    {
        return strtolower((string) $request->header('X-localization', app()->getLocale()));
    }
}
