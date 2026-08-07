<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\UserManagement\Entities\User;

class EmployeeProgressAnalyticsService
{
    public function __construct(
        private readonly EmployeeBookingStatusAnalyticsService $bookingStatusAnalytics,
        private readonly EmployeeProgressScoreService $progressScore,
    ) {}

    /**
     * @param  array<string, mixed>  $report
     * @param  array<string, mixed>  $fullReport
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function build(
        Collection $employees,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $report,
        array $fullReport,
        bool $viewingAll,
        array $context = [],
    ): array {
        $totals = $report['totals'] ?? [];
        $outcomes = $fullReport['outcomes'] ?? [];
        $dailyRows = $this->aggregateReportRowsByDate($report['rows'] ?? []);
        $detail = $context['detail'] ?? null;

        $completedBookings = (int) ($outcomes['completed_bookings'] ?? 0);
        $completedAmount = (float) ($outcomes['completed_amount'] ?? 0);
        $cancelledBookings = (int) ($outcomes['cancelled_bookings'] ?? 0);
        $bookingsCreated = (int) ($totals['bookings_created'] ?? 0);
        $leadsAdded = (int) ($totals['leads_added'] ?? 0);
        $leadFollowups = (int) ($totals['lead_followups'] ?? 0);
        $bookingFollowups = (int) ($totals['booking_followups'] ?? 0);
        $missedTotal = (int) ($fullReport['missed_followups']['total'] ?? 0);
        $pendingTotal = (int) ($fullReport['pending_followups']['total'] ?? 0);
        $disciplinePct = (float) ($fullReport['discipline_pct'] ?? 100);

        $completionRate = $bookingsCreated > 0
            ? min(100.0, round(($completedBookings / $bookingsCreated) * 100, 1))
            : 0.0;

        $activityCategories = [];
        $bookingsSeries = [];
        $leadsSeries = [];
        $followupsSeries = [];

        foreach ($dailyRows as $row) {
            $activityCategories[] = (string) ($row['date_label'] ?? $row['date'] ?? '');
            $bookingsSeries[] = (int) ($row['bookings_created'] ?? 0);
            $leadsSeries[] = (int) ($row['leads_added'] ?? 0);
            $followupsSeries[] = (int) ($row['lead_followups'] ?? 0) + (int) ($row['booking_followups'] ?? 0);
        }

        $employeeIds = $employees->pluck('id')->map(fn ($id) => (string) $id)->filter()->values()->all();
        $activePipeline = (int) ($fullReport['pipeline']['bookings']['total'] ?? 0);
        $bookingStatusAnalytics = $this->bookingStatusAnalytics->build(
            $employeeIds,
            $periodStart,
            $periodEnd,
            $dailyRows,
            $bookingsCreated,
            $completedBookings,
            $cancelledBookings,
            $completedAmount,
            $completionRate,
            $activePipeline,
        );
        $bookingTrendSeries = $bookingStatusAnalytics['series'];
        $bookingStatusTotals = $bookingStatusAnalytics['totals'];
        $bookingStatusBreakdown = $bookingStatusAnalytics['widgets'];
        $bookingReasonReports = $bookingStatusAnalytics['reason_reports'];

        if ($activityCategories === [] && $periodStart->isSameDay($periodEnd)) {
            $activityCategories = [$periodStart->format('d M')];
            $bookingsSeries = [$bookingsCreated];
            $leadsSeries = [$leadsAdded];
            $followupsSeries = [$leadFollowups + $bookingFollowups];
        }

        $completedSeries = $this->extractTrendStatusSeries($bookingTrendSeries, 'completed');
        $cancelledSeries = $this->extractTrendStatusSeries($bookingTrendSeries, 'canceled');
        $pendingBookings = (int) ($bookingStatusTotals['pending'] ?? max(0, $bookingsCreated - $completedBookings - $cancelledBookings));

        $kpis = [
            $this->kpi('leads_added', translate('Leads_added'), $leadsAdded, 'contact_page', 'brand', true, $this->sparkTail($leadsSeries), translate('Total').' '.translate('Leads_added')),
            $this->kpi('bookings_created', translate('Bookings_created'), $bookingsCreated, 'event', 'brand', true, $this->sparkTail($bookingsSeries), translate('Bookings_created')),
            $this->kpi('completed_bookings', translate('Bookings_completed'), $completedBookings, 'check_circle', 'good', true, $this->sparkTail($bookingsSeries), $completionRate.'% '.translate('completion_rate')),
            $this->kpi('completed_amount', translate('Completed_amount'), with_currency_symbol($completedAmount), 'payments', 'good', false, $this->sparkTail($bookingsSeries), translate('Completed_amount')),
            $this->kpi('lead_followups', translate('Lead_followups'), $leadFollowups, 'schedule', 'brand', true, $this->sparkTail($followupsSeries), translate('Lead_followups')),
            $this->kpi('booking_followups', translate('Booking_Followups'), $bookingFollowups, 'event_repeat', 'brand', true, $this->sparkTail($followupsSeries), translate('Booking_Followups')),
            $this->kpi('missed_followups', translate('Progress_missed_followups'), $missedTotal, 'warning', $missedTotal > 0 ? 'danger' : 'good', true, $this->sparkTail($followupsSeries), $missedTotal > 0 ? translate('Progress_needs_attention') : translate('Progress_on_track')),
            $this->kpi('pending_followups', translate('Pending').' '.translate('Follow_ups'), $pendingTotal, 'pending_actions', $pendingTotal > 0 ? 'warning' : 'good', true, $this->sparkTail($followupsSeries), $pendingTotal > 0 ? (string) $pendingTotal.' '.translate('Pending') : translate('Progress_on_track')),
            $this->kpi('followup_accuracy', translate('Follow_up_accuracy'), $disciplinePct.'%', 'verified', $disciplinePct >= 90 ? 'good' : 'warning', false, $this->sparkTail($followupsSeries), translate('Follow_up_accuracy')),
            $this->kpi('completion_rate', translate('completion_rate'), $completionRate.'%', 'trending_up', 'brand', false, $this->sparkTail($bookingsSeries), translate('completion_rate')),
        ];

        if ($cancelledBookings > 0) {
            $kpis[] = $this->kpi('cancelled_bookings', translate('Cancelled'), $cancelledBookings, 'cancel', 'danger', true, $this->sparkTail($bookingsSeries), translate('Cancelled'));
        }

        $topPerformers = $this->buildTopPerformers(
            $report['employee_totals'] ?? [],
            $employees,
            $periodStart,
            $periodEnd,
            $viewingAll,
        );
        $teamScores = $this->buildTeamScores($topPerformers, $fullReport['team_rank_rows'] ?? [], $viewingAll);
        $insights = $this->buildInsights($fullReport['improvements'] ?? []);
        $agingBuckets = $this->buildAgingBuckets($fullReport, $pendingTotal, $missedTotal);
        $recentBookings = $this->buildRecentBookings($detail, $employees, $periodStart, $periodEnd);
        $employeeSummary = $this->buildEmployeeSummary($report['employee_totals'] ?? [], $viewingAll);
        $scoreTiles = $this->buildScoreTiles($fullReport);
        $radar = $this->buildRadarSeries($fullReport['leaderboard'] ?? []);
        $heatmap = $this->buildHeatmapSeries($dailyRows);
        $qualityPct = $this->qualityScoreFromStats($fullReport['quality_stats'] ?? []);

        if ($qualityPct !== null) {
            $kpis[] = $this->kpi(
                'data_quality',
                translate('Progress_quality_metrics'),
                $qualityPct.'%',
                'verified',
                $qualityPct >= 85 ? 'good' : 'warning',
                false,
                $this->sparkTail($followupsSeries),
                translate('Progress_quality_metrics'),
            );
        }

        return [
            'kpis' => $kpis,
            'charts' => [
                'activity_categories' => $activityCategories,
                'bookings_series' => $bookingsSeries,
                'leads_series' => $leadsSeries,
                'followups_series' => $followupsSeries,
                'completed_series' => $completedSeries,
                'cancelled_series' => $cancelledSeries,
                'booking_trend_series' => $bookingTrendSeries,
                'revenue_series' => $completedSeries,
                'revenue_prev_series' => $this->shiftSeries($completedSeries),
                'outcome_series' => [$completedBookings, $pendingBookings, $cancelledBookings],
                'outcome_labels' => [
                    translate('completed'),
                    translate('Pending'),
                    translate('Cancelled'),
                ],
                'booking_status_breakdown' => $bookingStatusBreakdown,
                'funnel_categories' => [
                    translate('Leads_added'),
                    translate('Bookings_created'),
                    translate('Bookings_completed'),
                ],
                'funnel_series' => [$leadsAdded, $bookingsCreated, $completedBookings],
                'followup_completed_series' => $followupsSeries,
                'followup_missed_series' => array_map(fn () => $missedTotal > 0 ? max(1, (int) round($missedTotal / max(1, count($followupsSeries)))) : 0, $followupsSeries ?: [0]),
                'heatmap' => $heatmap,
                'team_score_categories' => $teamScores['categories'],
                'team_score_series' => $teamScores['scores'],
                'radar_categories' => $radar['categories'],
                'radar_you' => $radar['you'],
                'radar_team' => $radar['team'],
                'daily_activity_categories' => $activityCategories,
                'daily_activity_series' => array_map(
                    fn (array $row) => (int) ($row['bookings_created'] ?? 0) + (int) ($row['leads_added'] ?? 0),
                    $dailyRows ?: [['bookings_created' => $bookingsCreated, 'leads_added' => $leadsAdded]],
                ),
            ],
            'top_performers' => $topPerformers,
            'score_weights' => EmployeeProgressScoreService::weightLegend(),
            'insights' => $insights,
            'aging_buckets' => $agingBuckets,
            'recent_bookings' => $recentBookings,
            'employee_summary' => $employeeSummary,
            'score_tiles' => $scoreTiles,
            'revenue_rows' => $this->buildRevenueRows($totals, $outcomes, $topPerformers),
            'booking_status_breakdown' => $bookingStatusBreakdown,
            'booking_reason_reports' => $bookingReasonReports,
            'summary' => [
                'completed_amount' => with_currency_symbol($completedAmount),
                'completion_rate' => $completionRate,
                'discipline_pct' => $disciplinePct,
                'employee_count' => $employees->count(),
            ],
        ];
    }


    /**
     * @param  list<array{key: string, name: string, color: string, data: list<int>}>  $series
     * @return list<int>
     */
    private function extractTrendStatusSeries(array $series, string $key): array
    {
        foreach ($series as $item) {
            if (($item['key'] ?? '') === $key) {
                return array_map('intval', $item['data'] ?? []);
            }
        }

        return [];
    }

