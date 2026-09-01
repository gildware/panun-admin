<?php

class CarpentryContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Carpentry');

        return match ($slug) {
            'door-installation' => self::install($name, 'wooden doors', [
                'New homes', 'Door replacement', 'Main entrance', 'Bedroom doors', 'Office cabins', 'Pre-purchased doors',
            ], [
                'On-site inspection of opening and frame',
                'Door mounting and level alignment',
                'Hinge installation and gap adjustment',
                'Latch and lock alignment',
                'Open/close testing and work-area cleanup',
            ], [
                'Cost of door, frame, lockset, or hardware',
                'Major frame rebuilding or civil/concrete work',
                'Custom door fabrication from raw timber',
                'Painting, polishing, or veneer finishing',
                'Old door disposal unless agreed on site',
            ]),
            'window-installation' => self::install($name, 'wooden windows', [
                'New window openings', 'Window replacement', 'Standard hinged windows', 'Sliding windows', 'Home renovations',
            ], [
                'On-site check of opening and frame condition',
                'Window unit fitting and alignment',
                'Hardware and track adjustment within scope',
                'Smooth open/close testing',
                'Work-area cleanup after fitting',
            ], [
                'Cost of window unit, glass, or hardware',
                'Masonry, plastering, or waterproofing work',
                'Custom window fabrication unless booked under making',
                'Painting or polishing',
                'High-rise exterior work requiring scaffolding',
            ]),
            'bed-installation' => self::install($name, 'single and double beds', [
                'Single bed assembly', 'Double bed assembly', 'Bed uninstall', 'Uninstall + reinstall', 'Flat-pack beds',
            ], [
                'Bed frame assembly or uninstall for booked size',
                'Secure fixing of joints and hardware within scope',
                'Stability check after install',
                'Basic alignment of headboard/footboard where applicable',
                'Work-area tidy-up after service',
            ], [
                'Cost of bed, mattress, or spare hardware',
                'Custom bed fabrication (book Bed Making)',
                'Mattress cleaning or disposal',
                'Wall mounting of heavy units outside bed scope',
                'Painting or polishing',
            ]),
            'table-installation' => self::install($name, 'tables and study desks', [
                'Dining tables', 'Study tables', 'Office desks', 'Table uninstall', 'Install + uninstall',
            ], [
                'Table assembly or uninstall within booked variation',
                'Leg, top, and hardware fixing',
                'Level and stability check',
                'Basic adjustment of fittings',
                'Work-area cleanup',
            ], [
                'Cost of table or hardware parts',
                'Custom table fabrication (book Table Making)',
                'Glass top cutting or replacement',
                'Painting or polishing',
                'Moving furniture between floors unless agreed',
            ]),
            'curtain-rod-installation' => self::install($name, 'curtain rods, double rods, and tracks', [
                'New curtain rods', 'Double rods', 'Curtain tracks', 'Rod uninstall', 'Uninstall and reinstall',
            ], [
                'On-site check of wall, window width, and bracket positions',
                'Secure fixing of brackets and anchors for the booked variation',
                'Rod or track alignment and centre support where needed',
                'Hang test and open/close check after fitting',
                'Work-area tidy-up after installation',
            ], [
                'Cost of rod, track, brackets, or curtains',
                'Fabric stitching, lining, or curtain making',
                'Major plaster repair or masonry rebuild around failed holes',
                'Motorised or smart curtain systems unless agreed on site',
                'Interior decor advice (book Curtains & Soft Furnishing Advice)',
            ]),
            'wooden-flooring-install' => self::install($name, 'laminate, engineered, and solid wooden floors', [
                'Laminate / click-lock floors', 'Engineered wood', 'Solid wood planks', 'Flooring with skirting', 'Home renovations',
            ], [
                'On-site floor check and layout review',
                'Labour for the booked flooring variation',
                'Basic underlay and plank fitting within scope',
                'Skirting labour when that variation is booked',
                'Work-area tidy-up and handover tips',
            ], [
                'Cost of planks, underlay, skirting, or adhesives',
                'Furniture shifting unless agreed on site',
                'Subfloor civil repair, levelling compound, or damp treatment',
                'Tile flooring (book Masonry) or wall wooden panels',
            ]),
            'bed-making' => self::making($name, 'custom wooden beds', [
                'Custom single or double beds', 'Space-saving designs', 'Bedroom woodwork', 'Made-to-measure frames',
            ]),
            'wardrobe-making' => self::making($name, 'custom wardrobes', [
                'Bedroom wardrobes', 'Sliding or hinged designs', 'Storage optimization', 'Made-to-measure units',
            ]),
            'almirah-making' => self::making($name, 'custom almirahs', [
                'Storage almirahs', 'Bedroom cupboards', 'Compact homes', 'Made-to-measure cabinets',
            ]),
            'table-making' => self::making($name, 'custom tables', [
                'Dining tables', 'Study desks', 'Shop counters', 'Made-to-measure tabletops',
            ]),
            'shop-shelves-making' => self::making($name, 'shop shelving', [
                'Retail shelves', 'Display racks', 'Storage bays', 'Shop fit-outs',
            ]),
            'kitchen-cabinet-making' => self::making($name, 'kitchen cabinets', [
                'Modular-style cabinets', 'Kitchen renovations', 'Cupboard replacement', 'Made-to-measure kitchens',
            ]),
            'custom-carpentry-work' => self::making($name, 'bespoke woodwork', [
                'Custom woodwork', 'Unique furniture pieces', 'Interior carpentry', 'Specialty joinery',
            ]),
            'door-repair' => self::repair($name, 'doors and door fittings', [
                'Sticking doors', 'Loose hinges', 'Latch issues', 'Alignment problems', 'Minor panel damage',
            ], [
                'On-site inspection and fault diagnosis',
                'Hinge tightening, realignment, or replacement labour',
                'Latch/lock alignment within carpentry scope',
                'Minor panel or gap correction',
                'Function test and cleanup',
            ], [
                'New door or premium hardware cost',
                'Full door replacement (book Door Installation)',
                'Custom fabrication or major frame rebuild',
                'Painting, polishing, or smart-lock electrical work',
            ]),
            'wooden-flooring-repair' => self::repair($name, 'wooden and laminate floor faults', [
                'Loose or lifting planks', 'Scratches and dents', 'Gaps and clicking', 'Water-damaged boards',
            ], [
                'On-site diagnosis of the floor fault',
                'Repair labour for the booked symptom when practical',
                'Plank refit or gap attention within scope',
                'Walk-test after repair',
                'Work-area cleanup',
            ], [
                'Cost of replacement planks, underlay, or polish chemicals',
                'Full floor re-lay (book Wooden Flooring Install)',
                'Subfloor civil repair or waterproofing',
                'Furniture shifting unless agreed on site',
            ]),
            'furniture-repair' => self::repair($name, 'wooden furniture', [
                'Loose joints', 'Wobbly chairs/tables', 'Broken parts', 'Frame reinforcement', 'Home furniture',
            ], [
                'On-site inspection of damaged furniture',
                'Joint tightening and structural reinforcement within scope',
                'Minor part repair or refit labour',
                'Stability check after repair',
                'Work-area cleanup',
            ], [
                'Full furniture replacement',
                'Upholstery or fabric work',
                'Custom remaking of entire pieces',
                'Polishing or refinishing unless agreed',
            ]),
            'kitchen-cabinet-repair' => self::repair($name, 'kitchen cabinets', [
                'Loose hinges', 'Sagging doors', 'Damaged shelves', 'Drawer issues', 'Kitchen cupboards',
            ], [
                'On-site inspection of cabinets and hardware',
                'Hinge, catch, and alignment repair within scope',
                'Shelf and door adjustment labour',
                'Function test of doors and drawers',
                'Cleanup of work area',
            ], [
                'Full kitchen remodel or new cabinet making',
                'Cost of new boards or premium hardware',
                'Plumbing or electrical work',
                'Painting or laminate replacement unless agreed',
            ]),
            'wardrobe-repair' => self::repair($name, 'wardrobes and cupboards', [
                'Sliding track issues', 'Loose hinges', 'Damaged panels', 'Shelf repairs', 'Bedroom wardrobes',
            ], [
                'On-site inspection of wardrobe condition',
                'Hinge, track, and alignment repair within scope',
                'Panel and shelf adjustment labour',
                'Smooth open/close testing',
                'Work-area cleanup',
            ], [
                'Full wardrobe replacement or custom making',
                'Cost of new panels or channels',
                'Interior decoration or lighting work',
                'Painting or polishing unless agreed',
            ]),
            'window-repair' => self::repair($name, 'wooden windows', [
                'Sticking sashes', 'Loose fittings', 'Frame gaps', 'Track issues', 'Home windows',
            ], [
                'On-site inspection of window and frame',
                'Alignment and hardware repair within scope',
                'Minor frame or sash correction',
                'Open/close and latch testing',
                'Cleanup after repair',
            ], [
                'New window unit cost',
                'Glass cutting or glazing beyond carpentry labour',
                'Masonry or waterproofing repairs',
                'Full window replacement (book Window Installation)',
            ]),
            'other-carpentry-repair' => self::repair($name, 'general woodwork', [
                'Misc wood repairs', 'Shelves and frames', 'Trim and skirting', 'Small joinery fixes', 'Custom diagnosis',
            ], [
                'On-site inspection and scope confirmation',
                'Targeted carpentry repair within agreed scope',
                'Hardware tightening or minor wood fixes',
                'Function and finish check',
                'Work-area cleanup',
            ], [
                'Major fabrication or new furniture making',
                'Civil, electrical, or plumbing work',
                'Materials cost unless supplied separately',
                'Jobs outside confirmed on-site scope',
            ]),
            'roof-installation' => self::roof($name, 'wooden roof installation', [
                'New wooden roofs', 'Roof structure upgrades', 'Home extensions', 'Timber roofing projects',
            ], true),
            'roof-repair' => self::roof($name, 'wooden roof repairs', [
                'Damaged roof sections', 'Loose supports', 'Leak-related wood repair', 'Structural timber fixes',
            ], false),
            default => self::install($name, 'carpentry work', [
                'Home woodwork', 'Installation jobs', 'Repair visits', 'Custom carpentry',
            ], [
                'On-site review before work starts',
                'Carpentry labour within booked scope',
                'Basic alignment and hardware attention',
                'Function check and neat handover',
            ], [
                'Material cost unless agreed separately',
                'Civil or electrical work',
                'Scope outside the confirmed booking',
            ]),
        };
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        if (stripos($variantTitle, 'inspection') !== false) {
            return "Verified Panun Kaergar carpenter inspects the site for {$serviceName}, confirms practical scope, and advises the right plan and estimate on site.";
        }

        return "{$serviceName} — {$variantTitle}. Verified carpenter completes the booked variation and hands over neatly.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        $inspectionSlugs = [
            'bed-making', 'wardrobe-making', 'almirah-making', 'table-making',
            'shop-shelves-making', 'kitchen-cabinet-making', 'custom-carpentry-work',
            'door-repair', 'furniture-repair', 'kitchen-cabinet-repair', 'wardrobe-repair',
            'window-repair', 'other-carpentry-repair', 'roof-installation', 'roof-repair',
        ];

        if (in_array($serviceSlug, $inspectionSlugs, true)) {
            return 'This ₹50 inspection fee is adjusted against your final carpentry bill if you proceed with the full service through Panun Kaergar.';
        }

        if ($serviceSlug === 'wooden-flooring-repair') {
            return 'Inspection or visit fee may be adjusted against your final carpentry bill if you proceed with the full service through Panun Kaergar. Planks, underlay, and extra materials are extra unless listed.';
        }

        if ($serviceSlug === 'wooden-flooring-install') {
            return 'Final price is based on the selected variation. Cost of planks, underlay, skirting, and extra materials need confirmation before work starts.';
        }

        return 'Final price is based on the selected variation. Extra work or materials need confirmation before work starts.';
    }

    private static function install(
        string $name,
        string $focus,
        array $idealFor,
        array $included,
        array $excluded
    ): array {
        return [
            'short_description' => "Expert {$name} by verified Panun Kaergar carpenters at your home.",
            'intro' => "Precise {$name} with careful fitting, alignment, and a clean handover for {$focus}.",
            'description' => "{$name} by Panun Kaergar connects you with verified carpenters for {$focus}. The professional reviews the site, completes the booked installation variation with proper alignment and hardware attention, then tests and hands over neatly. For booking help, call or WhatsApp Panun Kaergar support from the app or website.",
            'card_highlights' => [
                self::highlight('tools', 'Expert Installation', 'blue', 0),
                self::highlight('quality', 'Level-Aligned Fit', 'green', 1),
                self::highlight('verified', 'Verified Carpenters', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Choose the right variation and share photos or sizes for {$name}."),
                self::step('verified', 'Carpenter assigned', 'A verified Panun Kaergar carpenter confirms the visit and arrives with tools.'),
                self::step('location', 'On-site check', 'Opening, frame, or furniture condition is reviewed before work begins.', 'thumb'),
                self::step('tools', 'Installation work', 'Fitting, alignment, and hardware work are completed for the booked variation.', 'cover'),
                self::step('quality', 'Test & handover', 'Function checks are done, the area is cleaned, and care tips are shared.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Please keep the door, window, bed, or table unit and compatible hardware ready unless confirmed otherwise.',
                'Clear access to the work area helps complete the job in one visit.',
                'Final time may vary if frames are out of square or hardware is incomplete — explained before proceeding.',
                'Share type, size, and photos when booking for best results.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling where possible.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => [
                self::faq("What is covered in {$name}?", 'The carpenter completes the booked variation after a quick on-site check, focusing on secure fitting, alignment, and smooth operation.'),
                self::faq('Do I need to buy the product myself?', 'Yes for most installation jobs — supply the door, window, bed, or table unless a making/fabrication service is booked separately.'),
                self::faq('How do I contact Panun Kaergar?', 'Use call, WhatsApp, the website booking form, or the Panun Kaergar app — support details are available in the app profile and contact sections.'),
                self::faq('Is the work checked before handover?', 'Yes. A function test and neat handover are completed before the carpenter leaves.'),
            ],
        ];
    }

    private static function making(string $name, string $focus, array $idealFor): array
    {
        return [
            'short_description' => "Custom {$name} after on-site inspection by verified Panun Kaergar carpenters.",
            'intro' => "Made-to-measure {$name} planned on site for {$focus}.",
            'description' => "{$name} by Panun Kaergar starts with a verified carpenter’s on-site inspection for {$focus}. Measurements, material options, and practical scope are confirmed before fabrication and fitting proceed. Inspection fee is adjusted against the final bill if you continue with Panun Kaergar. For booking help, call or WhatsApp support from the app or website.",
            'card_highlights' => [
                self::highlight('tools', 'Custom Making', 'blue', 0),
                self::highlight('quality', 'Made to Measure', 'green', 1),
                self::highlight('verified', 'Verified Carpenters', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book inspection', "Book on-site inspection for {$name} and share rough requirements."),
                self::step('location', 'Site visit', 'Carpenter measures the space, discusses design options, and confirms scope.', 'thumb'),
                self::step('tools', 'Making & fitting', 'Custom woodwork is fabricated and installed as per the agreed plan.', 'cover'),
                self::step('quality', 'Finish check', 'Fit, finish, and function are reviewed before handover.'),
                self::step('sparkle', 'Handover', 'Care guidance is shared and the work area is left neat.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included([
                'On-site inspection and measurement',
                'Scope and estimate discussion',
                'Custom making labour as per agreed plan after confirmation',
                'Fitting and basic alignment on installation',
                'Final check and handover',
            ]),
            'good_to_know' => self::notes([
                'Inspection fee is adjusted against the final making bill if you proceed through Panun Kaergar.',
                'Material grade, finish, and timeline are confirmed after the site visit.',
                'Civil breaking, plumbing, or electrical work may need separate booking.',
                'Share inspiration photos and approximate sizes when booking.',
                'Notify at least 2 hours before the inspection slot to reschedule when possible.',
            ]),
            'whats_not_included' => self::included([
                'Material cost unless included in the agreed quote',
                'Civil, plumbing, or electrical work',
                'Painting or polishing outside the agreed finish',
                'Work beyond the confirmed on-site scope',
            ]),
            'faqs' => [
                self::faq("What happens in the {$name} inspection?", 'A verified carpenter visits, measures the area, discusses options, and shares a practical plan and estimate.'),
                self::faq('Is the ₹50 fee adjusted later?', 'Yes — if you proceed with the full making job through Panun Kaergar, the inspection fee is adjusted against the final bill.'),
                self::faq('How do I contact support?', 'Call, WhatsApp, book online, or use the Panun Kaergar app — contact options are in the profile/support section.'),
                self::faq('How long does custom making take?', 'Timeline depends on size, material, and design complexity confirmed during inspection.'),
            ],
        ];
    }

    private static function repair(
        string $name,
        string $focus,
        array $idealFor,
        array $included,
        array $excluded
    ): array {
        return [
            'short_description' => "Professional {$name} for homes and offices by verified Panun Kaergar carpenters.",
            'intro' => "Reliable {$name} after on-site diagnosis for {$focus}.",
            'description' => "{$name} by Panun Kaergar starts with a verified carpenter inspecting {$focus}, confirming what can be fixed on the visit, and completing agreed repair work. Inspection fee is adjusted against the final bill if you proceed. For help booking, use call, WhatsApp, website, or the app profile/contact options.",
            'card_highlights' => [
                self::highlight('tools', 'Expert Repair', 'blue', 0),
                self::highlight('quality', 'On-site Diagnosis', 'green', 1),
                self::highlight('verified', 'Verified Carpenters', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book inspection', "Share photos and issue details for {$name}."),
                self::step('location', 'On-site diagnosis', 'Carpenter inspects the damage and confirms practical repair scope.', 'thumb'),
                self::step('tools', 'Repair work', 'Agreed carpentry repairs and hardware adjustments are completed.', 'cover'),
                self::step('quality', 'Function test', 'Movement, alignment, and basic finish are checked.'),
                self::step('sparkle', 'Handover', 'Work area cleaned and care tips shared.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Inspection fee is adjusted against the final repair bill if you proceed through Panun Kaergar.',
                'Keep spare hardware ready if you already have matching parts.',
                'Major damage may need parts, remaking, or a follow-up visit — quoted on site.',
                'Clear access to the item helps complete repairs faster.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling where possible.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => [
                self::faq("What is covered in {$name}?", 'After inspection, the carpenter completes agreed repair labour within practical on-site scope for the reported issue.'),
                self::faq('Is the inspection fee adjusted?', 'Yes, if you proceed with the full repair through Panun Kaergar.'),
                self::faq('How do I contact Panun Kaergar?', 'Use call, WhatsApp, web booking, or the app — support/contact is available from your profile area.'),
                self::faq('Can every issue be fixed in one visit?', 'Minor issues often yes; major damage or missing parts may need a follow-up or separate making/install booking.'),
            ],
        ];
    }

    private static function roof(string $name, string $focus, array $idealFor, bool $isInstall): array
    {
        $verb = $isInstall ? 'installation' : 'repair';

        return [
            'short_description' => "Professional {$name} after on-site inspection by verified Panun Kaergar carpenters.",
            'intro' => "Careful wooden roof {$verb} planned on site for safety and durability.",
            'description' => "{$name} by Panun Kaergar begins with a verified carpenter inspecting {$focus}, confirming structure condition, and advising the right {$verb} plan. Work proceeds after scope confirmation. Inspection fee is adjusted against the final bill if you continue. Contact Panun Kaergar via call, WhatsApp, website, or app profile/support for booking help.",
            'card_highlights' => [
                self::highlight('tools', $isInstall ? 'Roof Fitting' : 'Roof Repair', 'blue', 0),
                self::highlight('quality', 'Structure First', 'green', 1),
                self::highlight('verified', 'Verified Carpenters', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book inspection', "Share roof photos and concern areas for {$name}."),
                self::step('location', 'Site inspection', 'Carpenter assesses structure, access, and practical scope.', 'thumb'),
                self::step('tools', ucfirst($verb).' work', "Agreed roof {$verb} carpentry is completed safely.", 'cover'),
                self::step('quality', 'Structure check', 'Supports, joints, and basic finish are reviewed.'),
                self::step('sparkle', 'Handover', 'Guidance shared and work area left as neat as practical.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included([
                'On-site roof inspection and scope confirmation',
                "Carpentry labour for agreed roof {$verb}",
                'Basic structural attention within confirmed scope',
                'Safety-conscious work practices',
                'Final check and handover guidance',
            ]),
            'good_to_know' => self::notes([
                'Inspection fee is adjusted against the final roofing bill if you proceed through Panun Kaergar.',
                'Material type, extent of damage, and access affect final price and timeline.',
                'Waterproofing membranes or civil work may need separate specialists.',
                'Share clear photos of the roof area when booking.',
                'Notify at least 2 hours before the inspection slot to reschedule when possible.',
            ]),
            'whats_not_included' => self::included([
                'Material cost unless included in the agreed quote',
                'Full waterproofing membrane systems unless confirmed',
                'Civil RCC or masonry work',
                'Electrical or solar mounting work outside carpentry scope',
            ]),
            'faqs' => [
                self::faq("What is covered in {$name}?", "After inspection, the carpenter completes agreed wooden roof {$verb} labour within the confirmed scope."),
                self::faq('Is the ₹50 fee adjusted later?', 'Yes — if you proceed with the full roofing job through Panun Kaergar.'),
                self::faq('How do I contact support?', 'Call, WhatsApp, book online, or use the Panun Kaergar app profile/contact options.'),
                self::faq('Is roof work weather dependent?', 'Yes. Safe access and dry conditions may be required; the carpenter will advise on site.'),
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
        $icons = ['home', 'building', 'sparkle', 'tools', 'quality', 'check', 'calendar', 'location', 'door', 'wood'];
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
        $icons = ['tools', 'check', 'quality', 'sparkle', 'verified', 'location', 'home', 'calendar', 'door', 'wood'];
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
