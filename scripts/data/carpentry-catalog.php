<?php

/**
 * Carpentry Services catalog — sub-categories, services, and variants (₹50 flat).
 *
 * Admin main slug stays `carpentary` (live typo retained).
 */

if (! function_exists('carpentry_variant')) {
    function carpentry_variant(string $key, string $title, float $price = 50.0): array
    {
        return [
            'variant_key' => $key,
            'title' => $title,
            'variation_label' => $title,
            'price' => $price,
        ];
    }
}

if (! function_exists('carpentry_inspection_variant')) {
    function carpentry_inspection_variant(string $title = 'Book Site Inspection'): array
    {
        $key = str_contains(strtolower($title), 'on site') || str_contains(strtolower($title), 'on-site')
            ? 'book-on-site-inspection'
            : 'book-site-inspection';

        return carpentry_variant($key, $title, 50.0);
    }
}

return [
    'category' => [
        'name' => 'Carpentry Services',
        'slug' => 'carpentary',
        'description' => 'Professional carpentry installation, custom making, repairs, and roofing works across Kashmir by verified Panun Kaergar carpenters.',
        'sort_order' => 5,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Carpentry Installation',
            'slug' => 'carpentry-installation',
            'description' => 'Door, window, bed, and table installation by verified carpenters.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Carpentry Making',
            'slug' => 'carpentry-making',
            'description' => 'Custom bed, wardrobe, almirah, table, shelves, kitchen cabinet, and carpentry fabrication after site inspection.',
            'sort_order' => 2,
        ],
        [
            'name' => 'Carpentry Repairs',
            'slug' => 'carpentry-repairs',
            'description' => 'Door, furniture, kitchen cabinet, wardrobe, window, and other carpentry repairs.',
            'sort_order' => 3,
        ],
        [
            'name' => 'Roofing Works',
            'slug' => 'roofing-works',
            'description' => 'Wooden roof installation and repair after on-site inspection.',
            'sort_order' => 4,
        ],
    ],
    'deactivate_sub_slugs' => [],
    'services' => [
        // Carpentry Installation
        [
            'name' => 'Door Installation',
            'slug' => 'door-installation',
            'sub_category_slug' => 'carpentry-installation',
            'base_price' => 50.0,
            'variants' => [
                carpentry_variant('standard-door', 'Standard Door'),
                carpentry_variant('sliding-door', 'Sliding Door'),
            ],
        ],
        [
            'name' => 'Window Installation',
            'slug' => 'window-installation',
            'sub_category_slug' => 'carpentry-installation',
            'base_price' => 50.0,
            'variants' => [
                carpentry_variant('standard-window', 'Standard Window'),
                carpentry_variant('sliding-window', 'Sliding Window'),
            ],
        ],
        [
            'name' => 'Bed Installation',
            'slug' => 'bed-installation',
            'sub_category_slug' => 'carpentry-installation',
            'base_price' => 50.0,
            'variants' => [
                carpentry_variant('single-bed-install', 'Single Bed Install'),
                carpentry_variant('single-bed-uninstall', 'Single Bed Uninstall'),
                carpentry_variant('single-bed-uninstall-install', 'Single Bed Uninstall+ Install'),
                carpentry_variant('double-bed-install', 'Double Bed Install'),
                carpentry_variant('double-bed-uninstall', 'Double Bed Uninstall'),
                carpentry_variant('double-bed-uninstall-install', 'Double Bed Uninstall+ Install'),
            ],
        ],
        [
            'name' => 'Table Installation',
            'slug' => 'table-installation',
            'sub_category_slug' => 'carpentry-installation',
            'base_price' => 50.0,
            'variants' => [
                carpentry_variant('table-install', 'Table Install'),
                carpentry_variant('table-uninstall', 'Table Uninstall'),
                carpentry_variant('table-install-uninstall', 'Table Install Uninstall'),
            ],
        ],

        // Carpentry Making
        [
            'name' => 'Bed Making',
            'slug' => 'bed-making',
            'sub_category_slug' => 'carpentry-making',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book on Site inspection')],
        ],
        [
            'name' => 'Wardrobe Making',
            'slug' => 'wardrobe-making',
            'sub_category_slug' => 'carpentry-making',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book on Site inspection')],
        ],
        [
            'name' => 'Almirah Making',
            'slug' => 'almirah-making',
            'sub_category_slug' => 'carpentry-making',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book on Site inspection')],
        ],
        [
            'name' => 'Table Making',
            'slug' => 'table-making',
            'sub_category_slug' => 'carpentry-making',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book on Site inspection')],
        ],
        [
            'name' => 'Shop Shelves Making',
            'slug' => 'shop-shelves-making',
            'sub_category_slug' => 'carpentry-making',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book on Site inspection')],
        ],
        [
            'name' => 'Kitchen Cabinet Making',
            'slug' => 'kitchen-cabinet-making',
            'sub_category_slug' => 'carpentry-making',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book on Site inspection')],
        ],
        [
            'name' => 'Custom Carpentry Work',
            'slug' => 'custom-carpentry-work',
            'sub_category_slug' => 'carpentry-making',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book on Site inspection')],
        ],

        // Carpentry Repairs
        [
            'name' => 'Door Repair',
            'slug' => 'door-repair',
            'sub_category_slug' => 'carpentry-repairs',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book Site Inspection')],
        ],
        [
            'name' => 'Furniture Repair',
            'slug' => 'furniture-repair',
            'sub_category_slug' => 'carpentry-repairs',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book Site Inspection')],
        ],
        [
            'name' => 'Kitchen Cabinet Repair',
            'slug' => 'kitchen-cabinet-repair',
            'sub_category_slug' => 'carpentry-repairs',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book Site Inspection')],
        ],
        [
            'name' => 'Wardrobe Repair',
            'slug' => 'wardrobe-repair',
            'sub_category_slug' => 'carpentry-repairs',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book Site Inspection')],
        ],
        [
            'name' => 'Window Repair',
            'slug' => 'window-repair',
            'sub_category_slug' => 'carpentry-repairs',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book Site Inspection')],
        ],
        [
            'name' => 'Other Carpentry Repair',
            'slug' => 'other-carpentry-repair',
            'sub_category_slug' => 'carpentry-repairs',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book on Site inspection')],
        ],

        // Roofing Works
        [
            'name' => 'Roof Installation',
            'slug' => 'roof-installation',
            'sub_category_slug' => 'roofing-works',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book Site Inspection')],
        ],
        [
            'name' => 'Roof Repair',
            'slug' => 'roof-repair',
            'sub_category_slug' => 'roofing-works',
            'base_price' => 50.0,
            'variants' => [carpentry_inspection_variant('Book Site Inspection')],
        ],
    ],
];
