<?php

namespace Modules\ProviderManagement\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Entities\Booking;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\FavoriteProvider;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderShowcaseItem;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\ProviderManagement\Services\CustomerProviderDetailsCache;
use Modules\ProviderManagement\Services\CustomerProviderDetailsPayloadSlimmer;
use Modules\CustomerModule\Services\CustomerProviderPayloadSlimmer;
use Modules\ProviderManagement\Services\CustomerProviderDetailsService;
use Modules\ProviderManagement\Services\CustomerProviderListFetcher;
use Modules\ProviderManagement\Services\ProviderCompletedServicesCounter;
use Modules\ProviderManagement\Services\ProviderSubscribedServicesCounter;
use Modules\ProviderManagement\Services\ProviderPackageEligibilityResolver;
use Modules\ReviewModule\Entities\Review;
use Modules\ServiceManagement\Entities\FavoriteService;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ServiceManagement\Services\CustomerServiceResponseEnricher;

class ProviderController extends Controller
{
    private Provider $provider;
    private Category $category;
    private SubscribedService $subscribed_service;
    private Booking $booking;

    private Service $service;
    private Variation $variation;
    private FavoriteProvider $favoriteProvider;
    private FavoriteService $favoriteService;
    private Review $review;
    private bool $is_customer_logged_in;
    private string|int|null $customer_user_id;

