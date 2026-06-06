<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Account-specific answers must come from live backend data, never model memory.
 */
final class MobileAppAiDataAuthorityPolicy
{
    /** @return list<string> */
    public static function intentsRequiringLiveData(): array
    {
        return [
            MobileAppAiIntentCatalog::VIEW_CART,
            MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY,
            MobileAppAiIntentCatalog::CART_REMOVE_ITEM,
            MobileAppAiIntentCatalog::CART_RESCHEDULE,
            MobileAppAiIntentCatalog::CART_CLEAR,
            MobileAppAiIntentCatalog::CART_QTY_CHANGE,
            MobileAppAiIntentCatalog::PRICING_QUERY,
            MobileAppAiIntentCatalog::BOOKING_STATUS,
            MobileAppAiIntentCatalog::BOOKING_CANCEL,
            MobileAppAiIntentCatalog::BOOKING_REBOOK,
            MobileAppAiIntentCatalog::BIDDING_LIST,
            MobileAppAiIntentCatalog::COUPON_LIST,
            MobileAppAiIntentCatalog::COUPON_APPLY,
        ];
    }

    public static function requiresLiveData(string $intent): bool
    {
        return in_array($intent, self::intentsRequiringLiveData(), true);
    }

    public static function promptAppendix(): string
    {
        return '## Data authority (mandatory)'."\n"
            .'- Never state cart contents, booking status, counts, totals, bids, coupons, or addresses from memory.'."\n"
            .'- Always call the appropriate tool or use deterministic handlers that fetch live account data.'."\n"
            .'- If data is unavailable, say you cannot see it yet — do not estimate.';
    }
}
