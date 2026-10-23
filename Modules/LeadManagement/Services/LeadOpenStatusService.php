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

        $this->hydrateUnknownCloseState($leads);

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
            $label = $isOpen ? 'Open' : 'Closed';
            $badgeClass = $isOpen ? 'bg-danger' : 'bg-success';
            if (! $isOpen && $lead->isNonResponsive()) {
                $label = 'Non Responsive';
                $badgeClass = 'bg-secondary';
            }
            $meta[(int) $lead->id] = [
                'is_open' => $isOpen,
                'label' => $label,
                'badge_class' => $badgeClass,
            ];
        }

        return $meta;
    }

    public function isLeadOpen(Lead $lead): bool
    {
        $meta = $this->buildLeadStatusMeta(collect([$lead]));

        return (bool) ($meta[(int) $lead->id]['is_open'] ?? false);
    }

    /**
     * Superfone should attach to this lead instead of minting an Unknown.
     * Open leads win. Booked / future leads are reused. Invalid, cancelled,
     * and non-responsive leads are not.
     */
    public function firstLeadForIncomingCall(Collection $leads): ?Lead
    {
        if ($leads->isEmpty()) {
            return null;
        }

        $open = $this->firstOpenLead($leads);
        if ($open) {
            return $open;
        }

        foreach ($leads as $lead) {
            if ($this->leadCanReceiveCall($lead)) {
                return $lead;
            }
        }

        return null;
    }

    public function firstOpenLead(Collection $leads): ?Lead
    {
        if ($leads->isEmpty()) {
            return null;
        }

        $meta = $this->buildLeadStatusMeta($leads);

        return $leads->first(
            fn (Lead $lead) => (bool) ($meta[(int) $lead->id]['is_open'] ?? false)
        );
    }

    /**
     * Booked (completed) customer/provider leads stay on the same person.
     * Cancelled, invalid, and non-responsive leads do not absorb the next call.
     */
    public function leadCanReceiveCall(Lead $lead): bool
    {
        $this->hydrateUnknownCloseState(collect([$lead]));

        if ($lead->lead_type === Lead::TYPE_INVALID) {
            return false;
        }

        if ($lead->isNonResponsive()) {
            return false;
        }

        if (in_array($lead->lead_type, [Lead::TYPE_UNKNOWN, Lead::TYPE_FUTURE_CUSTOMER, Lead::TYPE_FUTURE_PROVIDER], true)) {
            return true;
        }

        if (! in_array($lead->lead_type, [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER], true)) {
            return false;
        }

        $history = LeadTypeHistory::query()
            ->where('lead_id', $lead->id)
            ->where('type', $lead->lead_type)
            ->orderByDesc('created_at')
            ->first();
        $data = ($history && is_array($history->data)) ? $history->data : [];

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $statusId = $data['customer_lead_status_id'] ?? null;
            if (! $statusId) {
                return true;
            }
            $status = CustomerLeadStatus::query()->find($statusId);

            return strtolower((string) ($status?->base_type ?? 'pending')) !== 'cancel';
        }

        $statusId = $data['provider_lead_status_id'] ?? null;
        if (! $statusId) {
            return true;
        }
        $status = ProviderLeadStatus::query()->find($statusId);

        return strtolower((string) ($status?->base_type ?? 'pending')) !== 'cancel';
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
            $this->ensureUnknownCloseState($lead);

            return ! $lead->isNonResponsive();
        }

        if (in_array($lead->lead_type, [Lead::TYPE_INVALID, Lead::TYPE_FUTURE_CUSTOMER, Lead::TYPE_FUTURE_PROVIDER], true)) {
            return false;
        }

        $data = ($typeHistory && is_array($typeHistory->data)) ? $typeHistory->data : [];

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $this->ensureUnknownCloseState($lead);
            if ($lead->isNonResponsive()) {
                return false;
            }

            $statusId = $data['customer_lead_status_id'] ?? null;
            if (!$statusId) {
                return true;
            }
            $status = $customerStatuses?->get((int) $statusId) ?? CustomerLeadStatus::find($statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));

            return !in_array($baseType, ['completed', 'cancel'], true);
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $this->ensureUnknownCloseState($lead);
            if ($lead->isNonResponsive()) {
                return false;
            }

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

    /**
     * Partial lead selects used by list filters often omit the close columns.
     *
     * @param  Collection<int, Lead>  $leads
     */
    public function hydrateUnknownCloseState(Collection $leads): void
    {
        $missingIds = [];
        foreach ($leads as $lead) {
            if (! $lead->supportsNonResponsiveClose()) {
                continue;
            }
            if (! array_key_exists('non_responsive_at', $lead->getAttributes())) {
                $missingIds[] = (int) $lead->id;
            }
        }

        if ($missingIds === []) {
            return;
        }

        $flags = Lead::query()->whereIn('id', $missingIds)->pluck('non_responsive_at', 'id');
        foreach ($leads as $lead) {
            if (! $lead->supportsNonResponsiveClose()) {
                continue;
            }
            if (array_key_exists('non_responsive_at', $lead->getAttributes())) {
                continue;
            }
            $lead->setAttribute('non_responsive_at', $flags->get($lead->id));
        }
    }

    private function ensureUnknownCloseState(Lead $lead): void
    {
        if (array_key_exists('non_responsive_at', $lead->getAttributes())) {
            return;
        }

        $lead->setAttribute(
            'non_responsive_at',
            Lead::query()->whereKey($lead->id)->value('non_responsive_at')
        );
    }
}
