<?php

/**
 * Home Appliances catalog — cleaned live structure.
 *
 * Live main slug stays `home-appliance`.
 */

if (! function_exists('ha_variant')) {
    function ha_variant(string $key, string $title, float $price): array
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
        'name' => 'Home Appliances',
        'slug' => 'home-appliance',
        'description' => 'Professional home appliance installation, repair, and servicing across Kashmir by verified Panun Kaergar technicians.',
        'sort_order' => 2,
        'is_featured' => 1,
    ],
    'sub_categories' => [
        [
            'name' => 'Air Conditioners',
            'slug' => 'air-conditioners',
            'description' => 'AC installation, repair, servicing, uninstallation, and gas refill.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Battery & Inverters',
            'slug' => 'battery-inverters',
            'description' => 'Inverter and battery installation, repair, servicing, and uninstallation.',
            'sort_order' => 2,
        ],
        [
            'name' => 'CCTV',
            'slug' => 'cctv',
            'description' => 'CCTV camera installation and repair for homes and shops.',
            'sort_order' => 3,
        ],
        [
            'name' => 'Geysers',
            'slug' => 'geysers',
            'description' => 'Geyser installation, repair, cleaning, and uninstallation.',
            'sort_order' => 4,
        ],
        [
            'name' => 'LED / Smart TV',
            'slug' => 'led-smart-tv',
            'description' => 'TV wall mounting, repair, and uninstallation by screen size.',
            'sort_order' => 5,
        ],
        [
            'name' => 'Refrigerators',
            'slug' => 'refrigerators',
            'description' => 'Refrigerator installation, repair, and gas refill / leak fix.',
            'sort_order' => 6,
        ],
        [
            'name' => 'Washing Machine',
            'slug' => 'washing-machine',
            'description' => 'Washing machine installation, repair, servicing, and uninstallation.',
            'sort_order' => 7,
        ],
        [
            'name' => 'Water Purifier',
            'slug' => 'ro-purifier',
            'description' => 'RO installation and service / repair.',
            'sort_order' => 8,
        ],
        [
            'name' => 'Small Appliances',
            'slug' => 'induction-heaters',
            'description' => 'Repair and installation for fans, microwave, chimney, hob, cooler, heater, and more.',
            'sort_order' => 9,
        ],
    ],
    'deactivate_sub_slugs' => [],
    'services' => [
        // Air Conditioners
        [
            'name' => 'AC Installation',
            'slug' => 'ac-installation',
            'sub_category_slug' => 'air-conditioners',
            'base_price' => 1099.0,
            'variants' => [
                ha_variant('split-ac', 'Split AC', 1499.0),
                ha_variant('window-ac', 'Window AC', 1099.0),
            ],
        ],
        [
            'name' => 'AC Repair',
            'slug' => 'ac-repair',
            'sub_category_slug' => 'air-conditioners',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
                ha_variant('lessno-cooling', 'Less/no cooling', 299.0),
                ha_variant('power-issue', 'Power issue', 299.0),
                ha_variant('unwanted-noisesmell', 'Unwanted noise/smell', 499.0),
                ha_variant('water-leakage', 'Water leakage', 599.0),
            ],
        ],
        [
            'name' => 'AC Servicing',
            'slug' => 'ac-servicing',
            'sub_category_slug' => 'air-conditioners',
            'base_price' => 499.0,
            'variants' => [
                ha_variant('general-servicing', 'General Servicing', 499.0),
                ha_variant('foam-jet-servicing', 'Foam-jet Servicing', 1098.0),
            ],
        ],
        [
            'name' => 'AC Uninstallation',
            'slug' => 'ac-uninstallation',
            'sub_category_slug' => 'air-conditioners',
            'base_price' => 1099.0,
            'variants' => [
                ha_variant('split-ac', 'Split AC', 1499.0),
                ha_variant('window-ac', 'Window AC', 1099.0),
            ],
        ],
        [
            'name' => 'Gas refill & check-up',
            'slug' => 'gas-refill-check-up',
            'sub_category_slug' => 'air-conditioners',
            'base_price' => 2800.0,
            'variants' => [
                ha_variant('gas-refill-check-up', 'Gas Refill & Check-up', 2800.0),
            ],
        ],

        // Battery & Inverters
        [
            'name' => 'Inverter/battery Installation',
            'slug' => 'inverter-installation',
            'sub_category_slug' => 'battery-inverters',
            'base_price' => 485.0,
            'variants' => [
                ha_variant('single-battery', 'Single Battery', 485.0),
                ha_variant('double-battery', 'Double Battery', 575.0),
            ],
        ],
        [
            'name' => 'Inverter Repair',
            'slug' => 'inverter-repair',
            'sub_category_slug' => 'battery-inverters',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Inverter Servicing',
            'slug' => 'inverter-servicing',
            'sub_category_slug' => 'battery-inverters',
            'base_price' => 249.0,
            'variants' => [
                ha_variant('inverter-servicing', 'Inverter Servicing', 249.0),
            ],
        ],
        [
            'name' => 'Inverter Uninstallation',
            'slug' => 'inverter-uninstallation',
            'sub_category_slug' => 'battery-inverters',
            'base_price' => 499.0,
            'variants' => [
                ha_variant('inverter-uninstallation', 'Inverter Uninstallation', 499.0),
            ],
        ],

        // CCTV
        [
            'name' => 'CCTV Installation',
            'slug' => 'cctv-installation',
            'sub_category_slug' => 'cctv',
            'base_price' => 499.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 499.0),
            ],
        ],
        [
            'name' => 'CCTV Repair',
            'slug' => 'cctv-repair',
            'sub_category_slug' => 'cctv',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
            ],
        ],

        // Geysers
        [
            'name' => 'Geyser Installation',
            'slug' => 'geyser-installation',
            'sub_category_slug' => 'geysers',
            'base_price' => 399.0,
            'variants' => [
                ha_variant('storage-geyser', 'Storage Geyser', 399.0),
                ha_variant('instant-geyser', 'Instant Geyser', 349.0),
            ],
        ],
        [
            'name' => 'Geyser Repair',
            'slug' => 'geyser-repair',
            'sub_category_slug' => 'geysers',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
                ha_variant('no-heating', 'No heating', 299.0),
                ha_variant('leakage', 'Leakage', 299.0),
                ha_variant('thermostat-issue', 'Thermostat issue', 499.0),
                ha_variant('heating-element-issue', 'Heating element issue', 799.0),
                ha_variant('connection-leak', 'Connection leak', 99.0),
            ],
        ],
        [
            'name' => 'Geyser Cleaning',
            'slug' => 'geyser-cleaning',
            'sub_category_slug' => 'geysers',
            'base_price' => 399.0,
            'variants' => [
                ha_variant('geyser-cleaning', 'Geyser Cleaning', 399.0),
            ],
        ],
        [
            'name' => 'Geyser Uninstallation',
            'slug' => 'geyser-uninstallation',
            'sub_category_slug' => 'geysers',
            'base_price' => 249.0,
            'variants' => [
                ha_variant('geyser-uninstallation', 'Geyser Uninstallation', 249.0),
            ],
        ],

        // LED / Smart TV
        [
            'name' => 'TV Installation',
            'slug' => 'tv-installation',
            'sub_category_slug' => 'led-smart-tv',
            'base_price' => 399.0,
            'variants' => [
                ha_variant('upto-30-inch', 'Up to 30 inch', 399.0),
                ha_variant('32-to-43-inch', '32 to 43 inch', 599.0),
                ha_variant('46-to-55-inch', '46 to 55 inch', 749.0),
                ha_variant('56-to-65-inch', '56 to 65 inch', 949.0),
                ha_variant('over-75-inch', 'Over 75 inch', 1999.0),
            ],
        ],
        [
            'name' => 'TV Repair',
            'slug' => 'tv-repair',
            'sub_category_slug' => 'led-smart-tv',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
                ha_variant('display-issue', 'Display issue', 249.0),
                ha_variant('power-issue', 'Power issue', 249.0),
                ha_variant('sound-issue', 'Sound issue', 249.0),
            ],
        ],
        [
            'name' => 'TV Uninstallation',
            'slug' => 'tv-uninstallation',
            'sub_category_slug' => 'led-smart-tv',
            'base_price' => 349.0,
            'variants' => [
                ha_variant('upto-46-inch', 'Up to 46 inch', 349.0),
                ha_variant('46-to-55-inch', '46 to 55 inch', 399.0),
                ha_variant('over-65-inch', 'Over 65 inch', 599.0),
            ],
        ],

        // Refrigerators
        [
            'name' => 'Refrigerator Installation',
            'slug' => 'refrigerator-installation',
            'sub_category_slug' => 'refrigerators',
            'base_price' => 299.0,
            'variants' => [
                ha_variant('single-door', 'Single Door', 299.0),
                ha_variant('double-door', 'Double Door', 349.0),
                ha_variant('side-by-side', 'Side by Side', 449.0),
                ha_variant('french-door', 'French Door', 449.0),
            ],
        ],
        [
            'name' => 'Refrigerator Repair',
            'slug' => 'refrigerator-repair',
            'sub_category_slug' => 'refrigerators',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
                ha_variant('cooling-issue', 'Cooling issue', 299.0),
                ha_variant('leak', 'Leak', 299.0),
                ha_variant('noise', 'Noise', 299.0),
                ha_variant('thermostat-issue', 'Thermostat issue', 699.0),
                ha_variant('power-issue', 'Power issue', 299.0),
            ],
        ],
        [
            'name' => 'Fridge gas refill & leak fix',
            'slug' => 'gas-refill-leak-fix',
            'sub_category_slug' => 'refrigerators',
            'base_price' => 1499.0,
            'variants' => [
                ha_variant('gas-refill-leak-fix', 'Fridge Gas Refill & Leak Fix', 1499.0),
            ],
        ],

        // Washing Machine
        [
            'name' => 'Washing Machine Installation',
            'slug' => 'washing-machine-installation',
            'sub_category_slug' => 'washing-machine',
            'base_price' => 399.0,
            'variants' => [
                ha_variant('front-load', 'Front Load', 399.0),
                ha_variant('top-load', 'Top Load', 399.0),
                ha_variant('semi-automatic', 'Semi-automatic', 349.0),
            ],
        ],
        [
            'name' => 'Washing Machine Repair',
            'slug' => 'washing-machine-repair',
            'sub_category_slug' => 'washing-machine',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
                ha_variant('drain-issue', 'Drain issue', 299.0),
                ha_variant('not-spinning-washing', 'Not spinning/washing', 299.0),
                ha_variant('noise', 'Noise', 299.0),
                ha_variant('water-leakage', 'Water leakage', 299.0),
                ha_variant('power-issue', 'Power issue', 299.0),
                ha_variant('display-error', 'Display error', 299.0),
            ],
        ],
        [
            'name' => 'Washing Machine Servicing',
            'slug' => 'washing-machine-servicing',
            'sub_category_slug' => 'washing-machine',
            'base_price' => 1099.0,
            'variants' => [
                ha_variant('front-load', 'Front Load', 1099.0),
                ha_variant('top-load', 'Top Load', 1099.0),
                ha_variant('descaling', 'Descaling', 1099.0),
                ha_variant('machine-cover', 'Machine Cover', 1099.0),
            ],
        ],
        [
            'name' => 'Washing Machine Uninstallation',
            'slug' => 'washing-machine-uninstallation',
            'sub_category_slug' => 'washing-machine',
            'base_price' => 349.0,
            'variants' => [
                ha_variant('front-load', 'Front Load', 399.0),
                ha_variant('top-load', 'Top Load', 399.0),
                ha_variant('semi-automatic', 'Semi-automatic', 349.0),
            ],
        ],

        // Water Purifier
        [
            'name' => 'RO Installation',
            'slug' => 'ro-installation',
            'sub_category_slug' => 'ro-purifier',
            'base_price' => 499.0,
            'variants' => [
                ha_variant('ro-installation', 'RO Installation', 499.0),
            ],
        ],
        [
            'name' => 'RO Service / Repair',
            'slug' => 'ro-service',
            'sub_category_slug' => 'ro-purifier',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
                ha_variant('filter-replacement', 'Filter replacement', 399.0),
                ha_variant('low-water-output', 'Low water output', 299.0),
                ha_variant('leakage', 'Leakage', 299.0),
                ha_variant('no-power', 'No power', 249.0),
            ],
        ],

        // Small Appliances
        [
            'name' => 'Fan Repair',
            'slug' => 'fan-repair',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
                ha_variant('ceiling-fan', 'Ceiling fan', 249.0),
                ha_variant('pedestal-table-fan', 'Pedestal/Table fan', 249.0),
            ],
        ],
        [
            'name' => 'Microwave Repair',
            'slug' => 'microwave-repair',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
                ha_variant('no-heating', 'No heating', 299.0),
                ha_variant('display-issue', 'Display issue', 249.0),
                ha_variant('turntable-issue', 'Turntable issue', 249.0),
            ],
        ],
        [
            'name' => 'Induction Heater Repair',
            'slug' => 'induction-heater-repair',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Oven / OTG Repair',
            'slug' => 'oven-otg-repair',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Vacuum Cleaner Repair',
            'slug' => 'vacuum-cleaner-repair',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Mixer Grinder Repair',
            'slug' => 'mixer-grinder-repair',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Chimney Repair',
            'slug' => 'chimney-repair',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
                ha_variant('no-suction', 'No suction', 299.0),
                ha_variant('noise', 'Noise', 299.0),
                ha_variant('light-issue', 'Light issue', 249.0),
            ],
        ],
        [
            'name' => 'Chimney Installation',
            'slug' => 'chimney-installation',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 799.0,
            'variants' => [
                ha_variant('chimney-installation', 'Chimney Installation', 799.0),
            ],
        ],
        [
            'name' => 'Hob Repair',
            'slug' => 'hob-repair',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Hob Installation',
            'slug' => 'hob-installation',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 499.0,
            'variants' => [
                ha_variant('hob-installation', 'Hob Installation', 499.0),
            ],
        ],
        [
            'name' => 'Air Cooler Repair',
            'slug' => 'air-cooler-repair',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Room Heater Repair',
            'slug' => 'room-heater-repair',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Dishwasher Repair',
            'slug' => 'dishwasher-repair',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 199.0,
            'variants' => [
                ha_variant('book-site-inspection', 'Book Site Inspection', 199.0),
            ],
        ],
        [
            'name' => 'Dishwasher Installation',
            'slug' => 'dishwasher-installation',
            'sub_category_slug' => 'induction-heaters',
            'base_price' => 699.0,
            'variants' => [
                ha_variant('dishwasher-installation', 'Dishwasher Installation', 699.0),
            ],
        ],
    ],
];
