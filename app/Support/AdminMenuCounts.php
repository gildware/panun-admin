<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\AppCustomRequest;
use Modules\BookingModule\Entities\WebBooking;
use Modules\BookingModule\Entities\WebProviderRequest;
use Modules\CartModule\Entities\Cart;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderChangeRequest;
use Modules\ProviderManagement\Entities\ProviderShowcaseItem;
use Modules\ReviewModule\Entities\ProviderCustomerReview;
use Modules\ReviewModule\Entities\Review;
use Modules\ServiceManagement\Entities\ServiceRequest;

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
                'web_provider_requests_pending' => self::safeCountPendingWebProviderRequests(),
                'app_custom_requests_pending' => self::safeCountPendingAppCustomRequests(),
                'pending_verify_bookings' => self::safeCountPendingVerifyBookings(),
                'unassigned_leads' => self::safeCountUnassignedOpenLeads(),
                'pending_bookings' => self::safeCountPendingBookings(),
                'customer_cart_not_contacted' => self::safeCountCustomerCartNotContacted(),
                'new_service_requests' => self::safeCountNewServiceRequests(),
            ];
        });
    }

    public static function forget(): void
    {
        Cache::forget('admin_sidebar_menu_counts');
    }

    public static function badgeCountForUrl(string $url): int
    {
        $counts = self::all();
        $path = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
        $query = [];
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);

        if (str_starts_with($path, 'admin/booking/web-bookings')) {
            return (int) ($counts['web_bookings_pending'] ?? 0);
        }

        if (str_starts_with($path, 'admin/booking/web-provider-requests')) {
            return (int) ($counts['web_provider_requests_pending'] ?? 0);
        }

        if (str_starts_with($path, 'admin/booking/app-custom-requests')) {
            return (int) ($counts['app_custom_requests_pending'] ?? 0);
        }

        if (str_starts_with($path, 'admin/provider/onboarding')) {
            return (int) ($counts['pending_providers'] ?? 0);
        }

        if (str_starts_with($path, 'admin/provider/showcase-approval')) {
            return (int) ($counts['pending_showcase_items'] ?? 0);
        }

        if (str_starts_with($path, 'admin/provider/profile-change')) {
            return (int) ($counts['pending_profile_changes'] ?? 0);
        }

        if (str_starts_with($path, 'admin/booking/list/verification')) {
            return (int) ($counts['pending_verify_bookings'] ?? 0);
        }

        if (str_starts_with($path, 'admin/booking/list/special-scenarios')) {
            return (int) ($counts['special_scenarios'] ?? 0);
        }

        if (str_starts_with($path, 'admin/booking/list/cancelled-by-provider')) {
            return (int) ($counts['cancelled_by_provider'] ?? 0);
        }

        if (str_starts_with($path, 'admin/booking/reviews/list')) {
            return (int) ($counts['pending_booking_reviews'] ?? 0);
        }

        if (str_starts_with($path, 'admin/service/request/list')) {
            return (int) ($counts['new_service_requests'] ?? 0);
        }

        if (str_starts_with($path, 'admin/customer-cart')) {
            return (int) ($counts['customer_cart_not_contacted'] ?? 0);
        }

        if (str_starts_with($path, 'admin/booking/list') || str_starts_with($path, 'admin/booking/details')) {
            $bookingStatus = (string) ($query['booking_status'] ?? 'all');

            if ($bookingStatus === 'pending') {
                return (int) ($counts['pending_bookings'] ?? 0);
            }

            if ($bookingStatus === 'all' || $bookingStatus === '') {
                return is_admin_employee()
                    ? (int) ($counts['pending_bookings'] ?? 0)
                    : (int) ($counts['all_bookings'] ?? 0);
            }
        }

        if (
            preg_match('#^admin/lead(/|$)#', $path)
            && ! str_contains($path, 'outbound-enquiry')
            && ! str_contains($path, 'configuration')
            && ! str_contains($path, 'reports')
            && ! str_contains($path, 'todays-followups')
        ) {
            $handledBy = (array) ($query['handled_by'] ?? []);
            if (in_array('__unassigned__', $handledBy, true)) {
                return (int) ($counts['unassigned_leads'] ?? 0);
            }
        }

        return 0;
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

            $withoutLead = WebBooking::query()
                ->whereNull('lead_id')
                ->where('status', WebBooking::STATUS_PENDING_REVIEW)
                ->count();

            $leadIds = WebBooking::query()
                ->whereNotNull('lead_id')
                ->distinct()
                ->pluck('lead_id')
                ->filter()
                ->unique()
                ->values();

            if ($leadIds->isEmpty()) {
                return $withoutLead;
            }

            $openLeadQuery = Lead::query()->whereIn('id', $leadIds);
            app(LeadOpenStatusService::class)->restrictQueryToOpenLeads($openLeadQuery);
            $openLeadIds = $openLeadQuery->pluck('id');

            if ($openLeadIds->isEmpty()) {
                return $withoutLead;
            }

            $withOpenLead = WebBooking::query()
                ->whereIn('lead_id', $openLeadIds)
                ->count();

            return $withoutLead + $withOpenLead;
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function safeCountPendingWebProviderRequests(): int
    {
        try {
            if (! Schema::hasTable('web_provider_requests')) {
                return 0;
            }

            return WebProviderRequest::query()
                ->where('status', WebProviderRequest::STATUS_PENDING_REVIEW)
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
                ->pending()
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function safeCountPendingVerifyBookings(): int
    {
        try {
            $maxBookingAmount = (float) ((business_config('max_booking_amount', 'booking_setup'))->live_values ?? 0);

            return Booking::query()
                ->where('is_verified', '0')
                ->where('payment_method', 'cash_after_service')
                ->where('total_booking_amount', '>', $maxBookingAmount)
                ->whereIn('booking_status', ['pending', 'accepted'])
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function safeCountUnassignedOpenLeads(): int
    {
        try {
            if (! Schema::hasTable('leads')) {
                return 0;
            }

            $query = Lead::query()->where(function ($w) {
                $w->whereNull('handled_by')
                    ->orWhere('handled_by', '')
                    ->orWhere('handled_by', Lead::HANDLED_BY_AI);
            });
            app(LeadOpenStatusService::class)->restrictQueryToOpenLeads($query);

            return $query->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function safeCountPendingBookings(): int
    {
        try {
            $maxBookingAmount = (float) ((business_config('max_booking_amount', 'booking_setup'))->live_values ?? 0);

            return Booking::query()->adminPendingBookings($maxBookingAmount)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function safeCountCustomerCartNotContacted(): int
    {
        try {
            if (! Schema::hasTable('carts')) {
                return 0;
            }

            return (int) Cart::query()
                ->leftJoin('customer_cart_contacts as ccc', 'ccc.customer_id', '=', 'carts.customer_id')
                ->whereNotNull('carts.customer_id')
                ->where('carts.is_guest', 0)
                ->whereNull('ccc.contacted_at')
                ->distinct()
                ->count('carts.customer_id');
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function safeCountNewServiceRequests(): int
    {
        try {
            if (! Schema::hasTable('service_requests')) {
                return 0;
            }

            return ServiceRequest::query()->where('status', 'pending')->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
