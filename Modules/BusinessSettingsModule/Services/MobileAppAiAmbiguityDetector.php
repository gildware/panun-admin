<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Vague targets that must not execute — ask clarification.
 */
final class MobileAppAiAmbiguityDetector
{
    public static function isVagueTargetPhrase(string $text): bool
    {
        $t = mb_strtolower(trim($text));

        return (bool) preg_match(
            '/\b(?:that|this|the)\s+(?:one|booking|service|item)\b/iu',
            $t
        ) || (bool) preg_match(
            '/\b(?:old one|old ones|the other|that one|this one)\b/iu',
            $t
        ) || (bool) preg_match(
            '/\b(?:change|move|update)\s+(?:the\s+)?(?:date|time)\b/iu',
            $t
        ) && ! preg_match('/\b(?:ac|repair|inverter|plumb|electric)\b/iu', $t);
    }

    public static function clarificationFor(string $text, ?string $domain = null): string
    {
        if ($domain === MobileAppAiIntentDomainCatalog::CART || preg_match('/\bcart\b/iu', $text)) {
            return 'Which cart item do you mean? Say **show my cart** to see names, or name the service (e.g. AC repair).';
        }
        if ($domain === MobileAppAiIntentDomainCatalog::BOOKING || preg_match('/\bbooking\b/iu', $text)) {
            return 'Which booking should I use? Say **my bookings** or share your **PK…** reference.';
        }

        return 'I want to help — could you be a bit more specific about what to change?';
    }
}
