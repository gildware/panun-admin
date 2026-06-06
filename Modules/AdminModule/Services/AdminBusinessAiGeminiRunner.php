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
            .'Reply in markdown with ## headings. For focused questions, ## Executive Summary, ## Key Metrics, and a short ## Detailed Analysis are enough. '
            .'Do not call any more tools — write the final answer now.';
    }

    private function shouldUseServerToolFallback(string $userMessage): bool
    {
        return $this->inferToolsForQuestion($userMessage) !== [];
    }

    /**
     * @return list<array{name: string, args: array<string, mixed>}>
     */
    private function inferToolsForQuestion(string $userMessage): array
    {
        $text = strtolower(trim($userMessage));
        if ($text === '') {
            return [];
        }

        $tools = [];

        if (preg_match('/\b(zone|zones|area|areas|region|regions|location|locations|city|cities|locality|localities)\b/i', $userMessage)
            && preg_match('/\b(booking|bookings|order|orders)\b/i', $userMessage)) {
            $tools[] = ['name' => 'get_business_reports', 'args' => ['report_type' => 'booking_analytics']];
        }

        if (preg_match('/\b(whatsapp|chat|chats|inbox|unassigned|human support)\b/i', $userMessage)) {
            $tools[] = ['name' => 'get_whatsapp_conversations_overview', 'args' => []];
        }

        if (preg_match('/\b(employee|staff|agent|handled by|who is handling|workload|incomplete|unspecified|missing data|not filled)\b/i', $userMessage)) {
            $tools[] = ['name' => 'analyze_employee_activity', 'args' => ['analysis' => 'full_employee_overview']];
        }

        if (preg_match('/\b(lead|leads|pipeline|crm|followup|follow-up)\b/i', $userMessage)) {
            $tools[] = ['name' => 'get_business_reports', 'args' => ['report_type' => 'lead_pipeline']];
        }

        if (preg_match('/\b(dashboard|widget|ledger|followup|follow-up|top provider|top customer)\b/i', $userMessage)) {
            $tools[] = ['name' => 'get_dashboard_snapshot', 'args' => []];
        }

        if (preg_match('/\b(relation|related|linked|connect|connection|same phone|who handles)\b/i', $userMessage)) {
            $tools[] = ['name' => 'get_entity_relations', 'args' => []];
        }

        if (preg_match('/\b(revenue|earning|earnings|financial|profit|payable|money)\b/i', $userMessage)) {
            $tools[] = ['name' => 'get_business_dashboard_overview', 'args' => []];
        }

        if (preg_match('/\b(full|complete|health|overview|analysis|report|summary)\b/i', $userMessage)
            && ! preg_match('/\b(zone|area|booking|lead|whatsapp|customer|provider)\b/i', $userMessage)) {
            $tools[] = ['name' => 'get_dashboard_snapshot', 'args' => []];
        }

        return array_slice($tools, 0, 2);
    }

    /**
     * @param  list<array<string, mixed>>  $contents
     * @param  list<array{name: string, result: array<string, mixed>}>  $toolResultsBag
     */
    private function injectServerToolFallback(string $userMessage, array &$contents, array &$toolResultsBag): bool
    {
        $planned = $this->inferToolsForQuestion($userMessage);
        if ($planned === []) {
            return false;
        }

        $modelParts = [];
        $userParts = [];
        foreach ($planned as $plan) {
            $name = (string) $plan['name'];
            $args = is_array($plan['args'] ?? null) ? $plan['args'] : [];
            $modelParts[] = [
                'functionCall' => [
                    'name' => $name,
                    'args' => (object) $args,
                ],
            ];
            $result = $this->compactToolResult($this->toolExecutor->execute($name, $args));
            $toolResultsBag[] = ['name' => $name, 'result' => $result];
            $userParts[] = [
                'functionResponse' => [
                    'name' => $name,
                    'response' => $result,
                ],
            ];
        }

        $contents[] = ['role' => 'model', 'parts' => $modelParts];
        $contents[] = ['role' => 'user', 'parts' => $userParts];
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

            if (($result['report_type'] ?? '') === 'booking_analytics' || isset($result['data']['zone_wise'])) {
                $payload = ($result['report_type'] ?? '') === 'booking_analytics'
                    ? (is_array($result['data'] ?? null) ? $result['data'] : [])
                    : (is_array($result['data'] ?? null) ? $result['data'] : $result);

                return $this->formatBookingAnalyticsFallback($payload, $userMessage);
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatBookingAnalyticsFallback(array $data, string $userMessage): string
    {
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $zones = is_array($data['zone_wise'] ?? null) ? $data['zone_wise'] : [];
        $total = (int) ($summary['total'] ?? 0);
        $missingZone = (int) ($summary['missing_zone'] ?? 0);

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
        foreach (['leads', 'bookings', 'providers', 'customers', 'conversations', 'outbound_enquiries', 'incomplete_leads', 'employees'] as $listKey) {
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
        if (isset($compact['booking']) && is_array($compact['booking'])) {
            foreach (['followups', 'partial_payments', 'status_history', 'change_logs', 'reopen_events', 'incidents'] as $nested) {
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
- For broad questions, call at most 2–3 tools per turn, then write your analysis.
- **Full admin-tab data is available via tools:**
  - Leads: query_leads, get_lead_details, analyze_leads, query_outbound_enquiries — includes type_history (cancellation reasons/remarks), followups, checklist, pipeline status.
  - Bookings: query_bookings, get_booking_details, analyze_bookings — followups, partial payments, settlement, repeats, compensations, reopen, status/change history, lead link.
  - Customers: query_customers, get_customer_details, analyze_customers — overview, addresses, wallet/loyalty, performance, incidents, reviews, payments.
  - Providers: query_providers, get_provider_details, analyze_providers — zones, bank, services, servicemen, performance, incidents, bookings.
  - Dashboard: get_business_dashboard_overview (KPIs) or get_dashboard_snapshot (full widgets: ledger, followups, top lists, earning chart).
  - Relations: get_entity_relations — link phone/lead/booking/customer/provider/WhatsApp/outbound enquiry in one call.
  - WhatsApp: get_whatsapp_conversations_overview, query_whatsapp_conversations, get_whatsapp_conversation_details — chat_handler (inbox assignee) vs lead_handler (CRM); linked_lead_is_customer; system bookings on thread.
  - Employees: analyze_employee_activity (workload, chats, bookings, incomplete leads per handler), query_incomplete_leads (unspecified/missing lead fields + who handles + booking link).
- For cancellation reason questions: analyze_leads with analysis=customer_cancellation_reasons (or provider_cancellation_reasons).
- For employee performance / who handles chats / incomplete data: analyze_employee_activity or query_incomplete_leads.
- For geography / zone booking questions: get_business_reports report_type=booking_analytics (zone_wise).
- Cross-reference domains: use get_entity_relations when asked how records connect; report chat_handler vs lead_handler for WhatsApp.
- You are read-only — never claim you changed data.

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
