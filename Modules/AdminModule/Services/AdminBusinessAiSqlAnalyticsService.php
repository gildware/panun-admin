<?php

namespace Modules\AdminModule\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\WhatsAppModule\Services\WhatsAppGeminiSupportClient;

/**
 * Natural-language → validated read-only SQL → rows + charts for Talk With AI.
 */
class AdminBusinessAiSqlAnalyticsService
{
    /** @var list<string> */
    private const ALLOWED_TABLES = [
        'bookings',
        'booking_status_histories',
        'booking_followups',
        'booking_cancellation_reasons',
        'booking_customer_cancellation_reasons',
        'booking_provider_cancellation_reasons',
        'booking_details',
        'booking_details_amounts',
        'booking_partial_payments',
        'booking_change_logs',
        'booking_schedule_histories',
        'booking_reopen_events',
        'booking_extra_services',
        'leads',
        'lead_followups',
        'lead_type_histories',
        'lead_cancellation_reasons',
        'provider_cancellation_reasons',
        'lead_invalid_reasons',
        'lead_future_customer_reasons',
        'zones',
        'categories',
        'users',
        'providers',
        'services',
        'whatsapp_conversations',
        'whatsapp_messages',
    ];

    /** @var list<string> */
    private const FORBIDDEN_SQL_TOKENS = [
        'insert', 'update', 'delete', 'drop', 'alter', 'create', 'truncate', 'replace',
        'grant', 'revoke', 'call', 'exec', 'execute', 'load_file', 'outfile', 'dumpfile',
        'infile', 'lock tables', 'unlock tables', 'information_schema', 'performance_schema',
        'mysql.', 'sys.', 'sleep(', 'benchmark(', 'into outfile', 'into dumpfile',
        'handler ', 'purge', 'rename', 'attach', 'detach', 'xp_',
    ];

    /**
     * Credential and identity-document columns. Blocked in SQL and stripped from result rows
     * so they can never reach the Gemini payload or the admin UI, even via SELECT *.
     *
     * @var list<string>
     */
    private const SENSITIVE_COLUMNS = [
        'password', 'remember_token', 'fcm_token',
        'identification_number', 'identification_image',
        'company_identity_number', 'company_identity_images',
    ];

