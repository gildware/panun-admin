<?php

namespace Modules\PromotionManagement\Services;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Modules\PromotionManagement\Entities\Advertisement;
use Modules\ProviderManagement\Entities\FavoriteProvider;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderShowcaseItem;
use Modules\ProviderManagement\Services\ZoneProviderEligibilityService;

class CustomerAdvertisementListFetcher
{
    public function __construct(
        private Advertisement $advertisement,
        private Provider $provider,
        private FavoriteProvider $favoriteProvider,
        private ZoneProviderEligibilityService $zoneEligibility,
    ) {}

    public function paginate(Request $request, mixed $customerUserId): LengthAwarePaginator
    {
        $eligibleProviderIds = $this->zoneEligibility->advertisementEligibleIds();

        if ($eligibleProviderIds === []) {
            return new LengthAwarePaginator(
                [],
                0,
                (int) $request['limit'],
                (int) $request['offset'],
                ['path' => '']
            );
        }

        $bundleMode = (bool) Config::get('customer_home_bundle_active');

        $relations = [
            'attachments',
            'attachment',
            'review',
            'rating',
            'showcase',
        ];

        if ($bundleMode) {
            $relations['provider'] = function ($query) {
                $query->with(['subscribed_services.sub_category' => function ($subQuery) {
                    $subQuery->withoutGlobalScopes();
                }]);
            };
        } else {
            $relations[] = 'provider.owner';
        }

        $advertisements = $this->advertisement->with($relations)
            ->orderByRaw('ISNULL(priority), priority')
            ->ofRunning()
            ->whereIn('provider_id', $eligibleProviderIds)
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        $this->transformCollection($advertisements, $customerUserId);

        return $advertisements;
    }

    private function transformCollection(LengthAwarePaginator $advertisements, mixed $customerUserId): void
    {
        $collection = $advertisements->getCollection();
        $providerIds = $collection->pluck('provider_id')->filter()->unique()->values()->all();
        $bundleMode = (bool) Config::get('customer_home_bundle_active');

        $providersById = ($providerIds === [] || $bundleMode)
            ? collect()
            : $this->provider
                ->with(['subscribed_services.sub_category' => function ($query) {
                    $query->withoutGlobalScopes();
                }])
                ->whereIn('id', $providerIds)
                ->get()
                ->keyBy('id');

        $favoriteProviderIds = [];
        if ($customerUserId && $providerIds !== []) {
            $favoriteProviderIds = $this->favoriteProvider
                ->where('customer_user_id', $customerUserId)
                ->whereIn('provider_id', $providerIds)
                ->pluck('provider_id')
                ->mapWithKeys(fn ($id) => [(string) $id => true])
                ->all();
        }

        $showcaseProviderIds = $collection
            ->filter(fn ($advertisement) => (int) ($advertisement?->showcase?->value ?? 0) === 1)
            ->pluck('provider_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $showcaseItemsByProvider = $showcaseProviderIds === []
            ? collect()
            : ProviderShowcaseItem::query()
                ->whereIn('provider_id', $showcaseProviderIds)
                ->where('is_active', 1)
                ->where('is_approved', ProviderShowcaseItem::STATUS_APPROVED)
                ->orderByDesc('sort_order')
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('provider_id')
                ->map(fn ($items) => $items->take(4)->values());

        foreach ($collection as $advertisement) {
            foreach ($advertisement->attachments as $attachment) {
                if ($attachment->type == 'provider_cover_image') {
                    $advertisement->provider_cover_image_full_path = $attachment->provider_cover_image_full_path;
                }
                if ($attachment->type == 'provider_profile_image') {
                    $advertisement->provider_profile_image_full_path = $attachment->provider_profile_image_full_path;
                }
            }

            $advertisement->promotional_video_full_path = $advertisement?->attachment?->promotional_video_full_path;
            $advertisement->provider_review = $advertisement?->review?->value;
            $advertisement->provider_rating = $advertisement?->rating?->value;
            $advertisement->provider_showcase = $advertisement?->showcase?->value;

            if ((int) $advertisement->provider_showcase === 1) {
                $advertisement->showcase_items = $showcaseItemsByProvider
                    ->get($advertisement->provider_id, collect())
                    ->values()
                    ->all();
            } else {
                $advertisement->showcase_items = [];
            }

            if ($advertisement->provider) {
                if ($bundleMode) {
                    $advertisement->setRelation(
                        'provider',
                        $this->minimalProviderForBundle($advertisement->provider, $favoriteProviderIds)
                    );
                } elseif ($enrichedProvider = $providersById->get($advertisement->provider_id)) {
                    $enrichedProvider->is_favorite = isset($favoriteProviderIds[(string) $enrichedProvider->id]) ? 1 : 0;
                    $advertisement->setRelation('provider', $enrichedProvider);
                } else {
                    $advertisement->provider->is_favorite = isset($favoriteProviderIds[(string) $advertisement->provider->id]) ? 1 : 0;
                }
            }

            unset($advertisement->attachments, $advertisement->attachment, $advertisement->review, $advertisement->rating, $advertisement->showcase);
        }

        $advertisements->setCollection($collection->values());
    }

    /**
     * @param  array<string, true>  $favoriteProviderIds
     */
    private function minimalProviderForBundle(Provider $provider, array $favoriteProviderIds): Provider
    {
        $provider->setRelation(
            'subscribed_services',
            $provider->relationLoaded('subscribed_services')
                ? $provider->subscribed_services->map(function ($service) {
                    $subCategory = $service->subCategory ?? $service->sub_category ?? null;

                    return (object) [
                        'sub_category' => $subCategory ? (object) ['name' => $subCategory->name ?? ''] : null,
                    ];
                })
                : collect()
        );
        $provider['is_favorite'] = isset($favoriteProviderIds[(string) $provider->id]) ? 1 : 0;
        $provider->unsetRelation('owner');

        return $provider;
    }
}
