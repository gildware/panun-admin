<?php

class BookKaergarContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Book Kaergar');
        $role = (string) ($service['role'] ?? self::roleFromSlug($slug));
        $roleLabel = self::roleLabel($role);
        $focus = self::serviceFocus($role);

        return [
            'short_description' => "Hire a verified Panun Kaergar {$roleLabel} by the hour, half day, or full day across Kashmir — clear time packages and on-time visits.",
            'intro' => "Book a verified {$roleLabel} for the time you need — 1 hour, half day, or full day.",
            'description' => "{$name} by Panun Kaergar lets you hire a verified {$roleLabel} for a fixed time package instead of a single fixed job. Tell us the work you need, pick 1 hour, half day (4 hours), or full day (8 hours), and a professional arrives ready to work. Materials and extras outside the booked labour time are confirmed on site. For booking help, use call, WhatsApp, website, or the Panun Kaergar app profile/contact options.",
            'card_highlights' => [
                self::highlight('verified', 'Verified Pros', 'purple', 0),
                self::highlight('calendar', 'Hour / Half / Full Day', 'blue', 1),
                self::highlight('tools', 'Ready to Work', 'green', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Choose time package', 'Select 1 hour, half day, or full day and share what needs doing.'),
                self::step('verified', 'Pro assigned', "A verified Panun Kaergar {$roleLabel} is confirmed for your slot.", 'thumb'),
                self::step('tools', 'On-site work', self::sessionLine($roleLabel, $focus), 'cover'),
                self::step('quality', 'Scope check', 'Work done within the booked time is reviewed with you before wrap-up.'),
                self::step('sparkle', 'Handover', 'Area left tidy; extras or follow-up visits are quoted clearly if needed.'),
            ],
            'perfect_for' => self::chips(self::idealFor($role)),
            'whats_included' => self::included(self::includedItems($role)),
            'good_to_know' => self::notes([
                'Packages cover professional labour time only — materials are separate unless agreed.',
                'Share access details, parking, and any society permissions when booking.',
                'Describe the work clearly so the right tools and skill level are assigned.',
                'If work needs more time, ask for an extension or a follow-up booking before overtime starts.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Cost of materials, spare parts, paint, fittings, or consumables',
                'Specialist machinery hire unless agreed in advance',
                'Overnight stay or multi-day camp unless booked separately',
                'Municipal permits, NOCs, or society approvals',
                'Work outside the booked time package without confirmation',
            ]),
            'faqs' => self::faqsFor($roleLabel),
        ];
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        return "{$serviceName} — {$variantTitle}. Verified Panun Kaergar professional labour for the selected time package. Materials and extras are confirmed on site.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        return 'Hour, half-day, and full-day packages cover labour time only. Materials, spare parts, and consumables are charged separately if needed.';
    }

    private static function roleFromSlug(string $slug): string
    {
        return match (true) {
            str_contains($slug, 'carpenter') => 'carpenter',
            str_contains($slug, 'electrician') => 'electrician',
            str_contains($slug, 'plumber') => 'plumber',
            str_contains($slug, 'painter') => 'painter',
            str_contains($slug, 'mason') => 'mason',
            str_contains($slug, 'labour') => 'labour',
            str_contains($slug, 'welder') => 'welder',
            str_contains($slug, 'gardener') => 'gardener',
            str_contains($slug, 'cleaner') => 'cleaner',
            str_contains($slug, 'makeup') => 'makeup',
            str_contains($slug, 'mehndi') => 'mehndi',
            default => 'professional',
        };
    }

    private static function roleLabel(string $role): string
    {
        return match ($role) {
            'carpenter' => 'carpenter',
            'electrician' => 'electrician',
            'plumber' => 'plumber',
            'painter' => 'painter',
            'mason' => 'mason',
            'labour' => 'labour helper',
            'welder' => 'welder / fabricator',
            'gardener' => 'gardener',
            'cleaner' => 'cleaner',
            'makeup' => 'makeup artist',
            'mehndi' => 'mehndi artist',
            default => 'professional',
        };
    }

    private static function serviceFocus(string $role): string
    {
        return match ($role) {
            'carpenter' => 'woodwork, fittings, and carpentry tasks as discussed on site',
            'electrician' => 'electrical checks, fittings, and safe wiring tasks as discussed on site',
            'plumber' => 'plumbing fixtures, leaks, and pipework tasks as discussed on site',
            'painter' => 'surface prep and painting tasks as discussed on site',
            'mason' => 'brick, tile, cement, and masonry tasks as discussed on site',
            'labour' => 'loading, shifting, and general site helper tasks as discussed on site',
            'welder' => 'welding, cutting, and metal fabrication tasks as discussed on site',
            'gardener' => 'lawn, planting, and garden upkeep tasks as discussed on site',
            'cleaner' => 'home or site cleaning tasks as discussed on site',
            'makeup' => 'makeup application for the booked event window',
            'mehndi' => 'mehndi / henna application for the booked event window',
            default => 'agreed on-site professional tasks',
        };
    }

    private static function sessionLine(string $roleLabel, string $focus): string
    {
        return "The {$roleLabel} completes {$focus} within your booked time.";
    }

    private static function idealFor(string $role): array
    {
        return match ($role) {
            'carpenter' => ['Door & furniture fixes', 'Small woodwork jobs', 'Half-day carpentry help', 'On-site fitting support'],
            'electrician' => ['Socket & switch work', 'Wiring help', 'Board checks', 'Half-day electrical jobs'],
            'plumber' => ['Leak fixes', 'Fixture help', 'Bathroom pipework', 'Half-day plumbing jobs'],
            'painter' => ['Touch-ups', 'Single-room painting', 'Prep & coat days', 'Half-day paint help'],
            'mason' => ['Tile & brick work', 'Cement patch jobs', 'Site masonry days', 'Half-day mason help'],
            'labour' => ['Loading & shifting', 'Site cleanup help', 'Construction assist', 'Full-day labour'],
            'welder' => ['Gate & grill work', 'On-site welding', 'Fabrication help', 'Half-day metal work'],
            'gardener' => ['Lawn & beds', 'Terrace gardens', 'Seasonal tidy-up', 'Half-day garden help'],
            'cleaner' => ['Home deep tidy', 'Post-work cleanup', 'Half-day cleaning', 'Office floor help'],
            'makeup' => ['Events & functions', 'Bridal party slots', 'Half-day makeup days', 'Photo-ready looks'],
            'mehndi' => ['Wedding mehndi', 'Event henna', 'Half-day mehndi days', 'Guest mehndi lines'],
            default => ['Homes', 'Shops', 'Sites', 'Events'],
        };
    }

    private static function includedItems(string $role): array
    {
        $common = [
            'Verified Panun Kaergar professional',
            'Labour for the selected time package',
            'On-site discussion of tasks before starting',
            'Basic tools for the trade (as carried by the pro)',
            'Area left reasonably tidy after work',
        ];

        $extra = match ($role) {
            'labour' => ['Helper for loading, shifting, and general site assist'],
            'makeup', 'mehndi' => ['Artist time for the booked window', 'Basic kit as carried by the artist'],
            default => ['Work scoped to what fits the booked duration'],
        };

        return array_values(array_unique(array_merge($common, $extra)));
    }

    private static function faqsFor(string $roleLabel): array
    {
        return [
            self::faq('What do hour, half day, and full day mean?', '1 hour is a short visit package. Half day is about 4 hours. Full day is about 8 hours of professional labour time.'),
            self::faq("Are materials included when I book a {$roleLabel}?", 'No — packages cover labour time. Materials, spare parts, and consumables are quoted separately if needed.'),
            self::faq('Can the pro do any task within the time?', 'Work stays within the skill of the booked role. Share your task list when booking so the right professional is assigned.'),
            self::faq('What if the job needs more time?', 'Ask before overtime starts. You can extend on site if available, or book another package for a follow-up visit.'),
            self::faq('How do I contact Panun Kaergar?', 'Use call, WhatsApp, the website booking form, or the Panun Kaergar app — support details are in the app profile and contact sections.'),
        ];
    }

    private static function highlight(string $icon, string $text, string $color, int $sort): array
    {
        return ['icon' => $icon, 'text' => $text, 'color' => $color, 'sort_order' => $sort];
    }

    private static function step(string $icon, string $title, string $description, ?string $image = null): array
    {
        $step = ['icon' => $icon, 'title' => $title, 'description' => $description];
        if ($image) {
            $step['image'] = $image;
        }

        return $step;
    }

    private static function chips(array $items): array
    {
        $chips = [];
        foreach ($items as $i => $text) {
            $chips[] = ['icon' => 'home', 'text' => $text, 'sort_order' => $i];
        }

        return $chips;
    }

    private static function included(array $items): array
    {
        $rows = [];
        foreach ($items as $i => $title) {
            $rows[] = ['icon' => 'check', 'title' => $title, 'sort_order' => $i];
        }

        return $rows;
    }

    private static function notes(array $items): array
    {
        $rows = [];
        foreach ($items as $i => $title) {
            $rows[] = ['icon' => 'check', 'title' => $title, 'sort_order' => $i];
        }

        return $rows;
    }

    private static function faq(string $question, string $answer): array
    {
        return [$question, $answer];
    }
}