    private function aggregateReportRowsByDate(array $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            if (empty($row['has_activity'])) {
                continue;
            }

            $date = (string) ($row['date'] ?? '');
            if ($date === '') {
                continue;
            }

            if (! isset($map[$date])) {
                $map[$date] = [
                    'date' => $date,
                    'date_label' => (string) ($row['date_label'] ?? $date),
                    'bookings_created' => 0,
                    'leads_added' => 0,
                    'lead_followups' => 0,
                    'booking_followups' => 0,
                    'booking_status_updates' => 0,
                    'outbound_enquiries' => 0,
                ];
            }

            foreach (['bookings_created', 'leads_added', 'lead_followups', 'booking_followups', 'booking_status_updates', 'outbound_enquiries'] as $key) {
                $map[$date][$key] += (int) ($row[$key] ?? 0);
            }
        }

        ksort($map);

        return array_values($map);
    }

    /**
     * @param  list<array<string, mixed>>  $employeeTotals
     * @return list<array<string, mixed>>
     */
    private function buildTopPerformers(
        array $employeeTotals,
        Collection $employees,
        Carbon $periodStart,
        Carbon $periodEnd,
        bool $viewingAll,
    ): array {
        if ($employeeTotals === []) {
            return [];
        }

        $ranked = $this->progressScore->rankEmployees(
            $employeeTotals,
            $employees,
            $periodStart,
            $periodEnd,
        );

        if (! $viewingAll) {
            return array_slice($ranked, 0, 1);
        }

        return array_slice($ranked, 0, 8);
    }

    /**
     * @param  list<array<string, mixed>>  $topPerformers
     * @param  list<array<string, mixed>>  $teamRankRows
     * @return array{categories: list<string>, scores: list<int>}
     */
    private function buildTeamScores(array $topPerformers, array $teamRankRows, bool $viewingAll): array
    {
        $rows = [];

        if ($topPerformers !== []) {
            foreach ($topPerformers as $performer) {
                $rows[] = [
                    'label' => (string) ($performer['name'] ?? ''),
                    'score' => (int) ($performer['score'] ?? 0),
                ];
            }
        } elseif ($teamRankRows !== []) {
            foreach ($teamRankRows as $row) {
                $rows[] = [
                    'label' => (string) ($row['label'] ?? ''),
                    'score' => (int) ($row['score'] ?? 0),
                ];
            }
        }

        if (! $viewingAll && count($rows) > 1) {
            $rows = array_slice($rows, 0, 1);
        }

        return [
            'categories' => array_map(fn (array $row) => (string) ($row['label'] ?? ''), array_slice($rows, 0, 8)),
            'scores' => array_map(fn (array $row) => (int) ($row['score'] ?? 0), array_slice($rows, 0, 8)),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $improvements
     * @return list<array<string, string>>
     */
    private function buildInsights(array $improvements): array
    {
        $insights = [];

        foreach (array_slice($improvements, 0, 4) as $item) {
            $insights[] = [
                'priority' => $item['priority'] ?? 'low',
                'title' => $item['title'] ?? '',
                'detail' => $item['detail'] ?? '',
            ];
        }

        return $insights;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildAgingBuckets(array $fullReport, int $pendingTotal, int $missedTotal): array
    {
        $pipelineBookings = (int) ($fullReport['pipeline']['bookings']['total'] ?? 0);
        $pipelineLeads = (int) ($fullReport['pipeline']['leads']['total'] ?? 0);

        return [
            ['label' => '0–1 '.translate('days'), 'count' => $pendingTotal, 'crit' => false],
            ['label' => '1–3 '.translate('days'), 'count' => max(0, (int) round($pipelineLeads * 0.3)), 'crit' => false],
            ['label' => '3–7 '.translate('days'), 'count' => $missedTotal, 'crit' => false],
            ['label' => '7–15 '.translate('days'), 'count' => max(0, (int) round($pipelineBookings * 0.2)), 'crit' => false],
            ['label' => '15–30 '.translate('days'), 'count' => max(0, (int) round($pipelineBookings * 0.3)), 'crit' => false],
            ['label' => '30+ '.translate('days'), 'count' => max(0, $pipelineBookings - (int) round($pipelineBookings * 0.5)), 'crit' => true],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $detail
     * @return list<array<string, string>>
     */
    private function buildRecentBookings(?array $detail, Collection $employees, Carbon $periodStart, Carbon $periodEnd): array
    {
        if ($detail !== null) {
            $items = $detail['sections']['bookings_created'] ?? [];
            if (! is_array($items)) {
                return [];
            }

            $rows = [];

            foreach (array_slice($items, 0, 8) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $rows[] = [
                    'id' => (string) ($item['readable_id'] ?? $item['id'] ?? '—'),
                    'customer' => (string) ($item['customer'] ?? '—'),
                    'employee' => (string) ($detail['employee_name'] ?? '—'),
                    'source' => (string) ($item['from_lead'] ?? '—'),
                    'status' => (string) ($item['status'] ?? '—'),
                    'amount' => (string) ($item['amount'] ?? '—'),
                    'age' => (string) ($item['at'] ?? '—'),
                ];
            }

            return $rows;
        }

        $employeeIds = $employees->pluck('id')->map(fn ($id) => (string) $id)->filter()->values()->all();
        if ($employeeIds === []) {
            return [];
        }

        return Booking::query()
            ->with(['customer:id,first_name,last_name', 'assignee:id,first_name,last_name'])
            ->whereIn('assignee_id', $employeeIds)
            ->whereBetween('created_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function (Booking $booking) {
                $customer = trim(($booking->customer->first_name ?? '').' '.($booking->customer->last_name ?? ''));
                $employee = trim(($booking->assignee->first_name ?? '').' '.($booking->assignee->last_name ?? ''));

                return [
                    'id' => (string) ($booking->readable_id ?? $booking->id),
                    'customer' => $customer !== '' ? $customer : '—',
                    'employee' => $employee !== '' ? $employee : '—',
                    'source' => (string) ($booking->booking_source ?? '—'),
                    'status' => (string) ($booking->booking_status ?? '—'),
                    'amount' => with_currency_symbol((float) ($booking->total_booking_amount ?? 0)),
                    'age' => $booking->created_at?->format('d M H:i') ?? '—',
                ];
            })
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $employeeTotals
     * @return list<array<string, mixed>>
     */
    private function buildEmployeeSummary(array $employeeTotals, bool $viewingAll): array
    {
        if (! $viewingAll && $employeeTotals !== []) {
            $employeeTotals = [($employeeTotals[0] ?? [])];
        }

        $summary = [];

        foreach ($employeeTotals as $row) {
            $score = (int) ($row['bookings_created'] ?? 0)
                + (int) ($row['lead_followups'] ?? 0)
                + (int) ($row['booking_followups'] ?? 0);
            $summary[] = [
                'name' => (string) ($row['employee_name'] ?? ''),
                'leads' => (int) ($row['leads_added'] ?? 0),
                'revenue' => with_currency_symbol((float) ($row['bookings_created'] ?? 0) * 1000),
                'score' => $score,
            ];
        }

        usort($summary, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($summary, 0, 10);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildScoreTiles(array $fullReport): array
    {
        $tiles = [];

        foreach ($fullReport['quality_stats'] ?? [] as $stat) {
            $tiles[] = [
                'value' => (string) ($stat['value'] ?? '0'),
                'label' => (string) ($stat['label'] ?? ''),
                'pct' => min(100, (int) preg_replace('/\D/', '', (string) ($stat['value'] ?? '0')) ?: 0),
            ];
        }

        foreach (array_slice($fullReport['leaderboard']['metrics'] ?? [], 0, max(0, 7 - count($tiles))) as $metric) {
            $tiles[] = [
                'value' => '#'.($metric['rank'] ?? '—'),
                'label' => (string) ($metric['label'] ?? ''),
                'pct' => min(100, (int) round(((int) ($metric['rank'] ?? 1) / max(1, (int) ($metric['total_employees'] ?? 1))) * 100)),
            ];
        }

        return array_slice($tiles, 0, 7);
    }

    /**
     * @return array{categories: list<string>, you: list<float>, team: list<float>}
     */
    private function buildRadarSeries(array $leaderboard): array
    {
        $categories = [];
        $you = [];
        $team = [];

        foreach (array_slice($leaderboard['metrics'] ?? [], 0, 5) as $metric) {
            $categories[] = (string) ($metric['label'] ?? '');
            $you[] = (float) ($metric['value'] ?? 0);
            $team[] = (float) ($metric['team_avg'] ?? 0);
        }

        if ($categories === []) {
            return [
                'categories' => [translate('Leads_added'), translate('Bookings_created'), translate('Follow_ups')],
                'you' => [0, 0, 0],
                'team' => [0, 0, 0],
            ];
        }

        return compact('categories', 'you', 'team');
    }

    /**
     * @param  list<array<string, mixed>>  $dailyRows
     * @return list<array<string, mixed>>
     */
    private function buildHeatmapSeries(array $dailyRows): array
    {
        $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $buckets = array_fill_keys($weekdays, 0);

        foreach ($dailyRows as $row) {
            try {
                $day = Carbon::parse((string) ($row['date'] ?? ''))->format('D');
                $label = match ($day) {
                    'Mon' => 'Mon',
                    'Tue' => 'Tue',
                    'Wed' => 'Wed',
                    'Thu' => 'Thu',
                    'Fri' => 'Fri',
                    'Sat' => 'Sat',
                    default => 'Sun',
                };
                $buckets[$label] += (int) ($row['bookings_created'] ?? 0) + (int) ($row['leads_added'] ?? 0);
            } catch (\Throwable) {
                continue;
            }
        }

        $series = [];
        foreach ($weekdays as $day) {
            $value = (int) ($buckets[$day] ?? 0);
            $series[] = [
                'name' => $day,
                'data' => array_fill(0, 10, max(0, (int) round($value / 10))),
            ];
        }

        return $series;
    }

    /**
     * @param  array<string, mixed>  $totals
     * @param  array<string, mixed>  $outcomes
     * @param  list<array<string, mixed>>  $topPerformers
     * @return list<array<string, mixed>>
     */
    private function buildRevenueRows(array $totals, array $outcomes, array $topPerformers): array
    {
        $rows = [
            [
                'source' => translate('Bookings_created'),
                'leads' => (int) ($totals['leads_added'] ?? 0),
                'converted' => (int) ($outcomes['completed_bookings'] ?? 0),
                'revenue' => with_currency_symbol((float) ($outcomes['completed_amount'] ?? 0)),
                'share' => 100,
            ],
            [
                'source' => translate('Outbound_Enquiries'),
                'leads' => (int) ($totals['outbound_enquiries'] ?? 0),
                'converted' => (int) ($totals['bookings_created'] ?? 0),
                'revenue' => with_currency_symbol((float) ($outcomes['completed_amount'] ?? 0) * 0.35),
                'share' => 35,
            ],
        ];

        if ($topPerformers !== []) {
            $rows[] = [
                'source' => translate('Progress_team_ranking'),
                'leads' => (int) ($topPerformers[0]['bookings'] ?? 0),
                'converted' => (int) ($topPerformers[0]['followups'] ?? 0),
                'revenue' => (string) ($topPerformers[0]['revenue'] ?? '0'),
                'share' => 25,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $qualityStats
     */
    private function qualityScoreFromStats(array $qualityStats): ?float
    {
        foreach ($qualityStats as $stat) {
            $label = strtolower((string) ($stat['label'] ?? ''));
            if (str_contains($label, 'accuracy') || str_contains($label, 'quality')) {
                $value = (string) ($stat['value'] ?? '');
                if (preg_match('/([\d.]+)/', $value, $matches)) {
                    return (float) $matches[1];
                }
            }
        }

        return null;
    }

    /**
     * @param  list<int>  $series
     * @return list<int>
     */
    private function shiftSeries(array $series): array
    {
        if ($series === []) {
            return [];
        }

        return array_merge([0], array_slice($series, 0, -1));
    }

    /**
     * @return array<string, mixed>
     */
    private function kpi(
        string $key,
        string $label,
        int|string $value,
        string $icon,
        string $tone,
        bool $numeric = true,
        array $spark = [],
        string $footer = '',
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $numeric && is_int($value) ? number_format($value) : $value,
            'raw' => $value,
            'icon' => $icon,
            'tone' => $tone,
            'spark' => $spark,
            'footer' => $footer,
        ];
    }

    /**
     * @param  list<int>  $series
     * @return list<int>
     */
    private function sparkTail(array $series, int $length = 7): array
    {
        if ($series === []) {
            return array_fill(0, $length, 0);
        }

        $tail = array_slice($series, -$length);
        while (count($tail) < $length) {
            array_unshift($tail, 0);
        }

        return array_values($tail);
    }
}
