<?php

namespace Modules\PromotionManagement\Http\Controllers\Api\V1\Customer;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\PromotionManagement\Services\CustomerAdvertisementListFetcher;

class AdvertisementsController extends Controller
{
    public function __construct(
        private CustomerAdvertisementListFetcher $advertisementListFetcher,
    ) {}

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function AdsList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $customerUserId = auth('api')->user()?->id ?? $request['guest_id'];
        $advertisements = $this->advertisementListFetcher->paginate($request, $customerUserId);

        return response()->json(response_formatter(DEFAULT_200, $advertisements), 200);
    }
}
