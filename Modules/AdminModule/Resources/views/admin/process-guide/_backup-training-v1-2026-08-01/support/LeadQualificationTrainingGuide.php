<?php

namespace Modules\AdminModule\Support;

class LeadQualificationTrainingGuide
{
    /**
     * Trainer-led deck — what to do, say, update, and aim for at each step.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function slides(): array
    {
        return [
            [
                'id' => 'title',
                'number' => 1,
                'title' => 'Lead Handling Training',
                'subtitle' => 'Panun Kaergar — Home Service Provider of Kashmir',
                'tagline' => 'Every call and message shapes how Kashmir sees Panun Kaergar.',
                'type' => 'title',
                'footer' => 'For call centre, lead qualifiers & admin panel staff',
            ],
            [
                'id' => 'mission',
                'number' => 2,
                'title' => 'Your mission — before any step',
                'subtitle' => 'You are not just updating a lead. You are Panun Kaergar to the customer.',
                'type' => 'mindset',
                'intro' => 'A user’s first experience decides whether they book, refer, or never come back. Train for conversion and trust — not just box-ticking.',
                'principles' => [
                    [
                        'title' => 'Respond quickly',
                        'body' => 'Call back fast. Reply on WhatsApp the same day. Silence feels like we don’t care.',
                    ],
                    [
                        'title' => 'Sound helpful, not robotic',
                        'body' => 'Use the customer’s name, confirm what they need, and explain the next step clearly.',
                    ],
                    [
                        'title' => 'Never leave a lead without a next action',
                        'body' => 'Every lead must have remarks + follow-up date OR a clear closed outcome in the panel.',
                    ],
                    [
                        'title' => 'Protect the brand on bad outcomes too',
                        'body' => 'Even invalid or cancelled leads deserve a polite, professional close — they may refer someone later.',
                    ],
                    [
                        'title' => 'Convert when you can — nurture when you cannot',
                        'body' => 'Push toward booking or provider registration. If not today, leave them warm for tomorrow.',
                    ],
                ],
            ],
            [
                'id' => 'journey',
                'number' => 3,
                'title' => 'Start → End — the full journey',
                'subtitle' => 'Know where every lead must land',
                'type' => 'end-map',
                'intro' => 'Initial state: a lead comes in (call, social, website, AI chat, app). Your job: move it to exactly one end state — never leave it hanging.',
                'start' => 'Lead arrives → record source → classify → follow the right path',
                'success' => [
                    [
                        'label' => '✓ Customer booking confirmed',
                        'body' => 'Service details captured, ₹100 confirmation taken, booking created, customer & provider notified. Happy customer.',
                    ],
                    [
                        'label' => '✓ Provider registered',
                        'body' => 'Documents received, final call done, provider added to panel and WhatsApp groups. Happy provider.',
                    ],
                ],
                'nurture' => [
                    [
                        'label' => '◐ Future customer',
                        'body' => 'No need today — but informed about Panun Kaergar, referral ask done, follow-up set. Still a win.',
                    ],
                ],
                'closure' => [
                    [
                        'label' => '✕ Customer cancelled',
                        'body' => 'Customer declined after discussion, reschedule failed, or chose not to proceed. Close chat professionally with remarks.',
                    ],
                    [
                        'label' => '✕ Provider cancelled / dropped',
                        'body' => 'No documents after 3 follow-ups, or provider stopped responding. Cancel lead with reason documented.',
                    ],
                    [
                        'label' => '✕ Invalid lead',
                        'body' => 'Wrong service or non-serviceable area. Close with what they asked for — no ghosting.',
                    ],
                    [
                        'label' => '✕ No provider match',
                        'body' => 'No availability after group post, calls, and alternate dates. Inform customer honestly, then cancel if no path left.',
                    ],
                ],
            ],
            [
                'id' => 'always-do',
                'number' => 4,
                'title' => 'On every lead — 4 non-negotiables',
                'type' => 'checklist',
                'items' => [
                    [
                        'title' => '1. Record the source',
                        'body' => 'Facebook, Instagram, which phone number, AI chat, website booking, etc. — before anything else.',
                    ],
                    [
                        'title' => '2. Classify within the first contact',
                        'body' => 'Customer / Provider / Future / Invalid / Unknown — pick one direction and follow it.',
                    ],
                    [
                        'title' => '3. Update the panel after every touch',
                        'body' => 'Remarks = what happened. Follow-up date = when YOU will act next. Never empty after a call or message.',
                    ],
                    [
                        'title' => '4. Know your next step before hanging up',
                        'body' => 'Tell the user what happens next and when they will hear from you. That builds trust.',
                    ],
                ],
            ],
            [
                'id' => 'step-1',
                'number' => 5,
                'title' => 'Step 1 — Lead arrives',
                'subtitle' => 'Initial state: enquiry enters the system',
                'type' => 'playbook',
                'steps' => [
                    [
                        'title' => 'Social media (Facebook / Instagram)',
                        'goal' => 'Create a complete lead record fast',
                        'do' => [
                            'Reply warmly — thank them for contacting Panun Kaergar.',
                            'Collect: name, phone, service needed, location, preferred date/time.',
                            'Create new lead in admin panel manually.',
                        ],
                        'panel' => ['Source = social channel', 'All details in remarks', 'Set follow-up if they go quiet'],
                        'next' => '→ Step 2: Classify the lead',
                        'tip' => 'A quick “We’ll call you shortly” message stops them calling competitors.',
                    ],
                    [
                        'title' => 'Direct calls (8899881555, 8899556555, 889918155, 9103076946)',
                        'goal' => 'Same as social — don’t lose phone leads',
                        'do' => [
                            'Answer professionally: “Panun Kaergar, how can I help you?”',
                            'If missed: call back within minutes if possible.',
                            'Create lead with every detail from the conversation.',
                        ],
                        'panel' => ['Note which number they called', 'Lead type after first questions'],
                        'next' => '→ Step 2: Classify the lead',
                        'tip' => 'Phone leads are hottest — prioritize these over older follow-ups.',
                    ],
                    [
                        'title' => 'Auto-created in panel (AI chat, website, app)',
                        'goal' => 'Verify and qualify — don’t assume data is complete',
                        'do' => [
                            'Open the lead — check name, phone, service, address.',
                            'Call or WhatsApp if anything is missing.',
                            'Confirm intent before classifying.',
                        ],
                        'panel' => ['Verify/auto fields', 'Add correction notes if user gave more on call'],
                        'next' => '→ Step 2: Classify the lead',
                        'tip' => 'These users already trust us enough to enquire online — respond fast to convert.',
                    ],
                ],
            ],
            [
                'id' => 'step-2',
                'number' => 6,
                'title' => 'Step 2 — Classify immediately',
                'subtitle' => 'Decision: what do you already know?',
                'type' => 'playbook',
                'intro' => 'Wrong classification wastes time and loses the user. Ask enough to decide in one contact when possible.',
                'steps' => [
                    [
                        'title' => 'Customer lead — needs a home service',
                        'goal' => 'Move to booking path',
                        'do' => ['Confirm: what service, where, when.', 'Mark as customer lead in panel.'],
                        'next' => '→ Step 4: Customer booking path',
                        'tip' => 'Repeat their request back: “So you need plumbing in Srinagar on Tuesday — correct?”',
                    ],
                    [
                        'title' => 'Provider lead — wants to join as partner',
                        'goal' => 'Move to onboarding',
                        'do' => ['Confirm they want to work with Panun Kaergar as a provider.', 'Mark as provider lead.'],
                        'next' => '→ Step 5: Provider onboarding',
                        'tip' => 'Providers are growth — treat them as valued, not an interruption.',
                    ],
                    [
                        'title' => 'Future customer — no need now',
                        'goal' => 'Leave a positive impression + referral',
                        'do' => [
                            'Briefly explain Panun Kaergar services.',
                            'Ask them to save the number and refer friends/family.',
                            'Set a light follow-up (e.g. 2–4 weeks) if appropriate.',
                        ],
                        'panel' => ['Mark future customer', 'Note “no immediate need” + referral ask done'],
                        'next' => '→ End state: Future customer',
                        'tip' => 'Don’t rush off — 30 seconds of warmth today = booking next month.',
                    ],
                    [
                        'title' => 'Invalid — wrong service or area',
                        'goal' => 'Close professionally',
                        'do' => [
                            'Politely explain we don’t cover that service/area (if true).',
                            'Thank them for reaching out.',
                        ],
                        'panel' => ['Mark invalid', 'Record exact service/location requested'],
                        'next' => '→ End state: Invalid lead',
                        'tip' => '“Sorry we can’t help with X, but thank you for thinking of Panun Kaergar.”',
                    ],
                    [
                        'title' => 'Unknown — not enough information',
                        'goal' => 'Get clarity on a call',
                        'do' => ['Do NOT guess. Call the user now.'],
                        'panel' => ['Mark unknown until call done'],
                        'next' => '→ Step 3: Outbound call',
                        'tip' => '“Just called us” with no detail = unknown, not invalid.',
                    ],
                ],
            ],
            [
                'id' => 'step-3',
                'number' => 7,
                'title' => 'Step 3 — Unknown lead: outbound call',
                'type' => 'playbook',
                'steps' => [
                    [
                        'title' => 'When user picks up',
                        'goal' => 'Classify in one conversation',
                        'say' => '“Assalam alaikum, I’m calling from Panun Kaergar regarding your enquiry. May I know what service you’re looking for?”',
                        'do' => [
                            'Listen fully before typing.',
                            'Ask: service type, location, when needed, customer or provider?',
                        ],
                        'panel' => ['Update remarks with answers', 'Re-classify → customer / provider / future / invalid'],
                        'next' => '→ Step 3a if still unclear, else go to Step 4 or 5',
                        'tip' => 'Smile on the phone — users hear it.',
                    ],
                    [
                        'title' => 'When user does NOT pick up',
                        'goal' => 'Stay visible — don’t lose the lead',
                        'say' => 'WhatsApp: “We tried calling you regarding your enquiry with Panun Kaergar (Home service provider of Kashmir). If you need any help or any service feel free to call us.”',
                        'do' => ['Send message within minutes of missed call.', 'Set follow-up for next day.'],
                        'panel' => ['Remarks: “No pickup — WA sent”', 'Follow-up = tomorrow'],
                        'next' => '→ Call again on follow-up date → Step 3a',
                        'tip' => 'One missed call without WhatsApp = lead gone to a competitor.',
                    ],
                ],
            ],
            [
                'id' => 'step-3a',
                'number' => 8,
                'title' => 'Step 3a — Lead qualifier (on the call)',
                'subtitle' => 'Four questions that decide the path',
                'type' => 'script',
                'questions' => [
                    ['q' => 'Do you need a home service for yourself?', 'if_yes' => 'Customer lead → Step 4'],
                    ['q' => 'Do you want to join Panun Kaergar as a service provider?', 'if_yes' => 'Provider lead → Step 5'],
                    ['q' => 'Just saving our number / no need now?', 'if_yes' => 'Future customer → inform + referral ask'],
                    ['q' => 'Service or area we don’t cover?', 'if_yes' => 'Invalid → polite close + document'],
                ],
                'note' => 'After deciding: update lead type in panel immediately so the next agent sees the right path.',
            ],
            [
                'id' => 'step-4-intro',
                'number' => 9,
                'title' => 'Step 4 — Customer lead: convert to booking',
                'subtitle' => 'Your conversion goal: confirmed booking with happy customer',
                'type' => 'playbook',
                'steps' => [
                    [
                        'title' => 'First call — collect & confirm',
                        'goal' => 'Zero ambiguity before searching providers',
                        'do' => [
                            'What service (exact), full address, date & time.',
                            'Read back everything for confirmation.',
                        ],
                        'say' => '“I’ll find the best available partner for you and update you shortly.”',
                        'panel' => ['Customer lead + full service details in remarks'],
                        'next' => 'Ask: provider call first, or direct booking?',
                        'tip' => 'Customers who feel heard rarely cancel later.',
                    ],
                ],
                'question' => 'Does customer want to talk to the provider BEFORE booking?',
                'paths' => [
                    ['label' => 'Path A', 'title' => 'No — book directly', 'ref' => 'step-4-direct'],
                    ['label' => 'Path B', 'title' => 'Yes — provider discussion first', 'ref' => 'step-4-provider-first'],
                ],
            ],
            [
                'id' => 'step-4-direct',
                'number' => 10,
                'title' => 'Path A — Direct booking',
                'type' => 'playbook',
                'steps' => [
                    [
                        'title' => 'After confirming details',
                        'say' => 'WhatsApp: “As per our discussion over call you need this service — Service details, address, date/time. We will look for a partner available to do the work for that time.”',
                        'do' => [
                            'Post in provider group: service, area, date/time — who is available?',
                            'Start 10-minute timer mentally.',
                        ],
                        'panel' => ['Customer lead updated', 'Follow-up set'],
                        'next' => 'Provider replies in 10 min?',
                    ],
                    [
                        'title' => 'Provider ready → close the booking',
                        'goal' => 'End state: Customer booking confirmed ✓',
                        'do' => [
                            'Take ₹100 booking confirmation.',
                            'Create booking in panel.',
                            'Notify customer AND provider.',
                            'Set service-day follow-up.',
                        ],
                        'tip' => 'Confirm to customer: “Your booking is confirmed — our partner will contact you.”',
                    ],
                    [
                        'title' => 'No reply in 10 minutes',
                        'goal' => 'Don’t go silent on the customer',
                        'do' => [
                            'Call nearby providers directly.',
                            'If alternate slots exist → offer to customer.',
                            'If nothing works → honest update, then follow-up or cancel.',
                        ],
                        'next' => '→ Slide 11: No provider availability',
                        'tip' => 'Message customer: “Still checking availability — will update you soon.”',
                    ],
                ],
            ],
            [
                'id' => 'step-4-availability',
                'number' => 11,
                'title' => 'When providers are busy — save the experience',
                'subtitle' => 'This is where most leads are lost. Don’t ghost the customer.',
                'type' => 'playbook',
                'steps' => [
                    [
                        'title' => 'Alternate times available',
                        'do' => ['Call customer with options.', 'WhatsApp summary of call.', 'If OK → ₹100 + booking.'],
                        'panel' => ['Note agreed slot or “customer declined reschedule”'],
                        'next' => 'Yes → booking ✓ | No → follow-up date',
                    ],
                    [
                        'title' => 'Nobody available',
                        'say' => '“Our partners are busy for your date — may we check other dates or call you when someone frees up?”',
                        'do' => [
                            'One more check with providers if customer is flexible.',
                            'If still no match → cancel lead, close chat politely.',
                        ],
                        'panel' => ['Remarks: why no match', 'End state: No provider match or Customer cancelled'],
                        'tip' => 'A clear “we tried everything” close is better than silence.',
                    ],
                ],
            ],
            [
                'id' => 'step-4-provider-first',
                'number' => 12,
                'title' => 'Path B — Provider discussion first',
                'type' => 'playbook',
                'steps' => [
                    [
                        'title' => 'Set expectations',
                        'say' => 'WhatsApp: “…We will look for a partner available and connect you with the service provider.”',
                        'do' => ['Provider group: who can speak with customer?', '10-minute SLA applies.'],
                        'panel' => ['Customer lead + follow-up'],
                    ],
                    [
                        'title' => 'Before conference call',
                        'say' => '“If you face any issue with pricing or quality, tell Panun Kaergar — we have other partners too.”',
                        'do' => ['Set up conference call: customer + provider.'],
                        'tip' => 'This line protects trust — customer knows we’re on their side.',
                    ],
                    [
                        'title' => 'After call — customer wants service',
                        'goal' => 'End state: Customer booking confirmed ✓',
                        'do' => ['₹100 confirmation → create booking → notify both parties.'],
                    ],
                    [
                        'title' => 'Customer unsure or says no',
                        'do' => ['Understand concern — try once to address it.', 'Follow-up date OR cancel if firm no.'],
                        'next' => 'End state: Customer cancelled or follow-up',
                    ],
                ],
            ],
            [
                'id' => 'step-5',
                'number' => 13,
                'title' => 'Step 5 — Provider lead: get them registered',
                'subtitle' => 'End goal: provider on panel + in WhatsApp groups',
                'type' => 'onboarding',
                'intro' => 'Providers grow the business. Be welcoming, clear on commission, and persistent (max 3 follow-ups).',
                'availability' => [
                    ['label' => 'Free now', 'action' => 'Start Step 1 brief call immediately.'],
                    ['label' => 'Busy now', 'action' => 'Schedule exact date/time — call then (max 3 follow-ups total).'],
                ],
                'phases' => [
                    [
                        'step' => 'Step 1',
                        'title' => 'Brief call',
                        'body' => 'Explain Panun Kaergar, commission, get service type + area, ask when they can send documents.',
                        'tip' => 'Sound like a partner invitation, not an interrogation.',
                    ],
                    [
                        'step' => 'Step 2',
                        'title' => 'Agreement & documents',
                        'body' => 'Send agreement + doc list. Agree a deadline for submission. Follow up if missed.',
                    ],
                    [
                        'step' => 'Step 3',
                        'title' => 'Final call (after docs)',
                        'body' => 'Explain daily work, WhatsApp groups, how jobs flow.',
                    ],
                    [
                        'step' => 'Step 4',
                        'title' => 'Add to panel',
                        'body' => 'Create provider in admin + add to relevant WhatsApp groups.',
                        'tip' => 'End state: Provider registered ✓',
                    ],
                ],
                'note' => 'No documents after 3 follow-ups → End state: Provider cancelled — document attempts in remarks.',
            ],
            [
                'id' => 'end-states-detail',
                'number' => 14,
                'title' => 'All end states — what “done” looks like',
                'type' => 'outcomes',
                'intro' => 'Before closing any lead, ask: “Which end state is this?” and confirm panel matches.',
                'outcomes' => [
                    ['label' => '✓ Customer booking confirmed', 'body' => '₹100 taken, booking ID created, both parties messaged. Remarks complete.'],
                    ['label' => '✓ Provider registered', 'body' => 'On panel, in groups, onboarding complete.'],
                    ['label' => '◐ Future customer', 'body' => 'Panun Kaergar explained, referral ask done, optional follow-up set.'],
                    ['label' => '✕ Customer cancelled', 'body' => 'Reason in remarks (declined service, reschedule failed, chose competitor). Chat closed.'],
                    ['label' => '✕ Provider cancelled', 'body' => '3 follow-ups failed or they withdrew. Reason documented.'],
                    ['label' => '✕ Invalid lead', 'body' => 'Wrong service/area recorded. Polite close sent.'],
                    ['label' => '✕ No provider match', 'body' => 'Customer informed, alternatives tried, no availability. Cancel with clear notes.'],
                ],
            ],
            [
                'id' => 'templates',
                'number' => 15,
                'title' => 'WhatsApp — copy, personalize, send',
                'type' => 'templates',
                'templates' => [
                    [
                        'title' => 'Missed call',
                        'text' => 'We tried calling you regarding your enquiry with Panun Kaergar (Home service provider of Kashmir). If you need any help or any service feel free to call us.',
                    ],
                    [
                        'title' => 'Direct booking',
                        'text' => 'As per our discussion over call you need this service — Service details, address, date/time. We will look for a partner available to do the work for that time.',
                    ],
                    [
                        'title' => 'Provider discussion',
                        'text' => 'As per our discussion over call you need this service — Service details, address, date/time. We will look for a partner available and connect you with the service provider.',
                    ],
                    [
                        'title' => 'Still working on availability',
                        'text' => 'We are still checking partner availability for your requested date. We will update you shortly — thank you for your patience.',
                    ],
                ],
            ],
            [
                'id' => 'rules',
                'number' => 16,
                'title' => 'SLAs — if you break these, leads die',
                'type' => 'rules',
                'rules' => [
                    'Respond to new leads same day — phone leads within minutes when possible.',
                    'Provider group: 10-minute rule — then call providers directly.',
                    '₹100 booking confirmation before creating any booking.',
                    'Max 3 follow-ups for provider onboarding — then cancel with notes.',
                    'Every call ends with panel update: remarks + follow-up OR closed outcome.',
                    'Never argue with customers — offer alternatives and escalate to team lead if stuck.',
                ],
            ],
            [
                'id' => 'shift-checklist',
                'number' => 17,
                'title' => 'Before you end your shift',
                'type' => 'checklist',
                'items' => [
                    ['title' => 'No lead without follow-up date or closed status', 'body' => 'Scan your open leads — every one must have a next action.'],
                    ['title' => 'All today’s calls logged in remarks', 'body' => 'Another agent may pick up tomorrow — they should read the story.'],
                    ['title' => 'Pending provider group posts handled', 'body' => '10 min passed? Did you call providers and update the customer?'],
                    ['title' => 'Handover note for hot leads', 'body' => 'Booking almost closed? Flag in remarks: “CALL BACK — ready to pay.”'],
                ],
            ],
            [
                'id' => 'closing',
                'number' => 18,
                'title' => 'You represent Panun Kaergar',
                'type' => 'title',
                'subtitle' => 'Use Flowchart for the visual process · Step-by-step guide for daily reference',
                'tagline' => 'Fast, helpful, clear — that’s how we convert leads and grow Kashmir’s trust.',
                'footer' => 'Questions? Ask your team lead before guessing.',
            ],
        ];
    }
}
