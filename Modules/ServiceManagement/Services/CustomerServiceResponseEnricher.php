<?php

namespace Modules\ServiceManagement\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Modules\ServiceManagement\Entities\FavoriteService;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;
use Modules\ServiceManagement\Entities\Variation;

class CustomerServiceResponseEnricher
{
    /**
     * @param  iterable<Service>|LengthAwarePaginator  $services
     */
    public static function enrich(iterable $services, mixed $customerUserId, bool $includeTax = true): void
    {
        $collection = $services instanceof LengthAwarePaginator
            ? $services->getCollection()
            : ($services instanceof Collection ? $services : collect($services));

        if ($collection->isEmpty()) {
            return;
        }

        $serviceIds = $collection->pluck('id')->filter()->map(fn ($id) => (string) $id)->values()->all();
        $favoriteIds = self::favoriteServiceIds($customerUserId, $serviceIds);
        $variationFormats = Variation::variationsAppFormatForManyServices($serviceIds);

        foreach ($collection as $service) {
            if (! $service instanceof Service) {
                continue;
            }

            $serviceId = (string) $service->id;
            $service['is_favorite'] = isset($favoriteIds[$serviceId]) ? 1 : 0;

            if ($includeTax) {
                $service->loadMissing(['category', 'subCategory']);
                $service->setAttribute('tax', effective_service_tax_percentage($service));
                $service->setAttribute('tax_label', effective_service_tax_label($service));
            }

            $service['variations_app_format'] = $variationFormats[$serviceId]
                ?? Variation::variationsAppFormatForCustomer($serviceId);
        }
    }

    /**
     * @param  list<string>  $serviceIds
     * @return array<string, true>
     */
    private static function favoriteServiceIds(mixed $customerUserId, array $serviceIds): array
    {
        if ($customerUserId === null || $customerUserId === '' || $serviceIds === []) {
            return [];
        }

        return FavoriteService::query()
            ->where('customer_user_id', $customerUserId)
            ->whereIn('service_id', $serviceIds)
            ->pluck('service_id')
            ->mapWithKeys(fn ($id) => [(string) $id => true])
            ->all();
    }
}
