<?php

namespace Modules\CustomerModule\Http\Controllers\Api\V1\Customer;

use App\Services\GuestCheckoutService;
use App\Services\GuestSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class GuestSessionController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => 'required|uuid',
            'guest_secret' => 'required|string|min:32|max:128',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        if ($reject = GuestCheckoutService::rejectIfRequiresLogin(false)) {
            return $reject;
        }

        GuestSessionService::register($request->guest_id, $request->guest_secret);

        return response()->json(response_formatter(DEFAULT_200), 200);
    }
}
