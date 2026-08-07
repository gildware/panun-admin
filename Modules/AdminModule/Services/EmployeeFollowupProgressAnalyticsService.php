<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\BookingModule\Services\BookingFollowupService;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Services\LeadFollowupService;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\UserManagement\Entities\User;

class EmployeeFollowupProgressAnalyticsService
{
    public function __construct(
        private readonly LeadOpenStatusService $leadOpenStatus,
        private readonly LeadFollowupService $leadFollowupService,
        private readonly BookingFollowupService $bookingFollowupService,
    ) {}

    /**
     * @param  Collection<int, User>  $employees
     * @param  array<string, mixed>  $fullReport
     * @return array<string, mixed>
     */
    public function build(
        Collection $employees,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $fullReport = [],
    ): array {
        $employeeIds = $employees->pluck('id')->map(fn ($id) => (string) $id)->filter()->values()->all();

        if ($employeeIds === []) {
            return $this->emptyPayload();
        }

        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();
        $asOf = Carbon::now()->lt($rangeEnd) ? Carbon::now() : $rangeEnd;

        $leads = $this->analyzeLeadFollowups($employeeIds, $rangeStart, $rangeEnd, $asOf);
        $leads['widget_rows'] = $this->buildScopeWidgetRows($leads, 'lead');
        $bookings = $this->analyzeBookingFollowups($employeeIds, $rangeStart, $rangeEnd, $asOf);
        $bookings['widget_rows'] = $this->buildScopeWidgetRows($bookings, 'booking');
        $outcomeImpact = $this->buildOutcomeImpact($employeeIds, $rangeStart, $rangeEnd, $asOf);

        $overallOnTime = (int) ($leads['on_time'] ?? 0) + (int) ($bookings['on_time'] ?? 0);
        $overallLate = (int) ($leads['late'] ?? 0) + (int) ($bookings['late'] ?? 0);
        $overallMissed = (int) ($leads['missed'] ?? 0) + (int) ($bookings['missed'] ?? 0);
        $overallDone = (int) ($leads['total_done'] ?? 0) + (int) ($bookings['total_done'] ?? 0);
        $overallDue = $overallOnTime + $overallLate + $overallMissed;
        $overallAccuracy = $overallDue > 0 ? round(($overallOnTime / $overallDue) * 100, 1) : 100.0;

        $avgDelayMinutes = $this->weightedAverageDelay(
            [(int) ($leads['avg_delay_minutes'] ?? 0), (int) ($bookings['avg_delay_minutes'] ?? 0)],
            [(int) ($leads['late'] ?? 0), (int) ($bookings['late'] ?? 0)],
        );

        $pendingTotal = (int) ($fullReport['pending_followups']['total'] ?? 0);
        $missedSnapshot = (int) ($fullReport['missed_followups']['total'] ?? 0);

        return [
            'overall' => [
                'total_done' => $overallDone,
                'on_time' => $overallOnTime,
                'late' => $overallLate,
                'missed' => $overallMissed,
                'pending' => $pendingTotal,
                'missed_open' => $missedSnapshot,
                'accuracy_pct' => $overallAccuracy,
                'avg_delay_minutes' => $avgDelayMinutes,
                'avg_delay_label' => $this->formatDelay($avgDelayMinutes),
                'for_others' => (int) ($leads['for_others'] ?? 0) + (int) ($bookings['for_others'] ?? 0),
                'by_others' => (int) ($leads['by_others'] ?? 0) + (int) ($bookings['by_others'] ?? 0),
                'summary_rows' => $this->summaryRows([
                    ['key' => 'done', 'label' => translate('Progress_followups_done') ?? translate('Follow_ups'), 'count' => $overallDone, 'tone' => 'brand', 'icon' => 'task_alt'],
                    ['key' => 'on_time', 'label' => translate('Progress_on_time_followups') ?? translate('Follow_up_accuracy'), 'count' => $overallOnTime, 'tone' => 'good', 'icon' => 'schedule'],
                    ['key' => 'late', 'label' => translate('Progress_late_followups') ?? translate('Pending'), 'count' => $overallLate, 'tone' => 'warning', 'icon' => 'running_with_errors'],
                    ['key' => 'missed', 'label' => translate('Progress_missed_followups'), 'count' => $overallMissed, 'tone' => 'danger', 'icon' => 'warning'],
                    ['key' => 'for_others', 'label' => translate('Progress_followups_for_others'), 'count' => (int) ($leads['for_others'] ?? 0) + (int) ($bookings['for_others'] ?? 0), 'tone' => 'brand', 'icon' => 'group_add'],
                    ['key' => 'by_others', 'label' => translate('Progress_followups_by_others'), 'count' => (int) ($leads['by_others'] ?? 0) + (int) ($bookings['by_others'] ?? 0), 'tone' => 'warning', 'icon' => 'groups'],
                ], max(1, $overallDone)),
            ],
            'leads' => $leads,
            'bookings' => $bookings,
            'outcome_impact' => $outcomeImpact,
            'aging_buckets' => $this->buildDelayBuckets($leads['late_rows'] ?? [], $bookings['late_rows'] ?? []),
            'charts' => [
                'categories' => $bookings['daily_categories'] ?? [],
                'lead_categories' => $leads['daily_categories'] ?? [],
                'booking_categories' => $bookings['daily_categories'] ?? [],
                'lead_done_series' => $leads['daily_on_time'] ?? [],
                'booking_done_series' => $bookings['daily_on_time'] ?? [],
                'lead_late_series' => $leads['daily_late'] ?? [],
                'booking_late_series' => $bookings['daily_late'] ?? [],
                'lead_missed_series' => $leads['daily_missed'] ?? [],
                'booking_missed_series' => $bookings['daily_missed'] ?? [],
                'outcome_discipline_labels' => $outcomeImpact['discipline_labels'] ?? [],
                'lead_converted_pct_series' => $outcomeImpact['charts']['lead_converted_pct'] ?? [],
                'lead_cancelled_pct_series' => $outcomeImpact['charts']['lead_cancelled_pct'] ?? [],
                'lead_pending_pct_series' => $outcomeImpact['charts']['lead_pending_pct'] ?? [],
                'booking_completed_pct_series' => $outcomeImpact['charts']['booking_completed_pct'] ?? [],
                'booking_cancelled_pct_series' => $outcomeImpact['charts']['booking_cancelled_pct'] ?? [],
                'booking_pending_pct_series' => $outcomeImpact['charts']['booking_pending_pct'] ?? [],
            ],
        ];
    }

