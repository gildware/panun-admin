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
            $result = $this->runRule($rule, $force || $onlyRuleId !== null, $trigger);

            if ($result['status'] === WhatsAppVoiceFollowupAutomationRule::STATUS_SUCCESS) {
                $stats['dispatched'] += (int) ($result['dispatched_count'] ?? 0);
            } elseif ($result['status'] === WhatsAppVoiceFollowupAutomationRule::STATUS_EMPTY) {
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
        string $trigger = WhatsAppVoiceFollowupAutomationRun::TRIGGER_CRON
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
        $filters = $rule->normalizedFilters();
        $filters['_current_rule_id'] = $rule->id;

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
        if ($matchedCount === 0) {
            $message = translate('WhatsApp_followup_automation_no_candidates');

            return $this->finishRun($run, $rule, [
                'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_EMPTY,
                'matched_count' => 0,
                'dispatched_count' => 0,
                'message' => $message,
                'campaign_ids' => [],
                'error' => null,
            ], $startedAt);
        }

        $dispatchResult = $this->dispatchService->dispatchCandidates($candidates, [
            'campaign_name' => $rule->campaign_name,
            'send_option' => 'now',
            'concurrent_call_limit' => max(1, (int) $rule->concurrent_call_limit),
            'enabled_reschedule_call' => (bool) $rule->enabled_reschedule_call,
            'auto_retry' => (bool) $rule->auto_retry,
            'auto_retry_schedule' => $rule->auto_retry_schedule,
            'retry_limit' => max(1, (int) $rule->retry_limit),
            'source' => 'auto',
            'dispatched_by' => null,
        ]);

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
            ], $startedAt);
        }

        return $this->finishRun($run, $rule, [
            'status' => WhatsAppVoiceFollowupAutomationRule::STATUS_SUCCESS,
            'matched_count' => $matchedCount,
            'dispatched_count' => (int) $dispatchResult['dispatched_count'],
            'message' => $dispatchResult['message'],
            'campaign_ids' => $dispatchResult['campaign_ids'] ?? [],
            'error' => null,
        ], $startedAt);
    }

    /**
     * @param  array{status: string, matched_count: int, dispatched_count: int, message: string, campaign_ids: array<int, mixed>, error: ?string}  $result
     * @return array{status: string, dispatched_count: int, matched_count: int, message: string, run_id: int}
     */
    private function finishRun(
        WhatsAppVoiceFollowupAutomationRun $run,
        WhatsAppVoiceFollowupAutomationRule $rule,
        array $result,
        \Illuminate\Support\Carbon $startedAt
    ): array {
        $finishedAt = now();
        $durationMs = max(0, (int) $startedAt->diffInMilliseconds($finishedAt));

        $run->update([
            'status' => $result['status'],
            'contacts_matched' => (int) $result['matched_count'],
            'contacts_dispatched' => (int) $result['dispatched_count'],
            'campaign_ids' => array_values((array) ($result['campaign_ids'] ?? [])),
            'duration_ms' => $durationMs,
            'message' => mb_substr((string) $result['message'], 0, 2000),
            'error' => $result['error'] ? mb_substr((string) $result['error'], 0, 2000) : null,
            'finished_at' => $finishedAt,
        ]);

        $rule->update([
            'last_run_at' => $finishedAt,
            'last_run_contacts' => (int) $result['dispatched_count'],
            'last_run_status' => $result['status'],
            'last_run_message' => mb_substr((string) $result['message'], 0, 2000),
        ]);

        return [
            'status' => $result['status'],
            'dispatched_count' => (int) $result['dispatched_count'],
            'matched_count' => (int) $result['matched_count'],
            'message' => $result['message'],
            'run_id' => $run->id,
        ];
    }
}
