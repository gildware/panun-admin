<?php

/**
 * Men's Salon — lean at-home catalog (arranged MVP).
 *
 * Live main slug stays `mens-salon`.
 * Sub-categories keep existing slugs where possible.
 */

if (! function_exists('mens_salon_variant')) {
    function mens_salon_variant(string $key, string $title, float $price): array
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
        'name' => "Men's Salon",
        'slug' => 'mens-salon',
        'description' => 'At-home men’s grooming in Kashmir — haircut, beard, color, facial, waxing, and massage by verified Panun Kaergar stylists.',
        'sort_order' => 6,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => "Men's Hair Services",
            'slug' => 'mens-hair-services',
            'description' => 'Men’s haircut, kids cut, hair color, and hair treatment at home.',
            'sort_order' => 0,
        ],
        [
            'name' => "Men's Beard & Shaving",
            'slug' => 'mens-beard-shaving',
            'description' => 'Beard trim, clean shave, and beard color for men at home.',
            'sort_order' => 1,
        ],
        [
            'name' => "Men's Skin & Grooming",
            'slug' => 'mens-skin-grooming-care',
            'description' => 'Detan, waxing, facial, threading, manicure, pedicure, and massage for men.',
            'sort_order' => 2,
        ],
    ],
    /** Old UC-style / duplicate sub-slugs if any appear later */
    'deactivate_sub_slugs' => [],
    'services' => [
        // Hair
        [
            'name' => "Men's Hair Cut",
            'slug' => 'mens-hair-cut',
            'sub_category_slug' => 'mens-hair-services',
            'base_price' => 300.0,
            'variants' => [
                mens_salon_variant('standard-hair-cut', 'Standard Hair Cut', 300.0),
                mens_salon_variant('premium-hair-cut', 'Premium Hair Cut', 600.0),
            ],
        ],
        [
            'name' => "Men's Kids Hair Cut",
            'slug' => 'mens-kids-hair-cut',
            'sub_category_slug' => 'mens-hair-services',
            'base_price' => 259.0,
            'variants' => [
                mens_salon_variant('kids-hair-cut', 'Kids Hair Cut', 259.0),
            ],
        ],
        [
            'name' => "Men's Hair Color",
            'slug' => 'mens-hair-color',
            'sub_category_slug' => 'mens-hair-services',
            'base_price' => 199.0,
            'variants' => [
                mens_salon_variant('hair-color-with-product', 'Hair Color With Product', 299.0),
                mens_salon_variant('hair-color-without-product', 'Hair Color Without Product', 199.0),
            ],
        ],
        [
            'name' => "Men's Hair Treatment",
            'slug' => 'mens-hair-treatment',
            'sub_category_slug' => 'mens-hair-services',
            'base_price' => 700.0,
            'variants' => [
                mens_salon_variant('standard-hair-treatment', 'Standard Hair Treatment', 700.0),
                mens_salon_variant('premium-hair-treatment', 'Premium Hair Treatment', 1000.0),
            ],
        ],

        // Beard & Shaving
        [
            'name' => "Men's Beard Trimming",
            'slug' => 'mens-beard-trimming',
            'sub_category_slug' => 'mens-beard-shaving',
            'base_price' => 400.0,
            'variants' => [
                mens_salon_variant('beard-trim', 'Beard Trim', 400.0),
                mens_salon_variant('beard-mustache-trim', 'Beard & Mustache Trim', 600.0),
            ],
        ],
        [
            'name' => "Men's Clean Shave",
            'slug' => 'mens-clean-shave',
            'sub_category_slug' => 'mens-beard-shaving',
            'base_price' => 300.0,
            'variants' => [
                mens_salon_variant('standard-clean-shave', 'Standard Clean Shave', 300.0),
                mens_salon_variant('premium-clean-shave', 'Premium Clean Shave', 800.0),
            ],
        ],
        [
            'name' => "Men's Beard Color",
            'slug' => 'mens-beard-color',
            'sub_category_slug' => 'mens-beard-shaving',
            'base_price' => 199.0,
            'variants' => [
                mens_salon_variant('beard-color-with-product', 'Beard Color With Product', 199.0),
                mens_salon_variant('beard-color-without-product', 'Beard Color Without Product', 400.0),
            ],
        ],

        // Skin & Grooming
        [
            'name' => "Men's Detan",
            'slug' => 'mens-detan',
            'sub_category_slug' => 'mens-skin-grooming-care',
            'base_price' => 199.0,
            'variants' => [
                mens_salon_variant('face-neck-detan', 'Face & Neck Detan', 199.0),
                mens_salon_variant('hands-detan', 'Hands Detan', 199.0),
            ],
        ],
        [
            'name' => "Men's Waxing",
            'slug' => 'mens-waxing',
            'sub_category_slug' => 'mens-skin-grooming-care',
            'base_price' => 400.0,
            'variants' => [
                mens_salon_variant('underarm-waxing', 'Underarm Waxing', 400.0),
                mens_salon_variant('chest-waxing', 'Chest Waxing', 400.0),
                mens_salon_variant('back-waxing', 'Back Waxing', 800.0),
                mens_salon_variant('full-arms-waxing', 'Full Arms Waxing', 800.0),
            ],
        ],
        [
            'name' => "Men's Facial & Cleanup",
            'slug' => 'mens-facial-cleanup',
            'sub_category_slug' => 'mens-skin-grooming-care',
            'base_price' => 400.0,
            'variants' => [
                mens_salon_variant('instant-cleanup', 'Instant Cleanup', 400.0),
                mens_salon_variant('deep-cleanup-facial', 'Deep Cleanup Facial', 1000.0),
            ],
        ],
        [
            'name' => "Men's Threading",
            'slug' => 'mens-threading',
            'sub_category_slug' => 'mens-skin-grooming-care',
            'base_price' => 100.0,
            'variants' => [
                mens_salon_variant('eyebrow-threading', 'Eyebrow Threading', 100.0),
                mens_salon_variant('full-face-threading', 'Full Face Threading', 600.0),
            ],
        ],
        [
            'name' => "Men's Pedicure",
            'slug' => 'mens-pedicure',
            'sub_category_slug' => 'mens-skin-grooming-care',
            'base_price' => 549.0,
            'variants' => [
                mens_salon_variant('express-pedicure', 'Express Pedicure', 549.0),
            ],
        ],
        [
            'name' => "Men's Manicure",
            'slug' => 'mens-manicure',
            'sub_category_slug' => 'mens-skin-grooming-care',
            'base_price' => 499.0,
            'variants' => [
                mens_salon_variant('express-manicure', 'Express Manicure', 499.0),
            ],
        ],
        [
            'name' => "Men's Nail Cut & File",
            'slug' => 'mens-nail-cut-file',
            'sub_category_slug' => 'mens-skin-grooming-care',
            'base_price' => 99.0,
            'variants' => [
                mens_salon_variant('hands-nail-cut-file', 'Hands Nail Cut & File', 99.0),
                mens_salon_variant('feet-nail-cut-file', 'Feet Nail Cut & File', 199.0),
            ],
        ],
        [
            'name' => "Men's Massage",
            'slug' => 'mens-massage',
            'sub_category_slug' => 'mens-skin-grooming-care',
            'base_price' => 109.0,
            'variants' => [
                mens_salon_variant('head-massage-10-min', 'Head Massage 10 Min', 109.0),
                mens_salon_variant('head-massage-20-min', 'Head Massage 20 Min', 199.0),
                mens_salon_variant('head-neck-shoulder-massage', 'Head Neck & Shoulder Massage', 299.0),
            ],
        ],
    ],
];
