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

        $resolved = $this->baseBundleCache->remember($request, $layoutHash);
        $base = $resolved['bundle'];
        $fresh = (bool) ($resolved['fresh'] ?? false);

        // content_version only changes when admin clicks Rebuild (bumpGlobal there).
        // Edits without rebuild keep the same version so apps do not refetch.
        $contentVersion = CustomerHomeContentVersion::resolveForRequest($layoutHash, $numericUserId);

        $bundle = auth('api')->check()
            ? $this->bundlePersonalizer->apply($base, $request, (int) auth('api')->id())
            : $base;

        return array_merge(
            [
                'content_version' => $contentVersion,
                'cache_status' => $fresh ? 'hit' : (string) ($resolved['source'] ?? 'miss'),
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
