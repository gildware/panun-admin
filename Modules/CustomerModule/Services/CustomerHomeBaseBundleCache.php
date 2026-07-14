<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Http\Request;
use Modules\ZoneManagement\Entities\Zone;

class CustomerHomeBaseBundleCache
{
    public const BASE_VERSION = 'v18';

    public const TTL = 900;

    public function __construct(
        private CustomerHomeBundleComposer $composer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function remember(Request $request, string $layoutHash): array
    {
        $zoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? '');
        $locale = $this->resolveLocale($request);

        if ($zoneId === '') {
            return CustomerHomeBundlePayloadSlimmer::slim(
                $this->composer->buildSharedBase($request)
            );
        }

        return CustomerApiResponseCache::remember(
            self::cacheKey($zoneId, $locale, $layoutHash),
            fn () => CustomerHomeBundlePayloadSlimmer::slim(
                $this->composer->buildSharedBase($request)
            ),
            self::TTL
        );
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
            $cache->remember($request, $layoutHash);
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

    private function resolveLocale(Request $request): string
    {
        return strtolower((string) $request->header('X-localization', app()->getLocale()));
    }
}
