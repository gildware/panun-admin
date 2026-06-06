<?php

/**
 * Map customer problem descriptions → catalog search queries we actually offer.
 */
return [

    'trades' => [
        [
            'id' => 'plumbing',
            'label' => 'plumbing',
            'catalog_queries' => ['plumbing', 'plumber'],
            'signals' => [
                'plumb', 'pipe', 'tap', 'faucet', 'leak', 'leaking', 'drip', 'drain',
                'toilet', 'bathroom', 'blockage', 'clog', 'water tank', 'nal', 'tanki', 'sink',
                'sewer', 'motor', 'borewell',
            ],
        ],
        [
            'id' => 'electrical',
            'label' => 'electrical work',
            'catalog_queries' => ['electrician', 'electrical'],
            'signals' => [
                'electric', 'wiring', 'wire', 'socket', 'switch', 'mcb', 'breaker', 'short circuit',
                'power cut', 'fuse', 'light', 'fan', 'meter', 'voltage', 'spark',
            ],
        ],
        [
            'id' => 'ac',
            'label' => 'AC repair',
            'catalog_queries' => ['AC repair', 'air condition'],
            'signals' => [
                'ac', 'a/c', 'air condition', 'cooling', 'compressor', 'gas refill', 'split',
            ],
        ],
        [
            'id' => 'appliance',
            'label' => 'appliance repair',
            'catalog_queries' => ['refrigerator', 'washing machine', 'geyser repair', 'TV repair', 'RO'],
            'signals' => [
                'fridge', 'refrigerator', 'washing', 'ro water', 'tv', 'microwave', 'oven', 'appliance',
            ],
        ],
        [
            'id' => 'cleaning',
            'label' => 'home cleaning',
            'catalog_queries' => ['cleaning', 'home cleaning', 'deep cleaning'],
            'signals' => [
                'clean', 'cleaning', 'safai', 'maid', 'housekeeping', 'dust',
            ],
        ],
        [
            'id' => 'carpentry',
            'label' => 'carpentry',
            'catalog_queries' => ['carpenter', 'carpentry'],
            'signals' => [
                'carpent', 'wood', 'furniture', 'door', 'cabinet', 'mistri', 'mistry',
            ],
        ],
        [
            'id' => 'painting',
            'label' => 'painting',
            'catalog_queries' => ['painter', 'painting'],
            'signals' => [
                'paint', 'painting', 'whitewash',
            ],
        ],
        [
            'id' => 'pest',
            'label' => 'pest control',
            'catalog_queries' => ['pest', 'pest control'],
            'signals' => [
                'pest', 'cockroach', 'rat', 'termite', 'mosquito',
            ],
        ],
    ],

    'unsupported' => [
        [
            'label' => 'laptop or computer repair',
            'signals' => ['laptop', 'computer', 'pc ', 'macbook', 'desktop', 'windows install', 'software', 'printer'],
        ],
        [
            'label' => 'mobile phone repair',
            'signals' => ['iphone', 'android phone', 'mobile screen', 'phone repair', 'smartphone'],
        ],
        [
            'label' => 'car or vehicle repair',
            'signals' => ['car repair', 'bike repair', 'vehicle', 'automobile', 'mechanic car'],
        ],
        [
            'label' => 'TV or electronics repair',
            'signals' => ['tv repair', 'television', 'speaker', 'home theatre'],
        ],
        [
            'label' => 'medical or health services',
            'signals' => ['doctor', 'hospital', 'medicine', 'nurse', 'ambulance'],
        ],
    ],

    'fallback_catalog_queries' => ['plumbing', 'electrician', 'AC repair', 'cleaning'],

];
