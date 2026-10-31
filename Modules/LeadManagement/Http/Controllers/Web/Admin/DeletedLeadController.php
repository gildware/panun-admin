<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\UserManagement\Entities\User;

class DeletedLeadController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(is_super_admin(), 403);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $leads = Lead::onlyTrashed()
            ->with(['deletedByUser', 'source'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('phone_number', 'like', '%'.$search.'%')
                        ->orWhere('deletion_reason', 'like', '%'.$search.'%');
                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest('deleted_at')
            ->paginate(pagination_limit())
            ->withQueryString();

        $collection = $leads->getCollection();
        $leadStatusMeta = $collection->isEmpty()
            ? []
            : app(LeadOpenStatusService::class)->buildLeadStatusMeta($collection);

        $handledByIds = $collection->pluck('handled_by')
            ->filter(fn ($value) => Lead::assigneeIsHuman($value))
            ->unique()
            ->values()
            ->all();

        $handledByNames = [];
        if ($handledByIds !== []) {
            $users = User::query()->whereIn('id', $handledByIds)->get(['id', 'first_name', 'last_name', 'email']);
            foreach ($users as $user) {
                $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
                $handledByNames[(string) $user->id] = $fullName !== '' ? $fullName : $user->email;
            }
        }

        return view('leadmanagement::admin.leads.deleted', [
            'leads' => $leads,
            'search' => $search,
            'leadStatusMeta' => $leadStatusMeta,
            'handledByNames' => $handledByNames,
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        // withTrashed (not onlyTrashed): a second click / browser resubmit after a
        // successful restore used to 404 via findOrFail on an already-active lead.
        $lead = Lead::withTrashed()->find($id);
        if (! $lead) {
            toastr()->error(translate('Lead_not_found') ?: 'Lead not found.');

            return redirect()->route('admin.lead.deleted');
        }

        if (! $lead->trashed()) {
            toastr()->info(translate('Lead_already_restored') ?: 'This lead is already active.');

            return redirect()->route('admin.lead.show', $lead->id);
        }

        try {
            $lead->deleted_by = null;
            $lead->deletion_reason = null;
            $lead->restore();
        } catch (\Throwable $e) {
            report($e);
            toastr()->error(translate('Lead_restore_failed') ?: 'Could not restore this lead. Please try again.');

            return redirect()->route('admin.lead.deleted');
        }

        toastr()->success(translate('Lead_restored_successfully'));

        return redirect()->route('admin.lead.show', $lead->id);
    }

    public function forceDestroy(int $id): RedirectResponse
    {
        $lead = Lead::onlyTrashed()->find($id);
        if (! $lead) {
            $exists = Lead::withTrashed()->whereKey($id)->exists();
            toastr()->info(
                $exists
                    ? (translate('Lead_already_restored') ?: 'This lead is already active.')
                    : (translate('Lead_not_found') ?: 'Lead not found.')
            );

            return redirect()->route('admin.lead.deleted');
        }

        try {
            $lead->forceDelete();
        } catch (\Throwable $e) {
            report($e);
            toastr()->error(translate('Lead_delete_failed') ?: 'Could not permanently delete this lead. Please try again.');

            return redirect()->route('admin.lead.deleted');
        }

        toastr()->success(translate('Lead_deleted_permanently'));

        return redirect()->route('admin.lead.deleted');
    }
}
