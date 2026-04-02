<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\UserManagement\Entities\User;

class LeadFollowupController extends Controller
{
    public function todaysFollowups(Request $request): Renderable
    {
        $selectedHandledById = (string) $request->input('handled_by', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $effectiveTo = Carbon::today()->toDateString();
        if ($dateTo) {
            try {
                $parsedTo = Carbon::parse($dateTo)->toDateString();
                $effectiveTo = $parsedTo > Carbon::today()->toDateString() ? Carbon::today()->toDateString() : $parsedTo;
            } catch (\Throwable $e) {
                $effectiveTo = Carbon::today()->toDateString();
            }
        }

        $baseQuery = Lead::query()
            ->whereNotNull('next_followup_at')
            // Include missed follow-ups from previous days up to and including today.
            ->whereDate('next_followup_at', '<=', $effectiveTo)
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('next_followup_at', '>=', $dateFrom);
            })
            ->when($selectedHandledById !== '', function ($q) use ($selectedHandledById) {
                if ($selectedHandledById === Lead::FILTER_UNASSIGNED_VALUE) {
                    $q->where(function ($sub) {
                        $sub->whereNull('handled_by')
                            ->orWhere('handled_by', '')
                            ->orWhere('handled_by', Lead::HANDLED_BY_AI);
                    });
                } else {
                    $q->where('handled_by', $selectedHandledById);
                }
            });

        app(LeadOpenStatusService::class)->restrictQueryToOpenLeads($baseQuery);

        $totalFollowups = (clone $baseQuery)->count();

        $leads = (clone $baseQuery)
            ->with(['source', 'adSource'])
            // Sort from previous to current.
            ->orderBy('next_followup_at')
            ->paginate(pagination_limit())
            ->appends($request->query());

        // Used for displaying assignee names in Blade.
        $handledByIds = $leads->pluck('handled_by')->filter()->unique()->values()->all();
        $handledByUsers = $handledByIds !== []
            ? User::whereIn('id', $handledByIds)->get(['id', 'first_name', 'last_name', 'email'])->keyBy(fn (User $u) => (string) $u->id)
            : collect();

        foreach ($leads as $lead) {
            if (!Lead::assigneeIsHuman($lead->handled_by)) {
                $lead->handled_by_name = translate('Unassigned');
                continue;
            }
            $user = $handledByUsers->get((string) $lead->handled_by);
            $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';
            $lead->handled_by_name = $fullName ?: ($user->email ?? translate('Unassigned'));
        }

        $assignees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
            ->ofStatus(1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        return view('leadmanagement::admin.leads.todays-followups', compact(
            'leads',
            'assignees',
            'selectedHandledById',
            'dateFrom',
            'dateTo',
            'totalFollowups'
        ));
    }
}

