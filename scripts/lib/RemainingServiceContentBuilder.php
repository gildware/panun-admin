<?php

class RemainingServiceContentBuilder
{
    public static function build(array $service): array
    {
        return match ($service['role']) {
            'repair' => self::buildRepair($service),
            'install' => self::buildInstall($service),
            'cleaning' => self::buildCleaning($service),
            'painting' => self::buildPainting($service),
            'masonry-install' => self::buildMasonryInstall($service),
            'masonry-repair' => self::buildMasonryRepair($service),
            'salon' => self::buildSalon($service),
            'laundry' => self::buildLaundry($service),
            'hourly-booking' => self::buildHourlyBooking($service),
            default => throw new InvalidArgumentException('Unsupported role: '.$service['role']),
        };
    }

    private static function buildRepair(array $service): array
    {
        $name = $service['name'];
        $focus = self::categoryFocus($service['category'], 'repair');

        return [
            'short_description' => "Reliable {$name} by verified Panun Kaergar technicians for careful diagnosis, expert repair, and tested handover.",
            'intro' => "Fast on-site assessment and dependable {$name} from verified technicians.",
            'description' => "{$name} by Panun Kaergar helps restore safe performance with a technician-led site check, clear scope confirmation, careful repair work, and a clean handover. The visit focuses on {$focus}, so the issue is understood before work begins and the final result is checked before the technician leaves.",
            'card_highlights' => [
                self::highlight('tools', 'Expert Diagnosis', 'blue', 0),
                self::highlight('quality', 'Tested Repair', 'green', 1),
                self::highlight('verified', 'Verified Technicians', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Share the issue, address, and clear photos of your {$name} requirement so the right technician can be assigned."),
                self::step('verified', 'Technician assigned', 'A verified Panun Kaergar technician confirms your visit and arrives with the right tools.'),
                self::step('tools', 'Inspection & diagnosis', "The technician checks {$focus} before confirming the repair scope."),
                self::step('quality', 'Repair & testing', 'Approved work is completed carefully and final checks are done before handover.', 'thumb'),
                self::step('sparkle', 'Clean handover', 'Work area is cleaned and basic care guidance is shared before the technician leaves.', 'cover'),
            ],
            'perfect_for' => self::chips([
                'Homes',
                'Offices',
                'Urgent issues',
                'Recurring faults',
                'Wear-and-tear fixes',
                $name,
            ]),
            'whats_included' => self::included([
                'On-site inspection',
                'Issue diagnosis',
                'Scope confirmation before work',
                'Skilled repair within service scope',
                'Basic testing after completion',
                'Neat work-area cleanup',
            ]),
            'good_to_know' => self::notes([
                'Please keep the service area accessible before the technician arrives.',
                'Share photos or a short issue note while booking for faster scoping.',
                'Replacement parts or extra material, if needed, are confirmed separately on site.',
                'Major structural, civil, or brand-warranty work is not included in standard repair scope.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Cost of replacement parts or accessories',
                'Major rebuilds beyond standard service scope',
                'Painting, polishing, or finish restoration unless separately agreed',
                'Brand warranty claims with the original manufacturer or seller',
                'Any hidden issue discovered outside the booked scope',
            ]),
            'faqs' => [
                self::faq("How is {$name} scoped on site?", 'The technician first inspects the issue, confirms what is required, and explains the workable repair scope before proceeding.'),
                self::faq('Are replacement parts included?', 'No. Any extra parts or consumables needed to complete the job are confirmed separately on site.'),
                self::faq("How long does {$name} usually take?", 'Most standard visits are completed within the booked slot, but complex faults or additional parts can extend the turnaround.'),
                self::faq('Will the work be checked before handover?', 'Yes. Panun Kaergar technicians test the completed work and share the outcome before leaving.'),
            ],
        ];
    }

    private static function buildInstall(array $service): array
    {
        $name = $service['name'];
        $focus = self::categoryFocus($service['category'], 'install');

        return [
            'short_description' => "Professional {$name} by verified Panun Kaergar technicians for safe setup, secure fitting, and clean handover.",
            'intro' => "Safe setup and reliable {$name} from verified technicians.",
            'description' => "{$name} by Panun Kaergar is designed for customers who need professional setup, stable fitting, and a clean final handover. The technician checks {$focus}, confirms placement or connection needs, completes the installation carefully, and tests the result before closing the visit.",
            'card_highlights' => [
                self::highlight('tools', 'Professional Setup', 'blue', 0),
                self::highlight('quality', 'Secure Fitting', 'green', 1),
                self::highlight('verified', 'Verified Technicians', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Share your address, installation details, and clear photos for {$name}."),
                self::step('verified', 'Technician assigned', 'A verified Panun Kaergar technician confirms your booking and arrives prepared for the job.'),
                self::step('location', 'Site review', "The technician checks {$focus} and confirms the practical installation scope.", 'thumb'),
                self::step('tools', 'Installation & fitting', 'The service is installed carefully with alignment, safe fitting, and connection checks as needed.'),
                self::step('sparkle', 'Test & handover', 'Final checks are completed, the area is cleaned, and basic usage guidance is shared.', 'cover'),
            ],
            'perfect_for' => self::chips([
                'New setups',
                'Replacement jobs',
                'Home upgrades',
                'Fresh fit-outs',
                'Residential spaces',
                $name,
            ]),
            'whats_included' => self::included([
                'On-site review before work begins',
                'Installation within the booked scope',
                'Basic fitting and alignment',
                'Safety or function check after setup',
                'Clean handover',
            ]),
            'good_to_know' => self::notes([
                'Please keep the site ready and accessible before the visit starts.',
                'Customer-supplied units, fixtures, or approved materials should be available unless agreed otherwise.',
                'Extra fittings, accessories, or structural corrections may be quoted separately on site.',
                'Major civil, rewiring, or fabrication work is not included unless explicitly booked.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Cost of customer-selected products or replacement accessories',
                'Major site preparation or civil alteration',
                'Hidden defect correction outside the booked scope',
                'Brand warranty claims or manufacturer-side support',
                'Haul-away or disposal unless separately agreed',
            ]),
            'faqs' => [
                self::faq("What should I keep ready for {$name}?", 'Please keep the service area accessible and any customer-supplied item, part, or fixture ready before the technician arrives.'),
                self::faq('Is site checking included before fitting?', 'Yes. The technician reviews the area first so the practical scope is confirmed before installation begins.'),
                self::faq('Are materials included in the booking price?', 'Not by default. Customer-selected units, fixtures, and any extra accessories are handled separately unless specifically included.'),
                self::faq('Will the installation be tested before handover?', 'Yes. Panun Kaergar technicians perform a basic post-installation check before completing the visit.'),
            ],
        ];
    }

    private static function buildCleaning(array $service): array
    {
        $name = $service['name'];

        return [
            'short_description' => "Thorough {$name} by trusted Panun Kaergar professionals for cleaner spaces, fresher surfaces, and a polished finish.",
            'intro' => "Detailed {$name} with careful cleaning steps and a fresher handover.",
            'description' => "{$name} by Panun Kaergar is structured for customers who want a cleaner, fresher space with dependable service standards. The team reviews the area first, works through visible dust, grease, stains, and buildup, then completes a final quality check so the cleaned space is ready for everyday use.",
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
            'perfect_for' => self::chips([
                'Routine refresh',
                'Deep cleaning days',
                'Family homes',
                'Move-in prep',
                'Visible buildup',
                $name,
            ]),
            'whats_included' => self::included([
                'Area review before cleaning starts',
                'Targeted cleaning within booked scope',
                'Visible dust, grease, and stain reduction',
                'Final wipe-down and quality check',
                'Neat handover',
            ]),
            'good_to_know' => self::notes([
                'Please remove fragile or highly valuable personal items from the work area before the team arrives.',
                'Heavily neglected spaces may need extra time or additional scope confirmation on site.',
                'Permanent stains, old damage, or material wear may not be fully reversible.',
                'Special restoration, pest control, or repair work is outside standard cleaning scope.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Permanent stain reversal or material repair',
                'Structural repair, pest control, or polishing work',
                'Packing, moving, or disposal outside the cleaning scope',
                'Any extra area not confirmed in the booking',
            ]),
            'faqs' => [
                self::faq("What is covered in {$name}?", 'The team completes the booked cleaning scope after a quick area review and focuses on visible dirt, grease, dust, and overall freshness.'),
                self::faq('Do I need to provide cleaning material?', 'No. Panun Kaergar teams arrive with the required standard tools and supplies unless a special product is discussed in advance.'),
                self::faq('Will all stains be removed completely?', 'Not always. Results depend on the material condition, stain age, and buildup level, but the team will aim for the best possible finish within scope.'),
                self::faq('Is the area checked before the team leaves?', 'Yes. A final walk-through is done before handover.'),
            ],
        ];
    }

    private static function buildPainting(array $service): array
    {
        $name = $service['name'];

        return [
            'short_description' => "Professional {$name} by Panun Kaergar teams for cleaner prep, even coats, and a neat finish.",
            'intro' => "Site-reviewed {$name} with careful prep and an even, cleaner finish.",
            'description' => "{$name} by Panun Kaergar is intended for customers who need a site-reviewed painting job with better surface prep, cleaner edges, and a polished final look. The team first checks the painting area, confirms the practical scope, prepares the surface, and completes the booked application with finish checks before handover.",
            'card_highlights' => [
                self::highlight('brush', 'Surface Prep', 'blue', 0),
                self::highlight('quality', 'Even Finish', 'green', 1),
                self::highlight('verified', 'Professional Team', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Share the space, surface condition, and photos for {$name}."),
                self::step('location', 'Site inspection', 'The team reviews the surface, access, and prep needs before work begins.', 'thumb'),
                self::step('brush', 'Prep & masking', 'Covering, minor prep, and work-area setup are completed for a cleaner job.'),
                self::step('quality', 'Application & finishing', 'The booked coating work is completed carefully with attention to edges and uniformity.'),
                self::step('sparkle', 'Review & handover', 'Final touch-ups are checked and the area is handed over neatly.', 'cover'),
            ],
            'perfect_for' => self::chips([
                'Homes',
                'Shops',
                'Renovation refresh',
                'Wall updates',
                'Visible wear',
                $name,
            ]),
            'whats_included' => self::included([
                'Site review before work',
                'Basic prep and masking within scope',
                'Coating application within booked scope',
                'Finish review and touch-up check',
                'Clean handover',
            ]),
            'good_to_know' => self::notes([
                'Customer-selected paint, shade choices, or special finishes should be confirmed before the visit if not already included.',
                'Damp walls, heavy damage, or major repairs may need separate work before painting can begin.',
                'Drying time can vary based on weather, ventilation, and surface condition.',
                'Scaffolding, major scraping, or large civil correction is not part of standard painting scope unless agreed.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Major wall repair, waterproofing, or civil correction',
                'Scaffolding or specialized access equipment',
                'Extra rooms, elevations, or surfaces outside the booked scope',
                'Shift of heavy furniture unless separately arranged',
            ]),
            'faqs' => [
                self::faq("How is {$name} planned on site?", 'The team first checks the surface and access conditions, then confirms the practical work scope before starting.'),
                self::faq('Are paint materials always included?', 'Not always. Material supply depends on what was booked or agreed in advance.'),
                self::faq('Will the team do prep before application?', 'Yes. Basic masking and prep within the booked scope are completed before application begins.'),
                self::faq('Is a final finish check included?', 'Yes. The team reviews the finish and handles touch-up checks before handover.'),
            ],
        ];
    }

    private static function buildMasonryInstall(array $service): array
    {
        $name = $service['name'];

        return [
            'short_description' => "Professional {$name} by Panun Kaergar masons for planned site work, durable execution, and neat handover.",
            'intro' => "Site-reviewed {$name} with steady execution and dependable finishing.",
            'description' => "{$name} by Panun Kaergar helps customers move forward with site-based masonry work that needs proper review before execution. The mason checks the work area first, confirms practical scope and access, then completes the booked job with attention to alignment, finish quality, and safe handover.",
            'card_highlights' => [
                self::highlight('tools', 'Site Review', 'blue', 0),
                self::highlight('quality', 'Durable Work', 'green', 1),
                self::highlight('verified', 'Skilled Masons', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Share dimensions, photos, and site details for {$name}."),
                self::step('location', 'Site inspection', 'A Panun Kaergar mason reviews access, measurements, and practical requirements on site.', 'thumb'),
                self::step('tools', 'Layout & preparation', 'The work area is aligned with the agreed scope before execution starts.'),
                self::step('quality', 'Execution', 'The booked masonry work is completed carefully with focus on level, jointing, and finish quality.'),
                self::step('sparkle', 'Final review', 'The result is checked with you and the area is handed over as neatly as possible.', 'cover'),
            ],
            'perfect_for' => self::chips([
                'Renovation work',
                'New build stages',
                'Home upgrades',
                'Structural finishing',
                'Measured site jobs',
                $name,
            ]),
            'whats_included' => self::included([
                'On-site review before starting',
                'Practical scope confirmation',
                'Execution within booked masonry scope',
                'Basic finish review',
                'Neat handover',
            ]),
            'good_to_know' => self::notes([
                'Materials, extra labor, or curing-related dependencies may be confirmed separately if not already included.',
                'Large-volume jobs, site delays, or weather impact can affect completion time.',
                'Major demolition, debris haul-away, or structural redesign is not part of standard installation scope unless agreed.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Major demolition or heavy debris removal',
                'Structural redesign or engineering approvals',
                'Any extra measured area outside the booked scope',
                'Material supply unless separately agreed',
            ]),
            'faqs' => [
                self::faq("Why does {$name} need site inspection first?", 'Most masonry installation work depends on actual measurements, surface condition, and access, so the mason confirms the practical scope on site before execution.'),
                self::faq('Are materials included?', 'Material handling depends on what was booked. If extra material is needed, it is confirmed separately.'),
                self::faq('Can completion time change on site?', 'Yes. Quantity, curing needs, and access conditions can change the working timeline.'),
                self::faq('Will the finished work be reviewed before handover?', 'Yes. The completed work is checked with you before the visit is closed.'),
            ],
        ];
    }

    private static function buildMasonryRepair(array $service): array
    {
        $name = $service['name'];

        return [
            'short_description' => "Reliable {$name} by Panun Kaergar masons for careful repair, improved stability, and a neat handover.",
            'intro' => "Site-reviewed {$name} with careful repair steps and better finish control.",
            'description' => "{$name} by Panun Kaergar is designed for customers who need skilled masonry repair with practical on-site review before work begins. The mason checks the damaged area first, confirms the workable repair scope, completes the required correction carefully, and reviews the final condition before handover.",
            'card_highlights' => [
                self::highlight('tools', 'Damage Review', 'blue', 0),
                self::highlight('quality', 'Careful Repair', 'green', 1),
                self::highlight('verified', 'Skilled Masons', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Share photos and location details for {$name}."),
                self::step('location', 'Damage inspection', 'The mason checks cracks, loose areas, level differences, or surface damage first.', 'thumb'),
                self::step('tools', 'Repair preparation', 'Repair scope, surface condition, and practical execution plan are confirmed before work starts.'),
                self::step('quality', 'Repair work', 'The booked masonry repair is completed carefully with finish and stability checks.'),
                self::step('sparkle', 'Review & handover', 'The repaired area is reviewed with you before the visit closes.', 'cover'),
            ],
            'perfect_for' => self::chips([
                'Cracks',
                'Surface correction',
                'Loose areas',
                'Patch work',
                'Home repairs',
                $name,
            ]),
            'whats_included' => self::included([
                'On-site damage review',
                'Repair work within booked scope',
                'Basic finish and stability check',
                'Neat handover',
            ]),
            'good_to_know' => self::notes([
                'Some repairs may need drying or curing time before the area returns to full use.',
                'Old hidden damage can expand the final repair scope once opened up.',
                'Material matching and exact color blending may vary depending on the surface condition.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Large-scale reconstruction or redesign',
                'Material supply unless separately agreed',
                'Full-area replacement outside the repair scope',
                'Painting or decorative finish restoration unless separately booked',
            ]),
            'faqs' => [
                self::faq("What is checked first during {$name}?", 'The mason checks the actual damage, surrounding surface condition, and the practical repair scope before starting.'),
                self::faq('Will the finish match perfectly?', 'The team will aim for a neat result, but exact texture or color matching can vary based on existing surface age and condition.'),
                self::faq('Can hidden damage affect the scope?', 'Yes. If the mason finds deeper damage after inspection, the team will explain the updated repair need before proceeding.'),
                self::faq('Is the repaired area reviewed before handover?', 'Yes. The completed repair is checked with you before the visit is closed.'),
            ],
        ];
    }

    private static function buildHourlyBooking(array $service): array
    {
        $name = $service['name'];
        $trade = match ($service['category_slug'] ?? '') {
            'painting' => 'painter',
            'masonry' => 'mason',
            'carpentary' => 'carpenter',
            default => 'technician',
        };
        $tradeLabel = ucfirst($trade);

        return [
            'short_description' => "Book a verified Panun Kaergar {$trade} by the hour, half-day, or full day for flexible on-site help with tools and skilled hands.",
            'intro' => "Flexible {$name} — choose 1 hour, 4 hours, or a full day with a verified local professional.",
            'description' => "{$name} by Panun Kaergar lets you hire a verified {$trade} for the time you need. Pick a 1-hour slot for quick fixes, 4 hours for medium jobs, or a full day for larger work. Your {$trade} arrives with the right tools, reviews the task on site, completes work within the booked duration, and hands over neatly.",
            'card_highlights' => [
                self::highlight('calendar', 'Flexible Duration', 'blue', 0),
                self::highlight('tools', 'Skilled '.$tradeLabel, 'green', 1),
                self::highlight('verified', 'Verified Professionals', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Choose your duration', "Select 1 hour, 4 hours, or full day for {$name} and share your address and task details."),
                self::step('verified', $tradeLabel.' assigned', "A verified Panun Kaergar {$trade} confirms your slot and arrives with the right tools.", 'thumb'),
                self::step('tools', 'On-site review', 'The professional reviews your task list, access, and materials before starting work.'),
                self::step('quality', 'Work in progress', "Skilled {$trade} work is completed carefully within your booked time.", 'cover'),
                self::step('sparkle', 'Handover', 'The work area is tidied and any follow-up scope is discussed before the visit ends.'),
            ],
            'perfect_for' => self::chips([
                'Quick fixes',
                'Medium repairs',
                'Full-day projects',
                'Home upgrades',
                'On-site help',
                $name,
            ]),
            'whats_included' => self::included([
                'Verified professional for booked duration',
                'Basic hand tools carried by the professional',
                'On-site task review before work begins',
                'Skilled labour within booked time',
                'Basic cleanup before handover',
            ]),
            'good_to_know' => self::notes([
                'Booked time covers labour only unless materials are separately agreed.',
                'Extra time beyond the selected package can be quoted on site if needed.',
                'Please keep the work area accessible and share photos or a task list while booking.',
                'Major materials, civil work, or specialist equipment hire are confirmed separately.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Cost of building materials, paint, tiles, or wood',
                'Specialist machinery or scaffolding hire',
                'Work beyond the booked duration without on-site agreement',
                'Major demolition or debris disposal unless separately booked',
                'Brand warranty or manufacturer support',
            ]),
            'faqs' => [
                self::faq("Which duration should I choose for {$name}?", 'Choose 1 hour for small fixes, 4 hours for medium tasks like multiple fittings or patch work, and full day for larger projects.'),
                self::faq('Are materials included?', 'Labour is included for the booked duration. Materials such as paint, cement, tiles, or wood are typically supplied by the customer unless agreed otherwise.'),
                self::faq("What if the work takes longer than booked?", "Your {$trade} will discuss any extra scope or additional time on site before proceeding."),
                self::faq('Will the professional bring tools?', "Yes. Panun Kaergar {$trade}s arrive with standard hand tools. Specialist materials or equipment are confirmed separately if needed."),
            ],
        ];
    }

    private static function buildSalon(array $service): array
    {
        $name = $service['name'];

        return [
            'short_description' => "Professional {$name} by trained Panun Kaergar stylists for polished grooming, clean technique, and confident results.",
            'intro' => "Comfort-focused {$name} with stylist consultation, neat execution, and aftercare guidance.",
            'description' => "{$name} by Panun Kaergar is delivered by trained stylists who balance comfort, hygiene, and finish quality throughout the appointment. Your stylist first understands the look or grooming goal, prepares the service carefully, completes the booked session with attention to detail, and shares practical aftercare guidance before handover.",
            'card_highlights' => [
                self::highlight('sparkle', 'Polished Finish', 'blue', 0),
                self::highlight('quality', 'Hygiene Focused', 'green', 1),
                self::highlight('verified', 'Trained Stylists', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Choose your preferred package for {$name} and share any style or comfort notes in advance."),
                self::step('verified', 'Stylist assigned', 'A trained Panun Kaergar stylist confirms the booking and arrives prepared for your session.'),
                self::step('quality', 'Consultation & prep', 'Your stylist understands the desired look, skin or hair sensitivity, and prepares the service setup.', 'thumb'),
                self::step('sparkle', 'Service session', 'The booked salon service is completed with care, neat technique, and finish attention.'),
                self::step('check', 'Final look & aftercare', 'The final result is reviewed with you and simple aftercare guidance is shared.', 'cover'),
            ],
            'perfect_for' => self::chips([
                'Routine grooming',
                'Event prep',
                'Self-care days',
                'Weekend refresh',
                'Home comfort',
                $name,
            ]),
            'whats_included' => self::included([
                'Pre-service consultation',
                'Professional stylist-led session',
                'Basic hygiene setup and neat execution',
                'Finish review and aftercare tips',
            ]),
            'good_to_know' => self::notes([
                'Please mention any sensitivity, allergies, or recent skin or hair treatment while booking.',
                'Actual service duration can vary depending on hair length, skin condition, or detailing needs.',
                'Package inclusions can differ between the basic and premium variants shown in the app.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Medical or dermatology treatment',
                'Brand-specific premium products unless included in the selected package',
                'Extra add-ons outside the selected package',
                'Any service not confirmed at booking time',
            ]),
            'faqs' => [
                self::faq("How do I choose the right {$name} package?", 'Use the basic package for a straightforward session and the premium package for a more complete finish or extra care where applicable.'),
                self::faq('Can I share style preferences before the appointment?', 'Yes. Please share your look preference, product sensitivity, or comfort notes while booking so the stylist can prepare well.'),
                self::faq('Are hygiene and cleanup handled during the session?', 'Yes. Panun Kaergar stylists follow a neat, hygiene-focused process throughout the appointment.'),
                self::faq('Will I get aftercare guidance?', 'Yes. Your stylist shares simple aftercare advice relevant to the completed service before handover.'),
            ],
        ];
    }

    private static function buildLaundry(array $service): array
    {
        $name = $service['name'];

        return [
            'short_description' => "Delicate {$name} by Panun Kaergar fabric-care specialists for safer handling, cleaner finish, and ready-to-wear results.",
            'intro' => "Fabric-safe {$name} with careful handling and a polished final finish.",
            'description' => "{$name} by Panun Kaergar is planned for garments and fabric items that need more careful handling than regular washing. The service begins with fabric and condition review, checks for visible stains or detailing needs, then follows the suitable care process so the final item is cleaner, fresher, and better prepared for reuse.",
            'card_highlights' => [
                self::highlight('quality', 'Fabric Safe', 'blue', 0),
                self::highlight('sparkle', 'Fresh Finish', 'green', 1),
                self::highlight('verified', 'Specialist Care', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Share garment details and fabric notes for {$name}."),
                self::step('check', 'Item review', 'Fabric type, embellishments, visible stains, and handling needs are reviewed first.'),
                self::step('quality', 'Care process', 'The suitable cleaning process is selected to protect the garment while improving freshness and finish.'),
                self::step('sparkle', 'Finishing', 'The item is finished, checked, and prepared for neat return or handover.', 'thumb'),
                self::step('verified', 'Ready to wear', 'Final quality review is completed before handover.', 'cover'),
            ],
            'perfect_for' => self::chips([
                'Delicate garments',
                'Occasion wear',
                'Premium fabrics',
                'Careful stain handling',
                'Ready-to-wear finish',
                $name,
            ]),
            'whats_included' => self::included([
                'Fabric and condition review',
                'Suitable cleaning process selection',
                'Basic finishing and quality check',
                'Neat ready-to-wear handover',
            ]),
            'good_to_know' => self::notes([
                'Please mention existing stains, embellishments, fabric sensitivity, or damage before processing.',
                'Some old stains, color bleeding, or wear marks may not fully reverse even with careful treatment.',
                'Turnaround can vary depending on fabric delicacy and finishing requirements.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Guaranteed removal of every old or set stain',
                'Repair of tears, missing embellishments, or damaged lining',
                'Major alteration or tailoring work',
                'Restoration of color loss caused by prior wear or damage',
            ]),
            'faqs' => [
                self::faq("Why is {$name} handled separately?", 'Premium or delicate items often need gentler care steps than regular washing, so the process is chosen based on fabric condition and finish needs.'),
                self::faq('Will every stain come out completely?', 'Not always. Results depend on fabric type, stain age, and prior treatment history, but the item is handled with fabric safety in mind.'),
                self::faq('Can I mention embroidery or embellishments in advance?', 'Yes. Please share those details while booking so the item can be handled more carefully.'),
                self::faq('Is a final quality check included?', 'Yes. The item is reviewed before handover.'),
            ],
        ];
    }

    private static function categoryFocus(string $category, string $mode): string
    {
        return match ($category) {
            'electrical' => $mode === 'repair'
                ? 'wiring, electrical points, safe load handling, and visible fault symptoms'
                : 'wiring paths, load needs, installation points, and safe electrical fitting',
            'plumbing' => $mode === 'repair'
                ? 'leaks, blockages, flow issues, fittings, and pipe condition'
                : 'pipe runs, fixture points, water flow, and fitting alignment',
            default => $mode === 'repair'
                ? 'the affected area, current condition, and the likely repair requirement'
                : 'the work area, fitting points, and the practical installation requirement',
        };
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
