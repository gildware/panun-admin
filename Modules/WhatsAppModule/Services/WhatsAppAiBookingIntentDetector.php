<?php

namespace Modules\WhatsAppModule\Services;

/**
 * Cheap PHP gate for "this WhatsApp thread is a customer who needs a home service".
 * Used to decide whether the server should persist a booking draft even if Gemini forgot to call the tool.
 */
final class WhatsAppAiBookingIntentDetector
{
    /**
     * True when customer text shows they want (or are asking us to do) a home service job.
     */
    public static function looksLikeCustomerServiceNeed(string $customerTextBlob): bool
    {
        $blob = mb_strtolower(trim($customerTextBlob), 'UTF-8');
        if ($blob === '') {
            return false;
        }

        if (self::looksLikeProviderOnboarding($blob)) {
            return false;
        }

        if (self::looksLikeCatalogBrowseOnly($blob)) {
            return false;
        }

        foreach (self::serviceNeedPatterns() as $pattern) {
            if (preg_match($pattern, $blob) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function looksLikeProviderOnboarding(string $customerTextBlob): bool
    {
        $blob = mb_strtolower(trim($customerTextBlob), 'UTF-8');
        if ($blob === '') {
            return false;
        }

        $needles = [
            'join as provider',
            'join as a provider',
            'become a partner',
            'become partner',
            'become a provider',
            'register as provider',
            'provider registration',
            'i am a provider',
            'i am provider',
            'looking for work',
            'want to work with you',
            'want to join',
            'onboard as',
        ];

        foreach ($needles as $n) {
            if (str_contains($blob, $n)) {
                return true;
            }
        }

        return false;
    }

    /**
     * "What services do you offer?" without naming a job they want done.
     */
    public static function looksLikeCatalogBrowseOnly(string $customerTextBlob): bool
    {
        $blob = mb_strtolower(trim($customerTextBlob), 'UTF-8');
        if ($blob === '') {
            return false;
        }

        $browse = [
            'what services',
            'which services',
            'kya services',
            'kon si service',
            'kon si services',
            'list of services',
            'services do you offer',
            'services you offer',
            'what do you offer',
            'kya kya service',
        ];

        $hitBrowse = false;
        foreach ($browse as $n) {
            if (str_contains($blob, $n)) {
                $hitBrowse = true;
                break;
            }
        }

        if (!$hitBrowse) {
            return false;
        }

        foreach (self::serviceNeedPatterns() as $pattern) {
            if (preg_match($pattern, $blob) === 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private static function serviceNeedPatterns(): array
    {
        return [
            '/\bdo\s+u(?:ou)?\s+provide\b/u',
            '/\b(?:need|want|book|booking)\b.{0,40}\b(?:service|technician|visit|wala|wale)\b/u',
            '/\b(?:electrician|plumber|plumbing|carpenter|painter|painting|mason|geyser|fridge|inverter|generator|cctv|pest|laundry|salon)\b/u',
            '/\b(?:ac|a\/c|air\s*condition(?:er|ing)?)\b/u',
            '/\b(?:ceiling\s*fan|fan\s+install|washing\s*machine|booster\s*pump|wooden\s*floor)/u',
            '/\b(?:install(?:ation)?|repair|wiring|switch|leak|tap|bathroom|plaster|mistary|mistry)\b/u',
            '/\b(?:chahiye|karwana|lagwana|lagao|theek\s+karo)\b/u',
        ];
    }
}
