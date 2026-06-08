<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupAutomationRule;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupAutomationRun;
use Modules\LeadManagement\Services\WhatsAppVoiceFollowupAutomationRunner;

class VoiceCallCronJobController extends Controller
{
    public function __construct(
        private readonly WhatsAppVoiceFollowupAutomationRunner $runner
    ) {}

    public function runs(Request $request): View
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

        if ($result['status'] === WhatsAppVoiceFollowupAutomationRule::STATUS_SUCCESS) {
            if (request()->expectsJson()) {
                return response()->json(['ok' => true, 'result' => $result]);
            }
            toastr()->success($result['message']);

            return $this->redirectToCronTab();
        }

        if (request()->expectsJson()) {
            return response()->json(['ok' => false, 'result' => $result], 422);
        }

        toastr()->warning($result['message']);

        return $this->redirectToCronTab();
    }

    private function redirectToCronTab(): RedirectResponse
    {
        return redirect()->route('admin.voice-call.index', ['tab' => 'voice_cron']);
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
