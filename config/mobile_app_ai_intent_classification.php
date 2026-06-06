<?php

return [
    /** AI is the primary language-understanding layer for every message. */
    'ai_primary' => env('MOBILE_APP_AI_PRIMARY_UNDERSTANDING', true),

    /** Use Gemini for NLU (always when ai_primary; otherwise when rule confidence is low). */
    'use_gemini' => env('MOBILE_APP_AI_INTENT_GEMINI', true),

    /**
     * AI-only NLU — no regex/rule fallback or regex intent overrides.
     * When Gemini is unavailable the message is classified as unknown (soft clarify).
     */
    'ai_only_understanding' => env('MOBILE_APP_AI_ONLY_UNDERSTANDING', true),

    /** Optional ultra-fast regex shortcuts (disabled when AI-primary). */
    'fast_routes_enabled' => env('MOBILE_APP_AI_FAST_ROUTES', false),

    /** Rule-based result used directly when confidence >= this (legacy merge path). */
    'rule_confidence_direct' => 0.82,

    /** Minimum confidence to accept any classification (else unknown). */
    'min_confidence' => 0.45,

    /** Prefer rule over AI when rule confidence >= this and intent is a summary. */
    'rule_wins_over_gemini' => 0.88,

    /** Block booking_start when cart family rule scored >= this. */
    'cart_blocks_booking_threshold' => 0.55,

    /** Resolve cart remove/reschedule targets with Gemini + live cart catalog. */
    'cart_action_use_gemini' => env('MOBILE_APP_AI_CART_ACTION_GEMINI', true),
];
