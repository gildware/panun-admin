<?php

namespace Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Modules\BusinessSettingsModule\Services\MobileAppManagementService;
use Modules\CategoryManagement\Entities\Category;
use Modules\PromotionManagement\Entities\Banner;
use Modules\PromotionManagement\Entities\Campaign;
use Modules\ProviderManagement\Entities\FavoriteProvider;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Services\ZoneProviderEligibilityService;
use Modules\ServiceManagement\Entities\FavoriteService;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\PromotionManagement\Support\CustomerCampaignApiQuery;

class MobileAppHomeController extends Controller
{
    public function __construct(
        protected MobileAppManagementService $managementService,
        protected Service $service,
        protected Provider $provider,
        protected Banner $banner,
        protected Campaign $campaign,
        protected Category $category,
        protected FavoriteService $favoriteService,
        protected FavoriteProvider $favoriteProvider,
    ) {
    }

    private function customerContext(Request $request): array
    {
        $user = auth('api')->user();

        return [
            'logged_in' => (bool) $user,
            'user_id' => $user?->id ?? $request->input('guest_id'),
        ];
    }

    public function sectionServices(Request $request, string $key): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:50',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $section = $this->managementService->getSectionByKey($key);
        if (!$section || !($section['enabled'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        if (($section['data_mode'] ?? 'default') !== 'manual') {
            return response()->json(response_formatter(DEFAULT_400, null, [['error_code' => 'data_mode', 'message' => 'Section does not use manual service data']]), 400);
        }

        $ids = array_values(array_filter($section['service_ids'] ?? []));
        if ($ids === []) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $limit = (int) $request['limit'];
        $offset = (int) $request['offset'];

        $services = $this->resolveManualServicesForZone($ids);
        if ($services->isEmpty()) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $matched = $services->slice(($offset - 1) * $limit, $limit)->values();

        $customerUserId = $this->customerContext($request)['user_id'];
        foreach ($matched as $service) {
            $service['is_favorite'] = $customerUserId && $this->favoriteService
                ->where('customer_user_id', $customerUserId)
                ->where('service_id', $service->id)
                ->exists() ? 1 : 0;
        }

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $this->mapServices($matched),
            $services->count(),
            $limit,
            $offset,
            ['path' => '']
        );

        return response()->json(response_formatter(DEFAULT_200, $paginator), 200);
    }

    public function sectionProviders(Request $request, string $key): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:50',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $section = $this->managementService->getSectionByKey($key);
        if (!$section || !($section['enabled'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        if (($section['data_mode'] ?? 'default') !== 'manual') {
            return response()->json(response_formatter(DEFAULT_400, null, [['error_code' => 'data_mode', 'message' => 'Section does not use manual provider data']]), 400);
        }

        $ids = array_values(array_filter($section['provider_ids'] ?? []));
        if ($ids === []) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $limit = (int) $request['limit'];
        $offset = (int) $request['offset'];

        $zoneEligibleIds = $this->zoneEligibleProviderIds($ids);
        if ($zoneEligibleIds === []) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $pageIds = array_slice($zoneEligibleIds, ($offset - 1) * $limit, $limit);

        $orderSql = 'FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $pageIds)).')';

        $providers = $this->provider
            ->with(['owner', 'subscribed_services.sub_category' => function ($query) {
                $query->withoutGlobalScopes();
            }])
            ->whereIn('id', $pageIds)
            ->ofStatus(1)
            ->where('app_availability', 1)
            ->where('is_suspended', 0)
            ->withCount(['bookings as total_service_served' => function ($query) {
                $query->where('booking_status', 'completed');
            }, 'subscribed_services'])
            ->orderByRaw($orderSql)
            ->get();

        $customerUserId = $this->customerContext($request)['user_id'];
        foreach ($providers as $provider) {
            $provider['is_favorite'] = $customerUserId && $this->favoriteProvider
                ->where('customer_user_id', $customerUserId)
                ->where('provider_id', $provider->id)
                ->exists() ? 1 : 0;
        }

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $providers,
            count($zoneEligibleIds),
            $limit,
            $offset,
            ['path' => '']
        );

        return response()->json(response_formatter(DEFAULT_200, $paginator), 200);
    }

    public function sectionBanners(Request $request, string $key): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:50',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $section = $this->managementService->getSectionByKey($key);
        if (!$section || !($section['enabled'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        if (($section['data_mode'] ?? 'default') !== 'manual') {
            return response()->json(response_formatter(DEFAULT_400, null, [['error_code' => 'data_mode', 'message' => 'Section does not use manual banner data']]), 400);
        }

        $ids = array_values(array_filter($section['banner_ids'] ?? []));
        if ($ids === []) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $limit = (int) $request['limit'];
        $offset = (int) $request['offset'];

        $matchedBannerIds = $this->banner
            ->ofStatus(1)
            ->whereIn('id', $ids)
            ->orderByRaw('FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $ids)).')')
            ->pluck('id')
            ->all();

        if ($matchedBannerIds === []) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $pageIds = array_slice($matchedBannerIds, ($offset - 1) * $limit, $limit);

        $orderSql = 'FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $pageIds)).')';

        $banners = $this->banner
            ->with(['service', 'category'])
            ->ofStatus(1)
            ->whereIn('id', $pageIds)
            ->orderByRaw($orderSql)
            ->get()
            ->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $banners,
            count($matchedBannerIds),
            $limit,
            $offset,
            ['path' => '']
        );

        return response()->json(response_formatter(DEFAULT_200, $paginator), 200);
    }

    public function sectionCampaigns(Request $request, string $key): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:50',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $section = $this->managementService->getSectionByKey($key);
        if (!$section || !($section['enabled'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        if (($section['data_mode'] ?? 'default') !== 'manual') {
            return response()->json(response_formatter(DEFAULT_400, null, [['error_code' => 'data_mode', 'message' => 'Section does not use manual campaign data']]), 400);
        }

        $ids = array_values(array_filter($section['campaign_ids'] ?? []));
        if ($ids === []) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $limit = (int) $request['limit'];
        $offset = (int) $request['offset'];

        $matchedCampaignIds = CustomerCampaignApiQuery::withCustomerRelations($this->campaign->newQuery())
            ->whereIn('id', $ids)
            ->ofStatus(1)
            ->orderByRaw('FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $ids)).')')
            ->pluck('id')
            ->all();

        if ($matchedCampaignIds === []) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $pageIds = array_slice($matchedCampaignIds, ($offset - 1) * $limit, $limit);

        $orderSql = 'FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $pageIds)).')';

        $campaigns = CustomerCampaignApiQuery::withCustomerRelations($this->campaign->newQuery())
            ->whereIn('id', $pageIds)
            ->ofStatus(1)
            ->orderByRaw($orderSql)
            ->get();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $campaigns,
            count($matchedCampaignIds),
            $limit,
            $offset,
            ['path' => '']
        );

        return response()->json(response_formatter(DEFAULT_200, $paginator), 200);
    }

    public function sectionCategories(Request $request, string $key): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:50',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $section = $this->managementService->getSectionByKey($key);
        if (!$section || !($section['enabled'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        if (($section['data_mode'] ?? 'default') !== 'manual') {
            return response()->json(response_formatter(DEFAULT_400, null, [['error_code' => 'data_mode', 'message' => 'Section does not use manual category data']]), 400);
        }

        $ids = array_values(array_filter($section['category_ids'] ?? []));
        if ($ids === []) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $limit = (int) $request['limit'];
        $offset = (int) $request['offset'];

        $contentType = (string) ($section['content_type'] ?? '');
        $categoryType = $contentType === MobileAppManagementService::CONTENT_SUB_CATEGORIES ? 'sub' : 'main';

        if ($key === 'feathered_categories') {
            $matchedCategoryIds = $this->category
                ->ofStatus(1)
                ->ofType('main')
                ->mainWithActiveCatalog()
                ->whereIn('id', $ids)
                ->tap(fn ($query) => $this->applyCustomerZoneToCategoryQuery($query))
                ->orderByRaw('FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $ids)).')')
                ->pluck('id')
                ->all();

            if ($matchedCategoryIds === []) {
                return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
            }

            $pageIds = array_slice($matchedCategoryIds, ($offset - 1) * $limit, $limit);
            $orderSql = 'FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $pageIds)).')';

            $categories = $this->category->with(['zonesBasicInfo', 'services_by_category.variations', 'services_by_category' => function ($query) {
                $query->ofStatus(1)
                    ->where(function ($query) {
                        $query->whereDoesntHave('service_discount')
                            ->orWhereHas('service_discount');
                    })
                    ->where(function ($query) {
                        $query->whereDoesntHave('category.category_discount')
                            ->orWhereHas('category.category_discount');
                    })
                    ->with(['variations', 'service_discount', 'category.category_discount']);
                $this->applyCustomerZoneToServiceQuery($query);
            }])
                ->ofStatus(1)
                ->ofType('main')
                ->mainWithActiveCatalog()
                ->whereIn('id', $pageIds)
                ->tap(fn ($query) => $this->applyCustomerZoneToCategoryQuery($query))
                ->orderByRaw($orderSql)
                ->get();

            $customerUserId = $this->customerContext($request)['user_id'];
            foreach ($categories as $category) {
                $category->services_by_category = $this->mapCategoryServices($category->services_by_category, $customerUserId);
            }

            $collection = $categories;
            $ids = $matchedCategoryIds;
        } else {
            $with = $categoryType === 'sub' ? ['parent'] : ['zones'];
            $matchedCategoryIds = $this->category
                ->withCount(['services' => function ($query) {
                    $query->where('is_active', 1);
                }])
                ->with($with)
                ->ofStatus(1)
                ->ofType($categoryType)
                ->whereIn('id', $ids)
                ->tap(fn ($query) => $this->applyCustomerZoneToCategoryQuery($query))
                ->orderByRaw('FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $ids)).')')
                ->pluck('id')
                ->all();
            $pageIds = array_slice($matchedCategoryIds, ($offset - 1) * $limit, $limit);

            if ($pageIds === []) {
                return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
            }

            $orderSql = 'FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $pageIds)).')';

            $collectionQuery = $this->category
                ->withCount(['services' => function ($query) {
                    $query->where('is_active', 1);
                }])
                ->with($with)
                ->ofStatus(1)
                ->ofType($categoryType)
                ->whereIn('id', $pageIds)
                ->tap(fn ($query) => $this->applyCustomerZoneToCategoryQuery($query));

            if ($categoryType === 'sub') {
                $collectionQuery->withActiveServices();
            }

            $collection = $collectionQuery->orderByRaw($orderSql)->get();
            $ids = $matchedCategoryIds;
        }

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $collection,
            count($ids),
            $limit,
            $offset,
            ['path' => '']
        );

        return response()->json(response_formatter(DEFAULT_200, $paginator), 200);
    }

