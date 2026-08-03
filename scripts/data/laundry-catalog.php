<?php

/**
 * Dry Cleaning & Laundry catalog — sub-categories, services, and variants (₹50 flat).
 */

if (! function_exists('laundry_variant')) {
    function laundry_variant(string $key, string $title, float $price = 50.0): array
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
        'name' => 'Dry Cleaning & Laundry',
        'slug' => 'laundry',
        'description' => 'Professional laundry, garment dry cleaning, home linen, shoe, and bag care across Kashmir by verified Panun Kaergar teams.',
        'sort_order' => 1,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Laundry',
            'slug' => 'wash-laundry',
            'description' => 'Clothing laundry, home linen wash, shoe cleaning, and bag cleaning with careful fabric handling.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Dry Cleaning',
            'slug' => 'dry-clean',
            'description' => 'Garment and home linen dry cleaning for suits, sarees, occasion wear, curtains, and quilts.',
            'sort_order' => 2,
        ],
    ],
    'deactivate_sub_slugs' => [],
    'services' => [
        // Laundry
        [
            'name' => 'Clothing Laundry',
            'slug' => 'clothing-laundry',
            'sub_category_slug' => 'wash-laundry',
            'base_price' => 50.0,
            'variants' => [
                laundry_variant('wash-fold-per-kg', 'Wash & Fold (Per Kg)'),
                laundry_variant('wash-iron-per-kg', 'Wash & Iron (Per Kg)'),
            ],
        ],
        [
            'name' => 'Home Linen Laundry',
            'slug' => 'home-linen-laundry',
            'sub_category_slug' => 'wash-laundry',
            'base_price' => 50.0,
            'variants' => [
                laundry_variant('bedsheet-single', 'Bedsheet (Single)'),
                laundry_variant('bedsheet-double', 'Bedsheet (Double)'),
                laundry_variant('blanket-single', 'Blanket (Single)'),
                laundry_variant('blanket-double', 'Blanket (Double)'),
                laundry_variant('comforter-quilt-single', 'Comforter / Quilt (Single)'),
                laundry_variant('comforter-quilt-double', 'Comforter / Quilt (Double)'),
                laundry_variant('curtain', 'Curtain'),
                laundry_variant('pillow-cover', 'Pillow Cover'),
                laundry_variant('towel-small', 'Towel (Small)'),
                laundry_variant('towel-large', 'Towel (Large)'),
            ],
        ],
        [
            'name' => 'Shoe Cleaning',
            'slug' => 'shoe-cleaning',
            'sub_category_slug' => 'wash-laundry',
            'base_price' => 50.0,
            'variants' => [
                laundry_variant('sneakers', 'Sneakers'),
                laundry_variant('leather-shoes', 'Leather Shoes'),
                laundry_variant('sports-shoes', 'Sports Shoes'),
                laundry_variant('boots', 'Boots'),
            ],
        ],
        [
            'name' => 'Bag Cleaning',
            'slug' => 'bag-cleaning',
            'sub_category_slug' => 'wash-laundry',
            'base_price' => 50.0,
            'variants' => [
                laundry_variant('school-bag', 'School Bag'),
                laundry_variant('backpack', 'Backpack'),
                laundry_variant('handbag', 'Handbag'),
                laundry_variant('laptop-bag', 'Laptop Bag'),
            ],
        ],

        // Dry Cleaning
        [
            'name' => 'Garment Dry Cleaning',
            'slug' => 'garment-dry-cleaning',
            'sub_category_slug' => 'dry-clean',
            'base_price' => 50.0,
            'variants' => [
                laundry_variant('shirt', 'Shirt'),
                laundry_variant('t-shirt', 'T-Shirt'),
                laundry_variant('trouser-jeans', 'Trouser / Jeans'),
                laundry_variant('kurta-kurti', 'Kurta / Kurti'),
                laundry_variant('salwar-suit', 'Salwar Suit'),
                laundry_variant('saree', 'Saree'),
                laundry_variant('suit-2-piece', 'Suit (2 Piece)'),
                laundry_variant('suit-3-piece', 'Suit (3 Piece)'),
                laundry_variant('blazer', 'Blazer'),
                laundry_variant('jacket', 'Jacket'),
                laundry_variant('coat', 'Coat'),
                laundry_variant('sweater', 'Sweater'),
                laundry_variant('lehenga', 'Lehenga'),
                laundry_variant('sherwani', 'Sherwani'),
            ],
        ],
        [
            'name' => 'Home Linen Dry Cleaning',
            'slug' => 'home-linen-dry-cleaning',
            'sub_category_slug' => 'dry-clean',
            'base_price' => 50.0,
            'variants' => [
                laundry_variant('curtain', 'Curtain'),
                laundry_variant('blanket', 'Blanket'),
                laundry_variant('comforter-quilt', 'Comforter / Quilt'),
            ],
        ],
    ],
];
