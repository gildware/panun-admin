<?php

namespace Modules\AdminModule\Support;

class LeadQualificationTextGuide
{
    /**
     * @return array<int, array{title: string, intro?: string, steps: array<int, array{title: string, body?: string, items?: array<int, string>, branches?: array<int, array{label: string, steps: array<int, array{title: string, body?: string, items?: array<int, string>}>}>}>}>
     */
    public static function sections(): array
    {
        return [
            [
                'title' => '1. Lead arrives',
                'intro' => 'Every enquiry enters through one of these channels. Note where it came from in the lead record.',
                'steps' => [
                    [
                        'title' => 'Social media',
                        'items' => ['Facebook', 'Instagram'],
                    ],
                    [
                        'title' => 'Direct calls',
                        'items' => [
                            '8899881555',
                            '8899556555',
                            '889918155',
                            '9103076946',
                        ],
                    ],
                    [
                        'title' => 'In admin panel (auto-created lead)',
                        'items' => [
                            'AI Chat',
                            'Website booking — customer',
                            'Website booking — provider',
                            'Custom app request',
                        ],
                    ],
                    [
                        'title' => 'Create or confirm the lead',
                        'branches' => [
                            [
                                'label' => 'Phone / social (manual)',
                                'steps' => [[
                                    'title' => 'Collect all possible details from the customer and create a new lead in the admin panel.',
                                ]],
                            ],
                            [
                                'label' => 'Panel / app (automatic)',
                                'steps' => [[
                                    'title' => 'Lead is auto-created in the admin panel — verify details and continue qualification.',
                                ]],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => '2. Initial classification',
                'intro' => 'Based on what you already know, classify the lead before deeper qualification.',
                'steps' => [
                    [
                        'title' => 'What do you know about the lead?',
                        'branches' => [
                            [
                                'label' => 'Customer lead',
                                'steps' => [[
                                    'title' => 'User needs a home service — proceed to Section 4 (customer service path).',
                                ]],
                            ],
                            [
                                'label' => 'Provider lead',
                                'steps' => [[
                                    'title' => 'User wants to join as a service provider — proceed to Section 5 (provider onboarding).',
                                ]],
                            ],
                            [
                                'label' => 'Future customer lead',
                                'steps' => [[
                                    'title' => 'User has no immediate need or saved the number for future use.',
                                    'body' => 'Inform them about Panun Kaergar and try outbound sales — ask them to refer Panun Kaergar if they or anyone they know needs a service later.',
                                ]],
                            ],
                            [
                                'label' => 'Invalid lead',
                                'steps' => [[
                                    'title' => 'User asks for a service you do not provide, or is in a non-serviceable area.',
                                    'body' => 'Record exactly what service or location they asked for. If there was no response, note that too.',
                                ]],
                            ],
                            [
                                'label' => 'Unknown lead',
                                'steps' => [[
                                    'title' => 'Customer only sent a vague message (e.g. “just called us”) with not enough information to classify.',
                                    'body' => 'Continue to Section 3 — call the user and find out what they need.',
                                ]],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => '3. Unknown lead — outbound call',
                'steps' => [
                    [
                        'title' => 'Call the user and collect details about what they want from Panun Kaergar.',
                    ],
                    [
                        'title' => 'Did the user pick up?',
                        'branches' => [
                            [
                                'label' => 'User did not pick up',
                                'steps' => [
                                    [
                                        'title' => 'Send WhatsApp message',
                                        'body' => '“We tried calling you regarding your enquiry with Panun Kaergar (Home service provider of Kashmir). If you need any help or any service feel free to call us.”',
                                    ],
                                    [
                                        'title' => 'Update the lead',
                                        'body' => 'Add initial remarks and set a follow-up date for the next day.',
                                    ],
                                ],
                            ],
                            [
                                'label' => 'User picked up',
                                'steps' => [[
                                    'title' => 'Run the lead qualifier (Section 3a) to decide customer vs provider vs future vs invalid.',
                                ]],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => '3a. Lead qualifier (after speaking to the user)',
                'steps' => [
                    [
                        'title' => 'Lead qualifier — what does the user want?',
                        'branches' => [
                            [
                                'label' => 'User needs service',
                                'steps' => [[
                                    'title' => 'Mark as customer lead → Section 4.',
                                ]],
                            ],
                            [
                                'label' => 'User wants to join as service provider',
                                'steps' => [[
                                    'title' => 'Mark as provider lead → Section 5.',
                                ]],
                            ],
                            [
                                'label' => 'Future / saved number only',
                                'steps' => [[
                                    'title' => 'Mark as future customer lead — inform about Panun Kaergar and ask for referrals when needed.',
                                ]],
                            ],
                            [
                                'label' => 'Invalid',
                                'steps' => [[
                                    'title' => 'Mark as invalid lead — document why (wrong service, wrong area, etc.).',
                                ]],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => '4. Customer lead — service booking',
                'steps' => [
                    [
                        'title' => 'Call the customer and get full service details (what, where, when). Confirm with them.',
                    ],
                    [
                        'title' => 'Does the customer want to talk to the provider first?',
                        'branches' => [
                            [
                                'label' => 'No — book service directly',
                                'steps' => [
                                    [
                                        'title' => 'Send WhatsApp confirmation',
                                        'body' => '“As per our discussion over call you need this service — Service details, address, date/time. We will look for a partner available to do the work for that time.”',
                                    ],
                                    [
                                        'title' => 'Update lead as customer lead with all details and set follow-up as needed.',
                                    ],
                                    [
                                        'title' => 'Post in the provider group: check who is available for that service.',
                                    ],
                                    [
                                        'title' => 'Did anyone reply within 10 minutes?',
                                        'branches' => [
                                            [
                                                'label' => 'Yes',
                                                'steps' => [[
                                                    'title' => 'Any provider replied and is ready for service → take ₹100 booking confirmation, create booking, notify customer and provider, set follow-ups.',
                                                ]],
                                            ],
                                            [
                                                'label' => 'No',
                                                'steps' => [
                                                    ['title' => 'Call all nearby providers — check availability for the customer’s date and next availability.'],
                                                    [
                                                        'title' => 'Got alternate availability?',
                                                        'branches' => [
                                                            [
                                                                'label' => 'Providers available at different times',
                                                                'steps' => [
                                                                    ['title' => 'Call customer — share available slots; if OK, schedule.'],
                                                                    ['title' => 'Send WhatsApp summarizing the call.'],
                                                                    [
                                                                        'title' => 'Customer OK to reschedule?',
                                                                        'branches' => [
                                                                            ['label' => 'Yes', 'steps' => [['title' => 'Proceed to booking confirmation (₹100, create booking, messages, follow-ups).']]],
                                                                            ['label' => 'No', 'steps' => [['title' => 'Set follow-up date and check again later.']]],
                                                                        ],
                                                                    ],
                                                                ],
                                                            ],
                                                            [
                                                                'label' => 'No one replied or is unavailable',
                                                                'steps' => [
                                                                    ['title' => 'Tell customer providers are busy — ask for more time; you will update them.'],
                                                                    ['title' => 'Send WhatsApp with what was discussed.'],
                                                                    ['title' => 'Set follow-up date with customer (and try other dates if they agree).'],
                                                                    ['title' => 'Optionally check once more if any provider can work on the customer’s requested dates.'],
                                                                    ['title' => 'If still no match → cancel the lead and close chat.'],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'label' => 'Yes — provider discussion first',
                                'steps' => [
                                    [
                                        'title' => 'Send WhatsApp',
                                        'body' => '“As per our discussion over call you need this service — Service details, address, date/time. We will look for a partner available and connect you with the service provider.”',
                                    ],
                                    ['title' => 'Update lead as customer lead with remarks and follow-up date.'],
                                    [
                                        'title' => 'Message provider group — who is available for a discussion with the customer? Ask for next availability.',
                                    ],
                                    [
                                        'title' => 'Did anyone reply within 10 minutes?',
                                        'branches' => [
                                            [
                                                'label' => 'Yes — provider ready for discussion',
                                                'steps' => [
                                                    ['title' => 'Before the conference call, tell the customer: if pricing or quality is an issue, inform Panun Kaergar — we have multiple providers and can find a better match.'],
                                                    ['title' => 'Set up a conference call with provider and customer.'],
                                                    [
                                                        'title' => 'Does the customer want the service?',
                                                        'branches' => [
                                                            [
                                                                'label' => 'Yes',
                                                                'steps' => [[
                                                                    'title' => 'Take ₹100 booking confirmation, create the booking, send messages to customer and provider, and set follow-ups.',
                                                                ]],
                                                            ],
                                                            [
                                                                'label' => 'No — wants to discuss with someone else / will inform later',
                                                                'steps' => [
                                                                    ['title' => 'Understand their concern; try to convince and close the booking.'],
                                                                    ['title' => 'Set follow-up date and check again.'],
                                                                ],
                                                            ],
                                                            [
                                                                'label' => 'Customer denies service entirely',
                                                                'steps' => [['title' => 'Cancel the lead and close its chat.']],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                'label' => 'No reply in 10 minutes',
                                                'steps' => [
                                                    ['title' => 'Call nearby providers for availability and next slots.'],
                                                    [
                                                        'title' => 'Follow the same availability / reschedule / follow-up / cancel branches as in the direct-booking path above.',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => '5. Provider lead — onboarding',
                'steps' => [
                    [
                        'title' => 'Ask if they are available for a brief onboarding call.',
                        'branches' => [
                            [
                                'label' => 'Available now',
                                'steps' => [[
                                    'title' => 'Step 1 — Brief call',
                                    'body' => 'Explain Panun Kaergar and commission. Get their service type and service area. Ask when they can submit documents.',
                                ]],
                            ],
                            [
                                'label' => 'Available later',
                                'steps' => [[
                                    'title' => 'Schedule a follow-up date/time and call the provider then (max 3 follow-ups; after that cancel the lead).',
                                ]],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Step 2 — Agreement and documents',
                        'body' => 'Send agreement and document list. Ask for a date/time by when they will submit the form and documents. Schedule follow-up if needed (max 3 follow-ups).',
                    ],
                    [
                        'title' => 'Documents shared?',
                        'branches' => [
                            [
                                'label' => 'Yes',
                                'steps' => [[
                                    'title' => 'Step 3 — Final call',
                                    'body' => 'Explain how work, WhatsApp groups, and day-to-day process operate.',
                                ]],
                            ],
                            [
                                'label' => 'No',
                                'steps' => [[
                                    'title' => 'Schedule another follow-up and call again (max 3 total follow-ups, then cancel lead).',
                                ]],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Step 4 — Add to panel',
                        'body' => 'Add the provider in the admin panel and add them to the relevant WhatsApp groups.',
                    ],
                ],
            ],
            [
                'title' => '6. Lead outcomes (reference)',
                'intro' => 'Every path should end in one of these states with proper remarks and follow-up dates where applicable.',
                'steps' => [
                    ['title' => 'Customer lead — active service enquiry or booking in progress.'],
                    ['title' => 'Provider lead — onboarding in progress or completed.'],
                    ['title' => 'Future customer lead — nurture for later; referral ask done.'],
                    ['title' => 'Invalid lead — closed with clear reason documented.'],
                    ['title' => 'Unknown lead — resolved after outbound call or waiting on follow-up.'],
                    ['title' => 'Cancelled lead — after 3 failed follow-ups, customer decline, or no provider match.'],
                ],
            ],
        ];
    }
}
