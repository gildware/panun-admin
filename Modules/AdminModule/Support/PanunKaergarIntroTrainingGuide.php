<?php

namespace Modules\AdminModule\Support;

class PanunKaergarIntroTrainingGuide
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function slides(): array
    {
        $slides = [
            self::slideWelcome(),
            self::slideWhoWeAre(),
            self::slideWhyExist(),
            self::slideProblem(),
            self::slideSolution(),
            self::slideVision(),
            self::slideMission(),
            self::slideHowWeDiffer(),
            self::slideWhatWeManage(),
            self::slideEcosystem(),
            self::slideTechnology(),
            self::slidePartners(),
            self::slidePeople(),
            self::slideYourRole(),
            self::slideMindset(),
            self::slideHowWeWork(),
            self::slideExpectations(),
            self::slideOneTeam(),
            self::slideSuccess(),
            self::slidePromise(),
            self::slideQna(),
            self::slideThanks(),
        ];

        foreach ($slides as $i => &$slide) {
            $slide['number'] = $i + 1;
            $slide = self::applySlideMeta($slide);
        }

        return $slides;
    }

    /**
     * @param  array<string, mixed>  $slide
     * @return array<string, mixed>
     */
    private static function applySlideMeta(array $slide): array
    {
        $meta = self::slideMetaMap();
        $id = $slide['id'] ?? '';
        if (isset($meta[$id])) {
            $slide = array_merge($meta[$id], $slide);
        }
        unset($slide['intro'], $slide['overview']);

        return $slide;
    }

    /** @return array<string, array{icon: string}> */
    private static function slideMetaMap(): array
    {
        return [
            'welcome' => ['icon' => 'waving_hand'],
            'who-we-are' => ['icon' => 'home'],
            'why-exist' => ['icon' => 'route'],
            'problem' => ['icon' => 'priority_high'],
            'solution' => ['icon' => 'auto_fix_high'],
            'vision' => ['icon' => 'flag'],
            'mission' => ['icon' => 'center_focus_strong'],
            'how-we-differ' => ['icon' => 'compare_arrows'],
            'what-we-manage' => ['icon' => 'hub'],
            'ecosystem' => ['icon' => 'apps'],
            'technology' => ['icon' => 'devices'],
            'partner-ecosystem' => ['icon' => 'handshake'],
            'people' => ['icon' => 'groups'],
            'your-role' => ['icon' => 'badge'],
            'mindset' => ['icon' => 'psychology'],
            'how-we-work' => ['icon' => 'verified'],
            'expectations' => ['icon' => 'task_alt'],
            'one-team' => ['icon' => 'account_tree'],
            'success' => ['icon' => 'trending_up'],
            'promise' => ['icon' => 'favorite'],
            'qna' => ['icon' => 'forum'],
            'thanks' => ['icon' => 'celebration'],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideWelcome(): array
    {
        return [
            'id' => 'welcome',
            'title' => 'PANUN KAERGAR',
            'tagline' => 'WELCOME TO',
            'subtitle' => 'Taking the Hassle Out of Home Services.',
            'body' => 'A better way for customers to get the help they need — and for professionals to focus on the work they do best.',
            'footer' => "You're not just joining a company.\nYou're joining a mission to take the hassle out of home services for Kashmir.",
            'badge' => 'Team induction',
            'type' => 'pk-cover',
            'logo' => 'pk-logo.png',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideWhoWeAre(): array
    {
        return [
            'id' => 'who-we-are',
            'kicker' => '01  Who we are',
            'title' => 'Who is Panun Kaergar?',
            'title_accent' => 'Panun Kaergar',
            'subtitle' => "Kashmir's full-stack home-services company.",
            'lede' => 'We make getting professional help simple by managing the entire service journey — from booking to support.',
            'type' => 'pk-who',
            'logo' => 'pk-logo.png',
            'hero_image' => 'pk-s2-handshake.png',
            'hero_position' => 'center 28%',
            'tagline' => 'You Need the Service. We Handle Everything Else.',
            'model_label' => 'Our model',
            'flow' => [
                ['icon' => 'groups', 'label' => 'Customer', 'sub' => 'Needs a service'],
                [
                    'label' => 'Panun Kaergar',
                    'tone' => 'brand',
                    'actions' => [
                        ['icon' => 'calendar_month', 'label' => 'Books'],
                        ['icon' => 'headset_mic', 'label' => 'Coordinates'],
                        ['icon' => 'settings', 'label' => 'Manages'],
                        ['icon' => 'verified_user', 'label' => 'Supports'],
                    ],
                ],
                ['icon' => 'engineering', 'label' => 'Professional', 'sub' => 'Delivers the service'],
            ],
            'model_caption' => 'One company manages the experience from request to resolution.',
            'pillars' => [
                [
                    'icon' => 'location_on',
                    'label' => 'Local',
                    'text' => 'Built for Kashmir and our communities. We understand the people, homes and needs.',
                ],
                [
                    'icon' => 'smartphone',
                    'label' => 'Tech-Enabled',
                    'text' => 'App, Website, WhatsApp and Phone — your choice, your convenience.',
                ],
                [
                    'icon' => 'workspace_premium',
                    'label' => 'Verified Partners',
                    'text' => 'Trained, verified and committed to quality service.',
                ],
                [
                    'icon' => 'volunteer_activism',
                    'label' => 'Service-Owned',
                    'text' => 'We stay involved beyond the booking to ensure satisfaction and peace of mind.',
                ],
            ],
            'banner' => "We're not just a directory or lead generation tool.",
            'banner_accent' => 'We manage the service experience.',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideWhyExist(): array
    {
        return [
            'id' => 'why-exist',
            'kicker' => '02 — Why we exist',
            'title' => 'Why does Panun Kaergar exist?',
            'type' => 'pk-why',
            'slide_image' => 'pk-s3-why-slide.png',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideProblem(): array
    {
        return [
            'id' => 'problem',
            'kicker' => '03 — The problem',
            'title' => "The problem isn't finding a service.",
            'subtitle' => "It's managing everything around it.",
            'support' => "You don't just need a professional.\nYou need a smooth, reliable, and hassle-free\nway to get the job done.",
            'type' => 'pk-problem',
            'stages' => [
                [
                    'n' => '01',
                    'title' => 'Find',
                    'accent' => 'gold',
                    'badge' => 'search',
                    'desc' => 'Finding the right professional.',
                    'line' => 'Who can you trust to do it right?',
                    'image' => 'pk-s4-find.png',
                    'points' => [
                        ['icon' => 'person_search', 'title' => 'Finding the right professional', 'text' => "So many options, who's reliable?"],
                        ['icon' => 'call', 'title' => 'Unanswered calls', 'text' => "You don't know who will actually show up."],
                    ],
                ],
                [
                    'n' => '02',
                    'title' => 'Arrange',
                    'accent' => 'coral',
                    'badge' => 'currency_rupee',
                    'desc' => 'Getting the details right.',
                    'line' => "So it's clear, fair and on time.",
                    'image' => 'pk-s4-arrange.png',
                    'points' => [
                        ['icon' => 'sell', 'title' => 'Unclear pricing', 'text' => "You don't know what you'll pay until it's done."],
                        ['icon' => 'schedule', 'title' => 'Delays & no-shows', 'text' => 'Your day gets held up waiting for someone.'],
                    ],
                ],
                [
                    'n' => '03',
                    'title' => 'Chase',
                    'accent' => 'teal',
                    'badge' => 'sync',
                    'desc' => 'Keeping it on track.',
                    'line' => 'Because nothing happens automatically.',
                    'image' => 'pk-s4-chase.png',
                    'points' => [
                        ['icon' => 'groups', 'title' => 'Constant coordination', 'text' => "Calls, messages, updates… it never ends."],
                        ['icon' => 'chat', 'title' => 'Repeated follow-ups', 'text' => "You're always the one reaching out."],
                    ],
                ],
                [
                    'n' => '04',
                    'title' => 'After',
                    'accent' => 'purple',
                    'badge' => 'credit_card',
                    'desc' => 'Getting it done — and paid.',
                    'line' => "The work isn't over after the service.",
                    'image' => 'pk-s4-after.png',
                    'points' => [
                        ['icon' => 'payments', 'title' => 'Payment hassles', 'text' => "Chasing payments shouldn't be your job."],
                        ['icon' => 'headset_mic', 'title' => 'No clear support', 'text' => 'When something goes wrong, who do you turn to?'],
                    ],
                ],
            ],
            'banner' => "Getting a home service shouldn't feel like",
            'banner_accent' => 'managing a project.',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideSolution(): array
    {
        return [
            'id' => 'solution',
            'kicker' => '04 — Our solution',
            'title' => 'You need the service.',
            'subtitle' => 'We handle everything else.',
            'type' => 'pk-process',
            'hero_image' => 'pk-s5-solution.png',
            'hero_position' => '30% 22%',
            'process' => [
                ['icon' => 'chat', 'label' => 'Customer request'],
                ['icon' => 'fact_check', 'label' => 'Understand requirement'],
                ['icon' => 'person_search', 'label' => 'Find the right professional'],
                ['icon' => 'event', 'label' => 'Coordinate the job'],
                ['icon' => 'handyman', 'label' => 'Service delivery'],
                ['icon' => 'support_agent', 'label' => 'Follow-up & support'],
            ],
            'highlight' => 'The customer gets the service. Panun Kaergar manages the experience.',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideVision(): array
    {
        return [
            'id' => 'vision',
            'kicker' => '05 — Our vision',
            'title' => 'Our vision',
            'subtitle' => "To become Kashmir's most trusted platform for service solutions.",
            'type' => 'visual',
            'hero_image' => 'pk-s6-vision.png',
            'hero_position' => 'center 38%',
            'card_groups' => [
                [
                    'layout' => 'row-4',
                    'cards' => [
                        ['icon' => 'spa', 'title' => 'Simplify', 'text' => 'Make everyday service needs easier.', 'color' => 'customer'],
                        ['icon' => 'work', 'title' => 'Empower', 'text' => 'Create more opportunity for skilled local professionals.', 'color' => 'provider'],
                        ['icon' => 'devices', 'title' => 'Modernize', 'text' => 'Bring organization, technology and convenience to services.', 'color' => 'source'],
                        ['icon' => 'verified_user', 'title' => 'Build trust', 'text' => 'Create an experience customers can confidently rely on.', 'color' => 'future'],
                    ],
                ],
            ],
            'highlight' => 'Tradition meets technology.',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideMission(): array
    {
        return [
            'id' => 'mission',
            'kicker' => '06 — Our mission',
            'title' => 'Our mission',
            'subtitle' => 'Reliable help for every home — and better opportunities for local professionals.',
            'type' => 'pk-mission',
            'center' => 'PANUN KAERGAR',
            'left' => [
                'kicker' => 'For customers',
                'items' => [
                    ['icon' => 'schedule', 'label' => 'Convenience'],
                    ['icon' => 'verified', 'label' => 'Reliability'],
                    ['icon' => 'visibility', 'label' => 'Transparency'],
                    ['icon' => 'engineering', 'label' => 'Professional service'],
                    ['icon' => 'support_agent', 'label' => 'Support when things go wrong'],
                ],
            ],
            'right' => [
                'kicker' => 'For professionals',
                'items' => [
                    ['icon' => 'trending_up', 'label' => 'More opportunities'],
                    ['icon' => 'phone_disabled', 'label' => 'Less time chasing customers'],
                    ['icon' => 'event_available', 'label' => 'Less coordination overhead'],
                    ['icon' => 'dashboard', 'label' => 'Better organized work'],
                    ['icon' => 'handyman', 'label' => 'Focus on their skill'],
                ],
            ],
            'highlight' => 'Connecting both sides. Managing the experience.',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideHowWeDiffer(): array
    {
        return [
            'id' => 'how-we-differ',
            'kicker' => '07 — Different',
            'title' => "We're not just a directory.",
            'subtitle' => 'A directory gives you a contact. Panun Kaergar gives you a managed service experience.',
            'type' => 'pk-compare',
            'compare' => [
                [
                    'kicker' => 'Traditional way',
                    'title' => 'You run the job',
                    'tone' => 'old',
                    'steps' => ['Find someone', 'Call', 'Wait', 'Negotiate', 'Coordinate', 'Follow up', 'Chase'],
                ],
                [
                    'kicker' => 'Panun Kaergar',
                    'title' => 'We manage the experience',
                    'tone' => 'new',
                    'steps' => ['Tell us what you need', 'We coordinate', 'Right professional', 'Service delivered', 'We support'],
                ],
            ],
            'highlight' => 'The difference is ownership.',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideWhatWeManage(): array
    {
        return [
            'id' => 'what-we-manage',
            'kicker' => '08 — Customer experience',
            'title' => 'We own the customer experience.',
            'subtitle' => 'The professional focuses on the work. We manage everything around the work.',
            'type' => 'pk-process',
            'process' => [
                ['icon' => 'chat', 'label' => 'Request'],
                ['icon' => 'event', 'label' => 'Booking'],
                ['icon' => 'forum', 'label' => 'Communication'],
                ['icon' => 'person_search', 'label' => 'Professional allocation'],
                ['icon' => 'sync_alt', 'label' => 'Job coordination'],
                ['icon' => 'handyman', 'label' => 'Service delivery'],
                ['icon' => 'payments', 'label' => 'Payment'],
                ['icon' => 'replay', 'label' => 'Follow-up'],
                ['icon' => 'reviews', 'label' => 'Feedback & support'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideEcosystem(): array
    {
        return [
            'id' => 'ecosystem',
            'kicker' => '09 — Service ecosystem',
            'title' => 'One platform. Many needs.',
            'subtitle' => 'Built to become the place people think of whenever they need a service.',
            'type' => 'visual',
            'icon_grid_cols' => '3',
            'icon_grid' => [
                ['n' => '01', 'icon' => 'plumbing', 'title' => 'Home services', 'text' => 'Plumbing · Electrical · Carpentry · Masonry · Painting · Appliances'],
                ['n' => '02', 'icon' => 'cleaning_services', 'title' => 'Home care', 'text' => 'Cleaning · Laundry · Dry clean · Pest control'],
                ['n' => '03', 'icon' => 'spa', 'title' => 'Beauty & wellness', 'text' => "Men's salon · Women's salon · Spa & beauty"],
                ['n' => '04', 'icon' => 'yard', 'title' => 'Lifestyle', 'text' => 'Gardening · Pet grooming · Interior decor'],
                ['n' => '05', 'icon' => 'directions_car', 'title' => 'Vehicle', 'text' => 'Cars · Bikes · Scooters · Wash · Repair · Maintenance'],
                ['n' => '06', 'icon' => 'apartment', 'title' => 'Construction', 'text' => 'Construction · Renovation · Maintenance'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideTechnology(): array
    {
        return [
            'id' => 'technology',
            'kicker' => '10 — Technology',
            'title' => 'Technology is the engine.',
            'subtitle' => 'Service is the product.',
            'type' => 'visual',
            'hero_image' => 'pk-s11-tech.png',
            'hero_position' => '55% 28%',
            'card_groups' => [
                [
                    'layout' => 'row-4',
                    'cards' => [
                        ['icon' => 'language', 'title' => 'Website', 'text' => 'Easy access to services.', 'color' => 'source'],
                        ['icon' => 'smartphone', 'title' => 'App', 'text' => 'Book, track and manage.', 'color' => 'future'],
                        ['icon' => 'chat', 'title' => 'WhatsApp', 'text' => 'Simple communication.', 'color' => 'customer'],
                        ['icon' => 'call', 'title' => 'Phone', 'text' => 'Human support.', 'color' => 'provider'],
                    ],
                ],
            ],
            'chips' => [
                ['icon' => 'event', 'label' => 'Booking'],
                ['icon' => 'my_location', 'label' => 'Tracking'],
                ['icon' => 'forum', 'label' => 'Job-specific chat'],
                ['icon' => 'history', 'label' => 'Booking history'],
                ['icon' => 'replay', 'label' => 'Rebooking'],
                ['icon' => 'tune', 'label' => 'Custom requests'],
                ['icon' => 'star', 'label' => 'Ratings'],
                ['icon' => 'loyalty', 'label' => 'Loyalty & rewards'],
            ],
            'highlight' => 'Technology makes the process easier. People make the experience better.',
        ];
    }

    /** @return array<string, mixed> */
    private static function slidePartners(): array
    {
        return [
            'id' => 'partner-ecosystem',
            'kicker' => '11 — Partner ecosystem',
            'title' => 'Customers need help. Professionals need opportunity.',
            'subtitle' => 'Panun Kaergar connects both sides and manages the experience.',
            'type' => 'visual',
            'hero_image' => 'pk-s12-partner.png',
            'hero_position' => 'center 32%',
            'card_groups' => [
                [
                    'layout' => 'row-3',
                    'cards' => [
                        ['icon' => 'home', 'title' => 'Customer', 'text' => 'Has a problem. Needs a service.', 'color' => 'customer'],
                        [
                            'icon' => 'hub',
                            'title' => 'Panun Kaergar',
                            'text' => 'Finds the right professional. Coordinates the job. Manages communication. Supports the customer. Helps manage payment.',
                            'color' => 'source',
                        ],
                        ['icon' => 'handyman', 'title' => 'Professional', 'text' => 'Delivers the service.', 'color' => 'provider'],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slidePeople(): array
    {
        return [
            'id' => 'people',
            'kicker' => '12 — Our people',
            'title' => 'The platform can connect people.',
            'subtitle' => 'Our team makes the experience work.',
            'type' => 'pk-people',
            'hero_image' => 'pk-s13-people.png',
            'hero_position' => 'center 26%',
            'links' => ['Booking', 'Follow-up'],
            'pulse' => 'Every call. Every message. Every booking. Every follow-up. Every problem.',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideYourRole(): array
    {
        return [
            'id' => 'your-role',
            'kicker' => '13 — Your role',
            'title' => 'Where do you fit in?',
            'subtitle' => 'Your job is to make the Panun Kaergar promise real.',
            'type' => 'visual',
            'hero_image' => 'pk-s14-role.png',
            'hero_position' => '40% 22%',
            'icon_grid_cols' => '2',
            'icon_grid' => [
                ['n' => '1', 'icon' => 'hearing', 'title' => 'Customer', 'text' => 'Make customers feel heard.'],
                ['n' => '2', 'icon' => 'engineering', 'title' => 'Professional', 'text' => 'Help professionals succeed.'],
                ['n' => '3', 'icon' => 'bolt', 'title' => 'Speed', 'text' => 'Keep things moving.'],
                ['n' => '4', 'icon' => 'forum', 'title' => 'Communication', 'text' => 'Never leave people guessing.'],
                ['n' => '5', 'icon' => 'assignment_ind', 'title' => 'Ownership', 'text' => 'Take responsibility.'],
                ['n' => '6', 'icon' => 'verified_user', 'title' => 'Trust', 'text' => 'Protect the brand.'],
            ],
            'highlight' => 'The customer experiences Panun Kaergar through you.',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideMindset(): array
    {
        return [
            'id' => 'mindset',
            'kicker' => '14 — Ownership',
            'title' => "Don't just complete tasks.",
            'subtitle' => 'Solve the problem.',
            'type' => 'pk-compare',
            'compare' => [
                [
                    'kicker' => 'Wrong mindset',
                    'title' => "That's not my responsibility.",
                    'tone' => 'old',
                    'text' => 'The task is closed. The customer is still stuck.',
                ],
                [
                    'kicker' => 'Panun Kaergar mindset',
                    'title' => 'How do we get this resolved?',
                    'tone' => 'new',
                    'text' => 'Listen. Take ownership. Find the right person. Follow through. Make sure it gets resolved.',
                ],
            ],
            'highlight' => 'Own the problem until it reaches the right person.',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideHowWeWork(): array
    {
        return [
            'id' => 'how-we-work',
            'kicker' => '15 — The standard',
            'title' => 'How we work',
            'subtitle' => 'Six behaviors that turn a booking into a trusted experience.',
            'type' => 'visual',
            'icon_grid_cols' => '3',
            'icon_grid' => [
                ['n' => '1', 'icon' => 'hearing', 'title' => 'Listen', 'text' => 'Understand what the customer actually needs.'],
                ['n' => '2', 'icon' => 'fact_check', 'title' => 'Understand', 'text' => 'Get the details right before taking action.'],
                ['n' => '3', 'icon' => 'forum', 'title' => 'Communicate', 'text' => 'Never leave customers or professionals guessing.'],
                ['n' => '4', 'icon' => 'sync_alt', 'title' => 'Coordinate', 'text' => 'Make sure the right people and information connect.'],
                ['n' => '5', 'icon' => 'replay', 'title' => 'Follow through', 'text' => "Don't assume something is done. Confirm it."],
                ['n' => '6', 'icon' => 'task_alt', 'title' => 'Close', 'text' => "A task isn't complete until the customer journey is complete."],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideExpectations(): array
    {
        return [
            'id' => 'expectations',
            'kicker' => '16 — Expectations',
            'title' => 'Be the person who gets things done.',
            'subtitle' => 'What we expect from every team member.',
            'type' => 'visual',
            'icon_grid_cols' => '4',
            'icon_grid' => [
                ['n' => '1', 'icon' => 'notifications_active', 'title' => 'Responsiveness', 'text' => 'Respond quickly.'],
                ['n' => '2', 'icon' => 'assignment_ind', 'title' => 'Ownership', 'text' => 'Take responsibility.'],
                ['n' => '3', 'icon' => 'gps_fixed', 'title' => 'Accuracy', 'text' => 'Get the details right.'],
                ['n' => '4', 'icon' => 'campaign', 'title' => 'Communication', 'text' => 'Keep people informed.'],
                ['n' => '5', 'icon' => 'badge', 'title' => 'Professionalism', 'text' => 'Represent the brand well.'],
                ['n' => '6', 'icon' => 'groups', 'title' => 'Teamwork', 'text' => 'Solve problems together.'],
                ['n' => '7', 'icon' => 'done_all', 'title' => 'Follow-through', 'text' => "Don't let tasks disappear."],
            ],
            'highlight' => 'Small actions create big customer experiences.',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideOneTeam(): array
    {
        return [
            'id' => 'one-team',
            'kicker' => '17 — One team',
            'title' => 'Customer experience has no department.',
            'subtitle' => "Customers don't see departments. They see one company.",
            'type' => 'pk-funnel',
            'hero_image' => 'pk-s18-oneteam.png',
            'hero_position' => 'center 30%',
            'departments' => [
                ['icon' => 'campaign', 'label' => 'Marketing'],
                ['icon' => 'settings', 'label' => 'Operations'],
                ['icon' => 'headset_mic', 'label' => 'Support'],
                ['icon' => 'devices', 'label' => 'Technology'],
                ['icon' => 'account_balance', 'label' => 'Finance'],
                ['icon' => 'flag', 'label' => 'Management'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideSuccess(): array
    {
        return [
            'id' => 'success',
            'kicker' => '18 — Success',
            'title' => 'What are we building?',
            'subtitle' => 'Not just more bookings. A trusted service ecosystem.',
            'type' => 'visual',
            'hero_image' => 'pk-s19-success.png',
            'hero_position' => '48% 38%',
            'card_groups' => [
                [
                    'layout' => 'row-4',
                    'cards' => [
                        ['icon' => 'home', 'title' => 'Customers', 'text' => 'People who trust us.', 'color' => 'customer'],
                        ['icon' => 'handyman', 'title' => 'Professionals', 'text' => 'Professionals who want to work with us.', 'color' => 'provider'],
                        ['icon' => 'task_alt', 'title' => 'Service', 'text' => 'Jobs completed properly.', 'color' => 'source'],
                        ['icon' => 'flag', 'title' => 'Brand', 'text' => 'A service brand Kashmir can rely on.', 'color' => 'future'],
                    ],
                ],
            ],
            'highlight' => 'We are building trust at scale.',
        ];
    }

    /** @return array<string, mixed> */
    private static function slidePromise(): array
    {
        return [
            'id' => 'promise',
            'title' => 'The Panun Kaergar Promise',
            'type' => 'pk-promise',
            'hero_image' => 'pk-s20-promise.png',
            'hero_position' => 'center 34%',
            'promise_cards' => [
                ['kicker' => 'Customer', 'icon' => 'home', 'quote' => "I don't have to chase anyone."],
                ['kicker' => 'Professional', 'icon' => 'handyman', 'quote' => 'I can focus on my work.'],
                ['kicker' => 'Team', 'icon' => 'groups', 'quote' => 'We make sure everything moves.'],
            ],
            'promise_title' => 'You Need the Service.',
            'promise_sub' => 'We Handle Everything Else.',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideQna(): array
    {
        return [
            'id' => 'qna',
            'title' => 'Questions?',
            'type' => 'pk-qna',
            'prompts' => [
                'What is unclear?',
                'What can we improve?',
                'What do you want to know?',
            ],
            'highlight' => "Let's talk.",
        ];
    }

    /** @return array<string, mixed> */
    private static function slideThanks(): array
    {
        return [
            'id' => 'thanks',
            'title' => "We're building more than a service platform.",
            'tagline' => 'Welcome to Panun Kaergar',
            'subtitle' => "We're building a better way for Kashmir to get things done.",
            'footer' => 'For customers.    For professionals.    For our communities.    For Kashmir.',
            'badge' => "Let's build it together.",
            'type' => 'pk-close',
            'hero_image' => 'pk-s22-close.png',
            'hero_position' => 'center 30%',
        ];
    }
}
