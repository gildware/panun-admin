<?php

class GardeningContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Gardening service');
        $subSlug = (string) ($service['sub_category_slug'] ?? '');
        $focus = self::serviceFocus($slug);

        return [
            'short_description' => "Professional {$name} in Srinagar by verified Panun Kaergar gardeners — neat finish, proper tools, and on-time visits.",
            'intro' => self::introLine($name, $focus),
            'description' => "{$name} by Panun Kaergar helps you keep outdoor spaces tidy and healthy. A verified gardener visits your site, reviews the garden area, completes the booked work with proper tools, and shares simple upkeep tips before handover.",
            'card_highlights' => [
                self::highlight('verified', 'Verified Gardeners', 'purple', 0),
                self::highlight('tools', 'Proper Equipment', 'blue', 1),
                self::highlight('sparkle', 'Neat Finish', 'green', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Choose the right package for {$name} and pick a convenient time."),
                self::step('verified', 'Gardener assigned', 'A verified Panun Kaergar gardener confirms the visit.', 'thumb'),
                self::step('tools', 'On-site work', self::sessionLine($focus), 'cover'),
                self::step('quality', 'Quality check', 'Work area is reviewed for a clean, even finish.'),
                self::step('sparkle', 'Handover tips', 'Basic upkeep guidance shared before the gardener leaves.'),
            ],
            'perfect_for' => self::chips(self::idealFor($slug, $subSlug)),
            'whats_included' => self::included(self::includedItems($slug)),
            'good_to_know' => self::notes([
                'Ensure garden access and water supply are available on site.',
                'Mention terrace access, gate codes, or society permissions when booking.',
                'Green waste is collected and bagged unless disposal is agreed separately.',
                'Seasonal plants may need follow-up care — ask your gardener on site.',
                'Notify at least 2 hours before for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Plants, pots, soil, or fertilizer unless agreed on site',
                'Large tree felling or hazardous height work',
                'Hard landscaping, paving, or masonry',
                'Pest control for home interiors',
                'Disposal fees at municipal dump unless quoted',
            ]),
            'faqs' => self::faqsFor($slug),
        ];
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        if ($variantTitle === 'Book Site Inspection' || $variantTitle === 'Book On Site Inspection') {
            return self::inspectionDescription($serviceName);
        }

        return "{$serviceName} — {$variantTitle}. Verified gardener visit with tools and a neat finish.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        if ($serviceSlug === 'book-a-gardener') {
            return 'Hourly and day packages cover labour only. Plants, soil, and materials are charged separately if needed.';
        }

        if (str_contains($serviceSlug, 'monthly')) {
            return 'Monthly plan includes scheduled visits as per package. Extra visits or materials are quoted separately.';
        }

        return 'This ₹100 inspection fee will be adjusted against your final gardening bill if you proceed with the full service through Panun Kaergar.';
    }

    private static function inspectionDescription(string $serviceName): string
    {
        return "Verified gardener inspects your garden for {$serviceName}, reviews scope and plant condition, and recommends the right plan on site.";
    }

    private static function serviceFocus(string $slug): string
    {
        return match (true) {
            str_contains($slug, 'lawn-mowing') => 'mowing and trimming the lawn to an even height',
            str_contains($slug, 'grass-edging') => 'edging and levelling grass borders along paths and beds',
            str_contains($slug, 'planting-repotting') => 'planting new saplings and repotting container plants',
            str_contains($slug, 'soil-preparation') => 'soil loosening, compost mixing, and fertilizing',
            str_contains($slug, 'terrace-balcony') => 'setting up planters, soil, and layout for terrace or balcony gardens',
            str_contains($slug, 'drip-irrigation') => 'installing drip lines and emitters for efficient watering',
            str_contains($slug, 'hedge-cutting') => 'trimming hedges and boundary shrubs to shape',
            str_contains($slug, 'tree-shrub') => 'pruning trees and shrubs for health and shape',
            str_contains($slug, 'plant-shaping') => 'deadheading flowers and shaping ornamental plants',
            str_contains($slug, 'cleanup-weeding') => 'weeding beds and clearing overgrown patches',
            str_contains($slug, 'leaf-debris') => 'collecting fallen leaves and garden debris',
            str_contains($slug, 'seasonal') => 'seasonal cleanup, mulching, and prep for the next season',
            str_contains($slug, 'monthly') => 'scheduled monthly garden upkeep visits',
            str_contains($slug, 'plant-pest') => 'treating plant pests and common garden diseases',
            str_contains($slug, 'book-a-gardener') => 'general gardening tasks as discussed on site',
            default => 'professional garden care',
        };
    }

    private static function introLine(string $name, string $focus): string
    {
        return "Reliable {$name} with verified gardeners and a neat outdoor finish.";
    }

    private static function sessionLine(string $focus): string
    {
        return "The gardener completes {$focus} using proper tools and safe work practices.";
    }

    private static function idealFor(string $slug, string $subSlug): array
    {
        return match ($subSlug) {
            'lawn-grass-care' => ['Home lawns', 'Society green areas', 'Office lawns', 'Overgrown grass patches'],
            'planting-soil-care' => ['New planters', 'Terrace gardens', 'Balcony pots', 'Seasonal planting'],
            'pruning-trimming' => ['Boundary hedges', 'Ornamental shrubs', 'Fruit trees', 'Overgrown branches'],
            'garden-cleanup-maintenance' => ['Weedy gardens', 'Seasonal cleanup', 'Regular upkeep', 'Leaf-heavy yards'],
            default => ['Homes', 'Terraces', 'Offices', 'Society gardens'],
        };
    }

    private static function includedItems(string $slug): array
    {
        return match (true) {
            str_contains($slug, 'lawn-mowing') => [
                'Lawn mowing to even height',
                'Edge trimming along borders',
                'Clippings collected and bagged',
                'Basic uneven patch tidy-up',
            ],
            str_contains($slug, 'book-a-gardener') => [
                'Verified gardener labour',
                'Standard gardening hand tools',
                'On-site task discussion',
                'Work as per booked duration',
            ],
            str_contains($slug, 'monthly') => [
                'Scheduled monthly visits',
                'Lawn, bed, and general tidy-up',
                'Weeding and light pruning',
                'Seasonal adjustment on request',
            ],
            str_contains($slug, 'drip-irrigation') => [
                'Drip line layout planning',
                'Emitter and connector installation',
                'Basic pressure check',
                'Usage guidance',
            ],
            default => [
                'On-site assessment',
                'Work as per booked service',
                'Standard gardening tools',
                'Area left tidy after work',
                'Basic upkeep guidance',
            ],
        };
    }

    private static function faqsFor(string $slug): array
    {
        return [
            self::faq('Do you bring tools and equipment?', 'Yes — standard gardening tools are carried by the gardener. Special materials are discussed on site.'),
            self::faq('Can I book for terrace or balcony gardens?', 'Yes — mention terrace access and size when booking so the right package is assigned.'),
            self::faq('How long does the service take?', 'Duration depends on garden size and scope. The gardener confirms timing after inspection or on arrival.'),
            self::faq('Is green waste removal included?', 'Clippings are collected and bagged. Municipal disposal is included only if agreed when booking.'),
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
