<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Services\LeadFollowupService;

/**
 * Employee ranking score: quantity of work (+) and late follow-up penalties (−).
 */
class EmployeeProgressScoreService
{
    public const POINTS_BOOKINGS_CREATED = 2;

    public const POINTS_BOOKINGS_COMPLETED = 10;

    public const POINTS_LEADS_HANDLED = 3;

    public const POINTS_PROVIDERS_REGISTERED = 10;

    /** @var list<array{key: string, max_minutes: int|null, points: int, label: string}> */
    public const LATE_PENALTY_BUCKETS = [
        ['key' => 'late_1h', 'max_minutes' => 60, 'points' => 1, 'label' => 'Follow up late by ≤1 hour'],
        ['key' => 'late_2h', 'max_minutes' => 120, 'points' => 2, 'label' => 'Follow up late by ≤2 hours'],
        ['key' => 'late_4h', 'max_minutes' => 240, 'points' => 3, 'label' => 'Follow up late by ≤4 hours'],
        ['key' => 'late_8h', 'max_minutes' => 480, 'points' => 5, 'label' => 'Follow up late by ≤8 hours'],
        ['key' => 'late_over_8h', 'max_minutes' => null, 'points' => 10, 'label' => 'Follow up late by >8 hours'],
    ];

    public function __construct(
        private readonly LeadFollowupService $leadFollowupService,
    ) {}

    /**
     * @return list<array{key: string, label: string, points: int, sign: string}>
     */
    public static function weightLegend(): array
    {
        $legend = [
            ['key' => 'bookings_created', 'label' => translate('Bookings_created') ?? 'Bookings created', 'points' => self::POINTS_BOOKINGS_CREATED, 'sign' => '+'],
            ['key' => 'bookings_completed', 'label' => translate('Bookings_Completed') ?? 'Bookings completed', 'points' => self::POINTS_BOOKINGS_COMPLETED, 'sign' => '+'],
            ['key' => 'leads_handled', 'label' => translate('Leads_Handled') ?? 'Leads handled', 'points' => self::POINTS_LEADS_HANDLED, 'sign' => '+'],
            ['key' => 'providers_registered', 'label' => translate('Progress_provider_registered') ?? 'Providers registered', 'points' => self::POINTS_PROVIDERS_REGISTERED, 'sign' => '+'],
        ];

        foreach (self::LATE_PENALTY_BUCKETS as $bucket) {
            $legend[] = [
                'key' => $bucket['key'],
                'label' => self::lateBucketLabel($bucket),
                'points' => $bucket['points'],
                'sign' => '−',
            ];
        }

        return $legend;
    }

    /**
     * @param  array{key: string, label: string}  $bucket
     */
    public static function lateBucketLabel(array $bucket): string
    {
        $key = 'Progress_'.$bucket['key'];
        $translated = translate($key);

        return ($translated !== '' && $translated !== $key) ? $translated : $bucket['label'];
    }

