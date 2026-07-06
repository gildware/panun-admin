<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Slim service rows for provider mobile list APIs.
 */
class ProviderServicePayloadSlimmer
{
    /** @var list<string> */
    public const ITEM_KEYS = [
        'id',
        'name',
        'thumbnail',
        'thumbnail_full_path',
        'variations',
        'service_discount',
    ];

    /** @var list<string> */
    public const VARIATION_KEYS = [
        'id',
        'variant_key',
        'price',
    ];

    /**
     * @return list<string>
     */
    public static function listEagerRelations(): array
    {
        return [
            'variations:id,service_id,variant_key,price',
            'service_discount',
        ];
    }

    public static function slimPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn ($service) => self::slimItem(is_array($service) ? $service : $service->toArray())
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

            if ($key === 'variations' && is_array($item[$key])) {
                $slim[$key] = array_map(
                    fn ($variation) => self::pickKeys(
                        is_array($variation) ? $variation : (array) $variation,
                        self::VARIATION_KEYS
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
