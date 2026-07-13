#!/usr/bin/env php
<?php

/**
 * Build gardening icon + photo prompt JSON from catalog.
 * php scripts/build-gardening-prompts.php
 */

$catalog = require __DIR__.'/data/gardening-catalog.php';
$assetsRoot = '/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets';
$iconStyle = 'Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered.';

$iconPrompts = ['categories' => [], 'variants' => []];

$categorySubjects = [
    'gardening' => 'small plant sprout with garden trowel and leaf, professional gardening service',
    'lawn-grass-care' => 'lawn mower silhouette cutting grass strip with small grass blades',
    'planting-soil-care' => 'potted plant with small shovel and soil mound',
    'pruning-trimming' => 'garden hedge shears cutting a small shrub branch',
    'garden-cleanup-maintenance' => 'garden rake with small leaf pile',
];

$iconPrompts['categories'][] = [
    'slug' => $catalog['category']['slug'],
    'filename' => $catalog['category']['slug'].'.png',
    'prompt' => "Category icon: {$categorySubjects[$catalog['category']['slug']]}. {$iconStyle}",
];

foreach ($catalog['sub_categories'] as $sub) {
    $subject = $categorySubjects[$sub['slug']] ?? $sub['name'];
    $iconPrompts['categories'][] = [
        'slug' => $sub['slug'],
        'filename' => $sub['slug'].'.png',
        'prompt' => "Category icon: {$subject}. {$iconStyle}",
    ];
}

$variantVisual = static function (string $key, string $label): string {
    return match ($key) {
        'book-site-inspection' => 'magnifying glass with clipboard for site inspection',
        'small' => 'small garden plot icon, compact lawn area',
        'medium' => 'medium garden lawn icon, mid-size green area',
        'large' => 'large garden lawn icon, big green area',
        'hourly' => 'clock showing one hour',
        'half-day' => 'sun at horizon with half circle, 4 hour work slot',
        'full-day' => 'full sun icon representing 8 hour work day',
        default => "gardening option icon for {$label}",
    };
};

foreach ($catalog['services'] as $service) {
    $slug = $service['slug'];
    foreach ($service['variants'] as $variant) {
        $key = $variant['variant_key'];
        $label = $variant['title'];
        $visual = $variantVisual($key, $label);
        $iconPrompts['variants'][] = [
            'slug' => $slug,
            'variant_key' => $key,
            'filename' => "{$slug}-{$key}.png",
            'prompt' => "Variation icon for gardening: {$visual} for {$label}. {$iconStyle}",
        ];
    }
}

$photoPrompts = [];
foreach ($catalog['services'] as $service) {
    $slug = $service['slug'];
    $name = $service['name'];
    $scene = match (true) {
        str_contains($slug, 'book-a-gardener') => 'Kashmiri origin Indian gardener handyman facing camera with warm smile, holding pruning shears and trowel',
        str_contains($slug, 'lawn-mowing') => 'Indian gardener using lawn mower on green grass at a Srinagar home garden',
        str_contains($slug, 'terrace') => 'Indian gardener arranging potted plants on a Srinagar apartment terrace',
        str_contains($slug, 'drip-irrigation') => 'Indian gardener installing drip irrigation lines along plant beds',
        str_contains($slug, 'hedge') || str_contains($slug, 'pruning') || str_contains($slug, 'shaping') => 'Indian gardener trimming shrubs and plants with shears',
        str_contains($slug, 'cleanup') || str_contains($slug, 'leaf') || str_contains($slug, 'weeding') => 'Indian gardener weeding and clearing garden debris',
        str_contains($slug, 'planting') || str_contains($slug, 'soil') => 'Indian gardener working with soil and plants in garden beds',
        str_contains($slug, 'pest') => 'Indian gardener spraying organic plant treatment on garden plants',
        default => "Indian gardener performing {$name} at a Srinagar home garden",
    };

    $photoPrompts[] = [
        'slug' => $slug,
        'name' => $name,
        'sub_category_slug' => $service['sub_category_slug'],
        'thumbnail_prompt' => "Professional close-up photograph of {$name} in progress, {$scene}, natural soft daylight, shallow depth of field, photorealistic home service stock photo. No text, no logos, no watermarks.",
        'cover_prompt' => "Wide landscape professional photograph showing {$name}, {$scene}, natural daylight, photorealistic home service photography. No text, no logos, no watermarks.",
        'thumbnail_path' => "{$assetsRoot}/{$slug}-thumbnail.png",
        'cover_path' => "{$assetsRoot}/{$slug}-cover.png",
    ];
}

$dataDir = __DIR__.'/assets/data';
if (! is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

file_put_contents(
    $dataDir.'/gardening-icon-prompts.json',
    json_encode($iconPrompts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
);
file_put_contents(
    $dataDir.'/gardening-photo-prompts.json',
    json_encode($photoPrompts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
);

echo 'Wrote gardening-icon-prompts.json ('.count($iconPrompts['categories']).' categories, '.count($iconPrompts['variants'])." variants)\n";
echo 'Wrote gardening-photo-prompts.json ('.count($photoPrompts)." services)\n";