    /**
     * @param  list<array<string, mixed>>  $employeeTotals
     * @return list<array<string, mixed>>
     */
    public function rankEmployees(
        array $employeeTotals,
        Collection $employees,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): array {
        $employeeIds = collect($employeeTotals)
            ->map(fn (array $row) => (string) ($row['employee_id'] ?? ''))
            ->filter()
            ->values()
            ->all();

        if ($employeeIds === []) {
            $employeeIds = $employees->pluck('id')->map(fn ($id) => (string) $id)->filter()->values()->all();
        }

        $leadsByEmployee = $this->leadsHandledByEmployee($employeeIds, $periodStart, $periodEnd);
        $providersByEmployee = $this->providersRegisteredByEmployee($employeeIds, $periodStart, $periodEnd);
        $lateByEmployee = $this->lateFollowupPenaltiesByEmployee($employeeIds, $periodStart, $periodEnd);
        $ranked = [];

        foreach ($employeeTotals as $employeeRow) {
            $employeeId = (string) ($employeeRow['employee_id'] ?? '');
            if ($employeeId === '') {
                continue;
            }

            $ranked[] = $this->scoreEmployeeRow(
                $employeeRow,
                (int) ($leadsByEmployee[$employeeId] ?? 0),
                (int) ($providersByEmployee[$employeeId] ?? 0),
                $lateByEmployee[$employeeId] ?? $this->emptyLatePenalty(),
            );
        }

        usort($ranked, function (array $a, array $b) {
            $scoreCmp = ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }

            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        foreach ($ranked as $index => &$row) {
            $row['rank'] = $index + 1;
        }
        unset($row);

        return $ranked;
    }

    /**
     * @param  array<string, mixed>  $employeeRow
     * @param  array{total_count: int, total_points: int, buckets: array<string, array{count: int, unit_points: int, points: int, label: string}>}  $latePenalty
     * @return array<string, mixed>
     */
    public function scoreEmployeeRow(
        array $employeeRow,
        ?int $leadsHandledOverride = null,
        int $providersRegistered = 0,
        array $latePenalty = [],
    ): array {
        $bookingsCreated = (int) ($employeeRow['bookings_created'] ?? 0);
        $bookingsCompleted = (int) ($employeeRow['bookings_completed'] ?? 0);
        $leadsHandled = $leadsHandledOverride ?? (int) ($employeeRow['leads_assigned'] ?? $employeeRow['leads_handled'] ?? 0);
        $latePenalty = $latePenalty === [] ? $this->emptyLatePenalty() : $latePenalty;

        $marks = [
            $this->markLine(
                'bookings_created',
                translate('Bookings_created') ?? 'Bookings created',
                $bookingsCreated,
                self::POINTS_BOOKINGS_CREATED,
                true,
            ),
            $this->markLine(
                'bookings_completed',
                translate('Bookings_Completed') ?? 'Bookings completed',
                $bookingsCompleted,
                self::POINTS_BOOKINGS_COMPLETED,
                true,
            ),
            $this->markLine(
                'leads_handled',
                translate('Leads_Handled') ?? 'Leads handled',
                $leadsHandled,
                self::POINTS_LEADS_HANDLED,
                true,
            ),
            $this->markLine(
                'providers_registered',
                translate('Progress_provider_registered') ?? 'Providers registered',
                $providersRegistered,
                self::POINTS_PROVIDERS_REGISTERED,
                true,
            ),
        ];

        foreach (self::LATE_PENALTY_BUCKETS as $bucket) {
            $bucketKey = $bucket['key'];
            $bucketData = $latePenalty['buckets'][$bucketKey] ?? [];
            $count = (int) ($bucketData['count'] ?? 0);
            $marks[] = $this->markLine(
                $bucketKey,
                self::lateBucketLabel($bucket),
                $count,
                (int) ($bucketData['unit_points'] ?? $bucket['points']),
                false,
            );
        }

        $quantityScore = 0;
        $penaltyScore = 0;
        foreach ($marks as $mark) {
            if (! empty($mark['positive'])) {
                $quantityScore += (int) ($mark['points'] ?? 0);
            } else {
                $penaltyScore += (int) ($mark['points'] ?? 0);
            }
        }
        $score = $quantityScore + $penaltyScore;

        return [
            'employee_id' => (string) ($employeeRow['employee_id'] ?? ''),
            'name' => (string) ($employeeRow['employee_name'] ?? ''),
            'bookings' => $bookingsCreated,
            'bookings_completed' => $bookingsCompleted,
            'leads' => $leadsHandled,
            'providers_registered' => $providersRegistered,
            'chats' => (int) ($employeeRow['whatsapp_replies'] ?? 0),
            'followups' => (int) ($employeeRow['lead_followups'] ?? 0) + (int) ($employeeRow['booking_followups'] ?? 0),
            'late_followups' => (int) ($latePenalty['total_count'] ?? 0),
            'late_penalty_points' => (int) ($latePenalty['total_points'] ?? 0),
            'missed_followups' => 0,
            'cancelled' => (int) ($employeeRow['bookings_cancelled'] ?? 0),
            'quantity_score' => $quantityScore,
            'penalty_score' => $penaltyScore,
            'score' => $score,
            'marks' => $marks,
            'revenue' => with_currency_symbol(0),
        ];
    }

    /**
     * Same definition as Leads tab: leads assigned to the employee with received date in period.
     *
     * @param  list<string>  $employeeIds
     * @return array<string, int>
     */
    public function leadsHandledByEmployee(array $employeeIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $counts = array_fill_keys($employeeIds, 0);

        if ($employeeIds === []) {
            return $counts;
        }

        $rows = Lead::query()
            ->selectRaw('handled_by, COUNT(*) as cnt')
            ->whereIn('handled_by', $employeeIds)
            ->whereBetween('date_time_of_lead_received', [
                $periodStart->copy()->startOfDay(),
                $periodEnd->copy()->endOfDay(),
            ])
            ->whereNotNull('handled_by')
            ->where('handled_by', '!=', Lead::HANDLED_BY_AI)
            ->groupBy('handled_by')
            ->pluck('cnt', 'handled_by');

        foreach ($rows as $id => $count) {
            $counts[(string) $id] = (int) $count;
        }

        return $counts;
    }

    /**
     * Provider leads marked Registered (base_type completed) by the employee in the period.
     *
     * @param  list<string>  $employeeIds
     * @return array<string, int>
     */
    public function providersRegisteredByEmployee(array $employeeIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $counts = array_fill_keys($employeeIds, 0);

        if ($employeeIds === []) {
            return $counts;
        }

        $completedStatusIds = ProviderLeadStatus::query()
            ->where('base_type', 'completed')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if ($completedStatusIds === []) {
            return $counts;
        }

        $histories = LeadTypeHistory::query()
            ->where('type', Lead::TYPE_PROVIDER)
            ->whereIn('created_by', $employeeIds)
            ->whereBetween('created_at', [
                $periodStart->copy()->startOfDay(),
                $periodEnd->copy()->endOfDay(),
            ])
            ->get(['lead_id', 'created_by', 'data']);

        $seen = [];
        foreach ($histories as $history) {
            $data = is_array($history->data) ? $history->data : [];
            $statusId = isset($data['provider_lead_status_id']) ? (string) $data['provider_lead_status_id'] : '';
            if ($statusId === '' || ! in_array($statusId, $completedStatusIds, true)) {
                continue;
            }

            $employeeId = (string) ($history->created_by ?? '');
            $leadId = (string) ($history->lead_id ?? '');
            if ($employeeId === '' || $leadId === '' || ! array_key_exists($employeeId, $counts)) {
                continue;
            }

            $dedupeKey = $employeeId.'|'.$leadId;
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;
            $counts[$employeeId]++;
        }

        return $counts;
    }

    /**
     * Late follow-ups with hour-tier penalties (lead + booking), attributed to the performer.
     *
     * @param  list<string>  $employeeIds
     * @return array<string, array{total_count: int, total_points: int, buckets: array<string, array{count: int, unit_points: int, points: int, label: string}>}>
     */
    public function lateFollowupPenaltiesByEmployee(array $employeeIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $late = [];
        foreach ($employeeIds as $employeeId) {
            $late[$employeeId] = $this->emptyLatePenalty();
        }

        if ($employeeIds === []) {
            return $late;
        }

        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        $periodLeadFollowups = LeadFollowup::query()
            ->whereIn('created_by', $employeeIds)
            ->whereNotNull('followup_at')
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->get(['id', 'lead_id', 'created_by', 'followup_at', 'due_followup_at']);

        $leadIds = $periodLeadFollowups->pluck('lead_id')->map(fn ($id) => (string) $id)->filter()->unique()->values()->all();
        if ($leadIds !== []) {
            $leads = Lead::query()
                ->whereIn('id', $leadIds)
                ->get(['id', 'date_time_of_lead_received'])
                ->keyBy(fn (Lead $lead) => (string) $lead->id);

            $historyByLead = LeadFollowup::query()
                ->whereIn('lead_id', $leadIds)
                ->whereNotNull('followup_at')
                ->orderBy('followup_at')
                ->get(['id', 'lead_id', 'followup_at', 'due_followup_at', 'next_followup_at'])
                ->groupBy(fn (LeadFollowup $followup) => (string) $followup->lead_id);

            $dueByFollowupId = [];
            foreach ($historyByLead as $leadId => $history) {
                $lead = $leads->get((string) $leadId);
                if (! $lead) {
                    continue;
                }
                foreach ($this->leadFollowupService->buildFollowupDelayMeta($lead, $history) as $followupId => $meta) {
                    $dueByFollowupId[(int) $followupId] = $meta['due_at'] ?? null;
                }
            }

            foreach ($periodLeadFollowups as $followup) {
                $due = $dueByFollowupId[(int) $followup->id] ?? $followup->due_followup_at;
                if (! $due instanceof Carbon) {
                    $due = $due ? Carbon::parse($due) : null;
                }
                if (! $due || $followup->followup_at->lte($due)) {
                    continue;
                }

                $employeeId = (string) ($followup->created_by ?? '');
                if ($employeeId === '' || ! array_key_exists($employeeId, $late)) {
                    continue;
                }

                $delayMinutes = (int) round($due->diffInMinutes($followup->followup_at));
                $this->addLatePenalty($late[$employeeId], $delayMinutes);
            }
        }

        $bookingFollowups = BookingFollowup::query()
            ->whereIn('created_by', $employeeIds)
            ->whereNotNull('followup_at')
            ->whereIn('status', ['completed', 'rescheduled'])
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->get(['created_by', 'followup_at', 'due_followup_at', 'date', 'status']);

        foreach ($bookingFollowups as $followup) {
            if ($followup->isRescheduled()) {
                continue;
            }

            $dueRaw = $followup->due_followup_at ?? $followup->date;
            if (! $dueRaw) {
                continue;
            }
            $due = $dueRaw instanceof Carbon ? $dueRaw : Carbon::parse($dueRaw);
            if ($followup->followup_at->lte($due)) {
                continue;
            }

            $employeeId = (string) ($followup->created_by ?? '');
            if ($employeeId === '' || ! array_key_exists($employeeId, $late)) {
                continue;
            }

            $delayMinutes = (int) round($due->diffInMinutes($followup->followup_at));
            $this->addLatePenalty($late[$employeeId], $delayMinutes);
        }

        return $late;
    }

    /**
     * @return array{total_count: int, total_points: int, buckets: array<string, array{count: int, unit_points: int, points: int, label: string}>}
     */
    private function emptyLatePenalty(): array
    {
        $buckets = [];
        foreach (self::LATE_PENALTY_BUCKETS as $bucket) {
            $buckets[$bucket['key']] = [
                'count' => 0,
                'unit_points' => $bucket['points'],
                'points' => 0,
                'label' => self::lateBucketLabel($bucket),
            ];
        }

        return [
            'total_count' => 0,
            'total_points' => 0,
            'buckets' => $buckets,
        ];
    }

    /**
     * @param  array{total_count: int, total_points: int, buckets: array<string, array{count: int, unit_points: int, points: int, label: string}>}  $late
     */
    private function addLatePenalty(array &$late, int $delayMinutes): void
    {
        if ($delayMinutes <= 0) {
            return;
        }

        $bucket = $this->lateBucketForMinutes($delayMinutes);
        $key = $bucket['key'];
        $unit = $bucket['points'];

        $late['buckets'][$key]['count']++;
        $late['buckets'][$key]['points'] -= $unit;
        $late['total_count']++;
        $late['total_points'] -= $unit;
    }

    /**
     * @return array{key: string, max_minutes: int|null, points: int, label: string}
     */
    private function lateBucketForMinutes(int $delayMinutes): array
    {
        foreach (self::LATE_PENALTY_BUCKETS as $bucket) {
            $max = $bucket['max_minutes'];
            if ($max === null || $delayMinutes <= $max) {
                return $bucket;
            }
        }

        return self::LATE_PENALTY_BUCKETS[array_key_last(self::LATE_PENALTY_BUCKETS)];
    }

    /**
     * @return array{key: string, label: string, count: int, unit_points: int, points: int, positive: bool}
     */
    private function markLine(string $key, string $label, int $count, int $unitPoints, bool $positive): array
    {
        $points = $positive
            ? $count * $unitPoints
            : -1 * ($count * $unitPoints);

        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'unit_points' => $unitPoints,
            'points' => $points,
            'positive' => $positive,
        ];
    }
}
