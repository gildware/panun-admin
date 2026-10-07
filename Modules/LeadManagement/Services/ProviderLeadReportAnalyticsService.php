<?php

namespace Modules\LeadManagement\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderCancellationReason;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Entities\Source;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;

class ProviderLeadReportAnalyticsService
{
    private const UNSPECIFIED_KEY = '__unspecified__';

    /**
     * @return array<string, mixed>
     */
    public function build(Builder $baseQuery, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $leads = (clone $baseQuery)
            ->where('lead_type', Lead::TYPE_PROVIDER)
            ->get(['id', 'date_time_of_lead_received', 'source_id', 'ad_source_id', 'handled_by', 'phone_number', 'name', 'next_followup_at', 'remarks']);

        if ($leads->isEmpty()) {
            return $this->emptyPayload();
        }

        $leadIds = $leads->pluck('id')->all();

        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->where('type', Lead::TYPE_PROVIDER)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        $statusIds = [];
        $zoneIds = [];
        $categoryIds = [];
        $subCategoryIds = [];
        $cancelReasonIds = [];

        foreach ($histories as $history) {
            $data = is_array($history->data) ? $history->data : [];
            if ($id = $this->normalizeReferenceId($data['provider_lead_status_id'] ?? null)) {
                $statusIds[] = $id;
            }
            foreach ($this->zoneIdsFromData($data) as $zid) {
                $zoneIds[] = $zid;
            }
            if ($id = $this->normalizeReferenceId($data['provider_service_category'] ?? null)) {
                $categoryIds[] = $id;
            }
            if ($id = $this->normalizeReferenceId($data['provider_service_subcategory'] ?? null)) {
                $subCategoryIds[] = $id;
            }
            if ($id = $this->normalizeReferenceId($data['provider_cancellation_reason_id'] ?? null)) {
                $cancelReasonIds[] = $id;
            }
        }

        $statuses = $statusIds !== []
            ? ProviderLeadStatus::whereIn('id', array_unique($statusIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();
        $zones = $zoneIds !== []
            ? Zone::withoutGlobalScopes()->whereIn('id', array_unique($zoneIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();
        $categories = $categoryIds !== []
            ? Category::withoutGlobalScopes()->whereIn('id', array_unique($categoryIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();
        $subCategories = $subCategoryIds !== []
            ? Category::withoutGlobalScopes()->ofType('sub')->whereIn('id', array_unique($subCategoryIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();
        $cancelReasons = $cancelReasonIds !== []
            ? ProviderCancellationReason::whereIn('id', array_unique($cancelReasonIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();

        $bucketKeys = ['pending', 'completed', 'cancelled'];
        $overall = array_fill_keys($bucketKeys, 0);
        $categoryBuckets = [];
        $zoneBuckets = [];
        $subCategoryBuckets = [];
        $completedCategory = [];
        $completedZone = [];
        $completedSubCategory = [];
        $cancelledCategory = [];
        $cancelledZone = [];
        $cancelReasonCounts = [];
        $outcomeLeads = ['pending' => [], 'completed' => [], 'cancelled' => []];
        $categoryLeads = [];
        $zoneLeads = [];
        $subCategoryLeads = [];
        $completedCategoryLeads = [];
        $completedZoneLeads = [];
        $completedSubCategoryLeads = [];
        $cancelledCategoryLeads = [];
        $cancelledZoneLeads = [];
        $cancelReasonLeads = [];
        $dayLeads = array_fill_keys(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], []);
        $hourLeads = array_fill_keys(array_map('strval', range(0, 23)), []);
        $leadHourCounts = array_fill(0, 24, 0);
        $leadDayCounts = [
            'Mon' => 0, 'Tue' => 0, 'Wed' => 0, 'Thu' => 0, 'Fri' => 0, 'Sat' => 0, 'Sun' => 0,
        ];

        $missingZone = 0;
        $missingCategory = 0;
        $openContext = [];
        $closedContext = [];

        foreach ($leads as $lead) {
            $leadId = (string) $lead->id;
            $history = $histories->get($lead->id);
            $data = ($history && is_array($history->data)) ? $history->data : [];
            $statusId = $this->normalizeReferenceId($data['provider_lead_status_id'] ?? null);
            $status = $statusId ? $statuses->get($statusId) : null;
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));

            $outcome = $this->classifyOutcome($baseType);
            $overall[$outcome]++;
            $this->appendLeadId($outcomeLeads, $outcome, $leadId);

            $zoneIdList = $this->zoneIdsFromData($data);
            $zoneId = $zoneIdList[0] ?? null;
            $categoryId = $this->normalizeReferenceId($data['provider_service_category'] ?? null);
            $subCategoryId = $this->normalizeReferenceId($data['provider_service_subcategory'] ?? null);

            if (!$zoneId) {
                $missingZone++;
            }
            if (!$categoryId) {
                $missingCategory++;
            }

            $categoryDim = $this->resolveDimension($categoryId, $categories);
            $zoneDim = $this->resolveDimension($zoneId, $zones);
            $subCategoryDim = $this->resolveDimension($subCategoryId, $subCategories);

            $this->incrementBucket($categoryBuckets, $categoryDim['key'], $categoryDim['label'], $outcome);
            $this->incrementBucket($zoneBuckets, $zoneDim['key'], $zoneDim['label'], $outcome);
            $this->incrementBucket($subCategoryBuckets, $subCategoryDim['key'], $subCategoryDim['label'], $outcome);
            $this->appendLeadId($categoryLeads, $categoryDim['key'], $leadId);
            $this->appendLeadId($zoneLeads, $zoneDim['key'], $leadId);
            $this->appendLeadId($subCategoryLeads, $subCategoryDim['key'], $leadId);

            if ($outcome === 'completed') {
                $this->incrementSimple($completedCategory, $categoryDim['key'], $categoryDim['label']);
                $this->incrementSimple($completedZone, $zoneDim['key'], $zoneDim['label']);
                $this->incrementSimple($completedSubCategory, $subCategoryDim['key'], $subCategoryDim['label']);
                $this->appendLeadId($completedCategoryLeads, $categoryDim['key'], $leadId);
                $this->appendLeadId($completedZoneLeads, $zoneDim['key'], $leadId);
                $this->appendLeadId($completedSubCategoryLeads, $subCategoryDim['key'], $leadId);
                $closedContext[] = [
                    'lead' => $lead,
                    'outcome' => 'completed',
                    'category' => $categoryDim,
                    'zone' => $zoneDim,
                    'subcategory' => $subCategoryDim,
                    'reason' => ['key' => self::UNSPECIFIED_KEY, 'label' => translate('Not_Specified')],
                    'remarks' => '',
                ];
            } elseif ($outcome === 'cancelled') {
                $this->incrementSimple($cancelledCategory, $categoryDim['key'], $categoryDim['label']);
                $this->incrementSimple($cancelledZone, $zoneDim['key'], $zoneDim['label']);
                $reasonId = $this->normalizeReferenceId($data['provider_cancellation_reason_id'] ?? null);
                $reasonDim = $this->resolveDimension($reasonId, $cancelReasons);
                $this->incrementSimple($cancelReasonCounts, $reasonDim['key'], $reasonDim['label']);
                $this->appendLeadId($cancelledCategoryLeads, $categoryDim['key'], $leadId);
                $this->appendLeadId($cancelledZoneLeads, $zoneDim['key'], $leadId);
                $this->appendLeadId($cancelReasonLeads, $reasonDim['key'], $leadId);
                $closedContext[] = [
                    'lead' => $lead,
                    'outcome' => 'cancelled',
                    'category' => $categoryDim,
                    'zone' => $zoneDim,
                    'subcategory' => $subCategoryDim,
                    'reason' => $reasonDim,
                    'remarks' => trim((string) ($data['provider_cancellation_remarks'] ?? '')),
                ];
            } else {
                $openContext[] = [
                    'lead' => $lead,
                    'status_tab' => app(LeadOpenStatusDeepBuilder::class)->isHold($lead, (string) ($status?->name ?? '')) ? 'hold' : 'pending',
                    'category' => $categoryDim,
                    'zone' => $zoneDim,
                ];
            }

            $receivedAt = $lead->date_time_of_lead_received;
            if ($receivedAt instanceof Carbon) {
                $leadHourCounts[(int) $receivedAt->format('G')]++;
                $dayKey = $receivedAt->format('D');
                $leadDayCounts[$dayKey] = ($leadDayCounts[$dayKey] ?? 0) + 1;
                $this->appendLeadId($dayLeads, $dayKey, $leadId);
                $this->appendLeadId($hourLeads, (string) (int) $receivedAt->format('G'), $leadId);
            }
        }

        $total = $leads->count();
        $completed = $overall['completed'];
        $cancelled = $overall['cancelled'];
        $pending = $overall['pending'];
        $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;
        $cancelRate = $total > 0 ? round(($cancelled / $total) * 100, 1) : 0.0;

        $categoryWise = $this->finalizeBuckets($categoryBuckets, $total);
        $zoneWise = $this->finalizeBuckets($zoneBuckets, $total);
        $subCategoryWise = $this->finalizeBuckets($subCategoryBuckets, $total);

        $open = $this->buildOpenStatus($leads, $openContext);
        $closed = $this->buildClosedStatus($leads, $closedContext);

        $insights = $this->buildInsights(
            $total,
            $completed,
            $cancelled,
            $pending,
            $completionRate,
            $cancelRate,
            $missingZone,
            $missingCategory,
            $categoryWise,
            $zoneWise,
            $leadHourCounts,
            $cancelReasonCounts
        );

        return [
            'summary' => [
                'total' => $total,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'pending' => $pending,
                'hold' => (int) ($open['hold']['summary']['total'] ?? 0),
                'pending_action' => (int) ($open['pending']['summary']['total'] ?? 0),
                'completion_rate' => $completionRate,
                'cancel_rate' => $cancelRate,
                'missing_zone' => $missingZone,
                'missing_category' => $missingCategory,
            ],
            'insights' => $insights,
            'outcome_breakdown' => [
                ['label' => translate('completed'), 'total' => $completed, 'color' => '#1cc88a', 'key' => 'completed'],
                ['label' => translate('Cancelled'), 'total' => $cancelled, 'color' => '#e74a3b', 'key' => 'cancelled'],
                ['label' => translate('Pending'), 'total' => $pending, 'color' => '#f6c23e', 'key' => 'pending'],
            ],
            'category_wise' => $categoryWise,
            'zone_wise' => $zoneWise,
            'subcategory_wise' => $subCategoryWise,
            'completed' => [
                'category_wise' => $this->finalizeSimple($completedCategory),
                'zone_wise' => $this->finalizeSimple($completedZone),
                'subcategory_wise' => $this->finalizeSimple($completedSubCategory),
            ],
            'cancelled' => [
                'category_wise' => $this->finalizeSimple($cancelledCategory),
                'zone_wise' => $this->finalizeSimple($cancelledZone),
                'reasons' => $this->finalizeSimple($cancelReasonCounts),
            ],
            'hold' => [
                'category_wise' => $open['hold']['categories'] ?? [],
                'zone_wise' => $open['hold']['zones'] ?? [],
                'reasons' => $open['hold']['reasons'] ?? [],
            ],
            'pending_open' => [
                'category_wise' => $open['pending']['categories'] ?? [],
                'zone_wise' => $open['pending']['zones'] ?? [],
                'reasons' => $open['pending']['reasons'] ?? [],
            ],
            'hold_deep' => $open['hold'],
            'pending_deep' => $open['pending'],
            'cancelled_deep' => $closed['cancelled_deep'],
            'leads_by_tab' => [
                'completed' => $closed['completed_rows'],
                'cancelled' => $closed['cancelled_rows'],
                'hold' => $open['hold']['rows'] ?? [],
                'pending' => $open['pending']['rows'] ?? [],
            ],
            'tab_counts' => [
                'completed' => $completed,
                'cancelled' => $cancelled,
                'hold' => (int) ($open['hold']['summary']['total'] ?? 0),
                'pending' => (int) ($open['pending']['summary']['total'] ?? 0),
            ],
            'drilldown' => [
                'outcome' => $outcomeLeads,
                'category_wise' => $categoryLeads,
                'zone_wise' => $zoneLeads,
                'subcategory_wise' => $subCategoryLeads,
                'lead_received_by_day' => $dayLeads,
                'lead_received_by_hour' => $hourLeads,
                'completed' => [
                    'category_wise' => $completedCategoryLeads,
                    'zone_wise' => $completedZoneLeads,
                    'subcategory_wise' => $completedSubCategoryLeads,
                ],
                'cancelled' => [
                    'category_wise' => $cancelledCategoryLeads,
                    'zone_wise' => $cancelledZoneLeads,
                    'reasons' => $cancelReasonLeads,
                ],
                'hold' => [
                    'category_wise' => $open['hold']['category_leads'] ?? [],
                    'zone_wise' => $open['hold']['zone_leads'] ?? [],
                    'reasons' => $open['hold']['reason_leads'] ?? [],
                ],
                'pending_open' => [
                    'category_wise' => $open['pending']['category_leads'] ?? [],
                    'zone_wise' => $open['pending']['zone_leads'] ?? [],
                    'reasons' => $open['pending']['reason_leads'] ?? [],
                ],
            ],
            'lead_received_by_hour' => array_values($leadHourCounts),
            'lead_received_by_hour_labels' => $this->hourLabels(),
            'lead_received_by_day' => array_values($leadDayCounts),
            'lead_received_by_day_labels' => array_keys($leadDayCounts),
        ];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  list<array<string, mixed>>  $openContext
     * @return array{hold: array<string, mixed>, pending: array<string, mixed>, by_lead: array<int|string, array<string, mixed>>}
     */
    private function buildOpenStatus(Collection $leads, array $openContext): array
    {
        if ($openContext === []) {
            return app(LeadOpenStatusDeepBuilder::class)->build([]);
        }

        $leadIds = $leads->pluck('id')->all();
        $followupsByLead = LeadFollowup::query()
            ->whereIn('lead_id', $leadIds)
            ->orderBy('followup_at')
            ->get()
            ->groupBy('lead_id');

        $sourceIds = $leads->pluck('source_id')->filter()->unique()->values()->all();
        $sources = $sourceIds !== []
            ? Source::whereIn('id', $sourceIds)->get(['id', 'name'])->keyBy('id')
            : collect();

        $items = [];
        foreach ($openContext as $ctx) {
            $lead = $ctx['lead'];
            $source = $sources->get($lead->source_id);
            $items[] = [
                'lead' => $lead,
                'status_tab' => $ctx['status_tab'],
                'category' => $ctx['category'],
                'zone' => $ctx['zone'],
                'followups' => $followupsByLead->get($lead->id, collect()),
                'source' => $source?->name ?? '—',
            ];
        }

        return app(LeadOpenStatusDeepBuilder::class)->build($items);
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  list<array<string, mixed>>  $closedContext
     * @return array{completed_rows: list<array<string, mixed>>, cancelled_rows: list<array<string, mixed>>, cancelled_deep: array<string, mixed>}
     */
    private function buildClosedStatus(Collection $leads, array $closedContext): array
    {
        $emptyDeep = [
            'category_reason_matrix' => [],
            'category_zone_matrix' => [],
            'reason_zone_matrix' => [],
            'remarks' => [],
        ];
        if ($closedContext === []) {
            return [
                'completed_rows' => [],
                'cancelled_rows' => [],
                'cancelled_deep' => $emptyDeep,
            ];
        }

        $followupsByLead = LeadFollowup::query()
            ->whereIn('lead_id', $leads->pluck('id')->all())
            ->orderBy('followup_at')
            ->get()
            ->groupBy('lead_id');

        $handlerIds = [];
        foreach ($closedContext as $ctx) {
            $handledBy = $ctx['lead']->handled_by ?? null;
            if (Lead::assigneeIsHuman($handledBy)) {
                $handlerIds[] = $handledBy;
            }
        }
        $users = $handlerIds !== []
            ? User::whereIn('id', array_values(array_unique($handlerIds)))->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id')
            : collect();

        $sourceIds = $leads->pluck('source_id')->filter()->unique()->values()->all();
        $sources = $sourceIds !== []
            ? Source::whereIn('id', $sourceIds)->get(['id', 'name'])->keyBy('id')
            : collect();

        $builder = app(LeadOpenStatusDeepBuilder::class);
        $completedRows = [];
        $cancelledRows = [];
        $categoryReason = [];
        $categoryZone = [];
        $reasonZone = [];
        $remarks = [];

        foreach ($closedContext as $ctx) {
            /** @var Lead $lead */
            $lead = $ctx['lead'];
            $followups = $followupsByLead->get($lead->id, collect());
            $engagement = $builder->engagement($lead, $followups);
            $handledBy = $lead->handled_by;
            if (!Lead::assigneeIsHuman($handledBy)) {
                $handlerLabel = $handledBy === Lead::HANDLED_BY_AI ? translate('AI') : translate('Unassigned');
            } else {
                $user = $users->get($handledBy);
                $fullName = $user ? trim(($user->first_name ?? '').' '.($user->last_name ?? '')) : '';
                $handlerLabel = $fullName ?: ($user->email ?? (string) $handledBy);
            }
            $source = $sources->get($lead->source_id);
            $category = $ctx['category'];
            $zone = $ctx['zone'];
            $reason = $ctx['reason'];
            $row = [
                'lead_id' => $lead->id,
                'name' => $lead->name ?: '—',
                'phone' => $lead->phone_number,
                'category' => $category['label'],
                'zone' => $zone['label'],
                'subcategory' => $ctx['subcategory']['label'] ?? '—',
                'cancel_reason' => ($ctx['outcome'] ?? '') === 'cancelled' ? $reason['label'] : '—',
                'cancellation_remarks' => ($ctx['remarks'] ?? '') !== '' ? $ctx['remarks'] : '—',
                'handled_by' => $handlerLabel,
                'source' => $source?->name ?? '—',
                'received_at' => $lead->date_time_of_lead_received?->format('d M Y, h:i A') ?? '—',
                'followup_count' => $engagement['followup_count'] ?? 0,
                'hours_to_first_followup' => $engagement['hours_to_first_followup'] ?? null,
                'first_followup_on_time' => $engagement['first_followup_on_time'] ?? null,
                'never_followed_up' => $engagement['never_followed_up'] ?? false,
                'delayed_first_contact' => $engagement['delayed_first_contact'] ?? false,
            ];

            if (($ctx['outcome'] ?? '') === 'completed') {
                $completedRows[] = $row;
                continue;
            }

            $cancelledRows[] = $row;
            $this->incrementNested($categoryReason, $category['key'], $category['label'], $reason['key'], $reason['label']);
            $this->incrementNested($categoryZone, $category['key'], $category['label'], $zone['key'], $zone['label']);
            $this->incrementNested($reasonZone, $reason['key'], $reason['label'], $zone['key'], $zone['label']);
            if (($ctx['remarks'] ?? '') !== '') {
                $remarks[] = [
                    'category' => $category['label'],
                    'zone' => $zone['label'],
                    'reason' => $reason['label'],
                    'text' => $ctx['remarks'],
                    'followup_count' => $engagement['followup_count'] ?? 0,
                    'hours_to_first_followup' => $engagement['hours_to_first_followup'] ?? null,
                ];
            }
        }

        return [
            'completed_rows' => $completedRows,
            'cancelled_rows' => $cancelledRows,
            'cancelled_deep' => [
                'category_reason_matrix' => $this->finalizeNested($categoryReason),
                'category_zone_matrix' => $this->finalizeNested($categoryZone),
                'reason_zone_matrix' => $this->finalizeNested($reasonZone),
                'remarks' => array_slice($remarks, 0, 50),
            ],
        ];
    }

    /**
     * @param  array<string, array{label: string, total: int, children: array<string, array{label: string, total: int}>}>  $matrix
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
    private function finalizeNested(array $matrix): array
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

    private function classifyOutcome(string $baseType): string
    {
        if ($baseType === 'cancel') {
            return 'cancelled';
        }
        if ($baseType === 'completed') {
            return 'completed';
        }

        return 'pending';
    }

    /**
     * @return list<string>
     */
    private function zoneIdsFromData(array $data): array
    {
        if (!empty($data['zone_ids']) && is_array($data['zone_ids'])) {
            $ids = [];
            foreach ($data['zone_ids'] as $zid) {
                if ($normalized = $this->normalizeReferenceId($zid)) {
                    $ids[] = $normalized;
                }
            }

            return array_values(array_unique($ids));
        }
        if ($id = $this->normalizeReferenceId($data['zone_id'] ?? null)) {
            return [$id];
        }

        return [];
    }

    private function normalizeReferenceId(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || $value === '0') {
                return null;
            }

            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) (int) $value;
        }

        return null;
    }

    /**
     * @return array{key: string, label: string}
     */
    private function resolveDimension(?string $id, Collection $entities): array
    {
        $unspecifiedLabel = translate('Not_Specified');

        if (!$id) {
            return ['key' => self::UNSPECIFIED_KEY, 'label' => $unspecifiedLabel];
        }

        $entity = $entities->get($id);
        $name = $entity?->name ?? null;

        if ($name === null || trim((string) $name) === '') {
            return ['key' => self::UNSPECIFIED_KEY, 'label' => $unspecifiedLabel];
        }

        return ['key' => $id, 'label' => $name];
    }

    /**
     * @param  array<string, array{label: string, pending: int, completed: int, cancelled: int, total: int}>  $buckets
     */
    private function incrementBucket(array &$buckets, string $key, string $label, string $outcome): void
    {
        if (!isset($buckets[$key])) {
            $buckets[$key] = [
                'label' => $label,
                'pending' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'total' => 0,
            ];
        }
        $buckets[$key][$outcome]++;
        $buckets[$key]['total']++;
    }

    /**
     * @param  array<string, array{label: string, total: int}>  $bucket
     */
    private function incrementSimple(array &$bucket, string $key, string $label): void
    {
        if (!isset($bucket[$key])) {
            $bucket[$key] = ['label' => $label, 'total' => 0, 'key' => $key];
        }
        $bucket[$key]['total']++;
    }

    /**
     * @param  array<string, list<string>>  $bucket
     */
    private function appendLeadId(array &$bucket, string $key, string $leadId): void
    {
        if (!isset($bucket[$key])) {
            $bucket[$key] = [];
        }
        $bucket[$key][] = $leadId;
    }

    /**
     * @param  array<string, array{label: string, pending: int, completed: int, cancelled: int, total: int}>  $buckets
     * @return list<array<string, mixed>>
     */
    private function finalizeBuckets(array $buckets, int $grandTotal): array
    {
        $rows = [];
        foreach ($buckets as $key => $row) {
            $total = (int) $row['total'];
            $completed = (int) $row['completed'];
            $rows[] = [
                'key' => (string) $key,
                'label' => $row['label'],
                'total' => $total,
                'completed' => $completed,
                'cancelled' => (int) $row['cancelled'],
                'pending' => (int) $row['pending'],
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0.0,
                'share_percent' => $grandTotal > 0 ? round(($total / $grandTotal) * 100, 1) : 0.0,
            ];
        }
        usort($rows, fn ($a, $b) => $this->compareReportRows($a, $b));

        return $rows;
    }

    /**
     * @param  array<string, array{label: string, total: int}>  $bucket
     * @return list<array{label: string, total: int}>
     */
    private function finalizeSimple(array $bucket): array
    {
        $rows = array_values($bucket);
        usort($rows, fn ($a, $b) => $this->compareReportRows($a, $b));

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    private function compareReportRows(array $a, array $b): int
    {
        $unspecified = translate('Not_Specified');
        $aUnspecified = ($a['label'] ?? '') === $unspecified;
        $bUnspecified = ($b['label'] ?? '') === $unspecified;
        if ($aUnspecified !== $bUnspecified) {
            return $aUnspecified ? 1 : -1;
        }

        return ($b['total'] ?? 0) <=> ($a['total'] ?? 0);
    }

    /**
     * @return list<string>
     */
    private function hourLabels(): array
    {
        $labels = [];
        for ($h = 0; $h < 24; $h++) {
            $labels[] = sprintf('%02d:00', $h);
        }

        return $labels;
    }

    /**
     * @param  list<array<string, mixed>>  $categoryWise
     * @param  list<array<string, mixed>>  $zoneWise
     * @param  array<int, int>  $leadHourCounts
     * @param  array<string, array{label: string, total: int}>  $cancelReasonCounts
     * @return list<array{type: string, text: string}>
     */
    private function buildInsights(
        int $total,
        int $completed,
        int $cancelled,
        int $pending,
        float $completionRate,
        float $cancelRate,
        int $missingZone,
        int $missingCategory,
        array $categoryWise,
        array $zoneWise,
        array $leadHourCounts,
        array $cancelReasonCounts
    ): array {
        $insights = [];

        $insights[] = [
            'type' => 'info',
            'text' => sprintf(
                '%d %s: %d %s, %d %s, %d %s (%.1f%% %s).',
                $total,
                translate('provider_leads'),
                $completed,
                translate('completed'),
                $cancelled,
                translate('Cancelled'),
                $pending,
                translate('Pending'),
                $completionRate,
                translate('completion_rate')
            ),
        ];

        if ($completionRate >= 50) {
            $insights[] = [
                'type' => 'success',
                'text' => sprintf('%s %.1f%% %s.', translate('Strong'), $completionRate, translate('provider_completion_rate')),
            ];
        } elseif ($completionRate < 25 && $total >= 5) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf('%s %.1f%% %s — %s.', translate('Low'), $completionRate, translate('provider_completion_rate'), translate('Review_provider_onboarding_and_followup')),
            ];
        }

        if ($cancelRate >= 35 && $cancelled > 0) {
            $insights[] = [
                'type' => 'danger',
                'text' => sprintf('%s %.1f%% %s — %s.', translate('High'), $cancelRate, translate('cancellation_rate'), translate('Review_provider_dropout_and_requirements')),
            ];
        }

        if ($missingCategory > 0 && ($missingCategory / max(1, $total)) >= 0.15) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf('%d %s %s.', $missingCategory, translate('leads'), translate('missing_service_category_capture_on_intake')),
            ];
        }

        if ($missingZone > 0 && ($missingZone / max(1, $total)) >= 0.15) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf('%d %s %s.', $missingZone, translate('leads'), translate('missing_zone_capture_for_area_insights')),
            ];
        }

        if ($categoryWise !== []) {
            $top = $categoryWise[0];
            $insights[] = [
                'type' => 'info',
                'text' => sprintf(
                    '%s: %s — %d %s (%.1f%% %s).',
                    translate('Top_category'),
                    $top['label'],
                    $top['total'],
                    translate('Leads'),
                    $top['completion_rate'],
                    translate('completed')
                ),
            ];
            $worst = null;
            foreach ($categoryWise as $row) {
                if (($row['total'] ?? 0) >= 3 && ($worst === null || ($row['completion_rate'] ?? 100) < ($worst['completion_rate'] ?? 100))) {
                    $worst = $row;
                }
            }
            if ($worst && ($worst['completion_rate'] ?? 0) < 20 && ($worst['label'] ?? '') !== ($top['label'] ?? '')) {
                $insights[] = [
                    'type' => 'warning',
                    'text' => sprintf(
                        '%s %s (%.1f%% %s) — %s.',
                        translate('Weak_category'),
                        $worst['label'],
                        $worst['completion_rate'],
                        translate('completed'),
                        translate('Consider_training_or_capacity_in_this_service')
                    ),
                ];
            }
        }

        if ($zoneWise !== []) {
            $topZone = $zoneWise[0];
            $insights[] = [
                'type' => 'info',
                'text' => sprintf('%s: %s (%d %s).', translate('Top_zone'), $topZone['label'], $topZone['total'], translate('Leads')),
            ];
        }

        $peakLeadHour = $this->peakHour($leadHourCounts);
        if ($peakLeadHour !== null) {
            $insights[] = [
                'type' => 'info',
                'text' => sprintf('%s %s %s.', translate('Peak_lead_intake_time'), $peakLeadHour, translate('provider_staff_scheduling_hint')),
            ];
        }

        if ($cancelReasonCounts !== []) {
            $reasons = $this->finalizeSimple($cancelReasonCounts);
            $insights[] = [
                'type' => 'danger',
                'text' => sprintf('%s: %s.', translate('Top_cancellation_reason'), $reasons[0]['label']),
            ];
        }

        if ($pending > 0 && $pending >= ($completed + $cancelled)) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf('%d %s %s.', $pending, translate('Pending'), translate('provider_leads_need_followup')),
            ];
        }

