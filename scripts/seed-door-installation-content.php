<?php

/**
 * Seed Door Installation tagline, card highlights, and overview sections (local DB).
 *
 * php artisan tinker scripts/seed-door-installation-content.php
 */

use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Services\ServiceOverviewContentResolver;

$serviceId = '7ae680f7-97ed-464e-87e1-5da2aaae55c5';

$service = Service::withoutGlobalScopes()->find($serviceId);
if (! $service) {
    throw new RuntimeException("Service not found: {$serviceId}");
}

$coverUrl = $service->cover_image_full_path
    ?: 'https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod/service/door-installation/2026-07-08-6a4e58e7a1b82.webp';
$thumbUrl = $service->thumbnail_full_path
    ?: 'https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod/service/door-installation/2026-07-08-6a4e58dc3e9c1.webp';

$shortDescription = 'Expert door fitting by verified carpenters at your home.';

$overviewContent = [
    'intro' => 'Precise wooden door installation with smooth swing and secure latch.',
    'override_top_icons' => false,
    'override_why_choose' => false,
    'top_icons' => [],
    'card_highlights' => [
        ['icon' => 'tools', 'text' => 'Expert Installation', 'color' => 'blue', 'sort_order' => 0],
        ['icon' => 'quality', 'text' => 'Level-Aligned Fit', 'color' => 'green', 'sort_order' => 1],
        ['icon' => 'verified', 'text' => 'Verified Carpenters', 'color' => 'purple', 'sort_order' => 2],
    ],
    'why_choose' => ['title' => '', 'items' => []],
    'service_process' => [
        'title' => 'How It Works',
        'items' => [
            [
                'icon' => 'calendar',
                'title' => 'Book your slot',
                'description' => 'Choose consultation or full installation, then share your address, door size and photos of the opening.',
                'sort_order' => 0,
            ],
            [
                'icon' => 'verified',
                'title' => 'Carpenter assigned',
                'description' => 'A verified Panun Kaergar carpenter confirms your visit and arrives with professional fitting tools.',
                'sort_order' => 1,
            ],
            [
                'icon' => 'location',
                'title' => 'On-site visit',
                'description' => 'Technician reaches your home or office on schedule and inspects the door opening and frame condition.',
                'image' => $thumbUrl,
                'sort_order' => 2,
            ],
            [
                'icon' => 'door',
                'title' => 'Frame & door check',
                'description' => 'Opening measurements, frame squareness and hinge positions are assessed before mounting begins.',
                'sort_order' => 3,
            ],
            [
                'icon' => 'quality',
                'title' => 'Precision fitting',
                'description' => 'Door is level-aligned, hinges secured, gaps shimmed and lock hardware adjusted for smooth operation.',
                'image' => $coverUrl,
                'sort_order' => 4,
            ],
            [
                'icon' => 'sparkle',
                'title' => 'Test & handover',
                'description' => 'Open/close and latch checks completed, work area cleaned, and basic door care tips shared with you.',
                'sort_order' => 5,
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

$service->short_description = $shortDescription;
$service->overview_content = $normalized;
$service->save();

Translation::query()->updateOrCreate(
    [
        'translationable_type' => Service::class,
        'translationable_id' => $serviceId,
        'locale' => 'en',
        'key' => 'short_description',
    ],
    ['value' => $shortDescription]
);

echo "Seeded Door Installation ({$serviceId}).\n";
echo "Tagline: {$shortDescription}\n";
echo 'Card highlights: '.count($normalized['card_highlights'])."\n";
echo 'Overview sections: intro, service_process, perfect_for, whats_included, good_to_know, whats_not_included'."\n";
