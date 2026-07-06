<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Concurrency;
use Modules\BusinessSettingsModule\Services\MobileAppManagementService;
use Modules\CategoryManagement\Http\Controllers\Api\V1\Customer\CategoryController;
use Modules\CategoryManagement\Http\Controllers\Api\V1\Customer\SubCategoryController;
use Modules\PaymentModule\Http\Controllers\Api\V1\Customer\OfflinePaymentController;
use Modules\PromotionManagement\Http\Controllers\Api\V1\Customer\BannerController;
use Modules\PromotionManagement\Http\Controllers\Api\V1\Customer\CampaignController;
use Modules\PromotionManagement\Services\CustomerAdvertisementListFetcher;
use Modules\ProviderManagement\Services\CustomerProviderListFetcher;
use Modules\ProviderManagement\Services\ZoneProviderEligibilityService;
use Modules\ServiceManagement\Http\Controllers\Api\V1\Customer\ServiceController;

class CustomerHomeBundleComposer
{
    /** @var array<string, string> */
    private const SECTION_TO_BUNDLE_KEY = [
        'banners' => 'banners',
        'categories' => 'categories',
        'highlight_providers' => 'advertisements',
        'popular_services' => 'popular_services',
        'campaigns' => 'campaigns',
        'recommended_services' => 'recommended_services',
        'nearby_providers' => 'nearby_providers',
        'recommended_providers' => 'providers',
        'trending_services' => 'trending_services',
        'feathered_categories' => 'featured_categories',
        'recently_viewed' => 'recently_viewed_services',
    ];

    public function __construct(
        protected MobileAppManagementService $mobileAppManagementService,
    ) {}

    public function build(Request $request): array
    {
        return $this->buildInternal($request, includeUserSections: auth('api')->check());
    }

    /**
     * Shared zone payload cached for all guests; user-specific sections are merged later.
     *
     * @return array<string, mixed>
     */
    public function buildSharedBase(Request $request): array
    {
        return $this->buildInternal($request, includeUserSections: false);
    }

