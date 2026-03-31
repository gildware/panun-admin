<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\AdminModule\Services\Maintenance\OperationalDataResetService;

class SystemMaintenanceController extends Controller
{
    public function index()
    {
        $this->authorizeAccess();

        return view('adminmodule::admin.maintenance.data-reset');
    }

    public function reset(Request $request, OperationalDataResetService $resetService): RedirectResponse
    {
        $this->authorizeAccess();

        $request->validate([
            'confirm' => 'required|in:RESET',
        ]);

        $resetService->reset();

        Toastr::success(translate('Operational_data_has_been_cleared_successfully'));

        return redirect()->route('admin.dashboard');
    }

    private function authorizeAccess(): void
    {
        if (! Gate::allows('backup_view') && ! Gate::allows('business_view') && ! Gate::allows('booking_view')) {
            abort(403);
        }
    }
}

