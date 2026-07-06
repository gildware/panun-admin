<?php

namespace Modules\CategoryManagement\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Modules\CategoryManagement\Entities\Category;
use Modules\CustomerModule\Services\CustomerCategoryPayloadSlimmer;
use Modules\CustomerModule\Services\CustomerServicePayloadSlimmer;
use Modules\ServiceManagement\Entities\FavoriteService;
use Modules\ServiceManagement\Entities\RecentView;
use Modules\ServiceManagement\Entities\Variation;

class CategoryController extends Controller
{

    private Category $category;
    private RecentView $recentView;
    private  FavoriteService $favoriteService;
    private bool $is_customer_logged_in;
    private mixed $customer_user_id;

    public function __construct(Category $category, RecentView $recentView, FavoriteService $favoriteService, Request $request)
    {
        $this->category = $category;
        $this->recentView = $recentView;
        $this->favoriteService = $favoriteService;

        $user = api_user();
        $this->is_customer_logged_in = (bool) $user;
        $this->customer_user_id = $this->is_customer_logged_in ? $user->id : $request['guest_id'];
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $categories = $this->category
            ->ofStatus(1)
            ->ofType('main')
            ->mainWithActiveCatalog()
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        $categories->setCollection(
            $categories->getCollection()->map(function ($category) {
                return CustomerCategoryPayloadSlimmer::slimGridItem($category->toArray());
            })
        );

        return response()->json(response_formatter(DEFAULT_200, $categories), 200);
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function childes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'slug' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $childes = $this->category->ofStatus(1)->ofType('sub')->withoutGlobalScopes(['zone_wise_data'])
            ->withCount(['services' => function ($query) {
                $query->where('is_active', 1);
            }])
            ->having('services_count', '>', 0)
            ->whereHas('parent', function ($query) use ($request) {
                $query->ofStatus(1)->where('slug', $request['slug']);
            })
            ->orderBy('name', 'asc')
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        if ($childes->count() > 0) {
            $authUser = auth('api')->user();
            if ($authUser) {
                $categoryId = $childes->first()->parent_id;
                if ($categoryId) {
                    $recentView = $this->recentView->firstOrNew(['category_id' => $categoryId, 'user_id' => $authUser->id]);
                    $recentView->total_category_view += 1;
                    $recentView->save();
                }
            }

            return response()->json(
                response_formatter(DEFAULT_200, CustomerCategoryPayloadSlimmer::slimPaginator($childes)),
                200
            );
        }

        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function featured(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $categories = $this->category->with([
            'services_by_category' => function ($query) {
                $query->ofStatus(1)
                    ->where(function ($query) {
                        $query->whereDoesntHave('service_discount')
                            ->orWhereHas('service_discount');
                    })
                    ->where(function ($query) {
                        $query->whereDoesntHave('category.category_discount')
                            ->orWhereHas('category.category_discount');
                    })
                    ->with(CustomerServicePayloadSlimmer::listEagerRelations());
            },
        ])
            ->ofStatus(1)
            ->ofFeatured(1)
            ->ofType('main')
            ->mainWithActiveCatalog()
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        foreach ($categories as $category) {
            $category->services_by_category = self::variationMapper($category->services_by_category);
        }

        $categories->setCollection(
            $categories->getCollection()->map(function ($category) {
                return CustomerCategoryPayloadSlimmer::slimFeaturedItem($category->toArray());
            })
        );

        return response()->json(response_formatter(DEFAULT_200, $categories), 200);
    }

    private function variationMapper($services)
    {
        $services->map(function ($service) {
            $service['is_favorite'] = $this->favoriteService->where('customer_user_id',$this->customer_user_id)->where('service_id',$service->id)->exists() ? 1 : 0;
            $service['variations_app_format'] = self::variationsAppFormat($service);
            return $service;
        });
        return $services;
    }

    private function variationsAppFormat($service): array
    {
        return Variation::variationsAppFormatForCustomer((string) $service->id);
    }

}
