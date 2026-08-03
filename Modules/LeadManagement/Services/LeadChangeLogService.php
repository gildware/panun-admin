<?php

namespace Modules\LeadManagement\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\CustomerLeadArea;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\CustomerLeadTag;
use Modules\LeadManagement\Entities\District;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\LeadChangeLog;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\ProviderCancellationReason;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\ServiceManagement\Entities\Service;
use Modules\ZoneManagement\Entities\Zone;
use Modules\ProviderManagement\Entities\Provider;

class LeadChangeLogService
{
    public function record(int $leadId, array $changes, ?string $changedBy = null): void
    {
        if ($changes === []) {
            return;
        }

        LeadChangeLog::create([
            'lead_id' => $leadId,
            'changed_by' => $changedBy ?? Auth::id(),
            'changes' => $changes,
        ]);
    }

    /**
     * @return array<string, array{label: string, old: string, new: string}>
     */
    public function buildTypeHistoryDiff(string $leadType, array $oldData, array $newData): array
    {
        $diff = [];

        foreach ($this->typeHistoryFieldLabels($leadType) as $key => $labelKey) {
            if ($key === 'zone_ids' && $leadType === Lead::TYPE_PROVIDER) {
                $oldNorm = $this->normalizeProviderZoneIds($oldData);
                $newNorm = $this->normalizeProviderZoneIds($newData);
                if ($oldNorm === $newNorm) {
                    continue;
                }
                $diff[$key] = [
                    'label' => $labelKey,
                    'old' => $this->providerZonesDisplay($oldData),
                    'new' => $this->providerZonesDisplay($newData),
                ];
                continue;
            }

            $old = $oldData[$key] ?? null;
            $new = $newData[$key] ?? null;

            if ($this->normalizeTypeHistoryValue($leadType, $key, $old) === $this->normalizeTypeHistoryValue($leadType, $key, $new)) {
                continue;
            }

            $diff[$key] = [
                'label' => $labelKey,
                'old' => $this->typeHistoryDisplayValue($leadType, $key, $old),
                'new' => $this->typeHistoryDisplayValue($leadType, $key, $new),
            ];
        }

        return $diff;
    }

