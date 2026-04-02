<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;

class LeadOpenStatusService
{
    /**
     * Limit a leads query to rows that are "open" per {@see isLeadOpenByTypeHistory()}.
     */
    public function restrictQueryToOpenLeads(Builder $leadQuery): void
    {
        $candidateLeads = (clone $leadQuery)->get(['id', 'lead_type']);
        $meta = $this->buildLeadStatusMeta($candidateLeads);
        $openIds = array_keys(array_filter($meta, static fn (array $m) => $m['is_open']));
        if ($openIds === []) {
            $leadQuery->whereRaw('1 = 0');
        } else {
            $leadQuery->whereIn('id', $openIds);
        }
    }

    /**
     * @param Collection<int, Lead> $leads
     * @return array<int, array{is_open: bool, label: string, badge_class: string}>
     */
    public function buildLeadStatusMeta(Collection $leads): array
    {
        $leadIds = $leads->pluck('id')->all();
        if (empty($leadIds)) {
            return [];
        }

        $histories = LeadTypeHistory::whereIn('lead_id', $leadIds)
            ->whereIn('type', [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER])
            ->orderByDesc('created_at')
            ->get();

        $latestByComposite = [];
        foreach ($histories as $history) {
            $compositeKey = $history->lead_id . '|' . $history->type;
            if (!isset($latestByComposite[$compositeKey])) {
                $latestByComposite[$compositeKey] = $history;
            }
        }

        $customerStatusIds = [];
        $providerStatusIds = [];
        foreach ($latestByComposite as $key => $history) {
            $data = is_array($history->data) ? $history->data : [];
            if (str_ends_with((string) $key, '|' . Lead::TYPE_CUSTOMER) && !empty($data['customer_lead_status_id'])) {
                $customerStatusIds[] = (int) $data['customer_lead_status_id'];
            }
            if (str_ends_with((string) $key, '|' . Lead::TYPE_PROVIDER) && !empty($data['provider_lead_status_id'])) {
                $providerStatusIds[] = (int) $data['provider_lead_status_id'];
            }
        }

        $customerStatuses = !empty($customerStatusIds)
            ? CustomerLeadStatus::whereIn('id', array_unique($customerStatusIds))->get()->keyBy('id')
            : collect();
        $providerStatuses = !empty($providerStatusIds)
            ? ProviderLeadStatus::whereIn('id', array_unique($providerStatusIds))->get()->keyBy('id')
            : collect();

        $meta = [];
        foreach ($leads as $lead) {
            $history = $latestByComposite[$lead->id . '|' . $lead->lead_type] ?? null;
            $isOpen = $this->isLeadOpenByTypeHistory($lead, $history, $customerStatuses, $providerStatuses);
            $meta[(int) $lead->id] = [
                'is_open' => $isOpen,
                'label' => $isOpen ? 'Open' : 'Closed',
                'badge_class' => $isOpen ? 'bg-danger' : 'bg-success',
            ];
        }

        return $meta;
    }

    /**
     * @param Collection<int, CustomerLeadStatus>|null $customerStatuses
     * @param Collection<int, ProviderLeadStatus>|null $providerStatuses
     */
    public function isLeadOpenByTypeHistory(
        Lead $lead,
        ?LeadTypeHistory $typeHistory,
        ?Collection $customerStatuses = null,
        ?Collection $providerStatuses = null
    ): bool {
        if ($lead->lead_type === Lead::TYPE_UNKNOWN) {
            return true;
        }

        if (in_array($lead->lead_type, [Lead::TYPE_INVALID, Lead::TYPE_FUTURE_CUSTOMER], true)) {
            return false;
        }

        $data = ($typeHistory && is_array($typeHistory->data)) ? $typeHistory->data : [];

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $statusId = $data['customer_lead_status_id'] ?? null;
            if (!$statusId) {
                return true;
            }
            $status = $customerStatuses?->get((int) $statusId) ?? CustomerLeadStatus::find($statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));

            return !in_array($baseType, ['completed', 'cancel'], true);
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $statusId = $data['provider_lead_status_id'] ?? null;
            if (!$statusId) {
                return true;
            }
            $status = $providerStatuses?->get((int) $statusId) ?? ProviderLeadStatus::find($statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));

            return !in_array($baseType, ['completed', 'cancel'], true);
        }

        return false;
    }
}
