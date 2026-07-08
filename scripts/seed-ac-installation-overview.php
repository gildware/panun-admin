<?php

/**
 * Seed AC Installation overview sections (local DB).
 * php artisan tinker scripts/seed-ac-installation-overview.php
 */

use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Services\ServiceOverviewContentResolver;

$serviceId = '0affd967-975b-4fc2-94af-4b870bf0945a';

$service = Service::withoutGlobalScopes()->find($serviceId);
if (! $service) {
    throw new RuntimeException("Service not found: {$serviceId}");
}

$coverUrl = $service->cover_image_full_path;
$thumbUrl = $service->thumbnail_full_path;

$overviewContent = [
    'intro' => 'Expert technicians install your AC with secure mounting, proper piping, safe electrical connections and thorough cooling checks for reliable performance.',
    'override_top_icons' => false,
    'override_why_choose' => false,
    'top_icons' => [],
    'why_choose' => ['title' => '', 'items' => []],
    'service_process' => [
        'title' => 'Installation Process',
        'items' => [
            ['icon' => 'calendar', 'title' => 'Choose date & time', 'image' => $thumbUrl, 'sort_order' => 0],
            ['icon' => 'location', 'title' => 'Expert reaches your location', 'image' => $coverUrl, 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Installation & setup', 'image' => $thumbUrl, 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Testing & handover', 'image' => $coverUrl, 'sort_order' => 3],
        ],
    ],
    'perfect_for' => [
        'title' => 'Perfect For',
        'items' => [
            ['icon' => 'home', 'text' => 'New Homes', 'sort_order' => 0],
            ['icon' => 'building', 'text' => 'Apartments', 'sort_order' => 1],
            ['icon' => 'building', 'text' => 'Offices', 'sort_order' => 2],
            ['icon' => 'shop', 'text' => 'Shops', 'sort_order' => 3],
            ['icon' => 'sparkle', 'text' => 'Renovation', 'sort_order' => 4],
            ['icon' => 'tools', 'text' => 'Split AC', 'sort_order' => 5],
            ['icon' => 'tools', 'text' => 'Window AC', 'sort_order' => 6],
        ],
    ],
    'whats_included' => [
        'title' => "What's Included",
        'items' => [
            ['icon' => 'tools', 'text' => 'AC unit mounting', 'sort_order' => 0],
            ['icon' => 'tools', 'text' => 'Indoor & outdoor fixing', 'sort_order' => 1],
            ['icon' => 'check', 'text' => 'Copper piping', 'sort_order' => 2],
            ['icon' => 'check', 'text' => 'Electrical connection', 'sort_order' => 3],
            ['icon' => 'check', 'text' => 'Drain pipe setup', 'sort_order' => 4],
            ['icon' => 'quality', 'text' => 'Vacuum & gas check', 'sort_order' => 5],
            ['icon' => 'sparkle', 'text' => 'Test run & cooling', 'sort_order' => 6],
            ['icon' => 'sparkle', 'text' => 'Work area clean-up', 'sort_order' => 7],
        ],
    ],
    'good_to_know' => [
        'title' => 'Good To Know',
        'items' => [
            ['text' => 'Keep the installation area clear before the visit', 'sort_order' => 0],
            ['text' => 'Ensure a working power point is available near the unit', 'sort_order' => 1],
            ['text' => 'Share AC brand, model and tonnage when booking', 'sort_order' => 2],
            ['text' => 'Indoor and outdoor locations should be accessible', 'sort_order' => 3],
            ['text' => 'Extra piping or material may affect final cost — explained before work starts', 'sort_order' => 4],
            ['text' => 'Notify at least 2 hours before for cancellation or rescheduling', 'sort_order' => 5],
        ],
    ],
    'whats_not_included' => [
        'title' => 'Not Included',
        'items' => [
            ['text' => 'AC unit cost (unless added separately)', 'sort_order' => 0],
            ['text' => 'Civil work, wall breaking or core drilling', 'sort_order' => 1],
            ['text' => 'Major electrical rewiring or new circuit installation', 'sort_order' => 2],
            ['text' => 'Gas refilling beyond standard installation scope', 'sort_order' => 3],
            ['text' => 'Annual maintenance or deep cleaning service', 'sort_order' => 4],
            ['text' => 'Old unit removal and disposal unless agreed on site', 'sort_order' => 5],
            ['text' => 'Structural modifications to walls or facades', 'sort_order' => 6],
        ],
    ],
];

$normalized = ServiceOverviewContentResolver::normalizeServiceContent($overviewContent);
$service->overview_content = $normalized;
$service->save();

echo "Seeded overview content for AC Installation ({$serviceId}).\n";
echo 'Sections: process, perfect_for, included, good_to_know, not_included + global top_icons/why_choose defaults.'."\n";
