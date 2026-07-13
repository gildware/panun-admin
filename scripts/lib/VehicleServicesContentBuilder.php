<?php

class VehicleServicesContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Vehicle service');
        $subSlug = (string) ($service['sub_category_slug'] ?? '');
        $focus = self::serviceFocus($slug);

        return [
            'short_description' => "Professional {$name} in Srinagar by verified Panun Kaergar technicians — on-time visits, proper tools, and clear handover.",
            'intro' => self::introLine($name, $focus),
            'description' => "{$name} by Panun Kaergar brings a verified technician to your location. The vehicle is inspected first, the booked work is completed with proper tools and consumables where included, and you receive simple care tips before handover.",
            'card_highlights' => [
                self::highlight('verified', 'Verified Technicians', 'purple', 0),
                self::highlight('tools', 'Proper Equipment', 'blue', 1),
                self::highlight('sparkle', 'Neat Finish', 'green', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Choose the right package for {$name} and share your vehicle type."),
                self::step('verified', 'Technician assigned', 'A verified Panun Kaergar technician confirms the visit.', 'thumb'),
                self::step('tools', 'On-site service', self::sessionLine($focus), 'cover'),
                self::step('quality', 'Quality check', 'Work is reviewed and the vehicle is checked before handover.'),
                self::step('sparkle', 'Care tips', 'Basic upkeep guidance shared before the technician leaves.'),
            ],
            'perfect_for' => self::chips(self::idealFor($slug, $subSlug)),
            'whats_included' => self::included(self::includedItems($slug)),
            'good_to_know' => self::notes([
                'Share vehicle make, model, and parking location when booking.',
                'Ensure keys and access to the vehicle are available on site.',
                'Parts such as batteries, brake pads, or AC gas may be charged separately unless quoted.',
                'Major engine or transmission work may need garage follow-up after inspection.',
                'Notify at least 2 hours before for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Replacement parts unless agreed on site',
                'Towing or long-distance roadside recovery',
                'Insurance or RTO paperwork',
                'Paint or body dent repair',
                'Disposal of old parts unless quoted',
            ]),
            'faqs' => self::faqsFor($slug),
        ];
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        if ($variantTitle === 'Book Site Inspection' || $variantTitle === 'Book On Site Inspection') {
            return self::inspectionDescription($serviceName);
        }

        return "{$serviceName} — {$variantTitle}. Verified technician visit with proper tools and a neat finish.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        if (str_contains($serviceSlug, 'wash') || str_contains($serviceSlug, 'detailing') || str_contains($serviceSlug, 'cleaning')) {
            return 'Package covers labour and standard cleaning materials. Heavy stain removal or add-on treatments may be quoted on site.';
        }

        if (str_contains($serviceSlug, 'battery')) {
            return 'This ₹100 inspection fee will be adjusted against your final battery replacement bill if you proceed through Panun Kaergar. Battery cost is separate.';
        }

        return 'This ₹100 inspection fee will be adjusted against your final vehicle service bill if you proceed with the full service through Panun Kaergar.';
    }

    private static function inspectionDescription(string $serviceName): string
    {
        return "Verified technician inspects your vehicle for {$serviceName}, reviews condition and scope, and recommends the right plan on site.";
    }

    private static function serviceFocus(string $slug): string
    {
        return match (true) {
            str_contains($slug, 'exterior-car-wash') => 'foam wash, rinse, and exterior wipe-down',
            str_contains($slug, 'interior-car-cleaning') => 'vacuuming, dashboard wipe, and interior surface cleaning',
            str_contains($slug, 'full-car-detailing') => 'deep exterior and interior cleaning with finishing polish',
            str_contains($slug, 'general-car-inspection') => 'a full visual and basic functional check of your car',
            str_contains($slug, 'periodic-car-service') => 'oil check, filter inspection, fluid top-up, and basic service points',
            str_contains($slug, 'car-battery') => 'battery health testing and safe replacement if needed',
            str_contains($slug, 'car-ac') => 'AC cooling check, filter cleaning, and gas top-up if required',
            str_contains($slug, 'car-tyre') => 'puncture locating and tubeless or tube tyre repair',
            str_contains($slug, 'bike-general') => 'chain lube, brake check, oil top-up, and general tune-up',
            str_contains($slug, 'scooter-periodic') => 'scooter oil change, filter check, and periodic service points',
            str_contains($slug, 'bike-scooter-wash') => 'foam wash and wipe-down for your two-wheeler',
            str_contains($slug, 'two-wheeler-tyre') => 'puncture repair for bike or scooter tyres',
            str_contains($slug, 'two-wheeler-battery') => 'battery testing and replacement for two-wheelers',
            default => 'professional vehicle care',
        };
    }

    private static function introLine(string $name, string $focus): string
    {
        return "Reliable {$name} with verified technicians and a clean handover.";
    }

    private static function sessionLine(string $focus): string
    {
        return "The technician completes {$focus} using proper tools and safe work practices.";
    }

    private static function idealFor(string $slug, string $subSlug): array
    {
        return match ($subSlug) {
            'car-wash-detailing' => ['Daily drivers', 'Weekend cars', 'Pre-festival cleaning', 'Used car handover'],
            'car-repair-maintenance' => ['Periodic servicing', 'Weak battery', 'Low AC cooling', 'Flat or punctured tyre'],
            'bike-scooter-service' => ['Daily commute bikes', 'Family scooters', 'Pre-monsoon service', 'Winter battery check'],
            default => ['Cars', 'Bikes', 'Scooters', 'Home parking'],
        };
    }

    private static function includedItems(string $slug): array
    {
        return match (true) {
            str_contains($slug, 'exterior-car-wash') => [
                'Foam wash and rinse',
                'Exterior wipe-down',
                'Tyre and wheel face cleaning',
                'Basic drying finish',
            ],
            str_contains($slug, 'interior-car-cleaning') => [
                'Interior vacuuming',
                'Dashboard and console wipe',
                'Seat surface cleaning',
                'Floor mat cleaning',
            ],
            str_contains($slug, 'full-car-detailing') => [
                'Exterior wash and polish',
                'Interior deep clean',
                'Glass cleaning inside and out',
                'Tyre dressing finish',
            ],
            str_contains($slug, 'periodic-car-service') => [
                'Engine oil level check',
                'Air filter inspection',
                'Brake and fluid visual check',
                'Basic service report',
            ],
            str_contains($slug, 'bike-general') => [
                'Chain clean and lube',
                'Brake adjustment check',
                'Engine oil top-up or change as needed',
                'General tune-up points',
            ],
            str_contains($slug, 'bike-scooter-wash') => [
                'Foam wash',
                'Full body wipe-down',
                'Chain area rinse',
                'Basic drying',
            ],
            default => [
                'On-site inspection',
                'Diagnosis of reported issue',
                'Service recommendation',
                'Clear quote before major work',
            ],
        };
    }

    private static function faqsFor(string $slug): array
    {
        $common = [
            ['Do you come to my location?', 'Yes. Our technicians visit your home, office, or society parking where access is available.'],
            ['Are parts included in the price?', 'Labour packages are listed in the app. Parts such as batteries, filters, or gas are usually quoted separately on site.'],
            ['How long does the service take?', 'Most wash and basic services take 1–2 hours. Repairs depend on the issue found during inspection.'],
        ];

        $specific = match (true) {
            str_contains($slug, 'wash') || str_contains($slug, 'detailing') || str_contains($slug, 'cleaning') => [
                ['Do I need to arrange water and power?', 'Please confirm water access near the parking spot. Power is usually not required for exterior wash.'],
            ],
            str_contains($slug, 'battery') => [
                ['Can you test my battery on site?', 'Yes. The technician checks voltage and cranking health before recommending replacement.'],
            ],
            str_contains($slug, 'tyre') => [
                ['Can you fix tubeless tyre punctures?', 'Yes. Common tubeless punctures can often be repaired on site with a plug patch.'],
            ],
            str_contains($slug, 'ac') => [
                ['Will AC gas refill fix weak cooling?', 'Not always. The technician checks for leaks and filter issues before recommending gas top-up.'],
            ],
            default => [],
        };

        return array_merge($specific, $common);
    }

    private static function highlight(string $icon, string $title, string $color, int $sort): array
    {
        return ['icon' => $icon, 'title' => $title, 'color' => $color, 'sort_order' => $sort];
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
        return array_map(static fn (string $label, int $i): array => [
            'label' => $label,
            'sort_order' => $i,
        ], $items, array_keys($items));
    }

    private static function included(array $items): array
    {
        return array_map(static fn (string $title, int $i): array => [
            'icon' => 'check',
            'title' => $title,
            'sort_order' => $i,
        ], $items, array_keys($items));
    }

    private static function notes(array $items): array
    {
        return array_map(static fn (string $title, int $i): array => [
            'icon' => 'info',
            'title' => $title,
            'sort_order' => $i,
        ], $items, array_keys($items));
    }
}
