<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Slim booking rows for customer booking list API responses.
 */
class CustomerBookingListPayloadSlimmer
{
    /** @var list<string> */
    public const BOOKING_KEYS = [
        'id',
        'readable_id',
        'booking_status',
        'created_at',
        'service_schedule',
        'is_repeated',
        'repeats',
        'list_display_total',
        'payable_grand_total',
        'total_booking_amount',
        'sub_category_id',
        'is_customize_booking',
        'booking_status_display_key',
        'booking_status_badge_variant',
        'booking_status_tags',
    ];

    /** @var list<string> */
    public const REPEAT_KEYS = [
        'id',
        'readable_id',
        'booking_status',
        'service_schedule',
    ];

    public static function slimPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn ($booking) => self::slimItem(is_array($booking) ? $booking : $booking->toArray())
            )
        );

        return $paginator;
    }

    /**
     * @param  array<string, mixed>  $booking
     * @return array<string, mixed>
     */
    public static function slimItem(array $booking): array
    {
        $slim = [];

        foreach (self::BOOKING_KEYS as $key) {
            if (! array_key_exists($key, $booking)) {
                continue;
            }

            if ($key === 'repeats' && is_array($booking[$key])) {
                $slim[$key] = array_map(
                    fn ($repeat) => self::pickKeys(
                        is_array($repeat) ? $repeat : (array) $repeat,
                        self::REPEAT_KEYS
                    ),
                    $booking[$key]
                );
                continue;
            }

            $slim[$key] = $booking[$key];
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
