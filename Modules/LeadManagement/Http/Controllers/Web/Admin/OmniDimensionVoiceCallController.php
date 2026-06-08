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
use Modules\LeadManagement\Entities\CustomerLeadTag;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\LeadManagement\Entities\OmniDimensionCallDispatch;
use Modules\LeadManagement\Entities\OmniDimensionCallTranscriptTransliteration;
use Modules\LeadManagement\Entities\OmniDimensionHiddenCallLog;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupAutomationRule;
use Modules\LeadManagement\Services\OmniDimensionService;
use Modules\LeadManagement\Services\OutboundCallContextService;
use Modules\LeadManagement\Services\VoiceBulkCallContactBuilder;
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
        private readonly VoiceCallTranscriptHinglishService $transcriptHinglish
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
        $audienceCounts = [
            'all_customers' => $audienceService->countCustomersWithPhone(),
            'all_providers' => $audienceService->countProvidersWithPhone(),
        ];
        $categoryRecipientCounts = [];
        foreach ($categories as $cat) {
            $categoryRecipientCounts[(string) $cat->id] = $audienceService->countProvidersInCategory((string) $cat->id);
        }

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
            'audienceCounts' => $audienceCounts,
            'categoryRecipientCounts' => $categoryRecipientCounts,
            'waChatTags' => $waChatTags,
            'customerLeadTags' => $customerLeadTags,
            'waFollowupDefaults' => ['silent_min_hours' => 2],
            'voiceCronTableReady' => Schema::hasTable('whatsapp_voice_followup_automation_rules'),
            'voiceCronRules' => Schema::hasTable('whatsapp_voice_followup_automation_rules')
                ? WhatsAppVoiceFollowupAutomationRule::query()->orderBy('id')->get()
                : collect(),
        ]);
    }

    public function history(Request $request): View
    {
        return $this->callLogsView($request, 'history');
    }

    public function forwardedCalls(Request $request): View
    {
        return $this->callLogsView($request, 'forwarded');
    }

    public function callbackCalls(Request $request): View
    {
        return $this->callLogsView($request, 'callback');
    }

    private function callLogsView(Request $request, string $listMode = 'history'): View
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

    public function store(Request $request): RedirectResponse
    {
        if (!$this->omniDimension->isConfigured()) {
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
            toastr()->error(translate('Voice_call_dispatch_failed'));

            return back()->withInput();
        }

        $requestId = $result['request_id'];
        $dispatchStatus = $result['status'] ?? 'dispatched';

        OmniDimensionCallDispatch::create([
            'omnidim_request_id' => $requestId,
            'to_number_e164' => $toE164,
            'call_context' => $callContext,
            'dispatched_by' => Auth::id(),
        ]);

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

        toastr()->success(translate('Voice_call_dispatched_successfully'));

        return redirect()->route('admin.voice-call.index');
    }

    public function bulkCampaigns(Request $request): View
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

    public function storeBulk(Request $request, WhatsAppMarketingAudienceService $audienceService): RedirectResponse
    {
        if (!$this->omniDimension->isConfigured()) {
            toastr()->error(translate('OmniDimension_is_not_configured'));

            return back()->withInput();
        }

        $reasons = implode(',', OutboundCallContextService::callReasons());

        $validated = $request->validate([
            'campaign_name' => 'required|string|max:255',
            'phone_number_id' => 'required|integer|min:1',
            'audience_type' => 'required|in:all_customers,all_providers,providers_by_category,csv_import',
            'category_id' => 'required_if:audience_type,providers_by_category|nullable|string|max:64|exists:categories,id',
            'contacts_csv' => 'required_if:audience_type,csv_import|nullable|file|mimes:csv,txt|max:5120',
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
        ]);

        $csvPath = null;
        if ($validated['audience_type'] === 'csv_import' && $request->hasFile('contacts_csv')) {
            $dir = 'voice_bulk/csv';
            Storage::disk('local')->makeDirectory($dir);
            $csvPath = $request->file('contacts_csv')->storeAs(
                $dir,
                Str::uuid()->toString() . '.csv',
                'local'
            );
        }

        $recipients = $validated['audience_type'] === 'csv_import' && $csvPath
            ? $this->bulkContactBuilder->parseContactsCsv($csvPath)
            : $audienceService->resolve(
                $validated['audience_type'],
                $validated['category_id'] ?? null,
                null
            );

        if ($recipients === []) {
            toastr()->error(translate('no_data_found'));

            return back()->withInput();
        }

        $sharedContext = $this->outboundCallContext->build($validated);
        $contactList = $this->bulkContactBuilder->buildContactList($recipients, $sharedContext);

        if ($contactList === []) {
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
            toastr()->error(translate('Voice_bulk_campaign_failed'));

            return back()->withInput();
        }

        $campaignId = $result['campaign_id'];
        $status = $result['status'] ?? 'pending';
        $message = translate('Voice_bulk_campaign_created_successfully');
        if ($campaignId !== null) {
            $message .= ' #' . $campaignId . ' (' . $status . ')';
        }

        toastr()->success($message);

        return redirect()->route('admin.voice-call.index', ['tab' => 'bulk']);
    }
}
