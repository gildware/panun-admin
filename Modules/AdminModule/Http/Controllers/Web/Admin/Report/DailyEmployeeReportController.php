<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin\Report;

use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
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
        $employees = $this->reportService->loadEmployees();
        $report = $this->reportService->buildReport($employees, $dateFrom, $dateTo);

        return view('adminmodule::admin.report.daily-employee', [
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
            'rows' => $report['rows'],
            'totals' => $report['totals'],
            'employeeCount' => $employees->count(),
            'metricColumns' => $this->metricColumns(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function detail(Request $request): Renderable|JsonResponse
    {
        $this->authorize('lead_report_view');

        $date = $this->resolveSingleDate($request->input('date'));

        // Multi-select on detail page. Legacy single `employee_id` still supported.
        $focusEmployeeIds = array_values(array_filter(array_map('strval', (array) $request->input('employee_ids', []))));
        $legacyEmployeeId = (string) $request->input('employee_id', '');
        if ($focusEmployeeIds === [] && $legacyEmployeeId !== '' && $legacyEmployeeId !== 'all') {
            $focusEmployeeIds = [$legacyEmployeeId];
        }

        $filterEmployees = $this->reportService->loadEmployees();
        $employees = $filterEmployees;

        // Ensure any focused IDs not in the active list are still loaded.
        $missing = array_values(array_filter(
            $focusEmployeeIds,
            fn ($id) => ! $employees->contains(fn ($u) => (string) $u->id === $id)
        ));
        if ($missing !== []) {
            $employees = $employees->merge($this->reportService->loadEmployees($missing))->unique('id');
        }

        $detail = $this->reportService->buildDayDetail($employees, $date, $focusEmployeeIds);
        $metricColumns = $this->metricColumns();
        $sectionDefs = $this->detailSectionDefs();

        if ($request->boolean('ajax') || $request->ajax()) {
            return response()->json([
                'date' => $detail['date'],
                'date_label' => $detail['date_label'],
                'employee_ids' => $detail['employee_ids'],
                'employee_name' => $detail['employee_name'],
                'metrics_html' => view('adminmodule::admin.report.partials.daily-employee-detail-metrics', [
                    'metricColumns' => $metricColumns,
                    'totals' => $detail['totals'],
                ])->render(),
                'sections_html' => view('adminmodule::admin.report.partials.daily-employee-detail-sections', [
                    'sectionDefs' => $sectionDefs,
                    'sections' => $detail['sections'],
                ])->render(),
            ]);
        }

        return view('adminmodule::admin.report.daily-employee-detail', [
            'date' => $detail['date'],
            'dateLabel' => $detail['date_label'],
            'focusEmployeeIds' => $detail['employee_ids'],
            'employeeName' => $detail['employee_name'],
            'totals' => $detail['totals'],
            'sections' => $detail['sections'],
            'filterEmployees' => $filterEmployees,
            'metricColumns' => $metricColumns,
            'sectionDefs' => $sectionDefs,
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
            ['key' => 'whatsapp_assigned_from_ai', 'label' => translate('WA_Assigned_From_AI'), 'short' => translate('WA_From_AI_short'), 'group' => 'whatsapp'],
            ['key' => 'whatsapp_assigned_from_employee', 'label' => translate('WA_Assigned_From_Employee'), 'short' => translate('WA_From_Emp_short'), 'group' => 'whatsapp'],
            ['key' => 'whatsapp_chats_closed', 'label' => translate('WA_Chats_Closed'), 'short' => translate('WA_Closed_short'), 'group' => 'whatsapp'],
            ['key' => 'whatsapp_chats_replied', 'label' => translate('WA_Chats_Replied'), 'short' => translate('WA_Replied_short'), 'group' => 'whatsapp'],
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
                'key' => 'whatsapp_assigned_from_ai',
                'title' => translate('WA_Assigned_From_AI'),
                'columns' => [
                    ['key' => 'phone', 'label' => translate('Phone')],
                    ['key' => 'from', 'label' => translate('Assigned_From')],
                    ['key' => 'employee', 'label' => translate('Employee')],
                    ['key' => 'at', 'label' => translate('Time')],
                ],
            ],
            [
                'key' => 'whatsapp_assigned_from_employee',
                'title' => translate('WA_Assigned_From_Employee'),
                'columns' => [
                    ['key' => 'phone', 'label' => translate('Phone')],
                    ['key' => 'from', 'label' => translate('Assigned_From')],
                    ['key' => 'employee', 'label' => translate('Employee')],
                    ['key' => 'at', 'label' => translate('Time')],
                ],
            ],
            [
                'key' => 'whatsapp_chats_closed',
                'title' => translate('WA_Chats_Closed'),
                'columns' => [
                    ['key' => 'phone', 'label' => translate('Phone')],
                    ['key' => 'status', 'label' => translate('Status')],
                    ['key' => 'employee', 'label' => translate('Employee')],
                    ['key' => 'at', 'label' => translate('Time')],
                ],
            ],
            [
                'key' => 'whatsapp_chats_replied',
                'title' => translate('WA_Chats_Replied'),
                'columns' => [
                    ['key' => 'name', 'label' => translate('Name')],
                    ['key' => 'phone', 'label' => translate('Phone')],
                    ['key' => 'replies', 'label' => translate('Replies')],
                    ['key' => 'at', 'label' => translate('Last_Reply')],
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
