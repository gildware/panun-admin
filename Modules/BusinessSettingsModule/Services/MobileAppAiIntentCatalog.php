<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Canonical customer-app AI intents (NLU output + rule fallback).
 */
final class MobileAppAiIntentCatalog
{
    public const VIEW_CART = 'view_cart';

    public const CART_SUMMARY = 'cart_summary';

    public const CART_SCHEDULE_QUERY = 'cart_schedule_query';

    public const CART_REMOVE_ITEM = 'cart_remove_item';

    public const CART_RESCHEDULE = 'cart_reschedule';

    public const CART_CLEAR = 'cart_clear';

    public const CART_QTY_CHANGE = 'cart_qty_change';

    public const PRICING_QUERY = 'pricing_query';

    public const BOOKING_START = 'booking_start';

    public const BOOKING_WIZARD_CONTINUE = 'booking_wizard_continue';

    public const BOOKING_STATUS = 'booking_status';

    public const BOOKING_SUMMARY = 'booking_summary';

    public const BOOKING_CANCEL = 'booking_cancel';

    public const BOOKING_REBOOK = 'booking_rebook';

    public const COUPON_APPLY = 'coupon_apply';

    public const COUPON_REMOVE = 'coupon_remove';

    public const COUPON_LIST = 'coupon_list';

    public const BIDDING_LIST = 'bidding_list';

    public const BIDDING_SUMMARY = 'bidding_summary';

    public const BIDDING_CREATE = 'bidding_create';

    public const BIDDING_ACCEPT = 'bidding_accept';

    public const BIDDING_DECLINE = 'bidding_decline';

    public const SERVICE_TRIAGE = 'service_triage';

    public const SERVICE_DETAILS = 'service_details';

    public const HUMAN_SUPPORT = 'human_support';

    public const ADDRESS_SUMMARY = 'address_summary';

    public const APP_TROUBLESHOOT = 'app_troubleshoot';

    public const GREETING = 'greeting';

    public const THANKS = 'thanks';

    public const UNKNOWN = 'unknown';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::VIEW_CART,
            self::CART_SUMMARY,
            self::CART_SCHEDULE_QUERY,
            self::CART_REMOVE_ITEM,
            self::CART_RESCHEDULE,
            self::CART_CLEAR,
            self::CART_QTY_CHANGE,
            self::PRICING_QUERY,
            self::BOOKING_START,
            self::BOOKING_WIZARD_CONTINUE,
            self::BOOKING_STATUS,
            self::BOOKING_SUMMARY,
            self::BOOKING_CANCEL,
            self::BOOKING_REBOOK,
            self::COUPON_APPLY,
            self::COUPON_REMOVE,
            self::COUPON_LIST,
            self::BIDDING_LIST,
            self::BIDDING_SUMMARY,
            self::BIDDING_CREATE,
            self::BIDDING_ACCEPT,
            self::BIDDING_DECLINE,
            self::SERVICE_TRIAGE,
            self::SERVICE_DETAILS,
            self::HUMAN_SUPPORT,
            self::ADDRESS_SUMMARY,
            self::APP_TROUBLESHOOT,
            self::GREETING,
            self::THANKS,
            self::UNKNOWN,
        ];
    }

    /** @return list<string> */
    public static function cartIntents(): array
    {
        return [
            self::VIEW_CART,
            self::CART_SUMMARY,
            self::CART_SCHEDULE_QUERY,
            self::CART_REMOVE_ITEM,
            self::CART_RESCHEDULE,
            self::CART_CLEAR,
            self::CART_QTY_CHANGE,
            self::PRICING_QUERY,
            self::COUPON_APPLY,
            self::COUPON_REMOVE,
            self::COUPON_LIST,
        ];
    }

    /** @return list<string> */
    public static function summaryIntents(): array
    {
        return [
            self::CART_SUMMARY,
            self::BOOKING_SUMMARY,
            self::BIDDING_SUMMARY,
            self::ADDRESS_SUMMARY,
        ];
    }

    public static function isSummaryIntent(string $intent): bool
    {
        return in_array($intent, self::summaryIntents(), true);
    }

    public static function isValid(string $intent): bool
    {
        return in_array($intent, self::all(), true);
    }

    public static function isCartFamily(string $intent): bool
    {
        return in_array($intent, self::cartIntents(), true);
    }
}
