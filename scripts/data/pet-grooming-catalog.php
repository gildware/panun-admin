<?php

/**
 * Pet Grooming catalog — category, sub-categories, services, and variants.
 */

if (! function_exists('pet_grooming_dog_sizes')) {
    function pet_grooming_dog_sizes(array $prices): array
    {
        $labels = [
            'small' => 'Small (up to 10 kg)',
            'medium' => 'Medium (10–25 kg)',
            'large' => 'Large (25–40 kg)',
            'extra-large' => 'Extra large (40+ kg)',
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

if (! function_exists('pet_grooming_dog_sizes_three')) {
    function pet_grooming_dog_sizes_three(array $prices): array
    {
        $labels = [
            'small' => 'Small (up to 10 kg)',
            'medium' => 'Medium (10–25 kg)',
            'large' => 'Large (25–40 kg)',
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

if (! function_exists('pet_grooming_cat_coats')) {
    function pet_grooming_cat_coats(array $prices): array
    {
        return [
            [
                'variant_key' => 'short-hair',
                'title' => 'Short hair',
                'variation_label' => 'Short hair',
                'price' => (float) ($prices['short-hair'] ?? 0),
            ],
            [
                'variant_key' => 'long-hair',
                'title' => 'Long hair',
                'variation_label' => 'Long hair',
                'price' => (float) ($prices['long-hair'] ?? 0),
            ],
        ];
    }
}

if (! function_exists('pet_grooming_per_pet')) {
    function pet_grooming_per_pet(float $price): array
    {
        return [[
            'variant_key' => 'per-pet',
            'title' => 'Per pet',
            'variation_label' => 'Per pet',
            'price' => $price,
        ]];
    }
}

if (! function_exists('pet_grooming_monthly_plan')) {
    function pet_grooming_monthly_plan(array $prices): array
    {
        return [
            [
                'variant_key' => '1-visit-per-month',
                'title' => '1 visit per month',
                'variation_label' => '1 visit per month',
                'price' => (float) ($prices['1-visit'] ?? 0),
            ],
            [
                'variant_key' => '2-visits-per-month',
                'title' => '2 visits per month',
                'variation_label' => '2 visits per month',
                'price' => (float) ($prices['2-visits'] ?? 0),
            ],
        ];
    }
}

return [
    'category' => [
        'name' => 'Pet Grooming',
        'slug' => 'pet-grooming',
        'description' => 'Professional at-home pet grooming in Kashmir. Bath, haircut, nail care, and spa packages for dogs and cats by trained groomers.',
        'sort_order' => 13,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Dog Grooming',
            'slug' => 'dog-grooming',
            'description' => 'Full grooming, bath, haircut, nail care, deshedding, and spa packages for dogs of all sizes.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Cat Grooming',
            'slug' => 'cat-grooming',
            'description' => 'Gentle grooming, bath, mat removal, lion cuts, and nail care for cats at home.',
            'sort_order' => 2,
        ],
    ],
    'services' => [
        // Dog Grooming
        [
            'name' => 'Full Dog Grooming',
            'slug' => 'full-dog-grooming',
            'sub_category_slug' => 'dog-grooming',
            'base_price' => 799.0,
            'variants' => pet_grooming_dog_sizes(['small' => 799, 'medium' => 999, 'large' => 1299, 'extra-large' => 1599]),
        ],
        [
            'name' => 'Dog Bath & Brush',
            'slug' => 'dog-bath-and-brush',
            'sub_category_slug' => 'dog-grooming',
            'base_price' => 499.0,
            'variants' => pet_grooming_dog_sizes(['small' => 499, 'medium' => 649, 'large' => 849, 'extra-large' => 1049]),
        ],
        [
            'name' => 'Dog Haircut & Trim',
            'slug' => 'dog-haircut-and-trim',
            'sub_category_slug' => 'dog-grooming',
            'base_price' => 599.0,
            'variants' => pet_grooming_dog_sizes(['small' => 599, 'medium' => 749, 'large' => 949, 'extra-large' => 1149]),
        ],
        [
            'name' => 'Dog Nail Clipping',
            'slug' => 'dog-nail-clipping',
            'sub_category_slug' => 'dog-grooming',
            'base_price' => 199.0,
            'variants' => pet_grooming_per_pet(199.0),
        ],
        [
            'name' => 'Dog Ear Cleaning',
            'slug' => 'dog-ear-cleaning',
            'sub_category_slug' => 'dog-grooming',
            'base_price' => 149.0,
            'variants' => pet_grooming_per_pet(149.0),
        ],
        [
            'name' => 'Dog Teeth Brushing',
            'slug' => 'dog-teeth-brushing',
            'sub_category_slug' => 'dog-grooming',
            'base_price' => 149.0,
            'variants' => pet_grooming_per_pet(149.0),
        ],
        [
            'name' => 'Dog Deshedding Treatment',
            'slug' => 'dog-deshedding-treatment',
            'sub_category_slug' => 'dog-grooming',
            'base_price' => 499.0,
            'variants' => [
                ['variant_key' => 'short-coat', 'title' => 'Short coat', 'variation_label' => 'Short coat', 'price' => 499.0],
                ['variant_key' => 'long-coat', 'title' => 'Long / double coat', 'variation_label' => 'Long / double coat', 'price' => 699.0],
            ],
        ],
        [
            'name' => 'Dog Flea & Tick Bath',
            'slug' => 'dog-flea-tick-bath',
            'sub_category_slug' => 'dog-grooming',
            'base_price' => 449.0,
            'variants' => pet_grooming_dog_sizes_three(['small' => 449, 'medium' => 549, 'large' => 649]),
        ],
        [
            'name' => 'Dog Paw Pad Trim',
            'slug' => 'dog-paw-pad-trim',
            'sub_category_slug' => 'dog-grooming',
            'base_price' => 149.0,
            'variants' => pet_grooming_per_pet(149.0),
        ],
        [
            'name' => 'Puppy First Groom',
            'slug' => 'puppy-first-groom',
            'sub_category_slug' => 'dog-grooming',
            'base_price' => 599.0,
            'variants' => [[
                'variant_key' => 'puppy',
                'title' => 'Puppy (under 6 months)',
                'variation_label' => 'Puppy (under 6 months)',
                'price' => 599.0,
            ]],
        ],
        [
            'name' => 'Senior Dog Gentle Groom',
            'slug' => 'senior-dog-gentle-groom',
            'sub_category_slug' => 'dog-grooming',
            'base_price' => 899.0,
            'variants' => pet_grooming_dog_sizes_three(['small' => 899, 'medium' => 1099, 'large' => 1399]),
        ],
        [
            'name' => 'Dog Spa Package',
            'slug' => 'dog-spa-package',
            'sub_category_slug' => 'dog-grooming',
            'base_price' => 999.0,
            'variants' => pet_grooming_dog_sizes_three(['small' => 999, 'medium' => 1199, 'large' => 1499]),
        ],
        [
            'name' => 'Dog Monthly Grooming Plan',
            'slug' => 'dog-monthly-grooming-plan',
            'sub_category_slug' => 'dog-grooming',
            'base_price' => 699.0,
            'variants' => pet_grooming_monthly_plan(['1-visit' => 699, '2-visits' => 1299]),
        ],
        // Cat Grooming
        [
            'name' => 'Full Cat Grooming',
            'slug' => 'full-cat-grooming',
            'sub_category_slug' => 'cat-grooming',
            'base_price' => 699.0,
            'variants' => pet_grooming_cat_coats(['short-hair' => 699, 'long-hair' => 899]),
        ],
        [
            'name' => 'Cat Bath & Brush',
            'slug' => 'cat-bath-and-brush',
            'sub_category_slug' => 'cat-grooming',
            'base_price' => 449.0,
            'variants' => pet_grooming_cat_coats(['short-hair' => 449, 'long-hair' => 599]),
        ],
        [
            'name' => 'Cat Mat Removal',
            'slug' => 'cat-mat-removal',
            'sub_category_slug' => 'cat-grooming',
            'base_price' => 399.0,
            'variants' => [
                ['variant_key' => 'mild-mats', 'title' => 'Mild mats', 'variation_label' => 'Mild mats', 'price' => 399.0],
                ['variant_key' => 'severe-mats', 'title' => 'Severe mats', 'variation_label' => 'Severe mats', 'price' => 699.0],
            ],
        ],
        [
            'name' => 'Cat Lion Cut',
            'slug' => 'cat-lion-cut',
            'sub_category_slug' => 'cat-grooming',
            'base_price' => 799.0,
            'variants' => pet_grooming_per_pet(799.0),
        ],
        [
            'name' => 'Cat Nail Trim',
            'slug' => 'cat-nail-trim',
            'sub_category_slug' => 'cat-grooming',
            'base_price' => 149.0,
            'variants' => pet_grooming_per_pet(149.0),
        ],
        [
            'name' => 'Cat Ear Cleaning',
            'slug' => 'cat-ear-cleaning',
            'sub_category_slug' => 'cat-grooming',
            'base_price' => 129.0,
            'variants' => pet_grooming_per_pet(129.0),
        ],
        [
            'name' => 'Kitten First Groom',
            'slug' => 'kitten-first-groom',
            'sub_category_slug' => 'cat-grooming',
            'base_price' => 499.0,
            'variants' => [[
                'variant_key' => 'kitten',
                'title' => 'Kitten (under 6 months)',
                'variation_label' => 'Kitten (under 6 months)',
                'price' => 499.0,
            ]],
        ],
        [
            'name' => 'Senior Cat Gentle Groom',
            'slug' => 'senior-cat-gentle-groom',
            'sub_category_slug' => 'cat-grooming',
            'base_price' => 749.0,
            'variants' => pet_grooming_cat_coats(['short-hair' => 749, 'long-hair' => 949]),
        ],
        [
            'name' => 'Cat Flea & Tick Bath',
            'slug' => 'cat-flea-tick-bath',
            'sub_category_slug' => 'cat-grooming',
            'base_price' => 399.0,
            'variants' => pet_grooming_per_pet(399.0),
        ],
        [
            'name' => 'Cat Monthly Grooming Plan',
            'slug' => 'cat-monthly-grooming-plan',
            'sub_category_slug' => 'cat-grooming',
            'base_price' => 599.0,
            'variants' => pet_grooming_monthly_plan(['1-visit' => 599, '2-visits' => 1099]),
        ],
    ],
];
