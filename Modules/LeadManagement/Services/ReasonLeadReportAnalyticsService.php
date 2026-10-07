<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\LeadManagement\Entities\CustomerLeadArea;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\Source;
use Modules\UserManagement\Entities\User;

/**
 * Reason, area, and remarks analysis for future-customer and invalid leads.
 * These lead types are already classified, so they are not split into hold or pending.
 */
class ReasonLeadReportAnalyticsService
{
    private const UNSPECIFIED_KEY = '__unspecified__';

    /**
     * @return array<string, mixed>
     */
    public function build(Builder $baseQuery, string $leadType): array
    {
        $leads = (clone $baseQuery)
            ->where('lead_type', $leadType)
            ->get(['id', 'date_time_of_lead_received', 'source_id', 'handled_by', 'phone_number', 'name', 'remarks']);

        $parentLabel = $leadType === Lead::TYPE_INVALID
            ? translate('Invalid_Reason')
            : translate('Future_Customer_Reason');

        if ($leads->isEmpty()) {
            return $this->emptyPayload($leadType, $parentLabel);
        }

        $reasonField = $leadType === Lead::TYPE_INVALID ? 'invalid_reason_id' : 'future_customer_reason_id';
        $remarksField = $leadType === Lead::TYPE_INVALID ? 'invalid_remarks' : 'future_customer_remarks';
        $reasonModel = $leadType === Lead::TYPE_INVALID ? LeadInvalidReason::class : LeadFutureCustomerReason::class;

        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leads->pluck('id')->all())
            ->where('type', $leadType)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        $reasonIds = [];
        $areaIds = [];
        foreach ($histories as $history) {
            $data = is_array($history->data) ? $history->data : [];
            if (!empty($data[$reasonField])) {
                $reasonIds[] = $data[$reasonField];
            }
            if (!empty($data['area_id'])) {
                $areaIds[] = $data['area_id'];
            }
        }

