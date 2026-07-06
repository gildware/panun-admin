<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Http\Request;
use Modules\BusinessSettingsModule\Services\MobileAppManagementService;

class CustomerHomeBundleService
{
    public function __construct(
        protected MobileAppManagementService $mobileAppManagementService,
        protected CustomerHomeBundleComposer $bundleComposer,
    ) {}

    public function build(Request $request): array
    {
        $zoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? '');
        $locale = strtolower((string) $request->header('X-localization', app()->getLocale()));
        $authKey = auth('api')->check()
            ? 'user:'.auth('api')->id()
            : 'guest:'.(string) ($request->input('guest_id') ?? $request->header('guest_id') ?? 'anon');

        $layoutHash = $this->layoutHash();
        $userId = auth('api')->id();
        $contentVersion = CustomerHomeContentVersion::resolveForRequest(
            $layoutHash,
            is_numeric($userId) ? (int) $userId : null,
        );

        $cacheKey = 'customer_home_bundle:v7:'.$contentVersion.':'.$zoneId.':'.$locale.':'.$authKey;

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

        return [
            'version' => CustomerHomeContentVersion::resolveForRequest(
                $layoutHash,
                is_numeric($userId) ? (int) $userId : null,
            ),
            'layout_hash' => $layoutHash,
        ];
    }
}
