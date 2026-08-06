<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin\Report;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DailyEmployeeReportController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('admin.my-progress', array_filter([
            'tab' => 'monthly',
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ]));
    }

    public function detail(Request $request): RedirectResponse
    {
        $employeeId = $request->input('employee_id');
        $employeeIds = (array) $request->input('employee_ids', []);
        if (! $employeeId && $employeeIds !== []) {
            $employeeId = $employeeIds[0];
        }

        return redirect()->route('admin.my-progress', array_filter([
            'tab' => 'daily',
            'date' => $request->input('date'),
            'employee_id' => $employeeId,
        ]));
    }
}
