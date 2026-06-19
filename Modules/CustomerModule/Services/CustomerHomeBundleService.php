<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Http\Request;
use Modules\BusinessSettingsModule\Services\MobileAppManagementService;

class CustomerHomeBundleService
{
    public function __construct(
        protected MobileAppManagementService $mobileAppManagementService,
    ) {}

    public function build(Request $request): array
    {
        $zoneId = (string) ($request->header('zoneId') ?? $request->header('zoneid') ?? '');
        $locale = strtolower((string) $request->header('X-localization', app()->getLocale()));
        $authKey = auth('api')->check()
            ? 'user:'.auth('api')->id()
            : 'guest:'.(string) ($request->input('guest_id') ?? $request->header('guest_id') ?? 'anon');

        $layoutHash = substr(md5(json_encode($this->mobileAppManagementService->homeSectionsForApi())), 0, 12);
        $cacheKey = 'customer_home_bundle:v3:'.$layoutHash.':'.$zoneId.':'.$locale.':'.$authKey;

        return CustomerApiResponseCache::remember(
            $cacheKey,
            fn () => array_merge(
                $this->fetchPublicBundle($request),
                array_filter($this->fetchPersonalBundle($request), fn ($value) => $value !== null)
            ),
            CustomerApiResponseCache::HOME_BUNDLE_TTL
        );
    }

    private function fetchPublicBundle(Request $request): array
    {
        $providerBody = ['sort_by' => 'default', 'rating' => 0];

        return array_filter([
            'banners' => $this->dispatchGet($request, '/api/v1/customer/banner', ['limit' => 10, 'offset' => 1]),
            'categories' => $this->dispatchGet($request, '/api/v1/customer/category', ['limit' => 100, 'offset' => 1]),
            'popular_services' => $this->dispatchGet($request, '/api/v1/customer/service/popular', ['limit' => 10, 'offset' => 1]),
            'trending_services' => $this->dispatchGet($request, '/api/v1/customer/service/trending', ['limit' => 10, 'offset' => 1]),
            'recommended_services' => $this->dispatchGet($request, '/api/v1/customer/service/recommended', ['limit' => 10, 'offset' => 1]),
            'recommended_search' => $this->dispatchGet($request, '/api/v1/customer/service/search/recommended'),
            'providers' => $this->dispatchPost(
                $request,
                '/api/v1/customer/provider/list',
                $providerBody,
                ['limit' => 10, 'offset' => 1]
            ),
            'nearby_providers' => $this->dispatchPost(
                $request,
                '/api/v1/customer/provider/list',
                $providerBody,
                ['limit' => 30, 'offset' => 1]
            ),
            'campaigns' => $this->dispatchGet($request, '/api/v1/customer/campaign', ['limit' => 10, 'offset' => 1]),
            'advertisements' => $this->dispatchGet($request, '/api/v1/customer/advertisements/ads-list', ['limit' => 50, 'offset' => 1]),
            'featured_categories' => $this->dispatchGet($request, '/api/v1/customer/featured-categories', ['limit' => 100, 'offset' => 1]),
            'sub_categories' => $this->dispatchGet($request, '/api/v1/customer/sub-categories', ['limit' => 8, 'offset' => 1]),
            'offline_payment_methods' => $this->dispatchGet($request, '/api/v1/customer/offline-payment/methods', ['limit' => 100, 'offset' => 1]),
            'curated_sections' => $this->fetchCuratedSections($request),
        ], fn ($value) => $value !== null);
    }

    private function fetchPersonalBundle(Request $request): array
    {
        if (! auth('api')->check()) {
            return [];
        }

        return array_filter([
            'recently_viewed_services' => $this->dispatchGet(
                $request,
                '/api/v1/customer/service/recently-viewed',
                ['limit' => 10, 'offset' => 1]
            ),
        ], fn ($value) => $value !== null);
    }

    private function fetchCuratedSections(Request $request): array
    {
        $sections = [];

        foreach ($this->mobileAppManagementService->homeSectionsForApi()['sections'] ?? [] as $section) {
            if (! ($section['enabled'] ?? false) || ($section['data_mode'] ?? 'default') !== 'manual') {
                continue;
            }

            $key = (string) ($section['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $limit = (int) ($section['item_limit'] ?? 10);
            $contentType = (string) ($section['content_type'] ?? '');

            $content = match (true) {
                $contentType === MobileAppManagementService::CONTENT_SERVICES => $this->dispatchGet(
                    $request,
                    "/api/v1/customer/mobile-app-home/section/{$key}/services",
                    ['limit' => $limit, 'offset' => 1]
                ),
                $contentType === MobileAppManagementService::CONTENT_PROVIDERS => $this->dispatchGet(
                    $request,
                    "/api/v1/customer/mobile-app-home/section/{$key}/providers",
                    ['limit' => $limit, 'offset' => 1]
                ),
                $contentType === MobileAppManagementService::CONTENT_BANNERS => $this->dispatchGet(
                    $request,
                    "/api/v1/customer/mobile-app-home/section/{$key}/banners",
                    ['limit' => $limit, 'offset' => 1]
                ),
                in_array($contentType, [
                    MobileAppManagementService::CONTENT_CATEGORIES,
                    MobileAppManagementService::CONTENT_SUB_CATEGORIES,
                ], true) => $this->dispatchGet(
                    $request,
                    "/api/v1/customer/mobile-app-home/section/{$key}/categories",
                    ['limit' => $limit, 'offset' => 1]
                ),
                $contentType === MobileAppManagementService::CONTENT_CAMPAIGNS => $this->dispatchGet(
                    $request,
                    "/api/v1/customer/mobile-app-home/section/{$key}/campaigns",
                    ['limit' => $limit, 'offset' => 1]
                ),
                default => null,
            };

            if ($content !== null) {
                $sections[$key] = $content;
            }
        }

        return $sections;
    }

    private function dispatchGet(Request $parent, string $path, array $query = []): mixed
    {
        $uri = $path.($query !== [] ? '?'.http_build_query($query) : '');

        return $this->dispatch($parent, 'GET', $uri);
    }

    private function dispatchPost(Request $parent, string $path, array $payload = [], array $query = []): mixed
    {
        $uri = $path.($query !== [] ? '?'.http_build_query($query) : '');

        return $this->dispatch($parent, 'POST', $uri, $payload);
    }

    private function dispatch(Request $parent, string $method, string $uri, array $payload = []): mixed
    {
        $server = ['HTTP_ACCEPT' => 'application/json'];

        foreach (['zoneId', 'zoneid', 'X-localization', 'Authorization', 'guest_id'] as $name) {
            $value = $parent->header($name);
            if ($value !== null && $value !== '') {
                $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
            }
        }

        $sub = Request::create($uri, $method, $payload, [], [], $server);
        $sub->headers->set('Accept', 'application/json');

        if ($zone = $parent->header('zoneId') ?? $parent->header('zoneid')) {
            $sub->headers->set('zoneId', $zone);
        }
        if ($auth = $parent->header('Authorization')) {
            $sub->headers->set('Authorization', $auth);
        }
        if ($guest = $parent->input('guest_id') ?? $parent->header('guest_id')) {
            $sub->merge(['guest_id' => $guest]);
        }

        $response = app()->handle($sub);
        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $decoded = json_decode($response->getContent(), true);

        return is_array($decoded) ? ($decoded['content'] ?? null) : null;
    }
}
