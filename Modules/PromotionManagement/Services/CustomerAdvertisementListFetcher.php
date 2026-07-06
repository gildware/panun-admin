<?php

namespace Modules\PromotionManagement\Services;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

        $advertisements = $this->advertisement->with([
            'attachments',
            'attachment',
            'review',
            'rating',
            'showcase',
            'provider.owner',
        ])
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

        $providersById = $providerIds === []
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
                $enrichedProvider = $providersById->get($advertisement->provider_id);
                if ($enrichedProvider) {
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
}
