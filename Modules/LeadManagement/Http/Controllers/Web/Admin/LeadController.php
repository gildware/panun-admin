<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\LeadManagement\Entities\AdSource;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\Source;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\CustomerLeadTag;
use Modules\LeadManagement\Entities\LeadProviderChecklist;
use Modules\LeadManagement\Entities\ProviderChecklistItem;
use Modules\LeadManagement\Entities\ProviderCancellationReason;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Entities\District;
use Modules\LeadManagement\Entities\LeadChangeLog;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\ZoneManagement\Entities\Zone;
use Modules\UserManagement\Entities\User;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Service;
use Modules\BookingModule\Entities\Booking;

class LeadController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $tab = $request->get('tab', 'all');
        $validTabs = ['all', 'unknown', 'customer', 'future_customer', 'provider', 'invalid'];
        if (!in_array($tab, $validTabs)) {
            $tab = 'all';
        }

        $search = $request->get('search', '');
        $sourceIds = array_filter((array) $request->input('source_id', []));
        $adSourceIds = array_filter((array) $request->input('ad_source_id', []));
        $handledByFilterIds = array_filter((array) $request->input('handled_by', []));
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $filterStatusIds = array_filter((array) $request->input('status_ids', []));
        $filterDistrictIds = array_filter((array) $request->input('district_ids', []));
        $filterZoneIds = array_filter((array) $request->input('zone_ids', []));
        $filterCategoryIds = array_filter((array) $request->input('category_ids', []));
        $hasProviderFilters = $tab === 'provider' && ($filterStatusIds !== [] || $filterDistrictIds !== [] || $filterZoneIds !== [] || $filterCategoryIds !== []);

        $filterCustomerStatusIds = array_filter((array) $request->input('customer_status_ids', []));
        $filterCustomerZoneIds = array_filter((array) $request->input('customer_zone_ids', []));
        $filterCustomerCategoryIds = array_filter((array) $request->input('customer_category_ids', []));
        $filterCustomerSubCategoryIds = array_filter((array) $request->input('customer_sub_category_ids', []));
        $estimatedDateFrom = $request->get('estimated_date_from');
        $estimatedDateTo = $request->get('estimated_date_to');
        $hasCustomerFilters = $tab === 'customer' && (
            $filterCustomerStatusIds !== []
            || $filterCustomerZoneIds !== []
            || $filterCustomerCategoryIds !== []
            || $filterCustomerSubCategoryIds !== []
            || ($estimatedDateFrom && $estimatedDateTo)
        );

        $with = ['source', 'adSource'];
        if ($tab === 'customer') {
            $with[] = 'customerLeadTags';
        }

        $query = Lead::with($with)
            ->ofType($tab === 'all' ? null : $tab)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhereRaw('CAST(id AS CHAR) LIKE ?', ['%' . $search . '%']);
                });
            })
            ->when($sourceIds !== [], function ($q) use ($sourceIds) {
                $q->whereIn('source_id', $sourceIds);
            })
            ->when($adSourceIds !== [], function ($q) use ($adSourceIds) {
                $q->whereIn('ad_source_id', $adSourceIds);
            })
            ->when($handledByFilterIds !== [], function ($q) use ($handledByFilterIds) {
                $q->whereIn('handled_by', $handledByFilterIds);
            })
            ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('date_time_of_lead_received', [
                    $dateFrom . ' 00:00:00',
                    $dateTo . ' 23:59:59',
                ]);
            })
            ->latest('date_time_of_lead_received');

        if ($hasProviderFilters) {
            $providerLeadIds = Lead::ofType('provider')->pluck('id')->all();
            if ($providerLeadIds !== []) {
                $histories = LeadTypeHistory::whereIn('lead_id', $providerLeadIds)
                    ->where('type', 'provider')
                    ->orderByDesc('created_at')
                    ->get();
                $latestByLead = $histories->groupBy('lead_id')->map(fn ($group) => $group->first());
                $matchingLeadIds = $latestByLead->filter(function ($h) use ($filterStatusIds, $filterDistrictIds, $filterZoneIds, $filterCategoryIds) {
                    $d = is_array($h->data) ? $h->data : [];
                    if ($filterStatusIds !== [] && !in_array($d['provider_lead_status_id'] ?? null, $filterStatusIds)) {
                        return false;
                    }
                    if ($filterDistrictIds !== [] && !in_array($d['district_id'] ?? null, $filterDistrictIds)) {
                        return false;
                    }
                    if ($filterZoneIds !== [] && !in_array($d['zone_id'] ?? null, $filterZoneIds)) {
                        return false;
                    }
                    if ($filterCategoryIds !== [] && !in_array($d['provider_service_category'] ?? null, $filterCategoryIds)) {
                        return false;
                    }
                    return true;
                })->keys()->all();
                $query->whereIn('id', $matchingLeadIds);
            }
        }

        if ($hasCustomerFilters) {
            $customerLeadIds = Lead::ofType('customer')->pluck('id')->all();
            if ($customerLeadIds !== []) {
                $histories = LeadTypeHistory::whereIn('lead_id', $customerLeadIds)
                    ->where('type', 'customer')
                    ->orderByDesc('created_at')
                    ->get();
                $latestByLead = $histories->groupBy('lead_id')->map(fn ($group) => $group->first());
                $matchingLeadIds = $latestByLead->filter(function ($h) use ($filterCustomerStatusIds, $filterCustomerZoneIds, $filterCustomerCategoryIds, $filterCustomerSubCategoryIds, $estimatedDateFrom, $estimatedDateTo) {
                    $d = is_array($h->data) ? $h->data : [];
                    if ($filterCustomerStatusIds !== [] && !in_array($d['customer_lead_status_id'] ?? null, $filterCustomerStatusIds)) {
                        return false;
                    }
                    if ($filterCustomerZoneIds !== [] && !in_array($d['zone_id'] ?? null, $filterCustomerZoneIds)) {
                        return false;
                    }
                    if ($filterCustomerCategoryIds !== [] && !in_array($d['service_category'] ?? null, $filterCustomerCategoryIds)) {
                        return false;
                    }
                    if ($filterCustomerSubCategoryIds !== [] && !in_array($d['service_subcategory'] ?? null, $filterCustomerSubCategoryIds)) {
                        return false;
                    }
                    if ($estimatedDateFrom && $estimatedDateTo) {
                        $estAt = $d['estimated_service_at'] ?? null;
                        if (!$estAt) {
                            return false;
                        }
                        try {
                            $est = \Carbon\Carbon::parse($estAt);
                            $from = \Carbon\Carbon::parse($estimatedDateFrom)->startOfDay();
                            $to = \Carbon\Carbon::parse($estimatedDateTo)->endOfDay();
                            if ($est->lt($from) || $est->gt($to)) {
                                return false;
                            }
                        } catch (\Throwable $e) {
                            return false;
                        }
                    }
                    return true;
                })->keys()->all();
                $query->whereIn('id', $matchingLeadIds);
            }
        }

        $queryParams = [
            'tab' => $tab,
            'search' => $search,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
        if ($sourceIds !== []) {
            $queryParams['source_id'] = $sourceIds;
        }
        if ($adSourceIds !== []) {
            $queryParams['ad_source_id'] = $adSourceIds;
        }
        if ($handledByFilterIds !== []) {
            $queryParams['handled_by'] = $handledByFilterIds;
        }
        if ($tab === 'provider') {
            if ($filterStatusIds !== []) {
                $queryParams['status_ids'] = $filterStatusIds;
            }
            if ($filterDistrictIds !== []) {
                $queryParams['district_ids'] = $filterDistrictIds;
            }
            if ($filterZoneIds !== []) {
                $queryParams['zone_ids'] = $filterZoneIds;
            }
            if ($filterCategoryIds !== []) {
                $queryParams['category_ids'] = $filterCategoryIds;
            }
        }
        if ($tab === 'customer') {
            if ($filterCustomerStatusIds !== []) {
                $queryParams['customer_status_ids'] = $filterCustomerStatusIds;
            }
            if ($filterCustomerZoneIds !== []) {
                $queryParams['customer_zone_ids'] = $filterCustomerZoneIds;
            }
            if ($filterCustomerCategoryIds !== []) {
                $queryParams['customer_category_ids'] = $filterCustomerCategoryIds;
            }
            if ($filterCustomerSubCategoryIds !== []) {
                $queryParams['customer_sub_category_ids'] = $filterCustomerSubCategoryIds;
            }
            if ($estimatedDateFrom) {
                $queryParams['estimated_date_from'] = $estimatedDateFrom;
            }
            if ($estimatedDateTo) {
                $queryParams['estimated_date_to'] = $estimatedDateTo;
            }
        }

        $leads = $query->paginate(pagination_limit())->appends($queryParams);

        // handled_by stores the user ID (string/UUID). Build a map id => display name.
        $handledByIds = $leads->pluck('handled_by')
            ->filter(fn ($value) => !empty($value))
            ->unique()
            ->values()
            ->all();

        $handledByNames = [];
        if (!empty($handledByIds)) {
            $users = User::whereIn('id', $handledByIds)->get(['id', 'first_name', 'last_name', 'email']);
            foreach ($users as $user) {
                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                $handledByNames[(string) $user->id] = $fullName ?: $user->email;
            }
        }

        $filterSources = Source::active()->orderBy('name')->get(['id', 'name']);
        $filterAdSources = AdSource::active()->orderBy('name')->get(['id', 'name']);
        $filterEmployees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
            ->ofStatus(1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        $filterProviderStatuses = ProviderLeadStatus::where('is_active', true)->orderBy('name')->get(['id', 'name', 'color']);
        $filterCustomerStatuses = CustomerLeadStatus::where('is_active', true)->orderBy('name')->get(['id', 'name', 'color']);
        $filterDistricts = District::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $filterZones = Zone::ofStatus(1)->orderBy('name')->get(['id', 'name']);
        $filterCategories = Category::withoutGlobalScopes()->ofType('main')->orderBy('name')->get(['id', 'name']);
        $filterSubCategories = Category::withoutGlobalScopes()->ofType('sub')->orderBy('name')->get(['id', 'name']);

        $providerLeadData = [];
        if ($tab === 'provider' && $leads->isNotEmpty()) {
            $leadIds = $leads->pluck('id')->all();
            $histories = LeadTypeHistory::whereIn('lead_id', $leadIds)
                ->where('type', 'provider')
                ->orderByDesc('created_at')
                ->get();
            $latestByLead = $histories->groupBy('lead_id')->map(fn ($group) => $group->first());

            $statusIds = [];
            $districtIds = [];
            $zoneIds = [];
            $categoryIds = [];
            $cancelReasonIds = [];
            foreach ($latestByLead as $h) {
                $d = is_array($h->data) ? $h->data : [];
                if (!empty($d['provider_lead_status_id'])) {
                    $statusIds[] = $d['provider_lead_status_id'];
                }
                if (!empty($d['district_id'])) {
                    $districtIds[] = $d['district_id'];
                }
                if (!empty($d['zone_id'])) {
                    $zoneIds[] = $d['zone_id'];
                }
                if (!empty($d['provider_service_category'])) {
                    $categoryIds[] = $d['provider_service_category'];
                }
                if (!empty($d['provider_cancellation_reason_id'])) {
                    $cancelReasonIds[] = $d['provider_cancellation_reason_id'];
                }
            }
            $statuses = ProviderLeadStatus::whereIn('id', array_unique($statusIds))->get()->keyBy('id');
            $districts = District::whereIn('id', array_unique($districtIds))->get()->keyBy('id');
            $zones = Zone::withoutGlobalScopes()->whereIn('id', array_unique($zoneIds))->get()->keyBy('id');
            $categories = Category::withoutGlobalScopes()->whereIn('id', array_unique($categoryIds))->get()->keyBy('id');
            $cancelReasons = $cancelReasonIds !== []
                ? ProviderCancellationReason::whereIn('id', array_unique($cancelReasonIds))->get()->keyBy('id')
                : collect();

            $checklistDone = LeadProviderChecklist::whereIn('lead_id', $leadIds)
                ->where('is_done', true)
                ->selectRaw('lead_id, count(*) as cnt')
                ->groupBy('lead_id')
                ->pluck('cnt', 'lead_id')
                ->all();
            $checklistTotal = ProviderChecklistItem::active()->count();

            foreach ($leadIds as $lid) {
                $h = $latestByLead->get($lid);
                $d = ($h && is_array($h->data)) ? $h->data : [];
                $statusId = $d['provider_lead_status_id'] ?? null;
                $status = $statusId ? $statuses->get($statusId) : null;
                $districtId = $d['district_id'] ?? null;
                $zoneId = $d['zone_id'] ?? null;
                $categoryId = $d['provider_service_category'] ?? null;
                $cancelReasonId = $d['provider_cancellation_reason_id'] ?? null;
                $cancelReason = $cancelReasonId ? $cancelReasons->get($cancelReasonId) : null;
                $providerLeadData[$lid] = [
                    'status_name' => $status?->name ?? '—',
                    'status_color' => $status && !empty($status->color) ? $status->color : '#0d6efd',
                    'district_name' => $districtId ? ($districts->get($districtId)?->name ?? '—') : '—',
                    'zone_name' => $zoneId ? ($zones->get($zoneId)?->name ?? '—') : '—',
                    'category_name' => $categoryId ? ($categories->get($categoryId)?->name ?? '—') : '—',
                    'cancellation_reason' => $cancelReason?->name ?? '—',
                    'checklist_done' => (int) ($checklistDone[$lid] ?? 0),
                    'checklist_total' => (int) $checklistTotal,
                ];
            }
        }

        $customerLeadData = [];
        if ($tab === 'customer' && $leads->isNotEmpty()) {
            $leadIds = $leads->pluck('id')->all();
            $histories = LeadTypeHistory::whereIn('lead_id', $leadIds)
                ->where('type', 'customer')
                ->orderByDesc('created_at')
                ->get();
            $latestByLead = $histories->groupBy('lead_id')->map(fn ($group) => $group->first());

            // Fallback source of truth: bookings table (when history doesn't have booking_id yet)
            $latestBookingByLead = Booking::whereIn('lead_id', $leadIds)
                ->orderByDesc('created_at')
                ->get(['id', 'lead_id', 'readable_id'])
                ->groupBy('lead_id')
                ->map(fn ($group) => $group->first());

            $statusIds = [];
            $zoneIds = [];
            $categoryIds = [];
            $subCategoryIds = [];
            $cancelReasonIds = [];
            $bookingIds = [];
            foreach ($latestByLead as $h) {
                $d = is_array($h->data) ? $h->data : [];
                if (!empty($d['customer_lead_status_id'])) {
                    $statusIds[] = $d['customer_lead_status_id'];
                }
                if (!empty($d['zone_id'])) {
                    $zoneIds[] = $d['zone_id'];
                }
                if (!empty($d['service_category'])) {
                    $categoryIds[] = $d['service_category'];
                }
                if (!empty($d['service_subcategory'])) {
                    $subCategoryIds[] = $d['service_subcategory'];
                }
                if (!empty($d['cancellation_reason_id'])) {
                    $cancelReasonIds[] = $d['cancellation_reason_id'];
                }
                if (!empty($d['booking_id'])) {
                    $bookingIds[] = $d['booking_id'];
                }
            }
            $statuses = CustomerLeadStatus::whereIn('id', array_unique($statusIds))->get()->keyBy('id');
            $zones = Zone::withoutGlobalScopes()->whereIn('id', array_unique($zoneIds))->get()->keyBy('id');
            $categories = Category::withoutGlobalScopes()->whereIn('id', array_unique($categoryIds))->get()->keyBy('id');
            $subCategories = Category::withoutGlobalScopes()->ofType('sub')->whereIn('id', array_unique($subCategoryIds))->get()->keyBy('id');
            $cancelReasons = $cancelReasonIds !== []
                ? LeadCancellationReason::whereIn('id', array_unique($cancelReasonIds))->get()->keyBy('id')
                : collect();
            $bookings = $bookingIds !== []
                ? Booking::whereIn('id', array_unique($bookingIds))->get(['id', 'readable_id'])->keyBy('id')
                : collect();

            foreach ($leadIds as $lid) {
                $h = $latestByLead->get($lid);
                $d = ($h && is_array($h->data)) ? $h->data : [];
                $statusId = $d['customer_lead_status_id'] ?? null;
                $status = $statusId ? $statuses->get($statusId) : null;
                $zoneId = $d['zone_id'] ?? null;
                $categoryId = $d['service_category'] ?? null;
                $subCategoryId = $d['service_subcategory'] ?? null;
                $cancelReasonId = $d['cancellation_reason_id'] ?? null;
                $cancelReason = $cancelReasonId ? $cancelReasons->get($cancelReasonId) : null;
                $estAt = $d['estimated_service_at'] ?? null;
                $estAtFormatted = $estAt ? (function () use ($estAt) {
                    try {
                        return \Carbon\Carbon::parse($estAt)->format('d F Y h:i a');
                    } catch (\Throwable $e) {
                        return '—';
                    }
                })() : '—';

                $bookingId = $d['booking_id'] ?? null;
                $booking = $bookingId ? $bookings->get($bookingId) : null;
                if (!$booking) {
                    $booking = $latestBookingByLead->get($lid);
                }
                $customerLeadData[$lid] = [
                    'status_name' => $status?->name ?? '—',
                    'status_color' => $status && !empty($status->color) ? $status->color : '#0d6efd',
                    'status_base_type' => $status?->base_type ?? 'pending',
                    'zone_name' => $zoneId ? ($zones->get($zoneId)?->name ?? '—') : '—',
                    'category_name' => $categoryId ? ($categories->get($categoryId)?->name ?? '—') : '—',
                    'sub_category_name' => $subCategoryId ? ($subCategories->get($subCategoryId)?->name ?? '—') : '—',
                    'cancellation_reason' => $cancelReason?->name ?? '—',
                    'estimated_service_at' => $estAtFormatted,
                    'booking_id' => $booking?->id,
                    'booking_readable_id' => $booking?->readable_id,
                ];
            }
        }

        $reasonLeadData = [];
        if (in_array($tab, ['invalid', 'future_customer'], true) && $leads->isNotEmpty()) {
            $leadIds = $leads->pluck('id')->all();
            $histories = LeadTypeHistory::whereIn('lead_id', $leadIds)
                ->where('type', $tab)
                ->orderByDesc('created_at')
                ->get();
            $latestByLead = $histories->groupBy('lead_id')->map(fn ($group) => $group->first());
            if ($tab === 'invalid') {
                $reasonIds = $latestByLead->map(fn ($h) => is_array($h->data ?? null) ? ($h->data['invalid_reason_id'] ?? null) : null)->filter()->unique()->values()->all();
                $reasons = LeadInvalidReason::whereIn('id', $reasonIds)->get()->keyBy('id');
                foreach ($leadIds as $lid) {
                    $h = $latestByLead->get($lid);
                    $reasonId = ($h && is_array($h->data ?? null)) ? ($h->data['invalid_reason_id'] ?? null) : null;
                    $reasonLeadData[$lid] = $reasonId ? ($reasons->get($reasonId)?->name ?? '—') : '—';
                }
            } else {
                $reasonIds = $latestByLead->map(fn ($h) => is_array($h->data ?? null) ? ($h->data['future_customer_reason_id'] ?? null) : null)->filter()->unique()->values()->all();
                $reasons = LeadFutureCustomerReason::whereIn('id', $reasonIds)->get()->keyBy('id');
                foreach ($leadIds as $lid) {
                    $h = $latestByLead->get($lid);
                    $reasonId = ($h && is_array($h->data ?? null)) ? ($h->data['future_customer_reason_id'] ?? null) : null;
                    $reasonLeadData[$lid] = $reasonId ? ($reasons->get($reasonId)?->name ?? '—') : '—';
                }
            }
        }

        $leadStatusMeta = [];
        if ($leads->isNotEmpty()) {
            $leadStatusMeta = $this->buildLeadStatusMeta($leads->getCollection());
        }

        if ($request->ajax() || $request->boolean('ajax')) {
            $filtersAppliedCount = count($sourceIds) + count($adSourceIds) + count($handledByFilterIds)
                + (!empty($dateFrom) && !empty($dateTo) ? 1 : 0);
            if ($tab === 'provider') {
                $filtersAppliedCount += count($filterStatusIds) + count($filterDistrictIds) + count($filterZoneIds) + count($filterCategoryIds);
            }
            if ($tab === 'customer') {
                $filtersAppliedCount += count($filterCustomerStatusIds) + count($filterCustomerZoneIds) + count($filterCustomerCategoryIds) + count($filterCustomerSubCategoryIds) + (!empty($estimatedDateFrom) && !empty($estimatedDateTo) ? 1 : 0);
            }
            $html = view('leadmanagement::admin.leads.partials._table', compact('leads', 'handledByNames', 'tab', 'providerLeadData', 'customerLeadData', 'reasonLeadData', 'leadStatusMeta'))->render();
            return response()->json([
                'html' => $html,
                'total' => $leads->total(),
                'filters_applied_count' => $filtersAppliedCount,
            ]);
        }

        return view('leadmanagement::admin.leads.index', compact(
            'leads',
            'tab',
            'handledByNames',
            'providerLeadData',
            'customerLeadData',
            'reasonLeadData',
            'leadStatusMeta',
            'filterSources',
            'filterAdSources',
            'filterEmployees',
            'filterProviderStatuses',
            'filterCustomerStatuses',
            'filterDistricts',
            'filterZones',
            'filterCategories',
            'filterSubCategories',
            'search',
            'sourceIds',
            'adSourceIds',
            'handledByFilterIds',
            'filterStatusIds',
            'filterDistrictIds',
            'filterZoneIds',
            'filterCategoryIds',
            'filterCustomerStatusIds',
            'filterCustomerZoneIds',
            'filterCustomerCategoryIds',
            'filterCustomerSubCategoryIds',
            'estimatedDateFrom',
            'estimatedDateTo',
            'dateFrom',
            'dateTo'
        ));
    }

    public function create(): View
    {
        $sources = Source::active()->orderBy('name')->get();
        $adSources = AdSource::active()->orderBy('name')->get();
        $employees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
            ->ofStatus(1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
        $currentEmployeeId = Auth::id();

        return view('leadmanagement::admin.leads.create', compact('sources', 'adSources', 'employees', 'currentEmployeeId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:32',
            'source_id' => 'nullable|exists:sources,id',
            'lead_type' => 'required|in:unknown,customer,provider,invalid,future_customer',
            'date_time_of_lead_received' => 'nullable|date',
            'ad_source_id' => 'nullable|exists:adsources,id',
            'handled_by' => 'nullable|string|max:64',
            'remarks' => 'nullable|string|max:1000',
            'next_followup_at' => 'nullable|date',
        ]);

        $validated['created_by'] = Auth::id();
        $lead = Lead::create($validated);

        $addedByUser = Auth::user();
        $addedByName = $addedByUser
            ? (trim(($addedByUser->first_name ?? '') . ' ' . ($addedByUser->last_name ?? '')) ?: $addedByUser->email)
            : '—';
        $addedAt = $lead->created_at?->format('d F Y h:i a') ?? now()->format('d F Y h:i a');

        LeadChangeLog::create([
            'lead_id' => $lead->id,
            'changed_by' => Auth::id(),
            'changes' => [
                'lead_added' => [
                    'label' => 'Lead_Added',
                    'old' => '—',
                    'new' => translate('Added_by') . ' ' . $addedByName . ' ' . translate('on') . ' ' . $addedAt,
                ],
            ],
        ]);

        toastr()->success(translate('Lead created successfully'));

        return redirect()->route('admin.lead.index');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $lead = Lead::findOrFail($id);

        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|required|string|max:32',
            'source_id' => 'sometimes|nullable|exists:sources,id',
            'lead_type' => 'sometimes|required|in:unknown,customer,provider,invalid,future_customer',
            'date_time_of_lead_received' => 'sometimes|nullable|date',
            'ad_source_id' => 'sometimes|nullable|exists:adsources,id',
            'handled_by' => 'sometimes|nullable|string|max:64',
            'remarks' => 'sometimes|nullable|string|max:1000',
            'next_followup_at' => 'sometimes|nullable|date',
        ];

        $validated = $request->validate($rules);

        if (empty($validated)) {
            $url = route('admin.lead.show', $lead->id);
            if ($request->boolean('in_modal')) {
                $url .= '?in_modal=1';
            }
            return redirect($url);
        }

        $keys = array_keys($validated);
        $oldValues = $lead->only($keys);

        $lead->update($validated);

        $changes = $this->buildLeadChangeDiff($oldValues, $validated);
        if (!empty($changes)) {
            LeadChangeLog::create([
                'lead_id' => $lead->id,
                'changed_by' => Auth::id(),
                'changes' => $changes,
            ]);
        }

        toastr()->success(translate('Lead_updated_successfully'));

        $url = route('admin.lead.show', $lead->id);
        if ($request->boolean('in_modal')) {
            $url .= '?in_modal=1';
        }
        return redirect($url);
    }

    /**
     * Build change log array: field_key => ['label' => ..., 'old' => display, 'new' => display]
     */
    protected function buildLeadChangeDiff(array $oldValues, array $newValues): array
    {
        $diff = [];
        $fieldLabels = [
            'name' => 'Name',
            'phone_number' => 'Phone_Number',
            'source_id' => 'Source',
            'ad_source_id' => 'Ad_Source',
            'handled_by' => 'Handled_By',
            'lead_type' => 'Lead_Type',
            'date_time_of_lead_received' => 'Recieved_On',
            'next_followup_at' => 'Followup_On',
            'remarks' => 'Initial_Remarks',
        ];
        $dateFields = ['date_time_of_lead_received', 'next_followup_at'];

        foreach ($fieldLabels as $key => $labelKey) {
            $old = $oldValues[$key] ?? null;
            $new = $newValues[$key] ?? null;

            if (in_array($key, $dateFields, true)) {
                $oldNorm = $this->normalizeDateTimeForCompare($old);
                $newNorm = $this->normalizeDateTimeForCompare($new);
            } else {
                $oldNorm = ($old === null || $old === '') ? '' : (string) $old;
                $newNorm = ($new === null || $new === '') ? '' : (string) $new;
            }

            if ($oldNorm === $newNorm) {
                continue;
            }
            $diff[$key] = [
                'label' => $labelKey,
                'old' => $this->leadChangeDisplayValue($key, $old),
                'new' => $this->leadChangeDisplayValue($key, $new),
            ];
        }
        return $diff;
    }

    /**
     * Normalize datetime for comparison so model Carbon and request "Y-m-d\TH:i" match when same.
     */
    protected function normalizeDateTimeForCompare(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    protected function leadChangeDisplayValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if ($key === 'source_id') {
            $source = Source::find($value);
            return $source ? $source->name : (string) $value;
        }
        if ($key === 'ad_source_id') {
            $ad = AdSource::find($value);
            return $ad ? $ad->name : (string) $value;
        }
        if ($key === 'handled_by') {
            $user = User::find($value);
            if ($user) {
                $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                return $name ?: $user->email;
            }
            return (string) $value;
        }
        if ($key === 'lead_type') {
            return Lead::leadTypes()[$value] ?? (string) $value;
        }
        if ($key === 'date_time_of_lead_received' || $key === 'next_followup_at') {
            try {
                return \Carbon\Carbon::parse($value)->format('d F Y h:i a');
            } catch (\Throwable $e) {
                return (string) $value;
            }
        }
        return (string) $value;
    }

    public function updateType(Request $request, int $id): RedirectResponse
    {
        $lead = Lead::findOrFail($id);

        $leadType = $request->input('lead_type');

        if (!in_array($leadType, ['customer', 'provider', 'invalid', 'future_customer'], true)) {
            abort(400, 'Invalid lead type');
        }

        if ($leadType === 'invalid') {
            $data = $request->validate([
                'invalid_reason_id' => 'required|exists:lead_invalid_reasons,id',
                'invalid_remarks' => 'nullable|string|max:1000',
            ]);

            LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => 'invalid',
                'data' => $data,
                'created_by' => Auth::id(),
            ]);
        } elseif ($leadType === 'future_customer') {
            $data = $request->validate([
                'future_customer_reason_id' => 'required|exists:lead_future_customer_reasons,id',
                'future_customer_remarks' => 'nullable|string|max:1000',
            ]);

            LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => 'future_customer',
                'data' => $data,
                'created_by' => Auth::id(),
            ]);
        } elseif ($leadType === 'customer') {
            $request->merge([
                'zone_id' => $request->input('zone_id') ?: null,
                'service_category' => $request->input('service_category') ?: null,
                'service_subcategory' => $request->input('service_subcategory') ?: null,
                'service_name' => $request->input('service_name') ?: null,
                'customer_lead_status_id' => $request->input('customer_lead_status_id') ?: null,
            ]);
            $data = $request->validate([
                'zone_id' => 'nullable|exists:zones,id',
                'service_category' => 'nullable|exists:categories,id',
                'service_subcategory' => 'nullable|exists:categories,id',
                'service_name' => 'nullable|exists:services,id',
                'variant_key' => 'nullable|string|max:255',
                'service_description' => 'nullable|string|max:1000',
                'estimated_service_at' => 'nullable|date',
                'customer_lead_status_id' => 'nullable|exists:customer_lead_statuses,id',
            ]);

            $isUpdateCustomer = $request->boolean('update_customer') && $lead->lead_type === Lead::TYPE_CUSTOMER;

            if ($isUpdateCustomer) {
                $customerHistory = LeadTypeHistory::where('lead_id', $lead->id)
                    ->where('type', 'customer')
                    ->latest()
                    ->first();
                $payload = array_merge($data, ['booking_status' => $customerHistory->data['booking_status'] ?? 'pending']);
                if ($customerHistory) {
                    $customerHistory->update(['data' => $payload]);
                } else {
                    LeadTypeHistory::create([
                        'lead_id' => $lead->id,
                        'type' => 'customer',
                        'data' => array_merge($data, ['booking_status' => 'pending']),
                        'created_by' => Auth::id(),
                    ]);
                }
                toastr()->success(translate('Customer_lead_information_updated_successfully'));
                $url = route('admin.lead.show', $lead->id);
                if ($request->boolean('in_modal')) {
                    $url .= '?in_modal=1';
                }
                return redirect($url);
            }

            LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => 'customer',
                'data' => array_merge($data, [
                    'booking_status' => 'pending',
                ]),
                'created_by' => Auth::id(),
            ]);
        } elseif ($leadType === 'provider') {
            $request->merge([
                'district_id' => $request->input('district_id') ?: null,
                'provider_lead_status_id' => $request->input('provider_lead_status_id') ?: null,
                'zone_id' => $request->input('zone_id') ?: null,
                'provider_service_category' => $request->input('provider_service_category') ?: null,
                'provider_service_subcategory' => $request->input('provider_service_subcategory') ?: null,
            ]);
            $data = $request->validate([
                'district_id' => 'nullable|exists:districts,id',
                'full_address' => 'nullable|string|max:1000',
                'service_areas' => 'nullable|string|max:1000',
                'provider_lead_status_id' => 'nullable|exists:provider_lead_statuses,id',
                'zone_id' => 'nullable|exists:zones,id',
                'provider_service_category' => 'nullable|exists:categories,id',
                'provider_service_subcategory' => 'nullable|exists:categories,id',
                'provider_service_details' => 'nullable|string|max:1000',
            ]);

            $isUpdateProvider = $request->boolean('update_provider') && $lead->lead_type === Lead::TYPE_PROVIDER;

            if ($isUpdateProvider) {
                $providerHistory = LeadTypeHistory::where('lead_id', $lead->id)
                    ->where('type', 'provider')
                    ->latest()
                    ->first();
                if ($providerHistory) {
                    $providerHistory->update(['data' => $data]);
                } else {
                    LeadTypeHistory::create([
                        'lead_id' => $lead->id,
                        'type' => 'provider',
                        'data' => $data,
                        'created_by' => Auth::id(),
                    ]);
                }
                toastr()->success(translate('Provider_lead_information_updated_successfully'));
                $url = route('admin.lead.show', $lead->id);
                if ($request->boolean('in_modal')) {
                    $url .= '?in_modal=1';
                }
                return redirect($url);
            }

            LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => 'provider',
                'data' => $data,
                'created_by' => Auth::id(),
            ]);
        }

        $oldType = $lead->lead_type;
        $lead->lead_type = $leadType;
        $lead->save();

        LeadChangeLog::create([
            'lead_id' => $lead->id,
            'changed_by' => Auth::id(),
            'changes' => [
                'lead_type' => [
                    'label' => 'Lead_Type',
                    'old' => $this->leadChangeDisplayValue('lead_type', $oldType),
                    'new' => $this->leadChangeDisplayValue('lead_type', $leadType),
                ],
            ],
        ]);

        toastr()->success(translate('Lead_type_updated_successfully'));

        $url = route('admin.lead.show', $lead->id);
        if ($request->boolean('in_modal')) {
            $url .= '?in_modal=1';
        }
        return redirect($url);
    }

    public function show($id): View
    {
        $lead = Lead::with(['source', 'adSource', 'followups.createdBy', 'changeLogs.changedByUser', 'createdBy', 'customerLeadTags'])->findOrFail($id);

        $invalidReasons = LeadInvalidReason::where('is_active', true)->orderBy('name')->get();
        $futureCustomerReasons = LeadFutureCustomerReason::where('is_active', true)->orderBy('name')->get();
        $cancellationReasons = LeadCancellationReason::where('is_active', true)->orderBy('name')->get();
        $customerLeadStatuses = CustomerLeadStatus::where('is_active', true)->orderBy('name')->get();
        $customerLeadTags = CustomerLeadTag::where('is_active', true)->orderBy('name')->get();
        $providerLeadStatuses = ProviderLeadStatus::where('is_active', true)->orderBy('name')->get();
        $providerCancellationReasons = ProviderCancellationReason::where('is_active', true)->orderBy('name')->get();
        $districts = District::where('is_active', true)->orderBy('name')->get();
        $zones = Zone::ofStatus(1)->orderBy('name')->get();

        $handledByName = null;
        if ($lead->handled_by) {
            $user = User::find($lead->handled_by);
            if ($user) {
                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                $handledByName = $fullName ?: $user->email;
            }
        }

        $addedByName = null;
        if ($lead->createdBy) {
            $creator = $lead->createdBy;
            $fullName = trim(($creator->first_name ?? '') . ' ' . ($creator->last_name ?? ''));
            $addedByName = $fullName ?: $creator->email;
        }

        $typeHistory = LeadTypeHistory::where('lead_id', $lead->id)
            ->where('type', $lead->lead_type)
            ->latest()
            ->first();

        $typeHistoryDisplay = $this->resolveTypeHistoryDisplay($typeHistory, $lead->lead_type);
        $leadOpenStatus = $this->isLeadOpenByTypeHistory($lead, $typeHistory);

        // Support both query params: in_modal=1 (legacy) and inModal=1 (some redirects)
        $inModal = request()->boolean('in_modal') || request()->boolean('inModal');

        $leadBooking = null;
        if ($lead->lead_type === Lead::TYPE_CUSTOMER && $typeHistory && is_array($typeHistory->data ?? null)) {
            $bookingId = $typeHistory->data['booking_id'] ?? null;
            if ($bookingId) {
                $booking = Booking::find($bookingId, ['id', 'readable_id']);
                if ($booking) {
                    $leadBooking = [
                        'id' => $booking->id,
                        'readable_id' => $booking->readable_id,
                    ];
                }
            }
        }
        if (!$leadBooking && $lead->lead_type === Lead::TYPE_CUSTOMER) {
            $booking = Booking::where('lead_id', $lead->id)->orderByDesc('created_at')->first(['id', 'readable_id']);
            if ($booking) {
                $leadBooking = [
                    'id' => $booking->id,
                    'readable_id' => $booking->readable_id,
                ];
            }
        }

        $sources = Source::active()->orderBy('name')->get();
        $adSources = AdSource::active()->orderBy('name')->get();
        $employees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
            ->ofStatus(1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        $changeLogs = $lead->changeLogs;

        $providerChecklistItems = collect();
        $providerChecklistDoneMap = [];
        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $providerChecklistItems = ProviderChecklistItem::active()->orderBy('name')->get();
            $providerChecklistDoneMap = LeadProviderChecklist::where('lead_id', $lead->id)
                ->get()
                ->keyBy('provider_checklist_item_id')
                ->map(fn ($row) => $row->is_done)
                ->all();
        }

        return view('leadmanagement::admin.leads.show', compact(
            'lead',
            'handledByName',
            'addedByName',
            'invalidReasons',
            'futureCustomerReasons',
            'cancellationReasons',
            'customerLeadStatuses',
            'customerLeadTags',
            'providerLeadStatuses',
            'providerCancellationReasons',
            'districts',
            'zones',
            'typeHistory',
            'typeHistoryDisplay',
            'leadOpenStatus',
            'inModal',
            'leadBooking',
            'sources',
            'providerChecklistItems',
            'providerChecklistDoneMap',
            'adSources',
            'employees',
            'changeLogs'
        ));
    }

    public function updateProviderChecklist(Request $request, int $id, int $checklistItem): RedirectResponse|JsonResponse
    {
        $lead = Lead::findOrFail($id);
        if ($lead->lead_type !== Lead::TYPE_PROVIDER) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Lead is not a provider'], 422);
            }
            return redirect()->route('admin.lead.show', $lead->id)->with('error', translate('Lead_is_not_a_provider'));
        }

        ProviderChecklistItem::active()->findOrFail($checklistItem);

        $entry = LeadProviderChecklist::firstOrCreate(
            [
                'lead_id' => $lead->id,
                'provider_checklist_item_id' => $checklistItem,
            ],
            ['is_done' => false]
        );
        $entry->is_done = $request->boolean('is_done');
        $entry->save();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'is_done' => $entry->is_done]);
        }
        return back()->with('success', translate('Checklist_updated_successfully'));
    }

    public function updateProviderChecklistBulk(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $lead = Lead::findOrFail($id);
        if ($lead->lead_type !== Lead::TYPE_PROVIDER) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Lead is not a provider'], 422);
            }
            return redirect()->route('admin.lead.show', $lead->id)->with('error', translate('Lead_is_not_a_provider'));
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.provider_checklist_item_id' => 'required|exists:provider_checklist_items,id',
            'items.*.is_done' => 'required',
        ]);

        $itemIds = array_column($validated['items'], 'provider_checklist_item_id');
        $itemsById = ProviderChecklistItem::whereIn('id', $itemIds)->get()->keyBy('id');

        $oldEntries = LeadProviderChecklist::where('lead_id', $lead->id)
            ->whereIn('provider_checklist_item_id', $itemIds)
            ->get()
            ->keyBy('provider_checklist_item_id');

        $changes = [];
        foreach ($validated['items'] as $row) {
            $itemId = (int) $row['provider_checklist_item_id'];
            $wasDone = $oldEntries->get($itemId)?->is_done ?? false;
            $isDone = filter_var($row['is_done'], FILTER_VALIDATE_BOOLEAN);
            if ($wasDone === $isDone) {
                continue;
            }
            $name = $itemsById->get($itemId)?->name ?? (string) $itemId;
            $changes['provider_checklist_' . $itemId] = [
                'label' => $name,
                'old' => $wasDone ? translate('Done') : translate('Pending'),
                'new' => $isDone ? translate('Done') : translate('Pending'),
            ];
        }

        foreach ($validated['items'] as $row) {
            $isDone = filter_var($row['is_done'], FILTER_VALIDATE_BOOLEAN);
            LeadProviderChecklist::updateOrCreate(
                [
                    'lead_id' => $lead->id,
                    'provider_checklist_item_id' => $row['provider_checklist_item_id'],
                ],
                ['is_done' => $isDone]
            );
        }

        if (!empty($changes)) {
            LeadChangeLog::create([
                'lead_id' => $lead->id,
                'changed_by' => Auth::id(),
                'changes' => $changes,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', translate('Checklist_updated_successfully'));
    }

    public function updateProviderStatus(Request $request, int $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        if ($lead->lead_type !== Lead::TYPE_PROVIDER) {
            return response()->json(['message' => translate('Lead_is_not_a_provider')], 422);
        }

        $validated = $request->validate([
            'provider_lead_status_id' => 'nullable|exists:provider_lead_statuses,id',
            'provider_cancellation_reason_id' => 'nullable|exists:provider_cancellation_reasons,id',
            'provider_cancellation_remarks' => 'nullable|string|max:1000',
        ]);
        $statusId = $validated['provider_lead_status_id'] ?? null;

        $statusModel = $statusId ? ProviderLeadStatus::find($statusId) : null;
        $baseType = $statusModel?->base_type ?? 'pending';

        if ($baseType === 'cancel') {
            $request->validate([
                'provider_cancellation_reason_id' => 'required|exists:provider_cancellation_reasons,id',
            ]);
        }

        $history = LeadTypeHistory::where('lead_id', $lead->id)
            ->where('type', 'provider')
            ->latest()
            ->first();

        $data = $history && is_array($history->data) ? $history->data : [];
        $data['provider_lead_status_id'] = $statusId;
        if ($baseType === 'cancel') {
            $data['provider_cancellation_reason_id'] = $request->input('provider_cancellation_reason_id');
            $data['provider_cancellation_remarks'] = $request->input('provider_cancellation_remarks');
        } else {
            unset($data['provider_cancellation_reason_id'], $data['provider_cancellation_remarks']);
        }
        if ($history) {
            $history->update(['data' => $data]);
        } else {
            LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => 'provider',
                'data' => $data,
                'created_by' => Auth::id(),
            ]);
        }

        $statusName = $statusModel?->name ?? '—';
        $statusColor = $statusModel && !empty($statusModel->color) ? $statusModel->color : '#0d6efd';
        return response()->json(['success' => true, 'status_name' => $statusName, 'status_color' => $statusColor]);
    }

    public function updateCustomerStatus(Request $request, int $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        if ($lead->lead_type !== Lead::TYPE_CUSTOMER) {
            return response()->json(['message' => translate('Lead_is_not_a_customer')], 422);
        }

        $validated = $request->validate([
            'customer_lead_status_id' => 'nullable|exists:customer_lead_statuses,id',
            'cancellation_reason_id' => 'nullable|exists:lead_cancellation_reasons,id',
            'cancellation_remarks' => 'nullable|string|max:1000',
        ]);
        $statusId = $validated['customer_lead_status_id'] ?? null;

        $statusModel = $statusId ? CustomerLeadStatus::find($statusId) : null;
        $baseType = $statusModel?->base_type ?? 'pending';

        if ($baseType === 'cancel') {
            $request->validate([
                'cancellation_reason_id' => 'required|exists:lead_cancellation_reasons,id',
            ]);
        }

        $history = LeadTypeHistory::where('lead_id', $lead->id)
            ->where('type', 'customer')
            ->latest()
            ->first();

        $data = $history && is_array($history->data) ? $history->data : [];
        $data['customer_lead_status_id'] = $statusId;
        if ($baseType === 'cancel') {
            $data['cancellation_reason_id'] = $request->input('cancellation_reason_id');
            $data['cancellation_remarks'] = $request->input('cancellation_remarks');
        } else {
            unset($data['cancellation_reason_id'], $data['cancellation_remarks']);
        }
        if ($history) {
            $history->update(['data' => $data]);
        } else {
            LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => 'customer',
                'data' => $data,
                'created_by' => Auth::id(),
            ]);
        }

        $statusName = $statusModel?->name ?? '—';
        $statusColor = $statusModel && !empty($statusModel->color) ? $statusModel->color : '#0d6efd';
        return response()->json(['success' => true, 'status_name' => $statusName, 'status_color' => $statusColor]);
    }

    public function updateCustomerTags(Request $request, int $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        if ($lead->lead_type !== Lead::TYPE_CUSTOMER) {
            return response()->json(['message' => translate('Lead_is_not_a_customer')], 422);
        }

        $validated = $request->validate([
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:customer_lead_tags,id',
        ]);
        $tagIds = $validated['tag_ids'] ?? [];
        $lead->customerLeadTags()->sync($tagIds);

        $tags = $lead->customerLeadTags()
            ->orderBy('customer_lead_tags.name')
            ->get(['customer_lead_tags.id as id', 'customer_lead_tags.name as name', 'customer_lead_tags.color as color']);
        return response()->json([
            'success' => true,
            'tags' => $tags->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color ?? '#0d6efd'])->values()->all(),
        ]);
    }

    public function storeCustomerLeadTag(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);
        $tag = CustomerLeadTag::create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#0d6efd',
            'is_active' => true,
        ]);
        return response()->json([
            'success' => true,
            'tag' => ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color ?? '#0d6efd'],
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    protected function resolveTypeHistoryDisplay(?LeadTypeHistory $typeHistory, string $leadType): array
    {
        if (!$typeHistory || !is_array($typeHistory->data)) {
            return [];
        }

        $data = $typeHistory->data;
        $rows = [];

        if ($leadType === 'invalid') {
            $reason = isset($data['invalid_reason_id'])
                ? LeadInvalidReason::find($data['invalid_reason_id'])?->name
                : null;
            $rows[] = ['label' => translate('Reason'), 'value' => $reason ?? '—'];
            $rows[] = ['label' => translate('Remarks'), 'value' => $data['invalid_remarks'] ?? '—'];
        } elseif ($leadType === 'future_customer') {
            $reason = isset($data['future_customer_reason_id'])
                ? LeadFutureCustomerReason::find($data['future_customer_reason_id'])?->name
                : null;
            $rows[] = ['label' => translate('Reason'), 'value' => $reason ?? '—'];
            $rows[] = ['label' => translate('Remarks'), 'value' => $data['future_customer_remarks'] ?? '—'];
        } elseif ($leadType === 'customer') {
            $zone = isset($data['zone_id']) ? Zone::withoutGlobalScopes()->find($data['zone_id']) : null;
            $category = isset($data['service_category']) ? Category::withoutGlobalScopes()->find($data['service_category']) : null;
            $subCategory = isset($data['service_subcategory']) ? Category::withoutGlobalScopes()->find($data['service_subcategory']) : null;
            $service = isset($data['service_name']) ? Service::withoutGlobalScopes()->find($data['service_name']) : null;
            $customerStatus = isset($data['customer_lead_status_id']) ? CustomerLeadStatus::find($data['customer_lead_status_id']) : null;

            $rows[] = ['label' => translate('Zone'), 'value' => $zone?->name ?? ($data['zone_id'] ?? '—')];
            $rows[] = ['label' => translate('Category'), 'value' => $category?->name ?? '—'];
            $rows[] = ['label' => translate('Sub_Category'), 'value' => $subCategory?->name ?? '—'];
            $rows[] = ['label' => translate('Service'), 'value' => $service?->name ?? '—'];
            $rows[] = ['label' => translate('Select_Service_Variant'), 'value' => $data['variant_key'] ?? '—'];
            $rows[] = ['label' => translate('Service_Additional_Details_(Optional)'), 'value' => $data['service_description'] ?? '—'];
            $est = $data['estimated_service_at'] ?? null;
            $rows[] = ['label' => translate('Estimated_Date_Time_of_Service'), 'value' => $est ? \Carbon\Carbon::parse($est)->format('d F Y h:i a') : '—'];
            $rows[] = ['label' => translate('Customer_Lead_Status'), 'value' => $customerStatus?->name ?? '—'];
            if (!empty($data['cancellation_reason_id'])) {
                $cancelReason = LeadCancellationReason::find($data['cancellation_reason_id']);
                $rows[] = ['label' => translate('Customer_cancellation_reasons'), 'value' => $cancelReason?->name ?? '—'];
            }
            if (!empty($data['cancellation_remarks'])) {
                $rows[] = ['label' => translate('Cancellation_Remarks'), 'value' => $data['cancellation_remarks']];
            }
            $headerStatusColor = $customerStatus && !empty($customerStatus->color) ? $customerStatus->color : '#0d6efd';
            return ['rows' => $rows, 'header_status' => $customerStatus?->name ?? '—', 'header_status_color' => $headerStatusColor];
        } elseif ($leadType === 'provider') {
            $district = isset($data['district_id']) ? District::find($data['district_id']) : null;
            $status = isset($data['provider_lead_status_id']) ? ProviderLeadStatus::find($data['provider_lead_status_id']) : null;
            $zone = isset($data['zone_id']) ? Zone::withoutGlobalScopes()->find($data['zone_id']) : null;
            $providerCategory = isset($data['provider_service_category']) ? Category::withoutGlobalScopes()->find($data['provider_service_category']) : null;
            $providerSubCategory = isset($data['provider_service_subcategory']) ? Category::withoutGlobalScopes()->find($data['provider_service_subcategory']) : null;

            $basic = [
                ['label' => translate('District'), 'value' => $district?->name ?? '—'],
                ['label' => translate('Full_Address'), 'value' => $data['full_address'] ?? '—'],
                ['label' => translate('Service_Areas'), 'value' => $data['service_areas'] ?? '—'],
            ];
            $service = [
                ['label' => translate('Zone'), 'value' => $zone?->name ?? '—'],
                ['label' => translate('Service_Category'), 'value' => $providerCategory?->name ?? (is_string($data['provider_service_category'] ?? null) ? ($data['provider_service_category'] ?? '—') : '—')],
                ['label' => translate('Sub_Category'), 'value' => $providerSubCategory?->name ?? '—'],
                ['label' => translate('Service_Details'), 'value' => $data['provider_service_details'] ?? '—'],
            ];
            if (!empty($data['provider_cancellation_reason_id'])) {
                $cancelReason = ProviderCancellationReason::find($data['provider_cancellation_reason_id']);
                $service[] = ['label' => translate('Provider_cancellation_reasons'), 'value' => $cancelReason?->name ?? '—'];
            }
            if (!empty($data['provider_cancellation_remarks'])) {
                $service[] = ['label' => translate('Cancellation_Remarks'), 'value' => $data['provider_cancellation_remarks']];
            }
            $headerStatusColor = $status && !empty($status->color) ? $status->color : '#0d6efd';
            return ['basic' => $basic, 'service' => $service, 'header_status' => $status?->name ?? '—', 'header_status_color' => $headerStatusColor];
        }

        return $rows;
    }

    /**
     * @param \Illuminate\Support\Collection<int, Lead> $leads
     * @return array<int, array{is_open: bool, label: string, badge_class: string}>
     */
    protected function buildLeadStatusMeta(\Illuminate\Support\Collection $leads): array
    {
        $leadIds = $leads->pluck('id')->all();
        if (empty($leadIds)) {
            return [];
        }

        $histories = LeadTypeHistory::whereIn('lead_id', $leadIds)
            ->whereIn('type', [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER])
            ->orderByDesc('created_at')
            ->get();

        $latestByComposite = [];
        foreach ($histories as $history) {
            $compositeKey = $history->lead_id . '|' . $history->type;
            if (!isset($latestByComposite[$compositeKey])) {
                $latestByComposite[$compositeKey] = $history;
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

        $customerStatuses = !empty($customerStatusIds)
            ? CustomerLeadStatus::whereIn('id', array_unique($customerStatusIds))->get()->keyBy('id')
            : collect();
        $providerStatuses = !empty($providerStatusIds)
            ? ProviderLeadStatus::whereIn('id', array_unique($providerStatusIds))->get()->keyBy('id')
            : collect();

        $meta = [];
        foreach ($leads as $lead) {
            $history = $latestByComposite[$lead->id . '|' . $lead->lead_type] ?? null;
            $isOpen = $this->isLeadOpenByTypeHistory($lead, $history, $customerStatuses, $providerStatuses);
            $meta[(int) $lead->id] = [
                'is_open' => $isOpen,
                'label' => $isOpen ? 'Open' : 'Closed',
                'badge_class' => $isOpen ? 'bg-danger' : 'bg-success',
            ];
        }

        return $meta;
    }

    /**
     * A lead is open if:
     * - unknown
     * - customer with pending base_type status
     * - provider with pending base_type status
     *
     * A lead is closed if:
     * - invalid / future_customer
     * - customer with completed/cancel base_type status
     * - provider with completed/cancel base_type status
     *
     * Missing customer/provider status defaults to pending (open).
     *
     * @param \Illuminate\Support\Collection<int, CustomerLeadStatus>|null $customerStatuses
     * @param \Illuminate\Support\Collection<int, ProviderLeadStatus>|null $providerStatuses
     */
    protected function isLeadOpenByTypeHistory(
        Lead $lead,
        ?LeadTypeHistory $typeHistory,
        ?\Illuminate\Support\Collection $customerStatuses = null,
        ?\Illuminate\Support\Collection $providerStatuses = null
    ): bool {
        if ($lead->lead_type === Lead::TYPE_UNKNOWN) {
            return true;
        }

        if (in_array($lead->lead_type, [Lead::TYPE_INVALID, Lead::TYPE_FUTURE_CUSTOMER], true)) {
            return false;
        }

        $data = ($typeHistory && is_array($typeHistory->data)) ? $typeHistory->data : [];

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $statusId = $data['customer_lead_status_id'] ?? null;
            if (!$statusId) {
                return true;
            }
            $status = $customerStatuses?->get((int) $statusId) ?? CustomerLeadStatus::find($statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
            return !in_array($baseType, ['completed', 'cancel'], true);
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $statusId = $data['provider_lead_status_id'] ?? null;
            if (!$statusId) {
                return true;
            }
            $status = $providerStatuses?->get((int) $statusId) ?? ProviderLeadStatus::find($statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
            return !in_array($baseType, ['completed', 'cancel'], true);
        }

        return false;
    }

    public function storeFollowup(Request $request, int $leadId): RedirectResponse
    {
        $lead = Lead::findOrFail($leadId);

        $validated = $request->validate([
            'followup_at' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
            'next_followup_at' => 'nullable|date',
        ]);

        $noMoreFollowup = $request->boolean('no_more_followup');

        if ($noMoreFollowup) {
            $validated['next_followup_at'] = null;
        }

        LeadFollowup::create([
            'lead_id' => $lead->id,
            'followup_at' => $validated['followup_at'],
            'remarks' => $validated['remarks'] ?? null,
            'next_followup_at' => $noMoreFollowup ? null : ($validated['next_followup_at'] ?? null),
            'created_by' => Auth::id(),
        ]);

        if (!$noMoreFollowup && !empty($validated['next_followup_at'])) {
            $lead->next_followup_at = $validated['next_followup_at'];
            $lead->save();
        } elseif ($noMoreFollowup) {
            $lead->next_followup_at = null;
            $lead->save();
        }

        toastr()->success(translate('Follow_up_added_successfully'));

        $url = route('admin.lead.show', $lead->id);
        if ($request->boolean('in_modal')) {
            $url .= '?in_modal=1';
        }
        return redirect($url);
    }

    public function destroy(int $id): RedirectResponse
    {
        $lead = Lead::findOrFail($id);
        $lead->delete();

        toastr()->success(translate('Lead_deleted_successfully'));

        return redirect()->route('admin.lead.index');
    }
}
