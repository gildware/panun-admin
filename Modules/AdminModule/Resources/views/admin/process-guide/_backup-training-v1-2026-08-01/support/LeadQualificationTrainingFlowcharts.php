<?php

namespace Modules\AdminModule\Support;

class LeadQualificationTrainingFlowcharts
{
    /**
     * Mini flowcharts for training slides.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function all(): array
    {
        return [
            'master-journey' => [
                ['kind' => 'start', 'label' => 'Lead arrives'],
                ['kind' => 'action', 'label' => 'Record source + create/verify lead'],
                ['kind' => 'action', 'label' => 'Classify'],
                ['kind' => 'decision', 'label' => 'Lead type?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Customer', 'tone' => 'success', 'to' => 'Booking path'],
                    ['label' => 'Provider', 'tone' => 'success', 'to' => 'Onboarding'],
                    ['label' => 'Unknown', 'tone' => 'warn', 'to' => 'Outbound call'],
                    ['label' => 'Future', 'tone' => 'neutral', 'to' => 'Nurture + close'],
                    ['label' => 'Invalid', 'tone' => 'danger', 'to' => 'Polite close'],
                ]],
                ['kind' => 'action', 'label' => 'Follow path → update panel each step'],
                ['kind' => 'end', 'label' => 'One clear end state', 'tone' => 'success'],
            ],
            'lead-arrival' => [
                ['kind' => 'start', 'label' => 'Enquiry comes in'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Social / Call', 'tone' => 'neutral', 'to' => 'Create lead manually'],
                    ['label' => 'Panel / App', 'tone' => 'neutral', 'to' => 'Verify auto-created lead'],
                ]],
                ['kind' => 'action', 'label' => 'Capture: name, phone, service, area, date'],
                ['kind' => 'action', 'label' => 'Tag source in panel'],
                ['kind' => 'end', 'label' => '→ Classify (Step 2)', 'tone' => 'success'],
            ],
            'classify' => [
                ['kind' => 'decision', 'label' => 'What do you know about the lead?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Needs service', 'tone' => 'success', 'to' => 'Customer → Step 4'],
                    ['label' => 'Wants to join as provider', 'tone' => 'success', 'to' => 'Provider → Step 5'],
                    ['label' => 'No need now', 'tone' => 'neutral', 'to' => 'Future customer'],
                    ['label' => 'Wrong service/area', 'tone' => 'danger', 'to' => 'Invalid'],
                    ['label' => 'Not enough info', 'tone' => 'warn', 'to' => 'Unknown → call'],
                ]],
            ],
            'unknown-call' => [
                ['kind' => 'start', 'label' => 'Unknown lead'],
                ['kind' => 'action', 'label' => 'Call user'],
                ['kind' => 'decision', 'label' => 'Picked up?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Yes', 'tone' => 'success', 'to' => 'Ask qualifier questions → re-classify'],
                    ['label' => 'No', 'tone' => 'warn', 'to' => 'WhatsApp + follow-up tomorrow'],
                ]],
            ],
            'customer-booking' => [
                ['kind' => 'start', 'label' => 'Customer lead'],
                ['kind' => 'action', 'label' => 'Call — get service, address, date/time'],
                ['kind' => 'decision', 'label' => 'Talk to provider first?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'No', 'tone' => 'success', 'to' => 'Path A: Direct booking'],
                    ['label' => 'Yes', 'tone' => 'success', 'to' => 'Path B: Discussion first'],
                ]],
            ],
            'direct-booking' => [
                ['kind' => 'action', 'label' => 'WhatsApp customer — confirm details'],
                ['kind' => 'action', 'label' => 'Post in provider group (10 min SLA)'],
                ['kind' => 'decision', 'label' => 'Provider available?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Yes', 'tone' => 'success', 'to' => '₹100 → create booking → notify both'],
                    ['label' => 'No reply', 'tone' => 'warn', 'to' => 'Call providers → alternate slots'],
                    ['label' => 'Still none', 'tone' => 'danger', 'to' => 'Update customer → cancel if no match'],
                ]],
            ],
            'provider-onboarding' => [
                ['kind' => 'start', 'label' => 'Provider lead'],
                ['kind' => 'action', 'label' => 'Brief call — explain PK + commission'],
                ['kind' => 'action', 'label' => 'Send agreement + document list'],
                ['kind' => 'decision', 'label' => 'Documents received?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Yes', 'tone' => 'success', 'to' => 'Final call → add to panel'],
                    ['label' => 'No', 'tone' => 'warn', 'to' => 'Follow-up (max 3) → cancel'],
                ]],
                ['kind' => 'end', 'label' => 'Provider registered ✓', 'tone' => 'success'],
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
