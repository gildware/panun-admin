#!/usr/bin/env php
<?php

/**
 * Build interior decor icon + photo prompt JSON from catalog.
 * php scripts/build-interior-decor-prompts.php
 */

$catalog = require __DIR__.'/data/interior-decor-catalog.php';
$assetsRoot = '/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets';

$iconStyle = 'Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered.';

$iconPrompts = ['categories' => [], 'variants' => []];

$iconPrompts['categories'][] = [
    'slug' => 'interior-decor',
    'filename' => 'interior-decor.png',
    'prompt' => "Category icon: sofa with floor lamp and small potted plant, interior decor and home styling service. {$iconStyle}",
];
$iconPrompts['categories'][] = [
    'slug' => 'home-decor-consultation',
    'filename' => 'home-decor-consultation.png',
    'prompt' => "Category icon: Indian home living room floor plan with sofa and coffee table, home decor consultation. {$iconStyle}",
];
$iconPrompts['categories'][] = [
    'slug' => 'commercial-decor-styling',
    'filename' => 'commercial-decor-styling.png',
    'prompt' => "Category icon: modern office desk with chair and shop display shelf, commercial interior styling. {$iconStyle}",
];

$inspectionIconPrompt = static function (string $serviceName) use ($iconStyle): string {
    return "Variation icon for interior decor: clipboard with house floor plan and pencil, book site visit for {$serviceName}. {$iconStyle}";
};

foreach ($catalog['services'] as $service) {
    $slug = $service['slug'];
    $name = $service['name'];
    foreach ($service['variants'] as $variant) {
        $key = $variant['variant_key'];
        $iconPrompts['variants'][] = [
            'slug' => $slug,
            'variant_key' => $key,
            'filename' => "{$slug}-{$key}.png",
            'prompt' => $inspectionIconPrompt($name),
        ];
    }
}

$sceneFor = static function (string $subSlug): string {
    return match ($subSlug) {
        'commercial-decor-styling' => 'a modern Indian office or retail shop interior in Srinagar',
        default => 'a warm modern Indian home living room in Srinagar with natural light',
    };
};

$photoPrompts = [];
foreach ($catalog['services'] as $service) {
    $slug = $service['slug'];
    $name = $service['name'];
    $subSlug = $service['sub_category_slug'] ?? '';
    $setting = $sceneFor($subSlug);

    $scene = match ($slug) {
        'room-layout-space-planning' => 'interior decor specialist measuring room and sketching furniture layout on paper beside sofa',
        'home-makeover-consultation' => 'decor consultant discussing colour swatches and furniture placement with homeowner in living room',
        'curtains-soft-furnishing-advice' => 'decor expert showing curtain fabric samples and cushion options near window',
        'office-shop-interior-styling' => 'interior stylist reviewing office desk layout and shop display arrangement with client',
        default => 'professional interior decor consultation at home',
    };

    $photoPrompts[] = [
        'slug' => $slug,
        'name' => $name,
        'sub_category_slug' => $subSlug,
        'thumbnail_prompt' => "Professional close-up photograph of {$scene}, {$setting}, Kashmiri Indian interior decor consultant in smart casual attire, natural soft lighting, shallow depth of field, photorealistic stock photo style. No text, no logos, no watermarks.",
        'cover_prompt' => "Wide landscape professional photograph showing {$name} service, {$setting}, interior decor consultant walking through space with client, natural daylight, photorealistic home service photography. No text, no logos, no watermarks.",
        'thumbnail_path' => "{$assetsRoot}/{$slug}-thumbnail.png",
        'cover_path' => "{$assetsRoot}/{$slug}-cover.png",
    ];
}

$iconOut = __DIR__.'/assets/data/interior-decor-icon-prompts.json';
$photoOut = __DIR__.'/assets/data/interior-decor-photo-prompts.json';

file_put_contents($iconOut, json_encode($iconPrompts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
file_put_contents($photoOut, json_encode($photoPrompts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

echo "Wrote {$iconOut} (".count($iconPrompts['categories']).' categories, '.count($iconPrompts['variants'])." variants)\n";
echo "Wrote {$photoOut} (".count($photoPrompts)." services)\n";
