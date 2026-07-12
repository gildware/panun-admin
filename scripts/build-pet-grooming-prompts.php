#!/usr/bin/env php
<?php

/**
 * Build pet grooming icon + photo prompt JSON from catalog.
 * php scripts/build-pet-grooming-prompts.php
 */

$catalog = require __DIR__.'/data/pet-grooming-catalog.php';
$assetsRoot = '/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets';
$iconStyle = 'Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered.';

$iconPrompts = ['categories' => [], 'variants' => []];

$iconPrompts['categories'][] = [
    'slug' => 'pet-grooming',
    'filename' => 'pet-grooming.png',
    'prompt' => "Category icon: dog and cat silhouette with grooming comb and scissors, professional pet grooming service. {$iconStyle}",
];
$iconPrompts['categories'][] = [
    'slug' => 'dog-grooming',
    'filename' => 'dog-grooming.png',
    'prompt' => "Category icon: friendly dog face with grooming comb and scissors, dog grooming service. {$iconStyle}",
];
$iconPrompts['categories'][] = [
    'slug' => 'cat-grooming',
    'filename' => 'cat-grooming.png',
    'prompt' => "Category icon: cat face with grooming brush, cat grooming service. {$iconStyle}",
];

$variantIconPrompt = static function (string $slug, string $variantKey, string $label): string {
    $iconStyle = 'Flat filled vector mobile app icon. Solid dark navy blue #1A233A shapes only on pure white background. Bold minimalist geometric style like Urban Company app icons. No text, no gradients, no shadows, centered.';

    $visual = match ($variantKey) {
        'small' => 'small dog silhouette icon, compact pet size up to 10 kg',
        'medium' => 'medium dog silhouette icon, mid-size pet 10 to 25 kg',
        'large' => 'large dog silhouette icon, big pet 25 to 40 kg',
        'extra-large' => 'extra large dog silhouette icon, giant breed over 40 kg',
        'short-hair' => 'short-haired cat silhouette icon with smooth coat',
        'long-hair' => 'long-haired cat silhouette icon with fluffy coat',
        'short-coat' => 'short coat dog icon with smooth fur',
        'long-coat' => 'long double coat dog icon with fluffy fur',
        'per-pet' => 'single paw print icon representing one pet',
        'puppy' => 'small puppy dog icon under 6 months',
        'kitten' => 'small kitten cat icon under 6 months',
        'mild-mats' => 'cat with small tangle knot icon, mild matting',
        'severe-mats' => 'cat with heavy matted fur icon, severe matting',
        '1-visit-per-month' => 'calendar icon with number 1, one monthly visit',
        '2-visits-per-month' => 'calendar icon with number 2, two monthly visits',
        default => "pet grooming option icon for {$label}",
    };

    return "Variation icon for pet grooming: {$visual} for {$label}. {$iconStyle}";
};

foreach ($catalog['services'] as $service) {
    $slug = $service['slug'];
    foreach ($service['variants'] as $variant) {
        $key = $variant['variant_key'];
        $label = $variant['title'];
        $iconPrompts['variants'][] = [
            'slug' => $slug,
            'variant_key' => $key,
            'filename' => "{$slug}-{$key}.png",
            'prompt' => $variantIconPrompt($slug, $key, $label),
        ];
    }
}

$photoPrompts = [];
foreach ($catalog['services'] as $service) {
    $slug = $service['slug'];
    $name = $service['name'];
    $isCat = ($service['sub_category_slug'] ?? '') === 'cat-grooming';
    $pet = $isCat ? 'cat' : 'dog';
    $setting = 'clean modern Indian home living room or bathroom, at-home pet grooming visit';

    $scene = match (true) {
        str_contains($slug, 'full-') => "professional groomer giving full grooming to a {$pet} with bath, brush, and trim",
        str_contains($slug, 'bath-and-brush') => "groomer bathing and blow-drying a {$pet} with brush in hand",
        str_contains($slug, 'haircut') => "groomer trimming {$pet} coat with scissors and clippers",
        str_contains($slug, 'nail') => "close-up of groomer clipping {$pet} nails carefully",
        str_contains($slug, 'ear') => "groomer gently cleaning {$pet} ears with cotton pad",
        str_contains($slug, 'teeth') => "groomer brushing {$pet} teeth with pet toothbrush",
        str_contains($slug, 'deshedding') => "groomer deshedding a {$pet} with undercoat rake brush",
        str_contains($slug, 'flea') => "groomer giving medicated flea bath to a {$pet}",
        str_contains($slug, 'paw') => "groomer trimming fur around {$pet} paw pads",
        str_contains($slug, 'puppy') || str_contains($slug, 'kitten') => "gentle first grooming session for a young {$pet} with patient groomer",
        str_contains($slug, 'senior') => "gentle slow grooming of a senior {$pet} on soft mat",
        str_contains($slug, 'spa') => "spa grooming with massage and paw balm for a relaxed {$pet}",
        str_contains($slug, 'mat') => "groomer carefully dematting a long-haired cat coat",
        str_contains($slug, 'lion-cut') => "groomer giving lion cut trim to a cat on grooming table",
        str_contains($slug, 'monthly') => "regular monthly grooming visit, groomer with portable kit greeting {$pet} owner at Indian home",
        default => "professional at-home {$pet} grooming service",
    };

    $photoPrompts[] = [
        'slug' => $slug,
        'name' => $name,
        'sub_category_slug' => $service['sub_category_slug'],
        'thumbnail_prompt' => "Professional close-up photograph of {$scene}, {$setting}, Kashmiri Indian pet groomer in clean uniform with grooming tools, natural soft lighting, shallow depth of field, photorealistic stock photo style. No text, no logos, no watermarks.",
        'cover_prompt' => "Wide landscape professional photograph showing {$name} service, {$setting}, at-home pet grooming with professional groomer and happy {$pet}, natural daylight, photorealistic home service photography. No text, no logos, no watermarks.",
        'thumbnail_path' => "{$assetsRoot}/{$slug}-thumbnail.png",
        'cover_path' => "{$assetsRoot}/{$slug}-cover.png",
    ];
}

$iconOut = __DIR__.'/assets/data/pet-grooming-icon-prompts.json';
$photoOut = __DIR__.'/assets/data/pet-grooming-photo-prompts.json';

file_put_contents($iconOut, json_encode($iconPrompts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
file_put_contents($photoOut, json_encode($photoPrompts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

echo "Wrote {$iconOut} (".count($iconPrompts['categories']).' categories, '.count($iconPrompts['variants'])." variants)\n";
echo "Wrote {$photoOut} (".count($photoPrompts)." services)\n";
