<?php

namespace Modules\PromotionManagement\Http\Controllers\Api\V1\Customer;


use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Modules\PromotionManagement\Entities\Advertisement;
use Modules\ProviderManagement\Entities\FavoriteProvider;
use Modules\ProviderManagement\Entities\ProviderShowcaseItem;

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
                'provider.subscribed_services.sub_category' => function ($query) {
                    $query->withoutGlobalScopes();
                },
            ])
            ->orderByRaw('ISNULL(priority), priority')
            ->ofRunning()
            ->whereHas('provider', function ($query) use ($zoneId) {
                $query->coveringLeafZone($zoneId);
            })
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        $filteredAdvertisement = $advertisements->getCollection()->filter(function ($advertisement) {
            return advertisementsEligibility($advertisement->provider_id);
        });

        $advertisements->setCollection($filteredAdvertisement->values());

        $isCustomerLoggedIn = (bool)auth('api')->user();
        $customerUserId = $isCustomerLoggedIn ? auth('api')->user()->id : $request['guest_id'];

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
                $advertisement->showcase_items = ProviderShowcaseItem::where('provider_id', $advertisement->provider_id)
                    ->where('is_active', 1)
                    ->where('is_approved', ProviderShowcaseItem::STATUS_APPROVED)
                    ->orderByDesc('sort_order')
                    ->orderByDesc('created_at')
                    ->limit(4)
                    ->get();
            } else {
                $advertisement->showcase_items = [];
            }

            // Check if the provider is favorite
            $advertisement->provider->is_favorite = $this->favoriteProvider
                ->where('customer_user_id', $customerUserId)
                ->where('provider_id', $advertisement->provider->id)
                ->exists() ? 1 : 0;

            unset($advertisement->attachments, $advertisement->attachment, $advertisement->review, $advertisement->rating, $advertisement->showcase);
        }

        return response()->json(response_formatter(DEFAULT_200, $advertisements), 200);
    }

}