    /**
     * @param  list<string>  $employeeIds
     * @return array<string, mixed>
     */
    private function analyzeLeadFollowups(array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd, Carbon $asOf): array
    {
        $completed = LeadFollowup::query()
            ->with(['lead:id,handled_by,name,phone_number'])
            ->whereIn('created_by', $employeeIds)
            ->whereNotNull('followup_at')
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->get();

        $onTime = 0;
        $late = 0;
        $delayTotal = 0;
        $forOthers = 0;
        $takenCount = 0;
        $rescheduledCount = 0;
        $lateRows = [];
        $dailyDone = [];
        $dailyOnTime = [];
        $dailyLate = [];

        foreach ($completed as $followup) {
            $dayKey = $followup->followup_at->toDateString();
            $dailyDone[$dayKey] = ($dailyDone[$dayKey] ?? 0) + 1;

            if ($followup->isRescheduled()) {
                $rescheduledCount++;
            } else {
                $takenCount++;
            }

            $assignee = (string) ($followup->lead?->handled_by ?? '');
            $performer = (string) ($followup->created_by ?? '');
            if ($assignee !== '' && $assignee !== Lead::HANDLED_BY_AI && $performer !== '' && $assignee !== $performer) {
                $forOthers++;
            }

            $due = $followup->due_followup_at;
            if (! $due) {
                continue;
            }

            if ($followup->followup_at->lte($due->copy()->endOfDay())) {
                $onTime++;
                $dailyOnTime[$dayKey] = ($dailyOnTime[$dayKey] ?? 0) + 1;
            } else {
                $late++;
                $delayMinutes = (int) round($due->diffInMinutes($followup->followup_at));
                $delayTotal += $delayMinutes;
                $dailyLate[$dayKey] = ($dailyLate[$dayKey] ?? 0) + 1;
                $lateRows[] = [
                    'type' => translate('Lead_followups'),
                    'reference' => $followup->lead?->name ?: ($followup->lead?->phone_number ?: '#'.$followup->lead_id),
                    'due_at' => $due->format('d M Y h:i a'),
                    'done_at' => $followup->followup_at->format('d M Y h:i a'),
                    'delay_label' => $this->leadFollowupService->formatDelayDuration($delayMinutes),
                    'delay_minutes' => $delayMinutes,
                ];
            }
        }

        $missedOpen = 0;
        $dailyMissed = [];
        foreach ($employeeIds as $employeeId) {
            $missedQuery = Lead::query()
                ->where('handled_by', $employeeId)
                ->whereNotNull('next_followup_at')
                ->whereBetween('next_followup_at', [$rangeStart, $rangeEnd])
                ->where('next_followup_at', '<', $asOf);
            $this->leadOpenStatus->restrictQueryToOpenLeads($missedQuery);
            $missedDates = $missedQuery->pluck('next_followup_at');
            $missedOpen += $missedDates->count();
            foreach ($missedDates as $dueAt) {
                $dayKey = Carbon::parse($dueAt)->toDateString();
                $dailyMissed[$dayKey] = ($dailyMissed[$dayKey] ?? 0) + 1;
            }
        }

        $pendingOpen = 0;
        foreach ($employeeIds as $employeeId) {
            $pendingQuery = Lead::query()
                ->where('handled_by', $employeeId)
                ->whereNotNull('next_followup_at')
                ->whereBetween('next_followup_at', [$rangeStart, $rangeEnd])
                ->where('next_followup_at', '>=', $asOf);
            $this->leadOpenStatus->restrictQueryToOpenLeads($pendingQuery);
            $pendingOpen += (int) $pendingQuery->count();
        }

        $byOthers = (int) LeadFollowup::query()
            ->whereHas('lead', fn ($q) => $q->whereIn('handled_by', $employeeIds))
            ->whereNotNull('followup_at')
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->whereNotIn('created_by', $employeeIds)
            ->count();

        $totalDone = $completed->count();
        $dueItems = $onTime + $late + $missedOpen;
        $avgDelay = $late > 0 ? (int) round($delayTotal / $late) : 0;
        $categories = $this->dateKeysFromCounts($dailyDone, $rangeStart, $rangeEnd);

        return [
            'total_done' => $totalDone,
            'taken' => $takenCount,
            'rescheduled' => $rescheduledCount,
            'on_time' => $onTime,
            'late' => $late,
            'missed' => $missedOpen,
            'pending_open' => $pendingOpen,
            'accuracy_pct' => $dueItems > 0 ? round(($onTime / $dueItems) * 100, 1) : 100.0,
            'avg_delay_minutes' => $avgDelay,
            'avg_delay_label' => $this->formatDelay($avgDelay),
            'for_others' => $forOthers,
            'by_others' => $byOthers,
            'late_rows' => collect($lateRows)->sortByDesc('delay_minutes')->take(20)->values()->all(),
            'summary_rows' => $this->summaryRows([
                ['key' => 'done', 'label' => translate('Progress_followups_done') ?? translate('Follow_ups'), 'count' => $totalDone, 'tone' => 'brand', 'icon' => 'task_alt'],
                ['key' => 'taken', 'label' => translate('Taken'), 'count' => $takenCount, 'tone' => 'good', 'icon' => 'check_circle'],
                ['key' => 'rescheduled', 'label' => translate('Reschedule'), 'count' => $rescheduledCount, 'tone' => 'warning', 'icon' => 'event_repeat'],
                ['key' => 'on_time', 'label' => translate('Progress_on_time_followups') ?? translate('Follow_up_accuracy'), 'count' => $onTime, 'tone' => 'good', 'icon' => 'schedule'],
                ['key' => 'late', 'label' => translate('Progress_late_followups') ?? translate('Pending'), 'count' => $late, 'tone' => 'warning', 'icon' => 'running_with_errors'],
                ['key' => 'missed', 'label' => translate('Progress_missed_followups'), 'count' => $missedOpen, 'tone' => 'danger', 'icon' => 'warning'],
                ['key' => 'for_others', 'label' => translate('Progress_followups_for_others'), 'count' => $forOthers, 'tone' => 'brand', 'icon' => 'group_add'],
                ['key' => 'by_others', 'label' => translate('Progress_followups_by_others'), 'count' => $byOthers, 'tone' => 'warning', 'icon' => 'groups'],
            ], max(1, $totalDone)),
            'daily_categories' => array_map(fn (string $day) => Carbon::parse($day)->format('d M'), $categories),
            'daily_done' => array_map(fn (string $day) => (int) ($dailyDone[$day] ?? 0), $categories),
            'daily_on_time' => array_map(fn (string $day) => (int) ($dailyOnTime[$day] ?? 0), $categories),
            'daily_late' => array_map(fn (string $day) => (int) ($dailyLate[$day] ?? 0), $categories),
            'daily_missed' => array_map(fn (string $day) => (int) ($dailyMissed[$day] ?? 0), $categories),
        ];
    }

