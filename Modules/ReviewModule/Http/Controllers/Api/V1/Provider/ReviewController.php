<?php

namespace Modules\ReviewModule\Http\Controllers\Api\V1\Provider;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\ProviderManagement\Services\CustomerProviderDetailsPayloadSlimmer;
use Modules\ReviewModule\Entities\Review;

class ReviewController extends Controller
{
    private $review;

    public function __construct(Review $review)
    {
        $this->review = $review;
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
            'offset' => 'required|numeric|min:1|max:100000',
            'status' => 'required|in:active,inactive,all'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $providerId = $request->user()->provider->id;

        $reviews = $this->review->with([
            'customer:id,first_name,last_name,profile_image',
            'service:id,name',
            'reviewReply:id,review_id,reply,updated_at',
            'booking:id,readable_id',
            'booking.detail:id,booking_id,service_id,variant_key',
        ])
            ->where('provider_id', $providerId)
            ->when($request->has('status') && $request['status'] != 'all', function ($query) use ($request) {
                return $query->ofStatus(($request['status'] == 'active') ? 1 : 0);
            })->latest()->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        $ratingGroupCount = DB::table('reviews')->where('provider_id', $providerId)
            ->where('is_active', 1)
            ->select('review_rating', DB::raw('count(review_comment) as total_comment'), DB::raw('count(*) as total'))
            ->groupBy('review_rating')
            ->get();

        $reviewCount = (int) $reviews->total();
        if ($reviewCount === 0) {
            foreach ($ratingGroupCount as $count) {
                $reviewCount += (int) ($count->total ?? 0);
            }
        }

        $ratingInfo = [
            'rating_count' => $request->user()->provider['rating_count'],
            'review_count' => $reviewCount,
            'average_rating' => $request->user()->provider['avg_rating'],
            'rating_group_count' => $ratingGroupCount
                ->map(fn ($row) => [
                    'review_rating' => $row->review_rating,
                    'total_comment' => $row->total_comment ?? null,
                    'total' => $row->total,
                ])
                ->values()
                ->all(),
        ];

        $payload = CustomerProviderDetailsPayloadSlimmer::slimPaginatedReviews($reviews, $ratingInfo);

        return response()->json(response_formatter(DEFAULT_200, $payload), 200);
    }

    /**
     * Show the specified resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'string' => 'required',
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'status' => 'required|in:all,active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $keys = explode(' ', base64_decode($request['string']));
        $reviews = $this->review->where('provider_id', $request->user()->provider->id)
            ->where(function ($query) use ($keys) {
                foreach ($keys as $key) {
                    $query->orWhere('booking_id', 'LIKE', '%' . $key . '%')
                        ->orWhere('provider_id', 'LIKE', '%' . $key . '%');
                }
            })->when($request['status'] != 'all', function ($query) use ($request) {
                return $query->ofStatus(($request['status'] == 'active') ? 1 : 0);
            })->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        if ($reviews->count() > 0) {
            return response()->json(response_formatter(DEFAULT_200, $reviews), 200);
        }
        return response()->json(response_formatter(DEFAULT_204, $reviews), 200);
    }

}