    private function mapCategoryServices($services, mixed $customerUserId = null)
    {
        return $services->map(function ($service) use ($customerUserId) {
            $service['is_favorite'] = $customerUserId && $this->favoriteService
                ->where('customer_user_id', $customerUserId)
                ->where('service_id', $service->id)
                ->exists() ? 1 : 0;
            $service['variations_app_format'] = Variation::variationsAppFormatForCustomer((string) $service->id);

            return $service;
        });
    }

    private function mapServices($services)
    {
        return $services->map(function ($service) {
            $service->loadMissing(['category', 'subCategory']);
            $service->setAttribute('tax', effective_service_tax_percentage($service));
            $service->setAttribute('tax_label', effective_service_tax_label($service));
            $service['variations_app_format'] = Variation::variationsAppFormatForCustomer((string) $service->id);

            return $service;
        });
    }

    private function emptyPaginator(Request $request): \Illuminate\Pagination\LengthAwarePaginator
    {
        return new \Illuminate\Pagination\LengthAwarePaginator(
            [],
            0,
            (int) $request->input('limit', 10),
            (int) $request->input('offset', 1),
            ['path' => '']
        );
    }

    /**
     * Resolve admin-picked services in display order, respecting the customer zone scope.
     *
     * @param  list<string>  $orderedIds
     */
    private function resolveManualServicesForZone(array $orderedIds): \Illuminate\Support\Collection
    {
        if ($orderedIds === []) {
            return collect();
        }

        $orderSql = 'FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $orderedIds)).')';

