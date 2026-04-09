<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\AdminModule\Services\Maintenance\OperationalDataResetService;
use Modules\AdminModule\Services\Maintenance\WhatsAppOperationalDataResetService;

class SystemMaintenanceController extends Controller
{
    public function index()
    {
        $this->authorizeAccess();

        return view('adminmodule::admin.maintenance.data-reset');
    }

    public function reset(
        Request $request,
        OperationalDataResetService $resetService,
        WhatsAppOperationalDataResetService $whatsAppResetService
    ): RedirectResponse {
        $this->authorizeAccess();

        if ($request->input('reset_form') === 'whatsapp') {
            $request->validate([
                'confirm' => 'required|in:RESET',
            ]);

            $options = [
                'all' => $request->boolean('whatsapp_all'),
                'messages' => $request->boolean('whatsapp_messages'),
                'human_support' => $request->boolean('whatsapp_human_support'),
                'provider_leads' => $request->boolean('whatsapp_provider_leads'),
                'bookings' => $request->boolean('whatsapp_bookings'),
                'users' => $request->boolean('whatsapp_users'),
            ];

            if (! $options['all'] && ! array_filter(array_diff_key($options, ['all' => true]))) {
                throw ValidationException::withMessages([
                    'whatsapp_scope' => translate('Select_at_least_one_WhatsApp_data_option'),
                ]);
            }

            $whatsAppResetService->reset($options);

            Toastr::success(translate('Selected_WhatsApp_data_has_been_cleared'));

            return redirect()->route('admin.system-maintenance.data-reset.index');
        }

        if ($request->input('reset_form') === 'financial') {
            $request->validate([
                'confirm' => 'required|in:RESET',
            ]);

            $resetService->resetFinancialRecordsOnly();

            Toastr::success(translate('Financial_records_cleared_successfully'));

            return redirect()->route('admin.system-maintenance.data-reset.index');
        }

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

