<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\AdminModule\Services\Maintenance\AdminCustomerDeletionService;
use Modules\AdminModule\Services\Maintenance\AdminProviderDeletionService;
use Modules\AdminModule\Services\Maintenance\OperationalDataResetService;
use Modules\AdminModule\Services\Maintenance\WhatsAppOperationalDataResetService;

class SystemMaintenanceController extends Controller
{
    private function allowLongMaintenanceRun(): void
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
    }

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
        $this->allowLongMaintenanceRun();

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

        if (in_array($request->input('reset_form'), ['providers', 'customers'], true)) {
            abort(400, 'Use progressive reset endpoints for provider and customer bulk delete.');
        }

        $request->validate([
            'confirm' => 'required|in:RESET',
        ]);

        $resetService->reset();

        Toastr::success(translate('Operational_data_has_been_cleared_successfully'));

        return redirect()->route('admin.dashboard');
    }

    public function progressInit(
        Request $request,
        AdminProviderDeletionService $providerDeletionService,
        AdminCustomerDeletionService $customerDeletionService
    ): JsonResponse {
        $this->authorizeAccess();
        $this->allowLongMaintenanceRun();

        try {
            $request->validate([
                'confirm' => 'required|in:RESET',
                'type' => 'required|in:providers,customers',
            ]);

            if (env('APP_ENV') === 'demo') {
                return response()->json([
                    'ok' => false,
                    'message' => 'This function is disabled for demo mode',
                ], 403);
            }

            if ($request->input('type') === 'providers') {
                return response()->json([
                    'ok' => true,
                    'type' => 'providers',
                    'total' => $providerDeletionService->countProviders(),
                    'skipped' => 0,
                ]);
            }

            return response()->json([
                'ok' => true,
                'type' => 'customers',
                'total' => $customerDeletionService->countDeletableCustomers(),
                'skipped' => $customerDeletionService->countSkippedCustomers(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage() ?: translate('Operation_failed_try_again'),
            ], 500);
        }
    }

    public function progressStep(
        Request $request,
        AdminProviderDeletionService $providerDeletionService,
        AdminCustomerDeletionService $customerDeletionService
    ): JsonResponse {
        $this->authorizeAccess();
        $this->allowLongMaintenanceRun();

        try {
            $request->validate([
                'type' => 'required|in:providers,customers',
                'total' => 'required|integer|min:0',
                'current' => 'required|integer|min:0',
                'skipped' => 'nullable|integer|min:0',
            ]);

            if (env('APP_ENV') === 'demo') {
                return response()->json([
                    'ok' => false,
                    'message' => 'This function is disabled for demo mode',
                ], 403);
            }

            $total = (int) $request->input('total');
            $current = (int) $request->input('current');
            $skipped = (int) $request->input('skipped', 0);

            if ($request->input('type') === 'providers') {
                $result = $providerDeletionService->deleteNextProvider($total, $current);

                return response()->json([
                    'ok' => true,
                    'type' => 'providers',
                    'complete' => $result['complete'],
                    'current' => $result['current'],
                    'total' => $result['total'],
                    'label' => $result['label'],
                    'message' => $result['complete']
                        ? translate('All_providers_and_related_data_have_been_deleted')
                        : null,
                ]);
            }

            $result = $customerDeletionService->deleteNextCustomer($total, $current, $skipped);

            $message = null;
            if ($result['complete']) {
                $message = translate('All_customers_and_related_data_have_been_deleted').' ('.$result['current'].')';
                if ($result['skipped'] > 0) {
                    $message .= '. '.translate('Customers_linked_to_provider_businesses_were_skipped').' ('.$result['skipped'].')';
                }
            }

            return response()->json([
                'ok' => true,
                'type' => 'customers',
                'complete' => $result['complete'],
                'current' => $result['current'],
                'total' => $result['total'],
                'skipped' => $result['skipped'],
                'label' => $result['label'],
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage() ?: translate('Operation_failed_try_again'),
            ], 500);
        }
    }

    private function authorizeAccess(): void
    {
        if (! Gate::allows('backup_view') && ! Gate::allows('business_view') && ! Gate::allows('booking_view')) {
            abort(403);
        }
    }
}

