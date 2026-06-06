<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\CustomerLeadTag;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\LeadChangeLog;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadProviderChecklist;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderCancellationReason;
use Modules\LeadManagement\Entities\ProviderChecklistItem;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;

class AdminBusinessAiLeadInsightService
{
    public function __construct(
        protected LeadOpenStatusService $leadOpenStatus,
    ) {}

    /**
     * @param  Collection<int, Lead>  $leads
     * @return list<array<string, mixed>>
     */
    public function enrichSummaries(Collection $leads): array
    {
        if ($leads->isEmpty()) {
            return [];
        }

        $profiles = $this->buildProfilesForLeads($leads);

        return $leads->map(function (Lead $lead) use ($profiles) {
            $base = [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone_number,
                'lead_type' => $lead->lead_type,
                'source' => $lead->source?->name,
                'ad_source' => $lead->adSource?->name,
                'handled_by' => $lead->handled_by,
                'received_at' => $lead->date_time_of_lead_received?->toIso8601String(),
                'next_followup_at' => $lead->next_followup_at?->toIso8601String(),
                'remarks' => $lead->remarks,
                'tags' => $lead->relationLoaded('customerLeadTags')
                    ? $lead->customerLeadTags->pluck('name')->all()
                    : [],
            ];

            return array_merge($base, $profiles[(int) $lead->id] ?? []);
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function enrichDetail(Lead $lead): array
    {
        $lead->load([
            'source', 'adSource', 'createdBy', 'customerLeadTags',
            'followups', 'changeLogs.changedByUser', 'providerChecklist',
        ]);

        $statusMeta = $this->leadOpenStatus->buildLeadStatusMeta(collect([$lead]));
        $meta = $statusMeta[(int) $lead->id] ?? ['is_open' => false, 'label' => '—'];
        $profile = $this->buildProfilesForLeads(collect([$lead]))[(int) $lead->id] ?? [];

        $histories = LeadTypeHistory::query()
            ->where('lead_id', $lead->id)
            ->orderByDesc('created_at')
            ->get();

        $adminIds = $histories->pluck('created_by')
            ->merge($lead->changeLogs->pluck('changed_by'))
            ->merge($lead->followups->pluck('created_by'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $adminNames = $this->adminNamesById($adminIds);

        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'phone' => $lead->phone_number,
            'lead_type' => $lead->lead_type,
            'source' => $lead->source?->name,
            'ad_source' => $lead->adSource?->name,
            'handled_by' => $lead->handled_by,
            'remarks' => $lead->remarks,
            'received_at' => $lead->date_time_of_lead_received?->toIso8601String(),
            'next_followup_at' => $lead->next_followup_at?->toIso8601String(),
            'created_by' => $lead->createdBy ? trim($lead->createdBy->first_name.' '.$lead->createdBy->last_name) : null,
            'is_open' => (bool) ($meta['is_open'] ?? false),
            'pipeline_status_label' => $meta['label'] ?? null,
            'tags' => $lead->customerLeadTags->pluck('name')->all(),
            'type_profile' => $profile,
            'type_history' => $histories->map(fn (LeadTypeHistory $h) => [
                'type' => $h->type,
                'at' => $h->created_at?->toIso8601String(),
                'created_by' => $adminNames[(string) $h->created_by] ?? null,
                'data' => is_array($h->data) ? $h->data : [],
            ])->values()->all(),
            'followups' => $lead->followups->map(fn (LeadFollowup $f) => [
                'at' => $f->followup_at?->toIso8601String(),
                'remarks' => $f->remarks,
                'next_followup_at' => $f->next_followup_at?->toIso8601String(),
                'created_by' => $adminNames[(string) $f->created_by] ?? null,
            ])->values()->all(),
            'change_logs' => $lead->changeLogs->take(30)->map(fn (LeadChangeLog $c) => [
                'at' => $c->created_at?->toIso8601String(),
                'changed_by' => $c->changedByUser
                    ? trim($c->changedByUser->first_name.' '.$c->changedByUser->last_name)
                    : null,
                'changes' => is_array($c->changes) ? $c->changes : [],
            ])->values()->all(),
            'provider_checklist' => $lead->providerChecklist->map(fn (LeadProviderChecklist $c) => [
                'item_id' => $c->provider_checklist_item_id,
                'is_done' => (bool) $c->is_done,
            ])->values()->all(),
            'linked_bookings' => Booking::query()
                ->where('lead_id', $lead->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['id', 'readable_id', 'booking_status', 'total_booking_amount', 'created_at'])
                ->map(fn (Booking $b) => [
                    'readable_id' => $b->readable_id,
                    'status' => $b->booking_status,
                    'amount' => (float) ($b->total_booking_amount ?? 0),
                    'created_at' => $b->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function analyze(array $args): array
    {
        $analysis = strtolower(trim((string) ($args['analysis'] ?? 'full_lead_overview')));
        $leadType = (string) ($args['lead_type'] ?? 'customer');
        if ($leadType === 'all') {
            $leadType = '';
        }

        $q = Lead::query();
        if ($leadType !== '') {
            $q->where('lead_type', $leadType);
        }
        if (! empty($args['date_from'])) {
            $q->where('date_time_of_lead_received', '>=', Carbon::parse((string) $args['date_from'])->startOfDay());
        }
        if (! empty($args['date_to'])) {
            $q->where('date_time_of_lead_received', '<=', Carbon::parse((string) $args['date_to'])->endOfDay());
        }

        $leads = $q->orderByDesc('date_time_of_lead_received')->limit(2000)->get();
        $profiles = $this->buildProfilesForLeads($leads);

        $payload = [
            'ok' => true,
            'analysis' => $analysis,
            'leads_in_scope' => $leads->count(),
            'lead_type_filter' => $leadType !== '' ? $leadType : 'all',
        ];

        return match ($analysis) {
            'customer_cancellation_reasons' => array_merge($payload, $this->aggregateCustomerCancellations($leads, $profiles)),
            'provider_cancellation_reasons' => array_merge($payload, $this->aggregateProviderCancellations($leads, $profiles)),
            'invalid_reasons' => array_merge($payload, $this->aggregateSimpleReasons($leads, $profiles, 'invalid_reason', Lead::TYPE_INVALID)),
            'future_customer_reasons' => array_merge($payload, $this->aggregateSimpleReasons($leads, $profiles, 'future_customer_reason', Lead::TYPE_FUTURE_CUSTOMER)),
            'customer_status_breakdown' => array_merge($payload, $this->aggregateStatusBreakdown($leads, $profiles, Lead::TYPE_CUSTOMER)),
            'provider_status_breakdown' => array_merge($payload, $this->aggregateStatusBreakdown($leads, $profiles, Lead::TYPE_PROVIDER)),
            'full_lead_overview' => array_merge($payload, [
                'customer_cancellation_reasons' => $this->aggregateCustomerCancellations(
                    $leads->where('lead_type', Lead::TYPE_CUSTOMER),
                    $profiles
                ),
                'invalid_reasons' => $this->aggregateSimpleReasons($leads, $profiles, 'invalid_reason', Lead::TYPE_INVALID),
                'future_customer_reasons' => $this->aggregateSimpleReasons($leads, $profiles, 'future_customer_reason', Lead::TYPE_FUTURE_CUSTOMER),
                'customer_status_breakdown' => $this->aggregateStatusBreakdown($leads, $profiles, Lead::TYPE_CUSTOMER),
                'by_lead_type' => $leads->groupBy('lead_type')->map->count(),
            ]),
            default => [
                'ok' => false,
                'error' => 'unknown_analysis',
                'allowed' => [
                    'customer_cancellation_reasons',
                    'provider_cancellation_reasons',
                    'invalid_reasons',
                    'future_customer_reasons',
                    'customer_status_breakdown',
                    'provider_status_breakdown',
                    'full_lead_overview',
                ],
            ],
        };
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @return array<string, array<string, mixed>>
     */
    private function buildProfilesForLeads(Collection $leads): array
    {
        if ($leads->isEmpty()) {
            return [];
        }

        $leadIds = $leads->pluck('id')->all();
        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id');

        $statusMeta = $this->leadOpenStatus->buildLeadStatusMeta($leads);

        $lookup = $this->buildLookupTables($histories->flatten(1), $leads);

        $profiles = [];
        foreach ($leads as $lead) {
            $leadHistories = $histories->get($lead->id, collect());
            $latestForType = $leadHistories->first(fn (LeadTypeHistory $h) => $h->type === $lead->lead_type)
                ?? $leadHistories->first();

            $profiles[(int) $lead->id] = $this->resolveProfileForLead(
                $lead,
                $latestForType,
                $leadHistories,
                $lookup,
                $statusMeta[(int) $lead->id] ?? []
            );
        }

        return $profiles;
    }

    /**
     * @param  Collection<int, LeadTypeHistory>  $histories
     * @param  Collection<int, Lead>  $leads
     * @return array<string, mixed>
     */
    private function buildLookupTables(Collection $histories, Collection $leads): array
    {
        $customerStatusIds = [];
        $providerStatusIds = [];
        $zoneIds = [];
        $categoryIds = [];
        $serviceIds = [];
        $customerCancelIds = [];
        $providerCancelIds = [];
        $invalidReasonIds = [];
        $futureReasonIds = [];
        $bookingIds = [];

        foreach ($histories as $h) {
            $d = is_array($h->data) ? $h->data : [];
            if (! empty($d['customer_lead_status_id'])) {
                $customerStatusIds[] = (int) $d['customer_lead_status_id'];
            }
            if (! empty($d['provider_lead_status_id'])) {
                $providerStatusIds[] = (int) $d['provider_lead_status_id'];
            }
            foreach (['zone_id', 'zone_ids'] as $zk) {
                if (! empty($d[$zk])) {
                    $v = $d[$zk];
                    if (is_array($v)) {
                        foreach ($v as $zid) {
                            $zoneIds[] = $zid;
                        }
                    } else {
                        $zoneIds[] = $v;
                    }
                }
            }
            foreach (['service_category', 'service_subcategory', 'provider_service_category', 'provider_service_subcategory'] as $ck) {
                if (! empty($d[$ck])) {
                    $categoryIds[] = $d[$ck];
                }
            }
            if (! empty($d['service_name'])) {
                $serviceIds[] = $d['service_name'];
            }
            if (! empty($d['cancellation_reason_id'])) {
                $customerCancelIds[] = (int) $d['cancellation_reason_id'];
            }
            if (! empty($d['provider_cancellation_reason_id'])) {
                $providerCancelIds[] = (int) $d['provider_cancellation_reason_id'];
            }
            if (! empty($d['invalid_reason_id'])) {
                $invalidReasonIds[] = (int) $d['invalid_reason_id'];
            }
            if (! empty($d['future_customer_reason_id'])) {
                $futureReasonIds[] = (int) $d['future_customer_reason_id'];
            }
            if (! empty($d['booking_id'])) {
                $bookingIds[] = $d['booking_id'];
            }
        }

        return [
            'customer_statuses' => CustomerLeadStatus::query()->whereIn('id', array_unique($customerStatusIds))->get()->keyBy('id'),
            'provider_statuses' => ProviderLeadStatus::query()->whereIn('id', array_unique($providerStatusIds))->get()->keyBy('id'),
            'zones' => $zoneIds !== [] ? Zone::withoutGlobalScopes()->whereIn('id', array_unique($zoneIds))->get()->keyBy('id') : collect(),
            'categories' => $categoryIds !== [] ? Category::withoutGlobalScopes()->whereIn('id', array_unique($categoryIds))->get()->keyBy('id') : collect(),
            'services' => $serviceIds !== [] ? Service::withoutGlobalScopes()->whereIn('id', array_unique($serviceIds))->get()->keyBy('id') : collect(),
            'customer_cancel_reasons' => $customerCancelIds !== [] ? LeadCancellationReason::query()->whereIn('id', array_unique($customerCancelIds))->get()->keyBy('id') : collect(),
            'provider_cancel_reasons' => $providerCancelIds !== [] ? ProviderCancellationReason::query()->whereIn('id', array_unique($providerCancelIds))->get()->keyBy('id') : collect(),
            'invalid_reasons' => $invalidReasonIds !== [] ? LeadInvalidReason::query()->whereIn('id', array_unique($invalidReasonIds))->get()->keyBy('id') : collect(),
            'future_reasons' => $futureReasonIds !== [] ? LeadFutureCustomerReason::query()->whereIn('id', array_unique($futureReasonIds))->get()->keyBy('id') : collect(),
            'bookings' => $bookingIds !== [] ? Booking::query()->whereIn('id', array_unique($bookingIds))->get(['id', 'readable_id', 'booking_status'])->keyBy('id') : collect(),
            'latest_booking_by_lead' => Booking::query()
                ->whereIn('lead_id', $leads->pluck('id')->all())
                ->orderByDesc('created_at')
                ->get(['id', 'lead_id', 'readable_id', 'booking_status'])
                ->groupBy('lead_id')
                ->map(fn ($g) => $g->first()),
        ];
    }

    /**
     * @param  Collection<int, LeadTypeHistory>  $allHistories
     * @param  array<string, mixed>  $lookup
     * @param  array<string, mixed>  $statusMeta
     * @return array<string, mixed>
     */
    private function resolveProfileForLead(
        Lead $lead,
        ?LeadTypeHistory $latest,
        Collection $allHistories,
        array $lookup,
        array $statusMeta,
    ): array {
        $d = ($latest && is_array($latest->data)) ? $latest->data : [];

        $profile = [
            'is_open' => (bool) ($statusMeta['is_open'] ?? false),
            'pipeline_status' => $statusMeta['label'] ?? null,
            'history_entries' => $allHistories->count(),
        ];

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $status = $lookup['customer_statuses']->get((int) ($d['customer_lead_status_id'] ?? 0));
            $cancelId = (int) ($d['cancellation_reason_id'] ?? 0);
            $cancel = $cancelId ? $lookup['customer_cancel_reasons']->get($cancelId) : null;
            $booking = ! empty($d['booking_id'])
                ? $lookup['bookings']->get($d['booking_id'])
                : $lookup['latest_booking_by_lead']->get($lead->id);

            $profile['customer'] = [
                'status' => $status?->name,
                'status_base_type' => $status?->base_type,
                'zone' => $this->resolveName($lookup['zones'], $d['zone_id'] ?? null),
                'service_category' => $this->resolveName($lookup['categories'], $d['service_category'] ?? null),
                'service_subcategory' => $this->resolveName($lookup['categories'], $d['service_subcategory'] ?? null),
                'service' => $this->resolveName($lookup['services'], $d['service_name'] ?? null),
                'service_description' => $d['service_description'] ?? null,
                'variant_key' => $d['variant_key'] ?? null,
                'estimated_service_at' => $d['estimated_service_at'] ?? null,
                'booking_status' => $d['booking_status'] ?? null,
                'booking_id' => $booking?->readable_id ?? $booking?->id,
                'system_booking_status' => $booking?->booking_status,
                'cancellation_reason' => $cancel?->name,
                'cancellation_remarks' => $d['cancellation_remarks'] ?? null,
                'is_cancelled' => strtolower((string) ($status?->base_type ?? '')) === 'cancel' || $cancelId > 0,
            ];
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $status = $lookup['provider_statuses']->get((int) ($d['provider_lead_status_id'] ?? 0));
            $cancelId = (int) ($d['provider_cancellation_reason_id'] ?? 0);
            $cancel = $cancelId ? $lookup['provider_cancel_reasons']->get($cancelId) : null;
            $zoneNames = [];
            foreach ((array) ($d['zone_ids'] ?? [$d['zone_id'] ?? null]) as $zid) {
                if ($zid && ($zn = $lookup['zones']->get($zid)?->name)) {
                    $zoneNames[] = $zn;
                }
            }

            $profile['provider'] = [
                'status' => $status?->name,
                'status_base_type' => $status?->base_type,
                'district_id' => $d['district_id'] ?? null,
                'zones' => array_values(array_unique($zoneNames)),
                'full_address' => $d['full_address'] ?? null,
                'service_areas' => $d['service_areas'] ?? null,
                'service_category' => $this->resolveName($lookup['categories'], $d['provider_service_category'] ?? null),
                'service_subcategory' => $this->resolveName($lookup['categories'], $d['provider_service_subcategory'] ?? null),
                'service_details' => $d['provider_service_details'] ?? null,
                'cancellation_reason' => $cancel?->name,
                'cancellation_remarks' => $d['provider_cancellation_remarks'] ?? null,
                'is_cancelled' => strtolower((string) ($status?->base_type ?? '')) === 'cancel' || $cancelId > 0,
            ];
        }

        if ($lead->lead_type === Lead::TYPE_INVALID) {
            $reasonId = (int) ($d['invalid_reason_id'] ?? 0);
            $profile['invalid'] = [
                'reason' => $reasonId ? ($lookup['invalid_reasons']->get($reasonId)?->name) : null,
                'remarks' => $d['invalid_remarks'] ?? null,
            ];
        }

        if ($lead->lead_type === Lead::TYPE_FUTURE_CUSTOMER) {
            $reasonId = (int) ($d['future_customer_reason_id'] ?? 0);
            $profile['future_customer'] = [
                'reason' => $reasonId ? ($lookup['future_reasons']->get($reasonId)?->name) : null,
                'remarks' => $d['future_customer_remarks'] ?? null,
            ];
        }

        return $profile;
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  array<string, array<string, mixed>>  $profiles
     * @return array<string, mixed>
     */
    private function aggregateCustomerCancellations(Collection $leads, array $profiles): array
    {
        $customerLeads = $leads->where('lead_type', Lead::TYPE_CUSTOMER);
        $byReason = [];
        $samples = [];
        $noReason = 0;
        $cancelledTotal = 0;

        foreach ($customerLeads as $lead) {
            $p = $profiles[(int) $lead->id]['customer'] ?? null;
            if (! is_array($p) || ! ($p['is_cancelled'] ?? false)) {
                continue;
            }
            $cancelledTotal++;
            $reason = trim((string) ($p['cancellation_reason'] ?? ''));
            if ($reason === '') {
                $noReason++;
                $reason = '(No reason recorded)';
            }
            $byReason[$reason] = ($byReason[$reason] ?? 0) + 1;
            if (count($samples) < 15) {
                $samples[] = [
                    'lead_id' => $lead->id,
                    'name' => $lead->name,
                    'phone' => $lead->phone_number,
                    'cancellation_reason' => $p['cancellation_reason'],
                    'cancellation_remarks' => $p['cancellation_remarks'],
                    'status' => $p['status'] ?? null,
                    'handled_by' => $lead->handled_by,
                ];
            }
        }

        arsort($byReason);

        return [
            'cancelled_customer_leads' => $cancelledTotal,
            'without_recorded_reason' => $noReason,
            'by_reason' => collect($byReason)->map(fn ($count, $reason) => [
                'reason' => $reason,
                'count' => $count,
            ])->values()->all(),
            'samples' => $samples,
        ];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  array<string, array<string, mixed>>  $profiles
     * @return array<string, mixed>
     */
    private function aggregateProviderCancellations(Collection $leads, array $profiles): array
    {
        $rows = $leads->where('lead_type', Lead::TYPE_PROVIDER);
        $byReason = [];
        $cancelledTotal = 0;

        foreach ($rows as $lead) {
            $p = $profiles[(int) $lead->id]['provider'] ?? null;
            if (! is_array($p) || ! ($p['is_cancelled'] ?? false)) {
                continue;
            }
            $cancelledTotal++;
            $reason = trim((string) ($p['cancellation_reason'] ?? '')) ?: '(No reason recorded)';
            $byReason[$reason] = ($byReason[$reason] ?? 0) + 1;
        }
        arsort($byReason);

        return [
            'cancelled_provider_leads' => $cancelledTotal,
            'by_reason' => collect($byReason)->map(fn ($count, $reason) => [
                'reason' => $reason,
                'count' => $count,
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  array<string, array<string, mixed>>  $profiles
     * @return array<string, mixed>
     */
    private function aggregateSimpleReasons(Collection $leads, array $profiles, string $key, string $type): array
    {
        $rows = $leads->where('lead_type', $type);
        $byReason = [];

        foreach ($rows as $lead) {
            $block = $profiles[(int) $lead->id][$type === Lead::TYPE_INVALID ? 'invalid' : 'future_customer'] ?? null;
            if (! is_array($block)) {
                continue;
            }
            $reason = trim((string) ($block['reason'] ?? '')) ?: '(No reason recorded)';
            $byReason[$reason] = ($byReason[$reason] ?? 0) + 1;
        }
        arsort($byReason);

        return [
            'total' => $rows->count(),
            'by_reason' => collect($byReason)->map(fn ($count, $reason) => [
                'reason' => $reason,
                'count' => $count,
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  array<string, array<string, mixed>>  $profiles
     * @return array<string, mixed>
     */
    private function aggregateStatusBreakdown(Collection $leads, array $profiles, string $type): array
    {
        $rows = $leads->where('lead_type', $type);
        $key = $type === Lead::TYPE_CUSTOMER ? 'customer' : 'provider';
        $byStatus = [];

        foreach ($rows as $lead) {
            $block = $profiles[(int) $lead->id][$key] ?? null;
            $status = is_array($block) ? ($block['status'] ?? '(No status)') : '(No status)';
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
        }
        arsort($byStatus);

        return [
            'total' => $rows->count(),
            'by_status' => collect($byStatus)->map(fn ($count, $status) => [
                'status' => $status,
                'count' => $count,
            ])->values()->all(),
        ];
    }

    private function resolveName(Collection $map, mixed $id): ?string
    {
        if ($id === null || $id === '') {
            return null;
        }

        return $map->get($id)?->name;
    }

    /**
     * @param  list<mixed>  $ids
     * @return array<string, string>
     */
    private function adminNamesById(array $ids): array
    {
        $humanIds = collect($ids)->filter()->unique()->values()->all();
        if ($humanIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $humanIds)
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $u) => [
                (string) $u->id => trim($u->first_name.' '.$u->last_name) ?: 'Staff',
            ])
            ->all();
    }
}