    public function layoutIncludesRecentlyViewed(): bool
    {
        foreach ($this->mobileAppManagementService->homeSectionsForApi()['sections'] ?? [] as $section) {
            if (! ($section['enabled'] ?? false)) {
                continue;
            }

            if (($section['key'] ?? '') === 'recently_viewed') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchRecentlyViewedSection(Request $request): ?array
    {
        if (! auth('api')->check()) {
            return null;
        }

        $task = $this->taskForBundleKey('recently_viewed_services', $request, includeUserSections: true);
        if ($task === null) {
            return null;
        }

        $content = $task();

        return is_array($content) ? $content : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInternal(Request $request, bool $includeUserSections): array
    {
        $this->applyRequestContext($request);
        Config::set('customer_home_bundle_active', true);

        if (Config::get('zone_id')) {
            app(ZoneProviderEligibilityService::class)->snapshot();
        }

        $homeLayout = $this->homeLayout();
        $tasks = [];
        $bundleKeysNeeded = $this->resolveBundleKeysToFetch($homeLayout, $includeUserSections);

        foreach ($bundleKeysNeeded as $bundleKey) {
            $task = $this->taskForBundleKey($bundleKey, $request, $includeUserSections);
            if ($task !== null) {
                $tasks[$bundleKey] = $task;
            }
        }

        foreach ($this->curatedSectionTasks($request, $homeLayout) as $taskKey => $task) {
            $tasks[$taskKey] = $task;
        }

        $results = $this->runConcurrently($tasks);
        $bundle = [];

        foreach ($bundleKeysNeeded as $bundleKey) {
            if ($bundleKey === 'providers_full') {
                if (($results['providers_full'] ?? null) !== null) {
                    $bundle['providers'] = $this->sliceListContent($results['providers_full'], 10);
                    $bundle['nearby_providers'] = $results['providers_full'];
                }
                continue;
            }

            if (($results[$bundleKey] ?? null) !== null) {
                $bundle[$bundleKey] = $results[$bundleKey];
            }
        }

        $curatedSections = [];
        foreach ($results as $taskKey => $content) {
            if (str_starts_with((string) $taskKey, 'curated:') && $content !== null) {
                $curatedSections[substr((string) $taskKey, 8)] = $content;
            }
        }

        if ($curatedSections !== []) {
            $bundle['curated_sections'] = $curatedSections;
        }

        return $bundle;
    }

    /**
     * @return array{sections: list<array<string, mixed>>}
     */
    private function homeLayout(): array
    {
        return $this->mobileAppManagementService->homeSectionsForApi();
    }

    /**
     * @param  array{sections: list<array<string, mixed>>}  $homeLayout
     * @return list<string>
     */
    private function resolveBundleKeysToFetch(array $homeLayout, bool $includeUserSections): array
    {
        $keys = [];
        $needsProviders = false;

        foreach ($homeLayout['sections'] ?? [] as $section) {
            if (! ($section['enabled'] ?? false)) {
                continue;
            }

            $sectionKey = (string) ($section['key'] ?? '');
            if ($sectionKey === '') {
                continue;
            }

            if ($sectionKey === 'search') {
                $keys[] = 'recommended_search';
                continue;
            }

            if (isset(self::SECTION_TO_BUNDLE_KEY[$sectionKey])) {
                $bundleKey = self::SECTION_TO_BUNDLE_KEY[$sectionKey];
                if ($bundleKey === 'recently_viewed_services') {
                    continue;
                }
                if (in_array($bundleKey, ['providers', 'nearby_providers'], true)) {
                    $needsProviders = true;
                } else {
                    $keys[] = $bundleKey;
                }
            }
        }

        if ($needsProviders) {
            $keys[] = 'providers_full';
        }

        if ($this->needsDefaultSubCategories($homeLayout)) {
            $keys[] = 'sub_categories';
        }

        if ($this->needsOfflinePaymentMethods()) {
            $keys[] = 'offline_payment_methods';
        }

        if ($includeUserSections && auth('api')->check()) {
            $keys[] = 'recently_viewed_services';
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  array{sections: list<array<string, mixed>>}  $homeLayout
     */
    private function needsDefaultSubCategories(array $homeLayout): bool
    {
        foreach ($homeLayout['sections'] ?? [] as $section) {
            if (! ($section['enabled'] ?? false)) {
                continue;
            }

            if (($section['data_mode'] ?? MobileAppManagementService::DATA_MODE_DEFAULT) !== MobileAppManagementService::DATA_MODE_DEFAULT) {
                continue;
            }

            if (($section['content_type'] ?? '') === MobileAppManagementService::CONTENT_SUB_CATEGORIES) {
                return true;
            }
        }

        return false;
    }

    private function needsOfflinePaymentMethods(): bool
    {
        return (int) (business_config('offline_payment', 'service_setup')?->live_values ?? 0) === 1;
    }

    private function taskForBundleKey(string $bundleKey, Request $request, bool $includeUserSections): ?callable
    {
        $customerUserId = $includeUserSections ? $this->customerUserId($request) : null;

        return match ($bundleKey) {
            'banners' => fn () => $this->invoke(BannerController::class, 'index', $request, ['limit' => 10, 'offset' => 1]),
            'categories' => fn () => $this->invoke(CategoryController::class, 'index', $request, ['limit' => 100, 'offset' => 1]),
            'popular_services' => fn () => $this->invoke(ServiceController::class, 'popular', $request, ['limit' => 10, 'offset' => 1]),
            'trending_services' => fn () => $this->invoke(ServiceController::class, 'trending', $request, ['limit' => 10, 'offset' => 1]),
            'recommended_services' => fn () => $this->invoke(ServiceController::class, 'recommended', $request, ['limit' => 10, 'offset' => 1]),
            'recommended_search' => fn () => $this->invoke(ServiceController::class, 'searchRecommended', $request),
            'providers_full' => fn () => $this->paginatorContent(
                app(CustomerProviderListFetcher::class)->paginate(
                    $this->listRequest($request, ['limit' => 30, 'offset' => 1, 'sort_by' => 'default', 'rating' => 0]),
                    $customerUserId,
                )
            ),
            'campaigns' => fn () => $this->invoke(CampaignController::class, 'index', $request, ['limit' => 10, 'offset' => 1]),
            'advertisements' => fn () => $this->paginatorContent(
                app(CustomerAdvertisementListFetcher::class)->paginate(
                    $this->listRequest($request, ['limit' => 15, 'offset' => 1]),
                    $customerUserId,
                )
            ),
            'featured_categories' => fn () => $this->invoke(CategoryController::class, 'featured', $request, ['limit' => 100, 'offset' => 1]),
            'sub_categories' => fn () => $this->invoke(SubCategoryController::class, 'index', $request, ['limit' => 8, 'offset' => 1]),
            'offline_payment_methods' => fn () => $this->invoke(OfflinePaymentController::class, 'getMethods', $request, ['limit' => 100, 'offset' => 1]),
            'recently_viewed_services' => auth('api')->check()
                ? fn () => $this->invoke(ServiceController::class, 'recentlyViewed', $request, ['limit' => 10, 'offset' => 1])
                : null,
            default => null,
        };
    }

    /**
     * @param  array{sections: list<array<string, mixed>>}  $homeLayout
     * @return array<string, callable(): mixed>
     */
    private function curatedSectionTasks(Request $request, array $homeLayout): array
    {
        $tasks = [];

        foreach ($homeLayout['sections'] ?? [] as $section) {
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
                $contentType === MobileAppManagementService::CONTENT_SERVICES => fn () => $this->invoke(
                    \Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Customer\MobileAppHomeController::class,
                    'sectionServices',
                    $request,
                    ['limit' => $limit, 'offset' => 1],
                    ['key' => $key]
                ),
                $contentType === MobileAppManagementService::CONTENT_PROVIDERS => fn () => $this->invoke(
                    \Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Customer\MobileAppHomeController::class,
                    'sectionProviders',
                    $request,
                    ['limit' => $limit, 'offset' => 1],
                    ['key' => $key]
                ),
                $contentType === MobileAppManagementService::CONTENT_BANNERS => fn () => $this->invoke(
                    \Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Customer\MobileAppHomeController::class,
                    'sectionBanners',
                    $request,
                    ['limit' => $limit, 'offset' => 1],
                    ['key' => $key]
                ),
                in_array($contentType, [
                    MobileAppManagementService::CONTENT_CATEGORIES,
                    MobileAppManagementService::CONTENT_SUB_CATEGORIES,
                ], true) => fn () => $this->invoke(
                    \Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Customer\MobileAppHomeController::class,
                    'sectionCategories',
                    $request,
                    ['limit' => $limit, 'offset' => 1],
                    ['key' => $key]
                ),
                $contentType === MobileAppManagementService::CONTENT_CAMPAIGNS => fn () => $this->invoke(
                    \Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Customer\MobileAppHomeController::class,
                    'sectionCampaigns',
                    $request,
                    ['limit' => $limit, 'offset' => 1],
                    ['key' => $key]
                ),
                default => fn () => null,
            };
        }

        return $tasks;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $route
     * @param  array<string, mixed>  $body
     */
    private function invoke(
        string $controller,
        string $action,
        Request $parent,
        array $query = [],
        array $route = [],
        string $method = 'GET',
        array $body = [],
    ): mixed {
        $request = $this->syntheticRequest($parent, $query, $body, $method);
        $response = app()->call([app($controller), $action], array_merge(['request' => $request], $route));

        return $this->extractContent($response);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $payload
     */
    private function syntheticRequest(Request $parent, array $query = [], array $payload = [], string $method = 'GET'): Request
    {
        $request = Request::create('/', $method, array_merge($query, $payload));
        $request->setUserResolver($parent->getUserResolver());
        $request->headers->replace($parent->headers->all());

        if ($guest = $parent->input('guest_id') ?? $parent->header('guest_id')) {
            $request->merge(['guest_id' => $guest]);
        }

        return $request;
    }

    private function applyRequestContext(Request $request): void
    {
        $zoneId = $request->header('zoneId') ?? $request->header('zoneid');
        if ($zoneId) {
            Config::set('zone_id', $zoneId);
        }
    }

    private function extractContent(mixed $response): mixed
    {
        if (! $response instanceof JsonResponse) {
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $decoded = json_decode($response->getContent(), true);

        return is_array($decoded) ? ($decoded['content'] ?? null) : null;
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

    private function customerUserId(Request $request): mixed
    {
        return auth('api')->id() ?? $request->input('guest_id') ?? $request->header('guest_id');
    }

    private function listRequest(Request $parent, array $params): Request
    {
        return $this->syntheticRequest($parent, $params, $params);
    }

    /**
     * @return array<string, mixed>
     */
    private function paginatorContent(LengthAwarePaginator $paginator): array
    {
        return $paginator->toArray();
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
}
