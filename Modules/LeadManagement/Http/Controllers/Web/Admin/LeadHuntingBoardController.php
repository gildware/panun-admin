<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadHuntingInterest;
use Modules\LeadManagement\Services\LeadHuntingBoardService;
use Modules\LeadManagement\Services\LeadHuntingNotificationService;
use Modules\ZoneManagement\Entities\Zone;

class LeadHuntingBoardController extends Controller
{
    public function __construct(
        private readonly LeadHuntingBoardService $huntingBoard,
    ) {}

    public function index(Request $request): View
    {
        if (! $this->huntingBoard->schemaReady()) {
            abort(503, 'Provider hunting is not available until migrations have run.');
        }
        $search = trim((string) $request->input('search', ''));
        $categoryId = (string) $request->input('category_id', '');
        $subCategoryId = (string) $request->input('sub_category_id', '');
        $zoneId = (string) $request->input('zone_id', '');
        $bidFilter = (string) $request->input('bids', 'all');
        if (! in_array($bidFilter, ['all', 'has', 'none'], true)) {
            $bidFilter = 'all';
        }

        $query = $this->huntingBoard->publishedQuery()
            ->with([
                'huntingStartedByUser:id,first_name,last_name,email',
                'huntingInterests' => function ($q) {
                    $q->where('status', LeadHuntingInterest::STATUS_INTERESTED)
                        ->with('provider')
                        ->orderByDesc('updated_at')
                        ->orderByDesc('id');
                },
            ])
            ->withCount([
                'huntingInterests as hunting_interests_count' => function ($q) {
                    $q->where('status', LeadHuntingInterest::STATUS_INTERESTED);
                },
                'huntingInterests as hunting_rejections_count' => function ($q) {
                    $q->where('status', LeadHuntingInterest::STATUS_REJECTED);
                },
            ]);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like, $search) {
                $q->where('name', 'like', $like)
                    ->orWhere('phone_number', 'like', $like);
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        if ($bidFilter === 'has') {
            $query->whereHas('huntingInterests', fn ($q) => $q->where('status', LeadHuntingInterest::STATUS_INTERESTED));
        } elseif ($bidFilter === 'none') {
            $query->whereDoesntHave('huntingInterests', fn ($q) => $q->where('status', LeadHuntingInterest::STATUS_INTERESTED));
        }

        $subIdsUnderCategory = [];
        if ($categoryId !== '' && $subCategoryId === '') {
            $subIdsUnderCategory = Category::withoutGlobalScopes()
                ->ofType('sub')
                ->where('parent_id', $categoryId)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();
        }

        if ($categoryId !== '' || $subCategoryId !== '' || $zoneId !== '') {
            $candidateIds = (clone $query)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $dataMap = $this->huntingBoard->latestCustomerDataByLeadIds($candidateIds);
            $keep = [];
            foreach ($candidateIds as $lid) {
                $data = $dataMap[$lid] ?? [];
                if ($subCategoryId !== '' && (string) ($data['service_subcategory'] ?? '') !== $subCategoryId) {
                    continue;
                }
                if ($subCategoryId === '' && $categoryId !== '') {
                    $leadCategory = (string) ($data['service_category'] ?? '');
                    $leadSub = (string) ($data['service_subcategory'] ?? '');
                    $matchesCategory = $leadCategory === $categoryId
                        || ($leadSub !== '' && in_array($leadSub, $subIdsUnderCategory, true));
                    if (! $matchesCategory) {
                        continue;
                    }
                }
                if ($zoneId !== '' && (string) ($data['zone_id'] ?? '') !== $zoneId) {
                    continue;
                }
                $keep[] = $lid;
            }
            if ($keep === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $keep);
            }
        }

        $viewMode = (string) $request->input('view', 'list');
        if (! in_array($viewMode, ['list', 'calendar'], true)) {
            $viewMode = 'list';
        }

        $calendarEvents = [];
        $leads = null;
        $rows = [];

