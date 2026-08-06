<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Services\ImpersonationService;
use Modules\UserManagement\Entities\User;

class ImpersonationController extends Controller
{
    public function __construct(private readonly ImpersonationService $impersonation)
    {
    }

    public function start(string $id): RedirectResponse
    {
        $actor = auth()->user();
        if (! $actor || ! $this->impersonation->canStart($actor)) {
            Toastr::error(translate('Access_denied'));

            return redirect()->route('admin.dashboard');
        }

        $employee = User::query()
            ->where('id', $id)
            ->where('user_type', 'admin-employee')
            ->first();

        if (! $employee) {
            Toastr::error(translate('no_data_available'));

            return redirect()->back();
        }

        if (! $employee->is_active) {
            Toastr::error(translate('Cannot_view_as_inactive_employee'));

            return redirect()->back();
        }

        $role = $employee->roles()->first();
        if (! $role || ! $role->is_active) {
            Toastr::error(translate('Cannot_view_as_employee_without_active_role'));

            return redirect()->back();
        }

        $this->impersonation->start($actor, $employee);

        Toastr::success(translate('Now_viewing_as_employee').': '.trim($employee->first_name.' '.$employee->last_name));

        return redirect()->to($this->impersonation->landingUrl($employee));
    }

    public function leave(): RedirectResponse
    {
        if (! $this->impersonation->isActive()) {
            return redirect()->route('admin.dashboard');
        }

        $impersonator = $this->impersonation->leave();
        if (! $impersonator) {
            Toastr::warning(translate('Access_denied'));

            return redirect()->route('admin.auth.login');
        }

        Toastr::success(translate('Exited_employee_view'));

        return redirect()->route('admin.dashboard');
    }
}