    /**
     * @param  list<string>  $employeeIds
     * @return array<string, mixed>
     */
    private function analyzeBookingFollowups(array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd, Carbon $asOf): array
    {
        $completed = BookingFollowup::query()
            ->with(['booking:id,readable_id,assignee_id'])
            ->whereIn('created_by', $employeeIds)
            ->whereNotNull('followup_at')
            ->whereIn('status', ['completed', 'rescheduled'])
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->get();

        $onTime = 0;
        $late = 0;
        $delayTotal = 0;
        $forOthers = 0;
        $takenCount = 0;
        $rescheduledCount = 0;
        $lateRows = [];
        $dailyDone = [];
        $dailyOnTime = [];
        $dailyLate = [];

        foreach ($completed as $followup) {
            if ($followup->isRescheduled()) {
                $rescheduledCount++;
                continue;
            }

            $takenCount++;
            $dayKey = $followup->followup_at->toDateString();
            $dailyDone[$dayKey] = ($dailyDone[$dayKey] ?? 0) + 1;

            $assignee = (string) ($followup->booking?->assignee_id ?? '');
            $performer = (string) ($followup->created_by ?? '');
            if ($assignee !== '' && $performer !== '' && $assignee !== $performer) {
                $forOthers++;
            }

            $dueRaw = $followup->due_followup_at ?? $followup->date;
            if (! $dueRaw) {
                continue;
            }
            $due = $dueRaw instanceof Carbon ? $dueRaw : Carbon::parse($dueRaw);

            if ($followup->followup_at->lte($due->copy()->endOfDay())) {
                $onTime++;
                $dailyOnTime[$dayKey] = ($dailyOnTime[$dayKey] ?? 0) + 1;
            } else {
                $late++;
                $delayMinutes = (int) round($due->diffInMinutes($followup->followup_at));
                $delayTotal += $delayMinutes;
                $dailyLate[$dayKey] = ($dailyLate[$dayKey] ?? 0) + 1;
                $lateRows[] = [
                    'type' => translate('Booking_Followups'),
                    'reference' => '#'.($followup->booking?->readable_id ?? $followup->booking_id),
                    'due_at' => $due->format('d M Y h:i a'),
                    'done_at' => $followup->followup_at->format('d M Y h:i a'),
                    'delay_label' => $this->bookingFollowupService->formatDelayDuration($delayMinutes),
                    'delay_minutes' => $delayMinutes,
                ];
            }
        }

        $missedRows = BookingFollowup::query()
            ->where('status', 'scheduled')
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->where('date', '<', $asOf)
            ->whereHas('booking', function ($q) use ($employeeIds) {
                $q->whereIn('assignee_id', $employeeIds)
                    ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
            })
            ->get(['date']);

        $missedOpen = $missedRows->count();
        $dailyMissed = [];
        foreach ($missedRows as $row) {
            $dayKey = Carbon::parse($row->date)->toDateString();
            $dailyMissed[$dayKey] = ($dailyMissed[$dayKey] ?? 0) + 1;
        }

        $pendingOpen = (int) BookingFollowup::query()
            ->where('status', 'scheduled')
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->where('date', '>=', $asOf)
            ->whereHas('booking', function ($q) use ($employeeIds) {
                $q->whereIn('assignee_id', $employeeIds)
                    ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
            })
            ->count();

        $byOthers = (int) BookingFollowup::query()
            ->whereHas('booking', fn ($q) => $q->whereIn('assignee_id', $employeeIds))
            ->whereNotNull('followup_at')
            ->whereIn('status', ['completed', 'rescheduled'])
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->whereNotIn('created_by', $employeeIds)
            ->count();

        $totalDone = $takenCount;
        $dueItems = $onTime + $late + $missedOpen;
        $avgDelay = $late > 0 ? (int) round($delayTotal / $late) : 0;
        $categories = $this->dateKeysFromCounts($dailyDone, $rangeStart, $rangeEnd);

        return [
            'total_done' => $totalDone,
            'taken' => $takenCount,
            'rescheduled' => $rescheduledCount,
            'on_time' => $onTime,
            'late' => $late,
            'missed' => $missedOpen,
            'pending_open' => $pendingOpen,
            'accuracy_pct' => $dueItems > 0 ? round(($onTime / $dueItems) * 100, 1) : 100.0,
            'avg_delay_minutes' => $avgDelay,
            'avg_delay_label' => $this->formatDelay($avgDelay),
            'for_others' => $forOthers,
            'by_others' => $byOthers,
            'late_rows' => collect($lateRows)->sortByDesc('delay_minutes')->take(20)->values()->all(),
            'summary_rows' => $this->summaryRows([
                ['key' => 'done', 'label' => translate('Progress_followups_done') ?? translate('Follow_ups'), 'count' => $totalDone, 'tone' => 'brand', 'icon' => 'task_alt'],
                ['key' => 'taken', 'label' => translate('Taken'), 'count' => $takenCount, 'tone' => 'good', 'icon' => 'check_circle'],
                ['key' => 'rescheduled', 'label' => translate('Reschedule'), 'count' => $rescheduledCount, 'tone' => 'warning', 'icon' => 'event_repeat'],
                ['key' => 'on_time', 'label' => translate('Progress_on_time_followups') ?? translate('Follow_up_accuracy'), 'count' => $onTime, 'tone' => 'good', 'icon' => 'schedule'],
                ['key' => 'late', 'label' => translate('Progress_late_followups') ?? translate('Pending'), 'count' => $late, 'tone' => 'warning', 'icon' => 'running_with_errors'],
                ['key' => 'missed', 'label' => translate('Progress_missed_followups'), 'count' => $missedOpen, 'tone' => 'danger', 'icon' => 'warning'],
                ['key' => 'for_others', 'label' => translate('Progress_followups_for_others'), 'count' => $forOthers, 'tone' => 'brand', 'icon' => 'group_add'],
                ['key' => 'by_others', 'label' => translate('Progress_followups_by_others'), 'count' => $byOthers, 'tone' => 'warning', 'icon' => 'groups'],
            ], max(1, max($totalDone, $rescheduledCount))),
            'daily_categories' => array_map(fn (string $day) => Carbon::parse($day)->format('d M'), $categories),
            'daily_done' => array_map(fn (string $day) => (int) ($dailyDone[$day] ?? 0), $categories),
            'daily_on_time' => array_map(fn (string $day) => (int) ($dailyOnTime[$day] ?? 0), $categories),
            'daily_late' => array_map(fn (string $day) => (int) ($dailyLate[$day] ?? 0), $categories),
            'daily_missed' => array_map(fn (string $day) => (int) ($dailyMissed[$day] ?? 0), $categories),
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<string>
     */
    private function dateKeysFromCounts(array $counts, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $keys = [];
        $cursor = $rangeStart->copy()->startOfDay();
        $end = $rangeEnd->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $keys[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $keys;
    }

    /**
     * @param  list<int>  $delays
     * @param  list<int>  $weights
     */
    private function weightedAverageDelay(array $delays, array $weights): int
    {
        $totalWeight = array_sum($weights);
        if ($totalWeight <= 0) {
            return 0;
        }

        $sum = 0;
        foreach ($delays as $index => $delay) {
            $sum += $delay * ($weights[$index] ?? 0);
        }

        return (int) round($sum / $totalWeight);
    }

    private function formatDelay(int $minutes): string
    {
        if ($minutes <= 0) {
            return translate('Progress_no_delay') ?? '—';
        }

        return $this->leadFollowupService->formatDelayDuration($minutes);
    }

    /**
     * @param  list<array<string, mixed>>  $lateRows
     * @return list<array<string, mixed>>
     */
    private function buildDelayBuckets(array $lateRows, array $bookingLateRows): array
    {
        $all = array_merge($lateRows, $bookingLateRows);
        $buckets = [
            ['label' => translate('Progress_delay_under_1h') ?? '< 1 hour', 'count' => 0, 'crit' => false],
            ['label' => translate('Progress_delay_1_24h') ?? '1–24 hours', 'count' => 0, 'crit' => false],
            ['label' => translate('Progress_delay_1_3d') ?? '1–3 days', 'count' => 0, 'crit' => true],
            ['label' => translate('Progress_delay_over_3d') ?? '3+ days', 'count' => 0, 'crit' => true],
        ];

        foreach ($all as $row) {
            $minutes = (int) ($row['delay_minutes'] ?? 0);
            if ($minutes < 60) {
                $buckets[0]['count']++;
            } elseif ($minutes < 1440) {
                $buckets[1]['count']++;
            } elseif ($minutes < 4320) {
                $buckets[2]['count']++;
            } else {
                $buckets[3]['count']++;
            }
        }

        return $buckets;
    }

    /**
     * @param  list<string>  $employeeIds
     * @return array<string, mixed>
     */
    private function buildOutcomeImpact(array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd, Carbon $asOf): array
    {
        $disciplineKeys = ['on_time', 'late', 'missed'];
        $disciplineLabels = [
            translate('Progress_on_time_followups') ?? translate('Follow_up_accuracy'),
            translate('Progress_late_followups') ?? translate('Pending'),
            translate('Progress_missed_followups'),
        ];

        $leadOutcomeDefs = [
            'converted' => ['label' => translate('Progress_converted') ?? translate('Bookings_completed'), 'tone' => 'good', 'icon' => 'check_circle'],
            'cancelled' => ['label' => translate('Cancelled'), 'tone' => 'danger', 'icon' => 'cancel'],
            'pending' => ['label' => translate('Pending'), 'tone' => 'warning', 'icon' => 'hourglass_top'],
        ];

        $leadDiscipline = $this->collectLeadDisciplineMap($employeeIds, $rangeStart, $rangeEnd, $asOf);
        $leadDetails = $this->resolveLeadOutcomeDetails(array_keys($leadDiscipline));

        $customerMaps = $this->filterLeadMapsBySegment($leadDiscipline, $leadDetails, [Lead::TYPE_CUSTOMER], false);
        $providerMaps = $this->filterLeadMapsBySegment($leadDiscipline, $leadDetails, [Lead::TYPE_PROVIDER], false);
        $customerBuckets = $this->aggregateOutcomeBuckets($customerMaps['discipline'], $customerMaps['outcomes'], $leadOutcomeDefs, $disciplineKeys);
        $providerBuckets = $this->aggregateOutcomeBuckets($providerMaps['discipline'], $providerMaps['outcomes'], $leadOutcomeDefs, $disciplineKeys);
        $generalByTiming = $this->buildGeneralResultByTimingRows($leadDiscipline, $leadDetails, $disciplineKeys, $disciplineLabels);

        // Keep pooled "all active" rows for charts / backward compatibility (exclude invalids).
        $allActiveMaps = $this->filterLeadMapsBySegment(
            $leadDiscipline,
            $leadDetails,
            [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER, Lead::TYPE_FUTURE_CUSTOMER],
            false,
        );
        $leadBuckets = $this->aggregateOutcomeBuckets($allActiveMaps['discipline'], $allActiveMaps['outcomes'], $leadOutcomeDefs, $disciplineKeys);

        $bookingDiscipline = $this->collectBookingDisciplineMap($employeeIds, $rangeStart, $rangeEnd, $asOf);
        $bookingOutcomes = $this->resolveBookingOutcomes(array_keys($bookingDiscipline));
        $bookingBuckets = $this->aggregateOutcomeBuckets($bookingDiscipline, $bookingOutcomes, [
            'completed' => ['label' => translate('Bookings_completed'), 'tone' => 'good', 'icon' => 'task_alt'],
            'cancelled' => ['label' => translate('Cancelled'), 'tone' => 'danger', 'icon' => 'cancel'],
            'pending' => ['label' => translate('Pending'), 'tone' => 'warning', 'icon' => 'hourglass_top'],
        ], $disciplineKeys);

        return [
            'discipline_labels' => $disciplineLabels,
            'leads' => [
                'buckets' => $leadBuckets,
                'comparison_rows' => $this->buildComparisonRows($leadBuckets, $disciplineKeys, $disciplineLabels, 'converted'),
                'general_by_timing' => $generalByTiming,
                'customer' => [
                    'buckets' => $customerBuckets,
                    'comparison_rows' => $this->buildComparisonRows($customerBuckets, $disciplineKeys, $disciplineLabels, 'converted'),
                ],
                'provider' => [
                    'buckets' => $providerBuckets,
                    'comparison_rows' => $this->buildComparisonRows($providerBuckets, $disciplineKeys, $disciplineLabels, 'converted'),
                ],
            ],
            'bookings' => [
                'buckets' => $bookingBuckets,
                'comparison_rows' => $this->buildComparisonRows($bookingBuckets, $disciplineKeys, $disciplineLabels, 'completed'),
            ],
            'charts' => [
                'lead_converted_pct' => array_map(fn (string $key) => (float) ($leadBuckets[$key]['rates']['converted'] ?? 0), $disciplineKeys),
                'lead_cancelled_pct' => array_map(fn (string $key) => (float) ($leadBuckets[$key]['rates']['cancelled'] ?? 0), $disciplineKeys),
                'lead_pending_pct' => array_map(fn (string $key) => (float) ($leadBuckets[$key]['rates']['pending'] ?? 0), $disciplineKeys),
                'booking_completed_pct' => array_map(fn (string $key) => (float) ($bookingBuckets[$key]['rates']['completed'] ?? 0), $disciplineKeys),
                'booking_cancelled_pct' => array_map(fn (string $key) => (float) ($bookingBuckets[$key]['rates']['cancelled'] ?? 0), $disciplineKeys),
                'booking_pending_pct' => array_map(fn (string $key) => (float) ($bookingBuckets[$key]['rates']['pending'] ?? 0), $disciplineKeys),
            ],
        ];
    }

    /**
     * @param  list<string>  $employeeIds
     * @return array<string, string> lead_id => on_time|late|missed
     */
    private function collectLeadDisciplineMap(array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd, Carbon $asOf): array
    {
        $severity = [];

        $completed = LeadFollowup::query()
            ->whereIn('created_by', $employeeIds)
            ->whereNotNull('followup_at')
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->get(['lead_id', 'followup_at', 'due_followup_at']);

        foreach ($completed as $followup) {
            $leadId = (string) $followup->lead_id;
            $due = $followup->due_followup_at;
            if (! $due) {
                continue;
            }

            $level = $followup->followup_at->lte($due->copy()->endOfDay()) ? 1 : 2;
            $this->setDisciplineSeverity($severity, $leadId, $level);
        }

        foreach ($employeeIds as $employeeId) {
            $missedQuery = Lead::query()
                ->where('handled_by', $employeeId)
                ->whereNotNull('next_followup_at')
                ->whereBetween('next_followup_at', [$rangeStart, $rangeEnd])
                ->where('next_followup_at', '<', $asOf);
            $this->leadOpenStatus->restrictQueryToOpenLeads($missedQuery);

            foreach ($missedQuery->pluck('id') as $leadId) {
                $this->setDisciplineSeverity($severity, (string) $leadId, 3);
            }
        }

        return $this->severityMapToDiscipline($severity);
    }

    /**
     * @param  list<string>  $employeeIds
     * @return array<string, string> booking_id => on_time|late|missed
     */
    private function collectBookingDisciplineMap(array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd, Carbon $asOf): array
    {
        $severity = [];

        $completed = BookingFollowup::query()
            ->whereIn('created_by', $employeeIds)
            ->whereNotNull('followup_at')
            ->whereIn('status', ['completed', 'rescheduled'])
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->get(['booking_id', 'followup_at', 'due_followup_at', 'date', 'status']);

        foreach ($completed as $followup) {
            if ($followup->isRescheduled()) {
                continue;
            }

            $bookingId = (string) $followup->booking_id;
            $dueRaw = $followup->due_followup_at ?? $followup->date;
            if (! $dueRaw) {
                continue;
            }
            $due = $dueRaw instanceof Carbon ? $dueRaw : Carbon::parse($dueRaw);

            $level = $followup->followup_at->lte($due->copy()->endOfDay()) ? 1 : 2;
            $this->setDisciplineSeverity($severity, $bookingId, $level);
        }

        $missedIds = BookingFollowup::query()
            ->where('status', 'scheduled')
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->where('date', '<', $asOf)
            ->whereHas('booking', function ($q) use ($employeeIds) {
                $q->whereIn('assignee_id', $employeeIds)
                    ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
            })
            ->pluck('booking_id');

        foreach ($missedIds as $bookingId) {
            $this->setDisciplineSeverity($severity, (string) $bookingId, 3);
        }

        return $this->severityMapToDiscipline($severity);
    }

    /**
     * @param  array<string, int>  $severity
     */
    private function setDisciplineSeverity(array &$severity, string $entityId, int $level): void
    {
        if (! isset($severity[$entityId]) || $severity[$entityId] < $level) {
            $severity[$entityId] = $level;
        }
    }

    /**
     * @param  array<string, int>  $severity
     * @return array<string, string>
     */
    private function severityMapToDiscipline(array $severity): array
    {
        $map = [1 => 'on_time', 2 => 'late', 3 => 'missed'];
        $result = [];

        foreach ($severity as $entityId => $level) {
            $result[(string) $entityId] = $map[$level] ?? 'on_time';
        }

        return $result;
    }

    /**
     * @param  list<string>  $leadIds
     * @return array<string, array{outcome: string, segment: string}>
     */
    private function resolveLeadOutcomeDetails(array $leadIds): array
    {
        if ($leadIds === []) {
            return [];
        }

        $leads = Lead::query()
            ->whereIn('id', $leadIds)
            ->get(['id', 'lead_type', 'name', 'phone_number']);

        $historiesByLead = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id');

        $customerStatusIds = [];
        $providerStatusIds = [];

        foreach ($historiesByLead as $group) {
            foreach ($group as $history) {
                $data = is_array($history->data) ? $history->data : [];
                if ($history->type === Lead::TYPE_PROVIDER) {
                    if ($id = $this->normalizeReferenceId($data['provider_lead_status_id'] ?? null)) {
                        $providerStatusIds[] = $id;
                    }
                } elseif (in_array($history->type, [Lead::TYPE_CUSTOMER, Lead::TYPE_FUTURE_CUSTOMER], true)) {
                    if ($id = $this->normalizeReferenceId($data['customer_lead_status_id'] ?? null)) {
                        $customerStatusIds[] = $id;
                    }
                }
            }
        }

        $customerStatuses = $customerStatusIds !== []
            ? CustomerLeadStatus::whereIn('id', array_unique($customerStatusIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();
        $providerStatuses = $providerStatusIds !== []
            ? ProviderLeadStatus::whereIn('id', array_unique($providerStatusIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();

        $bookingsByLead = Booking::query()
            ->whereIn('lead_id', $leadIds)
            ->orderByDesc('created_at')
            ->get(['id', 'lead_id'])
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        $details = [];
        foreach ($leads as $lead) {
            $leadId = (string) $lead->id;
            $type = (string) ($lead->lead_type ?? '');
            $histories = $historiesByLead->get($lead->id) ?? collect();
            $latestHistory = $histories->first();

            if ($type === Lead::TYPE_INVALID) {
                $details[$leadId] = [
                    'outcome' => 'invalid',
                    'segment' => $this->priorLeadSegmentFromHistories($histories, $type),
                    'lead_type' => Lead::TYPE_INVALID,
                ];
                continue;
            }

            if ($type === Lead::TYPE_CUSTOMER) {
                $history = $histories->first(fn ($row) => $row->type === Lead::TYPE_CUSTOMER) ?? $latestHistory;
                $data = ($history && is_array($history->data)) ? $history->data : [];
                $statusId = $this->normalizeReferenceId($data['customer_lead_status_id'] ?? null);
                $status = $statusId ? $customerStatuses->get($statusId) : null;
                $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
                $bookingStatus = strtolower((string) ($data['booking_status'] ?? ''));
                $hasBooking = $bookingsByLead->has($lead->id);
                $details[$leadId] = [
                    'outcome' => $this->classifyCustomerLeadOutcome($baseType, $bookingStatus, $hasBooking),
                    'segment' => Lead::TYPE_CUSTOMER,
                    'lead_type' => Lead::TYPE_CUSTOMER,
                ];
                continue;
            }

            if ($type === Lead::TYPE_FUTURE_CUSTOMER) {
                $history = $histories->first(fn ($row) => $row->type === Lead::TYPE_FUTURE_CUSTOMER) ?? $latestHistory;
                $data = ($history && is_array($history->data)) ? $history->data : [];
                $statusId = $this->normalizeReferenceId($data['customer_lead_status_id'] ?? null);
                $status = $statusId ? $customerStatuses->get($statusId) : null;
                $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
                $bookingStatus = strtolower((string) ($data['booking_status'] ?? ''));
                $hasBooking = $bookingsByLead->has($lead->id);
                $details[$leadId] = [
                    'outcome' => $this->classifyCustomerLeadOutcome($baseType, $bookingStatus, $hasBooking),
                    'segment' => Lead::TYPE_FUTURE_CUSTOMER,
                    'lead_type' => Lead::TYPE_FUTURE_CUSTOMER,
                ];
                continue;
            }

            if ($type === Lead::TYPE_PROVIDER) {
                $history = $histories->first(fn ($row) => $row->type === Lead::TYPE_PROVIDER) ?? $latestHistory;
                $data = ($history && is_array($history->data)) ? $history->data : [];
                $statusId = $this->normalizeReferenceId($data['provider_lead_status_id'] ?? null);
                $status = $statusId ? $providerStatuses->get($statusId) : null;
                $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
                $details[$leadId] = [
                    'outcome' => $this->classifyProviderLeadOutcome($baseType),
                    'segment' => Lead::TYPE_PROVIDER,
                    'lead_type' => Lead::TYPE_PROVIDER,
                ];
                continue;
            }

            $details[$leadId] = [
                'outcome' => 'pending',
                'segment' => 'unknown',
                'lead_type' => 'unknown',
            ];
        }

        return $details;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>  $histories
     */
    private function priorLeadSegmentFromHistories($histories, string $currentType): string
    {
        foreach ($histories as $history) {
            $type = (string) ($history->type ?? '');
            if ($type === '' || $type === Lead::TYPE_INVALID || $type === Lead::TYPE_UNKNOWN) {
                continue;
            }
            if (in_array($type, [Lead::TYPE_CUSTOMER, Lead::TYPE_FUTURE_CUSTOMER, Lead::TYPE_PROVIDER], true)) {
                return $type;
            }
        }

        return in_array($currentType, [Lead::TYPE_CUSTOMER, Lead::TYPE_FUTURE_CUSTOMER, Lead::TYPE_PROVIDER], true)
            ? $currentType
            : 'unknown';
    }

    /**
     * @param  array<string, string>  $disciplineMap
     * @param  array<string, array{outcome: string, segment: string}>  $details
     * @param  list<string>  $segments
     * @return array{discipline: array<string, string>, outcomes: array<string, string>}
     */
    private function filterLeadMapsBySegment(array $disciplineMap, array $details, array $segments, bool $invalidOnly): array
    {
        $discipline = [];
        $outcomes = [];

        foreach ($disciplineMap as $leadId => $timing) {
            $detail = $details[$leadId] ?? null;
            if ($detail === null) {
                continue;
            }

            $isInvalid = ($detail['outcome'] ?? '') === 'invalid';
            if ($invalidOnly !== $isInvalid) {
                continue;
            }
            if (! in_array($detail['segment'] ?? '', $segments, true)) {
                continue;
            }

            $discipline[$leadId] = $timing;
            $outcomes[$leadId] = $isInvalid ? 'invalid' : (string) ($detail['outcome'] ?? 'pending');
        }

        return ['discipline' => $discipline, 'outcomes' => $outcomes];
    }

    /**
     * @param  array<string, string>  $disciplineMap
     * @param  array<string, array{outcome: string, segment: string, lead_type?: string}>  $details
     * @param  list<string>  $disciplineKeys
     * @param  list<string>  $disciplineLabels
     * @return list<array<string, mixed>>
     */
    private function buildGeneralResultByTimingRows(array $disciplineMap, array $details, array $disciplineKeys, array $disciplineLabels): array
    {
        $rows = [];
        foreach ($disciplineKeys as $index => $key) {
            $counts = [
                Lead::TYPE_CUSTOMER => 0,
                Lead::TYPE_PROVIDER => 0,
                Lead::TYPE_FUTURE_CUSTOMER => 0,
                Lead::TYPE_INVALID => 0,
                'unknown' => 0,
            ];
            $total = 0;

            foreach ($disciplineMap as $leadId => $timing) {
                if ($timing !== $key) {
                    continue;
                }
                $type = (string) ($details[$leadId]['lead_type'] ?? 'unknown');
                if (! array_key_exists($type, $counts)) {
                    $type = 'unknown';
                }
                $counts[$type]++;
                $total++;
            }

            $rows[] = [
                'key' => $key,
                'label' => $disciplineLabels[$index] ?? $key,
                'total' => $total,
                'customer' => $counts[Lead::TYPE_CUSTOMER],
                'provider' => $counts[Lead::TYPE_PROVIDER],
                'future_customer' => $counts[Lead::TYPE_FUTURE_CUSTOMER],
                'invalid' => $counts[Lead::TYPE_INVALID],
                'unknown' => $counts['unknown'],
            ];
        }

        return $rows;
    }

    /**
     * @param  list<string>  $bookingIds
     * @return array<string, string> booking_id => completed|cancelled|pending
     */
    private function resolveBookingOutcomes(array $bookingIds): array
    {
        if ($bookingIds === []) {
            return [];
        }

        $bookings = Booking::query()
            ->whereIn('id', $bookingIds)
            ->get(['id', 'booking_status', 'readable_id']);

        $outcomes = [];
        foreach ($bookings as $booking) {
            $outcomes[(string) $booking->id] = $this->classifyBookingOutcome((string) ($booking->booking_status ?? ''));
        }

        return $outcomes;
    }

    private function classifyCustomerLeadOutcome(string $baseType, string $bookingStatus, bool $hasBooking): string
    {
        if ($baseType === 'cancel' || $bookingStatus === 'cancelled') {
            return 'cancelled';
        }
        if (in_array($baseType, ['completed', 'booked'], true) || $bookingStatus === 'booked' || $hasBooking) {
            return 'converted';
        }

        return 'pending';
    }

    private function classifyProviderLeadOutcome(string $baseType): string
    {
        if ($baseType === 'cancel') {
            return 'cancelled';
        }
        if ($baseType === 'completed') {
            return 'converted';
        }

        return 'pending';
    }

    private function classifyBookingOutcome(string $status): string
    {
        $normalized = strtolower($status);
        if (in_array($normalized, ['cancelled', 'canceled'], true)) {
            return 'cancelled';
        }
        if ($normalized === 'completed') {
            return 'completed';
        }

        return 'pending';
    }

    /**
     * @param  array<string, string>  $disciplineMap
     * @param  array<string, string>  $outcomeMap
     * @param  array<string, array<string, mixed>>  $outcomeDefs
     * @param  list<string>  $disciplineKeys
     * @return array<string, array<string, mixed>>
     */
    private function aggregateOutcomeBuckets(array $disciplineMap, array $outcomeMap, array $outcomeDefs, array $disciplineKeys): array
    {
        $buckets = [];
        foreach ($disciplineKeys as $key) {
            $counts = array_fill_keys(array_keys($outcomeDefs), 0);
            $buckets[$key] = ['total' => 0, 'counts' => $counts, 'rates' => array_fill_keys(array_keys($outcomeDefs), 0.0), 'rows' => []];
        }

        foreach ($disciplineMap as $entityId => $discipline) {
            $outcome = $outcomeMap[$entityId] ?? 'pending';
            if (! isset($buckets[$discipline]['counts'][$outcome])) {
                continue;
            }

            $buckets[$discipline]['counts'][$outcome]++;
            $buckets[$discipline]['total']++;
        }

        foreach ($disciplineKeys as $key) {
            $total = (int) ($buckets[$key]['total'] ?? 0);
            $rows = [];

            foreach ($outcomeDefs as $outcomeKey => $meta) {
                $count = (int) ($buckets[$key]['counts'][$outcomeKey] ?? 0);
                $rate = $total > 0 ? round(($count / $total) * 100, 1) : 0.0;
                $buckets[$key]['rates'][$outcomeKey] = $rate;
                $rows[] = array_merge($meta, [
                    'key' => $outcomeKey,
                    'count' => $count,
                    'pct' => $rate,
                ]);
            }

            $buckets[$key]['rows'] = $rows;
            $successKey = array_key_first($outcomeDefs);
            $cancelKey = 'cancelled';
            $buckets[$key]['success_rate'] = (float) ($buckets[$key]['rates'][$successKey] ?? 0);
            $buckets[$key]['cancel_rate'] = (float) ($buckets[$key]['rates'][$cancelKey] ?? 0);
        }

        return $buckets;
    }

    /**
     * @param  array<string, array<string, mixed>>  $buckets
     * @param  list<string>  $disciplineKeys
     * @param  list<string>  $disciplineLabels
     * @return list<array<string, mixed>>
     */
    private function buildComparisonRows(array $buckets, array $disciplineKeys, array $disciplineLabels, string $successKey): array
    {
        $rows = [];
        foreach ($disciplineKeys as $index => $key) {
            $bucket = $buckets[$key] ?? [];
            $rows[] = [
                'key' => $key,
                'label' => $disciplineLabels[$index] ?? $key,
                'total' => (int) ($bucket['total'] ?? 0),
                'success_rate' => (float) ($bucket['rates'][$successKey] ?? 0),
                'cancel_rate' => (float) ($bucket['rates']['cancelled'] ?? 0),
                'pending_rate' => (float) ($bucket['rates']['pending'] ?? 0),
                'outcome_rows' => $bucket['rows'] ?? [],
            ];
        }

        return $rows;
    }

    private function normalizeReferenceId(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || $value === '0') {
                return null;
            }

            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) (int) $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return list<array<string, mixed>>
     */
    private function buildScopeWidgetRows(array $section, string $scope): array
    {
        $isBooking = $scope === 'booking';
        $helpPrefix = $isBooking ? 'booking_followup' : 'lead_followup';
        $followupLabel = $isBooking ? translate('Booking_Followups') : translate('Lead_followups');
        $totalDone = (int) ($section['total_done'] ?? 0);
        $accuracy = (float) ($section['accuracy_pct'] ?? 100);

        $items = [
            [
                'key' => 'done',
                'help_key' => $isBooking ? 'booking_followups' : 'lead_followups',
                'label' => $followupLabel,
                'count' => $totalDone,
                'tone' => 'brand',
                'icon' => 'task_alt',
                'sublabel' => translate('Progress_followups_done') ?? translate('Follow_ups'),
            ],
            [
                'key' => 'taken',
                'help_key' => $helpPrefix.'_taken',
                'label' => translate('Taken'),
                'count' => (int) ($section['taken'] ?? 0),
                'tone' => 'good',
                'icon' => 'check_circle',
            ],
            [
                'key' => 'rescheduled',
                'help_key' => $helpPrefix.'_rescheduled',
                'label' => translate('Reschedule'),
                'count' => (int) ($section['rescheduled'] ?? 0),
                'tone' => 'warning',
                'icon' => 'event_repeat',
            ],
            [
                'key' => 'on_time',
                'help_key' => $helpPrefix.'_on_time',
                'label' => translate('Progress_on_time_followups') ?? translate('Follow_up_accuracy'),
                'count' => (int) ($section['on_time'] ?? 0),
                'tone' => 'good',
                'icon' => 'schedule',
            ],
            [
                'key' => 'late',
                'help_key' => $helpPrefix.'_late',
                'label' => translate('Progress_late_followups') ?? translate('Pending'),
                'count' => (int) ($section['late'] ?? 0),
                'tone' => 'warning',
                'icon' => 'running_with_errors',
            ],
            [
                'key' => 'missed',
                'help_key' => $helpPrefix.'_missed',
                'label' => translate('Progress_missed_followups'),
                'count' => (int) ($section['missed'] ?? 0),
                'tone' => 'danger',
                'icon' => 'warning',
            ],
            [
                'key' => 'pending',
                'help_key' => 'pending_followups',
                'label' => translate('Pending').' '.translate('Follow_ups'),
                'count' => (int) ($section['pending_open'] ?? 0),
                'tone' => 'warning',
                'icon' => 'pending_actions',
            ],
            [
                'key' => 'accuracy',
                'help_key' => 'followup_accuracy',
                'label' => translate('Follow_up_accuracy'),
                'count' => $accuracy,
                'tone' => $accuracy >= 90 ? 'good' : 'warning',
                'icon' => 'verified',
                'display' => 'percent',
            ],
            [
                'key' => 'for_others',
                'help_key' => $helpPrefix.'_for_others',
                'label' => translate('Progress_followups_for_others'),
                'count' => (int) ($section['for_others'] ?? 0),
                'tone' => 'brand',
                'icon' => 'group_add',
            ],
            [
                'key' => 'by_others',
                'help_key' => $helpPrefix.'_by_others',
                'label' => translate('Progress_followups_by_others'),
                'count' => (int) ($section['by_others'] ?? 0),
                'tone' => 'warning',
                'icon' => 'groups',
            ],
        ];

        // Lead total includes taken + reschedule; booking total is taken-only.
        if (! $isBooking) {
            $denominator = max(1, $totalDone);
        } else {
            $denominator = max(1, $totalDone + (int) ($section['rescheduled'] ?? 0));
        }

        $rows = $this->summaryRows($items, $denominator);

        foreach ($rows as &$row) {
            if (($row['display'] ?? '') === 'percent') {
                $row['pct'] = min(100.0, round((float) ($row['count'] ?? 0), 1));
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function summaryRows(array $items, int $denominator): array
    {
        return collect($items)->map(function (array $item) use ($denominator) {
            $count = (int) ($item['count'] ?? 0);
            $isPercent = ($item['display'] ?? '') === 'percent';

            return array_merge($item, [
                'total' => $isPercent ? null : $denominator,
                'pct' => $denominator > 0 ? round(($count / $denominator) * 100, 1) : 0.0,
            ]);
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        $zeroRows = fn () => $this->summaryRows([
            ['key' => 'done', 'label' => translate('Progress_followups_done') ?? translate('Follow_ups'), 'count' => 0, 'tone' => 'brand', 'icon' => 'task_alt'],
            ['key' => 'taken', 'label' => translate('Taken'), 'count' => 0, 'tone' => 'good', 'icon' => 'check_circle'],
            ['key' => 'rescheduled', 'label' => translate('Reschedule'), 'count' => 0, 'tone' => 'warning', 'icon' => 'event_repeat'],
            ['key' => 'on_time', 'label' => translate('Progress_on_time_followups') ?? translate('Follow_up_accuracy'), 'count' => 0, 'tone' => 'good', 'icon' => 'schedule'],
            ['key' => 'late', 'label' => translate('Progress_late_followups') ?? translate('Pending'), 'count' => 0, 'tone' => 'warning', 'icon' => 'running_with_errors'],
            ['key' => 'missed', 'label' => translate('Progress_missed_followups'), 'count' => 0, 'tone' => 'danger', 'icon' => 'warning'],
            ['key' => 'for_others', 'label' => translate('Progress_followups_for_others'), 'count' => 0, 'tone' => 'brand', 'icon' => 'group_add'],
            ['key' => 'by_others', 'label' => translate('Progress_followups_by_others'), 'count' => 0, 'tone' => 'warning', 'icon' => 'groups'],
        ], 1);

        $emptyMetrics = [
            'total_done' => 0,
            'taken' => 0,
            'rescheduled' => 0,
            'on_time' => 0,
            'late' => 0,
            'missed' => 0,
            'pending_open' => 0,
            'accuracy_pct' => 100.0,
            'for_others' => 0,
            'by_others' => 0,
        ];

        $emptySection = [
            'total_done' => 0,
            'taken' => 0,
            'rescheduled' => 0,
            'on_time' => 0,
            'late' => 0,
            'missed' => 0,
            'pending_open' => 0,
            'accuracy_pct' => 100.0,
            'avg_delay_minutes' => 0,
            'avg_delay_label' => translate('Progress_no_delay') ?? '—',
            'for_others' => 0,
            'by_others' => 0,
            'late_rows' => [],
            'summary_rows' => $zeroRows(),
            'daily_categories' => [],
            'daily_done' => [],
            'daily_on_time' => [],
            'daily_late' => [],
            'daily_missed' => [],
        ];

        $emptyLeadSection = array_merge($emptySection, [
            'widget_rows' => $this->buildScopeWidgetRows($emptyMetrics, 'lead'),
        ]);
        $emptyBookingSection = array_merge($emptySection, [
            'widget_rows' => $this->buildScopeWidgetRows($emptyMetrics, 'booking'),
        ]);

        $emptyOutcomeBuckets = [];
        foreach (['on_time', 'late', 'missed'] as $key) {
            $emptyOutcomeBuckets[$key] = [
                'total' => 0,
                'counts' => ['converted' => 0, 'cancelled' => 0, 'pending' => 0],
                'rates' => ['converted' => 0.0, 'cancelled' => 0.0, 'pending' => 0.0],
                'rows' => [],
                'success_rate' => 0.0,
                'cancel_rate' => 0.0,
            ];
        }
        $emptyBookingOutcomeBuckets = [];
        foreach (['on_time', 'late', 'missed'] as $key) {
            $emptyBookingOutcomeBuckets[$key] = [
                'total' => 0,
                'counts' => ['completed' => 0, 'cancelled' => 0, 'pending' => 0],
                'rates' => ['completed' => 0.0, 'cancelled' => 0.0, 'pending' => 0.0],
                'rows' => [],
                'success_rate' => 0.0,
                'cancel_rate' => 0.0,
            ];
        }
        $emptyDisciplineLabels = [
            translate('Progress_on_time_followups') ?? translate('Follow_up_accuracy'),
            translate('Progress_late_followups') ?? translate('Pending'),
            translate('Progress_missed_followups'),
        ];
        $emptyComparisonRows = array_map(fn (string $label, int $i) => [
            'key' => ['on_time', 'late', 'missed'][$i],
            'label' => $label,
            'total' => 0,
            'success_rate' => 0.0,
            'cancel_rate' => 0.0,
            'pending_rate' => 0.0,
            'outcome_rows' => [],
        ], $emptyDisciplineLabels, array_keys($emptyDisciplineLabels));

        return [
            'overall' => array_merge($emptySection, [
                'pending' => 0,
                'missed_open' => 0,
                'summary_rows' => $zeroRows(),
            ]),
            'leads' => $emptyLeadSection,
            'bookings' => $emptyBookingSection,
            'outcome_impact' => [
                'discipline_labels' => $emptyDisciplineLabels,
                'leads' => [
                    'buckets' => $emptyOutcomeBuckets,
                    'comparison_rows' => $emptyComparisonRows,
                    'general_by_timing' => array_map(fn (string $label, int $i) => [
                        'key' => ['on_time', 'late', 'missed'][$i],
                        'label' => $label,
                        'total' => 0,
                        'customer' => 0,
                        'provider' => 0,
                        'future_customer' => 0,
                        'invalid' => 0,
                        'unknown' => 0,
                    ], $emptyDisciplineLabels, array_keys($emptyDisciplineLabels)),
                    'customer' => [
                        'buckets' => $emptyOutcomeBuckets,
                        'comparison_rows' => $emptyComparisonRows,
                    ],
                    'provider' => [
                        'buckets' => $emptyOutcomeBuckets,
                        'comparison_rows' => $emptyComparisonRows,
                    ],
                ],
                'bookings' => [
                    'buckets' => $emptyBookingOutcomeBuckets,
                    'comparison_rows' => $emptyComparisonRows,
                ],
                'charts' => [
                    'lead_converted_pct' => [0.0, 0.0, 0.0],
                    'lead_cancelled_pct' => [0.0, 0.0, 0.0],
                    'lead_pending_pct' => [0.0, 0.0, 0.0],
                    'booking_completed_pct' => [0.0, 0.0, 0.0],
                    'booking_cancelled_pct' => [0.0, 0.0, 0.0],
                    'booking_pending_pct' => [0.0, 0.0, 0.0],
                ],
            ],
            'aging_buckets' => $this->buildDelayBuckets([], []),
            'charts' => [
                'categories' => [],
                'lead_categories' => [],
                'booking_categories' => [],
                'lead_done_series' => [],
                'booking_done_series' => [],
                'lead_late_series' => [],
                'booking_late_series' => [],
                'lead_missed_series' => [],
                'booking_missed_series' => [],
                'outcome_discipline_labels' => $emptyDisciplineLabels,
                'lead_converted_pct_series' => [0.0, 0.0, 0.0],
                'lead_cancelled_pct_series' => [0.0, 0.0, 0.0],
                'lead_pending_pct_series' => [0.0, 0.0, 0.0],
                'booking_completed_pct_series' => [0.0, 0.0, 0.0],
                'booking_cancelled_pct_series' => [0.0, 0.0, 0.0],
                'booking_pending_pct_series' => [0.0, 0.0, 0.0],
            ],
        ];
    }
}
