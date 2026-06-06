<?php

namespace Modules\AdminModule\Services;

use Illuminate\Support\Facades\Log;
use Modules\WhatsAppModule\Services\WhatsAppGeminiSupportClient;

class AdminBusinessAiGeminiRunner
{
    private const RETRYABLE_REASONS = [
        'no_parts',
        'no_candidate',
        'finish_MALFORMED_FUNCTION_CALL',
        'finish_UNEXPECTED_TOOL_CALL',
        'finish_RECITATION',
        'finish_OTHER',
    ];

    public function __construct(
        protected WhatsAppGeminiSupportClient $gemini,
        protected AdminBusinessAiToolExecutor $toolExecutor,
        protected AdminBusinessAiSessionService $session,
        protected AdminBusinessAiQuestionRouter $questionRouter,
    ) {}

    /**
     * @return array{ok: bool, reply?: string, error?: string}
     */
    public function chat(int $adminUserId, string $userMessage): array
    {
        if (! config('admin_business_ai.enabled', true)) {
            return ['ok' => false, 'error' => __('admin_business_ai.disabled')];
        }

        if ((string) config('services.gemini.api_key') === '') {
            return ['ok' => false, 'error' => __('admin_business_ai.missing_api_key')];
        }

        $text = trim($userMessage);
        if ($text === '') {
            return ['ok' => false, 'error' => __('admin_business_ai.empty_message')];
        }

        $this->session->append($adminUserId, 'user', $text);

        try {
            $result = $this->runTurnLoop($adminUserId);
            if (! ($result['ok'] ?? false)) {
                $this->session->popLast($adminUserId);
            }

            return $result;
        } catch (\Throwable $e) {
            $this->session->popLast($adminUserId);
            Log::error('Admin business AI exception', [
                'admin_id' => $adminUserId,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => __('admin_business_ai.gemini_exception')];
        }
    }

    /**
     * @return array{ok: bool, reply?: string, error?: string}
     */
    private function runTurnLoop(int $adminUserId): array
    {
        $userMessage = $this->lastUserMessageText($adminUserId);
        $system = $this->buildSystemPrompt();
        $contents = $this->buildGeminiContents($adminUserId);
        $tools = AdminBusinessAiToolExecutor::functionDeclarations();
        $model = (string) config('admin_business_ai.gemini_model', 'gemini-2.5-flash');
        $maxRounds = (int) config('admin_business_ai.max_tool_rounds', 8);
        $maxOutTokens = (int) config('admin_business_ai.max_output_tokens', 2048);
        $httpTimeout = (int) config('admin_business_ai.gemini_http_timeout', 90);
        $blockedRetries = 0;
        $hadToolResults = false;
        $forceTextOnly = false;
        $serverToolFallbackUsed = false;
        /** @var list<array{name: string, result: array<string, mixed>}> $toolResultsBag */
        $toolResultsBag = [];

        $iter = 0;
        while ($iter < $maxRounds) {
            $iter++;
            $activeTools = $forceTextOnly ? [] : $tools;
            $turn = $this->gemini->generateTurn($system, $contents, $activeTools, null, $model, $maxOutTokens, $httpTimeout);

            if ($turn['type'] === 'blocked') {
                $reason = (string) ($turn['reason'] ?? 'blocked');
                Log::warning('Admin business AI blocked', [
                    'reason' => $reason,
                    'admin_id' => $adminUserId,
                    'iter' => $iter,
                    'had_tool_results' => $hadToolResults,
                    'force_text_only' => $forceTextOnly,
                ]);

                if (! $serverToolFallbackUsed && ! $hadToolResults && $this->shouldUseServerToolFallback($userMessage)) {
                    if ($this->injectServerToolFallback($userMessage, $contents, $toolResultsBag)) {
                        $serverToolFallbackUsed = true;
                        $hadToolResults = true;
                        $forceTextOnly = true;

                        continue;
                    }
                }

                if ($hadToolResults && ! $forceTextOnly) {
                    $forceTextOnly = true;
                    $contents[] = [
                        'role' => 'user',
                        'parts' => [['text' => $this->synthesisNudge($userMessage)]],
                    ];

                    continue;
                }

                $maxBlockedRetries = $hadToolResults ? 4 : 3;
                if ($blockedRetries < $maxBlockedRetries && $this->isRetryableBlocked($reason)) {
                    $blockedRetries++;
                    $contents[] = [
                        'role' => 'user',
                        'parts' => [[
                            'text' => $hadToolResults
                                ? $this->synthesisNudge($userMessage)
                                : 'Call one relevant tool (e.g. get_business_reports with booking_analytics for area/zone questions), then answer in plain text with markdown headings.',
                        ]],
                    ];

                    continue;
                }

                $fallback = $this->buildDeterministicFallback($toolResultsBag, $userMessage);
                if ($fallback !== '') {
                    $this->session->append($adminUserId, 'model', $fallback);

                    return ['ok' => true, 'reply' => $fallback];
                }

                return ['ok' => false, 'error' => $this->blockedErrorMessage($reason)];
            }

            if ($turn['type'] === 'text') {
                $reply = trim((string) ($turn['text'] ?? ''));
                if ($reply === '') {
                    if (! $serverToolFallbackUsed && ! $hadToolResults && $this->shouldUseServerToolFallback($userMessage)) {
                        if ($this->injectServerToolFallback($userMessage, $contents, $toolResultsBag)) {
                            $serverToolFallbackUsed = true;
                            $hadToolResults = true;
                            $forceTextOnly = true;

                            continue;
                        }
                    }

                    if ($iter === 1 && ! $forceTextOnly && $activeTools !== []) {
                        $contents[] = [
                            'role' => 'user',
                            'parts' => [[
                                'text' => 'Call the most relevant tool now (for area/zone booking questions use get_business_reports with report_type booking_analytics). Do not reply with text only yet.',
                            ]],
                        ];

                        continue;
                    }

                    if ($hadToolResults && ! $forceTextOnly) {
                        $forceTextOnly = true;
                        $contents[] = [
                            'role' => 'user',
                            'parts' => [['text' => $this->synthesisNudge($userMessage)]],
                        ];

                        continue;
                    }

                    if ($blockedRetries < 4) {
                        $blockedRetries++;
                        $contents[] = [
                            'role' => 'user',
                            'parts' => [['text' => $this->synthesisNudge($userMessage)]],
                        ];

                        continue;
                    }

                    $fallback = $this->buildDeterministicFallback($toolResultsBag, $userMessage);
                    if ($fallback !== '') {
                        $this->session->append($adminUserId, 'model', $fallback);

                        return ['ok' => true, 'reply' => $fallback];
                    }

                    return ['ok' => false, 'error' => __('admin_business_ai.empty_reply')];
                }
                if (! $hadToolResults
                    && $this->questionRouter->mentionsCoreDomain($userMessage)
                    && ! $serverToolFallbackUsed) {
                    if ($this->injectServerToolFallback($userMessage, $contents, $toolResultsBag)) {
                        $serverToolFallbackUsed = true;
                        $hadToolResults = true;
                        $forceTextOnly = true;

                        continue;
                    }
                }

                if ($hadToolResults && $this->looksLikeDataRefusal($reply)) {
                    $fallback = $this->buildDeterministicFallback($toolResultsBag, $userMessage);
                    if ($fallback !== '') {
                        $this->session->append($adminUserId, 'model', $fallback);

                        return ['ok' => true, 'reply' => $fallback];
                    }
                }

                $this->session->append($adminUserId, 'model', $reply);

                return ['ok' => true, 'reply' => $reply];
            }

            if ($turn['type'] !== 'function_calls') {
                break;
            }

            $modelParts = [];
            foreach ($turn['calls'] as $c) {
                $modelParts[] = [
                    'functionCall' => [
                        'name' => $c['name'],
                        'args' => (object) ($c['args'] ?? []),
                    ],
                ];
            }
            $contents[] = ['role' => 'model', 'parts' => $modelParts];

            $userParts = [];
            foreach ($turn['calls'] as $c) {
                $args = $c['args'] ?? [];
                if ($args instanceof \stdClass) {
                    $args = json_decode(json_encode($args), true) ?: [];
                }
                if (! is_array($args)) {
                    $args = [];
                }
                $result = $this->compactToolResult(
                    $this->toolExecutor->execute((string) $c['name'], $args)
                );
                $toolResultsBag[] = ['name' => (string) $c['name'], 'result' => $result];
                $userParts[] = [
                    'functionResponse' => [
                        'name' => $c['name'],
                        'response' => $result,
                    ],
                ];
            }
            $contents[] = ['role' => 'user', 'parts' => $userParts];
            $hadToolResults = true;
            $forceTextOnly = true;
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $this->synthesisNudge($userMessage)]],
            ];
        }

        $fallback = $this->buildDeterministicFallback($toolResultsBag, $userMessage);
        if ($fallback !== '') {
            $this->session->append($adminUserId, 'model', $fallback);

            return ['ok' => true, 'reply' => $fallback];
        }

        if (! $serverToolFallbackUsed && trim($userMessage) !== '') {
            $exploreBag = [];
            if ($this->injectServerToolFallback($userMessage, $contents, $exploreBag)) {
                $ultimate = $this->buildDeterministicFallback($exploreBag, $userMessage);
                if ($ultimate !== '') {
                    $this->session->append($adminUserId, 'model', $ultimate);

                    return ['ok' => true, 'reply' => $ultimate];
                }
            }
        }

        return ['ok' => false, 'error' => __('admin_business_ai.tool_rounds_exceeded')];
    }

    private function lastUserMessageText(int $adminUserId): string
    {
        $messages = $this->session->messages($adminUserId);
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                return trim((string) ($messages[$i]['text'] ?? ''));
            }
        }

        return '';
    }

    private function synthesisNudge(string $userMessage): string
    {
        $question = $userMessage !== '' ? $userMessage : 'the admin question';

        return 'Using ONLY the tool results above, answer: "'.$question.'". '
            .'The data is live from the database right now. You MUST answer from the tool payloads — forbidden phrases: "I don\'t have that information", "data is not available", "I cannot determine", "the report does not contain", "not in my tools", "I don\'t have access". '
            .'For categories, services, bookings, leads, customers, and providers you ALWAYS have live data when ok:true was returned. '
            .'If a count is 0 or a list is empty, state that explicitly (e.g. "0 cancelled leads") instead of claiming missing data. '
            .'Reply in markdown with ## headings. For focused questions, ## Executive Summary, ## Key Metrics, and a short ## Detailed Analysis are enough. '
            .'Do not call any more tools — write the final answer now.';
    }

    private function shouldUseServerToolFallback(string $userMessage): bool
    {
        $text = trim($userMessage);
        if ($text === '') {
            return false;
        }

        if ($this->questionRouter->mentionsCoreDomain($text)) {
            return true;
        }

        if ($this->questionRouter->inferToolsForQuestion($text) !== []) {
            return true;
        }

        return str_word_count($text) >= 2;
    }

    private function looksLikeDataRefusal(string $reply): bool
    {
        if ($reply === '') {
            return false;
        }

        return (bool) preg_match(
            '/\b(don\'?t have (?:that |this )?(?:information|data|access)|do not have (?:that |this )?(?:information|data|access)|'
            .'cannot determine|can\'?t determine|not available|no (?:data|information) (?:available|found)|'
            .'report does not contain|outside (?:my|the) (?:tools|scope)|'
            .'i(?:\'m| am) unable to|unable to (?:find|retrieve|access)|'
            .'not in (?:my|the) (?:tools|dataset|database))\b/i',
            $reply
        );
    }

    /**
     * @param  list<array<string, mixed>>  $contents
     * @param  list<array{name: string, result: array<string, mixed>}>  $toolResultsBag
     */
    private function injectServerToolFallback(string $userMessage, array &$contents, array &$toolResultsBag): bool
    {
        $name = 'explore_business_data';
        $args = ['question' => $userMessage];
        $result = $this->compactToolResult($this->toolExecutor->execute($name, $args));
        if (! ($result['ok'] ?? false)) {
            $maxTools = (int) config('admin_business_ai.max_explore_tools', 6);
            $planned = $this->questionRouter->inferToolsForQuestion($userMessage, $maxTools);
            if ($planned === []) {
                $planned = $this->questionRouter->defaultDiscoveryBundle();
            }

            $modelParts = [];
            $userParts = [];
            foreach ($planned as $plan) {
                $toolName = (string) $plan['name'];
                $toolArgs = is_array($plan['args'] ?? null) ? $plan['args'] : [];
                $modelParts[] = [
                    'functionCall' => [
                        'name' => $toolName,
                        'args' => (object) $toolArgs,
                    ],
                ];
                $toolResult = $this->compactToolResult($this->toolExecutor->execute($toolName, $toolArgs));
                $toolResultsBag[] = ['name' => $toolName, 'result' => $toolResult];
                $userParts[] = [
                    'functionResponse' => [
                        'name' => $toolName,
                        'response' => $toolResult,
                    ],
                ];
            }

            if ($modelParts === []) {
                return false;
            }

            $contents[] = ['role' => 'model', 'parts' => $modelParts];
            $contents[] = ['role' => 'user', 'parts' => $userParts];
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $this->synthesisNudge($userMessage)]],
            ];

            return true;
        }

        $toolResultsBag[] = ['name' => $name, 'result' => $result];
        $contents[] = [
            'role' => 'model',
            'parts' => [[
                'functionCall' => [
                    'name' => $name,
                    'args' => (object) $args,
                ],
            ]],
        ];
        $contents[] = [
            'role' => 'user',
            'parts' => [[
                'functionResponse' => [
                    'name' => $name,
                    'response' => $result,
                ],
            ]],
        ];
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $this->synthesisNudge($userMessage)]],
        ];

        return true;
    }

    /**
     * @param  list<array{name: string, result: array<string, mixed>}>  $toolResultsBag
     */
    private function buildDeterministicFallback(array $toolResultsBag, string $userMessage): string
    {
        foreach ($toolResultsBag as $entry) {
            $result = $entry['result'] ?? [];
            if (! ($result['ok'] ?? false)) {
                continue;
            }

            if (($result['report_type'] ?? '') === 'booking_analytics' || isset($result['data']['zone_wise']) || isset($result['data']['category_wise'])) {
                $payload = ($result['report_type'] ?? '') === 'booking_analytics'
                    ? (is_array($result['data'] ?? null) ? $result['data'] : [])
                    : (is_array($result['data'] ?? null) ? $result['data'] : $result);

                return $this->formatBookingAnalyticsFallback($payload, $userMessage);
            }

            if (in_array($result['report_type'] ?? '', ['customer', 'provider'], true)
                && is_array($result['data']['category_wise'] ?? null)
                && $result['data']['category_wise'] !== []) {
                return $this->formatCategoryPerformanceFallback(
                    $result['data'],
                    $userMessage,
                    'lead '.(string) ($result['report_type'] ?? 'customer')
                );
            }

            if (in_array($result['analysis'] ?? '', [
                'customer_cancellation_reasons',
                'provider_cancellation_reasons',
                'invalid_reasons',
                'future_customer_reasons',
            ], true)) {
                return $this->formatLeadCancellationReasonsFallback($result);
            }

            if (isset($result['timing']['insights']) && is_array($result['timing']['insights']) && $result['timing']['insights'] !== []) {
                return $this->formatTimingInsightsFallback($result);
            }
        }

        $genericSections = [];
        foreach ($toolResultsBag as $entry) {
            $result = $entry['result'] ?? [];
            if (($entry['name'] ?? '') === 'explore_business_data' && ($result['ok'] ?? false)) {
                $nested = $this->buildDeterministicFallbackFromExplore($result);
                if ($nested !== '') {
                    return $nested;
                }
            }
            if ($result['ok'] ?? false) {
                $generic = $this->formatGenericToolFallback((string) ($entry['name'] ?? 'tool'), $result);
                if ($generic !== '') {
                    $genericSections[] = $generic;
                }
            }
        }

        if (count($genericSections) > 1) {
            return $this->formatCombinedFallback($genericSections, $userMessage);
        }

        return $genericSections[0] ?? '';
    }

    /**
     * @param  list<string>  $sections
     */
    private function formatCombinedFallback(array $sections, string $userMessage): string
    {
        $lines = [
            '## Executive Summary',
            'Live data was pulled from **'.count($sections).'** admin sources to answer your question.',
            '',
            '## Key Metrics',
        ];

        foreach ($sections as $section) {
            foreach (preg_split('/\r\n|\r|\n/', $section) ?: [] as $line) {
                if (preg_match('/^-\s+\*\*/', $line) || preg_match('/^\d+\.\s+\*\*/', $line)) {
                    $lines[] = $line;
                }
            }
        }

        $lines[] = '';
        $lines[] = '## Detailed Analysis';
        $lines[] = 'The figures above are real-time from categories, services, bookings, leads, customers, and/or providers in the admin database.';

        if ($userMessage !== '') {
            $lines[] = '';
            $lines[] = '_Question: '.$userMessage.'_';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $exploreResult
     */
    private function buildDeterministicFallbackFromExplore(array $exploreResult): string
    {
        $nestedBag = [];
        foreach ($exploreResult['results'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $nestedBag[] = [
                'name' => (string) ($item['tool'] ?? 'tool'),
                'result' => is_array($item['result'] ?? null) ? $item['result'] : [],
            ];
        }

        if ($nestedBag === []) {
            return '';
        }

        return $this->buildDeterministicFallback($nestedBag, (string) ($exploreResult['question'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function formatTimingInsightsFallback(array $result): string
    {
        $insights = is_array($result['timing']['insights'] ?? null) ? $result['timing']['insights'] : [];
        $cohort = (string) ($result['cohort'] ?? $result['analysis'] ?? 'cohort');
        $size = (int) ($result['cohort_size'] ?? $result['leads_in_scope'] ?? $result['bookings_in_scope'] ?? 0);

        $lines = [
            '## Executive Summary',
            $insights[0] ?? "Timing analysis for {$cohort} ({$size} records).",
            '',
            '## Key Metrics',
            "- Records analyzed: **{$size}**",
        ];

        foreach (array_slice($insights, 0, 8) as $insight) {
            $lines[] = '- '.$insight;
        }

        if (isset($result['timing']['lag_hours']) && is_array($result['timing']['lag_hours'])) {
            $lines[] = '';
            $lines[] = '## Lag statistics (hours)';
            foreach ($result['timing']['lag_hours'] as $label => $stats) {
                if (! is_array($stats) || ($stats['median_hours'] ?? null) === null) {
                    continue;
                }
                $lines[] = sprintf(
                    '- %s: median **%.2f**h, p90 **%.2f**h (%d samples)',
                    str_replace('_', ' ', (string) $label),
                    (float) $stats['median_hours'],
                    (float) ($stats['p90_hours'] ?? 0),
                    (int) ($stats['count'] ?? 0)
                );
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function formatGenericToolFallback(string $toolName, array $result): string
    {
        $lines = ['## Executive Summary', 'Live data pulled from **'.$toolName.'**.', '', '## Key Metrics'];
        $metricCount = 0;

        $countKeys = [
            'total', 'count', 'leads_in_scope', 'bookings_in_scope', 'cancelled_customer_leads',
            'cancelled_provider_leads', 'cohort_size', 'overdue_scheduled_followups', 'paid_count',
            'unpaid_count', 'reopened_count', 'pending_approval_count', 'total_customers',
            'total_providers', 'total_bookings', 'total_leads', 'open_chats', 'unassigned_chats',
            'total_services', 'active_services', 'inactive_services', 'total_reviews', 'total_promotions',
            'active_now', 'total_subscribers', 'active_subscribers', 'expired_subscribers',
            'main_categories', 'sub_categories', 'tools_run',
        ];
        foreach ($countKeys as $key) {
            if (isset($result[$key]) && is_numeric($result[$key])) {
                $lines[] = '- '.str_replace('_', ' ', ucfirst($key)).': **'.$result[$key].'**';
                $metricCount++;
            }
        }

        if (isset($result['metrics']) && is_array($result['metrics'])) {
            foreach ($result['metrics'] as $label => $value) {
                if (is_scalar($value)) {
                    $lines[] = '- '.str_replace('_', ' ', (string) $label).': **'.$value.'**';
                    $metricCount++;
                }
            }
        }

        if (isset($result['by_reason']) && is_array($result['by_reason']) && $result['by_reason'] !== []) {
            $lines[] = '';
            $lines[] = '## Breakdown';
            foreach (array_slice($result['by_reason'], 0, 10) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $lines[] = sprintf(
                    '%d. **%s** — %s',
                    $index + 1,
                    (string) ($row['reason'] ?? $row['label'] ?? 'Unknown'),
                    (string) ($row['count'] ?? $row['total'] ?? 0)
                );
                $metricCount++;
            }
        }

        if (isset($result['by_status']) && is_array($result['by_status'])) {
            $lines[] = '';
            $lines[] = '## By status';
            foreach ($result['by_status'] as $status => $count) {
                $lines[] = '- **'.$status.'**: '.$count;
                $metricCount++;
            }
        }

        if (isset($result['data']['cancellation']['reasons']) && is_array($result['data']['cancellation']['reasons'])) {
            $lines[] = '';
            $lines[] = '## Cancellation reasons';
            foreach (array_slice($result['data']['cancellation']['reasons'], 0, 10) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $lines[] = sprintf(
                    '%d. **%s** — %s',
                    $index + 1,
                    (string) ($row['label'] ?? $row['reason'] ?? 'Unknown'),
                    (string) ($row['total'] ?? $row['count'] ?? 0)
                );
                $metricCount++;
            }
        }

        if (isset($result['queues']) && is_array($result['queues'])) {
            $lines[] = '';
            $lines[] = '## Queues';
            foreach ($result['queues'] as $queue => $count) {
                $lines[] = '- **'.str_replace('_', ' ', (string) $queue).'**: '.$count;
                $metricCount++;
            }
        }

        if ($metricCount === 0) {
            return '';
        }

        $lines[] = '';
        $lines[] = '## Detailed Analysis';
        $lines[] = 'Figures above are real-time from the admin database.';

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function formatLeadCancellationReasonsFallback(array $result): string
    {
        $analysis = (string) ($result['analysis'] ?? '');
        $reasons = is_array($result['by_reason'] ?? null) ? $result['by_reason'] : [];
        $total = (int) ($result['cancelled_customer_leads'] ?? $result['cancelled_provider_leads'] ?? $result['leads_in_scope'] ?? 0);
        $withoutReason = (int) ($result['without_recorded_reason'] ?? 0);

        $title = match ($analysis) {
            'provider_cancellation_reasons' => 'provider lead cancellation reasons',
            'invalid_reasons' => 'invalid lead reasons',
            'future_customer_reasons' => 'future customer lead reasons',
            default => 'customer lead cancellation reasons',
        };

        if ($reasons === []) {
            return "## Executive Summary\n"
                ."No {$title} were found in the analyzed lead set.\n\n"
                ."## Key Metrics\n"
                ."- Leads in scope: **{$total}**";
        }

        $top = $reasons[0];
        $topReason = (string) ($top['reason'] ?? 'Unknown');
        $topCount = (int) ($top['count'] ?? 0);

        $lines = [
            '## Executive Summary',
            "**{$topReason}** is the top {$title} with **{$topCount}** leads.",
            '',
            '## Key Metrics',
            "- Cancelled/analyzed leads: **{$total}**",
        ];

        if ($withoutReason > 0) {
            $lines[] = "- Without recorded reason: **{$withoutReason}**";
        }

        $lines[] = '';
        $lines[] = '## Top reasons';

        foreach (array_slice($reasons, 0, 10) as $index => $row) {
            $lines[] = sprintf(
                '%d. **%s** — %d',
                $index + 1,
                (string) ($row['reason'] ?? 'Unknown'),
                (int) ($row['count'] ?? 0)
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatCategoryPerformanceFallback(array $data, string $userMessage, string $contextLabel): string
    {
        $categories = is_array($data['category_wise'] ?? null) ? $data['category_wise'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $total = (int) ($summary['total'] ?? 0);

        if ($categories === []) {
            return "## Executive Summary\n"
                ."No category breakdown is available yet for {$contextLabel}.\n\n"
                ."## Key Metrics\n"
                ."- Records in scope: **{$total}**";
        }

        $top = $categories[0];
        $topLabel = (string) ($top['label'] ?? 'Unknown');
        $topTotal = (int) ($top['total'] ?? 0);
        $topShare = (float) ($top['share_percent'] ?? 0);
        $topCompletion = (float) ($top['completion_rate'] ?? $top['conversion_rate'] ?? 0);

        $lines = [
            '## Executive Summary',
            "**{$topLabel}** is the top-performing category in {$contextLabel} — **{$topTotal}** records (**{$topShare}%** share".($topCompletion > 0 ? sprintf(', %.1f%% completion/conversion', $topCompletion) : '').').',
            '',
            '## Key Metrics',
            "- Total in scope: **{$total}**",
            '',
            '## Top categories',
        ];

        foreach (array_slice($categories, 0, 10) as $index => $row) {
            $completion = (float) ($row['completion_rate'] ?? $row['conversion_rate'] ?? 0);
            $lines[] = sprintf(
                '%d. **%s** — %d (%.1f%% share, %.1f%% completed/converted, %d completed, %d cancelled, %d pending)',
                $index + 1,
                (string) ($row['label'] ?? 'Unknown'),
                (int) ($row['total'] ?? 0),
                (float) ($row['share_percent'] ?? 0),
                $completion,
                (int) ($row['completed'] ?? $row['booked'] ?? 0),
                (int) ($row['cancelled'] ?? 0),
                (int) ($row['pending'] ?? 0)
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatBookingAnalyticsFallback(array $data, string $userMessage): string
    {
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $categories = is_array($data['category_wise'] ?? null) ? $data['category_wise'] : [];
        $zones = is_array($data['zone_wise'] ?? null) ? $data['zone_wise'] : [];
        $total = (int) ($summary['total'] ?? 0);
        $missingZone = (int) ($summary['missing_zone'] ?? 0);
        $wantCategory = (bool) preg_match('/\b(category|categories|subcategory|service type)\b/i', $userMessage);

        if ($wantCategory && $categories !== []) {
            return $this->formatCategoryPerformanceFallback($data, $userMessage, 'bookings');
        }

        if ($zones === []) {
            return "## Executive Summary\n"
                ."I pulled live booking analytics, but no area/zone breakdown is available yet.\n\n"
                ."## Key Metrics\n"
                ."- Total bookings: **{$total}**\n"
                ."- Bookings missing zone/area: **{$missingZone}**\n\n"
                ."## Recommendations\n"
                ."1. (High) Capture zone on intake so area-level booking reports are reliable.";
        }

        $top = $zones[0];
        $topLabel = (string) ($top['label'] ?? 'Unknown');
        $topTotal = (int) ($top['total'] ?? 0);
        $topShare = (float) ($top['share_percent'] ?? 0);
        $completionRate = (float) ($summary['completion_rate'] ?? 0);

        $lines = [
            '## Executive Summary',
            "**{$topLabel}** drives the most bookings — **{$topTotal}** bookings (**{$topShare}%** of all bookings).",
            '',
            '## Key Metrics',
            "- Total bookings: **{$total}**",
            sprintf('- Overall completion rate: **%.1f%%**', $completionRate),
            '',
            '## Bookings by area (zone)',
        ];

        foreach (array_slice($zones, 0, 8) as $index => $zone) {
            $lines[] = sprintf(
                '%d. **%s** — %d bookings (%.1f%% share, %.1f%% completed)',
                $index + 1,
                (string) ($zone['label'] ?? 'Unknown'),
                (int) ($zone['total'] ?? 0),
                (float) ($zone['share_percent'] ?? 0),
                (float) ($zone['completion_rate'] ?? 0)
            );
        }

        if ($missingZone > 0) {
            $lines[] = '';
            $lines[] = '## Risks & Concerns';
            $lines[] = "- **{$missingZone}** bookings have no zone captured, which can under-report some areas.";
        }

        $lines[] = '';
        $lines[] = '## Recommendations';
        $lines[] = "1. (High) Invest marketing and provider capacity in **{$topLabel}** — it is already your strongest booking area.";

        return implode("\n", $lines);
    }

    private function isRetryableBlocked(string $reason): bool
    {
        if (in_array($reason, self::RETRYABLE_REASONS, true)) {
            return true;
        }

        return str_starts_with($reason, 'finish_') && $reason !== 'finish_STOP';
    }

    private function blockedErrorMessage(string $reason): string
    {
        if ($reason === 'missing_api_key') {
            return (string) __('admin_business_ai.missing_api_key');
        }
        if (str_starts_with($reason, 'http_')) {
            return (string) __('admin_business_ai.gemini_http_error');
        }
        if (in_array($reason, ['no_parts', 'no_candidate', 'finish_MALFORMED_FUNCTION_CALL', 'finish_UNEXPECTED_TOOL_CALL'], true)) {
            return (string) __('admin_business_ai.gemini_empty_turn');
        }

        return (string) __('admin_business_ai.gemini_error');
    }

    /**
     * Keep tool payloads small enough for Gemini to synthesize a final answer.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function compactToolResult(array $result): array
    {
        $maxBytes = (int) config('admin_business_ai.max_tool_response_bytes', 14000);
        $encoded = json_encode($result);
        if ($encoded !== false && strlen($encoded) <= $maxBytes) {
            return $result;
        }

        $compact = $result;
        if (isset($compact['data']) && is_array($compact['data'])) {
            foreach (['booking_created_by_hour', 'booking_created_by_day', 'booking_created_by_hour_labels', 'booking_created_by_day_labels'] as $drop) {
                unset($compact['data'][$drop]);
            }
            if (isset($compact['data']['category_wise'])) {
                $compact['data']['category_wise'] = array_slice($compact['data']['category_wise'], 0, 8);
            }
            if (isset($compact['data']['zone_wise'])) {
                $compact['data']['zone_wise'] = array_slice($compact['data']['zone_wise'], 0, 8);
            }
        }
        if (isset($compact['results']) && is_array($compact['results']) && count($compact['results']) > 5) {
            $compact['results'] = array_slice($compact['results'], 0, 5);
            $compact['results_truncated'] = true;
        }
        foreach (['leads', 'bookings', 'providers', 'customers', 'conversations', 'outbound_enquiries', 'incomplete_leads', 'employees', 'entries', 'transactions', 'withdraw_requests', 'followups', 'services', 'categories', 'promotions', 'subscribers', 'reviews'] as $listKey) {
            if (isset($compact[$listKey]) && is_array($compact[$listKey]) && count($compact[$listKey]) > 15) {
                $compact[$listKey] = array_slice($compact[$listKey], 0, 15);
                $compact[$listKey.'_truncated'] = true;
            }
        }
        foreach (['recent_ledger_transactions', 'pending_bookings_sample', 'top_providers', 'top_customers', 'todays_pending_booking_followups', 'todays_pending_lead_followups'] as $dashKey) {
            if (isset($compact[$dashKey]) && is_array($compact[$dashKey]) && count($compact[$dashKey]) > 10) {
                $compact[$dashKey] = array_slice($compact[$dashKey], 0, 10);
            }
        }
        if (isset($compact['timing']['sample_bookings']) && is_array($compact['timing']['sample_bookings']) && count($compact['timing']['sample_bookings']) > 12) {
            $compact['timing']['sample_bookings'] = array_slice($compact['timing']['sample_bookings'], 0, 12);
        }
        if (isset($compact['sample_bookings']) && is_array($compact['sample_bookings']) && count($compact['sample_bookings']) > 12) {
            $compact['sample_bookings'] = array_slice($compact['sample_bookings'], 0, 12);
        }
        if (isset($compact['timing']['sample_leads']) && is_array($compact['timing']['sample_leads']) && count($compact['timing']['sample_leads']) > 12) {
            $compact['timing']['sample_leads'] = array_slice($compact['timing']['sample_leads'], 0, 12);
        }
        if (isset($compact['timing_summary']['sample_leads']) && is_array($compact['timing_summary']['sample_leads'])) {
            unset($compact['timing_summary']['sample_leads']);
        }
        if (isset($compact['lead']) && is_array($compact['lead'])) {
            foreach (['followups', 'change_logs', 'type_history', 'status_timeline'] as $nested) {
                if (isset($compact['lead'][$nested]) && is_array($compact['lead'][$nested]) && count($compact['lead'][$nested]) > 12) {
                    $compact['lead'][$nested] = array_slice($compact['lead'][$nested], 0, 12);
                }
            }
        }
        if (isset($compact['data']['category_wise']) && is_array($compact['data']['category_wise']) && count($compact['data']['category_wise']) > 10) {
            $compact['data']['category_wise'] = array_slice($compact['data']['category_wise'], 0, 10);
            $compact['data']['category_wise_truncated'] = true;
        }
        if (isset($compact['data']['zone_wise']) && is_array($compact['data']['zone_wise']) && count($compact['data']['zone_wise']) > 10) {
            $compact['data']['zone_wise'] = array_slice($compact['data']['zone_wise'], 0, 10);
            $compact['data']['zone_wise_truncated'] = true;
        }
        if (isset($compact['booking']) && is_array($compact['booking'])) {
            foreach (['followups', 'partial_payments', 'status_history', 'schedule_history', 'change_logs', 'reopen_events', 'incidents'] as $nested) {
                if (isset($compact['booking'][$nested]) && is_array($compact['booking'][$nested]) && count($compact['booking'][$nested]) > 12) {
                    $compact['booking'][$nested] = array_slice($compact['booking'][$nested], 0, 12);
                }
            }
        }
        if (isset($compact['unassigned_chat_samples_with_lead_handlers']) && is_array($compact['unassigned_chat_samples_with_lead_handlers'])) {
            $compact['unassigned_chat_samples_with_lead_handlers'] = array_slice($compact['unassigned_chat_samples_with_lead_handlers'], 0, 12);
        }

        $compact['_note'] = 'Payload trimmed for AI context; counts and totals are still accurate.';

        return $compact;
    }

    private function buildSystemPrompt(): string
    {
        $company = (string) ((business_config('business_name', 'business_information'))?->live_values ?? 'Panun Kaergar');

        return <<<PROMPT
You are the Business Expert AI for {$company}'s admin panel — a senior business analyst and operations advisor.

## Data rules
- Always call tools before stating any count, revenue figure, status, name, or trend. Never guess.
- All tool results are **live, real-time** database reads. NEVER tell the admin "I don't have that information", "data is not available", "the report does not contain", or "I cannot determine" — especially for **categories, services, bookings, leads, customers, and providers**. You always have tools for these six domains.
- If a tool returned ok:true, you MUST use its fields. If a list is empty or count is 0, say so explicitly (e.g. "0 active services in that category") — never claim the data is missing.
- If the first tool lacks a specific field, call another specialized tool or explore_business_data before concluding.
- For ANY question about categories, services, bookings, leads, customers, or providers — however phrased — call explore_business_data with the exact question, or call the matching query_/analyze_/get_* tool. Then answer from the results.
- For broad or unclear questions, call explore_business_data — it auto-runs up to 6 relevant tools across all domains.
- **Full admin-tab data is available via tools (40 tools, all read-only):**
  - Leads timing/lag: analyze_leads no_response_timing_report — full cohort stats for No Response leads: peak receive hours, reply/followup/update hours, median/p90 lag hours, handler breakdown, never-replied counts. no_response_leads also includes timing_summary. lead_timing_report with cohort filter for other segments (invalid, customer_pending, etc). lead_activity_report includes timing aggregates.
  - Leads: query_leads non_responsive_only=true for list. get_lead_details for single-lead activity_summary.
  - Leads: get_lead_details returns all_fields — zone, categories, service, cancellation reason/remarks, received date, every followup, handler, tags, district/zones (provider). query_leads filters by zone, category, source, tag. get_lead_inbound_report mirrors admin Lead Reports (customer|provider). get_employee_lead_productivity mirrors per-user lead report.
  - Bookings timing/lag: analyze_bookings booking_timing_report — peak created hours, lag created→followup/accepted/completed/canceled/payment, assignee+zone breakdown. cancellation_timing_report and followup_timing_report for focused cohorts. cohort filter: pending|accepted|canceled|overdue_followup|loss_making|unpaid|verify_pending|offline_payment|etc.
  - Bookings: get_booking_details returns all_fields. query_bookings filters by zone, category, assignee, lead_id, is_paid, settlement_outcome, overdue_followup. query_booking_queues / get_booking_queues_overview for verify requests, offline payments, special scenarios, overdue followups.
  - Financial: query_ledger, query_transactions, query_withdraw_requests, query_pending_provider_balances. get_business_reports also supports earning, expense, commission_earning, transaction_summary.
  - Customers: query_customers, get_customer_details, analyze_customers — overview, addresses, wallet/loyalty, performance, incidents, reviews, payments.
  - Providers: query_providers, get_provider_details, analyze_providers — zones, bank, services, servicemen, performance, incidents, bookings.
  - Dashboard: get_business_dashboard_overview (KPIs) or get_dashboard_snapshot (full widgets: ledger, followups, top lists, earning chart).
  - Relations: get_entity_relations — link phone/lead/booking/customer/provider/WhatsApp/outbound enquiry in one call.
  - WhatsApp: get_whatsapp_conversations_overview, query_whatsapp_conversations, get_whatsapp_conversation_details — chat_handler (inbox assignee) vs lead_handler (CRM); linked_lead_is_customer; system bookings on thread.
  - Employees: analyze_employee_activity (workload, chats, bookings, incomplete leads per handler), query_incomplete_leads (unspecified/missing lead fields + who handles + booking link).
  - Service catalog: query_services, analyze_services (catalog_overview, top_by_orders, by_category, low_rated).
  - Category catalog: query_categories, analyze_category_catalog (catalog_overview, by_zone, inactive).
  - Reviews: analyze_reviews (overview, by_rating, top/low rated services, top providers, recent_negative).
  - Promotions: query_promotions, analyze_promotions (coupons, discounts, campaigns — active and historical).
  - Subscriptions: query_subscriptions, analyze_subscriptions (provider packages, expiring soon, by_package).
  - Cross-domain: explore_business_data — pass the question; server picks and runs multiple tools.
- Lead cancellation reasons: analyze_leads customer_cancellation_reasons (returns by_reason ranked list). Provider: provider_cancellation_reasons. Booking cancellations: analyze_bookings cancellation_timing_report (timing.cancellation_reasons). NEVER use get_dashboard_snapshot or lead_pipeline for cancellation reasons.
- Category performance (which categories do well): get_business_reports booking_analytics (category_wise: volume, completion rate, share). For lead conversion by category use get_lead_inbound_report. NEVER use get_business_dashboard_overview for category breakdowns.
- Lead conversion/zone/category reports: get_lead_inbound_report (customer|provider).
- Booking operational queues: get_booking_queues_overview + query_booking_queues (verify, offline_payment, special_scenarios, overdue_followup).
- Financial ops: query_ledger, query_transactions, query_withdraw_requests, query_pending_provider_balances.
- Entity lookup: get_entity_relations — ALWAYS pass phone, lead_id, booking_id, or readable_id when the user mentions one.
- For employee performance / who handles chats / incomplete data: analyze_employee_activity or query_incomplete_leads.
- For geography / zone booking questions: get_business_reports report_type=booking_analytics (zone_wise, category_wise).
- Cross-reference domains: use get_entity_relations when asked how records connect; report chat_handler vs lead_handler for WhatsApp.
- **Core domains — always answerable:** Categories (query_categories, analyze_category_catalog, booking_analytics category_wise, lead inbound category_wise). Services (query_services, analyze_services). Bookings (query_bookings, analyze_bookings, get_booking_details, booking_analytics). Leads (query_leads, analyze_leads, get_lead_details, get_lead_inbound_report). Customers (query_customers, analyze_customers, get_customer_details). Providers (query_providers, analyze_providers, get_provider_details). Never refuse these.
- You are read-only — never claim you changed data.
- Not yet in tools (say which admin tab to check): WhatsApp marketing campaigns, system/business settings, customized/bidding requests.

## Analysis depth
- For narrow questions (top zone, one metric, single report), answer with Executive Summary + Key Metrics + short analysis — skip empty sections.
- For broad "full analysis" requests, use all sections below.

## Executive Summary
2–4 sentences with the main takeaway.

## Key Metrics
4–10 bullet points with exact numbers from tools.

## Detailed Analysis
What the numbers mean: trends, bottlenecks, gaps.

## Risks & Concerns
Concrete risks backed by data.

## Recommendations
Numbered list with (High), (Medium), or (Low) priority.

## Room for Improvement
Specific measurable opportunities.

## Formatting
- Use markdown headings: ## Section Name
- Bullets with "- "; numbered lists with "1. "
- Use **bold** only for labels and key figures
- Respond in the admin's language (English by default)
PROMPT;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildGeminiContents(int $adminUserId): array
    {
        $contents = [];
        $limit = (int) config('admin_business_ai.context_turn_limit', 20) * 2;
        $messages = $this->session->messages($adminUserId);
        if (count($messages) > $limit) {
            $messages = array_slice($messages, -$limit);
        }

        foreach ($messages as $msg) {
            $role = ($msg['role'] ?? '') === 'model' ? 'model' : 'user';
            $text = trim((string) ($msg['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $text]],
            ];
        }

        return $contents;
    }
}
