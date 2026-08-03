<?php

namespace Modules\AdminModule\Support;

class LeadQualificationTrainingFlowcharts
{
    /**
     * Mini flowcharts for training slides — aligned with Flowchart tab & step-by-step guide.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function all(): array
    {
        return [
            'master-journey' => [
                ['kind' => 'start', 'label' => 'Enquiry arrives — Facebook, Instagram, phone call, missed call, website, or app'],
                ['kind' => 'action', 'label' => 'Create a new lead in panel OR open the auto-created lead — fill source, phone, and first remarks'],
                ['kind' => 'decision', 'label' => 'Do you already know what this person wants? (Enough to pick a type?)'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Unknown — message was vague (“hi”, “I called”)', 'tone' => 'warn', 'to' => 'Outbound call → ask what they need → change type in panel'],
                    ['label' => 'Customer — needs home service now', 'tone' => 'success', 'to' => 'Call for full details → Path A (direct book) or Path B (talk to provider first)'],
                    ['label' => 'Provider — wants to join as Panun Kaergar partner', 'tone' => 'success', 'to' => 'Onboarding call → send documents → add to panel and WhatsApp groups'],
                    ['label' => 'Future customer — no service needed today', 'tone' => 'neutral', 'to' => 'Tell them about Panun Kaergar services → warm WhatsApp → close as Future ✓'],
                    ['label' => 'Invalid — wrong service or area Panun Kaergar does not cover', 'tone' => 'danger', 'to' => 'Write reason in panel → polite WhatsApp → close as Invalid ✓'],
                ]],
                ['kind' => 'action', 'label' => 'After every call: write notes → update panel (type + remarks) → WhatsApp customer → set Followup On if needed'],
                ['kind' => 'end', 'label' => 'Every lead ends in ONE status: booking confirmed, provider registered, future customer, invalid, or cancelled', 'tone' => 'success'],
            ],
            'lead-arrival' => [
                ['kind' => 'start', 'label' => 'Start shift — run checklist: ringing phone → WA unread → today\'s follow-ups → Web Bookings / Provider Requests / App Custom Requests → social apps → Human support'],
                ['kind' => 'decision', 'label' => 'New enquiry — which channel did it come from?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Facebook / Instagram / YouTube — comment or DM', 'tone' => 'warn', 'to' => 'Read in social app → Leads → Add New Lead (manual). Source + phone + paste their message in remarks — same day'],
                    ['label' => 'Direct call or missed call', 'tone' => 'warn', 'to' => 'Answer live first. Missed call → WA within 5 min + create/update lead (Source = Phone). No duplicate if lead exists'],
                    ['label' => 'WhatsApp — human number (Active Chats)', 'tone' => 'neutral', 'to' => 'Open chat → assign yourself → chat status + tags (Lead ID, stage) → update linked lead (Handled By, status, Followup On)'],
                    ['label' => 'WhatsApp — AI chat (Human support tab)', 'tone' => 'neutral', 'to' => 'Take over → assign + tag thread → open linked lead (Source = AI Chat) → verify phone, service, address'],
                    ['label' => 'Web Booking / Web Provider Request / App Custom Request', 'tone' => 'success', 'to' => 'Open list in panel → read entry → open linked auto-lead → fix anything missing → then qualify'],
                ]],
                ['kind' => 'action', 'label' => 'Every path ends the same: lead in panel with source, phone, remarks, Handled By, and Followup On if not finished'],
                ['kind' => 'action', 'label' => 'WhatsApp threads: chat assignee + status + tags must match lead panel — both updated before you move on'],
                ['kind' => 'decision', 'label' => 'Can you pick Customer, Provider, Future, or Invalid right now?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'No — vague (“hi”, “I called”) or missing details', 'tone' => 'warn', 'to' => 'Type = Unknown → outbound call same day (or no-pickup flow)'],
                    ['label' => 'Yes — clear what they want', 'tone' => 'success', 'to' => 'Set correct type → panel + WhatsApp → open matching handling slide'],
                ]],
                ['kind' => 'end', 'label' => 'Lead tracked in panel — ready for qualification or next follow-up', 'tone' => 'success'],
            ],
            'shift-routine' => [
                ['kind' => 'start', 'label' => 'Start of shift — log in and scan every channel before queue work'],
                ['kind' => 'action', 'label' => 'In order: phone & missed calls → WA unread → today\'s follow-ups → Human support → web/app lists → social DMs'],
                ['kind' => 'action', 'label' => 'Open Followups Pending Till Today — sort: emergency → hot booking → due today → new messages'],
                ['kind' => 'decision', 'label' => 'During shift — phone rings, emergency, or hot booking while you are busy?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Live phone, emergency, or customer waiting on provider', 'tone' => 'warn', 'to' => 'Stop queue → handle fully (panel + WA + Followup) → return to queue'],
                    ['label' => 'Non-urgent DM or missed call only', 'tone' => 'success', 'to' => 'Quick reply + create/update lead → continue current work'],
                ]],
                ['kind' => 'action', 'label' => 'Work rhythm: one lead at a time until panel + WhatsApp + Followup On OR closed'],
                ['kind' => 'action', 'label' => 'Every 30–60 min between tasks: missed calls, WA badge, Human support, web/app refresh, social scan'],
                ['kind' => 'action', 'label' => 'End of shift: all touched chats tagged; all touched leads have Handled By, status, Followup or close'],
                ['kind' => 'end', 'label' => 'Handover clean — next shift continues without calling you', 'tone' => 'success'],
            ],
            'followup-daily' => [
                ['kind' => 'start', 'label' => 'Start of shift — log into admin panel'],
                ['kind' => 'action', 'label' => 'Open “Followups Pending Till Today” — this is your work queue for the day'],
                ['kind' => 'action', 'label' => 'Work in priority order: ringing phone first → emergency → hot booking → due today → new messages'],
                ['kind' => 'decision', 'label' => 'Phone rings or new urgent lead while you are on another task?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Live phone call or emergency', 'tone' => 'warn', 'to' => 'Answer now → handle fully → return to queue'],
                    ['label' => 'Non-urgent DM or missed call only', 'tone' => 'success', 'to' => 'Quick reply + create/update lead → continue queue'],
                ]],
                ['kind' => 'action', 'label' => 'After each contact: notes → panel update (remarks + type) → WhatsApp → Followup On date + urgency'],
                ['kind' => 'end', 'label' => 'End of shift: no overdue follow-ups left without action or closed status', 'tone' => 'success'],
            ],
            'classify' => [
                ['kind' => 'decision', 'label' => 'Read the enquiry — what is this person asking for?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Vague — only “hi”, “I called”, or no service mentioned', 'tone' => 'warn', 'to' => 'Unknown — you must call and ask before doing anything else'],
                    ['label' => 'Needs a home service (plumber, electrician, cleaning, etc.)', 'tone' => 'success', 'to' => 'Customer — proceed to booking slides'],
                    ['label' => 'Wants to work with Panun Kaergar as a service partner', 'tone' => 'success', 'to' => 'Provider — proceed to onboarding slides'],
                    ['label' => 'No service needed now — saving number, renovation later, just moved', 'tone' => 'neutral', 'to' => 'Future customer — explain services, warm close'],
                    ['label' => 'Service or area Panun Kaergar does not offer', 'tone' => 'danger', 'to' => 'Invalid — polite close with reason in panel'],
                ]],
                ['kind' => 'action', 'label' => 'Pick exactly ONE type in panel — if you called an Unknown lead, change the type on that same call'],
                ['kind' => 'end', 'label' => 'Type is set → open the matching handling slide (Customer, Provider, etc.)', 'tone' => 'success'],
            ],
            'unknown-call' => [
                ['kind' => 'start', 'label' => 'Unknown lead — not enough info to classify'],
                ['kind' => 'action', 'label' => 'Outbound call — collect what they want from Panun Kaergar'],
                ['kind' => 'decision', 'label' => 'User picked up?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Yes', 'tone' => 'success', 'to' => 'Lead qualifier → reclassify same call'],
                    ['label' => 'No', 'tone' => 'warn', 'to' => 'No-pickup flow — max 3 follow-ups'],
                ]],
                ['kind' => 'decision', 'label' => 'Lead qualifier — what does user want?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Needs service', 'tone' => 'success', 'to' => '→ Customer slide'],
                    ['label' => 'Wants to join Panun Kaergar as partner', 'tone' => 'success', 'to' => '→ Provider onboarding slide'],
                    ['label' => 'Future / saved number', 'tone' => 'neutral', 'to' => '→ Future slide'],
                    ['label' => 'Invalid request', 'tone' => 'danger', 'to' => '→ Invalid slide'],
                ]],
                ['kind' => 'end', 'label' => 'Never stay Unknown after a successful call', 'tone' => 'success'],
            ],
            'unknown-no-pickup' => [
                ['kind' => 'start', 'label' => 'Unknown — user did NOT pick up (max 3 follow-ups)'],
                ['kind' => 'action', 'label' => 'Each attempt: Add follow-up → Taken → Call + WhatsApp same day'],
                ['kind' => 'action', 'label' => 'Attempt 1 — WA missed-call template + Followup On next day'],
                ['kind' => 'action', 'label' => 'Attempt 2 — call on Followup On → no pickup → WA + new Followup On'],
                ['kind' => 'action', 'label' => 'Attempt 3 — final call → no pickup → WA + all dates in remarks + follow-ups tab'],
                ['kind' => 'decision', 'label' => 'Picked up on any attempt?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Yes — any attempt', 'tone' => 'success', 'to' => 'Qualify → reclassify → handling slide'],
                    ['label' => 'No — all 3 failed', 'tone' => 'danger', 'to' => 'Next: vague vs documented need (Path C)'],
                ]],
                ['kind' => 'decision', 'label' => 'Enquiry vague only, or DM/form showed customer job details?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Vague ("hi", missed call only)', 'tone' => 'danger', 'to' => 'Mark as Invalid → Did not Know About Enquiry'],
                    ['label' => 'DM/form had service + location (Path C)', 'tone' => 'warn', 'to' => 'Mark as Customer → Change Status Cancel → No Response From Customer'],
                ]],
                ['kind' => 'end', 'label' => 'Lead closed OR reclassified if they answered', 'tone' => 'success'],
            ],
            'customer-booking' => [
                ['kind' => 'start', 'label' => 'Customer lead — user needs home service'],
                ['kind' => 'action', 'label' => 'Outbound call — get full details: service, issue, address, date/time'],
                ['kind' => 'decision', 'label' => 'Customer picked up?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Yes', 'tone' => 'success', 'to' => 'Notes → panel → WhatsApp → Path A or B'],
                    ['label' => 'No', 'tone' => 'warn', 'to' => 'No-pickup flow — max 3 follow-ups'],
                ]],
                ['kind' => 'decision', 'label' => 'Customer wants to talk to provider first?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'No — direct booking', 'tone' => 'success', 'to' => 'Path A steps below'],
                    ['label' => 'Yes — discussion first', 'tone' => 'success', 'to' => 'Path B steps below'],
                ]],
            ],
            'customer-no-pickup' => [
                ['kind' => 'start', 'label' => 'Customer lead — call for service details — no pickup (max 3 follow-ups)'],
                ['kind' => 'action', 'label' => 'Attempt 1: WhatsApp summary / enquiry message same day'],
                ['kind' => 'action', 'label' => 'Panel — Attempt 1/3 in remarks + Followup On next day'],
                ['kind' => 'action', 'label' => 'Attempt 2: Call on Followup On — no pickup → WA + Attempt 2/3 + new Followup On'],
                ['kind' => 'action', 'label' => 'Attempt 3: Final call — no pickup → WA + Attempt 3/3 documented'],
                ['kind' => 'decision', 'label' => 'Picked up on any attempt?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Yes — any attempt', 'tone' => 'success', 'to' => 'Collect details → Path A or B'],
                    ['label' => 'No — all 3 failed', 'tone' => 'danger', 'to' => 'Change Status → Cancel → No Response From Customer'],
                ]],
                ['kind' => 'action', 'label' => 'After 3 no-pickups: all attempt dates in remarks + follow-up rows + close lead'],
                ['kind' => 'end', 'label' => 'Cancelled — No Response From Customer ✓ OR booking path if they answered', 'tone' => 'success'],
            ],
            'direct-booking' => [
                ['kind' => 'action', 'label' => 'WhatsApp: service details, address, date/time — finding partner for that time'],
                ['kind' => 'action', 'label' => 'Update lead as Customer — full remarks + Followup On'],
                ['kind' => 'action', 'label' => 'Post in provider group — who is available for this service? (10 min SLA)'],
                ['kind' => 'decision', 'label' => 'Anyone replied within 10 minutes?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Yes — ready for service', 'tone' => 'success', 'to' => '₹100 → Create Booking → notify both'],
                    ['label' => 'No reply', 'tone' => 'warn', 'to' => 'Call nearby providers — check dates'],
                ]],
                ['kind' => 'decision', 'label' => 'Got alternate provider availability?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Different times offered', 'tone' => 'success', 'to' => 'Call customer → share slots → WA → book or follow-up'],
                    ['label' => 'Nobody available', 'tone' => 'danger', 'to' => 'Tell customer busy → WA → follow-up → cancel if no match'],
                ]],
                ['kind' => 'end', 'label' => 'Booking confirmed ✓ OR Change Status → Cancel with reason ✓', 'tone' => 'success'],
            ],
            'discussion-booking' => [
                ['kind' => 'action', 'label' => 'WhatsApp: details + we will find partner and connect you with provider'],
                ['kind' => 'action', 'label' => 'Update lead as Customer — remarks + Followup On'],
                ['kind' => 'action', 'label' => 'Provider group — who is available for discussion with customer?'],
                ['kind' => 'decision', 'label' => 'Provider ready within 10 min?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Yes', 'tone' => 'success', 'to' => 'Pre-call brief customer → conference call'],
                    ['label' => 'No', 'tone' => 'warn', 'to' => 'Call providers — same as Path A no-reply branch'],
                ]],
                ['kind' => 'decision', 'label' => 'After conference — customer wants service?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Yes', 'tone' => 'success', 'to' => '₹100 → Create Booking → messages + follow-ups'],
                    ['label' => 'Will decide later', 'tone' => 'warn', 'to' => 'Understand concern → follow-up date'],
                    ['label' => 'Denies service', 'tone' => 'danger', 'to' => 'Cancel lead + close chat'],
                ]],
                ['kind' => 'end', 'label' => 'Booking ✓ OR Change Status → Cancel with reason ✓', 'tone' => 'success'],
            ],
            'provider-onboarding' => [
                ['kind' => 'start', 'label' => 'Provider lead — wants to join Panun Kaergar'],
                ['kind' => 'decision', 'label' => 'Available for brief onboarding call now?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Yes', 'tone' => 'success', 'to' => 'Step 1 brief call now'],
                    ['label' => 'Later', 'tone' => 'warn', 'to' => 'Schedule follow-up — call then (max 3)'],
                ]],
                ['kind' => 'action', 'label' => 'Step 1 — Brief call: explain Panun Kaergar model, commission, service type, area, and document deadline'],
                ['kind' => 'action', 'label' => 'Step 2 — Send agreement + document list — ask submit-by date'],
                ['kind' => 'decision', 'label' => 'Documents shared?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Yes', 'tone' => 'success', 'to' => 'Step 3 final call'],
                    ['label' => 'No', 'tone' => 'warn', 'to' => 'Follow-up (max 3 total) → cancel lead'],
                ]],
                ['kind' => 'action', 'label' => 'Step 3 — Final call: explain work, WhatsApp groups, 10-min reply rule'],
                ['kind' => 'action', 'label' => 'Step 4 — Providers → Add New Provider (/admin/provider/create) + correct WhatsApp group(s)'],
                ['kind' => 'end', 'label' => 'Provider registered ✓', 'tone' => 'success'],
            ],
            'future-customer' => [
                ['kind' => 'start', 'label' => 'Future customer — they do not need a service today'],
                ['kind' => 'action', 'label' => 'On call: confirm why — saving our number, renovation in 3 months, just moved, etc.'],
                ['kind' => 'action', 'label' => 'Panel — type Future customer + pick the reason from dropdown (required)'],
                ['kind' => 'action', 'label' => 'Tell the customer what Panun Kaergar offers (plumbing, electrical, cleaning, etc.) and when to call us'],
                ['kind' => 'action', 'label' => 'Ask: “Do you know anyone who needs home service right now?” — capture referral in remarks if yes'],
                ['kind' => 'action', 'label' => 'Warm-close WhatsApp — thank them, ask them to save Panun Kaergar number 8899881555'],
                ['kind' => 'action', 'label' => 'Contact again later? → Add Outbound Enquiry on lead (call/message, status, datetime, remarks)'],
                ['kind' => 'end', 'label' => 'Future customer ✓ — valid close, lead documented', 'tone' => 'success'],
            ],
            'invalid-lead' => [
                ['kind' => 'start', 'label' => 'Invalid — request Panun Kaergar cannot fulfil (wrong service or outside service area)'],
                ['kind' => 'action', 'label' => 'Write in remarks exactly what they asked for — service name and location'],
                ['kind' => 'action', 'label' => 'Panel — type Invalid + select reason (e.g. service not offered, area not covered)'],
                ['kind' => 'action', 'label' => 'Polite WhatsApp — sorry we cannot help with that, briefly list what Panun Kaergar does offer'],
                ['kind' => 'end', 'label' => 'Invalid ✓ — lead closed professionally', 'tone' => 'success'],
            ],
        ];
    }

    /**
     * Flatten a flowchart into numbered follow-steps for training slides.
     *
     * @return array<int, array{icon: string, text: string}>
     */
    public static function followSteps(string $key): array
    {
        $nodes = self::get($key);
        if ($nodes === null) {
            return [];
        }

        $steps = [];
        foreach ($nodes as $node) {
            $kind = $node['kind'] ?? 'action';
            if ($kind === 'fork') {
                foreach ($node['branches'] ?? [] as $branch) {
                    $label = trim(($branch['label'] ?? '').' → '.($branch['to'] ?? ''));
                    $steps[] = ['text' => $label];
                }

                continue;
            }

            $icon = match ($kind) {
                'start' => '',
                'decision' => '',
                'end' => '',
                default => '',
            };
            $step = ['text' => $node['label'] ?? ''];
            if ($icon !== '') {
                $step['icon'] = $icon;
            }
            $steps[] = $step;
        }

        return $steps;
    }

