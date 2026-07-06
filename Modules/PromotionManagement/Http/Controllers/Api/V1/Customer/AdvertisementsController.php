<?php

namespace Modules\PromotionManagement\Http\Controllers\Api\V1\Customer;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Modules\PromotionManagement\Entities\Advertisement;
use Modules\ProviderManagement\Entities\FavoriteProvider;
use Modules\ProviderManagement\Entities\ProviderShowcaseItem;
use Modules\ProviderManagement\Services\ProviderPackageEligibilityResolver;

class AdvertisementsController extends Controller
{

    public function __construct(
        private Advertisement $advertisement,
        private FavoriteProvider $favoriteProvider,
    )
    {}

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function AdsList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $zoneId = Config::get('zone_id');

        $advertisements = $this->advertisement->with([
                'attachments',
                'attachment',
                'review',
                'rating',
                'showcase',
                'provider',
                'provider.owner',
            ])
            ->orderByRaw('ISNULL(priority), priority')
            ->ofRunning()
            ->whereHas('provider', function ($query) use ($zoneId) {
                $query->coveringLeafZone($zoneId);
            })
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        $collection = $advertisements->getCollection();
        $providerIds = $collection->pluck('provider_id')->filter()->unique()->map(fn ($id) => (string) $id)->values()->all();
        $eligibility = app(ProviderPackageEligibilityResolver::class)->preload($providerIds);

        $filteredAdvertisement = $collection->filter(
            fn ($advertisement) => $eligibility->canShowAdvertisement((string) $advertisement->provider_id)
        );

        $advertisements->setCollection($filteredAdvertisement->values());

        $isCustomerLoggedIn = (bool)auth('api')->user();
        $customerUserId = $isCustomerLoggedIn ? auth('api')->user()->id : $request['guest_id'];
        $providerIds = $advertisements->getCollection()->pluck('provider_id')->filter()->unique()->values()->all();

        $favoriteProviderIds = [];
        if ($customerUserId && $providerIds !== []) {
            $favoriteProviderIds = $this->favoriteProvider
                ->where('customer_user_id', $customerUserId)
                ->whereIn('provider_id', $providerIds)
                ->pluck('provider_id')
                ->mapWithKeys(fn ($id) => [(string) $id => true])
                ->all();
        }

        $showcaseProviderIds = $advertisements->getCollection()
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

        foreach($advertisements as $advertisement){
            foreach ($advertisement->attachments as $attachment){
                if($attachment->type == 'provider_cover_image') $advertisement->provider_cover_image_full_path = $attachment->provider_cover_image_full_path;
                if($attachment->type == 'provider_profile_image') $advertisement->provider_profile_image_full_path  = $attachment->provider_profile_image_full_path;
            }
            $advertisement->promotional_video_full_path = $advertisement?->attachment?->promotional_video_full_path;

            $advertisement->provider_review = $advertisement?->review?->value;
            $advertisement->provider_rating = $advertisement?->rating?->value;
            $advertisement->provider_showcase = $advertisement?->showcase?->value;

            if ((int) $advertisement->provider_showcase === 1) {
                $advertisement->showcase_items = $showcaseItemsByProvider->get($advertisement->provider_id, collect())->values()->all();
            } else {
                $advertisement->showcase_items = [];
            }

            if ($advertisement->provider) {
                $advertisement->provider->is_favorite = isset($favoriteProviderIds[(string) $advertisement->provider->id]) ? 1 : 0;
            }

            unset($advertisement->attachments, $advertisement->attachment, $advertisement->review, $advertisement->rating, $advertisement->showcase);
        }

        return response()->json(response_formatter(DEFAULT_200, $advertisements), 200);
    }

}
