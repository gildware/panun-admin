<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\LeadManagement\Entities\LeadOutboundEnquiryStatus;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Entities\Source;
use Modules\LeadManagement\Entities\AdSource;
use Modules\BookingModule\Entities\Booking;
use Modules\UserManagement\Entities\User;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LeadReportController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): Renderable
    {
        $this->authorize('report_view');

        $tab = $request->input('tab', 'inbound');
        if (!in_array($tab, ['inbound', 'outbound', 'user'], true)) {
            $tab = 'inbound';
        }

        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        if ($tab === 'user') {
            $userId = $request->input('user_id');
            if (!$userId) {
                $userId = Auth::id();
            }

            $user = User::find($userId);
            $userName = $user
                ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->email ?? (string) $userId)
                : (string) $userId;

            $baseUserLeads = Lead::query()
                ->where('handled_by', $userId)
                ->with(['source', 'adSource'])
                ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('date_time_of_lead_received', [
                        $dateFrom->copy()->startOfDay(),
                        $dateTo->copy()->endOfDay(),
                    ]);
                });

            $userLeadsTotal = (clone $baseUserLeads)->count();
            $userLeadsForOpenClosed = (clone $baseUserLeads)->get(['id', 'lead_type']);
            $userOpenClosedSummary = $this->buildOpenClosedSummary($userLeadsForOpenClosed);

            $userLeadsByTypeRows = (clone $baseUserLeads)
                ->select('lead_type', DB::raw('count(*) as total'))
                ->groupBy('lead_type')
                ->get();

            $userLeadsByTypeMap = $userLeadsByTypeRows->mapWithKeys(function ($row) {
                return [(string) $row->lead_type => (int) $row->total];
            })->all();

            $leadsTimeline = [];
            $leadsPerDay = [];
            if ($dateFrom && $dateTo) {
                $period = CarbonPeriod::create($dateFrom->copy()->startOfDay(), $dateTo->copy()->startOfDay());
                $dailyCountsRaw = (clone $baseUserLeads)
                    ->selectRaw('DATE(date_time_of_lead_received) as day, COUNT(*) as total')
                    ->groupBy('day')
                    ->orderBy('day')
                    ->pluck('total', 'day')
                    ->all();

                foreach ($period as $date) {
                    $leadsTimeline[] = $date->format('d M');
                    $key = $date->toDateString();
                    $leadsPerDay[] = (int) ($dailyCountsRaw[$key] ?? 0);
                }
            }

            // Bookings for those leads
            $userLeadIds = (clone $baseUserLeads)->pluck('id')->all();
            $bookingsCount = $userLeadIds !== [] ? Booking::whereIn('lead_id', $userLeadIds)->count() : 0;

            $pendingFollowups = (clone $baseUserLeads)
                ->whereNotNull('next_followup_at')
                ->where('next_followup_at', '>=', now())
                ->count();

            $followupTimeline = [];
            $followupsPerDay = [];
            if ($dateFrom && $dateTo) {
                $period = CarbonPeriod::create($dateFrom->copy()->startOfDay(), $dateTo->copy()->startOfDay());
                $followupsRaw = (clone $baseUserLeads)
                    ->whereNotNull('next_followup_at')
                    ->whereBetween('next_followup_at', [
                        $dateFrom->copy()->startOfDay(),
                        $dateTo->copy()->endOfDay(),
                    ])
                    ->selectRaw('DATE(next_followup_at) as day, COUNT(*) as total')
                    ->groupBy('day')
                    ->orderBy('day')
                    ->pluck('total', 'day')
                    ->all();

                foreach ($period as $date) {
                    $followupTimeline[] = $date->format('d M');
                    $key = $date->toDateString();
                    $followupsPerDay[] = (int) ($followupsRaw[$key] ?? 0);
                }
            }

            // Provider / Customer statuses (latest per lead)
            $providerLeadIds = (clone $baseUserLeads)
                ->where('lead_type', Lead::TYPE_PROVIDER)
                ->pluck('id')
                ->all();
            $customerLeadIds = (clone $baseUserLeads)
                ->where('lead_type', Lead::TYPE_CUSTOMER)
                ->pluck('id')
                ->all();

            $providerStatusSummary = [];
            $providerCanceledCount = 0;
            if ($providerLeadIds !== []) {
                $providerHistories = LeadTypeHistory::whereIn('lead_id', $providerLeadIds)
                    ->where('type', 'provider')
                    ->orderByDesc('created_at')
                    ->get()
                    ->groupBy('lead_id')
                    ->map(fn ($group) => $group->first());

                $statusIds = $providerHistories->map(function ($h) {
                    $d = is_array($h->data) ? $h->data : [];
                    return $d['provider_lead_status_id'] ?? null;
                })->filter()->unique()->values()->all();

                $statuses = $statusIds !== []
                    ? ProviderLeadStatus::whereIn('id', $statusIds)->get()->keyBy('id')
                    : collect();

                foreach ($providerHistories as $history) {
                    $d = is_array($history->data) ? $history->data : [];
                    $sid = $d['provider_lead_status_id'] ?? null;
                    if (!$sid) continue;
                    $status = $statuses->get($sid);
                    if (!$status) continue;

                    $baseType = $status->base_type ?? 'pending';
                    $providerCanceledCount += $baseType === 'cancel' ? 1 : 0;

                    $providerStatusSummary[(string) $sid] = $providerStatusSummary[(string) $sid] ?? [
                        'name' => $status->name,
                        'total' => 0,
                    ];
                    $providerStatusSummary[(string) $sid]['total']++;
                }
            }

            $customerStatusSummary = [];
            $customerCanceledCount = 0;
            if ($customerLeadIds !== []) {
                $customerHistories = LeadTypeHistory::whereIn('lead_id', $customerLeadIds)
                    ->where('type', 'customer')
                    ->orderByDesc('created_at')
                    ->get()
                    ->groupBy('lead_id')
                    ->map(fn ($group) => $group->first());

                $statusIds = $customerHistories->map(function ($h) {
                    $d = is_array($h->data) ? $h->data : [];
                    return $d['customer_lead_status_id'] ?? null;
                })->filter()->unique()->values()->all();

                $statuses = $statusIds !== []
                    ? CustomerLeadStatus::whereIn('id', $statusIds)->get()->keyBy('id')
                    : collect();

                foreach ($customerHistories as $history) {
                    $d = is_array($history->data) ? $history->data : [];
                    $sid = $d['customer_lead_status_id'] ?? null;
                    if (!$sid) continue;
                    $status = $statuses->get($sid);
                    if (!$status) continue;

                    $baseType = $status->base_type ?? 'pending';
                    $customerCanceledCount += $baseType === 'cancel' ? 1 : 0;

                    $customerStatusSummary[(string) $sid] = $customerStatusSummary[(string) $sid] ?? [
                        'name' => $status->name,
                        'total' => 0,
                    ];
                    $customerStatusSummary[(string) $sid]['total']++;
                }
            }

            $canceledTotal = $providerCanceledCount + $customerCanceledCount;

            $providerStatusSummary = array_values($providerStatusSummary);
            usort($providerStatusSummary, fn ($a, $b) => ($b['total'] ?? 0) <=> ($a['total'] ?? 0));

            $customerStatusSummary = array_values($customerStatusSummary);
            usort($customerStatusSummary, fn ($a, $b) => ($b['total'] ?? 0) <=> ($a['total'] ?? 0));

            // Outbound
            $userOutboundBase = LeadOutboundEnquiry::query()
                ->where('handled_by', $userId);

            $userOutboundBase->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('contacted_at', [
                    $dateFrom->copy()->startOfDay(),
                    $dateTo->copy()->endOfDay(),
                ]);
            });

            $userOutboundTotal = (clone $userOutboundBase)->count();

            $outboundChannelCounts = (clone $userOutboundBase)
                ->select('contacted_through', DB::raw('count(*) as total'))
                ->groupBy('contacted_through')
                ->pluck('total', 'contacted_through')
                ->all();

            $userOutboundByChannel = [
                ['label' => 'Call', 'total' => (int) ($outboundChannelCounts['call'] ?? 0)],
                ['label' => 'Message', 'total' => (int) ($outboundChannelCounts['message'] ?? 0)],
            ];

            $outboundStatusRows = (clone $userOutboundBase)
                ->select('status_id', DB::raw('count(*) as total'))
                ->whereNotNull('status_id')
                ->groupBy('status_id')
                ->get();

            $outboundStatusIds = $outboundStatusRows->pluck('status_id')->filter()->unique()->values()->all();
            $outboundStatuses = $outboundStatusIds !== []
                ? LeadOutboundEnquiryStatus::whereIn('id', $outboundStatusIds)->get(['id', 'name'])->keyBy('id')
                : collect();

            $userOutboundByStatus = $outboundStatusRows->map(function ($row) use ($outboundStatuses) {
                return [
                    'label' => $outboundStatuses->get($row->status_id)?->name ?? '—',
                    'total' => (int) $row->total,
                ];
            })->sortByDesc('total')->values()->all();

            $filterEmployees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
                ->ofStatus(1)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'email']);

            $userLeadsByTypeLabels = [];
            $userLeadsByTypeValues = [];
            foreach ($userLeadsByTypeMap as $type => $total) {
                $label = \Modules\LeadManagement\Entities\Lead::leadTypes()[$type] ?? (string) $type;
                $userLeadsByTypeLabels[] = $label;
                $userLeadsByTypeValues[] = (int) $total;
            }

            $providerStatusLabels = array_map(fn ($r) => $r['name'], $providerStatusSummary);
            $providerStatusValues = array_map(fn ($r) => (int) $r['total'], $providerStatusSummary);
            $customerStatusLabels = array_map(fn ($r) => $r['name'], $customerStatusSummary);
            $customerStatusValues = array_map(fn ($r) => (int) $r['total'], $customerStatusSummary);

            $userOutboundStatusLabels = array_map(fn ($r) => $r['label'], $userOutboundByStatus);
            $userOutboundStatusValues = array_map(fn ($r) => (int) $r['total'], $userOutboundByStatus);

            return view('leadmanagement::admin.reports.index', [
                'tab' => 'user',
                'dateFrom' => $dateFrom?->toDateString(),
                'dateTo' => $dateTo?->toDateString(),
                'filterEmployees' => $filterEmployees,
                'filterSources' => Source::active()->orderBy('name')->get(['id', 'name']),
                'filterAdSources' => AdSource::active()->orderBy('name')->get(['id', 'name']),
                'selectedUserId' => (string) $userId,
                'selectedUserName' => $userName,
                'queryParams' => [
                    'tab' => 'user',
                    'date_from' => $dateFrom?->toDateString(),
                    'date_to' => $dateTo?->toDateString(),
                    'user_id' => $userId,
                ],
                'userLeadsTotal' => (int) $userLeadsTotal,
                'userCanceledTotal' => (int) $canceledTotal,
                'userBookingsCount' => (int) $bookingsCount,
                'userOutboundTotal' => (int) $userOutboundTotal,
                'userPendingFollowupsTotal' => (int) $pendingFollowups,
                'userLeadsTimeline' => $leadsTimeline,
                'userLeadsPerDay' => $leadsPerDay,
                'userLeadsByTypeLabels' => $userLeadsByTypeLabels,
                'userLeadsByTypeValues' => $userLeadsByTypeValues,
                'userOpenClosedLabels' => ['Open', 'Closed'],
                'userOpenClosedValues' => [
                    (int) ($userOpenClosedSummary['open'] ?? 0),
                    (int) ($userOpenClosedSummary['closed'] ?? 0),
                ],
                'userProviderStatusLabels' => $providerStatusLabels,
                'userProviderStatusValues' => $providerStatusValues,
                'userCustomerStatusLabels' => $customerStatusLabels,
                'userCustomerStatusValues' => $customerStatusValues,
                'userOutboundByChannel' => $userOutboundByChannel,
                'userOutboundStatusLabels' => $userOutboundStatusLabels,
                'userOutboundStatusValues' => $userOutboundStatusValues,
                'userFollowupTimeline' => $followupTimeline,
                'userFollowupsPerDay' => $followupsPerDay,
            ]);
        }

        if ($tab === 'outbound') {
            $outboundBaseQuery = LeadOutboundEnquiry::query()
                ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('contacted_at', [
                        $dateFrom->copy()->startOfDay(),
                        $dateTo->copy()->endOfDay(),
                    ]);
                })
                ->when($request->filled('handled_by_ids'), function ($q) use ($request) {
                    $q->whereIn('handled_by', (array) $request->input('handled_by_ids', []));
                });

            $selectedContactedThroughs = (array) $request->input('contacted_throughs', []);
            $outboundBaseQuery->when($selectedContactedThroughs !== [], function ($q) use ($selectedContactedThroughs) {
                $q->whereIn('contacted_through', $selectedContactedThroughs);
            });

            $totalOutbound = (clone $outboundBaseQuery)->count();

            $channelCounts = (clone $outboundBaseQuery)
                ->select('contacted_through', DB::raw('count(*) as total'))
                ->groupBy('contacted_through')
                ->pluck('total', 'contacted_through')
                ->all();

            $outboundByChannel = [
                ['label' => 'Call', 'total' => (int) ($channelCounts['call'] ?? 0)],
                ['label' => 'Message', 'total' => (int) ($channelCounts['message'] ?? 0)],
            ];

            $outboundByUserRaw = (clone $outboundBaseQuery)
                ->select('handled_by', DB::raw('count(*) as total'))
                ->whereNotNull('handled_by')
                ->groupBy('handled_by')
                ->get();

            $outboundUserIds = $outboundByUserRaw->pluck('handled_by')->filter()->unique()->values()->all();
            $outboundUsers = $outboundUserIds !== []
                ? User::whereIn('id', $outboundUserIds)->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id')
                : collect();

            $outboundByUser = $outboundByUserRaw->map(function ($row) use ($outboundUsers) {
                $user = $outboundUsers->get($row->handled_by);
                $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : null;
                $label = $fullName ?: ($user->email ?? (string) $row->handled_by);
                return ['label' => $label, 'total' => (int) $row->total];
            })->sortByDesc('total')->values()->all();

            $statusRows = (clone $outboundBaseQuery)
                ->select('status_id', DB::raw('count(*) as total'))
                ->whereNotNull('status_id')
                ->groupBy('status_id')
                ->get();

            $statusIds = $statusRows->pluck('status_id')->filter()->unique()->values()->all();
            $statuses = $statusIds !== []
                ? LeadOutboundEnquiryStatus::whereIn('id', $statusIds)->get(['id', 'name'])->keyBy('id')
                : collect();

            $outboundByStatus = $statusRows->map(function ($row) use ($statuses) {
                $status = $statuses->get($row->status_id);
                return ['label' => $status?->name ?? '—', 'total' => (int) $row->total];
            })->sortByDesc('total')->values()->all();

            // Status by channel (Call vs Message)
            $statusByChannelRows = (clone $outboundBaseQuery)
                ->select('contacted_through', 'status_id', DB::raw('count(*) as total'))
                ->whereNotNull('status_id')
                ->whereNotNull('contacted_through')
                ->groupBy('contacted_through', 'status_id')
                ->get();

            $statusTotals = [];
            foreach ($statusByChannelRows as $row) {
                $key = (string) $row->status_id;
                if (!isset($statusTotals[$key])) {
                    $statusTotals[$key] = 0;
                }
                $statusTotals[$key] += (int) $row->total;
            }

            $sortedStatusIds = array_keys($statusTotals);
            usort($sortedStatusIds, function ($a, $b) use ($statusTotals) {
                return ($statusTotals[$b] ?? 0) <=> ($statusTotals[$a] ?? 0);
            });

            $statusLabels = [];
            foreach ($sortedStatusIds as $sid) {
                $statusLabels[] = $statuses->get((int) $sid)?->name ?? '—';
            }

            $callStatusMap = [];
            $messageStatusMap = [];
            foreach ($statusByChannelRows as $row) {
                $sid = (string) $row->status_id;
                $through = (string) $row->contacted_through;
                if ($through === 'call') {
                    $callStatusMap[$sid] = (int) $row->total;
                } elseif ($through === 'message') {
                    $messageStatusMap[$sid] = (int) $row->total;
                }
            }

            $callStatusCounts = [];
            $messageStatusCounts = [];
            foreach ($sortedStatusIds as $sid) {
                $callStatusCounts[] = (int) ($callStatusMap[$sid] ?? 0);
                $messageStatusCounts[] = (int) ($messageStatusMap[$sid] ?? 0);
            }

            // User-wise status matrix (stacked)
            $userStatusRows = (clone $outboundBaseQuery)
                ->select('handled_by', 'status_id', DB::raw('count(*) as total'))
                ->whereNotNull('handled_by')
                ->whereNotNull('status_id')
                ->groupBy('handled_by', 'status_id')
                ->get();

            $userTotals = [];
            foreach ($userStatusRows as $row) {
                $uid = (string) $row->handled_by;
                if (!isset($userTotals[$uid])) {
                    $userTotals[$uid] = 0;
                }
                $userTotals[$uid] += (int) $row->total;
            }

            $sortedUserIds = array_keys($userTotals);
            usort($sortedUserIds, fn ($a, $b) => ($userTotals[$b] ?? 0) <=> ($userTotals[$a] ?? 0));

            $userStatusUsers = $sortedUserIds !== []
                ? User::whereIn('id', $sortedUserIds)->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id')
                : collect();

            $userCategories = [];
            foreach ($sortedUserIds as $uid) {
                $u = $userStatusUsers->get($uid);
                $fullName = $u ? trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) : null;
                $userCategories[] = $fullName ?: ($u->email ?? $uid);
            }

            $matrix = [];
            foreach ($userStatusRows as $row) {
                $uid = (string) $row->handled_by;
                $sid = (string) $row->status_id;
                $matrix[$sid][$uid] = (int) $row->total;
            }

            $userStatusSeries = [];
            foreach ($sortedStatusIds as $sid) {
                $label = $statuses->get((int) $sid)?->name ?? '—';
                $data = [];
                foreach ($sortedUserIds as $uid) {
                    $data[] = (int) (($matrix[$sid][$uid] ?? 0));
                }
                $userStatusSeries[] = ['name' => $label, 'data' => $data];
            }

            $filterEmployees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
                ->ofStatus(1)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'email']);

            $queryParams = [
                'tab' => 'outbound',
                'date_from' => $dateFrom?->toDateString(),
                'date_to' => $dateTo?->toDateString(),
            ];
            if ($request->filled('handled_by_ids')) {
                $queryParams['handled_by_ids'] = (array) $request->input('handled_by_ids', []);
            }
            if (!empty($selectedContactedThroughs)) {
                $queryParams['contacted_throughs'] = $selectedContactedThroughs;
            }

            return view('leadmanagement::admin.reports.index', [
                'tab' => 'outbound',
                'dateFrom' => $dateFrom?->toDateString(),
                'dateTo' => $dateTo?->toDateString(),
                'filterEmployees' => $filterEmployees,
                'selectedHandledByIds' => (array) $request->input('handled_by_ids', []),
                'selectedContactedThroughs' => $selectedContactedThroughs,
                'queryParams' => $queryParams,
                'totalOutbound' => $totalOutbound,
                'outboundByChannel' => $outboundByChannel,
                'outboundByUser' => $outboundByUser,
                'outboundByStatus' => $outboundByStatus,
                'outboundStatusLabels' => $statusLabels,
                'outboundCallStatusCounts' => $callStatusCounts,
                'outboundMessageStatusCounts' => $messageStatusCounts,
                'outboundUserCategories' => $userCategories,
                'outboundUserStatusSeries' => $userStatusSeries,
            ]);
        }

        $baseQuery = Lead::query()
            ->with(['source', 'adSource'])
            ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('date_time_of_lead_received', [
                    $dateFrom->copy()->startOfDay(),
                    $dateTo->copy()->endOfDay(),
                ]);
            })
            ->when($request->filled('lead_type') && $request->input('lead_type') !== 'all', function ($q) use ($request) {
                $q->where('lead_type', $request->input('lead_type'));
            })
            ->when($request->filled('source_ids'), function ($q) use ($request) {
                $q->whereIn('source_id', (array) $request->input('source_ids', []));
            })
            ->when($request->filled('ad_source_ids'), function ($q) use ($request) {
                $q->whereIn('ad_source_id', (array) $request->input('ad_source_ids', []));
            })
            ->when($request->filled('handled_by_ids'), function ($q) use ($request) {
                $q->whereIn('handled_by', (array) $request->input('handled_by_ids', []));
            });

        $totalLeads = (clone $baseQuery)->count();
        $inboundLeadsForOpenClosed = (clone $baseQuery)->get(['id', 'lead_type']);
        $inboundOpenClosedSummary = $this->buildOpenClosedSummary($inboundLeadsForOpenClosed);

        $leadsByType = (clone $baseQuery)
            ->select('lead_type', DB::raw('count(*) as total'))
            ->groupBy('lead_type')
            ->pluck('total', 'lead_type')
            ->all();

        $dailyCountsRaw = (clone $baseQuery)
            ->selectRaw('DATE(date_time_of_lead_received) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->all();

        $timeline = [];
        $leadsPerDay = [];
        if ($dateFrom && $dateTo) {
            $period = CarbonPeriod::create($dateFrom->copy()->startOfDay(), $dateTo->copy()->startOfDay());
            foreach ($period as $date) {
                $key = $date->toDateString();
                $timeline[] = $date->format('d M');
                $leadsPerDay[] = (int) ($dailyCountsRaw[$key] ?? 0);
            }
        }

        $handledByRaw = (clone $baseQuery)
            ->select('handled_by', DB::raw('count(*) as total'))
            ->whereNotNull('handled_by')
            ->groupBy('handled_by')
            ->get();

        $handledByIds = $handledByRaw->pluck('handled_by')->filter()->unique()->values()->all();
        $handledByUsers = $handledByIds !== []
            ? User::whereIn('id', $handledByIds)->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id')
            : collect();

        $handledByNames = [];
        foreach ($handledByUsers as $user) {
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $handledByNames[(string) $user->id] = $fullName ?: $user->email;
        }

        $userWise = $handledByRaw->map(function ($row) use ($handledByUsers) {
            $user = $handledByUsers->get($row->handled_by);
            $fullName = $user
                ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
                : null;
            $label = $fullName ?: ($user->email ?? (string) $row->handled_by);
            return [
                'label' => $label,
                'total' => (int) $row->total,
            ];
        })->sortByDesc('total')->values()->all();

        $sourceWise = (clone $baseQuery)
            ->select('source_id', DB::raw('count(*) as total'))
            ->groupBy('source_id')
            ->get();

        $sourceIds = $sourceWise->pluck('source_id')->filter()->unique()->values()->all();
        $sources = $sourceIds !== []
            ? Source::whereIn('id', $sourceIds)->get(['id', 'name'])->keyBy('id')
            : collect();

        $sourceWise = $sourceWise->map(function ($row) use ($sources) {
            $source = $row->source_id ? $sources->get($row->source_id) : null;
            return [
                'label' => $source?->name ?? '—',
                'total' => (int) $row->total,
            ];
        })->sortByDesc('total')->values()->all();

        $adSourceWise = (clone $baseQuery)
            ->select('ad_source_id', DB::raw('count(*) as total'))
            ->groupBy('ad_source_id')
            ->get();

        $adSourceIds = $adSourceWise->pluck('ad_source_id')->filter()->unique()->values()->all();
        $adSources = $adSourceIds !== []
            ? AdSource::whereIn('id', $adSourceIds)->get(['id', 'name'])->keyBy('id')
            : collect();

        $adSourceWise = $adSourceWise->map(function ($row) use ($adSources) {
            $ad = $row->ad_source_id ? $adSources->get($row->ad_source_id) : null;
            return [
                'label' => $ad?->name ?? '—',
                'total' => (int) $row->total,
            ];
        })->sortByDesc('total')->values()->all();

        $customerStatusSummary = $this->buildCustomerStatusSummary($baseQuery);
        $providerStatusSummary = $this->buildProviderStatusSummary($baseQuery);
        $invalidReasonSummary = $this->buildReasonSummary($baseQuery, 'invalid');
        $futureCustomerReasonSummary = $this->buildReasonSummary($baseQuery, 'future_customer');

        $pendingFollowups = (clone $baseQuery)
            ->whereNotNull('next_followup_at')
            ->where('next_followup_at', '>=', now())
            ->count();

        $todayLeads = (clone $baseQuery)
            ->whereDate('date_time_of_lead_received', Carbon::today())
            ->count();

        $filterSources = Source::active()->orderBy('name')->get(['id', 'name']);
        $filterAdSources = AdSource::active()->orderBy('name')->get(['id', 'name']);
        $filterEmployees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
            ->ofStatus(1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        $queryParams = [
            'tab' => 'inbound',
            'date_from' => $dateFrom?->toDateString(),
            'date_to' => $dateTo?->toDateString(),
            'lead_type' => $request->input('lead_type', 'all'),
        ];
        if ($request->filled('source_ids')) {
            $queryParams['source_ids'] = (array) $request->input('source_ids', []);
        }
        if ($request->filled('ad_source_ids')) {
            $queryParams['ad_source_ids'] = (array) $request->input('ad_source_ids', []);
        }
        if ($request->filled('handled_by_ids')) {
            $queryParams['handled_by_ids'] = (array) $request->input('handled_by_ids', []);
        }

        $leadsForTable = (clone $baseQuery)
            ->with(['source', 'adSource', 'createdBy'])
            ->orderByDesc('date_time_of_lead_received')
            ->paginate(pagination_limit())
            ->appends($queryParams);

        // Per-lead type details for table
        $leadIdsForTable = $leadsForTable->pluck('id')->all();
        [$customerStatusByLead, $providerStatusByLead, $invalidReasonByLead, $futureCustomerReasonByLead] =
            $this->buildPerLeadTypeDetails($leadIdsForTable);

        return view('leadmanagement::admin.reports.index', [
            'tab' => 'inbound',
            'totalLeads' => $totalLeads,
            'leadsByType' => $leadsByType,
            'inboundOpenClosedLabels' => ['Open', 'Closed'],
            'inboundOpenClosedValues' => [
                (int) ($inboundOpenClosedSummary['open'] ?? 0),
                (int) ($inboundOpenClosedSummary['closed'] ?? 0),
            ],
            'timeline' => $timeline,
            'leadsPerDay' => $leadsPerDay,
            'userWise' => $userWise,
            'sourceWise' => $sourceWise,
            'adSourceWise' => $adSourceWise,
            'customerStatusSummary' => $customerStatusSummary,
            'providerStatusSummary' => $providerStatusSummary,
            'invalidReasonSummary' => $invalidReasonSummary,
            'futureCustomerReasonSummary' => $futureCustomerReasonSummary,
            'pendingFollowups' => $pendingFollowups,
            'todayLeads' => $todayLeads,
            'dateFrom' => $dateFrom?->toDateString(),
            'dateTo' => $dateTo?->toDateString(),
            'selectedLeadType' => $request->input('lead_type', 'all'),
            'selectedSourceIds' => (array) $request->input('source_ids', []),
            'selectedAdSourceIds' => (array) $request->input('ad_source_ids', []),
            'selectedHandledByIds' => (array) $request->input('handled_by_ids', []),
            'filterSources' => $filterSources,
            'filterAdSources' => $filterAdSources,
            'filterEmployees' => $filterEmployees,
            'queryParams' => $queryParams,
            'leads' => $leadsForTable,
            'handledByNames' => $handledByNames,
            'customerStatusByLead' => $customerStatusByLead,
            'providerStatusByLead' => $providerStatusByLead,
            'invalidReasonByLead' => $invalidReasonByLead,
            'futureCustomerReasonByLead' => $futureCustomerReasonByLead,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function download(Request $request): StreamedResponse|string
    {
        $this->authorize('report_export');

        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $query = Lead::query()
            ->with(['source', 'adSource', 'createdBy'])
            ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('date_time_of_lead_received', [
                    $dateFrom->copy()->startOfDay(),
                    $dateTo->copy()->endOfDay(),
                ]);
            })
            ->when($request->filled('lead_type') && $request->input('lead_type') !== 'all', function ($q) use ($request) {
                $q->where('lead_type', $request->input('lead_type'));
            })
            ->when($request->filled('source_ids'), function ($q) use ($request) {
                $q->whereIn('source_id', (array) $request->input('source_ids', []));
            })
            ->when($request->filled('ad_source_ids'), function ($q) use ($request) {
                $q->whereIn('ad_source_id', (array) $request->input('ad_source_ids', []));
            })
            ->when($request->filled('handled_by_ids'), function ($q) use ($request) {
                $q->whereIn('handled_by', (array) $request->input('handled_by_ids', []));
            })
            ->orderByDesc('date_time_of_lead_received');

        $leads = $query->get();

        if ($leads->isEmpty()) {
            return translate('No_leads_found_for_the_selected_filters');
        }

        $fileName = time() . '-lead-report.xlsx';

        $leadIds = $leads->pluck('id')->all();
        [$customerStatusByLead, $providerStatusByLead, $invalidReasonByLead, $futureCustomerReasonByLead] =
            $this->buildPerLeadTypeDetails($leadIds);

        return (new FastExcel($leads))->download($fileName, function (Lead $lead) use (
            $customerStatusByLead,
            $providerStatusByLead,
            $invalidReasonByLead,
            $futureCustomerReasonByLead
        ) {
            $creator = $lead->createdBy;
            $creatorName = null;
            if ($creator) {
                $fullName = trim(($creator->first_name ?? '') . ' ' . ($creator->last_name ?? ''));
                $creatorName = $fullName ?: $creator->email;
            }

            return [
                'ID' => $lead->id,
                'Name' => $lead->name ?? '',
                'Phone' => $lead->phone_number,
                'Lead Type' => Lead::leadTypes()[$lead->lead_type] ?? $lead->lead_type,
                'Source' => $lead->source?->name ?? '',
                'Ad Source' => $lead->adSource?->name ?? '',
                'Handled By' => $this->resolveHandledByName($lead->handled_by),
                'Received At' => optional($lead->date_time_of_lead_received)->format('Y-m-d H:i:s'),
                'Next Followup At' => optional($lead->next_followup_at)->format('Y-m-d H:i:s'),
                'Created By' => $creatorName,
                'Remarks' => $lead->remarks,
                'Customer Status' => $customerStatusByLead[$lead->id] ?? '',
                'Provider Status' => $providerStatusByLead[$lead->id] ?? '',
                'Invalid Reason' => $invalidReasonByLead[$lead->id] ?? '',
                'Future Customer Reason' => $futureCustomerReasonByLead[$lead->id] ?? '',
            ];
        });
    }

    private function resolveDateRange(Request $request): array
    {
        $from = $request->input('date_from');
        $to = $request->input('date_to');

        if (!$from || !$to) {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now();
            return [$start, $end];
        }

        try {
            $start = Carbon::parse($from)->startOfDay();
            $end = Carbon::parse($to)->endOfDay();
        } catch (\Throwable $e) {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now();
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private function resolveHandledByName(?string $handledBy): string
    {
        if (!$handledBy || $handledBy === Lead::HANDLED_BY_AI) {
            return translate('Unassigned');
        }
        $user = User::find($handledBy);
        if ($user) {
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            return $fullName ?: $user->email;
        }
        return (string) $handledBy;
    }

    private function buildCustomerStatusSummary($baseQuery): array
    {
        $leadIds = (clone $baseQuery)
            ->where('lead_type', Lead::TYPE_CUSTOMER)
            ->pluck('id')
            ->all();

        if ($leadIds === []) {
            return [];
        }

        $histories = LeadTypeHistory::whereIn('lead_id', $leadIds)
            ->where('type', 'customer')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        if ($histories->isEmpty()) {
            return [];
        }

        $statusIds = $histories->map(function ($history) {
            $data = is_array($history->data) ? $history->data : [];
            return $data['customer_lead_status_id'] ?? null;
        })->filter()->unique()->values()->all();

        if ($statusIds === []) {
            return [];
        }

        $statuses = CustomerLeadStatus::whereIn('id', $statusIds)->get()->keyBy('id');

        $summary = [];
        foreach ($histories as $history) {
            $data = is_array($history->data) ? $history->data : [];
            $statusId = $data['customer_lead_status_id'] ?? null;
            if (!$statusId) {
                continue;
            }
            $status = $statuses->get($statusId);
            if (!$status) {
                continue;
            }
            $baseType = $status->base_type ?? 'pending';
            if (!isset($summary[$statusId])) {
                $summary[$statusId] = [
                    'name' => $status->name,
                    'base_type' => $baseType,
                    'color' => $status->color ?? '#0d6efd',
                    'total' => 0,
                ];
            }
            $summary[$statusId]['total']++;
        }

        return array_values($summary);
    }

    private function buildProviderStatusSummary($baseQuery): array
    {
        $leadIds = (clone $baseQuery)
            ->where('lead_type', Lead::TYPE_PROVIDER)
            ->pluck('id')
            ->all();

        if ($leadIds === []) {
            return [];
        }

        $histories = LeadTypeHistory::whereIn('lead_id', $leadIds)
            ->where('type', 'provider')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        if ($histories->isEmpty()) {
            return [];
        }

        $statusIds = $histories->map(function ($history) {
            $data = is_array($history->data) ? $history->data : [];
            return $data['provider_lead_status_id'] ?? null;
        })->filter()->unique()->values()->all();

        if ($statusIds === []) {
            return [];
        }

        $statuses = ProviderLeadStatus::whereIn('id', $statusIds)->get()->keyBy('id');

        $summary = [];
        foreach ($histories as $history) {
            $data = is_array($history->data) ? $history->data : [];
            $statusId = $data['provider_lead_status_id'] ?? null;
            if (!$statusId) {
                continue;
            }
            $status = $statuses->get($statusId);
            if (!$status) {
                continue;
            }
            $baseType = $status->base_type ?? 'pending';
            if (!isset($summary[$statusId])) {
                $summary[$statusId] = [
                    'name' => $status->name,
                    'base_type' => $baseType,
                    'color' => $status->color ?? '#0d6efd',
                    'total' => 0,
                ];
            }
            $summary[$statusId]['total']++;
        }

        return array_values($summary);
    }

    private function buildReasonSummary($baseQuery, string $type): array
    {
        if (!in_array($type, ['invalid', 'future_customer'], true)) {
            return [];
        }

        $leadType = $type === 'invalid' ? Lead::TYPE_INVALID : Lead::TYPE_FUTURE_CUSTOMER;

        $leadIds = (clone $baseQuery)
            ->where('lead_type', $leadType)
            ->pluck('id')
            ->all();

        if ($leadIds === []) {
            return [];
        }

        $histories = LeadTypeHistory::whereIn('lead_id', $leadIds)
            ->where('type', $type)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        if ($histories->isEmpty()) {
            return [];
        }

        $reasonKey = $type === 'invalid' ? 'invalid_reason_id' : 'future_customer_reason_id';

        $reasonIds = $histories->map(function ($history) use ($reasonKey) {
            $data = is_array($history->data) ? $history->data : [];
            return $data[$reasonKey] ?? null;
        })->filter()->unique()->values()->all();

        if ($reasonIds === []) {
            return [];
        }

        $reasonModel = $type === 'invalid' ? LeadInvalidReason::class : LeadFutureCustomerReason::class;
        $reasons = $reasonModel::whereIn('id', $reasonIds)->get()->keyBy('id');

        $summary = [];
        foreach ($histories as $history) {
            $data = is_array($history->data) ? $history->data : [];
            $reasonId = $data[$reasonKey] ?? null;
            if (!$reasonId) {
                continue;
            }
            $reason = $reasons->get($reasonId);
            if (!$reason) {
                continue;
            }
            if (!isset($summary[$reasonId])) {
                $summary[$reasonId] = [
                    'name' => $reason->name,
                    'total' => 0,
                ];
            }
            $summary[$reasonId]['total']++;
        }

        return array_values($summary);
    }

    /**
     * @param Collection<int, Lead> $leads
     * @return array{open:int, closed:int}
     */
    private function buildOpenClosedSummary(Collection $leads): array
    {
        if ($leads->isEmpty()) {
            return ['open' => 0, 'closed' => 0];
        }

        $leadIds = $leads->pluck('id')->all();
        $histories = LeadTypeHistory::whereIn('lead_id', $leadIds)
            ->whereIn('type', [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER])
            ->orderByDesc('created_at')
            ->get();

        $latestByComposite = [];
        foreach ($histories as $history) {
            $key = $history->lead_id . '|' . $history->type;
            if (!isset($latestByComposite[$key])) {
                $latestByComposite[$key] = $history;
            }
        }

        $customerStatusIds = [];
        $providerStatusIds = [];
        foreach ($latestByComposite as $key => $history) {
            $data = is_array($history->data) ? $history->data : [];
            if (str_ends_with((string) $key, '|' . Lead::TYPE_CUSTOMER) && !empty($data['customer_lead_status_id'])) {
                $customerStatusIds[] = (int) $data['customer_lead_status_id'];
            }
            if (str_ends_with((string) $key, '|' . Lead::TYPE_PROVIDER) && !empty($data['provider_lead_status_id'])) {
                $providerStatusIds[] = (int) $data['provider_lead_status_id'];
            }
        }

        $customerStatuses = $customerStatusIds !== []
            ? CustomerLeadStatus::whereIn('id', array_unique($customerStatusIds))->get()->keyBy('id')
            : collect();
        $providerStatuses = $providerStatusIds !== []
            ? ProviderLeadStatus::whereIn('id', array_unique($providerStatusIds))->get()->keyBy('id')
            : collect();

        $open = 0;
        $closed = 0;

        foreach ($leads as $lead) {
            $isOpen = false;

            if ($lead->lead_type === Lead::TYPE_UNKNOWN) {
                $isOpen = true;
            } elseif (in_array($lead->lead_type, [Lead::TYPE_INVALID, Lead::TYPE_FUTURE_CUSTOMER], true)) {
                $isOpen = false;
            } elseif ($lead->lead_type === Lead::TYPE_CUSTOMER) {
                $history = $latestByComposite[$lead->id . '|' . Lead::TYPE_CUSTOMER] ?? null;
                $data = ($history && is_array($history->data)) ? $history->data : [];
                $statusId = $data['customer_lead_status_id'] ?? null;
                if (!$statusId) {
                    $isOpen = true;
                } else {
                    $status = $customerStatuses->get((int) $statusId);
                    $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
                    $isOpen = !in_array($baseType, ['completed', 'cancel'], true);
                }
            } elseif ($lead->lead_type === Lead::TYPE_PROVIDER) {
                $history = $latestByComposite[$lead->id . '|' . Lead::TYPE_PROVIDER] ?? null;
                $data = ($history && is_array($history->data)) ? $history->data : [];
                $statusId = $data['provider_lead_status_id'] ?? null;
                if (!$statusId) {
                    $isOpen = true;
                } else {
                    $status = $providerStatuses->get((int) $statusId);
                    $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
                    $isOpen = !in_array($baseType, ['completed', 'cancel'], true);
                }
            }

            if ($isOpen) {
                $open++;
            } else {
                $closed++;
            }
        }

        return ['open' => $open, 'closed' => $closed];
    }

    /**
     * @param array<int> $leadIds
     * @return array{0: array<int,string>, 1: array<int,string>, 2: array<int,string>, 3: array<int,string>}
     */
    private function buildPerLeadTypeDetails(array $leadIds): array
    {
        $customerStatusByLead = [];
        $providerStatusByLead = [];
        $invalidReasonByLead = [];
        $futureCustomerReasonByLead = [];

        if ($leadIds === []) {
            return [$customerStatusByLead, $providerStatusByLead, $invalidReasonByLead, $futureCustomerReasonByLead];
        }

        // Customer statuses per lead
        $customerHistories = LeadTypeHistory::whereIn('lead_id', $leadIds)
            ->where('type', 'customer')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());
        if ($customerHistories->isNotEmpty()) {
            $statusIds = $customerHistories->map(function ($history) {
                $data = is_array($history->data) ? $history->data : [];
                return $data['customer_lead_status_id'] ?? null;
            })->filter()->unique()->values()->all();
            if ($statusIds !== []) {
                $statuses = CustomerLeadStatus::whereIn('id', $statusIds)->get()->keyBy('id');
                foreach ($customerHistories as $leadId => $history) {
                    $data = is_array($history->data) ? $history->data : [];
                    $statusId = $data['customer_lead_status_id'] ?? null;
                    if ($statusId && $statuses->has($statusId)) {
                        $customerStatusByLead[(int)$leadId] = $statuses->get($statusId)->name;
                    }
                }
            }
        }

        // Provider statuses per lead
        $providerHistories = LeadTypeHistory::whereIn('lead_id', $leadIds)
            ->where('type', 'provider')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());
        if ($providerHistories->isNotEmpty()) {
            $statusIds = $providerHistories->map(function ($history) {
                $data = is_array($history->data) ? $history->data : [];
                return $data['provider_lead_status_id'] ?? null;
            })->filter()->unique()->values()->all();
            if ($statusIds !== []) {
                $statuses = ProviderLeadStatus::whereIn('id', $statusIds)->get()->keyBy('id');
                foreach ($providerHistories as $leadId => $history) {
                    $data = is_array($history->data) ? $history->data : [];
                    $statusId = $data['provider_lead_status_id'] ?? null;
                    if ($statusId && $statuses->has($statusId)) {
                        $providerStatusByLead[(int)$leadId] = $statuses->get($statusId)->name;
                    }
                }
            }
        }

        // Invalid reasons per lead
        $invalidHistories = LeadTypeHistory::whereIn('lead_id', $leadIds)
            ->where('type', 'invalid')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());
        if ($invalidHistories->isNotEmpty()) {
            $reasonIds = $invalidHistories->map(function ($history) {
                $data = is_array($history->data) ? $history->data : [];
                return $data['invalid_reason_id'] ?? null;
            })->filter()->unique()->values()->all();
            if ($reasonIds !== []) {
                $reasons = LeadInvalidReason::whereIn('id', $reasonIds)->get()->keyBy('id');
                foreach ($invalidHistories as $leadId => $history) {
                    $data = is_array($history->data) ? $history->data : [];
                    $reasonId = $data['invalid_reason_id'] ?? null;
                    if ($reasonId && $reasons->has($reasonId)) {
                        $invalidReasonByLead[(int)$leadId] = $reasons->get($reasonId)->name;
                    }
                }
            }
        }

        // Future customer reasons per lead
        $futureHistories = LeadTypeHistory::whereIn('lead_id', $leadIds)
            ->where('type', 'future_customer')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());
        if ($futureHistories->isNotEmpty()) {
            $reasonIds = $futureHistories->map(function ($history) {
                $data = is_array($history->data) ? $history->data : [];
                return $data['future_customer_reason_id'] ?? null;
            })->filter()->unique()->values()->all();
            if ($reasonIds !== []) {
                $reasons = LeadFutureCustomerReason::whereIn('id', $reasonIds)->get()->keyBy('id');
                foreach ($futureHistories as $leadId => $history) {
                    $data = is_array($history->data) ? $history->data : [];
                    $reasonId = $data['future_customer_reason_id'] ?? null;
                    if ($reasonId && $reasons->has($reasonId)) {
                        $futureCustomerReasonByLead[(int)$leadId] = $reasons->get($reasonId)->name;
                    }
                }
            }
        }

        return [$customerStatusByLead, $providerStatusByLead, $invalidReasonByLead, $futureCustomerReasonByLead];
    }
}