    /**
     * Expanded follow-steps with a short “why / how” line under each action.
     *
     * @return array<int, array{text: string, detail?: string}>
     */
    public static function richSteps(string $key): array
    {
        $rich = self::richStepsMap();

        return $rich[$key] ?? array_map(
            fn (array $step) => isset($step['detail']) ? $step : ['text' => $step['text'] ?? ''],
            self::followSteps($key),
        );
    }

    /**
     * @return array<string, array<int, array{text: string, detail?: string}>>
     */
    private static function richStepsMap(): array
    {
        return [
            'master-journey' => [
                ['text' => 'Enquiry arrives — Facebook, Instagram, direct call, missed call, website, app booking, or AI chat', 'detail' => 'Social and phone → create lead manually. Website, app, AI chat → lead auto-created, open and update.'],
                ['text' => 'Create a new lead OR open the auto-created lead — fill source, phone, and first remarks', 'detail' => 'Manual: fetch from DM/call. Auto: open chat/request and verify details before proceeding.'],
                ['text' => 'Ask yourself: do I already know what this person wants?', 'detail' => 'If the message only says “hi” or “I called” — you do NOT know yet. That is Unknown until you call.'],
                ['text' => 'If Unknown — outbound call, ask what they need, then change type in panel', 'detail' => 'On the call use the lead qualifier: service need? join as partner? no need now? wrong request?'],
                ['text' => 'If Customer — call for service, problem, address, date/time → Path A or Path B', 'detail' => 'Path A = book directly. Path B = customer wants to speak to provider before booking.'],
                ['text' => 'If Provider — onboarding call, send agreement + documents, add to panel and WhatsApp groups', 'detail' => 'Provider is someone who wants to work with Panun Kaergar, not someone who needs a plumber.'],
                ['text' => 'If Future customer — explain Panun Kaergar services to them, warm WhatsApp, close as Future ✓', 'detail' => 'You inform the customer about Panun Kaergar — not “inform the office”. Tell them what we do, when to call, ask for referrals.'],
                ['text' => 'If Invalid — write why in panel, polite WhatsApp, close as Invalid ✓', 'detail' => 'Example: car repair (we do home services only) or city we do not cover. Still be polite.'],
                ['text' => 'After every call: notes → panel (type + remarks) → WhatsApp → Followup On if another touch needed', 'detail' => 'Do not type in panel during the call. Update immediately after you hang up, before the next lead.'],
                ['text' => 'End with ONE clear status — booking, provider registered, future customer, invalid, or cancelled', 'detail' => 'Open leads with no follow-up date are failures. Every lead must land somewhere.'],
            ],
            'lead-arrival' => [
                ['text' => 'Start every shift — run the checklist on “Where leads come from” slide', 'detail' => 'WhatsApp unread, follow-ups due, Web Bookings, App Custom Requests, Human support, then social apps.'],
                ['text' => 'Facebook / Instagram / YouTube comment or DM → create NEW lead manually in Leads', 'detail' => 'External apps for reading; panel Leads → Add New Lead for tracking. Same day — never leave only in social app.'],
                ['text' => 'Direct or missed call → create/update lead in Leads + missed-call WA within 5 min', 'detail' => 'Phone beats chat. Check missed-call log regularly. Source = Phone.'],
                ['text' => 'WhatsApp human (Active Chats) → reply in panel inbox, link to lead', 'detail' => 'Operations → WhatsApp → Active Chats, or header WhatsApp icon.'],
                ['text' => 'WhatsApp — tag every thread: assignee + chat status + tags (Lead ID, manager, stage)', 'detail' => 'Manage tags in chat header. Status/tags show in chat list so you do not forget open threads.'],
                ['text' => 'Also update lead in panel: Handled By, Customer/Provider Status, Followup On', 'detail' => 'Chat tags are for inbox tracking; lead record is the official handover — keep both in sync.'],
                ['text' => 'AI WhatsApp → Human support tab + Leads filtered by AI Chat', 'detail' => 'Take over, assign yourself, tag thread, verify auto-lead details, then qualify.'],
                ['text' => 'Web Bookings / Web Provider Requests / App Custom Requests → open list, verify linked lead', 'detail' => 'Auto-created — fix missing phone/address in panel, then classify and process.'],
                ['text' => 'Can you pick Customer, Provider, Future, or Invalid right now?', 'detail' => '“Hi I called you” = No → Unknown. “Need plumber tomorrow” = Yes → Customer.'],
                ['text' => 'Not enough info → Unknown → outbound call same day', 'detail' => 'Unknown is temporary until you speak to them or complete 3 no-pickup attempts.'],
                ['text' => 'Enough info → correct type → handling slide + panel + WA + Followup On', 'detail' => 'Every channel ends the same way — fully documented in Leads.'],
            ],
            'classify' => [
                ['text' => 'Read the full enquiry — what is this person actually asking for?', 'detail' => 'Read the DM, listen to the voicemail, check missed-call log — do not guess from phone number alone.'],
                ['text' => 'Vague (“hi”, “I called”, no service) → Unknown — call before anything else', 'detail' => 'Never mark Customer just because they messaged — confirm on a call.'],
                ['text' => 'Needs home service (plumber, electrician, cleaning, etc.) → Customer', 'detail' => 'They want someone to come to their home and fix/clean something.'],
                ['text' => 'Wants to join Panun Kaergar as a service partner → Provider', 'detail' => 'They want to earn by doing jobs for Panun Kaergar customers — not book a job for themselves.'],
                ['text' => 'No service now — saving number, renovation later, just moved → Future customer', 'detail' => 'Valid outcome — not Invalid. They may call back in weeks or months.'],
                ['text' => 'Wrong service or area Panun Kaergar does not cover → Invalid', 'detail' => 'Example: legal advice, delivery outside Kashmir service area. Document exact request.'],
                ['text' => 'Pick exactly ONE type in panel', 'detail' => 'Panel has one type field — never leave two types in remarks only.'],
                ['text' => 'If you just qualified an Unknown on a call — change the type on that same call', 'detail' => 'Before hanging up, you should already know: Customer, Provider, Future, or Invalid.'],
            ],
            'followup-daily' => [
                ['text' => 'Start shift — log into admin panel', 'detail' => 'Check you can see leads, follow-ups, and phone lines before taking contacts.'],
                ['text' => 'Open “Followups Pending Till Today” — your main work queue', 'detail' => 'This list shows every lead that needs action today, sorted by urgency.'],
                ['text' => 'Work in order: ringing phone → emergency → hot booking → due today → new async messages', 'detail' => 'Hot booking = customer waiting for provider. Emergency = flooding, no power, safety issue.'],
                ['text' => 'Phone rings or urgent lead while busy?', 'detail' => 'Live voice contact always interrupts non-urgent panel work.'],
                ['text' => 'Live call or emergency → answer, handle fully (panel + WhatsApp), then return to queue', 'detail' => 'Finish the lead properly — do not answer and then leave panel half-empty.'],
                ['text' => 'Non-urgent DM only → quick reply, create/update lead, continue queue', 'detail' => 'Acknowledge fast (“Thanks, we will call you shortly”) then keep working follow-ups.'],
                ['text' => 'After each contact: notes → panel update → WhatsApp → Followup On + urgency', 'detail' => 'Same rule as mission slide — every touch documented before moving on.'],
                ['text' => 'End shift — no overdue follow-ups without action or closed status', 'detail' => 'Hand over clean queue to next shift — remarks must explain where each lead stands.'],
            ],
            'unknown-call' => [
                ['text' => 'Open the Unknown lead in panel — confirm phone number and source (Facebook, missed call, etc.)', 'detail' => 'You cannot classify until you speak to them. Unknown is temporary.'],
                ['text' => 'Outbound call — introduce Panun Kaergar and ask what help they need', 'detail' => 'Listen and take notes on paper or notepad. Do not update the panel while on the call.'],
                ['text' => 'User picked up? → Run the lead qualifier on the same call', 'detail' => 'Ask: home service need? want to join as partner? saving number for later? wrong request?'],
                ['text' => 'Reclassify in panel immediately — pick exactly ONE type', 'detail' => 'Customer, Provider, Future customer, or Invalid. Never leave as Unknown after a successful call.'],
                ['text' => 'Call ends → update panel from your notes (type, full remarks, urgency)', 'detail' => 'Include what they said, address if given, and next action.'],
                ['text' => 'Send WhatsApp same minute → set Followup On if another touch is needed', 'detail' => 'Use missed-call template if they did not answer; summary template if they did.'],
                ['text' => 'Panel complete → go to the handling slide for the new type', 'detail' => 'Customer → booking slides. Provider → onboarding. Future / Invalid → those slides.'],
                ['text' => 'User did NOT pick up? → go to Unknown no-pickup slide (max 3 follow-ups)', 'detail' => 'Do not guess the type. Start Attempt 1/3 same day.'],
            ],
            'unknown-no-pickup' => [
                ['text' => 'Attempt 1 — same day: send WhatsApp (missed-call template) + Add follow-up → Taken → Call', 'detail' => 'Tell them Panun Kaergar tried calling about their enquiry.'],
                ['text' => 'Panel — Attempt 1/3 in remarks + Followup On next working day', 'detail' => 'Keep type as Unknown until they answer. Log follow-up row, not remarks only.'],
                ['text' => 'Attempt 2 — on Followup On: call again → no pickup → WA + Attempt 2/3 + new Followup On', 'detail' => 'Add follow-up row each time.'],
                ['text' => 'Attempt 3 — final call → no pickup → WA + Attempt 3/3 in remarks + follow-ups tab', 'detail' => 'Last attempt before close.'],
                ['text' => 'Picked up on Attempt 2 or 3? → qualify on that call and reclassify', 'detail' => 'Same as Unknown call slide — one type, full remarks, WhatsApp after call.'],
                ['text' => 'All 3 failed — was enquiry vague only, or did DM/form show customer job details?', 'detail' => 'Path C: Instagram DM with service + address but no pickup = documented need.'],
                ['text' => 'Vague only ("hi", missed call) → Mark as Invalid → Did not Know About Enquiry', 'detail' => 'List every attempt date in remarks + 3 follow-up rows.'],
                ['text' => 'DM/form had service details (Path C) → Mark as Customer → Change Status Cancel → No Response From Customer', 'detail' => 'Not Invalid — customer need was documented, contact failed. No provider group post.'],
            ],
            'customer-booking' => [
                ['text' => 'Customer lead open — confirm they need a home service (plumbing, electrical, cleaning, etc.)', 'detail' => 'If type is still Unknown, you should have classified on the qualify call first.'],
                ['text' => 'Outbound call — collect full details: service type, exact problem, complete address, preferred date and time', 'detail' => 'Notes on call only. Providers and the panel both need enough detail to act.'],
                ['text' => 'Customer picked up? → after call update panel as Customer with full remarks', 'detail' => 'Problem + location + timing in remarks. Set urgency if emergency.'],
                ['text' => 'Send WhatsApp summary of what was discussed — same minute as panel update', 'detail' => 'Template: service, address, date/time, and that you are finding a partner.'],
                ['text' => 'Ask: does customer want to book directly, or talk to a provider first?', 'detail' => 'Direct = Path A. Wants discussion = Path B. Wrong path wastes time.'],
                ['text' => 'No pickup? → Customer no-pickup slide (max 3 follow-ups)', 'detail' => 'Do not post in provider group until you have spoken to the customer.'],
                ['text' => 'Yes to direct booking → Path A slide', 'detail' => 'Group post, ₹100, Create Booking.'],
                ['text' => 'Yes to discussion first → Path B slide', 'detail' => 'Group post for discussion, conference call, then book or follow up.'],
            ],
            'customer-no-pickup' => [
                ['text' => 'Attempt 1 — same day: WhatsApp with enquiry / summary message', 'detail' => 'Reference their service need if known from DM; otherwise use general Panun Kaergar enquiry message.'],
                ['text' => 'Panel — “Attempt 1/3 — customer no pickup” + Followup On next day', 'detail' => 'Keep type Customer if already set; otherwise Unknown until they answer.'],
                ['text' => 'Attempt 2 — call on Followup On → if no pickup, WhatsApp + “Attempt 2/3” + new Followup On', 'detail' => 'Each attempt must be documented — managers review remarks.'],
                ['text' => 'Attempt 3 — final call → if no pickup, WhatsApp + “Attempt 3/3”', 'detail' => 'Last chance before close.'],
                ['text' => 'Picked up on any attempt? → collect full details → Path A or Path B', 'detail' => 'Treat it like a fresh customer call — complete panel + WhatsApp before group post.'],
                ['text' => 'All 3 failed? → all attempt dates in remarks', 'detail' => 'Same documentation rule as Unknown no-pickup.'],
                ['text' => 'Close — Change Status → Cancel → No Response From Customer', 'detail' => 'Do not leave the lead open with no follow-up date.'],
            ],
            'direct-booking' => [
                ['text' => 'WhatsApp customer — service, address, date/time; finding partner for that slot', 'detail' => 'Mandatory after every call. Customer should never wonder if Panun Kaergar is working on their request.'],
                ['text' => 'Panel — Customer type, full remarks, Followup On if waiting on providers', 'detail' => 'Remarks = what you posted to the group so anyone can pick up the lead.'],
                ['text' => 'Post in provider WhatsApp group — standard English format with Lead ID on top', 'detail' => '*Service Request – #{ID}* → service + problem → 📍 address → 🕐 preferred time → availability ask + alternate slot request.'],
                ['text' => '10-minute SLA — anyone replied ready for service?', 'detail' => 'Timer starts when group message is sent.'],
                ['text' => 'Yes, provider ready → collect ₹100 from customer → Create Booking in panel', 'detail' => '₹100 before booking is saved — no exceptions.'],
                ['text' => 'Send booking confirmation WhatsApp to customer and provider', 'detail' => 'Both sides must know booking ID, time, and address.'],
                ['text' => 'No reply in 10 min → call nearby providers yourself', 'detail' => 'Do not go silent. WhatsApp customer: still checking availability.'],
                ['text' => 'Alternate times offered? → call customer, share slots, book or set follow-up', 'detail' => 'Customer chooses slot → ₹100 → Create Booking.'],
                ['text' => 'Nobody available → tell customer honestly, WhatsApp update, follow-up or Change Status → Cancel with reason ✓', 'detail' => 'Professional close beats ghosting the customer.'],
            ],
            'discussion-booking' => [
                ['text' => 'WhatsApp customer — details + we will connect you with a provider to discuss', 'detail' => 'Set expectation: short discussion call, not immediate booking.'],
                ['text' => 'Panel — Customer, full remarks, Followup On while waiting', 'detail' => 'Note “Path B — wants provider discussion first”.'],
                ['text' => 'Provider group — Discussion Request format with Lead ID', 'detail' => 'Same structure as Path A but title “Discussion Request” and note customer wants to talk before booking.'],
                ['text' => 'Provider ready within 10 min? → brief customer pre-call, then conference call', 'detail' => 'You stay on the line or coordinate — do not drop the customer.'],
                ['text' => 'No provider in 10 min → call providers (same as Path A no-reply branch)', 'detail' => 'Keep customer updated on WhatsApp throughout.'],
                ['text' => 'After conference — customer wants service? → ₹100 → Create Booking → confirmations', 'detail' => 'Same booking rules as Path A once they commit.'],
                ['text' => 'Customer will decide later? → note concern in panel, set Followup On, WhatsApp summary', 'detail' => 'Example: price concern — offer alternate provider on follow-up.'],
                ['text' => 'Customer denies service? → Change Status → Cancel with reason, close chat, document in remarks', 'detail' => 'Cancelled with clear reason — still brand-safe. Not Invalid.'],
            ],
            'provider-onboarding' => [
                ['text' => 'Provider lead — person wants to join Panun Kaergar as a home-service partner', 'detail' => 'Different from a customer who needs a plumber. They want to receive jobs from us.'],
                ['text' => 'Can they take a brief onboarding call now?', 'detail' => 'If busy → schedule Followup On — max 3 attempts to reach them, same as customer no-pickup rule.'],
                ['text' => 'Yes → Step 1 brief call now', 'detail' => 'Explain Panun Kaergar, how partners get jobs, commission, which services and areas we need.'],
                ['text' => 'No / later → schedule follow-up call on Followup On date (max 3 total attempts)', 'detail' => 'Document each attempt in remarks + WhatsApp after each touch.'],
                ['text' => 'Step 1 — Brief call: Panun Kaergar model, commission, service type, area, document deadline', 'detail' => 'They must understand expectations before sending documents.'],
                ['text' => 'Step 2 — WhatsApp agreement + list of required documents + submit-by date', 'detail' => 'Example docs: ID, skill proof, bank details — per onboarding checklist.'],
                ['text' => 'Did they send documents by the deadline?', 'detail' => 'Check WhatsApp — if not, follow up (max 3 total follow-ups on docs).'],
                ['text' => 'Documents received → Step 3 final call', 'detail' => 'Explain how jobs arrive, 10-minute reply rule in provider group, payment flow.'],
                ['text' => 'No documents after follow-ups → Change Status → Cancel → Not Intrested + full remarks', 'detail' => 'Document all follow-up dates and WhatsApp sent.'],
                ['text' => 'Step 3 — Final call: job flow, WhatsApp groups, 10-minute reply rule', 'detail' => 'Provider must know they must reply “YES + name” in group within 10 minutes.'],
                ['text' => 'Step 4 — Providers menu → Add New Provider → fill wizard (name, phone, trade, zones) → save', 'detail' => 'Leads and bookings → Providers → Add New Provider (/admin/provider/create). Phone must match WhatsApp.'],
                ['text' => 'Add to correct provider WhatsApp group(s) for their trade and area', 'detail' => 'Checklist tick “added to group”. Is Added in Panel link on lead should match new provider record.'],
                ['text' => 'End — Provider registered ✓', 'detail' => 'Valid success state — they can now receive job posts from Panun Kaergar.'],
            ],
            'future-customer' => [
                ['text' => 'Future customer — they do not need a home service today', 'detail' => 'Common reasons: saving our number, renovation in a few months, just moved to Kashmir.'],
                ['text' => 'On call — confirm why they contacted us and why not booking now', 'detail' => 'Write the reason clearly — you will pick the same reason in panel dropdown.'],
                ['text' => 'Panel — type Future customer + select reason from dropdown (required field)', 'detail' => 'Reason examples: saving number, renovation later, no immediate need.'],
                ['text' => 'Tell the customer what Panun Kaergar offers — plumbing, electrical, cleaning, repairs, etc.', 'detail' => 'WHO you inform: the customer. WHAT: our services and coverage. WHY: so they call us when need arises, not a random competitor.'],
                ['text' => 'Ask: “Anyone you know who needs home service right now?” — note referral in remarks', 'detail' => 'Referrals can become Customer leads today — capture name and phone if given.'],
                ['text' => 'Warm-close WhatsApp — thank them, ask to save Panun Kaergar number 8899881555', 'detail' => 'Professional close — they should feel welcome to call later.'],
                ['text' => 'Contact them again later? → Add Outbound Enquiry on the Future customer lead', 'detail' => 'Sidebar or section button — log call/message, status, datetime, remarks. Count shows on hero badge.'],
                ['text' => 'End — Future customer ✓ (valid close, not a failure)', 'detail' => 'Lead is closed but relationship kept — different from Invalid or Cancelled.'],
            ],
            'invalid-lead' => [
                ['text' => 'Invalid — Panun Kaergar cannot help with this request', 'detail' => 'Wrong service (e.g. mobile repair) or location outside our service area — not “I don’t feel like helping”.'],
                ['text' => 'Write in remarks exactly what they asked for — service name + location', 'detail' => 'Managers audit invalid closes — vague remarks get rejected.'],
                ['text' => 'Panel — type Invalid + pick reason (service not offered / area not covered / etc.)', 'detail' => 'Reason dropdown must match what you wrote in remarks.'],
                ['text' => 'Polite WhatsApp — sorry we cannot help with that specific request', 'detail' => 'Briefly mention what Panun Kaergar does offer (home services in our coverage area).'],
                ['text' => 'End — Invalid ✓ — lead closed professionally', 'detail' => 'Still brand-safe — customer should respect Panun Kaergar even if we cannot serve them.'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
