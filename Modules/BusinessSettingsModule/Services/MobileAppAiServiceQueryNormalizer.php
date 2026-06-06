<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Turn free-text (English + Roman Urdu/Hinglish) into a catalog search query.
 */
final class MobileAppAiServiceQueryNormalizer
{
    /**
     * @var array<string, string>
     */
    private const APPLIANCE_ALIASES = [
        'ac' => 'AC repair',
        'a c' => 'AC repair',
        'air conditioner' => 'AC repair',
        'air conditioning' => 'AC repair',
        'air condition' => 'AC repair',
        'cooling' => 'AC repair',
        'fridge' => 'refrigerator repair',
        'refrigerator' => 'refrigerator repair',
        'tv' => 'TV repair',
        'geyser' => 'geyser repair',
        'water heater' => 'geyser repair',
        'washing machine' => 'washing machine repair',
        'ro' => 'RO water purifier',
    ];

    public static function normalize(string $text): string
    {
        $original = trim($text);
        if ($original === '') {
            return '';
        }

        if (MobileAppAiBookingMessageDetector::looksLikeServiceBookingRequest($text)
            || MobileAppAiBookingMessageDetector::hasBookingIntent($text)) {
            return '';
        }

        foreach (self::APPLIANCE_ALIASES as $needle => $canonical) {
            if (preg_match('/\b'.preg_quote($needle, '/').'\b/i', $original)) {
                return $canonical;
            }
        }

        $t = mb_strtolower($original);
        $t = (string) preg_replace(
            '/\b(theek|theak|thik|sahi|karwana|karwani|karwao|karna|karni|kar do|karwa do|chahiye|chaiye|chaiye|mujhe|mujhay|ko|ka|ki|ke|mein|main|se|hai|ho|he|mukhya|please|help|kripya|jaldi|abhi|kal|aaj)\b/iu',
            ' ',
            $t
        );
        $t = (string) preg_replace(
            '/\b(i want|i need|need to|want to|have to|would like|please|help me|get|have|fix|repair|install|book|booking|service|a|an|the|for|to|my)\b/iu',
            ' ',
            $t
        );
        $t = trim((string) preg_replace('/\s+/', ' ', $t));

        if ($t === '' || mb_strlen($t) < 2) {
            return $original;
        }

        if (preg_match('/\b(ac|a\/c)\b/i', $t)) {
            return 'AC repair';
        }

        return mb_convert_case($t, MB_CASE_TITLE, 'UTF-8');
    }

    public static function isGenericBookingPhrase(string $text): bool
    {
        $s = mb_strtolower(trim($text));
        $s = trim((string) preg_replace('/[^a-z0-9\s]/', ' ', $s));
        $s = trim((string) preg_replace('/\s+/', ' ', $s));

        if ($s === '') {
            return true;
        }

        $generic = [
            'book a service', 'book service', 'book', 'booking', 'a service', 'service',
            'i want to book', 'need to book', 'help me book', 'schedule a service',
            'make a booking', 'new booking', 'home service', 'get service',
        ];

        if (in_array($s, $generic, true) || str_contains($s, 'book a service')) {
            return true;
        }

        if (MobileAppAiBookingMessageDetector::looksLikeTechnicianRequest($text)) {
            return true;
        }

        if (MobileAppAiBookingMessageDetector::hasBookingIntent($text)
            && ! MobileAppAiBookingMessageDetector::hasServiceTradeHint($text)) {
            $lower = mb_strtolower(trim($text));
            if (MobileAppAiBookingMessageDetector::looksLikeServiceBookingRequest($text)) {
                return true;
            }

            $isProblem = (bool) preg_match(
                '/\b(leak|leaking|not working|problem|issue|ho raha|kar raha|cooling|tap|pipe|kharab)\b/iu',
                $lower
            );
            if (! $isProblem) {
                return true;
            }
        }

        return false;
    }

    /**
     * Customer describes a home-service need (not app help, not generic "book").
     */
    public static function looksLikeProblemOrService(string $text): bool
    {
        if (self::isGenericBookingPhrase($text)) {
            return false;
        }

        if (MobileAppAiBookingMessageDetector::looksLikeBookingStatusQuery($text)) {
            return false;
        }

        if (MobileAppAiCartScheduleReply::looksLikeCartScheduleQuery($text)
            || MobileAppAiCartRequestParser::looksLikeViewCart($text)) {
            return false;
        }

        $resolved = MobileAppAiServiceIntentResolver::resolve($text);
        if ($resolved['unsupported'] !== null) {
            return true;
        }

        if ($resolved['trade_id'] !== '') {
            return true;
        }

        return self::looksLikeServiceRequest($text);
    }

    public static function looksLikeServiceRequest(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '' || mb_strlen($t) < 3 || mb_strlen($t) > 200) {
            return false;
        }

        if (self::isGenericBookingPhrase($text)) {
            return false;
        }

        if (MobileAppAiBookingMessageDetector::looksLikeBookingStatusQuery($text)) {
            return false;
        }

        if (MobileAppAiCartScheduleReply::looksLikeCartScheduleQuery($text)) {
            return false;
        }

        if (MobileAppAiCartRequestParser::looksLikeViewCart($text)) {
            return false;
        }

        if (preg_match(
            '/\b(payment|otp|login|log in|sign in|refund|human|agent|support hours|cart empty)\b/i',
            $t
        )) {
            return false;
        }

        $resolved = MobileAppAiServiceIntentResolver::resolve($text);
        if ($resolved['unsupported'] !== null) {
            return false;
        }

        if ($resolved['trade_id'] !== '') {
            return true;
        }

        if (MobileAppAiBookingMessageDetector::hasServiceTradeHint($text)) {
            return true;
        }

        if (preg_match(
            '/\b(ac|a\/c|air\s*condition|fridge|refrigerator|geyser|washing|ro\b|tv\b|cooler|heater)\b/i',
            $text
        )) {
            return true;
        }

        if (preg_match(
            '/\b(theek|theak|thik|karwana|karwani|karna|karni|fix|repair|install|safai|cleaning|mistri|mistary|mistry)\b/iu',
            $t
        )) {
            return true;
        }

        return false;
    }
}
