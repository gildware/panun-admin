<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Understand what the customer means and map it to services Panun Kaergar actually offers.
 */
final class MobileAppAiServiceIntentResolver
{
    /**
     * @return array{
     *   customer_said: string,
     *   catalog_query: string,
     *   trade_id: string,
     *   trade_label: string,
     *   unsupported: string|null,
     *   fallback_queries: list<string>
     * }
     */
    public static function resolve(string $text): array
    {
        $raw = trim($text);
        $lower = mb_strtolower($raw);

        $unsupported = self::detectUnsupported($lower);
        if ($unsupported !== null) {
            return [
                'customer_said' => $raw,
                'catalog_query' => '',
                'trade_id' => '',
                'trade_label' => '',
                'unsupported' => $unsupported,
                'fallback_queries' => [],
            ];
        }

        $trade = self::matchTrade($lower);
        $catalogQuery = '';
        $fallbackQueries = [];
        $tradeId = '';
        $tradeLabel = '';

        if ($trade !== null) {
            $tradeId = (string) ($trade['id'] ?? '');
            $tradeLabel = (string) ($trade['label'] ?? '');
            $queries = is_array($trade['catalog_queries'] ?? null) ? $trade['catalog_queries'] : [];
            $catalogQuery = trim((string) ($queries[0] ?? ''));
            $fallbackQueries = array_values(array_unique(array_filter(array_map('strval', $queries))));
        }

        if ($catalogQuery === '') {
            $catalogQuery = MobileAppAiServiceQueryNormalizer::normalize($raw);
            $fallbackQueries = array_values(array_unique(array_filter([
                $catalogQuery,
                ...self::configFallbackQueries(),
            ])));
        } elseif (! in_array($catalogQuery, $fallbackQueries, true)) {
            array_unshift($fallbackQueries, $catalogQuery);
        }

        return [
            'customer_said' => $raw,
            'catalog_query' => $catalogQuery,
            'trade_id' => $tradeId,
            'trade_label' => $tradeLabel,
            'unsupported' => null,
            'fallback_queries' => $fallbackQueries,
        ];
    }

    public static function unsupportedMessage(string $customerSaid, string $unsupportedLabel): string
    {
        $samples = self::sampleOfferingsPhrase();

        return 'We don\'t offer **'.$unsupportedLabel.'** on Panun Kaergar.'
            .($samples !== '' ? ' We provide: '.$samples.'.' : '')
            .' Tell me which of these you need.';
    }

    public static function noCatalogMatchMessage(string $customerSaid, string $catalogQuery, string $tradeLabel): string
    {
        $samples = self::sampleOfferingsPhrase();

        if ($tradeLabel !== '' && $catalogQuery !== '') {
            return 'We offer **'.$tradeLabel.'**, but nothing matched your area for this request.'
                .' Try *'.$catalogQuery.'* from Home, or '.$samples.'.';
        }

        if ($catalogQuery !== '' && ! self::customerSaidLooksLikeQuery($customerSaid, $catalogQuery)) {
            return 'For *'.self::shortCustomerSaid($customerSaid).'*, try booking **'.$catalogQuery.'**'
                .($samples !== '' ? ' — or '.$samples : '').'.';
        }

        return 'We couldn\'t find **'.self::shortCustomerSaid($customerSaid).'** in your area.'
            .($samples !== '' ? ' We offer: '.$samples.'.' : '');
    }

    public static function matchedTradeAck(string $customerSaid, string $tradeLabel, string $catalogQuery): string
    {
        if ($tradeLabel === '' || self::customerSaidLooksLikeQuery($customerSaid, $catalogQuery)) {
            return '';
        }

        return 'Sounds like **'.$tradeLabel.'** — ';
    }

    /** Only unsupported requests skip triage (show "we don't offer" immediately). */
    public static function shouldSkipTriage(string $text): bool
    {
        return self::resolve($text)['unsupported'] !== null;
    }

    private static function detectUnsupported(string $lower): ?string
    {
        foreach (self::configUnsupported() as $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($row['signals'] ?? [] as $signal) {
                if ($signal !== '' && str_contains($lower, mb_strtolower((string) $signal))) {
                    return (string) ($row['label'] ?? 'that service');
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function signalMatchesText(string $lower, string $signal): bool
    {
        if ($signal === '') {
            return false;
        }

        if (mb_strlen($signal) <= 3) {
            return (bool) preg_match('/(?<!\p{L})'.preg_quote($signal, '/').'(?!\p{L})/u', $lower);
        }

        return str_contains($lower, $signal);
    }

    private static function matchTrade(string $lower): ?array
    {
        $best = null;
        $bestScore = 0;

        foreach (self::configTrades() as $trade) {
            if (! is_array($trade)) {
                continue;
            }
            $score = 0;
            foreach ($trade['signals'] ?? [] as $signal) {
                $sig = mb_strtolower((string) $signal);
                if ($sig !== '' && self::signalMatchesText($lower, $sig)) {
                    $score += mb_strlen($sig) >= 4 ? 2 : 1;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $trade;
            }
        }

        return $bestScore > 0 ? $best : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function configTrades(): array
    {
        $trades = config('mobile_app_ai_service_intent.trades', []);

        return is_array($trades) ? $trades : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function configUnsupported(): array
    {
        $rows = config('mobile_app_ai_service_intent.unsupported', []);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<string>
     */
    private static function configFallbackQueries(): array
    {
        $rows = config('mobile_app_ai_service_intent.fallback_catalog_queries', []);

        return is_array($rows) ? array_values(array_filter(array_map('strval', $rows))) : [];
    }

    private static function sampleOfferingsPhrase(): string
    {
        try {
            $stats = app(MobileAppAiCatalogSearchService::class)->catalogStatsSnapshot();
            $parts = [];
            foreach (array_slice($stats['category_summaries'] ?? [], 0, 6) as $line) {
                $name = trim((string) preg_replace('/\s*\(\d+\)\s*$/', '', (string) $line));
                if ($name !== '') {
                    $parts[] = '*'.$name.'*';
                }
            }
            if ($parts !== []) {
                return implode(', ', $parts);
            }
        } catch (\Throwable) {
            // ignore
        }

        return '*AC repair*, *plumbing*, *electrical*, *cleaning*';
    }

    private static function customerSaidLooksLikeQuery(string $customerSaid, string $catalogQuery): bool
    {
        return mb_strtolower(trim($customerSaid)) === mb_strtolower(trim($catalogQuery));
    }

    private static function shortCustomerSaid(string $text): string
    {
        $t = trim($text);
        if (mb_strlen($t) <= 48) {
            return $t;
        }

        return rtrim(mb_substr($t, 0, 45)).'…';
    }
}