        return $insights;
    }

    /**
     * @param  array<int, int>  $hourCounts
     */
    private function peakHour(array $hourCounts): ?string
    {
        if ($hourCounts === []) {
            return null;
        }
        $max = max($hourCounts);
        if ($max <= 0) {
            return null;
        }
        $hour = array_search($max, $hourCounts, true);

        return sprintf('%02d:00', (int) $hour);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        return [
            'summary' => [
                'total' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'pending' => 0,
                'hold' => 0,
                'pending_action' => 0,
                'completion_rate' => 0,
                'cancel_rate' => 0,
                'missing_zone' => 0,
                'missing_category' => 0,
            ],
            'insights' => [
                ['type' => 'info', 'text' => translate('No_data_found')],
            ],
            'outcome_breakdown' => [],
            'category_wise' => [],
            'zone_wise' => [],
            'subcategory_wise' => [],
            'completed' => ['category_wise' => [], 'zone_wise' => [], 'subcategory_wise' => []],
            'cancelled' => ['category_wise' => [], 'zone_wise' => [], 'reasons' => []],
            'hold' => ['category_wise' => [], 'zone_wise' => [], 'reasons' => []],
            'pending_open' => ['category_wise' => [], 'zone_wise' => [], 'reasons' => []],
            'hold_deep' => [],
            'pending_deep' => [],
            'cancelled_deep' => [
                'category_reason_matrix' => [],
                'category_zone_matrix' => [],
                'reason_zone_matrix' => [],
                'remarks' => [],
            ],
            'leads_by_tab' => [
                'completed' => [],
                'cancelled' => [],
                'hold' => [],
                'pending' => [],
            ],
            'tab_counts' => [
                'completed' => 0,
                'cancelled' => 0,
                'hold' => 0,
                'pending' => 0,
            ],
            'drilldown' => [
                'outcome' => ['pending' => [], 'completed' => [], 'cancelled' => []],
                'category_wise' => [],
                'zone_wise' => [],
                'subcategory_wise' => [],
                'lead_received_by_day' => array_fill_keys(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], []),
                'lead_received_by_hour' => array_fill_keys(array_map('strval', range(0, 23)), []),
                'completed' => ['category_wise' => [], 'zone_wise' => [], 'subcategory_wise' => []],
                'cancelled' => ['category_wise' => [], 'zone_wise' => [], 'reasons' => []],
                'hold' => ['category_wise' => [], 'zone_wise' => [], 'reasons' => []],
                'pending_open' => ['category_wise' => [], 'zone_wise' => [], 'reasons' => []],
            ],
            'lead_received_by_hour' => array_fill(0, 24, 0),
            'lead_received_by_hour_labels' => $this->hourLabels(),
            'lead_received_by_day' => [0, 0, 0, 0, 0, 0, 0],
            'lead_received_by_day_labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        ];
    }
}
