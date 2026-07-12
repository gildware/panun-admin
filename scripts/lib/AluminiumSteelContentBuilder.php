<?php

class AluminiumSteelContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Metal works');
        $subSlug = (string) ($service['sub_category_slug'] ?? '');

        $role = match ($subSlug) {
            'metal-works-repairs' => 'repair',
            'metal-works-fabrication' => 'fabrication',
            default => 'install',
        };

        $material = self::materialLabel($slug);
        $focus = self::focusArea($slug);

        return match ($role) {
            'repair' => self::repairBase($name, $material, $focus),
            'fabrication' => self::fabricationBase($name, $material, $focus),
            default => self::installBase($name, $material, $focus),
        };
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        if ($variantTitle === 'Book On Site Inspection' || $variantTitle === 'Book Site Inspection') {
            return "Verified metal works professional inspects your site for {$serviceName}, takes measurements, and recommends the right scope and materials on site.";
        }

        return "{$serviceName} — {$variantTitle}.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        return 'This ₹100 inspection fee will be adjusted against your final aluminium and steel works bill if you proceed with the full service through Panun Kaergar.';
    }

    private static function materialLabel(string $slug): string
    {
        return match (true) {
            str_contains($slug, 'acp') => 'ACP (aluminium composite panel)',
            str_contains($slug, 'upvc') => 'uPVC',
            str_contains($slug, 'pvc') => 'PVC',
            str_contains($slug, 'aluminium') || str_contains($slug, 'aluminum') => 'aluminium',
            str_contains($slug, 'ss-grill') || str_contains($slug, 'ss-') => 'stainless steel (SS)',
            str_contains($slug, 'ms-gate') || str_contains($slug, 'ms-') => 'mild steel (MS)',
            str_contains($slug, 'steel') => 'steel',
            str_contains($slug, 'glass') => 'aluminium-framed glass',
            str_contains($slug, 'railing') => 'metal railing',
            str_contains($slug, 'gate') || str_contains($slug, 'grill') => 'metal gate or grill',
            str_contains($slug, 'shutter') => 'rolling shutter',
            str_contains($slug, 'false-ceiling') => 'false ceiling grid',
            str_contains($slug, 'pergola') => 'aluminium or steel structure',
            str_contains($slug, 'signage') => 'metal signage frame',
            default => 'metal',
        };
    }

    private static function focusArea(string $slug): string
    {
        return match (true) {
            str_contains($slug, 'acp') => 'exterior facade and cladding surfaces',
            str_contains($slug, 'window') => 'window openings and frames',
            str_contains($slug, 'door') && ! str_contains($slug, 'upvc-window-door') => 'door openings and hardware',
            str_contains($slug, 'balcony-railing') => 'balcony edges and safety railings',
            str_contains($slug, 'staircase-railing') => 'staircase sides and handrails',
            str_contains($slug, 'pvc-wall') || str_contains($slug, 'pvc-panel') => 'interior wall panels',
            str_contains($slug, 'false-ceiling') => 'ceiling grid and panel layout',
            str_contains($slug, 'gate') => 'main gate and compound entry',
            str_contains($slug, 'grill') => 'window and balcony grills',
            str_contains($slug, 'glass-partition') => 'office or room partitions',
            str_contains($slug, 'shutter') => 'shop front shutters',
            str_contains($slug, 'pergola') => 'car porch or outdoor pergola structure',
            str_contains($slug, 'signage') => 'shop boards and signage mounting',
            str_contains($slug, 'bracket') => 'brackets and support frames',
            str_contains($slug, 'railing') => 'railings and safety barriers',
            default => 'the work area on site',
        };
    }

    private static function installBase(string $name, string $material, string $focus): array
    {
        return [
            'short_description' => "Professional {$name} with precise measurement, secure fixing, and neat finishing. Verified metal works professionals for homes, shops, and offices.",
            'intro' => "Neat {$material} installation with proper alignment and secure fixing.",
            'description' => "{$name} by Panun Kaergar connects you with verified professionals for {$focus}. From on-site measurement to final fitting and alignment, work is planned for durability, safety, and a clean finish.",
            'card_highlights' => [
                self::highlight('tools', 'Precise Fitting', 'blue', 0),
                self::highlight('shield', 'Durable Finish', 'green', 1),
                self::highlight('verified', 'Verified Professionals', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Share photos, approximate size, and location details for {$name}."),
                self::step('verified', 'Site inspection', 'A verified professional inspects the site, confirms measurements, and agrees scope.', 'thumb'),
                self::step('tools', 'Installation work', "{$material} components are fitted, aligned, and secured as per the agreed plan.", 'cover'),
                self::step('quality', 'Alignment check', 'Movement, gaps, and basic finish are reviewed before handover.'),
                self::step('sparkle', 'Handover', 'Usage guidance and care tips shared after completion.'),
            ],
            'perfect_for' => self::chips([
                'New installations',
                'Home renovations',
                'Shop fronts',
                'Office fit-outs',
                'Balcony and facade upgrades',
            ]),
            'whats_included' => self::included([
                'On-site measurement and scope confirmation',
                'Standard installation labour as per agreed plan',
                'Basic alignment and hardware fitting',
                'Neat finishing at installation points',
                'Basic handover and usage guidance',
            ]),
            'good_to_know' => self::notes([
                'Keep the work area accessible before the visit.',
                'Material can be customer-supplied or sourced by the professional — confirm before booking.',
                'Civil breaking, plastering, or electrical work may need separate booking.',
                'Final pricing depends on size, material grade, and site conditions confirmed on inspection.',
                'Notify at least 2 hours before for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Cost of ACP sheets, profiles, glass, or raw metal material unless agreed',
                'Major civil, concrete, or structural modifications',
                'Painting, powder coating, or polishing unless booked separately',
                'Scaffolding, crane, or special access equipment',
                'Electrical wiring for shutters or motorised systems',
            ]),
            'faqs' => [
                self::faq('Do I need to buy materials before booking?', 'You can supply materials yourself or ask the professional to source them. Material cost is quoted separately after inspection.'),
                self::faq('How is pricing calculated?', 'Pricing is based on area, number of units, material type, and site complexity confirmed during the site inspection.'),
                self::faq('How long does installation take?', 'Most standard jobs are completed within the agreed visit or follow-up slot depending on size and material readiness.'),
                self::faq('Is the inspection fee adjusted?', 'Yes. The ₹100 site inspection fee is adjusted against your final bill if you proceed through Panun Kaergar.'),
            ],
        ];
    }

    private static function repairBase(string $name, string $material, string $focus): array
    {
        return [
            'short_description' => "Professional {$name} for loose fittings, damaged sections, and alignment issues. Restore safety and function with verified metal works professionals.",
            'intro' => "Reliable {$material} repair with secure refixing and smooth operation.",
            'description' => "{$name} by Panun Kaergar helps fix issues in {$focus} — from loose panels and worn hardware to misaligned shutters and damaged sections — so your metal work is safe and usable again.",
            'card_highlights' => [
                self::highlight('tools', 'Targeted Repair', 'blue', 0),
                self::highlight('shield', 'Safety Restored', 'green', 1),
                self::highlight('verified', 'Verified Professionals', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Share photos of the damage and describe the issue for {$name}."),
                self::step('verified', 'Damage assessment', 'Technician inspects the affected area and confirms repair scope on site.', 'thumb'),
                self::step('tools', 'Repair work', 'Loose sections are secured, hardware replaced or adjusted, and alignment corrected.', 'cover'),
                self::step('quality', 'Function check', 'Movement, locking, and stability are tested before handover.'),
                self::step('sparkle', 'Handover', 'Care guidance shared to help prevent repeat damage.'),
            ],
            'perfect_for' => self::chips([
                'Loose or damaged panels',
                'Stiff shutters or doors',
                'Broken hardware',
                'Misaligned railings',
                'Maintenance before monsoon',
            ]),
            'whats_included' => self::included([
                'On-site inspection of reported damage',
                'Minor alignment and refixing within agreed scope',
                'Basic hardware adjustment or tightening',
                'Welding touch-up for minor breaks where feasible',
                'Basic function check before handover',
            ]),
            'good_to_know' => self::notes([
                'Severely corroded or warped sections may need replacement rather than repair.',
                'Replacement panels, profiles, or hardware are billed separately if required.',
                'Painting or coating after welding may need a separate visit.',
                'Share clear photos when booking to help plan the right technician.',
                'Notify at least 2 hours before for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Cost of new ACP sheets, profiles, glass, or major spare parts',
                'Full replacement of large sections unless agreed on site',
                'Structural civil work or wall rebuilding',
                'Motor replacement for automated shutters',
                'Repainting or anti-rust treatment unless booked separately',
            ]),
            'faqs' => [
                self::faq('Can you fix only one panel or section?', 'Yes. Minor section repair and refixing are commonly handled within standard scope.'),
                self::faq('What if parts need replacement?', 'The technician will assess on site. Replacement parts may need to be sourced before work can be completed.'),
                self::faq('Is welding included?', 'Minor welding touch-up is included where feasible. Major fabrication or full section rebuild is quoted separately.'),
                self::faq('Is the inspection fee adjusted?', 'Yes. The ₹100 site inspection fee is adjusted against your final repair bill if you proceed through Panun Kaergar.'),
            ],
        ];
    }

    private static function fabricationBase(string $name, string $material, string $focus): array
    {
        return [
            'short_description' => "Custom {$name} with on-site measurement and workshop-quality fabrication. Built to fit your opening, design, and security needs.",
            'intro' => "Custom {$material} fabrication measured and built for your site.",
            'description' => "{$name} by Panun Kaergar connects you with verified fabricators for {$focus}. From measurement and design confirmation to cutting, welding, and fitting, custom metal work is planned for strength, fit, and durability.",
            'card_highlights' => [
                self::highlight('tools', 'Custom Build', 'blue', 0),
                self::highlight('shield', 'Strong & Secure', 'green', 1),
                self::highlight('verified', 'Verified Fabricators', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Share reference photos, approximate size, and design preference for {$name}."),
                self::step('verified', 'Measurement visit', 'Fabricator measures the site and confirms design, material gauge, and scope.', 'thumb'),
                self::step('tools', 'Fabrication', "{$material} is cut, welded, and prepared to match the agreed design.", 'cover'),
                self::step('quality', 'Installation / fitting', 'Fabricated unit is brought to site, fitted, and aligned.'),
                self::step('sparkle', 'Handover', 'Operation check and basic maintenance guidance shared.'),
            ],
            'perfect_for' => self::chips([
                'Non-standard sizes',
                'Custom gate designs',
                'Security grills',
                'Designer railings',
                'Shop and home upgrades',
            ]),
            'whats_included' => self::included([
                'On-site measurement and design confirmation',
                'Standard fabrication labour as per agreed design',
                'Cutting, welding, and assembly of the ordered unit',
                'Basic fitting and alignment at site',
                'Basic operation check before handover',
            ]),
            'good_to_know' => self::notes([
                'Complex designs may need a drawing or reference image before fabrication starts.',
                'Material grade (MS/SS/aluminium) and thickness affect strength and pricing.',
                'Painting or powder coating is usually booked separately unless agreed.',
                'Lead time depends on design complexity and workshop schedule.',
                'Notify at least 2 hours before for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Cost of raw metal sheets, pipes, or profiles unless agreed',
                'Premium designer finishes or laser-cut decorative panels unless quoted',
                'Major civil work for new openings or wall modifications',
                'Automated gate motors or electronic locks',
                'Transport of oversized units beyond local scope unless agreed',
            ]),
            'faqs' => [
                self::faq('Can I share a design photo?', 'Yes. Reference photos help the fabricator confirm pattern, spacing, and finish expectations.'),
                self::faq('How long does fabrication take?', 'Lead time depends on design complexity and material availability. Timeline is confirmed after inspection.'),
                self::faq('Is painting included?', 'Basic fabrication is included. Painting, powder coating, or polishing are usually quoted separately.'),
                self::faq('Is the inspection fee adjusted?', 'Yes. The ₹100 site inspection fee is adjusted against your final fabrication bill if you proceed through Panun Kaergar.'),
            ],
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