    /**
     * @param  array<int|string>  $oldTagIds
     * @param  array<int|string>  $newTagIds
     * @return array<string, array{label: string, old: string, new: string}>
     */
    public function buildCustomerTagsDiff(array $oldTagIds, array $newTagIds): array
    {
        $oldNorm = $this->normalizeIdList($oldTagIds);
        $newNorm = $this->normalizeIdList($newTagIds);

        if ($oldNorm === $newNorm) {
            return [];
        }

        return [
            'customer_lead_tags' => [
                'label' => 'Tags',
                'old' => $this->customerTagsDisplay($oldTagIds),
                'new' => $this->customerTagsDisplay($newTagIds),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function typeHistoryFieldLabels(string $leadType): array
    {
        return match ($leadType) {
            Lead::TYPE_CUSTOMER => [
                'zone_id' => 'Zone',
                'area_id' => 'Area',
                'service_category' => 'Category',
                'service_subcategory' => 'Sub_Category',
                'service_name' => 'Service',
                'variant_key' => 'Select_Service_Variant',
                'service_description' => 'Service_Additional_Details_(Optional)',
                'estimated_service_at' => 'Estimated_Date_Time_of_Service',
                'customer_lead_status_id' => 'Customer_Lead_Status',
                'temporary_provider_id' => 'Temporary_Provider',
                'cancellation_reason_id' => 'Customer_cancellation_reasons',
                'cancellation_remarks' => 'Cancellation_Remarks',
            ],
            Lead::TYPE_PROVIDER => [
                'district_id' => 'District',
                'full_address' => 'Full_Address',
                'service_areas' => 'Service_Areas',
                'zone_ids' => 'Zone',
                'area_id' => 'Area',
                'provider_service_category' => 'Service_Category',
                'provider_service_subcategory' => 'Sub_Category',
                'provider_service_details' => 'Service_Details',
                'provider_lead_status_id' => 'Provider_Lead_Status',
                'provider_cancellation_reason_id' => 'Provider_cancellation_reasons',
                'provider_cancellation_remarks' => 'Cancellation_Remarks',
            ],
            Lead::TYPE_INVALID => [
                'invalid_reason_id' => 'Reason',
                'area_id' => 'Area',
                'invalid_remarks' => 'Remarks',
            ],
            Lead::TYPE_FUTURE_CUSTOMER => [
                'future_customer_reason_id' => 'Reason',
                'area_id' => 'Area',
                'future_customer_remarks' => 'Remarks',
            ],
            default => [],
        };
    }

    /**
     * @param  array<int|string>  $ids
     */
    protected function normalizeIdList(array $ids): string
    {
        $normalized = array_values(array_unique(array_filter(array_map(static fn ($id) => (string) $id, $ids))));

        sort($normalized);

        return implode(',', $normalized);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function normalizeProviderZoneIds(array $data): string
    {
        $ids = [];
        if (! empty($data['zone_ids']) && is_array($data['zone_ids'])) {
            $ids = array_values(array_unique(array_filter(array_map('strval', $data['zone_ids']))));
        } elseif (! empty($data['zone_id'])) {
            $ids = [(string) $data['zone_id']];
        }

        sort($ids);

        return implode(',', $ids);
    }

    protected function normalizeTypeHistoryValue(string $leadType, string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($key === 'estimated_service_at') {
            try {
                return Carbon::parse($value)->format('Y-m-d H:i');
            } catch (\Throwable $e) {
                return (string) $value;
            }
        }

        if (is_array($value)) {
            return $this->normalizeIdList($value);
        }

        return (string) $value;
    }

    protected function typeHistoryDisplayValue(string $leadType, string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($key) {
            'zone_id' => Zone::withoutGlobalScopes()->find($value)?->name ?? (string) $value,
            'area_id' => CustomerLeadArea::find($value)?->name ?? (string) $value,
            'service_category', 'service_subcategory', 'provider_service_category', 'provider_service_subcategory' => Category::withoutGlobalScopes()->find($value)?->name ?? (string) $value,
            'service_name' => Service::withoutGlobalScopes()->find($value)?->name ?? (string) $value,
            'customer_lead_status_id' => CustomerLeadStatus::find($value)?->name ?? (string) $value,
            'provider_lead_status_id' => ProviderLeadStatus::find($value)?->name ?? (string) $value,
            'cancellation_reason_id' => LeadCancellationReason::find($value)?->name ?? (string) $value,
            'provider_cancellation_reason_id' => ProviderCancellationReason::find($value)?->name ?? (string) $value,
            'invalid_reason_id' => LeadInvalidReason::find($value)?->name ?? (string) $value,
            'future_customer_reason_id' => LeadFutureCustomerReason::find($value)?->name ?? (string) $value,
            'district_id' => District::find($value)?->name ?? (string) $value,
            'temporary_provider_id' => Provider::find($value)?->company_name ?? (string) $value,
            'estimated_service_at' => $this->formatDateTime($value),
            default => (string) $value,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function providerZonesDisplay(array $data): string
    {
        $ids = [];
        if (! empty($data['zone_ids']) && is_array($data['zone_ids'])) {
            $ids = array_values(array_unique(array_filter(array_map('strval', $data['zone_ids']))));
        } elseif (! empty($data['zone_id'])) {
            $ids = [(string) $data['zone_id']];
        }

        if ($ids === []) {
            return '—';
        }

        $labels = [];
        foreach ($ids as $id) {
            $name = Zone::withoutGlobalScopes()->find($id)?->name;
            if ($name) {
                $labels[] = $name;
            }
        }

        return $labels !== [] ? implode(', ', $labels) : '—';
    }

    /**
     * @param  array<int|string>  $tagIds
     */
    protected function customerTagsDisplay(array $tagIds): string
    {
        $ids = array_values(array_unique(array_filter(array_map(static fn ($id) => (int) $id, $tagIds))));
        if ($ids === []) {
            return '—';
        }

        $names = CustomerLeadTag::whereIn('id', $ids)->orderBy('name')->pluck('name')->all();

        return $names !== [] ? implode(', ', $names) : '—';
    }

    protected function formatDateTime(mixed $value): string
    {
        try {
            return Carbon::parse($value)->format('d F Y h:i a');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
