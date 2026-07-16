<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\BusinessSettingsModule\Services\MobileAppManagementService;

class CustomerHomeBundleService
{
    public const VERSION_CACHE_TTL = 30;

    public function __construct(
        protected MobileAppManagementService $mobileAppManagementService,
        protected CustomerHomeBaseBundleCache $baseBundleCache,
        protected CustomerHomeBundlePersonalizer $bundlePersonalizer,
    ) {}

    public function build(Request $request): array
    {
        $layoutHash = $this->layoutHash();
        $userId = auth('api')->id();
        $numericUserId = is_numeric($userId) ? (int) $userId : null;

        try {
            $resolved = $this->baseBundleCache->remember($request, $layoutHash);
        } catch (\Throwable $e) {
            report($e);
            $resolved = [
                'bundle' => CustomerHomeBaseBundleCache::emptyPayload(),
                'fresh' => false,
                'source' => 'error',
            ];
        }

        $base = is_array($resolved['bundle'] ?? null)
            ? $resolved['bundle']
            : CustomerHomeBaseBundleCache::emptyPayload();
        $fresh = (bool) ($resolved['fresh'] ?? false);
        $source = (string) ($resolved['source'] ?? 'miss');

        // content_version only changes when admin clicks Rebuild (bumpGlobal there).
        $contentVersion = CustomerHomeContentVersion::resolveForRequest($layoutHash, $numericUserId);

        $bundle = $base;
        if (auth('api')->check()) {
            try {
                $bundle = $this->bundlePersonalizer->apply($base, $request, (int) auth('api')->id());
            } catch (\Throwable $e) {
                // Never fail the whole home-bundle for favorites / recently-viewed —
                // that caused logged-in (and sometimes guest-adjacent) 500s and a
                // 10–23s multi-API fallback on the phone.
                report($e);
                $bundle = $base;
                $source = $source === 'hit' || $source === 'hit_normalized' || $source === 'legacy'
                    ? $source.'_personalizer_skipped'
                    : $source;
            }
        }

        return array_merge(
            [
                'content_version' => $contentVersion,
                'cache_status' => $fresh ? (str_starts_with($source, 'hit') ? $source : 'hit') : $source,
            ],
            $this->normalizeForMobileClient($bundle),
        );
    }

    /**
     * Ensures core list keys exist so mobile clients do not treat a sparse bundle as a failure.
     *
     * @param  array<string, mixed>  $bundle
     * @return array<string, mixed>
     */
    private function normalizeForMobileClient(array $bundle): array
    {
        $emptyList = [
            'data' => [],
            'total' => 0,
            'per_page' => 10,
            'current_page' => 1,
        ];

        foreach (['banners', 'categories', 'popular_services'] as $key) {
            if (! array_key_exists($key, $bundle)) {
                $bundle[$key] = $emptyList;
            }
        }

        return $bundle;
    }

    public function layoutHash(): string
    {
        return substr(md5(json_encode($this->mobileAppManagementService->homeSectionsForApi())), 0, 12);
    }

    /**
     * @return array{version: string, layout_hash: string}
     */
    public function versionPayload(Request $request): array
    {
        $layoutHash = $this->layoutHash();
        $userId = auth('api')->id();
        $zoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? 'no_zone');
        $authKey = $this->authCacheKey($request);

        $cacheKey = 'customer_home_bundle_version:v3:'
            .CustomerHomeContentVersion::global().':'
            .$zoneId.':'.$authKey.':'.$layoutHash;

        return Cache::remember(
            $cacheKey,
            self::VERSION_CACHE_TTL,
            fn () => [
                'version' => CustomerHomeContentVersion::resolveForRequest(
                    $layoutHash,
                    is_numeric($userId) ? (int) $userId : null,
                ),
                'layout_hash' => $layoutHash,
            ],
        );
    }

    private function authCacheKey(Request $request): string
    {
        if (auth('api')->check()) {
            return 'user:'.auth('api')->id();
        }

        return 'guest:shared';
    }
}
