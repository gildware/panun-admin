<?php

namespace Modules\ReviewModule\Http\Controllers\Api\V1\Provider;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Entities\Booking;
use Modules\ReviewModule\Entities\ProviderCustomerReview;
use Modules\ReviewModule\Services\CustomerRatingService;

class CustomerReviewController extends Controller
{
    public function __construct(
        private readonly ProviderCustomerReview $providerCustomerReview,
        private readonly Booking $booking,
        private readonly CustomerRatingService $customerRatingService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $providerId = $request->user()->provider->id;

        $review = $this->providerCustomerReview
            ->where('booking_id', $request->booking_id)
            ->where('provider_id', $providerId)
            ->first();

        return response()->json(response_formatter(DEFAULT_200, $review), 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|uuid',
            'review_rating' => 'required|numeric|min:1|max:5',
            'review_comment' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $providerId = $request->user()->provider->id;
        $booking = $this->booking->find($request->booking_id);

        if (!isset($booking)) {
            return response()->json(response_formatter(DEFAULT_404), 200);
        }

        if ($booking->booking_status !== 'completed') {
            return response()->json(response_formatter(DEFAULT_403, null, [[
                'error_code' => 'booking_not_completed',
                'message' => translate('You can only rate a customer after the booking is completed'),
            ]]), 403);
        }

        if ((string) $booking->provider_id !== (string) $providerId) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        if ($booking->isLossMakingFinancialSettlement()) {
            return response()->json(response_formatter(DEFAULT_403, null, [[
                'error_code' => 'loss_making_pending',
                'message' => translate('Review_not_available_until_loss_settled'),
            ]]), 403);
        }

        $review = $this->providerCustomerReview
            ->where('booking_id', $request->booking_id)
            ->where('provider_id', $providerId)
            ->first();

        if (!isset($review)) {
            $review = new ProviderCustomerReview();
        }

        $review->booking_id = $booking->id;
        $review->provider_id = $providerId;
        $review->customer_id = $booking->customer_id;
        $review->review_rating = (int) $request->review_rating;
        $review->review_comment = $request->review_comment;
        $review->booking_date = $booking->created_at;
        $review->is_active = 0;

        if (!$review->readable_id) {
            $baseReadableId = $booking->readable_id;
            $lastReview = $this->providerCustomerReview
                ->where('readable_id', 'like', "{$baseReadableId}%")
                ->orderBy('readable_id', 'desc')
                ->first();

            if ($lastReview) {
                $lastIdNumber = (int) substr($lastReview->readable_id, -3);
                $newReadableId = $baseReadableId . str_pad($lastIdNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $newReadableId = $baseReadableId . '200';
            }

            $review->readable_id = $newReadableId;
        }

        $review->save();

        $this->customerRatingService->syncReceivedRatings((string) $booking->customer_id);

        return response()->json(response_formatter(REVIEW_SUBMITTED_PENDING_APPROVAL_200, $review), 200);
    }
}
