<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin top navigation layout
    |--------------------------------------------------------------------------
    |
    | When true, replaces the legacy sidebar + header with the three-row top
    | chrome (utility bar, menu links, pinned shortcuts).
    |
    | Rollback: set ADMIN_TOP_NAV=false in .env and run php artisan config:clear
    |
    */
    'top_nav' => env('ADMIN_TOP_NAV', false),

    /*
    |--------------------------------------------------------------------------
    | Partial page navigation (Turbo Frame)
    |--------------------------------------------------------------------------
    |
    | When true with top_nav, menu/content links swap only the main area instead
    | of full page reloads. Rollback: ADMIN_PARTIAL_NAV=false
    |
    */
    'partial_nav' => env('ADMIN_PARTIAL_NAV', true),

    /*
    |--------------------------------------------------------------------------
    | Default pinned shortcuts (pin_key from AdminNavRegistry)
    |--------------------------------------------------------------------------
    */
    'default_pinned_nav' => [
        'booking.requests',
        'booking.verify',
        'social.whatsapp',
        'lead.index',
    ],

];
