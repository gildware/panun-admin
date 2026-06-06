<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Detect when the customer is frustrated that the AI misunderstood them.
 */
final class MobileAppAiFrustrationDetector
{
    public static function looksLikeFrustration(string $text): bool
    {
        $t = mb_strtolower(MobileAppAiInputNormalizer::forMatching($text));

        return (bool) preg_match(
            '/\b(?:samjha|samajh|samjhe|samjhi|understand|understood|kya\s+bola|kya\s+kaha|what\s+i\s+said|didn\'?t\s+(?:get|understand)|nahi\s+samjhe|galat|wrong)\b/iu',
            $t
        );
    }
}
