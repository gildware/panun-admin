<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Entities\PeoplePayslip;
use Modules\AdminModule\Services\PeopleWorkspace;

class AccountsPayrollController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private PeopleWorkspace $workspace)
    {
    }

    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if ($user && $this->workspace->isHr($user)) {
            return app(PeopleHrController::class)->index($request);
        }

        $this->authorize('ledger_view');

        $period = (string) $request->query('period', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = now()->format('Y-m');
        }

        $slips = PeoplePayslip::query()
            ->forPayroll()
            ->with('user:id,first_name,last_name')
            ->where('period', $period)
            ->where('status', 'published')
            ->where('held', false)
            ->get()
            ->sortBy(fn (PeoplePayslip $slip) => strtolower($slip->user ? $this->workspace->displayName($slip->user) : ''))
            ->values();

        return view('adminmodule::admin.accounts.payroll', [
            'period' => $period,
            'monthLabel' => Carbon::createFromFormat('Y-m', $period)->format('F Y'),
            'slips' => $slips,
            'headcount' => $slips->count(),
            'gross' => (float) $slips->sum('gross'),
            'deductions' => (float) $slips->sum('deductions'),
            'net' => (float) $slips->sum('net'),
            'workspace' => $this->workspace,
        ]);
    }
}
