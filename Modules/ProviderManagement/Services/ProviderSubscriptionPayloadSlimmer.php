<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Slim subscribed sub-category rows for provider subscription list APIs.
 */
class ProviderSubscriptionPayloadSlimmer
{
    /** @var list<string> */
    public const ITEM_KEYS = [
        'id',
        'provider_id',
        'category_id',
        'sub_category_id',
        'is_subscribed',
        'created_at',
        'updated_at',
        'services_count',
        'ongoing_booking_count',
        'completed_booking_count',
        'canceled_booking_count',
        'subscription_pending',
        'pending_subscription_action',
        'category',
        'sub_category',
    ];

    /** @var list<string> */
    public const CATEGORY_KEYS = [
        'id',
        'name',
    ];

    /** @var list<string> */
    public const SUB_CATEGORY_KEYS = [
        'id',
        'parent_id',
        'name',
        'image',
        'image_full_path',
        'subscription_pending',
        'pending_subscription_action',
        'services',
    ];

    /** @var list<string> */
    public const SERVICE_COUNT_KEYS = [
        'id',
        'is_active',
    ];

    /**
     * @return list<string>
     */
    public static function subCategoryEagerRelations(): array
    {
        return [
            'sub_category' => function ($query) {
                $query->select('id', 'parent_id', 'name', 'image', 'is_active')
                    ->with(['services' => function ($serviceQuery) {
                        $serviceQuery->select('id', 'sub_category_id', 'is_active');
                    }]);
            },
        ];
    }

    public static function slimPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn ($item) => self::slimItem(is_array($item) ? $item : $item->toArray())
            )
        );

        return $paginator;
    }

    /**
     * @param  list<mixed>  $items
     * @return list<array<string, mixed>>
     */
    public static function slimList(array $items): array
    {
        return array_values(array_map(function ($item) {
            $row = is_array($item) ? $item : $item->toArray();

            return self::slimItem($row);
        }, $items));
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

            if ($key === 'category' && is_array($item[$key])) {
                $slim[$key] = self::pickKeys($item[$key], self::CATEGORY_KEYS);
                continue;
            }

            if ($key === 'sub_category' && is_array($item[$key])) {
                $slim[$key] = self::slimSubCategory($item[$key]);
                continue;
            }

            $slim[$key] = $item[$key];
        }

        return $slim;
    }

    /**
     * @param  array<string, mixed>  $subCategory
     * @return array<string, mixed>
     */
    public static function slimSubCategory(array $subCategory): array
    {
        $slim = self::pickKeys($subCategory, self::SUB_CATEGORY_KEYS);

        if (! isset($slim['services']) || ! is_array($slim['services'])) {
            return $slim;
        }

        $slim['services'] = array_map(
            fn ($service) => self::pickKeys(
                is_array($service) ? $service : (array) $service,
                self::SERVICE_COUNT_KEYS
            ),
            $slim['services']
        );

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
