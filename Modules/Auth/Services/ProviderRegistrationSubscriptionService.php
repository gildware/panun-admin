<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Str;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\SubscribedService;

class ProviderRegistrationSubscriptionService
{
    public function __construct(
        private ProviderRegistrationCatalogService $catalog,
        private SubscribedService $subscribedService
    ) {}

    /**
     * @param  array<int, string>  $leafZoneIds
     * @param  array<int, mixed>  $requestedSubCategoryIds
     */
    public function syncForProvider(Provider $provider, array $leafZoneIds, array $requestedSubCategoryIds): void
    {
        $allSubs = $this->catalog->allSubCategoriesForZonesQuery($leafZoneIds)->get();
        $allowedIds = $allSubs->pluck('id')->all();

        $requested = [];
        foreach ($requestedSubCategoryIds as $rid) {
            if (is_string($rid) && Str::isUuid($rid) && in_array($rid, $allowedIds, true)) {
                $requested[] = $rid;
            }
        }
        $requested = array_values(array_unique($requested));

        foreach ($allSubs as $subCategory) {
            $this->subscribedService->create([
                'provider_id' => $provider->id,
                'category_id' => $subCategory->parent_id,
                'sub_category_id' => $subCategory->id,
                'is_subscribed' => in_array($subCategory->id, $requested, true) ? 1 : 0,
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    public function requestedIdsFromMixedInput(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $raw)));
    }
}
