<?php

/**
 * Plumbing Services catalog — Install / Repair / Inspection.
 *
 * Live main slug stays `plumbing`.
 */

if (! function_exists('plumbing_variant')) {
    function plumbing_variant(string $key, string $title, float $price): array
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
        'name' => 'Plumbing Services',
        'slug' => 'plumbing',
        'description' => 'Professional plumbing installation, repair, and inspection across Kashmir by verified Panun Kaergar plumbers.',
        'sort_order' => 1,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Plumbing Installation',
            'slug' => 'plumbing-installation',
            'description' => 'Tap, shower, basin, toilet, sink, pipe, drain, motor, tank, and full bathroom/kitchen plumbing installation.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Plumbing Repair',
            'slug' => 'plumbing-repair',
            'description' => 'Tap, shower, basin, toilet, drain, pipe, leak, pressure, motor, and tank repairs.',
            'sort_order' => 2,
        ],
        [
            'name' => 'Plumbing Inspection',
            'slug' => 'plumbing-inspection',
            'description' => 'Leak checks, blockage checks, safety checks, and pre-work plumbing surveys.',
            'sort_order' => 3,
        ],
    ],
    'deactivate_sub_slugs' => [
        'plumbing-fixtures',
        'plumbing-installs',
    ],
    'services' => [
        // Plumbing Installation
        [
            'name' => 'Plumbing Tap Install',
            'slug' => 'plumbing-tap-install',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 79.0,
            'variants' => [
                plumbing_variant('regular-tap', 'Regular Tap', 99.0),
                plumbing_variant('mixer-tap', 'Mixer Tap', 149.0),
                plumbing_variant('swan-neck-tap', 'Swan Neck Tap', 149.0),
                plumbing_variant('pillar-cock', 'Pillar Cock', 99.0),
                plumbing_variant('angle-valve', 'Angle Valve', 79.0),
            ],
        ],
        [
            'name' => 'Plumbing Shower Install',
            'slug' => 'plumbing-shower-install',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 99.0,
            'variants' => [
                plumbing_variant('shower-head', 'Shower Head', 99.0),
                plumbing_variant('hand-shower-jet-spray', 'Hand Shower / Jet Spray', 99.0),
                plumbing_variant('shower-mixer', 'Shower Mixer', 199.0),
            ],
        ],
        [
            'name' => 'Plumbing Basin Install',
            'slug' => 'plumbing-basin-install',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 99.0,
            'variants' => [
                plumbing_variant('wash-basin', 'Wash Basin', 249.0),
                plumbing_variant('pedestal-basin', 'Pedestal Basin', 299.0),
                plumbing_variant('bottle-trap', 'Bottle Trap', 99.0),
            ],
        ],
        [
            'name' => 'Plumbing Toilet Install',
            'slug' => 'plumbing-toilet-install',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 249.0,
            'variants' => [
                plumbing_variant('indian-toilet', 'Indian Toilet', 399.0),
                plumbing_variant('western-toilet-floor', 'Western Toilet (Floor)', 449.0),
                plumbing_variant('western-toilet-wall', 'Western Toilet (Wall)', 499.0),
                plumbing_variant('flush-tank-external', 'Flush Tank (External)', 249.0),
                plumbing_variant('flush-tank-concealed', 'Flush Tank (Concealed)', 349.0),
            ],
        ],
        [
            'name' => 'Plumbing Sink Install',
            'slug' => 'plumbing-sink-install',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 79.0,
            'variants' => [
                plumbing_variant('kitchen-sink', 'Kitchen Sink', 299.0),
                plumbing_variant('connection-hose', 'Connection Hose', 79.0),
            ],
        ],
        [
            'name' => 'Plumbing Pipe Install',
            'slug' => 'plumbing-pipe-install',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 149.0,
            'variants' => [
                plumbing_variant('pvc-cpvc-pipe', 'PVC / CPVC Pipe', 149.0),
                plumbing_variant('gi-metal-pipe', 'GI / Metal Pipe', 199.0),
                plumbing_variant('concealed-pipe', 'Concealed Pipe', 249.0),
                plumbing_variant('external-pipe', 'External Pipe', 149.0),
            ],
        ],
        [
            'name' => 'Plumbing Drain Install',
            'slug' => 'plumbing-drain-install',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 99.0,
            'variants' => [
                plumbing_variant('floor-drain-nahani-trap', 'Floor Drain / Nahani Trap', 149.0),
                plumbing_variant('drain-cover', 'Drain Cover', 99.0),
                plumbing_variant('waste-pipe', 'Waste Pipe', 119.0),
            ],
        ],
        [
            'name' => 'Plumbing Motor Install',
            'slug' => 'plumbing-motor-install',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 299.0,
            'variants' => [
                plumbing_variant('water-motor-pump', 'Water Motor / Pump', 299.0),
                plumbing_variant('motor-with-piping', 'Motor with Piping', 399.0),
            ],
        ],
        [
            'name' => 'Plumbing Tank Install',
            'slug' => 'plumbing-tank-install',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 99.0,
            'variants' => [
                plumbing_variant('overhead-tank-connection', 'Overhead Tank Connection', 249.0),
                plumbing_variant('float-valve-ball-cock', 'Float Valve / Ball Cock', 149.0),
                plumbing_variant('tank-cover-fit', 'Tank Cover Fit', 99.0),
            ],
        ],
        [
            'name' => 'Plumbing Geyser Connection',
            'slug' => 'plumbing-geyser-connection',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 199.0,
            'variants' => [
                plumbing_variant('hot-cold-water-connection', 'Hot / Cold Water Connection', 199.0),
            ],
        ],
        [
            'name' => 'Plumbing Accessory Install',
            'slug' => 'plumbing-accessory-install',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 99.0,
            'variants' => [
                plumbing_variant('shut-off-valve', 'Shut-off Valve', 99.0),
                plumbing_variant('non-return-valve', 'Non-Return Valve', 99.0),
                plumbing_variant('pressure-pump-connection', 'Pressure Pump Connection', 249.0),
            ],
        ],
        [
            'name' => 'Plumbing Full Bathroom Plumbing',
            'slug' => 'plumbing-full-bathroom-plumbing',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 299.0,
            'variants' => [
                plumbing_variant('book-inspection', 'Book Inspection', 299.0),
                plumbing_variant('renovation-new-setup', 'Renovation / New Setup', 399.0),
            ],
        ],
        [
            'name' => 'Plumbing Full Kitchen Plumbing',
            'slug' => 'plumbing-full-kitchen-plumbing',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 299.0,
            'variants' => [
                plumbing_variant('book-inspection', 'Book Inspection', 299.0),
                plumbing_variant('renovation-new-setup', 'Renovation / New Setup', 349.0),
            ],
        ],
        [
            'name' => 'Plumbing Full House Plumbing',
            'slug' => 'plumbing-full-house-plumbing',
            'sub_category_slug' => 'plumbing-installation',
            'base_price' => 299.0,
            'variants' => [
                plumbing_variant('book-inspection', 'Book Inspection', 299.0),
            ],
        ],

        // Plumbing Repair
        [
            'name' => 'Plumbing Tap Repair',
            'slug' => 'plumbing-tap-repair',
            'sub_category_slug' => 'plumbing-repair',
            'base_price' => 99.0,
            'variants' => [
                plumbing_variant('regular-tap', 'Regular Tap', 99.0),
                plumbing_variant('mixer-tap', 'Mixer Tap', 149.0),
                plumbing_variant('shower-mixer', 'Shower Mixer', 199.0),
                plumbing_variant('leaking-dripping', 'Leaking / Dripping', 99.0),
            ],
        ],
        [
            'name' => 'Plumbing Shower Repair',
            'slug' => 'plumbing-shower-repair',
            'sub_category_slug' => 'plumbing-repair',
            'base_price' => 99.0,
            'variants' => [
                plumbing_variant('shower-head-arm', 'Shower Head / Arm', 99.0),
                plumbing_variant('jet-spray-bidet', 'Jet Spray / Bidet', 99.0),
            ],
        ],
        [
            'name' => 'Plumbing Basin Repair',
            'slug' => 'plumbing-basin-repair',
            'sub_category_slug' => 'plumbing-repair',
            'base_price' => 99.0,
            'variants' => [
                plumbing_variant('leakage-bottle-trap', 'Leakage (Bottle Trap)', 149.0),
                plumbing_variant('leakage-waste-pipe', 'Leakage (Waste Pipe)', 99.0),
                plumbing_variant('blockage', 'Blockage', 149.0),
            ],
        ],
        [
            'name' => 'Plumbing Toilet Repair',
            'slug' => 'plumbing-toilet-repair',
            'sub_category_slug' => 'plumbing-repair',
            'base_price' => 149.0,
            'variants' => [
                plumbing_variant('flush-tank-external-pvc', 'Flush Tank (External PVC)', 149.0),
                plumbing_variant('flush-tank-external-ceramic', 'Flush Tank (External Ceramic)', 199.0),
                plumbing_variant('flush-tank-concealed', 'Flush Tank (Concealed)', 219.0),
                plumbing_variant('running-flush-weak-flush', 'Running Flush / Weak Flush', 149.0),
                plumbing_variant('seat-cisterna-fix', 'Seat / Cisterna Fix', 149.0),
            ],
        ],
        [
            'name' => 'Plumbing Drain Repair',
            'slug' => 'plumbing-drain-repair',
            'sub_category_slug' => 'plumbing-repair',
            'base_price' => 149.0,
            'variants' => [
                plumbing_variant('kitchen-sink-blockage', 'Kitchen Sink Blockage', 149.0),
                plumbing_variant('wash-basin-blockage', 'Wash Basin Blockage', 149.0),
                plumbing_variant('bathroom-floor-drain', 'Bathroom / Floor Drain', 149.0),
                plumbing_variant('toilet-pot-blockage', 'Toilet Pot Blockage', 299.0),
                plumbing_variant('bad-smell-trap-issue', 'Bad Smell / Trap Issue', 149.0),
            ],
        ],
        [
            'name' => 'Plumbing Pipe Repair',
            'slug' => 'plumbing-pipe-repair',
            'sub_category_slug' => 'plumbing-repair',
            'base_price' => 149.0,
            'variants' => [
                plumbing_variant('leak-joint-fix', 'Leak / Joint Fix', 149.0),
                plumbing_variant('burst-damaged-pipe', 'Burst / Damaged Pipe', 199.0),
                plumbing_variant('concealed-pipe-leak', 'Concealed Pipe Leak', 249.0),
                plumbing_variant('external-pipe-leak', 'External Pipe Leak', 149.0),
            ],
        ],
        [
            'name' => 'Plumbing Leak Repair',
            'slug' => 'plumbing-leak-repair',
            'sub_category_slug' => 'plumbing-repair',
            'base_price' => 99.0,
            'variants' => [
                plumbing_variant('visible-leak', 'Visible Leak', 149.0),
                plumbing_variant('hidden-wall-seepage', 'Hidden / Wall Seepage', 249.0),
                plumbing_variant('shut-off-valve-leak', 'Shut-off Valve Leak', 99.0),
            ],
        ],
        [
            'name' => 'Plumbing Pressure Repair',
            'slug' => 'plumbing-pressure-repair',
            'sub_category_slug' => 'plumbing-repair',
            'base_price' => 149.0,
            'variants' => [
                plumbing_variant('low-water-pressure', 'Low Water Pressure', 149.0),
                plumbing_variant('no-water-airlock', 'No Water / Airlock', 149.0),
            ],
        ],
        [
            'name' => 'Plumbing Motor Repair',
            'slug' => 'plumbing-motor-repair',
            'sub_category_slug' => 'plumbing-repair',
            'base_price' => 99.0,
            'variants' => [
                plumbing_variant('not-starting', 'Not Starting', 199.0),
                plumbing_variant('low-pressure-weak-flow', 'Low Pressure / Weak Flow', 199.0),
                plumbing_variant('noise-overheating', 'Noise / Overheating', 199.0),
                plumbing_variant('air-cavity-removal', 'Air Cavity Removal', 99.0),
            ],
        ],
        [
            'name' => 'Plumbing Tank Repair',
            'slug' => 'plumbing-tank-repair',
            'sub_category_slug' => 'plumbing-repair',
            'base_price' => 99.0,
            'variants' => [
                plumbing_variant('overflow-float-valve', 'Overflow / Float Valve', 149.0),
                plumbing_variant('connection-leakage', 'Connection Leakage', 99.0),
                plumbing_variant('cover-change', 'Cover Change', 99.0),
            ],
        ],
        [
            'name' => 'Plumbing Geyser Connection Repair',
            'slug' => 'plumbing-geyser-connection-repair',
            'sub_category_slug' => 'plumbing-repair',
            'base_price' => 149.0,
            'variants' => [
                plumbing_variant('inlet-outlet-leak', 'Inlet / Outlet Leak', 149.0),
            ],
        ],

        // Plumbing Inspection
        [
            'name' => 'Plumbing Site Check',
            'slug' => 'plumbing-site-check',
            'sub_category_slug' => 'plumbing-inspection',
            'base_price' => 149.0,
            'variants' => [
                plumbing_variant('leak-check', 'Leak Check', 149.0),
                plumbing_variant('blockage-check', 'Blockage Check', 149.0),
                plumbing_variant('unknown-problem', 'Unknown Problem', 149.0),
            ],
        ],
        [
            'name' => 'Plumbing Safety Check',
            'slug' => 'plumbing-safety-check',
            'sub_category_slug' => 'plumbing-inspection',
            'base_price' => 199.0,
            'variants' => [
                plumbing_variant('full-home-plumbing-check', 'Full Home Plumbing Check', 299.0),
                plumbing_variant('pipe-joint-check', 'Pipe / Joint Check', 199.0),
                plumbing_variant('motor-tank-check', 'Motor / Tank Check', 199.0),
                plumbing_variant('drain-smell-backflow-check', 'Drain Smell / Backflow Check', 199.0),
                plumbing_variant('winter-freeze-risk-check', 'Winter Freeze Risk Check', 199.0),
            ],
        ],
        [
            'name' => 'Plumbing Pre-Work Check',
            'slug' => 'plumbing-pre-work-check',
            'sub_category_slug' => 'plumbing-inspection',
            'base_price' => 249.0,
            'variants' => [
                plumbing_variant('before-renovation-plumbing-check', 'Before Renovation Plumbing Check', 249.0),
                plumbing_variant('full-house-plumbing-survey', 'Full House Plumbing Survey', 299.0),
            ],
        ],
    ],
];
