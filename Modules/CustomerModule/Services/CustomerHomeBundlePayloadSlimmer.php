<?php

namespace Modules\CustomerModule\Services;

/**
 * Shrinks cached home-bundle JSON for faster transfer and parsing on mobile.
 */
class CustomerHomeBundlePayloadSlimmer
{
    /** @var list<string> */
    private const SERVICE_LIST_KEYS = [
        'popular_services',
        'trending_services',
        'recommended_services',
        'recently_viewed_services',
    ];

    /** @var list<string> */
    private const SERVICE_ITEM_KEYS = [
        'id',
        'slug',
        'name',
        'thumbnail',
        'thumbnail_full_path',
        'is_favorite',
        'avg_rating',
        'rating_count',
        'variations_app_format',
        'service_discount',
        'campaign_discount',
        'category',
    ];

    /**
     * @param  array<string, mixed>  $bundle
     * @return array<string, mixed>
     */
    public static function slim(array $bundle): array
    {
        foreach (self::SERVICE_LIST_KEYS as $key) {
            if (isset($bundle[$key]) && is_array($bundle[$key])) {
                $bundle[$key] = self::slimServiceList($bundle[$key]);
            }
        }

        if (isset($bundle['advertisements']) && is_array($bundle['advertisements'])) {
            $bundle['advertisements'] = self::slimAdvertisementList($bundle['advertisements']);
        }

        if (isset($bundle['curated_sections']) && is_array($bundle['curated_sections'])) {
            foreach ($bundle['curated_sections'] as $sectionKey => $content) {
                if (! is_array($content)) {
                    continue;
                }

                if (self::looksLikeServiceList($content)) {
                    $bundle['curated_sections'][$sectionKey] = self::slimServiceList($content);
                }
            }
        }

        return $bundle;
    }

    /**
     * @param  array<string, mixed>  $list
     * @return array<string, mixed>
     */
    private static function slimServiceList(array $list): array
    {
        if (! isset($list['data']) || ! is_array($list['data'])) {
            return $list;
        }

        $list['data'] = array_map(
            fn ($item) => is_array($item) ? self::slimServiceItem($item) : $item,
            $list['data'],
        );

        return $list;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private static function slimServiceItem(array $item): array
    {
        $slim = [];

        foreach (self::SERVICE_ITEM_KEYS as $key) {
            if (! array_key_exists($key, $item)) {
                continue;
            }

            if ($key === 'variations_app_format') {
                $slim[$key] = self::slimVariationsAppFormat($item[$key]);
                continue;
            }

            if ($key === 'category') {
                $slim[$key] = self::slimServiceCategory($item[$key]);
                continue;
            }

            $slim[$key] = $item[$key];
        }

        return $slim;
    }

    /**
     * @param  array<string, mixed>  $list
     * @return array<string, mixed>
     */
    private static function slimAdvertisementList(array $list): array
    {
        if (! isset($list['data']) || ! is_array($list['data'])) {
            return $list;
        }

        $list['data'] = array_map(function ($item) {
            if (! is_array($item)) {
                return $item;
            }

            if (isset($item['provider']) && is_array($item['provider'])) {
                $item['provider'] = self::slimAdvertisementProvider($item['provider']);
            }

            return $item;
        }, $list['data']);

        return $list;
    }

    /**
     * @param  array<string, mixed>  $provider
     * @return array<string, mixed>
     */
    private static function slimAdvertisementProvider(array $provider): array
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
     * @return array<string, mixed>|null
     */
    private static function slimVariationsAppFormat(mixed $format): ?array
    {
        if (! is_array($format)) {
            return null;
        }

        $variations = [];
        foreach ($format['zone_wise_variations'] ?? [] as $variation) {
            if (! is_array($variation)) {
                continue;
            }

            $variations[] = array_filter([
                'variant_key' => $variation['variant_key'] ?? null,
                'variant_name' => $variation['variant_name'] ?? null,
                'price' => $variation['price'] ?? null,
            ], fn ($value) => $value !== null);
        }

        return [
            'zone_id' => $format['zone_id'] ?? null,
            'default_price' => $format['default_price'] ?? 0,
            'zone_wise_variations' => $variations,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function slimServiceCategory(mixed $category): ?array
    {
        if (! is_array($category)) {
            return null;
        }

        return array_filter([
            'category_discount' => $category['category_discount'] ?? null,
            'campaign_discount' => $category['campaign_discount'] ?? null,
        ], fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private static function looksLikeServiceList(array $content): bool
    {
        if (! isset($content['data']) || ! is_array($content['data']) || $content['data'] === []) {
            return false;
        }

        $first = $content['data'][0];

        return is_array($first) && (isset($first['variations_app_format']) || isset($first['slug']));
    }
}
