<?php

/**
 * Seed Door Installation overview sections on live DB.
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/seed-door-installation-overview-live.php
 */

use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Services\ServiceOverviewContentResolver;

$liveConnection = 'live_service_content';
config(['database.connections.'.$liveConnection => [
    'driver' => 'mysql',
    'host' => '82.25.121.201',
    'port' => '3306',
    'database' => 'u397782854_live_pk_dec',
    'username' => 'u397782854_live_pk_usr',
    'password' => env('LIVE_DB_PASSWORD', env('DB_PASSWORD', '')),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
]]);

if ((string) config('database.connections.'.$liveConnection.'.password') === '') {
    throw new RuntimeException('Set LIVE_DB_PASSWORD (or DB_PASSWORD) for live database.');
}

$serviceId = '7ae680f7-97ed-464e-87e1-5da2aaae55c5';
$mediaBase = 'https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod/';
$coverUrl = $mediaBase.'service/door-installation/2026-07-08-6a4e58e7a1b82.webp';
$thumbUrl = $mediaBase.'service/door-installation/2026-07-08-6a4e58dc3e9c1.webp';

$service = Service::on($liveConnection)->withoutGlobalScopes()->find($serviceId);
if (! $service) {
    throw new RuntimeException("Service not found: {$serviceId}");
}

$overviewContent = [
    'intro' => 'Door Installation by Panun Kaergar connects you with verified carpenters for precise fitting of new interior and exterior wooden doors. From hinge placement and frame alignment to smooth swing and secure latching, every job is completed with professional tools and quality workmanship.',
    'override_top_icons' => false,
    'override_why_choose' => false,
    'top_icons' => [],
    'why_choose' => ['title' => '', 'items' => []],
    'service_process' => [
        'title' => 'How It Works',
        'items' => [
            [
                'icon' => 'calendar',
                'title' => 'Book online',
                'description' => 'Choose consultation or installation and share your address and door details.',
                'sort_order' => 0,
            ],
            [
                'icon' => 'tools',
                'title' => 'On-site fitting',
                'description' => 'Carpenter aligns the door, fixes hinges and adjusts gaps and hardware.',
                'image' => $coverUrl,
                'sort_order' => 1,
            ],
            [
                'icon' => 'check',
                'title' => 'Test & handover',
                'description' => 'Open/close check, latch test, cleanup and basic care tips.',
                'sort_order' => 2,
            ],
        ],
    ],
    'perfect_for' => [
        'title' => 'Ideal For',
        'items' => [
            ['icon' => 'home', 'text' => 'New homes', 'sort_order' => 0],
            ['icon' => 'sparkle', 'text' => 'Renovation', 'sort_order' => 1],
            ['icon' => 'door', 'text' => 'Door replacement', 'sort_order' => 2],
            ['icon' => 'home', 'text' => 'Bedroom doors', 'sort_order' => 3],
            ['icon' => 'door', 'text' => 'Main entrance', 'sort_order' => 4],
            ['icon' => 'building', 'text' => 'Office cabins', 'sort_order' => 5],
            ['icon' => 'wood', 'text' => 'Pre-purchased doors', 'sort_order' => 6],
        ],
    ],
    'whats_included' => [
        'title' => "What's Included",
        'items' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'door', 'title' => 'Door mounting & alignment', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Hinge installation', 'sort_order' => 2],
            ['icon' => 'quality', 'title' => 'Level-aligned fitting', 'sort_order' => 3],
            ['icon' => 'check', 'title' => 'Latch & lock alignment', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Gap adjustment', 'sort_order' => 5],
            ['icon' => 'sparkle', 'title' => 'Testing & handover', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
    ],
    'good_to_know' => [
        'title' => 'Things to Know',
        'items' => [
            ['icon' => 'door', 'title' => 'Please ensure the door unit and compatible hinges/lock are available before the visit, unless confirmed otherwise at booking', 'sort_order' => 0],
            ['icon' => 'home', 'title' => 'Clear access to the work area helps the technician complete the job in one visit', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Final time and cost may vary if the frame is out of square or needs extra shimming — explained before proceeding', 'sort_order' => 2],
            ['icon' => 'wood', 'title' => 'Wooden doors may need minor seasonal adjustment in changing humidity', 'sort_order' => 3],
            ['icon' => 'check', 'title' => 'Share door type, size and photos of the opening when booking for best results', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before the slot for cancellation or rescheduling where possible', 'sort_order' => 5],
        ],
    ],
    'whats_not_included' => [
        'title' => 'Exclusions',
        'items' => [
            ['icon' => 'pricing', 'title' => 'Cost of the door, frame, lockset, or hardware (unless supplied separately)', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Major frame rebuilding, wall breaking, or civil/concrete work', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Custom door fabrication or carpentry from raw timber', 'sort_order' => 2],
            ['icon' => 'door', 'title' => 'Repair of severely damaged, rotted, or termite-affected frames', 'sort_order' => 3],
            ['icon' => 'tools', 'title' => 'Electrical work for automatic doors or access-control systems', 'sort_order' => 4],
            ['icon' => 'sparkle', 'title' => 'Painting, polishing, veneer work, or post-install finishing', 'sort_order' => 5],
            ['icon' => 'door', 'title' => 'Old door removal and disposal unless agreed as an add-on on site', 'sort_order' => 6],
            ['icon' => 'quality', 'title' => 'Glass cutting, panel replacement, or fire-rated door certification', 'sort_order' => 7],
        ],
    ],
];

$normalized = ServiceOverviewContentResolver::normalizeServiceContent($overviewContent);

Service::on($liveConnection)
    ->withoutGlobalScopes()
    ->where('id', $serviceId)
    ->update(['overview_content' => json_encode($normalized)]);

echo "Seeded overview content for Door Installation ({$serviceId}) on live.\n";
echo 'Sections: process (with descriptions), perfect_for, whats_included, good_to_know, whats_not_included (icon + title).'."\n";
