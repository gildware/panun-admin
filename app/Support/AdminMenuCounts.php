<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\AppCustomRequest;
use Modules\BookingModule\Entities\WebBooking;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderChangeRequest;
use Modules\ProviderManagement\Entities\ProviderShowcaseItem;
use Modules\ReviewModule\Entities\ProviderCustomerReview;
use Modules\ReviewModule\Entities\Review;

/**
 * Sidebar badge counts — cached to avoid heavy queries on every admin page.
 */
final class AdminMenuCounts
{
    public static function all(): array
    {
        return Cache::remember('admin_sidebar_menu_counts', 60, function () {
            return [
                'all_bookings' => Booking::count(),
                'pending_booking_reviews' => Review::where('is_active', 0)->count()
                    + ProviderCustomerReview::where('is_active', 0)->count(),
                'special_scenarios' => Booking::query()
                    ->where('is_repeated', 0)
                    ->whereNotNull('settlement_outcome')
                    ->where('settlement_outcome', '!=', '')
                    ->count(),
                'cancelled_by_provider' => Booking::query()->cancelledByProvider()->count(),
                'cancelled_by_customer' => self::safeCountCancelledByCustomerPendingRefund(),
                'pending_providers' => Provider::ofApproval(2)->count(),
                'pending_showcase_items' => ProviderShowcaseItem::where('is_approved', 2)->count(),
                'pending_profile_changes' => ProviderChangeRequest::where('status', 2)->count(),
                'denied_providers' => Provider::ofApproval(0)->count(),
                'web_bookings_pending' => self::safeCountPendingWebBookings(),
                'app_custom_requests_pending' => self::safeCountPendingAppCustomRequests(),
            ];
        });
    }

    public static function forget(): void
    {
        Cache::forget('admin_sidebar_menu_counts');
    }

    private static function safeCountCancelledByCustomerPendingRefund(): int
    {
        try {
            return Booking::query()->cancelledByCustomerPendingRefund()->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function safeCountPendingWebBookings(): int
    {
        try {
            if (! Schema::hasTable('web_bookings')) {
                return 0;
            }

            return WebBooking::query()
                ->where('status', WebBooking::STATUS_PENDING_REVIEW)
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function safeCountPendingAppCustomRequests(): int
    {
        try {
            if (! Schema::hasTable('app_custom_requests')) {
                return 0;
            }

            return AppCustomRequest::query()
                ->where('status', AppCustomRequest::STATUS_PENDING)
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
