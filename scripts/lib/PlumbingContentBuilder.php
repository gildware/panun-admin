<?php

class PlumbingContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Plumbing Service');

        return match ($slug) {
            'plumbing-tap-install' => self::install($name, 'taps, mixers, and angle valves', [
                'Regular taps', 'Mixer taps', 'Swan neck taps', 'Pillar cocks', 'Angle valves',
            ], [
                'Fitting of the booked tap / valve variation',
                'Basic leak check at joints after install',
                'Secure connection within practical scope',
                'Water-on test after install',
                'Work-area tidy-up',
            ], [
                'Cost of tap, mixer, valve, or fittings',
                'Major pipe rerouting beyond the fitting point',
                'Civil tiling or wall cutting unless agreed',
                'Geyser body installation (book Home Appliances)',
            ]),
            'plumbing-shower-install' => self::install($name, 'shower heads, jet sprays, and shower mixers', [
                'Shower heads', 'Hand showers / jet sprays', 'Shower mixers', 'Bathroom shower upgrades',
            ], [
                'Shower fitting labour for booked variation',
                'Secure mounting and connection within scope',
                'Basic leak and spray test',
                'Neat finishing of connection point',
                'Cleanup after install',
            ], [
                'Cost of shower head, arm, jet spray, or mixer',
                'Full bathroom plumbing package (book Full Bathroom Plumbing)',
                'Concealed mixer box chasing / plaster unless agreed',
                'False ceiling or civil fabrication',
            ]),
            'plumbing-basin-install' => self::install($name, 'wash basins, pedestal basins, and bottle traps', [
                'Wash basins', 'Pedestal basins', 'Bottle trap fitting', 'Basin replacement installs',
            ], [
                'Basin / trap install labour for booked type',
                'Inlet and waste connection within scope',
                'Leak check after install',
                'Level and secure mounting',
                'Work-area cleanup',
            ], [
                'Cost of basin, pedestal, trap, or brackets',
                'Marble / counter cutting unless agreed',
                'Major drain line replacement beyond trap',
                'Tile or civil repair after mount work',
            ]),
            'plumbing-toilet-install' => self::install($name, 'Indian/Western toilets and flush tanks', [
                'Indian toilets', 'Western floor toilets', 'Western wall toilets', 'External flush tanks', 'Concealed flush tanks',
            ], [
                'Toilet / flush tank install labour for booked variation',
                'Inlet and outlet connection within confirmed scope',
                'Flush and leak test after install',
                'Basic alignment and secure fixing',
                'Cleanup of work area',
            ], [
                'Cost of toilet, cistern, seat, or flush kit',
                'Major soil pipe or civil flooring work unless agreed',
                'Full bathroom renovation package',
                'Waterproofing membrane work unless agreed',
            ]),
            'plumbing-sink-install' => self::install($name, 'kitchen sinks and connection hoses', [
                'Kitchen sink fitting', 'Connection hose install', 'Kitchen wet-area plumbing adds',
            ], [
                'Sink / hose install labour for booked variation',
                'Inlet and drain connection within scope',
                'Leak check after fitting',
                'Secure mounting within practical limits',
                'Work-area tidy-up',
            ], [
                'Cost of sink, hose, or mixer',
                'Counter cut-out fabrication unless agreed',
                'Full kitchen plumbing package (book Full Kitchen Plumbing)',
                'RO / purifier install (book Home Appliances)',
            ]),
            'plumbing-pipe-install' => self::install($name, 'PVC, CPVC, metal, concealed, and external pipes', [
                'PVC / CPVC runs', 'GI / metal pipes', 'Concealed piping', 'External piping',
            ], [
                'Pipe install labour for booked variation',
                'Safe routing and jointing within confirmed scope',
                'Basic pressure / leak check',
                'Point readiness as agreed on site',
                'Work-area tidy-up',
            ], [
                'Cost of pipes, fittings, clamps, or primers',
                'Full bathroom / kitchen / house package work',
                'Civil chasing and plaster finish unless agreed',
                'Municipality digging permissions for outdoor runs',
            ]),
            'plumbing-drain-install' => self::install($name, 'floor drains, nahani traps, covers, and waste pipes', [
                'Floor drains / nahani traps', 'Drain covers', 'Waste pipe installs', 'Bathroom drainage points',
            ], [
                'Drain / waste install labour for booked variation',
                'Proper slope and trap attention within scope',
                'Flow check after install',
                'Neat finishing within practical limits',
                'Cleanup after service',
            ], [
                'Cost of trap, cover, or waste pipe materials',
                'Major sewer line replacement',
                'Civil flooring / tile redo unless agreed',
                'Septic tank or outdoor drain construction',
            ]),
            'plumbing-motor-install' => self::install($name, 'water motors and pump piping', [
                'Water motor / pump install', 'Motor with piping', 'Home water pressure pump setup',
            ], [
                'Motor / pump install labour for booked variation',
                'Basic piping connection within scope',
                'Start and flow check after install',
                'Guidance on safe usage at handover',
                'Work-area cleanup',
            ], [
                'Cost of motor, pump, starter, or pipe materials',
                'Electrical point / wiring (book Electrician)',
                'Tank fabrication or civil platform work unless agreed',
                'Borewell drilling',
            ]),
            'plumbing-tank-install' => self::install($name, 'overhead tank connections, float valves, and covers', [
                'Overhead tank connections', 'Float valve / ball cock', 'Tank cover fitting',
            ], [
                'Tank connection / valve / cover labour for booked variation',
                'Basic overflow and inlet check within scope',
                'Leak check after connection work',
                'Handover guidance',
                'Site tidy-up as practical',
            ], [
                'Cost of tank, float valve, cover, or fittings',
                'Tank cleaning / disinfection packages',
                'Roof civil work or tank stand fabrication unless agreed',
                'Motor install (book Plumbing Motor Install)',
            ]),
            'plumbing-geyser-connection' => self::install($name, 'geyser hot and cold water connections', [
                'Geyser water inlet/outlet connection', 'Hot line connection', 'Cold line connection',
            ], [
                'Hot / cold water connection labour',
                'Secure jointing within practical scope',
                'Leak check after connection',
                'Basic flow test',
                'Cleanup after service',
            ], [
                'Cost of geyser unit, hoses, or valves',
                'Geyser electrical point or body mounting (Electrician / Appliances)',
                'Major pipe reroute beyond connection points',
                'Civil wall cutting unless agreed',
            ]),
            'plumbing-accessory-install' => self::install($name, 'shut-off valves, non-return valves, and pressure pump connections', [
                'Shut-off valves', 'Non-return valves', 'Pressure pump connections', 'Small plumbing accessories',
            ], [
                'Accessory install labour for booked variation',
                'Safe connection within scope',
                'Leak / function check after install',
                'Neat finishing of connection point',
                'Cleanup after service',
            ], [
                'Cost of valves, pump, or accessory units',
                'Full pipe redesign',
                'Electrical work for pumps (book Electrician)',
                'Major civil opening unless agreed',
            ]),
            'plumbing-full-bathroom-plumbing' => self::inspection($name, 'full bathroom plumbing planning and setup', [
                'New bathroom plumbing', 'Bathroom renovation plumbing', 'Full bathroom wet-area setup quotes',
            ], true),
            'plumbing-full-kitchen-plumbing' => self::inspection($name, 'full kitchen plumbing planning and setup', [
                'New kitchen plumbing', 'Kitchen renovation plumbing', 'Kitchen wet-area setup quotes',
            ], true),
            'plumbing-full-house-plumbing' => self::inspection($name, 'full-house plumbing planning', [
                'New home plumbing', 'Full house plumbing estimates', 'Whole-home wet services planning',
            ], true),

            'plumbing-tap-repair' => self::repair($name, 'leaking and faulty taps and mixers', [
                'Leaking taps', 'Mixer faults', 'Shower mixer issues', 'Dripping fittings',
            ], [
                'On-site diagnosis of the tap / mixer issue',
                'Repair labour within practical scope for booked variation',
                'Washer / cartridge attention when practical',
                'Leak test after repair',
                'Cleanup of work area',
            ], [
                'Cost of cartridges, washers, or replacement taps',
                'Full new tap install package beyond repair',
                'Concealed pipe replacement behind walls unless agreed',
            ]),
            'plumbing-shower-repair' => self::repair($name, 'shower heads, arms, and jet sprays', [
                'Weak shower spray', 'Leaking shower arm', 'Faulty jet spray / bidet', 'Loose shower fittings',
            ], [
                'On-site shower fault diagnosis',
                'Repair labour for booked variation within scope',
                'Refit / seal attention when practical',
                'Spray and leak test after repair',
                'Work-area cleanup',
            ], [
                'Cost of new shower head, arm, or jet spray',
                'Full shower mixer replacement install if repair is not practical',
                'Concealed mixer body replacement unless parts available',
            ]),
            'plumbing-basin-repair' => self::repair($name, 'basin leaks and blockages', [
                'Bottle trap leaks', 'Waste pipe leaks', 'Basin blockages', 'Under-basin drips',
            ], [
                'On-site basin diagnosis',
                'Repair or clearing labour for booked variation',
                'Joint / trap attention within scope',
                'Flow and leak check after work',
                'Cleanup after service',
            ], [
                'Cost of new bottle trap, waste pipe, or fittings',
                'Full basin replacement install',
                'Major drain line excavation',
            ]),
            'plumbing-toilet-repair' => self::repair($name, 'flush tanks, weak flush, and cisterna issues', [
                'Running flush', 'Weak flush', 'External / concealed flush tank faults', 'Seat / cisterna fixes',
            ], [
                'On-site toilet / flush diagnosis',
                'Repair labour for booked variation within scope',
                'Float / flush kit attention when practical',
                'Flush and leak test after repair',
                'Work-area tidy-up',
            ], [
                'Cost of flush kit, seat, or cistern parts',
                'Full toilet replacement install',
                'Soil pipe civil replacement unless agreed',
            ]),
            'plumbing-drain-repair' => self::repair($name, 'blocked drains, toilet pots, and trap smell issues', [
                'Kitchen sink blockages', 'Wash basin blockages', 'Bathroom floor drains', 'Toilet pot blockages', 'Trap smell issues',
            ], [
                'On-site drain diagnosis',
                'Blockage clearing / repair labour for booked variation',
                'Trap attention within practical scope',
                'Flow check after clearing',
                'Basic hygiene tidy-up of work area',
            ], [
                'Cost of chemicals, rods, or replacement traps beyond visit scope',
                'Camera inspection packages unless agreed',
                'Major sewer excavation or septic work',
            ]),
            'plumbing-pipe-repair' => self::repair($name, 'leaking, burst, concealed, and external pipes', [
                'Joint leaks', 'Burst / damaged pipes', 'Concealed pipe leaks', 'External pipe leaks',
            ], [
                'On-site pipe fault diagnosis',
                'Repair labour for booked piping variation',
                'Safe joint / patch attention within scope',
                'Basic leak check after repair',
                'Work-area tidy-up',
            ], [
                'Cost of new pipe, fittings, or clamps',
                'Full house plumbing redesign',
                'Civil opening and plaster / tile finish unless agreed',
            ]),
            'plumbing-leak-repair' => self::repair($name, 'visible leaks, hidden seepage, and valve leaks', [
                'Visible leaks', 'Hidden / wall seepage', 'Shut-off valve leaks', 'Damp-patch tracing',
            ], [
                'On-site leak diagnosis',
                'Targeted repair labour within booked variation',
                'Safety guidance to limit water damage',
                'Leak check after temporary / agreed fix',
            ], [
                'Cost of parts or major pipe replacement',
                'Waterproofing / painting after seepage',
                'Structural civil repair of walls or slabs',
            ]),
            'plumbing-pressure-repair' => self::repair($name, 'low pressure, no water, and airlock issues', [
                'Low water pressure', 'No water / airlock', 'Uneven tap pressure', 'Supply flow issues',
            ], [
                'On-site pressure / supply diagnosis',
                'Repair labour within booked variation when practical',
                'Airlock / valve attention within scope',
                'Flow check after work',
            ], [
                'Cost of new pumps, valves, or pipe upgrades',
                'Utility street supply faults',
                'Borewell or municipal line work outside home scope',
            ]),
            'plumbing-motor-repair' => self::repair($name, 'water motor and pump faults', [
                'Motor not starting', 'Weak flow', 'Noise / overheating', 'Air cavity issues',
            ], [
                'On-site motor diagnosis',
                'Repair labour for booked symptom within scope',
                'Air cavity / basic service attention when practical',
                'Start and flow test after repair',
                'Work-area cleanup',
            ], [
                'Cost of motor rewind, capacitor, or replacement pump',
                'Full motor replacement install if repair is not practical',
                'Electrical wiring faults (book Electrician)',
            ]),
            'plumbing-tank-repair' => self::repair($name, 'tank overflow, connection leaks, and covers', [
                'Overflow / float valve faults', 'Tank connection leakage', 'Cover change',
            ], [
                'On-site tank issue diagnosis',
                'Repair labour for booked variation',
                'Float / connection attention when practical',
                'Overflow / leak check after repair',
            ], [
                'Cost of float valve, cover, or fittings',
                'Full tank replacement',
                'Tank cleaning / disinfection packages unless agreed',
            ]),
            'plumbing-geyser-connection-repair' => self::repair($name, 'geyser inlet and outlet water leaks', [
                'Geyser inlet leaks', 'Geyser outlet leaks', 'Connection hose drips',
            ], [
                'On-site geyser connection diagnosis',
                'Leak repair labour within booked scope',
                'Joint / hose attention when practical',
                'Leak test after repair',
            ], [
                'Cost of hoses, valves, or geyser unit',
                'Geyser heating / electrical repair (Home Appliances)',
                'Full geyser uninstallation / reinstallation package unless agreed',
            ]),

            'plumbing-site-check' => self::inspection($name, 'plumbing fault finding', [
                'Unknown plumbing problems', 'Leak checks', 'Blockage checks', 'General site diagnosis',
            ], false),
            'plumbing-safety-check' => self::inspection($name, 'home plumbing safety and risk checks', [
                'Full home plumbing check', 'Pipe / joint check', 'Motor / tank check', 'Drain smell / backflow check', 'Winter freeze risk check',
            ], false),
            'plumbing-pre-work-check' => self::inspection($name, 'pre-renovation and full-house plumbing surveys', [
                'Before renovation plumbing checks', 'Full house plumbing survey', 'Scope planning visits', 'Estimate-ready inspections',
            ], true),
            default => self::install($name, 'plumbing work', [
                'Home plumbing jobs', 'Installation visits', 'Repair visits', 'Safety checks',
            ], [
                'On-site review before work starts',
                'Plumbing labour within booked scope',
                'Basic leak / flow check',
                'Neat handover',
            ], [
                'Material cost unless agreed separately',
                'Civil or appliance repair work',
                'Scope outside the confirmed booking',
            ]),
        };
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        if (stripos($variantTitle, 'inspection') !== false || stripos($variantTitle, 'survey') !== false || stripos($variantTitle, 'check') !== false) {
            return "Verified Panun Kaergar plumber inspects the site for {$serviceName} — {$variantTitle}, confirms practical scope, and advises the right plan and estimate.";
        }

        return "{$serviceName} — {$variantTitle}. Verified plumber completes the booked variation safely and hands over neatly.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        $inspectionSlugs = [
            'plumbing-full-bathroom-plumbing',
            'plumbing-full-kitchen-plumbing',
            'plumbing-full-house-plumbing',
            'plumbing-site-check',
            'plumbing-safety-check',
            'plumbing-pre-work-check',
        ];

        if (in_array($serviceSlug, $inspectionSlugs, true)) {
            return 'Inspection fee may be adjusted against your final plumbing bill if you proceed with the full service through Panun Kaergar.';
        }

        return 'Final price is based on the selected variation. Extra piping, parts, or materials need confirmation before work starts.';
    }

    private static function install(
        string $name,
        string $focus,
        array $idealFor,
        array $included,
        array $excluded
    ): array {
        return [
            'short_description' => "Expert {$name} by verified Panun Kaergar plumbers at your home.",
            'intro' => "Safe {$name} with careful fitting, leak checks, and a clean handover for {$focus}.",
            'description' => "{$name} by Panun Kaergar connects you with verified plumbers for {$focus}. The professional reviews the site, completes the booked installation variation with secure connections and testing, then hands over neatly. For booking help, call or WhatsApp Panun Kaergar support from the app or website — contact options are also available in the app profile and contact sections.",
            'card_highlights' => [
                self::highlight('tools', 'Expert Installation', 'blue', 0),
                self::highlight('quality', 'Leak Checked', 'green', 1),
                self::highlight('verified', 'Verified Plumbers', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Choose the right variation and share photos or details for {$name}."),
                self::step('verified', 'Plumber assigned', 'A verified Panun Kaergar plumber confirms the visit and arrives with tools.'),
                self::step('location', 'On-site check', 'Water points, access, and fittings are reviewed before work begins.', 'thumb'),
                self::step('tools', 'Installation work', 'Fitting and plumbing connections are completed for the booked variation.', 'cover'),
                self::step('quality', 'Test & handover', 'Leak/flow checks are done, the area is cleaned, and care tips are shared.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Please keep the tap, toilet, sink, motor, or fitting ready unless confirmed otherwise.',
                'Clear access to the water point and shut-off valve helps complete the job in one visit.',
                'Parts and materials are usually extra unless listed in your booking.',
                'Share photos and issue/install details when booking for best results.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling where possible.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => [
                self::faq("What is covered in {$name}?", 'The plumber completes the booked variation after a quick on-site check, focusing on secure fitting, connections, and leak/flow testing.'),
                self::faq('Are materials included?', 'Labour is included for the booked variation. Taps, pipes, toilets, motors, and accessories are usually customer-supplied unless agreed.'),
                self::faq('How do I contact Panun Kaergar?', 'Use call, WhatsApp, the website booking form, or the Panun Kaergar app — support details are available in the app profile and contact sections.'),
                self::faq('Is the work tested before handover?', 'Yes. A basic leak/flow check and neat handover are completed before the plumber leaves.'),
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
            'short_description' => "Professional {$name} for homes by verified Panun Kaergar plumbers.",
            'intro' => "Reliable {$name} after on-site diagnosis for {$focus}.",
            'description' => "{$name} by Panun Kaergar starts with a verified plumber inspecting {$focus}, confirming what can be fixed on the visit, and completing agreed repair work safely. For help booking, use call, WhatsApp, website, or the app profile/contact options.",
            'card_highlights' => [
                self::highlight('tools', 'Expert Repair', 'blue', 0),
                self::highlight('quality', 'On-site Diagnosis', 'green', 1),
                self::highlight('verified', 'Verified Plumbers', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book repair', "Share photos and issue details for {$name}."),
                self::step('location', 'On-site diagnosis', 'Plumber inspects the fault and confirms practical repair scope.', 'thumb'),
                self::step('tools', 'Repair work', 'Agreed plumbing repairs are completed carefully.', 'cover'),
                self::step('quality', 'Leak / flow test', 'Water points are checked after the repair.'),
                self::step('sparkle', 'Handover', 'Work area cleaned and care tips shared.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Keep spare parts ready if you already have matching washers, traps, or valves.',
                'Major damage may need parts or a follow-up visit — quoted on site.',
                'Clear access to the shut-off valve and fault area helps complete repairs faster.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling where possible.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => [
                self::faq("What is covered in {$name}?", 'After diagnosis, the plumber completes agreed repair labour within practical on-site scope for the reported issue.'),
                self::faq('Are spare parts included?', 'Not by default. Parts such as washers, traps, valves, or pipes are usually extra unless agreed.'),
                self::faq('How do I contact Panun Kaergar?', 'Use call, WhatsApp, web booking, or the app — support/contact is available from your profile area.'),
                self::faq('Can every issue be fixed in one visit?', 'Minor issues often yes; major faults or missing parts may need a follow-up or separate install booking.'),
            ],
        ];
    }

    private static function inspection(string $name, string $focus, array $idealFor, bool $quoteHeavy): array
    {
        $quoteNote = $quoteHeavy
            ? 'This visit is for inspection and quotation. Final installation/repair price is confirmed after the survey.'
            : 'The plumber diagnoses the issue and advises the safest next step.';

        return [
            'short_description' => "Professional {$name} by verified Panun Kaergar plumbers.",
            'intro' => "Clear {$name} for {$focus}, with practical guidance before major work.",
            'description' => "{$name} by Panun Kaergar sends a verified plumber to inspect {$focus}, identify risks or scope, and explain the recommended plan. {$quoteNote} For booking help, call or WhatsApp support from the app or website — contact options are in the app profile and contact sections.",
            'card_highlights' => [
                self::highlight('tools', 'Expert Inspection', 'blue', 0),
                self::highlight('quality', 'Clear Diagnosis', 'green', 1),
                self::highlight('verified', 'Verified Plumbers', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book inspection', "Share photos and concerns for {$name}."),
                self::step('location', 'Site inspection', 'Plumber checks pipes, drains, tanks, or wet areas as booked.', 'thumb'),
                self::step('tools', 'Diagnosis', 'Findings and practical options are explained clearly.', 'cover'),
                self::step('quality', 'Recommendations', 'Repair, install, or safety next steps are advised.'),
                self::step('sparkle', 'Handover', 'Summary shared; inspection fee may adjust against full work if you proceed.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included([
                'On-site plumbing inspection for the booked variation',
                'Fault/safety/scope assessment within practical limits',
                'Clear recommendation on next steps',
                'Basic checks relevant to the booked visit',
                'Guidance for follow-up install or repair booking',
            ]),
            'good_to_know' => self::notes([
                $quoteNote,
                'Inspection fee may be adjusted against the final bill if you proceed through Panun Kaergar.',
                'Keep water-point access clear and share recent issue photos when booking.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before the inspection slot to reschedule when possible.',
            ]),
            'whats_not_included' => self::included([
                'Full repair or installation labour beyond inspection unless agreed on site',
                'Cost of parts, pipes, traps, or accessories',
                'Utility street supply or municipal line faults',
                'Civil opening/plaster/tile work',
            ]),
            'faqs' => [
                self::faq("What happens in {$name}?", 'A verified plumber visits, inspects the booked concern, and explains practical findings and next steps.'),
                self::faq('Is the inspection fee adjusted later?', 'It may be adjusted against the final plumbing bill if you proceed with the full job through Panun Kaergar.'),
                self::faq('How do I contact support?', 'Call, WhatsApp, book online, or use the Panun Kaergar app profile/contact options.'),
                self::faq('Will the issue be repaired in the same visit?', 'Minor fixes may be possible if parts and scope allow; larger jobs are quoted and booked after inspection.'),
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
