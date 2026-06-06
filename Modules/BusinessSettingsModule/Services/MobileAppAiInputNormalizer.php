<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Normalize mobile keyboard punctuation so rule-based matchers work reliably.
 */
final class MobileAppAiInputNormalizer
{
    public static function forMatching(string $text): string
    {
        $text = str_replace(
            ["\u{2019}", "\u{2018}", "\u{2032}", "\u{00B4}"],
            "'",
            $text
        );
        $text = str_replace(
            ["\u{201C}", "\u{201D}"],
            '"',
            $text
        );

        $text = self::splitGluedHinglish($text);

        return trim($text);
    }

    /**
     * Mobile users often glue particles: "serviceko" → "service ko", "waleko" → "wale ko".
     */
    private static function splitGluedHinglish(string $text): string
    {
        $text = (string) preg_replace(
            '/\b(service|repair|installation|item|line|cart|booking)(ko|ke|ki|se|mein)\b/iu',
            '$1 $2',
            $text
        );
        $text = (string) preg_replace(
            '/\b(\w+)(wali|wala|wale)(ko|ke|ki)\b/iu',
            '$1 $2 $3',
            $text
        );

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }
}
