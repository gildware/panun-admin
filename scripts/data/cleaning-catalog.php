<?php

/**
 * Cleaning Services catalog — sub-categories, services, and variants (₹50 flat).
 */

if (! function_exists('cleaning_variant')) {
    function cleaning_variant(string $key, string $title, float $price = 50.0): array
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
        'name' => 'Cleaning Services',
        'slug' => 'cleaning',
        'description' => 'Professional home, commercial, furniture, appliance, and post-construction cleaning across Kashmir by verified Panun Kaergar teams.',
        'sort_order' => 1,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Home & Commercial Cleaning',
            'slug' => 'home-commercial-cleaning',
            'description' => 'Bathroom, rooms, shops, kitchens, pantry, windows, and floor cleaning for homes and commercial spaces.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Furniture & Fabric Cleaning',
            'slug' => 'furniture-fabric-cleaning',
            'description' => 'Sofa, office chair, mattress, and carpet cleaning for homes and workplaces.',
            'sort_order' => 2,
        ],
        [
            'name' => 'Appliance & Utility Cleaning',
            'slug' => 'appliance-utility-cleaning',
            'description' => 'Fan, fridge, oven, chimney, and water tank cleaning by trained professionals.',
            'sort_order' => 3,
        ],
        [
            'name' => 'Post-Construction Cleaning',
            'slug' => 'post-construction-cleaning',
            'description' => 'Deep clean after renovation or construction for homes, offices, shops, hotels, and clinics.',
            'sort_order' => 4,
        ],
    ],
    'deactivate_sub_slugs' => [
        'home-cleaning',
        'office-commercial-cleaning',
    ],
    'services' => [
        // Home & Commercial Cleaning
        [
            'name' => 'Bathroom Cleaning',
            'slug' => 'bathroom-cleaning',
            'sub_category_slug' => 'home-commercial-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('standard-upto-20-sq-ft', 'Standard — Upto 20 sq ft'),
                cleaning_variant('intense-upto-20-sq-ft', 'Intense — Upto 20 sq ft'),
                cleaning_variant('standard-upto-50-sq-ft', 'Standard — Upto 50 sq ft'),
                cleaning_variant('intense-upto-50-sq-ft', 'Intense — Upto 50 sq ft'),
            ],
        ],
        [
            'name' => 'Room Cleaning',
            'slug' => 'room-cleaning',
            'sub_category_slug' => 'home-commercial-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('home-unfurnished', 'Home — Unfurnished'),
                cleaning_variant('home-furnished', 'Home — Furnished'),
            ],
        ],
        [
            'name' => 'Shop Cleaning',
            'slug' => 'shop-cleaning',
            'sub_category_slug' => 'home-commercial-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('shop-upto-200-sq-ft', 'Shop — Upto 200 sq ft'),
                cleaning_variant('shop-upto-500-sq-ft', 'Shop — Upto 500 sq ft'),
            ],
        ],
        [
            'name' => 'Kitchen Cleaning',
            'slug' => 'kitchen-cleaning',
            'sub_category_slug' => 'home-commercial-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('home-standard', 'Home — Standard'),
                cleaning_variant('home-intense', 'Home — Intense'),
            ],
        ],
        [
            'name' => 'Pantry Cleaning',
            'slug' => 'pantry-cleaning',
            'sub_category_slug' => 'home-commercial-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('office-standard', 'Office — Standard'),
                cleaning_variant('office-intense', 'Office — Intense'),
            ],
        ],
        [
            'name' => 'Restaurant Kitchen Cleaning',
            'slug' => 'restaurant-kitchen-cleaning',
            'sub_category_slug' => 'home-commercial-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('restaurant-standard', 'Restaurant — Standard'),
                cleaning_variant('restaurant-intense', 'Restaurant — Intense'),
            ],
        ],
        [
            'name' => 'Windows Cleaning',
            'slug' => 'windows-cleaning',
            'sub_category_slug' => 'home-commercial-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('glass-doors-windows', 'Glass doors & windows'),
                cleaning_variant('windows-with-net-glass-doors', 'Windows with net & glass doors'),
            ],
        ],
        [
            'name' => 'Floor Cleaning',
            'slug' => 'floor-cleaning',
            'sub_category_slug' => 'home-commercial-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('tile-marble-mopping-upto-500-sq-ft', 'Tile/Marble — Mopping upto 500 sq ft'),
                cleaning_variant('tile-marble-deep-scrub-upto-500-sq-ft', 'Tile/Marble — Deep scrub upto 500 sq ft'),
            ],
        ],

        // Furniture & Fabric Cleaning
        [
            'name' => 'Sofa Cleaning',
            'slug' => 'sofa-cleaning',
            'sub_category_slug' => 'furniture-fabric-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('leather-5-seater', 'Leather — 5 Seater'),
                cleaning_variant('leather-7-seater', 'Leather — 7 Seater'),
                cleaning_variant('fabric-5-seater', 'Fabric — 5 Seater'),
                cleaning_variant('fabric-7-seater', 'Fabric — 7 Seater'),
            ],
        ],
        [
            'name' => 'Office Chair Cleaning',
            'slug' => 'office-chair-cleaning',
            'sub_category_slug' => 'furniture-fabric-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('executive-chair', 'Executive chair'),
                cleaning_variant('visitor-workstation-chair', 'Visitor / workstation chair'),
            ],
        ],
        [
            'name' => 'Mattress Cleaning',
            'slug' => 'mattress-cleaning',
            'sub_category_slug' => 'furniture-fabric-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('double-mattress', 'Double mattress'),
                cleaning_variant('single-mattress', 'Single mattress'),
            ],
        ],
        [
            'name' => 'Carpet Cleaning',
            'slug' => 'carpet-cleaning',
            'sub_category_slug' => 'furniture-fabric-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('upto-10-sq-ft', 'Upto 10 sq ft'),
                cleaning_variant('upto-50-sq-ft', 'Upto 50 sq ft'),
                cleaning_variant('upto-100-sq-ft', 'Upto 100 sq ft'),
            ],
        ],

        // Appliance & Utility Cleaning
        [
            'name' => 'Fan Cleaning',
            'slug' => 'fan-cleaning',
            'sub_category_slug' => 'appliance-utility-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('table-pedestal-fan', 'Table / pedestal fan'),
                cleaning_variant('ceiling-fan', 'Ceiling fan'),
            ],
        ],
        [
            'name' => 'Fridge Cleaning',
            'slug' => 'fridge-cleaning',
            'sub_category_slug' => 'appliance-utility-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('standard', 'Standard'),
                cleaning_variant('intense', 'Intense'),
            ],
        ],
        [
            'name' => 'Oven / Microwave Cleaning',
            'slug' => 'oven-microwave-cleaning',
            'sub_category_slug' => 'appliance-utility-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('standard', 'Standard'),
                cleaning_variant('intense', 'Intense'),
            ],
        ],
        [
            'name' => 'Chimney Cleaning',
            'slug' => 'chimney-cleaning',
            'sub_category_slug' => 'appliance-utility-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('standard', 'Standard'),
                cleaning_variant('intense', 'Intense'),
            ],
        ],
        [
            'name' => 'Water Tank Cleaning',
            'slug' => 'water-tank-cleaning',
            'sub_category_slug' => 'appliance-utility-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('500-ltr', '500 ltr'),
                cleaning_variant('1000-ltr', '1000 ltr'),
            ],
        ],

        // Post-Construction Cleaning
        [
            'name' => 'Post-Construction Cleaning',
            'slug' => 'post-construction-cleaning-service',
            'sub_category_slug' => 'post-construction-cleaning',
            'base_price' => 50.0,
            'variants' => [
                cleaning_variant('home-upto-1000-sq-ft', 'Home — upto 1000 sq ft'),
                cleaning_variant('home-upto-5000-sq-ft', 'Home — upto 5000 sq ft'),
                cleaning_variant('office-shop-upto-1000-sq-ft', 'Office / Shop — upto 1000 sq ft'),
                cleaning_variant('hotel-restaurant-clinic-upto-1000-sq-ft', 'Hotel / Restaurant / Clinic — upto 1000 sq ft'),
                cleaning_variant('book-on-site-inspection', 'Book on Site Inspection'),
            ],
        ],
    ],
];
