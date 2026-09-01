<?php

/**
 * Electrician Services catalog — Install / Repair / Inspection.
 *
 * Live main slug stays `electrical`.
 */

if (! function_exists('electrical_variant')) {
    function electrical_variant(string $key, string $title, float $price): array
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
        'name' => 'Electrician Services',
        'slug' => 'electrical',
        'description' => 'Professional electric installation, repair, and inspection across Kashmir by verified Panun Kaergar electricians.',
        'sort_order' => 2,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Electric Installation',
            'slug' => 'electric-installation',
            'description' => 'Light, fan, switch, wiring, point, MCB, earthing, and accessory installation by verified electricians.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Electric Repair',
            'slug' => 'electric-repair',
            'description' => 'Light, fan, switch, MCB, wiring, power, DB panel, and earthing repairs.',
            'sort_order' => 2,
        ],
        [
            'name' => 'Electric Inspection',
            'slug' => 'electric-inspection',
            'description' => 'Fault checks, safety checks, and pre-work wiring surveys before major electrical work.',
            'sort_order' => 3,
        ],
    ],
    'deactivate_sub_slugs' => [
        'installation-services',
        'repairing-services',
    ],
    'services' => [
        // Electric Installation
        [
            'name' => 'Electric Light Install',
            'slug' => 'electric-light-install',
            'sub_category_slug' => 'electric-installation',
            'base_price' => 49.0,
            'variants' => [
                electrical_variant('bulb', 'Bulb', 49.0),
                electrical_variant('tube-light', 'Tube Light', 99.0),
                electrical_variant('ceiling-light', 'Ceiling Light', 89.0),
                electrical_variant('hanging-light', 'Hanging Light', 199.0),
                electrical_variant('chandelier', 'Chandelier', 499.0),
                electrical_variant('decorative-light', 'Decorative Light', 149.0),
            ],
        ],
        [
            'name' => 'Electric Fan Install',
            'slug' => 'electric-fan-install',
            'sub_category_slug' => 'electric-installation',
            'base_price' => 149.0,
            'variants' => [
                electrical_variant('ceiling-fan', 'Ceiling Fan', 149.0),
                electrical_variant('exhaust-fan', 'Exhaust Fan', 149.0),
                electrical_variant('bldc-fan', 'BLDC Fan', 149.0),
            ],
        ],
        [
            'name' => 'Electric Switch Install',
            'slug' => 'electric-switch-install',
            'sub_category_slug' => 'electric-installation',
            'base_price' => 69.0,
            'variants' => [
                electrical_variant('switch', 'Switch', 69.0),
                electrical_variant('socket', 'Socket', 69.0),
                electrical_variant('fan-regulator', 'Fan Regulator', 79.0),
                electrical_variant('switchboard', 'Switchboard', 99.0),
                electrical_variant('ac-switchboard', 'AC Switchboard', 299.0),
            ],
        ],
        [
            'name' => 'Electric Wiring Install',
            'slug' => 'electric-wiring-install',
            'sub_category_slug' => 'electric-installation',
            'base_price' => 119.0,
            'variants' => [
                electrical_variant('internal-wiring', 'Internal Wiring', 199.0),
                electrical_variant('external-wiring', 'External Wiring', 119.0),
                electrical_variant('concealed-wiring', 'Concealed Wiring', 249.0),
                electrical_variant('underground-wiring', 'Underground Wiring', 299.0),
                electrical_variant('new-room-wiring', 'New Room Wiring', 399.0),
            ],
        ],
        [
            'name' => 'Electric Full House Wiring',
            'slug' => 'electric-full-house-wiring',
            'sub_category_slug' => 'electric-installation',
            'base_price' => 299.0,
            'variants' => [
                electrical_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],
        [
            'name' => 'Electric Point Install',
            'slug' => 'electric-point-install',
            'sub_category_slug' => 'electric-installation',
            'base_price' => 199.0,
            'variants' => [
                electrical_variant('geyser-point', 'Geyser Point', 249.0),
                electrical_variant('ac-point', 'AC Point', 299.0),
                electrical_variant('exhaust-chimney-point', 'Exhaust / Chimney Point', 199.0),
            ],
        ],
        [
            'name' => 'Electric MCB Install',
            'slug' => 'electric-mcb-install',
            'sub_category_slug' => 'electric-installation',
            'base_price' => 149.0,
            'variants' => [
                electrical_variant('mcb', 'MCB', 149.0),
                electrical_variant('db-panel', 'DB Panel', 399.0),
            ],
        ],
        [
            'name' => 'Electric Earthing Install',
            'slug' => 'electric-earthing-install',
            'sub_category_slug' => 'electric-installation',
            'base_price' => 499.0,
            'variants' => [
                electrical_variant('new-earthing', 'New Earthing', 499.0),
            ],
        ],
        [
            'name' => 'Electric Accessory Install',
            'slug' => 'electric-accessory-install',
            'sub_category_slug' => 'electric-installation',
            'base_price' => 149.0,
            'variants' => [
                electrical_variant('stabilizer', 'Stabilizer', 149.0),
                electrical_variant('submeter', 'Submeter', 249.0),
                electrical_variant('doorbell', 'Doorbell', 149.0),
            ],
        ],
        [
            'name' => 'Electric Inverter Install',
            'slug' => 'electric-inverter-install',
            'sub_category_slug' => 'electric-installation',
            'base_price' => 299.0,
            'variants' => [
                electrical_variant('inverter-ups-with-wiring', 'Inverter / UPS with Wiring', 299.0),
            ],
        ],
        [
            'name' => 'Electric Solar Inverter Install',
            'slug' => 'electric-solar-inverter-install',
            'sub_category_slug' => 'electric-installation',
            'base_price' => 299.0,
            'variants' => [
                electrical_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],
        [
            'name' => 'Electric Temporary Wiring',
            'slug' => 'electric-temporary-wiring',
            'sub_category_slug' => 'electric-installation',
            'base_price' => 499.0,
            'variants' => [
                electrical_variant('event-temporary-setup', 'Event / Temporary Setup', 499.0),
            ],
        ],
        [
            'name' => 'Electric ACP Sign Board Install',
            'slug' => 'electric-acp-sign-board-install',
            'sub_category_slug' => 'electric-installation',
            'base_price' => 299.0,
            'variants' => [
                electrical_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],

        // Electric Repair
        [
            'name' => 'Electric Light Repair',
            'slug' => 'electric-light-repair',
            'sub_category_slug' => 'electric-repair',
            'base_price' => 99.0,
            'variants' => [
                electrical_variant('bulb-tube-ceiling-light', 'Bulb / Tube / Ceiling Light', 99.0),
            ],
        ],
        [
            'name' => 'Electric Fan Repair',
            'slug' => 'electric-fan-repair',
            'sub_category_slug' => 'electric-repair',
            'base_price' => 149.0,
            'variants' => [
                electrical_variant('not-spinning', 'Not Spinning', 149.0),
                electrical_variant('slow-speed', 'Slow Speed', 149.0),
                electrical_variant('noisy', 'Noisy', 149.0),
            ],
        ],
        [
            'name' => 'Electric Switch Repair',
            'slug' => 'electric-switch-repair',
            'sub_category_slug' => 'electric-repair',
            'base_price' => 69.0,
            'variants' => [
                electrical_variant('switch-socket', 'Switch / Socket', 69.0),
                electrical_variant('switchboard', 'Switchboard', 99.0),
            ],
        ],
        [
            'name' => 'Electric MCB Repair',
            'slug' => 'electric-mcb-repair',
            'sub_category_slug' => 'electric-repair',
            'base_price' => 99.0,
            'variants' => [
                electrical_variant('mcb', 'MCB', 99.0),
                electrical_variant('fuse', 'Fuse', 99.0),
            ],
        ],
        [
            'name' => 'Electric Wiring Repair',
            'slug' => 'electric-wiring-repair',
            'sub_category_slug' => 'electric-repair',
            'base_price' => 199.0,
            'variants' => [
                electrical_variant('internal-wiring', 'Internal Wiring', 199.0),
                electrical_variant('external-wiring', 'External Wiring', 199.0),
                electrical_variant('concealed-wiring', 'Concealed Wiring', 249.0),
                electrical_variant('underground-wiring', 'Underground Wiring', 299.0),
                electrical_variant('burnt-damaged-wire', 'Burnt / Damaged Wire', 199.0),
            ],
        ],
        [
            'name' => 'Electric Power Repair',
            'slug' => 'electric-power-repair',
            'sub_category_slug' => 'electric-repair',
            'base_price' => 149.0,
            'variants' => [
                electrical_variant('short-circuit', 'Short Circuit', 149.0),
                electrical_variant('tripping-voltage-issue', 'Tripping / Voltage Issue', 149.0),
                electrical_variant('pcb-auto-cut', 'PCB / Auto-Cut', 199.0),
            ],
        ],
        [
            'name' => 'Electric DB Panel Repair',
            'slug' => 'electric-db-panel-repair',
            'sub_category_slug' => 'electric-repair',
            'base_price' => 249.0,
            'variants' => [
                electrical_variant('panel-fault-overheating', 'Panel Fault / Overheating', 249.0),
            ],
        ],
        [
            'name' => 'Electric Earthing Repair',
            'slug' => 'electric-earthing-repair',
            'sub_category_slug' => 'electric-repair',
            'base_price' => 299.0,
            'variants' => [
                electrical_variant('earthing-fix', 'Earthing Fix', 299.0),
            ],
        ],
        [
            'name' => 'Electric ACP Sign Board Repair',
            'slug' => 'electric-acp-sign-board-repair',
            'sub_category_slug' => 'electric-repair',
            'base_price' => 299.0,
            'variants' => [
                electrical_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],

        // Electric Inspection
        [
            'name' => 'Electric Site Check',
            'slug' => 'electric-site-check',
            'sub_category_slug' => 'electric-inspection',
            'base_price' => 149.0,
            'variants' => [
                electrical_variant('fault-check', 'Fault Check', 149.0),
                electrical_variant('unknown-problem', 'Unknown Problem', 149.0),
            ],
        ],
        [
            'name' => 'Electric Safety Check',
            'slug' => 'electric-safety-check',
            'sub_category_slug' => 'electric-inspection',
            'base_price' => 199.0,
            'variants' => [
                electrical_variant('full-home-safety-check', 'Full Home Safety Check', 299.0),
                electrical_variant('earthing-check', 'Earthing Check', 199.0),
                electrical_variant('mcb-db-panel-check', 'MCB / DB Panel Check', 199.0),
                electrical_variant('voltage-load-check', 'Voltage / Load Check', 199.0),
                electrical_variant('short-circuit-risk-check', 'Short Circuit Risk Check', 199.0),
            ],
        ],
        [
            'name' => 'Electric Pre-Work Check',
            'slug' => 'electric-pre-work-check',
            'sub_category_slug' => 'electric-inspection',
            'base_price' => 249.0,
            'variants' => [
                electrical_variant('before-renovation-wiring-check', 'Before Renovation Wiring Check', 249.0),
                electrical_variant('full-house-wiring-survey', 'Full House Wiring Survey', 299.0),
            ],
        ],
    ],
];
