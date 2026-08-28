<?php

class HomeAppliancesContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Home Appliance Service');
        $kind = self::kind($slug);

        return match ($kind) {
            'install' => self::install($name, self::focus($slug, $name), self::ideal($slug), self::includedInstall($slug), self::excludedInstall($slug)),
            'repair' => self::repair($name, self::focus($slug, $name), self::ideal($slug), self::includedRepair($slug), self::excludedRepair($slug)),
            'service' => self::service($name, self::focus($slug, $name), self::ideal($slug), self::includedService($slug), self::excludedService($slug)),
            'uninstall' => self::uninstall($name, self::focus($slug, $name), self::ideal($slug), self::includedUninstall($slug), self::excludedUninstall($slug)),
            default => self::repair($name, self::focus($slug, $name), self::ideal($slug), self::includedRepair($slug), self::excludedRepair($slug)),
        };
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        if (stripos($variantTitle, 'inspection') !== false) {
            return "Verified Panun Kaergar technician inspects your appliance for {$serviceName} — {$variantTitle}, confirms the fault or scope, and shares a clear plan before major work.";
        }

        return "{$serviceName} — {$variantTitle}. Verified technician completes the booked variation carefully and hands over after testing.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        if (str_starts_with($serviceSlug, 'generator-') && (str_contains($serviceSlug, 'repair') || str_contains($serviceSlug, 'servicing'))) {
            return 'Inspection or visit fee may be adjusted against your final bill if you proceed with the full service through Panun Kaergar. Fuel, oil, filters, and spare parts are extra unless listed.';
        }

        if (str_contains($serviceSlug, 'repair') || str_contains($serviceSlug, 'service')) {
            return 'Inspection or visit fee may be adjusted against your final bill if you proceed with the full service through Panun Kaergar. Spare parts and consumables are extra unless listed.';
        }

        if (str_starts_with($serviceSlug, 'generator-')) {
            return 'Final price is based on the selected kVA variation. Fuel, oil, filters, spare parts, and new changeover wiring need confirmation before work starts.';
        }

        return 'Final price is based on the selected variation. Extra materials, mounts, copper piping, or parts need confirmation before work starts.';
    }

    private static function kind(string $slug): string
    {
        if (str_contains($slug, 'uninstallation')) {
            return 'uninstall';
        }
        if (str_contains($slug, 'servicing') || str_contains($slug, 'cleaning') || str_contains($slug, 'gas-refill') || $slug === 'gas-refill-check-up') {
            return 'service';
        }
        if (str_contains($slug, 'installation') || str_ends_with($slug, '-installation')) {
            return 'install';
        }
        if (str_contains($slug, 'repair') || $slug === 'ro-service') {
            return 'repair';
        }

        return 'repair';
    }

    private static function focus(string $slug, string $name): string
    {
        return match (true) {
            str_starts_with($slug, 'ac-') => 'air conditioners',
            str_starts_with($slug, 'inverter-') => 'inverters and batteries',
            str_starts_with($slug, 'cctv-') => 'CCTV cameras and recorders',
            str_starts_with($slug, 'geyser-') => 'geysers and water heaters',
            str_starts_with($slug, 'tv-') => 'LED and smart TVs',
            str_starts_with($slug, 'refrigerator-') || $slug === 'gas-refill-leak-fix' => 'refrigerators',
            str_starts_with($slug, 'deep-freezer-') => 'deep freezers',
            str_starts_with($slug, 'washing-machine-') => 'washing machines',
            str_starts_with($slug, 'ro-') => 'RO water purifiers',
            str_starts_with($slug, 'generator-') => 'petrol and diesel generators',
            default => strtolower($name),
        };
    }

    private static function ideal(string $slug): array
    {
        return match (true) {
            str_starts_with($slug, 'ac-') => ['Homes', 'Offices', 'Split AC', 'Window AC', 'Seasonal care'],
            str_starts_with($slug, 'inverter-') => ['Homes', 'Shops', 'Power backup setups', 'Battery banks'],
            str_starts_with($slug, 'cctv-') => ['Homes', 'Shops', 'Offices', 'Security upgrades'],
            str_starts_with($slug, 'geyser-') => ['Homes', 'Bathrooms', 'Winter readiness', 'Storage / Instant units'],
            str_starts_with($slug, 'tv-') => ['Homes', 'Wall mounting', 'Living rooms', 'Smart TVs'],
            str_starts_with($slug, 'refrigerator-') || $slug === 'gas-refill-leak-fix' => ['Homes', 'Kitchens', 'Single / Double door', 'Side by Side / French door'],
            str_starts_with($slug, 'deep-freezer-') => ['Homes', 'Shops', 'Chest freezer', 'Upright freezer', 'Commercial / display'],
            str_starts_with($slug, 'washing-machine-') => ['Homes', 'Front load', 'Top load', 'Semi-automatic'],
            str_starts_with($slug, 'ro-') => ['Homes', 'Kitchens', 'RO systems', 'Filter care'],
            str_starts_with($slug, 'generator-') => ['Homes', 'Shops', 'Petrol generators', 'Diesel generators', 'Power cuts'],
            str_contains($slug, 'chimney') || str_contains($slug, 'hob') => ['Kitchens', 'Cooking areas', 'Home upgrades'],
            default => ['Homes', 'Appliance faults', 'Quick diagnosis', 'Verified technicians'],
        };
    }

    private static function includedInstall(string $slug): array
    {
        if (str_starts_with($slug, 'generator-')) {
            return [
                'On-site placement and install review',
                'Labour for the booked kVA variation',
                'Basic generator connections within scope',
                'Startup and load test when fuel is available',
                'Work-area tidy-up and handover tips',
            ];
        }

        return [
            'On-site placement and install review',
            'Labour for the booked variation',
            'Secure fitting and basic connections within scope',
            'Function test after installation',
            'Work-area tidy-up and handover tips',
        ];
    }

    private static function excludedInstall(string $slug): array
    {
        if (str_starts_with($slug, 'generator-')) {
            return [
                'Cost of the generator, canopy, stand, or spare parts',
                'Fuel, engine oil, and filters',
                'Generator hire or overnight operator',
                'New changeover, DB, or house wiring (book Electrician)',
                'Civil foundation or exhaust fabrication unless agreed',
            ];
        }

        return [
            'Cost of the appliance, mounts, stand, or spare parts',
            'Extra copper piping, wiring, or civil chasing unless agreed',
            'Electrical point creation (book Electrician if needed)',
            'Plumbing line work beyond appliance connection (book Plumbing if needed)',
            'Scaffolding or special height access unless arranged',
        ];
    }

    private static function includedRepair(string $slug): array
    {
        if (str_starts_with($slug, 'generator-')) {
            return [
                'On-site fault diagnosis',
                'Repair labour for the booked variation when practical',
                'Safety and operating checks',
                'Test run after repair when fuel is available',
                'Clear briefing before major parts work',
            ];
        }

        return [
            'On-site fault diagnosis',
            'Repair labour for the booked variation when practical',
            'Safety and operating checks',
            'Test run after repair where applicable',
            'Clear briefing before major parts work',
        ];
    }

    private static function excludedRepair(string $slug): array
    {
        if (str_starts_with($slug, 'generator-')) {
            return [
                'Cost of spare parts, starter motor, AVR, carburettor, or PCB',
                'Fuel, engine oil, and filters',
                'Generator hire or overnight operator',
                'New changeover or house wiring (book Electrician)',
                'Full engine overhaul unless quoted after inspection',
            ];
        }

        return [
            'Cost of spare parts, PCBs, motors, filters, or sensors',
            'Full appliance replacement',
            'Brand warranty claim processing with the manufacturer',
            'Gas refill unless booked as a separate service / variation',
            'Civil, plaster, or paint corrections',
        ];
    }

    private static function includedService(string $slug): array
    {
        if (str_starts_with($slug, 'generator-')) {
            return [
                'Scheduled servicing labour for the booked kVA variation',
                'Oil, filter, and accessible checks within practical scope',
                'Basic safety review',
                'Test run after service when fuel is available',
                'Care tips at handover',
            ];
        }

        return [
            'Scheduled servicing labour for the booked variation',
            'Cleaning / checks within practical access',
            'Basic safety review',
            'Performance test after service',
            'Care tips at handover',
        ];
    }

    private static function excludedService(string $slug): array
    {
        if (str_starts_with($slug, 'generator-')) {
            return [
                'Cost of oil, filters, spark plugs, or spare parts',
                'Fuel for the test run if none is available on site',
                'Major engine or electrical repairs (book Generator Repair)',
                'Generator hire or overnight operator',
                'Changeover or house wiring (book Electrician)',
            ];
        }

        return [
            'Major spare-part replacement unless approved separately',
            'Gas top-up unless booked as gas refill',
            'Full dismantling beyond routine service scope',
            'Consumables such as filters or chemicals unless listed',
            'Installation or shifting work',
        ];
    }

    private static function includedUninstall(string $slug): array
    {
        if (str_starts_with($slug, 'generator-')) {
            return [
                'Safe shutdown and disconnection',
                'Careful generator removal for the booked variation',
                'Basic fuel and exhaust isolation where applicable',
                'Shift-ready handover',
                'Work-area tidy-up',
            ];
        }

        return [
            'Safe shutdown and disconnection',
            'Careful appliance removal for the booked variation',
            'Line / hose protection where applicable',
            'Shift-ready handover',
            'Work-area tidy-up',
        ];
    }

    private static function excludedUninstall(string $slug): array
    {
        if (str_starts_with($slug, 'generator-')) {
            return [
                'Transport, packing, or shifting labour unless agreed',
                'Civil, exhaust, or foundation repair after removal',
                'Reinstallation at a new location (book Generator Installation)',
                'Disposal of old units unless arranged separately',
                'Fuel draining beyond basic safe shutdown unless agreed',
            ];
        }

        return [
            'Transport, packing, or shifting labour unless agreed',
            'Wall, plaster, or tile repair after mount removal',
            'Reinstallation at a new location (book Installation)',
            'Disposal of old units unless arranged separately',
            'Scaffolding or crane access unless arranged',
        ];
    }

    private static function install(string $name, string $focus, array $idealFor, array $included, array $excluded): array
    {
        return [
            'short_description' => "Professional {$name} by verified Panun Kaergar technicians for safe setup and clean handover.",
            'intro' => "Reliable {$name} with careful fitting, testing, and a neat finish for {$focus}.",
            'description' => "{$name} by Panun Kaergar connects you with verified technicians for {$focus}. The professional reviews placement and connections, completes the booked installation variation, tests the appliance, and hands over neatly. For booking help, call or WhatsApp Panun Kaergar support from the app or website — contact options are also available in the app profile and contact sections.",
            'card_highlights' => [
                self::highlight('tools', 'Expert Installation', 'blue', 0),
                self::highlight('quality', 'Tested Handover', 'green', 1),
                self::highlight('verified', 'Verified Technicians', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Choose the right variation and share model or photos for {$name}."),
                self::step('verified', 'Technician assigned', 'A verified Panun Kaergar technician confirms the visit and arrives ready.'),
                self::step('location', 'On-site check', 'Placement, power, and access are reviewed before work begins.', 'thumb'),
                self::step('tools', 'Installation work', 'The booked variation is installed with secure fitting and connections.', 'cover'),
                self::step('quality', 'Test & handover', 'The appliance is tested, the area is cleaned, and care tips are shared.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Keep the appliance, remote, manuals, and mounts ready unless confirmed otherwise.',
                'Clear access to power and install location helps finish in one visit.',
                'Parts, mounts, and materials are usually extra unless listed in your booking.',
                'Share brand, model, and photos when booking for faster setup.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling where possible.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => self::faqs($name, 'install'),
        ];
    }

    private static function repair(string $name, string $focus, array $idealFor, array $included, array $excluded): array
    {
        return [
            'short_description' => "Trusted {$name} by verified Panun Kaergar technicians for careful diagnosis and tested repair.",
            'intro' => "Reliable {$name} that finds the root cause first and restores performance safely for {$focus}.",
            'description' => "{$name} by Panun Kaergar brings a verified technician to diagnose faults in {$focus}, explain the repair clearly, and complete the booked variation after your approval for major parts. For booking help, call or WhatsApp Panun Kaergar support from the app or website — contact options are also available in the app profile and contact sections.",
            'card_highlights' => [
                self::highlight('tools', 'Fault Diagnosis', 'blue', 0),
                self::highlight('quality', 'Tested Repair', 'green', 1),
                self::highlight('verified', 'Verified Technicians', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your repair', "Pick the closest issue variation or Book Site Inspection for {$name}."),
                self::step('verified', 'Technician assigned', 'A verified technician is matched based on the appliance and symptom.'),
                self::step('location', 'On-site diagnosis', 'The unit is inspected safely and the repair plan is explained.', 'thumb'),
                self::step('tools', 'Repair with approval', 'Booked repair work is completed; major parts are fitted only after approval.', 'cover'),
                self::step('quality', 'Test & handover', 'The appliance is tested and care guidance is shared before closing the job.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Choose Book Site Inspection if you are unsure about the exact fault.',
                'Keep the appliance powered and accessible for diagnosis where safe.',
                'Spare parts and gas top-up are quoted separately after inspection when needed.',
                'Share brand, model, error codes, and photos while booking.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling where possible.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => self::faqs($name, 'repair'),
        ];
    }

    private static function service(string $name, string $focus, array $idealFor, array $included, array $excluded): array
    {
        return [
            'short_description' => "Complete {$name} by verified Panun Kaergar technicians for cleaner performance and longer appliance life.",
            'intro' => "Preventive {$name} that improves hygiene, efficiency, and day-to-day reliability for {$focus}.",
            'description' => "{$name} by Panun Kaergar is handled by verified technicians who clean, check, and test {$focus} within the booked variation. Regular service reduces avoidable breakdowns. For booking help, call or WhatsApp Panun Kaergar support from the app or website — contact options are also available in the app profile and contact sections.",
            'card_highlights' => [
                self::highlight('sparkle', 'Deep Care', 'blue', 0),
                self::highlight('quality', 'Better Performance', 'green', 1),
                self::highlight('verified', 'Preventive Service', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book a service slot', "Select the right variation for {$name} and share appliance details."),
                self::step('verified', 'Technician assigned', 'A verified technician arrives with tools for the booked service type.'),
                self::step('location', 'Inspection before service', 'Condition and access are checked before cleaning or servicing starts.', 'thumb'),
                self::step('sparkle', 'Service work', 'Cleaning, checks, and care steps are completed for the booked variation.', 'cover'),
                self::step('quality', 'Test & handover', 'A performance check is done and maintenance tips are shared.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Routine servicing every few months helps avoid sudden failures.',
                'Heavy neglect may need a deeper service than a basic clean.',
                'Filters, chemicals, or gas work may be extra unless listed.',
                'Share brand, model, and last service date when booking.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling where possible.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => self::faqs($name, 'service'),
        ];
    }

    private static function uninstall(string $name, string $focus, array $idealFor, array $included, array $excluded): array
    {
        return [
            'short_description' => "Safe {$name} by verified Panun Kaergar technicians with careful disconnection and shift-ready handover.",
            'intro' => "Careful {$name} that protects the appliance and your walls during removal of {$focus}.",
            'description' => "{$name} by Panun Kaergar is done by verified technicians who shut down, disconnect, and remove {$focus} for the booked variation with care. For booking help, call or WhatsApp Panun Kaergar support from the app or website — contact options are also available in the app profile and contact sections.",
            'card_highlights' => [
                self::highlight('shield', 'Safe Removal', 'blue', 0),
                self::highlight('tools', 'Careful Disconnect', 'green', 1),
                self::highlight('verified', 'Shift Ready', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Confirm removal scope', "Share appliance type and access details for {$name}."),
                self::step('verified', 'Technician assigned', 'A verified technician arrives ready for safe uninstallation.'),
                self::step('location', 'Safety shutdown', 'Power and connections are isolated before removal.', 'thumb'),
                self::step('tools', 'Uninstallation work', 'The appliance is removed carefully for the booked variation.', 'cover'),
                self::step('quality', 'Handover', 'The unit is handed over shift-ready and the work area is tidied.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Clear floor and wall access helps remove mounts and units safely.',
                'Mention floor level and outdoor unit access for AC-type removals.',
                'Reinstallation should be booked separately if needed at the new place.',
                'Share photos of the current setup when booking.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling where possible.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => self::faqs($name, 'uninstall'),
        ];
    }

    private static function faqs(string $name, string $kind): array
    {
        if (str_contains($name, 'Generator')) {
            return match ($kind) {
                'install' => [
                    ['What should I keep ready for Generator Installation?', 'Keep the generator, fuel for a test run, and clear access ready. Share petrol or diesel type and kVA while booking.'],
                    ['Are fuel, oil, or the generator unit included?', 'No. Labour for the booked kVA variation is included. The machine, fuel, oil, filters, and spare parts are extra.'],
                    ['Do you also do changeover or house wiring?', 'Basic generator connections are covered. New changeover, DB, or house wiring should be booked under Electrician.'],
                    ['Can you install both petrol and diesel generators?', 'Yes. Choose the matching kVA variation so the technician arrives prepared.'],
                    ['Will the technician test after install?', 'Yes. A startup and basic load test is done when fuel is available on site.'],
                    ['How do I contact support?', 'Use call, WhatsApp, website, or the app profile and contact sections for booking help.'],
                ],
                'repair' => [
                    ['What if I am unsure why the generator failed?', 'Choose Book Site Inspection. The technician diagnoses first and then recommends the right repair.'],
                    ['Are spare parts, oil, or fuel included?', 'No. Parts, oil, filters, and fuel are quoted after inspection and used only with your approval.'],
                    ['Will inspection fee be adjusted?', 'Often yes if you proceed with the full repair through Panun Kaergar.'],
                    ['Can you repair both petrol and diesel generators?', 'Yes. Share the fuel type, kVA, brand, and symptom while booking.'],
                    ['How long does a repair visit take?', 'Most visits take about 1 to 3 hours depending on access, fault type, and parts.'],
                    ['How do I contact support?', 'Use call, WhatsApp, website, or the app profile and contact sections for booking help.'],
                ],
                'service' => [
                    ['How often should I service a generator?', 'Every 3 to 6 months for regular use, or sooner after heavy outage use or if it starts roughly.'],
                    ['Does servicing include oil and filters?', 'Labour is included. Oil, filters, spark plugs, and fuel are extra unless listed.'],
                    ['Does servicing include major repairs?', 'No. Book Generator Repair if it will not start, has no output, leaks, or unusual smoke.'],
                    ['Can you service both petrol and diesel sets?', 'Yes. Choose the matching kVA variation.'],
                    ['How long does servicing take?', 'Most routine services take about 1 to 2 hours depending on size and access.'],
                    ['How do I contact support?', 'Use call, WhatsApp, website, or the app profile and contact sections for booking help.'],
                ],
                default => [
                    ['Is Generator Uninstallation safe for shifting?', 'Yes. Technicians shut down and disconnect carefully so the set is better prepared for transport or storage.'],
                    ['Does uninstallation include reinstallation?', 'No. Book Generator Installation separately at the new location.'],
                    ['Do you also remove changeover wiring?', 'Basic generator disconnection is included. House or DB wiring changes should be booked under Electrician.'],
                    ['Should I drain the fuel first?', 'Leave enough for a safe shutdown check. Full draining is extra unless agreed.'],
                    ['How long does removal take?', 'Most uninstallations take about 45 to 120 minutes depending on size and access.'],
                    ['How do I contact support?', 'Use call, WhatsApp, website, or the app profile and contact sections for booking help.'],
                ],
            };
        }

        return match ($kind) {
            'install' => [
                ["What should I keep ready for {$name}?", 'Keep the appliance, mounts, manuals, and clear access ready. Share brand and model while booking.'],
                ['Are materials included?', 'Labour for the booked variation is included. Mounts, copper piping, spare parts, and materials are usually extra unless listed.'],
                ['Will the technician test after install?', 'Yes. A basic function test is done before handover whenever power and setup allow.'],
                ['Can you install on the same day I buy the appliance?', 'Often yes if access and fittings are ready. Share delivery timing when booking.'],
                ['Do you handle electrical or plumbing points?', 'Basic appliance connections are covered. New points or major line work may need Electrician or Plumbing.'],
                ['How do I contact support?', 'Use call, WhatsApp, website, or the app profile and contact sections for booking help.'],
            ],
            'repair' => [
                ["What if I am unsure about the fault for {$name}?", 'Choose Book Site Inspection. The technician diagnoses first and then recommends the right repair.'],
                ['Are spare parts included?', 'No. Parts are quoted after inspection and fitted only with your approval.'],
                ['Will inspection fee be adjusted?', 'Often yes if you proceed with the full repair through Panun Kaergar.'],
                ['How long does a repair visit take?', 'Most visits take about 1 to 3 hours depending on access, fault type, and parts.'],
                ['Can every appliance be repaired on the first visit?', 'Many can. If a rare part is needed, the technician will explain the next step clearly.'],
                ['How do I contact support?', 'Use call, WhatsApp, website, or the app profile and contact sections for booking help.'],
            ],
            'service' => [
                ["How often should I book {$name}?", 'Every 3 to 6 months is practical for regularly used appliances, or sooner if performance drops.'],
                ['Does servicing include major repairs?', 'No. Servicing focuses on cleaning and checks. Repairs and parts are separate if needed.'],
                ['Will performance improve after service?', 'Often yes — cleaner filters, coils, and drains usually improve airflow, cooling, or output.'],
                ['Are consumables included?', 'Only if listed in the variation. Filters and chemicals may be extra.'],
                ['How long does servicing take?', 'Most routine services take about 1 to 2 hours depending on dirt level and access.'],
                ['How do I contact support?', 'Use call, WhatsApp, website, or the app profile and contact sections for booking help.'],
            ],
            default => [
                ["Is {$name} safe for shifting?", 'Yes. Technicians disconnect carefully so the unit is better prepared for transport or storage.'],
                ['Does uninstallation include reinstallation?', 'No. Book Installation separately at the new location.'],
                ['Will wall holes be repaired?', 'Mount removal is included. Plaster or paint repair is usually extra unless agreed.'],
                ['Should I empty the appliance first?', 'Yes when practical — especially fridges, washers, and coolers.'],
                ['How long does removal take?', 'Most uninstallations take about 45 to 120 minutes depending on type and access.'],
                ['How do I contact support?', 'Use call, WhatsApp, website, or the app profile and contact sections for booking help.'],
            ],
        };
    }

    private static function highlight(string $icon, string $text, string $color, int $sort): array
    {
        return ['icon' => $icon, 'text' => $text, 'color' => $color, 'sort_order' => $sort];
    }

    private static function step(string $icon, string $title, string $description, ?string $image = null): array
    {
        $step = ['icon' => $icon, 'title' => $title, 'description' => $description];
        if ($image !== null) {
            $step['image'] = $image;
        }

        return $step;
    }

    private static function chips(array $texts): array
    {
        $items = [];
        $icons = ['home', 'building', 'quality', 'check', 'sparkle', 'tools', 'verified', 'power'];
        foreach (array_values($texts) as $i => $text) {
            $items[] = ['icon' => $icons[$i % count($icons)], 'text' => $text, 'sort_order' => $i];
        }

        return $items;
    }

    private static function included(array $titles): array
    {
        $items = [];
        $icons = ['check', 'tools', 'quality', 'sparkle', 'verified', 'shield', 'power', 'home'];
        foreach (array_values($titles) as $i => $title) {
            $items[] = ['icon' => $icons[$i % count($icons)], 'title' => $title, 'sort_order' => $i];
        }

        return $items;
    }

    private static function notes(array $titles): array
    {
        return self::included($titles);
    }
}
