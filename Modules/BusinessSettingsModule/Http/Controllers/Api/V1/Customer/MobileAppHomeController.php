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
use Modules\ServiceManagement\Entities\FavoriteService;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;

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
        $pageIds = array_slice($ids, ($offset - 1) * $limit, $limit);

        if ($pageIds === []) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $orderSql = 'FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $pageIds)).')';

        $services = $this->service
            ->with(['category.zonesBasicInfo', 'variations', 'service_discount', 'category.category_discount'])
            ->whereIn('id', $pageIds)
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
            ->orderByRaw($orderSql)
            ->get();

        $customerUserId = $this->customerContext($request)['user_id'];
        foreach ($services as $service) {
            $service['is_favorite'] = $customerUserId && $this->favoriteService
                ->where('customer_user_id', $customerUserId)
                ->where('service_id', $service->id)
                ->exists() ? 1 : 0;
        }

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $this->mapServices($services),
            count($ids),
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
        $pageIds = array_slice($ids, ($offset - 1) * $limit, $limit);

        if ($pageIds === []) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $orderSql = 'FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $pageIds)).')';

        // Admin-picked provider IDs must resolve regardless of customer zone.
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
            count($ids),
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
        $pageIds = array_slice($ids, ($offset - 1) * $limit, $limit);

        if ($pageIds === []) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $orderSql = 'FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $pageIds)).')';

        $banners = $this->banner->with(['service', 'category'])
            ->ofStatus(1)
            ->whereIn('id', $pageIds)
            ->orderByRaw($orderSql)
            ->get()
            ->filter(function ($item) {
                if ($item->resource_type == 'service' && is_null($item->service)) {
                    return false;
                }
                if ($item->resource_type == 'category' && is_null($item->category)) {
                    return false;
                }

                return true;
            })
            ->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $banners,
            count($ids),
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
        $pageIds = array_slice($ids, ($offset - 1) * $limit, $limit);

        if ($pageIds === []) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $orderSql = 'FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $pageIds)).')';

        $campaigns = $this->campaign
            ->withoutGlobalScope('zone_wise_data')
            ->with(['discount', 'discount.category_types.category', 'discount.service_types.service.category', 'discount.service_types.service.subCategory'])
            ->whereIn('id', $pageIds)
            ->ofStatus(1)
            ->orderByRaw($orderSql)
            ->get();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $campaigns,
            count($ids),
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
        $pageIds = array_slice($ids, ($offset - 1) * $limit, $limit);

        if ($pageIds === []) {
            return response()->json(response_formatter(DEFAULT_200, $this->emptyPaginator($request)), 200);
        }

        $orderSql = 'FIELD(id,'.implode(',', array_map(fn ($id) => "'".addslashes($id)."'", $pageIds)).')';

        $contentType = (string) ($section['content_type'] ?? '');
        $categoryType = $contentType === MobileAppManagementService::CONTENT_SUB_CATEGORIES ? 'sub' : 'main';

        if ($key === 'feathered_categories') {
            $categories = $this->category->with(['zones', 'services_by_category.variations', 'services_by_category' => function ($query) {
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
            }])
                ->ofStatus(1)
                ->ofType('main')
                ->mainWithActiveCatalog()
                ->whereIn('id', $pageIds)
                ->orderByRaw($orderSql)
                ->get();

            $customerUserId = $this->customerContext($request)['user_id'];
            foreach ($categories as $category) {
                $category->services_by_category = $this->mapCategoryServices($category->services_by_category, $customerUserId);
            }

            $collection = $categories;
        } else {
            // Admin-picked IDs must resolve regardless of customer zone (same as category/childes APIs).
            $with = $categoryType === 'sub' ? ['parent'] : ['zones'];
            $collectionQuery = $this->category->withoutGlobalScope('zone_wise_data')
                ->withCount(['services' => function ($query) {
                    $query->where('is_active', 1);
                }])
                ->with($with)
                ->ofStatus(1)
                ->ofType($categoryType)
                ->whereIn('id', $pageIds);

            if ($categoryType === 'sub') {
                $collectionQuery->withActiveServices();
            }

            $collection = $collectionQuery->orderByRaw($orderSql)->get();
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
}
