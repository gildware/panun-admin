<?php

namespace Modules\AdminModule\Support;

class PanunKaergarIntroTextGuide
{
    /**
     * @return array<int, array{title: string, intro?: string, steps: array<int, array<string, mixed>}>}
     */
    public static function sections(): array
    {
        return [
            [
                'title' => 'Welcome',
                'intro' => 'Your Home. Our Responsibility.',
                'steps' => [
                    ['title' => 'Who we are', 'body' => 'Kashmir’s full-stack home-services company. You tell us what needs to be done. We handle booking, pricing, coordination, payment, quality, and support.'],
                ],
            ],
            [
                'title' => 'Who we are',
                'intro' => 'A home-services company that stays responsible for the job.',
                'steps' => [
                    ['title' => 'One company for your home', 'body' => 'You hire Panun Kaergar, not a list of private numbers.'],
                    ['title' => 'We manage the experience', 'body' => 'Booking, pricing, coordination, payment, quality, and support stay with us.'],
                    ['title' => 'Teams and partners fulfill the work', 'body' => 'To Panun Kaergar standards.'],
                    ['title' => 'Price before work starts', 'body' => 'Panun Kaergar sets the customer-facing price.'],
                    ['title' => 'You come back to us', 'body' => 'If a visit goes wrong, you contact Panun Kaergar.'],
                ],
            ],
            [
                'title' => 'Vision',
                'intro' => 'Become Kashmir’s most trusted home-services company — local craftsmanship, modern booking, and one company responsible for the outcome.',
                'steps' => [
                    ['title' => 'The name they call first', 'body' => 'Simple to book. Craft stays bookable. The whole house. We stay responsible for the outcome.'],
                ],
            ],
            [
                'title' => 'Mission',
                'intro' => 'Help every household in Kashmir get professional service with a clear price and a company that stays responsible — while local skilled people do the work.',
                'steps' => [
                    ['title' => 'Help without the hunt', 'body' => 'One company owns the job. Teams and partners visit. Kashmir craft stays in the work.'],
                ],
            ],
            [
                'title' => 'Problem we are solving',
                'intro' => 'You shouldn’t have to run the job yourself.',
                'steps' => [
                    ['title' => 'On your own', 'items' => [
                        'Calling numbers that never pick up',
                        'A callback that never comes',
                        'A different price every time',
                        'Nobody shows up',
                        'Cash to whoever shows up',
                        'Nowhere to turn',
                    ]],
                ],
            ],
            [
                'title' => 'How is Panun Kaergar different',
                'intro' => 'No searching, negotiating, coordinating, or chasing. Panun Kaergar manages the service from booking to completion.',
                'steps' => [
                    ['title' => 'You hire Panun Kaergar', 'body' => 'The right professional, clear pricing, support throughout. You come back to us.'],
                    ['title' => 'Partners', 'body' => 'You Do the Work. We Handle the Rest.'],
                ],
            ],
            [
                'title' => 'What’s your role',
                'intro' => 'From the request to a quality check — this team manages every step.',
                'steps' => [
                    ['title' => '1 You tell us the job', 'body' => 'Job, area, time — phone, WhatsApp, website, or app.'],
                    ['title' => '2 We analyse the request', 'body' => 'Review, confirm service type, scope the work.'],
                    ['title' => '3 You get an estimate', 'body' => 'Price before work begins.'],
                    ['title' => '4 We send the right professional', 'body' => 'Assign and coordinate the visit.'],
                    ['title' => '5 The work gets done', 'body' => 'Payment through Panun Kaergar.'],
                    ['title' => '6 We check the quality', 'body' => 'If it isn’t right, they come back to us.'],
                ],
            ],
        ];
    }
}
