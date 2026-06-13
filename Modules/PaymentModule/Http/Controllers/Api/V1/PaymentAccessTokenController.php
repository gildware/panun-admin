<?php

namespace Modules\PaymentModule\Http\Controllers\Api\V1;

use App\Lib\PaymentAccessToken;
use App\Services\GuestCheckoutService;
use App\Services\GuestSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class PaymentAccessTokenController extends Controller
{
    public function issueForCustomer(Request $request): JsonResponse
    {
        $user = api_user();
        if ($user) {
            return response()->json(response_formatter(DEFAULT_200, [
                'access_token' => PaymentAccessToken::issue($user->id),
            ]), 200);
        }

        $validator = Validator::make($request->all(), [
            'guest_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_401, null, error_processor($validator)), 401);
        }

        if ($reject = GuestCheckoutService::rejectIfRequiresLogin(false)) {
            return $reject;
        }

        if ($reject = GuestSessionService::rejectIfInvalid($request, false, $request->guest_id)) {
            return $reject;
        }

        return response()->json(response_formatter(DEFAULT_200, [
            'access_token' => PaymentAccessToken::issue($request->guest_id),
        ]), 200);
    }

    public function issueForProvider(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user || !$user->provider) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        return response()->json(response_formatter(DEFAULT_200, [
            'access_token' => PaymentAccessToken::issue($user->id),
        ]), 200);
    }
}
