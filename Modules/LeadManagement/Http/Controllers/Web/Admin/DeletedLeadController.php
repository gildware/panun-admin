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
        $lead = Lead::onlyTrashed()->findOrFail($id);
        $lead->deleted_by = null;
        $lead->deletion_reason = null;
        $lead->restore();

        toastr()->success(translate('Lead_restored_successfully'));

        return redirect()->route('admin.lead.deleted');
    }

    public function forceDestroy(int $id): RedirectResponse
    {
        $lead = Lead::onlyTrashed()->findOrFail($id);
        $lead->forceDelete();

        toastr()->success(translate('Lead_deleted_permanently'));

        return redirect()->route('admin.lead.deleted');
    }
}
