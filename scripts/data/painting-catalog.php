<?php

/**
 * Painting services catalog — interior + exterior painting.
 */

if (! function_exists('painting_inspection_variant')) {
    function painting_inspection_variant(): array
    {
        return [
            'variant_key' => 'book-site-inspection',
            'title' => 'Book Site Inspection',
            'variation_label' => 'Book Site Inspection',
            'price' => 100.0,
        ];
    }
}

$inspection = 'painting_inspection_variant';

return [
    'category' => [
        'id' => '2d92c399-0709-481d-b499-e6921f3d9217',
        'name' => 'Painting',
        'slug' => 'painting',
    ],
    'sub_categories' => [
        'interior-painting' => [
            'id' => 'f452184d-06e5-4711-b192-20b556f82e8a',
            'name' => 'Interior Painting',
            'slug' => 'interior-painting',
        ],
        'exterior-painting' => [
            'id' => '69c22674-5102-47af-9d72-a0a01506196d',
            'name' => 'Exterior Painting',
            'slug' => 'exterior-painting',
        ],
    ],
    'services' => [
        // —— Existing interior ——
        [
            'id' => '44a83f40-cf78-413d-aac3-7c42888d4f49',
            'name' => 'Full House Interior Painting',
            'slug' => 'full-house-interior-painting',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'd966d3db-4bce-4db7-b498-0f94491e0b18',
            'name' => 'Full Room Painting',
            'slug' => 'full-room-painting',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'b36c244f-9494-42d3-a682-e00d527c05ed',
            'name' => 'Door Painting',
            'slug' => 'door-painting',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => '2a789707-4152-4cf9-aebf-06534a8e4c16',
            'name' => 'Window Painting',
            'slug' => 'window-painting',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => '3fd5d4b8-b59a-44a7-a1fb-a560a821ad63',
            'name' => 'Primer Application',
            'slug' => 'primer-application',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => '957fb9fb-86b9-4b29-813c-cce1e7957ce7',
            'name' => 'Texture Wall Painting',
            'slug' => 'texture-wall-painting',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => '3c8c2812-be3f-486c-a1d2-7491313cd7f3',
            'name' => 'Wall Putty Application',
            'slug' => 'wall-putty-application',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        // —— New interior ——
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901001',
            'name' => 'Single Wall Accent Painting',
            'slug' => 'single-wall-accent-painting',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901002',
            'name' => 'Ceiling Painting',
            'slug' => 'ceiling-painting',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901003',
            'name' => 'Ceiling & Trim Painting',
            'slug' => 'ceiling-trim-painting',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901004',
            'name' => 'Interior Touch-up & Patch Painting',
            'slug' => 'interior-touch-up-patch-painting',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901005',
            'name' => 'Interior Painting Consultation',
            'slug' => 'interior-painting-consultation',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901006',
            'name' => 'Old Paint Removal & Surface Scraping',
            'slug' => 'old-paint-removal-scraping',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901007',
            'name' => 'Bathroom & Kitchen Painting',
            'slug' => 'bathroom-kitchen-painting',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901008',
            'name' => 'Wardrobe & Almirah Painting',
            'slug' => 'wardrobe-almirah-painting',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901009',
            'name' => 'POP False Ceiling Painting',
            'slug' => 'pop-false-ceiling-painting',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-34567890100a',
            'name' => 'Interior Crack Filling & Repair',
            'slug' => 'interior-crack-filling-repair',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-34567890100b',
            'name' => 'Stain & Damp Spot Treatment',
            'slug' => 'stain-damp-spot-treatment',
            'sub_category_slug' => 'interior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        // —— Existing exterior ——
        [
            'id' => '47ab59c9-65e5-43eb-82ed-fef60e919a00',
            'name' => 'Building Painting',
            'slug' => 'building-painting',
            'sub_category_slug' => 'exterior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => '6feda669-dc43-4ba9-97d3-7fb07d696b5a',
            'name' => 'Exterior Texture Painting',
            'slug' => 'exterior-texture-painting',
            'sub_category_slug' => 'exterior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        // —— New exterior ——
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-34567890100c',
            'name' => 'Exterior Wall Painting',
            'slug' => 'exterior-wall-painting',
            'sub_category_slug' => 'exterior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-34567890100d',
            'name' => 'Full House Exterior Painting',
            'slug' => 'full-house-exterior-painting',
            'sub_category_slug' => 'exterior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-34567890100e',
            'name' => 'Boundary Wall Painting',
            'slug' => 'boundary-wall-painting',
            'sub_category_slug' => 'exterior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-34567890100f',
            'name' => 'Exterior Door & Gate Painting',
            'slug' => 'exterior-door-gate-painting',
            'sub_category_slug' => 'exterior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901010',
            'name' => 'Exterior Window & Grille Painting',
            'slug' => 'exterior-window-grille-painting',
            'sub_category_slug' => 'exterior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901011',
            'name' => 'Exterior Primer Application',
            'slug' => 'exterior-primer-application',
            'sub_category_slug' => 'exterior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901012',
            'name' => 'Exterior Wall Putty & Crack Repair',
            'slug' => 'exterior-wall-putty-crack-repair',
            'sub_category_slug' => 'exterior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901013',
            'name' => 'Exterior Touch-up & Patch Painting',
            'slug' => 'exterior-touch-up-patch-painting',
            'sub_category_slug' => 'exterior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
        [
            'id' => 'a1b2c3d4-e5f6-4789-a012-345678901014',
            'name' => 'Waterproof Weather Shield Coating',
            'slug' => 'waterproof-weather-shield-coating',
            'sub_category_slug' => 'exterior-painting',
            'base_price' => 100.0,
            'variants' => [$inspection()],
        ],
    ],
];
