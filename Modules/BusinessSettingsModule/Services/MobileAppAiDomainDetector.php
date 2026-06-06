<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Domain signal when intent is uncertain — routes before generic fallback.
 */
final class MobileAppAiDomainDetector
{
    public static function detect(string $text): ?string
    {
        $t = mb_strtolower(MobileAppAiInputNormalizer::forMatching($text));

        if (preg_match('/\b(cart|basket|checkout)\b/iu', $t)) {
            return MobileAppAiIntentDomainCatalog::CART;
        }
        if (preg_match('/\b(booking|bookings|order|pk[0-9a-z]{4,})\b/iu', $t)) {
            return MobileAppAiIntentDomainCatalog::BOOKING;
        }
        if (preg_match('/\b(bid|bidding|bids|provider bid)\b/iu', $t)) {
            return MobileAppAiIntentDomainCatalog::BIDDING;
        }
        if (preg_match('/\b(address|addresses|location|where to visit)\b/iu', $t)) {
            return MobileAppAiIntentDomainCatalog::ACCOUNT;
        }
        if (preg_match('/\b(support|help|payment|otp|refund|complaint)\b/iu', $t)) {
            return MobileAppAiIntentDomainCatalog::SUPPORT;
        }
        if (MobileAppAiServiceQueryNormalizer::looksLikeProblemOrService($text)) {
            return MobileAppAiIntentDomainCatalog::CATALOG;
        }

        return null;
    }
}
