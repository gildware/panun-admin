<?php

class MensSalonContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? "Men's grooming");

        $focus = self::serviceFocus($slug);

        return [
            'short_description' => "Professional {$name} at your home by trained Panun Kaergar men’s stylists — hygienic tools, neat finish, and comfortable at-home service.",
            'intro' => self::introLine($name, $focus),
            'description' => "{$name} by Panun Kaergar is delivered at your doorstep by trained men’s stylists. Your stylist understands the look you want, prepares a clean kit, completes the booked session carefully, and shares simple aftercare tips before leaving.",
            'card_highlights' => [
                self::highlight('verified', 'Trained Stylists', 'purple', 0),
                self::highlight('quality', 'Hygiene Focused', 'green', 1),
                self::highlight('sparkle', 'Polished Finish', 'blue', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Choose {$name} and share style notes or preferences."),
                self::step('verified', 'Stylist arrival', 'A verified men’s stylist arrives with a clean portable kit.', 'thumb'),
                self::step('tools', 'Service session', self::sessionLine($focus), 'cover'),
                self::step('quality', 'Finish check', 'You review the result; small adjustments are done before wrap-up.'),
                self::step('sparkle', 'Aftercare tips', 'Simple aftercare guidance shared before the stylist leaves.'),
            ],
            'perfect_for' => self::chips(self::idealFor($slug)),
            'whats_included' => self::included(self::includedItems($slug)),
            'good_to_know' => self::notes([
                'Keep a clean, well-lit seating area ready at home.',
                'Wash hair before haircut or color when possible.',
                'Share allergies, skin sensitivity, or preferred look when booking.',
                'Color and waxing may need patch-test time for sensitive skin.',
                'Notify at least 2 hours before for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Salon-only chemical treatments (rebonding / keratin systems)',
                'Medical dermatology diagnosis or treatment',
                'Products supplied by customer unless agreed',
                'Women’s or mixed-gender salon services',
                'Combo packages sold as separate services',
            ]),
            'faqs' => self::faqsFor($slug, $name),
        ];
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        return "{$serviceName} — {$variantTitle}. At-home men’s grooming with trained stylists and a clean finish.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        return 'Pricing is for one person per booking. Mention extra guests when booking if needed.';
    }

    private static function serviceFocus(string $slug): string
    {
        return match ($slug) {
            'mens-hair-cut' => 'men’s haircut and finishing',
            'mens-kids-hair-cut' => 'gentle kids haircut',
            'mens-hair-color' => 'men’s hair coloring',
            'mens-hair-treatment' => 'men’s hair treatment',
            'mens-beard-trimming' => 'beard shaping and trim',
            'mens-clean-shave' => 'clean shave with aftercare',
            'mens-beard-color' => 'beard coloring',
            'mens-detan' => 'detan application for men',
            'mens-waxing' => 'men’s body waxing',
            'mens-facial-cleanup' => 'facial cleanup for men',
            'mens-threading' => 'men’s threading',
            'mens-pedicure' => 'men’s pedicure',
            'mens-manicure' => 'men’s manicure',
            'mens-nail-cut-file' => 'nail cut and file',
            'mens-massage' => 'relaxing head or shoulder massage',
            default => 'men’s grooming',
        };
    }

    private static function introLine(string $name, string $focus): string
    {
        return "At-home {$name} focused on {$focus} with a neat, hygienic finish.";
    }

    private static function sessionLine(string $focus): string
    {
        return "The stylist completes {$focus} using clean tools and professional technique.";
    }

    private static function idealFor(string $slug): array
    {
        return match ($slug) {
            'mens-hair-cut' => ['Routine haircut', 'Office-ready look', 'At-home convenience', 'Style refresh'],
            'mens-kids-hair-cut' => ['Boys haircut', 'Home comfort for kids', 'Quick tidy cut', 'School-ready look'],
            'mens-hair-color' => ['Grey coverage', 'Even color finish', 'At-home coloring', 'Natural look refresh'],
            'mens-hair-treatment' => ['Dry or damaged hair', 'Hairfall concern', 'Scalp care', 'Post-color care'],
            'mens-beard-trimming' => ['Beard shape-up', 'Mustache tidy', 'Jawline definition', 'Weekly grooming'],
            'mens-clean-shave' => ['Smooth shave', 'Fresh look', 'Pre-event prep', 'Routine shave'],
            'mens-beard-color' => ['Grey beard coverage', 'Even beard tone', 'Natural finish', 'Quick touch-up'],
            'mens-detan' => ['Sun tan removal', 'Face brightness', 'Hands refresh', 'Outdoor lifestyle'],
            'mens-waxing' => ['Chest or back hair', 'Underarm clean-up', 'Arm waxing', 'Smooth finish'],
            'mens-facial-cleanup' => ['Blackhead cleanup', 'Instant freshness', 'Pre-event glow', 'Skin refresh'],
            'mens-threading' => ['Eyebrow shape', 'Full face tidy', 'Clean brow line', 'Quick grooming'],
            'mens-pedicure' => ['Foot care', 'Nail tidy', 'Hard skin soft finish', 'Hygiene refresh'],
            'mens-manicure' => ['Hand grooming', 'Nail tidy', 'Office-ready hands', 'Quick clean-up'],
            'mens-nail-cut-file' => ['Quick nail tidy', 'Hands or feet', 'Hygiene maintenance', 'Short session'],
            'mens-massage' => ['Stress relief', 'Head tension', 'Neck stiffness', 'Short home massage'],
            default => ['At-home men’s grooming', 'Busy schedules', 'Hygienic finish', 'Verified stylists'],
        };
    }

    private static function includedItems(string $slug): array
    {
        return match ($slug) {
            'mens-hair-cut' => [
                'Consultation on length and style',
                'Haircut with professional tools',
                'Neck and side tidy',
                'Basic finish and combing',
                'Aftercare tip',
            ],
            'mens-kids-hair-cut' => [
                'Gentle kids haircut',
                'Clean clipper or scissor finish',
                'Neck tidy',
                'Comfort-focused handling',
                'Aftercare tip',
            ],
            'mens-hair-color' => [
                'Color consultation',
                'Application as per selected variation',
                'Even coverage focus',
                'Clean-up of application area',
                'Aftercare tip',
            ],
            'mens-hair-treatment' => [
                'Hair and scalp assessment',
                'Treatment application',
                'Massage as required for the package',
                'Rinse or leave-on as per treatment',
                'Aftercare tip',
            ],
            'mens-beard-trimming' => [
                'Beard shape consultation',
                'Trim and line-up',
                'Mustache tidy when selected',
                'Symmetry check',
                'Aftercare tip',
            ],
            'mens-clean-shave' => [
                'Pre-shave prep',
                'Clean shave',
                'Hot towel or soothing finish as per package',
                'Aftershave care',
                'Aftercare tip',
            ],
            'mens-beard-color' => [
                'Beard color consultation',
                'Application as per selected variation',
                'Even tone focus',
                'Clean-up',
                'Aftercare tip',
            ],
            'mens-detan' => [
                'Skin prep',
                'Detan application on selected area',
                'Even coverage',
                'Clean wipe-down',
                'Aftercare tip',
            ],
            'mens-waxing' => [
                'Area prep',
                'Waxing of selected body area',
                'Hair removal finish',
                'Soothing wipe',
                'Aftercare tip',
            ],
            'mens-facial-cleanup' => [
                'Face cleanse',
                'Cleanup or facial steps as per variation',
                'Blackhead focus where included',
                'Moisturiser finish',
                'Aftercare tip',
            ],
            'mens-threading' => [
                'Shape consultation',
                'Threading of selected area',
                'Symmetry check',
                'Soothing finish',
                'Aftercare tip',
            ],
            'mens-pedicure' => [
                'Foot soak prep',
                'Nail cut and file',
                'Cuticle tidy',
                'Light scrub or finish as per express pedicure',
                'Aftercare tip',
            ],
            'mens-manicure' => [
                'Hand prep',
                'Nail cut and file',
                'Cuticle tidy',
                'Express manicure finish',
                'Aftercare tip',
            ],
            'mens-nail-cut-file' => [
                'Nail cutting',
                'Nail filing',
                'Quick tidy of selected area',
                'Hygiene-focused tools',
                'Aftercare tip',
            ],
            'mens-massage' => [
                'Quick preference check',
                'Timed massage for selected variation',
                'Focus on head, neck, or shoulders as booked',
                'Comfort pacing',
                'Aftercare tip',
            ],
            default => [
                'Professional stylist visit',
                'Clean portable kit',
                'Booked service completion',
                'Finish check',
                'Aftercare tip',
            ],
        };
    }

    private static function faqsFor(string $slug, string $name): array
    {
        $common = [
            ['q' => "Is {$name} done at home?", 'a' => 'Yes. A verified Panun Kaergar men’s stylist comes to your home with a portable kit.'],
            ['q' => 'Do I need to prepare anything?', 'a' => 'Keep a clean seating area ready. For hair services, washed hair helps. Share allergies or preferences when booking.'],
            ['q' => 'How long does it take?', 'a' => 'Most sessions finish within the typical slot for the selected variation. Exact time depends on hair/beard length and the package.'],
        ];

        $extra = match ($slug) {
            'mens-hair-color', 'mens-beard-color' => [
                ['q' => 'Do you bring color product?', 'a' => 'Choose With Product if you want product included. Choose Without Product for application-only when you provide product.'],
            ],
            'mens-waxing' => [
                ['q' => 'Which area should I book?', 'a' => 'Book the exact body area you need — underarms, chest, back, or full arms.'],
            ],
            'mens-kids-hair-cut' => [
                ['q' => 'Is it suitable for young boys?', 'a' => 'Yes. Stylists keep the session calm and short, with a comfort-first approach.'],
            ],
            default => [],
        };

        return array_map(
            static fn (array $item): array => [$item['q'], $item['a']],
            array_merge($common, $extra)
        );
    }

    private static function highlight(string $icon, string $text, string $color, int $sort): array
    {
        return [
            'icon' => $icon,
            'text' => $text,
            'color' => $color,
            'sort_order' => $sort,
        ];
    }

    private static function step(string $icon, string $title, string $description, ?string $image = null): array
    {
        $step = [
            'icon' => $icon,
            'title' => $title,
            'description' => $description,
        ];
        if ($image !== null) {
            $step['image'] = $image;
        }

        return $step;
    }

    private static function chips(array $labels): array
    {
        return array_map(static fn (string $label, int $i) => [
            'text' => $label,
            'sort_order' => $i,
        ], $labels, array_keys($labels));
    }

    private static function included(array $labels): array
    {
        return array_map(static fn (string $label, int $i) => [
            'text' => $label,
            'sort_order' => $i,
        ], $labels, array_keys($labels));
    }

    private static function notes(array $labels): array
    {
        return array_map(static fn (string $label, int $i) => [
            'text' => $label,
            'sort_order' => $i,
        ], $labels, array_keys($labels));
    }
}
