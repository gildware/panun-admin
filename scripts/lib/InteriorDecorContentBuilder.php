<?php

class InteriorDecorContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Interior decor consultation');

        return match ($slug) {
            'room-layout-space-planning' => self::roomLayout($name),
            'home-makeover-consultation' => self::homeMakeover($name),
            'curtains-soft-furnishing-advice' => self::softFurnishing($name),
            'office-shop-interior-styling' => self::commercialStyling($name),
            default => self::generic($name),
        };
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        if (in_array($variantTitle, ['Book Site Visit', 'Book On Site Inspection', 'Book Site Inspection'], true)) {
            return "Verified interior decor specialist visits your space for {$serviceName}, reviews layout and goals, and shares practical recommendations on site.";
        }

        return "{$serviceName} — {$variantTitle}.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        return 'This ₹100 visit fee will be adjusted against your final interior decor package if you proceed with follow-up work through Panun Kaergar.';
    }

    private static function roomLayout(string $name): array
    {
        return self::base($name, 'homes and flats that need better furniture flow', [
            'Living rooms',
            'Bedrooms',
            'New flats',
            'Awkward layouts',
            'Guest room planning',
        ], [
            'On-site room walkthrough',
            'Furniture placement suggestions',
            'Traffic flow and spacing advice',
            'Quick layout sketch or notes',
            'Priority list for next steps',
        ]);
    }

    private static function homeMakeover(string $name): array
    {
        return self::base($name, 'full-room or whole-home refreshes', [
            'Home makeovers',
            'Pre-renovation planning',
            'Colour updates',
            'Furniture refresh',
            'Before painting or carpentry',
        ], [
            'Colour and theme suggestions',
            'Furniture and decor coordination advice',
            'Budget-friendly makeover plan',
            'What to buy now vs later',
            'Links to related Panun Kaergar services',
        ]);
    }

    private static function softFurnishing(string $name): array
    {
        return self::base($name, 'curtains, cushions, and soft finishes', [
            'Curtain and blind choices',
            'Cushion and upholstery picks',
            'Window treatment planning',
            'Living room soft refresh',
            'Bedroom textile updates',
        ], [
            'Fabric and style recommendations',
            'Curtain length and mounting advice',
            'Colour matching with walls and furniture',
            'Seasonal and climate-friendly tips for Kashmir',
            'Shopping list for soft furnishings',
        ]);
    }

    private static function commercialStyling(string $name): array
    {
        return self::base($name, 'offices, shops, and small businesses', [
            'Home offices',
            'Retail shops',
            'Clinics and salons',
            'Reception areas',
            'Customer-facing spaces',
        ], [
            'Layout and display suggestions',
            'Brand-appropriate colour and theme advice',
            'Furniture and fixture placement',
            'Customer flow and visibility tips',
            'Practical next steps for setup',
        ], 'office or shop');
    }

    private static function generic(string $name): array
    {
        return self::base($name, 'homes and workspaces', ['Homes', 'Flats', 'Offices', 'Shops'], [
            'On-site consultation',
            'Practical styling advice',
            'Written or verbal recommendations',
        ]);
    }

    private static function base(string $name, string $focus, array $idealFor, array $included, string $space = 'home'): array
    {
        return [
            'short_description' => "Professional {$name} in Srinagar — practical advice from verified Panun Kaergar decor specialists.",
            'intro' => "Expert {$name} with on-site guidance and a clear plan for your {$space}.",
            'description' => "{$name} by Panun Kaergar connects you with a verified interior decor specialist who visits your {$space}, understands your goals and budget, and shares actionable styling advice you can use right away.",
            'card_highlights' => [
                ['icon' => 'verified', 'text' => 'Verified Specialists', 'color' => 'purple', 'sort_order' => 0],
                ['icon' => 'home', 'text' => 'On-Site Visit', 'color' => 'blue', 'sort_order' => 1],
                ['icon' => 'sparkle', 'text' => 'Practical Advice', 'color' => 'green', 'sort_order' => 2],
            ],
            'process_steps' => [
                ['icon' => 'calendar', 'title' => 'Book your visit', 'description' => "Choose {$name} and pick a convenient time slot."],
                ['icon' => 'verified', 'title' => 'Specialist assigned', 'description' => 'A verified Panun Kaergar decor specialist confirms the visit.', 'image' => 'thumb'],
                ['icon' => 'home', 'title' => 'On-site consultation', 'description' => "Walk through your space and discuss layout, style, and priorities for {$focus}.", 'image' => 'cover'],
                ['icon' => 'quality', 'title' => 'Recommendations shared', 'description' => 'You receive clear next steps and styling suggestions before the visit ends.'],
                ['icon' => 'sparkle', 'title' => 'Optional follow-up', 'description' => 'Book painting, carpentry, or cleaning on Panun Kaergar when you are ready to execute.'],
            ],
            'perfect_for' => array_map(static fn (string $item): array => ['text' => $item], $idealFor),
            'whats_included' => array_map(static fn (string $item): array => ['text' => $item], $included),
            'good_to_know' => array_map(static fn (string $item): array => ['text' => $item], [
                'Share room photos and rough dimensions when booking for faster planning.',
                'Be home or available to walk through the space during the visit.',
                'Consultation covers advice and planning — materials and labour are separate unless quoted.',
                'For painting, false ceiling, or carpentry work, book those services separately on Panun Kaergar.',
                'Notify at least 2 hours before for cancellation or rescheduling.',
            ]),
            'whats_not_included' => array_map(static fn (string $item): array => ['text' => $item], [
                'Furniture, curtains, paint, or decor product purchases',
                'Carpentry, painting, electrical, or masonry execution',
                '3D renders or architectural drawings unless agreed separately',
                'Contractor supervision or full project management',
            ]),
            'faqs' => self::faqsFor($name),
        ];
    }

    private static function faqsFor(string $name): array
    {
        return [
            ["What does {$name} include?", 'A specialist visits your space, reviews layout and goals, and shares practical styling advice. Product purchases and execution work are booked separately.'],
            ['Is this only advice or do you also do the work?', 'Most bookings start with consultation. For painting, carpentry, cleaning, and similar work, you can book those categories on Panun Kaergar after your decor visit.'],
            ['How much does interior decor consultation cost in Srinagar?', 'The site visit starts at ₹100. Full packages or follow-up coordination are quoted on site based on scope.'],
            ['How do I prepare for the visit?', 'Note your budget, take photos of each room, and list what you want to improve — lighting, furniture layout, colours, or soft furnishings.'],
        ];
    }
}
