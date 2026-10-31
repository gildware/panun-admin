<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LeadManagement\Entities\SuperfoneCall;
use Modules\LeadManagement\Services\SuperfoneCallService;

class DeletedCallController extends Controller
{
    public function __construct(private readonly SuperfoneCallService $calls)
    {
        $this->middleware(function ($request, $next) {
            abort_unless(is_super_admin(), 403);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $calls = SuperfoneCall::onlyTrashed()
            ->with([
                'deletedByUser:id,first_name,last_name,email,phone',
                'lead:id,name,phone_number,lead_type',
                'employee:id,first_name,last_name',
                'provider:id,company_name,contact_person_name',
                'booking:id,readable_id,booking_status',
                'customer:id,first_name,last_name,phone',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('customer_phone', 'like', '%'.$search.'%')
                        ->orWhere('customer_phone_digits', 'like', '%'.$search.'%')
                        ->orWhere('staff_name', 'like', '%'.$search.'%')
                        ->orWhere('cdr_uuid', 'like', '%'.$search.'%');
                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest('deleted_at')
            ->paginate(pagination_limit())
            ->withQueryString();

        return view('leadmanagement::admin.calls.deleted', [
            'calls' => $calls,
            'search' => $search,
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        $call = SuperfoneCall::withTrashed()->find($id);
        if (! $call) {
            toastr()->error(translate('Call_not_found') ?: 'Call not found.');

            return redirect()->route('admin.calls.deleted');
        }

        if (! $call->trashed()) {
            toastr()->info(translate('Call_already_restored') ?: 'This call is already active.');

            return redirect()->route('admin.calls.show', $call);
        }

        try {
            $this->calls->restore($call);
        } catch (\Throwable $e) {
            report($e);
            toastr()->error(translate('Call_restore_failed') ?: 'Could not restore this call. Please try again.');

            return redirect()->route('admin.calls.deleted');
        }

        toastr()->success(translate('Call_restored_successfully'));

        return redirect()->route('admin.calls.show', $call->id);
    }

    public function forceDestroy(int $id): RedirectResponse
    {
        $call = SuperfoneCall::onlyTrashed()->find($id);
        if (! $call) {
            $exists = SuperfoneCall::withTrashed()->whereKey($id)->exists();
            toastr()->info(
                $exists
                    ? (translate('Call_already_restored') ?: 'This call is already active.')
                    : (translate('Call_not_found') ?: 'Call not found.')
            );

            return redirect()->route('admin.calls.deleted');
        }

        try {
            $this->calls->forceDelete($call);
        } catch (\Throwable $e) {
            report($e);
            toastr()->error(translate('Call_delete_failed') ?: 'Could not permanently delete this call. Please try again.');

            return redirect()->route('admin.calls.deleted');
        }

        toastr()->success(translate('Call_deleted_permanently'));

        return redirect()->route('admin.calls.deleted');
    }
}
