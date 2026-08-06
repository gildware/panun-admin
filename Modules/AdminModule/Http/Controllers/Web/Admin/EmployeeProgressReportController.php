<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Services\EmployeeDashboardService;
use Modules\AdminModule\Services\Report\DailyEmployeeReportService;
use Modules\UserManagement\Entities\User;

class EmployeeProgressReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DailyEmployeeReportService $reportService,
        private readonly EmployeeDashboardService $employeeDashboard,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): Renderable
    {
        $user = $this->resolveProgressReportUser($request);
        $userId = (string) $user->id;
        $employees = collect([$user]);
        $tab = in_array($request->input('tab'), ['daily', 'monthly'], true)
            ? $request->input('tab')
            : 'daily';

        $metricColumns = $this->metricColumns();
        $sectionDefs = $this->detailSectionDefs();
        $employeeOptions = $this->employeeOptionsForViewer();
        $viewingAsAdmin = ! is_admin_employee();
        $employeeQuery = $viewingAsAdmin ? ['employee_id' => $userId] : [];

        if ($tab === 'monthly') {
            [$dateFrom, $dateTo] = $this->resolveMonthRange($request);
            $report = $this->reportService->buildReport($employees, $dateFrom, $dateTo);
            $dailyRows = collect($report['rows'])
                ->filter(fn (array $row) => ! empty($row['has_activity']))
                ->values()
                ->all();
            $activityTotals = $report['employee_totals'][0] ?? [];
            $monthly = $this->employeeDashboard->monthlyPerformanceForUser($user, $dateFrom, $dateTo);
            $fullReport = $this->employeeDashboard->progressFullReportForUser(
                $user,
                $dateFrom,
                $dateTo,
                $activityTotals,
            );

            return view('adminmodule::employee-progress-report', [
                'tab' => 'monthly',
                'user' => $user,
                'dateFrom' => $dateFrom->toDateString(),
                'dateTo' => $dateTo->toDateString(),
                'periodLabel' => $dateFrom->format('d M').' – '.$dateTo->format('d M Y'),
                'metricColumns' => $metricColumns,
                'activityTotals' => $activityTotals,
                'dailyRows' => $dailyRows,
                'monthly' => $monthly,
                'fullReport' => $fullReport,
                'detail' => null,
                'date' => null,
                'employeeOptions' => $employeeOptions,
                'viewingAsAdmin' => $viewingAsAdmin,
                'employeeQuery' => $employeeQuery,
            ]);
        }

        $date = $this->resolveSingleDate($request->input('date'));
        $detail = $this->reportService->buildDayDetail($employees, $date, [$userId]);
        $fullReport = $this->employeeDashboard->progressFullReportForUser(
            $user,
            $date,
            $date,
            $detail['totals'] ?? [],
        );

        return view('adminmodule::employee-progress-report', [
            'tab' => 'daily',
            'user' => $user,
            'date' => $date->toDateString(),
            'dateLabel' => $detail['date_label'],
            'metricColumns' => $metricColumns,
            'sectionDefs' => $sectionDefs,
            'detail' => $detail,
            'dailyRows' => [],
            'activityTotals' => [],
            'monthly' => [],
            'fullReport' => $fullReport,
            'dateFrom' => null,
            'dateTo' => null,
            'periodLabel' => null,
            'employeeOptions' => $employeeOptions,
            'viewingAsAdmin' => $viewingAsAdmin,
            'employeeQuery' => $employeeQuery,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    private function resolveProgressReportUser(Request $request): User
    {
        if (is_admin_employee()) {
            /** @var User $user */
            $user = auth()->user();

            return $user;
        }

        $this->authorize('report_view');

        $employeeId = $request->input('employee_id');
        if (! $employeeId) {
            /** @var User|null $firstEmployee */
            $firstEmployee = User::query()
                ->where('user_type', 'admin-employee')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->first();

            if (! $firstEmployee) {
                abort(404);
            }

            return $firstEmployee;
        }

        /** @var User $employee */
        $employee = User::query()
            ->where('user_type', 'admin-employee')
            ->findOrFail($employeeId);

        return $employee;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function employeeOptionsForViewer(): array
    {
        if (is_admin_employee()) {
            return [];
        }

        return User::query()
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
