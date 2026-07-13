<?php

/**
 * Vehicle Services catalog — category, sub-categories, services, and variants.
 */

if (! function_exists('vehicle_inspection_variant')) {
    function vehicle_inspection_variant(): array
    {
        return [
            'variant_key' => 'book-site-inspection',
            'title' => 'Book On Site Inspection',
            'variation_label' => 'Book On Site Inspection',
            'price' => 100.0,
        ];
    }
}

if (! function_exists('vehicle_car_size_variants')) {
    function vehicle_car_size_variants(array $prices): array
    {
        $labels = [
            'hatchback' => 'Hatchback',
            'sedan' => 'Sedan',
            'suv' => 'SUV',
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

if (! function_exists('vehicle_bike_cc_variants')) {
    function vehicle_bike_cc_variants(array $prices): array
    {
        return [
            [
                'variant_key' => '100-150cc',
                'title' => '100–150cc',
                'variation_label' => '100–150cc',
                'price' => (float) ($prices['100-150cc'] ?? 0),
            ],
            [
                'variant_key' => '150cc-plus',
                'title' => '150cc+',
                'variation_label' => '150cc+',
                'price' => (float) ($prices['150cc-plus'] ?? 0),
            ],
        ];
    }
}

if (! function_exists('vehicle_two_wheeler_variants')) {
    function vehicle_two_wheeler_variants(array $prices): array
    {
        return [
            [
                'variant_key' => 'bike',
                'title' => 'Bike',
                'variation_label' => 'Bike',
                'price' => (float) ($prices['bike'] ?? 0),
            ],
            [
                'variant_key' => 'scooter',
                'title' => 'Scooter',
                'variation_label' => 'Scooter',
                'price' => (float) ($prices['scooter'] ?? 0),
            ],
        ];
    }
}

return [
    'category' => [
        'name' => 'Vehicle Services',
        'slug' => 'vehicle-services',
        'description' => 'On-demand car and two-wheeler care in Srinagar — washing, periodic servicing, repairs, and battery or tyre help by verified technicians.',
        'sort_order' => 14,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Car Wash & Detailing',
            'slug' => 'car-wash-detailing',
            'description' => 'Exterior wash, interior cleaning, and full detailing for hatchbacks, sedans, and SUVs.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Car Repair & Maintenance',
            'slug' => 'car-repair-maintenance',
            'description' => 'Periodic servicing, battery replacement, AC service, tyre puncture repair, and on-site inspections.',
            'sort_order' => 2,
        ],
        [
            'name' => 'Bike & Scooter Service',
            'slug' => 'bike-scooter-service',
            'description' => 'General bike service, scooter periodic service, wash, battery, and puncture repair.',
            'sort_order' => 3,
        ],
    ],
    'services' => [
        [
            'name' => 'Exterior Car Wash',
            'slug' => 'exterior-car-wash',
            'sub_category_slug' => 'car-wash-detailing',
            'base_price' => 399.0,
            'variants' => vehicle_car_size_variants(['hatchback' => 399, 'sedan' => 499, 'suv' => 599]),
        ],
        [
            'name' => 'Interior Car Cleaning',
            'slug' => 'interior-car-cleaning',
            'sub_category_slug' => 'car-wash-detailing',
            'base_price' => 499.0,
            'variants' => vehicle_car_size_variants(['hatchback' => 499, 'sedan' => 699, 'suv' => 899]),
        ],
        [
            'name' => 'Full Car Detailing',
            'slug' => 'full-car-detailing',
            'sub_category_slug' => 'car-wash-detailing',
            'base_price' => 1499.0,
            'variants' => vehicle_car_size_variants(['hatchback' => 1499, 'sedan' => 1999, 'suv' => 2499]),
        ],
        [
            'name' => 'General Car Inspection',
            'slug' => 'general-car-inspection',
            'sub_category_slug' => 'car-repair-maintenance',
            'base_price' => 100.0,
            'variants' => [vehicle_inspection_variant()],
        ],
        [
            'name' => 'Periodic Car Service',
            'slug' => 'periodic-car-service',
            'sub_category_slug' => 'car-repair-maintenance',
            'base_price' => 1999.0,
            'variants' => vehicle_car_size_variants(['hatchback' => 1999, 'sedan' => 2499, 'suv' => 2999]),
        ],
        [
            'name' => 'Car Battery Replacement',
            'slug' => 'car-battery-replacement',
            'sub_category_slug' => 'car-repair-maintenance',
            'base_price' => 100.0,
            'variants' => [vehicle_inspection_variant()],
        ],
        [
            'name' => 'Car AC Service & Gas Refill',
            'slug' => 'car-ac-service-gas-refill',
            'sub_category_slug' => 'car-repair-maintenance',
            'base_price' => 100.0,
            'variants' => [vehicle_inspection_variant()],
        ],
        [
            'name' => 'Car Tyre Puncture Repair',
            'slug' => 'car-tyre-puncture-repair',
            'sub_category_slug' => 'car-repair-maintenance',
            'base_price' => 100.0,
            'variants' => [vehicle_inspection_variant()],
        ],
        [
            'name' => 'Bike General Service',
            'slug' => 'bike-general-service',
            'sub_category_slug' => 'bike-scooter-service',
            'base_price' => 499.0,
            'variants' => vehicle_bike_cc_variants(['100-150cc' => 499, '150cc-plus' => 699]),
        ],
        [
            'name' => 'Scooter Periodic Service',
            'slug' => 'scooter-periodic-service',
            'sub_category_slug' => 'bike-scooter-service',
            'base_price' => 100.0,
            'variants' => [vehicle_inspection_variant()],
        ],
        [
            'name' => 'Bike & Scooter Wash',
            'slug' => 'bike-scooter-wash',
            'sub_category_slug' => 'bike-scooter-service',
            'base_price' => 199.0,
            'variants' => vehicle_two_wheeler_variants(['bike' => 199, 'scooter' => 149]),
        ],
        [
            'name' => 'Two-Wheeler Tyre Puncture Repair',
            'slug' => 'two-wheeler-tyre-puncture-repair',
            'sub_category_slug' => 'bike-scooter-service',
            'base_price' => 100.0,
            'variants' => [vehicle_inspection_variant()],
        ],
        [
            'name' => 'Two-Wheeler Battery Replacement',
            'slug' => 'two-wheeler-battery-replacement',
            'sub_category_slug' => 'bike-scooter-service',
            'base_price' => 100.0,
            'variants' => [vehicle_inspection_variant()],
        ],
    ],
];