    public function __construct(
        protected WhatsAppGeminiSupportClient $gemini,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function analyze(array $args): array
    {
        if (! config('admin_business_ai.sql_analytics_enabled', true)) {
            return ['ok' => false, 'error' => 'sql_analytics_disabled'];
        }

        $question = trim((string) ($args['question'] ?? ''));
        $providedSql = trim((string) ($args['sql'] ?? ''));
        $maxRows = max(10, min(500, (int) config('admin_business_ai.sql_analytics_max_rows', 200)));
        $maxQueries = max(1, min(5, (int) config('admin_business_ai.sql_analytics_max_queries', 3)));

        $plan = null;
        $generationSource = 'provided_sql';

        if ($providedSql !== '') {
            $plan = [
                'title' => trim((string) ($args['title'] ?? 'Custom SQL analysis')),
                'explanation' => trim((string) ($args['explanation'] ?? '')),
                'queries' => [[
                    'id' => 'main',
                    'sql' => $providedSql,
                    'chart' => is_array($args['chart'] ?? null) ? $args['chart'] : null,
                ]],
            ];
        } elseif ($question !== '') {
            $plan = $this->generatePlanFromQuestion($question);
            $generationSource = 'gemini';
            if ($plan === null) {
                $plan = $this->templatePlanForQuestion($question);
                $generationSource = 'template';
            }
        } else {
            return ['ok' => false, 'error' => 'question_or_sql_required'];
        }

        if ($plan === null || empty($plan['queries']) || ! is_array($plan['queries'])) {
            return [
                'ok' => false,
                'error' => 'sql_generation_failed',
                'hint' => 'Could not produce a safe SELECT for this question. Try rephrasing or use analyze_bookings / analyze_leads.',
            ];
        }

        $executed = [];
        $allCharts = [];
        $allTables = [];
        $errors = [];

        foreach (array_slice($plan['queries'], 0, $maxQueries) as $index => $querySpec) {
            if (! is_array($querySpec)) {
                continue;
            }
            $sql = trim((string) ($querySpec['sql'] ?? ''));
            $validation = $this->validateAndNormalizeSql($sql, $maxRows);
            if (! ($validation['ok'] ?? false)) {
                $errors[] = [
                    'id' => (string) ($querySpec['id'] ?? 'q'.($index + 1)),
                    'error' => $validation['error'] ?? 'invalid_sql',
                    'sql' => $sql,
                ];
                continue;
            }

            $safeSql = (string) $validation['sql'];
            try {
                $rows = $this->executeSelect($safeSql);
            } catch (\Throwable $e) {
                Log::warning('Admin business AI SQL analytics execution failed', [
                    'error' => $e->getMessage(),
                    'sql' => $safeSql,
                ]);
                $errors[] = [
                    'id' => (string) ($querySpec['id'] ?? 'q'.($index + 1)),
                    'error' => 'execution_failed',
                    'message' => $e->getMessage(),
                    'sql' => $safeSql,
                ];
                continue;
            }

            $columns = $rows === [] ? [] : array_keys($rows[0]);
            $tableId = (string) ($querySpec['id'] ?? 'result_'.($index + 1));
            $tableTitle = (string) ($querySpec['title'] ?? $plan['title'] ?? 'Query result');
            $allTables[] = [
                'id' => $tableId,
                'title' => $tableTitle,
                'columns' => $columns,
                'rows' => $rows,
                'row_count' => count($rows),
            ];

            $chartSpec = is_array($querySpec['chart'] ?? null) ? $querySpec['chart'] : null;
            foreach ($this->buildChartsFromRows($rows, $chartSpec, $tableTitle) as $chart) {
                $allCharts[] = $chart;
            }

            $executed[] = [
                'id' => $tableId,
                'title' => $tableTitle,
                'sql' => $safeSql,
                'row_count' => count($rows),
                'columns' => $columns,
            ];
        }

        if ($executed === []) {
            return [
                'ok' => false,
                'error' => 'no_queries_executed',
                'generation_source' => $generationSource,
                'validation_errors' => $errors,
                'schema_hint' => $this->schemaSummaryForPrompt(),
            ];
        }

        return [
            'ok' => true,
            'analysis' => 'sql_analytics',
            'question' => $question !== '' ? $question : null,
            'title' => (string) ($plan['title'] ?? 'SQL analytics'),
            'explanation' => (string) ($plan['explanation'] ?? ''),
            'generation_source' => $generationSource,
            'queries_executed' => $executed,
            'tables' => $allTables,
            'charts' => array_slice($allCharts, 0, 6),
            'errors' => $errors,
            'guardrails' => [
                'read_only' => true,
                'allowed_tables' => self::ALLOWED_TABLES,
                'max_rows_per_query' => $maxRows,
                'max_queries' => $maxQueries,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function generatePlanFromQuestion(string $question): ?array
    {
        $system = <<<'SYS'
You are a MySQL analyst for a home-services admin panel.
Return ONLY valid JSON (no markdown) with this shape:
{
  "title": "short title",
  "explanation": "1-2 sentences",
  "queries": [
    {
      "id": "reasons",
      "title": "Cancellation reasons",
      "sql": "SELECT ... LIMIT 100",
      "chart": {"type":"bar","title":"...","label_column":"reason","value_column":"cnt"}
    }
  ]
}
Rules:
- MySQL 8 SELECT or WITH…SELECT only. No writes, no SHOW/DESCRIBE, no INFORMATION_SCHEMA.
- Use only allowlisted tables from the schema.
- Prefer 1–3 queries: aggregates for charts, then a detail sample with useful columns.
- Always include LIMIT (<= 200 for detail, <= 50 for aggregates is fine).
- Cancelled bookings = booking_status IN ('canceled','cancelled','refunded') — matches admin Cancelled tab.
- Cancellation reason: join booking_status_histories (latest canceled/refunded row) to booking_cancellation_reasons.
- For "why cancelled" include reason counts, status-before-cancel if possible, zone/category, followup counts, enquiry/created dates, remarks.
- Chart types: bar|column|donut. label_column + value_column must exist in that query's SELECT aliases.
SYS;

        $user = "SCHEMA:\n".$this->schemaSummaryForPrompt()."\n\nQUESTION:\n".$question;

        try {
            $raw = $this->gemini->generatePlainText(
                $system,
                $user,
                null,
                2048,
                (int) config('admin_business_ai.gemini_http_timeout', 90)
            );
        } catch (\Throwable $e) {
            Log::warning('Admin business AI SQL plan generation failed', ['error' => $e->getMessage()]);

            return null;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        return $this->parsePlanJson($raw);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parsePlanJson(string $raw): ?array
    {
        $text = trim($raw);
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $text, $m)) {
            $text = trim($m[1]);
        }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $json = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($json, true);
        if (! is_array($decoded) || empty($decoded['queries']) || ! is_array($decoded['queries'])) {
            return null;
        }

        return $decoded;
    }

    /**
     * Deterministic plans for common intents when Gemini is unavailable or fails.
     *
     * @return array<string, mixed>|null
     */
    private function templatePlanForQuestion(string $question): ?array
    {
        $q = strtolower($question);

        if (preg_match('/\b(cancel|cancelled|canceled|cancellation|refunded)\b/', $q)
            && preg_match('/\b(booking|bookings|order|orders)\b/', $q)) {
            return [
                'title' => 'Cancelled bookings analysis',
                'explanation' => 'Live SQL over canceled/refunded bookings (admin Cancelled tab), with reasons, stage, followups, and sample detail rows.',
                'queries' => [
                    [
                        'id' => 'cancel_reasons',
                        'title' => 'Cancellation reasons',
                        'sql' => <<<'SQL'
SELECT
  COALESCE(r.name, '(No reason recorded)') AS reason,
  COUNT(*) AS cnt
FROM bookings b
LEFT JOIN booking_status_histories h
  ON h.id = (
    SELECT h2.id
    FROM booking_status_histories h2
    WHERE h2.booking_id = b.id
      AND h2.booking_status IN ('canceled', 'cancelled', 'refunded')
    ORDER BY h2.created_at DESC, h2.id DESC
    LIMIT 1
  )
LEFT JOIN booking_cancellation_reasons r
  ON r.id = h.booking_cancellation_reason_id
WHERE b.booking_status IN ('canceled', 'cancelled', 'refunded')
GROUP BY COALESCE(r.name, '(No reason recorded)')
ORDER BY cnt DESC
LIMIT 30
SQL,
                        'chart' => [
                            'type' => 'bar',
                            'title' => 'Cancellation reasons',
                            'label_column' => 'reason',
                            'value_column' => 'cnt',
                        ],
                    ],
                    [
                        'id' => 'cancel_by_hour',
                        'title' => 'Cancelled bookings by created hour',
                        'sql' => <<<'SQL'
SELECT
  HOUR(b.created_at) AS created_hour,
  COUNT(*) AS cnt
FROM bookings b
WHERE b.booking_status IN ('canceled', 'cancelled', 'refunded')
GROUP BY HOUR(b.created_at)
ORDER BY created_hour
LIMIT 24
SQL,
                        'chart' => [
                            'type' => 'column',
                            'title' => 'Created hour of cancelled bookings',
                            'label_column' => 'created_hour',
                            'value_column' => 'cnt',
                        ],
                    ],
                    [
                        'id' => 'cancel_sample',
                        'title' => 'Cancelled booking detail sample',
                        'sql' => <<<'SQL'
SELECT
  b.readable_id,
  b.booking_status AS status,
  b.created_at AS enquiry_at,
  z.name AS zone,
  c.name AS category,
  TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) AS assignee,
  COALESCE(r.name, '(No reason recorded)') AS cancellation_reason,
  h.status_change_remarks AS cancellation_remarks,
  l.remarks AS initial_remarks,
  l.date_time_of_lead_received AS lead_enquiry_at,
  (
    SELECT COUNT(*)
    FROM booking_followups f
    WHERE f.booking_id = b.id
  ) AS followups_taken,
  (
    SELECT MIN(f.date)
    FROM booking_followups f
    WHERE f.booking_id = b.id
  ) AS first_followup_at,
  b.after_visit_cancel,
  b.is_paid,
  b.total_booking_amount
FROM bookings b
LEFT JOIN zones z ON z.id = b.zone_id
LEFT JOIN categories c ON c.id = b.category_id
LEFT JOIN users u ON u.id = b.assignee_id
LEFT JOIN leads l ON l.id = b.lead_id
LEFT JOIN booking_status_histories h
  ON h.id = (
    SELECT h2.id
    FROM booking_status_histories h2
    WHERE h2.booking_id = b.id
      AND h2.booking_status IN ('canceled', 'cancelled', 'refunded')
    ORDER BY h2.created_at DESC, h2.id DESC
    LIMIT 1
  )
LEFT JOIN booking_cancellation_reasons r
  ON r.id = h.booking_cancellation_reason_id
WHERE b.booking_status IN ('canceled', 'cancelled', 'refunded')
ORDER BY b.created_at DESC
LIMIT 100
SQL,
                        'chart' => null,
                    ],
                ],
            ];
        }

        if (preg_match('/\b(cancel|cancelled|canceled|cancellation)\b/', $q)
            && preg_match('/\b(lead|leads|crm)\b/', $q)) {
            return [
                'title' => 'Cancelled customer leads analysis',
                'explanation' => 'Customer leads marked cancelled, with reasons, enquiry date, remarks, and followups.',
                'queries' => [
                    [
                        'id' => 'lead_cancel_reasons',
                        'title' => 'Customer lead cancellation reasons',
                        'sql' => <<<'SQL'
SELECT
  COALESCE(r.name, '(No reason recorded)') AS reason,
  COUNT(*) AS cnt
FROM leads l
INNER JOIN lead_type_histories h
  ON h.id = (
    SELECT h2.id
    FROM lead_type_histories h2
    WHERE h2.lead_id = l.id
      AND h2.type = 'customer'
    ORDER BY h2.created_at DESC, h2.id DESC
    LIMIT 1
  )
LEFT JOIN lead_cancellation_reasons r
  ON r.id = CAST(JSON_UNQUOTE(JSON_EXTRACT(h.data, '$.cancellation_reason_id')) AS UNSIGNED)
WHERE l.lead_type = 'customer'
  AND (
    CAST(JSON_UNQUOTE(JSON_EXTRACT(h.data, '$.cancellation_reason_id')) AS UNSIGNED) > 0
    OR LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(h.data, '$.status_base_type')), '')) = 'cancel'
  )
GROUP BY COALESCE(r.name, '(No reason recorded)')
ORDER BY cnt DESC
LIMIT 30
SQL,
                        'chart' => [
                            'type' => 'bar',
                            'title' => 'Lead cancellation reasons',
                            'label_column' => 'reason',
                            'value_column' => 'cnt',
                        ],
                    ],
                ],
            ];
        }

        return null;
    }

