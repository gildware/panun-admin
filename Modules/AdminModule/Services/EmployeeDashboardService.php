<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\AdminModule\Services\Report\DailyEmployeeReportService;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\TaskBoardModule\Entities\TaskColumn;
use Modules\TaskBoardModule\Entities\TaskTicket;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\WhatsAppChatStatus;
use Modules\WhatsAppModule\Entities\WhatsAppChatThreadMeta;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\WhatsAppModule\Support\SocialInboxChannel;

class EmployeeDashboardService
{
    public function __construct(
        protected LeadOpenStatusService $leadOpenStatus,
        protected DailyEmployeeReportService $dailyEmployeeReport,
        protected EmployeeBookingStatusAnalyticsService $bookingStatusAnalytics,
        protected EmployeeProgressScoreService $progressScore,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $userId = (string) $user->id;
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $employeeScope = $this->usesEmployeeDashboardScope($user);

        $monthReport = $this->dailyEmployeeReport->buildReport(collect([$user]), $monthStart, $monthEnd);
        $todayReport = $this->dailyEmployeeReport->buildReport(collect([$user]), $today, $today);
        $monthTotals = $monthReport['employee_totals'][0] ?? [];
        $todayTotals = $todayReport['employee_totals'][0] ?? [];

        $attentionContext = $this->buildAttentionContext($userId, $today, $employeeScope);
        $workQueue = $this->buildWorkQueue($userId, $attentionContext, $employeeScope);
        $focus = $this->focusLineFromWorkQueue($workQueue);

        $monthlyPerformance = $this->monthlyPerformance($userId, $monthStart, $monthEnd, $monthTotals);
        $contributionVsAll = [
            'today' => $this->contributionVsAllForPeriod(
                $userId,
                $today,
                $today,
                $todayTotals,
                $this->completedBookingsCount($userId, $today, $today),
            ),
            'monthly' => $this->contributionVsAllForPeriod(
                $userId,
                $monthStart,
                $monthEnd,
                $monthTotals,
                (int) ($monthlyPerformance['completed_bookings'] ?? 0),
            ),
        ];
        $leaderboard = $this->teamLeaderboardForPeriod($userId, $monthStart, $monthEnd);
        $teamEmployees = $this->dashboardEmployees();
        $teamRankDaily = $this->teamOverallRankRows($teamEmployees, $today, $today);
        $teamRankMonthly = $this->teamOverallRankRows($teamEmployees, $monthStart, $monthEnd);
        if ($employeeScope) {
            $teamRankDaily = $this->filterRankRowsForEmployee($teamRankDaily, $userId);
            $teamRankMonthly = $this->filterRankRowsForEmployee($teamRankMonthly, $userId);
        }

        $payload = [
            'user' => $user,
            'greeting' => $this->greetingForUser($user),
            'focus_line' => $focus['line'],
            'focus_all_clear' => $focus['all_clear'],
            'work_queue' => $workQueue,
            'dashboard_employees' => $employeeScope ? [] : ($attentionContext['employee_options'] ?? []),
            'default_employee_id' => $employeeScope ? '' : (string) ($attentionContext['default_employee_id'] ?? ''),
            'default_dashboard_scope' => $employeeScope ? '' : '__all__',
            'today_done' => $this->formatTodayDone($todayTotals, $userId),
            'monthly' => $monthlyPerformance,
            'contribution_vs_all' => $contributionVsAll,
            'quality_stats' => $monthlyPerformance['quality_stats'] ?? [],
            'quality_stats_daily' => $this->buildQualityStatsForUser($userId, $today, $today, $todayTotals),
            'quality_stats_monthly' => $monthlyPerformance['quality_stats'] ?? [],
            'leaderboard' => $leaderboard,
            'progress_side_panel' => 'team_rank',
            'highlight_employee_id' => $userId,
            'team_rank_rows' => $teamRankMonthly,
            'team_rank_rows_daily' => $teamRankDaily,
            'team_rank_rows_monthly' => $teamRankMonthly,
        ];

        if (! $employeeScope) {
            $payload['progress_scopes'] = $this->buildAdminProgressScopes($teamEmployees);
            $payload['highlight_employee_id'] = '';
            $payload['team_rank_rows'] = [];
            $payload['team_rank_rows_daily'] = [];
            $payload['team_rank_rows_monthly'] = [];
        }

        return $payload;
    }

    /**
     * @param  Collection<int, User>  $employees
     * @return array<string, array<string, mixed>>
     */
    private function buildAdminProgressScopes(Collection $employees): array
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $teamRankDaily = $this->teamOverallRankRows($employees, $today, $today);
        $teamRankMonthly = $this->teamOverallRankRows($employees, $monthStart, $monthEnd);

        $scopes = [
            '__all__' => $this->buildTeamProgressScope($employees, $teamRankDaily, $teamRankMonthly),
        ];

        foreach ($employees as $employee) {
            $scopes[(string) $employee->id] = $this->buildEmployeeProgressScope(
                $employee,
                $teamRankDaily,
                $teamRankMonthly,
            );
        }

