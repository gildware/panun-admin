<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupAutomationRule;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupAutomationRun;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupDispatch;
use Modules\LeadManagement\Services\OmniDimensionService;
use Modules\LeadManagement\Services\OutboundCallContextService;
use Modules\LeadManagement\Services\WhatsAppFollowupContextBuilder;
use Modules\LeadManagement\Services\VoiceCallTabCache;
use Modules\LeadManagement\Services\WhatsAppFollowupCandidateQueryService;
use Modules\LeadManagement\Services\WhatsAppVoiceFollowupAutomationRunner;
use Symfony\Component\HttpFoundation\Response;

class VoiceCallCronJobController extends Controller
{
    public function __construct(
        private readonly WhatsAppVoiceFollowupAutomationRunner $runner,
        private readonly VoiceCallTabCache $tabCache,
        private readonly OmniDimensionService $omniDimension
    ) {}

    public function runs(Request $request): View|Response
    {
        return $this->tabCache->respond($request, VoiceCallTabCache::TAB_VOICE_CRON, function () use ($request): string {
            return $this->renderRuns($request)->render();
        });
    }

    public function runDetails(Request $request, WhatsAppVoiceFollowupAutomationRun $run): View|Response
    {
        if ($request->ajax()) {
            return response($this->renderRunDetails($run)->render());
        }

        return $this->renderRunDetails($run);
    }

