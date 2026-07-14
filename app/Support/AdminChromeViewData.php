<?php

namespace App\Support;

use Modules\AdminModule\Services\AdminInboxNotificationService;
use Modules\CustomerModule\Services\CustomerHomeCacheWarmState;

/**
 * Shared admin chrome data (nav badges, unread counts, breadcrumbs).
 * Computed once per HTTP request even when multiple layout partials render.
 */
final class AdminChromeViewData
{
    private static ?array $payload = null;

    public static function forView(): array
    {
        if (self::$payload !== null) {
            return self::$payload;
        }

        $user = auth()->user();
        $menuCounts = AdminMenuCounts::all();
        $maxBookingAmount = (float) ((business_config('max_booking_amount', 'booking_setup'))->live_values ?? 0);
        $notificationUnreadCount = $user
            ? (int) app(AdminInboxNotificationService::class)->unreadCount((string) $user->id)
            : 0;

        self::$payload = [
            'menuCounts' => $menuCounts,
            'supportUnreadCount' => AdminHeaderChatCounts::supportUnreadMessages($user),
            'staffUnreadCount' => AdminHeaderChatCounts::staffUnreadMessages($user),
            'whatsappUnreadCount' => AdminHeaderChatCounts::whatsappUnreadChats($user),
            'notificationUnreadCount' => $notificationUnreadCount,
            'all_bookings_menu_count' => $menuCounts['all_bookings'],
            'pending_booking_reviews_count' => $menuCounts['pending_booking_reviews'],
            'special_scenarios_menu_count' => $menuCounts['special_scenarios'],
            'cancelled_by_provider_menu_count' => $menuCounts['cancelled_by_provider'],
            'pending_providers' => $menuCounts['pending_providers'],
            'pending_showcase_items' => $menuCounts['pending_showcase_items'],
            'pending_profile_changes' => $menuCounts['pending_profile_changes'],
            'denied_providers' => $menuCounts['denied_providers'],
            'web_bookings_pending_count' => $menuCounts['web_bookings_pending'] ?? 0,
            'app_custom_requests_pending_count' => $menuCounts['app_custom_requests_pending'] ?? 0,
            'max_booking_amount' => $maxBookingAmount,
            'adminBreadcrumbs' => AdminBreadcrumb::resolve(),
            'adminNavMatch' => AdminNavRegistry::match(),
            'adminGroupSubmenu' => AdminNavRegistry::groupSubmenu(),
            'adminPinnedCatalog' => AdminPinnedNav::catalogForChrome($menuCounts, $maxBookingAmount),
            'adminDefaultPinKeys' => AdminNavRegistry::defaultPinKeys(),
            'adminUserPinnedKeys' => AdminPinnedNav::pinnedKeysForUser($user),
            'homeCacheNeedsReset' => CustomerHomeCacheWarmState::needsAdminReminder(),
        ];

        return self::$payload;
    }
}