        if ($viewMode === 'calendar') {
            $calendarLeads = (clone $query)->orderBy('id')->limit(1000)->get();
            $calendarData = $this->huntingBoard->latestCustomerDataByLeadIds(
                $calendarLeads->pluck('id')->map(fn ($id) => (int) $id)->all()
            );
            foreach ($calendarLeads as $lead) {
                $data = $calendarData[(int) $lead->id] ?? [];
                $public = $this->huntingBoard->publicJobFields($data);
                if (! $public['estimated_at']) {
                    continue;
                }
                $interestCount = (int) ($lead->hunting_interests_count ?? 0);
                $rejectCount = (int) ($lead->hunting_rejections_count ?? 0);
                $job = $public['job_text'] !== '—' ? $public['job_text'] : ($public['subcategory_name'] ?? '');
                $jobShort = \Illuminate\Support\Str::limit($job, 28);
                $when = $public['estimated_at']->format('d M Y, h:i A');
                $calendarEvents[] = [
                    'id' => (string) $lead->id,
                    'title' => $jobShort !== '' ? $jobShort : ('#'.$lead->id),
                    'start' => $public['estimated_at']->toIso8601String(),
                    'backgroundColor' => $interestCount > 0 ? '#d97706' : '#25274D',
                    'borderColor' => $interestCount > 0 ? '#b45309' : '#1c1e3d',
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'leadId' => (int) $lead->id,
                        'url' => route('admin.lead.show', $lead->id),
                        'customer' => (string) $lead->name,
                        'phone' => (string) $lead->phone_number,
                        'job' => $job,
                        'jobShort' => $jobShort,
                        'category' => $public['category_name'],
                        'subcategory' => $public['subcategory_name'],
                        'area' => $public['area_name'],
                        'zone' => $public['zone_name'],
                        'when' => $when,
                        'value' => $public['estimated_value'] !== null ? with_currency_symbol($public['estimated_value']) : '',
                        'bids' => $interestCount,
                        'rejects' => $rejectCount,
                    ],
                ];
            }
        } else {
            $leads = $query->orderByDesc('hunting_started_at')->orderByDesc('id')
                ->paginate(pagination_limit())
                ->appends($request->query());

            $leadIds = $leads->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $customerDataByLead = $this->huntingBoard->latestCustomerDataByLeadIds($leadIds);
            $allResponses = $leadIds === []
                ? collect()
                : LeadHuntingInterest::query()
                    ->whereIn('lead_id', $leadIds)
                    ->whereIn('status', [
                        LeadHuntingInterest::STATUS_INTERESTED,
                        LeadHuntingInterest::STATUS_REJECTED,
                    ])
                    ->with(['provider' => fn ($q) => $q->withTrashed()])
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy(fn (LeadHuntingInterest $interest) => (int) $interest->lead_id);

            foreach ($leads as $lead) {
                $data = $customerDataByLead[(int) $lead->id] ?? [];
                $public = $this->huntingBoard->publicJobFields($data);
                $starter = $lead->huntingStartedByUser;
                $starterName = '—';
                if ($starter) {
                    $full = trim(($starter->first_name ?? '').' '.($starter->last_name ?? ''));
                    $starterName = $full !== '' ? $full : (string) $starter->email;
                }
                $leadResponses = $allResponses->get((int) $lead->id, collect());
                $leadInterests = $leadResponses->where('status', LeadHuntingInterest::STATUS_INTERESTED)->values();
                $leadRejections = $leadResponses->where('status', LeadHuntingInterest::STATUS_REJECTED)->values();
                $rows[] = [
                    'lead' => $lead,
                    'public' => $public,
                    'started_by' => $starterName,
                    'interest_count' => max((int) ($lead->hunting_interests_count ?? 0), $leadInterests->count()),
                    'reject_count' => max((int) ($lead->hunting_rejections_count ?? 0), $leadRejections->count()),
                    'interests' => $this->serializeInterestRows($leadInterests),
                    'rejections' => $this->serializeInterestRows($leadRejections),
                    'pending_count' => $this->huntingBoard->pendingActionProviderCountForLead($lead),
                    'remind_url' => route('admin.lead.hunting-board.remind', $lead->id),
                ];
            }
        }

        $categories = Category::withoutGlobalScopes()
            ->ofType('main')
            ->orderBy('name')
            ->get(['id', 'name']);
        $subCategories = Category::withoutGlobalScopes()
            ->ofType('sub')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
        $zones = Zone::ofStatus(1)->orderBy('name')->get(['id', 'name']);

        $publishedCount = $this->huntingBoard->publishedCount();
        $withInterest = 0;
        $noInterest = 0;
        if (Schema::hasTable('lead_hunting_interests')) {
            $withInterest = $this->huntingBoard->publishedQuery()
                ->whereHas('huntingInterests', fn ($q) => $q->where('status', LeadHuntingInterest::STATUS_INTERESTED))
                ->count();
            $noInterest = max(0, $publishedCount - $withInterest);
        } else {
            $noInterest = $publishedCount;
        }

        return view('leadmanagement::admin.hunting-board.index', compact(
            'leads',
            'rows',
            'search',
            'categoryId',
            'subCategoryId',
            'zoneId',
            'bidFilter',
            'viewMode',
            'calendarEvents',
            'categories',
            'subCategories',
            'zones',
            'publishedCount',
            'withInterest',
            'noInterest',
        ));
    }

    public function start(Request $request, int $id): RedirectResponse
    {
        $lead = Lead::findOrFail($id);

        try {
            $this->huntingBoard->startHunting($lead);
        } catch (\RuntimeException $e) {
            toastr()->error($e->getMessage());

            return $this->backToLead($request, $lead->id);
        }

        toastr()->success(translate('Provider_hunting_started'));

        return $this->backToLead($request, $lead->id);
    }

    public function unpublish(Request $request, int $id): RedirectResponse
    {
        $lead = Lead::findOrFail($id);

        $validated = $request->validate([
            'hunting_unpublish_reason' => 'required|in:'.implode(',', LeadHuntingBoardService::unpublishReasons()),
            'hunting_unpublish_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->huntingBoard->unpublish(
                $lead,
                $validated['hunting_unpublish_reason'],
                $validated['hunting_unpublish_notes'] ?? null
            );
        } catch (\RuntimeException $e) {
            toastr()->error($e->getMessage());

            return $this->backToLead($request, $lead->id);
        }

        toastr()->success(translate('Lead_unpublished_from_hunting_board'));

        return $this->backToLead($request, $lead->id);
    }

    public function remind(Request $request, int $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        $validated = $request->validate([
            'message' => 'required|string|min:3|max:500',
        ]);

        if ($lead->hunting_status !== Lead::HUNTING_PUBLISHED) {
            return response()->json([
                'success' => false,
                'sent' => 0,
                'message' => translate('Lead_is_not_on_the_hunting_board'),
            ], 422);
        }

        $lockKey = 'open_request_reminder:'.$lead->id;
        $lockedUntil = (int) Cache::get($lockKey, 0);
        if ($lockedUntil > time()) {
            $wait = max(1, $lockedUntil - time());

            return response()->json([
                'success' => false,
                'sent' => 0,
                'retry_after' => $wait,
                'message' => str_replace(':seconds', (string) $wait, translate('Reminder_wait_before_resend')),
            ], 429);
        }

        $sent = app(LeadHuntingNotificationService::class)
            ->notifyProvidersJobReminder($lead, $validated['message']);

        if ($sent === 0) {
            return response()->json([
                'success' => false,
                'sent' => 0,
                'message' => translate('No_providers_pending_action'),
            ], 422);
        }

        Cache::put($lockKey, time() + 60, 60);

        return response()->json([
            'success' => true,
            'sent' => $sent,
            'retry_after' => 60,
            'message' => str_replace(':count', (string) $sent, translate('Reminder_sent_to_providers')),
        ]);
    }

    /**
     * @param  iterable<int, LeadHuntingInterest>  $interests
     * @return list<array{name: string, phone: string, image: string, note: ?string, url: ?string}>
     */
    private function serializeInterestRows(iterable $interests): array
    {
        $placeholder = asset('assets/provider-module/img/user2x.png');
        $out = [];
        foreach ($interests as $interest) {
            $provider = $interest->provider;
            $name = trim((string) ($provider?->company_name ?? ''));
            if ($name === '') {
                $name = trim((string) ($provider?->contact_person_name ?? ''));
            }
            $phone = trim((string) ($provider?->contact_person_phone ?? ''));
            if ($phone === '') {
                $phone = trim((string) ($provider?->company_phone ?? ''));
            }
            $note = trim((string) ($interest->note ?? ''));
            $out[] = [
                'name' => $name !== '' ? $name : translate('Provider'),
                'phone' => $phone,
                'image' => $provider?->list_avatar_full_path ?: $placeholder,
                'note' => $note !== '' ? $note : null,
                'url' => $provider ? route('admin.provider.details', [$provider->id, 'web_page' => 'overview']) : null,
            ];
        }

        return $out;
    }

    private function backToLead(Request $request, int $leadId): RedirectResponse
    {
        $url = route('admin.lead.show', $leadId);
        if ($request->boolean('in_modal')) {
            $url .= '?in_modal=1';
        }

        return redirect($url);
    }
}
