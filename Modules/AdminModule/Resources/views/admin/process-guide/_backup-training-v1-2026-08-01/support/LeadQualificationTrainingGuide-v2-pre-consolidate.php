<?php

namespace Modules\AdminModule\Support;

class LeadQualificationTrainingGuide
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function slides(): array
    {
        $slides = [
            self::slideTitle(),
            self::slideMissionAndWaRule(),
            self::slideJourneyAndOutcomes(),
            self::slideAlwaysDo(),
            self::slideStep1(),
            self::slideStep2(),
            self::slideStep3Combined(),
            self::slideStep4PathA(),
            self::slideStep4PathB(),
            self::slideStep5(),
            self::slideRolePlayBatch1(),
            self::slideRolePlayBatch2(),
            self::slideRulesAndShift(),
            self::slideQuiz(),
            self::slideClosing(),
        ];

        foreach ($slides as $i => &$slide) {
            $slide['number'] = $i + 1;
        }

        return $slides;
    }

    /** @return array{mandatory: bool, label: string, template: string, example: string} */
    private static function wa(string $label, string $template, string $example, bool $mandatory = true): array
    {
        return [
            'mandatory' => $mandatory,
            'label' => $label,
            'template' => $template,
            'example' => $example,
        ];
    }

    /** @return array<string, mixed> */
    private static function slideTitle(): array
    {
        return [
            'id' => 'title',
            'title' => 'Lead Handling Training',
            'subtitle' => 'Panun Kaergar — Home Service Provider of Kashmir',
            'tagline' => 'Every call and message shapes how Kashmir sees Panun Kaergar.',
            'type' => 'title',
            'footer' => 'For call centre, lead qualifiers & admin panel staff',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideMissionAndWaRule(): array
    {
        return [
            'id' => 'mission-wa',
            'title' => 'Your mission + #1 rule: WhatsApp after every contact',
            'subtitle' => 'You are Panun Kaergar to the user — not just a data entry agent',
            'type' => 'playbook',
            'important' => 'MANDATORY: After EVERY call or live discussion with a user, send a WhatsApp follow-up message immediately — at the same time, before moving to the next lead. No exceptions.',
            'steps' => [
                [
                    'title' => 'How you should sound',
                    'do' => [
                        'Respond fast — phone leads within minutes.',
                        'Use their name. Confirm what they need.',
                        'Never leave a lead without remarks + follow-up OR closed outcome.',
                        'Even invalid/cancelled leads get a polite WhatsApp close.',
                    ],
                ],
                [
                    'title' => 'Universal WhatsApp after any call/discussion',
                    'goal' => 'Send before you hang up the panel tab or take the next call',
                    'message' => self::wa(
                        'Mandatory follow-up — general format (adapt to situation)',
                        "As per our discussion over call — {summary of request/agreement}. Next step: {what Panun Kaergar will do next}.",
                        'As per our discussion over call — you enquired about home services with Panun Kaergar. Next step: we will call you shortly to confirm details.',
                    ),
                    'warning' => 'Do NOT finish a call and update only the panel. WhatsApp + panel must both happen in the same minute.',
                    'panel' => ['Remark: “Call done — WA sent [time]”', 'Follow-up date if waiting on user/provider'],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideJourneyAndOutcomes(): array
    {
        return [
            'id' => 'journey',
            'title' => 'Start → End — full journey & all outcomes',
            'type' => 'end-map',
            'flowchart' => 'master-journey',
            'intro' => 'Initial state: lead arrives. You move it to exactly ONE end state — never leave it open.',
            'start' => 'Lead arrives → source → classify → follow path → WhatsApp after each contact → panel update',
            'success' => [
                ['label' => '✓ Customer booking confirmed', 'body' => '₹100, booking created, customer & provider WhatsApp sent.'],
                ['label' => '✓ Provider registered', 'body' => 'On panel + WhatsApp groups.'],
            ],
            'nurture' => [
                ['label' => '◐ Future customer', 'body' => 'PK explained, referral ask, WhatsApp sent, optional follow-up.'],
            ],
            'closure' => [
                ['label' => '✕ Customer cancelled', 'body' => 'WhatsApp close + remarks.'],
                ['label' => '✕ Provider cancelled', 'body' => '3 follow-ups failed — documented.'],
                ['label' => '✕ Invalid lead', 'body' => 'Polite WhatsApp + reason in panel.'],
                ['label' => '✕ No provider match', 'body' => 'Customer informed by call + WhatsApp before cancel.'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideAlwaysDo(): array
    {
        return [
            'id' => 'always-do',
            'title' => 'On every lead — 5 non-negotiables',
            'type' => 'checklist',
            'important' => 'Rule #2 below applies after EVERY phone call, conference call, or live chat discussion.',
            'items' => [
                ['title' => '1. Record the source', 'body' => 'Facebook, Instagram, phone number, AI chat, website — first action.'],
                ['title' => '2. WhatsApp immediately after every discussion', 'body' => 'Same time as the call ends — summary + next step. Never skip.'],
                ['title' => '3. Classify in the first contact', 'body' => 'Customer / Provider / Future / Invalid / Unknown — one path only.'],
                ['title' => '4. Update panel after every touch', 'body' => 'Remarks + follow-up date OR closed outcome.'],
                ['title' => '5. Tell the user what happens next', 'body' => 'On call AND in the WhatsApp message.'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideStep1(): array
    {
        return [
            'id' => 'step-1',
            'title' => 'Step 1 — Lead arrives (complete)',
            'subtitle' => 'Then → Step 2 classify on next slide',
            'type' => 'playbook',
            'flowchart' => 'lead-arrival',
            'steps' => [
                [
                    'title' => 'A) Social media — Facebook / Instagram',
                    'do' => ['Reply warmly.', 'Collect name, phone, service, area, date/time.', 'Create lead manually.'],
                    'say' => '“Thank you for contacting Panun Kaergar! May I have your name, phone, and what service you need?”',
                    'message' => self::wa(
                        'WhatsApp after first reply (if discussion happened on chat)',
                        'Thank you for contacting Panun Kaergar — Home Service Provider of Kashmir. As discussed, we noted your request for {service}. We will call you shortly to confirm details.',
                        'Thank you for contacting Panun Kaergar — Home Service Provider of Kashmir. As discussed, we noted your request for plumbing. We will call you shortly to confirm details.',
                    ),
                    'panel' => ['Source = social', 'All details in remarks'],
                    'next' => '→ Step 2',
                ],
                [
                    'title' => 'B) Direct calls',
                    'do' => ['Answer: “Panun Kaergar, how can I help you?”', 'If missed — call back ASAP.', 'Create lead with full notes.'],
                    'warning' => 'Phone leads are hottest — handle before old follow-ups.',
                    'message' => self::wa(
                        'Mandatory WhatsApp after every answered or attempted call',
                        'We tried calling you regarding your enquiry with Panun Kaergar (Home service provider of Kashmir). If you need any help or any service feel free to call us.',
                        'We tried calling you regarding your enquiry with Panun Kaergar (Home service provider of Kashmir). If you need any help or any service feel free to call us.',
                    ),
                    'next' => '→ Step 2',
                ],
                [
                    'title' => 'C) Auto-created in panel',
                    'do' => ['Verify name, phone, service, address.', 'Call/WhatsApp if incomplete.', 'Classify after confirming intent.'],
                    'message' => self::wa(
                        'After verification call',
                        'As per our discussion over call — we received your enquiry via {website/app/AI chat}. We confirmed: {summary}. Next step: {classify path}.',
                        'As per our discussion over call — we received your website booking for electrical repair in Hyderpora. Next step: we are assigning a partner and will update you shortly.',
                    ),
                    'next' => '→ Step 2',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideStep2(): array
    {
        return [
            'id' => 'step-2',
            'title' => 'Step 2 — Classify immediately (complete)',
            'subtitle' => 'Pick ONE type → follow that path only',
            'type' => 'playbook',
            'flowchart' => 'classify',
            'steps' => [
                [
                    'title' => 'Customer — needs home service',
                    'do' => ['Confirm service, area, date/time.', 'Mark customer lead.'],
                    'message' => self::wa(
                        'WhatsApp after classification call',
                        'As per our discussion over call you need this service — {service}, {address}, {date/time}. We will look for a partner available to do the work for that time.',
                        'As per our discussion over call you need this service — Plumbing (leaking tap), Rajbagh Srinagar, Saturday 2 Aug 10 AM. We will look for a partner available to do the work for that time.',
                    ),
                    'next' => '→ Step 4 (next slides)',
                ],
                [
                    'title' => 'Provider — wants to join',
                    'do' => ['Confirm partner intent.', 'Mark provider lead.'],
                    'message' => self::wa(
                        'WhatsApp after first provider call',
                        'As per our discussion over call — thank you for your interest in joining Panun Kaergar. Next step: {agreement/docs call time}. We will send details shortly.',
                        'As per our discussion over call — thank you for your interest in joining Panun Kaergar as a plumbing partner. Next step: we will send the agreement and document list on WhatsApp today.',
                    ),
                    'next' => '→ Step 5 (next slides)',
                ],
                [
                    'title' => 'Future customer — no need now',
                    'do' => ['Explain Panun Kaergar briefly.', 'Ask to save number & refer others.'],
                    'message' => self::wa(
                        'WhatsApp warm close — mandatory',
                        'Thank you for contacting Panun Kaergar — your home service partner in Kashmir. Save our number for whenever you need help. If friends or family need a service, please refer us!',
                        'Thank you for contacting Panun Kaergar — your home service partner in Kashmir. Save our number for whenever you need help. If friends or family need a service, please refer us!',
                    ),
                    'panel' => ['Mark future customer', 'End state: Future customer ✓'],
                ],
                [
                    'title' => 'Invalid — wrong service or area',
                    'do' => ['Explain politely we cannot serve.', 'Thank them.'],
                    'message' => self::wa(
                        'Polite close — mandatory',
                        'Thank you for contacting Panun Kaergar. Unfortunately we cannot provide {service} in {area} at this time. We appreciate you reaching out — please contact us for home services we cover in Kashmir.',
                        'Thank you for contacting Panun Kaergar. Unfortunately we cannot provide car repair in Leh at this time. We appreciate you reaching out — please contact us for home services we cover in Kashmir.',
                    ),
                    'panel' => ['Mark invalid', 'Record exact request', 'End state: Invalid ✓'],
                ],
                [
                    'title' => 'Unknown — not enough info',
                    'do' => ['Mark unknown.', 'Call now — do not guess.'],
                    'next' => '→ Step 3 (next slide)',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideStep3Combined(): array
    {
        return [
            'id' => 'step-3',
            'title' => 'Step 3 — Unknown lead: call + qualify (complete)',
            'subtitle' => 'Outbound call and qualifier questions on one path',
            'type' => 'playbook',
            'flowchart' => 'unknown-call',
            'steps' => [
                [
                    'title' => '3.1 — Call the user',
                    'say' => '“Assalam alaikum, I’m calling from Panun Kaergar about your enquiry. What service are you looking for?”',
                    'do' => ['Listen fully.', 'Ask: service, location, when, customer or provider?'],
                ],
                [
                    'title' => '3.2 — If user picks up → qualify (ask in order)',
                    'do' => [
                        '① Need home service? → Customer → Step 4',
                        '② Want to join as provider? → Provider → Step 5',
                        '③ Saving number / no need now? → Future customer → WA + close',
                        '④ Wrong service/area? → Invalid → WA + close',
                    ],
                    'message' => self::wa(
                        'Mandatory WhatsApp immediately after qualifier call',
                        'As per our discussion over call — {summary of what they need}. Next step: {what you will do}.',
                        'As per our discussion over call — you need AC repair in Bemina this weekend. Next step: we will find an available partner and confirm booking details with you shortly.',
                    ),
                    'panel' => ['Update lead type immediately', 'Remarks + follow-up'],
                ],
                [
                    'title' => '3.3 — If user does NOT pick up',
                    'do' => ['Send WhatsApp within minutes.', 'Set follow-up = tomorrow.', 'Call again on follow-up date.'],
                    'message' => self::wa(
                        'Mandatory WhatsApp after missed call',
                        'We tried calling you regarding your enquiry with Panun Kaergar (Home service provider of Kashmir). If you need any help or any service feel free to call us.',
                        'We tried calling you regarding your enquiry with Panun Kaergar (Home service provider of Kashmir). If you need any help or any service feel free to call us.',
                    ),
                    'panel' => ['“No pickup — WA sent [time]”', 'Follow-up tomorrow'],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideStep4PathA(): array
    {
        return [
            'id' => 'step-4-path-a',
            'title' => 'Step 4 — Path A: Direct booking (complete flow)',
            'subtitle' => 'All steps A1→A4 on this slide — no jumping ahead',
            'type' => 'playbook',
            'flowchart' => 'direct-booking',
            'important' => 'WhatsApp to customer is mandatory after EVERY call in this path — send before posting in provider group.',
            'steps' => [
                [
                    'title' => 'A1 — Collect details on call',
                    'do' => ['Service (exact), full address, date & time.', 'Read back for confirmation.'],
                    'say' => '“I’ll find the best available partner and update you shortly.”',
                ],
                [
                    'title' => 'A2 — WhatsApp customer (mandatory — same time as call ends)',
                    'message' => self::wa(
                        'Customer confirmation message',
                        'As per our discussion over call you need this service — {service details}, {address}, {date/time}. We will look for a partner available to do the work for that time.',
                        'As per our discussion over call you need this service — Plumbing (leaking tap), Rajbagh Srinagar near Zero Bridge, Saturday 2 Aug 10 AM. We will look for a partner available to do the work for that time.',
                    ),
                    'panel' => ['Customer lead + full details in remarks'],
                ],
                [
                    'title' => 'A3 — Post in provider group (immediately after customer WA)',
                    'message' => self::wa(
                        'Provider group — availability check (not sent to customer)',
                        "🛠️ *Availability check — Panun Kaergar*\n\nService: {service}\nLocation: {area/address}\nDate: {date}\nTime: {time}\n\nCustomer ready to book. Who is *available*?\nReply *YES + name* or suggest alternate slot.\n⏱ Reply within *10 minutes*.",
                        "🛠️ *Availability check — Panun Kaergar*\n\nService: Plumbing — leaking kitchen tap\nLocation: Rajbagh, Srinagar (near Zero Bridge)\nDate: Saturday, 2 Aug 2026\nTime: 10:00 AM – 12:00 PM\n\nCustomer ready to book. Who is *available*?\nReply *YES + name* or suggest alternate slot.\n⏱ Reply within *10 minutes*.",
                    ),
                    'warning' => '10-minute SLA. If no reply → call providers directly AND WhatsApp customer (A4 below).',
                    'panel' => ['Remark: “Group posted [time]”'],
                ],
                [
                    'title' => 'A4a — Provider available → close booking',
                    'do' => ['Take ₹100 confirmation.', 'Create booking.', 'WhatsApp customer + provider.', 'Set service-day follow-up.'],
                    'message' => self::wa(
                        'Booking confirmed — to customer (after ₹100)',
                        "✅ *Booking confirmed — Panun Kaergar*\n\nService: {service}\nAddress: {address}\nDate/Time: {datetime}\nBooking ID: {id}\n\nOur partner will contact you before the visit. Thank you!",
                        "✅ *Booking confirmed — Panun Kaergar*\n\nService: Plumbing — leaking tap\nAddress: Rajbagh, Srinagar\nDate/Time: Sat 2 Aug, 10 AM\nBooking ID: PK-2841\n\nOur partner will contact you before the visit. Thank you!",
                    ),
                    'panel' => ['End state: Customer booking confirmed ✓'],
                ],
                [
                    'title' => 'A4b — No group reply in 10 min',
                    'do' => ['Call nearby providers.', 'WhatsApp customer immediately.', 'Offer alternate slots or honest update.'],
                    'message' => self::wa(
                        'Still checking — mandatory update to customer',
                        'As per our discussion over call — we are still checking partner availability for your requested date ({date/time}). We will update you shortly. Thank you for your patience.',
                        'As per our discussion over call — we are still checking partner availability for your requested date (Saturday 2 Aug, 10 AM). We will update you shortly. Thank you for your patience.',
                    ),
                    'warning' => 'Never go silent 15–20+ minutes during active booking.',
                ],
                [
                    'title' => 'A4c — Alternate slots OR no match',
                    'do' => [
                        'Alternate slots → call customer → WA summary → ₹100 if agreed.',
                        'No match after retries → honest WA → cancel with remarks.',
                    ],
                    'message' => self::wa(
                        'Alternate slots offered',
                        'As discussed on call — our partners are available at: {slot 1}, {slot 2}. Please let us know which works and we will confirm the booking.',
                        'As discussed on call — our partners are available at: Saturday 2 Aug 2 PM, or Sunday 3 Aug 11 AM. Please let us know which works and we will confirm the booking.',
                    ),
                    'panel' => ['End: Booking ✓ | Follow-up | No provider match ✓ | Customer cancelled ✓'],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideStep4PathB(): array
    {
        return [
            'id' => 'step-4-path-b',
            'title' => 'Step 4 — Path B: Provider discussion first (complete flow)',
            'subtitle' => 'Customer wants to talk to provider before booking',
            'type' => 'playbook',
            'important' => 'WhatsApp after every call — including after the conference call with provider.',
            'steps' => [
                [
                    'title' => 'B1 — After detail call: WhatsApp customer (mandatory)',
                    'message' => self::wa(
                        'Customer — discussion path',
                        'As per our discussion over call you need this service — {service}, {address}, {date/time}. We will look for a partner available and connect you with the service provider.',
                        'As per our discussion over call you need this service — AC repair, Bemina Srinagar, Sunday 3 Aug 11 AM. We will look for a partner available and connect you with the service provider.',
                    ),
                ],
                [
                    'title' => 'B2 — Provider group — discussion request',
                    'message' => self::wa(
                        'Provider group post',
                        "📞 *Discussion request — Panun Kaergar*\n\nService: {service}\nLocation: {area}\nCustomer wants to *talk before booking*.\nPreferred time: {date/time}\n\nWho can take a short call with customer?\nReply *YES + name + availability*.\n⏱ 10 minutes.",
                        "📞 *Discussion request — Panun Kaergar*\n\nService: AC repair — not cooling\nLocation: Bemina, Srinagar\nCustomer wants to *talk before booking*.\nPreferred time: Today after 4 PM\n\nWho can take a short call with customer?\nReply *YES + name + availability*.\n⏱ 10 minutes.",
                    ),
                    'warning' => '10 min SLA — then call providers directly.',
                ],
                [
                    'title' => 'B3 — Before conference call (say on phone)',
                    'say' => '“If you face any issue with pricing or quality, tell Panun Kaergar — we have other partners too.”',
                    'do' => ['Set up conference call: customer + provider.'],
                ],
                [
                    'title' => 'B4 — After conference call: mandatory WhatsApp + outcome',
                    'do' => [
                        'Yes → ₹100 + booking + WA confirmation to both.',
                        'Unsure → WA summarizing concern + follow-up.',
                        'No → polite WA + cancel.',
                    ],
                    'message' => self::wa(
                        'After conference call — summary to customer',
                        'As per our discussion over call with our partner — {summary: agreed service / pricing concern / next step}.',
                        'As per our discussion over call with our partner — you agreed to plumbing service at ₹800 on Saturday 10 AM. Next step: please send ₹100 booking confirmation and we will finalize the booking.',
                    ),
                    'panel' => ['End: Booking ✓ | Follow-up | Customer cancelled ✓'],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideStep5(): array
    {
        return [
            'id' => 'step-5',
            'title' => 'Step 5 — Provider onboarding (complete)',
            'subtitle' => 'WhatsApp after every onboarding call',
            'type' => 'onboarding',
            'flowchart' => 'provider-onboarding',
            'important' => 'Max 3 follow-ups. WhatsApp summary after each call — same time.',
            'availability' => [
                ['label' => 'Free now', 'action' => 'Brief call → WA summary → send agreement.'],
                ['label' => 'Busy', 'action' => 'Schedule callback → WA confirming time → max 3 follow-ups.'],
            ],
            'phases' => [
                ['step' => '1', 'title' => 'Brief call', 'body' => 'Explain PK, commission, service type, area, doc timeline.', 'tip' => 'WA: “As per our call — next step: agreement + documents by {date}.”'],
                ['step' => '2', 'title' => 'Agreement + docs', 'body' => 'Send on WhatsApp. Agree deadline. WA reminder if missed.'],
                ['step' => '3', 'title' => 'Final call', 'body' => 'Explain work, groups, process — after docs received. WA welcome message.'],
                ['step' => '4', 'title' => 'Add to panel', 'body' => 'Admin panel + WhatsApp groups.', 'tip' => 'End: Provider registered ✓'],
            ],
            'steps' => [
                [
                    'title' => 'Provider onboarding — WhatsApp after each call',
                    'message' => self::wa(
                        'After brief / follow-up onboarding call',
                        'As per our discussion over call — thank you for joining Panun Kaergar. Next step: {send agreement / submit documents by DATE / final onboarding call on DATE}.',
                        'As per our discussion over call — thank you for joining Panun Kaergar as an electrical partner in Srinagar. Next step: please submit your ID and agreement documents by Friday 5 PM.',
                    ),
                    'warning' => 'No documents after 3 follow-ups → Provider cancelled — list all attempt dates in remarks.',
                ],
            ],
            'note' => 'Path ends: Provider registered ✓ OR Provider cancelled ✓',
        ];
    }

    /** @return array<string, mixed> */
    private static function slideRolePlayBatch1(): array
    {
        return [
            'id' => 'roleplay-1',
            'title' => 'Role-play — vague message & price concern',
            'type' => 'roleplay',
            'scenarios' => [
                [
                    'title' => '“Hi, I called you” — vague message',
                    'situation' => 'Facebook — no details.',
                    'user_says' => 'Hi, I called you earlier.',
                    'good_response' => 'Reply + call: ask name, phone, service. After call → mandatory WA summary.',
                    'panel' => 'Unknown → classify on contact.',
                    'avoid' => 'Mark invalid. Skip WhatsApp.',
                ],
                [
                    'title' => '“Provider too expensive” after conference call',
                    'situation' => 'Path B — customer unhappy with quote.',
                    'user_says' => 'Too expensive, not sure.',
                    'good_response' => 'On call: offer alternate partner. Immediately after → WA: “As per our call — we understand the pricing concern. We are finding another partner option for you.”',
                    'avoid' => 'End call without WhatsApp. Argue with customer.',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideRolePlayBatch2(): array
    {
        return [
            'id' => 'roleplay-2',
            'title' => 'Role-play — silent provider group & onboarding ghost',
            'type' => 'roleplay',
            'scenarios' => [
                [
                    'title' => 'No provider reply in 10 minutes',
                    'situation' => 'Path A — customer waiting.',
                    'good_response' => 'Call providers. Immediately WA customer: “As per our discussion — still checking availability…” Never stay silent.',
                    'avoid' => 'Wait 1 hour. Cancel without telling customer.',
                ],
                [
                    'title' => 'Provider docs not sent — 3rd follow-up',
                    'situation' => 'Provider says “tomorrow” again.',
                    'good_response' => 'Schedule final call. WA: “As per our call — documents needed by {date} to activate your account.” After 3 misses → cancel with notes.',
                    'avoid' => 'Unlimited follow-ups without WhatsApp trail.',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideRulesAndShift(): array
    {
        return [
            'id' => 'rules-shift',
            'title' => 'SLAs + end-of-shift checklist',
            'type' => 'checklist',
            'important' => 'WhatsApp after every user discussion is as mandatory as ₹100 before booking.',
            'items' => [
                ['title' => 'WhatsApp same time as every call', 'body' => 'No call is complete until WA is sent + panel updated.'],
                ['title' => 'Provider group: 10 minutes', 'body' => 'Then call providers + WA customer update.'],
                ['title' => '₹100 before booking created', 'body' => 'Then booking confirmation WA to customer + provider.'],
                ['title' => 'Max 3 provider onboarding follow-ups', 'body' => 'Each with WA summary after call.'],
                ['title' => 'End of shift', 'body' => 'No open leads without follow-up or closed status. Flag 🔥 HOT leads in remarks.'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideQuiz(): array
    {
        return [
            'id' => 'quiz',
            'title' => 'Quick quiz',
            'subtitle' => 'Tap an answer — aim for 9/9',
            'type' => 'quiz',
            'questions' => [
                [
                    'id' => 'q1',
                    'question' => 'When must you send WhatsApp after a call with a user?',
                    'options' => ['Next day is fine', 'Immediately — same time, before next lead', 'Only for bookings', 'Only if they ask'],
                    'correct' => 1,
                    'explain' => 'Mandatory immediately after every discussion — same time, not later.',
                ],
                [
                    'id' => 'q2',
                    'question' => '“Just called us” with no details — lead type?',
                    'options' => ['Invalid', 'Unknown — call them', 'Future customer', 'Customer booking'],
                    'correct' => 1,
                    'explain' => 'Unknown until you call and qualify.',
                ],
                [
                    'id' => 'q3',
                    'question' => 'Path A — correct order after detail call?',
                    'options' => [
                        'Provider group first, then customer WA whenever',
                        'Customer WA immediately → then provider group → wait 10 min',
                        'Panel only, no WhatsApp',
                        'Wait for provider before any message',
                    ],
                    'correct' => 1,
                    'explain' => 'Customer WA mandatory first (same time as call), then provider group.',
                ],
                [
                    'id' => 'q4',
                    'question' => 'Provider group silent 10 min — you must?',
                    'options' => ['Wait until tomorrow', 'Call providers + WA customer update', 'Cancel customer silently', 'Only update panel'],
                    'correct' => 1,
                    'explain' => 'Call providers AND WhatsApp customer — never go silent.',
                ],
                [
                    'id' => 'q5',
                    'question' => 'When is ₹100 taken?',
                    'options' => ['After service', 'Before creating booking, provider confirmed', 'Never', 'Provider leads only'],
                    'correct' => 1,
                    'explain' => '₹100 before booking creation.',
                ],
                [
                    'id' => 'q6',
                    'question' => 'Future customer — valid action?',
                    'options' => ['Mark invalid', 'Explain PK + referral ask + mandatory WA', 'Ignore', 'Cancel immediately'],
                    'correct' => 1,
                    'explain' => 'Future customer is valid — nurture with WhatsApp close.',
                ],
                [
                    'id' => 'q7',
                    'question' => 'After conference call (Path B), you must?',
                    'options' => ['Nothing if they said yes verbally', 'WhatsApp summary + panel update immediately', 'Wait for customer to message first', 'Only update panel'],
                    'correct' => 1,
                    'explain' => 'WA summary mandatory after every discussion including conference calls.',
                ],
                [
                    'id' => 'q8',
                    'question' => 'Provider onboarding — max follow-ups?',
                    'options' => ['Unlimited', '3, each with WA after call', '1', '10'],
                    'correct' => 1,
                    'explain' => 'Max 3 follow-ups, WhatsApp after each call.',
                ],
                [
                    'id' => 'q9',
                    'question' => 'Minimum panel update after every contact?',
                    'options' => ['Name only', 'Remarks + follow-up OR closed outcome', 'Nothing', 'Booking ID only'],
                    'correct' => 1,
                    'explain' => 'Remarks + follow-up or closed status — always.',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function slideClosing(): array
    {
        return [
            'id' => 'closing',
            'title' => 'You represent Panun Kaergar',
            'type' => 'title',
            'subtitle' => 'Call → WhatsApp (same time) → Panel → Next step',
            'tagline' => 'Every lead deserves a clear path to an end state.',
            'footer' => 'Retake quiz until 9/9. Flowchart & step-by-step guide for reference.',
        ];
    }
}
