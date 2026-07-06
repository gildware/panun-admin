<?php

namespace Modules\ProviderManagement\Services;

/**
 * Shrinks provider dashboard section payloads for faster transfer on mobile.
 */
class ProviderDashboardPayloadSlimmer
{
    /** @var list<string> */
    private const RECENT_BOOKING_KEYS = [
        'id',
        'readable_id',
        'created_at',
        'booking_status',
        'is_repeated',
        'detail',
    ];

    /** @var list<string> */
    private const BOOKING_DETAIL_KEYS = [
        'id',
        'service_id',
        'service',
    ];

    /** @var list<string> */
    private const SERVICE_KEYS = [
        'id',
        'name',
        'thumbnail',
        'thumbnail_full_path',
    ];

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    public static function slimSections(array $sections): array
    {
        return array_map(function ($section) {
            if (! is_array($section)) {
                return $section;
            }

            if (isset($section['recent_bookings']) && is_array($section['recent_bookings'])) {
                $section['recent_bookings'] = self::slimRecentBookings($section['recent_bookings']);
            }

            if (isset($section['subscriptions']) && is_array($section['subscriptions'])) {
                $section['subscriptions'] = ProviderSubscriptionPayloadSlimmer::slimList($section['subscriptions']);
            }

            if (isset($section['customized_post']) && is_array($section['customized_post'])) {
                $section['customized_post'] = array_map(
                    fn ($post) => ProviderCustomizedPostPayloadSlimmer::slimItem(
                        is_array($post) ? $post : $post->toArray()
                    ),
                    $section['customized_post']
                );
            }

            return $section;
        }, $sections);
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @return array<string, mixed>
     */
    public static function slimBundle(array $bundle): array
    {
        if (isset($bundle['dashboard']) && is_array($bundle['dashboard'])) {
            $bundle['dashboard'] = self::slimSections($bundle['dashboard']);
        }

        return $bundle;
    }

    /**
     * @param  list<mixed>  $bookings
     * @return list<array<string, mixed>>
     */
    public static function slimRecentBookings(array $bookings): array
    {
        return array_values(array_map(function ($booking) {
            $row = is_array($booking) ? $booking : $booking->toArray();

            return self::slimRecentBookingItem($row);
        }, $bookings));
    }

    /**
     * @param  array<string, mixed>  $booking
     * @return array<string, mixed>
     */
    public static function slimRecentBookingItem(array $booking): array
    {
        $slim = self::pickKeys($booking, self::RECENT_BOOKING_KEYS);

        if (! isset($slim['detail']) || ! is_array($slim['detail'])) {
            return $slim;
        }

        $firstDetail = $slim['detail'][0] ?? null;
        if (! is_array($firstDetail)) {
            unset($slim['detail']);

            return $slim;
        }

        $detail = self::pickKeys($firstDetail, self::BOOKING_DETAIL_KEYS);
        if (isset($detail['service']) && is_array($detail['service'])) {
            $detail['service'] = self::pickKeys($detail['service'], self::SERVICE_KEYS);
        }

        $slim['detail'] = [$detail];

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
