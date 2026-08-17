<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadComment;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Services\LeadOpenStatusService;

/**
 * Scores how completely employees fill closed leads (remarks, call logs, pipeline fields).
 */
class LeadDataQualityScoreService
{
    public const POINTS_INITIAL_REMARKS = 15;

    public const POINTS_CALL_LOG = 15;

    public const POINTS_CALL_LOG_REMARKS = 10;

    public const POINTS_COMMENT = 10;

    public const POINTS_IDENTITY = 10;

    public const POINTS_TYPED = 10;

    public const POINTS_PIPELINE_FIELDS = 20;

    public const POINTS_END_STATE_HYGIENE = 10;

    public const MAX_SCORE = 100;

    public const THRESHOLD_HIGH = 80;

    public const THRESHOLD_MID = 50;

    public const MARKS_HIGH = 5;

    public const MARKS_MID = 2;

    /**
     * @return list<array{label: string, points: int}>
     */
    public static function checkLegend(): array
    {
        return [
            ['label' => 'Initial call remarks', 'points' => self::POINTS_INITIAL_REMARKS],
            ['label' => 'Call log added', 'points' => self::POINTS_CALL_LOG],
            ['label' => 'Call log remarks', 'points' => self::POINTS_CALL_LOG_REMARKS],
            ['label' => 'Comment added', 'points' => self::POINTS_COMMENT],
            ['label' => 'Name and phone', 'points' => self::POINTS_IDENTITY],
            ['label' => 'Lead type set', 'points' => self::POINTS_TYPED],
            ['label' => 'Zone, category, and status', 'points' => self::POINTS_PIPELINE_FIELDS],
            ['label' => 'End-state reason / remarks', 'points' => self::POINTS_END_STATE_HYGIENE],
        ];
    }

    public static function rankingInputsSummary(): string
    {
        $parts = [];
        foreach (self::checkLegend() as $row) {
            $parts[] = $row['label'].' (+'.$row['points'].')';
        }

        return implode('; ', $parts).'. Total 100.';
    }

    public function __construct(
        private readonly LeadOpenStatusService $leadOpenStatus,
    ) {}

