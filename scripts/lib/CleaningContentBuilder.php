<?php

class CleaningContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Cleaning');

        return match ($slug) {
            'bathroom-cleaning' => self::service($name, 'bathrooms and washrooms', [
                'Home bathrooms', 'Washrooms', 'Standard refresh', 'Intense deep clean', 'Tile & fixture buildup',
            ], [
                'Bathroom fixtures, tiles, and floor cleaning within booked size',
                'Sink, taps, toilet exterior, and mirror wipe-down',
                'Visible soap scum, limescale, and stain reduction',
                'Final quality check and neat handover',
            ], [
                'Permanent stain or grout color restoration',
                'Plumbing repair or fixture replacement',
                'Areas outside the booked bathroom size',
            ]),
            'room-cleaning' => self::service($name, 'home rooms', [
                'Unfurnished rooms', 'Furnished rooms', 'Move-in refresh', 'Routine room cleaning',
            ], [
                'Floor mopping and surface dusting within room scope',
                'Furniture dusting for furnished bookings',
                'Visible dirt and cobweb reduction',
                'Final walk-through before handover',
            ], [
                'Deep sofa or mattress cleaning (book separately)',
                'Window deep cleaning outside room scope',
                'Packing, moving, or disposal',
            ]),
            'shop-cleaning' => self::service($name, 'retail and shop floors', [
                'Small shops', 'Retail counters', 'Showrooms up to booked area', 'Daily or weekly refresh',
            ], [
                'Floor and surface cleaning within booked shop area',
                'Counter and display-area dusting within scope',
                'Visible dirt and footprint reduction',
                'Neat handover ready for business hours',
            ], [
                'Warehouse or storage areas outside booked sq ft',
                'Glass facade work outside shop cleaning scope',
                'Stock rearrangement or inventory handling',
            ]),
            'kitchen-cleaning' => self::service($name, 'home kitchens', [
                'Home kitchens', 'Standard kitchen clean', 'Intense grease clean', 'Cupboard exteriors',
            ], [
                'Countertops, sink, stove exterior, and floor cleaning',
                'Visible grease and spill reduction within scope',
                'Cabinet exterior wipe-down',
                'Final quality check',
            ], [
                'Chimney deep cleaning (book separately)',
                'Inside refrigerator or oven deep clean (book separately)',
                'Permanent burn marks or damaged surfaces',
            ]),
            'pantry-cleaning' => self::service($name, 'office pantries', [
                'Office pantries', 'Staff tea points', 'Standard pantry refresh', 'Intense pantry clean',
            ], [
                'Pantry counters, sink, and floor cleaning',
                'Appliance exterior wipe-down within scope',
                'Visible spills and grease reduction',
                'Neat handover for office use',
            ], [
                'Full restaurant kitchen cleaning',
                'Deep appliance interiors outside pantry scope',
                'Stock restocking or disposal',
            ]),
            'restaurant-kitchen-cleaning' => self::service($name, 'restaurant kitchens', [
                'Restaurant kitchens', 'Commercial cook lines', 'Standard service clean', 'Intense grease clean',
            ], [
                'Cook line, counters, and floor cleaning within scope',
                'Visible grease and food residue reduction',
                'Sink and prep-area wipe-down',
                'Final hygiene-focused handover',
            ], [
                'Hood and duct deep cleaning beyond booked scope',
                'Equipment repair or servicing',
                'Pest control or structural repair',
            ]),
            'windows-cleaning' => self::service($name, 'glass doors and windows', [
                'Glass doors', 'Windows without nets', 'Windows with nets', 'Shopfront glass',
            ], [
                'Glass door and window cleaning within booked type',
                'Frame wipe-down within reachable scope',
                'Visible streaks and dust reduction',
                'Final clarity check',
            ], [
                'High-rise exterior glass requiring scaffolding',
                'Broken glass repair or sealant work',
                'Interior curtain or blind cleaning',
            ]),
            'floor-cleaning' => self::service($name, 'tile and marble floors', [
                'Tile floors', 'Marble floors', 'Mopping up to 500 sq ft', 'Deep scrub up to 500 sq ft',
            ], [
                'Floor mopping or deep scrub within booked method and area',
                'Visible dirt and footprint reduction',
                'Edge and corner attention within scope',
                'Final dry and neat finish',
            ], [
                'Areas beyond 500 sq ft without extra booking',
                'Stone polishing or restoration chemicals beyond scope',
                'Carpet cleaning (book separately)',
            ]),
            'sofa-cleaning' => self::service($name, 'leather and fabric sofas', [
                'Leather sofas', 'Fabric sofas', '5-seater sofas', '7-seater sofas',
            ], [
                'Sofa surface cleaning for booked material and seater size',
                'Cushion and seating area attention within scope',
                'Visible dust, stains, and odor reduction',
                'Final finish check',
            ], [
                'Guaranteed removal of every old or set stain',
                'Leather repair, re-dye, or reupholstery',
                'Mattress or carpet cleaning',
            ]),
            'office-chair-cleaning' => self::service($name, 'office seating', [
                'Executive chairs', 'Visitor chairs', 'Workstation chairs', 'Office seating refresh',
            ], [
                'Chair fabric/leather cleaning for booked chair type',
                'Seat, back, and armrest attention within scope',
                'Visible dust and stain reduction',
                'Ready-to-use handover',
            ], [
                'Chair mechanism or wheel repair',
                'Full sofa cleaning',
                'Bulk warehouse seating outside booking count',
            ]),
            'mattress-cleaning' => self::service($name, 'home mattresses', [
                'Single mattresses', 'Double mattresses', 'Bedroom hygiene refresh', 'Dust-mite conscious clean',
            ], [
                'Mattress surface cleaning for booked size',
                'Visible dust and stain reduction',
                'Both-side attention where practical',
                'Final freshness check',
            ], [
                'Guaranteed allergy cure claims',
                'Mattress repair or replacement',
                'Bed frame or headboard deep cleaning unless booked',
            ]),
            'carpet-cleaning' => self::service($name, 'home and office carpets', [
                'Small rugs', 'Medium carpets', 'Larger carpet areas', 'Floor rug refresh',
            ], [
                'Carpet cleaning within booked size',
                'Visible dirt and stain reduction',
                'Surface grooming and finish check',
                'Neat handover after drying guidance',
            ], [
                'Permanent dye bleed or fiber damage reversal',
                'Wall-to-wall flooring beyond booked sq ft',
                'Upholstery cleaning outside carpet scope',
            ]),
            'fan-cleaning' => self::service($name, 'ceiling and pedestal fans', [
                'Ceiling fans', 'Table fans', 'Pedestal fans', 'Dust buildup refresh',
            ], [
                'Fan blade and body cleaning for booked fan type',
                'Visible dust and grime reduction',
                'Safe wipe-down of reachable parts',
                'Ready-to-use handover',
            ], [
                'Fan motor repair or replacement',
                'Electrical fault fixing',
                'AC or cooler cleaning',
            ]),
            'fridge-cleaning' => self::service($name, 'refrigerators', [
                'Home refrigerators', 'Standard fridge clean', 'Intense fridge clean', 'Shelf & gasket refresh',
            ], [
                'Interior shelves, drawers, and door gasket cleaning',
                'Exterior wipe-down within scope',
                'Visible spill and odor reduction',
                'Final freshness check',
            ], [
                'Gas refill or compressor repair',
                'Defrosting of frozen-solid freezers beyond practical scope',
                'Food disposal or restocking',
            ]),
            'oven-microwave-cleaning' => self::service($name, 'ovens and microwaves', [
                'Home microwaves', 'Ovens', 'Standard clean', 'Intense grease clean',
            ], [
                'Interior cavity and turntable/tray cleaning within scope',
                'Exterior wipe-down',
                'Visible grease and food residue reduction',
                'Final odor and finish check',
            ], [
                'Heating element or electrical repair',
                'Commercial bakery oven deep overhaul',
                'Permanent burn mark removal guarantee',
            ]),
            'chimney-cleaning' => self::service($name, 'kitchen chimneys', [
                'Home chimneys', 'Standard chimney clean', 'Intense grease clean', 'Filter refresh',
            ], [
                'Chimney filter and canopy cleaning within booked intensity',
                'Visible grease reduction on reachable parts',
                'Exterior wipe-down within scope',
                'Final check before handover',
            ], [
                'Duct replacement or fabrication',
                'Motor repair or spare-part replacement',
                'Full kitchen deep clean (book separately)',
            ]),
            'water-tank-cleaning' => self::service($name, 'overhead and storage water tanks', [
                '500 litre tanks', '1000 litre tanks', 'Home water tanks', 'Hygiene refresh',
            ], [
                'Tank interior cleaning for booked capacity',
                'Sludge and visible sediment reduction',
                'Rinse and readiness guidance',
                'Final hygiene-focused handover',
            ], [
                'Tank repair, welding, or replacement',
                'Pipeline flushing beyond tank scope',
                'Water testing lab certificates unless arranged separately',
            ]),
            'post-construction-cleaning-service' => self::service($name, 'post-renovation and construction sites', [
                'Homes after renovation', 'Offices & shops', 'Hotels, restaurants & clinics', 'Debris dust clean-up',
            ], [
                'Dust, debris, and construction residue cleaning within booked space type and size',
                'Floor, wall, and fixture wipe-down within scope',
                'Site inspection option when booked',
                'Final ready-to-use handover',
            ], [
                'Civil repair, painting, or polishing',
                'Hazardous material handling',
                'Areas beyond booked square footage without confirmation',
            ]),
            default => self::service($name, 'homes and commercial spaces', [
                'Routine refresh', 'Deep cleaning days', 'Family homes', 'Commercial spaces', $name,
            ], [
                'Area review before cleaning starts',
                'Targeted cleaning within booked scope',
                'Visible dust, grease, and stain reduction',
                'Final wipe-down and quality check',
                'Neat handover',
            ], [
                'Permanent stain reversal or material repair',
                'Structural repair, pest control, or polishing work',
                'Any extra area not confirmed in the booking',
            ]),
        };
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        if (stripos($variantTitle, 'inspection') !== false) {
            return "Verified Panun Kaergar professional inspects the site for {$serviceName}, confirms practical scope, and advises the right cleaning plan on site.";
        }

        return "{$serviceName} — {$variantTitle}. Verified team cleans within the booked variation scope and completes a neat handover.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        if ($serviceSlug === 'post-construction-cleaning-service') {
            return 'Inspection fee is adjusted against the final post-construction cleaning bill if you proceed with the full service through Panun Kaergar.';
        }

        return 'Final price is based on the selected variation. Extra areas or intensity upgrades need confirmation before work starts.';
    }

    private static function service(
        string $name,
        string $focus,
        array $idealFor,
        array $included,
        array $excluded
    ): array {
        return [
            'short_description' => "Thorough {$name} by trusted Panun Kaergar professionals for cleaner spaces, fresher surfaces, and a polished finish.",
            'intro' => "Detailed {$name} with careful cleaning steps and a fresher handover for {$focus}.",
            'description' => "{$name} by Panun Kaergar is structured for customers who want a cleaner, fresher space with dependable service standards. The team reviews the area first, works through visible dust, grease, stains, and buildup across {$focus}, then completes a final quality check so the cleaned space is ready for everyday use.",
            'card_highlights' => [
                self::highlight('sparkle', 'Deep Refresh', 'blue', 0),
                self::highlight('quality', 'Neat Finish', 'green', 1),
                self::highlight('verified', 'Trusted Team', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Share space details and any problem areas for {$name}."),
                self::step('verified', 'Team assigned', 'A Panun Kaergar team confirms the visit and arrives with the required cleaning tools and supplies.'),
                self::step('location', 'Area review', 'The team reviews the cleaning area, priorities, and visible buildup before work begins.', 'thumb'),
                self::step('sparkle', 'Cleaning in progress', 'Targeted cleaning is carried out carefully with attention to hygiene and visible finish.'),
                self::step('quality', 'Final check & handover', 'The cleaned area is reviewed, touched up if needed, and handed over neatly.', 'cover'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Please remove fragile or highly valuable personal items from the work area before the team arrives.',
                'Heavily neglected spaces may need extra time or additional scope confirmation on site.',
                'Permanent stains, old damage, or material wear may not be fully reversible.',
                'Special restoration, pest control, or repair work is outside standard cleaning scope.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => [
                self::faq("What is covered in {$name}?", 'The team completes the booked cleaning scope after a quick area review and focuses on visible dirt, grease, dust, and overall freshness.'),
                self::faq('Do I need to provide cleaning material?', 'No. Panun Kaergar teams arrive with the required standard tools and supplies unless a special product is discussed in advance.'),
                self::faq('Will all stains be removed completely?', 'Not always. Results depend on the material condition, stain age, and buildup level, but the team will aim for the best possible finish within scope.'),
                self::faq('Is the area checked before the team leaves?', 'Yes. A final walk-through is done before handover.'),
            ],
        ];
    }

    private static function highlight(string $icon, string $text, string $color, int $sortOrder): array
    {
        return [
            'icon' => $icon,
            'text' => $text,
            'color' => $color,
            'sort_order' => $sortOrder,
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
        $items = [];
        $icons = ['home', 'building', 'sparkle', 'tools', 'quality', 'check', 'calendar', 'location'];
        foreach (array_values($labels) as $index => $label) {
            $items[] = [
                'icon' => $icons[$index % count($icons)],
                'text' => $label,
                'sort_order' => $index,
            ];
        }

        return $items;
    }

    private static function included(array $titles): array
    {
        $items = [];
        $icons = ['tools', 'check', 'quality', 'sparkle', 'verified', 'location', 'home', 'calendar'];
        foreach (array_values($titles) as $index => $title) {
            $items[] = [
                'icon' => $icons[$index % count($icons)],
                'title' => $title,
                'sort_order' => $index,
            ];
        }

        return $items;
    }

    private static function notes(array $titles): array
    {
        $items = [];
        $icons = ['check', 'home', 'tools', 'quality', 'calendar', 'sparkle', 'verified', 'location'];
        foreach (array_values($titles) as $index => $title) {
            $items[] = [
                'icon' => $icons[$index % count($icons)],
                'title' => $title,
                'sort_order' => $index,
            ];
        }

        return $items;
    }

    private static function faq(string $question, string $answer): array
    {
        return [$question, $answer];
    }
}
