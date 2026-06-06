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

    'max_query_limit' => 50,
];