    /**
     * @param  list<string>  $employeeIds
     * @return array<string, array{
     *     closed_count: int,
     *     avg_score: float,
     *     quality_pct: float,
     *     pass_count: int,
     *     high_count: int,
     *     mid_count: int,
     *     low_count: int,
     *     mark_points: int
     * }>
     */
    public function summarizeForEmployees(array $employeeIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $empty = $this->emptySummary();
        $summaries = [];
        foreach ($employeeIds as $employeeId) {
            $summaries[(string) $employeeId] = $empty;
        }

        if ($employeeIds === []) {
            return $summaries;
        }

        $leads = Lead::query()
            ->whereIn('handled_by', $employeeIds)
            ->whereNotNull('handled_by')
            ->where('handled_by', '!=', Lead::HANDLED_BY_AI)
            ->whereBetween('date_time_of_lead_received', [
                $periodStart->copy()->startOfDay(),
                $periodEnd->copy()->endOfDay(),
            ])
            ->get([
                'id',
                'name',
                'phone_number',
                'lead_type',
                'remarks',
                'handled_by',
            ]);

        if ($leads->isEmpty()) {
            return $summaries;
        }

        $statusMeta = $this->leadOpenStatus->buildLeadStatusMeta($leads);
        $closedLeads = $leads->filter(function (Lead $lead) use ($statusMeta) {
            $meta = $statusMeta[(int) $lead->id] ?? null;

            return $meta !== null && ! ($meta['is_open'] ?? true);
        })->values();

        if ($closedLeads->isEmpty()) {
            return $summaries;
        }

        $leadIds = $closedLeads->pluck('id')->map(fn ($id) => (string) $id)->all();
        $context = $this->loadScoringContext($leadIds);

        $totals = [];
        foreach ($employeeIds as $employeeId) {
            $totals[(string) $employeeId] = [
                'score_sum' => 0,
                'closed_count' => 0,
                'pass_count' => 0,
                'high_count' => 0,
                'mid_count' => 0,
                'low_count' => 0,
            ];
        }

        foreach ($closedLeads as $lead) {
            $employeeId = (string) ($lead->handled_by ?? '');
            if ($employeeId === '' || ! isset($totals[$employeeId])) {
                continue;
            }

            $leadHistories = $context['histories']->get((string) $lead->id) ?? collect();
            $typedHistories = $leadHistories
                ->filter(fn (LeadTypeHistory $history) => (string) $history->type === (string) $lead->lead_type)
                ->values();

            $result = $this->scoreLead(
                $lead,
                $typedHistories->isNotEmpty() ? $typedHistories : $leadHistories,
                $context['call_logs']->get((string) $lead->id) ?? collect(),
                $context['comments']->get((string) $lead->id) ?? collect(),
                $context['customer_statuses'],
                $context['provider_statuses'],
            );

            $score = (int) $result['score'];
            $totals[$employeeId]['closed_count']++;
            $totals[$employeeId]['score_sum'] += $score;
            if (! empty($result['passes_hard_checks'])) {
                $totals[$employeeId]['pass_count']++;
            }
            if ($score >= self::THRESHOLD_HIGH) {
                $totals[$employeeId]['high_count']++;
            } elseif ($score >= self::THRESHOLD_MID) {
                $totals[$employeeId]['mid_count']++;
            } else {
                $totals[$employeeId]['low_count']++;
            }
        }

        foreach ($totals as $employeeId => $row) {
            $closed = (int) $row['closed_count'];
            $pass = (int) $row['pass_count'];
            $high = (int) $row['high_count'];
            $mid = (int) $row['mid_count'];

            $summaries[$employeeId] = [
                'closed_count' => $closed,
                'avg_score' => $closed > 0 ? round($row['score_sum'] / $closed, 1) : 0.0,
                'quality_pct' => $closed > 0 ? round(($pass / $closed) * 100, 1) : 0.0,
                'pass_count' => $pass,
                'high_count' => $high,
                'mid_count' => $mid,
                'low_count' => (int) $row['low_count'],
                'mark_points' => ($high * self::MARKS_HIGH) + ($mid * self::MARKS_MID),
            ];
        }

        return $summaries;
    }

    /**
     * @param  Collection<int, LeadTypeHistory>|null  $histories
     * @param  Collection<int, LeadFollowup>  $callLogs
     * @param  Collection<int, LeadComment>  $comments
     * @param  Collection<int|string, CustomerLeadStatus>  $customerStatuses
     * @param  Collection<int|string, ProviderLeadStatus>  $providerStatuses
     * @return array{
     *     score: int,
     *     max_score: int,
     *     checks: list<array{key: string, label: string, points: int, earned: int, passed: bool}>,
     *     passes_hard_checks: bool
     * }
     */
    public function scoreLead(
        Lead $lead,
        ?Collection $histories,
        Collection $callLogs,
        Collection $comments,
        Collection $customerStatuses,
        Collection $providerStatuses,
    ): array {
        $history = $histories?->first();
        $data = ($history && is_array($history->data)) ? $history->data : [];

        $hasInitialRemarks = trim((string) ($lead->remarks ?? '')) !== '';
        $hasCallLog = $callLogs->isNotEmpty();
        $hasCallLogRemarks = $callLogs->contains(
            fn (LeadFollowup $followup) => trim((string) ($followup->remarks ?? '')) !== ''
        );
        $hasComment = $comments->contains(
            fn (LeadComment $comment) => trim((string) ($comment->body ?? '')) !== ''
        );
        $hasIdentity = trim((string) ($lead->name ?? '')) !== ''
            && trim((string) ($lead->phone_number ?? '')) !== '';
        $isTyped = $lead->lead_type !== Lead::TYPE_UNKNOWN;

        $pipelinePassed = $this->pipelineFieldsComplete($lead, $data);
        $endStatePassed = $this->endStateHygieneComplete($lead, $data, $customerStatuses, $providerStatuses);

        $checks = [
            $this->check('initial_remarks', 'Initial call remarks', self::POINTS_INITIAL_REMARKS, $hasInitialRemarks),
            $this->check('call_log', 'Call log added', self::POINTS_CALL_LOG, $hasCallLog),
            $this->check('call_log_remarks', 'Call log remarks', self::POINTS_CALL_LOG_REMARKS, $hasCallLogRemarks),
            $this->check('comment', 'Comment added', self::POINTS_COMMENT, $hasComment),
            $this->check('identity', 'Name and phone filled', self::POINTS_IDENTITY, $hasIdentity),
            $this->check('typed', 'Lead type set', self::POINTS_TYPED, $isTyped),
            $this->check('pipeline_fields', 'Zone, category, and status', self::POINTS_PIPELINE_FIELDS, $pipelinePassed),
            $this->check('end_state_hygiene', 'End-state reason / remarks', self::POINTS_END_STATE_HYGIENE, $endStatePassed),
        ];

        $score = array_sum(array_column($checks, 'earned'));

        $passesHardChecks = $hasInitialRemarks
            && $hasCallLogRemarks
            && $pipelinePassed
            && $endStatePassed;

        return [
            'score' => $score,
            'max_score' => self::MAX_SCORE,
            'checks' => $checks,
            'passes_hard_checks' => $passesHardChecks,
        ];
    }

