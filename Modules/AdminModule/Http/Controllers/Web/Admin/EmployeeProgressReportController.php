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
        if (! is_admin_employee()) {
            abort(403);
        }

        /** @var User $user */
        $user = auth()->user();
        $userId = (string) $user->id;
        $employees = collect([$user]);
        $tab = in_array($request->input('tab'), ['daily', 'monthly'], true)
            ? $request->input('tab')
            : 'daily';

        $metricColumns = $this->metricColumns();
        $sectionDefs = $this->detailSectionDefs();

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
        ]);
    }

    /**
     * @return list<array{key: string, label: string, short: string, group: string}>
     */
    private function metricColumns(): array
    {
        return [
            ['key' => 'leads_added', 'label' => translate('Leads_Added'), 'short' => translate('Leads_Added_short'), 'group' => 'leads'],
            ['key' => 'leads_assigned', 'label' => translate('Leads_Assigned'), 'short' => translate('Leads_Assigned_short'), 'group' => 'leads'],
            ['key' => 'lead_followups', 'label' => translate('Lead_Followups_Taken'), 'short' => translate('Lead_Followups_short'), 'group' => 'leads'],
            ['key' => 'bookings_created', 'label' => translate('Bookings_Created'), 'short' => translate('Bookings_Created_short'), 'group' => 'bookings'],
            ['key' => 'booking_followups', 'label' => translate('Booking_Followups_Taken'), 'short' => translate('Booking_Followups_short'), 'group' => 'bookings'],
            ['key' => 'booking_status_updates', 'label' => translate('Booking_Status_Updates'), 'short' => translate('Booking_Status_short'), 'group' => 'bookings'],
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
                'key' => 'leads_added',
                'title' => translate('Leads_Added'),
                'columns' => [
                    ['key' => 'name', 'label' => translate('Name')],
                    ['key' => 'phone', 'label' => translate('Phone')],
                    ['key' => 'lead_type', 'label' => translate('Type')],
                    ['key' => 'source', 'label' => translate('Lead_Source')],
                    ['key' => 'at', 'label' => translate('Time')],
                ],
            ],
            [
                'key' => 'leads_assigned',
                'title' => translate('Leads_Assigned'),
                'columns' => [
                    ['key' => 'name', 'label' => translate('Name')],
                    ['key' => 'phone', 'label' => translate('Phone')],
                    ['key' => 'from', 'label' => translate('Assigned_From')],
                    ['key' => 'employee', 'label' => translate('Employee')],
                    ['key' => 'at', 'label' => translate('Time')],
                ],
            ],
            [
                'key' => 'lead_followups',
                'title' => translate('Lead_Followups_Taken'),
                'columns' => [
                    ['key' => 'name', 'label' => translate('Lead')],
                    ['key' => 'phone', 'label' => translate('Phone')],
                    ['key' => 'remarks', 'label' => translate('Remarks')],
                    ['key' => 'urgency', 'label' => translate('Urgency')],
                    ['key' => 'at', 'label' => translate('Time')],
                ],
            ],
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
            [
                'key' => 'booking_followups',
                'title' => translate('Booking_Followups_Taken'),
                'columns' => [
                    ['key' => 'readable_id', 'label' => translate('Booking_ID')],
                    ['key' => 'reason', 'label' => translate('Reason')],
                    ['key' => 'for', 'label' => translate('For')],
                    ['key' => 'status', 'label' => translate('Status')],
                    ['key' => 'at', 'label' => translate('Time')],
                ],
            ],
            [
                'key' => 'booking_status_updates',
                'title' => translate('Booking_Status_Updates'),
                'columns' => [
                    ['key' => 'readable_id', 'label' => translate('Booking_ID')],
                    ['key' => 'status', 'label' => translate('Status')],
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