    private function renderRunDetails(WhatsAppVoiceFollowupAutomationRun $run): View
    {
        $run->load(['rule:id,name,campaign_name,dispatch_mode']);

        $callLogData = $this->loadRunCallLogs($run);

        return view('leadmanagement::admin.voice-calls._voice_cron_run_details', [
            'run' => $run,
            'calls' => $callLogData['calls'],
            'callsError' => $callLogData['calls_error'],
            'statusCounts' => $callLogData['status_counts'],
            'callReasonLabels' => OutboundCallContextService::callReasonLabels(),
            'contextKeys' => OutboundCallContextService::CONTEXT_KEYS,
        ]);
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function enrichPendingCandidates(array $raw): \Illuminate\Support\Collection
    {
        $builder = app(WhatsAppFollowupContextBuilder::class);

        return collect($raw)->map(function ($candidate) use ($builder) {
            if (!is_array($candidate)) {
                return $candidate;
            }

            $existingContext = is_array($candidate['call_context'] ?? null) ? $candidate['call_context'] : [];
            $needsRebuild = $existingContext === []
                || !isset($existingContext['customer_name'])
                || count($existingContext) < 4;

            if ($needsRebuild) {
                $built = $builder->buildForCandidate($candidate);
                $candidate['call_context'] = $built['context'];
                $candidate['lead_summary_preview'] = $built['lead_summary_preview'];
                $candidate['conversation_recap'] = $built['conversation_recap'];
            }

            return $candidate;
        })->values();
    }

    /**
     * @return array{calls: array<int, array<string, mixed>>, calls_error: ?string, status_counts: array<string, int>}
     */
    private function loadRunCallLogs(WhatsAppVoiceFollowupAutomationRun $run): array
    {
        $campaignIds = array_values(array_filter(array_map('intval', is_array($run->campaign_ids) ? $run->campaign_ids : [])));

        $dispatches = WhatsAppVoiceFollowupDispatch::query()
            ->with('lead:id,name,phone_number')
            ->where(function ($query) use ($run, $campaignIds) {
                $query->where('automation_run_id', $run->id);
                if ($campaignIds !== []) {
                    $query->orWhereIn('omnidim_campaign_id', $campaignIds);
                }
            })
            ->orderByDesc('id')
            ->get()
            ->unique('id')
            ->values();

        $contextByPhone = [];
        $dispatchMetaByPhone = [];
        foreach ($dispatches as $dispatch) {
            $phones = array_filter([
                (string) $dispatch->wa_phone,
                (string) $dispatch->to_number_e164,
                $this->omniDimension->normalizeToE164((string) $dispatch->wa_phone),
            ]);
            foreach ($phones as $phone) {
                if ($phone === '') {
                    continue;
                }
                $contextByPhone[$phone] = is_array($dispatch->call_context) ? $dispatch->call_context : [];
                $dispatchMetaByPhone[$phone] = $dispatch;
            }
        }

        $calls = [];
        $callsError = null;

        if ($this->omniDimension->isConfigured() && $campaignIds !== []) {
            foreach ($campaignIds as $campaignId) {
                $page = 1;
                $fetchedForCampaign = 0;
                $totalForCampaign = null;

                do {
                    $result = $this->omniDimension->listCallLogs([
                        'bulk_call_id' => $campaignId,
                        'page' => $page,
                        'page_size' => 150,
                    ], $callsError);

                    if (!$result['ok']) {
                        break;
                    }

                    $totalForCampaign = (int) ($result['total'] ?? 0);
                    foreach ($result['calls'] ?? [] as $call) {
                        if (!is_array($call)) {
                            continue;
                        }
                        $phone = trim((string) ($call['to_number'] ?? ''));
                        $normalized = $this->omniDimension->normalizeToE164($phone) ?? $phone;
                        $dispatch = $dispatchMetaByPhone[$normalized] ?? $dispatchMetaByPhone[$phone] ?? null;
                        $context = $contextByPhone[$normalized] ?? $contextByPhone[$phone] ?? [];

                        $calls[] = array_merge($call, [
                            'campaign_id' => $campaignId,
                            'dispatch_context' => $context,
                            'lead' => $dispatch?->lead,
                            'lead_type' => $dispatch?->lead_type,
                            'dispatched_at' => $dispatch?->created_at,
                        ]);
                        $fetchedForCampaign++;
                    }

                    $page++;
                } while ($totalForCampaign !== null && $fetchedForCampaign < $totalForCampaign && $page <= 20);
            }
        }

        if ($calls === [] && $dispatches->isNotEmpty()) {
            foreach ($dispatches as $dispatch) {
                $phone = (string) ($dispatch->wa_phone ?: $dispatch->to_number_e164);
                $calls[] = [
                    'id' => null,
                    'to_number' => $phone,
                    'call_status' => (string) ($dispatch->call_status ?: 'pending'),
                    'call_duration' => '—',
                    'time_of_call' => $dispatch->created_at?->format('d M Y H:i') ?? '—',
                    'campaign_id' => $dispatch->omnidim_campaign_id,
                    'dispatch_context' => is_array($dispatch->call_context) ? $dispatch->call_context : [],
                    'lead' => $dispatch->lead,
                    'lead_type' => $dispatch->lead_type,
                    'dispatched_at' => $dispatch->created_at,
                ];
            }
        }

        usort($calls, function (array $a, array $b): int {
            $timeA = (string) ($a['time_of_call'] ?? '');
            $timeB = (string) ($b['time_of_call'] ?? '');

            return strcmp($timeB, $timeA);
        });

        $statusCounts = [];
        foreach ($calls as $call) {
            $status = strtolower(trim((string) ($call['call_status'] ?? 'unknown')));
            if ($status === '') {
                $status = 'unknown';
            }
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }

        return [
            'calls' => $calls,
            'calls_error' => $callsError,
            'status_counts' => $statusCounts,
        ];
    }

    public function dispatchPreview(Request $request, WhatsAppVoiceFollowupAutomationRun $run): View|Response
    {
        if (!$run->isPendingApproval()) {
            return response('<div class="alert alert-warning mb-0">' . e(translate('Voice_cron_run_not_pending_approval')) . '</div>');
        }

        $candidates = $this->enrichPendingCandidates(is_array($run->pending_candidates) ? $run->pending_candidates : []);

        return view('leadmanagement::admin.voice-calls._voice_cron_dispatch_modal_body', [
            'run' => $run,
            'candidates' => $candidates,
            'callReasonLabels' => OutboundCallContextService::callReasonLabels(),
            'contextKeys' => OutboundCallContextService::CONTEXT_KEYS,
        ]);
    }

    public function approveRun(Request $request, WhatsAppVoiceFollowupAutomationRun $run): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'phones' => 'required|array|min:1',
            'phones.*' => 'required|string|max:32',
        ]);

        $result = $this->runner->approveRun($run, Auth::id(), array_values($validated['phones']));

        if ($result['status'] === WhatsAppVoiceFollowupAutomationRule::STATUS_SUCCESS) {
            $this->invalidateCronRelatedCaches();
            if (request()->expectsJson()) {
                return response()->json(['ok' => true, 'result' => $result]);
            }
            toastr()->success($result['message']);

            return $this->redirectToCronTab(skipCacheInvalidation: true);
        }

        if (request()->expectsJson()) {
            return response()->json(['ok' => false, 'result' => $result], 422);
        }

        toastr()->warning($result['message']);

        return $this->redirectToCronTab();
    }

    private function renderRuns(Request $request): View
    {
        $ruleId = (int) $request->get('rule_id', 0);
        $page = max(1, (int) $request->get('page', 1));
        $perPage = max(10, min(50, pagination_limit()));

        $query = WhatsAppVoiceFollowupAutomationRun::query()
            ->with('rule:id,name')
            ->orderByDesc('started_at');

        if ($ruleId > 0) {
            $query->where('rule_id', $ruleId);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return view('leadmanagement::admin.voice-calls._voice_cron_runs', [
            'runs' => $paginator,
            'ruleId' => $ruleId > 0 ? $ruleId : null,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $this->validateRule($request);
        $rule = WhatsAppVoiceFollowupAutomationRule::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'rule' => $rule]);
        }

        toastr()->success(translate('Voice_cron_job_saved'));

        return $this->redirectToCronTab();
    }

    public function update(Request $request, WhatsAppVoiceFollowupAutomationRule $rule): RedirectResponse|JsonResponse
    {
        $validated = $this->validateRule($request);
        $rule->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'rule' => $rule->fresh()]);
        }

        toastr()->success(translate('Voice_cron_job_saved'));

        return $this->redirectToCronTab();
    }

    public function destroy(WhatsAppVoiceFollowupAutomationRule $rule): RedirectResponse|JsonResponse
    {
        $rule->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        toastr()->success(translate('deleted_successfully'));

        return $this->redirectToCronTab();
    }

    public function stop(WhatsAppVoiceFollowupAutomationRule $rule): RedirectResponse|JsonResponse
    {
        $rule->update(['is_enabled' => false]);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'is_enabled' => false]);
        }

        toastr()->success(translate('Voice_cron_job_stopped'));

        return $this->redirectToCronTab();
    }

    public function start(WhatsAppVoiceFollowupAutomationRule $rule): RedirectResponse|JsonResponse
    {
        $rule->update(['is_enabled' => true]);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'is_enabled' => true]);
        }

        toastr()->success(translate('Voice_cron_job_started'));

        return $this->redirectToCronTab();
    }

    public function runNow(WhatsAppVoiceFollowupAutomationRule $rule): RedirectResponse|JsonResponse
    {
        $result = $this->runner->runRule(
            $rule,
            true,
            WhatsAppVoiceFollowupAutomationRun::TRIGGER_MANUAL
        );

        if (in_array($result['status'], [
            WhatsAppVoiceFollowupAutomationRule::STATUS_SUCCESS,
            WhatsAppVoiceFollowupAutomationRule::STATUS_PENDING_APPROVAL,
            WhatsAppVoiceFollowupAutomationRule::STATUS_EMPTY,
        ], true)) {
            $this->invalidateCronRelatedCaches();
            if (request()->expectsJson()) {
                return response()->json(['ok' => true, 'result' => $result]);
            }
            toastr()->success($result['message']);

            return $this->redirectToCronTab(skipCacheInvalidation: true);
        }

        if (request()->expectsJson()) {
            return response()->json(['ok' => false, 'result' => $result], 422);
        }

        toastr()->warning($result['message']);

        return $this->redirectToCronTab();
    }

    private function redirectToCronTab(bool $skipCacheInvalidation = false): RedirectResponse
    {
        if (!$skipCacheInvalidation) {
            $this->invalidateCronRelatedCaches();
        }

        return redirect()->route('admin.voice-call.index', ['tab' => 'voice_cron']);
    }

    private function invalidateCronRelatedCaches(): void
    {
        WhatsAppFollowupCandidateQueryService::clearSearchCache();
        $this->tabCache->forgetMany([
            VoiceCallTabCache::TAB_VOICE_CRON,
            VoiceCallTabCache::TAB_WHATSAPP_FOLLOWUP,
            VoiceCallTabCache::TAB_BULK,
        ]);
        $this->tabCache->forgetCallLogTabs();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRule(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_enabled' => 'nullable|boolean',
            'interval_minutes' => 'required|integer|min:15|max:10080',
            'campaign_name' => 'required|string|max:255',
            'max_contacts_per_run' => 'required|integer|min:1|max:500',
            'concurrent_call_limit' => 'nullable|integer|min:1|max:20',
            'enabled_reschedule_call' => 'nullable|boolean',
            'auto_retry' => 'nullable|boolean',
            'auto_retry_schedule' => 'nullable|string|in:immediately,next_day,scheduled_time',
            'retry_limit' => 'nullable|integer|min:1|max:5',
            'silent_min_hours' => 'nullable|integer|min:0|max:168',
            'silent_max_hours' => 'nullable|integer|min:0|max:168',
            'lead_types' => 'nullable|array',
            'lead_types.*' => 'string|max:32',
            'lead_open' => 'nullable|string|in:,open,closed',
            'wa_chat_bucket' => 'nullable|string|in:,open,closed',
            'wa_chat_tag_ids' => 'nullable|array',
            'wa_chat_tag_ids.*' => 'integer|min:1',
            'customer_lead_tag_ids' => 'nullable|array',
            'customer_lead_tag_ids.*' => 'integer|min:1',
            'handled_by' => 'nullable|string|in:,ai,human',
            'human_support' => 'nullable|string|in:,exclude,only',
            'exclude_called_within_hours' => 'nullable|integer|min:0|max:168',
            'other_cron_job_mode' => 'nullable|string|in:,include,exclude',
            'other_cron_job_ids' => 'nullable|array',
            'other_cron_job_ids.*' => 'integer|min:1',
            'dispatch_mode' => 'nullable|string|in:auto,approval',
        ]);

        return [
            'name' => $validated['name'],
            'is_enabled' => $request->boolean('is_enabled', true),
            'interval_minutes' => (int) $validated['interval_minutes'],
            'campaign_name' => $validated['campaign_name'],
            'max_contacts_per_run' => (int) $validated['max_contacts_per_run'],
            'concurrent_call_limit' => max(1, (int) ($validated['concurrent_call_limit'] ?? 1)),
            'enabled_reschedule_call' => $request->boolean('enabled_reschedule_call'),
            'auto_retry' => $request->boolean('auto_retry'),
            'auto_retry_schedule' => $validated['auto_retry_schedule'] ?? null,
            'retry_limit' => max(1, (int) ($validated['retry_limit'] ?? 1)),
            'dispatch_mode' => ($validated['dispatch_mode'] ?? WhatsAppVoiceFollowupAutomationRule::DISPATCH_MODE_APPROVAL) === WhatsAppVoiceFollowupAutomationRule::DISPATCH_MODE_AUTO
                ? WhatsAppVoiceFollowupAutomationRule::DISPATCH_MODE_AUTO
                : WhatsAppVoiceFollowupAutomationRule::DISPATCH_MODE_APPROVAL,
            'filters' => [
                'silent_min_hours' => (int) ($validated['silent_min_hours'] ?? 2),
                'silent_max_hours' => $validated['silent_max_hours'] ?? null,
                'lead_types' => array_values(array_filter((array) ($validated['lead_types'] ?? []))),
                'lead_open' => (string) ($validated['lead_open'] ?? ''),
                'wa_chat_bucket' => (string) ($validated['wa_chat_bucket'] ?? ''),
                'wa_chat_tag_ids' => array_map('intval', array_filter((array) ($validated['wa_chat_tag_ids'] ?? []))),
                'customer_lead_tag_ids' => array_map('intval', array_filter((array) ($validated['customer_lead_tag_ids'] ?? []))),
                'handled_by' => (string) ($validated['handled_by'] ?? ''),
                'human_support' => (string) ($validated['human_support'] ?? 'exclude'),
                'exclude_called_within_hours' => (int) ($validated['exclude_called_within_hours'] ?? 24),
                'other_cron_job_mode' => in_array((string) ($validated['other_cron_job_mode'] ?? ''), ['include', 'exclude'], true)
                    ? (string) $validated['other_cron_job_mode']
                    : '',
                'other_cron_job_ids' => array_map('intval', array_filter((array) ($validated['other_cron_job_ids'] ?? []))),
            ],
        ];
    }
}
