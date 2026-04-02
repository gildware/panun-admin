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
    'gemini_model' => env('WHATSAPP_GEMINI_MODEL', 'gemini-2.5-flash'),
    // IST work hours for human handoff messaging (24h format H:i).
    'support_work_hours_start' => env('WHATSAPP_SUPPORT_HOURS_START', '09:00'),
    'support_work_hours_end' => env('WHATSAPP_SUPPORT_HOURS_END', '18:00'),
    'support_timezone' => env('WHATSAPP_SUPPORT_TIMEZONE', 'Asia/Kolkata'),
    /** Stored + displayed times for whatsapp_messages in admin chat (IANA zone, e.g. Asia/Kolkata). */
    'message_timezone' => env('WHATSAPP_MESSAGE_TIMEZONE', 'Asia/Kolkata'),
    'support_phone_display' => env('WHATSAPP_SUPPORT_PHONE_DISPLAY', ''),

    // When true, AI reply runs in the webhook request (no queue worker). Set false if you use redis/database queue + workers.
    'ai_dispatch_sync' => filter_var(env('WHATSAPP_AI_DISPATCH_SYNC', true), FILTER_VALIDATE_BOOL),
];
