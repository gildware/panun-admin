<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Modules\AdminModule\Services\EmployeeFollowupProgressAnalyticsService;
use Modules\AdminModule\Services\EmployeeLeadProgressAnalyticsService;
use Modules\AdminModule\Services\EmployeeDashboardService;
use Modules\AdminModule\Services\EmployeeProgressContributionService;
use Modules\AdminModule\Services\EmployeeProgressMetricHelp;
use Modules\AdminModule\Services\EmployeeProgressAnalyticsService;
use Modules\AdminModule\Services\EmployeeProgressRankMetricDetailService;
use Modules\AdminModule\Services\EmployeeProgressScoreService;
use Modules\AdminModule\Services\Report\DailyEmployeeReportService;
use Modules\UserManagement\Entities\User;

class EmployeeProgressReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DailyEmployeeReportService $reportService,
        private readonly EmployeeDashboardService $employeeDashboard,
        private readonly EmployeeProgressAnalyticsService $progressAnalytics,
        private readonly EmployeeLeadProgressAnalyticsService $leadProgressAnalytics,
        private readonly EmployeeFollowupProgressAnalyticsService $followupProgressAnalytics,
        private readonly EmployeeProgressContributionService $contributionTotals,
        private readonly EmployeeProgressRankMetricDetailService $rankMetricDetailService,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): Renderable
    {
        $scope = $this->resolveScope($request);
        /** @var Collection<int, User> $employees */
        $employees = $scope['employees'];
        $user = $scope['user'];
        $viewingAll = $scope['viewing_all'];
        $viewingAsAdmin = $scope['viewing_as_admin'];
        $employeeQuery = $scope['employee_query'];

        $tab = in_array($request->input('tab'), ['daily', 'monthly'], true)
            ? $request->input('tab')
            : 'monthly';

        $metricColumns = $this->metricColumns();
        $activityMetricColumns = DailyEmployeeReportService::activityMetricColumns();
        $sectionDefs = $this->detailSectionDefs();
        $employeeOptions = $this->employeeOptionsForViewer();

        if ($tab === 'monthly') {
            [$dateFrom, $dateTo] = $this->resolveMonthRange($request);
            $report = $this->reportService->buildReport($employees, $dateFrom, $dateTo);
            $dailyRows = collect($report['rows'])
                ->filter(fn (array $row) => ! empty($row['has_activity']))
                ->values()
                ->all();
            $activityTotals = $viewingAll
                ? ($report['totals'] ?? [])
                : ($report['employee_totals'][0] ?? []);
            $activityTotals = DailyEmployeeReportService::withDerivedActivityMetrics($activityTotals);
            $activityDailyRows = $this->buildActivityDailyRows($dailyRows, $viewingAll);
            $monthly = $this->employeeDashboard->monthlyPerformanceForEmployees($employees, $dateFrom, $dateTo);
            $fullReport = $this->employeeDashboard->progressFullReportForEmployees(
                $employees,
                $dateFrom,
                $dateTo,
                $activityTotals,
            );
            $analytics = $this->progressAnalytics->build(
                $employees,
                $dateFrom,
                $dateTo,
                $report,
                $fullReport,
                $viewingAll,
                ['detail' => null],
            );
            $leadAnalytics = $this->leadProgressAnalytics->build($employees, $dateFrom, $dateTo);
            $leadAnalytics['period_label'] = $dateFrom->format('d M').' – '.$dateTo->format('d M Y');
            $followupAnalytics = $this->followupProgressAnalytics->build($employees, $dateFrom, $dateTo, $fullReport);
            $followupAnalytics['period_label'] = $dateFrom->format('d M').' – '.$dateTo->format('d M Y');

            [$activityTeamTotals, $analytics, $leadAnalytics, $followupAnalytics] = $this->withContributionTotals(
                $employees,
                $dateFrom,
                $dateTo,
                $viewingAll,
                $report['totals'] ?? [],
                $analytics,
                $leadAnalytics,
                $followupAnalytics,
            );

            return view('adminmodule::employee-progress-report', [
                'tab' => 'monthly',
                'user' => $user,
                'viewingAllEmployees' => $viewingAll,
                'showContributionTotals' => ! $viewingAll,
                'dateFrom' => $dateFrom->toDateString(),
                'dateTo' => $dateTo->toDateString(),
                'periodLabel' => $dateFrom->format('d M').' – '.$dateTo->format('d M Y'),
                'metricColumns' => $metricColumns,
                'activityMetricColumns' => $activityMetricColumns,
                'activityTotals' => $activityTotals,
                'activityTeamTotals' => $activityTeamTotals,
                'activityDailyRows' => $activityDailyRows,
                'dailyRows' => $dailyRows,
                'monthly' => $monthly,
                'fullReport' => $fullReport,
                'analytics' => $analytics,
                'leadAnalytics' => $leadAnalytics,
                'followupAnalytics' => $followupAnalytics,
                'detail' => null,
                'date' => null,
                'employeeOptions' => $employeeOptions,
                'viewingAsAdmin' => $viewingAsAdmin,
                'employeeQuery' => $employeeQuery,
                'metricHelpRegistry' => EmployeeProgressMetricHelp::registry(),
            ]);
        }

        $date = $this->resolveSingleDate($request->input('date'));
        $focusIds = $viewingAll ? [] : [(string) $user?->id];
        $detail = $this->reportService->buildDayDetail($employees, $date, $focusIds);
        $dayReport = $this->reportService->buildReport($employees, $date, $date);
        $activityTotals = DailyEmployeeReportService::withDerivedActivityMetrics($detail['totals'] ?? []);
        $fullReport = $this->employeeDashboard->progressFullReportForEmployees(
            $employees,
            $date,
            $date,
            $activityTotals,
        );
        $analytics = $this->progressAnalytics->build(
            $employees,
            $date,
            $date,
            $dayReport,
            $fullReport,
            $viewingAll,
            ['detail' => $detail],
        );
        $leadAnalytics = $this->leadProgressAnalytics->build($employees, $date, $date);
        $leadAnalytics['period_label'] = $detail['date_label'] ?? $date->format('d M Y');
        $followupAnalytics = $this->followupProgressAnalytics->build($employees, $date, $date, $fullReport);
        $followupAnalytics['period_label'] = $detail['date_label'] ?? $date->format('d M Y');

        [$activityTeamTotals, $analytics, $leadAnalytics, $followupAnalytics] = $this->withContributionTotals(
            $employees,
            $date,
            $date,
            $viewingAll,
            $dayReport['totals'] ?? [],
            $analytics,
            $leadAnalytics,
            $followupAnalytics,
        );

        return view('adminmodule::employee-progress-report', [
            'tab' => 'daily',
            'user' => $user,
            'viewingAllEmployees' => $viewingAll,
            'showContributionTotals' => ! $viewingAll,
            'date' => $date->toDateString(),
            'dateLabel' => $detail['date_label'],
            'metricColumns' => $metricColumns,
            'activityMetricColumns' => $activityMetricColumns,
            'activityTotals' => $activityTotals,
            'activityTeamTotals' => $activityTeamTotals,
            'activityDailyRows' => [],
            'sectionDefs' => $sectionDefs,
            'detail' => $detail,
            'dailyRows' => [],
            'monthly' => [],
            'fullReport' => $fullReport,
            'analytics' => $analytics,
            'leadAnalytics' => $leadAnalytics,
            'followupAnalytics' => $followupAnalytics,
            'dateFrom' => null,
            'dateTo' => null,
            'periodLabel' => null,
            'employeeOptions' => $employeeOptions,
            'viewingAsAdmin' => $viewingAsAdmin,
            'employeeQuery' => $employeeQuery,
            'metricHelpRegistry' => EmployeeProgressMetricHelp::registry(),
        ]);
    }

    /**
     * Full marks report for one employee — every mark type with underlying record tables.
     *
     * @throws AuthorizationException
     */
    public function employeeRankingReport(Request $request): Renderable
    {
        $scope = $this->resolveScope($request);

        $employeeId = (string) $request->input('employee_id', '');
        if ($employeeId === '' && $scope['user']) {
            $employeeId = (string) $scope['user']->id;
        }

        if ($employeeId === '' || $employeeId === '__all__') {
            abort(404);
        }

        if (is_admin_employee() && (string) auth()->id() !== $employeeId) {
            abort(403);
        }

        $range = $this->resolveRankingPeriod($request);
        $dateFrom = $range['dateFrom'];
        $dateTo = $range['dateTo'];

        /** @var User $employee */
        $employee = User::query()
            ->where('user_type', 'admin-employee')
            ->findOrFail($employeeId);

        $employeeName = trim((string) $employee->first_name.' '.(string) $employee->last_name);
        if ($employeeName === '') {
            $employeeName = (string) $employee->email;
        }

        $teamEmployees = $this->employeeDashboard->dashboardEmployeeCollection();
        if ($teamEmployees->isEmpty()) {
            $teamEmployees = collect([$employee]);
        }

        $teamRankRows = $this->employeeDashboard->teamOverallRankRows($teamEmployees, $dateFrom, $dateTo);
        $scoreRow = collect($teamRankRows)->firstWhere('employee_id', $employeeId) ?? [];

        $fullReport = $this->rankMetricDetailService->buildFullEmployeeReport($employeeId, $dateFrom, $dateTo);

        $employeeOptions = $this->employeeOptionsForRankingReport();

        return view('adminmodule::employee-progress-employee-ranking-report', [
            'user' => $scope['user'],
            'employee' => $employee,
            'employeeName' => $employeeName,
            'viewingAsAdmin' => $scope['viewing_as_admin'],
            'employeeQuery' => $scope['employee_query'],
            'employeeOptions' => $employeeOptions,
            'period' => $range['period'],
            'periodLabel' => $range['periodLabel'],
            'dayLabel' => $range['dayLabel'],
            'date' => $range['date'],
            'month' => $range['month'],
            'dateFrom' => $range['dateFromParam'],
            'dateTo' => $range['dateToParam'],
            'teamRank' => ! empty($scoreRow['rank']) ? (int) $scoreRow['rank'] : null,
            'scoreRow' => $scoreRow,
            'fullReport' => $fullReport,
            'scoreWeights' => EmployeeProgressScoreService::weightLegend(),
        ]);
    }

    /**
     * Full ranking marks report (team scores with breakdown tables).
     *
     * @throws AuthorizationException
     */
    public function rankingReport(Request $request): Renderable
    {
        $scope = $this->resolveScope($request);
        $teamEmployees = $this->employeeDashboard->dashboardEmployeeCollection();
        if ($teamEmployees->isEmpty()) {
            $teamEmployees = User::query()
                ->where('user_type', 'admin-employee')
                ->ofStatus(1)
                ->get();
        }

        $period = $request->input('period') === 'monthly' ? 'monthly' : 'daily';
        if ($period === 'monthly') {
            [$dateFrom, $dateTo] = $this->resolveMonthRange($request);
            $periodLabel = $dateFrom->format('d M').' – '.$dateTo->format('d M Y');
        } else {
            $date = $this->resolveSingleDate($request->input('date'));
            $dateFrom = $date;
            $dateTo = $date;
            $periodLabel = $date->format('d M Y');
        }

        $teamRankRows = $this->employeeDashboard->teamOverallRankRows($teamEmployees, $dateFrom, $dateTo);
        $highlightEmployeeId = $scope['user']
            ? (string) $scope['user']->id
            : (string) $request->input('employee_id', '');

        if ($highlightEmployeeId === '__all__') {
            $highlightEmployeeId = '';
        }

        $rankMetricPeriodParams = $period === 'monthly'
            ? ['period' => 'monthly', 'date_from' => $dateFrom->toDateString(), 'date_to' => $dateTo->toDateString()]
            : ['period' => 'daily', 'date' => $dateFrom->toDateString()];

        $employeeOptions = $this->employeeOptionsForViewer();
        $pageTitle = translate('Progress_ranking_marks_report') ?? 'Ranking marks report';

        return view('adminmodule::employee-progress-ranking-report', [
            'user' => $scope['user'],
            'viewingAllEmployees' => $scope['viewing_all'],
            'viewingAsAdmin' => $scope['viewing_as_admin'],
            'employeeQuery' => $scope['employee_query'],
            'employeeOptions' => $employeeOptions,
            'period' => $period,
            'periodLabel' => $periodLabel,
            'date' => $period === 'daily' ? $dateFrom->toDateString() : null,
            'dateFrom' => $period === 'monthly' ? $dateFrom->toDateString() : null,
            'dateTo' => $period === 'monthly' ? $dateTo->toDateString() : null,
            'teamRankRows' => $teamRankRows,
            'highlightEmployeeId' => $highlightEmployeeId !== '' ? $highlightEmployeeId : null,
            'scoreWeights' => EmployeeProgressScoreService::weightLegend(),
            'rankMetricPeriodParams' => $rankMetricPeriodParams,
            'rankMetricEmployeeQuery' => $scope['employee_query'],
            'pageTitle' => $pageTitle,
            'backUrl' => route('admin.dashboard'),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function rankMetricDetail(Request $request): Renderable
    {
        $scope = $this->resolveScope($request);
        $metricKey = (string) $request->input('metric', '');

        if (! in_array($metricKey, EmployeeProgressRankMetricDetailService::validMetricKeys(), true)) {
            abort(404);
        }

        $employeeId = (string) $request->input('employee_id', '');
        if ($employeeId === '' && $scope['user']) {
            $employeeId = (string) $scope['user']->id;
        }

        if ($employeeId === '') {
            abort(404);
        }

        if (is_admin_employee() && (string) auth()->id() !== $employeeId) {
            abort(403);
        }

        $range = $this->resolveRankingPeriod($request);
        $dateFrom = $range['dateFrom'];
        $dateTo = $range['dateTo'];

        /** @var User $employee */
        $employee = User::query()
            ->where('user_type', 'admin-employee')
            ->findOrFail($employeeId);

        $detail = $this->rankMetricDetailService->build($metricKey, $employeeId, $dateFrom, $dateTo);
        $employeeName = trim((string) $employee->first_name.' '.(string) $employee->last_name);
        if ($employeeName === '') {
            $employeeName = (string) $employee->email;
        }

        $backParams = array_filter(array_merge(
            $scope['employee_query'],
            $this->rankingPeriodQueryParams($range),
            ['section' => 'overview'],
        ));

        $employeeOptions = $this->employeeOptionsForRankingReport();

        return view('adminmodule::employee-progress-rank-metric-detail', [
            'user' => $scope['user'],
            'employee' => $employee,
            'employeeName' => $employeeName,
            'viewingAsAdmin' => $scope['viewing_as_admin'],
            'employeeQuery' => $scope['employee_query'],
            'employeeOptions' => $employeeOptions,
            'metric' => $metricKey,
            'period' => $range['period'],
            'periodLabel' => $range['periodLabel'],
            'dayLabel' => $range['dayLabel'],
            'date' => $range['date'],
            'month' => $range['month'],
            'dateFrom' => $range['dateFromParam'],
            'dateTo' => $range['dateToParam'],
            'detail' => $detail,
            'backUrl' => route('admin.my-progress', $backParams),
        ]);
    }

    /**
     * @return array{
     *     employees: Collection<int, User>,
     *     user: User|null,
     *     viewing_all: bool,
     *     viewing_as_admin: bool,
     *     employee_query: array<string, string>
     * }
     *
     * @throws AuthorizationException
     */
    private function resolveScope(Request $request): array
    {
        if (is_admin_employee()) {
            /** @var User $user */
            $user = auth()->user();

            return [
                'employees' => collect([$user]),
                'user' => $user,
                'viewing_all' => false,
                'viewing_as_admin' => false,
                'employee_query' => [],
            ];
        }

        $this->authorize('report_view');

        $allEmployees = $this->employeeDashboard->dashboardEmployeeCollection();
        if ($allEmployees->isEmpty()) {
            abort(404);
        }

        $employeeId = (string) $request->input('employee_id', '__all__');

        if ($employeeId === '__all__') {
            return [
                'employees' => $allEmployees,
                'user' => null,
                'viewing_all' => true,
                'viewing_as_admin' => true,
                'employee_query' => ['employee_id' => '__all__'],
            ];
        }

        /** @var User $employee */
        $employee = User::query()
            ->where('user_type', 'admin-employee')
            ->findOrFail($employeeId);

        return [
            'employees' => collect([$employee]),
            'user' => $employee,
            'viewing_all' => false,
            'viewing_as_admin' => true,
            'employee_query' => ['employee_id' => (string) $employee->id],
        ];
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function employeeOptionsForViewer(): array
    {
        if (is_admin_employee()) {
            return [];
        }

        $options = [
            [
                'id' => '__all__',
                'name' => translate('All_Employees'),
            ],
        ];

        $employees = User::query()
            ->where('user_type', 'admin-employee')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->map(function (User $employee): array {
                $name = trim((string) $employee->first_name.' '.(string) $employee->last_name);

                return [
                    'id' => (string) $employee->id,
                    'name' => $name !== '' ? $name : (string) $employee->email,
                ];
            })
            ->values()
            ->all();

        return array_merge($options, $employees);
    }

    /**
     * Employee picker for ranking marks report (no "all employees" entry).
     *
     * @return list<array{id: string, name: string}>
     */
    private function employeeOptionsForRankingReport(): array
    {
        if (is_admin_employee()) {
            return [];
        }

        return User::query()
            ->where('user_type', 'admin-employee')
            ->ofStatus(1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->map(function (User $employee): array {
                $name = trim((string) $employee->first_name.' '.(string) $employee->last_name);

                return [
                    'id' => (string) $employee->id,
                    'name' => $name !== '' ? $name : (string) $employee->email,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Attach employee-vs-all contribution totals (mine / team) when viewing a single employee.
     *
     * @param  Collection<int, User>  $employees
     * @param  array<string, mixed>  $scopedTotals
     * @param  array<string, mixed>  $analytics
     * @param  array<string, mixed>  $leadAnalytics
     * @param  array<string, mixed>  $followupAnalytics
     * @return array{0: array<string, int>, 1: array<string, mixed>, 2: array<string, mixed>, 3: array<string, mixed>}
     */
    private function withContributionTotals(
        Collection $employees,
        Carbon $dateFrom,
        Carbon $dateTo,
        bool $viewingAll,
        array $scopedTotals,
        array $analytics,
        array $leadAnalytics,
        array $followupAnalytics,
    ): array {
        if ($viewingAll) {
            return [
                [],
                $this->contributionTotals->clearBookingContribution($analytics),
                $this->contributionTotals->clearLeadContribution($leadAnalytics),
                $this->contributionTotals->clearFollowupContribution($followupAnalytics),
            ];
        }

        $teamEmployees = $this->employeeDashboard->dashboardEmployeeCollection();
        if ($teamEmployees->isEmpty()) {
            $teamEmployees = User::query()
                ->where('user_type', 'admin-employee')
                ->get();
        }

        if ($teamEmployees->count() <= 1) {
            return [
                DailyEmployeeReportService::withDerivedActivityMetrics($scopedTotals),
                $this->contributionTotals->clearBookingContribution($analytics),
                $this->contributionTotals->clearLeadContribution($leadAnalytics),
                $this->contributionTotals->clearFollowupContribution($followupAnalytics),
            ];
        }

        $teamReport = $this->reportService->buildReport($teamEmployees, $dateFrom, $dateTo);
        $activityTeamTotals = DailyEmployeeReportService::withDerivedActivityMetrics($teamReport['totals'] ?? []);
        $teamFullReport = $this->employeeDashboard->progressFullReportForEmployees(
            $teamEmployees,
            $dateFrom,
            $dateTo,
            $activityTeamTotals,
        );
        $teamAnalytics = $this->progressAnalytics->build(
            $teamEmployees,
            $dateFrom,
            $dateTo,
            $teamReport,
            $teamFullReport,
            true,
            ['detail' => null],
        );
        $teamLeadAnalytics = $this->leadProgressAnalytics->build($teamEmployees, $dateFrom, $dateTo);
        $teamFollowupAnalytics = $this->followupProgressAnalytics->build(
            $teamEmployees,
            $dateFrom,
            $dateTo,
            $teamFullReport,
        );

        return [
            $activityTeamTotals,
            $this->contributionTotals->attachBookingAnalytics($analytics, $teamAnalytics),
            $this->contributionTotals->attachLeadAnalytics($leadAnalytics, $teamLeadAnalytics),
            $this->contributionTotals->attachFollowupAnalytics($followupAnalytics, $teamFollowupAnalytics),
        ];
    }

    /**
     * Day-wise activity rows for the Daily Basis report (aggregates team when viewing all).
     *
     * @param  list<array<string, mixed>>  $dailyRows
     * @return list<array<string, mixed>>
     */
    private function buildActivityDailyRows(array $dailyRows, bool $viewingAll): array
    {
        if ($dailyRows === []) {
            return [];
        }

        if (! $viewingAll) {
            return collect($dailyRows)
                ->map(fn (array $row) => DailyEmployeeReportService::withDerivedActivityMetrics($row))
                ->sortByDesc('date')
                ->values()
                ->all();
        }

        $metricKeys = array_column(DailyEmployeeReportService::activityMetricColumns(), 'key');
        $grouped = [];

        foreach ($dailyRows as $row) {
            $date = (string) ($row['date'] ?? '');
            if ($date === '') {
                continue;
            }

            if (! isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'date_label' => $row['date_label'] ?? $date,
                    'online_seconds' => 0,
                    'has_activity' => false,
                ];
                foreach ($metricKeys as $key) {
                    $grouped[$date][$key] = 0;
                }
                foreach (DailyEmployeeReportService::METRIC_KEYS as $key) {
                    $grouped[$date][$key] = 0;
                }
            }

            $grouped[$date]['online_seconds'] += (int) ($row['online_seconds'] ?? 0);
            if (! empty($row['has_activity'])) {
                $grouped[$date]['has_activity'] = true;
            }

            foreach (DailyEmployeeReportService::METRIC_KEYS as $key) {
                $grouped[$date][$key] += (int) ($row[$key] ?? 0);
            }
        }

        $rows = array_map(function (array $row) {
            $row = DailyEmployeeReportService::withDerivedActivityMetrics($row);
            $seconds = (int) ($row['online_seconds'] ?? 0);
            $hours = intdiv($seconds, 3600);
            $minutes = intdiv($seconds % 3600, 60);
            $row['online_hours'] = $seconds <= 0
                ? '0m'
                : ($hours > 0 ? sprintf('%dh %dm', $hours, $minutes) : sprintf('%dm', max($minutes, 1)));

            return $row;
        }, array_values($grouped));

        usort($rows, fn (array $a, array $b) => strcmp((string) $b['date'], (string) $a['date']));

        return $rows;
    }

    /**
     * @return list<array{key: string, label: string, short: string, group: string}>
     */
    private function metricColumns(): array
    {
        return [
            ['key' => 'bookings_created', 'label' => translate('Bookings_Created'), 'short' => translate('Bookings_Created_short'), 'group' => 'bookings'],
            ['key' => 'outbound_enquiries', 'label' => translate('Outbound_Enquiries'), 'short' => translate('Outbound_short'), 'group' => 'other'],
        ];
    }

    /**
     * @return list<array{key: string, title: string, columns: list<array{key: string, label: string}>}>
     */
    private function detailSectionDefs(): array
    {
        return [
            [
                'key' => 'bookings_created',
                'title' => translate('Bookings_Created'),
                'columns' => [
                    ['key' => 'readable_id', 'label' => translate('Booking_ID')],
                    ['key' => 'customer', 'label' => translate('Customer')],
                    ['key' => 'phone', 'label' => translate('Phone')],
                    ['key' => 'status', 'label' => translate('Status')],
                    ['key' => 'from_lead', 'label' => translate('From_Lead')],
                    ['key' => 'at', 'label' => translate('Time')],
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     period: string,
     *     dateFrom: Carbon,
     *     dateTo: Carbon,
     *     periodLabel: string,
     *     dayLabel: string|null,
     *     date: string|null,
     *     month: string|null,
     *     dateFromParam: string|null,
     *     dateToParam: string|null,
     * }
     */
    private function resolveRankingPeriod(Request $request): array
    {
        $period = (string) $request->input('period', 'daily');
        if (! in_array($period, ['daily', 'monthly', 'custom'], true)) {
            $period = 'daily';
        }

        if ($period === 'daily') {
            $dateFrom = $this->resolveSingleDate($request->input('date'));

            return [
                'period' => 'daily',
                'dateFrom' => $dateFrom,
                'dateTo' => $dateFrom->copy(),
                'periodLabel' => $dateFrom->format('l, d M Y'),
                'dayLabel' => $dateFrom->format('l'),
                'date' => $dateFrom->toDateString(),
                'month' => null,
                'dateFromParam' => null,
                'dateToParam' => null,
            ];
        }

        if ($period === 'monthly') {
            $month = (string) $request->input('month', '');
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $dateFrom = Carbon::parse($month.'-01')->startOfDay();
            } elseif ($request->filled('date_from')) {
                try {
                    $dateFrom = Carbon::parse((string) $request->input('date_from'))->startOfMonth()->startOfDay();
                } catch (\Throwable) {
                    $dateFrom = Carbon::today()->startOfMonth();
                }
            } else {
                $dateFrom = Carbon::today()->startOfMonth();
            }

            $dateTo = $dateFrom->copy()->endOfMonth()->startOfDay();

            return [
                'period' => 'monthly',
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'periodLabel' => $dateFrom->format('F Y'),
                'dayLabel' => null,
                'date' => null,
                'month' => $dateFrom->format('Y-m'),
                'dateFromParam' => $dateFrom->toDateString(),
                'dateToParam' => $dateTo->toDateString(),
            ];
        }

        [$dateFrom, $dateTo] = $this->resolveMonthRange($request);

        return [
            'period' => 'custom',
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'periodLabel' => $dateFrom->format('d M').' – '.$dateTo->format('d M Y'),
            'dayLabel' => null,
            'date' => null,
            'month' => null,
            'dateFromParam' => $dateFrom->toDateString(),
            'dateToParam' => $dateTo->toDateString(),
        ];
    }

    /**
     * @param  array{
     *     period: string,
     *     date: string|null,
     *     month: string|null,
     *     dateFromParam: string|null,
     *     dateToParam: string|null,
     * }  $range
     * @return array<string, string>
     */
    private function rankingPeriodQueryParams(array $range): array
    {
        if ($range['period'] === 'monthly') {
            return array_filter([
                'tab' => 'monthly',
                'month' => $range['month'],
                'date_from' => $range['dateFromParam'],
                'date_to' => $range['dateToParam'],
            ]);
        }

        if ($range['period'] === 'custom') {
            return array_filter([
                'tab' => 'monthly',
                'period' => 'custom',
                'date_from' => $range['dateFromParam'],
                'date_to' => $range['dateToParam'],
            ]);
        }

        return array_filter([
            'tab' => 'daily',
            'date' => $range['date'],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveMonthRange(Request $request): array
    {
        $from = $request->input('date_from');
        $to = $request->input('date_to');

        if ($from && $to) {
            try {
                $start = Carbon::parse($from)->startOfDay();
                $end = Carbon::parse($to)->endOfDay();

                if ($start->gt($end)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }

                if ($start->diffInDays($end) > 90) {
                    $end = $start->copy()->addDays(90)->endOfDay();
                }

                return [$start->copy()->startOfDay(), $end->copy()->startOfDay()];
            } catch (\Throwable) {
                // fall through
            }
        }

        $today = Carbon::today();

        return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()->startOfDay()];
    }

    private function resolveSingleDate(?string $date): Carbon
    {
        if (! $date) {
            return Carbon::today();
        }

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            return Carbon::today();
        }
    }
}
