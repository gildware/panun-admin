<?php

$services = require __DIR__.'/remaining-services-manifest.php';
$serviceMap = [];
foreach ($services as $service) {
    $serviceMap[$service['slug']] = $service;
}

$inspectionOldKeys = [
    'book-site-inspection',
    'Book-at-Home-Consultation',
    'Book--at-Home-Consultation',
    'Book-at--Home-Consultation',
    'Book-At-Home-Consultation',
    'book-at-home-consultation',
    'book at home consultation',
    'General-Inspection',
    'Site-Inspection',
    'Inspection',
];

$slugWords = static fn (string $slug): string => ucwords(str_replace('-', ' ', $slug));
$compactName = static fn (string $name): string => trim((string) preg_replace('/\s+/', ' ', str_replace('&', '', $name)));
$hyphenName = static fn (string $name): string => str_replace(' ', '-', $compactName($name));

$packageOldKeys = static function (array $service) use ($slugWords, $hyphenName): array {
    $name = $service['name'];
    $slug = $service['slug'];
    $keys = [
        $slug,
        $slugWords($slug),
        $hyphenName($name),
        $hyphenName($name).'--Package',
        $hyphenName($name).'-Package',
        str_replace('-', ' ', $slug),
        'Package',
    ];

    if ($service['role'] === 'cleaning') {
        $keys[] = 'Cleaning';
    }

    if ($service['role'] === 'laundry') {
        $keys[] = 'Dry-Clean';
        $keys[] = 'Laundry';
    }

    if ($service['role'] === 'install') {
        $keys[] = 'Installation';
        $keys[] = 'Install';
    }

    return array_values(array_unique(array_filter($keys)));
};

$inspectionDescription = static function (array $service): string {
    return match ($service['category']) {
        'electrical' => "Verified technician inspects {$service['name']} requirements, safety points, and likely work scope on site.",
        'plumbing' => "Verified technician inspects {$service['name']} needs, fittings, and likely work scope on site.",
        'painting' => "Verified Panun Kaergar team inspects {$service['name']} scope, surface condition, and access on site.",
        'masonry' => "Verified Panun Kaergar mason inspects {$service['name']} scope, measurements, and site condition on site.",
        default => "Verified Panun Kaergar technician inspects {$service['name']} scope and practical work requirements on site.",
    };
};

$packageDescription = static function (array $service): string {
    return match ($service['category']) {
        'cleaning' => "Professional {$service['name']} completed by trusted Panun Kaergar professionals with careful cleaning steps and a neat finish.",
        'laundry' => "Professional {$service['name']} with fabric-safe handling, careful processing, and a polished ready-to-wear finish.",
        default => "Professional {$service['name']} completed by a verified Panun Kaergar technician with careful setup and tested handover.",
    };
};

$inspectionVariant = static function (array $service, bool $createIfMissing = false) use ($inspectionOldKeys, $inspectionDescription): array {
    return [
        'variants' => [[
            'old_keys' => $inspectionOldKeys,
            'variant_key' => 'book-site-inspection',
            'title' => 'Book Site Inspection',
            'description' => $inspectionDescription($service),
            'note' => "This inspection fee will be adjusted against your final {$service['name']} bill if you proceed with the full service through Panun Kaergar.",
            'create_if_missing' => $createIfMissing,
        ]],
    ];
};

$packageVariant = static function (array $service) use ($packageOldKeys, $packageDescription): array {
    return [
        'variants' => [[
            'old_keys' => $packageOldKeys($service),
            'variant_key' => $service['slug'],
            'title' => $service['name'],
            'description' => $packageDescription($service),
            'note' => null,
            'create_if_missing' => false,
        ]],
    ];
};

$salonVariants = static function (array $service): array {
    return [
        'variants' => [
            [
                'old_keys' => ['Basic', 'basic', 'Basic-Package', 'Basic Package', 'basic-package'],
                'variant_key' => 'basic-package',
                'title' => 'Basic Package',
                'description' => "A polished essential {$service['name']} session by a Panun Kaergar stylist for a neat result and comfortable at-home experience.",
                'note' => null,
                'create_if_missing' => true,
            ],
            [
                'old_keys' => ['Premium', 'premium', 'Premium-Package', 'Premium Package', 'premium-package'],
                'variant_key' => 'premium-package',
                'title' => 'Premium Package',
                'description' => "An upgraded {$service['name']} session with extra finish attention, comfort, and stylist care for customers who want a more complete result.",
                'note' => null,
                'create_if_missing' => true,
            ],
        ],
    ];
};

$inspectionSlugs = [
    'fault-diagnosis',
    'lighting-repair',
    'pcb-transformer-auto-cut',
    'short-circuit-voltage-issues',
    'brick-work-construction',
    'concrete-work',
    'drain-construction',
    'floor-tiling-installation',
    'plaster-rendering',
    'step-stair-construction',
    'stone-work-construction',
    'wall-construction',
    'floor-tile-repair',
    'plaster-repair',
    'stair-repair',
    'wall-crack-repair',
    'full-house-interior-painting',
    'full-room-painting',
    'door-painting',
    'window-painting',
    'primer-application',
    'texture-wall-painting',
    'wall-putty-application',
    'single-wall-accent-painting',
    'ceiling-painting',
    'ceiling-trim-painting',
    'interior-touch-up-patch-painting',
    'interior-painting-consultation',
    'old-paint-removal-scraping',
    'bathroom-kitchen-painting',
    'wardrobe-almirah-painting',
    'pop-false-ceiling-painting',
    'interior-crack-filling-repair',
    'stain-damp-spot-treatment',
    'building-painting',
    'exterior-texture-painting',
    'exterior-wall-painting',
    'full-house-exterior-painting',
    'boundary-wall-painting',
    'exterior-door-gate-painting',
    'exterior-window-grille-painting',
    'exterior-primer-application',
    'exterior-wall-putty-crack-repair',
    'exterior-touch-up-patch-painting',
    'waterproof-weather-shield-coating',
    'drain-pipe-blockage-removal',
    'geyser-plumbing',
    'low-water-pressure-repair',
    'washroom-drain-cleaning',
    'water-leakage-repair',
    'water-motor-repair',
    'water-pipe-repair',
    'pipe-fitting-replacement',
    'tap-mixer-tap-installation',
    'washroom-accessories-installation',
    'water-motor-installation',
    'pipe-installation',
    'water-tank-installation',
    'window-installation',
];

$packageSlugs = [
    'carpet-cleaning',
    'full-home-cleaning',
    'kitchen-cleaning',
    'tanky-cleaning',
    'lehenga-dry-clean',
    'electrical-wiring',
    'lighting-installation',
    'switch-sockets',
];

$createIfMissing = [
    'wall-construction' => true,
    'exterior-texture-painting' => true,
    'geyser-plumbing' => true,
];

$manifest = [];

foreach ($packageSlugs as $slug) {
    $manifest[$slug] = $packageVariant($serviceMap[$slug]);
}

foreach ($inspectionSlugs as $slug) {
    $manifest[$slug] = $inspectionVariant($serviceMap[$slug], $createIfMissing[$slug] ?? false);
}

foreach ($serviceMap as $slug => $service) {
    if ($service['role'] === 'salon') {
        $manifest[$slug] = $salonVariants($service);
    }
}

ksort($manifest);

return $manifest;
