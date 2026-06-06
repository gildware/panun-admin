<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Parse free-text cart management requests (clear, remove items, reschedule).
 */
final class MobileAppAiCartRequestParser
{
    /**
     * @return array{op: string, target: string, schedule_text: string, cart_filter: string}|null
     */
    public static function parse(string $text): ?array
    {
        $text = MobileAppAiInputNormalizer::forMatching($text);
        $t = mb_strtolower(trim($text));
        if ($t === '' || mb_strlen($t) < 4) {
            return null;
        }

        $cartFilter = self::detectCartFilter($text);
        if ($cartFilter !== '' && preg_match('/\b(remove|delete|drop|cancel|hatao?|hata\s+do|nikal)\b/iu', $t)) {
            return [
                'op' => 'remove',
                'target' => '',
                'schedule_text' => '',
                'cart_filter' => $cartFilter,
            ];
        }

        if (MobileAppAiCartScheduleReply::looksLikeCartScheduleQuery($text)) {
            return null;
        }

        $scheduleText = self::extractSchedulePhrase($text);
        if ($scheduleText !== '' && self::looksLikeRescheduleChangeIntent($t)) {
            return [
                'op' => 'reschedule',
                'target' => self::extractRemoveTarget($text),
                'schedule_text' => $scheduleText,
                'cart_filter' => '',
            ];
        }

        if (self::looksLikeClearAll($t)) {
            return ['op' => 'clear_all', 'target' => '', 'schedule_text' => '', 'cart_filter' => ''];
        }

        if (self::looksLikeKeepOneDeleteRest($t)) {
            return [
                'op' => 'keep_one',
                'target' => self::extractScopeTarget($text),
                'schedule_text' => '',
                'cart_filter' => '',
            ];
        }

        if (self::looksLikeRemoveIntent($t)) {
            if (self::looksLikeKeepOneDeleteRest($t)) {
                return [
                    'op' => 'keep_one',
                    'target' => self::extractScopeTarget($text),
                    'schedule_text' => '',
                    'cart_filter' => '',
                ];
            }

            $keepTarget = self::extractKeepOnlyTarget($text);
            $removeTarget = self::extractRemoveTarget($text);
            $cartFilter = self::detectCartFilter($text);
            if ($keepTarget !== '' && $removeTarget === '' && $cartFilter === '') {
                return [
                    'op' => 'keep_only',
                    'target' => $keepTarget,
                    'schedule_text' => '',
                    'cart_filter' => '',
                ];
            }

            return [
                'op' => 'remove',
                'target' => $cartFilter !== '' ? '' : $removeTarget,
                'keep_target' => $keepTarget,
                'schedule_text' => '',
                'cart_filter' => $cartFilter,
            ];
        }

        if (self::looksLikeViewCart($t)) {
            return ['op' => 'view', 'target' => '', 'schedule_text' => '', 'cart_filter' => ''];
        }

        if (self::looksLikeKeepOnlyIntent($t)) {
            return [
                'op' => 'keep_only',
                'target' => self::extractKeepOnlyTarget($text),
                'schedule_text' => '',
                'cart_filter' => '',
            ];
        }

        return null;
    }

    public static function detectCartFilter(string $text): string
    {
        $t = mb_strtolower(MobileAppAiInputNormalizer::forMatching($text));

        if (preg_match(
            '/\b(?:past|old|expired|overdue|puran[ei]|purani|guzar|guzri|guzar\s+chuk|pehle\s+ki|'
            .'jo\s+past|past\s+date|date\s+guzar|pehle\s+wale|purane\s+date)\b/iu',
            $t
        ) && preg_match('/\b(remove|delete|drop|cancel|hata|nikal|clear)\b/iu', $t)) {
            return 'visit_before_now';
        }

        if (preg_match('/\b(?:future|upcoming|aane\s+wale|baad\s+ki|next)\b/iu', $t)
            && preg_match('/\b(remove|delete|drop|cancel|hata|nikal)\b/iu', $t)) {
            return 'visit_after_now';
        }

        if (preg_match('/\b(?:no\s+schedule|without\s+schedule|time\s+nahi|schedule\s+nahi)\b/iu', $t)
            && preg_match('/\b(remove|delete|drop|cancel|hata|nikal)\b/iu', $t)) {
            return 'no_schedule';
        }

        return '';
    }

