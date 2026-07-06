<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Concurrency;
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

        $layoutHash = $this->layoutHash();
        $userId = auth('api')->id();
        $contentVersion = CustomerHomeContentVersion::resolveForRequest(
            $layoutHash,
            is_numeric($userId) ? (int) $userId : null,
        );

        $cacheKey = 'customer_home_bundle:v6:'.$contentVersion.':'.$zoneId.':'.$locale.':'.$authKey;

        $bundle = CustomerApiResponseCache::remember(
            $cacheKey,
            fn () => $this->fetchBundleConcurrently($request),
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

    private function fetchBundleConcurrently(Request $request): array
    {
        $providerBody = ['sort_by' => 'default', 'rating' => 0];

        $tasks = [
            'banners' => fn () => $this->dispatchGet($request, '/api/v1/customer/banner', ['limit' => 10, 'offset' => 1]),
            'categories' => fn () => $this->dispatchGet($request, '/api/v1/customer/category', ['limit' => 100, 'offset' => 1]),
            'popular_services' => fn () => $this->dispatchGet($request, '/api/v1/customer/service/popular', ['limit' => 10, 'offset' => 1]),
            'trending_services' => fn () => $this->dispatchGet($request, '/api/v1/customer/service/trending', ['limit' => 10, 'offset' => 1]),
            'recommended_services' => fn () => $this->dispatchGet($request, '/api/v1/customer/service/recommended', ['limit' => 10, 'offset' => 1]),
            'recommended_search' => fn () => $this->dispatchGet($request, '/api/v1/customer/service/search/recommended'),
            'providers_full' => fn () => $this->dispatchPost(
                $request,
                '/api/v1/customer/provider/list',
                $providerBody,
                ['limit' => 30, 'offset' => 1]
            ),
            'campaigns' => fn () => $this->dispatchGet($request, '/api/v1/customer/campaign', ['limit' => 10, 'offset' => 1]),
            'advertisements' => fn () => $this->dispatchGet($request, '/api/v1/customer/advertisements/ads-list', ['limit' => 15, 'offset' => 1]),
            'featured_categories' => fn () => $this->dispatchGet($request, '/api/v1/customer/featured-categories', ['limit' => 100, 'offset' => 1]),
            'sub_categories' => fn () => $this->dispatchGet($request, '/api/v1/customer/sub-categories', ['limit' => 8, 'offset' => 1]),
            'offline_payment_methods' => fn () => $this->dispatchGet($request, '/api/v1/customer/offline-payment/methods', ['limit' => 100, 'offset' => 1]),
        ];

        if (auth('api')->check()) {
            $tasks['recently_viewed_services'] = fn () => $this->dispatchGet(
                $request,
                '/api/v1/customer/service/recently-viewed',
                ['limit' => 10, 'offset' => 1]
            );
        }

        foreach ($this->curatedSectionTasks($request) as $taskKey => $task) {
            $tasks[$taskKey] = $task;
        }

        $results = $this->runConcurrently($tasks);

        $bundle = [];
        foreach ([
            'banners',
            'categories',
            'popular_services',
            'trending_services',
            'recommended_services',
            'recommended_search',
            'campaigns',
            'advertisements',
            'featured_categories',
            'sub_categories',
            'offline_payment_methods',
            'recently_viewed_services',
        ] as $key) {
            if (($results[$key] ?? null) !== null) {
                $bundle[$key] = $results[$key];
            }
        }

        if (($results['providers_full'] ?? null) !== null) {
            $bundle['providers'] = $this->sliceListContent($results['providers_full'], 10);
            $bundle['nearby_providers'] = $results['providers_full'];
        }

        $curatedSections = [];
        foreach ($results as $taskKey => $content) {
            if (str_starts_with($taskKey, 'curated:') && $content !== null) {
                $curatedSections[substr($taskKey, 8)] = $content;
            }
        }
        if ($curatedSections !== []) {
            $bundle['curated_sections'] = $curatedSections;
        }

        return $bundle;
    }

    /**
     * @return array<string, callable(): mixed>
     */
    private function curatedSectionTasks(Request $request): array
    {
        $tasks = [];

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
            $taskKey = 'curated:'.$key;

            $tasks[$taskKey] = match (true) {
                $contentType === MobileAppManagementService::CONTENT_SERVICES => fn () => $this->dispatchGet(
                    $request,
                    "/api/v1/customer/mobile-app-home/section/{$key}/services",
                    ['limit' => $limit, 'offset' => 1]
                ),
                $contentType === MobileAppManagementService::CONTENT_PROVIDERS => fn () => $this->dispatchGet(
                    $request,
                    "/api/v1/customer/mobile-app-home/section/{$key}/providers",
                    ['limit' => $limit, 'offset' => 1]
                ),
                $contentType === MobileAppManagementService::CONTENT_BANNERS => fn () => $this->dispatchGet(
                    $request,
                    "/api/v1/customer/mobile-app-home/section/{$key}/banners",
                    ['limit' => $limit, 'offset' => 1]
                ),
                in_array($contentType, [
                    MobileAppManagementService::CONTENT_CATEGORIES,
                    MobileAppManagementService::CONTENT_SUB_CATEGORIES,
                ], true) => fn () => $this->dispatchGet(
                    $request,
                    "/api/v1/customer/mobile-app-home/section/{$key}/categories",
                    ['limit' => $limit, 'offset' => 1]
                ),
                $contentType === MobileAppManagementService::CONTENT_CAMPAIGNS => fn () => $this->dispatchGet(
                    $request,
                    "/api/v1/customer/mobile-app-home/section/{$key}/campaigns",
                    ['limit' => $limit, 'offset' => 1]
                ),
                default => fn () => null,
            };
        }

        return $tasks;
    }

    /**
     * @param  array<string, callable(): mixed>  $tasks
     * @return array<string, mixed>
     */
    private function runConcurrently(array $tasks): array
    {
        if ($tasks === []) {
            return [];
        }

        try {
            return Concurrency::run($tasks);
        } catch (\Throwable) {
            $results = [];
            foreach ($tasks as $key => $task) {
                $results[$key] = $task();
            }

            return $results;
        }
    }

    /**
     * @param  array<string, mixed>|null  $content
     * @return array<string, mixed>|null
     */
    private function sliceListContent(?array $content, int $limit): ?array
    {
        if ($content === null || ! isset($content['data']) || ! is_array($content['data'])) {
            return $content;
        }

        $sliced = array_slice($content['data'], 0, $limit);
        $content['data'] = $sliced;
        if (isset($content['to'])) {
            $content['to'] = min((int) $content['to'], count($sliced));
        }
        if (isset($content['per_page'])) {
            $content['per_page'] = $limit;
        }

        return $content;
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
