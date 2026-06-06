<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Grouped account-summary intents (not hundreds of scattered matchers).
 */
final class MobileAppAiSummaryIntentDetector
{
    /**
     * @return array{intent: string, confidence: float, mode: string}|null
     */
    public static function detect(string $text): ?array
    {
        $t = mb_strtolower(MobileAppAiInputNormalizer::forMatching($text));

        if ($booking = self::detectBooking($t)) {
            return $booking;
        }
        if ($cart = self::detectCart($t)) {
            return $cart;
        }
        if ($bid = self::detectBidding($t)) {
            return $bid;
        }
        if ($addr = self::detectAddress($t)) {
            return $addr;
        }

        return null;
    }

    /**
     * @return array{intent: string, confidence: float, mode: string}|null
     */
    private static function detectBooking(string $t): ?array
    {
        if (MobileAppAiBookingManageService::looksLikeCancelBooking($t)
            || MobileAppAiBookingManageService::looksLikeRebook($t)) {
            return null;
        }

        if (MobileAppAiBookingMessageDetector::looksLikeBookingCountQuery($t)) {
            return ['intent' => MobileAppAiIntentCatalog::BOOKING_SUMMARY, 'confidence' => 0.94, 'mode' => 'count'];
        }

        if (preg_match('/\b(?:latest|last|most recent|next|upcoming)\b.*\b(?:booking|order)\b/iu', $t)
            || preg_match('/\b(?:booking|order)\b.*\b(?:latest|last|next|upcoming)\b/iu', $t)) {
            return ['intent' => MobileAppAiIntentCatalog::BOOKING_SUMMARY, 'confidence' => 0.9, 'mode' => 'latest'];
        }

        if (MobileAppAiBookingMessageDetector::looksLikeBookingStatusQuery($t)
            || preg_match('/\b(?:meri|mere)\s+booking/iu', $t)) {
            return ['intent' => MobileAppAiIntentCatalog::BOOKING_SUMMARY, 'confidence' => 0.91, 'mode' => 'list'];
        }

        return null;
    }

    /**
     * @return array{intent: string, confidence: float, mode: string}|null
     */
    private static function detectCart(string $t): ?array
    {
        if (preg_match('/\b(?:how many|kitne|number of|count of)\b.*\b(?:item|service|line)s?\b.*\b(?:cart|basket)\b/iu', $t)
            || preg_match('/\b(?:cart|basket)\b.*\b(?:how many|kitne|count|items?)\b/iu', $t)) {
            return ['intent' => MobileAppAiIntentCatalog::CART_SUMMARY, 'confidence' => 0.93, 'mode' => 'count'];
        }

        if (MobileAppAiPricingReply::looksLikePricingQuery($t)
            || preg_match('/\b(?:cart|basket)\s+(?:total|charges?|amount|bill)\b/iu', $t)) {
            return ['intent' => MobileAppAiIntentCatalog::CART_SUMMARY, 'confidence' => 0.9, 'mode' => 'total'];
        }

        if (MobileAppAiCartRequestParser::looksLikeViewCart($t)
            || preg_match('/\b(?:mera|meri)\s+(?:cart|basket)\b/iu', $t)
            || preg_match('/\bcart\s+dikhao\b/iu', $t)) {
            return ['intent' => MobileAppAiIntentCatalog::CART_SUMMARY, 'confidence' => 0.92, 'mode' => 'items'];
        }

        return null;
    }

    /**
     * @return array{intent: string, confidence: float, mode: string}|null
     */
    private static function detectBidding(string $t): ?array
    {
        if (preg_match('/\b(?:how many|kitne|pending)\b.*\b(?:bid|bidding)/iu', $t)
            || preg_match('/\b(?:bid|bidding)\b.*\b(?:how many|kitne|pending|count)\b/iu', $t)) {
            return ['intent' => MobileAppAiIntentCatalog::BIDDING_SUMMARY, 'confidence' => 0.9, 'mode' => 'count'];
        }

        if (MobileAppAiBiddingService::looksLikeBiddingIntent($t)
            && ! MobileAppAiBiddingService::looksLikeAcceptBid($t)
            && ! MobileAppAiBiddingService::looksLikeDenyBid($t)) {
            return ['intent' => MobileAppAiIntentCatalog::BIDDING_SUMMARY, 'confidence' => 0.85, 'mode' => 'list'];
        }

        return null;
    }

    /**
     * @return array{intent: string, confidence: float, mode: string}|null
     */
    private static function detectAddress(string $t): ?array
    {
        if (preg_match('/\b(?:how many|kitne|count)\b.*\b(?:address|addresses)\b/iu', $t)) {
            return ['intent' => MobileAppAiIntentCatalog::ADDRESS_SUMMARY, 'confidence' => 0.9, 'mode' => 'count'];
        }

        if (preg_match('/\b(?:my|saved|meri|mere)\s+(?:address|addresses|location)\b/iu', $t)
            || preg_match('/\b(?:address|addresses)\s+(?:list|dikhao)\b/iu', $t)) {
            return ['intent' => MobileAppAiIntentCatalog::ADDRESS_SUMMARY, 'confidence' => 0.88, 'mode' => 'list'];
        }

        return null;
    }
}
