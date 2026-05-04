<?php

/**
 * Customer-safe support knowledge for WhatsApp AI (search_support_knowledge tool).
 * Edit this file to add FAQs, troubleshooting flows, and extra human-handoff phrases — no deploy needed for copy if config is cached appropriately in prod.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Optional provider onboarding URL (shown when relevant)
    |--------------------------------------------------------------------------
    */
    'provider_onboarding_form_url' => env('WHATSAPP_PROVIDER_ONBOARDING_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Extra phrases that trigger human handoff (merged with built-in list)
    |--------------------------------------------------------------------------
    |
    | @var list<string>
    */
    'human_handoff_extra_phrases' => [
        'supervisor',
        'complaint',
        'refund',
        'legal',
        'kis se baat',
    ],

    /*
    |--------------------------------------------------------------------------
    | Short tips appended when knowledge search has few matches
    |--------------------------------------------------------------------------
    |
    | @var list<string>
    */
    'general_tips' => [
        'If something is unsafe (gas smell, sparks, major water leak), advise the customer to stay safe and call emergency services if needed, then we can still log a booking for follow-up.',
        'Assistant replies must be English or Hinglish (Roman letters) only — never Kashmiri or other languages/scripts. One clear question at a time when collecting booking details.',
        'Use get_public_business_info for services, zones, and visiting-charge notes — never invent amounts.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default visiting charge text (when booking_setup has no visiting_charge)
    |--------------------------------------------------------------------------
    |
    | Shown in get_public_business_info so the assistant always has customer-safe
    | wording. Override per environment or set visiting_charge in admin instead.
    |
    */
    'default_visiting_charge_note' => env(
        'WHATSAPP_DEFAULT_VISITING_CHARGE_NOTE',
        'Visiting charges are ₹100. Final repair or service cost is confirmed after the technician inspects the job.'
    ),

    /*
    |--------------------------------------------------------------------------
    | Service area (WhatsApp AI booking guard — keyword hints)
    |--------------------------------------------------------------------------
    |
    | High-confidence zone match from the DB always allows the address.
    | Otherwise we block when an "outside" token appears and no "inside" token does.
    | Adjust lists for your operational area (Kashmir valley vs full J&K, etc.).
    |
    | @var list<string>
    */
    'service_area_inside_keywords' => [
        'jammu and kashmir',
        'jammu & kashmir',
        'j&k',
        'jk ut',
        'union territory of jammu',
        'kashmir',
        'srinagar',
        'anantnag',
        'kulgam',
        'pulwama',
        'shopian',
        'budgam',
        'ganderbal',
        'bandipora',
        'bandipore',
        'baramulla',
        'kupwara',
        'handwara',
        'sopore',
        'pampore',
        'awantipora',
        'magam',
        'jammu',
        'rajouri',
        'poonch',
        'kathua',
        'samba',
        'udhampur',
        'reasi',
        'ramban',
        'doda',
        'kishtwar',
        'leh',
        'ladakh',
    ],

    /*
    | Strong signals the customer is outside J&K / Kashmir operations.
    | Keep multi-word phrases where helpful (e.g. "new delhi").
    |
    | @var list<string>
    */
    'service_area_outside_keywords' => [
        'new delhi',
        'south delhi',
        'north delhi',
        'east delhi',
        'west delhi',
        'delhi ncr',
        'gurgaon',
        'gurugram',
        'noida',
        'greater noida',
        'faridabad',
        'ghaziabad',
        'mumbai',
        'bombay',
        'bangalore',
        'bengaluru',
        'chennai',
        'madras',
        'hyderabad',
        'secunderabad',
        'kolkata',
        'calcutta',
        'pune',
        'ahmedabad',
        'surat',
        'jaipur',
        'lucknow',
        'kanpur',
        'indore',
        'bhopal',
        'nagpur',
        'kochi',
        'cochin',
        'thiruvananthapuram',
        'trivandrum',
        'goa',
        'chandigarh',
        'dehradun',
        'haridwar',
        'rishikesh',
        'shimla',
        'manali',
        'amritsar',
        'ludhiana',
        'patna',
        'ranchi',
        'guwahati',
        'visakhapatnam',
        'vizag',
        'coimbatore',
        'madurai',
        'tiruchirappalli',
        'trichy',
        'telangana',
        'andhra pradesh',
        'karnataka',
        'tamil nadu',
        'kerala',
        'maharashtra',
        'gujarat',
        'rajasthan',
        'west bengal',
        'bihar',
        'punjab',
        'haryana',
        'uttar pradesh',
        'uttarakhand',
        'himachal pradesh',
        'assam',
        'odisha',
        'orissa',
        'pakistan',
        'lahore',
        'karachi',
    ],

    /*
    | Customer-safe sentence appended to get_public_business_info for the model.
    */
    'service_coverage_policy_note' => env(
        'WHATSAPP_SERVICE_COVERAGE_NOTE',
        'We currently operate only in Jammu & Kashmir (including Kashmir valley). Do not accept or submit bookings for service addresses outside this region.'
    ),

    /*
    |--------------------------------------------------------------------------
    | FAQs (keyword search over question + answer)
    |--------------------------------------------------------------------------
    |
    | @var list<array{q: string, a: string}>
    */
    'faqs' => [
        [
            'q' => 'How do I book a service?',
            'a' => 'Tell us which service you need, your full address with district/area, preferred date and time, and the name for the booking. We will save a request and our team will confirm it with you.',
        ],
        [
            'q' => 'Is my booking confirmed immediately?',
            'a' => 'After you confirm the details with us here, the request is sent to our team. Final confirmation happens when staff verifies and confirms — we always say this clearly so expectations are right.',
        ],
        [
            'q' => 'How much will it cost?',
            'a' => 'Visiting charges are ₹100 (as in our business information). The full job price is fixed after the technician visits and assesses the work — we do not guess amounts beyond what is in our settings.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Troubleshooting: keys are matched if the customer query contains them
    |--------------------------------------------------------------------------
    |
    | @var array<string, array{title: string, steps: list<string>}>
    */
    'troubleshooting' => [
        'ac' => [
            'title' => 'Air conditioner not cooling well',
            'steps' => [
                'Check if the remote is on cool mode and temperature is set below room temperature.',
                'Clean or replace the indoor filter if it is dusty — weak airflow often reduces cooling.',
                'Ensure outdoor unit has space around it and the fan is running.',
                'If ice forms on pipes or there is burning smell, turn off the AC and book a technician — do not force it to run.',
            ],
        ],
        'ac repair' => [
            'title' => 'Air conditioner not cooling well',
            'steps' => [
                'Check cool mode and temperature on the remote.',
                'Check indoor filter and outdoor unit airflow.',
                'Unusual noise, smell, or ice — stop using and book a visit.',
            ],
        ],
        'ac broken' => [
            'title' => 'AC not working or acting strange',
            'steps' => [
                'Confirm power to the indoor unit and that the remote has batteries and is on cool mode.',
                'If there is burning smell, smoke, or water near electrics — turn off and stop using; safety first.',
                'If it still fails after basic checks, we can book a technician — say if you want quick troubleshooting first or go straight to a visit.',
            ],
        ],
        'geyser' => [
            'title' => 'Geyser / water heater issues',
            'steps' => [
                'If there is water leaking near electrics, turn off power at the mains and stop using — safety first.',
                'Check thermostat setting and that the power supply to the geyser is on (MCB not tripped).',
                'No hot water after a long wait — note any error lights and book a technician.',
            ],
        ],
        'plumb' => [
            'title' => 'Leaks or water pressure',
            'steps' => [
                'For major flooding, shut the main water valve if you can find it safely.',
                'Small leaks — note where it drips and whether hot or cold line; it helps the plumber bring parts.',
                'Low pressure only in one tap often needs cleaning; whole-house low pressure may be supply or tank — book inspection.',
            ],
        ],
        'electric' => [
            'title' => 'Electrical tripping or sparks',
            'steps' => [
                'Sparks, burning smell, or someone shocked — turn off mains if safe and call emergency help if needed.',
                'If only one MCB trips repeatedly, unplug heavy appliances on that circuit before reset once.',
                'Do not open distribution boards yourself — book a certified electrician.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional extra public labels from business_config (customer-safe text only)
    |--------------------------------------------------------------------------
    |
    | @var list<array{key: string, settings_type: string, snapshot_key: string}>
    */
    'extra_public_business_config' => [
        // Example: ['key' => 'cancellation_policy', 'settings_type' => 'booking_setup', 'snapshot_key' => 'cancellation_note'],
    ],
];