    private static function looksLikeKeepOnlyIntent(string $text): bool
    {
        if (self::looksLikeKeepOneDeleteRest($text)) {
            return false;
        }

        if (preg_match('/\b(?:rakho|rakh)\s+(?:only\s+)?/iu', $text)
            && ! preg_match('/\b(?:remove|delete|drop|hata)\b/iu', $text)) {
            return true;
        }

        return (bool) preg_match('/\b(?:keep|leave)\s+(?:only\s+)?/iu', $text)
            && ! preg_match('/\b(?:remove|delete|drop)\b/iu', $text);
    }

    public static function looksLikeKeepOneDeleteRest(string $text): bool
    {
        $t = mb_strtolower(MobileAppAiInputNormalizer::forMatching($text));

        if (preg_match('/\b(?:koi\s*b(?:hi)?|any(?:\s+one)?)\s+(?:ek|one)\b/iu', $t)
            && preg_match('/\b(?:rakho|rakh|keep|leave)\b/iu', $t)
            && preg_match('/\b(?:baki|baaki|rest|others?|remaining)\b/iu', $t)) {
            return true;
        }

        if (self::hasKeepOneIntent($t)
            && preg_match('/\b(?:baki|baaki|sab|saare|rest|others?)\b.*\b(?:delete|remove|delte|hata|hatao|drop)\b/iu', $t)) {
            return true;
        }

        if (preg_match('/\b(?:baki|baaki|sab|saare|rest|others?)\b.*\b(?:delete|remove|delte|hata|hatao|drop)\b/iu', $t)
            && self::hasKeepOneIntent($t)) {
            return true;
        }

        if (preg_match('/\b(?:sab|saare|all)\b.*\b(?:delete|remove|delte|hata|hatao|drop)\b/iu', $t)
            && self::hasKeepOneIntent($t)) {
            return true;
        }

        return preg_match('/\b(?:keep|leave)\s+(?:any\s+)?(?:one|1)\b/iu', $t)
            && preg_match('/\b(?:delete|remove)\s+(?:the\s+)?(?:rest|others?|remaining)\b/iu', $t);
    }

    private static function hasKeepOneIntent(string $text): bool
    {
        return (bool) preg_match(
            '/\b(?:ek|one)\s+(?:hi\s+)?(?:\w+\s+){0,4}(?:rakho|rakh|keep|leave)\b/iu',
            $text
        );
    }

    public static function extractScopeTarget(string $text): string
    {
        if (preg_match(
            '/\b(ac|inverter|plumber|electrician|geyser|fridge|washing\s+machine|water\s+purifier|ro)\s+(?:ke|ki)\b/iu',
            $text,
            $m
        )) {
            return mb_strtolower(self::cleanTarget((string) ($m[1] ?? '')));
        }

        if (preg_match(
            '/\b(?:ek|one)\s+hi\s+(.+?)\s+(?:rakho|rakh|keep|leave)\b/iu',
            $text,
            $m
        )) {
            $scope = self::cleanTarget((string) ($m[1] ?? ''));

            return $scope !== '' && ! self::isVerbParticle($scope) ? mb_strtolower($scope) : '';
        }

        if (preg_match(
            '/\b(ac|inverter|plumber|electrician|geyser|fridge|washing\s+machine|water\s+purifier|ro)\s+(?:repair|installation|service)\b/iu',
            $text,
            $m
        )) {
            return mb_strtolower(self::cleanTarget((string) ($m[0] ?? '')));
        }

        if (preg_match(
            '/\b(?:sab|saare|all)\s+(.+?)\s+(?:delete|remove|delte|hata|hatao|drop)\b/iu',
            $text,
            $m
        )) {
            $scope = self::cleanTarget((string) ($m[1] ?? ''));

            return $scope !== '' && ! self::isVerbParticle($scope) ? $scope : '';
        }

        return '';
    }

    private static function isVerbParticle(string $word): bool
    {
        return (bool) preg_match('/^(karo|kar|do|de|dena|rakho|rakh|baki|baaki|ek|hi|koi|bhi|sab|saare|wahan|yahan|wahaan|yahaan|se|ko|wali|wala|wale)$/iu', $word);
    }

