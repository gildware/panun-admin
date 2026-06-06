<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Crisp, focused customer replies — ask when unclear; answer only what was asked.
 */
final class MobileAppAiReplyStyle
{
    public const MAX_TIP_LINES = 2;

    public const MAX_REPLY_CHARS = 420;

    /**
     * Lines appended to Gemini system prompt.
     */
    public static function brevityRules(): string
    {
        return 'Reply style: **very short** (1–3 sentences, under 60 words when possible). '
            .'If the customer is vague, ask **one** clarifying question — do not list many tips yet. '
            .'When clear, answer **only** what they asked; max 2 bullet tips if troubleshooting. '
            .'No repetition, no internal jargon, no long lectures.';
    }

    /**
     * Issue description is too thin to give targeted advice.
     */
    public static function isVagueIssue(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '' || mb_strlen($t) < 8) {
            return true;
        }

        if (preg_match('/^(yes|no|ok|okay|haan|ha|theek|help|hi|hello|ac|repair|service|book)\.?$/iu', $t)) {
            return true;
        }

        if (preg_match(
            '/\b(not\s+cool|no\s+cool|leak|leaking|noise|sound|smell|spark|trip|broken|band|nahi|'
            .'cooling|heating|water|gas|start|chalu|kharab|problem|issue|fix|drip|block|pressure|'
            .'error|display|wiring|socket|tap|drain|refill|theak|theek|thik)\b/iu',
            $t
        )) {
            return false;
        }

        return mb_strlen($t) < 18;
    }

    /**
     * @param  list<string>  $lines
     */
    public static function formatTipLines(array $lines, int $max = self::MAX_TIP_LINES): string
    {
        $out = [];
        foreach (array_slice($lines, 0, $max) as $line) {
            $line = self::shorten(trim($line), 95);
            if ($line !== '') {
                $out[] = '• '.$line;
            }
        }

        return $out === [] ? '' : implode("\n", $out);
    }

    public static function shorten(string $text, int $maxLen = 95): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        if (mb_strlen($text) <= $maxLen) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $maxLen - 1)).'…';
    }

    public static function clampReply(string $reply): string
    {
        $reply = trim($reply);
        if (mb_strlen($reply) <= self::MAX_REPLY_CHARS) {
            return $reply;
        }

        return rtrim(mb_substr($reply, 0, self::MAX_REPLY_CHARS - 3)).'…';
    }

    /**
     * Strip internal tool/API names that must never appear in customer chat.
     */
    public static function sanitizeCustomerFacing(string $reply): string
    {
        $reply = trim($reply);
        if ($reply === '') {
            return '';
        }

        $patterns = [
            '/\bI am calling the\s+[`\']?[\w_]+[`\']?\s+tool[^.]*\.?\s*/iu',
            '/\bI(?:\'m| am) (?:calling|using|invoking)\s+(?:the\s+)?[`\']?[\w_]+[`\']?[^.]*\.?\s*/iu',
            '/\b(?:calling|using|invoking)\s+(?:the\s+)?[`\']?(?:manage_customer_cart|manage_app_booking|get_customer_cart_summary|list_my_system_bookings)[`\']?[^.]*\.?\s*/iu',
            '/[`\'](?:manage_customer_cart|manage_app_booking|get_customer_cart_summary|list_my_system_bookings|add_service_to_customer_cart)[`\']/iu',
            '/\b(?:manage_customer_cart|manage_app_booking|get_customer_cart_summary|list_my_system_bookings|add_service_to_customer_cart)\b/iu',
            '/\bfunction(?:Call|Response)\b/iu',
        ];

        foreach ($patterns as $pattern) {
            $reply = (string) preg_replace($pattern, '', $reply);
        }

        $reply = trim((string) preg_replace('/\s{2,}/', ' ', $reply));

        return self::clampReply($reply);
    }

    public static function prefersHinglish(string $text): bool
    {
        if (preg_match('/[\x{0900}-\x{097F}]/u', $text)) {
            return true;
        }

        return (bool) preg_match(
            '/\b(karo|kar|hai|hain|mera|meri|unko|isko|chahiye|wala|wali|subah|sham|kal|aaj|nahi|haan|theek|dikhao|hata|hatao|nikal|batao|bata|kya|jo|ki|se|mein|mujhe|mujhay)\b/iu',
            $text
        );
    }

    /**
     * Dynamic Gemini appendix — match the customer's last message language.
     */
    public static function localize(string $english, string $hinglish, string $userText): string
    {
        return self::clampReply(self::prefersHinglish($userText) ? $hinglish : $english);
    }

    public static function languageAppendixForText(string $text): string
    {
        if (self::prefersHinglish($text)) {
            return '## Language for this turn'
                ."\nThe customer's last message is **Roman Hinglish / Roman Urdu**. Reply in the **same style** (mix Hindi/Urdu words in Roman script with English as they do). Do not switch to formal English only.";
        }

        return '## Language for this turn'
            ."\nThe customer's last message is **English**. Reply in clear, natural **English** (same tone as their message).";
    }
}
