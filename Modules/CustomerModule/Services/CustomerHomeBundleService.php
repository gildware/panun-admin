<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\BusinessSettingsModule\Services\MobileAppManagementService;

class CustomerHomeBundleService
{
    public const BUNDLE_CACHE_VERSION = 'v10';

    public const VERSION_CACHE_TTL = 30;

    public function __construct(
        protected MobileAppManagementService $mobileAppManagementService,
        protected CustomerHomeBundleComposer $bundleComposer,
    ) {}

    public function build(Request $request): array
    {
        $zoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? '');
        $locale = strtolower((string) $request->header('X-localization', app()->getLocale()));
        $authKey = $this->authCacheKey($request);

        $layoutHash = $this->layoutHash();
        $userId = auth('api')->id();
        $contentVersion = CustomerHomeContentVersion::resolveForRequest(
            $layoutHash,
            is_numeric($userId) ? (int) $userId : null,
        );

        $cacheKey = 'customer_home_bundle:'.self::BUNDLE_CACHE_VERSION.':'.$contentVersion.':'.$zoneId.':'.$locale.':'.$authKey;

        $bundle = CustomerApiResponseCache::remember(
            $cacheKey,
            fn () => $this->bundleComposer->build($request),
            CustomerApiResponseCache::HOME_BUNDLE_TTL
        );

        return array_merge(['content_version' => $contentVersion], $bundle);
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

        $cacheKey = 'customer_home_bundle_version:v1:'.$zoneId.':'.$authKey.':'.$layoutHash;

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