    /**
     * @return array{
     *     closed_count: int,
     *     avg_score: float,
     *     quality_pct: float,
     *     pass_count: int,
     *     high_count: int,
     *     mid_count: int,
     *     low_count: int,
     *     mark_points: int
     * }
     */
    public function emptySummary(): array
    {
        return [
            'closed_count' => 0,
            'avg_score' => 0.0,
            'quality_pct' => 0.0,
            'pass_count' => 0,
            'high_count' => 0,
            'mid_count' => 0,
            'low_count' => 0,
            'mark_points' => 0,
        ];
    }

    /**
     * @param  list<string>  $leadIds
     * @return array{
     *     histories: Collection<string, Collection<int, LeadTypeHistory>>,
     *     call_logs: Collection<string, Collection<int, LeadFollowup>>,
     *     comments: Collection<string, Collection<int, LeadComment>>,
     *     customer_statuses: Collection<int|string, CustomerLeadStatus>,
     *     provider_statuses: Collection<int|string, ProviderLeadStatus>
     * }
     */
    public function scoringContextForLeadIds(array $leadIds): array
    {
        return $this->loadScoringContext($leadIds);
    }

    /**
     * @param  list<string>  $leadIds
     * @return array{
     *     histories: Collection<string, Collection<int, LeadTypeHistory>>,
     *     call_logs: Collection<string, Collection<int, LeadFollowup>>,
     *     comments: Collection<string, Collection<int, LeadComment>>,
     *     customer_statuses: Collection<int|string, CustomerLeadStatus>,
     *     provider_statuses: Collection<int|string, ProviderLeadStatus>
     * }
     */
    private function loadScoringContext(array $leadIds): array
    {
        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn (LeadTypeHistory $history) => (string) $history->lead_id);

        $callLogs = LeadFollowup::query()
            ->whereIn('lead_id', $leadIds)
            ->where('contact_channel', LeadFollowup::CHANNEL_CALL)
            ->where('followup_status', LeadFollowup::STATUS_TAKEN)
            ->get(['id', 'lead_id', 'remarks', 'contact_channel', 'followup_status', 'created_by'])
            ->groupBy(fn (LeadFollowup $followup) => (string) $followup->lead_id);

        $comments = LeadComment::query()
            ->whereIn('lead_id', $leadIds)
            ->get(['id', 'lead_id', 'body', 'created_by'])
            ->groupBy(fn (LeadComment $comment) => (string) $comment->lead_id);

