<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Pick the best catalog row for what the customer described — avoid false positives (e.g. "water" → Geyser).
 */
final class MobileAppAiCatalogServiceMatcher
{
    /** @var list<string> */
    private const GENERIC_TOKENS = [
        'water', 'tap', 'leak', 'leaking', 'repair', 'service', 'installation', 'install', 'fix',
        'home', 'the', 'and', 'for', 'with',
    ];

    /** @var array<string, list<string>> */
    private const TRADE_SERVICE_BOOSTS = [
        'plumbing' => [
            'leak', 'leakage', 'tap', 'mixer', 'pipe', 'drain', 'blockage', 'geyser', 'toilet', 'bathroom',
            'pressure', 'fitting', 'washroom', 'plumb',
        ],
        'electrical' => ['electric', 'wiring', 'wire', 'socket', 'switch', 'mcb', 'breaker', 'light', 'fan'],
        'ac' => ['ac', 'air', 'condition', 'cooling', 'split', 'compressor'],
        'appliance' => ['fridge', 'refrigerator', 'washing', 'machine', 'ro', 'tv', 'microwave', 'oven'],
        'cleaning' => ['clean', 'cleaning', 'deep', 'dust', 'maid'],
    ];

    /** @var array<string, list<string>> */
    private const TRADE_SERVICE_PENALTIES = [
        'plumbing' => ['washing machine', 'cctv', 'salon', 'paint', 'carpent', 'pest'],
    ];

    /**
     * @param  list<array<string, mixed>>  $options
     * @return array<string, mixed>|null
     */
    public static function pickBest(array $options, string $customerText, ?array $intent = null): ?array
    {
        if ($options === []) {
            return null;
        }

        $intent ??= MobileAppAiServiceIntentResolver::resolve($customerText);
        $scores = [];
        foreach ($options as $i => $option) {
            $scores[$i] = self::scoreOption($option, $customerText, $intent);
        }

        arsort($scores);
        $indices = array_keys($scores);
        $bestIdx = $indices[0];
        $bestScore = $scores[$bestIdx];
        $secondScore = $scores[$indices[1] ?? $bestIdx] ?? 0;

        if ($bestScore < 6) {
            return null;
        }
        if (count($options) > 1 && ($bestScore - $secondScore) < 2) {
            $tieA = self::tieBreakScore($options[$bestIdx], $customerText);
            $tieB = self::tieBreakScore($options[$indices[1]], $customerText);
            if ($tieA <= $tieB) {
                return null;
            }
        }

        return $options[$bestIdx];
    }

    /**
     * @param  array<string, mixed>  $option
     */
    private static function tieBreakScore(array $option, string $customerText): int
    {
        $name = mb_strtolower(trim((string) ($option['name'] ?? '')));
        $customer = mb_strtolower(trim($customerText));
        $bonus = 0;
        if (str_contains($customer, 'leak') && str_contains($name, 'leak')) {
            $bonus += 4;
        }
        if (str_contains($customer, 'tap') && str_contains($name, 'tap')) {
            $bonus += 4;
        }
        if (str_contains($name, 'geyser') && ! str_contains($customer, 'geyser')) {
            $bonus -= 6;
        }

        return $bonus;
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @return list<array<string, mixed>>
     */
    public static function rankTop(array $options, string $customerText, ?array $intent = null, int $limit = 3): array
    {
        if ($options === []) {
            return [];
        }

        $intent ??= MobileAppAiServiceIntentResolver::resolve($customerText);
        $scored = [];
        foreach ($options as $i => $option) {
            $scored[] = ['i' => $i, 'score' => self::scoreOption($option, $customerText, $intent)];
        }
        usort($scored, static function ($a, $b) use ($options, $customerText, $intent) {
            if ($b['score'] !== $a['score']) {
                return $b['score'] <=> $a['score'];
            }

            return self::tieBreakScore($options[$b['i']], $customerText)
                <=> self::tieBreakScore($options[$a['i']], $customerText);
        });

        $out = [];
        foreach (array_slice($scored, 0, max(1, $limit)) as $row) {
            if ($row['score'] > 0) {
                $out[] = $options[$row['i']];
            }
        }

        return $out !== [] ? $out : array_slice($options, 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $option
     * @param  array<string, mixed>  $intent
     */
    public static function scoreOption(array $option, string $customerText, array $intent): int
    {
        $name = mb_strtolower(trim((string) ($option['name'] ?? '')));
        $customer = mb_strtolower(trim($customerText));
        if ($name === '' || $customer === '') {
            return 0;
        }

        $score = 0;

        if ($name === $customer) {
            return 20;
        }

        if (str_contains($customer, $name)) {
            $score += 12;
        } elseif (str_contains($name, $customer)) {
            $score += 10;
        }

        $customerTokens = self::meaningfulTokens($customer);
        $nameTokens = self::meaningfulTokens($name);
        foreach ($customerTokens as $token) {
            if (in_array($token, $nameTokens, true)) {
                $score += mb_strlen($token) >= 5 ? 4 : 2;
            } elseif (str_contains($name, $token) && mb_strlen($token) >= 4) {
                $score += 3;
            }
        }

        $tradeId = (string) ($intent['trade_id'] ?? '');
        if ($tradeId !== '') {
            foreach (self::TRADE_SERVICE_BOOSTS[$tradeId] ?? [] as $boost) {
                if ($boost !== '' && str_contains($name, $boost)) {
                    $score += 3;
                }
            }
            foreach (self::TRADE_SERVICE_PENALTIES[$tradeId] ?? [] as $penalty) {
                if ($penalty !== '' && str_contains($name, $penalty)) {
                    $score -= 4;
                }
            }
        }

        if (str_contains($customer, 'leak') && str_contains($name, 'leak')) {
            $score += 5;
        }
        if (str_contains($customer, 'tap') && str_contains($name, 'tap')) {
            $score += 5;
        }
        if (str_contains($customer, 'geyser') && str_contains($name, 'geyser')) {
            $score += 5;
        }
        if (str_contains($customer, 'geyser') === false && str_contains($name, 'geyser')) {
            $score -= 3;
        }

        return max(0, $score);
    }

    /**
     * @return list<string>
     */
    private static function meaningfulTokens(string $text): array
    {
        $parts = preg_split('/[^a-z0-9]+/u', mb_strtolower($text)) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '' || mb_strlen($part) < 3) {
                continue;
            }
            if (in_array($part, self::GENERIC_TOKENS, true) && mb_strlen($part) < 5) {
                continue;
            }
            $out[] = $part;
        }

        return array_values(array_unique($out));
    }
}
