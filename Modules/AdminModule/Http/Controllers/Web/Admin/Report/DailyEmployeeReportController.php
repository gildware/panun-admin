<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin\Report;

use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Services\Report\DailyEmployeeReportService;

class DailyEmployeeReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly DailyEmployeeReportService $reportService)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): Renderable
    {
        $this->authorize('lead_report_view');

        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $selectedEmployeeIds = array_values(array_filter((array) $request->input('employee_ids', [])));

        $filterEmployees = $this->reportService->loadEmployees();
        $employees = $this->reportService->loadEmployees(
            $selectedEmployeeIds !== [] ? $selectedEmployeeIds : null
        );

        $report = $this->reportService->buildReport($employees, $dateFrom, $dateTo);

        return view('adminmodule::admin.report.daily-employee', [
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
            'filterEmployees' => $filterEmployees,
            'selectedEmployeeIds' => $selectedEmployeeIds,
            'rows' => $report['rows'],
            'totals' => $report['totals'],
            'employeeTotals' => $report['employee_totals'],
            'employeeCount' => $employees->count(),
            'viewMode' => $request->input('view_mode', 'daily') === 'summary' ? 'summary' : 'daily',
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(Request $request): array
    {
        $from = $request->input('date_from');
        $to = $request->input('date_to');

        if (! $from || ! $to) {
            $today = Carbon::today();

            return [$today->copy(), $today->copy()];
        }

        try {
            $start = Carbon::parse($from)->startOfDay();
            $end = Carbon::parse($to)->endOfDay();
        } catch (\Throwable) {
            $today = Carbon::today();

            return [$today->copy(), $today->copy()];
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        } else {
            $end = $end->copy()->endOfDay();
            $start = $start->copy()->startOfDay();
        }

        $maxDays = 90;
        if ($start->diffInDays($end) > $maxDays) {
            $end = $start->copy()->addDays($maxDays)->endOfDay();
        }

        return [$start, $end->copy()->startOfDay()];
    }
}