    public static function looksLikeViewCart(string $text): bool
    {
        $t = mb_strtolower(MobileAppAiInputNormalizer::forMatching($text));

        if (preg_match(
            '/\b((?:show|see|view|check|list|tell\s+me)\s+(?:what\s+)?(?:is\s+)?(?:in\s+)?(?:my\s+)?cart|'
            .'(?:what(?:\'?s|s| is| are)|which)\s+(?:items?|services?)?\s*(?:are\s+)?(?:in\s+)?(?:my\s+)?cart|'
            .'my\s+cart\s+(?:items|list|summary|contents?)|cart\s+(?:items|list|summary|contents?))\b/iu',
            $t
        )) {
            return true;
        }

        if (preg_match('/\b(?:cart|basket)\b/iu', $t)
            && preg_match('/\b(what|which|tell|list|show|see|view|check|anything|contents?|items?|have|got)\b/iu', $t)
            && ! preg_match('/\b(remove|delete|clear|empty|add|apply\s+coupon)\b/iu', $t)) {
            return true;
        }

        // Roman Urdu / Hinglish view-cart phrases (no destructive verbs).
        if (! preg_match('/\b(remove|delete|clear|empty|hatao|hata|nikalo|nikal)\b/iu', $t)) {
            if (preg_match('/\b(?:cart|basket)\s+mein\s+(?:kya|kaun|kaunsi|kitni)\b/iu', $t)) {
                return true;
            }
            if (preg_match('/\b(?:mera|mere)\s+cart\b/iu', $t)
                && preg_match('/\b(?:kya|dikhao|dekhao|batao|bata|hai)\b/iu', $t)) {
                return true;
            }
            if (preg_match('/\b(?:cart|basket)\s+(?:dikhao|dekhao|batao|bata|dikha)\b/iu', $t)) {
                return true;
            }
            if (preg_match('/\bkya\s+hai\s+(?:cart|basket)\b/iu', $t)) {
                return true;
            }
        }

        return false;
    }

    private static function looksLikeClearAll(string $text): bool
    {
        if (! str_contains($text, 'cart')) {
            return (bool) preg_match('/\b(clear|empty|delete|remove|wipe)\s+(?:my\s+)?(?:whole\s+|all\s+|entire\s+)?(?:cart|basket)\b/iu', $text)
                || (bool) preg_match('/\b(clear|empty)\s+(?:everything|all)\b/iu', $text);
        }

        return (bool) preg_match(
            '/\b(clear|empty|delete|remove|wipe)\s+(?:my\s+|the\s+|whole\s+|all\s+|entire\s+)*(?:cart|basket)\b/iu',
            $text
        ) || (bool) preg_match('/\b(clear|empty)\s+(?:my\s+)?all\s+cart\b/iu', $text);
    }

    private static function looksLikeRemoveIntent(string $text): bool
    {
        if (! preg_match('/\b(remove|delete|drop|cancel|hatao?|hata\s+do|nikal)\b/iu', $text)) {
            return false;
        }

        if (self::looksLikeClearAll($text)) {
            return false;
        }

        return str_contains($text, 'cart')
            || self::extractRemoveTarget($text) !== ''
            || self::detectCartFilter($text) !== ''
            || (bool) preg_match('/\b(remove|delete|hatao?|hata\s+do|nikal)\s+(?:karo|do|de|dena)\b/iu', $text)
            || (bool) preg_match('/\b(remove|delete)\s+(?:this|that|it)\b/iu', $text)
            || (bool) preg_match('/\s+ko\s+(?:hatao?|hata\s+do|nikal)\b/iu', $text);
    }

    public static function looksLikeRescheduleChangeIntent(string $text): bool
    {
        return (bool) preg_match(
            '/\b(change|reschedule|update|move|shift|postpone|set)\b.*\b(date|time|schedule|visit|slot|appointment)\b/iu',
            $text
        ) || (bool) preg_match(
            '/\b(date|time|schedule|visit)\b.*\b(change|reschedule|update|to)\b/iu',
            $text
        );
    }

    private static function extractRemoveTarget(string $text): string
    {
        // Hinglish / Roman Urdu: "{service} ko hatao" (service before verb).
        $hinglishPatterns = [
            '/^(.+?)\s+ko\s+(?:hatao?|hata\s+do|nikal(?:o|na|do)?|remove|delete|drop)\s*(?:karo|kar|do|de|dena)?\s*$/iu',
            '/^(.+?)\s+(?:wali|wala|wale)\s+(?:delete|remove|hatao?|hata\s+do|nikal)\s*(?:karo|kar|do|de|dena)?\s*$/iu',
            '/^(.+?)\s+(?:wali|wala|wale)\s+(?:service|repair)\s+ko\s+(?:remove|delete|hatao?|hata\s+do|nikal)\s*(?:karo|kar|do|de|dena)?\s*$/iu',
            '/\b(?:cart|basket)\s+se\s+(.+?)\s+(?:ko\s+)?(?:hatao?|hata\s+do|nikal|remove|delete)\b/iu',
            '/^(.+?)\s+(?:hatao?|hata\s+do|nikal)\s*(?:wahan|yahan|cart|wahaan|yahaan)?\s*(?:se)?\s*$/iu',
        ];
        foreach ($hinglishPatterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $cleaned = self::cleanTarget((string) ($m[1] ?? ''));
                if ($cleaned !== '' && ! self::isVerbParticle($cleaned)) {
                    return $cleaned;
                }
            }
        }

