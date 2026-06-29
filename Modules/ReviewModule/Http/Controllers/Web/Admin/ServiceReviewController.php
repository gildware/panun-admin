<?php

namespace Modules\ReviewModule\Http\Controllers\Web\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ReviewModule\Entities\Review;
use Modules\ReviewModule\Services\ProviderReviewRatingService;

class ServiceReviewController extends Controller
{
    public function __construct(
        private readonly Review $review,
        private readonly ProviderReviewRatingService $providerReviewRatingService,
    ) {
    }

    public function approve(string $id): JsonResponse
    {
        $review = $this->review->where('id', $id)->first();

        if (!$review) {
            return response()->json(['message' => translate('Resource not found')], 404);
        }

        if (!$review->is_active) {
            $this->review->where('id', $id)->update(['is_active' => 1]);
            $this->providerReviewRatingService->syncForReviewTargets($review->service_id, $review->provider_id);
            $review->refresh();
            send_review_approved_to_provider_notification($review);
            send_review_published_to_customer_notification($review);
        }

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $review = $this->review->where('id', $id)->first();

        if (!$review) {
            return response()->json(['message' => translate('Resource not found')], 404);
        }

        $serviceId = $review->service_id;
        $providerId = $review->provider_id;

        $review->reviewReply?->delete();
        $review->delete();

        $this->providerReviewRatingService->syncForReviewTargets($serviceId, $providerId);

        return response()->json(response_formatter(DEFAULT_DELETE_200), 200);
    }
}
