<?php

namespace Modules\ProviderManagement\Services;

/**
 * Slim customized post rows for provider dashboard and list previews.
 */
class ProviderCustomizedPostPayloadSlimmer
{
    /** @var list<string> */
    public const POST_KEYS = [
        'id',
        'service_description',
        'booking_schedule',
        'created_at',
        'distance',
        'service',
        'sub_category',
        'customer',
        'addition_instructions',
    ];

    /** @var list<string> */
    public const SERVICE_KEYS = [
        'id',
        'name',
        'thumbnail',
        'thumbnail_full_path',
    ];

    /** @var list<string> */
    public const SUB_CATEGORY_KEYS = [
        'id',
        'name',
    ];

    /** @var list<string> */
    public const CUSTOMER_KEYS = [
        'id',
        'first_name',
        'last_name',
        'profile_image',
        'profile_image_full_path',
    ];

    /** @var list<string> */
    public const INSTRUCTION_KEYS = [
        'id',
        'details',
    ];

    /**
     * @return list<string>
     */
    public static function listEagerRelations(): array
    {
        return [
            'addition_instructions:id,post_id,details',
            'service:id,name,thumbnail',
            'sub_category:id,name',
            'customer:id,first_name,last_name,profile_image',
            'service_address:id,lat,lon',
        ];
    }

    /**
     * @param  iterable<mixed>  $posts
     * @param  mixed  $provider
     * @return list<array<string, mixed>>
     */
    public static function enrichAndSlimList(iterable $posts, mixed $provider): array
    {
        $coordinates = $provider->coordinates ?? null;
        $slimmed = [];

        foreach ($posts as $post) {
            $row = is_array($post) ? $post : $post->toArray();

            if (! is_array($post) && $coordinates && $post->relationLoaded('service_address') && $post->service_address) {
                $originLat = $coordinates['latitude'] ?? null;
                $originLng = $coordinates['longitude'] ?? null;
                $destLat = $post->service_address?->lat;
                $destLng = $post->service_address?->lon;
                if (is_valid_lat_lng($originLat, $originLng) && is_valid_lat_lng($destLat, $destLng)) {
                    $distance = get_distance([$originLat, $originLng], [$destLat, $destLng]);
                    $row['distance'] = $distance ? number_format($distance, 2).' km' : null;
                }
            }

            $slimmed[] = self::slimItem($row);
        }

        return $slimmed;
    }

    /**
     * @param  array<string, mixed>  $post
     * @return array<string, mixed>
     */
    public static function slimItem(array $post): array
    {
        $slim = [];

        foreach (self::POST_KEYS as $key) {
            if (! array_key_exists($key, $post)) {
                continue;
            }

            if ($key === 'service' && is_array($post[$key])) {
                $slim[$key] = self::pickKeys($post[$key], self::SERVICE_KEYS);
                continue;
            }

            if ($key === 'sub_category' && is_array($post[$key])) {
                $slim[$key] = self::pickKeys($post[$key], self::SUB_CATEGORY_KEYS);
                continue;
            }

            if ($key === 'customer' && is_array($post[$key])) {
                $slim[$key] = self::pickKeys($post[$key], self::CUSTOMER_KEYS);
                continue;
            }

            if ($key === 'addition_instructions' && is_array($post[$key])) {
                $slim[$key] = array_map(
                    fn ($instruction) => self::pickKeys(
                        is_array($instruction) ? $instruction : (array) $instruction,
                        self::INSTRUCTION_KEYS
                    ),
                    $post[$key]
                );
                continue;
            }

            $slim[$key] = $post[$key];
        }

        if (! array_key_exists('addition_instructions', $slim)) {
            $slim['addition_instructions'] = [];
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
