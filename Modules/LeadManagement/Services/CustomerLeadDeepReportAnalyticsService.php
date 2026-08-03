<?php

namespace Modules\LeadManagement\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\Source;
use Modules\UserManagement\Entities\User;

class CustomerLeadDeepReportAnalyticsService
{
    private const UNSPECIFIED_KEY = '__unspecified__';

    /** First follow-up SLA: next calendar day at 10:00 from lead received (matches LeadFollowupService). */
    private const FIRST_FOLLOWUP_SLA_HOURS = 24;

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  list<array<string, mixed>>  $leadRows  Per-lead snapshot from main analytics loop
     * @return array<string, mixed>
     */
    public function build(Collection $leads, array $leadRows): array
    {
        if ($leadRows === []) {
            return $this->emptyPayload();
        }

        $leadIds = $leads->pluck('id')->all();
        $followupsByLead = LeadFollowup::query()
            ->whereIn('lead_id', $leadIds)
            ->orderBy('followup_at')
            ->get()
            ->groupBy('lead_id');

        $handlerIds = $leads->pluck('handled_by')
            ->filter(fn ($id) => Lead::assigneeIsHuman($id))
            ->unique()
            ->values()
            ->all();

        $users = $handlerIds !== []
            ? User::whereIn('id', $handlerIds)->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id')
            : collect();

        $sourceIds = $leads->pluck('source_id')->filter()->unique()->values()->all();
        $sources = $sourceIds !== []
            ? Source::whereIn('id', $sourceIds)->get(['id', 'name'])->keyBy('id')
            : collect();

        $noResponseReasonIds = $this->noResponseReasonIds();

        $cancelCategoryReason = [];
        $cancelCategoryZone = [];
        $cancelReasonZone = [];
        $cancelRemarks = [];
        $staffBuckets = [];
        $engagementRows = [];
        $cancelledRows = [];
        $bookedRows = [];
        $noResponseCancelledRows = [];
        $leadsByTab = [
            'booked' => [],
            'cancelled' => [],
            'pending' => [],
            'hold' => [],
        ];

        foreach ($leadRows as $row) {
            $lead = $leads->get($row['lead_id']);
            if (!$lead) {
                continue;
            }

            $outcome = (string) ($row['outcome'] ?? 'pending');
            $statusTab = $this->resolveStatusTab($outcome, $lead, (string) ($row['status_name'] ?? ''));
            $category = $row['category'] ?? ['key' => self::UNSPECIFIED_KEY, 'label' => translate('Not_Specified')];
            $zone = $row['zone'] ?? ['key' => self::UNSPECIFIED_KEY, 'label' => translate('Not_Specified')];
            $subCategory = $row['subcategory'] ?? ['key' => self::UNSPECIFIED_KEY, 'label' => translate('Not_Specified')];
            $reason = $row['cancel_reason'] ?? ['key' => self::UNSPECIFIED_KEY, 'label' => translate('Not_Specified')];

            $followups = $followupsByLead->get($lead->id, collect());
            $engagement = $this->computeEngagementMetrics($lead, $followups);
            $engagement['outcome'] = $outcome;
            $engagement['status_tab'] = $statusTab;
            $engagement['lead_id'] = $lead->id;
            $engagement['is_no_response_cancel'] = $outcome === 'cancelled'
                && $this->isNoResponseReason($reason['key'], $reason['label'], $noResponseReasonIds);
            $engagementRows[] = $engagement;

            $handlerLabel = $this->resolveHandlerLabel($lead->handled_by, $users);
            $source = $sources->get($lead->source_id);

            $detailRow = [
                'lead_id' => $lead->id,
                'name' => $lead->name ?: '—',
                'phone' => $lead->phone_number,
                'category' => $category['label'],
                'zone' => $zone['label'],
                'subcategory' => $subCategory['label'],
                'status_tab' => $statusTab,
                'status_name' => $row['status_name'] ?? '—',
                'cancel_reason' => $outcome === 'cancelled' ? $reason['label'] : '—',
                'cancellation_remarks' => trim((string) ($row['cancellation_remarks'] ?? '')) ?: '—',
                'handled_by' => $handlerLabel,
                'source' => $source?->name ?? '—',
                'received_at' => $lead->date_time_of_lead_received?->format('d M Y, h:i A') ?? '—',
                'next_followup_at' => $lead->next_followup_at?->format('d M Y, h:i A') ?? '—',
                'followup_count' => $engagement['followup_count'],
                'hours_to_first_followup' => $engagement['hours_to_first_followup'],
                'first_followup_on_time' => $engagement['first_followup_on_time'],
                'never_followed_up' => $engagement['never_followed_up'],
                'delayed_first_contact' => $engagement['delayed_first_contact'],
                'is_no_response_cancel' => $engagement['is_no_response_cancel'],
            ];
            $leadsByTab[$statusTab][] = $detailRow;

            $handlerKey = Lead::assigneeIsHuman($lead->handled_by) ? (string) $lead->handled_by : Lead::FILTER_UNASSIGNED_VALUE;
            if (!isset($staffBuckets[$handlerKey])) {
                $staffBuckets[$handlerKey] = $this->emptyStaffBucket($handlerKey, $users);
            }
            $staffBuckets[$handlerKey]['total']++;
            if (isset($staffBuckets[$handlerKey][$outcome])) {
                $staffBuckets[$handlerKey][$outcome]++;
            }
            $staffBuckets[$handlerKey]['followup_count_total'] += $engagement['followup_count'];
            if ($engagement['hours_to_first_followup'] !== null) {
                $staffBuckets[$handlerKey]['first_followup_hours'][] = $engagement['hours_to_first_followup'];
            }
            if ($engagement['first_followup_on_time'] === true) {
                $staffBuckets[$handlerKey]['first_followup_on_time']++;
            } elseif ($engagement['first_followup_on_time'] === false) {
                $staffBuckets[$handlerKey]['first_followup_late']++;
            }
            if ($outcome === 'cancelled') {
                if ($engagement['followup_count'] === 0) {
                    $staffBuckets[$handlerKey]['cancelled_zero_followup']++;
                }
                if ($engagement['hours_to_first_followup'] !== null && $engagement['hours_to_first_followup'] > self::FIRST_FOLLOWUP_SLA_HOURS) {
                    $staffBuckets[$handlerKey]['cancelled_delayed_first_contact']++;
                }
            }

            if ($outcome === 'booked') {
                $bookedRows[] = $engagement;
            }

            if ($outcome !== 'cancelled') {
                continue;
            }

            $cancelledRows[] = $engagement;
            if ($engagement['is_no_response_cancel']) {
                $noResponseCancelledRows[] = $engagement;
            }

            $this->incrementNested($cancelCategoryReason, $category['key'], $category['label'], $reason['key'], $reason['label']);
            $this->incrementNested($cancelCategoryZone, $category['key'], $category['label'], $zone['key'], $zone['label']);
            $this->incrementNested($cancelReasonZone, $reason['key'], $reason['label'], $zone['key'], $zone['label']);

            $remarks = trim((string) ($row['cancellation_remarks'] ?? ''));
            if ($remarks !== '') {
                $cancelRemarks[] = [
                    'lead_id' => $lead->id,
                    'category' => $category['label'],
                    'zone' => $zone['label'],
                    'reason' => $reason['label'],
                    'text' => $remarks,
                    'hours_to_first_followup' => $engagement['hours_to_first_followup'],
                    'followup_count' => $engagement['followup_count'],
                ];
            }
        }

        return [
            'cancelled_deep' => [
                'category_reason_matrix' => $this->finalizeNestedMatrix($cancelCategoryReason),
                'category_zone_matrix' => $this->finalizeNestedMatrix($cancelCategoryZone),
                'reason_zone_matrix' => $this->finalizeNestedMatrix($cancelReasonZone),
                'remarks' => array_slice($cancelRemarks, 0, 50),
            ],
            'staff_performance' => $this->finalizeStaffPerformance($staffBuckets),
            'engagement' => $this->buildEngagementSummary($engagementRows, $cancelledRows, $bookedRows, $noResponseCancelledRows),
            'leads_by_tab' => $leadsByTab,
            'tab_counts' => [
                'booked' => count($leadsByTab['booked']),
                'cancelled' => count($leadsByTab['cancelled']),
                'pending' => count($leadsByTab['pending']),
                'hold' => count($leadsByTab['hold']),
            ],
        ];
    }

