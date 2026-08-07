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
