<?php

namespace Modules\ReviewModule\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\ReviewModule\Entities\ProviderCustomerReview;
use Modules\UserManagement\Entities\User;

class ReceivedRatingController extends Controller
{
    public function __construct(
        private readonly ProviderCustomerReview $providerCustomerReview,
        private readonly User $user,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        if (!user_can_use_customer_app($request->user())) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:50',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $customer = $this->user->find($request->user()->id);

        $reviews = $this->providerCustomerReview
            ->with([
                'provider:id,company_name,logo',
                'booking:id,readable_id,created_at',
            ])
            ->where('customer_id', $request->user()->id)
            ->where('is_active', 1)
            ->orderByDesc('created_at')
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        return response()->json(response_formatter(DEFAULT_200, [
            'reviews' => $reviews,
            'rating' => [
                'average_rating' => (float) ($customer->received_avg_rating ?? 0),
                'rating_count' => (int) ($customer->received_rating_count ?? 0),
            ],
        ]), 200);
    }
}
