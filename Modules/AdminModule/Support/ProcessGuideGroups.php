<?php

namespace Modules\AdminModule\Support;

class ProcessGuideGroups
{
    /**
     * Flowchart section groups — rendered as clickable boxes on the board.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            [
                'id' => 'lead-sources',
                'step' => 1,
                'title' => 'Lead Sources',
                'subtitle' => 'Where enquiries enter the pipeline',
                'matchKinds' => ['channel'],
                'matchTextContains' => [
                    'LEAD AUTO CREATED IN ADMIN PANEL',
                    'Collect all Possible detail from customer',
                ],
                'intro' => 'Every enquiry enters through one of these channels. Note where it came from in the lead record before you qualify.',
                'sections' => [
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
                ],
                'notes' => [
                    'Phone / social leads: collect all possible details from the customer and create a new lead manually.',
                    'Panel / app leads: lead is auto-created — verify details and continue qualification.',
                ],
            ],
        ];
    }
}
