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
];