        $customerStatusIds = [];
        $providerStatusIds = [];
        foreach ($histories as $leadHistories) {
            $latest = $leadHistories->first();
            $data = ($latest && is_array($latest->data)) ? $latest->data : [];
            if (! empty($data['customer_lead_status_id'])) {
                $customerStatusIds[] = (int) $data['customer_lead_status_id'];
            }
            if (! empty($data['provider_lead_status_id'])) {
                $providerStatusIds[] = (int) $data['provider_lead_status_id'];
            }
        }

        $customerStatuses = $customerStatusIds !== []
            ? CustomerLeadStatus::query()->whereIn('id', array_unique($customerStatusIds))->get()->keyBy('id')
            : collect();
        $providerStatuses = $providerStatusIds !== []
            ? ProviderLeadStatus::query()->whereIn('id', array_unique($providerStatusIds))->get()->keyBy('id')
            : collect();

        return [
            'histories' => $histories,
            'call_logs' => $callLogs,
            'comments' => $comments,
            'customer_statuses' => $customerStatuses,
            'provider_statuses' => $providerStatuses,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function pipelineFieldsComplete(Lead $lead, array $data): bool
    {
        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            return $this->filled($data['customer_lead_status_id'] ?? null)
                && $this->hasZone($data)
                && $this->filled($data['service_category'] ?? null);
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            return $this->filled($data['provider_lead_status_id'] ?? null)
                && $this->hasZone($data)
                && $this->filled($data['provider_service_category'] ?? null);
        }

        if ($lead->lead_type === Lead::TYPE_INVALID) {
            return $this->filled($data['invalid_reason_id'] ?? null);
        }

        if ($lead->lead_type === Lead::TYPE_FUTURE_CUSTOMER) {
            return $this->filled($data['future_customer_reason_id'] ?? null);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<int|string, CustomerLeadStatus>  $customerStatuses
     * @param  Collection<int|string, ProviderLeadStatus>  $providerStatuses
     */
    private function endStateHygieneComplete(
        Lead $lead,
        array $data,
        Collection $customerStatuses,
        Collection $providerStatuses,
    ): bool {
        if ($lead->lead_type === Lead::TYPE_INVALID) {
            return $this->filled($data['invalid_reason_id'] ?? null)
                && $this->filled($data['invalid_remarks'] ?? null);
        }

        if ($lead->lead_type === Lead::TYPE_FUTURE_CUSTOMER) {
            return $this->filled($data['future_customer_reason_id'] ?? null)
                && $this->filled($data['future_customer_remarks'] ?? null);
        }

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $statusId = $data['customer_lead_status_id'] ?? null;
            if (! $this->filled($statusId)) {
                return false;
            }
            $status = $customerStatuses->get((int) $statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
            if ($baseType === 'cancel') {
                return $this->filled($data['cancellation_reason_id'] ?? null)
                    && $this->filled($data['cancellation_remarks'] ?? null);
            }

            return in_array($baseType, ['completed', 'cancel'], true);
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $statusId = $data['provider_lead_status_id'] ?? null;
            if (! $this->filled($statusId)) {
                return false;
            }
            $status = $providerStatuses->get((int) $statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
            if ($baseType === 'cancel') {
                return $this->filled($data['provider_cancellation_reason_id'] ?? null)
                    && $this->filled($data['provider_cancellation_remarks'] ?? null);
            }

            return in_array($baseType, ['completed', 'cancel'], true);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function hasZone(array $data): bool
    {
        if ($this->filled($data['zone_id'] ?? null)) {
            return true;
        }

        $zoneIds = $data['zone_ids'] ?? null;
        if (is_array($zoneIds)) {
            foreach ($zoneIds as $zoneId) {
                if ($this->filled($zoneId)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function filled(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return true;
    }

    /**
     * @return array{key: string, label: string, points: int, earned: int, passed: bool}
     */
    private function check(string $key, string $label, int $points, bool $passed): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'points' => $points,
            'earned' => $passed ? $points : 0,
            'passed' => $passed,
        ];
    }
}
