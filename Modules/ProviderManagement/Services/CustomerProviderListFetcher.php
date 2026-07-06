<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\ProviderManagement\Entities\FavoriteProvider;
use Modules\ProviderManagement\Entities\Provider;

class CustomerProviderListFetcher
{
    public function __construct(
        private Provider $provider,
        private FavoriteProvider $favoriteProvider,
        private ZoneProviderEligibilityService $zoneEligibility,
    ) {}

    public function paginate(Request $request, mixed $customerUserId): LengthAwarePaginator
    {
        $eligibleProviderIds = $this->zoneEligibility->bookingEligibleIds();

        if ($eligibleProviderIds === []) {
            return new LengthAwarePaginator(
                [],
                0,
                (int) $request['limit'],
                (int) $request['offset'],
                ['path' => '']
            );
        }

        $providersQuery = $this->provider->with(['owner'])
            ->whereIn('id', $eligibleProviderIds)
            ->ofStatus(1)
            ->where('app_availability', 1)
            ->withCount(['bookings as total_service_served' => function ($query) {
                $query->where('booking_status', 'completed');
            }, 'subscribed_services'])
            ->when($request->has('category_ids'), function ($query) use ($request) {
                $query->whereHas('subscribed_services', function ($query) use ($request) {
                    if ($request->has('category_ids')) {
                        $query->whereIn('category_id', $request['category_ids']);
                    }
                });
            })
            ->when($request->has('rating'), function ($query) use ($request) {
                $query->where('avg_rating', '>=', $request['rating']);
            })
            ->when($request->has('service_availability'), function ($query) use ($request) {
                $query->where('service_availability', $request['service_availability']);
            })
            ->when($request->has('sort_by'), function ($query) use ($request) {
                if ($request['sort_by'] == 'asc' || $request['sort_by'] == 'desc') {
                    $query->orderBy('company_name', $request['sort_by']);
                } elseif ($request['sort_by'] == 'popular') {
                    $query->orderBy('avg_rating', 'desc');
                }
            })
            ->when(! $request->has('sort_by') || $request['sort_by'] === 'default', function ($query) {
                $query->latest();
            })
            ->where('is_suspended', 0);

        $providers = $providersQuery
            ->paginate($request['limit'], ['*'], 'page', $request['offset'])
            ->withPath('');

        $this->attachFavoriteFlags($providers, $customerUserId);

        return $providers;
    }

    private function attachFavoriteFlags(LengthAwarePaginator $providers, mixed $customerUserId): void
    {
        $providerIds = $providers->getCollection()->pluck('id')->all();
        if ($providerIds === [] || $customerUserId === null || $customerUserId === '') {
            foreach ($providers as $provider) {
                $provider['is_favorite'] = 0;
            }

            return;
        }

        $favoriteProviderIds = $this->favoriteProvider
            ->where('customer_user_id', $customerUserId)
            ->whereIn('provider_id', $providerIds)
            ->pluck('provider_id')
            ->mapWithKeys(fn ($id) => [(string) $id => true])
            ->all();

        foreach ($providers as $provider) {
            $provider['is_favorite'] = isset($favoriteProviderIds[(string) $provider->id]) ? 1 : 0;
        }
    }
}
