<?php

return [
    'confidence' => [
        'execute' => (float) env('MOBILE_APP_AI_CONF_EXECUTE', 0.85),
        'clarify_min' => (float) env('MOBILE_APP_AI_CONF_CLARIFY_MIN', 0.60),
        'gemini_below' => (float) env('MOBILE_APP_AI_CONF_GEMINI_BELOW', 0.60),
    ],

    'multi_intent' => [
        'enabled' => env('MOBILE_APP_AI_MULTI_INTENT', true),
        'max_per_message' => 3,
    ],

    'escalation' => [
        'enabled' => env('MOBILE_APP_AI_ESCALATION', true),
        'max_fallbacks' => 3,
        'max_failed_intents' => 3,
        'angry_keywords' => ['angry', 'fraud', 'scam', 'useless', 'worst', 'refund now', 'police'],
    ],

    'cost' => [
        'enabled' => env('MOBILE_APP_AI_COST_GUARD', true),
        'messages_per_user_per_day' => (int) env('MOBILE_APP_AI_MSG_LIMIT', 200),
        'gemini_calls_per_user_per_day' => (int) env('MOBILE_APP_AI_GEMINI_LIMIT', 80),
    ],

    'analytics' => [
        'enabled' => env('MOBILE_APP_AI_ANALYTICS', true),
    ],

    'resume_booking' => [
        'enabled' => env('MOBILE_APP_AI_RESUME_BOOKING', true),
        'stale_hours' => 24,
    ],

    'proactive' => [
        'enabled' => env('MOBILE_APP_AI_PROACTIVE', true),
    ],

    /**
     * WhatsApp-style agent: Gemini understands, calls tools, and writes the customer reply.
     * When enabled, normal messages skip the intent-classifier/dispatcher pipeline.
     */
    'conversational_agent' => [
        'enabled' => env('MOBILE_APP_AI_CONVERSATIONAL_AGENT', true),
    ],
];
