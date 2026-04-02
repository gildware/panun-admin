<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'whatsapp_cloud' => [
        'token' => env('WHATSAPP_CLOUD_TOKEN'),
        'phone_id' => env('WHATSAPP_CLOUD_PHONE_ID'),
        'waba_id' => env('WHATSAPP_CLOUD_WABA_ID'),
        /** Meta (Facebook) App ID — required for Graph Resumable Upload when creating IMAGE/VIDEO template headers via API. */
        'app_id' => env('WHATSAPP_CLOUD_APP_ID'),
        'version' => env('WHATSAPP_CLOUD_VERSION', 'v19.0'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        /**
         * When opening admin chat from booking, ask Meta whether the number is on WhatsApp (POST .../contacts).
         * Does not send the customer a message. Set false to use the probe message instead (or alongside fallback).
         */
        'open_chat_use_contacts' => filter_var(env('WHATSAPP_OPEN_CHAT_USE_CONTACTS', 'true'), FILTER_VALIDATE_BOOL),
        /**
         * Fallback: if the contacts check fails with an ambiguous response, try a minimal outbound text (same as legacy probe).
         */
        'open_chat_probe_fallback_after_contacts' => filter_var(env('WHATSAPP_OPEN_CHAT_PROBE_FALLBACK', 'false'), FILTER_VALIDATE_BOOL),
        /**
         * When contacts is off, call Graph messages API with a minimal text to confirm the recipient can receive WhatsApp.
         * Set WHATSAPP_OPEN_CHAT_PROBE=false to skip the probe (contacts-only or inbound-thread-only).
         */
        'open_chat_probe_enabled' => filter_var(env('WHATSAPP_OPEN_CHAT_PROBE', 'true'), FILTER_VALIDATE_BOOL),
        /** Single-character probe body (Meta often rejects empty / invisible-only). Override in .env if needed. */
        'open_chat_probe_text' => (string) (env('WHATSAPP_OPEN_CHAT_PROBE_TEXT') ?: '.'),
        /**
         * Local/dev only: allow opening a thread without Cloud API + without a successful probe.
         * Production should keep this false so numbers are verified via Graph when there is no prior inbound message.
         */
        'allow_open_without_graph_verify' => filter_var(env('WHATSAPP_ALLOW_OPEN_WITHOUT_GRAPH_VERIFY', 'false'), FILTER_VALIDATE_BOOL),
    ],

    'whatsapp_internal' => [
        'token' => env('INTERNAL_WHATSAPP_API_TOKEN'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        /** Default model for admin AI content generation (WhatsApp uses WHATSAPP_GEMINI_MODEL). */
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

];
