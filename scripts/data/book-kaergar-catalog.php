<?php

/**
 * Book Kaergar catalog — time-hire professionals (hour / half day / full day).
 */

if (! function_exists('book_kaergar_time_variants')) {
    function book_kaergar_time_variants(array $prices = []): array
    {
        $hourly = (float) ($prices['hourly'] ?? 300.0);
        $halfDay = (float) ($prices['half-day'] ?? 1000.0);
        $fullDay = (float) ($prices['full-day'] ?? 1800.0);

        return [
            [
                'variant_key' => 'hourly',
                'title' => 'Book for 1 hour',
                'variation_label' => '1 hour',
                'price' => $hourly,
            ],
            [
                'variant_key' => 'half-day',
                'title' => 'Book for half day (4 hours)',
                'variation_label' => 'Half day (4 hours)',
                'price' => $halfDay,
            ],
            [
                'variant_key' => 'full-day',
                'title' => 'Book for full day (8 hours)',
                'variation_label' => 'Full day (8 hours)',
                'price' => $fullDay,
            ],
        ];
    }
}

return [
    'category' => [
        'name' => 'Book Kaergar',
        'slug' => 'book-kaergar',
        'description' => 'Hire verified Panun Kaergar professionals by the hour, half day, or full day across Kashmir — trades, site help, home care, and beauty artists.',
        'sort_order' => 1,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Home Trades',
            'slug' => 'home-trades',
            'description' => 'Book carpenters, electricians, plumbers, and painters by time.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Building & Site',
            'slug' => 'building-site',
            'description' => 'Book masons, labour, and welders / fabricators for site work.',
            'sort_order' => 2,
        ],
        [
            'name' => 'Home Care',
            'slug' => 'home-care',
            'description' => 'Book gardeners and cleaners for home upkeep by the hour or day.',
            'sort_order' => 3,
        ],
        [
            'name' => 'Beauty Artists',
            'slug' => 'beauty-artists',
            'description' => 'Book makeup and mehndi artists for events by time package.',
            'sort_order' => 4,
        ],
    ],
    'services' => [
        [
            'name' => 'Book Carpenter',
            'slug' => 'book-a-carpenter',
            'sub_category_slug' => 'home-trades',
            'role' => 'carpenter',
            'base_price' => 300.0,
            'variants' => book_kaergar_time_variants(),
        ],
        [
            'name' => 'Book Electrician',
            'slug' => 'book-an-electrician',
            'sub_category_slug' => 'home-trades',
            'role' => 'electrician',
            'base_price' => 300.0,
            'variants' => book_kaergar_time_variants(),
        ],
        [
            'name' => 'Book Plumber',
            'slug' => 'book-a-plumber',
            'sub_category_slug' => 'home-trades',
            'role' => 'plumber',
            'base_price' => 300.0,
            'variants' => book_kaergar_time_variants(),
        ],
        [
            'name' => 'Book Painter',
            'slug' => 'book-a-painter',
            'sub_category_slug' => 'home-trades',
            'role' => 'painter',
            'base_price' => 300.0,
            'variants' => book_kaergar_time_variants(),
        ],
        [
            'name' => 'Book Mason',
            'slug' => 'book-a-mason',
            'sub_category_slug' => 'building-site',
            'role' => 'mason',
            'base_price' => 300.0,
            'variants' => book_kaergar_time_variants(),
        ],
        [
            'name' => 'Book Labour',
            'slug' => 'book-labour',
            'sub_category_slug' => 'building-site',
            'role' => 'labour',
            'base_price' => 250.0,
            'variants' => book_kaergar_time_variants([
                'hourly' => 250.0,
                'half-day' => 800.0,
                'full-day' => 1400.0,
            ]),
        ],
        [
            'name' => 'Book Welder / Fabricator',
            'slug' => 'book-a-welder',
            'sub_category_slug' => 'building-site',
            'role' => 'welder',
            'base_price' => 350.0,
            'variants' => book_kaergar_time_variants([
                'hourly' => 350.0,
                'half-day' => 1200.0,
                'full-day' => 2000.0,
            ]),
        ],
        [
            'name' => 'Book Gardener',
            'slug' => 'book-a-gardener',
            'sub_category_slug' => 'home-care',
            'role' => 'gardener',
            'base_price' => 300.0,
            'variants' => book_kaergar_time_variants(),
        ],
        [
            'name' => 'Book Cleaner',
            'slug' => 'book-a-cleaner',
            'sub_category_slug' => 'home-care',
            'role' => 'cleaner',
            'base_price' => 300.0,
            'variants' => book_kaergar_time_variants(),
        ],
        [
            'name' => 'Book Makeup Artist',
            'slug' => 'book-makeup-artist',
            'sub_category_slug' => 'beauty-artists',
            'role' => 'makeup',
            'base_price' => 500.0,
            'variants' => book_kaergar_time_variants([
                'hourly' => 500.0,
                'half-day' => 1800.0,
                'full-day' => 3200.0,
            ]),
        ],
        [
            'name' => 'Book Mehndi Artist',
            'slug' => 'book-mehndi-artist',
            'sub_category_slug' => 'beauty-artists',
            'role' => 'mehndi',
            'base_price' => 400.0,
            'variants' => book_kaergar_time_variants([
                'hourly' => 400.0,
                'half-day' => 1400.0,
                'full-day' => 2500.0,
            ]),
        ],
    ],
    /** Old time-hire slugs under skill categories — deactivate if not in this catalog */
    'retire_slugs' => [],
];