        return $scopes;
    }

    /**
     * @param  list<array<string, mixed>>|null  $teamRankDaily
     * @param  list<array<string, mixed>>|null  $teamRankMonthly
     * @return array<string, mixed>
     */
    private function buildEmployeeProgressScope(
        User $employee,
        ?array $teamRankDaily = null,
        ?array $teamRankMonthly = null,
    ): array {
        $userId = (string) $employee->id;
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $monthReport = $this->dailyEmployeeReport->buildReport(collect([$employee]), $monthStart, $monthEnd);
        $todayReport = $this->dailyEmployeeReport->buildReport(collect([$employee]), $today, $today);
        $monthTotals = $monthReport['employee_totals'][0] ?? [];
        $todayTotals = $todayReport['employee_totals'][0] ?? [];
        $monthlyPerformance = $this->monthlyPerformance($userId, $monthStart, $monthEnd, $monthTotals);
        $leaderboard = $this->teamLeaderboardForPeriod($userId, $monthStart, $monthEnd);
        $teamEmployees = $this->dashboardEmployees();
        $teamRankDaily ??= $this->teamOverallRankRows($teamEmployees, $today, $today);
        $teamRankMonthly ??= $this->teamOverallRankRows($teamEmployees, $monthStart, $monthEnd);

        $name = trim((string) $employee->first_name.' '.(string) $employee->last_name);
        if ($name === '') {
            $name = (string) $employee->email;
        }

        return [
            'title' => $name,
            'subtitle' => translate('Employee_progress_sub'),
            'view_report_url' => route('admin.my-progress', [
                'tab' => 'daily',
                'date' => $today->toDateString(),
                'employee_id' => $userId,
            ]),
            'today_done' => $this->formatTodayDone($todayTotals, $userId),
            'monthly' => $monthlyPerformance,
            'quality_stats_daily' => $this->buildQualityStatsForUser($userId, $today, $today, $todayTotals),
            'quality_stats_monthly' => $monthlyPerformance['quality_stats'] ?? [],
            'quality_stats' => $monthlyPerformance['quality_stats'] ?? [],
            'contribution_today' => $this->contributionVsAllForPeriod(
                $userId,
                $today,
                $today,
                $todayTotals,
                $this->completedBookingsCount($userId, $today, $today),
            ),
            'contribution_monthly' => $this->contributionVsAllForPeriod(
                $userId,
                $monthStart,
                $monthEnd,
                $monthTotals,
                (int) ($monthlyPerformance['completed_bookings'] ?? 0),
            ),
            'leaderboard' => $leaderboard,
            'progress_side_panel' => 'team_rank',
            'highlight_employee_id' => $userId,
            'team_rank_rows' => $teamRankMonthly,
            'team_rank_rows_daily' => $teamRankDaily,
            'team_rank_rows_monthly' => $teamRankMonthly,
        ];
    }

    /**
     * @param  Collection<int, User>  $employees
     * @param  list<array<string, mixed>>|null  $teamRankDaily
     * @param  list<array<string, mixed>>|null  $teamRankMonthly
     * @return array<string, mixed>
     */
    private function buildTeamProgressScope(
        Collection $employees,
        ?array $teamRankDaily = null,
        ?array $teamRankMonthly = null,
    ): array {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $monthReport = $this->dailyEmployeeReport->buildReport($employees, $monthStart, $monthEnd);
        $todayReport = $this->dailyEmployeeReport->buildReport($employees, $today, $today);
        $monthTotals = $monthReport['totals'] ?? [];
        $todayTotals = $todayReport['totals'] ?? [];

        $monthlyPerformance = $this->monthlyPerformanceForTeam($monthStart, $monthEnd, $monthTotals, $employees);
        $qualityDaily = $this->buildTeamQualityStatsForPeriod($employees, $today, $today, $todayTotals);
        $qualityMonthly = $this->buildTeamQualityStatsForPeriod($employees, $monthStart, $monthEnd, $monthTotals);
        $teamRankDaily ??= $this->teamOverallRankRows($employees, $today, $today);
        $teamRankMonthly ??= $this->teamOverallRankRows($employees, $monthStart, $monthEnd);

        return [
            'title' => translate('Team_Progress'),
            'subtitle' => translate('Team_progress_sub'),
            'view_report_url' => route('admin.my-progress', ['tab' => 'monthly']),
            'today_done' => $this->formatTodayDoneForTeam($todayTotals, $today, $today),
            'monthly' => $monthlyPerformance,
            'quality_stats_daily' => $qualityDaily,
            'quality_stats_monthly' => $qualityMonthly,
            'quality_stats' => $qualityMonthly,
            'contribution_today' => $this->teamEmployeeShareRows(
                $todayReport['employee_totals'] ?? [],
                $todayTotals,
                $today,
                $today,
            ),
            'contribution_monthly' => $this->teamEmployeeShareRows(
                $monthReport['employee_totals'] ?? [],
                $monthTotals,
                $monthStart,
                $monthEnd,
            ),
            'leaderboard' => [
                'overall_rank' => 0,
                'total_employees' => $employees->count(),
                'overall_score' => 0,
                'metrics' => [],
            ],
            'progress_side_panel' => 'team_rank',
            'highlight_employee_id' => '',
            'team_rank_rows' => $teamRankMonthly,
            'team_rank_rows_daily' => $teamRankDaily,
            'team_rank_rows_monthly' => $teamRankMonthly,
        ];
    }

    /**
     * @param  array<string, mixed>  $todayTotals
     * @return array{total: int, items: list<array<string, mixed>>}
     */
    private function formatTodayDoneForTeam(array $todayTotals, Carbon $periodStart, Carbon $periodEnd): array
    {
        $items = $this->buildTeamProgressStatItems($todayTotals, $periodStart, $periodEnd);

        return [
            'total' => $this->progressStatActivityTotal($items),
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $monthTotals
     * @param  Collection<int, User>  $employees
     * @return array<string, mixed>
     */
    private function monthlyPerformanceForTeam(
        Carbon $monthStart,
        Carbon $monthEnd,
        array $monthTotals,
        Collection $employees,
    ): array {
        $outcomes = $this->teamBookingOutcomesForPeriod($monthStart, $monthEnd);
        $missedStats = $this->teamMissedFollowupStats($employees);
        $disciplinePct = $missedStats['accuracy_pct'];
        $stats = $this->buildTeamProgressStatItems($monthTotals, $monthStart, $monthEnd);

        return [
            'period_label' => Carbon::now()->format('F Y'),
            'completed_bookings' => $outcomes['completed_bookings'],
            'completed_amount' => $outcomes['completed_amount'],
            'cancelled_bookings' => $outcomes['cancelled_bookings'],
            'lead_followups' => (int) ($monthTotals['lead_followups'] ?? 0),
            'booking_followups' => (int) ($monthTotals['booking_followups'] ?? 0),
            'outbounds_done' => (int) ($monthTotals['outbound_enquiries'] ?? 0),
            'followup_discipline_pct' => $disciplinePct,
            'missed_followups' => $missedStats['missed'],
            'stats' => $stats,
            'discipline_stat' => [
                'key' => 'followup_accuracy',
                'icon' => 'verified',
                'label' => translate('Follow_up_accuracy'),
                'value' => $disciplinePct.'%',
                'is_zero' => false,
                'tone' => $disciplinePct >= 90 ? 'good' : ($disciplinePct >= 70 ? 'brand' : 'warn'),
                'sub' => $missedStats['missed'] > 0
                    ? str_replace(':count', (string) $missedStats['missed'], translate('Progress_missed_followups_sub'))
                    : null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $periodTotals
     * @return list<array<string, mixed>>
     */
    private function buildTeamProgressStatItems(array $periodTotals, Carbon $periodStart, Carbon $periodEnd): array
    {
        $outcomes = $this->teamBookingOutcomesForPeriod($periodStart, $periodEnd);
        $employeeIds = $this->dashboardEmployees()->pluck('id')->map(fn ($id) => (string) $id)->filter()->values()->all();
        $statusTotals = $this->bookingStatusTotalsForEmployees($employeeIds, $periodStart, $periodEnd, $periodTotals, $outcomes);
        $urls = $this->teamProgressStatUrls();
        $items = [];

        foreach ($this->progressStatMetricDefinitions() as $definition) {
            $key = $definition['key'];
            $source = $definition['source'];
            $raw = match ($source) {
                'report' => (int) ($periodTotals[$key] ?? 0),
                'completed_amount' => (float) ($outcomes[$source] ?? 0),
                'booking_status' => (int) ($statusTotals[$key] ?? 0),
                default => (int) ($outcomes[$source] ?? 0),
            };

            if ($key === 'bookings_created') {
                $created = $this->progressScore->bookingsCreatedByEmployee($employeeIds, $periodStart, $periodEnd);
                $raw = (int) array_sum($created);
            }
            if ($key === 'completed_bookings' && (int) ($statusTotals['completed'] ?? 0) > 0) {
                $raw = (int) $statusTotals['completed'];
            } elseif ($key === 'completed_bookings') {
                $completed = $this->progressScore->bookingsCompletedByEmployee($employeeIds, $periodStart, $periodEnd);
                $raw = (int) array_sum($completed);
            }
            if ($key === 'cancelled_bookings') {
                $raw = (int) ($statusTotals['canceled'] ?? 0) + (int) ($statusTotals['cancelled_after_visit'] ?? 0);
                if ($raw <= 0) {
                    $raw = (int) ($outcomes['cancelled_bookings'] ?? 0);
                }
            }

            $value = $source === 'completed_amount'
                ? with_currency_symbol($raw)
                : (string) (int) $raw;

            $isZero = $source === 'completed_amount' ? $raw <= 0 : $raw <= 0;
            // Always show core booking-status quantity tiles (even at 0).
            if ($source === 'booking_status' && $isZero && ! in_array($key, ['pending', 'accepted', 'ongoing', 'on_hold', 'hold_after_visit'], true)) {
                continue;
            }

            $tone = in_array($key, ['cancelled_bookings', 'cancelled_after_visit', 'disputed_cancelled', 'disputed_completed', 'loss_making'], true)
                ? ($raw > 0 ? 'warn' : 'neutral')
                : $definition['tone'];

            $items[] = [
                'key' => $key,
                'icon' => $definition['icon'],
                'label' => $definition['label'],
                'value' => $value,
                'raw' => $raw,
                'count' => (int) $raw,
                'is_zero' => $isZero,
                'tone' => $tone,
                'url' => $urls[$key] ?? null,
                'include_in_total' => $definition['include_in_total'],
            ];
        }

        return $items;
    }

    /**
     * @return array<string, string|null>
     */
    private function teamProgressStatUrls(): array
    {
        return [
            'lead_followups' => route('admin.lead.todays_followups'),
            'booking_followups' => route('admin.booking.todays_followups'),
            'leads_added' => route('admin.lead.index'),
            'bookings_created' => route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']),
            'outbound_enquiries' => route('admin.lead.outbound-enquiry.index'),
            'whatsapp_chats_replied' => route('admin.whatsapp.conversations.index', ['channel' => 'whatsapp', 'tab' => 'chats']),
            'whatsapp_chats_closed' => route('admin.whatsapp.conversations.index', ['channel' => 'whatsapp', 'tab' => 'chats']),
            'booking_status_updates' => route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']),
            'completed_bookings' => route('admin.booking.list', ['booking_status' => 'completed', 'service_type' => 'all']),
            'completed_amount' => route('admin.booking.list', ['booking_status' => 'completed', 'service_type' => 'all']),
            'cancelled_bookings' => route('admin.booking.list', ['booking_status' => 'canceled', 'service_type' => 'all']),
            'pending' => route('admin.booking.list', ['booking_status' => 'pending', 'service_type' => 'all']),
            'accepted' => route('admin.booking.list', ['booking_status' => 'accepted', 'service_type' => 'all']),
            'ongoing' => route('admin.booking.list', ['booking_status' => 'ongoing', 'service_type' => 'all']),
            'on_hold' => route('admin.booking.list', ['booking_status' => 'on_hold', 'service_type' => 'all']),
            'hold_after_visit' => route('admin.booking.list', ['booking_status' => 'hold_after_visit', 'service_type' => 'all']),
            'cancelled_after_visit' => route('admin.booking.list', ['booking_status' => 'cancelled_after_visit', 'service_type' => 'all']),
            'disputed_cancelled' => route('admin.booking.list', ['booking_status' => 'disputed_cancelled', 'service_type' => 'all']),
            'disputed_completed' => route('admin.booking.list', ['booking_status' => 'disputed_completed', 'service_type' => 'all']),
            'loss_making' => route('admin.booking.list', ['booking_status' => 'loss_making_pending', 'service_type' => 'all']),
            'loss_recovered' => route('admin.booking.list', ['booking_status' => 'loss_recovered', 'service_type' => 'all']),
            'loss_settled' => route('admin.booking.list', ['booking_status' => 'loss_settled', 'service_type' => 'all']),
        ];
    }

    /**
     * @return array{completed_bookings: int, completed_amount: float, cancelled_bookings: int}
     */
    private function teamBookingOutcomesForPeriod(Carbon $periodStart, Carbon $periodEnd): array
    {
        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        $completed = Booking::query()
            ->where('booking_status', 'completed')
            ->whereNotNull('assignee_id')
            ->whereBetween('updated_at', [$rangeStart, $rangeEnd]);

        $cancelled = Booking::query()
            ->where('booking_status', 'canceled')
            ->whereNotNull('assignee_id')
            ->whereBetween('updated_at', [$rangeStart, $rangeEnd]);

        return [
            'completed_bookings' => (int) (clone $completed)->count(),
            'completed_amount' => round((float) (clone $completed)->sum('total_booking_amount'), 2),
            'cancelled_bookings' => (int) $cancelled->count(),
        ];
    }

    /**
     * @param  Collection<int, User>  $employees
     * @return array{missed: int, due: int, accuracy_pct: float}
     */
    private function teamMissedFollowupStats(Collection $employees): array
    {
        $missed = 0;
        $due = 0;

        foreach ($employees as $employee) {
            $stats = $this->followupAccuracyStats((string) $employee->id)['overall'];
            $missed += (int) ($stats['missed'] ?? 0);
            $due += (int) ($stats['due'] ?? 0);
        }

        return [
            'missed' => $missed,
            'due' => $due,
            'accuracy_pct' => $due > 0 ? max(0, round(100 - (($missed / $due) * 100), 1)) : 100.0,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $employeeTotals
     * @param  array<string, mixed>  $teamTotals
     * @return list<array{key: string, icon: string, label: string, mine: int, all: int, pct: float}>
     */
    private function teamEmployeeShareRows(
        array $employeeTotals,
        array $teamTotals,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): array {
        $metricKeys = ['bookings_created', 'outbound_enquiries'];
        $teamActivity = 0;

        foreach ($metricKeys as $key) {
            $teamActivity += (int) ($teamTotals[$key] ?? 0);
        }

        $teamActivity += (int) Booking::query()
            ->where('booking_status', 'completed')
            ->whereNotNull('assignee_id')
            ->whereBetween('updated_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->count();

        $rows = [];

        foreach ($employeeTotals as $employeeRow) {
            $employeeId = (string) ($employeeRow['employee_id'] ?? '');
            if ($employeeId === '') {
                continue;
            }

            $employeeActivity = 0;
            foreach ($metricKeys as $key) {
                $employeeActivity += (int) ($employeeRow[$key] ?? 0);
            }
            $employeeActivity += $this->completedBookingsCount($employeeId, $periodStart, $periodEnd);

            $rows[] = [
                'key' => $employeeId,
                'icon' => 'person',
                'label' => (string) ($employeeRow['employee_name'] ?? $employeeId),
                'mine' => $employeeActivity,
                'all' => $teamActivity,
                'pct' => $teamActivity > 0 ? round(($employeeActivity / $teamActivity) * 100, 1) : 0.0,
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['mine'] <=> $a['mine']);

        return $rows;
    }

    /**
     * Keep full-team rank numbers, but return only the selected employee's row.
     *
     * @param  list<array{rank: int, employee_id: string, label: string, score: int, marks?: list<array<string, mixed>>}>  $rows
     * @return list<array{rank: int, employee_id: string, label: string, score: int, marks?: list<array<string, mixed>>}>
     */
    private function filterRankRowsForEmployee(array $rows, string $employeeId): array
    {
        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => (string) ($row['employee_id'] ?? '') === $employeeId,
        ));
    }

    /**
     * @param  Collection<int, User>  $employees
     * @return list<array{rank: int, employee_id: string, label: string, score: int, marks?: list<array<string, mixed>>}>
     */
    private function teamOverallRankRows(Collection $employees, Carbon $periodStart, Carbon $periodEnd): array
    {
        if ($employees->isEmpty()) {
            return [];
        }

        $teamReport = $this->dailyEmployeeReport->buildReport($employees, $periodStart, $periodEnd);
        $ranked = $this->progressScore->rankEmployees(
            $teamReport['employee_totals'] ?? [],
            $employees,
            $periodStart,
            $periodEnd,
        );

        return array_map(static function (array $row) {
            return [
                'rank' => (int) ($row['rank'] ?? 0),
                'employee_id' => (string) ($row['employee_id'] ?? ''),
                'label' => (string) ($row['name'] ?? ''),
                'score' => (int) ($row['score'] ?? 0),
                'marks' => $row['marks'] ?? [],
                'quantity_score' => (int) ($row['quantity_score'] ?? 0),
                'penalty_score' => (int) ($row['penalty_score'] ?? 0),
            ];
        }, $ranked);
    }

    /**
     * @param  array<string, mixed>  $periodTotals
     * @return list<array<string, mixed>>
     */
    private function buildQualityStatsForUser(
        string $userId,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $periodTotals,
    ): array {
        $outcomes = $this->bookingOutcomesForPeriod($userId, $periodStart, $periodEnd);
        $followupDiscipline = $this->followupDisciplineForPeriod($userId, $periodStart, $periodEnd);
        $qualityMetrics = $this->progressQualityMetricsForPeriod(
            $userId,
            $periodStart,
            $periodEnd,
            $periodTotals,
            $outcomes,
            $followupDiscipline,
        );

        return $this->buildProgressQualityStatItems($qualityMetrics, $followupDiscipline);
    }

    /**
     * @param  Collection<int, User>  $employees
     * @param  array<string, mixed>  $periodTotals
     * @return list<array<string, mixed>>
     */
    private function buildTeamQualityStatsForPeriod(
        Collection $employees,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $periodTotals,
    ): array {
        $leadsHandled = 0;
        $unknownCount = 0;
        $futureCustomerCount = 0;
        $missingDataCount = 0;
        $cancelledBookings = 0;
        $completedBookings = 0;
        $leadOnTime = 0;
        $leadMissed = 0;
        $bookingOnTime = 0;
        $bookingMissed = 0;
        $outboundsDone = (int) ($periodTotals['outbound_enquiries'] ?? 0);

        foreach ($employees as $employee) {
            $userId = (string) $employee->id;
            $outcomes = $this->bookingOutcomesForPeriod($userId, $periodStart, $periodEnd);
            $discipline = $this->followupDisciplineForPeriod($userId, $periodStart, $periodEnd);
            $quality = $this->progressQualityMetricsForPeriod(
                $userId,
                $periodStart,
                $periodEnd,
                [],
                $outcomes,
                $discipline,
            );

            $leadsHandled += (int) ($quality['leads_handled'] ?? 0);
            $unknownCount += (int) ($quality['unknown_count'] ?? 0);
            $futureCustomerCount += (int) ($quality['future_customer_count'] ?? 0);
            $missingDataCount += (int) ($quality['leads_missing_data'] ?? 0);
            $cancelledBookings += (int) ($quality['booking_cancelled_count'] ?? 0);
            $completedBookings += (int) ($quality['booking_completed_count'] ?? 0);
            $leadOnTime += (int) ($discipline['leads']['on_time'] ?? 0);
            $leadMissed += (int) ($discipline['leads']['missed'] ?? 0);
            $bookingOnTime += (int) ($discipline['bookings']['on_time'] ?? 0);
            $bookingMissed += (int) ($discipline['bookings']['missed'] ?? 0);
        }

        $followupDiscipline = [
            'leads' => $this->followupDisciplineBucket($leadOnTime, $leadMissed),
            'bookings' => $this->followupDisciplineBucket($bookingOnTime, $bookingMissed),
            'overall' => $this->followupDisciplineBucket($leadOnTime + $bookingOnTime, $leadMissed + $bookingMissed),
        ];

        $bookingOutcomesTotal = $cancelledBookings + $completedBookings;
        $qualityMetrics = [
            'followup_accuracy' => $followupDiscipline['overall']['accuracy_pct'],
            'lead_followup_accuracy' => $followupDiscipline['leads']['accuracy_pct'],
            'booking_followup_accuracy' => $followupDiscipline['bookings']['accuracy_pct'],
            'missed_followups' => $followupDiscipline['overall']['missed'],
            'missed_lead_followups' => $followupDiscipline['leads']['missed'],
            'missed_booking_followups' => $followupDiscipline['bookings']['missed'],
            'on_time_followups' => $followupDiscipline['overall']['on_time'],
            'on_time_lead_followups' => $followupDiscipline['leads']['on_time'],
            'on_time_booking_followups' => $followupDiscipline['bookings']['on_time'],
            'leads_handled' => $leadsHandled,
            'unknown_count' => $unknownCount,
            'unknown_pct' => $leadsHandled > 0 ? round(($unknownCount / $leadsHandled) * 100, 1) : 0.0,
            'future_customer_count' => $futureCustomerCount,
            'future_customer_pct' => $leadsHandled > 0 ? round(($futureCustomerCount / $leadsHandled) * 100, 1) : 0.0,
            'leads_missing_data' => $missingDataCount,
            'booking_cancelled_pct' => $bookingOutcomesTotal > 0
                ? round(($cancelledBookings / $bookingOutcomesTotal) * 100, 1)
                : 0.0,
            'booking_cancelled_count' => $cancelledBookings,
            'booking_completed_count' => $completedBookings,
            'outbounds_done' => $outboundsDone,
        ];

        return $this->buildProgressQualityStatItems($qualityMetrics, $followupDiscipline);
    }

    /**
     * @return array{
     *     overall: array{on_time: int, missed: int, due: int, accuracy_pct: float},
     *     leads: array{on_time: int, missed: int, due: int, accuracy_pct: float},
     *     bookings: array{on_time: int, missed: int, due: int, accuracy_pct: float}
     * }
     */
    private function followupDisciplineForPeriod(string $userId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();
        $asOf = Carbon::now()->lt($rangeEnd) ? Carbon::now() : $rangeEnd;

        $leadOnTime = 0;
        $leadMissed = 0;
        $leadCompleted = LeadFollowup::query()
            ->where('created_by', $userId)
            ->whereNotNull('followup_at')
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->get(['followup_at', 'due_followup_at']);

        foreach ($leadCompleted as $followup) {
            $due = $followup->due_followup_at;
            if (! $due) {
                continue;
            }

            if ($followup->followup_at->lte($due->copy()->endOfDay())) {
                $leadOnTime++;
            } else {
                $leadMissed++;
            }
        }

        $leadPendingMissedQuery = Lead::query()
            ->where('handled_by', $userId)
            ->whereNotNull('next_followup_at')
            ->whereBetween('next_followup_at', [$rangeStart, $rangeEnd])
            ->where('next_followup_at', '<', $asOf);
        $this->leadOpenStatus->restrictQueryToOpenLeads($leadPendingMissedQuery);
        $leadMissed += (int) $leadPendingMissedQuery->count();

        $bookingOnTime = 0;
        $bookingMissed = 0;
        $bookingCompleted = BookingFollowup::query()
            ->where('created_by', $userId)
            ->whereNotNull('followup_at')
            ->whereIn('status', ['completed', 'rescheduled'])
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->get(['followup_at', 'due_followup_at', 'date']);

        foreach ($bookingCompleted as $followup) {
            $due = $followup->due_followup_at ?? $followup->date;
            if (! $due) {
                continue;
            }

            $dueAt = $due instanceof Carbon ? $due : Carbon::parse($due);
            if ($followup->followup_at->lte($dueAt->copy()->endOfDay())) {
                $bookingOnTime++;
            } else {
                $bookingMissed++;
            }
        }

        $bookingMissed += (int) BookingFollowup::query()
            ->where('status', 'scheduled')
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->where('date', '<', $asOf)
            ->whereHas('booking', function ($q) use ($userId) {
                $q->where('assignee_id', $userId)
                    ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
            })
            ->count();

        return [
            'leads' => $this->followupDisciplineBucket($leadOnTime, $leadMissed),
            'bookings' => $this->followupDisciplineBucket($bookingOnTime, $bookingMissed),
            'overall' => $this->followupDisciplineBucket($leadOnTime + $bookingOnTime, $leadMissed + $bookingMissed),
        ];
    }

    /**
     * @return array{on_time: int, missed: int, due: int, accuracy_pct: float}
     */
    private function followupDisciplineBucket(int $onTime, int $missed): array
    {
        $due = $onTime + $missed;

        return [
            'on_time' => $onTime,
            'missed' => $missed,
            'due' => $due,
            'accuracy_pct' => $due > 0 ? max(0, round(($onTime / $due) * 100, 1)) : 100.0,
        ];
    }

    private function followupOnTimeMissedSub(array $bucket): string
    {
        $onTime = (int) ($bucket['on_time'] ?? 0);
        $missed = (int) ($bucket['missed'] ?? 0);
        $due = (int) ($bucket['due'] ?? ($onTime + $missed));

        if ($due === 0) {
            return translate('Progress_no_followups_due');
        }

        return str_replace(
            [':on_time', ':missed'],
            [(string) $onTime, (string) $missed],
            translate('Progress_on_time_vs_missed_sub'),
        );
    }

    /**
     * @param  array{on_time: int, missed: int, due: int, accuracy_pct: float}  $overall
     * @param  array{on_time: int, missed: int, due: int, accuracy_pct: float}  $leads
     * @param  array{on_time: int, missed: int, due: int, accuracy_pct: float}  $bookings
     */
    private function followupDisciplineBreakdownSub(array $overall, array $leads, array $bookings): ?string
    {
        if (($overall['due'] ?? 0) === 0) {
            return null;
        }

        return str_replace(
            [':lead_on_time', ':lead_missed', ':booking_on_time', ':booking_missed'],
            [
                (string) ($leads['on_time'] ?? 0),
                (string) ($leads['missed'] ?? 0),
                (string) ($bookings['on_time'] ?? 0),
                (string) ($bookings['missed'] ?? 0),
            ],
            translate('Progress_followup_lead_booking_sub'),
        );
    }

    private function followupDisciplineValue(array $bucket): string
    {
        $due = (int) ($bucket['due'] ?? 0);
        if ($due === 0) {
            return '—';
        }

        return ($bucket['accuracy_pct'] ?? 0).'%';
    }

    private function completedBookingsCount(string $userId, Carbon $start, Carbon $end): int
    {
        return (int) Booking::query()
            ->where('assignee_id', $userId)
            ->where('booking_status', 'completed')
            ->whereBetween('updated_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->count();
    }

    private function usesEmployeeDashboardScope(User $user): bool
    {
        return $user->user_type === 'admin-employee';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAttentionContext(string $userId, Carbon $today, bool $employeeScope): array
    {
        $employees = $employeeScope ? collect() : $this->dashboardEmployees();
        $employeeOptions = $this->formatDashboardEmployeeOptions($employees);
        $defaultEmployeeId = (string) ($employeeOptions[0]['id'] ?? '');
        $perEmployee = $employeeScope
            ? ['leads' => [], 'bookings' => [], 'tasks' => [], 'whatsapp_assigned_unread' => []]
            : $this->buildPerEmployeeAttentionData($today, $employees);

        return [
            'lead_yours' => $employeeScope
                ? $this->leadFollowupsPendingTillToday($today, $userId)
                : ['total' => 0, 'items' => collect()],
            'lead_all' => $this->leadFollowupsPendingTillToday($today, null),
            'booking_yours' => $employeeScope
                ? $this->bookingFollowupsPendingTillToday($today, $userId)
                : ['total' => 0, 'items' => collect()],
            'booking_all' => $this->bookingFollowupsPendingTillToday($today, null),
            'unassigned' => $this->unassignedCounts(),
            'unassigned_leads' => $this->unassignedLeadsList(),
            'unassigned_bookings' => $this->unassignedBookingsList(),
            'whatsapp_assigned_unread' => $this->whatsappAssignedUnreadThreadItems($employeeScope ? $userId : null),
            'whatsapp_unassigned' => $this->whatsappOpenThreadItems(null, unassignedOnly: true),
            'tasks_yours' => $employeeScope
                ? $this->pendingTasksList($today, $userId)
                : ['total' => 0, 'items' => collect()],
            'tasks_all' => $this->pendingTasksList($today, null),
            'employee_options' => $employeeOptions,
            'default_employee_id' => $defaultEmployeeId,
            'per_employee' => $perEmployee,
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private function dashboardEmployees(): Collection
    {
        return User::query()
            ->where('user_type', 'admin-employee')
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }

    /**
     * @param  Collection<int, User>  $employees
     * @return list<array{id: string, name: string}>
     */
    private function formatDashboardEmployeeOptions(Collection $employees): array
    {
        return $employees->map(function (User $employee) {
            $name = trim((string) $employee->first_name . ' ' . (string) $employee->last_name);

            return [
                'id' => (string) $employee->id,
                'name' => $name !== '' ? $name : (string) $employee->email,
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, User>  $employees
     * @return array{
     *     leads: array<string, array{total: int, items: mixed, view_all_url: string}>,
     *     bookings: array<string, array{total: int, items: mixed, view_all_url: string}>,
     *     tasks: array<string, array{total: int, items: mixed, view_all_url: string}>,
     *     whatsapp_assigned_unread: array<string, array{total: int, items: mixed, view_all_url: string}>
     * }
     */
    private function buildPerEmployeeAttentionData(Carbon $today, Collection $employees): array
    {
        $leads = [];
        $bookings = [];
        $tasks = [];
        $whatsappAssignedUnread = [];

        foreach ($employees as $employee) {
            $employeeId = (string) $employee->id;
            $leads[$employeeId] = array_merge(
                $this->leadFollowupsPendingTillToday($today, $employeeId),
                ['view_all_url' => route('admin.lead.todays_followups', ['handled_by' => $employeeId])],
            );
            $bookings[$employeeId] = array_merge(
                $this->bookingFollowupsPendingTillToday($today, $employeeId),
                ['view_all_url' => route('admin.booking.todays_followups', ['assignee_id' => $employeeId])],
            );
            $tasks[$employeeId] = array_merge(
                $this->pendingTasksList($today, $employeeId),
                ['view_all_url' => route('admin.task-board.index')],
            );
            $whatsappAssignedUnread[$employeeId] = array_merge(
                $this->whatsappAssignedUnreadThreadItems($employeeId),
                ['view_all_url' => route('admin.whatsapp.conversations.index', [
                    'channel' => 'whatsapp',
                    'tab' => 'chats',
                    'handlers' => [$employeeId],
                    'unread_state' => ['unread'],
                ])],
            );
        }

        return [
            'leads' => $leads,
            'bookings' => $bookings,
            'tasks' => $tasks,
            'whatsapp_assigned_unread' => $whatsappAssignedUnread,
        ];
    }

    /**
     * @param  list<array{id: string, name: string}>  $employeeOptions
     * @param  array<string, array{total: int, items: mixed, view_all_url?: string}>  $datasetsByEmployeeId
     * @return array<string, mixed>
     */
    private function buildEmployeeTab(array $employeeOptions, array $datasetsByEmployeeId, string $defaultEmployeeId): array
    {
        $defaultDataset = $datasetsByEmployeeId[$defaultEmployeeId] ?? ['total' => 0, 'items' => collect()];

        return [
            'label' => translate('Employee'),
            'employee_select' => true,
            'employees' => $employeeOptions,
            'default_employee_id' => $defaultEmployeeId,
            'datasets' => $datasetsByEmployeeId,
            'total' => (int) ($defaultDataset['total'] ?? 0),
            'items' => $defaultDataset['items'] ?? collect(),
        ];
    }

    /**
     * @param  array<string, int>  $employeeCounts
     * @param  list<array{id: string, name: string}>  $employeeOptions
     * @param  array<string, string>  $employeeUrls
     * @return array<string, mixed>
     */
    private function buildAdminSplitWidget(
        string $key,
        string $title,
        string $icon,
        string $tone,
        array $employeeCounts,
        int $allCount,
        string $urlAll,
        string $scrollTo,
        array $employeeOptions,
        string $defaultEmployeeId,
        array $employeeUrls,
        bool $compact = false,
    ): array {
        return [
            'key' => $key,
            'type' => 'split',
            'title' => $title,
            'icon' => $icon,
            'tone' => $tone,
            'compact' => $compact,
            'chip_first_key' => 'employee',
            'chip_second_key' => 'all',
            'chip_first_label' => translate('Employee'),
            'chip_second_label' => translate('All'),
            'employee_select' => true,
            'employees' => $employeeOptions,
            'default_employee_id' => $defaultEmployeeId,
            'employee_counts' => $employeeCounts,
            'yours' => (int) ($employeeCounts[$defaultEmployeeId] ?? 0),
            'all' => $allCount,
            'url_yours' => $employeeUrls[$defaultEmployeeId] ?? '#',
            'url_all' => $urlAll,
            'employee_urls' => $employeeUrls,
            'scroll_to' => $scrollTo,
        ];
    }

    /**
     * @param  array<string, int>  $employeeCounts
     * @param  array<string, string>  $employeeUrls
     */
    private function mapEmployeeMetricArrays(array $datasets, string $countKey = 'total', string $urlKey = 'view_all_url'): array
    {
        $counts = [];
        $urls = [];

        foreach ($datasets as $employeeId => $dataset) {
            $counts[(string) $employeeId] = (int) ($dataset[$countKey] ?? 0);
            $urls[(string) $employeeId] = (string) ($dataset[$urlKey] ?? '#');
        }

        return [$counts, $urls];
    }

    /**
     * @param  array<string, array<string, mixed>>  $workQueue
     * @return array{line: string, all_clear: bool}
     */
    private function focusLineFromWorkQueue(array $workQueue): array
    {
        $pendingLabels = [
            'pending_lead_followup' => translate('Employee_focus_lead_followups'),
            'pending_booking_followup' => translate('Employee_focus_booking_followups'),
            'pending_tasks' => translate('Employee_focus_pending_tasks'),
        ];
        $pickupLabels = [
            'unassigned_leads' => translate('Employee_focus_unassigned_leads'),
            'unassigned_bookings' => translate('Employee_focus_unassigned_bookings'),
            'whatsapp_unassigned' => translate('Employee_focus_unassigned_whatsapp'),
            'whatsapp_assigned_unread' => translate('Employee_focus_assigned_whatsapp_unread'),
        ];

        $pendingParts = [];
        $pickupParts = [];
        $pendingTotal = 0;
        $pickupTotal = 0;

        foreach ($workQueue['pending']['widgets'] ?? [] as $widget) {
            $key = (string) ($widget['key'] ?? '');
            $count = (int) ($widget['total'] ?? 0);

            if ($count <= 0 || ! isset($pendingLabels[$key])) {
                continue;
            }

            $pendingTotal += $count;
            $pendingParts[] = $count . ' ' . $pendingLabels[$key];
        }

        foreach ($workQueue['pickup']['widgets'] ?? [] as $widget) {
            $key = (string) ($widget['key'] ?? '');
            $count = (int) ($widget['total'] ?? 0);

            if ($count <= 0 || ! isset($pickupLabels[$key])) {
                continue;
            }

            $pickupTotal += $count;
            $pickupParts[] = $count . ' ' . $pickupLabels[$key];
        }

        if ($pendingTotal === 0 && $pickupTotal === 0) {
            return [
                'line' => translate('Employee_all_caught_up'),
                'all_clear' => true,
            ];
        }

        $lines = [];

        if ($pendingTotal > 0) {
            $lines[] = str_replace(
                [':count', ':items'],
                [(string) $pendingTotal, $this->formatFocusItemList($pendingParts)],
                translate('Employee_focus_pending_work'),
            );
        }

        if ($pickupTotal > 0) {
            $lines[] = str_replace(
                [':count', ':items'],
                [(string) $pickupTotal, $this->formatFocusItemList($pickupParts)],
                translate('Employee_focus_new_to_pick'),
            );
        }

        return [
            'line' => implode(' ', $lines),
            'all_clear' => false,
        ];
    }

    /**
     * @param  list<string>  $parts
     */
    private function formatFocusItemList(array $parts): string
    {
        $parts = array_values(array_filter($parts));

        if ($parts === []) {
            return '';
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        if (count($parts) === 2) {
            return $parts[0] . ' and ' . $parts[1];
        }

        $last = array_pop($parts);

        return implode(', ', $parts) . ', and ' . $last;
    }

    private function whatsappOpenCount(?string $handledByUserId, bool $unassignedOnly = false): int
    {
        if (! Schema::hasTable('whatsapp_users')) {
            return 0;
        }

        $query = WhatsAppUser::query();
        if ($unassignedOnly) {
            $query->where(function ($q) {
                $q->whereNull('handled_by')
                    ->orWhere('handled_by', '')
                    ->orWhere('handled_by', Lead::HANDLED_BY_AI);
            });
        } elseif ($handledByUserId !== null) {
            $query->where('handled_by', $handledByUserId);
        }

        $assigned = $query->get(['phone']);
        if ($assigned->isEmpty()) {
            return 0;
        }

        $closedStatusIds = [];
        if (Schema::hasTable('whatsapp_chat_statuses')) {
            $closedStatusIds = WhatsAppChatStatus::query()
                ->where('bucket', 'closed')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($closedStatusIds === [] || ! Schema::hasTable('whatsapp_chat_thread_meta')) {
            return $assigned->count();
        }

        $phones = $assigned->pluck('phone')->filter()->values()->all();
        $closedCount = WhatsAppChatThreadMeta::query()
            ->whereIn('phone', $phones)
            ->whereIn('whatsapp_chat_status_id', $closedStatusIds)
            ->count();

        return max(0, $assigned->count() - $closedCount);
    }

    /**
     * @return array{leads: int, bookings: int}
     */
    private function unassignedCounts(): array
    {
        $unassignedLeads = Lead::query()->where(function ($w) {
            $w->whereNull('handled_by')
                ->orWhere('handled_by', '')
                ->orWhere('handled_by', Lead::HANDLED_BY_AI);
        });
        $this->leadOpenStatus->restrictQueryToOpenLeads($unassignedLeads);

        return [
            'leads' => $unassignedLeads->count(),
            'bookings' => Booking::query()
                ->whereNull('assignee_id')
                ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS)
                ->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $todayTotals
     * @return array{total: int, items: list<array<string, mixed>>}
     */
    private function formatTodayDone(array $todayTotals, string $userId): array
    {
        $today = Carbon::today();
        $items = $this->buildProgressStatItems($todayTotals, $userId, $today, $today);

        return [
            'total' => $this->progressStatActivityTotal($items),
            'items' => $items,
        ];
    }

    /**
     * @return list<array{key: string, icon: string, label: string, tone: string, source: string, include_in_total: bool}>
     */
    private function progressStatMetricDefinitions(): array
    {
        return [
            ['key' => 'leads_added', 'icon' => 'person_add', 'label' => translate('Leads_added') ?? translate('Leads'), 'tone' => 'lead', 'source' => 'report', 'include_in_total' => true],
            ['key' => 'lead_followups', 'icon' => 'event_repeat', 'label' => translate('Lead_followups') ?? translate('Lead_Followups'), 'tone' => 'lead', 'source' => 'report', 'include_in_total' => true],
            ['key' => 'booking_followups', 'icon' => 'event_available', 'label' => translate('Booking_followups') ?? translate('Booking_Followups'), 'tone' => 'booking', 'source' => 'report', 'include_in_total' => true],
            ['key' => 'bookings_created', 'icon' => 'add_shopping_cart', 'label' => translate('Bookings_created'), 'tone' => 'brand', 'source' => 'report', 'include_in_total' => true],
            ['key' => 'pending', 'icon' => 'hourglass_top', 'label' => translate('Booking_pending') ?? 'Booking pending', 'tone' => 'warn', 'source' => 'booking_status', 'include_in_total' => false],
            ['key' => 'accepted', 'icon' => 'thumb_up', 'label' => translate('Booking_accepted') ?? 'Booking accepted', 'tone' => 'brand', 'source' => 'booking_status', 'include_in_total' => false],
            ['key' => 'ongoing', 'icon' => 'play_circle', 'label' => translate('Booking_ongoing') ?? 'Booking ongoing', 'tone' => 'brand', 'source' => 'booking_status', 'include_in_total' => false],
            ['key' => 'on_hold', 'icon' => 'pause_circle', 'label' => translate('Booking_on_hold') ?? 'Booking on hold', 'tone' => 'warn', 'source' => 'booking_status', 'include_in_total' => false],
            ['key' => 'hold_after_visit', 'icon' => 'home_work', 'label' => translate('Booking_hold_after_visit') ?? 'Booking hold after visit', 'tone' => 'warn', 'source' => 'booking_status', 'include_in_total' => false],
            ['key' => 'completed_bookings', 'icon' => 'check_circle', 'label' => translate('Bookings_completed'), 'tone' => 'good', 'source' => 'completed_bookings', 'include_in_total' => true],
            ['key' => 'completed_amount', 'icon' => 'payments', 'label' => translate('Completed_amount'), 'tone' => 'brand', 'source' => 'completed_amount', 'include_in_total' => false],
            ['key' => 'cancelled_bookings', 'icon' => 'cancel', 'label' => translate('Cancelled_bookings'), 'tone' => 'warn', 'source' => 'cancelled_bookings', 'include_in_total' => true],
            ['key' => 'outbound_enquiries', 'icon' => 'call_made', 'label' => translate('Outbound_enquiries'), 'tone' => 'outbound', 'source' => 'report', 'include_in_total' => true],
            ['key' => 'whatsapp_chats_replied', 'icon' => 'forum', 'label' => translate('WhatsApp_replies'), 'tone' => 'whatsapp', 'source' => 'report', 'include_in_total' => true],
            ['key' => 'whatsapp_chats_closed', 'icon' => 'mark_chat_read', 'label' => translate('WhatsApp_chats_closed'), 'tone' => 'whatsapp-closed', 'source' => 'report', 'include_in_total' => true],
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function progressStatUrls(string $userId): array
    {
        return [
            'lead_followups' => route('admin.lead.todays_followups', ['handled_by' => $userId]),
            'booking_followups' => route('admin.booking.todays_followups', ['assignee_id' => $userId]),
            'leads_added' => route('admin.lead.index', ['handled_by' => [$userId]]),
            'bookings_created' => route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'outbound_enquiries' => route('admin.lead.outbound-enquiry.index'),
            'whatsapp_chats_replied' => route('admin.whatsapp.conversations.index', ['channel' => 'whatsapp', 'tab' => 'chats', 'handlers' => [$userId]]),
            'whatsapp_chats_closed' => route('admin.whatsapp.conversations.index', ['channel' => 'whatsapp', 'tab' => 'chats', 'handlers' => [$userId]]),
            'booking_status_updates' => route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'completed_bookings' => route('admin.booking.list', ['booking_status' => 'completed', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'completed_amount' => route('admin.booking.list', ['booking_status' => 'completed', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'cancelled_bookings' => route('admin.booking.list', ['booking_status' => 'canceled', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'pending' => route('admin.booking.list', ['booking_status' => 'pending', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'accepted' => route('admin.booking.list', ['booking_status' => 'accepted', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'ongoing' => route('admin.booking.list', ['booking_status' => 'ongoing', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'on_hold' => route('admin.booking.list', ['booking_status' => 'on_hold', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'hold_after_visit' => route('admin.booking.list', ['booking_status' => 'hold_after_visit', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'cancelled_after_visit' => route('admin.booking.list', ['booking_status' => 'cancelled_after_visit', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'disputed_cancelled' => route('admin.booking.list', ['booking_status' => 'disputed_cancelled', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'disputed_completed' => route('admin.booking.list', ['booking_status' => 'disputed_completed', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'loss_making' => route('admin.booking.list', ['booking_status' => 'loss_making_pending', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'loss_recovered' => route('admin.booking.list', ['booking_status' => 'loss_recovered', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
            'loss_settled' => route('admin.booking.list', ['booking_status' => 'loss_settled', 'service_type' => 'all', 'assignee_ids' => [$userId]]),
        ];
    }

    /**
     * @return array{completed_bookings: int, completed_amount: float, cancelled_bookings: int}
     */
    private function bookingOutcomesForPeriod(string $userId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        $completed = Booking::query()
            ->where('assignee_id', $userId)
            ->where('booking_status', 'completed')
            ->whereBetween('updated_at', [$rangeStart, $rangeEnd]);

        $cancelled = Booking::query()
            ->where('assignee_id', $userId)
            ->where('booking_status', 'canceled')
            ->whereBetween('updated_at', [$rangeStart, $rangeEnd]);

        return [
            'completed_bookings' => (int) (clone $completed)->count(),
            'completed_amount' => round((float) (clone $completed)->sum('total_booking_amount'), 2),
            'cancelled_bookings' => (int) $cancelled->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $periodTotals
     * @return list<array<string, mixed>>
     */
    private function buildProgressStatItems(array $periodTotals, string $userId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $outcomes = $this->bookingOutcomesForPeriod($userId, $periodStart, $periodEnd);
        $statusTotals = $this->bookingStatusTotalsForEmployees([$userId], $periodStart, $periodEnd, $periodTotals, $outcomes);
        $urls = $this->progressStatUrls($userId);
        $items = [];

        foreach ($this->progressStatMetricDefinitions() as $definition) {
            $key = $definition['key'];
            $source = $definition['source'];
            $raw = match ($source) {
                'report' => (int) ($periodTotals[$key] ?? 0),
                'completed_amount' => (float) ($outcomes[$source] ?? 0),
                'booking_status' => (int) ($statusTotals[$key] ?? 0),
                default => (int) ($outcomes[$source] ?? 0),
            };

            if ($key === 'bookings_created') {
                $created = $this->progressScore->bookingsCreatedByEmployee([$userId], $periodStart, $periodEnd);
                $raw = (int) ($created[$userId] ?? 0);
            }
            if ($key === 'completed_bookings' && (int) ($statusTotals['completed'] ?? 0) > 0) {
                $raw = (int) $statusTotals['completed'];
            } elseif ($key === 'completed_bookings') {
                $completed = $this->progressScore->bookingsCompletedByEmployee([$userId], $periodStart, $periodEnd);
                $raw = (int) ($completed[$userId] ?? 0);
            }
            if ($key === 'cancelled_bookings') {
                $raw = (int) ($statusTotals['canceled'] ?? 0) + (int) ($statusTotals['cancelled_after_visit'] ?? 0);
                if ($raw <= 0) {
                    $raw = (int) ($outcomes['cancelled_bookings'] ?? 0);
                }
            }

            $value = $source === 'completed_amount'
                ? with_currency_symbol($raw)
                : (string) (int) $raw;

            $isZero = $source === 'completed_amount' ? $raw <= 0 : $raw <= 0;
            // Always show core booking-status quantity tiles (even at 0).
            if ($source === 'booking_status' && $isZero && ! in_array($key, ['pending', 'accepted', 'ongoing', 'on_hold', 'hold_after_visit'], true)) {
                continue;
            }

            $tone = in_array($key, ['cancelled_bookings', 'cancelled_after_visit', 'disputed_cancelled', 'disputed_completed', 'loss_making'], true)
                ? ($raw > 0 ? 'warn' : 'neutral')
                : $definition['tone'];

            $items[] = [
                'key' => $key,
                'icon' => $definition['icon'],
                'label' => $definition['label'],
                'value' => $value,
                'raw' => $raw,
                'count' => (int) $raw,
                'is_zero' => $isZero,
                'tone' => $tone,
                'url' => $urls[$key] ?? null,
                'include_in_total' => $definition['include_in_total'],
            ];
        }

        return $items;
    }

    /**
     * @param  list<string>  $employeeIds
     * @param  array<string, mixed>  $periodTotals
     * @param  array{completed_bookings: int, completed_amount: float, cancelled_bookings: int}  $outcomes
     * @return array<string, int>
     */
    private function bookingStatusTotalsForEmployees(
        array $employeeIds,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $periodTotals,
        array $outcomes,
    ): array {
        $payload = $this->bookingStatusAnalytics->build(
            $employeeIds,
            $periodStart,
            $periodEnd,
            [],
            (int) ($periodTotals['bookings_created'] ?? 0),
            (int) ($outcomes['completed_bookings'] ?? 0),
            (int) ($outcomes['cancelled_bookings'] ?? 0),
            (float) ($outcomes['completed_amount'] ?? 0),
            0.0,
            0,
        );

        return $payload['totals'] ?? [];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function progressStatActivityTotal(array $items): int
    {
        $total = 0;

        foreach ($items as $item) {
            if (! empty($item['include_in_total'])) {
                $total += (int) ($item['raw'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * @return array<string, mixed>
     */
    public function monthlyPerformanceForUser(User $user, Carbon $monthStart, Carbon $monthEnd): array
    {
        $monthReport = $this->dailyEmployeeReport->buildReport(collect([$user]), $monthStart, $monthEnd);
        $monthTotals = $monthReport['employee_totals'][0] ?? [];

        return $this->monthlyPerformance((string) $user->id, $monthStart, $monthEnd, $monthTotals);
    }

    /**
     * Full progress report: pipeline, missed follow-ups, team comparison, scorecard, improvements.
     *
     * @param  array<string, mixed>  $periodTotals
     * @return array<string, mixed>
     */
    public function progressFullReportForUser(
        User $user,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $periodTotals = [],
    ): array {
        $userId = (string) $user->id;
        $today = Carbon::today();
        $outcomes = $this->bookingOutcomesForPeriod($userId, $periodStart, $periodEnd);
        $followupDiscipline = $this->followupDisciplineForPeriod($userId, $periodStart, $periodEnd);
        $disciplinePct = $followupDiscipline['overall']['accuracy_pct'];
        $qualityMetrics = $this->progressQualityMetricsForPeriod(
            $userId,
            $periodStart,
            $periodEnd,
            $periodTotals,
            $outcomes,
            $followupDiscipline,
        );
        $contribution = $this->contributionVsAllForPeriod(
            $userId,
            $periodStart,
            $periodEnd,
            $periodTotals,
            $outcomes['completed_bookings'],
        );
        $leaderboard = $this->teamLeaderboardForPeriod($userId, $periodStart, $periodEnd);
        $missedFollowups = [
            'leads' => $this->missedLeadFollowupsList($userId),
            'bookings' => $this->missedBookingFollowupsList($userId),
            'total' => 0,
        ];
        $missedFollowups['total'] = $missedFollowups['leads']['total'] + $missedFollowups['bookings']['total'];
        $pendingFollowups = [
            'leads' => $this->dueTodayLeadFollowupsList($userId, $today),
            'bookings' => $this->dueTodayBookingFollowupsList($userId, $today),
            'total' => 0,
        ];
        $pendingFollowups['total'] = $pendingFollowups['leads']['total'] + $pendingFollowups['bookings']['total'];
        $pipeline = [
            'leads' => $this->openLeadsPipeline($userId),
            'bookings' => $this->activeBookingsPipeline($userId),
        ];
        $scorecard = $this->buildProgressScorecard(
            $userId,
            $periodTotals,
            $outcomes,
            $disciplinePct,
            $followupDiscipline['overall'],
            $contribution,
            $leaderboard,
            $qualityMetrics,
        );
        $improvements = $this->buildProgressImprovements(
            $scorecard,
            $missedFollowups,
            $pendingFollowups,
            $contribution,
            $leaderboard,
            $disciplinePct,
            $outcomes,
        );

        return [
            'contribution' => $contribution,
            'leaderboard' => $leaderboard,
            'scorecard' => $scorecard,
            'improvements' => $improvements,
            'missed_followups' => $missedFollowups,
            'pending_followups' => $pendingFollowups,
            'pipeline' => $pipeline,
            'discipline_pct' => $disciplinePct,
            'missed_stats' => $followupDiscipline['overall'],
            'followup_accuracy' => $followupDiscipline,
            'quality_metrics' => $qualityMetrics,
            'quality_stats' => $this->buildProgressQualityStatItems($qualityMetrics, $followupDiscipline),
            'outcomes' => $outcomes,
        ];
    }

    /**
     * @param  array<string, mixed>  $monthTotals
     * @return array<string, mixed>
     */
    private function monthlyPerformance(string $userId, Carbon $monthStart, Carbon $monthEnd, array $monthTotals): array
    {
        $outcomes = $this->bookingOutcomesForPeriod($userId, $monthStart, $monthEnd);
        $followupDiscipline = $this->followupDisciplineForPeriod($userId, $monthStart, $monthEnd);
        $disciplinePct = $followupDiscipline['overall']['accuracy_pct'];
        $qualityMetrics = $this->progressQualityMetricsForPeriod(
            $userId,
            $monthStart,
            $monthEnd,
            $monthTotals,
            $outcomes,
            $followupDiscipline,
        );
        $stats = $this->buildProgressStatItems($monthTotals, $userId, $monthStart, $monthEnd);
        $qualityStats = $this->buildProgressQualityStatItems($qualityMetrics, $followupDiscipline);

        return [
            'period_label' => Carbon::now()->format('F Y'),
            'completed_bookings' => $outcomes['completed_bookings'],
            'completed_amount' => $outcomes['completed_amount'],
            'cancelled_bookings' => $outcomes['cancelled_bookings'],
            'lead_followups' => (int) ($monthTotals['lead_followups'] ?? 0),
            'booking_followups' => (int) ($monthTotals['booking_followups'] ?? 0),
            'outbounds_done' => (int) ($monthTotals['outbound_enquiries'] ?? 0),
            'followup_discipline_pct' => $disciplinePct,
            'missed_followups' => $followupDiscipline['overall']['missed'],
            'stats' => $stats,
            'quality_stats' => $qualityStats,
            'quality_metrics' => $qualityMetrics,
            'followup_accuracy' => $followupDiscipline,
            'discipline_stat' => [
                'key' => 'followup_accuracy',
                'icon' => 'verified',
                'label' => translate('Follow_up_accuracy'),
                'value' => $disciplinePct . '%',
                'is_zero' => false,
                'tone' => $disciplinePct >= 90 ? 'good' : ($disciplinePct >= 70 ? 'brand' : 'warn'),
                'sub' => $this->followupOnTimeMissedSub($followupDiscipline['overall']),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $userTotals
     * @return list<array{key: string, label: string, mine: int, all: int, pct: float}>
     */
    private function contributionVsAllForPeriod(
        string $userId,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $userTotals,
        int $completedBookingsMine,
    ): array {
        $teamEmployees = User::query()
            ->where('user_type', 'admin-employee')
            ->get();

        $teamReport = $this->dailyEmployeeReport->buildReport($teamEmployees, $periodStart, $periodEnd);
        $teamActivity = $teamReport['totals'];

        $teamCompletedBookings = (int) Booking::query()
            ->where('booking_status', 'completed')
            ->whereNotNull('assignee_id')
            ->whereBetween('updated_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->count();

        $rows = [
            [
                'key' => 'bookings_completed',
                'icon' => 'check_circle',
                'label' => translate('Bookings_completed'),
                'mine' => $completedBookingsMine,
                'all' => $teamCompletedBookings,
            ],
            [
                'key' => 'outbounds_done',
                'icon' => 'call_made',
                'label' => translate('Outbounds_done'),
                'mine' => (int) ($userTotals['outbound_enquiries'] ?? 0),
                'all' => (int) ($teamActivity['outbound_enquiries'] ?? 0),
            ],
            [
                'key' => 'bookings_created',
                'icon' => 'add_shopping_cart',
                'label' => translate('Bookings_created'),
                'mine' => (int) ($userTotals['bookings_created'] ?? 0),
                'all' => (int) ($teamActivity['bookings_created'] ?? 0),
            ],
        ];

        return array_map(function (array $row) {
            $row['pct'] = $row['all'] > 0 ? round(($row['mine'] / $row['all']) * 100, 1) : 0.0;

            return $row;
        }, $rows);
    }

    /**
     * @return array{
     *     overall: array{due: int, missed: int, accuracy_pct: float},
     *     leads: array{due: int, missed: int, accuracy_pct: float},
     *     bookings: array{due: int, missed: int, accuracy_pct: float}
     * }
     */
    private function followupAccuracyStats(string $userId): array
    {
        $today = Carbon::today();

        $bookingDueQuery = BookingFollowup::query()
            ->where('status', 'scheduled')
            ->whereDate('date', '<=', $today)
            ->whereHas('booking', function ($q) use ($userId) {
                $q->where('assignee_id', $userId)
                    ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
            });

        $bookingMissed = (clone $bookingDueQuery)->whereDate('date', '<', $today)->count();
        $bookingDue = (clone $bookingDueQuery)->count();

        $leadDueQuery = Lead::query()
            ->where('handled_by', $userId)
            ->whereNotNull('next_followup_at')
            ->whereDate('next_followup_at', '<=', $today);
        $this->leadOpenStatus->restrictQueryToOpenLeads($leadDueQuery);

        $leadMissed = (clone $leadDueQuery)->whereDate('next_followup_at', '<', $today)->count();
        $leadDue = (clone $leadDueQuery)->count();

        return [
            'leads' => $this->followupAccuracyBucket($leadDue, $leadMissed),
            'bookings' => $this->followupAccuracyBucket($bookingDue, $bookingMissed),
            'overall' => $this->followupAccuracyBucket($leadDue + $bookingDue, $leadMissed + $bookingMissed),
        ];
    }

    /**
     * @return array{due: int, missed: int, accuracy_pct: float}
     */
    private function followupAccuracyBucket(int $due, int $missed): array
    {
        return [
            'due' => $due,
            'missed' => $missed,
            'accuracy_pct' => $due > 0 ? max(0, round(100 - (($missed / $due) * 100), 1)) : 100.0,
        ];
    }

    /**
     * @param  array<string, mixed>  $periodTotals
     * @param  array{completed_bookings: int, completed_amount: float, cancelled_bookings: int}  $outcomes
     * @param  array{
     *     overall: array{due: int, missed: int, accuracy_pct: float},
     *     leads: array{due: int, missed: int, accuracy_pct: float},
     *     bookings: array{due: int, missed: int, accuracy_pct: float}
     * }  $followupAccuracy
     * @return array<string, mixed>
     */
    private function progressQualityMetricsForPeriod(
        string $userId,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $periodTotals,
        array $outcomes,
        array $followupAccuracy,
    ): array {
        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        $handledLeadsQuery = Lead::query()
            ->where('handled_by', $userId)
            ->whereBetween('date_time_of_lead_received', [$rangeStart, $rangeEnd]);

        $leadsHandled = (int) (clone $handledLeadsQuery)->count();
        $unknownCount = (int) (clone $handledLeadsQuery)->where('lead_type', Lead::TYPE_UNKNOWN)->count();
        $futureCustomerCount = (int) (clone $handledLeadsQuery)->where('lead_type', Lead::TYPE_FUTURE_CUSTOMER)->count();
        $missingDataCount = $this->countLeadsWithMissingDataForPeriod($userId, $rangeStart, $rangeEnd);

        $completedBookings = (int) ($outcomes['completed_bookings'] ?? 0);
        $cancelledBookings = (int) ($outcomes['cancelled_bookings'] ?? 0);
        $bookingOutcomesTotal = $completedBookings + $cancelledBookings;

        return [
            'followup_accuracy' => $followupAccuracy['overall']['accuracy_pct'],
            'lead_followup_accuracy' => $followupAccuracy['leads']['accuracy_pct'],
            'booking_followup_accuracy' => $followupAccuracy['bookings']['accuracy_pct'],
            'missed_followups' => $followupAccuracy['overall']['missed'],
            'missed_lead_followups' => $followupAccuracy['leads']['missed'],
            'missed_booking_followups' => $followupAccuracy['bookings']['missed'],
            'on_time_followups' => $followupAccuracy['overall']['on_time'] ?? 0,
            'on_time_lead_followups' => $followupAccuracy['leads']['on_time'] ?? 0,
            'on_time_booking_followups' => $followupAccuracy['bookings']['on_time'] ?? 0,
            'leads_handled' => $leadsHandled,
            'unknown_count' => $unknownCount,
            'unknown_pct' => $leadsHandled > 0 ? round(($unknownCount / $leadsHandled) * 100, 1) : 0.0,
            'future_customer_count' => $futureCustomerCount,
            'future_customer_pct' => $leadsHandled > 0 ? round(($futureCustomerCount / $leadsHandled) * 100, 1) : 0.0,
            'leads_missing_data' => $missingDataCount,
            'booking_cancelled_pct' => $bookingOutcomesTotal > 0
                ? round(($cancelledBookings / $bookingOutcomesTotal) * 100, 1)
                : 0.0,
            'booking_cancelled_count' => $cancelledBookings,
            'booking_completed_count' => $completedBookings,
            'outbounds_done' => (int) ($periodTotals['outbound_enquiries'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $qualityMetrics
     * @param  array{
     *     overall: array{on_time: int, missed: int, due: int, accuracy_pct: float},
     *     leads: array{on_time: int, missed: int, due: int, accuracy_pct: float},
     *     bookings: array{on_time: int, missed: int, due: int, accuracy_pct: float}
     * }  $followupAccuracy
     * @return list<array<string, mixed>>
     */
    private function buildProgressQualityStatItems(array $qualityMetrics, array $followupAccuracy): array
    {
        $pctTone = fn (float $pct): string => $pct >= 90 ? 'good' : ($pct >= 70 ? 'brand' : 'warn');
        $countTone = fn (int $count, string $good = 'good', string $bad = 'warn'): string => $count > 0 ? $bad : $good;

        $overallAcc = (float) ($followupAccuracy['overall']['accuracy_pct'] ?? $qualityMetrics['followup_accuracy'] ?? 0);
        $leadAcc = (float) ($followupAccuracy['leads']['accuracy_pct'] ?? $qualityMetrics['lead_followup_accuracy'] ?? 0);
        $bookingAcc = (float) ($followupAccuracy['bookings']['accuracy_pct'] ?? $qualityMetrics['booking_followup_accuracy'] ?? 0);
        $completed = (int) ($qualityMetrics['booking_completed_count'] ?? 0);
        $cancelled = (int) ($qualityMetrics['booking_cancelled_count'] ?? 0);
        $outcomesTotal = $completed + $cancelled;
        $completionRate = $outcomesTotal > 0
            ? round(($completed / $outcomesTotal) * 100, 1)
            : 0.0;

        return [
            [
                'key' => 'followup_accuracy',
                'icon' => 'verified',
                'label' => translate('Follow_up_accuracy'),
                'value' => $overallAcc.'%',
                'raw' => $overallAcc,
                'pct' => $overallAcc,
                'is_zero' => ($followupAccuracy['overall']['due'] ?? 0) === 0,
                'tone' => $pctTone($overallAcc),
                'sub' => ($followupAccuracy['overall']['due'] ?? 0) > 0
                    ? str_replace(
                        [':on_time', ':due'],
                        [(string) ($followupAccuracy['overall']['on_time'] ?? 0), (string) ($followupAccuracy['overall']['due'] ?? 0)],
                        translate('Progress_on_time_of_due_sub') ?? ':on_time of :due due',
                    )
                    : (translate('Progress_no_followups_due') ?? 'No follow-ups due'),
            ],
            [
                'key' => 'followup_on_time',
                'icon' => 'check_circle',
                'label' => translate('Progress_followups_on_time'),
                'value' => (string) ($followupAccuracy['overall']['on_time'] ?? 0),
                'raw' => (int) ($followupAccuracy['overall']['on_time'] ?? 0),
                'is_zero' => ($followupAccuracy['overall']['on_time'] ?? 0) === 0
                    && ($followupAccuracy['overall']['due'] ?? 0) > 0,
                'tone' => ($followupAccuracy['overall']['on_time'] ?? 0) > 0 ? 'good' : 'neutral',
                'sub' => $this->followupDisciplineBreakdownSub(
                    $followupAccuracy['overall'],
                    $followupAccuracy['leads'],
                    $followupAccuracy['bookings'],
                ),
            ],
            [
                'key' => 'followup_missed',
                'icon' => 'event_busy',
                'label' => translate('Progress_followups_missed'),
                'value' => (string) ($followupAccuracy['overall']['missed'] ?? 0),
                'raw' => (int) ($followupAccuracy['overall']['missed'] ?? 0),
                'is_zero' => ($followupAccuracy['overall']['missed'] ?? 0) === 0,
                'tone' => ($followupAccuracy['overall']['missed'] ?? 0) > 0 ? 'warn' : 'good',
                'sub' => ($followupAccuracy['overall']['due'] ?? 0) > 0
                    ? $this->followupDisciplineValue($followupAccuracy['overall']).' '.translate('Progress_followup_accuracy_short')
                    : translate('Progress_no_followups_due'),
            ],
            [
                'key' => 'lead_followup_accuracy',
                'icon' => 'fact_check',
                'label' => translate('Lead_followup_accuracy') ?? (translate('Lead').' '.translate('Follow_up_accuracy')),
                'value' => $leadAcc.'%',
                'raw' => $leadAcc,
                'pct' => $leadAcc,
                'is_zero' => ($followupAccuracy['leads']['due'] ?? 0) === 0,
                'tone' => $pctTone($leadAcc),
                'sub' => str_replace(
                    [':on_time', ':missed'],
                    [(string) ($followupAccuracy['leads']['on_time'] ?? 0), (string) ($followupAccuracy['leads']['missed'] ?? 0)],
                    translate('Progress_on_time_missed_sub') ?? ':on_time on time · :missed missed',
                ),
            ],
            [
                'key' => 'booking_followup_accuracy',
                'icon' => 'event_available',
                'label' => translate('Booking_followup_accuracy') ?? (translate('Booking').' '.translate('Follow_up_accuracy')),
                'value' => $bookingAcc.'%',
                'raw' => $bookingAcc,
                'pct' => $bookingAcc,
                'is_zero' => ($followupAccuracy['bookings']['due'] ?? 0) === 0,
                'tone' => $pctTone($bookingAcc),
                'sub' => str_replace(
                    [':on_time', ':missed'],
                    [(string) ($followupAccuracy['bookings']['on_time'] ?? 0), (string) ($followupAccuracy['bookings']['missed'] ?? 0)],
                    translate('Progress_on_time_missed_sub') ?? ':on_time on time · :missed missed',
                ),
            ],
            [
                'key' => 'booking_completion_rate',
                'icon' => 'trending_up',
                'label' => translate('completion_rate'),
                'value' => $completionRate.'%',
                'raw' => $completionRate,
                'pct' => $completionRate,
                'is_zero' => $outcomesTotal === 0,
                'tone' => $pctTone($completionRate),
                'sub' => str_replace(
                    [':completed', ':cancelled'],
                    [(string) $completed, (string) $cancelled],
                    translate('Progress_completed_vs_cancelled_sub') ?? ':completed completed · :cancelled cancelled',
                ),
            ],
            [
                'key' => 'booking_cancelled_pct',
                'icon' => 'cancel',
                'label' => translate('Booking_cancelled_percentage'),
                'value' => $qualityMetrics['booking_cancelled_pct'].'%',
                'raw' => $qualityMetrics['booking_cancelled_pct'],
                'pct' => (float) $qualityMetrics['booking_cancelled_pct'],
                'is_zero' => $cancelled === 0,
                'tone' => ((float) $qualityMetrics['booking_cancelled_pct']) > 0 ? 'warn' : 'good',
                'sub' => str_replace(
                    [':cancelled', ':completed'],
                    [(string) $cancelled, (string) $completed],
                    translate('Progress_cancelled_vs_completed_sub'),
                ),
            ],
            [
                'key' => 'unknown_pct',
                'icon' => 'help',
                'label' => translate('Unknown_leads_percentage'),
                'value' => $qualityMetrics['unknown_pct'].'%',
                'raw' => $qualityMetrics['unknown_pct'],
                'pct' => (float) $qualityMetrics['unknown_pct'],
                'is_zero' => ($qualityMetrics['unknown_count'] ?? 0) === 0,
                'tone' => ((float) $qualityMetrics['unknown_pct']) > 20 ? 'warn' : 'neutral',
                'sub' => str_replace(
                    [':count', ':total'],
                    [(string) ($qualityMetrics['unknown_count'] ?? 0), (string) ($qualityMetrics['leads_handled'] ?? 0)],
                    translate('Progress_of_leads_handled_sub'),
                ),
            ],
            [
                'key' => 'future_customer_pct',
                'icon' => 'schedule',
                'label' => translate('Future_customer_percentage'),
                'value' => $qualityMetrics['future_customer_pct'].'%',
                'raw' => $qualityMetrics['future_customer_pct'],
                'pct' => (float) $qualityMetrics['future_customer_pct'],
                'is_zero' => ($qualityMetrics['future_customer_count'] ?? 0) === 0,
                'tone' => 'neutral',
                'sub' => str_replace(
                    [':count', ':total'],
                    [(string) ($qualityMetrics['future_customer_count'] ?? 0), (string) ($qualityMetrics['leads_handled'] ?? 0)],
                    translate('Progress_of_leads_handled_sub'),
                ),
            ],
            [
                'key' => 'leads_missing_data',
                'icon' => 'report_problem',
                'label' => translate('Leads_with_missing_data'),
                'value' => (string) ($qualityMetrics['leads_missing_data'] ?? 0),
                'raw' => $qualityMetrics['leads_missing_data'] ?? 0,
                'is_zero' => ($qualityMetrics['leads_missing_data'] ?? 0) === 0,
                'tone' => $countTone((int) ($qualityMetrics['leads_missing_data'] ?? 0)),
                'sub' => translate('Progress_leads_data_quality_sub') ?? 'Leads needing name, phone, or type details',
            ],
        ];
    }

    private function countLeadsWithMissingDataForPeriod(string $userId, Carbon $rangeStart, Carbon $rangeEnd): int
    {
        $leads = Lead::query()
            ->where('handled_by', $userId)
            ->whereBetween('date_time_of_lead_received', [$rangeStart, $rangeEnd])
            ->get(['id', 'lead_type', 'name', 'phone_number']);

        if ($leads->isEmpty()) {
            return 0;
        }

        $typedLeadIds = $leads
            ->whereIn('lead_type', [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER])
            ->pluck('id')
            ->all();

        $historiesByLead = $typedLeadIds !== []
            ? LeadTypeHistory::query()
                ->whereIn('lead_id', $typedLeadIds)
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('lead_id')
            : collect();

        $count = 0;

        foreach ($leads as $lead) {
            if ($this->leadHasMissingData($lead, $historiesByLead->get($lead->id))) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  Collection<int, LeadTypeHistory>|null  $histories
     */
    private function leadHasMissingData(Lead $lead, ?Collection $histories): bool
    {
        if (trim((string) $lead->name) === '' || trim((string) $lead->phone_number) === '') {
            return true;
        }

        if ($lead->lead_type === Lead::TYPE_UNKNOWN) {
            return true;
        }

        if (! in_array($lead->lead_type, [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER], true)) {
            return false;
        }

        $history = $histories?->first();
        if (! $history) {
            return true;
        }

        $data = is_array($history->data) ? $history->data : [];
        $statusKey = $lead->lead_type === Lead::TYPE_CUSTOMER
            ? 'customer_lead_status_id'
            : 'provider_lead_status_id';

        return empty($data[$statusKey]);
    }

    /** @deprecated Use followupAccuracyStats() */
    private function missedFollowupStats(string $userId): array
    {
        $stats = $this->followupAccuracyStats($userId)['overall'];

        return [
            'due' => $stats['due'],
            'missed' => $stats['missed'],
            'pct' => $stats['due'] > 0 ? round(($stats['missed'] / $stats['due']) * 100, 1) : 0.0,
        ];
    }

    private function greetingForUser(User $user): string
    {
        $hour = (int) Carbon::now()->format('G');
        $timeGreeting = match (true) {
            $hour < 12 => translate('Good_morning'),
            $hour < 17 => translate('Good_afternoon'),
            default => translate('Good_evening'),
        };

        $firstName = trim((string) ($user->first_name ?? ''));
        if ($firstName === '') {
            $firstName = trim((string) ($user->last_name ?? ''));
        }
        if ($firstName === '') {
            $firstName = translate('Employee');
        }

        return $timeGreeting . ', ' . $firstName;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, array<string, mixed>>
     */
    private function buildWorkQueue(string $userId, array $context, bool $employeeScope): array
    {
        return [
            'pending' => [
                'key' => 'pending',
                'title' => translate('Whats_pending'),
                'subtitle' => translate('Whats_pending_subtitle'),
                'widgets' => $this->buildPendingWidgets($userId, $context, $employeeScope),
                'boxes' => $this->buildPendingBoxes($userId, $context, $employeeScope),
            ],
            'pickup' => [
                'key' => 'pickup',
                'title' => translate('New_to_pick_up'),
                'subtitle' => translate('New_to_pick_up_subtitle'),
                'widgets' => $this->buildPickupWidgets($userId, $context, $employeeScope),
                'boxes' => $this->buildPickupBoxes($userId, $context, $employeeScope),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array<string, mixed>>
     */
    private function buildPendingWidgets(string $userId, array $context, bool $employeeScope): array
    {
        if (! $employeeScope) {
            $employeeOptions = $context['employee_options'] ?? [];
            $defaultEmployeeId = (string) ($context['default_employee_id'] ?? '');
            [$leadCounts, $leadUrls] = $this->mapEmployeeMetricArrays($context['per_employee']['leads'] ?? []);
            [$bookingCounts, $bookingUrls] = $this->mapEmployeeMetricArrays($context['per_employee']['bookings'] ?? []);
            [$taskCounts, $taskUrls] = $this->mapEmployeeMetricArrays($context['per_employee']['tasks'] ?? []);

            return [
                $this->buildAdminSplitWidget(
                    'pending_lead_followup',
                    translate('Pending_Lead_Followup'),
                    'person',
                    'lead',
                    $leadCounts,
                    (int) $context['lead_all']['total'],
                    route('admin.lead.todays_followups'),
                    'inbox-box-lead_followups_pending',
                    $employeeOptions,
                    $defaultEmployeeId,
                    $leadUrls,
                ),
                $this->buildAdminSplitWidget(
                    'pending_booking_followup',
                    translate('Pending_Booking_Followup'),
                    'event',
                    'booking',
                    $bookingCounts,
                    (int) $context['booking_all']['total'],
                    route('admin.booking.todays_followups'),
                    'inbox-box-booking_followups_pending',
                    $employeeOptions,
                    $defaultEmployeeId,
                    $bookingUrls,
                ),
                $this->buildAdminSplitWidget(
                    'pending_tasks',
                    translate('Pending_tasks'),
                    'task_alt',
                    'task',
                    $taskCounts,
                    (int) $context['tasks_all']['total'],
                    route('admin.task-board.index'),
                    'inbox-box-pending_tasks',
                    $employeeOptions,
                    $defaultEmployeeId,
                    $taskUrls,
                    true,
                ),
            ];
        }

        return [
            [
                'key' => 'pending_lead_followup',
                'type' => 'split',
                'title' => translate('Pending_Lead_Followup'),
                'icon' => 'person',
                'tone' => 'lead',
                'yours' => $context['lead_yours']['total'],
                'all' => $context['lead_all']['total'],
                'url_yours' => route('admin.lead.todays_followups', ['handled_by' => $userId]),
                'url_all' => route('admin.lead.todays_followups'),
                'scroll_to' => 'inbox-box-lead_followups_pending',
            ],
            [
                'key' => 'pending_booking_followup',
                'type' => 'split',
                'title' => translate('Pending_Booking_Followup'),
                'icon' => 'event',
                'tone' => 'booking',
                'yours' => $context['booking_yours']['total'],
                'all' => $context['booking_all']['total'],
                'url_yours' => route('admin.booking.todays_followups', ['assignee_id' => $userId]),
                'url_all' => route('admin.booking.todays_followups'),
                'scroll_to' => 'inbox-box-booking_followups_pending',
            ],
            [
                'key' => 'pending_tasks',
                'type' => 'single',
                'compact' => true,
                'title' => translate('Pending_tasks'),
                'icon' => 'task_alt',
                'tone' => 'task',
                'total' => $context['tasks_yours']['total'],
                'url' => route('admin.task-board.index'),
                'scroll_to' => 'inbox-box-pending_tasks',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array<string, mixed>>
     */
    private function buildPickupWidgets(string $userId, array $context, bool $employeeScope): array
    {
        $unassigned = $context['unassigned'];
        $employeeOptions = $context['employee_options'] ?? [];
        $defaultEmployeeId = (string) ($context['default_employee_id'] ?? '');
        [$whatsappCounts, $whatsappUrls] = $this->mapEmployeeMetricArrays($context['per_employee']['whatsapp_assigned_unread'] ?? []);

        $assignedUnreadWidget = $employeeScope
            ? [
                'key' => 'whatsapp_assigned_unread',
                'type' => 'single',
                'title' => translate('WhatsApp_assigned_new_messages'),
                'icon' => 'mark_chat_unread',
                'tone' => 'whatsapp-unread',
                'total' => $context['whatsapp_assigned_unread']['total'],
                'url' => route('admin.whatsapp.conversations.index', [
                    'channel' => 'whatsapp',
                    'tab' => 'chats',
                    'handlers' => [$userId],
                    'unread_state' => ['unread'],
                ]),
                'scroll_to' => 'inbox-box-whatsapp_assigned_unread',
                'requires_permission' => 'whatsapp_chat_view',
            ]
            : $this->buildAdminSplitWidget(
                'whatsapp_assigned_unread',
                translate('WhatsApp_assigned_new_messages'),
                'mark_chat_unread',
                'whatsapp-unread',
                $whatsappCounts,
                (int) $context['whatsapp_assigned_unread']['total'],
                route('admin.whatsapp.conversations.index', [
                    'channel' => 'whatsapp',
                    'tab' => 'chats',
                    'unread_state' => ['unread'],
                ]),
                'inbox-box-whatsapp_assigned_unread',
                $employeeOptions,
                $defaultEmployeeId,
                $whatsappUrls,
            ) + ['requires_permission' => 'whatsapp_chat_view'];

        return [
            [
                'key' => 'unassigned_leads',
                'type' => 'single',
                'title' => translate('Unassigned_Leads'),
                'icon' => 'person_add',
                'tone' => 'unassigned-lead',
                'total' => $unassigned['leads'],
                'url' => route('admin.lead.index', ['handled_by' => ['__unassigned__']]),
                'scroll_to' => 'inbox-box-unassigned_leads',
            ],
            [
                'key' => 'unassigned_bookings',
                'type' => 'single',
                'title' => translate('Unassigned_Bookings'),
                'icon' => 'event_busy',
                'tone' => 'unassigned-booking',
                'total' => $unassigned['bookings'],
                'url' => route('admin.booking.list', [
                    'booking_status' => 'all',
                    'service_type' => 'all',
                    'assignee_ids' => ['__unassigned__'],
                ]),
                'scroll_to' => 'inbox-box-unassigned_bookings',
            ],
            [
                'key' => 'whatsapp_unassigned',
                'type' => 'single',
                'title' => translate('Unassigned_WhatsApp_Chats'),
                'icon' => 'forum',
                'tone' => 'whatsapp',
                'total' => $context['whatsapp_unassigned']['total'],
                'url' => route('admin.whatsapp.conversations.index', [
                    'channel' => 'whatsapp',
                    'tab' => 'chats',
                    'handlers' => ['ai'],
                ]),
                'scroll_to' => 'inbox-box-whatsapp_unassigned',
                'requires_permission' => 'whatsapp_chat_view',
            ],
            $assignedUnreadWidget,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array<string, mixed>>
     */
    private function buildPendingBoxes(string $userId, array $context, bool $employeeScope): array
    {
        $employeeOptions = $context['employee_options'] ?? [];
        $defaultEmployeeId = (string) ($context['default_employee_id'] ?? '');

        $leadTabs = $employeeScope
            ? [
                'yours' => [
                    'label' => translate('Yours'),
                    'total' => $context['lead_yours']['total'],
                    'items' => $context['lead_yours']['items'],
                ],
                'all' => [
                    'label' => translate('All'),
                    'total' => $context['lead_all']['total'],
                    'items' => $context['lead_all']['items'],
                ],
            ]
            : [
                'all' => [
                    'label' => translate('All'),
                    'total' => $context['lead_all']['total'],
                    'items' => $context['lead_all']['items'],
                ],
                'employee' => $this->buildEmployeeTab(
                    $employeeOptions,
                    $context['per_employee']['leads'] ?? [],
                    $defaultEmployeeId,
                ),
            ];

        $bookingTabs = $employeeScope
            ? [
                'yours' => [
                    'label' => translate('Yours'),
                    'total' => $context['booking_yours']['total'],
                    'items' => $context['booking_yours']['items'],
                ],
                'all' => [
                    'label' => translate('All'),
                    'total' => $context['booking_all']['total'],
                    'items' => $context['booking_all']['items'],
                ],
            ]
            : [
                'all' => [
                    'label' => translate('All'),
                    'total' => $context['booking_all']['total'],
                    'items' => $context['booking_all']['items'],
                ],
                'employee' => $this->buildEmployeeTab(
                    $employeeOptions,
                    $context['per_employee']['bookings'] ?? [],
                    $defaultEmployeeId,
                ),
            ];

        $taskTabs = $employeeScope
            ? [
                'yours' => [
                    'label' => translate('Yours'),
                    'total' => $context['tasks_yours']['total'],
                    'items' => $context['tasks_yours']['items'],
                ],
            ]
            : [
                'all' => [
                    'label' => translate('All'),
                    'total' => $context['tasks_all']['total'],
                    'items' => $context['tasks_all']['items'],
                ],
                'employee' => $this->buildEmployeeTab(
                    $employeeOptions,
                    $context['per_employee']['tasks'] ?? [],
                    $defaultEmployeeId,
                ),
            ];

        $leadTabs = $this->tabsWithAssigneeColumn($leadTabs, 'lead_followup');
        $bookingTabs = $this->tabsWithAssigneeColumn($bookingTabs, 'booking_followup');
        $taskTabs = $this->tabsWithAssigneeColumn($taskTabs, 'task');

        $defaultLeadEmployeeUrl = $context['per_employee']['leads'][$defaultEmployeeId]['view_all_url']
            ?? route('admin.lead.todays_followups');
        $defaultBookingEmployeeUrl = $context['per_employee']['bookings'][$defaultEmployeeId]['view_all_url']
            ?? route('admin.booking.todays_followups');
        $defaultTaskEmployeeUrl = $context['per_employee']['tasks'][$defaultEmployeeId]['view_all_url']
            ?? route('admin.task-board.index');

        return [
            [
                'key' => 'lead_followups_pending',
                'box_type' => 'tabbed',
                'list_format' => 'lead_followup',
                'layout_slot' => 'main',
                'columns' => $this->listColumns('lead_followup'),
                'title' => translate('Lead_Followups_Pending_Till_Today'),
                'icon' => 'person',
                'tone' => 'lead',
                'tabs' => $leadTabs,
                'view_all_yours_url' => $employeeScope
                    ? route('admin.lead.todays_followups', ['handled_by' => $userId])
                    : $defaultLeadEmployeeUrl,
                'view_all_all_url' => route('admin.lead.todays_followups'),
                'footer_yours_label' => translate('View_your_follow_ups'),
                'footer_all_label' => translate('View_all_follow_ups'),
            ],
            [
                'key' => 'booking_followups_pending',
                'box_type' => 'tabbed',
                'list_format' => 'booking_followup',
                'layout_slot' => 'main',
                'columns' => $this->listColumns('booking_followup'),
                'title' => translate('Booking_Followups_Pending_Till_Today'),
                'icon' => 'event',
                'tone' => 'booking',
                'tabs' => $bookingTabs,
                'view_all_yours_url' => $employeeScope
                    ? route('admin.booking.todays_followups', ['assignee_id' => $userId])
                    : $defaultBookingEmployeeUrl,
                'view_all_all_url' => route('admin.booking.todays_followups'),
                'footer_yours_label' => translate('View_your_follow_ups'),
                'footer_all_label' => translate('View_all_follow_ups'),
            ],
            [
                'key' => 'pending_tasks',
                'box_type' => 'tabbed',
                'list_format' => 'task',
                'list_display' => 'cards',
                'layout_slot' => 'side',
                'title' => translate('Pending_tasks'),
                'icon' => 'task_alt',
                'tone' => 'task',
                'tabs' => $taskTabs,
                'view_all_yours_url' => $employeeScope ? route('admin.task-board.index') : $defaultTaskEmployeeUrl,
                'view_all_all_url' => route('admin.task-board.index'),
                'footer_yours_label' => translate('View_your_tasks'),
                'footer_all_label' => translate('View_all_tasks'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array<string, mixed>>
     */
    private function buildPickupBoxes(string $userId, array $context, bool $employeeScope): array
    {
        $employeeOptions = $context['employee_options'] ?? [];
        $defaultEmployeeId = (string) ($context['default_employee_id'] ?? '');

        $assignedUnreadTabs = $employeeScope
            ? [
                'all' => [
                    'label' => translate('All'),
                    'total' => $context['whatsapp_assigned_unread']['total'],
                    'items' => $context['whatsapp_assigned_unread']['items'],
                ],
            ]
            : [
                'all' => [
                    'label' => translate('All'),
                    'total' => $context['whatsapp_assigned_unread']['total'],
                    'items' => $context['whatsapp_assigned_unread']['items'],
                ],
                'employee' => $this->buildEmployeeTab(
                    $employeeOptions,
                    $context['per_employee']['whatsapp_assigned_unread'] ?? [],
                    $defaultEmployeeId,
                ),
            ];

        $assignedUnreadTabs = $this->tabsWithAssigneeColumn($assignedUnreadTabs, 'pickup_whatsapp');

        $assignedUnreadViewAllUrl = $employeeScope
            ? route('admin.whatsapp.conversations.index', [
                'channel' => 'whatsapp',
                'tab' => 'chats',
                'handlers' => [$userId],
                'unread_state' => ['unread'],
            ])
            : route('admin.whatsapp.conversations.index', [
                'channel' => 'whatsapp',
                'tab' => 'chats',
                'unread_state' => ['unread'],
            ]);

        $defaultEmployeeAssignedUnreadUrl = $context['per_employee']['whatsapp_assigned_unread'][$defaultEmployeeId]['view_all_url']
            ?? $assignedUnreadViewAllUrl;

        return [
            [
                'key' => 'unassigned_leads',
                'box_type' => 'tabbed',
                'list_format' => 'lead_followup',
                'layout_slot' => 'default',
                'columns' => $this->listColumns('lead_followup'),
                'title' => translate('Unassigned_Leads'),
                'icon' => 'person_add',
                'tone' => 'unassigned-lead',
                'tabs' => [
                    'all' => [
                        'label' => translate('All'),
                        'total' => $context['unassigned_leads']['total'],
                        'items' => $context['unassigned_leads']['items'],
                    ],
                ],
                'view_all_yours_url' => route('admin.lead.index', ['handled_by' => ['__unassigned__']]),
                'view_all_all_url' => route('admin.lead.index', ['handled_by' => ['__unassigned__']]),
                'footer_yours_label' => translate('View_unassigned_leads'),
                'footer_all_label' => translate('View_unassigned_leads'),
            ],
            [
                'key' => 'unassigned_bookings',
                'box_type' => 'tabbed',
                'list_format' => 'booking_followup',
                'layout_slot' => 'default',
                'columns' => $this->listColumns('booking_followup'),
                'title' => translate('Unassigned_Bookings'),
                'icon' => 'event_busy',
                'tone' => 'unassigned-booking',
                'tabs' => [
                    'all' => [
                        'label' => translate('All'),
                        'total' => $context['unassigned_bookings']['total'],
                        'items' => $context['unassigned_bookings']['items'],
                    ],
                ],
                'view_all_yours_url' => route('admin.booking.list', [
                    'booking_status' => 'all',
                    'service_type' => 'all',
                    'assignee_ids' => ['__unassigned__'],
                ]),
                'view_all_all_url' => route('admin.booking.list', [
                    'booking_status' => 'all',
                    'service_type' => 'all',
                    'assignee_ids' => ['__unassigned__'],
                ]),
                'footer_yours_label' => translate('View_unassigned_bookings'),
                'footer_all_label' => translate('View_unassigned_bookings'),
            ],
            [
                'key' => 'whatsapp_unassigned',
                'box_type' => 'tabbed',
                'list_display' => 'whatsapp_cards',
                'layout_slot' => 'default',
                'title' => translate('Unassigned_WhatsApp_Chats'),
                'icon' => 'forum',
                'tone' => 'whatsapp',
                'requires_permission' => 'whatsapp_chat_view',
                'tabs' => [
                    'all' => [
                        'label' => translate('All'),
                        'total' => $context['whatsapp_unassigned']['total'],
                        'items' => $context['whatsapp_unassigned']['items'],
                    ],
                ],
                'view_all_yours_url' => route('admin.whatsapp.conversations.index', [
                    'channel' => 'whatsapp',
                    'tab' => 'chats',
                    'handlers' => ['ai'],
                ]),
                'view_all_all_url' => route('admin.whatsapp.conversations.index', [
                    'channel' => 'whatsapp',
                    'tab' => 'chats',
                    'handlers' => ['ai'],
                ]),
                'footer_yours_label' => translate('View_unassigned_whatsapp_chats'),
                'footer_all_label' => translate('View_unassigned_whatsapp_chats'),
            ],
            [
                'key' => 'whatsapp_assigned_unread',
                'box_type' => 'tabbed',
                'list_display' => 'whatsapp_cards',
                'layout_slot' => 'default',
                'title' => translate('WhatsApp_assigned_new_messages'),
                'icon' => 'mark_chat_unread',
                'tone' => 'whatsapp-unread',
                'requires_permission' => 'whatsapp_chat_view',
                'tabs' => $assignedUnreadTabs,
                'view_all_yours_url' => $employeeScope ? $assignedUnreadViewAllUrl : $defaultEmployeeAssignedUnreadUrl,
                'view_all_all_url' => $assignedUnreadViewAllUrl,
                'footer_yours_label' => translate('View_your_follow_ups'),
                'footer_all_label' => translate('View_unread'),
            ],
        ];
    }

    /**
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function unassignedLeadsList(int $previewLimit = 8): array
    {
        $base = Lead::query()->where(function ($w) {
            $w->whereNull('handled_by')
                ->orWhere('handled_by', '')
                ->orWhere('handled_by', Lead::HANDLED_BY_AI);
        });
        $this->leadOpenStatus->restrictQueryToOpenLeads($base);

        $leads = (clone $base)
            ->with('latestFollowup')
            ->orderByDesc('date_time_of_lead_received')
            ->take($previewLimit)
            ->get();

        return [
            'total' => (clone $base)->count(),
            'items' => $leads->map(fn (Lead $lead) => $this->formatLeadListRow(
                $lead,
                $lead->date_time_of_lead_received,
                $lead->latestFollowup?->urgency,
            ))->values(),
        ];
    }

    /**
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function unassignedBookingsList(int $previewLimit = 8): array
    {
        $base = Booking::query()
            ->whereNull('assignee_id')
            ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);

        $bookings = (clone $base)
            ->with('customer')
            ->orderByDesc('created_at')
            ->take($previewLimit)
            ->get();

        return [
            'total' => (clone $base)->count(),
            'items' => $bookings->map(function (Booking $booking) {
                $customerName = $booking->customer
                    ? trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? ''))
                    : '';

                return $this->formatBookingListRow(
                    route('admin.booking.details', [$booking->id, 'web_page' => 'general']),
                    $customerName !== '' ? $customerName : ($booking->readable_id ?? translate('Booking')),
                    translate(ucfirst((string) ($booking->booking_status ?? 'pending'))),
                    $booking->created_at,
                    $this->urgencyFromAge($booking->created_at),
                );
            })->values(),
        ];
    }

    /**
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function whatsappAssignedUnreadThreadItems(?string $userId, int $previewLimit = 8): array
    {
        if (! Schema::hasTable('whatsapp_users')) {
            return ['total' => 0, 'items' => collect()];
        }

        $unreadByDigits = $this->whatsappUnreadCountByPhoneDigits();
        if ($unreadByDigits->isEmpty()) {
            return ['total' => 0, 'items' => collect()];
        }

        $query = WhatsAppUser::query()->select(['phone', 'name', 'handled_by']);
        if ($userId !== null) {
            $query->where('handled_by', $userId);
        } else {
            $query->whereNotNull('handled_by')
                ->where('handled_by', '!=', '')
                ->where('handled_by', '!=', Lead::HANDLED_BY_AI);
        }

        $users = $query->get(['phone', 'name', 'handled_by']);

        return $this->buildWhatsAppPickupThreadList(
            $users,
            $unreadByDigits,
            userId: $userId ?? '',
            requireUnread: true,
            previewLimit: $previewLimit,
        );
    }

    /**
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function whatsappOpenThreadItems(?string $handledByUserId, int $previewLimit = 8, bool $unassignedOnly = false): array
    {
        if (! Schema::hasTable('whatsapp_users')) {
            return ['total' => 0, 'items' => collect()];
        }

        $query = WhatsAppUser::query()->select(['phone', 'name', 'handled_by']);
        if ($unassignedOnly) {
            $query->where(function ($q) {
                $q->whereNull('handled_by')
                    ->orWhere('handled_by', '')
                    ->orWhere('handled_by', Lead::HANDLED_BY_AI);
            });
        } elseif ($handledByUserId !== null) {
            $query->where('handled_by', $handledByUserId);
        }

        $users = $query->get();
        if ($users->isEmpty()) {
            return ['total' => 0, 'items' => collect()];
        }

        $unreadByDigits = $this->whatsappUnreadCountByPhoneDigits();

        return $this->buildWhatsAppPickupThreadList(
            $users,
            $unreadByDigits,
            userId: $handledByUserId ?? '',
            requireUnread: false,
            previewLimit: $previewLimit,
            totalCounter: fn () => $this->whatsappOpenCount($handledByUserId, $unassignedOnly),
        );
    }

    /**
     * @param  Collection<int, WhatsAppUser>  $users
     * @param  Collection<string, int>  $unreadByDigits
     * @param  (callable(): int)|null  $totalCounter
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function buildWhatsAppPickupThreadList(
        Collection $users,
        Collection $unreadByDigits,
        string $userId,
        bool $requireUnread,
        int $previewLimit,
        ?callable $totalCounter = null,
    ): array {
        $closedStatusIds = $this->closedWhatsAppStatusIds();
        $candidates = collect();
        $openCount = 0;

        foreach ($users as $waUser) {
            $phone = (string) $waUser->phone;
            if ($phone === '') {
                continue;
            }

            if ($this->isWhatsAppThreadClosed($phone, $closedStatusIds)) {
                continue;
            }

            $digits = $this->normalizeWaPhoneDigits($phone);
            $unreadCount = (int) ($unreadByDigits->get($digits) ?? 0);

            if ($requireUnread && $unreadCount <= 0) {
                continue;
            }

            $openCount++;

            $lastMessage = Schema::hasTable('whatsapp_messages')
                ? WhatsAppMessage::query()
                    ->where('phone', $digits)
                    ->orderByDesc('created_at')
                    ->first(['message_text', 'created_at'])
                : null;

            $candidates->push([
                'phone' => $phone,
                'digits' => $digits,
                'waUser' => $waUser,
                'unread_count' => $unreadCount,
                'last_message' => $lastMessage,
            ]);
        }

        $phonesForLookup = $candidates
            ->flatMap(fn (array $candidate) => array_values(array_unique([$candidate['phone'], $candidate['digits']])))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $tagsByPhone = $this->whatsappTagsByPhones($phonesForLookup);
        $statusByPhone = $this->whatsappStatusByPhones($phonesForLookup);

        $openThreads = $candidates->map(function (array $candidate) use ($tagsByPhone, $statusByPhone, $userId) {
            $phone = $candidate['phone'];
            $digits = $candidate['digits'];
            /** @var WhatsAppUser $waUser */
            $waUser = $candidate['waUser'];
            $lastMessage = $candidate['last_message'];
            $tags = $tagsByPhone->get($phone, $tagsByPhone->get($digits, []));
            $statusLabel = $statusByPhone->get($phone) ?? $statusByPhone->get($digits);

            return $this->formatWhatsAppChatCard(
                route('admin.whatsapp.conversations.chat', ['channel' => 'whatsapp', 'phone' => $digits]),
                $phone,
                $waUser->name ?: $phone,
                $lastMessage?->created_at ? Carbon::parse($lastMessage->created_at) : null,
                $lastMessage?->message_text
                    ? Str::limit(trim((string) $lastMessage->message_text), 80)
                    : translate('No_messages_yet'),
                (int) $candidate['unread_count'],
                $this->whatsAppHandlerLabel($waUser->handled_by ?? null, $userId),
                is_array($tags) ? $tags : [],
                $statusLabel,
            );
        });

        return [
            'total' => $totalCounter !== null ? $totalCounter() : $openCount,
            'items' => $openThreads
                ->sortByDesc(fn (array $thread) => $thread['datetime'] instanceof Carbon ? $thread['datetime']->timestamp : 0)
                ->take($previewLimit)
                ->values(),
        ];
    }

    /**
     * @param  list<string>  $phones
     * @return Collection<string, list<array{name: string, color: string}>>
     */
    private function whatsappTagsByPhones(array $phones): Collection
    {
        if (
            $phones === []
            || ! Schema::hasTable('whatsapp_chat_thread_tags')
            || ! Schema::hasTable('whatsapp_chat_tags')
            || ! Schema::hasTable('whatsapp_chat_thread_meta')
        ) {
            return collect();
        }

        $channel = SocialInboxChannel::current();

        return DB::table('whatsapp_chat_thread_tags as tt')
            ->join('whatsapp_chat_thread_meta as tm', 'tt.phone', '=', 'tm.phone')
            ->join('whatsapp_chat_tags as t', 'tt.whatsapp_chat_tag_id', '=', 't.id')
            ->whereIn('tt.phone', $phones)
            ->where('tm.channel', $channel)
            ->whereColumn('t.channel', 'tm.channel')
            ->orderBy('t.sort_order')
            ->orderBy('t.id')
            ->get(['tt.phone', 't.name', 't.color'])
            ->groupBy('phone')
            ->map(fn (Collection $rows) => $rows->map(fn ($row) => [
                'name' => (string) $row->name,
                'color' => (string) ($row->color ?: '#64748b'),
            ])->values()->all());
    }

    /**
     * @param  list<string>  $phones
     * @return Collection<string, string>
     */
    private function whatsappStatusByPhones(array $phones): Collection
    {
        if ($phones === [] || ! Schema::hasTable('whatsapp_chat_thread_meta')) {
            return collect();
        }

        return WhatsAppChatThreadMeta::query()
            ->with('status:id,name')
            ->whereIn('phone', $phones)
            ->get()
            ->mapWithKeys(fn (WhatsAppChatThreadMeta $meta) => [
                (string) $meta->phone => (string) ($meta->status?->name ?? ''),
            ])
            ->filter(fn (string $name) => $name !== '');
    }

    private function whatsAppHandlerLabel(?string $handledBy, string $currentUserId): string
    {
        if ($handledBy === null || $handledBy === '') {
            return translate('Unassigned');
        }

        if ($handledBy === Lead::HANDLED_BY_AI) {
            return translate('AI');
        }

        if ((string) $handledBy === $currentUserId) {
            return translate('You');
        }

        $user = User::query()->find($handledBy);

        return $this->employeeDisplayName($user, translate('Assigned'));
    }

    /**
     * @return Collection<string, int>
     */
    private function whatsappUnreadCountByPhoneDigits(): Collection
    {
        if (! Schema::hasTable('whatsapp_messages')) {
            return collect();
        }

        return WhatsAppMessage::query()
            ->where('channel', SocialInboxChannel::WHATSAPP)
            ->where('direction', 'IN')
            ->whereNull('admin_seen_at')
            ->selectRaw('phone, COUNT(*) as unread_count')
            ->groupBy('phone')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $this->normalizeWaPhoneDigits((string) $row->phone) => (int) $row->unread_count,
            ]);
    }

    /**
     * @return list<int>
     */
    private function closedWhatsAppStatusIds(): array
    {
        if (! Schema::hasTable('whatsapp_chat_statuses')) {
            return [];
        }

        return WhatsAppChatStatus::query()
            ->where('bucket', 'closed')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  list<int>  $closedStatusIds
     */
    private function isWhatsAppThreadClosed(string $phone, array $closedStatusIds): bool
    {
        if ($closedStatusIds === [] || ! Schema::hasTable('whatsapp_chat_thread_meta')) {
            return false;
        }

        $meta = WhatsAppChatThreadMeta::query()->where('phone', $phone)->first();

        return $meta && in_array((int) $meta->whatsapp_chat_status_id, $closedStatusIds, true);
    }

    private function normalizeWaPhoneDigits(?string $phone): string
    {
        return ltrim((string) $phone, '+');
    }

    /**
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function pendingTasksList(Carbon $today, ?string $assigneeUserId, int $previewLimit = 8): array
    {
        if (! Schema::hasTable('task_tickets')) {
            return ['total' => 0, 'items' => collect()];
        }

        $doneColumnIds = TaskColumn::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%done%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%complete%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%closed%']);
            })
            ->pluck('id')
            ->all();

        $base = TaskTicket::query()
            ->with(['column', 'assignees'])
            ->when($assigneeUserId !== null, fn ($q) => $q->whereHas(
                'assignees',
                fn ($assigneeQuery) => $assigneeQuery->where('users.id', $assigneeUserId),
            ))
            ->when($doneColumnIds !== [], fn ($q) => $q->whereNotIn('column_id', $doneColumnIds))
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<=', $today);

        $tasks = (clone $base)->orderBy('end_date')->take($previewLimit)->get();

        return [
            'total' => (clone $base)->count(),
            'items' => $tasks->map(function (TaskTicket $task) use ($today, $assigneeUserId) {
                $isOverdue = $task->end_date !== null && $task->end_date->lt($today->copy()->startOfDay());
                $urgency = $isOverdue
                    ? BookingFollowup::URGENCY_HIGH
                    : ($task->end_date?->isToday() ? BookingFollowup::URGENCY_MEDIUM : BookingFollowup::URGENCY_LOW);

                $row = $this->formatTaskListRow(
                    route('admin.task-board.index'),
                    $task->title,
                    $task->column->name ?? translate('Task'),
                    $task->end_date,
                    $urgency,
                    $isOverdue,
                );

                if ($assigneeUserId === null) {
                    $assigneeNames = $task->assignees
                        ->map(fn (User $assignee) => $this->employeeDisplayName($assignee, ''))
                        ->filter()
                        ->values();

                    $row['assignee_label'] = $assigneeNames->isNotEmpty()
                        ? $assigneeNames->join(', ')
                        : translate('Unassigned');
                }

                return $row;
            })->values(),
        ];
    }

    /**
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function leadFollowupsPendingTillToday(Carbon $today, ?string $handledByUserId, int $previewLimit = 8): array
    {
        $base = Lead::query()
            ->whereNotNull('next_followup_at')
            ->whereDate('next_followup_at', '<=', $today);

        if ($handledByUserId !== null) {
            $base->where('handled_by', $handledByUserId);
        }

        $this->leadOpenStatus->restrictQueryToOpenLeads($base);

        $leads = (clone $base)
            ->with('latestFollowup')
            ->orderBy('next_followup_at')
            ->take($previewLimit)
            ->get();

        return [
            'total' => (clone $base)->count(),
            'items' => $this->formatLeadPendingItems($leads, $today, $handledByUserId === null),
        ];
    }

    /**
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function bookingFollowupsPendingTillToday(Carbon $today, ?string $assigneeUserId, int $previewLimit = 8): array
    {
        $base = BookingFollowup::query()
            ->where('status', 'scheduled')
            ->whereDate('date', '<=', $today)
            ->whereHas('booking', function ($bookingQuery) use ($assigneeUserId) {
                $bookingQuery->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
                if ($assigneeUserId !== null) {
                    $bookingQuery->where('assignee_id', $assigneeUserId);
                }
            });

        $followups = (clone $base)
            ->with(['booking.customer', 'booking.assignee'])
            ->orderBy('date')
            ->take($previewLimit)
            ->get();

        return [
            'total' => (clone $base)->count(),
            'items' => $this->formatBookingPendingItems($followups, $today, $assigneeUserId === null),
        ];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @return Collection<int, array<string, mixed>>
     */
    private function formatLeadPendingItems(Collection $leads, Carbon $today, bool $showAssignee): Collection
    {
        $assigneeIds = $leads
            ->pluck('handled_by')
            ->filter(fn ($id) => Lead::assigneeIsHuman($id))
            ->unique()
            ->values()
            ->all();

        $users = $assigneeIds !== []
            ? User::query()->whereIn('id', $assigneeIds)->get()->keyBy(fn (User $u) => (string) $u->id)
            : collect();

        return $leads->map(function (Lead $lead) use ($today, $showAssignee, $users) {
            $row = $this->formatLeadListRow(
                $lead,
                $lead->next_followup_at,
                $lead->latestFollowup?->urgency,
                $today,
            );

            if ($showAssignee) {
                $row['assignee_label'] = $this->employeeDisplayName(
                    Lead::assigneeIsHuman($lead->handled_by) ? $users->get((string) $lead->handled_by) : null,
                    translate('Unassigned'),
                );
            }

            return $row;
        })->values();
    }

    /**
     * @param  Collection<int, BookingFollowup>  $followups
     * @return Collection<int, array<string, mixed>>
     */
    private function formatBookingPendingItems(Collection $followups, Carbon $today, bool $showAssignee): Collection
    {
        return $followups->map(function (BookingFollowup $followup) use ($today, $showAssignee) {
            $booking = $followup->booking;
            $customerName = $booking && $booking->customer
                ? trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? ''))
                : '';

            $row = $this->formatBookingListRow(
                $booking
                    ? route('admin.booking.details', [$booking->id, 'web_page' => 'followups'])
                    : '#',
                $customerName !== '' ? $customerName : ($booking?->readable_id ?? translate('Booking')),
                translate(ucfirst((string) ($followup->for ?: 'customer'))),
                $followup->date,
                $followup->urgency,
                $today,
            );

            if ($showAssignee) {
                $row['assignee_label'] = $this->employeeDisplayName($booking?->assignee, translate('Unassigned'));
                $row['name_sub'] = $booking?->readable_id ?: null;
            } elseif ($booking?->readable_id) {
                $row['name_sub'] = $booking->readable_id;
            }

            return $row;
        })->values();
    }

    private function employeeDisplayName(?User $user, string $fallback): string
    {
        if (! $user) {
            return $fallback;
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        return $name !== '' ? $name : ($user->email ?? $fallback);
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function listColumns(string $format, bool $withAssignee = false): array
    {
        $columns = match ($format) {
            'lead_followup' => [
                ['key' => 'name', 'label' => translate('Name')],
                ['key' => 'type', 'label' => translate('Lead_type')],
                ['key' => 'datetime', 'label' => translate('Date_time')],
                ['key' => 'urgency', 'label' => translate('Urgency')],
            ],
            'booking_followup' => [
                ['key' => 'name', 'label' => translate('Name')],
                ['key' => 'type', 'label' => translate('Type')],
                ['key' => 'datetime', 'label' => translate('Date_time')],
                ['key' => 'urgency', 'label' => translate('Urgency')],
            ],
            'task' => [
                ['key' => 'name', 'label' => translate('Name')],
                ['key' => 'type', 'label' => translate('Type')],
                ['key' => 'datetime', 'label' => translate('Date_time')],
                ['key' => 'urgency', 'label' => translate('Urgency')],
            ],
            'pickup_whatsapp' => [
                ['key' => 'name', 'label' => translate('Name')],
                ['key' => 'type', 'label' => translate('Type')],
                ['key' => 'datetime', 'label' => translate('Date_time')],
                ['key' => 'urgency', 'label' => translate('Urgency')],
            ],
            default => [],
        };

        if ($withAssignee) {
            array_splice($columns, 1, 0, [['key' => 'assignee', 'label' => translate('Assignee')]]);
        }

        return $columns;
    }

    /**
     * @param  array<string, array<string, mixed>>  $tabs
     * @return array<string, array<string, mixed>>
     */
    private function tabsWithAssigneeColumn(array $tabs, string $format): array
    {
        if (isset($tabs['all'])) {
            $tabs['all']['columns'] = $this->listColumns($format, true);
        }

        return $tabs;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatLeadListRow(
        Lead $lead,
        ?Carbon $datetime,
        ?string $urgency = null,
        ?Carbon $today = null,
    ): array {
        $today ??= Carbon::today();
        $urgency = $this->normalizeUrgency($urgency, $datetime, $today);

        return [
            'url' => route('admin.lead.show', $lead->id),
            'name' => $lead->name ?: translate('Unknown'),
            'name_sub' => $lead->phone_number ?: null,
            'type' => $this->leadTypeLabel($lead->lead_type),
            'datetime' => $datetime,
            'datetime_display' => $this->formatListDateTime($datetime),
            'urgency' => $urgency,
            'urgency_label' => translate(ucfirst($urgency)),
            'is_overdue' => $datetime !== null && $datetime->lt($today->copy()->startOfDay()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatBookingListRow(
        string $url,
        string $name,
        string $type,
        ?Carbon $datetime,
        ?string $urgency = null,
        ?Carbon $today = null,
        ?string $nameSub = null,
    ): array {
        $today ??= Carbon::today();
        $urgency = $this->normalizeUrgency($urgency, $datetime, $today);

        return [
            'url' => $url,
            'name' => $name,
            'name_sub' => $nameSub,
            'type' => $type,
            'datetime' => $datetime,
            'datetime_display' => $this->formatListDateTime($datetime),
            'urgency' => $urgency,
            'urgency_label' => translate(ucfirst($urgency)),
            'is_overdue' => $datetime !== null && $datetime->lt($today->copy()->startOfDay()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTaskListRow(
        string $url,
        string $name,
        string $type,
        ?Carbon $datetime,
        string $urgency,
        bool $isOverdue,
    ): array {
        $urgency = $this->normalizeUrgency($urgency, $datetime, Carbon::today());

        return [
            'url' => $url,
            'name' => $name,
            'type' => $type,
            'datetime' => $datetime,
            'datetime_display' => $this->formatListDateTime($datetime),
            'urgency' => $urgency,
            'urgency_label' => translate(ucfirst($urgency)),
            'is_overdue' => $isOverdue,
        ];
    }

    /**
     * @param  list<array{name: string, color: string}>  $tags
     * @return array<string, mixed>
     */
    private function formatWhatsAppChatCard(
        string $url,
        string $phone,
        string $displayName,
        ?Carbon $datetime,
        string $messagePreview,
        int $unreadCount,
        string $handlerLabel,
        array $tags,
        ?string $statusLabel = null,
    ): array {
        return [
            'url' => $url,
            'name' => $displayName,
            'phone' => $phone,
            'message_preview' => $messagePreview,
            'unread_count' => $unreadCount,
            'handler_label' => $handlerLabel,
            'tags' => $tags,
            'status_label' => $statusLabel,
            'datetime' => $datetime,
            'datetime_display' => $this->formatWhatsAppRelativeTime($datetime),
        ];
    }

    private function formatWhatsAppRelativeTime(?Carbon $datetime): string
    {
        if ($datetime === null) {
            return '—';
        }

        $totalMinutes = max(0, (int) $datetime->diffInMinutes(Carbon::now()));

        if ($totalMinutes < 1) {
            return translate('Just_now');
        }

        if ($totalMinutes < 60) {
            return str_replace(':count', (string) $totalMinutes, translate('time_min_ago'));
        }

        $hours = intdiv($totalMinutes, 60);
        if ($hours < 24) {
            return str_replace(':count', (string) $hours, translate('time_hr_ago'));
        }

        $days = intdiv($hours, 24);
        if ($days < 7) {
            return str_replace(':count', (string) $days, translate('time_d_ago'));
        }

        return $this->formatListDateTime($datetime);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPickupWhatsAppRow(string $url, string $name, ?Carbon $datetime, ?string $type = null): array
    {
        return [
            'url' => $url,
            'name' => $name,
            'type' => $type ?? translate('WhatsApp'),
            'datetime' => $datetime,
            'datetime_display' => $this->formatListDateTime($datetime),
            'urgency' => BookingFollowup::URGENCY_HIGH,
            'urgency_label' => translate('High'),
            'is_overdue' => false,
        ];
    }

    private function leadTypeLabel(?string $leadType): string
    {
        return Lead::leadTypes()[$leadType ?? ''] ?? ucfirst(str_replace('_', ' ', (string) $leadType));
    }

    private function formatListDateTime(?Carbon $datetime): string
    {
        return $datetime ? $datetime->format('d M, h:i A') : '—';
    }

    private function normalizeUrgency(?string $urgency, ?Carbon $datetime, Carbon $today): string
    {
        if ($urgency !== null && $urgency !== '' && in_array($urgency, BookingFollowup::URGENCIES, true)) {
            return $urgency;
        }

        if ($datetime !== null && $datetime->lt($today->copy()->startOfDay())) {
            return BookingFollowup::URGENCY_HIGH;
        }

        return BookingFollowup::URGENCY_MEDIUM;
    }

    private function urgencyFromAge(?Carbon $datetime): string
    {
        if ($datetime === null) {
            return BookingFollowup::URGENCY_MEDIUM;
        }

        return $datetime->lt(Carbon::now()->subDay())
            ? BookingFollowup::URGENCY_HIGH
            : BookingFollowup::URGENCY_MEDIUM;
    }

    /**
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function missedLeadFollowupsList(string $userId, int $limit = 100): array
    {
        $today = Carbon::today();
        $base = Lead::query()
            ->where('handled_by', $userId)
            ->whereNotNull('next_followup_at')
            ->whereDate('next_followup_at', '<', $today);
        $this->leadOpenStatus->restrictQueryToOpenLeads($base);

        $leads = (clone $base)
            ->with('latestFollowup')
            ->orderBy('next_followup_at')
            ->take($limit)
            ->get();

        return [
            'total' => (clone $base)->count(),
            'items' => $this->formatLeadPendingItems($leads, $today, false),
        ];
    }

    /**
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function missedBookingFollowupsList(string $userId, int $limit = 100): array
    {
        $today = Carbon::today();
        $base = BookingFollowup::query()
            ->where('status', 'scheduled')
            ->whereDate('date', '<', $today)
            ->whereHas('booking', function ($q) use ($userId) {
                $q->where('assignee_id', $userId)
                    ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
            });

        $followups = (clone $base)
            ->with(['booking.customer', 'booking.assignee'])
            ->orderBy('date')
            ->take($limit)
            ->get();

        return [
            'total' => (clone $base)->count(),
            'items' => $this->formatBookingPendingItems($followups, $today, false),
        ];
    }

    /**
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function dueTodayLeadFollowupsList(string $userId, Carbon $today, int $limit = 100): array
    {
        $base = Lead::query()
            ->where('handled_by', $userId)
            ->whereNotNull('next_followup_at')
            ->whereDate('next_followup_at', $today);
        $this->leadOpenStatus->restrictQueryToOpenLeads($base);

        $leads = (clone $base)
            ->with('latestFollowup')
            ->orderBy('next_followup_at')
            ->take($limit)
            ->get();

        return [
            'total' => (clone $base)->count(),
            'items' => $this->formatLeadPendingItems($leads, $today, false),
        ];
    }

    /**
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function dueTodayBookingFollowupsList(string $userId, Carbon $today, int $limit = 100): array
    {
        $base = BookingFollowup::query()
            ->where('status', 'scheduled')
            ->whereDate('date', $today)
            ->whereHas('booking', function ($q) use ($userId) {
                $q->where('assignee_id', $userId)
                    ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
            });

        $followups = (clone $base)
            ->with(['booking.customer', 'booking.assignee'])
            ->orderBy('date')
            ->take($limit)
            ->get();

        return [
            'total' => (clone $base)->count(),
            'items' => $this->formatBookingPendingItems($followups, $today, false),
        ];
    }

    /**
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function openLeadsPipeline(string $userId, int $limit = 100): array
    {
        $base = Lead::query()->where('handled_by', $userId);
        $this->leadOpenStatus->restrictQueryToOpenLeads($base);

        $leads = (clone $base)
            ->with('latestFollowup')
            ->orderByDesc('date_time_of_lead_received')
            ->take($limit)
            ->get();

        return [
            'total' => (clone $base)->count(),
            'items' => $leads->map(function (Lead $lead) {
                $row = $this->formatLeadListRow(
                    $lead,
                    $lead->next_followup_at ?? $lead->date_time_of_lead_received,
                    $lead->latestFollowup?->urgency,
                );
                $row['status'] = $this->leadTypeLabel($lead->lead_type);
                $row['received_at'] = $this->formatListDateTime($lead->date_time_of_lead_received);

                return $row;
            })->values(),
        ];
    }

    /**
     * @return array{total: int, items: Collection<int, array<string, mixed>>}
     */
    private function activeBookingsPipeline(string $userId, int $limit = 100): array
    {
        $base = Booking::query()
            ->where('assignee_id', $userId)
            ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);

        $bookings = (clone $base)
            ->with(['customer', 'followups' => fn ($q) => $q->where('status', 'scheduled')->orderBy('date')])
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();

        return [
            'total' => (clone $base)->count(),
            'items' => $bookings->map(function (Booking $booking) {
                $customerName = $booking->customer
                    ? trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? ''))
                    : '';
                $nextFollowup = $booking->followups->first();

                return $this->formatBookingListRow(
                    route('admin.booking.details', [$booking->id, 'web_page' => 'general']),
                    $customerName !== '' ? $customerName : ($booking->readable_id ?? translate('Booking')),
                    translate(ucfirst((string) ($booking->booking_status ?? 'pending'))),
                    $nextFollowup?->date ?? $booking->created_at,
                    $nextFollowup?->urgency,
                    null,
                    $booking->readable_id,
                );
            })->values(),
        ];
    }

    /**
     * @return array{
     *     overall_rank: int,
     *     total_employees: int,
     *     overall_score: int,
     *     metrics: list<array<string, mixed>>
     * }
     */
    private function teamLeaderboardForPeriod(string $userId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $teamEmployees = User::query()
            ->where('user_type', 'admin-employee')
            ->get();

        if ($teamEmployees->isEmpty()) {
            return [
                'overall_rank' => 1,
                'total_employees' => 1,
                'overall_score' => 0,
                'metrics' => [],
            ];
        }

        $teamReport = $this->dailyEmployeeReport->buildReport($teamEmployees, $periodStart, $periodEnd);
        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        $completedByEmployee = Booking::query()
            ->where('booking_status', 'completed')
            ->whereNotNull('assignee_id')
            ->whereBetween('updated_at', [$rangeStart, $rangeEnd])
            ->selectRaw('assignee_id, COUNT(*) as cnt')
            ->groupBy('assignee_id')
            ->pluck('cnt', 'assignee_id')
            ->map(fn ($v) => (int) $v);

        $metricDefs = [
            ['key' => 'bookings_completed', 'label' => translate('Bookings_completed'), 'source' => 'completed'],
            ['key' => 'bookings_created', 'label' => translate('Bookings_created'), 'source' => 'report'],
            ['key' => 'outbound_enquiries', 'label' => translate('Outbound_enquiries'), 'source' => 'report'],
        ];

        $employeeScores = [];
        foreach ($teamEmployees as $employee) {
            $employeeScores[(string) $employee->id] = 0;
        }

        $metrics = [];
        foreach ($metricDefs as $def) {
            $values = [];
            foreach ($teamReport['employee_totals'] as $row) {
                $empId = (string) ($row['employee_id'] ?? '');
                if ($empId === '') {
                    continue;
                }
                $value = $def['source'] === 'completed'
                    ? (int) ($completedByEmployee[$empId] ?? 0)
                    : (int) ($row[$def['key']] ?? 0);
                $values[$empId] = [
                    'employee_id' => $empId,
                    'employee_name' => (string) ($row['employee_name'] ?? $empId),
                    'value' => $value,
                ];
            }

            uasort($values, fn (array $a, array $b) => $b['value'] <=> $a['value']);
            $ranked = array_values($values);
            $teamTotal = array_sum(array_column($ranked, 'value'));
            $teamAvg = count($ranked) > 0 ? round($teamTotal / count($ranked), 1) : 0.0;
            $userRank = 1;
            $userValue = 0;

            foreach ($ranked as $index => $entry) {
                if ($entry['employee_id'] === $userId) {
                    $userRank = $index + 1;
                    $userValue = $entry['value'];
                    break;
                }
            }

            $topValue = $ranked[0]['value'] ?? 0;
            if ($userValue > 0 && $topValue > 0) {
                $employeeScores[$userId] += (int) round(($userValue / $topValue) * 100);
            }

            $metrics[] = [
                'key' => $def['key'],
                'label' => $def['label'],
                'rank' => $userRank,
                'total_employees' => count($ranked),
                'value' => $userValue,
                'team_avg' => $teamAvg,
                'team_total' => $teamTotal,
                'top_value' => $topValue,
                'vs_avg' => round($userValue - $teamAvg, 1),
            ];
        }

        arsort($employeeScores);
        $overallRank = 1;
        foreach (array_keys($employeeScores) as $index => $empId) {
            if ($empId === $userId) {
                $overallRank = $index + 1;
                break;
            }
        }

        return [
            'overall_rank' => $overallRank,
            'total_employees' => $teamEmployees->count(),
            'overall_score' => (int) ($employeeScores[$userId] ?? 0),
            'metrics' => $metrics,
        ];
    }

    /**
     * @param  array<string, mixed>  $periodTotals
     * @param  array{completed_bookings: int, completed_amount: float, cancelled_bookings: int}  $outcomes
     * @param  array{missed: int, due: int, accuracy_pct: float}  $missedStats
     * @param  list<array{key: string, label: string, mine: int, all: int, pct: float}>  $contribution
     * @param  array<string, mixed>  $leaderboard
     * @param  array<string, mixed>  $qualityMetrics
     * @return array{good: list<array<string, mixed>>, bad: list<array<string, mixed>>, neutral: list<array<string, mixed>>}
     */
    private function buildProgressScorecard(
        string $userId,
        array $periodTotals,
        array $outcomes,
        float $disciplinePct,
        array $missedStats,
        array $contribution,
        array $leaderboard,
        array $qualityMetrics = [],
    ): array {
        $good = [];
        $bad = [];
        $neutral = [];

        if ($outcomes['completed_bookings'] > 0) {
            $good[] = [
                'icon' => 'check_circle',
                'label' => translate('Bookings_completed'),
                'value' => (string) $outcomes['completed_bookings'],
                'detail' => with_currency_symbol($outcomes['completed_amount']) . ' ' . translate('Progress_revenue_generated'),
            ];
        }

        if ($disciplinePct >= 90) {
            $good[] = [
                'icon' => 'schedule',
                'label' => translate('Follow_up_accuracy'),
                'value' => round($disciplinePct) . '%',
                'detail' => translate('Progress_discipline_excellent'),
            ];
        } elseif ($disciplinePct >= 70) {
            $neutral[] = [
                'icon' => 'schedule',
                'label' => translate('Follow_up_accuracy'),
                'value' => round($disciplinePct) . '%',
                'detail' => translate('Progress_discipline_ok'),
            ];
        } else {
            $bad[] = [
                'icon' => 'schedule',
                'label' => translate('Follow_up_accuracy'),
                'value' => round($disciplinePct) . '%',
                'detail' => str_replace(':count', (string) $missedStats['missed'], translate('Progress_missed_followups_sub')),
            ];
        }

        $unknownPct = (float) ($qualityMetrics['unknown_pct'] ?? 0);
        if ($unknownPct > 20 && ($qualityMetrics['leads_handled'] ?? 0) > 0) {
            $bad[] = [
                'icon' => 'help',
                'label' => translate('Unknown_leads_percentage'),
                'value' => $unknownPct . '%',
                'detail' => str_replace(
                    [':count', ':total'],
                    [(string) ($qualityMetrics['unknown_count'] ?? 0), (string) ($qualityMetrics['leads_handled'] ?? 0)],
                    translate('Progress_of_leads_handled_sub'),
                ),
            ];
        }

        if (($qualityMetrics['leads_missing_data'] ?? 0) > 0) {
            $bad[] = [
                'icon' => 'report_problem',
                'label' => translate('Leads_with_missing_data'),
                'value' => (string) $qualityMetrics['leads_missing_data'],
                'detail' => translate('Progress_missing_data_detail'),
            ];
        }

        if (($qualityMetrics['outbounds_done'] ?? 0) > 0) {
            $neutral[] = [
                'icon' => 'call_made',
                'label' => translate('Outbounds_done'),
                'value' => (string) $qualityMetrics['outbounds_done'],
                'detail' => translate('Progress_outbounds_done_detail'),
            ];
        }

        if ($outcomes['cancelled_bookings'] > 0) {
            $bad[] = [
                'icon' => 'cancel',
                'label' => translate('Cancelled_bookings'),
                'value' => (string) $outcomes['cancelled_bookings'],
                'detail' => translate('Progress_cancelled_detail'),
            ];
        }

        $leadsAdded = (int) ($periodTotals['leads_added'] ?? 0);
        $bookingsCreated = (int) ($periodTotals['bookings_created'] ?? 0);
        if ($leadsAdded > 0 && $bookingsCreated > 0) {
            $conversion = round(($bookingsCreated / $leadsAdded) * 100, 1);
            $entry = [
                'icon' => 'trending_up',
                'label' => translate('Progress_lead_to_booking'),
                'value' => $conversion . '%',
                'detail' => $bookingsCreated . ' / ' . $leadsAdded . ' ' . translate('Progress_leads_converted'),
            ];
            if ($conversion >= 30) {
                $good[] = $entry;
            } elseif ($conversion >= 10) {
                $neutral[] = $entry;
            } else {
                $bad[] = $entry;
            }
        }

        foreach ($contribution as $row) {
            if ($row['pct'] >= 25 && $row['mine'] > 0) {
                $good[] = [
                    'icon' => $row['icon'] ?? 'leaderboard',
                    'label' => $row['label'],
                    'value' => $row['pct'] . '%',
                    'detail' => translate('Progress_top_team_contributor'),
                ];
            }
        }

        $overallRank = (int) ($leaderboard['overall_rank'] ?? 1);
        $totalEmployees = (int) ($leaderboard['total_employees'] ?? 1);
        if ($totalEmployees > 1) {
            $rankEntry = [
                'icon' => 'military_tech',
                'label' => translate('Progress_team_rank'),
                'value' => '#' . $overallRank . ' / ' . $totalEmployees,
                'detail' => translate('Progress_rank_based_on_activity'),
            ];
            if ($overallRank <= max(1, (int) ceil($totalEmployees / 3))) {
                $good[] = $rankEntry;
            } elseif ($overallRank <= (int) ceil($totalEmployees / 2)) {
                $neutral[] = $rankEntry;
            } else {
                $bad[] = $rankEntry;
            }
        }

        $bookingsCreated = (int) ($periodTotals['bookings_created'] ?? 0);
        $outboundsDone = (int) ($periodTotals['outbound_enquiries'] ?? 0);
        if ($bookingsCreated + $outboundsDone === 0 && $outcomes['completed_bookings'] === 0) {
            $neutral[] = [
                'icon' => 'info',
                'label' => translate('Progress_activity_level'),
                'value' => translate('Progress_no_activity'),
                'detail' => translate('Progress_no_activity_detail'),
            ];
        }

        return [
            'good' => $good,
            'bad' => $bad,
            'neutral' => $neutral,
        ];
    }

    /**
     * @param  array{good: list<array<string, mixed>>, bad: list<array<string, mixed>>, neutral: list<array<string, mixed>>}  $scorecard
     * @param  array<string, mixed>  $missedFollowups
     * @param  array<string, mixed>  $pendingFollowups
     * @param  list<array{key: string, label: string, mine: int, all: int, pct: float}>  $contribution
     * @param  array<string, mixed>  $leaderboard
     * @param  array{completed_bookings: int, completed_amount: float, cancelled_bookings: int}  $outcomes
     * @return list<array{priority: string, icon: string, title: string, detail: string}>
     */
    private function buildProgressImprovements(
        array $scorecard,
        array $missedFollowups,
        array $pendingFollowups,
        array $contribution,
        array $leaderboard,
        float $disciplinePct,
        array $outcomes,
    ): array {
        $suggestions = [];

        if (($missedFollowups['total'] ?? 0) > 0) {
            $leadCount = $missedFollowups['leads']['total'] ?? 0;
            $bookingCount = $missedFollowups['bookings']['total'] ?? 0;
            $suggestions[] = [
                'priority' => 'high',
                'icon' => 'warning',
                'title' => translate('Progress_improve_clear_overdue'),
                'detail' => str_replace(
                    [':leads', ':bookings'],
                    [(string) $leadCount, (string) $bookingCount],
                    translate('Progress_improve_overdue_detail'),
                ),
            ];
        }

        if (($pendingFollowups['total'] ?? 0) > 0) {
            $suggestions[] = [
                'priority' => 'high',
                'icon' => 'today',
                'title' => translate('Progress_improve_today_followups'),
                'detail' => str_replace(
                    ':count',
                    (string) $pendingFollowups['total'],
                    translate('Progress_improve_today_detail'),
                ),
            ];
        }

        if ($disciplinePct < 70) {
            $suggestions[] = [
                'priority' => 'high',
                'icon' => 'schedule',
                'title' => translate('Progress_improve_discipline'),
                'detail' => translate('Progress_improve_discipline_detail'),
            ];
        }

        if ($outcomes['cancelled_bookings'] > 0 && $outcomes['cancelled_bookings'] >= $outcomes['completed_bookings']) {
            $suggestions[] = [
                'priority' => 'medium',
                'icon' => 'cancel',
                'title' => translate('Progress_improve_cancellations'),
                'detail' => translate('Progress_improve_cancellations_detail'),
            ];
        }

        foreach ($leaderboard['metrics'] ?? [] as $metric) {
            if (($metric['vs_avg'] ?? 0) < -2 && ($metric['team_avg'] ?? 0) > 0) {
                $suggestions[] = [
                    'priority' => 'medium',
                    'icon' => 'trending_down',
                    'title' => str_replace(':metric', $metric['label'], translate('Progress_improve_below_avg')),
                    'detail' => str_replace(
                        [':yours', ':avg'],
                        [(string) ($metric['value'] ?? 0), (string) ($metric['team_avg'] ?? 0)],
                        translate('Progress_improve_below_avg_detail'),
                    ),
                ];
            }
        }

        foreach ($contribution as $row) {
            if ($row['all'] > 0 && $row['mine'] === 0 && $row['all'] >= 3) {
                $suggestions[] = [
                    'priority' => 'low',
                    'icon' => 'lightbulb',
                    'title' => str_replace(':metric', $row['label'], translate('Progress_improve_try_metric')),
                    'detail' => str_replace(':metric', $row['label'], translate('Progress_improve_try_metric_detail')),
                ];
                break;
            }
        }

        if ($suggestions === [] && count($scorecard['good'] ?? []) > 0) {
            $suggestions[] = [
                'priority' => 'low',
                'icon' => 'thumb_up',
                'title' => translate('Progress_improve_keep_going'),
                'detail' => translate('Progress_improve_keep_going_detail'),
            ];
        }

        $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
        usort($suggestions, fn (array $a, array $b) => ($priorityOrder[$a['priority']] ?? 9) <=> ($priorityOrder[$b['priority']] ?? 9));

        return array_slice($suggestions, 0, 6);
    }

    /**
     * @return Collection<int, User>
     */
    public function dashboardEmployeeCollection(): Collection
    {
        return $this->dashboardEmployees();
    }

    /**
     * @return array<string, mixed>
     */
    public function monthlyPerformanceForEmployees(Collection $employees, Carbon $periodStart, Carbon $periodEnd): array
    {
        if ($employees->count() === 1) {
            return $this->monthlyPerformanceForUser($employees->first(), $periodStart, $periodEnd);
        }

        $report = $this->dailyEmployeeReport->buildReport($employees, $periodStart, $periodEnd);

        return $this->monthlyPerformanceForTeam(
            $periodStart,
            $periodEnd,
            $report['totals'] ?? [],
            $employees,
        );
    }

    /**
     * @param  array<string, mixed>  $periodTotals
     * @return array<string, mixed>
     */
    public function progressFullReportForEmployees(
        Collection $employees,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $periodTotals = [],
    ): array {
        if ($employees->count() === 1) {
            $user = $employees->first();
            if (! $user instanceof User) {
                return [];
            }

            return $this->progressFullReportForUser($user, $periodStart, $periodEnd, $periodTotals);
        }

        return $this->progressFullReportForTeam($employees, $periodStart, $periodEnd, $periodTotals);
    }

    /**
     * @param  array<string, mixed>  $periodTotals
     * @return array<string, mixed>
     */
    private function progressFullReportForTeam(
        Collection $employees,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $periodTotals = [],
    ): array {
        $teamReport = $this->dailyEmployeeReport->buildReport($employees, $periodStart, $periodEnd);
        $teamTotals = $periodTotals !== [] ? $periodTotals : ($teamReport['totals'] ?? []);
        $outcomes = $this->teamBookingOutcomesForPeriod($periodStart, $periodEnd);
        $missedStats = $this->teamMissedFollowupStats($employees);
        $disciplinePct = $missedStats['accuracy_pct'];
        $qualityStats = $this->buildTeamQualityStatsForPeriod($employees, $periodStart, $periodEnd, $teamTotals);
        $contribution = $this->teamEmployeeShareRows(
            $teamReport['employee_totals'] ?? [],
            $teamTotals,
            $periodStart,
            $periodEnd,
        );
        $teamRankRows = $this->teamOverallRankRows($employees, $periodStart, $periodEnd);

        $scorecard = [
            'good' => [],
            'bad' => [],
            'neutral' => [],
        ];

        if ($outcomes['completed_bookings'] > 0) {
            $scorecard['good'][] = [
                'icon' => 'check_circle',
                'label' => translate('Bookings_completed'),
                'value' => (string) $outcomes['completed_bookings'],
                'detail' => with_currency_symbol($outcomes['completed_amount']),
            ];
        }

        if ((int) ($teamTotals['bookings_created'] ?? 0) > 0) {
            $scorecard['neutral'][] = [
                'icon' => 'add_shopping_cart',
                'label' => translate('Bookings_created'),
                'value' => (string) (int) ($teamTotals['bookings_created'] ?? 0),
                'detail' => null,
            ];
        }

        if ($missedStats['missed'] > 0) {
            $scorecard['bad'][] = [
                'icon' => 'warning',
                'label' => translate('Progress_missed_followups'),
                'value' => (string) $missedStats['missed'],
                'detail' => str_replace(':count', (string) $missedStats['missed'], translate('Progress_missed_followups_sub')),
            ];
        }

        $improvements = [];
        if ($missedStats['missed'] > 0) {
            $improvements[] = [
                'priority' => 'high',
                'icon' => 'warning',
                'title' => translate('Progress_improve_clear_overdue'),
                'detail' => str_replace(':count', (string) $missedStats['missed'], translate('Progress_missed_followups_sub')),
            ];
        }

        return [
            'viewing_team' => true,
            'contribution' => $contribution,
            'leaderboard' => [
                'total_employees' => $employees->count(),
                'overall_rank' => 0,
                'overall_score' => 0,
                'metrics' => [],
            ],
            'team_rank_rows' => $teamRankRows,
            'scorecard' => $scorecard,
            'improvements' => $improvements,
            'missed_followups' => [
                'leads' => ['total' => 0, 'items' => collect()],
                'bookings' => ['total' => 0, 'items' => collect()],
                'total' => $missedStats['missed'],
            ],
            'pending_followups' => [
                'leads' => ['total' => 0, 'items' => collect()],
                'bookings' => ['total' => 0, 'items' => collect()],
                'total' => 0,
            ],
            'pipeline' => [
                'leads' => ['total' => 0, 'items' => collect()],
                'bookings' => ['total' => 0, 'items' => collect()],
            ],
            'discipline_pct' => $disciplinePct,
            'missed_stats' => $missedStats,
            'quality_stats' => $qualityStats,
            'outcomes' => $outcomes,
        ];
    }
}
