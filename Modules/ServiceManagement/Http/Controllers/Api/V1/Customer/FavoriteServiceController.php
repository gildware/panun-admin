<?php

namespace Modules\ServiceManagement\Http\Controllers\Api\V1\Customer;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\CustomerModule\Services\CustomerServicePayloadSlimmer;
use Modules\ServiceManagement\Entities\FavoriteService;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Services\CustomerServiceResponseEnricher;

class FavoriteServiceController extends Controller
{
    private FavoriteService $favoriteService;
    private Service $service;

    public function __construct(FavoriteService $favoriteService, Service $service)
    {
        $this->favoriteService = $favoriteService;
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $services = $this->service->with(CustomerServicePayloadSlimmer::listEagerRelations())
            ->whereHas('favorites', function ($query) {
                $query->where('customer_user_id', auth('api')->user()->id);
            })
            ->active()->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        CustomerServiceResponseEnricher::enrich($services, auth('api')->id(), includeTax: false);
        foreach ($services as $service) {
            $service['is_favorite'] = 1;
        }

        return response()->json(
            response_formatter(DEFAULT_200, CustomerServicePayloadSlimmer::slimPaginator($services)),
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $favorite = $this->favoriteService->where('customer_user_id', $request->user()->id)->where('service_id', $request->service_id)->first();

        if ($favorite) {
            $favorite->delete();
            $status = 0;
        } else {
            $favorite = $this->favoriteService;
            $favorite->customer_user_id = $request->user()->id;
            $favorite->service_id = $request->service_id;
            $favorite->save();
            $status = 1;
        }

        if ($status) {
            return response()->json(response_formatter(SERVICE_ADD_TO_FAVORITE_200, ['status' => $status]), 200);
        }

        return response()->json(response_formatter(SERVICE_REMOVE_FAVORITE_200, ['status' => $status]), 200);
    }

    /**
     * Remove the specified resource from storage.
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $favorite = $this->favoriteService->where('customer_user_id', $request->user()->id)->where('service_id', $id)->first();

        if ($favorite) {

            $favorite->delete();

            return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
        }

        return response()->json(response_formatter(DEFAULT_404), 400);
    }
}
