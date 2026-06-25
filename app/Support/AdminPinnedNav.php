<?php

namespace App\Support;

use Modules\BookingModule\Entities\Booking;

class AdminPinnedNav
{
  /**
     * @return array<int, array{pin_key: string, label: string, url: string, paths: array<int, string>, count?: int}>
     */
    public static function catalogForChrome(array $menuCounts, float $maxBookingAmount): array
    {
        $verifyCount = Booking::where('is_verified', '0')
            ->where('payment_method', 'cash_after_service')
            ->where('total_booking_amount', '>', $maxBookingAmount)
            ->whereIn('booking_status', ['pending', 'accepted'])
            ->count();

        $counts = [
            'booking.requests' => (int) ($menuCounts['all_bookings'] ?? 0),
            'booking.verify' => $verifyCount,
        ];

        return collect(AdminNavRegistry::pinnableCatalog())
            ->map(function (array $item) use ($counts) {
                if (isset($counts[$item['pin_key']]) && $counts[$item['pin_key']] > 0) {
                    $item['count'] = $counts[$item['pin_key']];
                }

                return $item;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function validPinKeys(): array
    {
        return collect(AdminNavRegistry::pinnableCatalog())
            ->pluck('pin_key')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    public static function sanitizePinKeys(array $keys): array
    {
        $valid = array_flip(self::validPinKeys());
        $seen = [];

        return array_values(array_filter($keys, static function ($key) use (&$seen, $valid) {
            if (! is_string($key) || $key === '' || ! isset($valid[$key]) || isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;

            return true;
        }));
    }

    /**
     * @return array<int, string>
     */
    public static function pinnedKeysForUser(?\Modules\UserManagement\Entities\User $user): array
    {
        if ($user && is_array($user->admin_pinned_nav)) {
            return self::sanitizePinKeys($user->admin_pinned_nav);
        }

        return AdminNavRegistry::defaultPinKeys();
    }
}
