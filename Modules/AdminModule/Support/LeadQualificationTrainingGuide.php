<?php

namespace Modules\AdminModule\Support;

use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\ProviderCancellationReason;

class LeadQualificationTrainingGuide
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function slides(): array
    {
        $slides = [
            self::slideDeckGuide(),
            self::slideMission(),
            self::slideLeadSources(),
            self::slideShiftRoutine(),
            self::slideLeadClassifications(),
            self::slideUsingLeadPage(),
            self::slideWorkflowChecklist(),
            self::slideHandlingUnknowns(),
            self::slideHandlingCustomers(),
            self::slideHandlingProviders(),
            self::slideHandlingFutureCustomers(),
            self::slideHandlingInvalidLeads(),
            self::slideQuiz(),
        ];

        foreach ($slides as $i => &$slide) {
            $slide['number'] = $i + 1;
            $slide = self::applySlideMeta($slide);
        }

        return $slides;
    }

    /**
     * @param array<string, mixed> $slide
     * @return array<string, mixed>
     */
    private static function applySlideMeta(array $slide): array
    {
        $meta = self::slideMetaMap();
        $id = $slide['id'] ?? '';

        if (isset($meta[$id])) {
            $slide = array_merge($meta[$id], $slide);
        }

        if (empty($slide['overview']) && ! empty($slide['intro'])) {
            $slide['overview'] = $slide['intro'];
        }
        unset($slide['intro']);

        return $slide;
    }

    /**
     * Icon (Material Icons) + short overview for every slide.
     *
     * @return array<string, array{icon: string, overview: string}>
     */
    private static function slideMetaMap(): array
    {
        return [
            'deck-guide' => [
                'icon' => 'info',
                'overview' => 'Six words you will see in every slide — read these before starting the training.',
            ],
            'mission' => [
                'icon' => 'verified_user',
                'overview' => 'Thirteen core habits — click any card for full details, illustrated examples, and what to avoid.',
            ],
            'lead-sources' => [
                'icon' => 'hub',
                'overview' => 'Where to check each channel, how not to miss enquiries, and how to manage them in the panel — use this every shift.',
            ],
            'shift-routine' => [
                'icon' => 'schedule',
                'overview' => 'Start, during, and end of every shift — what to scan, when to scan again, and how to handle live leads so nothing is missed.',
            ],
            'lead-classifications' => [
                'icon' => 'label',
                'overview' => 'Five lead types — what each means and the first things to do once you pick the right one.',
            ],
            'using-lead-page' => [
                'icon' => 'dashboard',
                'overview' => 'Full panel guide — list, Add New Lead, follow-ups, call logs, initial recording, Today\'s queue, comments, Open/Closed, dropdown names, click maps, and every sidebar action.',
            ],
            'workflow-checklist' => [
                'icon' => 'checklist_rtl',
                'overview' => 'The workflow FAB on lead details, Workflow Stuck Items queue, and hard vs soft gates before Create Booking or type change.',
            ],
            'handling-unknowns' => [
                'icon' => 'contact_phone',
                'overview' => 'Unknown means you do not know what they want yet — call, run the qualifier, collect the right details, reclassify, then follow the matching slide.',
            ],
            'handling-customers' => [
                'icon' => 'home_repair_service',
                'overview' => 'Customer leads need a first call for full details, Path A or B choice, then booking steps — with examples for every stage including no-pickup.',
            ],
            'handling-providers' => [
                'icon' => 'handshake',
                'overview' => 'Provider onboarding — brief call, documents, final call, then add to panel and WhatsApp group. Same detailed steps as Customer and Unknown.',
            ],
            'handling-future-customers' => [
                'icon' => 'event',
                'overview' => 'Future customer — confirm why no booking today, explain Panun Kaergar, ask for referrals, warm close. Valid success, not Invalid.',
            ],
            'handling-invalid-leads' => [
                'icon' => 'block',
                'overview' => 'Invalid — wrong service or area. Document exactly what they asked, polite WhatsApp, close professionally.',
            ],
            'quiz' => [
                'icon' => 'quiz',
                'overview' => 'Expert certification — scenario-based questions on every slide, edge case, and process rule. A perfect score means you are ready for live leads solo.',
            ],
        ];
    }

    /** @return array{text: string, detail?: string} */
    private static function step(string $text, ?string $detail = null): array
    {
        $s = ['text' => $text];
        if ($detail !== null && $detail !== '') {
            $s['detail'] = $detail;
        }

        return $s;
    }

    /** @return array{text: string} */
    private static function fs(string $text): array
    {
        return ['text' => $text];
    }

    /**
     * @return array{text: string, detail?: string, collect?: string, example?: string, next?: string}
     */
    private static function trainingStep(
        string $text,
        string $detail = '',
        ?string $collect = null,
        ?string $example = null,
        ?string $next = null,
    ): array {
        $step = ['text' => $text];

        if ($detail !== '') {
            $step['detail'] = $detail;
        }
        if ($collect !== null) {
            $step['collect'] = $collect;
        }
        if ($example !== null) {
            $step['example'] = $example;
        }
        if ($next !== null) {
            $step['next'] = $next;
        }

        return $step;
    }

    /**
     * @param  array<int, array<string, mixed>>  $extraSteps
     * @return array{label: string, steps: array<int, array<string, mixed>>}
     */
    private static function workflowTrainingGroup(string $scenarioKey, string $label, array $extraSteps = []): array
    {
        $groups = WorkflowStepDefinitions::trainingPathSteps($scenarioKey);
        $steps = $groups[0]['steps'] ?? [];

        return [
            'label' => $label,
            'steps' => array_merge($steps, $extraSteps),
        ];
    }

    /**
     * @return array<int, array{text: string, detail?: string}>
     */
    private static function workflowStepsForScenario(string $scenarioKey): array
    {
        $steps = [];
        foreach (WorkflowStepDefinitions::scenarioStepKeys($scenarioKey) as $key) {
            $def = WorkflowStepDefinitions::step($key);
            if ($def === null) {
                continue;
            }
            $steps[] = WorkflowStepDefinitions::toTrainingStep($key, $def);
        }

        return $steps;
    }

    /** @return array{mandatory: bool, label: string, template: string, example: string, to: string} */
    private static function wa(string $afterWhat, string $template, string $example, bool $mandatory = true, string $to = 'customer'): array
    {
        $target = match ($to) {
            'provider-group' => 'provider group',
            'provider' => 'provider',
            default => 'customer',
        };

        return [
            'mandatory' => $mandatory,
            'to' => $to,
            'label' => "WhatsApp to {$target}",
            'template' => $template,
            'example' => $example,
        ];
    }

    /** @return array{mandatory: bool, label: string, template: string, example: string, to: string} */
    private static function waMissedCall(): array
    {
        return self::wa(
            'missed call',
            'We tried calling you regarding your enquiry with Panun Kaergar (Home service provider of Kashmir). If you need any help or any service feel free to call us.',
            'We tried calling you regarding your enquiry with Panun Kaergar (Home service provider of Kashmir). If you need any help or any service feel free to call us.',
        );
    }

    /** @return array{mandatory: bool, label: string, template: string, example: string, to: string} */
    private static function providerGroupPathA(): array
    {
        return [
            'mandatory' => true,
            'to' => 'provider-group',
            'label' => 'Provider group — Path A (direct booking)',
            'template' => "*Service Request – #{LEAD_ID}*\n"
                ."We have a new service request: {SERVICE} — {PROBLEM}.\n"
                ."📍 Address: {ADDRESS}\n"
                ."🕐 Preferred time: {DATE_TIME}\n"
                ."Is anyone available for this job?\n"
                .'If you are not free at this time, please reply with your next available slot.',
            'example' => "*Service Request – #2425*\n"
                ."We have a new service request: Top load washing machine — water is continuously draining.\n"
                ."📍 Address: Rawalpora\n"
                ."🕐 Preferred time: 01 August — 7 PM\n"
                ."Is anyone available for this job?\n"
                .'If you are not free at this time, please reply with your next available slot.',
        ];
    }

    /** @return array{mandatory: bool, label: string, template: string, example: string, to: string} */
    private static function providerGroupPathB(): array
    {
        return [
            'mandatory' => true,
            'to' => 'provider-group',
            'label' => 'Provider group — Path B (discussion first)',
            'template' => "*Discussion Request – #{LEAD_ID}*\n"
                ."We have a new service request: {SERVICE} — {PROBLEM}.\n"
                ."The customer wants to speak with a provider first (price / scope) before booking.\n"
                ."📍 Address: {ADDRESS}\n"
                ."🕐 Preferred discussion time: {DATE_TIME}\n"
                ."Can anyone take a short discussion call with this customer?\n"
                .'If you are not free at this time, please reply with your next available slot.',
            'example' => "*Discussion Request – #2425*\n"
                ."We have a new service request: Kitchen plumbing — tap leak.\n"
                ."The customer wants to speak with a provider first (price / scope) before booking.\n"
                ."📍 Address: Rajbagh\n"
                ."🕐 Preferred discussion time: 01 August — 5 PM\n"
                ."Can anyone take a short discussion call with this customer?\n"
                .'If you are not free at this time, please reply with your next available slot.',
        ];
    }

    /** @return array{mandatory: bool, label: string, template: string, example: string, to: string} */
    private static function waProviderAgreement(): array
    {
        return self::wa(
            'provider Step 1 call',
            "Assalam alaikum — thank you for your interest in joining Panun Kaergar as a service partner.\n\n"
            ."Please find attached our partner agreement. Required documents:\n"
            ."1. ID proof\n2. Skill / trade proof\n3. Bank details\n\n"
            .'Please submit by {DEADLINE_DATE}. Reply here if you have any questions.',
            "Assalam alaikum — thank you for your interest in joining Panun Kaergar as a service partner.\n\n"
            ."Please find attached our partner agreement. Required documents:\n"
            ."1. ID proof\n2. Skill / trade proof (electrician certificate)\n3. Bank details\n\n"
            .'Please submit by 10 August. Reply here if you have any questions.',
            true,
            'provider',
        );
    }

    /** @return array{mandatory: bool, label: string, template: string, example: string, to: string} */
    private static function waFutureClose(): array
    {
        return self::wa(
            'Future customer call',
            'Thank you for contacting Panun Kaergar — Kashmir\'s home service partner for plumbing, electrical, cleaning, repairs, and more. '
            .'When you need help, call or WhatsApp us on 8899881555. Please save our number. '
            .'If you know anyone who needs home service now, we would be happy to help.',
            'Thank you for contacting Panun Kaergar — Kashmir\'s home service partner for plumbing, electrical, cleaning, repairs, and more. '
            .'When you need help for your renovation in October, call or WhatsApp us on 8899881555. Please save our number.',
        );
    }

    /** @return array{mandatory: bool, label: string, template: string, example: string, to: string} */
    private static function waInvalidClose(): array
    {
        return self::wa(
            'Invalid lead close',
            'Thank you for contacting Panun Kaergar. Unfortunately we cannot help with {REQUEST} — we specialise in home services '
            .'(plumbing, electrical, cleaning, repairs) in our service area. '
            .'For those needs, please call or WhatsApp 8899881555 anytime.',
            'Thank you for contacting Panun Kaergar. Unfortunately we cannot help with car AC repair in Jammu — we specialise in home services '
            .'(plumbing, electrical, cleaning, repairs) in Kashmir. For home service needs, call or WhatsApp 8899881555 anytime.',
        );
    }

    /** @return array<string, mixed> */
    private static function slideDeckGuide(): array
    {
        return [
            'id' => 'deck-guide',
            'title' => 'How to read this guide',
            'subtitle' => 'Terms we use',
            'type' => 'deck-guide',
            'terms_title' => 'Terms we use',
            'terms' => [
                ['term' => 'Lead', 'definition' => 'One person’s enquiry in the admin panel — phone, source, type, remarks, and follow-up date.'],
                ['term' => 'Customer', 'definition' => 'Someone who needs a home service from Panun Kaergar (plumbing, electrical, cleaning, etc.).'],
                ['term' => 'Provider', 'definition' => 'Someone who wants to join Panun Kaergar as a service partner and receive jobs.'],
                ['term' => 'Unknown', 'definition' => 'Not enough information yet — you must call and ask what they need, then change the type.'],
                ['term' => 'Future customer', 'definition' => 'No service needed today — saving our number or may need help later; warm close in panel.'],
                ['term' => 'Follow-up', 'definition' => 'Next action date on the lead (Followup On in panel) — when you must call, message, or close the lead.'],
                ['term' => 'Mark as', 'definition' => 'Buttons on Unknown leads only — change type to Customer, Provider, Future customer, or Invalid (opens a modal with required fields).'],
                ['term' => 'Change Status', 'definition' => 'Move a Customer or Provider lead through the pipeline — especially Cancel, which requires a cancellation reason.'],
                ['term' => 'Add follow-up', 'definition' => 'Log each contact attempt — Taken + Call or WhatsApp + remarks. The Follow-ups tab shows how many times the lead was contacted.'],
                ['term' => 'Call log', 'definition' => 'A phone contact logged via Activity → Call Logs → Add Call Log (or Add follow-up → Call). Shows who you called, when, remarks, and optional recording.'],
                ['term' => 'Initial call recording', 'definition' => 'One audio upload on the lead main card for the first qualify call — play back, transcribe for AI summary, also listed in Call Logs tab.'],
                ['term' => 'Open / Closed', 'definition' => 'Lead queue state — Unknown stays Open; Invalid/Future always Closed; Customer/Provider Open until Completed or Cancelled status.'],
                ['term' => 'Initial remarks', 'definition' => 'Customer-facing summary on the lead — what was said, promised, and next step. Always update after every call.'],
                ['term' => 'Comments', 'definition' => 'Internal team notes on the lead — @mention staff, pin for shift handover. Not a substitute for follow-up rows.'],
                ['term' => 'Workflow checklist', 'definition' => 'Floating steps on lead details — tick each box as you qualify and move toward booking.'],
                ['term' => 'Hard gate', 'definition' => 'Panel blocks the action until required steps are done (e.g. outbound call before changing type from Unknown).'],
                ['term' => 'Soft gate', 'definition' => 'Panel shows a confirm modal for skipped steps — tick confirm only if work is truly done.'],
            ],
        ];
    }

    /**
     * @return array{label: string, text: string, image: string, type: string}
     */
    private static function missionExample(string $label, string $text, string $image = '', string $type = 'neutral'): array
    {
        $example = [
            'label' => $label,
            'text' => $text,
            'type' => $type,
        ];

        if ($image !== '') {
            $example['image'] = $image;
        }

        return $example;
    }

    /**
     * @param  array<int, string>  $detailPoints
     * @param  array<int, array{label: string, text: string, image: string, type: string}>  $examples
     * @param  array<int, string>  $bestPractices
     * @param  array<int, string>  $avoid
     * @return array<string, mixed>
     */
    private static function missionCard(
        string $id,
        string $title,
        string $description,
        string $detail,
        array $detailPoints,
        string $image,
        string $icon,
        array $examples,
        array $bestPractices,
        array $avoid,
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'detail' => $detail,
            'detail_points' => $detailPoints,
            'image' => $image,
            'icon' => $icon,
            'examples' => $examples,
            'best_practices' => $bestPractices,
            'avoid' => $avoid,
        ];
    }

    /** @return array<string, mixed> */
    private static function slideMission(): array
    {
        return [
            'id' => 'mission',
            'title' => 'Your job',
            'subtitle' => 'On every contact',
            'type' => 'point-cards',
            'point_cards' => [
                self::missionCard(
                    'notes',
                    'Listen first, note on paper',
                    'Give full attention on the call. Write notes — do not update the panel while the customer is speaking.',
                    'Your job on the call is to listen and capture facts — not to update the admin panel. Customers notice when you are typing instead of listening, and you will miss details that matter for booking and follow-up.',
                    [
                        'Write on paper or notepad: name, service, problem, full address, preferred date/time, and any objections.',
                        'Before you hang up, confirm details with the customer — repeat service, address, date/time, and urgency back to them.',
                        'Confirm next steps aloud: what you will do next, when they will hear from you, and via WhatsApp or call.',
                        'When the call ends, use your notes to update the panel — type, remarks, urgency, and Followup On if needed.',
                        'Only after notes are written and the lead is fully handled should you open the next lead.',
                    ],
                    'mission-01-notes.png',
                    'edit_note',
                    [
                        self::missionExample('Customer call', 'Customer: “Tap leaking in kitchen, Rajbagh, need plumber tomorrow morning.” → Note: Plumbing / kitchen tap / Rajbagh / tomorrow AM.', 'notes-ex-1.png', 'good'),
                        self::missionExample('Confirm before hang up', '“So — kitchen tap leak, Lane 2 Rajbagh, tomorrow morning. I will post to our team now and WhatsApp you within 30 minutes once we have a partner.”', 'notes-ex-2.png', 'good'),
                        self::missionExample('What to capture', 'Also note: who called (customer or provider), urgency (normal or emergency), and exact next action you promised on the call.', 'notes-ex-1.png', 'good'),
                        self::missionExample('Avoid on call', 'Typing in panel while customer speaks — they hear keyboard, lose trust, and details get missed.', 'next-lead-ex-2.png', 'bad'),
                    ],
                    ['Keep pen and notepad ready at desk', 'Confirm details and next steps before hanging up', 'Finish notes before opening next lead', 'Use notes for panel, WhatsApp, and group post'],
                    ['Typing in panel during the call', 'Relying on memory until end of shift', 'Taking next call before writing notes', 'Half-written notes you cannot read later'],
                ),
                self::missionCard(
                    'respond-same-day',
                    'Live calls & same-day response',
                    'Answer ringing phone first — before queue, DMs, or group work. Every enquiry gets a touch today.',
                    'Panun Kaergar standard: no enquiry waits overnight without contact. The ringing phone always comes first — even if you are mid-task on another lead. Speed of first touch builds trust and conversion.',
                    [
                        'Live inbound call → answer immediately, take notes, handle fully.',
                        'Missed call → WhatsApp within 5 minutes + create or update lead same day.',
                        'Social DM / app message → reply and attempt outbound call before end of shift.',
                        'Anything unfinished today → set Followup On with a real date, not “later”.',
                    ],
                    'mission-02-respond.png',
                    'schedule',
                    [
                        self::missionExample('Missed call', 'Missed call at 10:15 → WhatsApp by 10:20: “Sorry we missed your call — Panun Kaergar here, how can we help?” + lead created.', 'respond-same-day-ex-1.png', 'good'),
                        self::missionExample('Instagram DM', 'DM at 4 PM: “Thanks for messaging Panun Kaergar…” + call attempt before shift ends + lead in panel.', 'respond-same-day-ex-2.png', 'good'),
                        self::missionExample('Live call priority', 'Phone rings while updating panel → stop, answer call, return to panel after call is fully done.', 'respond-same-day-ex-1.png', 'good'),
                        self::missionExample('Do not wait', 'Lead from morning still untouched at 5 PM — every hour counts; same-day touch is mandatory.', 'next-lead-ex-2.png', 'bad'),
                    ],
                    ['Answer ringing phone before queue work', 'Set Followup On for anything unfinished today', 'Mark urgency in remarks for emergencies', 'Document channel: phone, WA, IG, app'],
                    ['Leaving DMs until “when free”', 'Promising callback with no Followup On', 'Ignoring overdue follow-ups', 'Treating missed call as low priority'],
                ),
                self::missionCard(
                    'panel-handover',
                    'Panel update — team handover',
                    'After the call: type, full remarks, urgency, follow-up date. Write so the next shift continues without calling you.',
                    'The panel is your handover to the next person on shift. They have not heard the call — remarks are the only source of truth. A good remark answers: who, what they need, what you did, what customer was told, and what happens next.',
                    [
                        'Update within 2 minutes of hanging up — while memory is fresh.',
                        'Include: lead type, service, address, date/time, path (A/B), provider/group status, attempt # if no pickup.',
                        'Flag HOT or emergency clearly in remarks and urgency field.',
                        'Set Followup On whenever the lead is not fully closed — with reason in remarks.',
                    ],
                    'mission-panel-handover.png',
                    'dashboard',
                    [
                        self::missionExample('Customer booking', '“Customer — electrical, MCB tripping, House 12 Lane 4 Bemina, today 6 PM, Path A, posted to group 5:42 PM, WA sent.”', 'panel-update-ex-1.png', 'good'),
                        self::missionExample('Path B handover', '“Path B — conference done 3 PM, wants cheaper option. Customer told we call back Thu. Followup On 4 Aug — alternate quote.”', 'team-comms-ex-1.png', 'good'),
                        self::missionExample('No pickup', '“Unknown — Attempt 2/3 no pickup 2:15 PM, missed-call WA sent, Followup On 3 Aug AM for Attempt 3.”', 'panel-update-ex-2.png', 'good'),
                        self::missionExample('Too thin', 'Remarks: “called” — next shift has no idea what was discussed or promised.', 'clear-details-ex-2.png', 'bad'),
                    ],
                    ['Write for someone who never spoke to this customer', 'Include path, attempt #, HOT when relevant', 'Never “see WA” without panel summary', 'Match panel to what customer was told'],
                    ['Empty or one-word remarks', 'Abbreviations only you understand', 'Wrong type or urgency unchanged', 'Open lead with no Followup On when pending'],
                ),
                self::missionCard(
                    'classify-immediately',
                    'Classify immediately after call',
                    'Unknown is temporary — after every successful call, set the correct type: Customer, Provider, Future, or Invalid.',
                    'Classification drives the whole process — Path A, Path B, onboarding, or close. Unknown means “not yet qualified.” Once you know what they want, change the type in the same panel update. Never leave Unknown open after a successful conversation.',
                    [
                        'Customer = wants a home service booked or quoted.',
                        'Provider = wants to join Panun Kaergar as a service partner.',
                        'Future customer = valid need but not now — save for later.',
                        'Invalid = outside our services — close politely with reason.',
                    ],
                    'mission-classify.png',
                    'category',
                    [
                        self::missionExample('Unknown → Customer', 'Answered call — kitchen tap leak, wants plumber tomorrow → Customer, Path A, full remarks.', 'classify-ex-1.png', 'good'),
                        self::missionExample('Unknown → Provider', 'Answered — electrician wants to register in Bemina → Provider, onboarding flow.', 'classify-ex-2.png', 'good'),
                        self::missionExample('Future customer', 'Renovation in 3 months → Future customer, warm WA, Followup On before their date.', 'polite-close-ex-1.png', 'good'),
                        self::missionExample('Leave Unknown', 'Spoke 5 minutes, know they need plumbing — type still Unknown at end of shift.', 'classify-ex-1.png', 'bad'),
                    ],
                    ['One type only — pick the best fit', 'Reclassify in same panel update as remarks', 'Notes on call → panel after → WhatsApp', 'Document reason if edge case'],
                    ['Unknown after successful qualify call', 'Wrong type to close faster', 'Multiple types in remarks', 'Invalid when they need service later'],
                ),
                self::missionCard(
                    'whatsapp-clear',
                    'WhatsApp & clear messages',
                    'Mandatory after every call — same minute as panel. Every message: service, problem, address, date/time, attempt #.',
                    'WhatsApp is proof of work for the customer and clarity for providers. Send before moving to the next lead. Use templates from the guide but always replace placeholders with real details from your notes.',
                    [
                        'Customer WA: summary of discussion + what happens next + Panun Kaergar name.',
                        'Group post: Service / Problem / Address / Date-time — who is available?',
                        'No pickup: missed-call template same day with attempt number.',
                        'Note in panel remarks that WhatsApp was sent and when.',
                    ],
                    'mission-whatsapp-clear.png',
                    'forum',
                    [
                        self::missionExample('Customer WA', '“As per our discussion — kitchen tap leak, Rajbagh, tomorrow 10 AM. We are finding a partner for that time. — Panun Kaergar”', 'whatsapp-ex-1.png', 'good'),
                        self::missionExample('Group post', '“Plumbing — kitchen tap leak, Lane 2 Rajbagh, Sat 10 AM, who available?”', 'clear-details-ex-1.png', 'good'),
                        self::missionExample('No pickup WA', 'Attempt 1/3 — “We tried calling from Panun Kaergar regarding your enquiry. Please call back or reply here.”', 'whatsapp-ex-2.png', 'good'),
                        self::missionExample('Too vague', '“need plumber urgent” — providers cannot act; customer sees unprofessional message.', 'clear-details-ex-2.png', 'bad'),
                    ],
                    ['Send while details are fresh', 'Same minute as panel update', 'Structured lines — easy to scan', 'Match WA text to panel remarks'],
                    ['Skipping WA because “they know”', 'Template with blank placeholders', 'WA sent but panel empty', 'Long unbroken paragraph nobody reads'],
                ),
                self::missionCard(
                    'next-lead',
                    'Finish one lead fully first',
                    'Panel + WhatsApp + follow-up date done → only then open the next lead.',
                    'Half-finished leads get lost — especially at shift change. One lead is complete when it is closed OR fully documented with type, remarks, WA sent, and Followup On for anything pending.',
                    [
                        'Checklist: notes written → panel updated → type correct → WA sent → Followup On if open.',
                        'Live phone still interrupts — that is correct; finish the interrupted lead after the call.',
                        'If waiting on provider: group posted, chase started, customer informed, Followup On set.',
                        'End of shift: no open leads without clear next action and date.',
                    ],
                    'mission-05-next-lead.png',
                    'arrow_forward',
                    [
                        self::missionExample('Complete flow', 'Panel ✓ → customer WA ✓ → group post ✓ → Followup On ✓ → then next live call or queue item.', 'next-lead-ex-1.png', 'good'),
                        self::missionExample('Provider pending', 'Posted to group, 5 min reminder sent, customer WA “searching for Sat 10 AM” — OK to take next call if Followup On set.', 'provider-group-chase-ex-1.png', 'good'),
                        self::missionExample('Avoid stacking', 'Three leads with half-filled panels and no WA — next shift cannot recover context.', 'next-lead-ex-2.png', 'bad'),
                        self::missionExample('Wrong order', 'Group post before panel saved — if interrupted, details are lost.', 'next-lead-ex-2.png', 'bad'),
                    ],
                    ['Mental checklist every time', 'One lead fully done before next', 'Live call exception is intentional', 'Review open leads before shift end'],
                    ['Stacking half-updated leads', 'Group post before panel saved', 'No Followup On on waiting leads', 'Jumping to social before WA sent'],
                ),
                self::missionCard(
                    'work-by-priority',
                    'Work by priority — not FIFO',
                    'HOT and emergency leads before normal queue. Overdue follow-ups before new low-priority work.',
                    'Panun Kaergar uses priority tiers — not strict first-in-first-out. A customer actively waiting for a provider beats checking in on a Future customer from last week. Mark urgency in the panel and work the queue accordingly.',
                    [
                        'HOT / emergency: no power, flooding, safety — act within hours, not days.',
                        'Customer waiting on provider response — chase before new enquiries.',
                        'Overdue Followup On leads — check at shift start before new work.',
                        'Live ringing phone still beats everything — answer first, then return to priority queue.',
                    ],
                    'mission-priority.png',
                    'priority_high',
                    [
                        self::missionExample('HOT customer waiting', 'Customer waiting 15 min on group → pause Instagram DMs, chase providers, send WA update, mark HOT in remarks.', 'priority-ex-1.png', 'good'),
                        self::missionExample('Emergency', 'Whole house no power → HOT flag, group post now, Followup On in 2 hours if no provider yet.', 'priority-ex-2.png', 'good'),
                        self::missionExample('Shift start', 'Open overdue Followup On list first — three leads due today before touching new Unknowns.', 'priority-ex-1.png', 'good'),
                        self::missionExample('Strict FIFO wrong', 'Routine Future customer call while HOT lead waiting on provider with no update for 20 min.', 'team-comms-ex-2.png', 'bad'),
                    ],
                    ['Check overdue Followup On at shift start', 'Mark HOT/emergency in panel and remarks', 'Customer waiting beats low-priority work', 'Live call always interrupts'],
                    ['Strict FIFO ignoring urgent leads', 'No urgency marked on time-sensitive lead', 'Old low-priority before HOT customer', 'Forgetting overdue follow-ups'],
                ),
                self::missionCard(
                    'no-pickup-rule',
                    'No pickup — 3 attempts rule',
                    'Unknown or Customer with no answer: Attempt 1/3, 2/3, 3/3 — each with WhatsApp same day. After 3 → close with reason.',
                    'Applies to Unknown leads qualifying outbound and Customer leads when they do not answer your call. Every attempt must be documented — date, time, WA sent. Space attempts reasonably; set Followup On between tries.',
                    [
                        'Each attempt: outbound call + WhatsApp same day + Add follow-up → Taken + attempt # in remarks.',
                        'Unknown after 3/3 with no contact → Mark as Invalid → Did not Know About Enquiry.',
                        'Customer no pickup after 3/3 → Change Status → Cancel → No Response From Customer.',
                        'Never close after 1 attempt; never leave open with no Followup On between attempts.',
                    ],
                    'mission-no-pickup.png',
                    'phone_missed',
                    [
                        self::missionExample('Attempt 1/3', 'Called 10 AM, no pickup → missed-call WA 10:05 → “Unknown Attempt 1/3, WA sent, Followup On tomorrow AM.”', 'no-pickup-ex-1.png', 'good'),
                        self::missionExample('Attempt 2/3', 'Second call next day, no pickup → WA again → “Attempt 2/3, Followup On for Attempt 3.”', 'no-pickup-ex-1.png', 'good'),
                        self::missionExample('After 3/3', 'Third call + WA same day → Mark as Invalid → Did not Know About Enquiry with full log of all three attempts in remarks + follow-ups.', 'no-pickup-ex-2.png', 'good'),
                        self::missionExample('Wrong close', 'One call, no WA, closed as No Response — process violation, customer may still need help.', 'no-pickup-ex-2.png', 'bad'),
                    ],
                    ['WhatsApp on every attempt same day', 'Attempt # in remarks and WA', 'Followup On between attempts', 'Full attempt log before close'],
                    ['Closing after one try', 'No WA on missed attempt', 'No attempt count in remarks', 'Open lead with no next attempt date'],
                ),
                self::missionCard(
                    'polite-close',
                    'Professional close — every outcome',
                    'Future customer and Invalid are valid wins. Stay calm when you cannot book or help.',
                    'Not every call ends in a booking — that is normal. How you close matters for Panun Kaergar brand: the person may refer others or call back later. Always thank them, explain clearly, send WhatsApp, and document in panel before closing.',
                    [
                        'Future customer: explain services, save contact, warm WA, set Followup On near their date.',
                        'Invalid: politely explain what Panun Kaergar does (home services), list examples, close with reason.',
                        'Cannot serve today: offer next slot or alternate — never ghost or argue.',
                        'Document close reason in remarks so team understands why lead is closed.',
                    ],
                    'mission-06-polite-close.png',
                    'volunteer_activism',
                    [
                        self::missionExample('Future customer', 'Renovation in 2 months → explain plumbing/electrical/painting, warm WA with number saved, Future customer ✓, Followup On 6 weeks out.', 'polite-close-ex-1.png', 'good'),
                        self::missionExample('Invalid enquiry', 'Car repair → “Panun Kaergar handles home services — plumbing, electrical, cleaning…” Invalid ✓, polite WA.', 'polite-close-ex-2.png', 'good'),
                        self::missionExample('Cannot serve today', 'No plumber today → offer tomorrow slot or honest timeline + WA — do not promise what you cannot deliver.', 'polite-close-ex-1.png', 'good'),
                        self::missionExample('Bad close', 'Hung up annoyed, no WA, Invalid without reason — damages brand and confuses next shift.', 'clear-details-ex-2.png', 'bad'),
                    ],
                    ['Thank them for contacting Panun Kaergar', 'State clearly what we can and cannot do', 'WhatsApp + panel before close', 'Valid outcomes are still success'],
                    ['Arguing or sounding annoyed', 'Invalid when they need service later', 'Close with no WhatsApp', 'No reason documented in remarks'],
                ),
                self::missionCard(
                    'customer-centric',
                    'Customer-centric — show empathy',
                    'Act like you are on the customer’s side. Listen, acknowledge the problem, and make them feel Panun Kaergar is here to help.',
                    'Empathy reduces callbacks and builds referrals. Use their name, repeat the problem back, and always explain what happens next and when they will hear from you — before you hang up.',
                    [
                        'Acknowledge first: “I understand that must be frustrating.”',
                        'Repeat key facts: service, address, urgency — so they feel heard.',
                        'Explain next step: “I am posting to our provider group now and will WhatsApp you within 30 minutes.”',
                        'Stay calm if they are upset — you represent Panun Kaergar, not your personal mood.',
                    ],
                    'mission-09-customer-centric.png',
                    'favorite',
                    [
                        self::missionExample('Routine booking', '“I understand — a leaking tap is stressful. We will find a plumber for Rajbagh tomorrow morning and WhatsApp you once confirmed.”', 'customer-centric-ex-1.png', 'good'),
                        self::missionExample('Emergency', '“I hear you — no power in the whole house. I am marking this urgent and posting to our group right now. You will get an update shortly.”', 'customer-centric-ex-2.png', 'good'),
                        self::missionExample('While searching', 'Provider search taking time → “We are still working on your Sat 10 AM slot — I will update you in 30 minutes.” Never silent.', 'customer-centric-ex-1.png', 'good'),
                        self::missionExample('Cold response', '“We don’t have anyone.” click — no empathy, no next step, customer feels abandoned.', 'clear-details-ex-2.png', 'bad'),
                    ],
                    ['Acknowledge before asking questions', 'Explain next step and timeline', 'Use customer name when possible', 'Update proactively during long searches'],
                    ['Sound rushed or uninterested', 'Blame the customer', 'Promise without explaining how', 'Silence while customer waits'],
                ),
                self::missionCard(
                    'followup-timing',
                    'Follow-up date — real availability',
                    'Ask the customer (and provider) when they are available. Set Followup On from that — not a random date.',
                    'Follow-up dates drive your queue and the customer’s expectation. Ask directly: “When are you free?” or “When should we call back?” Match urgency — emergencies need hours, bookings need agreed slots, provider waits need chase times.',
                    [
                        'Customer booking → Followup On before their slot to confirm provider.',
                        'Provider said “call Thursday” → Followup On Thu AM, not default Monday.',
                        'Waiting on group → Followup On in 2–4 hours for HOT, next day for normal.',
                        'Always note in remarks WHY that date was chosen.',
                    ],
                    'mission-10-followup-timing.png',
                    'event',
                    [
                        self::missionExample('Customer window', 'Free Sat 10 AM–2 PM → Followup On Sat 9 AM to confirm provider; remarks: “Confirm before customer window.”', 'followup-timing-ex-1.png', 'good'),
                        self::missionExample('Provider callback', 'Electrician: “Call Thursday afternoon” → Followup On Thu 2 PM — not next Monday by habit.', 'followup-timing-ex-2.png', 'good'),
                        self::missionExample('Provider chase', 'Group post 2 PM, no reply → Followup On same day 4 PM to chase + call providers.', 'followup-timing-ex-1.png', 'good'),
                        self::missionExample('Random date', 'Followup On “next week” with no reason while customer expected call today — trust broken.', 'followup-timing-ex-2.png', 'bad'),
                    ],
                    ['Ask availability before closing call', 'Match date to urgency and agreement', 'Document reason in remarks', 'Review overdue Followup On first each shift'],
                    ['Random dates with no reason', 'Same default for every lead', 'Overdue while customer waiting', 'No Followup On on open leads'],
                ),
                self::missionCard(
                    'provider-search-chase',
                    'Provider search & chase',
                    'Don’t stuck on 2 providers — post full details, 5 min reminder, at 15 min call nearby. Always update customer.',
                    'Finding a provider is active work — not waiting silently. Widen search if favourites are busy. The customer must never wonder if Panun Kaergar is still working on their request.',
                    [
                        'Step 1: Group post with full details (service / problem / address / date-time).',
                        'Step 2: 5-minute reminder in group if no suitable reply.',
                        'Step 3: At 15 minutes — call nearby providers from list; note each in panel.',
                        'Step 4: WhatsApp customer if search ongoing — “Still searching, update in X min.”',
                        'Step 5: Widen area or alternate providers — do not message same 2 people only.',
                    ],
                    'mission-provider-search.png',
                    'travel_explore',
                    [
                        self::missionExample('Group post', 'Use standard format: *Service Request – #2425* → service + problem → 📍 address → 🕐 timing → availability ask.', 'clear-details-ex-1.png', 'good'),
                        self::missionExample('5 min reminder', 'No reply → resend same format with Lead ID — do not shrink to “need plumber”.', 'provider-group-chase-ex-1.png', 'good'),
                        self::missionExample('15 min escalation', 'Still no confirm → call 3 nearby plumbers → WA customer: “Still searching for Sat 10 AM — update in 30 min.”', 'provider-group-chase-ex-2.png', 'good'),
                        self::missionExample('Stuck on two', 'Messaged same 2 electricians 4 times, no widen, customer silent 45 min — process failure.', 'find-providers-ex-2.png', 'bad'),
                    ],
                    ['Full details every group post', '5-min reminder until response or 15-min calls', 'Customer WA during long searches', 'Log every provider tried in remarks'],
                    ['Same 2 providers repeatedly', 'Vague group posts', 'Customer hanging with no update', 'Stopping after one group message'],
                ),
                self::missionCard(
                    'serve-the-customer',
                    'Your goal — get them served',
                    'Hard work on every lead until the customer gets service or a clear, honest close. That is the job.',
                    'Success = customer served OR properly closed with empathy and documentation. Posting once to a group is not success. Combine all habits: notes, panel, classify, WA, priority, chase, follow-up — until outcome is clear.',
                    [
                        'Persist professionally: alternate providers, different times, manager help if needed.',
                        'Update customer throughout — they should never feel forgotten.',
                        'If truly cannot serve: honest close, Future customer, or next slot — never ghost.',
                        'Hand over to next shift with full remarks if still open — never ambiguous open leads.',
                    ],
                    'mission-13-serve-customer.png',
                    'flag',
                    [
                        self::missionExample('Path A win', 'Group → reminders → calls → provider confirms → customer WA with name + time → panel closed/complete.', 'serve-the-customer-ex-1.png', 'good'),
                        self::missionExample('Alternate path', 'Usual provider busy → find another same day → customer informed → booked — persistence wins.', 'find-providers-ex-1.png', 'good'),
                        self::missionExample('Honest close', 'Cannot serve this week → offer next available + warm WA + Future customer or follow-up date.', 'serve-the-customer-ex-2.png', 'good'),
                        self::missionExample('Gave up early', 'One group post, no reminder, no customer WA, moved to next lead — customer lost.', 'serve-the-customer-ex-2.png', 'bad'),
                    ],
                    ['Own every lead until closed or clear handover', 'Empathy + process + persistence together', 'Measure by outcome not effort', 'Protect Panun Kaergar reputation every call'],
                    ['Stopping after one unanswered post', 'Next lead while customer thinks someone is coming', 'Lazy handover remarks', 'Ghosting instead of honest close'],
                ),
            ],
        ];
    }

    /**
     * @return array{label: string, url: string}
     */
    private static function panelLink(string $label, string $path): array
    {
        return [
            'label' => $label,
            'url' => str_starts_with($path, 'http') ? $path : url($path),
        ];
    }

    /**
     * @param  array<int, string>  $where
     * @param  array<int, string>  $dontMiss
     * @param  array<int, string>  $manage
     * @param  array<int, array{label: string, url: string}>  $links
     * @return array<string, mixed>
     */
    private static function leadSourceGuide(
        string $id,
        string $title,
        string $icon,
        string $tone,
        string $summary,
        array $where,
        array $dontMiss,
        array $manage,
        array $links = [],
        ?string $badge = null,
    ): array {
        $guide = [
            'id' => $id,
            'title' => $title,
            'icon' => $icon,
            'tone' => $tone,
            'summary' => $summary,
            'where' => $where,
            'dont_miss' => $dontMiss,
            'manage' => $manage,
            'links' => $links,
        ];

        if ($badge !== null) {
            $guide['badge'] = $badge;
        }

        return $guide;
    }

    /**
     * @return array<int, string>
     */
    private static function activeConfigurationNames(string $modelClass): array
    {
        try {
            return $modelClass::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<string, mixed> */
    private static function configuredDropdownCardGroup(): array
    {
        $invalid = self::activeConfigurationNames(LeadInvalidReason::class);
        $future = self::activeConfigurationNames(LeadFutureCustomerReason::class);
        $customerCancel = self::activeConfigurationNames(LeadCancellationReason::class);
        $providerCancel = self::activeConfigurationNames(ProviderCancellationReason::class);

        return [
            'title' => 'Dropdown names — live from Lead Configuration',
            'hint' => 'Names below are loaded from your panel. Pick the exact option that matches what the customer or provider said.',
            'layout' => 'detail',
            'cards' => [
                [
                    'icon' => 'block',
                    'title' => 'Invalid reasons',
                    'text' => 'Mark as Invalid. Vague Unknown after 3 no-pickups → Did not Know About Enquiry. Wrong service → Service not available. Outside area → Out of Kashmir.',
                    'color' => 'invalid',
                    'points' => $invalid !== [] ? $invalid : ['Ask manager — no invalid reasons configured yet'],
                ],
                [
                    'icon' => 'event',
                    'title' => 'Future customer reasons',
                    'text' => 'Mark as Future customer — required on save.',
                    'color' => 'future',
                    'points' => $future !== [] ? $future : ['Ask manager — no future customer reasons configured yet'],
                ],
                [
                    'icon' => 'cancel',
                    'title' => 'Customer cancel reasons',
                    'text' => 'Change Status → Cancel on customer lead — reason modal required.',
                    'color' => 'customer',
                    'points' => $customerCancel !== [] ? $customerCancel : ['Ask manager — no customer cancel reasons configured yet'],
                ],
                [
                    'icon' => 'handshake',
                    'title' => 'Provider cancel reasons',
                    'text' => 'Change Status → Cancel on provider lead — e.g. no response after 3 calls → Not Intrested; docs not sent → Not Intrested.',
                    'color' => 'provider',
                    'points' => $providerCancel !== [] ? $providerCancel : ['Ask manager — no provider cancel reasons configured yet'],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideLeadSources(): array
    {
        return [
            'id' => 'lead-sources',
            'title' => 'Where leads come from',
            'subtitle' => 'Section 1 — check, track, and never miss',
            'type' => 'visual',
            'shift_checklist' => [
                'Ringing phone — answer first (always)',
                'Header WhatsApp icon — unread chats',
                'Dashboard → Today\'s follow-ups (Leads + Bookings)',
                'Leads and bookings → Web Bookings + Web Provider Requests + App Custom Requests',
                'Facebook / Instagram / YouTube apps — comments & DMs (manual leads)',
                'WhatsApp → Human support tab (AI escalations)',
            ],
            'panel_links' => [
                self::panelLink('Leads list', '/admin/lead'),
                self::panelLink('Today\'s lead follow-ups', '/admin/lead/todays-followups'),
                self::panelLink('WhatsApp — Active Chats', '/admin/social-inbox/whatsapp/conversations?tab=chats'),
                self::panelLink('WhatsApp — Human support', '/admin/social-inbox/whatsapp/conversations?tab=human_support'),
                self::panelLink('WhatsApp — chat tags & status config', '/admin/social-inbox/whatsapp/conversations?tab=chat_config'),
                self::panelLink('Web Bookings', '/admin/booking/web-bookings'),
                self::panelLink('Web Provider Requests', '/admin/booking/web-provider-requests'),
                self::panelLink('App Custom Requests', '/admin/booking/app-custom-requests'),
                self::panelLink('WhatsApp message templates', '/admin/social-inbox/whatsapp/booking-message-templates'),
            ],
            'source_guides' => [
                self::leadSourceGuide(
                    'social-comments-dm',
                    'Facebook, Instagram & YouTube — comments & DMs',
                    'share',
                    'manual',
                    'These do NOT auto-create leads. You must read the comment or DM, collect details, and create a new lead manually in the panel.',
                    [
                        'Facebook — Page inbox & comments (Facebook app or browser)',
                        'Instagram — DMs & comment replies (Instagram app)',
                        'YouTube — comment replies (YouTube Studio / app)',
                        'After collecting details → Leads and bookings → Leads → Add New Lead',
                        'Set Source = Facebook, Instagram, or Other — paste what they wrote in remarks',
                    ],
                    [
                        'Check all three at shift start and again mid-shift — not once at end of day',
                        'Treat every comment/DM as same-day work — create lead before you forget context',
                        'If they gave no phone, ask in reply immediately — do not create lead without contact path',
                        'Pin or bookmark FB/IG tabs on your desktop — do not rely on memory',
                    ],
                    [
                        'Copy exact text into remarks: service, area, date, link to post if possible',
                        'Create lead same day with correct Source and phone number',
                        'If enough info → classify (Customer / Provider / Unknown). If vague → Unknown + call same day',
                        'Send WhatsApp if you have number → panel update → Followup On if not finished',
                        'Never leave enquiry only in social app — if it is not in Leads, next shift will miss it',
                    ],
                    [
                        self::panelLink('Add new lead', '/admin/lead'),
                        self::panelLink('Lead sources config', '/admin/lead/configuration'),
                    ],
                ),
                self::leadSourceGuide(
                    'direct-calls',
                    'Direct calls & missed calls',
                    'call',
                    'manual',
                    'Business phone lines (e.g. 8899881555). Live calls and missed calls need a lead in the panel — missed calls also need WhatsApp within 5 minutes.',
                    [
                        'Answer live calls on business phone — highest priority over chat/DM work',
                        'Missed calls — check phone call log / missed-call list on the handset',
                        'Create or update lead → Leads and bookings → Leads → Add New Lead',
                        'Missed-call WhatsApp → use template from Operations → WhatsApp → Message templates',
                    ],
                    [
                        'Phone ringing beats Instagram, Facebook, and panel work — always answer first',
                        'Check missed calls every 30–60 minutes — not at end of shift only',
                        'Missed call → WhatsApp within 5 minutes even if you will call back later',
                        'If same number already has a lead, open it — do not create duplicate',
                    ],
                    [
                        'Live call: notes on paper → after call create/update lead with Source = Phone',
                        'Remarks: what they said, service, address, urgency, next action',
                        'Missed call: WA template + lead with Attempt 1 if calling back, or qualify if they reply',
                        'Classify when clear; Unknown + outbound call same day if not enough info',
                        'Full panel + WhatsApp + Followup On before next lead',
                    ],
                    [
                        self::panelLink('Leads list / Add New Lead', '/admin/lead'),
                        self::panelLink('Missed-call WhatsApp templates', '/admin/social-inbox/whatsapp/booking-message-templates'),
                    ],
                ),
                self::leadSourceGuide(
                    'whatsapp-human',
                    'WhatsApp — human numbers (non-AI)',
                    'chat',
                    'inbox',
                    'Messages on staff/business WhatsApp numbers (not AI). Reply in panel, tag every active chat, and keep the linked lead status in sync — so you never open a thread and forget it.',
                    [
                        'Operations → WhatsApp → Active Chats (or header green WhatsApp icon)',
                        'Inside open chat: chat header → Assignee pill, Chat status, Manage tags',
                        'Linked lead — open from chat header → full lead page (Handled By, status, Followup On)',
                        'Filter inbox: Assignee, Chat status, Tags (filter drawer on conversations page)',
                        'Configure tag names & statuses: WhatsApp → Chat configuration tab',
                    ],
                    [
                        'Every active chat you touch must have: assignee (you), chat status, and tags — before you move away',
                        'Use tags for Lead ID (e.g. Lead-4521), who is managing, and stage (Waiting provider / Callback needed)',
                        'Scan chat list badges at shift end — no open thread without status or lead link',
                        'Watch unread badge on header; filter “Assigned to me” + open status for your queue',
                        'Also update lead record — chat tags alone are not enough for handover',
                    ],
                    [
                        'Open chat → Take over / assign to yourself if unassigned or still on AI',
                        'Set Chat status (Open, Waiting on provider, Closed, etc.) — matches real state',
                        'Manage tags → tick Lead ID tag + manager name + stage (create tags in Chat configuration if missing)',
                        'Open linked Lead → set Handled By, Customer/Provider Status, remarks, Followup On',
                        'Lead panel status and chat status must agree — e.g. “Waiting provider” on both',
                        'Before closing chat: status = closed OR follow-up date set on lead; tags show next action',
                        'Reply → panel update → customer WA → never leave chat updated but lead empty',
                    ],
                    [
                        self::panelLink('WhatsApp Active Chats', '/admin/social-inbox/whatsapp/conversations?tab=chats'),
                        self::panelLink('Chat tags & status setup', '/admin/social-inbox/whatsapp/conversations?tab=chat_config'),
                        self::panelLink('Leads list', '/admin/lead'),
                        self::panelLink('Today\'s follow-ups', '/admin/lead/todays-followups'),
                    ],
                ),
                self::leadSourceGuide(
                    'whatsapp-ai',
                    'AI WhatsApp chat (in panel)',
                    'smart_toy',
                    'inbox',
                    'Customer chatted with AI bot. Lead is usually auto-created. Take over from Human support, tag the thread, and sync lead status — same rules as human WhatsApp.',
                    [
                        'Operations → WhatsApp → Human support tab',
                        'Leads → filter Source = AI Chat',
                        'Inside chat after takeover: Assignee, Chat status, Tags (same as Active Chats)',
                        'Lead detail page — Handled By, Customer/Provider Status, Followup On',
                    ],
                    [
                        'Human support tab at shift start — every “want human” row same day',
                        'After takeover: immediately assign yourself + tag with Lead ID — do not reply untagged',
                        'Filter Leads by AI Chat for open items with no Followup On',
                        'Do not use header Talk With AI — that is staff assistant, not customer inbox',
                    ],
                    [
                        'Read full AI transcript → Take over chat → assign to yourself',
                        'Tag thread: Lead ID + stage (e.g. Qualifying / Posted to group) before typing reply',
                        'Set chat status Open while working; Waiting provider when chasing; Closed when done',
                        'Open linked lead → fix phone/service/address → Handled By = you → Customer/Provider Status',
                        'Update lead remarks + Followup On to match what you told customer',
                        'Chat list shows your tags/status — next shift sees ownership without asking you',
                    ],
                    [
                        self::panelLink('WhatsApp Human support', '/admin/social-inbox/whatsapp/conversations?tab=human_support'),
                        self::panelLink('Leads — filter AI Chat', '/admin/lead'),
                        self::panelLink('Chat tags & status setup', '/admin/social-inbox/whatsapp/conversations?tab=chat_config'),
                    ],
                ),
                self::leadSourceGuide(
                    'web-booking',
                    'Website — customer booking',
                    'language',
                    'auto',
                    'Customer submitted booking form on website. Lead auto-created — verify and process.',
                    [
                        'Leads and bookings → Web Bookings',
                        'Cross-check linked lead in Leads and bookings → Leads (Source: Website Direct Booking)',
                        'Dashboard → Today\'s follow-ups for due items',
                    ],
                    [
                        'Open Web Bookings list at shift start — treat new rows same day',
                        'Sort/filter by newest first — do not leave yesterday\'s unchecked',
                        'No contact number on form → check linked lead; escalate same day if still missing',
                        'Number present but address or time unclear → call same day to confirm, do not assume',
                    ],
                    [
                        'Open booking → read all fields → open linked Lead',
                        'Verify service, address, date/time, contact number — fix panel if incomplete; still no number → escalate same day',
                        'Classify → Customer path → group post / provider search as normal',
                        'WhatsApp customer confirmation → panel remarks → Followup On until booked or closed',
                    ],
                    [
                        self::panelLink('Web Bookings', '/admin/booking/web-bookings'),
                        self::panelLink('Leads list', '/admin/lead'),
                    ],
                ),
                self::leadSourceGuide(
                    'web-provider',
                    'Website — provider request',
                    'handshake',
                    'auto',
                    'Someone applied to join as service partner via website. Lead auto-created — provider onboarding path.',
                    [
                        'Leads and bookings → Web Provider Requests',
                        'Linked lead in Leads (Source: Website Partner Application)',
                        'Dashboard follow-ups if follow-up date set',
                    ],
                    [
                        'Check Web Provider Requests at shift start alongside Web Bookings',
                        'New partner applications are leads — not “just a form”',
                        'Call applicant same day if phone is present',
                    ],
                    [
                        'Open request → verify name, phone, service area, trade in panel',
                        'Set type Provider → follow provider onboarding slides',
                        'WhatsApp with agreement/docs steps per process → panel remarks each step',
                        'Followup On until registered or clearly closed',
                    ],
                    [
                        self::panelLink('Web Provider Requests', '/admin/booking/web-provider-requests'),
                        self::panelLink('Leads list', '/admin/lead'),
                    ],
                ),
                self::leadSourceGuide(
                    'app-customer',
                    'App — customer custom request',
                    'smartphone',
                    'auto',
                    'Customer submitted custom service request from mobile app. Lead auto-created — open and verify.',
                    [
                        'Leads and bookings → App Custom Requests',
                        'Linked lead in Leads (Source: App Custom Request)',
                        'Standard in-app bookings (not custom) → Leads and bookings → Booking Requests',
                    ],
                    [
                        'Check App Custom Requests at shift start — pin this page if you handle app leads',
                        'Do not confuse with Operations → Customized Requests (bidding posts) — different workflow',
                        'New app requests need contact same day',
                    ],
                    [
                        'Open request → read service details → open linked Lead',
                        'Verify contact number, address, date — update panel if incomplete; missing number → check linked lead or escalate',
                        'Classify Customer → Path A or B → same qualification as phone lead',
                        'WhatsApp + group post + Followup On — same standards as every customer lead',
                    ],
                    [
                        self::panelLink('App Custom Requests', '/admin/booking/app-custom-requests'),
                        self::panelLink('Booking Requests (standard app)', '/admin/booking/list?booking_status=all&service_type=all'),
                        self::panelLink('Leads list', '/admin/lead'),
                    ],
                ),
            ],
            'flowcharts' => [['key' => 'lead-arrival', 'title' => 'Process flow']],
            'message' => self::waMissedCall(),
            'remember' => [
                'Shift start: follow the checklist + open panel links below',
                'WhatsApp: assignee + chat status + tags on every thread you work — plus lead Handled By & status',
                'If it is not in Leads with source + phone + remarks, it does not exist for the team',
                'Manual channels (social, phone) = you create the lead',
                'Auto channels (web, app, AI) = open the list, verify lead, then qualify',
                'Phone beats chat — always',
            ],
            'avoid' => [
                'Opening a WhatsApp chat and leaving with no assignee, status, or tags',
                'Updating chat but not lead Handled By / Customer Status / Followup On',
                'Checking Facebook only at end of shift',
                'Ignoring Human support (AI) tab',
                'Assuming web/app forms are complete without opening them',
                'Creating duplicate leads for same phone number',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideShiftRoutine(): array
    {
        return [
            'id' => 'shift-routine',
            'title' => 'Your shift — start, during, end',
            'subtitle' => 'Section 1B — never miss a lead',
            'type' => 'visual',
            'source_guide_cols' => [
                'where' => 'What to check',
                'dont_miss' => 'How not to miss',
                'manage' => 'What to do',
            ],
            'panel_links' => [
                self::panelLink('Today\'s lead follow-ups', '/admin/lead/todays-followups'),
                self::panelLink('Leads list', '/admin/lead'),
                self::panelLink('WhatsApp — Active Chats', '/admin/social-inbox/whatsapp/conversations?tab=chats'),
                self::panelLink('WhatsApp — Human support', '/admin/social-inbox/whatsapp/conversations?tab=human_support'),
                self::panelLink('Web Bookings', '/admin/booking/web-bookings'),
                self::panelLink('Web Provider Requests', '/admin/booking/web-provider-requests'),
                self::panelLink('App Custom Requests', '/admin/booking/app-custom-requests'),
            ],
            'source_guides' => [
                self::leadSourceGuide(
                    'shift-start',
                    'Start of shift — before you take any lead',
                    'login',
                    'manual',
                    'First 10–15 minutes: scan every channel in order. Do not jump into one chat or one lead until the full sweep is done — otherwise yesterday\'s missed call or unread DM sits forgotten.',
                    [
                        'Business phone — answer if ringing; check missed-call log on handset',
                        'Header WhatsApp icon — unread count on Active Chats',
                        'Dashboard → Today\'s follow-ups (Leads + Bookings)',
                        'Leads → Followups Pending Till Today — your main queue',
                        'WhatsApp → Active Chats — filter Assigned to me + open status',
                        'WhatsApp → Human support tab — AI escalations waiting',
                        'Web Bookings + Web Provider Requests + App Custom Requests — newest rows',
                        'Facebook / Instagram / YouTube — unread DMs and new comments',
                    ],
                    [
                        'Phone ringing beats everything — answer before opening panel tabs',
                        'Follow the order above — do not start with social while follow-ups are unchecked',
                        'Treat every row in web/app lists from today as same-day work',
                        'Human support tab at start — not “when I have time”',
                        'If same phone already has a lead, open it — do not create duplicate',
                    ],
                    [
                        'Log in → confirm you can see leads, WA inbox, and phone lines',
                        'Run the full scan → note anything urgent (emergency, hot booking, live call-back)',
                        'Open follow-up queue → sort mentally: emergency → hot booking → due today → new async',
                        'Quick-touch only: live ring, emergency WA, or customer waiting on provider — then return to scan',
                        'Only after full sweep → start working the queue from top priority',
                    ],
                    [
                        self::panelLink('Today\'s follow-ups', '/admin/lead/todays-followups'),
                        self::panelLink('WhatsApp Active Chats', '/admin/social-inbox/whatsapp/conversations?tab=chats'),
                        self::panelLink('Human support', '/admin/social-inbox/whatsapp/conversations?tab=human_support'),
                    ],
                    'Start',
                ),
                self::leadSourceGuide(
                    'shift-between',
                    'During shift — between tasks (every 30–60 minutes)',
                    'update',
                    'inbox',
                    'While working the queue, new enquiries still arrive. Set a timer — between leads or every 30–60 minutes, run this mini-scan so nothing piles up unseen.',
                    [
                        'Phone missed-call log — any new since last check?',
                        'Header WhatsApp icon — new unread chats?',
                        'Human support tab — new AI “want human” rows?',
                        'Web Bookings / Provider Requests / App Custom Requests — refresh list',
                        'Facebook / Instagram / YouTube — quick DM & comment scan',
                        'Follow-ups due in the next hour — anything about to slip?',
                        'Chats you touched today — still have assignee + status + tags?',
                    ],
                    [
                        'Do not wait until end of shift to check missed calls or social',
                        'One unread WA at 10 AM can become a lost booking by 2 PM if you never look up',
                        'New web form while you are on a long call still needs same-day contact',
                        'If you opened a chat, it must stay tagged until closed or handed over',
                        'Between checks are not optional — they are how live leads do not go cold',
                    ],
                    [
                        'Finish current lead properly first (panel + WA + Followup On) — then scan',
                        'New missed call → WA within 5 min + lead create/update before returning to queue',
                        'New WA → assign yourself + status + tags before typing a long reply',
                        'New web/app row → open entry + linked lead → classify or schedule call',
                        'New social DM → create manual lead same day if you have phone or asked for it',
                        'Log quick note in remarks if you defer — never leave invisible work',
                    ],
                    [
                        self::panelLink('Leads list', '/admin/lead'),
                        self::panelLink('WhatsApp Active Chats', '/admin/social-inbox/whatsapp/conversations?tab=chats'),
                    ],
                    'Between',
                ),
                self::leadSourceGuide(
                    'shift-live',
                    'During shift — while you work (live leads & priority)',
                    'bolt',
                    'live',
                    'Your main job is the follow-up queue — but live contacts always win. Know when to stop, handle fully, then return.',
                    [
                        'Followups Pending Till Today — work in priority order',
                        'Ringing business phone — always interrupt panel work',
                        'Emergency keywords — flooding, no power, gas smell, locked out, safety',
                        'Hot booking — customer waiting on provider reply or slot confirmation',
                        'Provider group SLA — 10-minute reply window on posts you sent',
                        'Conference / callback times you promised customer or provider',
                    ],
                    [
                        'Live phone call beats Instagram, Facebook, and spreadsheet work — every time',
                        'Emergency beats sorting old DMs — handle, document, then return',
                        'Hot booking beats “organizing remarks” — customer waiting is a live lead',
                        'Never start lead #2 while lead #1 has empty panel or no WhatsApp sent',
                        'Non-urgent DM gets quick ack only — not a full qualification mid-emergency',
                    ],
                    [
                        'Default rhythm: one lead at a time until panel + WA + Followup On OR closed status',
                        'Phone rings → answer → notes on call → after call full panel update → WA → next',
                        'Emergency → triage on call → urgency in panel → provider path immediately',
                        'Hot booking → check group / call provider → update customer on WA within minutes',
                        'Non-urgent interrupt (DM, missed call while busy) → short reply + lead row + back to queue',
                        'After every live contact: remarks explain what happened and what is next',
                    ],
                    [
                        self::panelLink('Today\'s follow-ups', '/admin/lead/todays-followups'),
                        self::panelLink('Leads list', '/admin/lead'),
                    ],
                    'Live work',
                ),
                self::leadSourceGuide(
                    'shift-end',
                    'End of shift — handover & close-out',
                    'logout',
                    'auto',
                    'Last 15 minutes: nothing you touched stays half-done. Next shift must continue without calling you.',
                    [
                        'WhatsApp Active Chats — every thread you worked: assignee, status, tags, or closed',
                        'Human support tab — no unassigned escalation left with no owner',
                        'Leads you opened today — Handled By, status, Followup On, or closed',
                        'Followups Pending Till Today — none overdue without action or clear remarks',
                        'Open hot bookings — customer WA sent with honest update if still waiting',
                        'Social apps — no unread DM/comment left without lead in panel',
                        'Missed calls from last hour — lead + WA or documented attempt',
                    ],
                    [
                        'Leaving chat updated but lead empty is the #1 handover failure',
                        'Open lead with no Followup On = lost lead tomorrow',
                        '“I will do it tomorrow” with no panel date = next shift misses it',
                        'Untagged WA thread looks “done” in chat but invisible in lead list',
                        'End-of-shift social check catches what mid-shift scan missed',
                    ],
                    [
                        'Walk your touched leads — each must have next action or closed status',
                        'Write handover in remarks: who called, what promised, what provider said, next step',
                        'Set Followup On on anything still open — including for next shift if not yours',
                        'Close or tag chats — no “open” thread with no assignee',
                        'Brief manager or next staff verbally if anything urgent still live',
                        'Log out only when queue is clean or clearly handed over',
                    ],
                    [
                        self::panelLink('Today\'s follow-ups', '/admin/lead/todays-followups'),
                        self::panelLink('WhatsApp Active Chats', '/admin/social-inbox/whatsapp/conversations?tab=chats'),
                        self::panelLink('Leads list', '/admin/lead'),
                    ],
                    'End',
                ),
            ],
            'flowcharts' => [['key' => 'shift-routine', 'title' => 'Shift flow — start → during → end']],
            'remember' => [
                'Start: full channel scan before queue work',
                'Between: mini-scan every 30–60 min — phone, WA, Human support, web/app, social',
                'Live: ringing phone & emergencies interrupt everything — finish each lead fully',
                'End: every touched lead and chat documented for handover',
                'If it is not in Leads with source + phone + remarks, the team cannot see it',
            ],
            'avoid' => [
                'Starting shift inside one WhatsApp chat without scanning everything else',
                'Checking Facebook only at end of day',
                'Moving to next lead before panel + WhatsApp on current lead',
                'Open chats with no assignee, status, or tags at logout',
                'Overdue follow-ups with no remarks explaining why',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideLeadClassifications(): array
    {
        return [
            'id' => 'lead-classifications',
            'title' => 'Lead types',
            'subtitle' => 'Section 2 — classify every lead',
            'type' => 'visual',
            'cards_layout' => 'detail',
            'cards' => [
                [
                    'icon' => 'home_repair_service',
                    'title' => 'Customer',
                    'text' => 'Needs a home service — plumbing, electrical, cleaning, repair, or similar work at their address. They want Panun Kaergar to send a partner.',
                    'color' => 'customer',
                    'points' => [
                        'Call for full details: service, exact problem, complete address, date and time',
                        'Set type Customer in panel once confirmed — not before you know',
                        'Path A if ready to book directly; Path B if they want to talk to the provider first',
                        'After every call: panel remarks → WhatsApp summary → Followup On until booked or closed',
                    ],
                ],
                [
                    'icon' => 'handshake',
                    'title' => 'Provider',
                    'text' => 'Wants to join Panun Kaergar as a service partner — they will receive jobs from us. Not someone booking a plumber for their own home.',
                    'color' => 'provider',
                    'points' => [
                        'Brief onboarding call — explain how partners get jobs, commission, and areas we need',
                        'WhatsApp agreement + required documents with a clear submit-by date',
                        'Follow provider onboarding slides until added in panel and correct WhatsApp group',
                        'Do not run the customer booking flow for a partner application',
                    ],
                ],
                [
                    'icon' => 'help_outline',
                    'title' => 'Unknown',
                    'text' => 'Not enough information yet — only “hi”, “I called”, or no service mentioned. Temporary type until you speak to them and learn what they want.',
                    'color' => 'unknown',
                    'points' => [
                        'Set Unknown in panel — do not guess Customer or Provider',
                        'Outbound call same day — ask what help they need from Panun Kaergar',
                        'On a successful call, change to exactly one type before you hang up',
                        'No pickup → max 3 attempts with WhatsApp + Add follow-up each time, then Mark as Invalid → Did not Know About Enquiry',
                    ],
                ],
                [
                    'icon' => 'event',
                    'title' => 'Future customer',
                    'text' => 'Valid contact but no home service needed today — saving our number, renovation in a few months, just moved, or similar.',
                    'color' => 'future',
                    'points' => [
                        'On call, confirm why they contacted us and why they are not booking now',
                        'Set type Future customer + pick the reason from the panel dropdown',
                        'Tell them what Panun Kaergar offers and ask them to save 8899881555',
                        'Warm-close WhatsApp — this is a valid success, not Invalid',
                    ],
                ],
                [
                    'icon' => 'block',
                    'title' => 'Invalid',
                    'text' => 'Panun Kaergar cannot help — wrong service (e.g. car repair) or location outside our service area. Still handle politely.',
                    'color' => 'invalid',
                    'points' => [
                        'Write in remarks exactly what they asked for and where',
                        'Set type Invalid + select the reason in panel',
                        'Polite WhatsApp — sorry we cannot help, briefly list what we do offer',
                        'Never use Invalid for “no need now” — that is Future customer',
                    ],
                ],
            ],
            'remember' => ['Pick exactly one type in panel', 'Unknown is temporary — classify on the call'],
            'avoid' => ['Guessing type from phone number alone', 'Leaving Unknown after a successful call'],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideUsingLeadPage(): array
    {
        return [
            'id' => 'using-lead-page',
            'title' => 'Using the lead page',
            'subtitle' => 'Section 2B — list, detail, and panel actions',
            'type' => 'visual',
            'source_guide_cols' => [
                'where' => 'Where in panel',
                'dont_miss' => 'Common mistakes',
                'manage' => 'What to do',
            ],
            'panel_links' => [
                self::panelLink('Leads list', '/admin/lead'),
                self::panelLink('Today\'s lead follow-ups', '/admin/lead/todays-followups'),
                self::panelLink('Outbound enquiries', '/admin/lead/outbound-enquiry'),
                self::panelLink('Lead configuration', '/admin/lead/configuration'),
            ],
            'qualifier' => [
                'title' => 'Four ways to update a lead — use the right one',
                'items' => [
                    ['question' => 'Unknown lead — you now know the type after a call?', 'type' => 'Mark as …', 'note' => 'Hero buttons: Mark as Customer / Provider / Future customer / Invalid — opens modal with required fields'],
                    ['question' => 'Customer or Provider — edit zone, category, district, or non-cancel status?', 'type' => 'Edit', 'note' => 'Edit / Add Details on the type card → same modal, merges into existing data'],
                    ['question' => 'Change source, remarks, Handled By, or next follow-up date?', 'type' => 'Inline edit', 'note' => 'Small Edit on each card → save without opening the full type modal'],
                    ['question' => 'Just finished a phone call — log who you called and when?', 'type' => 'Add Call Log', 'note' => 'Activity → Call Logs → Add Call Log — Customer / Provider / Other, datetime, remarks, optional recording (also creates a Taken follow-up row)'],
                    ['question' => 'Customer or Provider — move to Cancelled / closed with a reason?', 'type' => 'Change Status', 'note' => 'Status chip → pick Cancel status → cancellation reason modal (required). Cannot cancel from Edit modal alone.'],
                ],
            ],
            'source_guides' => [
                self::leadSourceGuide(
                    'lead-list',
                    'Lead list — find your queue',
                    'view_list',
                    'manual',
                    'The list is how you pick work. Tabs filter by type; filters narrow by source, handler, open/closed, and dates. Follow-up badges show Missed / Due / Due soon on each row.',
                    [
                        'Leads and bookings → Leads',
                        'Tabs: All / Unknown / Customer / Future Customer / Provider / Invalid',
                        'Filter drawer: Source, Ad Source, Handled By, Open or Closed, date ranges',
                        'Customer tab: status, zone, category, tags. Provider tab: district, zone, checklist count',
                        'Future tab: outbound enquiry count filter',
                        'Row click or View → opens lead detail',
                    ],
                    [
                        'Working only from All tab and missing type-specific columns',
                        'Ignoring Missed / Due badges — someone promised action yesterday',
                        'Creating duplicate when open lead already exists for same phone',
                        'Filtering Open only but never checking Unknown tab for unqualified items',
                    ],
                    [
                        'Start shift on Today\'s follow-ups, then Unknown + your assigned Open leads',
                        'Use tab matching your task — Customer tab for bookings, Provider for onboarding',
                        'Search by phone or Lead ID before Add New Lead',
                        'Click row → read hero badges (type, status, pending follow-up) before calling',
                    ],
                    [
                        self::panelLink('Leads list', '/admin/lead'),
                        self::panelLink('Today\'s follow-ups', '/admin/lead/todays-followups'),
                    ],
                ),
                self::leadSourceGuide(
                    'lead-detail-layout',
                    'Lead detail page — what each section is for',
                    'web',
                    'inbox',
                    'Open any lead to see the full picture. Hero = summary. Main cards = type-specific info + Initial Call Recording. Sidebar = quick actions and schedule. Activity = comments, follow-ups, change log, and call logs.',
                    [
                        'Hero: name, phone, type badge, Open/Closed, status, pending follow-up alert',
                        'Pipeline stepper: customer/provider status steps (read-only progress view)',
                        'Main: Source & Ad Source, type info card, Initial Remarks, Initial Call Recording',
                        'Sidebar: Assigned To, Schedule (Received On + Next Follow-up), quick actions',
                        'Activity pills: Comments | Follow-ups (count) | Change History | Call Logs (count)',
                    ],
                    [
                        'Updating only Initial Remarks and never adding follow-up or call log rows for each call',
                        'Leaving Assigned To on AI or unassigned after you take the lead',
                        'Closing chat but lead still Open with no Followup On',
                        'Skipping Change History when disputing who changed status',
                        'Uploading every call to Initial Call Recording — that card is for the first call only',
                    ],
                    [
                        'Take ownership: Assigned To = you, Handled By = you',
                        'Read Initial Remarks + Call Logs / Follow-ups tabs before dialling — know prior attempts',
                        'Use Comments for internal handover (@ staff); use Remarks for customer-facing summary',
                        'Check pipeline stepper to see if lead is Pending, Booked, or needs Cancel',
                        'If first-call recording exists: play or transcribe before calling back',
                    ],
                    [
                        self::panelLink('Leads list', '/admin/lead'),
                    ],
                ),
                self::leadSourceGuide(
                    'mark-as-unknown',
                    'Unknown leads — Mark as buttons',
                    'label',
                    'manual',
                    'Only Unknown leads show Mark as Invalid / Future Customer / Customer / Provider in the hero. Each opens a modal — fill required fields before saving. After Mark as, follow the matching handling slide.',
                    [
                        'Open Unknown lead → hero → Mark as Customer / Provider / Future customer / Invalid',
                        'Customer modal: zone, area, category, service, estimated date, status',
                        'Provider modal: district, zones, categories, address, status',
                        'Future modal: reason dropdown (required) + area + remarks',
                        'Invalid modal: reason dropdown (required) + area + remarks',
                    ],
                    [
                        'Changing type by editing remarks only — type must change in panel',
                        'Mark as Customer before you have service, address, and timing on the call',
                        'Using Invalid for “no need now” — that is Future customer',
                        'Leaving Unknown after a successful qualify call',
                    ],
                    [
                        'Successful call → Mark as exactly one type in same update as remarks + WA',
                        'Dropdown reason must match what they said (Future / Invalid)',
                        'If still no pickup after 3 attempts → Mark as Invalid → reason Did not Know About Enquiry',
                        'If DM clearly shows customer need but no pickup → Mark as Customer first, then Change Status → Cancel → No Response From Customer',
                    ],
                    [
                        self::panelLink('Unknown leads tab', '/admin/lead?tab=unknown'),
                        self::panelLink('Invalid reasons config', '/admin/lead/configuration'),
                    ],
                ),
                self::leadSourceGuide(
                    'follow-ups-panel',
                    'Follow-ups — log every contact attempt',
                    'event_repeat',
                    'live',
                    'Followup On is the next due date. Each actual call or WhatsApp must also be logged with Add follow-up (or Take follow-up when one is pending). The Follow-ups tab count = how many times the lead was contacted.',
                    [
                        'Sidebar → Add Follow-up (when no pending) or Take Follow-up (banner when due)',
                        'Modal: Taken or Reschedule, Date/Time, Call or WhatsApp, Remarks, Urgency',
                        'Call follow-ups: optional recording upload',
                        'Activity → Follow-ups tab — history table with date, channel, taken by',
                        'Activity → Call Logs tab — same call rows in a phone-only view (includes initial recording if uploaded)',
                        'Schedule card → Edit Next Follow-up inline',
                    ],
                    [
                        'Writing "Attempt 2/3" only in remarks with zero follow-up rows',
                        'Reschedule without remarks explaining why',
                        'Open customer/unknown/provider lead with no next follow-up date when still active',
                        'Assuming the system auto-counts calls — it does not',
                    ],
                    [
                        'Every attempt: Add/Take follow-up → Taken → Call or WhatsApp → remarks e.g. "Attempt 2/3 — no pickup, WA sent"',
                        'Also update Initial Remarks with running summary for quick read',
                        'Set Followup On for next attempt date before closing the modal',
                        'Before close as No Response: Follow-ups tab should show 3 Taken rows (or remarks if legacy)',
                    ],
                    [
                        self::panelLink('Today\'s follow-ups', '/admin/lead/todays-followups'),
                    ],
                    'Log every touch',
                ),
                self::leadSourceGuide(
                    'initial-call-recording',
                    'Initial call recording — first contact audio',
                    'graphic_eq',
                    'live',
                    'One recording per lead for the first inbound or qualify call — separate from follow-up recordings. Upload on the main card, play back, and Transcribe for AI summary + transcript.',
                    [
                        'Main panel → Initial Call Recording card → Edit → upload audio (max 10MB)',
                        'View → play recording · Transcribe Recording → AI summary + full transcript',
                        'Also appears in Activity → Call Logs tab as the initial row',
                        'Copy summary into Initial Remarks after transcribing',
                    ],
                    [
                        'Uploading every call here — use Add Call Log or Add follow-up for later calls',
                        'Skipping transcription when recording is long or unclear — summary helps next shift',
                        'Replacing Initial Remarks with transcript only — still write your own summary',
                        'Confusing with follow-up recording on Add follow-up modal',
                    ],
                    [
                        'After first qualify call: upload recording if available from handset or call app',
                        'Click Transcribe → wait for summary → copy key details to Initial Remarks',
                        'Before handover: next shift can play recording or read transcript without calling you',
                        'Regenerate if transcription missed an important detail',
                    ],
                    [],
                    'First call audio',
                ),
                self::leadSourceGuide(
                    'call-logs-panel',
                    'Call logs — every phone contact in one place',
                    'add_call',
                    'live',
                    'Call Logs tab shows all phone contacts: initial recording (if uploaded) plus every call logged via Add Call Log or Add follow-up → Call. Use Add Call Log when you want to record who you called (customer, provider, or other number).',
                    [
                        'Activity → Call Logs tab (count badge) → table: Who · When · Remarks · Recording',
                        'Add Call Log button → Who you called (Customer / Provider / Other), When, Remarks, optional recording',
                        'Provider: search and select from panel · Other: enter name + phone manually',
                        'Each Add Call Log also creates a Taken follow-up row (Call channel)',
                        'Recording row → View → play + Transcribe (same as follow-up recordings)',
                    ],
                    [
                        'Logging calls only in remarks — Call Logs tab stays empty',
                        'Using Add Call Log for WhatsApp-only contact — use Add follow-up → WhatsApp instead',
                        'Calling a provider but leaving party type as Customer',
                        'Expecting Call Logs to auto-fill from phone — you must log each call',
                    ],
                    [
                        'After every outbound call: Add Call Log OR Add follow-up → Taken → Call (both work)',
                        'Provider chase call → Add Call Log → Provider → pick provider → remarks "Asked availability Sat 10 AM"',
                        'Unknown Attempt 2/3 → Add Call Log with date/time + "no pickup, WA sent" in remarks',
                        'Before Mark as from Unknown: at least one call row visible in Call Logs or Follow-ups tab',
                    ],
                    [],
                    'Log every call',
                ),
                self::leadSourceGuide(
                    'customer-panel-tools',
                    'Customer leads — booking tools',
                    'home_repair_service',
                    'auto',
                    'After Mark as Customer or web auto-lead, use customer-specific sidebar tools. Create Booking appears when status is pending and no AI booking in progress.',
                    [
                        'Sidebar → Create Booking for this Lead (Path A/B after ₹100)',
                        'Hero banner → Continue with AI booking (when WhatsApp AI draft exists)',
                        'Sidebar → Tags (Hot, Emergency, etc.) — shown on customer list',
                        'Sidebar → Temporary Provider — assign while discussing before booking',
                        'Customer info card → Edit: zone, area, category, status (not Cancel via Edit)',
                        'Status chip → Change Status for Cancel with reason',
                    ],
                    [
                        'Create Booking before customer confirms slot and ₹100 collected',
                        'Cancel via Edit modal — use Change Status → Cancel → pick reason',
                        'HOT lead with no tag and no urgency in remarks',
                        'Ignoring AI booking banner when customer started on WhatsApp',
                    ],
                    [
                        'Path A/B decided on call → remarks + tag if HOT → group post → Create Booking when ready',
                        'No pickup after 3 attempts → Change Status → Cancel → No Response From Customer',
                        'Other cancel reasons (price, denied service) → Change Status → Cancel → matching reason + remarks',
                        'After booking: status moves toward Booked; booking ID shows in hero and list',
                    ],
                    [
                        self::panelLink('Customer leads tab', '/admin/lead?tab=customer'),
                        self::panelLink('Cancellation reasons config', '/admin/lead/configuration'),
                    ],
                ),
                self::leadSourceGuide(
                    'provider-panel-tools',
                    'Provider leads — onboarding tools',
                    'handshake',
                    'manual',
                    'Provider leads track onboarding in panel: status pipeline, checklist items, and match to existing panel provider record.',
                    [
                        'Provider info card → Edit: district, zones, categories, address, status',
                        'Provider Checklist table → Edit → tick items done → Update',
                        'List column: Checklist Done/Total',
                        'Hero/link: Is Added in Panel (phone match to provider record)',
                        'Change Status → Cancel when closing (no response, docs not received, etc.)',
                    ],
                    [
                        'Adding to WhatsApp group before Step 3 final call and panel record complete',
                        'Checklist left empty while claiming onboarding done',
                        'Provider cancelled via remarks only — must Change Status with reason',
                    ],
                    [
                        'Step 1–4 onboarding from Provider slide — update checklist as each item completes',
                        'No pickup after 3 attempts → Change Status → Cancel → Not Intrested (provider no response)',
                        'Docs not received after 3 reminders → Change Status → Cancel → documents not received reason',
                        'When registered: status complete, checklist full, lead matches panel provider',
                    ],
                    [
                        self::panelLink('Provider leads tab', '/admin/lead?tab=provider'),
                        self::panelLink('Checklist items config', '/admin/lead/configuration'),
                    ],
                ),
                self::leadSourceGuide(
                    'future-outbound',
                    'Future customer — outbound enquiries',
                    'event',
                    'inbox',
                    'Future customer is a valid close — but you can log later outbound touches separately. Use Add Outbound Enquiry on the lead or the standalone Outbound Enquiries page.',
                    [
                        'Future customer lead → Add Outbound Enquiry (sidebar or main section)',
                        'Fields: contacted through (call/message), status, datetime, remarks',
                        'Standalone: Leads and bookings → Outbound Enquiries',
                        'Future tab filter: leads with no enquiries / exact count / at least N',
                    ],
                    [
                        'Treating Future as failure — it is a success state',
                        'Calling back months later with no outbound enquiry logged',
                        'Invalid reason for “no need today” — use Future customer reason dropdown',
                    ],
                    [
                        'On call: Mark as Future customer + reason dropdown + warm WA',
                        'If you contact them again later → Add Outbound Enquiry each time',
                        'Referral from Future call → separate Customer lead if pursuing today',
                    ],
                    [
                        self::panelLink('Future customer tab', '/admin/lead?tab=future_customer'),
                        self::panelLink('Outbound enquiries', '/admin/lead/outbound-enquiry'),
                    ],
                ),
                self::leadSourceGuide(
                    'close-paths',
                    'How to close — pick the correct path',
                    'task_alt',
                    'live',
                    'Different types close differently. Wrong path breaks reports and confuses the next shift.',
                    [
                        'Unknown + vague + 3 no-pickups → Mark as Invalid → Did not Know About Enquiry',
                        'Unknown + clear customer need in DM + 3 no-pickups → Mark as Customer → Change Status → Cancel → No Response From Customer',
                        'Customer / Provider no response → Change Status → Cancel → No Response From Customer (customer) or Not Intrested (provider)',
                        'Invalid enquiry → Mark as Invalid → reason (service not offered, outside area, etc.)',
                        'Future → Mark as Future customer → reason dropdown — no Cancel needed',
                    ],
                    [
                        'Cancelled ✓ on Unknown vague no-pickup — use Invalid → Did not Know About Enquiry reason',
                        'Invalid when they need service later — use Future customer',
                        'Cancel without cancellation reason modal filled',
                        'Closing with empty remarks — managers audit closes',
                    ],
                    [
                        'Before any close: remarks list all attempts, WA dates, and outcome',
                        'Dropdown reason must match remarks wording',
                        'After close: lead shows Closed; verify in correct tab (Invalid / Future / Cancelled status)',
                        'Managers: Lead Configuration defines reason names — pick the matching one',
                    ],
                    [
                        self::panelLink('Invalid leads tab', '/admin/lead?tab=invalid'),
                        self::panelLink('Lead configuration', '/admin/lead/configuration'),
                    ],
                    'Close correctly',
                ),
                self::leadSourceGuide(
                    'add-new-lead',
                    'Add New Lead — from the list',
                    'person_add',
                    'manual',
                    'Social comments, some missed calls, and referrals need a manual lead. Open Leads list → Add New Lead. System warns if the phone already has an open lead.',
                    [
                        'Leads list → Add New Lead button (top right)',
                        'Fields: Name, Phone*, Source*, Lead Type*, Received datetime',
                        'Optional: Ad Source, Handled By, Next Follow-up, Remarks',
                        'Type = Invalid or Future Customer → reason dropdown appears on create',
                        'Duplicate check: saving warns if open lead exists for same phone — open it instead',
                    ],
                    [
                        'Creating duplicate when warning shows an open lead',
                        'Lead with no phone and no way to contact — ask in social reply first',
                        'Wrong Source — breaks reports (use Facebook / Instagram / Phone / Website / AI Chat)',
                        'Skipping Ad Source when enquiry came from a specific ad campaign',
                    ],
                    [
                        'Collect phone + service hint before creating',
                        'Paste exact DM/comment text into Remarks',
                        'Vague message → Unknown + outbound call same day + Followup On',
                        'Clear need → pick correct type immediately with reason if Invalid/Future',
                    ],
                    [
                        self::panelLink('Leads list', '/admin/lead'),
                        self::panelLink('Sources & ad sources config', '/admin/lead/configuration'),
                    ],
                ),
                self::leadSourceGuide(
                    'todays-followups-page',
                    'Today\'s follow-ups — your daily queue',
                    'today',
                    'live',
                    'Dashboard link and Leads → Today\'s follow-ups. This is the first page most staff open each shift — overdue rows highlighted, click through to Take follow-up on the lead.',
                    [
                        'Dashboard → Today\'s follow-ups (Leads + Bookings)',
                        'Or Leads and bookings → Today\'s lead follow-ups',
                        'Filters: date range, Handled By, lead type, urgency',
                        'Missed / overdue rows highlighted — action needed now',
                        'Click lead name → opens detail → Take Follow-up banner or sidebar Add Follow-up',
                    ],
                    [
                        'Working from All leads tab only and missing overdue items',
                        'Ignoring rows assigned to AI/Unassigned that are yours to take',
                        'Closing the page without acting on red/missed rows',
                    ],
                    [
                        'Shift start: filter Handled By = you (or Unassigned you will take)',
                        'Work missed first → due today → then new queue',
                        'Click row → read Follow-ups tab + remarks before calling',
                        'After action: log follow-up + set next Followup On or close',
                    ],
                    [
                        self::panelLink('Today\'s follow-ups', '/admin/lead/todays-followups'),
                    ],
                    'Open first',
                ),
                self::leadSourceGuide(
                    'follow-up-details',
                    'Follow-up modal — Taken vs Reschedule',
                    'phone_callback',
                    'live',
                    'Add Follow-up when none pending. Take Follow-up when banner shows due/missed. Every contact attempt = one row in Follow-ups tab.',
                    [
                        'Take mode: Action = Taken (contact happened) or Reschedule (could not reach — new date)',
                        'Taken: pick Call or WhatsApp, date/time, remarks, urgency High/Medium/Low',
                        'Call Taken: optional recording upload (max 10MB) → transcribe later',
                        'Reschedule: set new Followup On + explain why in remarks',
                        'Follow-up remarks show when expanding recording row — also copy summary to Initial Remarks',
                    ],
                    [
                        'Using Reschedule when you actually spoke to the customer — use Taken',
                        'Taken without Call/WhatsApp channel selected',
                        'Expecting attempt count in remarks only — must add follow-up rows',
                        'Open lead with no next date after Reschedule',
                    ],
                    [
                        'No pickup attempt → Taken → Call → "Attempt 2/3 — no pickup, WA sent"',
                        'WhatsApp only → Taken → WhatsApp → paste template used',
                        'Customer asked callback Thu → Reschedule → Followup On Thu 2 PM + reason',
                        'Emergency lead → urgency High on follow-up + HOT tag on customer lead',
                    ],
                    [],
                ),
                self::leadSourceGuide(
                    'comments-vs-remarks',
                    'Comments vs Initial remarks — handover',
                    'forum',
                    'inbox',
                    'Initial Remarks = customer-facing summary any shift can read. Comments = internal team notes with @mentions and pin for handover.',
                    [
                        'Initial Remarks card → Edit → summary of calls, promises, next step',
                        'Activity → Comments tab → compose, @ staff name, optional @ provider/service',
                        'Activity → Call Logs tab — phone-only history (initial recording + logged calls)',
                        'Pin important comment (shift handover note) — shows at top',
                        'Change History tab — who changed type, status, Handled By, dates',
                    ],
                    [
                        'Long internal debate in Remarks — confuses next shift reading customer context',
                        'Comments only with no panel update — lead still shows wrong status',
                        'Using Comments instead of Add follow-up or Add Call Log for contact attempts',
                    ],
                    [
                        'Remarks example: "Customer — tap leak Rajbagh, Path A, waiting provider, WA sent 2 PM"',
                        'Pinned comment example: "@Sara — customer wants female plumber only, noted 3 Aug"',
                        'End of shift: Remarks complete + pinned comment if anything unusual',
                        'Dispute on status change → check Change History before re-arguing',
                    ],
                    [],
                ),
            ],
            'card_groups' => [
                [
                    'title' => 'Open vs Closed — when each shows',
                    'hint' => 'List filter Open/Closed uses these rules — not the same as "done in your head".',
                    'layout' => 'detail',
                    'cards' => [
                        [
                            'icon' => 'help_outline',
                            'title' => 'Unknown',
                            'text' => 'Always Open until reclassified or closed as Invalid.',
                            'color' => 'unknown',
                            'points' => ['Stays Open through no-pickup attempts', 'Mark as Invalid → becomes Closed'],
                        ],
                        [
                            'icon' => 'home_repair_service',
                            'title' => 'Customer / Provider',
                            'text' => 'Open while status is Pending or Booked. Closed when Completed or Cancelled.',
                            'color' => 'customer',
                            'points' => ['Pipeline stepper shows progress', 'Cancel via Change Status → reason modal'],
                        ],
                        [
                            'icon' => 'event',
                            'title' => 'Future / Invalid',
                            'text' => 'Always Closed once saved — valid end states.',
                            'color' => 'future',
                            'points' => ['Future may still get Outbound Enquiries logged', 'Invalid = no further follow-up required unless error'],
                        ],
                    ],
                ],
                self::configuredDropdownCardGroup(),
            ],
            'ui_maps' => [
                [
                    'title' => 'Lead detail page — click map',
                    'summary' => 'Use this map until you know the layout by heart. Numbers match the order staff use most.',
                    'steps' => [
                        ['label' => '1 Hero badges', 'text' => 'Type · Open/Closed · Status chip + edit (Change Status) · Pending follow-up'],
                        ['label' => '2 Mark as row', 'text' => 'Unknown only — four buttons across bottom of hero'],
                        ['label' => '3 AI booking banner', 'text' => 'Customer/Unknown — Continue with AI booking + View AI chat'],
                        ['label' => '4 Sidebar quick actions', 'text' => 'Create Booking · Add Follow-up · Tags · Temporary Provider · Assigned To'],
                        ['label' => '5 Type info card', 'text' => 'Edit / Add Details — zone, category, district, checklist'],
                        ['label' => '5b Initial Call Recording', 'text' => 'Main card — upload first-call audio · View · Transcribe for AI summary'],
                        ['label' => '6 Activity tabs', 'text' => 'Comments · Follow-ups (count) · Change History · Call Logs (count)'],
                    ],
                ],
                [
                    'title' => 'Add call log modal — fields',
                    'summary' => 'Quick way to log a phone call — also creates a Taken follow-up row automatically.',
                    'steps' => [
                        ['label' => 'Who you called', 'text' => 'Customer (lead contact) · Provider (search panel) · Other (name + phone)'],
                        ['label' => 'When', 'text' => 'Date and time of the call — defaults to now'],
                        ['label' => 'Remarks', 'text' => 'Attempt 2/3 — no pickup, WA sent · or provider chase notes'],
                        ['label' => 'Recording', 'text' => 'Optional audio upload (max 10MB) → View row → Transcribe later'],
                    ],
                ],
                [
                    'title' => 'Add follow-up modal — fields',
                    'summary' => 'Log every call and WhatsApp here — not only in remarks.',
                    'steps' => [
                        ['label' => 'Action', 'text' => 'Taken = contact happened · Reschedule = new date, no contact yet'],
                        ['label' => 'Channel', 'text' => 'Call or WhatsApp — required for Taken'],
                        ['label' => 'Remarks', 'text' => 'Attempt 1/3 — no pickup, WA sent'],
                        ['label' => 'Urgency', 'text' => 'High for HOT/emergency · Medium default · Low for routine'],
                        ['label' => 'Recording', 'text' => 'Optional on Call — upload then Transcribe from history row'],
                    ],
                ],
            ],
            'path_steps' => [
                [
                    'label' => 'Panel clicks — Mark as Customer (from Unknown)',
                    'steps' => [
                        self::trainingStep(
                            'Hero → Mark as Customer Lead',
                            'Only after successful qualify call with full job details collected.',
                            'Modal opens — fill required customer fields',
                            null,
                            'Save — type changes to Customer.',
                        ),
                        self::trainingStep(
                            'Fill customer modal fields',
                            'Match what customer said on call. Area can be typed new if not in list.',
                            'Status (usually Pending), Zone, Area, Category, Sub-category, Service, Variant, Estimated service date/time, service details in remarks',
                            'Plumbing · Kitchen tap leak · Zone Srinagar · Area Rajbagh · Tomorrow 10 AM',
                            'Add HOT tag in sidebar after save if emergency.',
                        ),
                        self::trainingStep(
                            'After save — sidebar actions',
                            'Inline edit Source/Remarks if needed. Set Assigned To + Handled By = you.',
                            'Remarks with Path A/B, Followup On if waiting on provider',
                            null,
                            'WhatsApp customer → then provider group if Customer path.',
                        ),
                    ],
                ],
                [
                    'label' => 'Panel clicks — Create Booking (Customer, Path A/B)',
                    'steps' => [
                        self::trainingStep(
                            'When button appears',
                            'Sidebar → Create Booking for this Lead — visible when Customer status is Pending and no AI booking in progress.',
                            '₹100 collected from customer first',
                            null,
                            'Click → booking form opens pre-filled from lead.',
                        ),
                        self::trainingStep(
                            'Complete booking form',
                            'Confirm provider, slot, address, service. Save booking.',
                            'Booking ID generated',
                            'Return to lead — success alert may show with booking link',
                            'Hero shows Booking ID link; status moves toward Booked.',
                        ),
                        self::trainingStep(
                            'After booking saved',
                            'Update remarks with provider name + booking ID. WhatsApp both sides.',
                            'Pipeline stepper shows Booked',
                            null,
                            'Followup On for service day if your process requires confirmation call.',
                        ),
                    ],
                ],
                [
                    'label' => 'Panel clicks — Continue with AI booking',
                    'steps' => [
                        self::trainingStep(
                            'Banner on lead page',
                            'Shows when WhatsApp AI started a draft booking linked to this lead.',
                            'Read AI status chip on banner',
                            null,
                            'View AI chat to read full bot conversation first.',
                        ),
                        self::trainingStep(
                            'Continue with AI booking',
                            'Click button → completes or adjusts AI draft in booking flow.',
                            'Fix missing fields AI could not collect',
                            'Phone, address, service time confirmed with customer if needed',
                            'Finish human review → booking saved or hand off to manual Create Booking.',
                        ),
                        self::trainingStep(
                            'Sync lead panel',
                            'Handled By = you, remarks summarize AI + your changes, customer WA sent.',
                            'Tags if HOT, Followup On if waiting on customer reply',
                            null,
                            'Do not run duplicate manual booking if AI booking already linked.',
                        ),
                    ],
                ],
                [
                    'label' => 'Panel clicks — Temporary provider & tags (Customer)',
                    'steps' => [
                        self::trainingStep(
                            'When to use Temporary Provider',
                            'Path B or discussing provider before booking — customer asked about a specific partner or you are negotiating.',
                            'Sidebar → Temporary Provider → search name/phone',
                            null,
                            'Assign → timestamp saved on lead.',
                        ),
                        self::trainingStep(
                            'Customer tags',
                            'Sidebar → Tags → tick Hot, Emergency, or create new tag inline.',
                            'Tags show on Customer list tab for managers',
                            'HOT + Emergency for whole-house power cut waiting on provider',
                            'Tags supplement remarks — do not replace panel update.',
                        ),
                        self::trainingStep(
                            'Remove temporary provider',
                            'When booking confirmed with same or different provider → remove temp assignment.',
                            'Create Booking with final provider',
                            null,
                            'Update remarks with final provider name.',
                        ),
                    ],
                ],
                [
                    'label' => 'Panel clicks — Provider checklist & panel match',
                    'steps' => [
                        self::trainingStep(
                            'Provider Checklist card',
                            'Edit → tick items as onboarding steps complete (agreement sent, docs received, added to group, etc.).',
                            'List tab shows Done/Total count',
                            null,
                            'Update after each onboarding milestone.',
                        ),
                        self::trainingStep(
                            'Is Added in Panel link',
                            'Hero meta line — auto-match by phone/name to provider record.',
                            'Click to open provider profile if match found',
                            null,
                            'Step 4 onboarding — ensure panel provider exists before WhatsApp group.',
                        ),
                        self::trainingStep(
                            'Change Status when registered',
                            'Status chip → edit → move to completed/registered status when onboarding done.',
                            'Or Cancel with reason if closing incomplete onboarding',
                            null,
                            'Checklist should be fully ticked when claiming complete.',
                        ),
                    ],
                ],
                [
                    'label' => 'Panel clicks — Outbound enquiry (Future customer)',
                    'steps' => [
                        self::trainingStep(
                            'Add Outbound Enquiry',
                            'Future customer lead → sidebar or section button → modal opens.',
                            'Customer name, phone, contacted through (call/message), status, datetime, remarks',
                            null,
                            'Save — count badge on hero increases.',
                        ),
                        self::trainingStep(
                            'Outbound status link types',
                            'Some statuses require linking to a Lead or Booking ID — pick per configured status.',
                            'Standalone page: Leads → Outbound Enquiries for all logs',
                            null,
                            'Future tab filter: find leads with no outbound touches yet.',
                        ),
                    ],
                ],
                [
                    'label' => 'Post-booking — status progression',
                    'steps' => [
                        self::trainingStep(
                            'Pending → Booked',
                            'Automatic when Create Booking saved and linked to lead.',
                            'Booking ID in hero + customer list column',
                            null,
                            'WhatsApp confirmation to customer and provider.',
                        ),
                        self::trainingStep(
                            'Booked → Completed',
                            'After service done — Change Status → Completed (or your configured completed status).',
                            'Remarks: service outcome, any issues',
                            null,
                            'Lead becomes Closed.',
                        ),
                        self::trainingStep(
                            'Booking Requests vs Create Booking from lead',
                            'App Custom Requests create Customer leads — qualify like phone. Standard in-app bookings live under Booking Requests — different list. Create Booking from lead is for panel Customer leads you qualified manually.',
                            'Use correct list for channel; one booking path per customer need',
                            null,
                            'See Lead sources slide for App Custom Requests.',
                        ),
                        self::trainingStep(
                            'Service-day follow-up',
                            'Optional Followup On morning of job — confirm provider reached customer.',
                            'Add follow-up Taken → Call after service',
                            null,
                            'Move to Completed when confirmed done.',
                        ),
                    ],
                ],
            ],
            'remember' => [
                'Four update flows: Mark as (Unknown) · Edit (type fields) · Inline edit (remarks/schedule) · Change Status (status chip edit)',
                'Log every call/WA as Add follow-up → Taken or Add Call Log — Follow-ups tab = contact count · Call Logs tab = phone-only view',
                'Initial Call Recording = first call audio on main card · transcribe → copy summary to Initial Remarks',
                'Remarks = customer summary · Comments = internal @ handover · Change History = audit',
                'Open/Closed rules + configured dropdown names — see cards above',
                'Unknown vague no-pickup → Invalid → Did not Know About Enquiry — DM with details → Customer then Cancel',
            ],
            'avoid' => [
                'Remarks-only updates when the panel has a button for it',
                'Skipping follow-up or call log rows and expecting auto attempt count',
                'Uploading repeat calls to Initial Call Recording instead of Add Call Log',
                'Cancel from Edit modal — use Change Status on status chip',
                'Wrong dropdown reason name vs what customer actually said',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideWorkflowChecklist(): array
    {
        return [
            'id' => 'workflow-checklist',
            'title' => 'Workflow checklist in the panel',
            'subtitle' => 'FAB on lead details · stuck queue · gates before Create Booking',
            'type' => 'visual',
            'panel_links' => [
                self::panelLink('Workflow Stuck Items', '/admin/workflow/stuck'),
                self::panelLink('Leads list', '/admin/lead'),
                self::panelLink('Today\'s lead follow-ups', '/admin/lead/todays-followups'),
            ],
            'important' => 'Checklist steps match this training deck — the panel reads the same workflow definitions.',
            'card_groups' => [
                [
                    'title' => 'Where to find it',
                    'hint' => 'On open Unknown and Customer lead detail pages.',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'pending_actions',
                            'title' => 'Floating workflow FAB',
                            'text' => 'Bottom-right on lead details — next step + progress.',
                            'color' => 'customer',
                            'points' => [
                                'Expand to see qualification steps for this lead',
                                'Tick checkbox when you finish a manual step',
                                'Each step links to the matching training slide',
                            ],
                        ],
                        [
                            'icon' => 'view_list',
                            'title' => 'Workflow Stuck Items',
                            'text' => 'Team queue — leads with pending workflow steps.',
                            'color' => 'provider',
                            'points' => [
                                'Process Guides → Workflow Stuck Items button',
                                'Unknown + Customer leads with overdue follow-ups',
                                'Open lead → complete next checkbox step',
                            ],
                        ],
                        [
                            'icon' => 'handshake',
                            'title' => 'Provider onboarding checklist',
                            'text' => 'Provider leads also show workflow steps while open.',
                            'color' => 'future',
                            'points' => [
                                'Brief call → agreement WA → docs → final call → panel',
                                'See Provider onboarding slide for full detail',
                                'Tick steps as you complete each onboarding touch',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Hard vs soft gates on leads',
                    'layout' => 'row-2',
                    'cards' => [
                        [
                            'icon' => 'block',
                            'title' => 'Hard gate — blocked',
                            'text' => 'Cannot proceed until step is done.',
                            'color' => 'invalid',
                            'points' => [
                                'Mark as Customer/Provider/Invalid from Unknown without outbound call logged (Add Call Log or Add follow-up → Call)',
                                'Create Booking without call + Path A/B noted in remarks',
                                'Mark customer Booked/Completed without booking linked',
                            ],
                        ],
                        [
                            'icon' => 'warning',
                            'title' => 'Soft gate — confirm',
                            'text' => 'Create Booking may warn if ₹100, provider group, or panel WA skipped.',
                            'color' => 'unknown',
                            'points' => [
                                'Confirm only if you actually did the step',
                                'Better: tick checkbox on FAB instead of skipping',
                                'Hard gates cannot be bypassed — fix the step first',
                            ],
                        ],
                    ],
                ],
            ],
            'path_steps' => [
                [
                    'label' => 'Unknown lead (from workflow)',
                    'steps' => self::workflowStepsForScenario('lead.unknown'),
                ],
                self::workflowTrainingGroup('lead.customer.path_a', 'Customer Path A (from workflow)'),
                self::workflowTrainingGroup('lead.customer.path_b', 'Customer Path B (from workflow)'),
                [
                    'label' => 'Provider onboarding (from workflow)',
                    'steps' => self::workflowStepsForScenario('lead.provider.onboarding'),
                ],
            ],
            'remember' => [
                'FAB steps = same order as this training',
                'Stuck Items = leads where you promised action but checklist is behind',
                'Create Booking button respects gates — read the modal before confirming skip',
            ],
            'avoid' => [
                'Bypassing hard gates by editing lead in wrong place',
                'Confirming soft gates for steps you did not finish',
                'Ignoring FAB on Provider leads during onboarding',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideHandlingUnknowns(): array
    {
        return [
            'id' => 'handling-unknowns',
            'title' => 'Unknown — qualify & follow up',
            'subtitle' => 'Section 3 — call, classify, document',
            'type' => 'visual',
            'qualifier' => [
                'title' => 'Lead qualifier — ask on every Unknown call until you know the type',
                'items' => [
                    ['question' => 'Do you need a home service (plumber, electrician, cleaning, repair)?', 'type' => 'Customer', 'note' => 'Collect service, problem, full address, date/time on the same call'],
                    ['question' => 'Do you want to join Panun Kaergar as a service partner?', 'type' => 'Provider', 'note' => 'Collect trade, area, phone — send to onboarding slides'],
                    ['question' => 'No service needed now — just saving our number or need later?', 'type' => 'Future customer', 'note' => 'Confirm reason, explain our services, warm close'],
                    ['question' => 'Request we cannot help with (wrong service or outside our area)?', 'type' => 'Invalid', 'note' => 'Write exact request + location in remarks, polite close'],
                ],
            ],
            'path_steps' => [
                [
                    'label' => 'Path A — User answers the call',
                    'steps' => [
                        self::trainingStep(
                            'Before dialling — open the Unknown lead in panel',
                            'Confirm phone number matches the enquiry. Read source (Facebook, missed call, WhatsApp, etc.) and what they wrote — even if it was only “hi”.',
                            'Phone number, Source, original message or call note, Handled By = you',
                            'Remarks before call: "Instagram DM — wrote \'Hi I called you\' — no service mentioned. Outbound today."',
                            'Dial the customer. Do not change type in panel yet.',
                        ),
                        self::trainingStep(
                            'Start the call — introduce Panun Kaergar and ask why they contacted us',
                            'Keep it short and friendly. Your job on this call is to find out what they want — not to book or onboard until you know the type.',
                            'Name (if not known), reason they messaged/called, which service or help they need',
                            '"Assalam alaikum, this is [your name] from Panun Kaergar home services in Kashmir. You contacted us — may I know what help you need?"',
                            'Listen and take notes on paper. Run the lead qualifier (box above).',
                        ),
                        self::trainingStep(
                            'Run the lead qualifier — pick exactly ONE type before you hang up',
                            'Ask the four qualifier questions in order. Stop as soon as you know the type. Unknown is only allowed until this call ends successfully.',
                            'Which path applies: Customer / Provider / Future customer / Invalid',
                            'Customer: "Kitchen tap is leaking." Provider: "I am an electrician — want to work with you." Future: "Just saving your number for renovation in 3 months." Invalid: "Need car AC repair in Jammu."',
                            'Collect the details for that type (Steps 4–7 below) on the same call.',
                        ),
                        self::trainingStep(
                            'If Customer — collect full booking details on the same call',
                            'Do not post in provider group until you have problem, address, and timing. Decide Path A (direct book) or Path B (talk to provider first).',
                            'Service type, exact problem, complete address with landmark, preferred date & time, urgency (emergency?)',
                            '"Plumbing — kitchen tap leak, House 12 Rajbagh near Jamia Masjid, tomorrow 10 AM, not emergency. Wants direct booking (Path A)."',
                            'Note Path A or B in your paper notes. After call → Customer slide.',
                        ),
                        self::trainingStep(
                            'If Provider — collect onboarding basics on the same call',
                            'They want to receive jobs from Panun Kaergar — not book a plumber for their home.',
                            'Full name, trade/service type, area they cover, phone, can they talk now or schedule callback',
                            '"Electrician, Srinagar south, wants partner registration — available for brief onboarding call now."',
                            'After call → Provider onboarding slide.',
                        ),
                        self::trainingStep(
                            'If Future customer or Invalid — confirm and close the right way on the call',
                            'Future = valid close with relationship kept. Invalid = polite no with reason documented — never rude.',
                            'Future: why no service now (dropdown reason). Invalid: exact request + why Panun Kaergar cannot help.',
                            'Future remarks: "Saving number — renovation in Oct, explained services, asked to save 8899881555." Invalid: "Car repair request — we do home services only."',
                            'After call → Future customer or Invalid slide.',
                        ),
                        self::trainingStep(
                            'Call ends — update panel immediately from your notes (before next lead)',
                            'Do not type in panel during the call. Update right after hang up: type, full remarks, urgency, Handled By, Followup On if still open.',
                            'Type (one only), remarks with everything said, next action, Followup On date if not closed',
                            'Type: Customer. Remarks: "Outbound qualify — plumbing kitchen tap Rajbagh, tomorrow 10 AM, Path A. WA sent. Posting to group." Followup On: today if waiting on provider.',
                            'Send WhatsApp same minute (Step 8), then open the handling slide for the new type.',
                        ),
                        self::trainingStep(
                            'WhatsApp the customer — same minute as panel update',
                            'Mandatory after every call. They should never wonder if Panun Kaergar is working on their enquiry.',
                            'If they answered: summary of what was discussed + next step. If no pickup: missed-call template.',
                            'Answered: "As per our call — kitchen tap leak, Rajbagh, tomorrow 10 AM. We are finding a partner for that slot." No pickup: use missed-call template below.',
                            'Open the matching handling slide — Customer, Provider, Future, or Invalid.',
                        ),
                    ],
                ],
                [
                    'label' => 'Path B — User does NOT pick up (max 3 attempts)',
                    'steps' => [
                        self::trainingStep(
                            'Attempt 1 — same day: send WhatsApp + Add follow-up in panel',
                            'Do not guess the type. Keep Unknown until they answer on any attempt. Add follow-up → Taken → Call → remarks with attempt #.',
                            'Date/time of call attempt, WhatsApp sent (yes/no), follow-up row logged',
                            'Remarks: "Attempt 1/3 — outbound no pickup 1 Aug 11:00. WA missed-call template sent." Followup On: next working day.',
                            'Attempt 2 on Followup On date.',
                        ),
                        self::trainingStep(
                            'Attempt 2 — on Followup On: call again',
                            'If they pick up → run full qualify flow (Path A above) on that call and reclassify immediately.',
                            'Attempt 2 date/time, outcome (pickup / no pickup), WA sent if no pickup',
                            'No pickup: "Attempt 2/3 — called 2 Aug 10:30, no answer. WA sent again." New Followup On: next day.',
                            'Attempt 3 on next Followup On — or qualify if they answered.',
                        ),
                        self::trainingStep(
                            'Attempt 3 — final call on Followup On date',
                            'Last attempt. If they pick up → qualify and reclassify. If not → prepare to close.',
                            'Attempt 3 date/time, final WA sent, all three dates listed',
                            'No pickup: "Attempt 3/3 — final call 3 Aug 11:00, no answer. WA sent. Closing No Response."',
                            'All 3 failed → close lead. Any pickup → Path A qualify flow.',
                        ),
                        self::trainingStep(
                            'All 3 attempts failed — close depends on what you knew',
                            'Vague Unknown (only "hi" / missed call) → Mark as Invalid → Did not Know About Enquiry. If DM/form already shows clear customer need but no pickup → Mark as Customer first, fill details from message, then Change Status → Cancel → No Response From Customer.',
                            'Which path: vague Invalid vs documented Customer cancel',
                            'Vague: Invalid → Did not Know About Enquiry. DM "need electrician Bemina": Mark as Customer with form details → Cancel → No Response From Customer after 3 attempts.',
                            'Each attempt logged as Add follow-up → Taken.',
                        ),
                    ],
                ],
                [
                    'label' => 'Path C — DM/form shows service details but no pickup (3 attempts)',
                    'steps' => [
                        self::trainingStep(
                            'Recognize documented customer need without a successful call',
                            'Instagram DM: "Need plumber — kitchen sink blocked, Rajbagh, tomorrow" — you have service + area + timing from text even though calls failed.',
                            'Copy details from source message into notes',
                            'Do not leave as Unknown after 3 attempts when customer need is documented.',
                            'Mark as Customer using DM details — not Invalid.',
                        ),
                        self::trainingStep(
                            'Mark as Customer Lead — fill modal from DM/form',
                            'Hero → Mark as Customer → enter zone, area, category, service, estimated date from message. Status Pending. Remarks cite DM text + all call attempts.',
                            'Path unknown — do not post to provider group without speaking to customer',
                            'Remarks: "Web form + 3 call attempts no pickup. DM: sink blocked Rajbagh tomorrow. No Response close."',
                            'Add follow-up rows for all 3 attempts + WA each day.',
                        ),
                        self::trainingStep(
                            'Change Status → Cancel → No Response From Customer',
                            'Status chip → edit → Cancel status → reason No Response From Customer + remarks listing attempts.',
                            'Not Invalid — customer need was real, contact failed',
                            null,
                            'Closed lead with full audit trail in Follow-ups tab + remarks.',
                        ),
                    ],
                ],
            ],
            'scenarios' => [
                [
                    'title' => 'Vague Instagram DM → qualifies as Customer',
                    'trigger' => 'DM: "Hi, I called you earlier" — no service mentioned. Lead type Unknown.',
                    'action' => 'Outbound call: "What service do you need?" → Customer: bathroom geyser not heating. Collect address (Hazratbal), date (today PM). Path A. After call: panel Customer + full remarks + WhatsApp summary → provider group.',
                    'panel' => 'Before: Unknown, source Instagram. After: Customer, remarks with problem + address + time + Path A, Followup On until booked.',
                ],
                [
                    'title' => 'Missed call → three no-pickups → close',
                    'trigger' => 'Missed call lead created. You call three days — no answer any time.',
                    'action' => 'Day 1: WA missed-call template + Attempt 1/3. Day 2: call + Attempt 2/3 + WA. Day 3: final call + Attempt 3/3 + WA. List every date in remarks.',
                    'panel' => 'Type stays Unknown until answer. After Attempt 3: Mark as Invalid, reason Did not Know About Enquiry, all attempt dates in remarks + follow-ups tab.',
                ],
                [
                    'title' => 'Web DM with full job details — 3 no-pickups',
                    'trigger' => 'Instagram DM: "Need electrician — MCB tripping, Bemina, today 6 PM." Three outbound calls — no answer.',
                    'action' => 'Attempt 1–3: Add follow-up each + WA. Mark as Customer with DM details in modal. No provider group — never spoke to them. Change Status → Cancel → No Response From Customer.',
                    'panel' => 'Customer type with form/DM details in remarks, 3 follow-up rows, Cancelled with No Response reason — not Invalid.',
                ],
                [
                    'title' => 'WhatsApp "want to join as partner" → Provider',
                    'trigger' => 'WA message: "I am a plumber, want to register with Panun Kaergar." Lead Unknown until you confirm on call.',
                    'action' => 'Call: confirm trade, area, documents readiness. Reclassify Provider on same call. WhatsApp agreement + doc list after call. Follow onboarding slides.',
                    'panel' => 'After call: Provider, remarks "Plumber, Budgam — onboarding call done, docs WA sent", Followup On for doc deadline.',
                ],
            ],
            'flowcharts' => [
                ['key' => 'unknown-call', 'title' => 'Flow — user answers'],
                ['key' => 'unknown-no-pickup', 'title' => 'Flow — no pickup (3 attempts)'],
            ],
            'message' => self::waMissedCall(),
            'remember' => [
                'Unknown is temporary — always reclassify on a successful call',
                'Notes on paper during call → panel + WhatsApp immediately after',
                'Never post to provider group until Customer details are complete',
            ],
            'avoid' => [
                'Guessing Customer because they messaged — ask on the call',
                'Updating panel while still on the phone',
                'Leaving Unknown after they answered',
                'More than 3 no-pickup attempts without manager approval',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideHandlingCustomers(): array
    {
        return [
            'id' => 'handling-customers',
            'title' => 'Customer — first call to booking',
            'subtitle' => 'Section 4 — collect details, pick path, complete booking',
            'type' => 'visual',
            'qualifier' => [
                'title' => 'Path choice — decide on the first call (before provider group post)',
                'items' => [
                    ['question' => 'Customer is ready to book — knows service, address, time, happy to proceed without talking to provider first?', 'type' => 'Path A — Direct booking', 'note' => 'Post full job to provider group → 10 min SLA → ₹100 → Create Booking'],
                    ['question' => 'Customer wants price, scope, or wants to speak to the provider before paying or booking?', 'type' => 'Path B — Discussion first', 'note' => 'Group post for discussion → conference call → then book, follow up, or cancel'],
                ],
            ],
            'path_steps' => [
                [
                    'label' => 'Step 1 — First call (user answers)',
                    'steps' => [
                        self::trainingStep(
                            'Before dialling — open Customer lead and read what you already know',
                            'Check source (website, DM, call), partial details from the form or message, and urgency. If the contact number is missing in panel, check the linked lead and booking row first.',
                            'Source, service hint, contact number, address/date if already known',
                            'Web booking: "Electrician — MCB tripping, Bemina" — number on linked lead → confirm full address and 6 PM slot on call.',
                            'No number anywhere → get contact same day or escalate. Otherwise dial — notes on paper only during call.',
                        ),
                        self::trainingStep(
                            'Confirm they need a home service and collect full job details',
                            'Providers and the panel both need enough detail to act. Vague jobs get ignored in the group.',
                            'Service type, exact problem, complete address with landmark, preferred date & time, emergency? (yes/no)',
                            '"Plumbing — kitchen tap leaking badly, House 12 Rajbagh near Jamia Masjid, tomorrow 10 AM, not emergency."',
                            'Ask Path A vs B question (qualifier box above).',
                        ),
                        self::trainingStep(
                            'Decide Path A or Path B on the same call — write it in your notes',
                            'Wrong path wastes provider and customer time. Path A = ready to book. Path B = wants discussion first.',
                            'Path choice, customer expectation (direct book vs talk to provider), any price concern noted',
                            'Path A: "Book directly — just send someone." Path B: "What will the plumber charge? I want to talk to him first."',
                            'Call ends → panel update immediately (Step 4).',
                        ),
                        self::trainingStep(
                            'Call ends — update panel before anything else (group post comes after)',
                            'Type Customer, full remarks, urgency if emergency, Handled By, Followup On if waiting on provider.',
                            'Type, remarks with problem + address + time + Path A/B, urgency, Followup On',
                            'Remarks: "Customer — electrical MCB tripping, Bemina House 4 Lane 2, today 6 PM, Path A. WA sent. Posting to group."',
                            'WhatsApp customer same minute (Step 5), then Path A or Path B steps below.',
                        ),
                        self::trainingStep(
                            'WhatsApp customer — summary of call + what happens next',
                            'Mandatory. Customer must know you are working on their request.',
                            'Service, address, date/time, next step (finding partner / discussion call)',
                            '"As per our call — MCB tripping, Bemina, today 6 PM. We are finding a partner available for that time."',
                            'Do not post in provider group until panel + WhatsApp are done.',
                        ),
                    ],
                ],
                [
                    'label' => 'Step 2 — No pickup on first call (max 3 attempts)',
                    'steps' => [
                        self::trainingStep(
                            'Attempt 1 — same day: WhatsApp + document in panel',
                            'Do not post in provider group — you have not spoken to the customer yet. Keep type Customer if already set from DM.',
                            'Attempt date/time, service hint from DM if any, WA sent',
                            'Remarks: "Attempt 1/3 — customer no pickup 1 Aug 14:00. WA: Please share address and time or call 8899881555." Followup On: next day.',
                            'Attempt 2 on Followup On.',
                        ),
                        self::trainingStep(
                            'Attempt 2 — call on Followup On date',
                            'If they answer → run Step 1 first-call flow fully, then Path A or B.',
                            'Attempt 2 outcome, WA if no pickup',
                            'No pickup: "Attempt 2/3 — 2 Aug 10:00, no answer. WA sent again." New Followup On.',
                            'Attempt 3 or qualify if they answered.',
                        ),
                        self::trainingStep(
                            'Attempt 3 — final call; close if all fail',
                            'Same 3-attempt rule as Unknown. List every date in remarks.',
                            'All three attempt dates, WA each time, close reason',
                            'After Attempt 3 no pickup: "Attempt 1: 1 Aug WA. Attempt 2: 2 Aug no pickup. Attempt 3: 3 Aug no pickup. Change Status → Cancel → No Response From Customer."',
                            'If they answered any attempt → Step 1 → Path A or B.',
                        ),
                    ],
                ],
                [
                    'label' => 'Panel — AI booking banner (when shown)',
                    'steps' => [
                        self::trainingStep(
                            'Lead page shows Continue with AI booking',
                            'Customer chatted on WhatsApp AI — draft booking linked. Read banner status + View AI chat first.',
                            'Confirm phone, service, address, time with customer if gaps in AI transcript',
                            null,
                            'Continue with AI booking → complete human review in booking flow.',
                        ),
                        self::trainingStep(
                            'After AI booking path',
                            'Update remarks, Handled By, tags if HOT. Do not also Create Booking manually if AI booking already linked.',
                            'Booking ID on hero when complete',
                            null,
                            'If AI path stuck → take over chat, fix lead fields, use Create Booking manually instead.',
                        ),
                    ],
                ],
                [
                    'label' => 'Path A — Direct booking (panel workflow steps)',
                    'steps' => WorkflowStepDefinitions::trainingPathSteps('lead.customer.path_a')[0]['steps'] ?? [],
                ],
                [
                    'label' => 'Path A — Direct booking',
                    'steps' => [
                        self::trainingStep(
                            'Post in provider WhatsApp group — use the standard format (Lead ID on top)',
                            '10-minute SLA starts when message is sent. Always include Lead ID (# from panel), service, problem, address, and timing — use the template below.',
                            'Lead ID, service type, exact problem, full address, date & time',
                            'See Provider group — Path A accordion below (e.g. #2425, washing machine, Rawalpora, 01 August 7 PM).',
                            'Start 10-minute timer. WhatsApp customer if search takes time.',
                        ),
                        self::trainingStep(
                            'Provider replied ready within 10 minutes?',
                            'If yes → collect ₹100 from customer before Create Booking. If no → call providers yourself — do not go silent.',
                            'Provider name, phone, confirmed slot, ₹100 collected (yes/no)',
                            'Provider: "YES — Adil, available 10 AM." → call customer confirm → collect ₹100 → Create Booking.',
                            'Booking confirmation WA to customer + provider, or chase alternate providers.',
                        ),
                        self::trainingStep(
                            'No reply in 10 min — call nearby providers + keep customer updated',
                            'WhatsApp customer: "Still checking availability for your slot — update shortly."',
                            'Providers called, alternate slots offered, customer choice',
                            'Provider offers 2 PM instead of 10 AM → call customer → share slot → book or Followup On.',
                            'Nobody available → honest WA + follow-up or Change Status → Cancel with reason.',
                        ),
                        self::trainingStep(
                            'Create Booking in panel + close the loop',
                            'Sidebar → Create Booking for this Lead (Pending status, ₹100 collected). Form opens pre-filled — confirm provider, slot, address → Save. Booking ID appears in hero.',
                            'Booking ID, provider name, date/time in remarks + confirmation WA both sides',
                            'Return from booking → success alert → hero Booking ID link → status Booked on pipeline',
                            'Optional Followup On service day → then Change Status → Completed after job done.',
                        ),
                    ],
                ],
                [
                    'label' => 'Path B — Discussion first (panel workflow steps)',
                    'steps' => WorkflowStepDefinitions::trainingPathSteps('lead.customer.path_b')[0]['steps'] ?? [],
                ],
                [
                    'label' => 'Path B — Discussion first, then book',
                    'steps' => [
                        self::trainingStep(
                            'WhatsApp customer — set expectation for discussion call',
                            'Not immediate booking — short call with provider to discuss scope/price.',
                            'Service, address, preferred discussion time',
                            '"We will connect you with a provider for a short discussion about your kitchen plumbing job — not booking yet until you agree."',
                            'Post to provider group for discussion availability.',
                        ),
                        self::trainingStep(
                            'Post to provider group — Path B discussion format (Lead ID on top)',
                            'Same structure as Path A but title "Discussion Request" and note that customer wants to talk before booking.',
                            'Lead ID, service, problem, address, discussion time window',
                            'See Provider group — Path B accordion below (e.g. #2425, kitchen plumbing, Rajbagh, discussion 5 PM).',
                            'Provider ready → brief customer pre-call → conference call.',
                        ),
                        self::trainingStep(
                            'Conference call — stay involved; do not drop the customer',
                            'You coordinate. Customer decides after speaking to provider.',
                            'Outcome: wants service / will decide later / denies service; price concern if any',
                            'Customer agrees after call → ₹100 → Create Booking same as Path A.',
                            'Will decide later → panel concern + Followup On + WA summary.',
                        ),
                        self::trainingStep(
                            'After conference — book, follow up, or cancel politely',
                            'Same ₹100 + Create Booking rules once customer commits.',
                            'Final outcome, reason if cancelled, next Followup On if pending',
                            'Price too high → note in remarks, offer alternate provider on follow-up, WA customer honestly.',
                            'Booking confirmed ✓ OR Change Status → Cancel with clear reason and remarks.',
                        ),
                    ],
                ],
            ],
            'scenarios' => [
                [
                    'title' => 'Clear customer — Path A direct book',
                    'trigger' => 'Instagram DM: "Need electrician — MCB tripping, Bemina, today evening." Customer confirms on call — book directly.',
                    'action' => 'Call → confirm full address + 6 PM → Path A → panel + WA → group post with full details → provider YES in 8 min → ₹100 → Create Booking → confirmation WA both sides.',
                    'panel' => 'Customer, remarks with problem + address + time + Path A + provider name, booking ID, closed/complete.',
                ],
                [
                    'title' => 'Customer wants price first — Path B',
                    'trigger' => 'On call: "How much for bathroom plumbing? I want to talk to the plumber before booking."',
                    'action' => 'Path B → WA customer → group "discussion call" post → provider ready → brief customer → conference → customer agrees → ₹100 → Create Booking.',
                    'panel' => 'Remarks "Path B — price discussion, Rajbagh bathroom plumbing." After conference: outcome + booking ID or Followup On with concern noted.',
                ],
                [
                    'title' => 'Customer no pickup — close after 3 attempts',
                    'trigger' => 'Web booking lead — you call 3 days, no answer. Service known from form: cleaning, Srinagar.',
                    'action' => 'Day 1: WA with service reference + Attempt 1/3. Day 2: call + Attempt 2/3 + WA. Day 3: final + Attempt 3/3. No group post — never spoke to customer.',
                    'panel' => 'Customer type, all attempt dates in remarks + follow-ups, Change Status → Cancel → No Response From Customer.',
                ],
            ],
            'flowcharts' => [
                ['key' => 'customer-booking', 'title' => 'Flow — first call & path choice'],
                ['key' => 'customer-no-pickup', 'title' => 'Flow — no pickup (3 attempts)'],
                ['key' => 'direct-booking', 'title' => 'Flow — Path A direct booking'],
                ['key' => 'discussion-booking', 'title' => 'Flow — Path B discussion first'],
            ],
            'messages' => [
                self::wa(
                    'customer call',
                    'As per our discussion over call you need this service — Service details, address, date/time. We will look for a partner available to do the work for that time.',
                    'As per our discussion over call you need this service — kitchen tap leak, Rajbagh, tomorrow 10 AM. We will look for a partner available to do the work for that time.',
                ),
                self::providerGroupPathA(),
                self::providerGroupPathB(),
            ],
            'remember' => [
                'Provider group: Lead ID on top, then service + problem + address + timing (use template)',
                'Path A vs B decided on the first call — write it in remarks',
                '₹100 before Create Booking; 10-minute rule on group replies',
                'WhatsApp customer after every call touch — never go silent',
            ],
            'avoid' => [
                'Posting vague one-liners instead of the standard provider group format',
                'Provider group post before speaking to customer (no-pickup leads)',
                'Create Booking before customer confirms (especially Path B)',
                'Dropping customer alone on provider conference call',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideHandlingProviders(): array
    {
        return [
            'id' => 'handling-providers',
            'title' => 'Provider — onboarding',
            'subtitle' => 'Section 5 — register a new service partner',
            'type' => 'visual',
            'panel_links' => [
                self::panelLink('Add New Provider', '/admin/provider/create'),
                self::panelLink('Providers list', '/admin/provider/list?status=all'),
            ],
            'qualifier' => [
                'title' => 'Provider onboarding — four steps to Provider registered ✓',
                'items' => [
                    ['question' => 'Step 1 — Brief call: explain Panun Kaergar, commission, trade, area, document deadline', 'type' => 'After first contact', 'note' => 'They must understand the model before you send documents'],
                    ['question' => 'Step 2 — WhatsApp agreement + document list + submit-by date', 'type' => 'Same day as Step 1', 'note' => 'ID, skill proof, bank details — use template below'],
                    ['question' => 'Step 3 — Final call: job flow, provider group, 10-minute reply rule, payment', 'type' => 'After docs received', 'note' => 'Only when documents are in — not before'],
                    ['question' => 'Step 4 — Providers → Add New Provider (/admin/provider/create) + WhatsApp group', 'type' => 'End of onboarding', 'note' => 'Create provider record in Provider admin — not on the lead page. Phone must match WhatsApp.'],
                ],
            ],
            'path_steps' => [
                [
                    'label' => 'First contact — user answers',
                    'steps' => [
                        self::trainingStep(
                            'Open Provider lead — confirm trade, area, and contact details',
                            'They want to receive jobs from Panun Kaergar — not book a plumber for their home. Check source: website partner form, call, or WhatsApp.',
                            'Name, trade/service type, area covered, contact number, source',
                            'Web Provider Request: "Electrician, Srinagar south" — open linked lead before calling.',
                            'Step 1 brief onboarding call — or schedule if busy.',
                        ),
                        self::trainingStep(
                            'Step 1 — Brief onboarding call',
                            'Explain how Panun Kaergar works, commission, which services/areas we need, and when documents are due.',
                            'Trade, experience, areas they cover, availability, document deadline agreed',
                            '"Panun Kaergar connects home service partners with customers. You reply YES in our provider group within 10 minutes when a job matches your trade and area. Commission is X%. Can you send documents by 10 August?"',
                            'Call ends → panel update + WhatsApp agreement (Step 2).',
                        ),
                        self::trainingStep(
                            'Call ends — update panel + send Step 2 WhatsApp same day',
                            'Mark as Provider or Edit provider card: district, zones, categories, address, status Pending. Provider Checklist → Edit → tick "Agreement sent" when WA goes out.',
                            'Remarks: trade, area, call summary, docs deadline, agreement WA sent, checklist updated',
                            'Remarks: "Provider — electrician, Budgam. Step 1 call done 1 Aug. Agreement + doc list WA sent. Followup 10 Aug for docs."',
                            'Wait for documents — follow up if deadline passes.',
                        ),
                    ],
                ],
                [
                    'label' => 'No pickup or busy — max 3 attempts to reach',
                    'steps' => [
                        self::trainingStep(
                            'Attempt 1 — same day: WhatsApp + panel note',
                            'Same 3-attempt rule as Customer/Unknown. Keep type Provider.',
                            'Attempt date/time, WA sent',
                            '"Attempt 1/3 — provider onboarding call, no pickup. WA sent with callback request." Followup On: next day.',
                            'Attempt 2 on Followup On.',
                        ),
                        self::trainingStep(
                            'Attempt 2 & 3 — call on Followup On dates',
                            'If they answer → run Step 1 brief call fully. If all 3 fail → close with notes.',
                            'Each attempt documented, WA after each touch',
                            'After Attempt 3: "All 3 attempts failed — Change Status → Cancel → Not Intrested."',
                            'Answer on any attempt → Step 1 flow. All failed → close lead.',
                        ),
                    ],
                ],
                [
                    'label' => 'Step 2 — Documents via WhatsApp',
                    'steps' => [
                        self::trainingStep(
                            'Send agreement + required document list with submit-by date',
                            'Use the Provider agreement template below. Clear deadline — not open-ended.',
                            'Agreement sent, doc list, deadline date, Followup On on deadline',
                            'WA sent 1 Aug — deadline 10 Aug. Followup On: 10 Aug to check if docs received.',
                            'On deadline → check WhatsApp for documents.',
                        ),
                        self::trainingStep(
                            'Documents not received by deadline — follow up (max 3 total on docs)',
                            'WhatsApp reminder → panel note each follow-up. After 3 doc follow-ups with no docs → cancel.',
                            'Follow-up dates, what was sent, provider response',
                            '"Attempt 2/3 on docs — reminder WA 10 Aug. No docs yet. New Followup 12 Aug."',
                            'Docs received → Step 3 final call. Still nothing → Change Status → Cancel with reason + full remarks.',
                        ),
                    ],
                ],
                [
                    'label' => 'Step 3 & 4 — Final call, panel, and WhatsApp group',
                    'steps' => [
                        self::trainingStep(
                            'Step 3 — Final call after documents received',
                            'Explain: jobs arrive in provider group, 10-minute YES reply rule, payment flow, professional conduct.',
                            'Provider understands group rules, payment, how jobs are assigned',
                            '"When we post a job matching your trade and area, reply YES + your name within 10 minutes. Payment is after job completion per our agreement."',
                            'Step 4 — add to panel and correct WhatsApp group.',
                        ),
                        self::trainingStep(
                            'Step 4 — Add provider in Provider admin (not the lead page)',
                            'Leads and bookings → Providers → Add New Provider (/admin/provider/create). Complete the wizard: owner name, phone (must match WhatsApp), trade, service zones/categories, commission. Save — then verify Is Added in Panel link on the lead matches.',
                            'Provider record created in Provider module, phone matches lead, zones/categories set',
                            'Providers → Add New Provider → "Adil Electrician" + 99XX + Srinagar south zones → saved → lead shows panel match link',
                            'Add to correct trade/area WhatsApp group → tick checklist items → Change Status to registered/completed.',
                        ),
                    ],
                ],
            ],
            'scenarios' => [
                [
                    'title' => 'Website partner application — full onboarding',
                    'trigger' => 'Web Provider Request: electrician, Budgam, phone on form. Applicant answers first call.',
                    'action' => 'Step 1 call → agreement WA → docs by 10 Aug → docs received 8 Aug → Step 3 final call → add panel + electrician group → Provider registered ✓.',
                    'panel' => 'Full remarks at each step; Followup On on doc deadline; final status Provider registered ✓.',
                ],
                [
                    'title' => 'Provider busy — scheduled callback',
                    'trigger' => 'WhatsApp: "Want to join as plumber" — on call says busy, call tomorrow.',
                    'action' => 'Schedule Followup On tomorrow → Attempt 1 call → Step 1 brief call → agreement WA same day.',
                    'panel' => 'Provider, remarks "Callback scheduled 2 Aug 11 AM — onboarding call", Followup On set.',
                ],
                [
                    'title' => 'Documents never sent — close after follow-ups',
                    'trigger' => 'Step 1 done, agreement sent, deadline passed, 3 doc reminders — no documents.',
                    'action' => 'Document each reminder in remarks → Change Status → Cancel — documents not received.',
                    'panel' => 'All follow-up dates listed, agreement WA dates noted, closed with clear reason.',
                ],
            ],
            'flowcharts' => [['key' => 'provider-onboarding', 'title' => 'Flow — provider onboarding']],
            'messages' => [self::waProviderAgreement()],
            'remember' => [
                'Provider ≠ customer — they receive jobs, not book one',
                'Step 3 only after documents received — never add to group before final call',
                'Max 3 attempts to reach + max 3 doc follow-ups',
                'Panel name/number must match WhatsApp group',
            ],
            'avoid' => [
                'Adding to provider group before Step 3 final call',
                'Skipping agreement or document deadline in writing',
                'Customer booking flow for a partner application',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideHandlingFutureCustomers(): array
    {
        return [
            'id' => 'handling-future-customers',
            'title' => 'Future customer — nurture & close',
            'subtitle' => 'Section 6 — valid close, relationship kept',
            'type' => 'visual',
            'panel_links' => [
                self::panelLink('Outbound enquiries', '/admin/lead/outbound-enquiry'),
            ],
            'qualifier' => [
                'title' => 'When to use Future customer (not Invalid, not Customer)',
                'items' => [
                    ['question' => 'Saving our number for later — no service needed today?', 'type' => 'Future customer', 'note' => 'Pick reason: saving number'],
                    ['question' => 'Renovation or need expected in weeks/months?', 'type' => 'Future customer', 'note' => 'Pick reason: renovation later / no immediate need'],
                    ['question' => 'Just moved — wants to know what we do but not booking now?', 'type' => 'Future customer', 'note' => 'Explain services, warm close'],
                    ['question' => 'Wrong service or outside area?', 'type' => 'Invalid — not Future', 'note' => 'Future is for valid contacts with no need today only'],
                ],
            ],
            'path_steps' => [
                [
                    'label' => 'On the call — qualify, inform, close warmly',
                    'steps' => [
                        self::trainingStep(
                            'Confirm why they contacted us and why they are not booking today',
                            'You need a clear reason for the panel dropdown — same words you will select in panel.',
                            'Reason for contact, why not booking now, any future timeframe mentioned',
                            '"Just moved to Srinagar — saving your number for when we need plumbing after renovation in 3 months."',
                            'Explain Panun Kaergar services on the same call.',
                        ),
                        self::trainingStep(
                            'Tell the customer what Panun Kaergar offers',
                            'WHO: the customer. WHAT: plumbing, electrical, cleaning, repairs, etc. WHY: so they call us later, not a competitor.',
                            'Services explained (at least 2–3 examples), coverage area mentioned',
                            '"We do plumbing, electrical, cleaning, and home repairs across Srinagar — call 8899881555 anytime you need help."',
                            'Ask for referrals — anyone needing service right now?',
                        ),
                        self::trainingStep(
                            'Ask for referrals — capture in remarks if given',
                            'A referral can become a Customer lead today — name and number if they give one.',
                            'Referral name, number, service need (if any)',
                            'Customer mentions neighbour needs electrician → note in remarks: "Referral: Ahmad 99XX — electrical, Rajbagh."',
                            'Warm close on call → then panel + WhatsApp.',
                        ),
                        self::trainingStep(
                            'Call ends — panel update immediately',
                            'Type Future customer + pick reason from dropdown (required). Full remarks.',
                            'Type, dropdown reason, remarks with call summary, referral if any',
                            'Future customer ✓, reason "Renovation later", remarks "Moved to Srinagar, renovation Oct, explained services, referral none."',
                            'Send warm-close WhatsApp same minute — template below.',
                        ),
                        self::trainingStep(
                            'WhatsApp — thank them and ask to save 8899881555',
                            'Professional close — they should feel welcome to call later. This is a success, not a failure.',
                            'WA sent confirming services and our number',
                            'See Future customer WhatsApp template below.',
                            'Lead closed Future customer ✓ — no Followup On unless they asked callback.',
                        ),
                    ],
                ],
                [
                    'label' => 'Later — log outbound contact on Future customer lead',
                    'steps' => [
                        self::trainingStep(
                            'When to Add Outbound Enquiry',
                            'Future customer is closed — but if you call or message them weeks later (follow-up campaign, they replied, referral check-in), log each touch separately.',
                            'Future customer lead still in panel, outbound count on hero badge',
                            'Renovation month arrived — you call to ask if they need plumbing → log outbound even though lead was closed as Future.',
                            'Open Future customer lead → Add Outbound Enquiry.',
                        ),
                        self::trainingStep(
                            'Fill outbound enquiry form',
                            'Customer name, phone, Contacted through (call or message), Status (from dropdown), datetime, remarks. Some statuses require linking a Lead ID or Booking ID — pick per status config.',
                            'What you said, outcome, next action if any',
                            'Called 1 Oct — still not ready, saving for November. Status: No booking yet. Remarks: "Follow-up call, still renovating."',
                            'Hero outbound count increases. Standalone list: Leads → Outbound Enquiries.',
                        ),
                        self::trainingStep(
                            'Referral from Future call → separate Customer lead',
                            'If they give a name/number for someone who needs service today, create or open a Customer lead for that person — do not mix into Future lead remarks only.',
                            'Referral name, phone, service need in both leads\' remarks',
                            'Caller Future closed; brother needs electrician → new Customer lead for brother.',
                            'Future lead stays closed; new lead gets full Customer flow.',
                        ),
                    ],
                ],
            ],
            'scenarios' => [
                [
                    'title' => 'Saving number after vague enquiry',
                    'trigger' => 'Unknown call: "I saw your ad — just saving your number for later." No service need now.',
                    'action' => 'Explain services → Future customer → reason "Saving number" → warm WA → close.',
                    'panel' => 'Future customer ✓, dropdown reason matches remarks, WA sent same day.',
                ],
                [
                    'title' => 'Renovation in 3 months — referral captured',
                    'trigger' => 'Call: renovation in October, no booking now. Mentions brother needs plumber this week.',
                    'action' => 'Future close for caller → create/link Customer lead for brother if number given → separate handling.',
                    'panel' => 'Future customer ✓ for main contact; referral noted; brother lead as Customer if pursued.',
                ],
            ],
            'flowcharts' => [['key' => 'future-customer', 'title' => 'Flow — Future customer']],
            'messages' => [self::waFutureClose()],
            'remember' => [
                'Future customer ✓ is a valid success — not Invalid, not Cancelled',
                'Dropdown reason is required — must match what they said on call',
                'Always explain Panun Kaergar services to the customer',
                'Ask for referrals — can become Customer leads today',
                'Re-contact later? → Add Outbound Enquiry on the closed Future lead (Leads → Outbound Enquiries for full list)',
            ],
            'avoid' => [
                'Invalid for "no need now" — that is Future customer',
                'Closing without explaining what Panun Kaergar does',
                'Skipping panel reason dropdown',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideHandlingInvalidLeads(): array
    {
        return [
            'id' => 'handling-invalid-leads',
            'title' => 'Invalid — polite close',
            'subtitle' => 'Section 7 — cannot help, still professional',
            'type' => 'visual',
            'qualifier' => [
                'title' => 'When to use Invalid — confirm before you close',
                'items' => [
                    ['question' => 'Service Panun Kaergar does not offer (car repair, legal, delivery, etc.)?', 'type' => 'Invalid', 'note' => 'Write exact request in remarks'],
                    ['question' => 'Location outside our service area?', 'type' => 'Invalid', 'note' => 'Write location they asked for'],
                    ['question' => 'No service needed today — saving number or need later?', 'type' => 'Future customer — not Invalid', 'note' => 'Invalid is not for "not now"'],
                    ['question' => 'Needs home service we cover — plumbing, electrical, cleaning?', 'type' => 'Customer — not Invalid', 'note' => 'If we can help, do not close as Invalid'],
                ],
            ],
            'path_steps' => [
                [
                    'label' => 'Confirm, document, close politely',
                    'steps' => [
                        self::trainingStep(
                            'Confirm the request is something Panun Kaergar cannot fulfil',
                            'Invalid is for wrong service or wrong area — not because you are busy or unwilling. Double-check before closing.',
                            'Exact service requested, location, why we cannot help',
                            'Customer asks car AC repair in Jammu → home services only, outside typical coverage → Invalid.',
                            'On call or after qualify — explain politely, then panel + WhatsApp.',
                        ),
                        self::trainingStep(
                            'On call — decline politely and briefly say what we do offer',
                            'Still brand-safe. Customer should respect Panun Kaergar even if we cannot serve this request.',
                            'What they asked, what you told them we offer instead',
                            '"I am sorry — we do home services like plumbing and electrical in Kashmir. We cannot help with car repair, but for home needs call 8899881555."',
                            'Write exact request in remarks before closing panel.',
                        ),
                        self::trainingStep(
                            'Panel — type Invalid + select matching reason from dropdown',
                            'Remarks and dropdown reason must agree — managers audit invalid closes.',
                            'Type Invalid, reason dropdown, full remarks with request + location',
                            'Invalid ✓, reason "Service not offered", remarks "Car AC repair requested — Jammu. Explained home services only."',
                            'Send polite WhatsApp same minute — template below.',
                        ),
                        self::trainingStep(
                            'WhatsApp — sorry we cannot help with that specific request',
                            'Briefly mention what Panun Kaergar does offer. Professional tone always.',
                            'WA sent, request referenced, our services mentioned',
                            'See Invalid close WhatsApp template below.',
                            'Lead closed Invalid ✓ — no Followup On unless they had a separate valid need.',
                        ),
                    ],
                ],
            ],
            'scenarios' => [
                [
                    'title' => 'Wrong service type',
                    'trigger' => 'Call: "Can you fix my iPhone screen?" — not a home service.',
                    'action' => 'Polite decline on call → Invalid, reason service not offered → remarks exact request → WA template.',
                    'panel' => 'Invalid ✓, remarks "Mobile screen repair — not offered", WA sent.',
                ],
                [
                    'title' => 'Outside service area',
                    'trigger' => 'Web form: plumbing request in city Panun Kaergar does not cover.',
                    'action' => 'Call or WA → explain coverage → Invalid with area reason → polite close.',
                    'panel' => 'Invalid ✓, reason area not covered, location in remarks, WA sent.',
                ],
            ],
            'flowcharts' => [['key' => 'invalid-lead', 'title' => 'Flow — Invalid close']],
            'messages' => [self::waInvalidClose()],
            'remember' => [
                'Write exactly what they asked for — service name and location',
                'Invalid ✓ is professional close — not rude, not lazy',
                'Dropdown reason must match remarks',
                'Never Invalid for "no need now" — that is Future customer',
            ],
            'avoid' => [
                'Invalid for active home repair need we can serve',
                'Vague remarks like "cannot help" with no detail',
                'Rude or dismissive tone on call or WhatsApp',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideQuiz(): array
    {
        return [
            'id' => 'quiz',
            'title' => 'Expert certification quiz',
            'subtitle' => 'Scenarios, process order & edge cases — perfect score required',
            'type' => 'quiz',
            'questions' => [
                [
                    'id' => 'q1',
                    'question' => 'Shift start: you are 5 minutes into scanning follow-ups when the business phone rings. What do you do?',
                    'options' => [
                        'Finish the follow-up scan first — 5 minutes will not matter',
                        'Answer the phone immediately, then return to the scan after the call is fully handled',
                        'Let it ring — check missed calls at end of shift',
                        'Quickly mark the current lead Invalid and answer',
                    ],
                    'correct' => 1,
                    'explain' => 'Live ringing phone always beats queue work. Answer → notes on call → full panel + WA → then continue the scan.',
                ],
                [
                    'id' => 'q2',
                    'question' => 'Instagram DM: "Hi, I called you earlier" — no service mentioned. Lead is Unknown. What is the correct first action?',
                    'options' => [
                        'Set type Customer — they clearly need help',
                        'Outbound call same day, run the four qualifier questions, reclassify only after they answer',
                        'Post to provider group immediately — someone will figure it out',
                        'Close as Invalid — not enough information',
                    ],
                    'correct' => 1,
                    'explain' => 'Unknown is temporary. Call → qualifier → exactly one type before hang up. Never guess from a vague message.',
                ],
                [
                    'id' => 'q3',
                    'question' => 'On an Unknown qualify call they say: "I am an electrician in Budgam — I want to work with Panun Kaergar." What happens on this same call?',
                    'options' => [
                        'Keep Unknown — onboarding takes multiple days',
                        'Reclassify Provider, collect trade/area/details, then after call send agreement WA and set Followup On for doc deadline',
                        'Run customer Path A — send them a plumber',
                        'Invalid — we only hire plumbers',
                    ],
                    'correct' => 1,
                    'explain' => 'Successful qualify call = immediate reclassification. Provider → onboarding basics on call → panel + agreement WA same day.',
                ],
                [
                    'id' => 'q4',
                    'question' => 'Web booking auto-created: "Electrician — MCB tripping, Bemina" with a phone number but address says only "Bemina". Correct handling?',
                    'options' => [
                        'Path A immediately — post to provider group with "Bemina"',
                        'Call same day to confirm full address + time, decide Path A/B on call, then panel + WA before any group post',
                        'Mark Invalid — incomplete form',
                        'Wait until customer calls back',
                    ],
                    'correct' => 1,
                    'explain' => 'Auto channels still need verification. Vague address = call to confirm. Panel + WA before provider group — always.',
                ],
                [
                    'id' => 'q5',
                    'question' => 'Facebook comment: "Need plumber in Rajbagh tomorrow." These do NOT auto-create leads. What must you do?',
                    'options' => [
                        'Reply on Facebook only — that is enough',
                        'Create manual lead same day (Source = Facebook), copy exact text to remarks, classify or Unknown + call, WA if you have number',
                        'Ignore until they DM',
                        'Forward screenshot to manager — not your job',
                    ],
                    'correct' => 1,
                    'explain' => 'Manual channels = you create the lead. If it is not in Leads with source + phone + remarks, the team cannot see it.',
                ],
                [
                    'id' => 'q6',
                    'question' => 'You open a WhatsApp chat, reply to the customer, update remarks in your head — then switch to another lead. What did you miss?',
                    'options' => [
                        'Nothing — replying in chat is enough',
                        'Assignee + chat status + tags on the thread AND linked lead Handled By, status, remarks, Followup On',
                        'Only need to close the chat at end of shift',
                        'Just add a Lead ID tag — panel can wait',
                    ],
                    'correct' => 1,
                    'explain' => 'Chat tags alone are not handover. Every active thread needs assignee, status, tags, plus lead panel in sync.',
                ],
                [
                    'id' => 'q7',
                    'question' => 'AI WhatsApp escalation appears in Human support tab. Customer wants a human. First steps after takeover?',
                    'options' => [
                        'Use header "Talk With AI" to draft a reply',
                        'Assign yourself, tag Lead ID + stage, set chat status, open linked lead, read full AI transcript, then reply',
                        'Create a brand-new lead — AI leads are not in the panel',
                        'Reply first, tag later when you have time',
                    ],
                    'correct' => 1,
                    'explain' => 'Human support tab, not Talk With AI (staff assistant). Takeover → assign + tag before typing → sync lead panel.',
                ],
                [
                    'id' => 'q8',
                    'question' => 'Missed call at 10:15 AM from a number that already has an open Unknown lead from yesterday. What do you do?',
                    'options' => [
                        'Create a new lead — missed call = new enquiry',
                        'Open the existing lead, WA within 5 minutes, attempt outbound call same day, document attempt # in remarks',
                        'WA only — no need to call again',
                        'Close yesterday\'s lead as duplicate',
                    ],
                    'correct' => 1,
                    'explain' => 'Same phone = open existing lead, no duplicate. Missed call → WA within 5 min + same-day contact attempt.',
                ],
                [
                    'id' => 'q9',
                    'question' => 'Customer call: "Kitchen tap leaking, Rajbagh, tomorrow 10 AM — just send someone, I trust you." Correct path and order after the call?',
                    'options' => [
                        'Path B → discussion group post first → panel later',
                        'Path A → panel update + customer WA same minute → then provider group post with Lead ID, service, problem, address, time',
                        'Path A → provider group first → panel when provider replies',
                        'Path B — they might want to negotiate price',
                    ],
                    'correct' => 1,
                    'explain' => 'Ready to book without talking to provider = Path A. Order: panel → WA → then group post with full template.',
                ],
                [
                    'id' => 'q10',
                    'question' => 'Customer on call: "How much will bathroom plumbing cost? I want to speak to the plumber before I pay anything." Which path and group message?',
                    'options' => [
                        'Path A — *Service Request* post, collect ₹100 immediately',
                        'Path B — *Discussion Request* post noting customer wants price/scope call before booking',
                        'Path A — post first, conference happens after booking',
                        'Invalid — price shoppers waste time',
                    ],
                    'correct' => 1,
                    'explain' => 'Wants provider discussion before booking = Path B. Group title "Discussion Request" + note they want to talk before booking.',
                ],
                [
                    'id' => 'q11',
                    'question' => 'Web booking lead — customer type set, you call 3 days, no answer any time. Service known from form only. What is NEVER done?',
                    'options' => [
                        'WhatsApp after each attempt with attempt # in remarks',
                        'Post to provider group — you never spoke to the customer to confirm details',
                        'Document all 3 attempt dates then Change Status → Cancel → No Response From Customer',
                        'Followup On between each attempt',
                    ],
                    'correct' => 1,
                    'explain' => 'No provider group post without a successful customer call. 3 attempts + WA + Add follow-up each → Change Status → Cancel → No Response From Customer.',
                ],
                [
                    'id' => 'q12',
                    'question' => 'Path A: provider replies YES in 8 minutes. Customer confirmed slot on phone. What must happen BEFORE Create Booking in panel?',
                    'options' => [
                        'Create Booking first — ₹100 can wait until service day',
                        'Collect ₹100 from customer, then Create Booking, then confirmation WA to customer and provider',
                        'WA customer only — booking saves itself from group reply',
                        'Add provider to panel as new Provider lead',
                    ],
                    'correct' => 1,
                    'explain' => '₹100 before Create Booking — no exceptions on Path A or after Path B conference when customer commits.',
                ],
                [
                    'id' => 'q13',
                    'question' => 'You posted Path A job #4521 to the provider group at 2:00 PM. By 2:10 PM nobody replied. What is the correct escalation?',
                    'options' => [
                        'Wait until tomorrow — providers are busy',
                        '5-min reminder already sent if needed → call nearby providers, WA customer "still checking availability", log each try in remarks',
                        'Cancel the lead — no providers available',
                        'Message the same two favourites a fourth time only',
                    ],
                    'correct' => 1,
                    'explain' => '10-minute SLA: chase actively. Reminder at 5 min, call providers at 15 min, never go silent — always update customer.',
                ],
                [
                    'id' => 'q14',
                    'question' => 'Path B conference call: customer and provider are discussing price. Your role?',
                    'options' => [
                        'Drop off — it is between them now',
                        'Stay involved and coordinate; after call document outcome, ₹100 + booking if they agree, or Followup On if deciding later',
                        'Create Booking during the call before they finish talking',
                        'Change lead to Invalid if price is too high',
                    ],
                    'correct' => 1,
                    'explain' => 'Never drop the customer on conference. You coordinate, document outcome, then book / follow up / cancel politely.',
                ],
                [
                    'id' => 'q15',
                    'question' => 'Unknown lead — Attempt 1/3 no pickup Monday, Attempt 2/3 no pickup Tuesday. What is correct for Attempt 3?',
                    'options' => [
                        'Close after Attempt 2 — two tries is enough',
                        'Attempt 3 on Followup On Wednesday: call + WA same day; if no pickup, Mark as Invalid → Did not Know About Enquiry with all 3 dates in remarks + follow-ups',
                        'Keep Unknown open indefinitely with weekly calls',
                        'Reclassify Customer because the enquiry seems urgent',
                    ],
                    'correct' => 1,
                    'explain' => 'Max 3 attempts, WA each same day, type stays Unknown until they answer. After 3/3 vague Unknown → Mark as Invalid → Did not Know About Enquiry with full log.',
                ],
                [
                    'id' => 'q16',
                    'question' => 'Call: "We are renovating in October — no plumbing needed now, just saving your number." Correct classification and close?',
                    'options' => [
                        'Invalid — no booking today',
                        'Future customer ✓ — explain services, dropdown reason matches call, warm WA, ask for referrals; NOT Invalid',
                        'Unknown — call back in October',
                        'Customer Path A — book a consultation for October now',
                    ],
                    'correct' => 1,
                    'explain' => 'No need today but valid contact = Future customer. Invalid is wrong service/area only.',
                ],
                [
                    'id' => 'q17',
                    'question' => 'Call: "Can you fix my car AC in Jammu?" Panun Kaergar does home services in Kashmir. Correct close?',
                    'options' => [
                        'Future customer — they may need help later',
                        'Invalid ✓ — exact request + location in remarks, dropdown reason matches, polite WA listing home services we offer',
                        'Customer — refer to a car mechanic partner',
                        'Unknown — need more information',
                    ],
                    'correct' => 1,
                    'explain' => 'Wrong service AND outside area = Invalid. Document exactly what they asked. Future customer is NOT for wrong service.',
                ],
                [
                    'id' => 'q17b',
                    'question' => 'Future customer call mentions: "My brother needs a plumber this week — his number is 99XX." What do you do?',
                    'options' => [
                        'Ignore — you already closed Future customer for the caller',
                        'Close caller as Future customer ✓ AND create/handle separate Customer lead for brother with referral noted in both remarks',
                        'Add brother to same lead remarks only — one lead is enough',
                        'Invalid the brother — only one service per family',
                    ],
                    'correct' => 1,
                    'explain' => 'Referrals can become Customer leads today. Future close for caller + separate Customer handling for active need.',
                ],
                [
                    'id' => 'q18',
                    'question' => 'Provider onboarding: Step 1 brief call done 1 Aug, agreement + doc list WA sent, deadline 10 Aug. Docs arrive 8 Aug. What is next?',
                    'options' => [
                        'Step 4 — add to WhatsApp group immediately so they do not miss jobs',
                        'Step 3 final call (group rules, 10-min reply, payment), THEN Step 4 add to panel + correct trade/area WhatsApp group',
                        'Step 2 again — resend agreement',
                        'Customer Path A — they are ready to work',
                    ],
                    'correct' => 1,
                    'explain' => 'Never add to group before Step 3 final call. Docs received → final call → panel + group. Step 3 only after docs.',
                ],
                [
                    'id' => 'q19',
                    'question' => 'Provider Step 1 done, agreement sent, deadline passed. You sent 3 document reminders — still no docs. Correct close?',
                    'options' => [
                        'Keep Provider open forever — they might send someday',
                        'Change Status → Cancel — documents not received; all reminder dates documented in remarks',
                        'Invalid — they wasted our time',
                        'Add to group anyway — docs can come later',
                    ],
                    'correct' => 1,
                    'explain' => 'Max 3 doc follow-ups after agreement. No docs → Change Status → Cancel with reason + full follow-up log in remarks.',
                ],
                [
                    'id' => 'q20',
                    'question' => 'During a customer call you hear keyboard typing. The customer says "Are you even listening?" What rule was broken?',
                    'options' => [
                        'Should have used Path B instead of Path A',
                        'Notes on paper during call — panel update only after hang up',
                        'Should have collected ₹100 on the call',
                        'Should have posted to provider group live during call',
                    ],
                    'correct' => 1,
                    'explain' => 'Never update panel while customer speaks. Paper notes → confirm details → panel + WA after call ends.',
                ],
                [
                    'id' => 'q21',
                    'question' => 'Emergency call while updating a Future customer lead: "Whole house no power, children at home, need electrician NOW." Priority action?',
                    'options' => [
                        'Finish Future customer remarks first — almost done',
                        'Stop current task, answer/triage emergency, HOT flag in panel, provider path immediately, return to queue after fully documented',
                        'Tell them to call back in 30 minutes',
                        'Mark Invalid — wrong time to call',
                    ],
                    'correct' => 1,
                    'explain' => 'Emergency beats non-urgent work. HOT flag, immediate provider search, customer updates — live emergency always first.',
                ],
                [
                    'id' => 'q22',
                    'question' => 'Customer free Sat 10 AM–2 PM for plumber visit. When should Followup On be set and why?',
                    'options' => [
                        'Next Monday by habit — standard follow-up day',
                        'Sat 9 AM — before their window, to confirm provider; note reason in remarks',
                        'No Followup On — booking handles itself',
                        '30 days out — they are not urgent',
                    ],
                    'correct' => 1,
                    'explain' => 'Followup On from real availability — ask customer, match urgency, document WHY that date in remarks.',
                ],
                [
                    'id' => 'q23',
                    'question' => 'End of shift: you updated 4 WhatsApp chats but 2 linked leads have empty remarks and no Followup On. What is the risk?',
                    'options' => [
                        'Low — chat history is enough for next shift',
                        '#1 handover failure — next shift cannot continue without calling you; fix panel before logout',
                        'Only managers care about panel — chat is primary',
                        'Leads auto-sync from WhatsApp overnight',
                    ],
                    'correct' => 1,
                    'explain' => 'Chat updated but lead empty = invisible work. Every touched lead needs Handled By, remarks, Followup On or closed status.',
                ],
                [
                    'id' => 'q24',
                    'question' => 'App Custom Request vs Operations → Customized Requests — why must you not confuse them?',
                    'options' => [
                        'They are the same list with different names',
                        'App Custom Requests = customer leads (auto-created, qualify like phone). Customized Requests = bidding posts — different workflow entirely',
                        'Customized Requests are always Invalid',
                        'App Custom Requests never need a call',
                    ],
                    'correct' => 1,
                    'explain' => 'Pin App Custom Requests for app leads. Customized Requests (bidding) is a separate operations workflow.',
                ],
                [
                    'id' => 'q25',
                    'question' => 'Hot booking: customer waiting 20 minutes since your group post, no provider confirmed. You are sorting Instagram DMs. What is wrong?',
                    'options' => [
                        'Nothing — DMs are same priority as hot bookings',
                        'Customer waiting on provider beats low-priority DMs — chase group, call providers, WA customer update, mark HOT in remarks',
                        'Cancel the booking — 20 minutes is too long',
                        'Post the same vague "need plumber" again without Lead ID',
                    ],
                    'correct' => 1,
                    'explain' => 'Work by priority, not FIFO. HOT / customer waiting beats social DMs. Active chase + customer WA mandatory.',
                ],
                [
                    'id' => 'q26',
                    'question' => 'Lead partially handled: panel saved, group posted, but no customer WA yet. Phone rings with new enquiry. After the new call, what must happen first?',
                    'options' => [
                        'Open the new enquiry immediately — new leads first',
                        'Finish the interrupted lead: send customer WA, set Followup On if needed, then handle new enquiry fully',
                        'Delete the partial lead — it is corrupted',
                        'Only WA matters — panel can stay incomplete',
                    ],
                    'correct' => 1,
                    'explain' => 'One lead fully done before next (panel + WA + Followup On). Live call interrupts — finish interrupted lead after the call.',
                ],
                [
                    'id' => 'q27',
                    'question' => 'Provider group Path A template — which line MUST appear at the top?',
                    'options' => [
                        '"Is anyone available?" — urgency first',
                        '*Service Request – #{LEAD_ID}* — Lead ID always on top, then service, problem, address, preferred time',
                        'Customer phone number — providers need to call direct',
                        '₹100 payment confirmation',
                    ],
                    'correct' => 1,
                    'explain' => 'Standard format: Lead ID on top (*Service Request – #2425*), then service + problem, 📍 address, 🕐 timing, availability ask.',
                ],
                [
                    'id' => 'q28',
                    'question' => 'Mid-shift mini-scan (every 30–60 min) — which finding needs immediate action before returning to current lead?',
                    'options' => [
                        'Yesterday\'s closed lead still visible in list — ignore',
                        'New missed call 2 minutes ago → WA within 5 min + lead create/update before returning to queue work',
                        'Old Future customer from last month — handle first',
                        'Unread Facebook comment from 3 days ago — low priority',
                    ],
                    'correct' => 1,
                    'explain' => 'New missed call during shift → WA within 5 min + lead row. Finish current lead properly first, then scan, then act on new items.',
                ],
                [
                    'id' => 'q29',
                    'question' => 'Electrician wants to register. On call you learn they actually need an electrician for their own home tomorrow. Correct handling?',
                    'options' => [
                        'Provider onboarding — they mentioned electrician trade',
                        'Customer lead for home repair — Provider flow is for partners who receive jobs, not book for their own home',
                        'Invalid — cannot be both',
                        'Unknown forever — too confusing',
                    ],
                    'correct' => 1,
                    'explain' => 'Provider = wants to join and receive jobs. Need electrician at own home = Customer. Classify by what they need today.',
                ],
                [
                    'id' => 'q30',
                    'question' => 'How do you see how many times a lead was contacted in the panel?',
                    'options' => [
                        'Automatic counter on the hero badge',
                        'Activity → Follow-ups tab count + history table — each Add follow-up → Taken → Call or WhatsApp row (Call Logs tab for phone-only view)',
                        'Initial remarks only — follow-ups tab is for bookings',
                        'Change History tab counts phone calls',
                    ],
                    'correct' => 1,
                    'explain' => 'No auto counter. Log each touch with Add follow-up → Taken or Add Call Log. Follow-ups tab shows count and history; Call Logs tab shows the same call rows plus initial recording; remarks are a summary backup.',
                ],
                [
                    'id' => 'q32',
                    'question' => 'Instagram DM: "Plumber needed — blocked sink, Rajbagh, tomorrow." You call 3 times — no answer. Correct close?',
                    'options' => [
                        'Mark as Invalid → Did not Know About Enquiry',
                        'Mark as Customer with DM details → Change Status → Cancel → No Response From Customer',
                        'Leave Unknown open forever',
                        'Post to provider group using DM details',
                    ],
                    'correct' => 1,
                    'explain' => 'Documented customer need = Mark as Customer first, then Cancel No Response — not Invalid. Never provider group without speaking to customer.',
                ],
                [
                    'id' => 'q33',
                    'question' => 'When should you use Comments vs Initial remarks on a lead?',
                    'options' => [
                        'Comments replace remarks — pick one only',
                        'Remarks = customer-facing summary for any shift; Comments = internal @ handover notes (can pin)',
                        'Remarks are optional if Comments exist',
                        'Comments are for customers to read',
                    ],
                    'correct' => 1,
                    'explain' => 'Remarks = what happened with customer. Comments = internal team coordination. Both can exist; neither replaces Add follow-up rows.',
                ],
                [
                    'id' => 'q34',
                    'question' => 'Customer lead status is Pending. Provider confirmed, ₹100 collected. Next panel click?',
                    'options' => [
                        'Change Status → Cancel to close quickly',
                        'Sidebar → Create Booking for this Lead → save → Booking ID on hero',
                        'Only update remarks — booking not needed in panel',
                        'Mark as Provider',
                    ],
                    'correct' => 1,
                    'explain' => 'Create Booking from sidebar when Pending. After save, status moves toward Booked and Booking ID links in hero.',
                ],
                [
                    'id' => 'q31',
                    'question' => 'What makes a lead "fully handled" so you may open the next one?',
                    'options' => [
                        'Call ended — move on for speed',
                        'Closed OR panel type + full remarks + WA sent + Followup On if still open — checklist complete',
                        'WhatsApp sent — panel optional if busy',
                        'Provider group posted — rest follows automatically',
                    ],
                    'correct' => 1,
                    'explain' => 'Complete = closed OR documented open: type, remarks, WA, Followup On. Half-finished leads get lost at shift change.',
                ],
                [
                    'id' => 'q35',
                    'question' => 'You try Mark as Customer on an Unknown lead but panel blocks you. Most likely cause?',
                    'options' => [
                        'Lead is too old',
                        'Hard gate — outbound call step not done (no follow-up or call log row / call not documented)',
                        'Customer leads are disabled on weekends',
                        'You need manager password',
                    ],
                    'correct' => 1,
                    'explain' => 'Unknown → type change requires outbound contact logged. Call → Add Call Log or Add follow-up → tick workflow step → then Mark as.',
                ],
            ],
        ];
    }
}
