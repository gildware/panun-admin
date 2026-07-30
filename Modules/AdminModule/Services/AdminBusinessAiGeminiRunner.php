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
     * @return array{ok: bool, reply?: string, charts?: list<array<string, mixed>>, error?: string}
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

            return ['ok' => false, 'error' => __('admin_business_ai.gemini_exception').' Exact error: '.$e->getMessage()];
        }
    }

    /**
     * @param  list<array{name: string, result: array<string, mixed>}>  $toolResultsBag
     * @return list<array<string, mixed>>
     */
    private function extractChartsFromToolResults(array $toolResultsBag): array
    {
        $charts = [];
        foreach ($toolResultsBag as $entry) {
            $result = $entry['result'] ?? [];
            if (! is_array($result) || ! ($result['ok'] ?? false)) {
                continue;
            }
            if (isset($result['charts']) && is_array($result['charts'])) {
                foreach ($result['charts'] as $chart) {
                    if (is_array($chart) && ! empty($chart['labels'])) {
                        $charts[] = $chart;
                    }
                }
            }
            if (($entry['name'] ?? '') === 'explore_business_data' && isset($result['results']) && is_array($result['results'])) {
                foreach ($result['results'] as $nested) {
                    if (! is_array($nested)) {
                        continue;
                    }
                    $nestedResult = is_array($nested['result'] ?? null) ? $nested['result'] : [];
                    if (isset($nestedResult['charts']) && is_array($nestedResult['charts'])) {
                        foreach ($nestedResult['charts'] as $chart) {
                            if (is_array($chart) && ! empty($chart['labels'])) {
                                $charts[] = $chart;
                            }
                        }
                    }
                }
            }
        }

        return array_slice($charts, 0, 6);
    }

    /**
     * @param  list<array{name: string, result: array<string, mixed>}>  $toolResultsBag
     * @return list<array<string, mixed>>
     */
    private function extractTablesFromToolResults(array $toolResultsBag): array
    {
        $tables = [];
        foreach ($toolResultsBag as $entry) {
            $result = $entry['result'] ?? [];
            if (! is_array($result) || ! ($result['ok'] ?? false)) {
                continue;
            }
            if (isset($result['tables']) && is_array($result['tables'])) {
                foreach ($result['tables'] as $table) {
                    if (is_array($table) && ! empty($table['columns'])) {
                        $tables[] = $table;
                    }
                }
            }
            if (($entry['name'] ?? '') === 'explore_business_data' && isset($result['results']) && is_array($result['results'])) {
                foreach ($result['results'] as $nested) {
                    if (! is_array($nested)) {
                        continue;
                    }
                    $nestedResult = is_array($nested['result'] ?? null) ? $nested['result'] : [];
                    if (isset($nestedResult['tables']) && is_array($nestedResult['tables'])) {
                        foreach ($nestedResult['tables'] as $table) {
                            if (is_array($table) && ! empty($table['columns'])) {
                                $tables[] = $table;
                            }
                        }
                    }
                }
            }
        }

        return array_slice($tables, 0, 4);
    }

    /**
     * @param  list<array{name: string, result: array<string, mixed>}>  $toolResultsBag
     * @return array{ok: bool, reply: string, charts: list<array<string, mixed>>, tables: list<array<string, mixed>>, note?: string}
     */
    private function successReply(int $adminUserId, string $reply, array $toolResultsBag = [], ?string $note = null): array
    {
        $charts = $this->extractChartsFromToolResults($toolResultsBag);
        $tables = $this->extractTablesFromToolResults($toolResultsBag);

        $notes = [];
        if ($note !== null && trim($note) !== '') {
            $notes[] = trim($note);
        }
        $toolNote = $this->toolFailureNote($toolResultsBag);
        if ($toolNote !== '') {
            $notes[] = $toolNote;
        }
        $note = implode(' ', $notes);
        if ($note !== '') {
            $reply = $this->prependNoteToReply($reply, $note);
        }
        $this->session->append($adminUserId, 'model', $reply, $charts, $tables, $note !== '' ? $note : null);

        $out = ['ok' => true, 'reply' => $reply, 'charts' => $charts, 'tables' => $tables];
        if ($note !== '') {
            $out['note'] = $note;
        }

        return $out;
    }

    /**
     * Report tools that crashed, including sub-tools inside explore_business_data, so a
     * partial answer never silently hides missing data.
     *
     * @param  list<array{name: string, result: array<string, mixed>}>  $toolResultsBag
     */
    private function toolFailureNote(array $toolResultsBag): string
    {
        $failures = [];
        foreach ($toolResultsBag as $entry) {
            $result = is_array($entry['result'] ?? null) ? $entry['result'] : [];
            foreach ($this->collectToolFailures((string) ($entry['name'] ?? ''), $result) as $failure) {
                $failures[$failure] = true;
            }
        }

        $failures = array_keys($failures);
        if ($failures === []) {
            return '';
        }

        return (string) __('admin_business_ai.tool_failed_note', [
            'count' => (string) count($failures),
            'details' => implode('; ', array_slice($failures, 0, 3)),
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return list<string>
     */
    private function collectToolFailures(string $toolName, array $result): array
    {
        $failures = [];
        if (($result['error'] ?? '') === 'tool_failed') {
            $name = (string) ($result['tool'] ?? $toolName);
            $message = trim((string) ($result['message'] ?? ''));
            $failures[] = $message !== '' ? $name.' — '.$message : $name;
        }

        if (isset($result['results']) && is_array($result['results'])) {
            foreach ($result['results'] as $nested) {
                if (! is_array($nested)) {
                    continue;
                }
                $nestedResult = is_array($nested['result'] ?? null) ? $nested['result'] : [];
                $failures = array_merge(
                    $failures,
                    $this->collectToolFailures((string) ($nested['tool'] ?? $toolName), $nestedResult)
                );
            }
        }

        return $failures;
    }

    private function prependNoteToReply(string $reply, string $note): string
    {
        $block = "> **Note:** {$note}\n\n";

        return str_starts_with(ltrim($reply), '> **Note:**') ? $reply : $block.$reply;
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
        $lastGeminiFailureNote = null;
        /** @var list<array{name: string, result: array<string, mixed>}> $toolResultsBag */
        $toolResultsBag = [];

        $mandatory = $this->questionRouter->inferMandatoryAggregateTools($userMessage);
        if ($mandatory !== []) {
            foreach ($mandatory as $plan) {
                $toolName = (string) ($plan['name'] ?? '');
                $toolArgs = is_array($plan['args'] ?? null) ? $plan['args'] : [];
                if ($toolName === '') {
                    continue;
                }
                $toolResultsBag[] = [
                    'name' => $toolName,
                    'result' => $this->compactToolResult($this->toolExecutor->execute($toolName, $toolArgs)),
                ];
            }
            $anyToolSucceeded = false;
            foreach ($toolResultsBag as $entry) {
                if (($entry['result']['ok'] ?? false) === true) {
                    $anyToolSucceeded = true;
                    break;
                }
            }

            // Gemini narrates over the verified payload; the deterministic report stays in
            // reserve for blocked/empty/refusal turns. When every forced tool failed, keep
            // tool calling open so Gemini can reach for a different tool.
            if ($this->injectPlannedTools($mandatory, $contents, $toolResultsBag)) {
                $hadToolResults = $anyToolSucceeded;
                $forceTextOnly = $anyToolSucceeded;
                if ($anyToolSucceeded) {
                    $contents[] = [
                        'role' => 'user',
                        'parts' => [['text' => $this->synthesisNudge($userMessage, $toolResultsBag)]],
                    ];
                }
            } else {
                $deterministic = $this->buildDeterministicFallback($toolResultsBag, $userMessage);
                if ($deterministic !== '') {
                    return $this->successReply($adminUserId, $deterministic, $toolResultsBag);
                }
            }
        }

        $iter = 0;
        while ($iter < $maxRounds) {
            $iter++;
            $activeTools = $forceTextOnly ? [] : $tools;
            $turn = $this->gemini->generateTurn($system, $contents, $activeTools, null, $model, $maxOutTokens, $httpTimeout);

            if ($turn['type'] === 'blocked') {
                $reason = (string) ($turn['reason'] ?? 'blocked');
                $lastGeminiFailureNote = $this->geminiFailureNote($turn);
                Log::warning('Admin business AI blocked', [
                    'reason' => $reason,
                    'message' => (string) ($turn['message'] ?? ''),
                    'admin_id' => $adminUserId,
                    'iter' => $iter,
                    'had_tool_results' => $hadToolResults,
                    'force_text_only' => $forceTextOnly,
                ]);

                // Quota / auth / hard API failures: do not keep retrying the same Gemini call.
                if ($this->isHardGeminiUnavailable($reason)) {
                    $fallback = $this->buildDeterministicFallback($toolResultsBag, $userMessage);
                    if ($fallback !== '') {
                        return $this->successReply($adminUserId, $fallback, $toolResultsBag, $lastGeminiFailureNote);
                    }

                    return ['ok' => false, 'error' => $lastGeminiFailureNote];
                }

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
                        'parts' => [['text' => $this->synthesisNudge($userMessage, $toolResultsBag)]],
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
                                ? $this->synthesisNudge($userMessage, $toolResultsBag)
                                : 'Call one relevant tool (e.g. get_business_reports with booking_analytics for area/zone questions), then answer in plain text with markdown headings.',
                        ]],
                    ];

                    continue;
                }

                $fallback = $this->buildDeterministicFallback($toolResultsBag, $userMessage);
                if ($fallback !== '') {
                    return $this->successReply($adminUserId, $fallback, $toolResultsBag, $lastGeminiFailureNote);
                }

                return [
                    'ok' => false,
                    'error' => $this->failureError(
                        $this->blockedErrorMessage($reason),
                        $lastGeminiFailureNote,
                        $toolResultsBag
                    ),
                ];
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
                            'parts' => [['text' => $this->synthesisNudge($userMessage, $toolResultsBag)]],
                        ];

                        continue;
                    }

                    if ($blockedRetries < 4) {
                        $blockedRetries++;
                        $contents[] = [
                            'role' => 'user',
                            'parts' => [['text' => $this->synthesisNudge($userMessage, $toolResultsBag)]],
                        ];

                        continue;
                    }

                    $fallback = $this->buildDeterministicFallback($toolResultsBag, $userMessage);
                    if ($fallback !== '') {
                        return $this->successReply($adminUserId, $fallback, $toolResultsBag, $lastGeminiFailureNote);
                    }

                    return [
                        'ok' => false,
                        'error' => $this->failureError(
                            (string) __('admin_business_ai.empty_reply'),
                            $lastGeminiFailureNote,
                            $toolResultsBag
                        ),
                    ];
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
                        return $this->successReply($adminUserId, $fallback, $toolResultsBag, $lastGeminiFailureNote);
                    }
                }

                return $this->successReply($adminUserId, $reply, $toolResultsBag);
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
                'parts' => [['text' => $this->synthesisNudge($userMessage, $toolResultsBag)]],
            ];
        }

        $fallback = $this->buildDeterministicFallback($toolResultsBag, $userMessage);
        if ($fallback !== '') {
            return $this->successReply($adminUserId, $fallback, $toolResultsBag, $lastGeminiFailureNote);
        }

        if (! $serverToolFallbackUsed && trim($userMessage) !== '') {
            $exploreBag = [];
            if ($this->injectServerToolFallback($userMessage, $contents, $exploreBag)) {
                $ultimate = $this->buildDeterministicFallback($exploreBag, $userMessage);
                if ($ultimate !== '') {
                    return $this->successReply($adminUserId, $ultimate, $exploreBag, $lastGeminiFailureNote);
                }
            }
        }

        return [
            'ok' => false,
            'error' => $this->failureError(
                (string) __('admin_business_ai.tool_rounds_exceeded'),
                $lastGeminiFailureNote,
                $toolResultsBag
            ),
        ];
    }

    /**
     * @param  list<array{name: string, result: array<string, mixed>}>  $toolResultsBag
     */
    private function failureError(string $default, ?string $geminiNote, array $toolResultsBag): string
    {
        $parts = [$geminiNote !== null && trim($geminiNote) !== '' ? trim($geminiNote) : $default];
        $toolNote = $this->toolFailureNote($toolResultsBag);
        if ($toolNote !== '') {
            $parts[] = $toolNote;
        }

        return implode(' ', $parts);
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

    /**
     * @param  list<array{name: string, result: array<string, mixed>}>  $toolResultsBag
     */
    private function synthesisNudge(string $userMessage, array $toolResultsBag = []): string
    {
        $question = $userMessage !== '' ? $userMessage : 'the admin question';

        $nudge = 'Using ONLY the tool results above, answer: "'.$question.'". '
            .'The data is live from the database right now. You MUST answer from the tool payloads — forbidden phrases: "I don\'t have that information", "data is not available", "I cannot determine", "the report does not contain", "not in my tools", "I don\'t have access". '
            .'For categories, services, bookings, leads, customers, and providers you ALWAYS have live data when ok:true was returned. '
            .'If a count is 0 or a list is empty, state that explicitly (e.g. "0 cancelled leads") instead of claiming missing data. ';

        if ($this->bagHasAnalyticalRows($toolResultsBag)) {
            $nudge .= 'Write a real analysis, not a metrics dump: open with the headline finding in prose, then explain what is driving it using the reason breakdowns, timing lags, remarks and followup counts in the payload. '
                .'Quote exact figures and cite the cancellation reasons and row-level examples that support each claim, and call out what the numbers do NOT explain. '
                .'Charts and tables are already rendered for the admin, so do not restate every row — interpret them. '
                .'Use markdown ## headings that describe your findings rather than generic labels. ';
        } else {
            $nudge .= 'Reply in markdown with ## headings. For focused questions, ## Executive Summary, ## Key Metrics, and a short ## Detailed Analysis are enough. ';
        }

        return $nudge.'Do not call any more tools — write the final answer now.';
    }

    /**
     * True when the payload carries row-level detail worth interpreting rather than summarizing.
     *
     * @param  list<array{name: string, result: array<string, mixed>}>  $toolResultsBag
     */
    private function bagHasAnalyticalRows(array $toolResultsBag): bool
    {
        foreach ($toolResultsBag as $entry) {
            $result = $entry['result'] ?? [];
            if (! is_array($result) || ($result['ok'] ?? false) !== true) {
                continue;
            }

            if (! empty($result['tables']) || ! empty($result['queries_executed'])) {
                return true;
            }

            $data = is_array($result['data'] ?? null) ? $result['data'] : $result;
            if (! empty($data['sample_bookings']) || ! empty($data['samples']) || ! empty($data['cancellation_reasons'])) {
                return true;
            }
        }

        return false;
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
            .'cannot determine|can\'?t determine|cannot be performed|can\'?t be performed|not available|no (?:data|information) (?:available|found)|'
            .'report does not contain|does not (?:offer|provide)|do not (?:offer|provide)|outside (?:my|the) (?:tools|scope)|'
            .'i(?:\'m| am) unable to|unable to (?:find|retrieve|access|perform)|'
            .'not in (?:my|the) (?:tools|dataset|database)|not possible to (?:answer|determine|perform))\b/i',
            $reply
        );
    }

    /**
     * @param  list<array{name: string, args: array<string, mixed>}>  $planned
     * @param  list<array<string, mixed>>  $contents
     * @param  list<array{name: string, result: array<string, mixed>}>  $toolResultsBag
     */
    private function injectPlannedTools(array $planned, array &$contents, array &$toolResultsBag): bool
    {
        $modelParts = [];
        $userParts = [];
        foreach ($planned as $plan) {
            $toolName = (string) ($plan['name'] ?? '');
            if ($toolName === '' || $toolName === 'explore_business_data') {
                continue;
            }
            $toolArgs = is_array($plan['args'] ?? null) ? $plan['args'] : [];
            $result = null;
            foreach ($toolResultsBag as $entry) {
                if (($entry['name'] ?? '') === $toolName) {
                    $result = $entry['result'];
                    break;
                }
            }
            if ($result === null) {
                $result = $this->compactToolResult($this->toolExecutor->execute($toolName, $toolArgs));
                $toolResultsBag[] = ['name' => $toolName, 'result' => $result];
            }
            $modelParts[] = [
                'functionCall' => [
                    'name' => $toolName,
                    'args' => (object) $toolArgs,
                ],
            ];
            $userParts[] = [
                'functionResponse' => [
                    'name' => $toolName,
                    'response' => $result,
                ],
            ];
        }

        if ($modelParts === []) {
            return false;
        }

        $contents[] = ['role' => 'model', 'parts' => $modelParts];
        $contents[] = ['role' => 'user', 'parts' => $userParts];

        return true;
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

            if (! $this->injectPlannedTools($planned, $contents, $toolResultsBag)) {
                return false;
            }

            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $this->synthesisNudge($userMessage, $toolResultsBag)]],
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
            'parts' => [['text' => $this->synthesisNudge($userMessage, $toolResultsBag)]],
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

            if (($result['analysis'] ?? '') === 'sql_analytics' || ($entry['name'] ?? '') === 'run_sql_analytics') {
                return $this->formatSqlAnalyticsFallback($result);
            }

            if (in_array($result['analysis'] ?? '', [
                'customer_cancellation_reasons',
                'provider_cancellation_reasons',
                'invalid_reasons',
                'future_customer_reasons',
            ], true)) {
                return $this->formatLeadCancellationReasonsFallback($result);
            }

            if (($result['analysis'] ?? '') === 'invalid_to_active_lead_progression') {
                return $this->formatInvalidToActiveProgressionFallback($result);
            }

            if (($result['analysis'] ?? '') === 'phones_with_multiple_leads') {
                return $this->formatPhonesWithMultipleLeadsFallback($result);
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
        $size = (int) ($result['total_matching']
            ?? $result['bookings_in_scope']
            ?? $result['cohort_size']
            ?? $result['leads_in_scope']
            ?? 0);
        $analyzed = (int) ($result['analyzed_count'] ?? $result['cohort_size'] ?? $size);

        $lines = [
            '## Executive Summary',
            $insights[0] ?? "Timing analysis for {$cohort} ({$size} records).",
            '',
            '## Key Metrics',
            "- Total matching: **{$size}**",
        ];

        if ($analyzed > 0 && $analyzed !== $size) {
            $lines[] = "- Records analyzed in detail: **{$analyzed}**";
        }

        foreach (array_slice($insights, 0, 8) as $insight) {
            $lines[] = '- '.$insight;
        }

        $reasons = is_array($result['timing']['cancellation_reasons'] ?? null)
            ? $result['timing']['cancellation_reasons']
            : [];
        if ($reasons !== []) {
            $lines[] = '';
            $lines[] = '## Cancellation reasons';
            foreach (array_slice($reasons, 0, 12) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $lines[] = sprintf(
                    '%d. **%s** — %d',
                    $index + 1,
                    (string) ($row['reason'] ?? 'Unknown'),
                    (int) ($row['count'] ?? 0)
                );
            }
        }

        $statusWhen = is_array($result['timing']['by_status_when_cancelled'] ?? null)
            ? $result['timing']['by_status_when_cancelled']
            : [];
        if ($statusWhen !== []) {
            $lines[] = '';
            $lines[] = '## Status when cancelled';
            foreach (array_slice($statusWhen, 0, 8) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $lines[] = '- **'.(string) ($row['status'] ?? 'unknown').'**: '.(int) ($row['count'] ?? 0);
            }
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

        $samples = is_array($result['sample_bookings'] ?? null) ? $result['sample_bookings'] : [];
        if ($samples !== []) {
            $lines[] = '';
            $lines[] = '## Sample cancelled bookings';
            foreach (array_slice($samples, 0, 15) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $lines[] = sprintf(
                    '%d. **%s** — enquiry %s | cancelled from **%s** | reason: %s | followups: %d | remarks: %s',
                    $index + 1,
                    (string) ($row['readable_id'] ?? '—'),
                    (string) ($row['enquiry_at'] ?? $row['created_at'] ?? '—'),
                    (string) ($row['status_when_cancelled'] ?? 'unknown'),
                    (string) ($row['cancellation_reason'] ?? $row['cancellation_remarks'] ?? '—'),
                    (int) ($row['followups_taken'] ?? 0),
                    trim((string) ($row['initial_remarks'] ?? $row['cancellation_remarks'] ?? '—')) ?: '—'
                );
            }
        }

        if (! empty($result['scan_note'])) {
            $lines[] = '';
            $lines[] = '## Note';
            $lines[] = (string) $result['scan_note'];
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
    private function formatInvalidToActiveProgressionFallback(array $result): string
    {
        $progressions = (int) ($result['invalid_then_active_progressions'] ?? 0);
        $uniquePhones = (int) ($result['unique_phones_invalid_then_active'] ?? 0);
        $withWhatsApp = (int) ($result['progressions_with_whatsapp_chat'] ?? 0);
        $scanned = (int) ($result['total_leads_scanned'] ?? 0);
        $totalDb = (int) ($result['total_leads_in_database'] ?? 0);
        $byNext = is_array($result['by_next_lead_type'] ?? null) ? $result['by_next_lead_type'] : [];

        $lines = [
            '## Executive Summary',
            "Across **{$scanned}** CRM leads scanned ({$totalDb} total in database), **{$uniquePhones}** phone numbers had an invalid lead immediately followed by a customer, provider, or future customer lead (**{$progressions}** progression instances). **{$withWhatsApp}** of those progressions have a WhatsApp chat on the same phone.",
            '',
            '## Key Metrics',
            "- Total leads scanned: **{$scanned}** (database total: **{$totalDb}**)",
            "- Phones with multiple CRM leads: **".(int) ($result['phones_with_multiple_leads'] ?? 0).'**',
            "- Invalid → active progressions: **{$progressions}**",
            "- Unique phones with invalid → active: **{$uniquePhones}**",
            "- Progressions with WhatsApp chat overlap: **{$withWhatsApp}**",
        ];

        foreach ($byNext as $type => $count) {
            $lines[] = '- Next lead type **'.$type.'**: **'.$count.'**';
        }

        $samples = is_array($result['sample_progressions'] ?? null) ? $result['sample_progressions'] : [];
        if ($samples !== []) {
            $lines[] = '';
            $lines[] = '## Sample progressions';
            foreach (array_slice($samples, 0, 10) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $lines[] = sprintf(
                    '%d. **%s** — invalid lead #%d → %s lead #%d%s',
                    $index + 1,
                    (string) ($row['phone'] ?? '—'),
                    (int) ($row['invalid_lead_id'] ?? 0),
                    (string) ($row['next_lead_type'] ?? '—'),
                    (int) ($row['next_lead_id'] ?? 0),
                    ! empty($row['has_whatsapp_chat']) ? ' (WhatsApp)' : ''
                );
            }
        }

        if (! empty($result['scan_note'])) {
            $lines[] = '';
            $lines[] = '## Note';
            $lines[] = (string) $result['scan_note'];
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function formatPhonesWithMultipleLeadsFallback(array $result): string
    {
        $multiPhones = (int) ($result['phones_with_multiple_crm_leads'] ?? 0);
        $whatsappMulti = (int) ($result['whatsapp_users_with_multiple_crm_leads'] ?? 0);
        $scanned = (int) ($result['total_leads_scanned'] ?? 0);
        $totalDb = (int) ($result['total_leads_in_database'] ?? 0);
        $byCount = is_array($result['by_lead_count'] ?? null) ? $result['by_lead_count'] : [];

        $lines = [
            '## Executive Summary',
            "**{$multiPhones}** phone numbers have more than one CRM lead. **{$whatsappMulti}** of those also have a WhatsApp conversation (same normalized phone).",
            '',
            '## Key Metrics',
            "- Total leads scanned: **{$scanned}** (database total: **{$totalDb}**)",
            "- Phones with multiple CRM leads: **{$multiPhones}**",
            "- WhatsApp users with multiple CRM leads: **{$whatsappMulti}**",
        ];

        foreach ($byCount as $bucket => $count) {
            $lines[] = '- Phones with **'.$bucket.'** leads: **'.$count.'**';
        }

        $samples = is_array($result['sample_phones'] ?? null) ? $result['sample_phones'] : [];
        if ($samples !== []) {
            $lines[] = '';
            $lines[] = '## Sample phones';
            foreach (array_slice($samples, 0, 10) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $types = is_array($row['lead_types'] ?? null) ? implode(', ', $row['lead_types']) : '—';
                $lines[] = sprintf(
                    '%d. **%s** — %d leads (%s)%s',
                    $index + 1,
                    (string) ($row['phone'] ?? '—'),
                    (int) ($row['lead_count'] ?? 0),
                    $types,
                    ! empty($row['has_whatsapp_chat']) ? ' · WhatsApp' : ''
                );
            }
        }

        if (! empty($result['scan_note'])) {
            $lines[] = '';
            $lines[] = '## Note';
            $lines[] = (string) $result['scan_note'];
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function formatSqlAnalyticsFallback(array $result): string
    {
        $title = (string) ($result['title'] ?? 'SQL analytics');
        $explanation = trim((string) ($result['explanation'] ?? ''));
        $tables = is_array($result['tables'] ?? null) ? $result['tables'] : [];
        $queries = is_array($result['queries_executed'] ?? null) ? $result['queries_executed'] : [];

        $totalRows = 0;
        foreach ($tables as $table) {
            if (is_array($table)) {
                $totalRows += (int) ($table['row_count'] ?? count($table['rows'] ?? []));
            }
        }

        $lines = [
            '## Executive Summary',
            $explanation !== '' ? $explanation : "Live SQL analysis for **{$title}**.",
            '',
            '## Key Metrics',
            '- Queries executed: **'.count($queries).'**',
            '- Rows returned: **'.$totalRows.'**',
            '- Source: **'.(string) ($result['generation_source'] ?? 'sql').'**',
        ];

        foreach ($tables as $table) {
            if (! is_array($table)) {
                continue;
            }
            $tableTitle = (string) ($table['title'] ?? $table['id'] ?? 'Result');
            $rows = is_array($table['rows'] ?? null) ? $table['rows'] : [];
            $columns = is_array($table['columns'] ?? null) ? $table['columns'] : [];
            $lines[] = '';
            $lines[] = '## '.$tableTitle;
            $lines[] = '- Rows: **'.(int) ($table['row_count'] ?? count($rows)).'**';

            if ($columns !== [] && $rows !== []) {
                // Prefer compact bullet preview for the first aggregate/detail rows.
                foreach (array_slice($rows, 0, 12) as $index => $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $parts = [];
                    foreach (array_slice($columns, 0, 8) as $col) {
                        $val = $row[$col] ?? null;
                        if (is_array($val) || is_object($val)) {
                            continue;
                        }
                        $parts[] = $col.': '.(string) ($val ?? '—');
                    }
                    if ($parts !== []) {
                        $lines[] = ($index + 1).'. '.implode(' | ', $parts);
                    }
                }
            }
        }

        if ($queries !== []) {
            $lines[] = '';
            $lines[] = '## SQL executed';
            foreach (array_slice($queries, 0, 3) as $q) {
                if (! is_array($q)) {
                    continue;
                }
                $lines[] = '- **'.(string) ($q['id'] ?? 'query').'**: `'.mb_substr((string) ($q['sql'] ?? ''), 0, 240).'`';
            }
        }

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

        $samples = is_array($result['samples'] ?? null) ? $result['samples'] : [];
        if ($samples !== []) {
            $lines[] = '';
            $lines[] = '## Sample cancelled leads';
            foreach (array_slice($samples, 0, 20) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $lines[] = sprintf(
                    '%d. Lead **#%s** %s — enquiry %s | reason: **%s** | followups: %d | remarks: %s',
                    $index + 1,
                    (string) ($row['lead_id'] ?? '—'),
                    (string) ($row['name'] ?? ''),
                    (string) ($row['enquiry_at'] ?? '—'),
                    (string) ($row['cancellation_reason'] ?? '—'),
                    (int) ($row['followups_taken'] ?? 0),
                    trim((string) ($row['initial_remarks'] ?? $row['cancellation_remarks'] ?? '—')) ?: '—'
                );
            }
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
        if ($this->isHardGeminiUnavailable($reason)) {
            return false;
        }
        if (in_array($reason, self::RETRYABLE_REASONS, true)) {
            return true;
        }

        return str_starts_with($reason, 'finish_') && $reason !== 'finish_STOP';
    }

    private function isHardGeminiUnavailable(string $reason): bool
    {
        if (in_array($reason, ['missing_api_key', 'http_401', 'http_403', 'http_429', 'http_503'], true)) {
            return true;
        }

        return str_starts_with($reason, 'api_') && (
            str_contains(strtolower($reason), 'quota')
            || str_contains(strtolower($reason), 'resource_exhausted')
            || str_contains(strtolower($reason), 'rate')
        );
    }

    /**
     * @param  array<string, mixed>  $turn
     */
    private function geminiFailureNote(array $turn): string
    {
        $exact = $this->exactGeminiError($turn);

        return (string) __('admin_business_ai.gemini_unavailable_note', ['error' => $exact]);
    }

    /**
     * @param  array<string, mixed>  $turn
     */
    private function exactGeminiError(array $turn): string
    {
        $message = trim((string) ($turn['message'] ?? ''));
        $reason = trim((string) ($turn['reason'] ?? 'blocked'));

        if ($message !== '') {
            if ($reason !== '' && ! str_contains($message, $reason)) {
                return $message.' ('.$reason.')';
            }

            return $message;
        }

        return $this->blockedErrorMessage($reason !== '' ? $reason : 'blocked');
    }

    private function blockedErrorMessage(string $reason): string
    {
        if ($reason === 'missing_api_key') {
            return (string) __('admin_business_ai.missing_api_key');
        }
        if ($reason === 'http_429') {
            return (string) __('admin_business_ai.gemini_quota_exceeded');
        }
        if (str_starts_with($reason, 'api_')) {
            return mb_substr($reason, 4);
        }
        if (str_starts_with($reason, 'http_')) {
            return (string) __('admin_business_ai.gemini_http_error').' ('.$reason.')';
        }
        if (in_array($reason, ['no_parts', 'no_candidate', 'finish_MALFORMED_FUNCTION_CALL', 'finish_UNEXPECTED_TOOL_CALL'], true)) {
            return (string) __('admin_business_ai.gemini_empty_turn').' ('.$reason.')';
        }
        if ($reason === 'exception') {
            return (string) __('admin_business_ai.gemini_exception');
        }

        return (string) __('admin_business_ai.gemini_error').' Exact error: '.$reason;
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
        if (isset($compact['tables']) && is_array($compact['tables'])) {
            foreach ($compact['tables'] as $i => $table) {
                if (! is_array($table)) {
                    continue;
                }
                if (isset($table['rows']) && is_array($table['rows']) && count($table['rows']) > 25) {
                    $compact['tables'][$i]['rows'] = array_slice($table['rows'], 0, 25);
                    $compact['tables'][$i]['rows_truncated'] = true;
                }
            }
        }
        if (isset($compact['queries_executed']) && is_array($compact['queries_executed']) && count($compact['queries_executed']) > 5) {
            $compact['queries_executed'] = array_slice($compact['queries_executed'], 0, 5);
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
  - Leads phone progression: analyze_leads invalid_to_active_lead_progression — scans all CRM leads (up to 5000), groups by phone, counts invalid→customer/provider/future_customer progressions and WhatsApp chat overlap. NEVER use query_leads for this.
  - Leads multi-lead phones: analyze_leads phones_with_multiple_leads — counts phones with 2+ CRM leads and whatsapp_users_with_multiple_crm_leads. NEVER use query_whatsapp_conversations (25-row cap) for this count.
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
  - **Ad-hoc SQL analytics:** run_sql_analytics — understands the question, generates validated read-only MySQL SELECTs on allowlisted tables, executes them, returns tables + charts. Prefer this when the admin asks for custom columns, charts/graphs, “why” deep-dives, or anything fixed analyze_* tools cannot shape. You may pass `question` and/or a SELECT `sql`. Never invent counts without running this or another tool.
- Lead cancellation reasons: analyze_leads customer_cancellation_reasons (returns by_reason ranked list + samples with enquiry_at, initial_remarks, followups). Provider: provider_cancellation_reasons. Booking cancellations: analyze_bookings cancellation_timing_report OR run_sql_analytics for custom columns/charts — counts canceled+refunded (admin Cancelled tab). NEVER use get_dashboard_snapshot or lead_pipeline for cancellation reasons.
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
