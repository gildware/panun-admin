<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Modules\BookingModule\Entities\Booking;
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
                'pending_providers' => Provider::ofApproval(2)->count(),
                'pending_showcase_items' => ProviderShowcaseItem::where('is_approved', 2)->count(),
                'pending_profile_changes' => ProviderChangeRequest::where('status', 2)->count(),
                'denied_providers' => Provider::ofApproval(0)->count(),
            ];
        });
    }

    public static function forget(): void
    {
        Cache::forget('admin_sidebar_menu_counts');
    }
}
