<?php

class MasonryContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Masonry Service');

        return match ($slug) {
            'masonry-brick-install' => self::install($name, 'brick walls, partitions, and pillar work', [
                'Brick walls', 'Brick partitions', 'Pillars / columns', 'New brick sections',
            ], [
                'On-site inspection of brickwork scope and measurements',
                'Practical quote after site review',
                'Guidance on material readiness and sequence',
                'Clear next-step plan for installation',
                'Work-area review notes',
            ], [
                'Final brick installation labour beyond inspection unless agreed on site',
                'Cost of bricks, cement, sand, or steel',
                'Full structural redesign or RCC work unless agreed',
                'Painting after masonry (book Painting)',
            ]),
            'masonry-plaster-install' => self::install($name, 'wall and ceiling plaster / rendering', [
                'Wall plaster', 'Ceiling plaster', 'External render', 'New plaster coats',
            ], [
                'On-site inspection of plaster area and surface condition',
                'Practical quote after site review',
                'Guidance on prep, coats, and curing',
                'Clear next-step plan for plaster work',
            ], [
                'Final plaster labour beyond inspection unless agreed on site',
                'Cost of cement, sand, gypsum, or additives',
                'Painting or putty finish (book Painting)',
                'Major structural repair unless agreed',
            ]),
            'masonry-tile-install' => self::install($name, 'floor and wall tile installation', [
                'Floor tiling', 'Wall tiling', 'Bathroom tiling', 'Kitchen tiling',
            ], [
                'On-site inspection of tile area, level, and wet-area readiness',
                'Practical quote after site review',
                'Guidance on bed prep and waterproofing needs',
                'Clear next-step plan for tiling',
            ], [
                'Final tiling labour beyond inspection unless agreed on site',
                'Cost of tiles, adhesive, grout, or spacers',
                'Marble / stone install (book Marble or Stone Install)',
                'Plumbing fixture install (book Plumbing)',
            ]),
            'masonry-marble-install' => self::install($name, 'marble flooring, cladding, and counter beds', [
                'Marble flooring', 'Marble cladding', 'Marble counter beds', 'Marble step finishes',
            ], [
                'On-site inspection of marble scope, levels, and support',
                'Practical quote after site review',
                'Guidance on cutting, bedding, and finish sequence',
                'Clear next-step plan for marble install',
            ], [
                'Final marble labour beyond inspection unless agreed on site',
                'Cost of marble slabs, adhesive, or polish materials',
                'Factory polishing packages unless agreed',
                'Plumbing cut-outs unless agreed separately',
            ]),
            'masonry-stone-install' => self::install($name, 'stone cladding, steps, and boundary stone work', [
                'Stone cladding', 'Stone steps', 'Boundary stone sections', 'Natural stone finishes',
            ], [
                'On-site inspection of stone work scope and access',
                'Practical quote after site review',
                'Guidance on bedding and jointing approach',
                'Clear next-step plan for stone install',
            ], [
                'Final stone labour beyond inspection unless agreed on site',
                'Cost of stone, mortar, or sealants',
                'Heavy crane / special lifting unless agreed',
                'Landscape softscaping (book Gardening)',
            ]),
            'masonry-stair-install' => self::install($name, 'steps and stair masonry construction', [
                'Single steps', 'Stair flights', 'Stair edge finishes', 'New stair sections',
            ], [
                'On-site inspection of stair dimensions and load path',
                'Practical quote after site review',
                'Guidance on riser/tread plan and finish options',
                'Clear next-step plan for stair work',
            ], [
                'Final stair labour beyond inspection unless agreed on site',
                'Cost of concrete, brick, marble, or tile finishes',
                'Full RCC structural redesign unless agreed',
                'Handrail fabrication (book Aluminium & Steel / Carpentry as needed)',
            ]),
            'masonry-waterproof-install' => self::install($name, 'bathroom and terrace waterproofing bases', [
                'Bathroom waterproofing base', 'Terrace / balcony base', 'Wet-area corner treatment',
            ], [
                'On-site inspection of wet-area base and leak risk points',
                'Practical quote after site review',
                'Guidance on membrane / coating system and cure time',
                'Clear next-step plan before tiling',
            ], [
                'Final waterproofing labour beyond inspection unless agreed on site',
                'Cost of membranes, coatings, or primers',
                'Tile install (book Masonry Tile Install)',
                'Active plumbing leak repair (book Plumbing)',
            ]),
            'masonry-boundary-install' => self::install($name, 'boundary walls, gate pillars, and coping', [
                'Boundary walls', 'Gate pillars', 'Coping / top finish', 'Compound wall sections',
            ], [
                'On-site inspection of boundary line, levels, and access',
                'Practical quote after site review',
                'Guidance on foundation and winter durability needs',
                'Clear next-step plan for boundary work',
            ], [
                'Final boundary labour beyond inspection unless agreed on site',
                'Cost of bricks, blocks, cement, or stone',
                'Gate fabrication (book Aluminium & Steel)',
                'Municipality / neighbour boundary disputes',
            ]),
            'masonry-full-bathroom-setup' => self::inspection($name, 'full bathroom masonry planning and setup', [
                'New bathroom masonry', 'Bathroom renovation base work', 'Waterproofing + tile planning',
            ], true),

            'masonry-crack-repair' => self::repair($name, 'hairline and deeper wall cracks', [
                'Hairline cracks', 'Settlement cracks', 'Wall crack diagnosis', 'Crack patch planning',
            ], [
                'On-site crack inspection and cause review',
                'Practical repair quote after diagnosis',
                'Guidance on monitoring vs repair',
                'Clear next-step repair plan',
            ], [
                'Final crack repair labour beyond inspection unless agreed on site',
                'Cost of fillers, mesh, or plaster materials',
                'Structural engineer certification unless arranged separately',
                'Full wall rebuild unless agreed',
            ]),
            'masonry-plaster-repair' => self::repair($name, 'hollow, peeling, and damaged plaster', [
                'Hollow plaster', 'Peeling plaster', 'Ceiling patches', 'External render patches',
            ], [
                'On-site plaster damage inspection',
                'Practical repair quote after diagnosis',
                'Guidance on patch matching and cure',
                'Clear next-step repair plan',
            ], [
                'Final plaster repair labour beyond inspection unless agreed on site',
                'Cost of plaster materials',
                'Painting touch-up (book Painting)',
                'Major ceiling structural repair unless agreed',
            ]),
            'masonry-tile-repair' => self::repair($name, 'loose, hollow, and broken tiles', [
                'Loose / hollow tiles', 'Broken tile replace', 'Grout issues', 'Small re-bedding jobs',
            ], [
                'On-site tile fault inspection',
                'Practical repair quote after diagnosis',
                'Guidance on matching tiles and re-bedding',
                'Clear next-step repair plan',
            ], [
                'Final tile repair labour beyond inspection unless agreed on site',
                'Cost of replacement tiles, adhesive, or grout',
                'Full bathroom retile package unless agreed',
                'Waterproofing redo unless booked separately',
            ]),
            'masonry-marble-repair' => self::repair($name, 'cracked, uneven, or damaged marble', [
                'Cracked marble', 'Uneven marble beds', 'Chipped edges', 'Marble joint issues',
            ], [
                'On-site marble damage inspection',
                'Practical repair quote after diagnosis',
                'Guidance on repair vs partial replace',
                'Clear next-step repair plan',
            ], [
                'Final marble repair labour beyond inspection unless agreed on site',
                'Cost of marble pieces, adhesive, or polish materials',
                'Factory re-polishing packages unless agreed',
                'Full marble reinstall unless agreed',
            ]),
            'masonry-stair-repair' => self::repair($name, 'broken edges, loose steps, and stair cracks', [
                'Broken stair edges', 'Loose steps', 'Stair cracks', 'Unsafe tread repairs',
            ], [
                'On-site stair safety and damage inspection',
                'Practical repair quote after diagnosis',
                'Guidance on temporary safety precautions',
                'Clear next-step repair plan',
            ], [
                'Final stair repair labour beyond inspection unless agreed on site',
                'Cost of concrete, tile, or marble materials',
                'Full stair rebuild unless agreed',
                'Handrail work unless booked separately',
            ]),
            'masonry-damp-repair' => self::repair($name, 'damp patches, seepage, and salt marks', [
                'Damp wall patches', 'Seepage areas', 'Efflorescence / salt marks', 'Wet-area damp diagnosis',
            ], [
                'On-site damp / seepage inspection',
                'Practical repair quote after diagnosis',
                'Guidance on masonry vs plumbing causes',
                'Clear next-step treatment plan',
            ], [
                'Final damp repair labour beyond inspection unless agreed on site',
                'Cost of waterproofing materials',
                'Active plumbing leak repair (book Plumbing)',
                'Painting after damp treatment (book Painting)',
            ]),
            'masonry-boundary-repair' => self::repair($name, 'boundary cracks and winter wall damage', [
                'Boundary wall cracks', 'Frost / winter damage', 'Gate pillar damage', 'Coping failures',
            ], [
                'On-site boundary damage inspection',
                'Practical repair quote after diagnosis',
                'Guidance on repair vs rebuild sections',
                'Clear next-step repair plan',
            ], [
                'Final boundary repair labour beyond inspection unless agreed on site',
                'Cost of bricks, cement, or stone',
                'Full boundary rebuild unless agreed',
                'Gate fabrication (book Aluminium & Steel)',
            ]),

            'masonry-site-check' => self::inspection($name, 'masonry fault finding', [
                'Crack checks', 'Damp checks', 'Unknown masonry problems', 'General site diagnosis',
            ], false),
            'masonry-safety-check' => self::inspection($name, 'home masonry safety checks', [
                'Full home masonry check', 'Structural crack review', 'External wall risk checks',
            ], false),
            'masonry-pre-work-check' => self::inspection($name, 'pre-renovation masonry surveys', [
                'Before renovation masonry checks', 'Scope planning visits', 'Estimate-ready inspections',
            ], true),
            default => self::install($name, 'masonry work', [
                'Home masonry jobs', 'Installation visits', 'Repair visits', 'Safety checks',
            ], [
                'On-site review before work starts',
                'Masonry labour within booked scope',
                'Clear next-step guidance',
                'Neat handover notes',
            ], [
                'Material cost unless agreed separately',
                'Plumbing or painting work',
                'Scope outside the confirmed booking',
            ]),
        };
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        if (stripos($variantTitle, 'inspection') !== false || stripos($variantTitle, 'survey') !== false || stripos($variantTitle, 'check') !== false || stripos($variantTitle, 'problem') !== false) {
            return "Verified Panun Kaergar mason inspects the site for {$serviceName} — {$variantTitle}, confirms practical scope, and advises the right plan and estimate.";
        }

        return "{$serviceName} — {$variantTitle}. Verified mason completes the booked variation carefully and hands over neatly.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        return 'Inspection fee may be adjusted against your final masonry bill if you proceed with the full service through Panun Kaergar.';
    }

    private static function install(
        string $name,
        string $focus,
        array $idealFor,
        array $included,
        array $excluded
    ): array {
        return [
            'short_description' => "Expert {$name} by verified Panun Kaergar masons — book inspection first.",
            'intro' => "Site-checked {$name} for {$focus}, with a clear quote before major work.",
            'description' => "{$name} by Panun Kaergar starts with a verified mason inspecting {$focus}, confirming measurements and practical scope, then sharing the right plan and estimate. Final installation work is confirmed after this visit. For booking help, call or WhatsApp Panun Kaergar support from the app or website — contact options are also available in the app profile and contact sections.",
            'card_highlights' => [
                self::highlight('tools', 'Expert Masonry', 'blue', 0),
                self::highlight('quality', 'Site Inspection First', 'green', 1),
                self::highlight('verified', 'Verified Masons', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book inspection', "Share photos and details for {$name}."),
                self::step('verified', 'Mason assigned', 'A verified Panun Kaergar mason confirms the visit.'),
                self::step('location', 'On-site check', 'Measurements, surface condition, and access are reviewed.', 'thumb'),
                self::step('tools', 'Quote & plan', 'Practical scope and estimate are explained clearly.', 'cover'),
                self::step('quality', 'Next steps', 'Proceed with agreed masonry work when you are ready.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Most masonry jobs need inspection before a firm price — area, access, and materials vary.',
                'Inspection fee may be adjusted against the final bill if you proceed through Panun Kaergar.',
                'Keep the work area accessible and share clear photos when booking.',
                'Materials are usually customer-supplied unless agreed on site.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling where possible.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => [
                self::faq("What is covered in {$name}?", 'The booked visit is an on-site inspection and quotation for the masonry job. Final install labour is confirmed after the survey.'),
                self::faq('Are materials included?', 'No. Bricks, cement, tiles, marble, stone, and other materials are usually extra unless agreed.'),
                self::faq('How do I contact Panun Kaergar?', 'Use call, WhatsApp, the website booking form, or the Panun Kaergar app — support details are available in the app profile and contact sections.'),
                self::faq('Is the inspection fee adjusted later?', 'It may be adjusted against your final masonry bill if you proceed with the full service through Panun Kaergar.'),
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
            'short_description' => "Professional {$name} by verified Panun Kaergar masons — inspection first.",
            'intro' => "Reliable {$name} after on-site diagnosis for {$focus}.",
            'description' => "{$name} by Panun Kaergar starts with a verified mason inspecting {$focus}, confirming what can be fixed, and sharing a practical repair plan and estimate. For help booking, use call, WhatsApp, website, or the app profile/contact options.",
            'card_highlights' => [
                self::highlight('tools', 'Expert Repair', 'blue', 0),
                self::highlight('quality', 'On-site Diagnosis', 'green', 1),
                self::highlight('verified', 'Verified Masons', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book inspection', "Share photos and issue details for {$name}."),
                self::step('location', 'On-site diagnosis', 'Mason inspects the damage and confirms practical repair scope.', 'thumb'),
                self::step('tools', 'Quote & plan', 'Repair options and estimate are explained clearly.', 'cover'),
                self::step('quality', 'Agreed repair', 'Repair work proceeds when scope and materials are confirmed.'),
                self::step('sparkle', 'Handover', 'Work area reviewed and care tips shared after agreed work.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Masonry repairs are quoted after seeing crack depth, damp source, or tile condition on site.',
                'Inspection fee may be adjusted against the final bill if you proceed through Panun Kaergar.',
                'Clear access and recent photos help the mason prepare better.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling where possible.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => [
                self::faq("What is covered in {$name}?", 'After diagnosis, the mason confirms practical repair scope and estimate. Final repair labour is agreed before work proceeds.'),
                self::faq('Are spare materials included?', 'Not by default. Fillers, plaster, tiles, marble pieces, and waterproofing materials are usually extra unless agreed.'),
                self::faq('How do I contact Panun Kaergar?', 'Use call, WhatsApp, web booking, or the app — support/contact is available from your profile area.'),
                self::faq('Can every issue be fixed in one visit?', 'Minor fixes may be possible if materials are ready; larger damage is quoted and scheduled after inspection.'),
            ],
        ];
    }

    private static function inspection(string $name, string $focus, array $idealFor, bool $quoteHeavy): array
    {
        $quoteNote = $quoteHeavy
            ? 'This visit is for inspection and quotation. Final installation/repair price is confirmed after the survey.'
            : 'The mason diagnoses the issue and advises the safest next step.';

        return [
            'short_description' => "Professional {$name} by verified Panun Kaergar masons.",
            'intro' => "Clear {$name} for {$focus}, with practical guidance before major work.",
            'description' => "{$name} by Panun Kaergar sends a verified mason to inspect {$focus}, identify risks or scope, and explain the recommended plan. {$quoteNote} For booking help, call or WhatsApp support from the app or website — contact options are in the app profile and contact sections.",
            'card_highlights' => [
                self::highlight('tools', 'Expert Inspection', 'blue', 0),
                self::highlight('quality', 'Clear Diagnosis', 'green', 1),
                self::highlight('verified', 'Verified Masons', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book inspection', "Share photos and concerns for {$name}."),
                self::step('location', 'Site inspection', 'Mason checks cracks, damp, plaster, tiles, or wet areas as booked.', 'thumb'),
                self::step('tools', 'Diagnosis', 'Findings and practical options are explained clearly.', 'cover'),
                self::step('quality', 'Recommendations', 'Repair, install, or safety next steps are advised.'),
                self::step('sparkle', 'Handover', 'Summary shared; inspection fee may adjust against full work if you proceed.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included([
                'On-site masonry inspection for the booked variation',
                'Fault/safety/scope assessment within practical limits',
                'Clear recommendation on next steps',
                'Basic checks relevant to the booked visit',
                'Guidance for follow-up install or repair booking',
            ]),
            'good_to_know' => self::notes([
                $quoteNote,
                'Inspection fee may be adjusted against the final bill if you proceed through Panun Kaergar.',
                'Keep wall/floor access clear and share recent issue photos when booking.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before the inspection slot to reschedule when possible.',
            ]),
            'whats_not_included' => self::included([
                'Full repair or installation labour beyond inspection unless agreed on site',
                'Cost of materials, membranes, tiles, or marble',
                'Plumbing leak repair (book Plumbing)',
                'Painting after masonry work (book Painting)',
            ]),
            'faqs' => [
                self::faq("What happens in {$name}?", 'A verified mason visits, inspects the booked concern, and explains practical findings and next steps.'),
                self::faq('Is the inspection fee adjusted later?', 'It may be adjusted against the final masonry bill if you proceed with the full job through Panun Kaergar.'),
                self::faq('How do I contact support?', 'Call, WhatsApp, book online, or use the Panun Kaergar app profile/contact options.'),
                self::faq('Will the issue be repaired in the same visit?', 'Minor fixes may be possible if materials and scope allow; larger jobs are quoted and booked after inspection.'),
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
        $icons = ['home', 'building', 'sparkle', 'tools', 'quality', 'check', 'calendar', 'location', 'bolt', 'shield'];
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
        $icons = ['tools', 'check', 'quality', 'sparkle', 'verified', 'location', 'home', 'calendar', 'bolt', 'shield'];
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
