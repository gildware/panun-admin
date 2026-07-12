<?php

/**
 * Aluminium & Steel Works catalog — category, sub-categories, services, and variants.
 */

if (! function_exists('aluminium_steel_inspection_variant')) {
    function aluminium_steel_inspection_variant(): array
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
        'name' => 'Aluminium & Steel Works',
        'slug' => 'aluminium-steel-works',
        'description' => 'Professional aluminium, steel, ACP, uPVC, railing, grill, and fabrication work for homes, shops, and offices in Kashmir.',
        'sort_order' => 12,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Metal Works Installation',
            'slug' => 'metal-works-installation',
            'description' => 'ACP cladding, aluminium and uPVC windows, railings, grills, gates, PVC panelling, and false ceiling installation.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Metal Works Repairs',
            'slug' => 'metal-works-repairs',
            'description' => 'Repair and maintenance for ACP panels, aluminium windows, railings, gates, grills, and false ceilings.',
            'sort_order' => 2,
        ],
        [
            'name' => 'Metal Works Fabrication',
            'slug' => 'metal-works-fabrication',
            'description' => 'Custom MS gates, SS grills, railings, aluminium frames, and steel bracket fabrication.',
            'sort_order' => 3,
        ],
    ],
    'services' => [
        // Installation
        ['name' => 'ACP Cladding Installation', 'slug' => 'acp-cladding-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Aluminium Window Installation', 'slug' => 'aluminium-window-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Aluminium Door Installation', 'slug' => 'aluminium-door-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'uPVC Window Installation', 'slug' => 'upvc-window-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'uPVC Door Installation', 'slug' => 'upvc-door-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Balcony Railing Installation', 'slug' => 'balcony-railing-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Staircase Railing Installation', 'slug' => 'staircase-railing-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'PVC Wall Panelling Installation', 'slug' => 'pvc-wall-panelling-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'False Ceiling Installation', 'slug' => 'false-ceiling-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'MS Gate Installation', 'slug' => 'ms-gate-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'SS Grill Installation', 'slug' => 'ss-grill-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Glass Partition Installation', 'slug' => 'glass-partition-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Shop Shutter Installation', 'slug' => 'shop-shutter-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Pergola & Car Porch Installation', 'slug' => 'pergola-car-porch-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Signage Frame Installation', 'slug' => 'signage-frame-installation', 'sub_category_slug' => 'metal-works-installation', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        // Repairs
        ['name' => 'ACP Panel Repair', 'slug' => 'acp-panel-repair', 'sub_category_slug' => 'metal-works-repairs', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Aluminium Window Repair', 'slug' => 'aluminium-window-repair', 'sub_category_slug' => 'metal-works-repairs', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Aluminium Door Repair', 'slug' => 'aluminium-door-repair', 'sub_category_slug' => 'metal-works-repairs', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'uPVC Window & Door Repair', 'slug' => 'upvc-window-door-repair', 'sub_category_slug' => 'metal-works-repairs', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Railing Repair', 'slug' => 'railing-repair', 'sub_category_slug' => 'metal-works-repairs', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Gate & Grill Repair', 'slug' => 'gate-grill-repair', 'sub_category_slug' => 'metal-works-repairs', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'False Ceiling Repair', 'slug' => 'false-ceiling-repair', 'sub_category_slug' => 'metal-works-repairs', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'PVC Panel Repair', 'slug' => 'pvc-panel-repair', 'sub_category_slug' => 'metal-works-repairs', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Shop Shutter Repair', 'slug' => 'shop-shutter-repair', 'sub_category_slug' => 'metal-works-repairs', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        // Fabrication
        ['name' => 'Custom MS Gate Fabrication', 'slug' => 'custom-ms-gate-fabrication', 'sub_category_slug' => 'metal-works-fabrication', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Custom SS Grill Fabrication', 'slug' => 'custom-ss-grill-fabrication', 'sub_category_slug' => 'metal-works-fabrication', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Custom Railing Fabrication', 'slug' => 'custom-railing-fabrication', 'sub_category_slug' => 'metal-works-fabrication', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Custom Aluminium Window Fabrication', 'slug' => 'custom-aluminium-window-fabrication', 'sub_category_slug' => 'metal-works-fabrication', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
        ['name' => 'Steel Bracket Fabrication', 'slug' => 'steel-bracket-fabrication', 'sub_category_slug' => 'metal-works-fabrication', 'base_price' => 100.0, 'variants' => [aluminium_steel_inspection_variant()]],
    ],
];
