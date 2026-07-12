<?php

class PestControlContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Pest control');
        $subSlug = (string) ($service['sub_category_slug'] ?? '');

        return match ($slug) {
            'apartment-cockroach-control' => self::homeApartment($name),
            'bungalow-cockroach-control' => self::homeBungalow($name),
            'kitchen-cockroach-control' => self::homeKitchen($name),
            'partial-home-cockroach-control' => self::homePartial($name),
            'office-cockroach-control' => self::officeCockroach($name),
            'office-rodent-control' => self::officeRodent($name),
            'office-ant-control' => self::officeAnt($name),
            'restaurant-kitchen-pest-control' => self::restaurantKitchen($name),
            'restaurant-dining-pest-control' => self::restaurantDining($name),
            'restaurant-cockroach-control' => self::restaurantCockroach($name),
            default => self::generic($name, $subSlug),
        };
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        if ($variantTitle === 'Book On Site Inspection' || $variantTitle === 'Book Site Inspection') {
            return self::inspectionDescription($serviceName);
        }

        return "{$serviceName} for {$variantTitle} — spray treatment followed by gel treatment after two weeks.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        return match (true) {
            str_starts_with($serviceSlug, 'restaurant-') => 'This ₹100 inspection fee will be adjusted against your final restaurant pest control bill if you proceed with the full service through Panun Kaergar.',
            str_starts_with($serviceSlug, 'office-') => 'This ₹100 inspection fee will be adjusted against your final office pest control bill if you proceed with the full service through Panun Kaergar.',
            default => 'This ₹100 inspection fee will be adjusted against your final home pest control bill if you proceed with the full service through Panun Kaergar.',
        };
    }

    private static function inspectionDescription(string $serviceName): string
    {
        return "Verified pest control professional inspects your premises for {$serviceName}, identifies infestation points, and recommends the right treatment plan on site.";
    }

    private static function homeApartment(string $name): array
    {
        return self::homeBase($name, 'apartments and multi-BHK homes', [
            'Apartments',
            '1–4 BHK homes',
            'Rented flats',
            'Family homes',
            'Kitchen & bathroom infestations',
        ], [
            'Full apartment spray treatment (visit 1)',
            'Gel treatment in drains and hiding spots (visit 2)',
            'Kitchen, bathroom & bedroom coverage',
            '3-month warranty against recurring cockroaches',
            'Safety guidance before and after treatment',
        ]);
    }

    private static function homeBungalow(string $name): array
    {
        return self::homeBase($name, 'bungalows and large independent homes', [
            'Bungalows',
            'Villas',
            'Large independent homes',
            'Multi-floor residences',
            'Wide-area infestations',
        ], [
            'Whole-home spray treatment (visit 1)',
            'Targeted gel treatment after 2 weeks (visit 2)',
            'Coverage based on built-up area',
            'Drain and crevice treatment',
            '3-month warranty against recurring cockroaches',
        ]);
    }

    private static function homeKitchen(string $name): array
    {
        return self::homeBase($name, 'kitchens and wet areas', [
            'Kitchen-only treatment',
            'Bathroom & kitchen combo',
            'Studio kitchens',
            'Pantry pest issues',
            'Localized infestations',
        ], [
            'Kitchen & bathroom spray treatment',
            'Gel application in drains and corners',
            'Two-visit treatment with 2-week gap',
            'Advice on utensil removal and safety',
            '3-month warranty on treated areas',
        ]);
    }

    private static function homePartial(string $name): array
    {
        return self::homeBase($name, 'selected rooms and kitchen areas', [
            'Bedroom + kitchen packages',
            'Partial home treatment',
            'Budget-friendly coverage',
            'Targeted infestations',
            'Smaller homes',
        ], [
            'Spray treatment in booked rooms (visit 1)',
            'Gel follow-up after 2 weeks (visit 2)',
            'Bedroom and kitchen coverage per package',
            'Optional add-on areas as selected',
            '3-month warranty on treated zones',
        ]);
    }

    private static function homeBase(string $name, string $focus, array $idealFor, array $included): array
    {
        return [
            'short_description' => "Professional {$name} with a proven 2-visit spray and gel process. Safe for homes, with a 3-month warranty against recurring cockroaches.",
            'intro' => "Long-lasting cockroach protection for {$focus}.",
            'description' => "{$name} by Panun Kaergar uses a unique two-step process: a spray treatment to eliminate active pests, followed by a gel treatment two weeks later to break the breeding cycle in drains and hidden areas. Our verified technicians treat {$focus} with approved chemicals and share clear safety steps for your family.",
            'card_highlights' => [
                self::highlight('shield', '3-Month Warranty', 'green', 0),
                self::highlight('quality', '2-Visit Treatment', 'blue', 1),
                self::highlight('verified', 'Verified Technicians', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your package', "Choose your home size or coverage area for {$name} and share your address."),
                self::step('verified', 'Technician assigned', 'A verified Panun Kaergar pest control professional confirms your visit slot.'),
                self::step('tools', 'Visit 1 — Spray treatment', 'Liquid chemical is sprayed in infested and high-risk areas including kitchen, bathroom, and drains.', 'thumb'),
                self::step('calendar', '2-week gap', 'The gap allows eggs to hatch so the second visit can eliminate new pests.'),
                self::step('quality', 'Visit 2 — Gel treatment', 'Semi-solid gel is applied in drains and hiding spots to break the breeding cycle.', 'cover'),
                self::step('sparkle', 'Warranty & guidance', '3-month warranty begins after completion. Safety and after-care guidance shared.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Empty the kitchen before visit 1 — utensils and items should be removed from treated surfaces.',
                'Keep children and pets away from sprayed areas until surfaces are completely dry.',
                'Do not wash treated surfaces with soap and water after drying — use a dry cloth only.',
                'The second visit is essential even if cockroaches are not visible after visit 1.',
                'Notify at least 2 hours before for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Utensil removal and rearrangement (unless booked as add-on)',
                'Structural repairs or sealing of entry points',
                'Termite, rodent, or bed bug treatment outside booked scope',
                'Pest control for areas not included in your selected package',
                'Repeat treatment within warranty period (covered if recurrence qualifies)',
            ]),
            'faqs' => [
                self::faq('Why are two visits needed?', 'The first visit kills active cockroaches. The second visit, two weeks later, targets newly hatched eggs and hidden pests in drains.'),
                self::faq('Is the treatment safe for children and pets?', 'Yes — approved chemicals are used. Keep children and pets away from sprayed surfaces until they are dry.'),
                self::faq('Do I need to empty my kitchen?', 'Yes. Clear kitchen shelves and countertops before the first visit for effective treatment.'),
                self::faq('What warranty do you provide?', 'A 3-month warranty against recurring cockroaches after both visits are completed.'),
            ],
        ];
    }

    private static function officeCockroach(string $name): array
    {
        return self::commercialBase($name, 'office workspaces', 'cockroaches in workstations, pantries, and washrooms', [
            'Corporate offices',
            'Co-working spaces',
            'Small offices',
            'Back-office areas',
            'Pantry & washroom zones',
        ]);
    }

    private static function officeRodent(string $name): array
    {
        return self::commercialBase($name, 'office premises', 'rodents in storage, ceiling, and utility areas', [
            'Offices with rodent activity',
            'Storage rooms',
            'Server / utility areas',
            'Basement offices',
            'Food storage zones',
        ], 'rodent');
    }

    private static function officeAnt(string $name): array
    {
        return self::commercialBase($name, 'office spaces', 'ants in pantry, desks, and entry points', [
            'Office pantries',
            'Reception areas',
            'Open-plan offices',
            'Meeting rooms',
            'Food storage cupboards',
        ], 'ant');
    }

    private static function restaurantKitchen(string $name): array
    {
        return self::commercialBase($name, 'restaurant kitchens', 'cockroaches and pests in commercial kitchen zones', [
            'Restaurant kitchens',
            'Cloud kitchens',
            'Café prep areas',
            'Hotel back-of-house',
            'Storage & prep zones',
        ], 'kitchen');
    }

    private static function restaurantDining(string $name): array
    {
        return self::commercialBase($name, 'restaurant dining areas', 'pests in customer seating and dining zones', [
            'Dining halls',
            'Café seating',
            'Banquet areas',
            'Outdoor seating',
            'High-footfall restaurants',
        ], 'dining');
    }

    private static function restaurantCockroach(string $name): array
    {
        return self::commercialBase($name, 'full restaurant premises', 'cockroaches across kitchen, storage, and dining areas', [
            'Full-service restaurants',
            'Quick-service outlets',
            'Multi-zone eateries',
            'Large dining spaces',
            'Premises-wide infestations',
        ]);
    }

    private static function commercialBase(
        string $name,
        string $place,
        string $focus,
        array $idealFor,
        string $type = 'cockroach'
    ): array {
        $chemicalNote = str_contains($type, 'kitchen') || str_contains($type, 'dining')
            ? 'Food-safe approved chemicals suitable for F&B environments.'
            : 'Commercial-grade approved treatment chemicals.';

        return [
            'short_description' => "Professional {$name} for {$place}. Discreet scheduling, {$chemicalNote}",
            'intro' => "Reliable commercial pest treatment for {$place}.",
            'description' => "{$name} by Panun Kaergar helps {$place} stay hygienic and pest-free. Our verified professionals inspect {$focus}, apply targeted spray and gel treatments, and schedule visits to minimise disruption to your operations.",
            'card_highlights' => [
                self::highlight('building', 'Commercial Grade', 'blue', 0),
                self::highlight('shield', 'Hygienic Treatment', 'green', 1),
                self::highlight('verified', 'Verified Professionals', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book by size or zone', "Select the right package for your {$place} and preferred time slot."),
                self::step('verified', 'Professional assigned', 'A verified Panun Kaergar pest control expert confirms the visit.'),
                self::step('tools', 'Site inspection', 'Technician inspects infestation points, entry areas, and treatment zones.', 'thumb'),
                self::step('quality', 'Spray & gel treatment', 'Targeted chemical application in infested and high-risk commercial areas.', 'cover'),
                self::step('sparkle', 'Handover & compliance', 'Treatment summary and hygiene guidance shared for your team.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included([
                'On-site inspection before treatment',
                'Commercial-grade spray treatment',
                'Gel application in drains and hiding areas',
                'Coverage as per selected size / zone package',
                'Basic after-care and hygiene guidance',
            ]),
            'good_to_know' => self::notes([
                'Inform staff about the treatment schedule in advance.',
                'Kitchen and food areas may need brief prep before the visit.',
                'Avoid contact with treated surfaces until completely dry.',
                'Follow-up visit may be recommended for severe infestations.',
                'Notify at least 2 hours before for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Structural proofing or sealing of entry points',
                'Pest types outside the booked service scope',
                'Deep cleaning or grease removal before treatment',
                'Equipment relocation unless agreed on site',
                'Government compliance certification (if separately required)',
            ]),
            'faqs' => [
                self::faq('Can treatment be done after business hours?', 'Yes — mention your preferred timing when booking and we will try to schedule accordingly.'),
                self::faq('Is it safe for food preparation areas?', 'Food-safe approved chemicals are used for restaurant and kitchen treatments. Follow the pre and post-treatment guidance shared by the technician.'),
                self::faq('How long does commercial treatment take?', 'Duration depends on premises size. Most standard office or restaurant packages are completed within the booked slot.'),
                self::faq('Do you provide repeat visits?', 'A follow-up visit may be included or recommended based on infestation severity. Confirm scope when booking.'),
            ],
        ];
    }

    private static function generic(string $name, string $subSlug): array
    {
        $context = match ($subSlug) {
            'office-pest-control' => 'offices and workplaces',
            'restaurant-pest-control' => 'restaurants and food businesses',
            default => 'homes and residential spaces',
        };

        return self::homeBase($name, $context, [$name, $context], [
            'On-site inspection',
            'Targeted pest treatment',
            'Professional handover',
        ]);
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