    /**
     * @return array{ok: bool, sql?: string, error?: string}
     */
    public function validateAndNormalizeSql(string $sql, int $maxRows): array
    {
        $sql = trim($sql);
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
        if ($sql === '') {
            return ['ok' => false, 'error' => 'empty_sql'];
        }

        // Strip trailing semicolons; reject multi-statement.
        $sql = rtrim($sql, " \t\n\r\0\x0B;");
        if (str_contains($sql, ';')) {
            return ['ok' => false, 'error' => 'multiple_statements_not_allowed'];
        }

        if (preg_match('/(--|\/\*|\*\/|#)/', $sql)) {
            return ['ok' => false, 'error' => 'sql_comments_not_allowed'];
        }

        $normalized = preg_replace('/\s+/', ' ', strtolower($sql)) ?? strtolower($sql);
        if (! preg_match('/^(select|with)\b/', $normalized)) {
            return ['ok' => false, 'error' => 'only_select_or_with_allowed'];
        }

        foreach (self::FORBIDDEN_SQL_TOKENS as $token) {
            $token = strtolower(trim($token));
            if ($token === '') {
                continue;
            }
            $pattern = '/\b'.preg_quote(rtrim($token, ' ('), '/').'\b/i';
            if (str_ends_with($token, '(')) {
                $pattern = '/\b'.preg_quote(substr($token, 0, -1), '/').'\s*\(/i';
            } elseif (str_contains($token, '.')) {
                $pattern = '/'.preg_quote($token, '/').'/i';
            } elseif (str_contains($token, ' ')) {
                $pattern = '/'.preg_quote($token, '/').'/i';
            }
            if (preg_match($pattern, $normalized)) {
                return ['ok' => false, 'error' => 'forbidden_token:'.$token];
            }
        }

        foreach (self::SENSITIVE_COLUMNS as $column) {
            if (preg_match('/\b'.preg_quote($column, '/').'\b/i', $normalized)) {
                return ['ok' => false, 'error' => 'sensitive_column_not_allowed:'.$column];
            }
        }

        // Extract table references after FROM / JOIN.
        if (! preg_match_all('/\b(?:from|join)\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $matches)) {
            return ['ok' => false, 'error' => 'no_table_references'];
        }
        $allowed = array_fill_keys(self::ALLOWED_TABLES, true);
        foreach ($matches[1] as $table) {
            $table = strtolower($table);
            if (! isset($allowed[$table])) {
                return ['ok' => false, 'error' => 'table_not_allowlisted:'.$table];
            }
        }

        // Force / cap LIMIT.
        if (preg_match('/\blimit\s+(\d+)\s*(?:,\s*\d+)?\s*$/i', $sql, $lim)) {
            $limit = (int) $lim[1];
            if ($limit > $maxRows) {
                $sql = preg_replace('/\blimit\s+\d+\s*(?:,\s*\d+)?\s*$/i', 'LIMIT '.$maxRows, $sql) ?? $sql;
            }
        } else {
            $sql .= ' LIMIT '.$maxRows;
        }

        return ['ok' => true, 'sql' => $sql];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function executeSelect(string $sql): array
    {
        $rows = DB::select($sql);
        $sensitive = array_fill_keys(self::SENSITIVE_COLUMNS, true);

        return array_map(static function ($row) use ($sensitive) {
            $arr = (array) $row;
            foreach ($arr as $k => $v) {
                if (isset($sensitive[strtolower((string) $k)])) {
                    unset($arr[$k]);

                    continue;
                }
                if ($v instanceof \DateTimeInterface) {
                    $arr[$k] = $v->format('c');
                }
            }

            return $arr;
        }, $rows);
    }

    /**
     * @param  list<object|array<string, mixed>>  $rawRows
     * @param  array<string, mixed>|null  $chartSpec
     * @return list<array<string, mixed>>
     */
    private function buildChartsFromRows(array $rawRows, ?array $chartSpec, string $fallbackTitle): array
    {
        $rows = array_map(static function ($row) {
            return (array) $row;
        }, $rawRows);

        if ($rows === []) {
            return [];
        }

        $charts = [];
        if ($chartSpec) {
            $labelCol = (string) ($chartSpec['label_column'] ?? '');
            $valueCol = (string) ($chartSpec['value_column'] ?? '');
            if ($labelCol !== '' && $valueCol !== '' && isset($rows[0][$labelCol]) && isset($rows[0][$valueCol])) {
                $labels = [];
                $values = [];
                foreach (array_slice($rows, 0, 24) as $row) {
                    $labels[] = (string) $row[$labelCol];
                    $values[] = is_numeric($row[$valueCol]) ? 0 + $row[$valueCol] : 0;
                }
                $type = strtolower((string) ($chartSpec['type'] ?? 'bar'));
                $charts[] = [
                    'id' => 'sql_'.md5($labelCol.$valueCol.$fallbackTitle),
                    'type' => in_array($type, ['bar', 'column', 'donut', 'pie'], true) ? $type : 'bar',
                    'title' => (string) ($chartSpec['title'] ?? $fallbackTitle),
                    'labels' => $labels,
                    'series' => $type === 'donut' || $type === 'pie'
                        ? $values
                        : [['name' => $valueCol, 'data' => $values]],
                ];

                return $charts;
            }
        }

        // Heuristic: 2–3 columns with one string-ish + one numeric.
        $columns = array_keys($rows[0]);
        if (count($columns) >= 2) {
            $labelCol = null;
            $valueCol = null;
            foreach ($columns as $col) {
                $sample = $rows[0][$col] ?? null;
                if ($valueCol === null && is_numeric($sample)) {
                    $valueCol = $col;
                } elseif ($labelCol === null && ! is_numeric($sample)) {
                    $labelCol = $col;
                }
            }
            if ($labelCol && $valueCol) {
                $labels = [];
                $values = [];
                foreach (array_slice($rows, 0, 24) as $row) {
                    $labels[] = (string) $row[$labelCol];
                    $values[] = is_numeric($row[$valueCol]) ? 0 + $row[$valueCol] : 0;
                }
                $charts[] = [
                    'id' => 'sql_auto_'.md5($labelCol.$valueCol),
                    'type' => 'bar',
                    'title' => $fallbackTitle,
                    'labels' => $labels,
                    'series' => [['name' => $valueCol, 'data' => $values]],
                ];
            }
        }

        return $charts;
    }

    private function schemaSummaryForPrompt(): string
    {
        return <<<'SCHEMA'
bookings(id, readable_id, customer_id, provider_id, zone_id, category_id, sub_category_id, assignee_id, lead_id, booking_status, is_paid, payment_method, total_booking_amount, service_schedule, service_description, after_visit_cancel, settlement_outcome, created_at, updated_at)
booking_status_histories(id, booking_id, changed_by, booking_status, booking_cancellation_reason_id, status_change_remarks, created_at)
booking_cancellation_reasons(id, name, description, is_active)
booking_followups(id, booking_id, date, reason, for, status, remarks, created_by, created_at)
leads(id, name, phone_number, lead_type, date_time_of_lead_received, handled_by, remarks, next_followup_at, created_at)
lead_followups(id, lead_id, followup_at, remarks, created_by, created_at)
lead_type_histories(id, lead_id, type, data JSON, created_at)
lead_cancellation_reasons(id, name)
zones(id, name, is_active)
categories(id, parent_id, name, position, is_active)
users(id, first_name, last_name, email, phone, user_type)
providers(id, company_name, company_phone, contact_person_name, zone relations via other tables)
services(id, name, category_id, sub_category_id, is_active)
Notes:
- Cancelled bookings: booking_status IN ('canceled','cancelled','refunded')
- Prefer LEFT JOIN zones/categories/users/leads for readable labels
SCHEMA;
    }
}
