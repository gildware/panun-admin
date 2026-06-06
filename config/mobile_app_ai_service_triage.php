<?php

/**
 * Pre-booking triage — short questions; tips only after the issue is clear.
 */
return [

    'default' => [
        'ask' => 'What exactly is the problem?',
        'clarify' => 'Can you describe the issue in a few words? (what happened, when)',
        'tips' => [
            'Check power/switches first.',
            'Gas smell or sparks → stay safe and switch off mains.',
        ],
    ],

    'ac' => [
        'ask' => 'What\'s wrong with the AC? (not cooling / leak / noise / won\'t start)',
        'clarify' => 'Is it not cooling, leaking water, noisy, or not switching on?',
        'tips' => [
            'Try **Cool** mode, lower temp, wait 5 min.',
            'Water inside → turn AC off; book if it continues.',
        ],
    ],

    'plumb' => [
        'ask' => 'What seems to be the problem? (leak, blocked drain, geyser, low pressure)',
        'clarify' => 'Where is the water coming from — the tap, a pipe under the sink, or somewhere else?',
        'tips' => [
            'Turn off the tap or the valve under the sink to slow the leak.',
            'Check if the drip is from the tap head or the pipe connection — don\'t force fittings if you\'re unsure.',
            'Place a towel or bucket under the drip and avoid using that tap until a plumber checks it.',
            'If water is spreading quickly, turn off the main water valve for your home if you can reach it safely.',
        ],
    ],

    'electric' => [
        'ask' => 'What electrical issue? (no power / breaker trips / switch)',
        'clarify' => 'Which room or device — and does the breaker trip?',
        'tips' => [
            'Reset MCB once; unplug loads if it trips again.',
            'Sparks or burning smell → switch off mains.',
        ],
    ],

    'appliance' => [
        'ask' => 'Which appliance and what happened?',
        'clarify' => 'Which appliance (fridge, RO, TV…) and what symptom?',
        'tips' => [
            'Check plug and power switch.',
            'Unplug if smoke, spark, or burning smell.',
        ],
    ],

];
