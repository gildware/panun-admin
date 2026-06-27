<?php

namespace Modules\ReviewModule\Http\Controllers\Web\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ReviewModule\Entities\ProviderCustomerReview;
use Modules\ReviewModule\Services\CustomerRatingService;

class CustomerReviewController extends Controller
{
    public function __construct(
        private readonly ProviderCustomerReview $providerCustomerReview,
        private readonly CustomerRatingService $customerRatingService,
    ) {
    }

    public function statusUpdate(string $id): JsonResponse
    {
        $review = $this->providerCustomerReview->where('id', $id)->first();

        if (!$review) {
            return response()->json(['message' => translate('Resource not found')], 404);
        }

        $this->providerCustomerReview->where('id', $id)->update(['is_active' => !$review->is_active]);
        $this->customerRatingService->syncReceivedRatings((string) $review->customer_id);

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    public function approve(string $id): JsonResponse
    {
        $review = $this->providerCustomerReview->where('id', $id)->first();

        if (!$review) {
            return response()->json(['message' => translate('Resource not found')], 404);
        }

        if (!$review->is_active) {
            $this->providerCustomerReview->where('id', $id)->update(['is_active' => 1]);
            $this->customerRatingService->syncReceivedRatings((string) $review->customer_id);
            $review->refresh();
            send_review_approved_to_customer_notification($review);
        }

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $review = $this->providerCustomerReview->where('id', $id)->first();

        if (!$review) {
            return response()->json(['message' => translate('Resource not found')], 404);
        }

        $customerId = (string) $review->customer_id;
        $review->delete();
        $this->customerRatingService->syncReceivedRatings($customerId);

        return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
    }
}
