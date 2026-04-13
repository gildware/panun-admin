<?php

return [
    'name' => 'WhatsAppModule',
    // Local admin DB tables (single source of truth for WhatsApp data).
    'tables' => [
        'conversation' => 'whatsapp_conversations',
        'messages' => 'whatsapp_messages',
        'bookings' => 'whatsapp_bookings',
        'provider_lead' => 'whatsapp_provider_leads',
        'users' => 'whatsapp_users',
        'booking_automation_logs' => 'whatsapp_booking_automation_message_logs',
    ],
    // Cache TTL in seconds for list/panel data. Set to 0 to disable.
    'cache_ttl' => (int) env('WHATSAPP_CACHE_TTL', 60),
    // Cache TTL for per-chat panel (messages + state). Shorter so replies feel fresh.
    'cache_ttl_chat' => (int) env('WHATSAPP_CACHE_TTL_CHAT', 20),
    // Max messages to load per chat (smaller = faster).
    'messages_limit' => (int) env('WHATSAPP_MESSAGES_LIMIT', 100),
    // Cache normalized phone sets for customer/provider matching (admin WhatsApp UI). 0 = no cache.
    'system_phone_match_cache_ttl' => (int) env('WHATSAPP_SYSTEM_PHONE_MATCH_CACHE_TTL', 300),

    // AI support (Gemini + tools). Set GEMINI_API_KEY to enable auto-replies on inbound webhooks.
    // Default on when env omitted; set WHATSAPP_AI_SUPPORT_ENABLED=false to disable.
    'ai_support_enabled' => filter_var(env('WHATSAPP_AI_SUPPORT_ENABLED', true), FILTER_VALIDATE_BOOL),
    'ai_greeting_buttons' => (bool) env('WHATSAPP_AI_GREETING_BUTTONS', true),
    // gemini-2.0-* is deprecated and often returns HTTP 404; use 2.5 or 1.5.
    // gemini-2.5-flash-lite is fastest for chat; use gemini-2.5-flash if you need higher quality.
    'gemini_model' => env('WHATSAPP_GEMINI_MODEL', 'gemini-2.5-flash-lite'),
    /** HTTP timeout seconds for each Gemini generateContent request. */
    'gemini_http_timeout' => max(5, min(120, (int) env('WHATSAPP_GEMINI_HTTP_TIMEOUT', 32))),
    /** Lower = faster responses (and shorter replies). */
    'gemini_max_output_tokens' => max(256, min(8192, (int) env('WHATSAPP_GEMINI_MAX_OUTPUT_TOKENS', 896))),
    'gemini_temperature' => (float) env('WHATSAPP_GEMINI_TEMPERATURE', 0.35),
    /** Max prior chat turns sent to Gemini (each IN/OUT pair may use two turns). Smaller = faster. */
    'ai_gemini_context_turn_limit' => max(6, min(40, (int) env('WHATSAPP_AI_GEMINI_CONTEXT_TURNS', 18))),
    /** Max characters per past message in context. */
    'ai_gemini_context_char_limit' => max(400, min(8000, (int) env('WHATSAPP_AI_GEMINI_CONTEXT_CHAR_LIMIT', 2200))),
    /** Max model↔tool rounds per inbound message (each round is at least one API call). */
    'ai_gemini_max_tool_rounds' => max(2, min(12, (int) env('WHATSAPP_AI_GEMINI_MAX_TOOL_ROUNDS', 6))),
    // IST work hours for human handoff messaging (24h format H:i).
    'support_work_hours_start' => env('WHATSAPP_SUPPORT_HOURS_START', '09:00'),
    'support_work_hours_end' => env('WHATSAPP_SUPPORT_HOURS_END', '18:00'),
    /** ISO weekdays 1=Mon … 7=Sun when DB has no db_support_days. */
    'support_work_days' => [1, 2, 3, 4, 5],
    /** Operational support times are always interpreted in IST for customers and AI. */
    'support_timezone' => 'Asia/Kolkata',
    /** Stored + displayed times for whatsapp_messages in admin chat (IANA zone, e.g. Asia/Kolkata). */
    'message_timezone' => env('WHATSAPP_MESSAGE_TIMEZONE', 'Asia/Kolkata'),
    'support_phone_display' => env('WHATSAPP_SUPPORT_PHONE_DISPLAY', ''),

    /**
     * Fallbacks when WhatsApp AI placeholder DB overrides and business settings are empty.
     */
    'placeholder_default_email' => env('WHATSAPP_PLACEHOLDER_DEFAULT_EMAIL', 'contact@panunkaergar.com'),
    'placeholder_default_website' => env('WHATSAPP_PLACEHOLDER_DEFAULT_WEBSITE', 'www.panunkaergar.com'),
    'placeholder_default_address' => env('WHATSAPP_PLACEHOLDER_DEFAULT_ADDRESS', 'Panun Kaergar Main Road Jawahar Nagar Srinagar - 190008'),
    'placeholder_default_tagline' => env('WHATSAPP_PLACEHOLDER_DEFAULT_TAGLINE', 'Ghar ki har zaroorat bas ek click dooer'),

    // When true, AI reply runs in the webhook request (no queue worker). Set false if you use redis/database queue + workers.
    'ai_dispatch_sync' => filter_var(env('WHATSAPP_AI_DISPATCH_SYNC', true), FILTER_VALIDATE_BOOL),

    /** Max polite "please clarify" rounds before auto handoff (report_unclear_user_intent tool). Handoff on the next unclear after this many clarifications. */
    'ai_unclear_max_clarify_rounds' => max(1, min(5, (int) env('WHATSAPP_AI_UNCLEAR_MAX_CLARIFY_ROUNDS', 2))),

    /**
     * Sandbox "phone" for admin AI playground + tests. Messages use this whatsapp_messages.phone;
     * outbound WhatsApp Cloud sends are skipped (see WhatsAppAiPlayground).
     */
    'ai_playground_phone' => env('WHATSAPP_AI_PLAYGROUND_PHONE', 'AI_TEST_SANDBOX'),
    /** Optional extra sandbox keys (exact match), comma-separated in env. */
    'ai_playground_phones_extra' => array_values(array_filter(array_map('trim', explode(',', (string) env('WHATSAPP_AI_PLAYGROUND_PHONES_EXTRA', ''))))),

    /** Minimum match score to auto-apply zone from address (WhatsAppZoneAddressMatcher). */
    'ai_zone_match_min_score_high' => (float) env('WHATSAPP_AI_ZONE_MATCH_MIN_SCORE_HIGH', 10),
    /** If second-best score ≥ top × this ratio, treat as ambiguous (no auto zone). */
    'ai_zone_match_ambiguity_ratio' => (float) env('WHATSAPP_AI_ZONE_MATCH_AMBIGUITY_RATIO', 0.88),

    /**
     * Optional HTTPS origin for files on the public disk when Meta must fetch a URL (template DOCUMENT header + invoice).
     * Leave empty to derive from APP_URL / filesystems public disk. Use your real public domain in production, or an
     * ngrok-style base for local testing (Meta cannot reach 127.0.0.1).
     */
    'public_media_base_url' => rtrim((string) env('WHATSAPP_PUBLIC_MEDIA_BASE_URL', ''), '/'),

    /**
     * Meta Business Suite — WhatsApp Manager message templates (create templates here; admin syncs via Sync Templates).
     */
    'meta_message_templates_url' => env(
        'WHATSAPP_META_MESSAGE_TEMPLATES_URL',
        'https://business.facebook.com/latest/whatsapp_manager/message_templates/?business_id=2551075801745646&tab=message-templates&filters=%7B%22date_range%22%3A7%2C%22language%22%3A[]%2C%22quality%22%3A[]%2C%22search_text%22%3A%22%22%2C%22status%22%3A[%22APPROVED%22%2C%22IN_APPEAL%22%2C%22PAUSED%22%2C%22PENDING%22%2C%22REJECTED%22]%2C%22tag%22%3A[]%7D&nav_ref=whatsapp_manager&asset_id=2142338593236143'
    ),
];
