<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Facades\Log;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupAutomationRule;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupAutomationRun;

class WhatsAppVoiceFollowupAutomationRunner
{
    public function __construct(
        private readonly WhatsAppFollowupCandidateQueryService $candidateQuery,
        private readonly WhatsAppVoiceFollowupDispatchService $dispatchService
    ) {}

    /**
     * @return array{processed: int, dispatched: int, skipped: int, failed: int}
     */
    public function runDueRules(bool $force = false, ?int $onlyRuleId = null): array
    {
        $stats = ['processed' => 0, 'dispatched' => 0, 'skipped' => 0, 'failed' => 0];

        $query = WhatsAppVoiceFollowupAutomationRule::query()->orderBy('id');
        if ($onlyRuleId !== null) {
            $query->where('id', $onlyRuleId);
        } else {
            $query->where('is_enabled', true);
        }

        $batchExcludedPhones = [];

        foreach ($query->get() as $rule) {
            if (!$force && $onlyRuleId === null && !$rule->isDue()) {
                continue;
            }

            if (!$rule->is_enabled && $onlyRuleId === null) {
                continue;
            }

            $stats['processed']++;
            $trigger = ($force || $onlyRuleId !== null)
                ? WhatsAppVoiceFollowupAutomationRun::TRIGGER_MANUAL
                : WhatsAppVoiceFollowupAutomationRun::TRIGGER_CRON;

            $otherCronMode = (string) ($rule->normalizedFilters()['other_cron_job_mode'] ?? '');
            $extraFilters = [];
            if (in_array($otherCronMode, ['exclude', 'exclude_all_active'], true) && $batchExcludedPhones !== []) {
                $extraFilters['_batch_excluded_phones'] = $batchExcludedPhones;
            }

            $result = $this->runRule($rule, $force || $onlyRuleId !== null, $trigger, $extraFilters);

            if (in_array($otherCronMode, ['exclude', 'exclude_all_active'], true)) {
                foreach ((array) ($result['matched_phones'] ?? []) as $phone) {
                    $phone = trim((string) $phone);
                    if ($phone !== '') {
                        $batchExcludedPhones[] = $phone;
                    }
                }
                $batchExcludedPhones = array_values(array_unique($batchExcludedPhones));
            }

            if (in_array($result['status'], [
                WhatsAppVoiceFollowupAutomationRule::STATUS_SUCCESS,
                WhatsAppVoiceFollowupAutomationRule::STATUS_PENDING_APPROVAL,
            ], true)) {
                $stats['dispatched'] += (int) ($result['dispatched_count'] ?? 0);
            } elseif (in_array($result['status'], [
                WhatsAppVoiceFollowupAutomationRule::STATUS_EMPTY,
                WhatsAppVoiceFollowupAutomationRule::STATUS_SKIPPED,
            ], true)) {
                $stats['skipped']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * @return array{status: string, dispatched_count: int, matched_count: int, message: string, run_id: ?int}
     */
    public function runRule(
        WhatsAppVoiceFollowupAutomationRule $rule,
        bool $forced = false,
        string $trigger = WhatsAppVoiceFollowupAutomationRun::TRIGGER_CRON,
        array $extraFilters = []
    ): array {
        $startedAt = now();
        $run = WhatsAppVoiceFollowupAutomationRun::create([
            'rule_id' => $rule->id,
            'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_SKIPPED,
            'contacts_matched' => 0,
            'contacts_dispatched' => 0,
            'campaign_ids' => [],
            'trigger' => $trigger,
            'started_at' => $startedAt,
        ]);

        if (!$rule->is_enabled && !$forced) {
            return $this->finishRun($run, $rule, [
                'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_SKIPPED,
                'matched_count' => 0,
                'dispatched_count' => 0,
                'message' => translate('Voice_cron_job_stopped'),
                'campaign_ids' => [],
                'error' => null,
            ], $startedAt);
        }

        $maxContacts = max(1, min(500, (int) $rule->max_contacts_per_run));
        $filters = array_merge($rule->normalizedFilters(), ['_current_rule_id' => $rule->id], $extraFilters);
        $matchedPhones = [];

        try {
            $candidates = $this->candidateQuery->collectAll($filters, $maxContacts);
        } catch (\Throwable $e) {
            report($e);

            return $this->finishRun($run, $rule, [
                'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_FAILED,
                'matched_count' => 0,
                'dispatched_count' => 0,
                'message' => translate('Voice_cron_job_run_failed'),
                'campaign_ids' => [],
                'error' => $e->getMessage(),
            ], $startedAt);
        }

        $matchedCount = $candidates->count();
        $matchedPhones = $candidates
            ->pluck('phone')
            ->map(fn ($phone) => trim((string) $phone))
            ->filter()
            ->values()
            ->all();

        if ($matchedCount === 0) {
            return $this->finishRun($run, $rule, [
                'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_EMPTY,
                'matched_count' => 0,
                'dispatched_count' => 0,
                'message' => translate('Voice_cron_run_zero_matches'),
                'campaign_ids' => [],
                'error' => null,
            ], $startedAt);
        }

        $dispatchMode = $rule->dispatch_mode === WhatsAppVoiceFollowupAutomationRule::DISPATCH_MODE_AUTO
            ? WhatsAppVoiceFollowupAutomationRule::DISPATCH_MODE_AUTO
            : WhatsAppVoiceFollowupAutomationRule::DISPATCH_MODE_APPROVAL;

        if ($dispatchMode === WhatsAppVoiceFollowupAutomationRule::DISPATCH_MODE_APPROVAL) {
            $this->supersedeOtherPendingRuns($rule->id, $run->id);

            return $this->finishRun($run, $rule, [
                'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_PENDING_APPROVAL,
                'matched_count' => $matchedCount,
                'dispatched_count' => 0,
                'message' => sprintf(translate('Voice_cron_pending_approval_message'), $matchedCount),
                'campaign_ids' => [],
                'error' => null,
                'pending_candidates' => $candidates->values()->all(),
            ], $startedAt, $matchedPhones);
        }

        $dispatchResult = $this->dispatchCandidatesForRule($rule, $candidates, $run->id);

        if (!$dispatchResult['ok']) {
            Log::warning('WhatsApp follow-up automation dispatch failed', [
                'rule_id' => $rule->id,
                'error' => $dispatchResult['error'] ?? null,
            ]);

            return $this->finishRun($run, $rule, [
                'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_FAILED,
                'matched_count' => $matchedCount,
                'dispatched_count' => 0,
                'message' => $dispatchResult['message'],
                'campaign_ids' => $dispatchResult['campaign_ids'] ?? [],
                'error' => $dispatchResult['error'] ?? null,
            ], $startedAt, $matchedPhones);
        }

        return $this->finishRun($run, $rule, [
            'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_SUCCESS,
            'matched_count' => $matchedCount,
            'dispatched_count' => (int) $dispatchResult['dispatched_count'],
            'message' => $dispatchResult['message'],
            'campaign_ids' => $dispatchResult['campaign_ids'] ?? [],
            'error' => null,
        ], $startedAt, $matchedPhones);
    }

    /**
     * @return array{status: string, dispatched_count: int, matched_count: int, message: string, run_id: int}
     */
    public function approveRun(WhatsAppVoiceFollowupAutomationRun $run, ?string $approvedBy = null, ?array $includedPhones = null): array
    {
        $run->loadMissing('rule');
        $rule = $run->rule;

        if (!$run->isPendingApproval()) {
            return [
                'status' => $run->status,
                'dispatched_count' => (int) $run->contacts_dispatched,
                'matched_count' => (int) $run->contacts_matched,
                'message' => translate('Voice_cron_run_not_pending_approval'),
                'run_id' => $run->id,
            ];
        }

        if ($rule === null) {
            return [
                'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_FAILED,
                'dispatched_count' => 0,
                'matched_count' => (int) $run->contacts_matched,
                'message' => translate('Voice_cron_job_run_failed'),
                'run_id' => $run->id,
            ];
        }

        $candidates = collect(is_array($run->pending_candidates) ? $run->pending_candidates : []);

        if ($includedPhones !== null) {
            $phoneSet = array_flip(array_map('strval', $includedPhones));
            $candidates = $candidates->filter(function ($candidate) use ($phoneSet) {
                return is_array($candidate) && isset($phoneSet[(string) ($candidate['phone'] ?? '')]);
            })->values();
        }

        if ($candidates->isEmpty()) {
            return $this->finishRun($run, $rule, [
                'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_EMPTY,
                'matched_count' => 0,
                'dispatched_count' => 0,
                'message' => translate('WhatsApp_followup_automation_no_candidates'),
                'campaign_ids' => [],
                'error' => null,
                'pending_candidates' => null,
                'approved_at' => now(),
                'approved_by' => $approvedBy,
            ], $run->started_at ?? now());
        }

        $originalMatched = (int) $run->contacts_matched;
        $dispatchResult = $this->dispatchCandidatesForRule($rule, $candidates, $run->id, $approvedBy);

        if (!$dispatchResult['ok']) {
            return $this->finishRun($run, $rule, [
                'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_FAILED,
                'matched_count' => $originalMatched,
                'dispatched_count' => 0,
                'message' => $dispatchResult['message'],
                'campaign_ids' => $dispatchResult['campaign_ids'] ?? [],
                'error' => $dispatchResult['error'] ?? null,
                'pending_candidates' => is_array($run->pending_candidates) ? $run->pending_candidates : [],
                'approved_at' => null,
                'approved_by' => null,
            ], $run->started_at ?? now());
        }

        return $this->finishRun($run, $rule, [
            'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_SUCCESS,
            'matched_count' => $originalMatched,
            'dispatched_count' => (int) $dispatchResult['dispatched_count'],
            'message' => $dispatchResult['message'],
            'campaign_ids' => $dispatchResult['campaign_ids'] ?? [],
            'error' => null,
            'pending_candidates' => null,
            'approved_at' => now(),
            'approved_by' => $approvedBy,
        ], $run->started_at ?? now());
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $candidates
     * @return array{ok: bool, campaign_ids: array<int, mixed>, dispatched_count: int, message: string, error: ?string}
     */
    private function dispatchCandidatesForRule(
        WhatsAppVoiceFollowupAutomationRule $rule,
        \Illuminate\Support\Collection $candidates,
        int $automationRunId,
        ?string $dispatchedBy = null
    ): array {
        return $this->dispatchService->dispatchCandidates($candidates, [
            'campaign_name' => $rule->campaign_name,
            'send_option' => 'now',
            'concurrent_call_limit' => max(1, (int) $rule->concurrent_call_limit),
            'enabled_reschedule_call' => (bool) $rule->enabled_reschedule_call,
            'auto_retry' => (bool) $rule->auto_retry,
            'auto_retry_schedule' => $rule->auto_retry_schedule,
            'retry_limit' => max(1, (int) $rule->retry_limit),
            'source' => $dispatchedBy ? 'cron_approved' : 'auto',
            'dispatched_by' => $dispatchedBy,
            'automation_run_id' => $automationRunId,
        ]);
    }

    private function supersedeOtherPendingRuns(int $ruleId, int $keepRunId): void
    {
        WhatsAppVoiceFollowupAutomationRun::query()
            ->where('rule_id', $ruleId)
            ->where('status', WhatsAppVoiceFollowupAutomationRun::STATUS_PENDING_APPROVAL)
            ->where('id', '!=', $keepRunId)
            ->update([
                'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_SKIPPED,
                'message' => translate('Voice_cron_pending_superseded'),
                'finished_at' => now(),
            ]);
    }

    /**
     * @param  array{status: string, matched_count: int, dispatched_count: int, message: string, campaign_ids: array<int, mixed>, error: ?string, pending_candidates?: ?array, approved_at?: ?\Illuminate\Support\Carbon, approved_by?: ?string}  $result
     * @return array{status: string, dispatched_count: int, matched_count: int, message: string, run_id: int}
     */
    private function finishRun(
        WhatsAppVoiceFollowupAutomationRun $run,
        WhatsAppVoiceFollowupAutomationRule $rule,
        array $result,
        \Illuminate\Support\Carbon $startedAt,
        array $matchedPhones = []
    ): array {
        $finishedAt = now();
        $durationMs = max(0, (int) $startedAt->diffInMilliseconds($finishedAt));

        $runUpdate = [
            'status' => $result['status'],
            'contacts_matched' => (int) $result['matched_count'],
            'contacts_dispatched' => (int) $result['dispatched_count'],
            'campaign_ids' => array_values((array) ($result['campaign_ids'] ?? [])),
            'duration_ms' => $durationMs,
            'message' => mb_substr((string) $result['message'], 0, 2000),
            'error' => $result['error'] ? mb_substr((string) $result['error'], 0, 2000) : null,
            'finished_at' => $finishedAt,
        ];

        if (array_key_exists('pending_candidates', $result)) {
            $runUpdate['pending_candidates'] = $result['pending_candidates'];
        }
        if (array_key_exists('approved_at', $result)) {
            $runUpdate['approved_at'] = $result['approved_at'];
        }
        if (array_key_exists('approved_by', $result)) {
            $runUpdate['approved_by'] = $result['approved_by'];
        }

        $run->update($runUpdate);

        $lastRunContacts = $result['status'] === WhatsAppVoiceFollowupAutomationRule::STATUS_PENDING_APPROVAL
            ? (int) $result['matched_count']
            : (int) $result['dispatched_count'];

        $rule->update([
            'last_run_at' => $finishedAt,
            'last_run_contacts' => $lastRunContacts,
            'last_run_status' => $result['status'],
            'last_run_message' => mb_substr((string) $result['message'], 0, 2000),
        ]);

        return [
            'status' => $result['status'],
            'dispatched_count' => (int) $result['dispatched_count'],
            'matched_count' => (int) $result['matched_count'],
            'matched_phones' => $matchedPhones,
            'message' => $result['message'],
            'run_id' => $run->id,
        ];
    }
}
