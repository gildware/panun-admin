<?php

namespace Modules\AdminModule\Support;

class BookingFollowupTrainingGuide
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function slides(): array
    {
        $slides = [
            self::slideDeckGuide(),
            self::slidePrerequisites(),
            self::slideYourJob(),
            self::slideWorkflowChecklist(),
            self::slideCreateBooking(),
            self::slideAppBookingRequests(),
            self::slideFollowUp(),
            self::slideBookingStatuses(),
            self::slideSpecialScenarios(),
            self::slideDisputes(),
            self::slidePayments(),
            self::slideFeedback(),
            self::slidePaymentChecklist(),
            self::slideQuiz(),
        ];

        foreach ($slides as $i => &$slide) {
            $slide['number'] = $i + 1;
            $slide = self::applySlideMeta($slide);
        }

        return $slides;
    }

    /**
     * @param  array<string, mixed>  $slide
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

    /** @return array<string, array{icon: string, overview: string}> */
    private static function slideMetaMap(): array
    {
        return [
            'deck-guide' => ['icon' => 'info', 'overview' => 'Booking terms used in every slide — read before you start.'],
            'prerequisites' => ['icon' => 'checklist'],
            'your-job' => ['icon' => 'verified_user'],
            'workflow-checklist' => ['icon' => 'checklist_rtl', 'overview' => 'The workflow FAB on booking details, stuck-items queue, and hard vs soft gates before status changes.'],
            'create-booking' => ['icon' => 'add_task'],
            'app-booking-requests' => ['icon' => 'smartphone', 'overview' => 'When a customer books directly in the app — Pending tab, provider accept, and when admin must intervene.'],
            'follow-up' => ['icon' => 'schedule'],
            'booking-statuses' => ['icon' => 'label'],
            'special-scenarios' => ['icon' => 'tune'],
            'payments' => ['icon' => 'payments'],
            'feedback' => ['icon' => 'rate_review'],
            'disputes' => ['icon' => 'gavel'],
            'payment-checklist' => ['icon' => 'fact_check'],
            'quiz' => ['icon' => 'quiz', 'overview' => 'Scenario quiz — prerequisites through payments and disputes.'],
        ];
    }

    /** @return array{text: string, detail?: string, collect?: string, example?: string, next?: string} */
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
     * Merge live workflow step definitions into training path_steps (single source of truth).
     *
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

    /** @return array{label: string, url: string} */
    private static function panelLink(string $label, string $url): array
    {
        return ['label' => $label, 'url' => $url];
    }

    /**
     * Panel click-map for training slides (field-by-field form guide).
     *
     * @param  array<int, array{label: string, text: string}>  $steps
     * @return array{title: string, summary: string, image?: string, steps: array<int, array{label: string, text: string}>}
     */
    private static function panelMap(string $title, string $summary, array $steps, ?string $image = null): array
    {
        $map = [
            'title' => $title,
            'summary' => $summary,
            'steps' => $steps,
        ];

        if ($image !== null && $image !== '') {
            $map['image'] = $image;
        }

        return $map;
    }

    /** @return array{mandatory: bool, label: string, template: string, example: string, to: string} */
    private static function wa(string $label, string $template, string $example, bool $mandatory = true, string $to = 'customer'): array
    {
        $target = match ($to) {
            'provider' => 'provider',
            default => 'customer',
        };

        return [
            'mandatory' => $mandatory,
            'to' => $to,
            'label' => $label !== '' ? $label : "WhatsApp to {$target}",
            'template' => $template,
            'example' => $example,
        ];
    }

    /**
     * @return array{label: string, text: string, image?: string, type: string}
     */
    private static function bookingExample(string $label, string $text, string $image = '', string $type = 'neutral'): array
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
     * @param  array<int, array{label: string, text: string, image?: string, type: string}>  $examples
     * @param  array<int, string>  $bestPractices
     * @param  array<int, string>  $avoid
     * @return array<string, mixed>
     */
    private static function bookingCard(
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
    private static function slideDeckGuide(): array
    {
        return [
            'id' => 'deck-guide',
            'title' => 'How to read this guide',
            'subtitle' => 'Booking terms we use',
            'type' => 'deck-guide',
            'terms_title' => 'Booking terms',
            'terms' => [
                ['term' => 'Booking', 'definition' => 'A confirmed service job in the panel — customer, provider, schedule, status, payments, and follow-ups.'],
                ['term' => 'Accepted', 'definition' => 'Provider assigned; visit not started. Follow-ups until service day.'],
                ['term' => 'Ongoing', 'definition' => 'Provider is on site or actively working. Set on/after service date when job starts.'],
                ['term' => 'Due Balance', 'definition' => 'Money customer still owes on booking details — must reach ₹0 before Completed.'],
                ['term' => 'Touchpoint', 'definition' => 'A planned contact: at booking, day before (3+ days out only), or service day.'],
                ['term' => 'Re Assign', 'definition' => 'Change provider before Ongoing — auto WhatsApp to customer, old provider, new provider.'],
                ['term' => 'Special scenario', 'definition' => 'Settlement path after visit — Cancel After Visit, Complete visit only, or Loss making.'],
                ['term' => 'Follow-ups tab', 'definition' => 'On booking details — log every call/WhatsApp, set next date until booking closes.'],
                ['term' => 'Bill breakdown', 'definition' => 'Total = service charge + each part (name + price). Mandatory in panel before close.'],
                ['term' => 'Assignee', 'definition' => 'Staff member accountable for the booking until Completed or Canceled.'],
                ['term' => 'Workflow checklist', 'definition' => 'Floating steps on booking details — tick each box as you complete touchpoints and close checklist items.'],
                ['term' => 'Hard gate', 'definition' => 'Panel blocks the action until required workflow steps are done (e.g. due balance zero before Completed).'],
                ['term' => 'Soft gate', 'definition' => 'Panel shows a confirm modal listing skipped steps — you can proceed after ticking confirm.'],
                ['term' => 'Pending cancellation', 'definition' => 'Provider or customer requested cancel — admin must approve or deny before booking closes.'],
            ],
        ];
    }

    /**
     * @param  array<int, string>  $detailPoints
     * @param  array<int, string>  $bestPractices
     * @param  array<int, string>  $avoid
     * @return array<string, mixed>
     * @deprecated Use bookingCard() — kept for any legacy callers
     */
    private static function jobCard(
        string $id,
        string $title,
        string $description,
        string $detail,
        array $detailPoints,
        string $icon,
        array $bestPractices,
        array $avoid,
    ): array {
        return self::bookingCard($id, $title, $description, $detail, $detailPoints, '', $icon, [], $bestPractices, $avoid);
    }

    /** @return array<string, mixed> */
    private static function slidePrerequisites(): array
    {
        return [
            'id' => 'prerequisites',
            'title' => 'Booking prerequisites',
            'subtitle' => 'Check every box before Create Booking',
            'type' => 'visual',
            'important' => 'Incomplete booking = double work on service day + angry customer + wasted provider trip.',
            'card_groups' => [
                [
                    'title' => 'Before you click Create Booking',
                    'hint' => 'All four areas must be ready — not “fix later”.',
                    'layout' => 'row-4',
                    'cards' => [
                        [
                            'icon' => 'person',
                            'title' => 'Lead / customer',
                            'text' => 'Qualified — not Unknown.',
                            'color' => 'customer',
                            'points' => [
                                'Service + problem on call',
                                'Full address with zone + area',
                                'Date/time agreed',
                            ],
                        ],
                        [
                            'icon' => 'home',
                            'title' => 'Customer record',
                            'text' => 'Phone + address in panel.',
                            'color' => 'customer',
                            'points' => [
                                'Phone matches WhatsApp',
                                'Zone matches address',
                                'No duplicate open booking',
                            ],
                        ],
                        [
                            'icon' => 'build',
                            'title' => 'Service & schedule',
                            'text' => 'Cart and timing correct.',
                            'color' => 'provider',
                            'points' => [
                                'Category + service selected',
                                'Schedule date + time set',
                                'Problem description for provider',
                            ],
                        ],
                        [
                            'icon' => 'engineering',
                            'title' => 'Provider plan',
                            'text' => 'Someone will do the job.',
                            'color' => 'provider',
                            'points' => [
                                'Provider assigned or accept plan',
                                'Covers zone + service',
                                'Same-day plan if none yet',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Stop — do not create if',
                    'hint' => 'Fix on call first or keep as lead with follow-up.',
                    'tone' => 'warn',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'location_off',
                            'title' => 'Vague address',
                            'text' => 'No zone or “near masjid” only.',
                            'color' => 'invalid',
                            'points' => ['Clarify on call first'],
                        ],
                        [
                            'icon' => 'person_off',
                            'title' => 'No provider plan',
                            'text' => 'Nobody assigned or findable.',
                            'color' => 'invalid',
                            'points' => ['Plan same-day before save'],
                        ],
                        [
                            'icon' => 'block',
                            'title' => 'Not agreed / duplicate',
                            'text' => 'Customer not committed or job exists.',
                            'color' => 'invalid',
                            'points' => ['Duplicate open booking for same job'],
                        ],
                    ],
                ],
            ],
            'remember' => ['Link lead_id when booking comes from a lead', 'Preview screen — verify cart total and provider before Store'],
            'avoid' => ['Saving to “fix details later”', 'Wrong zone selected'],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideYourJob(): array
    {
        return [
            'id' => 'your-job',
            'title' => 'Your job in bookings',
            'subtitle' => 'Click any card — steps, examples, and panel habits',
            'type' => 'point-cards',
            'point_cards' => [
                self::bookingCard(
                    'own-until-closed',
                    'Own the booking until closed',
                    'Drive every open booking to Completed or Canceled with zero due.',
                    'The assignee on the booking row is accountable until close. Hand over with full follow-up remarks if you shift — next shift has not heard the calls.',
                    [
                        'Check assignee every time you open a booking',
                        'Open bookings need a next follow-up date in panel',
                        'Closed = Completed/Canceled + due zero + bill confirmed + feedback done',
                    ],
                    'mission-panel-handover.png',
                    'assignment_ind',
                    [
                        self::bookingExample('Good handover', '“Assignee: Ali. Provider confirmed Thu 10 AM. Customer WA sent. Next: service day morning call provider.”', 'panel-update-ex-1.png', 'good'),
                        self::bookingExample('Takeover', 'New shift opens booking — reads remarks and continues without calling previous staff.', 'team-comms-ex-1.png', 'good'),
                        self::bookingExample('Bad handover', 'Remarks empty, assignee blank — booking sits in Accepted for 5 days.', 'clear-details-ex-2.png', 'bad'),
                        self::bookingExample('Almost closed', 'Marked Completed but due ₹200 still showing — not actually closed.', 'clear-details-ex-2.png', 'bad'),
                    ],
                    ['Set assignee when you create or take over', 'Update remarks after every contact'],
                    ['Assuming provider will handle alone', 'Closing without checking due balance'],
                ),
                self::bookingCard(
                    'followup-discipline',
                    'Follow-up discipline',
                    'Booking Followups Pending Till Today = your daily queue.',
                    'Document every call in Follow-ups tab. Work overdue rows first. Day-before call **only when service is 3+ days after booking** — not for same-day or tomorrow.',
                    [
                        'Add first follow-up when booking is created',
                        'Take follow-up after each contact — Completed or Rescheduled',
                        'Mandatory next date until booking closes',
                        'WhatsApp before a second call the same day',
                    ],
                    'mission-10-followup-timing.png',
                    'schedule',
                    [
                        self::bookingExample('Queue start', 'Shift opens → Booking Followups Pending Till Today → red rows first.', 'followup-timing-ex-1.png', 'good'),
                        self::bookingExample('Logged contact', 'Called provider 9:12 AM → Follow-ups tab → Taken → Call → remarks → next date set.', 'panel-update-ex-1.png', 'good'),
                        self::bookingExample('Day-before rule', 'Booked Mon for Fri → day-before call Wed. Booked Mon for Tue → skip day-before.', 'followup-timing-ex-2.png', 'good'),
                        self::bookingExample('No next date', 'Completed follow-up row but left next date empty — system blocks or booking orphaned.', 'next-lead-ex-2.png', 'bad'),
                    ],
                    ['Work queue at every shift start', 'Log exact attempt time in remarks'],
                    ['Skipping “easy” bookings', 'Daily calls with no new info'],
                ),
                self::bookingCard(
                    'touchpoint-timing',
                    'When to call — touchpoints',
                    'At booking · day before (3+ days out) · service day.',
                    'Fewer calls, better timing. Same-day and 1–2 days out: booking confirm + service day only. Three or more days out: add day-before provider check.',
                    [
                        'Touchpoint 1: always at booking (call + WhatsApp)',
                        'Touchpoint 2: day before — only if service is 3+ days away',
                        'Touchpoint 3: service day morning + ~1 hr before location check',
                        'Between touchpoints: WhatsApp OK — not daily calls',
                    ],
                    'training-journey.png',
                    'event_repeat',
                    [
                        self::bookingExample('Same-day job', 'Booked 9 AM for 2 PM today → provider call → customer call → 1 PM location check. No day-before.', 'followup-timing-ex-1.png', 'good'),
                        self::bookingExample('3 days out', 'Booked Mon for Thu → Wed provider call → Thu morning → 1 hr before Ongoing.', 'followup-timing-ex-2.png', 'good'),
                        self::bookingExample('Service day order', 'Morning: provider first (“visiting today?”). Customer only if needed.', 'customer-centric-ex-1.png', 'good'),
                        self::bookingExample('Call fatigue', 'Called customer 4 days in a row “just checking” — no new info each time.', 'clear-details-ex-2.png', 'bad'),
                    ],
                    ['Provider first on service day', 'Set Ongoing when provider starts — not before'],
                    ['Day-before call for tomorrow booking', 'Calling customer before confirming provider'],
                ),
                self::bookingCard(
                    'status-with-reason',
                    'Status changes need reasons',
                    'Every status change on booking details is deliberate.',
                    'Cancel and On hold need reason + responsible party. Ongoing only on/after service date when provider started. Cannot Re Assign after Ongoing.',
                    [
                        'Accepted → follow-ups until service day',
                        'Ongoing → provider on site / working',
                        'On hold → pause with new date + reason',
                        'Completed → due zero + bill confirmed',
                    ],
                    'mission-03-panel.png',
                    'swap_horiz',
                    [
                        self::bookingExample('Ongoing correct', 'Thu 10 AM slot → provider calls 10:15 “started” → staff sets Ongoing.', 'panel-update-ex-1.png', 'good'),
                        self::bookingExample('On hold', 'Parts needed → On hold + reason + hold estimated schedule + follow-up next day.', 'panel-update-ex-2.png', 'good'),
                        self::bookingExample('Too early Ongoing', 'Status Ongoing on Wed for Thu job — breaks reporting and Re Assign rules.', 'clear-details-ex-2.png', 'bad'),
                        self::bookingExample('Plain cancel after visit', 'Provider visited → plain Cancel — use special scenario instead.', 'clear-details-ex-2.png', 'bad'),
                    ],
                    ['Read status history when taking over', 'Match WhatsApp to status change'],
                    ['Ongoing before service date', 'Silent reschedule without WA'],
                ),
                self::bookingCard(
                    'bill-breakdown',
                    'Bill breakdown — mandatory',
                    'Total = service charge + each part — in panel before Completed.',
                    'Call provider for itemized bill. Enter every line on booking. Call customer to confirm amount matches. No vague totals.',
                    [
                        'Provider: total, service/labour, each part name + charge',
                        'Panel: enter all lines before Completed',
                        'Customer: confirm breakdown matches what they pay',
                        'Fix mismatch before close — not after dispute',
                    ],
                    'mission-03-panel.png',
                    'receipt_long',
                    [
                        self::bookingExample('Good breakdown', 'Service ₹400 + MCB ₹350 + labour ₹200 = ₹950 — all in panel, customer confirmed on call.', 'panel-update-ex-1.png', 'good'),
                        self::bookingExample('Customer confirm', '“Provider charged ₹950 — ₹400 service + ₹350 MCB + ₹200 labour. Correct?” → yes → Completed.', 'customer-centric-ex-1.png', 'good'),
                        self::bookingExample('Bad — vague', 'Provider says “1500 total” — no parts listed — panel wrong → dispute next day.', 'clear-details-ex-2.png', 'bad'),
                        self::bookingExample('Mismatch', 'Customer thought ₹800, panel shows ₹1200 — caught on confirm call before Completed.', 'customer-centric-ex-2.png', 'good'),
                    ],
                    ['Provider call before customer call', 'Due balance matches invoice lines'],
                    ['Completed with guessed amount', 'Skipping parts lines'],
                ),
                self::bookingCard(
                    'payments-zero-due',
                    'Payments — due must reach zero',
                    'Due Balance on booking details must hit ₹0 before close.',
                    'Record advance at create, partial payments during Ongoing, settlement lines on special scenarios. Note who received cash — company vs provider.',
                    [
                        'Advance at create if customer paid ₹100',
                        'Add payment during Ongoing as customer pays',
                        'Settlement modal for special scenarios until due clear',
                    ],
                    'mission-03-panel.png',
                    'payments',
                    [
                        self::bookingExample('During job', 'Customer pays ₹500 cash on site → Payments → Add payment → who received → due drops.', 'panel-update-ex-1.png', 'good'),
                        self::bookingExample('Before Completed', 'Due Balance shows 0.00 → safe to mark Completed.', 'panel-update-ex-2.png', 'good'),
                        self::bookingExample('Loss making', 'Customer paid less than invoice → Loss making scenario → track recovery.', 'panel-update-ex-2.png', 'neutral'),
                        self::bookingExample('Blocked close', 'Due ₹200 still showing → cannot Completed until payment recorded.', 'clear-details-ex-2.png', 'bad'),
                    ],
                    ['Watch Due Balance after every payment', 'Match who received cash for ledger'],
                    ['Mark Completed with money due', 'Forget provider share on visit lines'],
                ),
                self::bookingCard(
                    'notify-on-change',
                    'Notify on every change',
                    'WhatsApp auto-templates + manual confirm on important jobs.',
                    'After create, Re Assign, reschedule, or cancel — customer and provider must know within 15 minutes. Note in remarks what was sent.',
                    [
                        'After create: confirmation customer + provider',
                        'Re Assign: all three parties notified',
                        'Reschedule: updated date/time to both',
                    ],
                    'mission-04-whatsapp.png',
                    'chat',
                    [
                        self::bookingExample('After create', 'Auto WA fired → staff verifies both delivered → remarks “WA confirm sent”.', 'whatsapp-ex-1.png', 'good'),
                        self::bookingExample('Re Assign', 'New provider assigned → customer WA with new name + time within 15 min.', 'whatsapp-ex-2.png', 'good'),
                        self::bookingExample('Reschedule', '“Your booking moved to Fri 11 AM — provider [name] confirmed.”', 'whatsapp-ex-1.png', 'good'),
                        self::bookingExample('Silent change', 'Provider reassigned — customer not told — wrong person shows up.', 'clear-details-ex-2.png', 'bad'),
                    ],
                    ['WhatsApp within 15 minutes of change', 'Remarks note what was sent'],
                    ['Silent reschedule', 'Provider sent to old address'],
                ),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideWorkflowChecklist(): array
    {
        return [
            'id' => 'workflow-checklist',
            'title' => 'Workflow checklist in the panel',
            'subtitle' => 'FAB on booking details · stuck queue · hard vs soft gates',
            'type' => 'visual',
            'panel_links' => [
                self::panelLink('Workflow Stuck Items', '/admin/workflow/stuck'),
                self::panelLink('Booking list', '/admin/booking/list'),
                self::panelLink('Today\'s booking follow-ups', '/admin/booking/todays-followups'),
            ],
            'important' => 'The checklist is the same steps as this training deck — synced from the live workflow engine.',
            'card_groups' => [
                [
                    'title' => 'Where to find it',
                    'hint' => 'On every open booking detail page.',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'pending_actions',
                            'title' => 'Floating workflow FAB',
                            'text' => 'Bottom-right on booking details — shows next step + progress %.',
                            'color' => 'customer',
                            'points' => [
                                'Expand to see all steps for this booking',
                                'Tick checkbox when you complete a manual step',
                                'Training link on each step opens the matching slide',
                            ],
                        ],
                        [
                            'icon' => 'view_list',
                            'title' => 'Workflow Stuck Items',
                            'text' => 'Team queue of bookings with pending steps.',
                            'color' => 'provider',
                            'points' => [
                                'Process Guides header → Workflow Stuck Items',
                                'Also from Team nav when you have lead_view',
                                'Work oldest / overdue service dates first',
                            ],
                        ],
                        [
                            'icon' => 'fact_check',
                            'title' => 'Close checklist (before Completed)',
                            'text' => 'Separate group when status is Accepted/Ongoing/On hold.',
                            'color' => 'future',
                            'points' => [
                                'Provider bill breakdown',
                                'Panel bill entry',
                                'Customer billing confirm',
                                'Due balance zero (hard gate)',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Hard vs soft gates',
                    'hint' => 'Some actions block until steps are done; others ask you to confirm.',
                    'layout' => 'row-2',
                    'cards' => [
                        [
                            'icon' => 'block',
                            'title' => 'Hard gate — cannot proceed',
                            'text' => 'Panel stops the action until the step is done.',
                            'color' => 'invalid',
                            'points' => [
                                'Mark Completed while Due Balance > 0',
                                'Fix payment or loss-making settlement first',
                            ],
                        ],
                        [
                            'icon' => 'warning',
                            'title' => 'Soft gate — confirm modal',
                            'text' => 'Lists skipped checklist items — tick confirm to proceed anyway.',
                            'color' => 'unknown',
                            'points' => [
                                'Bill breakdown not ticked but due is zero',
                                'Use only when step is truly done but checkbox missed',
                                'Never confirm steps you have not actually finished',
                            ],
                        ],
                    ],
                ],
            ],
            'path_steps' => [
                self::workflowTrainingGroup('booking.post_create', 'After create — tick on booking details'),
                self::workflowTrainingGroup('booking.touchpoints', 'Active booking — three touchpoints'),
                self::workflowTrainingGroup('booking.close', 'Before Completed — close checklist'),
            ],
            'remember' => [
                'Tick checkboxes as you work — not at end of shift in bulk',
                'Stuck Items page = bookings where next workflow step is overdue',
                'Training slide link on each step if you forget the detail',
            ],
            'avoid' => [
                'Ignoring the FAB because you “know the process”',
                'Confirming soft gates for steps you skipped',
                'Marking Completed before close checklist is truly done',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideAppBookingRequests(): array
    {
        return [
            'id' => 'app-booking-requests',
            'title' => 'App booking requests (Pending)',
            'subtitle' => 'Customer booked in mobile app — not the same as Create Booking from lead',
            'type' => 'visual',
            'important' => 'App Custom Request = lead to qualify. Booking Request = confirmed app booking waiting for provider accept.',
            'panel_links' => [
                self::panelLink('Booking Requests — Pending tab', '/admin/booking/list?booking_status=pending&service_type=all'),
                self::panelLink('All bookings', '/admin/booking/list'),
                self::panelLink('App Custom Requests (leads)', '/admin/booking/app-custom-requests'),
            ],
            'card_groups' => [
                [
                    'title' => 'Three paths — do not confuse them',
                    'hint' => 'Wrong list = wrong workflow.',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'add_task',
                            'title' => 'Admin Create Booking from lead',
                            'text' => 'You qualified customer in Leads → Create Booking.',
                            'color' => 'customer',
                            'points' => [
                                'Store → status **Accepted** immediately',
                                'Provider already assigned on form',
                                'Follow create-booking + follow-up slides',
                            ],
                        ],
                        [
                            'icon' => 'smartphone',
                            'title' => 'App Booking Request',
                            'text' => 'Customer booked in app — auto row in panel.',
                            'color' => 'provider',
                            'points' => [
                                'Starts **Pending** — provider must accept in app',
                                'List → Pending Booking tab',
                                'Admin monitors — intervene if no accept',
                            ],
                        ],
                        [
                            'icon' => 'help_outline',
                            'title' => 'App Custom Request',
                            'text' => 'Customer asked for non-standard service.',
                            'color' => 'future',
                            'points' => [
                                'Creates a **lead** — qualify like phone',
                                'App Custom Requests list — not Pending tab',
                                'See Lead Qualification guide',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'When status is Pending — your job',
                    'layout' => 'row-2',
                    'cards' => [
                        [
                            'icon' => 'hourglass_empty',
                            'title' => 'Monitor at shift start',
                            'text' => 'Open Pending Booking tab every shift.',
                            'color' => 'unknown',
                            'points' => [
                                'Sort by newest or service date',
                                'Check if provider accepted in provider app',
                                'Call provider if accept is slow',
                            ],
                        ],
                        [
                            'icon' => 'engineering',
                            'title' => 'Admin intervention',
                            'text' => 'When provider ignores or declines.',
                            'color' => 'provider',
                            'points' => [
                                'Re Assign provider on booking details (before Ongoing)',
                                'WhatsApp customer within 15 minutes',
                                'Log follow-up in Follow-ups tab',
                            ],
                        ],
                    ],
                ],
            ],
            'ui_maps' => [
                self::panelMap(
                    'Pending → Accepted — what changes',
                    'Provider accepts in app OR admin assigns provider on booking details.',
                    [
                        ['label' => 'Pending Booking tab', 'text' => 'Leads and bookings → Booking Requests → filter Pending. Row shows customer, service, schedule, assigned provider if any.'],
                        ['label' => 'Provider accepts', 'text' => 'Status moves to Accepted automatically — start normal follow-up touchpoints.'],
                        ['label' => 'No accept by SLA', 'text' => 'Call provider → Re Assign if needed → confirm WhatsApp to customer.'],
                        ['label' => 'Assignee', 'text' => 'Set yourself on Pending rows you own — same as admin-created bookings.'],
                        ['label' => 'Linked lead', 'text' => 'Some app bookings link to a lead — open lead for full history but work the booking row for status.'],
                    ],
                    'booking-web-bookings-list.png',
                ),
            ],
            'remember' => [
                'Pending = waiting on provider accept — not “waiting on you to create”',
                'Admin create from lead skips Pending → goes straight to Accepted',
                'Pin Pending tab if you handle app bookings',
            ],
            'avoid' => [
                'Creating duplicate booking for same app request',
                'Leaving Pending rows unchecked for days',
                'Confusing App Custom Requests with Booking Requests',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideCreateBooking(): array
    {
        return [
            'id' => 'create-booking',
            'title' => 'How to create a booking',
            'subtitle' => 'Preview → Store → follow-up',
            'type' => 'visual',
            'flowchart' => 'create-booking',
            'note' => 'Preview → Store → set assignee → Add first follow-up → confirm WhatsApp sent.',
            'ui_maps' => [
                self::panelMap(
                    'Add New Booking — form fields (fill in order)',
                    'Operations → Add New Booking. Same fields when opened from lead (prefilled). Continue to Preview before Store.',
                    [
                        ['label' => 'Customer *', 'text' => 'Search and select customer. If new — create customer first, then return. Example: Fatima Shah · 989xxxxxxx.'],
                        ['label' => 'Service Address *', 'text' => 'Pick saved address or Add Address — must include zone + area. Example: House 12, Lane 4, Rajbagh, Srinagar. Not “near masjid”.'],
                        ['label' => 'Booking Source *', 'text' => 'How job came in — Phone, WhatsApp, Web, etc. Example: Phone.'],
                        ['label' => 'Assignee', 'text' => 'Who owns follow-ups until close. Default: Assign to me.'],
                        ['label' => 'Zone * → Add Service', 'text' => 'Pick zone matching address → Booking Summary → Add Service → category, sub-category, service, variant, qty. Example: Plumbing → Tap repair.'],
                        ['label' => 'Provider *', 'text' => 'Provider who will visit. Must cover zone + service. Example: Ahmad Plumbing.'],
                        ['label' => 'Where Service will be Provided *', 'text' => 'Usually “At customer location”. Provider location only if customer goes to workshop.'],
                        ['label' => 'Service Schedule *', 'text' => 'Date and time of visit. Example: 2026-08-05 10:00.'],
                        ['label' => 'Service Additional Details', 'text' => 'Optional — problem description for provider. Example: “Kitchen tap leaking continuously, stop valve under sink.”'],
                        ['label' => 'Advance Paid Amount', 'text' => 'Optional — if customer paid ₹100 at booking. Then pick Advance payment method + transaction ID if digital.'],
                        ['label' => 'Continue to Preview → Confirm & Create Booking', 'text' => 'Check cart total, provider name, schedule on preview screen — then Store. Status becomes Accepted.'],
                    ],
                    'booking-create-form.png',
                ),
                self::panelMap(
                    'Web booking — what customer submitted (verify on call)',
                    'Web Bookings → open row → read every field before Create Booking. Call customer if anything vague.',
                    [
                        ['label' => 'Reference', 'text' => 'Web form ID — note in lead remarks.'],
                        ['label' => 'Customer Name + Phone Number *', 'text' => 'Match to lead/customer record. Call if name typo or wrong number. Example: Ali · 98xxxxxx10.'],
                        ['label' => 'Service', 'text' => 'Category customer selected on website. Example: Electrical. Confirm exact problem on call.'],
                        ['label' => 'Area', 'text' => 'Often incomplete — get full address + zone on call. Example: website says “Rajbagh” → you collect “Lane 2, House 12”.'],
                        ['label' => 'Preferred date/time', 'text' => 'Customer’s request — confirm still works. Example: “Tomorrow morning”.'],
                        ['label' => 'Details', 'text' => 'Free text from website — read fully. Example: “MCB keeps tripping in kitchen”.'],
                        ['label' => 'Lead link → Create Booking', 'text' => 'If lead exists — click Create Booking (prefilled). If no lead — create/fix lead first with web source + full remarks.'],
                    ],
                    'booking-web-bookings-list.png',
                ),
                self::panelMap(
                    'After create — booking details (first clicks)',
                    'Every create path ends here. Do these before leaving the booking.',
                    [
                        ['label' => 'Assignee', 'text' => 'Set yourself if blank.'],
                        ['label' => 'Follow-ups tab → Add Follow-up', 'text' => 'Date/time, For (customer or provider), reason — first touchpoint logged.'],
                        ['label' => 'Confirm WhatsApp', 'text' => 'Auto confirmation to customer + provider — verify sent.'],
                        ['label' => 'Payments (if advance)', 'text' => 'Advance at create should appear in payment history.'],
                    ],
                    'booking-details-after-create.png',
                ),
                self::panelMap(
                    'Re Assign provider — before Ongoing only',
                    'Booking details → Provider section → Re Assign. Allowed while status is Pending, Accepted, On hold, or Pending cancellation — not after Ongoing.',
                    [
                        ['label' => 'When to use', 'text' => 'Provider unavailable, withdrew, or wrong trade/area. Day-before call finds no cover — reassign same day.'],
                        ['label' => 'Provider feedback modal', 'text' => 'If changing from an assigned provider, panel may ask for performance feedback first — complete honestly, then confirm Re Assign.'],
                        ['label' => 'Pick new provider', 'text' => 'Search provider covering zone + service → confirm. Auto WhatsApp to customer, old provider, new provider.'],
                        ['label' => 'WhatsApp verify', 'text' => 'Within 15 minutes — customer must know new name + time. Remarks: “Re Assign to [name] — WA sent 3 Aug 10:20.”'],
                        ['label' => 'After Ongoing', 'text' => 'Re Assign blocked — use On hold + new date, Cancel (before visit), or special scenario (after visit).'],
                    ],
                    'booking-create-form.png',
                ),
            ],
            'messages' => [
                self::wa(
                    'After booking saved — customer',
                    "Assalam alaikum — your Panun Kaergar booking is confirmed.\n\n"
                    ."Service: {SERVICE}\n📍 {ADDRESS}\n🕐 {DATE_TIME}\nProvider: {PROVIDER_NAME}\nBooking ID: #{BOOKING_ID}\n\n"
                    .'Reply here if you need to change time or address.',
                    "Assalam alaikum — your Panun Kaergar booking is confirmed.\n\n"
                    ."Service: Kitchen tap plumbing\n📍 Lane 2 Rajbagh\n🕐 Thu 10 AM\nProvider: Ahmad Plumbing\nBooking ID: #BK-4421\n\n"
                    .'Reply here if you need to change time or address.',
                ),
                self::wa(
                    'After booking saved — provider',
                    "New job assigned — Booking #{BOOKING_ID}\n\n"
                    ."{SERVICE} — {PROBLEM}\n📍 {ADDRESS}\n🕐 {DATE_TIME}\nCustomer: {CUSTOMER_NAME} · {PHONE}\n\n"
                    .'Confirm you can attend. Reply if any issue.',
                    "New job assigned — Booking #BK-4421\n\n"
                    ."Kitchen tap leak\n📍 Lane 2 Rajbagh\n🕐 Thu 10 AM\nCustomer: Fatima · 98xxxxxx10\n\n"
                    .'Confirm you can attend. Reply if any issue.',
                    true,
                    'provider',
                ),
            ],
            'remember' => ['Never save without complete address', 'First follow-up same day as create', 'Preview — verify cart total and provider before Store'],
            'avoid' => ['Creating duplicate booking for same job', 'Wrong zone selected', 'Saving to fix details later'],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideFollowUp(): array
    {
        return [
            'id' => 'follow-up',
            'title' => 'How to follow up bookings',
            'subtitle' => 'Three touchpoints · service day · billing · when things go wrong',
            'type' => 'visual',
            'flowchart' => 'follow-up',
            'important' => 'Day-before call **only when service is 3+ days out**. Same-day / 1–2 days: booking confirm + service day only.',
            'shift_checklist' => [
                'Follow-ups Pending Till Today — overdue first',
                'Service day: provider AM → customer if needed → WhatsApp 1 hr before → Ongoing',
                'After job: provider bill → panel → customer confirm',
            ],
            'card_groups' => [
                [
                    'title' => 'Three touchpoints',
                    'hint' => 'Memorize — stops call fatigue.',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'looks_one',
                            'title' => 'Touchpoint 1 — Booking',
                            'text' => 'Always — call + WhatsApp both parties.',
                            'color' => 'customer',
                            'points' => ['Provider first on same-day', 'Add first follow-up in panel', 'Set assignee'],
                        ],
                        [
                            'icon' => 'looks_two',
                            'title' => 'Touchpoint 2 — Day before',
                            'text' => 'Only if service is 3+ days after booking.',
                            'color' => 'provider',
                            'points' => ['Provider first — still available?', 'Skip for same-day / tomorrow / day after', 'Customer only if unclear'],
                        ],
                        [
                            'icon' => 'looks_3',
                            'title' => 'Touchpoint 3 — Service day',
                            'text' => 'Morning + ~1 hr before location.',
                            'color' => 'customer',
                            'points' => ['Provider first → customer if needed', 'WhatsApp 1 hr before → Ongoing', 'Bill + feedback after job'],
                        ],
                    ],
                ],
            ],
            'ui_maps' => [
                self::panelMap(
                    'Follow-ups tab — Add Follow-up modal',
                    'Booking details → Followups tab → Add Follow-up. Log every planned contact.',
                    [
                        ['label' => 'Date Time *', 'text' => 'When you will call/WhatsApp. Example: tomorrow 9:00 AM for service-day provider check.'],
                        ['label' => 'Reason', 'text' => 'Optional short note. Example: “Confirm provider for Thu visit”.'],
                        ['label' => 'For *', 'text' => 'Customer or Provider — who you will contact. Example: Provider for day-before check.'],
                        ['label' => 'Urgency', 'text' => 'High / Medium / Low. Default Medium. Use High for same-day service.'],
                        ['label' => 'Submit', 'text' => 'Saves scheduled follow-up — appears in Today\'s queue on that date.'],
                    ],
                    'booking-followup-add-modal.png',
                ),
                self::panelMap(
                    'Follow-ups tab — Take Follow-up modal',
                    'After you call — open scheduled row → Take Follow-up. Never complete without next date on open bookings.',
                    [
                        ['label' => 'Status * — Completed', 'text' => 'Contact happened. Then fill Remarks (required). Example: “Called provider — confirmed Thu 10 AM.”'],
                        ['label' => 'Status * — Rescheduled', 'text' => 'Could not reach or need new date. New Date Time * + Reschedule Reason * required.'],
                        ['label' => 'Remarks * (when Completed)', 'text' => 'What was said, outcome, next action. Handover for next shift.'],
                        ['label' => 'Schedule another follow up', 'text' => 'Checkbox — almost always required on open bookings until Completed/Canceled.'],
                        ['label' => 'Next Follow-up — Date Time *', 'text' => 'When to contact next. Example: service day 8:30 AM.'],
                        ['label' => 'For * / Urgency', 'text' => 'Who to call next and priority.'],
                    ],
                    'booking-followup-take-modal.png',
                ),
                self::panelMap(
                    'Today\'s follow-up queue',
                    'Dashboard or Booking Followups Pending Till Today — work overdue (red) first.',
                    [
                        ['label' => 'Overdue rows', 'text' => 'Past due date — handle before today\'s new items.'],
                        ['label' => 'Open booking from row', 'text' => '→ Followups tab → Take Follow-up after contact.'],
                        ['label' => 'Provider / Customer cards', 'text' => 'Shows next scheduled follow-up per party on booking details.'],
                    ],
                    'booking-followups-tab.png',
                ),
            ],
            'messages' => [
                self::wa(
                    'Day before (3+ days out) — customer',
                    "Reminder — Panun Kaergar service tomorrow {DATE_TIME}.\n📍 {ADDRESS}\nProvider: {PROVIDER_NAME}\n\nPlease confirm someone will be at the address.",
                    "Reminder — Panun Kaergar service tomorrow Thu 10 AM.\n📍 Lane 2 Rajbagh\nProvider: Ahmad Plumbing\n\nPlease confirm someone will be at the address.",
                ),
                self::wa(
                    '1 hour before — provider',
                    'Booking #{BOOKING_ID} — customer expecting you around {TIME}. Please reply when you reach the location.',
                    'Booking #BK-4421 — customer expecting you around 10 AM. Please reply when you reach the location.',
                    false,
                    'provider',
                ),
                self::wa(
                    'Provider cannot come — customer',
                    "Update on your booking #{BOOKING_ID}: your provider cannot make {DATE_TIME}. We are arranging {NEXT_STEP} and will confirm shortly.",
                    "Update on booking #BK-4421: your provider cannot make Thu 10 AM. We are arranging a new provider and will confirm within 30 minutes.",
                ),
            ],
            'remember' => [
                'Day-before only when service is 3+ days out',
                'Provider first on service day; log remarks every contact',
                'Bill breakdown in panel before Completed',
            ],
            'avoid' => ['Daily calls with no new info', 'Day-before call for tomorrow booking', 'Completing follow-up without next date'],
        ];
    }

    /** @return array<int, array{label: string, tabs: array<int, array{name: string, desc: string, tone: string}>}> */
    private static function listTabGroups(): array
    {
        return [
            [
                'label' => 'Main list tabs (filters)',
                'tabs' => [
                    ['name' => 'All Booking', 'desc' => 'Every booking — search by ID, phone, name', 'tone' => 'neutral'],
                    ['name' => 'Pending Booking', 'desc' => 'App booking waiting provider accept', 'tone' => 'pending'],
                    ['name' => 'Accepted', 'desc' => 'Provider assigned, visit not started', 'tone' => 'accepted'],
                    ['name' => 'Cancelled', 'desc' => 'All canceled bookings', 'tone' => 'canceled'],
                    ['name' => 'Ongoing', 'desc' => 'Provider on site / working', 'tone' => 'ongoing'],
                    ['name' => 'Completed', 'desc' => 'All completed (incl. special settlements)', 'tone' => 'completed'],
                ],
            ],
            [
                'label' => 'View more tabs',
                'tabs' => [
                    ['name' => 'Reopened', 'desc' => 'Open reopen ticket — action needed', 'tone' => 'reopen'],
                    ['name' => 'Resolved', 'desc' => 'Reopen closed without dispute', 'tone' => 'reopen'],
                    ['name' => 'Disputed and Cancelled', 'desc' => 'Dispute and close → canceled', 'tone' => 'dispute'],
                    ['name' => 'Disputed and Completed', 'desc' => 'Dispute and close → completed', 'tone' => 'dispute'],
                    ['name' => 'On hold', 'desc' => 'Paused — parts, reschedule', 'tone' => 'hold'],
                    ['name' => 'Hold after visit', 'desc' => 'Was Ongoing, then paused', 'tone' => 'hold'],
                    ['name' => 'Complete with no Service', 'desc' => 'Visit-only completion', 'tone' => 'settlement'],
                    ['name' => 'Cancel After Visit', 'desc' => 'Canceled after visit, visit fee kept', 'tone' => 'settlement'],
                    ['name' => 'Loss Making', 'desc' => 'Underpaid — loss pending', 'tone' => 'loss'],
                    ['name' => 'Loss recovered', 'desc' => 'Loss fully recovered', 'tone' => 'loss'],
                    ['name' => 'Settled', 'desc' => 'Loss written off', 'tone' => 'loss'],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideBookingStatuses(): array
    {
        return [
            'id' => 'booking-statuses',
            'title' => 'Booking statuses',
            'subtitle' => 'When to set each status on booking details',
            'type' => 'visual',
            'flowchart' => 'status-path',
            'card_groups' => [
                [
                    'title' => 'Main flow statuses',
                    'hint' => 'Set on booking details — list tabs filter by these.',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'hourglass_empty',
                            'title' => 'Pending',
                            'text' => 'App booking — provider not accepted.',
                            'color' => 'unknown',
                            'points' => ['Monitor Pending tab', 'Admin create usually → Accepted'],
                        ],
                        [
                            'icon' => 'check_circle',
                            'title' => 'Accepted',
                            'text' => 'Provider assigned — visit not started.',
                            'color' => 'customer',
                            'points' => ['Follow-ups + confirm parties', 'Next: Ongoing on service day'],
                        ],
                        [
                            'icon' => 'engineering',
                            'title' => 'Ongoing',
                            'text' => 'Provider on site / working.',
                            'color' => 'provider',
                            'points' => ['Add payments as customer pays', 'No Re Assign after this'],
                        ],
                    ],
                ],
                [
                    'title' => 'Pause, close, and exceptions',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'pause_circle',
                            'title' => 'On hold',
                            'text' => 'Paused — parts, reschedule.',
                            'color' => 'future',
                            'points' => ['Hold reason + schedule required', 'Back to Accepted/Ongoing'],
                        ],
                        [
                            'icon' => 'task_alt',
                            'title' => 'Completed',
                            'text' => 'Job closed — due zero.',
                            'color' => 'customer',
                            'points' => ['Normal or visit-only settlement', 'Then feedback slide'],
                        ],
                        [
                            'icon' => 'cancel',
                            'title' => 'Canceled',
                            'text' => 'Will not complete as booked.',
                            'color' => 'invalid',
                            'points' => ['Reason + responsible party', 'After visit → special scenario'],
                        ],
                    ],
                ],
                [
                    'title' => 'Plain cancel — before visit',
                    'hint' => 'Use status dropdown Cancel — not special scenarios.',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'person_off',
                            'title' => 'Customer cancel',
                            'text' => 'Customer changed mind before provider started.',
                            'color' => 'customer',
                            'points' => [
                                'Status → Canceled → customer cancel reason',
                                'WhatsApp provider — job cancelled',
                                'Cancel open follow-ups',
                            ],
                        ],
                        [
                            'icon' => 'engineering',
                            'title' => 'Provider cancel / no-show',
                            'text' => 'Provider cannot attend before visit.',
                            'color' => 'provider',
                            'points' => [
                                'Try Re Assign first if before service day',
                                'If canceling: provider cancel reason + remarks',
                                'Cancelled by Provider list tab for review',
                            ],
                        ],
                        [
                            'icon' => 'admin_panel_settings',
                            'title' => 'Admin cancel',
                            'text' => 'Duplicate, wrong service, customer unreachable.',
                            'color' => 'unknown',
                            'points' => [
                                'Pick admin cancel reason from config',
                                'Facts-only remarks — who decided what',
                                'Never plain Cancel after visit — use special scenario',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Pending cancellation — admin review',
                    'hint' => 'Provider or customer requested cancel in app — you approve or deny.',
                    'layout' => 'row-2',
                    'cards' => [
                        [
                            'icon' => 'pending',
                            'title' => 'What it means',
                            'text' => 'Status shows Pending cancellation — request waiting your decision.',
                            'color' => 'future',
                            'points' => [
                                'Booking details banner — review who requested + reason',
                                'Call both parties if facts unclear',
                                'Approve → Canceled · Deny → back to previous status',
                            ],
                        ],
                        [
                            'icon' => 'view_list',
                            'title' => 'List tabs',
                            'text' => 'Find these quickly at shift start.',
                            'color' => 'provider',
                            'points' => [
                                'Cancelled by Provider tab — withdrawals needing Re Assign',
                                'Cancelled by Customer tab — review pattern',
                                'Re Assign allowed while Pending cancellation if replacing provider',
                            ],
                        ],
                    ],
                ],
            ],
            'ui_maps' => [
                self::panelMap(
                    'Cancel modal — before visit',
                    'Booking details → Change status → Canceled. Not available after visit or on open reopen tickets.',
                    [
                        ['label' => 'Cancel reason *', 'text' => 'Pick from Booking Configuration — customer, provider, or admin reason lists.'],
                        ['label' => 'Responsible party', 'text' => 'Who caused the cancel — affects reporting. Example: Customer — changed schedule.'],
                        ['label' => 'Remarks', 'text' => 'Facts only — what was said on call, attempt to reassign if applicable.'],
                        ['label' => 'WhatsApp', 'text' => 'Tell the other party within 15 minutes — customer or provider.'],
                        ['label' => 'After visit', 'text' => 'If provider already visited → stop — use Configure special scenarios instead.'],
                    ],
                    'booking-dispute-close-modal.png',
                ),
            ],
            'tab_groups' => self::listTabGroups(),
            'remember' => [
                'List tabs find bookings — status dropdown on details changes them',
                'Plain Cancel = before visit · special scenario = after visit',
                'Pending cancellation needs approve/deny — do not ignore the banner',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideSpecialScenarios(): array
    {
        return [
            'id' => 'special-scenarios',
            'title' => 'Configure special scenarios',
            'subtitle' => 'What · When · How — after the provider has visited',
            'type' => 'visual',
            'flowchart' => 'special-scenario',
            'note' => 'This is **not** the normal Cancel dropdown. Open it only when status is **Ongoing** or **Hold after visit** and the visit already happened (or job is in an unusual financial state).',
            'important' => 'Consult **management** before making changes — same rule as Dispute and close.',
            'card_groups' => [
                [
                    'title' => 'Three scenarios — pick exactly one',
                    'hint' => 'Read the situation on the call first. Wrong scenario = wrong ledger and angry provider.',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'money_off',
                            'title' => '① Cancel After Visit',
                            'text' => 'Provider came but the booked job cannot be done.',
                            'color' => 'invalid',
                            'points' => [
                                'WHEN: parts missing, unsafe, customer refuses, no-fix',
                                'CHARGE: visit fee (+ optional closing)',
                                'FINISH: **Save and cancel** → Cancel After Visit',
                            ],
                        ],
                        [
                            'icon' => 'build_circle',
                            'title' => '② Complete visit only',
                            'text' => 'Real visit happened but almost no billable repair work.',
                            'color' => 'future',
                            'points' => [
                                'WHEN: diagnostic, reset, no fault found',
                                'CHARGE: visit fee — closing often ₹0',
                                'FINISH: **Save and complete** → Complete with no Service',
                            ],
                        ],
                        [
                            'icon' => 'trending_down',
                            'title' => '③ Loss making',
                            'text' => 'Customer paid less than the full invoice — shortfall must be split.',
                            'color' => 'unknown',
                            'points' => [
                                'WHEN: partial payment or agreed underpay',
                                'SPLIT: company + provider loss = gap',
                                'FINISH: **Save and complete** → Loss Making tab',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Rules that prevent mistakes',
                    'hint' => 'Same modal for all three — these checks apply every time.',
                    'tone' => 'warn',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'preview',
                            'title' => 'Watch the Preview panel',
                            'text' => 'It updates live as you type.',
                            'color' => 'source',
                            'points' => [
                                'Due Balance must hit **0.00** before save',
                                'Company share + provider share must match policy',
                                'If “Still due from customer” — use embedded Add payment in the same modal',
                            ],
                        ],
                        [
                            'icon' => 'payments',
                            'title' => 'Collect before you save',
                            'text' => 'Do not save with money still owed.',
                            'color' => 'customer',
                            'points' => [
                                'Cash on site → Received by **Provider** or **Company** — match reality',
                                'Digital → transaction ID required when received by company',
                                'Loss-making recovery payments later use special split fields',
                            ],
                        ],
                        [
                            'icon' => 'block',
                            'title' => 'Do not use plain Cancel',
                            'text' => 'After a visit, normal Cancel is usually wrong.',
                            'color' => 'invalid',
                            'points' => [
                                'Provider visited → use this modal, not status dropdown Cancel',
                                'Cancel After Visit needs **Cancellation Reason** + Save and cancel',
                                'Plain cancel leaves visit fee and ledger inconsistent',
                            ],
                        ],
                    ],
                ],
            ],
            'ui_maps' => [
                self::panelMap(
                    'Configure special scenarios — open modal',
                    'Booking details (Ongoing or Hold after visit) → Configure special scenarios. Title: “Special financial settlement”.',
                    [
                        ['label' => 'Pick scenario (radio) *', 'text' => '① Cancel After Visit ② Complete visit only (little/no service) ③ Loss making (customer underpaid). Only one applies.'],
                        ['label' => 'Preview panel', 'text' => 'Updates live as you type — check Due Balance and company/provider shares before save.'],
                    ],
                    'booking-special-scenario-overview.png',
                ),
                self::panelMap(
                    'Scenario: Cancel After Visit — fields to fill',
                    'Provider visited but job cannot complete. Ends with Save and cancel (not plain Cancel dropdown).',
                    [
                        ['label' => 'Visiting charges to be paid by customer (₹) *', 'text' => 'Visit/call-out fee customer owes. Example: ₹300 visit charge only.'],
                        ['label' => 'Company share of visit fee (₹)', 'text' => 'Auto from tier — verify matches policy. Example: ₹90 company / ₹210 provider.'],
                        ['label' => 'Provider share of visit fee (₹)', 'text' => 'Must pair with company share. Provider must agree on call.'],
                        ['label' => 'Closing amount (optional)', 'text' => 'Only if any billable work done before cancel. Usually ₹0 for pure no-fix visit.'],
                        ['label' => 'Collect payment in modal', 'text' => 'If Preview shows amount due — use embedded Add payment until Due = 0.'],
                        ['label' => 'Cancellation Reason *', 'text' => 'Required for Save and cancel. Pick reason matching situation (parts missing, customer refused, etc.).'],
                        ['label' => 'Save and cancel', 'text' => 'Enabled when due zero + cancellation reason set. Tags: Cancel After Visit.'],
                    ],
                    'booking-special-scenario-cancel.png',
                ),
                self::panelMap(
                    'Scenario: Complete visit only — fields to fill',
                    'Real visit but minimal billable service (diagnostic, tighten, no fault found).',
                    [
                        ['label' => 'Visiting charges to be paid by customer (₹) *', 'text' => 'Main fee customer pays. Example: ₹400 diagnostic visit.'],
                        ['label' => 'Company / Provider share on visit', 'text' => 'Verify split on preview — both must be correct for ledger.'],
                        ['label' => 'Closing amount (if any)', 'text' => 'Small extra labour if any — often leave ₹0.'],
                        ['label' => 'Notes', 'text' => 'Optional — what was done. Example: “Reset MCB, no fault found, advised replacement.”'],
                        ['label' => 'Save and complete', 'text' => 'After due = 0. Tags: Complete with no Service.'],
                    ],
                    'booking-special-scenario-complete-visit.png',
                ),
                self::panelMap(
                    'Scenario: Loss making — fields to fill',
                    'Customer paid less than full invoice. Due Balance > 0 but job closed as underpaid.',
                    [
                        ['label' => 'Amount paid by customer (₹) *', 'text' => 'What customer actually paid total. Example: ₹800 of ₹1200 invoice.'],
                        ['label' => 'Loss amount shared by company (₹) *', 'text' => 'Company absorbs part of shortfall. Must sum with provider loss = total loss.'],
                        ['label' => 'Loss amount shared by provider (₹) *', 'text' => 'Provider absorbs remainder. Agree split on call before save.'],
                        ['label' => 'Save', 'text' => 'Saves loss-making config. Later recovery payments use special split fields.'],
                        ['label' => 'Save and complete', 'text' => 'When due handled per preview. Track on Loss Making tab until recovered/settled.'],
                    ],
                    'booking-special-scenario-loss-making.png',
                ),
                self::panelMap(
                    'Embedded payment inside settlement modal',
                    'When Preview shows “Still due from customer” — collect before Save and cancel/complete.',
                    [
                        ['label' => 'Amount *', 'text' => 'Up to remaining due. Example: ₹300 visit fee customer pays cash.'],
                        ['label' => 'Received by *', 'text' => 'Provider (cash to provider) or Company (UPI/cash to office).'],
                        ['label' => 'Payment method + reference', 'text' => 'If Company — method + transaction ID. Reference Note optional.'],
                        ['label' => 'Loss recovery split', 'text' => 'Loss-making only — Provider loss recovery + Company loss recovery must equal payment amount.'],
                    ],
                    'booking-add-payment-modal.png',
                ),
            ],
            'remember' => [
                'One scenario only — radio buttons, not a mix',
                'Provider must agree to visit fee and loss split on the call before save',
                'Save and cancel vs Save and complete — different tags and list tabs',
            ],
            'avoid' => [
                'Plain Cancel from Ongoing when provider already visited',
                'Saving while Preview still shows due balance',
                'Guessing company/provider share without reading Preview',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideDisputes(): array
    {
        return [
            'id' => 'disputes',
            'title' => 'Disputed bookings',
            'subtitle' => 'Refund split · facts-only close · no second chances',
            'type' => 'visual',
            'flowchart' => 'dispute-close',
            'note' => 'Use **Dispute and close** only when Add payment or special scenarios cannot fix it — billing fight, reopened completed booking, or Cancel is blocked. After close: **no more payments**, **no reopen**.',
            'important' => 'Consult **management** before making changes. Call the **provider first** — paid totals and agreed refund before opening the modal.',
            'card_groups' => [
                [
                    'title' => 'When this is the right tool',
                    'hint' => 'Not for small adjustments — try normal payment fixes first.',
                    'tone' => 'warn',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'replay',
                            'title' => 'Reopened booking stuck',
                            'text' => 'Completed job reopened — cannot resolve normally.',
                            'color' => 'unknown',
                            'points' => [
                                'Complaint after Completed',
                                'Plain Cancel may be blocked',
                                'Need refund split, not another visit',
                            ],
                        ],
                        [
                            'icon' => 'receipt_long',
                            'title' => 'Billing disagreement',
                            'text' => 'Paid totals do not match the story.',
                            'color' => 'invalid',
                            'points' => [
                                'Customer disputes parts or labour',
                                'Provider vs customer paid amount differs',
                                'Agreed partial refund — split pools',
                            ],
                        ],
                        [
                            'icon' => 'supervisor_account',
                            'title' => 'Consult management first',
                            'text' => 'Same rule as special scenarios.',
                            'color' => 'provider',
                            'points' => [
                                'Get approval before Dispute and close',
                                'Facts on calls — not opinions in remarks',
                                'Not for everyday “₹50 off” requests',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Inside the modal — key fields',
                    'hint' => 'Verify read-only paid summary at top before typing refunds.',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'account_balance',
                            'title' => 'Company pool refund',
                            'text' => 'Office-collected money returned.',
                            'color' => 'customer',
                            'points' => [
                                'Refund from company pool (₹) *',
                                'Transaction ID when refund > 0',
                                'Final service/parts retained — optional',
                            ],
                        ],
                        [
                            'icon' => 'engineering',
                            'title' => 'Provider pool refund',
                            'text' => 'Provider-collected money returned.',
                            'color' => 'provider',
                            'points' => [
                                'Refund from provider pool (₹) *',
                                'Reference when provider refund > 0',
                                'Combined refunds ≤ customer paid',
                            ],
                        ],
                        [
                            'icon' => 'lock',
                            'title' => 'Apply and close',
                            'text' => 'Permanent snapshot — cannot undo.',
                            'color' => 'invalid',
                            'points' => [
                                'Resolve remarks * — facts only',
                                'Apply Refund and close',
                                'Disputed and Completed / Cancelled',
                            ],
                        ],
                    ],
                ],
            ],
            'ui_maps' => [
                self::panelMap(
                    'Dispute and close — when button appears',
                    'Booking details → Dispute and close (header or status area). For Ongoing, Hold after visit, or open reopen tickets where plain Cancel is blocked.',
                    [
                        ['label' => 'Before opening', 'text' => 'Gather facts: what customer paid, what provider collected, disagreement details. Provider call first.'],
                        ['label' => 'Paid summary (read-only top)', 'text' => 'Customer paid total, Collected by company, Collected by provider — verify numbers match your notes.'],
                    ],
                    'booking-dispute-button-area.png',
                ),
                self::panelMap(
                    'Dispute and close modal — fields to fill',
                    'Modal title: “Dispute and close”. Submit: Apply Refund and close. Facts-only remarks.',
                    [
                        ['label' => 'Dispute reason *', 'text' => 'Pick from configured list (Booking Configuration → Dispute reasons). Each has responsible party. Example: Billing disagreement — customer.'],
                        ['label' => 'Refund paid from company pool (₹) *', 'text' => 'Amount refunded from company’s collected share. Pre-filled — adjust to actual refund. Example: ₹200.'],
                        ['label' => 'Reference / transaction ID (company leg) *', 'text' => 'Required when company refund > 0. Example: UPI ref 123456789.'],
                        ['label' => 'Refund paid from provider pool (₹) *', 'text' => 'Amount refunded from provider’s collected share. Combined refunds ≤ customer paid.'],
                        ['label' => 'Reference / transaction ID (provider leg) *', 'text' => 'Required when provider refund > 0.'],
                        ['label' => 'Final Services Charges retained from customer', 'text' => 'Optional edit — how much of service charge customer keeps paying after dispute split. Commission fields update live.'],
                        ['label' => 'Final spare parts charges retained', 'text' => 'Optional — parts portion customer still pays. Split admin/provider earning shown read-only.'],
                        ['label' => 'Resolve remarks *', 'text' => 'Required — facts only, max 5000 chars. Example: “Customer paid ₹950, disputed ₹200 parts — agreed ₹150 refund from company pool per call 3 Aug.”'],
                        ['label' => 'Apply Refund and close', 'text' => 'Confirms dialog → booking becomes Disputed and Completed or Disputed and Cancelled. No more payments or reopen.'],
                    ],
                    'booking-dispute-close-modal.png',
                ),
                self::panelMap(
                    'Manager setup — Booking Configuration',
                    'Configure reasons before staff use Dispute and close.',
                    [
                        ['label' => 'Dispute reasons', 'text' => 'Each reason + responsible party: customer, provider, staff, no one.'],
                        ['label' => 'Admin cancel reasons', 'text' => 'Used with normal cancel and special scenario Save and cancel.'],
                        ['label' => 'Hold reasons + Reopen reasons', 'text' => 'On hold pauses job; reopen reasons when completed booking reopened.'],
                    ],
                ),
            ],
            'remember' => [
                'Consult management before Dispute and close',
                'Provider call first — paid totals and agreed refund',
                'Company + provider refunds ≤ customer paid total',
            ],
            'avoid' => [
                'Dispute for amounts fixable with Add payment',
                'Emotional remarks in Resolve remarks',
                'Opening modal without management approval',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slidePayments(): array
    {
        return [
            'id' => 'payments',
            'title' => 'Managing booking payments',
            'subtitle' => 'Customer side + provider share',
            'type' => 'visual',
            'important' => 'Due Balance must reach **₹0.00** before Completed.',
            'card_groups' => [
                [
                    'title' => 'Bill breakdown — get from provider call',
                    'hint' => 'Enter every line before customer confirm and Completed.',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'receipt',
                            'title' => 'Total bill',
                            'text' => 'Final amount customer pays.',
                            'color' => 'customer',
                            'points' => ['Must equal service + all parts', 'Matches Due Balance when paid in full'],
                        ],
                        [
                            'icon' => 'handyman',
                            'title' => 'Service / labour charge',
                            'text' => 'Work fee separate from parts.',
                            'color' => 'provider',
                            'points' => ['Diagnostic, repair labour, visit fee if applicable'],
                        ],
                        [
                            'icon' => 'inventory_2',
                            'title' => 'Parts — each line',
                            'text' => 'Name + charge per item.',
                            'color' => 'provider',
                            'points' => ['Example: MCB 32A — ₹350', 'No “misc parts ₹500” without detail'],
                        ],
                    ],
                ],
            ],
            'ui_maps' => [
                self::panelMap(
                    'Payments section — Add payment modal',
                    'Booking details → Payments → Add payment. Use during Ongoing or loss-making recovery.',
                    [
                        ['label' => 'Amount *', 'text' => 'Cannot exceed Due amount shown. Example: customer pays ₹500 cash of ₹950 due.'],
                        ['label' => 'Received by *', 'text' => 'Provider — cash given to provider on site. Company — paid to office/UPI. Pick who actually received.'],
                        ['label' => 'Advance payment method * (if Company)', 'text' => 'Cash After Service / Digital / offline sub-options — match how customer paid.'],
                        ['label' => 'Transaction ID / method fields', 'text' => 'Required for digital payments. Example: UPI ref, last 4 digits, etc.'],
                        ['label' => 'Reference Note', 'text' => 'Optional internal note. Example: “Cash collected by provider Ahmad on site.”'],
                        ['label' => 'Date', 'text' => 'Defaults today — change if backdating allowed.'],
                        ['label' => 'Loss recovery split (loss-making only)', 'text' => 'Provider loss recovery + Company loss recovery must equal Amount.'],
                        ['label' => 'Save', 'text' => 'Due Balance decreases — repeat until 0.00 before Completed.'],
                    ],
                    'booking-add-payment-modal.png',
                ),
                self::panelMap(
                    'Advance payment on Create Booking form',
                    'Optional block at bottom of create form — only if customer paid before service.',
                    [
                        ['label' => 'Advance Paid Amount', 'text' => 'Example: ₹100 booking fee collected on qualification call.'],
                        ['label' => 'Advance payment method *', 'text' => 'Required when amount > 0 — same method options as Add payment.'],
                        ['label' => 'Transaction reference', 'text' => 'If digital — enter ID. Shows in payment history after create.'],
                    ],
                    'booking-create-form-advance.png',
                ),
                self::panelMap(
                    'Bill lines on booking (before Completed)',
                    'Enter service + parts on booking cart/invoice — match provider call breakdown.',
                    [
                        ['label' => 'Service / labour line', 'text' => 'Example: Plumbing repair labour — ₹400.'],
                        ['label' => 'Parts lines (each item)', 'text' => 'Example: MCB 32A — ₹350; Tap washer set — ₹80. No lump “parts ₹500”.'],
                        ['label' => 'Due Balance', 'text' => 'Total invoice minus payments. Must hit 0 before Completed.'],
                        ['label' => 'Customer confirm call', 'text' => 'Read back every line — fix panel before close if customer disagrees.'],
                    ],
                    'booking-details-payments.png',
                ),
            ],
            'messages' => [
                self::wa(
                    'Bill confirm — customer (after provider call)',
                    "Quick confirm for booking #{BOOKING_ID}:\n\n"
                    ."Provider total: ₹{TOTAL}\n• Service/labour: ₹{SERVICE}\n• Parts: {PARTS_LIST}\n\n"
                    .'Does this match what you agreed to pay? Reply YES or call us if not.',
                    "Quick confirm for booking #BK-4421:\n\n"
                    ."Provider total: ₹950\n• Service/labour: ₹400\n• Parts: MCB 32A ₹350, labour ₹200\n\n"
                    .'Does this match what you agreed to pay? Reply YES or call us if not.',
                ),
            ],
            'remember' => [
                'Due Balance must reach zero before Completed',
                'Who received cash — company or provider',
                'Itemized bill — service + each part line',
            ],
            'avoid' => ['Lump “parts ₹500” without detail', 'Completed with due still showing', 'Customer confirm skipped'],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideFeedback(): array
    {
        return [
            'id' => 'feedback',
            'title' => 'Feedback on bookings',
            'subtitle' => 'After the job is closed',
            'type' => 'visual',
            'card_groups' => [
                [
                    'title' => 'After close — call order',
                    'hint' => 'Provider first — you need facts before the customer call.',
                    'layout' => 'row-3',
                    'cards' => [
                        [
                            'icon' => 'engineering',
                            'title' => '1 — Provider',
                            'text' => 'Bill breakdown + job done.',
                            'color' => 'provider',
                            'points' => ['Enter bill in panel first', 'Total, service, each part'],
                        ],
                        [
                            'icon' => 'person',
                            'title' => '2 — Customer',
                            'text' => 'Satisfied? Paid correctly?',
                            'color' => 'customer',
                            'points' => ['Matches panel amounts', 'Note complaints for tags'],
                        ],
                        [
                            'icon' => 'star',
                            'title' => '3 — Reviews',
                            'text' => 'Approve public reviews.',
                            'color' => 'future',
                            'points' => ['Booking Review list', 'No-show / absent tags if needed'],
                        ],
                    ],
                ],
            ],
            'remember' => ['Provider call before customer call', 'Approve pending reviews after close'],
            'avoid' => ['Customer call before bill is in panel', 'Ignoring no-show for provider score'],
        ];
    }

    /** @return array<string, mixed> */
    private static function slidePaymentChecklist(): array
    {
        return [
            'id' => 'payment-checklist',
            'title' => 'Payment & close checklist',
            'subtitle' => 'Before Completed or Save on special scenario',
            'type' => 'checklist',
            'items' => array_merge(
                array_map(
                    fn (array $item) => [
                        'title' => $item['title'],
                        'body' => $item['body'],
                        'details' => [$item['body']],
                    ],
                    WorkflowStepDefinitions::trainingChecklistItems('booking.close')
                ),
                [
                [
                    'title' => 'Bill breakdown in panel',
                    'body' => 'Service charge + each part entered with name and amount — matches provider invoice.',
                    'details' => [
                        'Provider called for itemized bill before close',
                        'Total = service charges + sum of parts (no mystery amounts)',
                        'Customer confirmed amount on call matches panel',
                    ],
                ],
                [
                    'title' => 'Customer payments',
                    'body' => 'Every rupee the customer paid is recorded in partial payments.',
                    'details' => [
                        'Due Balance on booking details shows 0.00',
                        'Payment method and transaction ID filled where required',
                        'Advance at create appears in payment history',
                        'Loss recovery payments split correctly if loss making',
                    ],
                ],
                [
                    'title' => 'Settlement lines (special scenarios)',
                    'body' => 'Visit and closing amounts match what customer agreed on call.',
                    'details' => [
                        'Visiting charges line — company vs provider share correct',
                        'Closing amount line (if any) — tiers applied correctly',
                        'Preview matched what you saved',
                    ],
                ],
                [
                    'title' => 'Provider side',
                    'body' => 'Provider knows final amount and their share.',
                    'details' => [
                        'Provider called for job details before close',
                        'Provider share on settlement reflects visit + service lines',
                        'No open disagreement — escalate to dispute if yes',
                    ],
                ],
                [
                    'title' => 'Status & tags',
                    'body' => 'Right close path used.',
                    'details' => [
                        'Normal job → Completed',
                        'Visit only → Save and complete (Complete with no Service tag)',
                        'Cancel after visit → Save and cancel (Cancel After Visit tag)',
                        'Underpaid → Loss making tag and track recovery',
                    ],
                ],
                [
                    'title' => 'After close',
                    'body' => 'Finish the loop.',
                    'details' => [
                        'Open follow-ups cancelled automatically',
                        'Approve pending reviews when they appear',
                        'File No-show or performance feedback if applicable',
                    ],
                ],
            ]),
        ];
    }

    /** @return array<string, mixed> */
    private static function slideQuiz(): array
    {
        return [
            'id' => 'quiz',
            'title' => 'Expert check',
            'subtitle' => 'Bookings — full process',
            'type' => 'quiz',
            'questions' => [
                [
                    'id' => 'q1',
                    'question' => 'Which is NOT a valid prerequisite before Create Booking?',
                    'options' => [
                        'Full address with zone',
                        'Customer phone in panel',
                        'Lead still type Unknown',
                        'Service schedule set',
                    ],
                    'correct' => 2,
                    'explain' => 'Qualify the lead first — Unknown means you do not know what they need yet.',
                ],
                [
                    'id' => 'q2',
                    'question' => 'Admin creates booking from lead. Starting status?',
                    'options' => ['Pending', 'Accepted', 'Ongoing', 'Completed'],
                    'correct' => 1,
                    'explain' => 'Admin store sets Accepted. Pending is for app customer bookings awaiting provider.',
                ],
                [
                    'id' => 'q3',
                    'question' => 'Provider not available day before service — still Accepted. Best action?',
                    'options' => [
                        'Wait until service day',
                        'Re Assign provider + WhatsApp customer',
                        'Mark Completed',
                        'Delete booking',
                    ],
                    'correct' => 1,
                    'explain' => 'Re Assign works before Ongoing. Notify customer same day.',
                ],
                [
                    'id' => 'q4',
                    'question' => 'When can you set Ongoing?',
                    'options' => [
                        'Any time after create',
                        'When provider starts on or after scheduled service date',
                        'Only after Completed',
                        'Never — provider sets it',
                    ],
                    'correct' => 1,
                    'explain' => 'Ongoing = provider actively on the job, on/after service day.',
                ],
                [
                    'id' => 'q5',
                    'question' => 'Provider visited but parts missing — cannot fix. Close how?',
                    'options' => [
                        'Status → Canceled (plain)',
                        'Configure special scenarios → Cancel After Visit → Save and cancel',
                        'Status → Completed',
                        'Ignore',
                    ],
                    'correct' => 1,
                    'explain' => 'After visit requires settlement scenario, not plain cancel from Ongoing.',
                ],
                [
                    'id' => 'q6',
                    'question' => 'Due Balance still ₹200. Can you mark Completed?',
                    'options' => ['Yes', 'No — record payment or loss making scenario first', 'Yes if provider agrees', 'Delete due'],
                    'correct' => 1,
                    'explain' => 'Due must reach zero (or loss making settlement saved).',
                ],
                [
                    'id' => 'q7',
                    'question' => 'Reopened booking — customer wants cancel. Which action?',
                    'options' => ['Plain Cancel', 'Dispute and close', 'Delete', 'On hold forever'],
                    'correct' => 1,
                    'explain' => 'Reopened tickets block plain cancel.',
                ],
                [
                    'id' => 'q8',
                    'question' => 'Where do loss-making bookings with remaining shortfall appear?',
                    'options' => ['Completed tab only', 'Loss Making (View more)', 'Pending', 'Web Bookings'],
                    'correct' => 1,
                    'explain' => 'Loss Making tab until recovered or settled.',
                ],
                [
                    'id' => 'q9',
                    'question' => 'Open booking — you complete a follow-up. System requires next date. Why?',
                    'options' => [
                        'Bug',
                        'Mandatory next follow-up until Completed/Canceled/Refunded',
                        'Only Mondays',
                        'Manager setting',
                    ],
                    'correct' => 1,
                    'explain' => 'requiresMandatoryNextFollowup() on all open bookings.',
                ],
                [
                    'id' => 'q10',
                    'question' => 'Correct order for post-job feedback calls?',
                    'options' => [
                        'Customer first, then provider',
                        'Provider first (job facts), then customer (confirm)',
                        'WhatsApp only',
                        'No calls needed',
                    ],
                    'correct' => 1,
                    'explain' => 'Provider first so you know what was done before asking customer.',
                ],
                [
                    'id' => 'q11',
                    'question' => 'Customer booked via mobile app — booking shows Pending. What is your first check?',
                    'options' => [
                        'Create a new booking from the lead',
                        'Open Pending tab — see if provider accepted; call provider or Re Assign if stuck',
                        'Mark Completed',
                        'Delete the row',
                    ],
                    'correct' => 1,
                    'explain' => 'App bookings start Pending until provider accepts. Monitor the Pending tab — intervene with Re Assign + customer WA if needed.',
                ],
                [
                    'id' => 'q12',
                    'question' => 'Booking shows Pending cancellation. What must admin do?',
                    'options' => [
                        'Nothing — it closes automatically',
                        'Review request on booking details — approve or deny after confirming facts',
                        'Always approve immediately',
                        'Use Dispute and close',
                    ],
                    'correct' => 1,
                    'explain' => 'Pending cancellation is a review queue — approve moves to Canceled, deny restores previous status.',
                ],
            ],
        ];
    }
}
