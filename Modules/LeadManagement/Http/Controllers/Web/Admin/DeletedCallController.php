<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

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

    public function index(Request $request): RedirectResponse
    {
        $query = ['status' => 'deleted'];
        $search = trim((string) $request->input('search', $request->input('q', '')));
        if ($search !== '') {
            $query['q'] = $search;
        }

        return redirect()->route('admin.calls.index', $query);
    }

    public function restore(int $id): RedirectResponse
    {
        $call = SuperfoneCall::withTrashed()->find($id);
        if (! $call) {
            toastr()->error(translate('Call_not_found') ?: 'Call not found.');

            return redirect()->route('admin.calls.index', ['status' => 'deleted']);
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

            return redirect()->route('admin.calls.index', ['status' => 'deleted']);
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

            return redirect()->route('admin.calls.index', ['status' => 'deleted']);
        }

        try {
            $this->calls->forceDelete($call);
        } catch (\Throwable $e) {
            report($e);
            toastr()->error(translate('Call_delete_failed') ?: 'Could not permanently delete this call. Please try again.');

            return redirect()->route('admin.calls.index', ['status' => 'deleted']);
        }

        toastr()->success(translate('Call_deleted_permanently'));

        return redirect()->route('admin.calls.index', ['status' => 'deleted']);
    }
}