        if (preg_match(
            '/\b(?:remove|delete|drop|cancel)\s+(?:the\s+)?(.+?)\s+(?:and\s+)?(?:but\s+)?(?:keep|leave)\s+(?:only\s+)?/iu',
            $text,
            $m
        )) {
            return self::cleanTarget((string) ($m[1] ?? ''));
        }

        $patterns = [
            '/\b(?:remove|delete|drop|cancel)\s+(?:the\s+)?(.+?)\s+(?:from\s+)?(?:my\s+)?cart\b/iu',
            '/\b(?:remove|delete)\s+(?:all\s+)?(.+?)\s+(?:items?|lines?|entries?)\b/iu',
            '/\b(?:remove|delete)\s+(.+?)\s+from\s+cart\b/iu',
            '/\b(?:remove|delete)\s+(?:everything|all)\s+except\s+(.+?)(?:\s+from\s+cart)?$/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return self::cleanTarget((string) ($m[1] ?? ''));
            }
        }

        if (preg_match('/\b(?:remove|delete|drop|hatao?|hata\s+do|nikal)\s+(.+)$/iu', $text, $m)) {
            $raw = (string) ($m[1] ?? '');
            $raw = (string) preg_replace(
                '/\s+(?:and\s+)?(?:but\s+)?(?:keep|leave|rakho|rakh)\s+(?:only\s+)?.+$/iu',
                '',
                $raw
            );
            $raw = (string) preg_replace('/\s+(?:wahan|yahan|wahaan|yahaan)\s*(?:se)?\s*$/iu', '', $raw);

            $cleaned = self::cleanTarget($raw);

            return self::isVerbParticle($cleaned) ? '' : $cleaned;
        }

        return '';
    }

    private static function extractKeepOnlyTarget(string $text): string
    {
        if (preg_match(
            '/\b(?:and\s+)?(?:but\s+)?(?:keep|leave|rakho|rakh)\s+(?:only\s+)?(?:the\s+)?(.+?)(?:\s+from\s+cart|\s+in\s+(?:my\s+)?cart)?$/iu',
            $text,
            $m
        )) {
            $cleaned = self::cleanTarget((string) ($m[1] ?? ''));

            return self::isVerbParticle($cleaned) ? '' : $cleaned;
        }

        if (preg_match('/\b(?:keep|leave|rakho|rakh)\s+(?:only\s+)?(?:the\s+)?(.+)$/iu', $text, $m)) {
            $cleaned = self::cleanTarget((string) ($m[1] ?? ''));

            return self::isVerbParticle($cleaned) ? '' : $cleaned;
        }

        return '';
    }

    private static function cleanTarget(string $target): string
    {
        $target = trim((string) preg_replace('/\s+/', ' ', $target));
        $target = (string) preg_replace(
            '/\b(please|pls|from|my|the|all|items?|lines?|cart|basket|services?|one|ones|'
            .'karo|kar|do|de|dena|rakho|rakh|baki|baaki|delete|remove|hata|hatao|nikal|sab|saare|ek|hi|koi|bhi|mein|ko|'
            .'wali|wala|wale|walee|service|services|'
            .'wahan|yahan|wahaan|yahaan|se|there|wahan\s+se|yahan\s+se)\b/iu',
            ' ',
            $target
        );
        $target = trim((string) preg_replace('/\s+/', ' ', $target));

        if ($target === '' || preg_match('/^(all|everything|whole)$/iu', $target)) {
            return '';
        }

        return $target;
    }

    private static function extractSchedulePhrase(string $text): string
    {
        $patterns = [
            '/\b(?:to|for|on|at)\s+(.+)$/iu',
            '/\b(?:change|reschedule|update|move)\s+(?:the\s+)?(?:date|time|schedule|visit)\s+(?:to|for|on|at)?\s*(.+)$/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $phrase = trim((string) ($m[1] ?? ''));
                if ($phrase !== '' && MobileAppAiSchedulePhraseParser::looksLikeSchedulePhrase($phrase)) {
                    return $phrase;
                }
            }
        }

        if (MobileAppAiSchedulePhraseParser::looksLikeSchedulePhrase($text)) {
            return trim($text);
        }

        return '';
    }
}
