<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Slim sub-category rows for provider category browse APIs.
 */
class ProviderSubCategoryPayloadSlimmer
{
    /** @var list<string> */
    public const ITEM_KEYS = [
        'id',
        'parent_id',
        'name',
        'image',
        'image_full_path',
        'description',
        'is_active',
        'is_subscribed',
        'services_count',
        'subscription_pending',
        'pending_subscription_action',
        'services',
    ];

    /** @var list<string> */
    public const SERVICE_COUNT_KEYS = [
        'id',
        'is_active',
    ];

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

            if ($key === 'services' && is_array($item[$key])) {
                $slim[$key] = array_map(
                    fn ($service) => self::pickKeys(
                        is_array($service) ? $service : (array) $service,
                        self::SERVICE_COUNT_KEYS
                    ),
                    $item[$key]
                );
                continue;
            }

            $slim[$key] = $item[$key];
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
