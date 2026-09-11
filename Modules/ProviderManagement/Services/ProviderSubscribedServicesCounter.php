<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\ServiceManagement\Entities\Service;

/**
 * Counts active catalog services across a provider's subscribed sub-categories.
 */
class ProviderSubscribedServicesCounter
{
    public function countForProvider(string $providerId): int
    {
        $counts = $this->countsForProviders([$providerId]);

        return $counts[$providerId] ?? 0;
    }

    /**
     * @param  list<string>  $providerIds
     * @return array<string, int>
     */
    public function countsForProviders(array $providerIds): array
    {
        $providerIds = array_values(array_unique(array_filter(array_map('strval', $providerIds))));
        if ($providerIds === []) {
            return [];
        }

        $subscriptions = DB::table('subscribed_services as ss')
            ->join('categories as cat', 'cat.id', '=', 'ss.category_id')
            ->join('categories as sub', 'sub.id', '=', 'ss.sub_category_id')
            ->join('categories as parent', 'parent.id', '=', 'sub.parent_id')
            ->whereIn('ss.provider_id', $providerIds)
            ->where('ss.is_subscribed', 1)
            ->where('cat.is_active', 1)
            ->where('cat.is_visible_in_customer_app', 1)
            ->where('sub.is_active', 1)
            ->where('sub.is_visible_in_customer_app', 1)
            ->where('parent.is_active', 1)
            ->where('parent.is_visible_in_customer_app', 1)
            ->select('ss.provider_id', 'ss.sub_category_id')
            ->get();

        $result = array_fill_keys($providerIds, 0);
        if ($subscriptions->isEmpty()) {
            return $result;
        }

        $subCategoryIds = $subscriptions
            ->pluck('sub_category_id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $servicesQuery = Service::query()
            ->withoutGlobalScope('zone_wise_data')
            ->whereIn('sub_category_id', $subCategoryIds)
            ->where('is_active', 1)
            ->where('is_visible_in_customer_app', 1)
            ->whereHas('subCategory', function ($query) {
                $query->withoutGlobalScopes()
                    ->where('is_active', 1)
                    ->where('is_visible_in_customer_app', 1);
            });

        $zoneId = Config::get('zone_id');
        if (is_string($zoneId) && $zoneId !== '') {
            $servicesQuery->where(function ($query) use ($zoneId) {
                $query
                    ->whereHas('category.zones', function ($zoneQuery) use ($zoneId) {
                        $zoneQuery->where('zone_id', $zoneId);
                    })
                    ->orWhereHas('subCategory.parent.zones', function ($zoneQuery) use ($zoneId) {
                        $zoneQuery->where('zone_id', $zoneId);
                    });
            });
        }

        $servicesBySubCategory = $servicesQuery
            ->select('id', 'sub_category_id')
            ->get()
            ->groupBy(fn ($service) => (string) $service->sub_category_id);

        $providerSubCategories = [];
        foreach ($subscriptions as $subscription) {
            $providerId = (string) $subscription->provider_id;
            $providerSubCategories[$providerId][] = (string) $subscription->sub_category_id;
        }

        foreach ($providerSubCategories as $providerId => $subCategoryIdsForProvider) {
            $count = 0;
            foreach (array_unique($subCategoryIdsForProvider) as $subCategoryId) {
                $count += $servicesBySubCategory->get($subCategoryId)?->count() ?? 0;
            }
            $result[$providerId] = $count;
        }

        return $result;
    }

    /**
     * @param  iterable<int, \Modules\ProviderManagement\Entities\Provider>  $providers
     */
    public function attachToProviders(iterable $providers): void
    {
        $providerIds = [];
        foreach ($providers as $provider) {
            $providerIds[] = (string) $provider->id;
        }

        $counts = $this->countsForProviders($providerIds);

        foreach ($providers as $provider) {
            $provider->subscribed_services_count = $counts[(string) $provider->id] ?? 0;
        }
    }
}
