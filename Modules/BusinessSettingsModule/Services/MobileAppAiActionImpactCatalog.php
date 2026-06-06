<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Action impact levels — HIGH always requires explicit confirmation.
 */
final class MobileAppAiActionImpactCatalog
{
    public const LOW = 'LOW';

    public const MEDIUM = 'MEDIUM';

    public const HIGH = 'HIGH';

    /** @return array<string, string> intent => impact */
    public static function intentImpactMap(): array
    {
        return [
            MobileAppAiIntentCatalog::VIEW_CART => self::LOW,
            MobileAppAiIntentCatalog::CART_SUMMARY => self::LOW,
            MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY => self::LOW,
            MobileAppAiIntentCatalog::BOOKING_SUMMARY => self::LOW,
            MobileAppAiIntentCatalog::BIDDING_SUMMARY => self::LOW,
            MobileAppAiIntentCatalog::ADDRESS_SUMMARY => self::LOW,
            MobileAppAiIntentCatalog::PRICING_QUERY => self::LOW,
            MobileAppAiIntentCatalog::BOOKING_STATUS => self::LOW,
            MobileAppAiIntentCatalog::SERVICE_DETAILS => self::LOW,
            MobileAppAiIntentCatalog::COUPON_LIST => self::LOW,
            MobileAppAiIntentCatalog::BIDDING_LIST => self::LOW,
            MobileAppAiIntentCatalog::GREETING => self::LOW,
            MobileAppAiIntentCatalog::THANKS => self::LOW,
            MobileAppAiIntentCatalog::APP_TROUBLESHOOT => self::LOW,
            MobileAppAiIntentCatalog::HUMAN_SUPPORT => self::LOW,
            MobileAppAiIntentCatalog::SERVICE_TRIAGE => self::LOW,
            MobileAppAiIntentCatalog::CART_RESCHEDULE => self::MEDIUM,
            MobileAppAiIntentCatalog::CART_QTY_CHANGE => self::MEDIUM,
            MobileAppAiIntentCatalog::CART_CLEAR => self::HIGH,
            MobileAppAiIntentCatalog::CART_REMOVE_ITEM => self::HIGH,
            MobileAppAiIntentCatalog::BOOKING_CANCEL => self::HIGH,
            MobileAppAiIntentCatalog::BOOKING_REBOOK => self::MEDIUM,
            MobileAppAiIntentCatalog::COUPON_APPLY => self::HIGH,
            MobileAppAiIntentCatalog::COUPON_REMOVE => self::HIGH,
            MobileAppAiIntentCatalog::BIDDING_ACCEPT => self::HIGH,
            MobileAppAiIntentCatalog::BIDDING_DECLINE => self::HIGH,
            MobileAppAiIntentCatalog::BOOKING_START => self::MEDIUM,
        ];
    }

    public static function impactForIntent(string $intent): string
    {
        return self::intentImpactMap()[$intent] ?? self::MEDIUM;
    }

    public static function requiresConfirmation(string $intent): bool
    {
        return self::impactForIntent($intent) === self::HIGH;
    }
}
