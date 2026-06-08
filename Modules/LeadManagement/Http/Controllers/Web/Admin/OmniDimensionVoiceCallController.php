<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\AdSource;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\CustomerLeadTag;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\LeadManagement\Entities\Source;
use Modules\LeadManagement\Entities\OmniDimensionApiLog;
use Modules\LeadManagement\Entities\OmniDimensionCallDispatch;
use Modules\LeadManagement\Entities\OmniDimensionCallTranscriptTransliteration;
use Modules\LeadManagement\Entities\OmniDimensionHiddenCallLog;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupAutomationRule;
use Modules\LeadManagement\Services\OmniDimensionService;
use Modules\LeadManagement\Services\OutboundCallContextService;
use Modules\LeadManagement\Services\VoiceBulkAudienceService;
use Modules\LeadManagement\Services\VoiceBulkCallContactBuilder;
use Modules\ZoneManagement\Entities\Zone;
use Modules\LeadManagement\Services\VoiceCallTabCache;
use Modules\LeadManagement\Services\VoiceCallTranscriptHinglishService;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\WhatsAppChatTag;
use Modules\WhatsAppModule\Services\WhatsAppMarketingAudienceService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OmniDimensionVoiceCallController extends Controller
{
    public function __construct(
        private readonly OmniDimensionService $omniDimension,
        private readonly OutboundCallContextService $outboundCallContext,
        private readonly VoiceBulkCallContactBuilder $bulkContactBuilder,
        private readonly VoiceBulkAudienceService $bulkAudience,
        private readonly VoiceCallTranscriptHinglishService $transcriptHinglish,
        private readonly VoiceCallTabCache $tabCache
    ) {}

    public function index(WhatsAppMarketingAudienceService $audienceService): View
    {
        $configured = $this->omniDimension->isConfigured();
        $loadError = null;
        $agents = [];
        $phoneNumbers = [];

        if ($configured) {
            $agentResult = $this->omniDimension->listAgents($loadError);
            $agents = $agentResult['agents'] ?? [];

            if (!$agentResult['ok']) {
                $loadError = $agentResult['error'] ?? $loadError;
            } else {
                $phoneResult = $this->omniDimension->listPhoneNumbers($loadError);
                $phoneNumbers = $phoneResult['phone_numbers'] ?? [];
                if (!$phoneResult['ok']) {
                    $loadError = $phoneResult['error'] ?? $loadError;
                }
            }
        }

        $employees = User::whereIn('user_type', ['super-admin', 'admin-employee'])
            ->ofStatus(1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        $categories = Category::query()->ofType('main')->ofStatus(1)->orderBy('name')->get();
        $subCategories = Category::query()->ofType('sub')->ofStatus(1)->orderBy('name')->get();
        $zones = Zone::query()->ofStatus(1)->orderBy('name')->get(['id', 'name']);
        $audienceCounts = [
            'all_customers' => $audienceService->countCustomersWithPhone(),
            'all_providers' => $audienceService->countProvidersWithPhone(),
        ];
        $categoryRecipientCounts = [];
        foreach ($categories as $cat) {
            $categoryRecipientCounts[(string) $cat->id] = $audienceService->countProvidersInCategory((string) $cat->id);
        }
        $leadSources = Source::query()->active()->orderBy('name')->get(['id', 'name']);
        $leadAdSources = AdSource::query()->active()->orderBy('name')->get(['id', 'name']);
        $customerLeadStatuses = CustomerLeadStatus::query()->orderBy('name')->get(['id', 'name']);
        $invalidReasons = LeadInvalidReason::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $futureCustomerReasons = LeadFutureCustomerReason::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $waChatTags = Schema::hasTable('whatsapp_chat_tags')
            ? WhatsAppChatTag::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'color'])
                ->map(fn ($t) => ['id' => (int) $t->id, 'name' => (string) $t->name, 'color' => (string) $t->color])
                ->values()->all()
            : [];

        $customerLeadTags = Schema::hasTable('customer_lead_tags')
            ? CustomerLeadTag::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'color'])
                ->map(fn ($t) => ['id' => (int) $t->id, 'name' => (string) $t->name, 'color' => (string) ($t->color ?? '')])
                ->values()->all()
            : [];

        return view('leadmanagement::admin.voice-calls.index', [
            'configured' => $configured,
            'loadError' => $loadError,
            'agents' => $agents,
            'phoneNumbers' => $phoneNumbers,
            'employees' => $employees,
            'currentEmployeeId' => Auth::id(),
            'callReasons' => OutboundCallContextService::callReasons(),
            'callReasonLabels' => OutboundCallContextService::callReasonLabels(),
            'contextKeys' => OutboundCallContextService::CONTEXT_KEYS,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'zones' => $zones,
            'audienceCounts' => $audienceCounts,
            'categoryRecipientCounts' => $categoryRecipientCounts,
            'leadSources' => $leadSources,
            'leadAdSources' => $leadAdSources,
            'customerLeadStatuses' => $customerLeadStatuses,
            'invalidReasons' => $invalidReasons,
            'futureCustomerReasons' => $futureCustomerReasons,
            'waChatTags' => $waChatTags,
            'customerLeadTags' => $customerLeadTags,
            'waFollowupDefaults' => ['silent_min_hours' => 2],
            'voiceCronTableReady' => Schema::hasTable('whatsapp_voice_followup_automation_rules'),
            'voiceCronRules' => Schema::hasTable('whatsapp_voice_followup_automation_rules')
                ? WhatsAppVoiceFollowupAutomationRule::query()->orderBy('id')->get()
                : collect(),
        ]);
    }

    public function history(Request $request): View|Response
    {
        return $this->callLogsView($request, 'history');
    }

    public function forwardedCalls(Request $request): View|Response
    {
        return $this->callLogsView($request, 'forwarded');
    }

    public function callbackCalls(Request $request): View|Response
    {
        return $this->callLogsView($request, 'callback');
    }

    private function callLogsView(Request $request, string $listMode = 'history'): View|Response
    {
        $tab = match ($listMode) {
            'forwarded' => VoiceCallTabCache::TAB_FORWARDED,
            'callback' => VoiceCallTabCache::TAB_CALLBACK,
            default => VoiceCallTabCache::TAB_HISTORY,
        };

        return $this->tabCache->respond($request, $tab, function () use ($request, $listMode): string {
            return $this->renderCallLogsView($request, $listMode)->render();
        });
    }

    private function renderCallLogsView(Request $request, string $listMode = 'history'): View
    {
        $configured = $this->omniDimension->isConfigured();
        $historyError = null;
        $agents = [];
        $callLogs = [];
        $callLogsTotal = 0;
        $historyPage = max(1, (int) $request->get('page', 1));
        $filterType = $listMode === 'history' ? null : $listMode;

        if ($configured) {
            $agentResult = $this->omniDimension->listAgents($historyError);
            $agents = $agentResult['agents'] ?? [];

            $historyResult = $this->omniDimension->listCallLogsFiltered([
                'page' => $historyPage,
                'page_size' => pagination_limit(),
                'agent_id' => $request->filled('agent_id') ? (int) $request->get('agent_id') : null,
                'call_status' => $request->filled('call_status') ? (string) $request->get('call_status') : null,
                'search' => $request->filled('search') ? (string) $request->get('search') : null,
                'filter_type' => $filterType,
            ], $historyError);

            $callLogs = $historyResult['calls'] ?? [];

            if (!$historyResult['ok']) {
                $historyError = $historyResult['error'] ?? $historyError;
            } else {
                $hiddenIds = OmniDimensionHiddenCallLog::hiddenIds();
                if ($hiddenIds !== []) {
                    $callLogs = array_values(array_filter(
                        $callLogs,
                        fn (array $call) => !in_array((int) ($call['id'] ?? 0), $hiddenIds, true)
                    ));
                }

                $callLogs = OmniDimensionCallDispatch::attachContextToCallLogs($callLogs);
                $callLogs = OmniDimensionCallTranscriptTransliteration::attachToCallLogs($callLogs);

                $search = trim((string) $request->get('search', ''));
                if ($filterType !== null || $search !== '') {
                    $callLogs = array_values(array_filter($callLogs, function (array $call) use ($filterType, $search): bool {
                        if ($filterType === 'forwarded' && !$this->omniDimension->isForwardedCall($call)) {
                            return false;
                        }
                        if ($filterType === 'callback' && !$this->omniDimension->isPendingCallbackCall($call)) {
                            return false;
                        }
                        if ($search !== '' && !$this->omniDimension->callMatchesSearch($call, $search)) {
                            return false;
                        }

                        return true;
                    }));

                    $callLogsTotal = count($callLogs);
                    $pageSize = pagination_limit();
                    $callLogs = array_slice($callLogs, ($historyPage - 1) * $pageSize, $pageSize);
                } else {
                    $callLogsTotal = $historyResult['total'] ?? 0;
                }
            }
        }

        $listConfig = match ($listMode) {
            'forwarded' => [
                'listRoute' => route('admin.voice-call.forwarded'),
                'filterFormId' => 'voice-forwarded-filter-form',
                'pageLinkClass' => 'voice-forwarded-page-link',
                'resetButtonClass' => 'voice-forwarded-reset',
            ],
            'callback' => [
                'listRoute' => route('admin.voice-call.callback'),
                'filterFormId' => 'voice-callback-filter-form',
                'pageLinkClass' => 'voice-callback-page-link',
                'resetButtonClass' => 'voice-callback-reset',
            ],
            default => [
                'listRoute' => route('admin.voice-call.history'),
                'filterFormId' => 'voice-history-filter-form',
                'pageLinkClass' => 'voice-history-page-link',
                'resetButtonClass' => 'voice-history-reset',
            ],
        };

        return view('leadmanagement::admin.voice-calls._history', [
            'configured' => $configured,
            'agents' => $agents,
            'callLogs' => $callLogs,
            'callLogsTotal' => $callLogsTotal,
            'historyError' => $historyError,
            'historyPage' => $historyPage,
            'filterAgentId' => $request->get('agent_id'),
            'filterCallStatus' => $request->get('call_status'),
            'filterSearch' => $request->get('search'),
            'listMode' => $listMode,
            'listRoute' => $listConfig['listRoute'],
            'filterFormId' => $listConfig['filterFormId'],
            'pageLinkClass' => $listConfig['pageLinkClass'],
            'resetButtonClass' => $listConfig['resetButtonClass'],
            'callReasonLabels' => OutboundCallContextService::callReasonLabels(),
        ]);
    }

    public function destroy(int $callLogId): JsonResponse
    {
        if ($callLogId <= 0) {
            return response()->json(['message' => translate('Something went wrong')], 422);
        }

        OmniDimensionHiddenCallLog::query()->firstOrCreate(
            ['omnidim_call_log_id' => $callLogId],
            ['hidden_by' => Auth::id()]
        );

        $this->omniDimension->clearCallLogsCache();
        $this->tabCache->forgetCallLogTabs();

        return response()->json(['message' => translate('Voice_call_history_removed')]);
    }

    public function translateTranscriptHinglish(Request $request): JsonResponse
    {
        @set_time_limit(300);

        $validated = $request->validate([
            'transcript' => 'required|string|max:50000',
            'call_log_id' => 'nullable|integer|min:1',
        ]);

        $transcript = trim((string) $validated['transcript']);
        $callLogId = isset($validated['call_log_id']) ? (int) $validated['call_log_id'] : null;

        if ($transcript === '') {
            return response()->json(['ok' => false, 'message' => translate('No_transcript_available')], 422);
        }

        if (!$this->transcriptHinglish->containsDevanagari($transcript)) {
            return response()->json([
                'ok' => true,
                'transcript' => $transcript,
                'hinglish' => false,
                'stored' => false,
            ]);
        }

        $stored = ($callLogId !== null && $callLogId > 0)
            ? OmniDimensionCallTranscriptTransliteration::findForCall($callLogId, $transcript)
            : null;

        if ($stored !== null) {
            return response()->json([
                'ok' => true,
                'transcript' => (string) $stored->transliterated_transcript,
                'hinglish' => true,
                'stored' => true,
            ]);
        }

        $translated = $this->transcriptHinglish->translateToHinglish($transcript, $callLogId);
        if ($translated === null) {
            return response()->json([
                'ok' => false,
                'message' => translate('Transcript_hinglish_translation_failed'),
            ], 503);
        }

        return response()->json([
            'ok' => true,
            'transcript' => $translated,
            'hinglish' => true,
            'stored' => false,
        ]);
    }

    public function recording(int $callLogId): Response
    {
        $error = null;
        $url = $this->omniDimension->getRecordingUrl($callLogId, $error);

        if ($url === null) {
            abort(404);
        }

        $stream = Http::timeout(60)->get($url);
        if ($stream->failed()) {
            abort(404);
        }

        return response($stream->body(), 200, [
            'Content-Type' => $stream->header('Content-Type') ?? 'audio/mpeg',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if (!$this->omniDimension->isConfigured()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => translate('OmniDimension_is_not_configured'),
                ], 422);
            }
            toastr()->error(translate('OmniDimension_is_not_configured'));

            return back()->withInput();
        }

        $validated = $request->validate(array_merge([
            'phone_number' => 'required|string|max:32',
            'agent_id' => 'required|integer|min:1',
            'from_number_id' => 'nullable|integer|min:1',
            'handled_by' => 'required|string|max:64',
            'remarks' => 'nullable|string|max:1000',
            'log_outbound_enquiry' => 'nullable|boolean',
        ], $this->outboundCallContext->validationRules()));

        $toE164 = $this->omniDimension->normalizeToE164($validated['phone_number']);
        if ($toE164 === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => translate('Invalid_phone_number'),
                    'errors' => ['phone_number' => [translate('Invalid_phone_number')]],
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['phone_number' => translate('Invalid_phone_number')]);
        }

        $callContext = $this->outboundCallContext->build($validated);

        $apiError = null;
        $result = $this->omniDimension->dispatchCall(
            (int) $validated['agent_id'],
            $toE164,
            isset($validated['from_number_id']) ? (int) $validated['from_number_id'] : null,
            $callContext,
            $apiError
        );

        if (!$result['ok']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => translate('Voice_call_dispatch_failed'),
                    'error' => $apiError,
                    'api_response' => $result['body'] ?? null,
                ], 422);
            }
            toastr()->error(translate('Voice_call_dispatch_failed'));

            return back()->withInput();
        }

        $requestId = $result['request_id'];
        $dispatchStatus = $result['status'] ?? 'dispatched';

        OmniDimensionCallDispatch::create([
            'omnidim_request_id' => $requestId,
            'dispatch_status' => $dispatchStatus,
            'to_number_e164' => $toE164,
            'call_context' => $callContext,
            'dispatched_by' => Auth::id(),
        ]);

        $this->tabCache->forgetCallLogTabs();

        $shouldLog = $request->boolean('log_outbound_enquiry', true);
        if ($shouldLog) {
            $agentLabel = (string) $request->input('agent_label', 'Agent #' . $validated['agent_id']);
            $fromLabel = (string) $request->input('from_number_label', '');

            $remarks = trim((string) ($validated['remarks'] ?? ''));
            $omniNote = 'OmniDimension call #' . ($requestId ?? '—') . ' (' . $dispatchStatus . ')'
                . ' · Agent: ' . $agentLabel
                . ($fromLabel !== '' ? ' · From: ' . $fromLabel : '');
            $remarks = $remarks !== '' ? ($remarks . ' | ' . $omniNote) : $omniNote;

            LeadOutboundEnquiry::create([
                'customer_name' => $validated['customer_name'],
                'phone_number' => $validated['phone_number'],
                'contacted_through' => 'call',
                'remarks' => $remarks,
                'status' => 'pending',
                'status_id' => null,
                'contacted_at' => now(),
                'created_by' => Auth::id(),
                'handled_by' => $validated['handled_by'],
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => translate('Voice_call_dispatched_successfully'),
                'request_id' => $requestId,
                'status' => $dispatchStatus,
                'to_number' => $toE164,
            ]);
        }

        toastr()->success(translate('Voice_call_dispatched_successfully'));

        return redirect()->route('admin.voice-call.index');
    }

    public function placedCalls(Request $request): View|Response
    {
        return $this->tabCache->respond($request, VoiceCallTabCache::TAB_PLACED, function () use ($request): string {
            return $this->renderPlacedCalls($request)->render();
        });
    }

    private function renderPlacedCalls(Request $request): View
    {
        $page = max(1, (int) $request->get('page', 1));
        $search = trim((string) $request->get('search', ''));

        $query = OmniDimensionCallDispatch::query()
            ->with(['dispatchedBy:id,first_name,last_name,email'])
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('to_number_e164', 'like', $like)
                    ->orWhere('omnidim_request_id', 'like', $like)
                    ->orWhere('call_context->customer_name', 'like', $like)
                    ->orWhere('call_context->lead_summary', 'like', $like)
                    ->orWhere('call_context->call_reason', 'like', $like);
            });
        }

        $dispatches = $query->paginate(pagination_limit(), ['*'], 'page', $page);

        $statusError = null;
        $callLogsByRequestId = [];
        if ($this->omniDimension->isConfigured() && $dispatches->isNotEmpty()) {
            $requestIds = $dispatches->pluck('omnidim_request_id')
                ->filter(fn ($id) => (int) $id > 0)
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
            $callLogsByRequestId = $this->omniDimension->findCallLogsByRequestIds($requestIds, $statusError);

            foreach ($dispatches as $dispatch) {
                $requestId = (int) ($dispatch->omnidim_request_id ?? 0);
                if ($requestId > 0 && isset($callLogsByRequestId[$requestId])) {
                    $callLogsByRequestId[$requestId]['dispatch_context'] = $dispatch->normalizedContext();
                }
            }

            if ($callLogsByRequestId !== []) {
                $enrichedCalls = OmniDimensionCallTranscriptTransliteration::attachToCallLogs(array_values($callLogsByRequestId));
                $callLogsByRequestId = [];
                foreach ($enrichedCalls as $call) {
                    $requestId = (int) ($call['call_request_id'] ?? 0);
                    if ($requestId > 0) {
                        $callLogsByRequestId[$requestId] = $call;
                    }
                }
            }
        }

        return view('leadmanagement::admin.voice-calls._place_call_list', [
            'dispatches' => $dispatches,
            'filterSearch' => $search,
            'listRoute' => route('admin.voice-call.placed'),
            'callReasonLabels' => OutboundCallContextService::callReasonLabels(),
            'contextKeys' => OutboundCallContextService::CONTEXT_KEYS,
            'callLogsByRequestId' => $callLogsByRequestId,
            'statusLoadError' => $statusError,
        ]);
    }

    public function refreshCatalog(): JsonResponse
    {
        if (!$this->omniDimension->isConfigured()) {
            return response()->json([
                'ok' => false,
                'message' => translate('OmniDimension_is_not_configured'),
            ], 422);
        }

        $error = null;
        $result = $this->omniDimension->refreshAgentsAndPhoneNumbers($error);

        if (!$result['ok']) {
            return response()->json([
                'ok' => false,
                'message' => translate('OmniDimension_catalog_refresh_failed'),
                'error' => $result['error'] ?? $error,
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => translate('OmniDimension_catalog_refreshed'),
            'agents' => $result['agents'],
            'phone_numbers' => $result['phone_numbers'],
            'agents_count' => count($result['agents']),
            'phone_numbers_count' => count($result['phone_numbers']),
        ]);
    }

    public function apiLogs(Request $request): View|Response
    {
        return $this->tabCache->respond($request, VoiceCallTabCache::TAB_API_LOGS, function () use ($request): string {
            return $this->renderApiLogs($request)->render();
        });
    }

    private function renderApiLogs(Request $request): View
    {
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = pagination_limit();

        $query = OmniDimensionApiLog::query()->orderByDesc('id');

        if ($request->filled('method')) {
            $query->where('method', strtoupper((string) $request->get('method')));
        }
        if ($request->filled('status')) {
            if ((string) $request->get('status') === 'success') {
                $query->where('ok', true);
            } elseif ((string) $request->get('status') === 'failed') {
                $query->where('ok', false);
            }
        }
        if ($request->filled('search')) {
            $search = '%' . trim((string) $request->get('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('path', 'like', $search)
                    ->orWhere('error', 'like', $search)
                    ->orWhere('request_body', 'like', $search)
                    ->orWhere('response_body', 'like', $search);
            });
        }

        $logs = $query->paginate($pageSize, ['*'], 'page', $page);

        return view('leadmanagement::admin.voice-calls._api_logs', [
            'logs' => $logs,
            'filterMethod' => $request->get('method'),
            'filterStatus' => $request->get('status'),
            'filterSearch' => $request->get('search'),
            'listRoute' => route('admin.voice-call.api-logs'),
        ]);
    }

    public function bulkCampaigns(Request $request): View|Response
    {
        return $this->tabCache->respond($request, VoiceCallTabCache::TAB_BULK, function () use ($request): string {
            return $this->renderBulkCampaigns($request)->render();
        });
    }

    private function renderBulkCampaigns(Request $request): View
    {
        $configured = $this->omniDimension->isConfigured();
        $bulkError = null;
        $campaigns = [];
        $campaignsTotal = 0;
        $bulkPage = max(1, (int) $request->get('page', 1));

        if ($configured) {
            $result = $this->omniDimension->listBulkCalls([
                'page' => $bulkPage,
                'page_size' => pagination_limit(),
                'status' => $request->filled('status') ? (string) $request->get('status') : null,
            ], $bulkError);

            $campaigns = $result['campaigns'] ?? [];
            $campaignsTotal = $result['total'] ?? 0;

            if (!$result['ok']) {
                $bulkError = $result['error'] ?? $bulkError;
            }
        }

        return view('leadmanagement::admin.voice-calls._bulk_campaigns', [
            'configured' => $configured,
            'campaigns' => $campaigns,
            'campaignsTotal' => $campaignsTotal,
            'bulkError' => $bulkError,
            'bulkPage' => $bulkPage,
            'filterStatus' => $request->get('status'),
        ]);
    }

    public function bulkCampaignDetails(Request $request, int $id): View|Response
    {
        return $this->tabCache->respond(
            $request,
            VoiceCallTabCache::TAB_BULK_DETAILS,
            function () use ($request, $id): string {
                return $this->renderBulkCampaignDetails($request, $id)->render();
            },
            ['id' => $id]
        );
    }

    private function renderBulkCampaignDetails(Request $request, int $id): View
    {
        $configured = $this->omniDimension->isConfigured();
        $detailsError = null;
        $callsError = null;
        $campaign = null;
        $calls = [];
        $callsTotal = 0;
        $callsPage = max(1, (int) $request->get('page', 1));

        $contactByPhone = [];
        if ($configured) {
            $detailResult = $this->omniDimension->getBulkCall($id, $detailsError);
            if ($detailResult['ok']) {
                $campaign = $detailResult['campaign'];
                $contactByPhone = $detailResult['contact_by_phone'] ?? [];
            } elseif ($detailsError === null) {
                $detailsError = $detailResult['error'] ?? 'omnidimension_bulk_call_detail_failed';
            }
            $callsResult = $this->omniDimension->listCallLogs([
                'bulk_call_id' => $id,
                'page' => $callsPage,
                'page_size' => pagination_limit(),
            ], $callsError);

            if ($callsResult['ok']) {
                $calls = $callsResult['calls'] ?? [];
                $callsTotal = (int) ($callsResult['total'] ?? 0);

                foreach ($calls as $index => $call) {
                    $phone = trim((string) ($call['to_number'] ?? ''));
                    $normalized = $this->omniDimension->normalizeToE164($phone) ?? $phone;
                    $context = $contactByPhone[$normalized] ?? $contactByPhone[$phone] ?? [];
                    if ($context !== []) {
                        $calls[$index]['dispatch_context'] = $context;
                    }
                }

                $calls = OmniDimensionCallTranscriptTransliteration::attachToCallLogs($calls);
            } elseif ($callsError === null) {
                $callsError = $callsResult['error'] ?? 'omnidimension_call_logs_failed';
            }
        }

        return view('leadmanagement::admin.voice-calls._bulk_campaign_details', [
            'configured' => $configured,
            'campaign' => $campaign,
            'campaignId' => $id,
            'calls' => $calls,
            'callsTotal' => $callsTotal,
            'callsPage' => $callsPage,
            'detailsError' => $detailsError,
            'callsError' => $callsError,
            'callReasonLabels' => OutboundCallContextService::callReasonLabels(),
            'detailsRoute' => route('admin.voice-call.bulk.campaigns.show', ['id' => $id]),
            'canCancelCampaign' => is_array($campaign)
                && $this->omniDimension->bulkCampaignIsCancellable($campaign['status'] ?? null),
        ]);
    }

    public function cancelBulkCampaign(Request $request, int $id): RedirectResponse|JsonResponse
    {
        if (!$this->omniDimension->isConfigured()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => translate('OmniDimension_is_not_configured'),
                ], 422);
            }
            toastr()->error(translate('OmniDimension_is_not_configured'));

            return back();
        }

        $detailsError = null;
        $detailResult = $this->omniDimension->getBulkCall($id, $detailsError);
        $campaign = $detailResult['campaign'] ?? null;

        if (!$detailResult['ok'] || !is_array($campaign)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => translate('Voice_bulk_campaign_details_load_failed'),
                ], 404);
            }
            toastr()->error(translate('Voice_bulk_campaign_details_load_failed'));

            return back();
        }

        if (!$this->omniDimension->bulkCampaignIsCancellable($campaign['status'] ?? null)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => translate('Voice_bulk_campaign_cancel_not_allowed'),
                ], 422);
            }
            toastr()->error(translate('Voice_bulk_campaign_cancel_not_allowed'));

            return back();
        }

        $apiError = null;
        $result = $this->omniDimension->cancelBulkCall($id, $apiError);

        if (!$result['ok']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => translate('Voice_bulk_campaign_cancel_failed'),
                    'error' => $apiError,
                    'api_response' => $result['body'] ?? null,
                ], 422);
            }
            toastr()->error(translate('Voice_bulk_campaign_cancel_failed'));

            return back();
        }

        $this->tabCache->forgetMany([
            VoiceCallTabCache::TAB_BULK,
            VoiceCallTabCache::TAB_BULK_DETAILS,
        ]);

        $message = translate('Voice_bulk_campaign_cancelled_successfully');
        $status = $result['status'] ?? 'cancelled';
        if ($status !== '') {
            $message .= ' (' . $status . ')';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'campaign_id' => $id,
                'status' => $status,
            ]);
        }

        toastr()->success($message);

        return redirect()->route('admin.voice-call.index', ['tab' => 'bulk']);
    }

    public function bulkAudiencePreview(Request $request): JsonResponse
    {
        $filters = $this->bulkAudience->parseFilters($request);
        if (($filters['recipient_kind'] ?? '') === VoiceBulkAudienceService::KIND_CSV) {
            return response()->json([
                'total_matching' => 0,
                'rows' => [],
                'preview_limit' => 50,
                'has_more' => false,
                'kind' => VoiceBulkAudienceService::KIND_CSV,
            ]);
        }

        return response()->json($this->bulkAudience->preview($filters));
    }

    public function bulkAudiencePreviewCsv(Request $request): JsonResponse
    {
        $request->validate([
            'contacts_csv' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $path = $request->file('contacts_csv')->store('voice_bulk/csv_preview', 'local');

        try {
            $preview = $this->bulkAudience->previewCsv($path);
        } finally {
            Storage::disk('local')->delete($path);
        }

        return response()->json($preview);
    }

    public function bulkSampleCsv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['name', 'phone', 'call_reason', 'lead_summary']);
            fputcsv($out, ['Example Customer', '919876543210', 'WHATSAPP_FOLLOWUP', 'Interested in plumbing service']);
            fputcsv($out, ['Another Contact', '+919123456789', '', '']);
            fclose($out);
        }, 'voice-bulk-contacts-sample.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storeBulk(Request $request): RedirectResponse|JsonResponse
    {
        if (!$this->omniDimension->isConfigured()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => translate('OmniDimension_is_not_configured'),
                ], 422);
            }
            toastr()->error(translate('OmniDimension_is_not_configured'));

            return back()->withInput();
        }

        $reasons = implode(',', OutboundCallContextService::callReasons());
        $audienceRules = $this->bulkAudience->filterRules();
        $audienceRules['contacts_csv'] = 'required_if:recipient_kind,' . VoiceBulkAudienceService::KIND_CSV . '|nullable|file|mimes:csv,txt|max:5120';

        $validated = $request->validate(array_merge([
            'campaign_name' => 'required|string|max:255',
            'phone_number_id' => 'required|integer|min:1',
            'send_option' => 'required|in:now,schedule',
            'scheduled_at' => 'nullable|required_if:send_option,schedule|date|after:now',
            'timezone' => 'nullable|string|max:64',
            'concurrent_call_limit' => 'nullable|integer|min:1|max:20',
            'enabled_reschedule_call' => 'nullable|boolean',
            'auto_retry' => 'nullable|boolean',
            'auto_retry_schedule' => 'nullable|string|in:immediately,next_day,scheduled_time',
            'retry_limit' => 'nullable|integer|min:1|max:5',
            'call_reason' => 'nullable|string|in:' . $reasons,
            'lead_status' => 'nullable|string|max:64',
            'lead_summary' => 'nullable|string|max:2000',
            'service_category' => 'nullable|string|max:255',
            'service_details' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ], $audienceRules));

        $filters = $this->bulkAudience->normalizeFilters($validated);
        $csvPath = null;
        if (($filters['recipient_kind'] ?? '') === VoiceBulkAudienceService::KIND_CSV && $request->hasFile('contacts_csv')) {
            $dir = 'voice_bulk/csv';
            Storage::disk('local')->makeDirectory($dir);
            $csvPath = $request->file('contacts_csv')->storeAs(
                $dir,
                Str::uuid()->toString() . '.csv',
                'local'
            );
        }

        $recipients = $this->bulkAudience->resolve($filters, $csvPath);

        if ($recipients === []) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => translate('no_data_found'),
                ], 422);
            }
            toastr()->error(translate('no_data_found'));

            return back()->withInput();
        }

        $sharedContext = $this->outboundCallContext->build($validated);
        $contactList = $this->bulkContactBuilder->buildContactList($recipients, $sharedContext);

        if ($contactList === []) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => translate('Voice_bulk_no_valid_contacts'),
                ], 422);
            }
            toastr()->error(translate('Voice_bulk_no_valid_contacts'));

            return back()->withInput();
        }

        $payload = $this->bulkContactBuilder->buildApiPayload(
            $validated['campaign_name'],
            (int) $validated['phone_number_id'],
            $contactList,
            $validated
        );

        $apiError = null;
        $result = $this->omniDimension->createBulkCall($payload, $apiError);

        if (!$result['ok']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => translate('Voice_bulk_campaign_failed'),
                    'error' => $apiError,
                    'api_response' => $result['body'] ?? null,
                ], 422);
            }
            toastr()->error(translate('Voice_bulk_campaign_failed'));

            return back()->withInput();
        }

        $campaignId = $result['campaign_id'];
        $status = $result['status'] ?? 'pending';

        $this->tabCache->forgetMany([
            VoiceCallTabCache::TAB_BULK,
            VoiceCallTabCache::TAB_BULK_DETAILS,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => translate('Voice_bulk_campaign_created_successfully'),
                'campaign_id' => $campaignId,
                'status' => $status,
                'contact_count' => count($contactList),
                'send_option' => $validated['send_option'] ?? 'now',
                'scheduled_at' => $validated['scheduled_at'] ?? null,
            ]);
        }

        $message = translate('Voice_bulk_campaign_created_successfully');
        if ($campaignId !== null) {
            $message .= ' #' . $campaignId . ' (' . $status . ')';
        }
        toastr()->success($message);

        return redirect()->route('admin.voice-call.index', ['tab' => 'bulk']);
    }
}
