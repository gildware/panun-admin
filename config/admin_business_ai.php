<?php

return [
    'enabled' => env('ADMIN_BUSINESS_AI_ENABLED', true),

    'gemini_model' => env('ADMIN_BUSINESS_AI_MODEL', env('GEMINI_MODEL', 'gemini-2.5-flash')),

    'max_tool_rounds' => max(4, min(16, (int) env('ADMIN_BUSINESS_AI_MAX_TOOL_ROUNDS', 10))),

    'max_output_tokens' => max(512, min(8192, (int) env('ADMIN_BUSINESS_AI_MAX_OUTPUT_TOKENS', 4096))),

    'max_tool_response_bytes' => max(4000, min(32000, (int) env('ADMIN_BUSINESS_AI_MAX_TOOL_RESPONSE_BYTES', 20000))),

    'gemini_http_timeout' => max(15, min(180, (int) env('ADMIN_BUSINESS_AI_HTTP_TIMEOUT', 90))),

    'context_turn_limit' => max(4, min(40, (int) env('ADMIN_BUSINESS_AI_CONTEXT_TURNS', 20))),

    'session_ttl_minutes' => max(30, min(10080, (int) env('ADMIN_BUSINESS_AI_SESSION_TTL', 1440))),

    'default_query_limit' => 25,

    // List tools (query_leads, query_bookings, etc.) never return more than this per call.
    'max_query_limit' => max(25, min(200, (int) env('ADMIN_BUSINESS_AI_MAX_QUERY_LIMIT', 100))),

    'max_explore_tools' => max(3, min(8, (int) env('ADMIN_BUSINESS_AI_MAX_EXPLORE_TOOLS', 6))),

    // Natural-language → validated read-only SQL analytics.
    'sql_analytics_enabled' => filter_var(env('ADMIN_BUSINESS_AI_SQL_ANALYTICS', true), FILTER_VALIDATE_BOOL),
    'sql_analytics_max_rows' => max(10, min(500, (int) env('ADMIN_BUSINESS_AI_SQL_MAX_ROWS', 200))),
    'sql_analytics_max_queries' => max(1, min(5, (int) env('ADMIN_BUSINESS_AI_SQL_MAX_QUERIES', 3))),
    'sql_analytics_timeout_ms' => max(1000, min(60000, (int) env('ADMIN_BUSINESS_AI_SQL_TIMEOUT_MS', 15000))),
];
