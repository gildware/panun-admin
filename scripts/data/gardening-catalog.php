<?php

/**
 * Gardening catalog — category, sub-categories, services, and variants.
 */

if (! function_exists('gardening_inspection_variant')) {
    function gardening_inspection_variant(): array
    {
        return [
            'variant_key' => 'book-site-inspection',
            'title' => 'Book Site Inspection',
            'variation_label' => 'Book Site Inspection',
            'price' => 100.0,
        ];
    }
}

if (! function_exists('gardening_size_variants')) {
    function gardening_size_variants(array $prices): array
    {
        $labels = [
            'small' => 'Small (up to 200 sq ft)',
            'medium' => 'Medium (200–500 sq ft)',
            'large' => 'Large (500+ sq ft)',
        ];

        $variants = [];
        foreach ($labels as $key => $label) {
            $variants[] = [
                'variant_key' => $key,
                'title' => $label,
                'variation_label' => $label,
                'price' => (float) ($prices[$key] ?? 0),
            ];
        }

        return $variants;
    }
}

if (! function_exists('gardening_two_size_variants')) {
    function gardening_two_size_variants(array $prices): array
    {
        return [
            [
                'variant_key' => 'small',
                'title' => 'Small',
                'variation_label' => 'Small',
                'price' => (float) ($prices['small'] ?? 0),
            ],
            [
                'variant_key' => 'large',
                'title' => 'Large',
                'variation_label' => 'Large',
                'price' => (float) ($prices['large'] ?? 0),
            ],
        ];
    }
}

return [
    'category' => [
        'name' => 'Gardening Services',
        'slug' => 'gardening',
        'description' => 'Professional gardening in Srinagar — lawn care, planting, pruning, cleanup, and seasonal maintenance for homes, terraces, and offices.',
        'sort_order' => 10,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Lawn & Grass Care',
            'slug' => 'lawn-grass-care',
            'description' => 'Lawn mowing, grass trimming, and edging for gardens of all sizes.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Planting & Soil Care',
            'slug' => 'planting-soil-care',
            'description' => 'Planting, repotting, soil preparation, terrace gardens, and drip irrigation setup.',
            'sort_order' => 2,
        ],
        [
            'name' => 'Pruning & Trimming',
            'slug' => 'pruning-trimming',
            'description' => 'Hedge cutting, tree and shrub pruning, and plant shaping.',
            'sort_order' => 3,
        ],
        [
            'name' => 'Garden Cleanup & Maintenance',
            'slug' => 'garden-cleanup-maintenance',
            'description' => 'Garden cleanup, weeding, seasonal maintenance, and monthly care plans.',
            'sort_order' => 4,
        ],
    ],
    'services' => [
        [
            'name' => 'Lawn Mowing & Trimming',
            'slug' => 'lawn-mowing-trimming',
            'sub_category_slug' => 'lawn-grass-care',
            'base_price' => 400.0,
            'variants' => gardening_size_variants(['small' => 400, 'medium' => 700, 'large' => 1200]),
        ],
        [
            'name' => 'Grass Edging & Levelling',
            'slug' => 'grass-edging-levelling',
            'sub_category_slug' => 'lawn-grass-care',
            'base_price' => 100.0,
            'variants' => [gardening_inspection_variant()],
        ],
        [
            'name' => 'Planting & Repotting',
            'slug' => 'planting-repotting',
            'sub_category_slug' => 'planting-soil-care',
            'base_price' => 100.0,
            'variants' => [gardening_inspection_variant()],
        ],
        [
            'name' => 'Soil Preparation & Fertilizing',
            'slug' => 'soil-preparation-fertilizing',
            'sub_category_slug' => 'planting-soil-care',
            'base_price' => 100.0,
            'variants' => [gardening_inspection_variant()],
        ],
        [
            'name' => 'Terrace & Balcony Garden Setup',
            'slug' => 'terrace-balcony-garden-setup',
            'sub_category_slug' => 'planting-soil-care',
            'base_price' => 800.0,
            'variants' => gardening_two_size_variants(['small' => 800, 'large' => 1500]),
        ],
        [
            'name' => 'Drip Irrigation Setup',
            'slug' => 'drip-irrigation-setup',
            'sub_category_slug' => 'planting-soil-care',
            'base_price' => 100.0,
            'variants' => [gardening_inspection_variant()],
        ],
        [
            'name' => 'Hedge Cutting',
            'slug' => 'hedge-cutting',
            'sub_category_slug' => 'pruning-trimming',
            'base_price' => 100.0,
            'variants' => [gardening_inspection_variant()],
        ],
        [
            'name' => 'Tree & Shrub Pruning',
            'slug' => 'tree-shrub-pruning',
            'sub_category_slug' => 'pruning-trimming',
            'base_price' => 100.0,
            'variants' => [gardening_inspection_variant()],
        ],
        [
            'name' => 'Plant Shaping & Deadheading',
            'slug' => 'plant-shaping-deadheading',
            'sub_category_slug' => 'pruning-trimming',
            'base_price' => 100.0,
            'variants' => [gardening_inspection_variant()],
        ],
        [
            'name' => 'Garden Cleanup & Weeding',
            'slug' => 'garden-cleanup-weeding',
            'sub_category_slug' => 'garden-cleanup-maintenance',
            'base_price' => 500.0,
            'variants' => gardening_size_variants(['small' => 500, 'medium' => 900, 'large' => 1500]),
        ],
        [
            'name' => 'Leaf & Debris Removal',
            'slug' => 'leaf-debris-removal',
            'sub_category_slug' => 'garden-cleanup-maintenance',
            'base_price' => 400.0,
            'variants' => gardening_two_size_variants(['small' => 400, 'large' => 800]),
        ],
        [
            'name' => 'Seasonal Garden Maintenance',
            'slug' => 'seasonal-garden-maintenance',
            'sub_category_slug' => 'garden-cleanup-maintenance',
            'base_price' => 100.0,
            'variants' => [gardening_inspection_variant()],
        ],
        [
            'name' => 'Monthly Garden Maintenance Plan',
            'slug' => 'monthly-garden-maintenance-plan',
            'sub_category_slug' => 'garden-cleanup-maintenance',
            'base_price' => 2000.0,
            'variants' => gardening_size_variants(['small' => 2000, 'medium' => 3500, 'large' => 5000]),
        ],
        [
            'name' => 'Plant Pest & Disease Treatment',
            'slug' => 'plant-pest-disease-treatment',
            'sub_category_slug' => 'garden-cleanup-maintenance',
            'base_price' => 100.0,
            'variants' => [gardening_inspection_variant()],
        ],
        [
            'name' => 'Book a Gardener',
            'slug' => 'book-a-gardener',
            'sub_category_slug' => 'garden-cleanup-maintenance',
            'base_price' => 300.0,
            'variants' => [
                [
                    'variant_key' => 'hourly',
                    'title' => 'Hourly (1 hour)',
                    'variation_label' => 'Hourly (1 hour)',
                    'price' => 300.0,
                ],
                [
                    'variant_key' => 'half-day',
                    'title' => 'Half Day (4 hours)',
                    'variation_label' => 'Half Day (4 hours)',
                    'price' => 1000.0,
                ],
                [
                    'variant_key' => 'full-day',
                    'title' => 'Full Day (8 hours)',
                    'variation_label' => 'Full Day (8 hours)',
                    'price' => 1800.0,
                ],
            ],
        ],
    ],
];
