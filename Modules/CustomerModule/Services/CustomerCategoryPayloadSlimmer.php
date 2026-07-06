<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Slim category payloads for home bundle grids and featured sections.
 */
class CustomerCategoryPayloadSlimmer
{
    /** @var list<string> */
    public const GRID_ITEM_KEYS = [
        'id',
        'slug',
        'name',
        'image',
        'image_full_path',
        'services_count',
    ];

    /** @var list<string> */
    public const FEATURED_ITEM_KEYS = [
        'id',
        'slug',
        'name',
        'image',
        'image_full_path',
    ];

    /** @var list<string> */
    public const SUB_CATEGORY_ITEM_KEYS = [
        'id',
        'slug',
        'parent_id',
        'name',
        'image',
        'image_full_path',
        'description',
        'is_active',
        'services_count',
    ];

    /**
     * @param  array<string, mixed>  $list
     * @return array<string, mixed>
     */
    public static function slimGridList(array $list): array
    {
        if (! isset($list['data']) || ! is_array($list['data'])) {
            return $list;
        }

        $list['data'] = array_map(
            fn ($item) => is_array($item) ? self::slimGridItem($item) : $item,
            $list['data'],
        );

        return $list;
    }

    /**
     * @param  array<string, mixed>  $list
     * @return array<string, mixed>
     */
    public static function slimFeaturedList(array $list): array
    {
        if (! isset($list['data']) || ! is_array($list['data'])) {
            return $list;
        }

        $list['data'] = array_map(
            fn ($item) => is_array($item) ? self::slimFeaturedItem($item) : $item,
            $list['data'],
        );

        return $list;
    }

    /**
     * @param  array<string, mixed>  $category
     * @return array<string, mixed>
     */
    public static function slimGridItem(array $category): array
    {
        return self::pickKeys($category, self::GRID_ITEM_KEYS);
    }

    public static function slimPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn ($item) => self::slimSubCategoryItem(is_array($item) ? $item : $item->toArray())
            )
        );

        return $paginator;
    }

    /**
     * @param  array<string, mixed>  $category
     * @return array<string, mixed>
     */
    public static function slimSubCategoryItem(array $category): array
    {
        return self::pickKeys($category, self::SUB_CATEGORY_ITEM_KEYS);
    }

    /**
     * @param  array<string, mixed>  $category
     * @return array<string, mixed>
     */
    public static function slimFeaturedItem(array $category): array
    {
        $slim = self::pickKeys($category, self::FEATURED_ITEM_KEYS);

        $services = $category['services_by_category'] ?? $category['services'] ?? null;
        if (is_array($services)) {
            $slim['services_by_category'] = array_map(
                fn ($service) => is_array($service)
                    ? CustomerServicePayloadSlimmer::slimItem($service)
                    : $service,
                $services,
            );
        }

        return $slim;
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
}
