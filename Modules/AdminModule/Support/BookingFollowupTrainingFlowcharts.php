<?php

namespace Modules\AdminModule\Support;

class BookingFollowupTrainingFlowcharts
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function all(): array
    {
        return [
            'create-booking' => [
                ['kind' => 'start', 'label' => 'Prerequisites met?'],
                ['kind' => 'decision', 'label' => 'Entry path'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Lead', 'tone' => 'success', 'to' => 'Create Booking for this Lead'],
                    ['label' => 'Web / App / WhatsApp', 'tone' => 'success', 'to' => 'Queue → Create Booking'],
                    ['label' => 'Direct', 'tone' => 'neutral', 'to' => 'Add New Booking'],
                ]],
                ['kind' => 'action', 'label' => 'Preview → Store → Accepted'],
                ['kind' => 'action', 'label' => 'WhatsApp + first follow-up'],
                ['kind' => 'end', 'label' => '→ Follow-up phase', 'tone' => 'success'],
            ],
            'follow-up' => [
                ['kind' => 'start', 'label' => 'Daily follow-up queue'],
                ['kind' => 'action', 'label' => 'Touchpoint: booking confirm'],
                ['kind' => 'decision', 'label' => 'Service 3+ days away?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'No (same-day / 1–2 days)', 'tone' => 'neutral', 'to' => 'Skip day-before → service day only'],
                    ['label' => 'Yes (3+ days out)', 'tone' => 'success', 'to' => 'Day before: provider first'],
                ]],
                ['kind' => 'decision', 'label' => 'Service day reached?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Not yet', 'tone' => 'neutral', 'to' => 'WhatsApp if needed — no daily calls'],
                    ['label' => 'Yes', 'tone' => 'success', 'to' => 'Morning: provider → customer → 1hr check → Ongoing'],
                ]],
                ['kind' => 'decision', 'label' => 'Provider available?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Yes', 'tone' => 'success', 'to' => 'Job done → bill breakdown → panel → customer confirm'],
                    ['label' => 'No — before Ongoing', 'tone' => 'warn', 'to' => 'Re Assign or On hold'],
                    ['label' => 'No-show', 'tone' => 'danger', 'to' => 'On hold / Cancel + feedback tag'],
                ]],
                ['kind' => 'end', 'label' => '→ Feedback & Completed', 'tone' => 'success'],
            ],
            'status-path' => [
                ['kind' => 'start', 'label' => 'Pending (app only)'],
                ['kind' => 'action', 'label' => 'Accepted'],
                ['kind' => 'action', 'label' => 'Ongoing'],
                ['kind' => 'decision', 'label' => 'Outcome'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Normal', 'tone' => 'success', 'to' => 'Completed'],
                    ['label' => 'Pause', 'tone' => 'warn', 'to' => 'On hold'],
                    ['label' => 'Unusual visit', 'tone' => 'warn', 'to' => 'Special scenario'],
                    ['label' => 'Cancel', 'tone' => 'danger', 'to' => 'Canceled'],
                ]],
            ],
            'special-scenario' => [
                ['kind' => 'start', 'label' => 'Ongoing or Hold after visit'],
                ['kind' => 'decision', 'label' => 'What happened?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'No job done', 'tone' => 'warn', 'to' => 'Cancel After Visit'],
                    ['label' => 'Minimal service', 'tone' => 'neutral', 'to' => 'Complete visit only'],
                    ['label' => 'Underpaid', 'tone' => 'danger', 'to' => 'Loss making'],
                ]],
                ['kind' => 'action', 'label' => 'Payments until due = 0'],
                ['kind' => 'end', 'label' => 'Save and cancel or complete', 'tone' => 'success'],
            ],
            'dispute-close' => [
                ['kind' => 'start', 'label' => 'Reopened or serious dispute'],
                ['kind' => 'action', 'label' => 'Pick dispute reason'],
                ['kind' => 'action', 'label' => 'Refund split — company + provider pools'],
                ['kind' => 'end', 'label' => 'Disputed and Completed / Cancelled', 'tone' => 'success'],
            ],
        ];
    }

    /** @return array<int, array{key: string, title: string}> */
    public static function referenceCharts(): array
    {
        return [
            ['key' => 'create-booking', 'title' => 'Create booking'],
            ['key' => 'follow-up', 'title' => 'Follow up'],
            ['key' => 'status-path', 'title' => 'Status path'],
            ['key' => 'special-scenario', 'title' => 'Special scenarios'],
            ['key' => 'dispute-close', 'title' => 'Dispute and close'],
        ];
    }

    /** @return array<int, array{text: string, detail?: string}> */
    public static function richSteps(string $key): array
    {
        $map = [
            'create-booking' => [
                ['text' => 'Verify prerequisites card group before any path', 'detail' => 'Customer, address, service, provider — all green.'],
                ['text' => 'Preview → Store — admin path sets Accepted', 'detail' => 'Check cart total and provider on preview screen.'],
                ['text' => 'After save: assignee + Follow-ups tab + WhatsApp both parties', 'detail' => 'First follow-up same day as create.'],
            ],
            'follow-up' => [
                ['text' => 'Touchpoint 1 — always at booking', 'detail' => 'Call + WhatsApp customer and provider.'],
                ['text' => 'Touchpoint 2 — day before only if 3+ days out', 'detail' => 'Provider first; skip for same-day and 1–2 days.'],
                ['text' => 'Touchpoint 3 — service day', 'detail' => 'Morning provider → customer if needed → 1hr WhatsApp → Ongoing.'],
                ['text' => 'After job — provider bill → panel → customer confirm → feedback', 'detail' => 'Due zero before Completed.'],
            ],
        ];

        return $map[$key] ?? [];
    }

    /** @return array<int, array{icon: string, text: string}> */
    public static function followSteps(string $key): array
    {
        if ($key !== 'follow-up') {
            return [];
        }

        return [
            ['icon' => 'playlist_add_check', 'text' => 'Open Booking Followups Pending Till Today'],
            ['icon' => 'call', 'text' => 'Touchpoint calls per schedule — provider first on service day'],
            ['icon' => 'chat', 'text' => 'WhatsApp between touchpoints — not daily harassment calls'],
            ['icon' => 'engineering', 'text' => 'Set Ongoing when provider starts on service day'],
            ['icon' => 'receipt_long', 'text' => 'Bill breakdown in panel before Completed'],
        ];
    }

    /** @return array<int, array<string, mixed>>|null */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
