<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Modules\ZoneManagement\Entities\Zone;

/**
 * Shared (guest) home-bundle file/cache store.
 *
 * Manual rebuild only:
 * - /home-bundle NEVER composes; it only reads the last built payload.
 * - Admin "Reset home cache" (or artisan warm) writes a new payload.
 * - Content edits do not invalidate or rebuild — apps keep getting the last build
 *   until an admin rebuilds.
 *
 * Apps often send multi-zone headers ("parent,leaf"). Warm stores per leaf zone id,
 * so lookup must try each token (and fall back to en locale) or guests/logged-in
 * both get cache_status=miss → empty → slow multi-API fallback.
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
        $rawZoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? '');
        $locale = $this->resolveLocale($request);

        if (trim($rawZoneId) === '') {
            return [
                'bundle' => self::emptyPayload(),
                'fresh' => false,
                'source' => 'no_zone',
            ];
        }

        foreach ($this->localeCandidates($locale) as $tryLocale) {
            foreach ($this->zoneCandidates($rawZoneId) as $tryZone) {
                $key = self::cacheKey($tryZone, $tryLocale);
                $cached = Cache::get($key);
                if (is_array($cached)) {
                    return [
                        'bundle' => $cached,
                        'fresh' => true,
                        'source' => $tryLocale === $locale && $tryZone === trim($rawZoneId)
                            ? 'hit'
                            : 'hit_normalized',
                    ];
                }

                // One-time legacy read from the previous "latest" key shape (pre manual-v1).
                $legacy = Cache::get(self::legacyLatestCacheKey($tryZone, $tryLocale, $layoutHash));
                if (is_array($legacy)) {
                    Cache::forever($key, $legacy);

                    return [
                        'bundle' => $legacy,
                        'fresh' => true,
                        'source' => 'legacy',
                    ];
                }
            }
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
        $rawZoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? '');
        $locale = $this->resolveLocale($request);

        $payload = CustomerHomeBundlePayloadSlimmer::slim(
            $this->composer->buildSharedBase($request)
        );

        $storeZone = $this->preferredStoreZoneId($rawZoneId);
        if ($storeZone === '') {
            return $payload;
        }

        // Store under the preferred leaf id and every token so multi-zone headers hit.
        foreach ($this->zoneCandidates($rawZoneId) as $zoneToken) {
            Cache::forever(self::cacheKey($zoneToken, $locale), $payload);
        }
        Cache::forever(self::cacheKey($storeZone, $locale), $payload);

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
     * Parse app zone headers: "a,b", "[a, b]", spaced tokens.
     *
     * @return list<string>
     */
    public static function parseZoneTokens(string $raw): array
    {
        $cleaned = str_replace(['[', ']', '"', "'"], '', $raw);
        $tokens = [];
        foreach (explode(',', $cleaned) as $token) {
            $token = trim($token);
            if ($token !== '') {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }

    /**
     * Ordered zone ids to try for cache lookup (leaf tokens first when detectable).
     *
     * @return list<string>
     */
    public function zoneCandidates(string $rawZoneId): array
    {
        $tokens = self::parseZoneTokens($rawZoneId);
        if ($tokens === []) {
            return [];
        }
        if (count($tokens) === 1) {
            return $tokens;
        }

        $leafFirst = $this->preferLeafZoneIds($tokens);

        // Exact raw string last (legacy accidental stores).
        $raw = trim($rawZoneId);
        if ($raw !== '' && ! in_array($raw, $leafFirst, true)) {
            $leafFirst[] = $raw;
        }

        return $leafFirst;
    }

    /**
     * @param  list<string>  $tokens
     * @return list<string>
     */
    private function preferLeafZoneIds(array $tokens): array
    {
        try {
            $zones = Zone::query()
                ->whereIn('id', $tokens)
                ->get(['id', 'parent_id']);

            if ($zones->isEmpty()) {
                return $tokens;
            }

            $idSet = array_fill_keys($tokens, true);
            $leaves = [];
            $parents = [];

            foreach ($zones as $zone) {
                $id = (string) $zone->id;
                $parentId = $zone->parent_id !== null ? (string) $zone->parent_id : '';
                // A token is a "parent in this set" if another token lists it as parent_id.
                $isParentOfAnother = $zones->contains(
                    fn ($other) => (string) $other->parent_id === $id && isset($idSet[(string) $other->id])
                );
                if ($isParentOfAnother) {
                    $parents[] = $id;
                } else {
                    $leaves[] = $id;
                }
                unset($parentId);
            }

            $ordered = array_values(array_unique(array_merge($leaves, $parents, $tokens)));

            return $ordered;
        } catch (\Throwable) {
            return $tokens;
        }
    }

    private function preferredStoreZoneId(string $rawZoneId): string
    {
        $candidates = $this->zoneCandidates($rawZoneId);

        return $candidates[0] ?? '';
    }

    /**
     * @return list<string>
     */
    private function localeCandidates(string $locale): array
    {
        $list = [$locale];
        if ($locale !== 'en') {
            $list[] = 'en';
        }

        return array_values(array_unique($list));
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
            // Heartbeat before each unit so a slow compose does not look like a dead rebuild.
            CustomerHomeCacheWarmState::touchRebuildHeartbeat();

            try {
                $request = Request::create('/api/v1/customer/home-bundle', 'GET');
                $request->headers->set('zoneId', $zoneId);
                $request->headers->set('X-localization', $loc);
                $cache->buildAndStore($request, $layoutHash);
            } catch (\Throwable $e) {
                $detail = $e->getMessage() !== '' ? $e->getMessage() : 'unknown error';
                throw new \RuntimeException(
                    "Home cache warm failed for zone {$zoneId} (locale {$loc}): {$detail}",
                    0,
                    $e
                );
            }

            $warmed++;

            if ($onProgress !== null) {
                $onProgress($progressOffset + $warmed);
            }
        }

        return $warmed;
    }

    public static function warmAll(?string $zoneId = null): int
    {
        // Shared hosting often caps request time; admin rebuild must be allowed to finish.
        ignore_user_abort(true);
        @set_time_limit(0);

        // Avoid Laravel Process concurrency during warm — Hostinger MySQL rejects forked CLI connections.
        Config::set('customer_home_cache_warming', true);

        $total = self::estimateRebuildTotal($zoneId);
        $warmed = 0;

        $onProgress = static function (int $done) use ($total): void {
            CustomerHomeCacheWarmState::markRebuildProgress($done, $total);
        };

        CustomerHomeCacheWarmState::markRebuildProgress(0, $total);

        try {
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
        } finally {
            Config::set('customer_home_cache_warming', false);
        }
    }

    private function resolveLocale(Request $request): string
    {
        return strtolower((string) $request->header('X-localization', app()->getLocale()));
    }
}
