<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Slim provider payloads for list cards, home bundle, and profile summary.
 */
class CustomerProviderPayloadSlimmer
{
    /** @var list<string> */
    public const LIST_ITEM_KEYS = [
        'id',
        'company_name',
        'company_address',
        'logo',
        'logo_full_path',
        'avg_rating',
        'rating_count',
        'is_favorite',
        'coordinates',
        'distance',
        'service_availability',
        'is_active',
    ];

    /** @var list<string> */
    public const SUMMARY_ITEM_KEYS = [
        'id',
        'company_name',
        'company_address',
        'logo',
        'logo_full_path',
        'cover_image',
        'cover_image_full_path',
        'avg_rating',
        'rating_count',
        'is_favorite',
        'is_active',
        'service_availability',
        'time_schedule',
        'weekends',
        'nextBookingEligibility',
        'scheduleBookingEligibility',
        'cash_limit_status',
        'total_service_served',
        'subscribed_services_count',
        'coordinates',
        'service_location',
    ];

    public static function slimPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn ($provider) => self::slimListItem(self::normalizeItem($provider))
            )
        );

        return $paginator;
    }

    /**
     * @param  array<string, mixed>  $list
     * @return array<string, mixed>
     */
    public static function slimList(array $list): array
    {
        if (! isset($list['data']) || ! is_array($list['data'])) {
            return $list;
        }

        $list['data'] = array_map(
            fn ($item) => is_array($item) ? self::slimListItem($item) : $item,
            $list['data'],
        );

        return $list;
    }

    /**
     * @param  array<string, mixed>  $provider
     * @return array<string, mixed>
     */
    public static function slimListItem(array $provider): array
    {
        return self::pickKeys($provider, self::LIST_ITEM_KEYS);
    }

    /**
     * @param  array<string, mixed>  $provider
     * @return array<string, mixed>
     */
    public static function slimSummaryItem(array $provider): array
    {
        return self::pickKeys($provider, self::SUMMARY_ITEM_KEYS);
    }

    /**
     * @param  array<string, mixed>  $provider
     * @return array<string, mixed>
     */
    public static function slimAdvertisementProvider(array $provider): array
    {
        $subscribedServices = [];
        foreach ($provider['subscribed_services'] ?? [] as $service) {
            if (! is_array($service)) {
                continue;
            }

            $subCategory = $service['sub_category'] ?? null;
            if (! is_array($subCategory)) {
                continue;
            }

            $name = $subCategory['name'] ?? null;
            if ($name === null || $name === '') {
                continue;
            }

            $subscribedServices[] = [
                'sub_category' => ['name' => $name],
            ];
        }

        return array_filter([
            'id' => $provider['id'] ?? null,
            'avg_rating' => $provider['avg_rating'] ?? null,
            'rating_count' => $provider['rating_count'] ?? null,
            'is_favorite' => $provider['is_favorite'] ?? 0,
            'subscribed_services' => $subscribedServices,
        ], fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private static function pickKeys(array $item, array $keys): array
    {
        $slim = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $item)) {
                $slim[$key] = $item[$key];
            }
        }

        return $slim;
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeItem(mixed $item): array
    {
        if (is_array($item)) {
            return $item;
        }

        if (is_object($item) && method_exists($item, 'toArray')) {
            return $item->toArray();
        }

        return (array) $item;
    }
}
