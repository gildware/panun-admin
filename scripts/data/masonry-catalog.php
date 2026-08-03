<?php

/**
 * Masonry Services catalog — Install / Repair / Inspection.
 *
 * Most install/repair jobs are inspection-first (site quote after visit).
 * Live main slug stays `masonry`.
 */

if (! function_exists('masonry_variant')) {
    function masonry_variant(string $key, string $title, float $price): array
    {
        return [
            'variant_key' => $key,
            'title' => $title,
            'variation_label' => $title,
            'price' => $price,
        ];
    }
}

return [
    'category' => [
        'name' => 'Masonary Services',
        'slug' => 'masonry',
        'description' => 'Professional masonry installation, repair, and inspection across Kashmir by verified Panun Kaergar masons.',
        'sort_order' => 1,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Masonry Installation',
            'slug' => 'masonry-installation',
            'description' => 'Brick, plaster, tile, marble, stone, stair, waterproofing, boundary, and bathroom masonry installation — inspection first.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Masonry Repair',
            'slug' => 'masonry-repair',
            'description' => 'Crack, plaster, tile, marble, stair, damp, and boundary masonry repairs — inspection first.',
            'sort_order' => 2,
        ],
        [
            'name' => 'Masonry Inspection',
            'slug' => 'masonry-inspection',
            'description' => 'Crack checks, damp checks, safety checks, and pre-renovation masonry surveys.',
            'sort_order' => 3,
        ],
    ],
    'deactivate_sub_slugs' => [
        'masonry-installs',
        'masonry-repairs',
    ],
    'services' => [
        // Installation (inspection-first)
        [
            'name' => 'Masonry Brick Install',
            'slug' => 'masonry-brick-install',
            'sub_category_slug' => 'masonry-installation',
            'base_price' => 299.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],
        [
            'name' => 'Masonry Plaster Install',
            'slug' => 'masonry-plaster-install',
            'sub_category_slug' => 'masonry-installation',
            'base_price' => 299.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],
        [
            'name' => 'Masonry Tile Install',
            'slug' => 'masonry-tile-install',
            'sub_category_slug' => 'masonry-installation',
            'base_price' => 299.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],
        [
            'name' => 'Masonry Marble Install',
            'slug' => 'masonry-marble-install',
            'sub_category_slug' => 'masonry-installation',
            'base_price' => 299.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],
        [
            'name' => 'Masonry Stone Install',
            'slug' => 'masonry-stone-install',
            'sub_category_slug' => 'masonry-installation',
            'base_price' => 299.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],
        [
            'name' => 'Masonry Stair Install',
            'slug' => 'masonry-stair-install',
            'sub_category_slug' => 'masonry-installation',
            'base_price' => 299.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],
        [
            'name' => 'Masonry Waterproof Install',
            'slug' => 'masonry-waterproof-install',
            'sub_category_slug' => 'masonry-installation',
            'base_price' => 299.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],
        [
            'name' => 'Masonry Boundary Install',
            'slug' => 'masonry-boundary-install',
            'sub_category_slug' => 'masonry-installation',
            'base_price' => 299.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],
        [
            'name' => 'Masonry Full Bathroom Setup',
            'slug' => 'masonry-full-bathroom-setup',
            'sub_category_slug' => 'masonry-installation',
            'base_price' => 299.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],

        // Repair (inspection-first)
        [
            'name' => 'Masonry Crack Repair',
            'slug' => 'masonry-crack-repair',
            'sub_category_slug' => 'masonry-repair',
            'base_price' => 199.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Masonry Plaster Repair',
            'slug' => 'masonry-plaster-repair',
            'sub_category_slug' => 'masonry-repair',
            'base_price' => 199.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Masonry Tile Repair',
            'slug' => 'masonry-tile-repair',
            'sub_category_slug' => 'masonry-repair',
            'base_price' => 199.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Masonry Marble Repair',
            'slug' => 'masonry-marble-repair',
            'sub_category_slug' => 'masonry-repair',
            'base_price' => 199.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Masonry Stair Repair',
            'slug' => 'masonry-stair-repair',
            'sub_category_slug' => 'masonry-repair',
            'base_price' => 199.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Masonry Damp Repair',
            'slug' => 'masonry-damp-repair',
            'sub_category_slug' => 'masonry-repair',
            'base_price' => 199.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Masonry Boundary Repair',
            'slug' => 'masonry-boundary-repair',
            'sub_category_slug' => 'masonry-repair',
            'base_price' => 199.0,
            'variants' => [
                masonry_variant('book-inspection', 'Book Inspection', 199.0),
            ],
        ],

        // Inspection
        [
            'name' => 'Masonry Site Check',
            'slug' => 'masonry-site-check',
            'sub_category_slug' => 'masonry-inspection',
            'base_price' => 149.0,
            'variants' => [
                masonry_variant('crack-check', 'Crack Check', 149.0),
                masonry_variant('damp-check', 'Damp Check', 149.0),
                masonry_variant('unknown-problem', 'Unknown Problem', 149.0),
            ],
        ],
        [
            'name' => 'Masonry Safety Check',
            'slug' => 'masonry-safety-check',
            'sub_category_slug' => 'masonry-inspection',
            'base_price' => 299.0,
            'variants' => [
                masonry_variant('full-home-masonry-check', 'Full Home Masonry Check', 299.0),
            ],
        ],
        [
            'name' => 'Masonry Pre-Work Check',
            'slug' => 'masonry-pre-work-check',
            'sub_category_slug' => 'masonry-inspection',
            'base_price' => 249.0,
            'variants' => [
                masonry_variant('before-renovation-check', 'Before Renovation Check', 249.0),
            ],
        ],
    ],
];
