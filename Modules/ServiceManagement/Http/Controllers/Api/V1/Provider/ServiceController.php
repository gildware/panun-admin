<?php

namespace Modules\ServiceManagement\Http\Controllers\Api\V1\Provider;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\ProviderManagement\Services\ProviderServicePayloadSlimmer;
use Modules\ReviewModule\Entities\Review;
use Modules\ReviewModule\Entities\ReviewReply;
use Modules\ProviderManagement\Services\CustomerProviderDetailsPayloadSlimmer;
use Modules\ServiceManagement\Services\ProviderServiceDetailsCache;
use Modules\ServiceManagement\Entities\Service;

class ServiceController extends Controller
{
    private Service $service;
    private Review $review;
    private ReviewReply $reviewRepl;
    private SubscribedService $subscribed_service;

    public function __construct(Service $service, Review $review, ReviewReply $reviewReply, SubscribedService $subscribed_service)
    {
        $this->service = $service;
        $this->review = $review;
        $this->reviewReply = $reviewReply;
        $this->subscribed_service = $subscribed_service;
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
            'status' => 'required|in:subscribed,unsubscribed,all',
            'zone_id' => 'uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $ids = $this->subscribed_service->where('provider_id', $request->user()->provider->id)
            ->when($request->has('status') && $request['status'] != 'all', function ($query) use ($request) {
                if ($request['status'] == 'subscribed') {
                    return $query->where(['is_subscribed' => 1]);
                } else {
                    return $query->where(['is_subscribed' => 0]);
                }
            })->pluck('sub_category_id')->toArray();

        $services = $this->service->with(ProviderServicePayloadSlimmer::listEagerRelations())->latest()
            ->whereIn('sub_category_id', $ids)
            ->orWhereIn('category_id', $ids)
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        if (count($services) < 1) {
            return response()->json(response_formatter(DEFAULT_204), 204);
        }

        return response()->json(response_formatter(DEFAULT_200, ProviderServicePayloadSlimmer::slimPaginator($services)), 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @param string $service_id
     * @return JsonResponse
     */
    public function review(Request $request, string $service_id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'status' => 'required|in:active,inactive,all'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }
        $limit = (int) $request['limit'];
        $offset = (int) $request['offset'];
        $status = (string) $request['status'];
        $providerId = (string) auth()->user()->provider->id;
        $cacheKey = ProviderServiceDetailsCache::reviewsCacheKey($service_id, $providerId, $status, $limit, $offset);

        $payload = ProviderServiceDetailsCache::rememberReviews($cacheKey, function () use ($request, $service_id, $providerId, $status, $limit, $offset) {
            $reviewsQuery = $this->review->with(['customer', 'booking.detail', 'service', 'reviewReply'])
                ->where('service_id', $service_id)
                ->where('provider_id', $providerId);

            if ($status === 'active') {
                $reviewsQuery->where('is_active', 1);
            } elseif ($status === 'inactive') {
                $reviewsQuery->where('is_active', 0);
            }

            $reviews = $reviewsQuery->latest()
                ->paginate($limit, ['*'], 'offset', $offset)
                ->withPath('');

            $ratingGroupCount = DB::table('reviews')->where('service_id', $service_id)
                ->where('provider_id', $providerId)
                ->where('is_active', 1)
                ->select('review_rating', DB::raw('count(review_comment) as total_comment'), DB::raw('count(*) as total'))
                ->groupBy('review_rating')
                ->get();

            $activeReviews = DB::table('reviews')->where('service_id', $service_id)
                ->where('provider_id', $providerId)
                ->where('is_active', 1)
                ->select('review_rating', DB::raw('count(*) as total'))
                ->groupBy('review_rating')
                ->get();

            $totalRating = 0;
            $ratingCount = 0;
            $reviewCount = 0;

            foreach ($ratingGroupCount as $count) {
                $totalRating += round($count->review_rating * $count->total, 2);
                $ratingCount += $count->total;
                $reviewCount += $count->total_comment;
            }

            $totalActiveRating = 0;
            $activeRatingCount = 0;

            foreach ($activeReviews as $activeReview) {
                $totalActiveRating += round($activeReview->review_rating * $activeReview->total, 2);
                $activeRatingCount += $activeReview->total;
            }

            $ratingInfo = [
                'rating_count' => $ratingCount,
                'review_count' => $reviewCount,
                'average_rating' => $activeRatingCount > 0 ? round($totalActiveRating / $activeRatingCount, 2) : 0,
                'rating_group_count' => $ratingGroupCount
                    ->map(fn ($row) => [
                        'review_rating' => $row->review_rating,
                        'total_comment' => $row->total_comment ?? null,
                        'total' => $row->total,
                    ])
                    ->values()
                    ->all(),
            ];

            return CustomerProviderDetailsPayloadSlimmer::slimPaginatedReviews($reviews, $ratingInfo);
        });

        return response()->json(response_formatter(DEFAULT_200, $payload ?? ['reviews' => [], 'rating' => []]), 200);

    }


    /**
     * Show the specified resource.
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $providerId = (string) auth()->user()->provider->id;
        $cacheKey = ProviderServiceDetailsCache::detailCacheKey(request(), $id, $providerId);

        $payload = ProviderServiceDetailsCache::rememberDetail($cacheKey, function () use ($id) {
            $service = $this->service->where('id', $id)->with([
                'variations',
                'serviceVariants.storage_image',
            ])->first();

            if (! isset($service)) {
                return null;
            }

            $service = self::variationsReactFormat($service);

            return is_array($service) ? $service : $service->toArray();
        });

        if ($payload !== null) {
            return response()->json(response_formatter(DEFAULT_200, $payload), 200);
        }

        return response()->json(response_formatter(DEFAULT_204), 200);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('servicemanagement::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function statusUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:1,0',
            'sub_category_ids' => 'required|array',
            'sub_category_ids.*' => 'uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $this->subscribed_service->whereIn('sub_category_id', $request['sub_category_ids'])->update(['is_subscribed' => $request['status']]);

        return response()->json(response_formatter(DEFAULT_STATUS_UPDATE_200), 200);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    private function variationsReactFormat($service)
    {
        $service->loadMissing(['variations', 'serviceVariants.storage_image']);
        $service['variations_react_format'] = $service->buildVariationsReactFormat();

        return $service;
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
            'offset' => 'required|numeric|min:1|max:100000'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $keys = explode(' ', base64_decode($request['string']));

        $service = $this->service->where(function ($query) use ($keys) {
            foreach ($keys as $key) {
                $query->orWhere('name', 'LIKE', '%' . $key . '%');
            }
            })
            ->when($request->has('status') && $request['status'] != 'all', function ($query) use ($request) {
                if ($request['status'] == 'active') {
                    return $query->where(['is_active' => 1]);
                } else {
                    return $query->where(['is_active' => 0]);
                }
            })
            ->with(['category.zonesBasicInfo'])->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        if (count($service) > 0) {
            return response()->json(response_formatter(DEFAULT_200, $service), 200);
        }
        return response()->json(response_formatter(DEFAULT_204, $service), 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function servicesBySubcategory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'sub_category_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        if (!request()?->user()?->provider) return response()->json(response_formatter(DEFAULT_403), 403);

        $services = $this->service->with(ProviderServicePayloadSlimmer::listEagerRelations())->ordered()
            ->whereHas('subCategory', fn ($query) => $query->where('sub_category_id', $request['sub_category_id']))
            ->when($request->has('search'), function ($query) use ($request){
                $keys = explode(' ', $request['search']);
                $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->where('name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->active()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        if (!isset($services)) return response()->json(response_formatter(DEFAULT_404), 404);

        return response()->json(response_formatter(DEFAULT_200, ProviderServicePayloadSlimmer::slimPaginator($services)), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function reviewReply(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'review_id' => 'required',
            'reply_content' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $providerUserId = auth('api')->user()->id;
        $review_id = $request->review_id;

        $readableId = $this->review->where('id', $review_id)->value('readable_id') ?? 0;


        $reviewReply = $this->reviewReply
            ->where('review_id', 'like', "{$review_id}%")
            ->orderBy('readable_id', 'desc')
            ->first();

        if (!$reviewReply) {
            $reviewReply = $this->reviewReply;
        }

        $reviewReply->review_id = $review_id;
        $reviewReply->readable_id = $readableId;
        $reviewReply->user_id = $providerUserId;
        $reviewReply->reply = $request->reply_content;
        $reviewReply->save();

        return response()->json(response_formatter(DEFAULT_200), 200);

    }
}
