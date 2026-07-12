<?php

/**
 * Pest Control catalog — category, sub-categories, services, and variants.
 */

if (! function_exists('pest_control_inspection_variant')) {
    function pest_control_inspection_variant(): array
    {
        return [
            'variant_key' => 'book-site-inspection',
            'title' => 'Book On Site Inspection',
            'variation_label' => 'Book On Site Inspection',
            'price' => 100.0,
        ];
    }
}

return [
    'category' => [
        'name' => 'Pest Control',
        'slug' => 'pest-control',
        'description' => 'Professional pest control for homes, offices, and restaurants in Kashmir. Cockroach, rodent, and ant treatments with safe chemicals and follow-up visits.',
        'sort_order' => 6,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Home Pest Control',
            'slug' => 'home-pest-control',
            'description' => 'Cockroach and pest treatments for apartments, bungalows, kitchens, and partial home areas.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Office Pest Control',
            'slug' => 'office-pest-control',
            'description' => 'Commercial pest control for offices — cockroach, rodent, and ant treatments by workspace size.',
            'sort_order' => 2,
        ],
        [
            'name' => 'Restaurant Pest Control',
            'slug' => 'restaurant-pest-control',
            'description' => 'Food-safe pest control for restaurant kitchens, dining areas, and full premises.',
            'sort_order' => 3,
        ],
    ],
    'services' => [
        [
            'name' => 'Apartment cockroach control',
            'slug' => 'apartment-cockroach-control',
            'sub_category_slug' => 'home-pest-control',
            'base_price' => 100.0,
            'variants' => [pest_control_inspection_variant()],
        ],
        [
            'name' => 'Bungalow cockroach control',
            'slug' => 'bungalow-cockroach-control',
            'sub_category_slug' => 'home-pest-control',
            'base_price' => 100.0,
            'variants' => [pest_control_inspection_variant()],
        ],
        [
            'name' => 'Kitchen cockroach control',
            'slug' => 'kitchen-cockroach-control',
            'sub_category_slug' => 'home-pest-control',
            'base_price' => 100.0,
            'variants' => [pest_control_inspection_variant()],
        ],
        [
            'name' => 'Partial home cockroach control',
            'slug' => 'partial-home-cockroach-control',
            'sub_category_slug' => 'home-pest-control',
            'base_price' => 100.0,
            'variants' => [pest_control_inspection_variant()],
        ],
        [
            'name' => 'Office cockroach control',
            'slug' => 'office-cockroach-control',
            'sub_category_slug' => 'office-pest-control',
            'base_price' => 100.0,
            'variants' => [pest_control_inspection_variant()],
        ],
        [
            'name' => 'Office rodent control',
            'slug' => 'office-rodent-control',
            'sub_category_slug' => 'office-pest-control',
            'base_price' => 100.0,
            'variants' => [pest_control_inspection_variant()],
        ],
        [
            'name' => 'Office ant control',
            'slug' => 'office-ant-control',
            'sub_category_slug' => 'office-pest-control',
            'base_price' => 100.0,
            'variants' => [pest_control_inspection_variant()],
        ],
        [
            'name' => 'Restaurant kitchen pest control',
            'slug' => 'restaurant-kitchen-pest-control',
            'sub_category_slug' => 'restaurant-pest-control',
            'base_price' => 100.0,
            'variants' => [pest_control_inspection_variant()],
        ],
        [
            'name' => 'Restaurant dining pest control',
            'slug' => 'restaurant-dining-pest-control',
            'sub_category_slug' => 'restaurant-pest-control',
            'base_price' => 100.0,
            'variants' => [pest_control_inspection_variant()],
        ],
        [
            'name' => 'Restaurant cockroach control',
            'slug' => 'restaurant-cockroach-control',
            'sub_category_slug' => 'restaurant-pest-control',
            'base_price' => 100.0,
            'variants' => [pest_control_inspection_variant()],
        ],
    ],
];
