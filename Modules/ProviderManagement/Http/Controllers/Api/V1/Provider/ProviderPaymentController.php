<?php

namespace Modules\ProviderManagement\Http\Controllers\Api\V1\Provider;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Services\ProviderPaymentTabDataService;

class ProviderPaymentController extends Controller
{
    public function __construct(
        protected Provider $provider,
        protected ProviderPaymentTabDataService $paymentTabDataService,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $provider = $this->provider->with('owner.account')->where('user_id', $request->user()->id)->first();
        if (!$provider) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        return response()->json(response_formatter(DEFAULT_200, [
            'overview' => $this->paymentTabDataService->overview($provider),
        ]), 200);
    }

    public function list(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_sub' => 'required|in:ledger,recorded,earning,special_earning,disputed',
            'offset' => 'required|integer|min:1|max:10000',
            'limit' => 'required|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $provider = $this->provider->where('user_id', $request->user()->id)->first();
        if (!$provider) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $list = $this->paymentTabDataService->list(
            $provider,
            (string) $request->input('payment_sub'),
            (int) $request->input('offset'),
            (int) $request->input('limit'),
        );

        return response()->json(response_formatter(DEFAULT_200, $list), 200);
    }
}
