<?php

class ElectricalContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Electric Service');

        return match ($slug) {
            'electric-light-install' => self::install($name, 'home lighting fixtures', [
                'New bulb fitting', 'Tube lights', 'Ceiling lights', 'Hanging lights', 'Chandeliers', 'Decorative lights',
            ], [
                'Safe fitting of the booked light type',
                'Basic connection check at the point',
                'Secure mounting within practical scope',
                'Power-on test after install',
                'Work-area tidy-up',
            ], [
                'Cost of lights, bulbs, holders, or decorative fittings',
                'Major rewiring beyond the light point',
                'False ceiling fabrication or civil cutting',
                'Designer chandelier scaffolding for extreme heights unless agreed',
            ]),
            'electric-fan-install' => self::install($name, 'ceiling, exhaust, and BLDC fans', [
                'Ceiling fans', 'Exhaust fans', 'BLDC / smart fans', 'Fan replacement installs',
            ], [
                'Fan mounting and secure fixing for booked type',
                'Electrical connection at the fan point',
                'Basic balance and run test',
                'Regulator connection check where applicable',
                'Work-area cleanup',
            ], [
                'Cost of fan, regulator, or spare hardware',
                'New fan point wiring (book Electric Point / Wiring Install)',
                'False ceiling cutting or civil work',
                'Remote pairing issues outside basic install scope',
            ]),
            'electric-switch-install' => self::install($name, 'switches, sockets, and switchboards', [
                'New switches', 'Sockets', 'Fan regulators', 'Switchboards', 'AC switchboards',
            ], [
                'Fitting of booked switch/socket/board variation',
                'Safe termination within the existing point/box',
                'Basic continuity and power check',
                'Neat cover plate finishing within scope',
                'Cleanup after install',
            ], [
                'Cost of switches, sockets, boards, or modular plates',
                'New concealed wiring or box chasing beyond variation',
                'Civil plaster repair after box cutting',
                'Smart-home hub programming unless agreed',
            ]),
            'electric-wiring-install' => self::install($name, 'internal, external, concealed, and underground wiring', [
                'Internal wiring', 'External wiring', 'Concealed wiring', 'Underground wiring', 'New room wiring',
            ], [
                'Wiring labour for the booked variation',
                'Safe routing and termination within confirmed scope',
                'Basic insulation and power check',
                'Point readiness as agreed on site',
                'Work-area tidy-up',
            ], [
                'Cost of wires, casing, conduits, or accessories',
                'Full-house package work (book Full House Wiring inspection)',
                'Civil chasing/plaster finish unless agreed',
                'Municipality digging permissions for underground runs',
            ]),
            'electric-full-house-wiring' => self::inspection($name, 'full-house wiring planning', [
                'New home wiring', 'Full rewiring plans', '1/2/3 BHK estimates', 'DB + whole-home setup quotes',
            ], true),
            'electric-point-install' => self::install($name, 'dedicated appliance power points', [
                'Geyser points', 'AC points', 'Exhaust / chimney points', 'New high-load points',
            ], [
                'Dedicated point installation labour for booked type',
                'Safe connection from suitable supply path within scope',
                'Basic load suitability check on site',
                'Power-on test after install',
                'Cleanup of work area',
            ], [
                'Cost of wires, MCB, sockets, or isolators',
                'Appliance installation itself (AC/geyser body fitting)',
                'Major DB upgrade unless booked separately',
                'Civil cutting/plaster finish unless agreed',
            ]),
            'electric-mcb-install' => self::install($name, 'MCB and DB panel installation', [
                'New MCB fitting', 'DB panel install', 'Distribution upgrades', 'Safety isolation adds',
            ], [
                'MCB or DB panel install labour for booked variation',
                'Safe termination and labeling within scope',
                'Basic trip/function check',
                'Panel cover refit where applicable',
                'Work-area cleanup',
            ], [
                'Cost of MCB, DB box, bus bars, or accessories',
                'Full house rewiring',
                'Utility meter work outside electrician labour',
                'Civil niche cutting unless agreed',
            ]),
            'electric-earthing-install' => self::install($name, 'new home earthing systems', [
                'New earthing pits', 'Earthing upgrades', 'Safety grounding for homes', 'Pre-appliance earthing needs',
            ], [
                'New earthing install labour within booked scope',
                'Connection to agreed home earthing path',
                'Basic continuity/earthing check after work',
                'Site tidy-up as practical',
            ], [
                'Cost of earthing electrode, salt/charcoal, or cable',
                'Civil excavation beyond practical home scope',
                'Utility approval paperwork',
                'Lightning protection systems unless agreed',
            ]),
            'electric-accessory-install' => self::install($name, 'stabilizers, submeters, and doorbells', [
                'Stabilizer install', 'Submeter fitting', 'Doorbell install', 'Small electrical accessories',
            ], [
                'Accessory install labour for booked variation',
                'Basic electrical connection within scope',
                'Function test after install',
                'Neat finishing of connection point',
                'Cleanup after service',
            ], [
                'Cost of stabilizer, submeter, or doorbell unit',
                'Inverter repair (book Home Appliances)',
                'Major wiring upgrades beyond accessory point',
                'Utility meter sealing/official meter work',
            ]),
            'electric-inverter-install' => self::install($name, 'inverter / UPS wiring installation', [
                'Inverter with wiring', 'UPS changeover wiring', 'Backup power point setup', 'DB-side inverter connect',
            ], [
                'Inverter/UPS wiring install labour',
                'Safe changeover/point connection within scope',
                'Basic power path check after install',
                'Guidance on safe usage at handover',
                'Work-area cleanup',
            ], [
                'Cost of inverter, UPS, or batteries',
                'Inverter/UPS product repair (Home Appliances)',
                'Battery replacement or PCB repair',
                'Solar panel mounting (separate solar scope)',
            ]),
            'electric-solar-inverter-install' => self::inspection($name, 'solar inverter wiring scope', [
                'Solar inverter wiring quotes', 'ACDB/DCDB connection plans', 'Earthing + DB link checks', 'Home solar electrical readiness',
            ], true),
            'electric-temporary-wiring' => self::install($name, 'event and temporary power setups', [
                'Wedding / event wiring', 'Temporary power setups', 'Short-term outdoor points', 'Function lighting feed',
            ], [
                'Temporary wiring labour for booked setup',
                'Safe temporary routing within confirmed scope',
                'Basic load and safety check',
                'Handover guidance for temporary use',
            ], [
                'Cost of cables, boards, or hired equipment',
                'Permanent house rewiring',
                'Generator hire or fuel',
                'Overnight standby operator unless agreed',
            ]),
            'electric-light-repair' => self::repair($name, 'light fixtures and fittings', [
                'Dead lights', 'Flickering tubes', 'Ceiling light faults', 'Holder issues',
            ], [
                'On-site diagnosis of the lighting fault',
                'Repair labour within practical scope for booked variation',
                'Basic connection/refit attention',
                'Power-on test after repair',
                'Cleanup of work area',
            ], [
                'Cost of new bulbs, drivers, or fixtures',
                'Full new light installation package beyond repair',
                'Decorative fabrication or false ceiling repair',
            ]),
            'electric-fan-repair' => self::repair($name, 'ceiling and exhaust fan faults', [
                'Fan not spinning', 'Slow speed', 'Noisy fan', 'Regulator-related fan issues',
            ], [
                'On-site fan fault diagnosis',
                'Repair labour for booked symptom within scope',
                'Capacitor/regulator attention when practical',
                'Run test after repair',
                'Work-area cleanup',
            ], [
                'Cost of capacitor, regulator, or replacement fan',
                'Full fan replacement install if repair is not practical',
                'Remote/PCB board replacement unless available on visit',
            ]),
            'electric-switch-repair' => self::repair($name, 'switches, sockets, and switchboards', [
                'Dead switches', 'Sparking sockets', 'Loose boards', 'Warm switch plates',
            ], [
                'On-site diagnosis of switch/socket/board issue',
                'Repair or safe refit labour within booked variation',
                'Basic continuity check',
                'Function test after repair',
                'Cleanup after service',
            ], [
                'Cost of new switches, sockets, or boards',
                'Full rewiring behind the wall',
                'Civil repair of broken wall boxes',
            ]),
            'electric-mcb-repair' => self::repair($name, 'MCB and fuse faults', [
                'Tripping MCB', 'Faulty fuse', 'Warm MCB', 'Isolation failures',
            ], [
                'On-site MCB/fuse diagnosis',
                'Repair or safe replacement labour within scope',
                'Basic trip/function check',
                'Panel tidy-up within practical scope',
            ], [
                'Cost of new MCB or fuse units',
                'Full DB panel redesign',
                'Utility-side meter faults',
            ]),
            'electric-wiring-repair' => self::repair($name, 'damaged or faulty house wiring', [
                'Internal wire faults', 'External wire damage', 'Concealed faults', 'Underground issues', 'Burnt wires',
            ], [
                'On-site wiring fault diagnosis',
                'Repair labour for booked wiring variation',
                'Safe retermination / patch within scope',
                'Basic insulation and power check',
                'Work-area tidy-up',
            ], [
                'Cost of new wire, casing, or conduit',
                'Full house rewiring package',
                'Civil opening and plaster finishing unless agreed',
            ]),
            'electric-power-repair' => self::repair($name, 'short circuit, tripping, and voltage issues', [
                'Short circuits', 'Frequent tripping', 'Voltage issues', 'PCB / auto-cut faults',
            ], [
                'On-site power fault diagnosis',
                'Targeted repair labour within booked variation',
                'Safety checks on affected circuit',
                'Guidance on next steps if parts are needed',
            ], [
                'Cost of PCB, transformers, or major spare parts',
                'Appliance internal repairs (book Appliance category)',
                'Utility transformer or street supply faults',
            ]),
            'electric-db-panel-repair' => self::repair($name, 'DB panel faults and overheating', [
                'Panel overheating', 'Burning smell at DB', 'Loose bus connections', 'Unstable DB feed',
            ], [
                'On-site DB panel inspection',
                'Repair labour for confirmed panel fault within scope',
                'Safe tightening/refit attention',
                'Basic thermal/function check after work',
            ], [
                'Cost of new DB box, bus bar, or MCBs',
                'Full electrical redesign of the home',
                'Utility meter replacement',
            ]),
            'electric-earthing-repair' => self::repair($name, 'weak or failed earthing', [
                'Failed earthing', 'Shock risk grounding fix', 'Earthing continuity repair', 'Ground upgrade labour',
            ], [
                'On-site earthing diagnosis',
                'Earthing repair labour within booked scope',
                'Continuity check after fix',
                'Safety guidance at handover',
            ], [
                'Cost of new earthing materials',
                'Major civil excavation packages',
                'Lightning arrestor systems unless agreed',
            ]),
            'electric-site-check' => self::inspection($name, 'electrical fault finding', [
                'Unknown electrical problems', 'General fault checks', 'No-power diagnosis', 'Intermittent issues',
            ], false),
            'electric-safety-check' => self::inspection($name, 'home electrical safety', [
                'Full home safety check', 'Earthing check', 'MCB / DB check', 'Voltage / load check', 'Short-circuit risk check',
            ], false),
            'electric-pre-work-check' => self::inspection($name, 'pre-renovation and full-house wiring surveys', [
                'Before renovation checks', 'Full house wiring survey', 'Scope planning visits', 'Estimate-ready inspections',
            ], true),
            default => self::install($name, 'electrical work', [
                'Home electrical jobs', 'Installation visits', 'Repair visits', 'Safety checks',
            ], [
                'On-site review before work starts',
                'Electrical labour within booked scope',
                'Basic safety and function check',
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
            return "Verified Panun Kaergar electrician inspects the site for {$serviceName} — {$variantTitle}, confirms practical scope, and advises the right plan and estimate.";
        }

        return "{$serviceName} — {$variantTitle}. Verified electrician completes the booked variation safely and hands over neatly.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        $inspectionSlugs = [
            'electric-full-house-wiring',
            'electric-solar-inverter-install',
            'electric-site-check',
            'electric-safety-check',
            'electric-pre-work-check',
        ];

        if (in_array($serviceSlug, $inspectionSlugs, true)) {
            return 'Inspection fee may be adjusted against your final electrical bill if you proceed with the full service through Panun Kaergar.';
        }

        return 'Final price is based on the selected variation. Extra wiring, parts, or materials need confirmation before work starts.';
    }

    private static function install(
        string $name,
        string $focus,
        array $idealFor,
        array $included,
        array $excluded
    ): array {
        return [
            'short_description' => "Expert {$name} by verified Panun Kaergar electricians at your home.",
            'intro' => "Safe {$name} with careful fitting, testing, and a clean handover for {$focus}.",
            'description' => "{$name} by Panun Kaergar connects you with verified electricians for {$focus}. The professional reviews the site, completes the booked installation variation with safe connections and testing, then hands over neatly. For booking help, call or WhatsApp Panun Kaergar support from the app or website — contact options are also available in the app profile and contact sections.",
            'card_highlights' => [
                self::highlight('tools', 'Expert Installation', 'blue', 0),
                self::highlight('quality', 'Safety Checked', 'green', 1),
                self::highlight('verified', 'Verified Electricians', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Choose the right variation and share photos or details for {$name}."),
                self::step('verified', 'Electrician assigned', 'A verified Panun Kaergar electrician confirms the visit and arrives with tools.'),
                self::step('location', 'On-site check', 'Point, load, and safety conditions are reviewed before work begins.', 'thumb'),
                self::step('tools', 'Installation work', 'Fitting and electrical connections are completed for the booked variation.', 'cover'),
                self::step('quality', 'Test & handover', 'Power and safety checks are done, the area is cleaned, and care tips are shared.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Please keep the light, fan, switchboard, inverter, or accessory ready unless confirmed otherwise.',
                'Clear access to the switchboard and work area helps complete the job in one visit.',
                'Parts and materials are usually extra unless listed in your booking.',
                'Share photos and issue/install details when booking for best results.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling where possible.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => [
                self::faq("What is covered in {$name}?", 'The electrician completes the booked variation after a quick on-site check, focusing on safe fitting, connections, and testing.'),
                self::faq('Are materials included?', 'Labour is included for the booked variation. Lights, fans, wires, MCBs, and accessories are usually customer-supplied unless agreed.'),
                self::faq('How do I contact Panun Kaergar?', 'Use call, WhatsApp, the website booking form, or the Panun Kaergar app — support details are available in the app profile and contact sections.'),
                self::faq('Is the work tested before handover?', 'Yes. A basic power/safety check and neat handover are completed before the electrician leaves.'),
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
            'short_description' => "Professional {$name} for homes by verified Panun Kaergar electricians.",
            'intro' => "Reliable {$name} after on-site diagnosis for {$focus}.",
            'description' => "{$name} by Panun Kaergar starts with a verified electrician inspecting {$focus}, confirming what can be fixed on the visit, and completing agreed repair work safely. For help booking, use call, WhatsApp, website, or the app profile/contact options.",
            'card_highlights' => [
                self::highlight('tools', 'Expert Repair', 'blue', 0),
                self::highlight('quality', 'On-site Diagnosis', 'green', 1),
                self::highlight('verified', 'Verified Electricians', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book repair', "Share photos and issue details for {$name}."),
                self::step('location', 'On-site diagnosis', 'Electrician inspects the fault and confirms practical repair scope.', 'thumb'),
                self::step('tools', 'Repair work', 'Agreed electrical repairs are completed safely.', 'cover'),
                self::step('quality', 'Safety test', 'Circuit function and basic safety are checked.'),
                self::step('sparkle', 'Handover', 'Work area cleaned and care tips shared.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Keep spare parts ready if you already have matching switches, capacitors, or MCBs.',
                'Major damage may need parts or a follow-up visit — quoted on site.',
                'Clear access to the DB panel and fault area helps complete repairs faster.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling where possible.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => [
                self::faq("What is covered in {$name}?", 'After diagnosis, the electrician completes agreed repair labour within practical on-site scope for the reported issue.'),
                self::faq('Are spare parts included?', 'Not by default. Parts such as MCB, switches, capacitors, or wire are usually extra unless agreed.'),
                self::faq('How do I contact Panun Kaergar?', 'Use call, WhatsApp, web booking, or the app — support/contact is available from your profile area.'),
                self::faq('Can every issue be fixed in one visit?', 'Minor issues often yes; major faults or missing parts may need a follow-up or separate install booking.'),
            ],
        ];
    }

    private static function inspection(string $name, string $focus, array $idealFor, bool $quoteHeavy): array
    {
        $quoteNote = $quoteHeavy
            ? 'This visit is for inspection and quotation. Final installation/repair price is confirmed after the survey.'
            : 'The electrician diagnoses the issue and advises the safest next step.';

        return [
            'short_description' => "Professional {$name} by verified Panun Kaergar electricians.",
            'intro' => "Clear {$name} for {$focus}, with practical guidance before major work.",
            'description' => "{$name} by Panun Kaergar sends a verified electrician to inspect {$focus}, identify risks or scope, and explain the recommended plan. {$quoteNote} For booking help, call or WhatsApp support from the app or website — contact options are in the app profile and contact sections.",
            'card_highlights' => [
                self::highlight('tools', 'Expert Inspection', 'blue', 0),
                self::highlight('quality', 'Clear Diagnosis', 'green', 1),
                self::highlight('verified', 'Verified Electricians', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book inspection', "Share photos and concerns for {$name}."),
                self::step('location', 'Site inspection', 'Electrician checks wiring, points, load, or safety items as booked.', 'thumb'),
                self::step('tools', 'Diagnosis', 'Findings and practical options are explained clearly.', 'cover'),
                self::step('quality', 'Recommendations', 'Repair, install, or safety next steps are advised.'),
                self::step('sparkle', 'Handover', 'Summary shared; inspection fee may adjust against full work if you proceed.'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included([
                'On-site electrical inspection for the booked variation',
                'Fault/safety/scope assessment within practical limits',
                'Clear recommendation on next steps',
                'Basic testing relevant to the booked check',
                'Guidance for follow-up install or repair booking',
            ]),
            'good_to_know' => self::notes([
                $quoteNote,
                'Inspection fee may be adjusted against the final bill if you proceed through Panun Kaergar.',
                'Keep DB panel access clear and share recent issue photos when booking.',
                'For support, use call, WhatsApp, website, or the app profile/contact sections.',
                'Notify at least 2 hours before the inspection slot to reschedule when possible.',
            ]),
            'whats_not_included' => self::included([
                'Full repair or installation labour beyond inspection unless agreed on site',
                'Cost of parts, wires, MCBs, or accessories',
                'Utility company meter or street-supply faults',
                'Civil opening/plaster work',
            ]),
            'faqs' => [
                self::faq("What happens in {$name}?", 'A verified electrician visits, inspects the booked concern, and explains practical findings and next steps.'),
                self::faq('Is the inspection fee adjusted later?', 'It may be adjusted against the final electrical bill if you proceed with the full job through Panun Kaergar.'),
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