    public function __construct(Provider $provider, Review $review, Category $category, SubscribedService $subscribed_service, Booking $booking, Service $service, Variation $variation, FavoriteProvider $favoriteProvider, FavoriteService $favoriteService, Request $request)
    {
        $this->provider = $provider;
        $this->category = $category;
        $this->subscribed_service = $subscribed_service;
        $this->booking = $booking;
        $this->service = $service;
        $this->variation = $variation;
        $this->favoriteProvider = $favoriteProvider;
        $this->favoriteService = $favoriteService;
        $this->review = $review;

        $user = api_user();
        $this->is_customer_logged_in = (bool) $user;
        $this->customer_user_id = $this->is_customer_logged_in ? $user->id : $request['guest_id'];
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function getProviderList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'sort_by' => 'in:asc,desc,default,popular',
            'service_availability' => 'in:0,1',
            'category_ids' => 'array',
            'category_ids.*' => 'uuid',
            'rating' => '',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $providers = CustomerProviderPayloadSlimmer::slimPaginator(
            app(CustomerProviderListFetcher::class)->paginate($request, $this->customer_user_id)
        );

        return response()->json(response_formatter(DEFAULT_200, $providers), 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function getProviderDetails(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $detailsService = app(CustomerProviderDetailsService::class);
        $provider = $detailsService->findProvider($request['id']);

        if (!isset($provider)) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $provider = $detailsService->enrichProvider($provider, $this->customer_user_id);
        $subCategories = $detailsService->getSubCategoriesWithServices($provider->id, $this->customer_user_id);
        $review = $detailsService->getReviews($provider->id, (int) $request['limit'], (int) $request['offset']);
        $ratingInfo = $detailsService->getRatingInfo($provider->id);
        $showcaseItems = $detailsService->getShowcaseItems($provider->id);

        return response()->json(response_formatter(DEFAULT_200, [
            'provider' => $provider,
            'sub_categories' => $subCategories,
            'reviews' => $review,
            'rating' => $ratingInfo,
            'showcase_items' => $showcaseItems,
        ]), 200);
    }

    public function getProviderDetailsSummary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $providerId = (string) $request['id'];
        $cacheKey = CustomerProviderDetailsCache::summaryCacheKey($request, $providerId, $this->customer_user_id);

        $payload = CustomerProviderDetailsCache::rememberSummary($cacheKey, function () use ($providerId) {
            $detailsService = app(CustomerProviderDetailsService::class);
            $provider = $detailsService->findProviderForSummary($providerId);

            if (! isset($provider)) {
                return null;
            }

            $provider = $detailsService->enrichProviderForSummary($provider, $this->customer_user_id);

            return [
                'provider' => CustomerProviderPayloadSlimmer::slimSummaryItem($provider->toArray()),
                'rating' => $detailsService->getRatingInfo($provider->id),
            ];
        });

        if ($payload === null) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $payload = $this->overlayProviderStatsOnSummary($payload, $providerId);

        return response()->json(response_formatter(DEFAULT_200, $payload), 200);
    }

    /**
     * @param  array{provider?: array<string, mixed>, rating?: array<string, mixed>}  $payload
     * @return array{provider?: array<string, mixed>, rating?: array<string, mixed>}
     */
    private function overlayProviderStatsOnSummary(array $payload, string $providerId): array
    {
        if (! isset($payload['provider']) || ! is_array($payload['provider'])) {
            return $payload;
        }

        $payload['provider']['total_service_served'] = app(ProviderCompletedServicesCounter::class)
            ->countForProvider($providerId);
        $payload['provider']['subscribed_services_count'] = app(ProviderSubscribedServicesCounter::class)
            ->countForProvider($providerId);

        return $payload;
    }

    public function getProviderDetailsServices(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|uuid',
            'limit_per_category' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $detailsService = app(CustomerProviderDetailsService::class);
        $providerId = (string) $request['id'];

        if (! $detailsService->providerExists($providerId)) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $limitPerCategory = $request->filled('limit_per_category')
            ? (int) $request->query('limit_per_category')
            : null;

        $cacheKey = CustomerProviderDetailsCache::servicesCacheKey(
            $request,
            $providerId,
            $this->customer_user_id,
            $limitPerCategory
        );

        $payload = CustomerProviderDetailsCache::rememberServices($cacheKey, function () use ($detailsService, $providerId, $limitPerCategory) {
            $subCategories = $detailsService->getSubCategoriesWithServices(
                $providerId,
                $this->customer_user_id,
                $limitPerCategory
            );

            return [
                'sub_categories' => CustomerProviderDetailsPayloadSlimmer::slimSubCategories($subCategories),
            ];
        });

        return response()->json(response_formatter(DEFAULT_200, $payload), 200);
    }

    public function getProviderDetailsReviews(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|uuid',
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $detailsService = app(CustomerProviderDetailsService::class);

        if (! $detailsService->providerExists($request['id'])) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $providerId = (string) $request['id'];
        $limit = (int) $request['limit'];
        $offset = (int) $request['offset'];

        $cacheKey = CustomerProviderDetailsCache::reviewsCacheKey($request, $providerId, $limit, $offset);

        $payload = CustomerProviderDetailsCache::rememberReviews($cacheKey, function () use ($detailsService, $providerId, $limit, $offset) {
            $reviews = $detailsService->getReviews($providerId, $limit, $offset);
            $rating = $detailsService->getRatingInfo($providerId);

            return CustomerProviderDetailsPayloadSlimmer::slimReviewsPayload(
                $reviews->toArray(),
                $rating
            );
        });

        if ($payload === null) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        return response()->json(response_formatter(DEFAULT_200, $payload), 200);
    }

    public function getProviderDetailsShowcase(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $detailsService = app(CustomerProviderDetailsService::class);
        $providerId = (string) $request['id'];

        if (! $detailsService->providerExists($providerId)) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        $cacheKey = CustomerProviderDetailsCache::showcaseCacheKey($request, $providerId);

        $payload = CustomerProviderDetailsCache::rememberShowcase($cacheKey, function () use ($detailsService, $providerId) {
            $items = $detailsService->getShowcaseItems($providerId);

            return [
                'showcase_items' => CustomerProviderDetailsPayloadSlimmer::slimShowcaseItems(
                    collect($items)->map(fn ($item) => is_array($item) ? $item : $item->toArray())->all()
                ),
            ];
        });

        return response()->json(response_formatter(DEFAULT_200, $payload), 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function getProviderListBySubCategory(Request $request): JsonResponse
    {
        $providers = $this->provider->with(['owner'])
            ->coveringZoneOrDescendants(Config::get('zone_id'))
            ->whereHas('subscribed_services', function ($query) use ($request) {
                $query->where('sub_category_id', $request['sub_category_id'])
                    ->where('is_subscribed', 1);
            })
            ->where('app_availability', 1)
            ->where('service_availability', 1)
            ->where('is_suspended', 0)
            ->where('is_active', 1)
            ->get();

        $eligibleProviders = [];

        foreach ($providers as $provider) {
            if (!nextBookingEligibility($provider->id)) {
                continue;
            }

            $limitStatus = provider_warning_amount_calculate_for_provider($provider);
            $provider['cash_limit_status'] = $limitStatus === false ? 'available' : $limitStatus;

            $provider['is_favorite'] = $this->favoriteProvider
                ->where('customer_user_id', $this->customer_user_id)
                ->where('provider_id', $provider->id)
                ->exists() ? 1 : 0;

            $providerId = $provider->id;
            $timeSchedule = provider_config('time_schedule', 'service_schedule', $providerId)->live_values ?? '';
            $weekEnds = provider_config('weekends', 'service_schedule', $providerId)->live_values ?? '';
            $provider->weekends = json_decode($weekEnds) ?? [];
            $provider->time_schedule = json_decode($timeSchedule);
            $provider->nextBookingEligibility = nextBookingEligibility($providerId);
            $provider->scheduleBookingEligibility = scheduleBookingEligibility($providerId);

            $eligibleProviders[] = $provider;
        }

        if ($request->filled(['origin_latitude', 'origin_longitude'])) {
            $originLat = (float) $request['origin_latitude'];
            $originLng = (float) $request['origin_longitude'];

            $destinations = [];
            $providerDestinationIndex = [];

            foreach ($eligibleProviders as $providerIndex => $provider) {
                $coordinates = $provider->coordinates;
                if (empty($coordinates['latitude']) || empty($coordinates['longitude'])) {
                    continue;
                }

                $destinations[] = [
                    'latitude' => (float) $coordinates['latitude'],
                    'longitude' => (float) $coordinates['longitude'],
                ];
                $providerDestinationIndex[$providerIndex] = count($destinations) - 1;
            }

            if (!empty($destinations)) {
                $distancesKm = compute_google_route_matrix_distances_km($originLat, $originLng, $destinations);

                foreach ($providerDestinationIndex as $providerIndex => $destinationIndex) {
                    $distanceKm = $distancesKm[$destinationIndex] ?? null;
                    $eligibleProviders[$providerIndex]['distance'] = $distanceKm;
                }
            }
        }

        return response()->json(response_formatter(DEFAULT_200, $eligibleProviders), 200);
    }

    public function getAvailableProvider(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'sort_by' => 'in:asc,desc',
            'booking_id' => 'required|uuid',
            'rating' => '',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $booking = $this->booking->where('id', $request->booking_id)->first();

        $providers = $this->provider
            ->coveringLeafZone($booking->zone_id)
            ->ofStatus(1)
            ->where('app_availability', 1)
            ->when(isset($booking->sub_category_id), function ($query) use ($request, $booking) {
                $query->whereHas('subscribed_services', function ($query) use ($request, $booking) {
                    $query->where('sub_category_id', $booking->sub_category_id)->where('is_subscribed', 1);
                });
            })
            ->when($request->has('rating'), function ($query) use ($request) {
                $query->where('avg_rating', '>=', $request['rating']);
            })
            ->when($request->has('sort_by'), function ($query) use ($request) {
                $query->orderBy('company_name', $request['sort_by']);
            })
            ->when(!$request->has('sort_by'), function ($query) use ($request) {
                $query->latest();
            })
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        foreach ($providers as $provider) {
            $provider['is_favorite'] = $this->favoriteProvider->where('customer_user_id', $this->customer_user_id)->where('provider_id', $provider->id)->exists() ? 1 : 0;
        }


        return response()->json(response_formatter(DEFAULT_200, $providers), 200);
    }

    public function getAvailableService(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'service_ids' => 'array',
            'service_ids.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $serivces = $this->service
            ->where('is_active', 1)
            ->whereIn('id', $request['service_ids'])
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $serivces), 200);
    }

    public function rebookingInformation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'booking_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $booking = $this->booking->with('detail')->where('id', $request->booking_id)->first();
        $bookingServices = $booking->detail ?? [];

        //provider ...
        $provider = $this->provider
            ->where('id', $booking?->provider?->id)
            ->ofStatus(1)
            ->where('app_availability', 1)
            ->whereHas('owner', function ($query) {
                $query->ofStatus(1);
            })
            ->where('zone_id', $request->header('zoneid'))
            ->when(business_config('suspend_on_exceed_cash_limit_provider', 'provider_config')->live_values, function ($query) {
                $query->where('is_suspended', 0);
            })
            ->whereHas('subscribed_services', function ($query) use ($request, $booking) {
                $query->where('sub_category_id', $booking->sub_category_id)->where('is_subscribed', 1);
            })
            ->first();

        //service ...
        $services = [];
        foreach ($bookingServices as $key => $service) {
            $serviceData = $this->service->with(['variations' => function ($query) use ($service, $booking, $request) {
                $query->where('variant_key', $service->variant_key)->where('zone_id', $request->header('zoneid'));
            }])->where('id', $service->service_id)->active()->first();

            $services[] = [
                'service_id' => $service->service_id,
                'service_name' => $service->service_name,
                'variant_key' => $service->variant_key,

                'service_unit_cost' => $serviceData?->variations?->first()?->price,
                'booking_service_unit_cost' => $service->service_cost,

                'is_available' => $serviceData?->variations?->first() ? 1 : 0,
                'is_price_changed' => ($serviceData?->variations?->first()?->price == $service->service_cost) || $serviceData?->variations?->first()?->price == null ? 0 : 1,
            ];
        }

        $isServiceInfoUnchanged = count(array_filter($services, function ($service) {
            return $service['is_price_changed'] === 1;
        })) === 0 ? 1 : 0;

        $data = [
            'is_provider_available' => $provider ? 1 : 0,
            'is_service_info_unchanged' => $isServiceInfoUnchanged,
            'services' => $services,
        ];

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        return response()->json(response_formatter(DEFAULT_200, $data), 200);
    }

}
