<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Shared slim service shape for home bundle, provider services, and list endpoints.
 */
class CustomerServicePayloadSlimmer
{
    /** @var list<string> */
    public const ITEM_KEYS = [
        'id',
        'slug',
        'name',
        'short_description',
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
     * @param  array<string, mixed>  $list
     * @return array<string, mixed>
     */
    public static function slimList(array $list): array
    {
        if (! isset($list['data']) || ! is_array($list['data'])) {
            return $list;
        }

        $list['data'] = array_map(
            fn ($item) => is_array($item) ? self::slimItem($item) : $item,
            $list['data'],
        );

        return $list;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function slimItem(array $item): array
    {
        $slim = [];

        foreach (self::ITEM_KEYS as $key) {
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
     * @return array<string, mixed>|null
     */
    public static function slimVariationsAppFormat(mixed $format): ?array
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
    public static function slimServiceCategory(mixed $category): ?array
    {
        if (! is_array($category) || array_is_list($category)) {
            return null;
        }

        $slim = array_filter([
            'category_discount' => $category['category_discount'] ?? null,
            'campaign_discount' => $category['campaign_discount'] ?? null,
        ], fn ($value) => $value !== null);

        return $slim === [] ? null : $slim;
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public static function looksLikeServiceList(array $content): bool
    {
        if (! isset($content['data']) || ! is_array($content['data']) || $content['data'] === []) {
            return false;
        }

        $first = $content['data'][0];

        return is_array($first) && (isset($first['variations_app_format']) || isset($first['slug']));
    }

    /**
     * @return list<string>
     */
    public static function listEagerRelations(): array
    {
        return ['service_discount', 'category.category_discount'];
    }

    public static function slimPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(function ($service) {
                $item = is_array($service) ? $service : $service->toArray();

                return self::slimItem($item);
            })
        );

        return $paginator;
    }
}
