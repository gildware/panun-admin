<?php

/**
 * Interior Decor catalog — simple consultation-focused category.
 */

if (! function_exists('interior_decor_inspection_variant')) {
    function interior_decor_inspection_variant(): array
    {
        return [
            'variant_key' => 'book-site-inspection',
            'title' => 'Book Site Visit',
            'variation_label' => 'Book Site Visit',
            'price' => 100.0,
        ];
    }
}

return [
    'category' => [
        'name' => 'Interior Decor',
        'slug' => 'interior-decor',
        'description' => 'Interior decor consultation in Srinagar — space planning, styling advice, and home makeover support.',
        'sort_order' => 14,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Home Decor Consultation',
            'slug' => 'home-decor-consultation',
            'description' => 'Layout, colour, furniture, and soft furnishing advice for homes and flats.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Commercial Decor Styling',
            'slug' => 'commercial-decor-styling',
            'description' => 'Interior styling for offices, shops, and small businesses.',
            'sort_order' => 2,
        ],
    ],
    'services' => [
        [
            'name' => 'Room Layout & Space Planning',
            'slug' => 'room-layout-space-planning',
            'sub_category_slug' => 'home-decor-consultation',
            'base_price' => 100.0,
            'variants' => [interior_decor_inspection_variant()],
        ],
        [
            'name' => 'Home Makeover Consultation',
            'slug' => 'home-makeover-consultation',
            'sub_category_slug' => 'home-decor-consultation',
            'base_price' => 100.0,
            'variants' => [interior_decor_inspection_variant()],
        ],
        [
            'name' => 'Curtains & Soft Furnishing Advice',
            'slug' => 'curtains-soft-furnishing-advice',
            'sub_category_slug' => 'home-decor-consultation',
            'base_price' => 100.0,
            'variants' => [interior_decor_inspection_variant()],
        ],
        [
            'name' => 'Office & Shop Interior Styling',
            'slug' => 'office-shop-interior-styling',
            'sub_category_slug' => 'commercial-decor-styling',
            'base_price' => 100.0,
            'variants' => [interior_decor_inspection_variant()],
        ],
    ],
];