        $query = $this->service
            ->with(['category.zonesBasicInfo', 'variations', 'service_discount', 'category.category_discount'])
            ->whereIn('id', $orderedIds)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereDoesntHave('service_discount')
                        ->orWhereHas('service_discount');
                })->orWhere(function ($query) {
                    $query->whereDoesntHave('category.category_discount')
                        ->orWhereHas('category.category_discount');
                });
            })
            ->active()
            ->orderByRaw($orderSql);

        $this->applyCustomerZoneToServiceQuery($query);

        return $query->get();
    }

    /**
     * @return list<string>
     */
    private function customerZoneIds(): array
    {
        $raw = Config::get('zone_id');

        return Variation::parseZoneIdCandidates(is_string($raw) ? $raw : null);
    }

    private function applyCustomerZoneToServiceQuery($query): void
    {
        $zoneIds = $this->customerZoneIds();
        if ($zoneIds === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereHas('category.zones', function ($zoneQuery) use ($zoneIds) {
            $zoneQuery->whereIn('category_zone.zone_id', $zoneIds);
        });
    }

    private function applyCustomerZoneToCategoryQuery($query): void
    {
        $zoneIds = $this->customerZoneIds();
        if ($zoneIds === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereHas('zones', function ($zoneQuery) use ($zoneIds) {
            $zoneQuery->whereIn('category_zone.zone_id', $zoneIds);
        });
    }

    /**
     * @param  list<string>  $orderedIds
     * @return list<string>
     */
    private function zoneEligibleProviderIds(array $orderedIds): array
    {
        if ($orderedIds === []) {
            return [];
        }

        $eligibleSet = array_flip(app(ZoneProviderEligibilityService::class)->bookingEligibleIds());

        return array_values(array_filter(
            $orderedIds,
            static fn (string $id): bool => isset($eligibleSet[$id]),
        ));
    }
}
