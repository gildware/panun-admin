<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Domain router — limits Gemini tools to the active domain.
 */
final class MobileAppAiIntentDomainCatalog
{
    public const CART = 'cart';

    public const BOOKING = 'booking';

    public const BIDDING = 'bidding';

    public const ACCOUNT = 'account';

    public const SUPPORT = 'support';

    public const PRICING = 'pricing';

    public const CATALOG = 'catalog';

    public static function domainForIntent(string $intent): string
    {
        return match ($intent) {
            MobileAppAiIntentCatalog::VIEW_CART,
            MobileAppAiIntentCatalog::CART_SUMMARY,
            MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY,
            MobileAppAiIntentCatalog::CART_REMOVE_ITEM,
            MobileAppAiIntentCatalog::CART_RESCHEDULE,
            MobileAppAiIntentCatalog::CART_CLEAR,
            MobileAppAiIntentCatalog::CART_QTY_CHANGE => self::CART,
            MobileAppAiIntentCatalog::BOOKING_START,
            MobileAppAiIntentCatalog::BOOKING_WIZARD_CONTINUE,
            MobileAppAiIntentCatalog::BOOKING_STATUS,
            MobileAppAiIntentCatalog::BOOKING_SUMMARY,
            MobileAppAiIntentCatalog::BOOKING_CANCEL,
            MobileAppAiIntentCatalog::BOOKING_REBOOK => self::BOOKING,
            MobileAppAiIntentCatalog::BIDDING_LIST,
            MobileAppAiIntentCatalog::BIDDING_SUMMARY,
            MobileAppAiIntentCatalog::BIDDING_CREATE,
            MobileAppAiIntentCatalog::BIDDING_ACCEPT,
            MobileAppAiIntentCatalog::BIDDING_DECLINE => self::BIDDING,
            MobileAppAiIntentCatalog::COUPON_APPLY,
            MobileAppAiIntentCatalog::COUPON_REMOVE,
            MobileAppAiIntentCatalog::COUPON_LIST,
            MobileAppAiIntentCatalog::ADDRESS_SUMMARY => self::ACCOUNT,
            MobileAppAiIntentCatalog::PRICING_QUERY => self::PRICING,
            MobileAppAiIntentCatalog::HUMAN_SUPPORT,
            MobileAppAiIntentCatalog::APP_TROUBLESHOOT => self::SUPPORT,
            MobileAppAiIntentCatalog::SERVICE_TRIAGE,
            MobileAppAiIntentCatalog::SERVICE_DETAILS,
            MobileAppAiIntentCatalog::UNKNOWN => self::CATALOG,
            default => self::CATALOG,
        };
    }

    /**
     * @return list<string>
     */
    public static function toolsForDomain(string $domain): array
    {
        $shared = [
            'get_public_business_info',
            'search_support_knowledge',
            'get_customer_account_snapshot',
            'report_unclear_user_intent',
            'request_human_support_handoff',
        ];

        return match ($domain) {
            self::CART => array_merge($shared, [
                'get_customer_cart_summary',
                'manage_customer_cart',
            ]),
            self::BOOKING => array_merge($shared, [
                'manage_app_booking',
                'list_my_system_bookings',
                'get_booking_status_by_reference',
                'list_my_saved_addresses',
                'match_zone_from_address',
            ]),
            self::BIDDING => $shared,
            self::ACCOUNT => array_merge($shared, [
                'get_customer_cart_summary',
                'list_my_saved_addresses',
                'list_my_system_bookings',
            ]),
            self::PRICING => array_merge($shared, [
                'get_customer_cart_summary',
                'get_public_business_info',
            ]),
            self::SUPPORT => array_merge($shared, [
                'search_support_knowledge',
            ]),
            self::CATALOG => array_merge($shared, [
                'search_catalog_services',
                'list_full_service_catalog',
                'list_service_categories',
                'list_service_areas',
                'manage_app_booking',
            ]),
            default => MobileAppAiSupportToolPolicy::ALLOWED_TOOLS,
        };
    }
}