        $reasons = $reasonIds !== []
            ? $reasonModel::whereIn('id', array_unique($reasonIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();
        $areas = $areaIds !== []
            ? CustomerLeadArea::query()->whereIn('id', array_unique($areaIds))->get(['id', 'name'])->keyBy(fn ($row) => (string) $row->id)
            : collect();

        $sourceIds = $leads->pluck('source_id')->filter()->unique()->values()->all();
        $sources = $sourceIds !== []
            ? Source::whereIn('id', $sourceIds)->get(['id', 'name'])->keyBy('id')
            : collect();

        $handlerIds = $leads->pluck('handled_by')->filter(fn ($id) => Lead::assigneeIsHuman($id))->unique()->values()->all();
        $users = $handlerIds !== []
            ? User::whereIn('id', $handlerIds)->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id')
            : collect();

        $reasonCounts = [];
        $areaCounts = [];
        $sourceCounts = [];
        $reasonLeads = [];
        $areaLeads = [];
        $sourceLeads = [];
        $reasonArea = [];
        $reasonSource = [];
        $remarks = [];
        $rows = [];
        $missingReason = 0;

        foreach ($leads as $lead) {
            $history = $histories->get($lead->id);
            $data = ($history && is_array($history->data)) ? $history->data : [];
            $reason = $this->dimension(
                isset($data[$reasonField]) ? (string) $data[$reasonField] : null,
                $reasons
            );
            $area = $this->dimension(
                isset($data['area_id']) ? (string) $data['area_id'] : null,
                $areas
            );
            $sourceModel = $sources->get($lead->source_id);
            $source = ($sourceModel && trim((string) $sourceModel->name) !== '')
                ? ['key' => (string) $lead->source_id, 'label' => $sourceModel->name]
                : ['key' => self::UNSPECIFIED_KEY, 'label' => translate('Not_Specified')];

            if ($reason['key'] === self::UNSPECIFIED_KEY) {
                $missingReason++;
            }

            $leadId = (string) $lead->id;
            $this->increment($reasonCounts, $reason['key'], $reason['label']);
            $this->increment($areaCounts, $area['key'], $area['label']);
            $this->increment($sourceCounts, $source['key'], $source['label']);
            $reasonLeads[$reason['key']][] = $leadId;
            $areaLeads[$area['key']][] = $leadId;
            $sourceLeads[$source['key']][] = $leadId;
            $this->incrementNested($reasonArea, $reason['key'], $reason['label'], $area['key'], $area['label']);
            $this->incrementNested($reasonSource, $reason['key'], $reason['label'], $source['key'], $source['label']);

            $remarkText = trim((string) ($data[$remarksField] ?? ''));
            if ($remarkText === '') {
                $remarkText = trim((string) ($lead->remarks ?? ''));
            }
            if ($remarkText !== '') {
                $remarks[] = [
                    'category' => $reason['label'],
                    'zone' => $area['label'],
                    'reason' => $source['label'],
                    'text' => $remarkText,
                    'followup_count' => 0,
                    'hours_to_first_followup' => null,
                ];
            }

            $rows[] = [
                'lead_id' => $lead->id,
                'name' => $lead->name ?: '—',
                'phone' => $lead->phone_number,
                'type_reason' => $reason['label'],
                'status_remarks' => $remarkText !== '' ? $remarkText : '—',
                'area' => $area['label'],
                'handled_by' => $this->handlerLabel($lead->handled_by, $users),
                'source' => $source['label'],
                'received_at' => $lead->date_time_of_lead_received?->format('d M Y, h:i A') ?? '—',
            ];
        }

        return [
            'lead_type' => $leadType,
            'parent_label' => $parentLabel,
            'summary' => [
                'total' => $leads->count(),
                'missing_reason' => $missingReason,
            ],
            'reasons' => $this->finalize($reasonCounts),
            'areas' => $this->finalize($areaCounts),
            'sources' => $this->finalize($sourceCounts),
            'deep' => [
                'reason_area_matrix' => $this->finalizeNested($reasonArea),
                'reason_source_matrix' => $this->finalizeNested($reasonSource),
                'remarks' => array_slice($remarks, 0, 50),
            ],
            'rows' => $rows,
            'drilldown' => [
                'reasons' => $reasonLeads,
                'areas' => $areaLeads,
                'sources' => $sourceLeads,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(string $leadType, string $parentLabel): array
    {
        return [
            'lead_type' => $leadType,
            'parent_label' => $parentLabel,
            'summary' => ['total' => 0, 'missing_reason' => 0],
            'reasons' => [],
            'areas' => [],
            'sources' => [],
            'deep' => [
                'reason_area_matrix' => [],
                'reason_source_matrix' => [],
                'remarks' => [],
            ],
            'rows' => [],
            'drilldown' => [
                'reasons' => [],
                'areas' => [],
                'sources' => [],
            ],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<string, object>  $entities
     * @return array{key: string, label: string}
     */
    private function dimension(?string $id, $entities): array
    {
        $id = $id !== null ? trim($id) : '';
        if ($id === '' || $id === '0') {
            return ['key' => self::UNSPECIFIED_KEY, 'label' => translate('Not_Specified')];
        }
        $name = $entities->get($id)?->name ?? null;
        if ($name === null || trim((string) $name) === '') {
            return ['key' => self::UNSPECIFIED_KEY, 'label' => translate('Not_Specified')];
        }

        return ['key' => $id, 'label' => $name];
    }

    private function handlerLabel(?string $handledBy, $users): string
    {
        if (!Lead::assigneeIsHuman($handledBy)) {
            return $handledBy === Lead::HANDLED_BY_AI ? translate('AI') : translate('Unassigned');
        }
        $user = $users->get($handledBy);
        $fullName = $user ? trim(($user->first_name ?? '').' '.($user->last_name ?? '')) : '';

        return $fullName ?: ($user->email ?? (string) $handledBy);
    }

    /**
     * @param  array<string, array{label: string, total: int, key: string}>  $bucket
     */
    private function increment(array &$bucket, string $key, string $label): void
    {
        if (!isset($bucket[$key])) {
            $bucket[$key] = ['label' => $label, 'total' => 0, 'key' => $key];
        }
        $bucket[$key]['total']++;
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
     * @param  array<string, array{label: string, total: int, key: string}>  $bucket
     * @return list<array<string, mixed>>
     */
    private function finalize(array $bucket): array
    {
        $rows = array_values($bucket);
        $unspecified = translate('Not_Specified');
        usort($rows, function ($a, $b) use ($unspecified) {
            $aMissing = ($a['label'] ?? '') === $unspecified;
            $bMissing = ($b['label'] ?? '') === $unspecified;
            if ($aMissing !== $bMissing) {
                return $aMissing ? 1 : -1;
            }

            return ($b['total'] ?? 0) <=> ($a['total'] ?? 0);
        });

        return $rows;
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
}