    private function resolveStatusTab(string $outcome, Lead $lead, string $statusName): string
    {
        if ($outcome === 'booked') {
            return 'booked';
        }
        if ($outcome === 'cancelled') {
            return 'cancelled';
        }

        if ($statusName !== '' && str_contains(strtolower($statusName), 'hold')) {
            return 'hold';
        }

        $next = $lead->next_followup_at;
        if ($next instanceof Carbon && $next->isFuture()) {
            return 'hold';
        }

        return 'pending';
    }

    private function resolveHandlerLabel(?string $handledBy, Collection $users): string
    {
        if (!Lead::assigneeIsHuman($handledBy)) {
            return $handledBy === Lead::HANDLED_BY_AI ? translate('AI') : translate('Unassigned');
        }

        $user = $users->get($handledBy);
        $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';

        return $fullName ?: ($user->email ?? (string) $handledBy);
    }

    /**
     * @param  Collection<int, \Illuminate\Support\Collection<int, LeadFollowup>>  $followups
     * @return array<string, mixed>
     */
    private function computeEngagementMetrics(Lead $lead, Collection $followups): array
    {
        $receivedAt = $lead->date_time_of_lead_received instanceof Carbon
            ? $lead->date_time_of_lead_received
            : ($lead->date_time_of_lead_received ? Carbon::parse($lead->date_time_of_lead_received) : null);

        $sorted = $followups->sortBy('followup_at')->values();
        $firstFollowup = $sorted->first();
        $firstFollowupAt = $firstFollowup?->followup_at instanceof Carbon
            ? $firstFollowup->followup_at
            : ($firstFollowup?->followup_at ? Carbon::parse($firstFollowup->followup_at) : null);

        $hoursToFirst = $this->hoursBetween($receivedAt, $firstFollowupAt);
        $slaDueAt = $receivedAt ? app(LeadFollowupService::class)->defaultNextFollowupAt($receivedAt) : null;

        $firstOnTime = null;
        if ($firstFollowupAt && $slaDueAt) {
            $firstOnTime = $firstFollowupAt->lte($slaDueAt);
        }

        $onTimeCount = 0;
        $lateCount = 0;
        $scheduledCount = 0;
        $previousDue = $slaDueAt;

        foreach ($sorted as $index => $followup) {
            $at = $followup->followup_at instanceof Carbon
                ? $followup->followup_at
                : ($followup->followup_at ? Carbon::parse($followup->followup_at) : null);
            if (!$at || !$previousDue) {
                $previousDue = $followup->next_followup_at instanceof Carbon
                    ? $followup->next_followup_at
                    : ($followup->next_followup_at ? Carbon::parse($followup->next_followup_at) : null);
                continue;
            }
            $scheduledCount++;
            if ($at->lte($previousDue)) {
                $onTimeCount++;
            } else {
                $lateCount++;
            }
            $previousDue = $followup->next_followup_at instanceof Carbon
                ? $followup->next_followup_at
                : ($followup->next_followup_at ? Carbon::parse($followup->next_followup_at) : null);
        }

        return [
            'followup_count' => $sorted->count(),
            'hours_to_first_followup' => $hoursToFirst,
            'first_followup_on_time' => $firstOnTime,
            'never_followed_up' => $sorted->isEmpty(),
            'delayed_first_contact' => $hoursToFirst !== null && $hoursToFirst > self::FIRST_FOLLOWUP_SLA_HOURS,
            'followup_on_time_count' => $onTimeCount,
            'followup_late_count' => $lateCount,
            'followup_scheduled_count' => $scheduledCount,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $all
     * @param  list<array<string, mixed>>  $cancelled
     * @param  list<array<string, mixed>>  $booked
     * @param  list<array<string, mixed>>  $noResponseCancelled
     * @return array<string, mixed>
     */
    private function buildEngagementSummary(array $all, array $cancelled, array $booked, array $noResponseCancelled): array
    {
        $allFirstHours = array_values(array_filter(array_column($all, 'hours_to_first_followup'), fn ($v) => $v !== null));
        $cancelledFirstHours = array_values(array_filter(array_column($cancelled, 'hours_to_first_followup'), fn ($v) => $v !== null));
        $bookedFirstHours = array_values(array_filter(array_column($booked, 'hours_to_first_followup'), fn ($v) => $v !== null));
        $noRespFirstHours = array_values(array_filter(array_column($noResponseCancelled, 'hours_to_first_followup'), fn ($v) => $v !== null));

        $cancelledZeroFollowup = count(array_filter($cancelled, fn ($r) => ($r['followup_count'] ?? 0) === 0));
        $noRespZeroFollowup = count(array_filter($noResponseCancelled, fn ($r) => ($r['followup_count'] ?? 0) === 0));
        $noRespDelayed = count(array_filter($noResponseCancelled, fn ($r) => ($r['delayed_first_contact'] ?? false) === true));

        $onTimeTotal = array_sum(array_column($all, 'followup_on_time_count'));
        $lateTotal = array_sum(array_column($all, 'followup_late_count'));
        $scheduledTotal = $onTimeTotal + $lateTotal;

        return [
            'summary' => [
                'avg_hours_to_first_followup' => $this->average($allFirstHours),
                'median_hours_to_first_followup' => $this->median($allFirstHours),
                'cancelled_never_followed_up' => $cancelledZeroFollowup,
                'cancelled_delayed_first_contact' => count(array_filter($cancelled, fn ($r) => ($r['delayed_first_contact'] ?? false) === true)),
                'followup_on_time_rate' => $scheduledTotal > 0 ? round(($onTimeTotal / $scheduledTotal) * 100, 1) : null,
                'followup_late_rate' => $scheduledTotal > 0 ? round(($lateTotal / $scheduledTotal) * 100, 1) : null,
            ],
            'no_response_analysis' => [
                'cancelled_count' => count($noResponseCancelled),
                'never_followed_up' => $noRespZeroFollowup,
                'delayed_first_contact' => $noRespDelayed,
                'avg_hours_to_first_followup' => $this->average($noRespFirstHours),
                'median_hours_to_first_followup' => $this->median($noRespFirstHours),
                'comparison' => [
                    'booked_median_hours' => $this->median($bookedFirstHours),
                    'cancelled_no_response_median_hours' => $this->median($noRespFirstHours),
                    'all_cancelled_median_hours' => $this->median($cancelledFirstHours),
                ],
            ],
            'insights' => $this->buildEngagementInsights(
                $cancelled,
                $noResponseCancelled,
                $cancelledZeroFollowup,
                $noRespZeroFollowup,
                $noRespDelayed,
                $this->median($bookedFirstHours),
                $this->median($noRespFirstHours),
                $scheduledTotal,
                $onTimeTotal,
                $lateTotal
            ),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $cancelled
     * @param  list<array<string, mixed>>  $noResponseCancelled
     * @return list<array{type: string, text: string}>
     */
    private function buildEngagementInsights(
        array $cancelled,
        array $noResponseCancelled,
        int $cancelledZeroFollowup,
        int $noRespZeroFollowup,
        int $noRespDelayed,
        ?float $bookedMedianHours,
        ?float $noRespMedianHours,
        int $scheduledTotal,
        int $onTimeTotal,
        int $lateTotal
    ): array {
        $insights = [];

        if ($cancelled !== [] && $cancelledZeroFollowup > 0) {
            $pct = round(($cancelledZeroFollowup / count($cancelled)) * 100, 1);
            $insights[] = [
                'type' => 'danger',
                'text' => sprintf(
                    '%d of %d %s (%.1f%%) %s.',
                    $cancelledZeroFollowup,
                    count($cancelled),
                    translate('cancelled_leads'),
                    $pct,
                    translate('had_zero_followups_before_cancel')
                ),
            ];
        }

        if (count($noResponseCancelled) > 0) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf(
                    '%d %s %s.',
                    count($noResponseCancelled),
                    translate('cancelled_leads'),
                    translate('marked_no_response_from_customer')
                ),
            ];
            if ($noRespZeroFollowup > 0) {
                $insights[] = [
                    'type' => 'danger',
                    'text' => sprintf(
                        '%d %s %s.',
                        $noRespZeroFollowup,
                        translate('no_response_cancellations'),
                        translate('had_no_staff_followup_logged')
                    ),
                ];
            }
            if ($noRespDelayed > 0) {
                $insights[] = [
                    'type' => 'warning',
                    'text' => sprintf(
                        '%d %s %s (> %dh).',
                        $noRespDelayed,
                        translate('no_response_cancellations'),
                        translate('first_contact_was_delayed'),
                        self::FIRST_FOLLOWUP_SLA_HOURS
                    ),
                ];
            }
            if ($bookedMedianHours !== null && $noRespMedianHours !== null && $noRespMedianHours > $bookedMedianHours * 1.5) {
                $insights[] = [
                    'type' => 'warning',
                    'text' => sprintf(
                        '%s: %s %.1fh vs %s %.1fh %s.',
                        translate('No_response_cancellations'),
                        translate('median_first_followup'),
                        $noRespMedianHours,
                        translate('booked_leads'),
                        $bookedMedianHours,
                        translate('suggesting_slower_staff_response_may_contribute')
                    ),
                ];
            }
        }

        if ($scheduledTotal > 0) {
            $onTimeRate = round(($onTimeTotal / $scheduledTotal) * 100, 1);
            $insights[] = [
                'type' => $onTimeRate >= 70 ? 'success' : 'warning',
                'text' => sprintf(
                    '%.1f%% %s (%d %s, %d %s).',
                    $onTimeRate,
                    translate('followups_completed_on_time'),
                    $onTimeTotal,
                    translate('on_time'),
                    $lateTotal,
                    translate('late')
                ),
            ];
        }

        return $insights;
    }

    /**
     * @param  array<string, array<string, mixed>>  $buckets
     * @return list<array<string, mixed>>
     */
    private function finalizeStaffPerformance(array $buckets): array
    {
        $rows = [];
        foreach ($buckets as $bucket) {
            $total = (int) $bucket['total'];
            $booked = (int) $bucket['booked'];
            $cancelled = (int) $bucket['cancelled'];
            $firstHours = $bucket['first_followup_hours'] ?? [];
            $onTime = (int) ($bucket['first_followup_on_time'] ?? 0);
            $late = (int) ($bucket['first_followup_late'] ?? 0);
            $firstTotal = $onTime + $late;

            $rows[] = [
                'handler_id' => $bucket['handler_id'],
                'label' => $bucket['label'],
                'total' => $total,
                'booked' => $booked,
                'cancelled' => $cancelled,
                'pending' => (int) ($bucket['pending'] ?? 0),
                'conversion_rate' => $total > 0 ? round(($booked / $total) * 100, 1) : 0.0,
                'cancel_rate' => $total > 0 ? round(($cancelled / $total) * 100, 1) : 0.0,
                'avg_followups_per_lead' => $total > 0 ? round(($bucket['followup_count_total'] ?? 0) / $total, 1) : 0.0,
                'avg_hours_to_first_followup' => $this->average($firstHours),
                'median_hours_to_first_followup' => $this->median($firstHours),
                'first_followup_on_time_rate' => $firstTotal > 0 ? round(($onTime / $firstTotal) * 100, 1) : null,
                'cancelled_zero_followup' => (int) ($bucket['cancelled_zero_followup'] ?? 0),
                'cancelled_delayed_first_contact' => (int) ($bucket['cancelled_delayed_first_contact'] ?? 0),
            ];
        }

        usort($rows, fn ($a, $b) => ($b['cancelled'] <=> $a['cancelled']) ?: ($b['total'] <=> $a['total']));

        return $rows;
    }

    /**
     * @param  array<string, array{label: string, children: array<string, array{label: string, total: int}>}>  $matrix
     */
    private function incrementNested(array &$matrix, string $parentKey, string $parentLabel, string $childKey, string $childLabel): void
    {
        if (!isset($matrix[$parentKey])) {
            $matrix[$parentKey] = ['label' => $parentLabel, 'total' => 0, 'children' => []];
        }
        $matrix[$parentKey]['total']++;
        if (!isset($matrix[$parentKey]['children'][$childKey])) {
            $matrix[$parentKey]['children'][$childKey] = ['label' => $childLabel, 'total' => 0];
        }
        $matrix[$parentKey]['children'][$childKey]['total']++;
    }

    /**
     * @param  array<string, array{label: string, total: int, children: array<string, array{label: string, total: int}>}>  $matrix
     * @return list<array<string, mixed>>
     */
    private function finalizeNestedMatrix(array $matrix): array
    {
        $rows = [];
        foreach ($matrix as $key => $row) {
            $children = array_values($row['children'] ?? []);
            usort($children, fn ($a, $b) => ($b['total'] ?? 0) <=> ($a['total'] ?? 0));
            $rows[] = [
                'key' => (string) $key,
                'label' => $row['label'],
                'total' => (int) $row['total'],
                'breakdown' => $children,
            ];
        }
        usort($rows, fn ($a, $b) => ($b['total'] ?? 0) <=> ($a['total'] ?? 0));

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyStaffBucket(string $handlerKey, Collection $users): array
    {
        if ($handlerKey === Lead::FILTER_UNASSIGNED_VALUE) {
            return [
                'handler_id' => $handlerKey,
                'label' => translate('Unassigned'),
                'total' => 0,
                'booked' => 0,
                'cancelled' => 0,
                'pending' => 0,
                'followup_count_total' => 0,
                'first_followup_hours' => [],
                'first_followup_on_time' => 0,
                'first_followup_late' => 0,
                'cancelled_zero_followup' => 0,
                'cancelled_delayed_first_contact' => 0,
            ];
        }

        $user = $users->get($handlerKey);
        $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';

        return [
            'handler_id' => $handlerKey,
            'label' => $fullName ?: ($user->email ?? $handlerKey),
            'total' => 0,
            'booked' => 0,
            'cancelled' => 0,
            'pending' => 0,
            'followup_count_total' => 0,
            'first_followup_hours' => [],
            'first_followup_on_time' => 0,
            'first_followup_late' => 0,
            'cancelled_zero_followup' => 0,
            'cancelled_delayed_first_contact' => 0,
        ];
    }

    /**
     * @return list<string>
     */
    private function noResponseReasonIds(): array
    {
        $needles = ['no response', 'unresponsive', 'not responding', 'no reply'];

        return LeadCancellationReason::query()
            ->where(function ($q) use ($needles) {
                foreach ($needles as $needle) {
                    $q->orWhereRaw('LOWER(name) LIKE ?', ['%' . $needle . '%']);
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    private function isNoResponseReason(string $reasonKey, string $reasonLabel, array $noResponseReasonIds): bool
    {
        if ($reasonKey !== self::UNSPECIFIED_KEY && in_array($reasonKey, $noResponseReasonIds, true)) {
            return true;
        }

        $hay = strtolower(trim($reasonLabel));
        foreach (['no response', 'unresponsive', 'not responding', 'no reply'] as $needle) {
            if ($hay !== '' && str_contains($hay, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function hoursBetween(?Carbon $from, ?Carbon $to): ?float
    {
        if (!$from || !$to) {
            return null;
        }

        return round($from->diffInMinutes($to, false) / 60, 2);
    }

    /**
     * @param  list<float|int>  $values
     */
    private function average(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        return round(array_sum($values) / count($values), 2);
    }

    /**
     * @param  list<float|int>  $values
     */
    private function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }
        sort($values, SORT_NUMERIC);
        $n = count($values);

        return round((float) $values[intval(floor(($n - 1) / 2))], 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        return [
            'cancelled_deep' => [
                'category_reason_matrix' => [],
                'category_zone_matrix' => [],
                'reason_zone_matrix' => [],
                'remarks' => [],
            ],
            'staff_performance' => [],
            'engagement' => [
                'summary' => [],
                'no_response_analysis' => [],
                'insights' => [],
            ],
            'leads_by_tab' => [
                'booked' => [],
                'cancelled' => [],
                'pending' => [],
                'hold' => [],
            ],
            'tab_counts' => [
                'booked' => 0,
                'cancelled' => 0,
                'pending' => 0,
                'hold' => 0,
            ],
        ];
    }
}
