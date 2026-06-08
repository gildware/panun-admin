<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;
use Modules\WhatsAppModule\Services\WhatsAppMarketingAudienceService;

class VoiceBulkAudienceService
{
    public const KIND_CUSTOMER = 'customer';

    public const KIND_PROVIDER = 'provider';

    public const KIND_LEAD = 'lead';

    public const KIND_CSV = 'csv_import';

    /** Lead types exposed in bulk audience UI (excludes provider-type CRM leads). */
    public const LEAD_KIND_TYPES = [
        Lead::TYPE_UNKNOWN,
        Lead::TYPE_INVALID,
        Lead::TYPE_FUTURE_CUSTOMER,
        Lead::TYPE_CUSTOMER,
    ];

    public function __construct(
        private readonly LeadOpenStatusService $leadOpenStatus,
        private readonly VoiceBulkCallContactBuilder $csvBuilder,
        private readonly WhatsAppMarketingAudienceService $marketingAudience
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function parseFilters(Request $request): array
    {
        $validated = $request->validate($this->filterRules());

        return $this->normalizeFilters($validated);
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeFilters(array $input): array
    {
        $kind = (string) ($input['recipient_kind'] ?? '');

        return [
            'recipient_kind' => $kind,
            'customer_category_id' => $this->nullableString($input['customer_category_id'] ?? null),
            'customer_sub_category_id' => $this->nullableString($input['customer_sub_category_id'] ?? null),
            'customer_zone_id' => $this->nullableString($input['customer_zone_id'] ?? null),
            'customer_last_booking_from' => $this->nullableDate($input['customer_last_booking_from'] ?? null),
            'customer_last_booking_to' => $this->nullableDate($input['customer_last_booking_to'] ?? null),
            'customer_booking_status' => $this->nullableString($input['customer_booking_status'] ?? null),
            'customer_registered_from' => $this->nullableDate($input['customer_registered_from'] ?? null),
            'customer_registered_to' => $this->nullableDate($input['customer_registered_to'] ?? null),
            'customer_has_booking' => $this->enumOrDefault($input['customer_has_booking'] ?? null, ['all', 'yes', 'no'], 'all'),
            'customer_active' => $this->enumOrDefault($input['customer_active'] ?? null, ['all', 'active', 'inactive'], 'active'),
            'provider_category_id' => $this->nullableString($input['provider_category_id'] ?? null),
            'provider_zone_id' => $this->nullableString($input['provider_zone_id'] ?? null),
            'provider_last_booking_from' => $this->nullableDate($input['provider_last_booking_from'] ?? null),
            'provider_last_booking_to' => $this->nullableDate($input['provider_last_booking_to'] ?? null),
            'provider_booking_category_id' => $this->nullableString($input['provider_booking_category_id'] ?? null),
            'provider_booking_status' => $this->nullableString($input['provider_booking_status'] ?? null),
            'provider_has_booking' => $this->enumOrDefault($input['provider_has_booking'] ?? null, ['all', 'yes', 'no'], 'all'),
            'lead_types' => array_values(array_intersect(
                array_map('strval', (array) ($input['lead_types'] ?? [])),
                self::LEAD_KIND_TYPES
            )),
            'lead_open_status' => $this->enumOrDefault($input['lead_open_status'] ?? null, ['all', 'open', 'closed'], 'all'),
            'lead_source_ids' => $this->idList($input['lead_source_ids'] ?? []),
            'lead_ad_source_ids' => $this->idList($input['lead_ad_source_ids'] ?? []),
            'lead_handled_by' => array_values(array_filter(array_map('strval', (array) ($input['lead_handled_by'] ?? [])))),
            'lead_received_from' => $this->nullableDate($input['lead_received_from'] ?? null),
            'lead_received_to' => $this->nullableDate($input['lead_received_to'] ?? null),
            'customer_lead_status_ids' => $this->idList($input['customer_lead_status_ids'] ?? []),
            'customer_lead_zone_ids' => $this->idList($input['customer_lead_zone_ids'] ?? []),
            'customer_lead_category_ids' => $this->idList($input['customer_lead_category_ids'] ?? []),
            'customer_lead_sub_category_ids' => $this->idList($input['customer_lead_sub_category_ids'] ?? []),
            'estimated_service_from' => $this->nullableDate($input['estimated_service_from'] ?? null),
            'estimated_service_to' => $this->nullableDate($input['estimated_service_to'] ?? null),
            'invalid_reason_ids' => $this->idList($input['invalid_reason_ids'] ?? []),
            'future_customer_reason_ids' => $this->idList($input['future_customer_reason_ids'] ?? []),
            'customer_lead_tag_ids' => $this->idList($input['customer_lead_tag_ids'] ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filterRules(): array
    {
        $leadTypes = implode(',', self::LEAD_KIND_TYPES);
        $bookingStatuses = implode(',', ['pending', 'accepted', 'ongoing', 'on_hold', 'completed', 'canceled', 'refunded']);

        return [
            'recipient_kind' => 'required|in:' . implode(',', [self::KIND_CUSTOMER, self::KIND_PROVIDER, self::KIND_LEAD, self::KIND_CSV]),
            'customer_category_id' => 'nullable|string|max:64|exists:categories,id',
            'customer_sub_category_id' => 'nullable|string|max:64|exists:categories,id',
            'customer_zone_id' => 'nullable|string|max:64|exists:zones,id',
            'customer_last_booking_from' => 'nullable|date',
            'customer_last_booking_to' => 'nullable|date|after_or_equal:customer_last_booking_from',
            'customer_booking_status' => 'nullable|string|in:' . $bookingStatuses,
            'customer_registered_from' => 'nullable|date',
            'customer_registered_to' => 'nullable|date|after_or_equal:customer_registered_from',
            'customer_has_booking' => 'nullable|in:all,yes,no',
            'customer_active' => 'nullable|in:all,active,inactive',
            'provider_category_id' => 'nullable|string|max:64|exists:categories,id',
            'provider_zone_id' => 'nullable|string|max:64|exists:zones,id',
            'provider_last_booking_from' => 'nullable|date',
            'provider_last_booking_to' => 'nullable|date|after_or_equal:provider_last_booking_from',
            'provider_booking_category_id' => 'nullable|string|max:64|exists:categories,id',
            'provider_booking_status' => 'nullable|string|in:' . $bookingStatuses,
            'provider_has_booking' => 'nullable|in:all,yes,no',
            'lead_types' => 'nullable|array',
            'lead_types.*' => 'string|in:' . $leadTypes,
            'lead_open_status' => 'nullable|in:all,open,closed',
            'lead_source_ids' => 'nullable|array',
            'lead_source_ids.*' => 'integer|exists:sources,id',
            'lead_ad_source_ids' => 'nullable|array',
            'lead_ad_source_ids.*' => 'integer|exists:ad_sources,id',
            'lead_handled_by' => 'nullable|array',
            'lead_handled_by.*' => 'string|max:64',
            'lead_received_from' => 'nullable|date',
            'lead_received_to' => 'nullable|date|after_or_equal:lead_received_from',
            'customer_lead_status_ids' => 'nullable|array',
            'customer_lead_status_ids.*' => 'integer|exists:customer_lead_statuses,id',
            'customer_lead_zone_ids' => 'nullable|array',
            'customer_lead_zone_ids.*' => 'string|max:64|exists:zones,id',
            'customer_lead_category_ids' => 'nullable|array',
            'customer_lead_category_ids.*' => 'string|max:64|exists:categories,id',
            'customer_lead_sub_category_ids' => 'nullable|array',
            'customer_lead_sub_category_ids.*' => 'string|max:64|exists:categories,id',
            'estimated_service_from' => 'nullable|date',
            'estimated_service_to' => 'nullable|date|after_or_equal:estimated_service_from',
            'invalid_reason_ids' => 'nullable|array',
            'invalid_reason_ids.*' => 'integer|exists:lead_invalid_reasons,id',
            'future_customer_reason_ids' => 'nullable|array',
            'future_customer_reason_ids.*' => 'integer|exists:lead_future_customer_reasons,id',
            'customer_lead_tag_ids' => 'nullable|array',
            'customer_lead_tag_ids.*' => 'integer|exists:customer_lead_tags,id',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{name: string, phone: string, category_name: string, context?: array<string, string>}>
     */
    public function resolve(array $filters, ?string $csvDiskPath = null): array
    {
        $kind = (string) ($filters['recipient_kind'] ?? '');

        $rows = match ($kind) {
            self::KIND_CUSTOMER => $this->resolveCustomers($filters),
            self::KIND_PROVIDER => $this->resolveProviders($filters),
            self::KIND_LEAD => $this->resolveLeads($filters),
            self::KIND_CSV => $csvDiskPath
                ? $this->marketingAudience->resolve(self::KIND_CSV, null, $csvDiskPath)
                : [],
            default => [],
        };

        return $this->uniqueRecipients($rows);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total_matching: int, rows: array<int, array<string, mixed>>, preview_limit: int, has_more: bool, kind: string}
     */
    public function preview(array $filters, int $previewLimit = 50): array
    {
        $kind = (string) ($filters['recipient_kind'] ?? '');
        $recipients = $this->resolve($filters);
        $total = count($recipients);
        $slice = array_slice($recipients, 0, $previewLimit);
        $rows = [];

        foreach ($slice as $recipient) {
            $rows[] = [
                'name' => $recipient['name'],
                'phone_normalized' => $recipient['phone'],
                'category_name' => $recipient['category_name'] ?? '',
                'lead_type' => $recipient['context']['lead_type'] ?? '',
            ];
        }

        return [
            'total_matching' => $total,
            'rows' => $rows,
            'preview_limit' => $previewLimit,
            'has_more' => $total > $previewLimit,
            'kind' => $kind,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total_matching: int, rows: array<int, array<string, mixed>>, preview_limit: int, has_more: bool, kind: string}
     */
    public function previewCsv(string $csvDiskPath, int $previewLimit = 50): array
    {
        $recipients = $this->resolve(['recipient_kind' => self::KIND_CSV], $csvDiskPath);
        $total = count($recipients);
        $slice = array_slice($recipients, 0, $previewLimit);
        $rows = [];

        foreach ($slice as $recipient) {
            $rows[] = [
                'name' => $recipient['name'],
                'phone_normalized' => $recipient['phone'],
                'category_name' => $recipient['category_name'] ?? '',
            ];
        }

        return [
            'total_matching' => $total,
            'rows' => $rows,
            'preview_limit' => $previewLimit,
            'has_more' => $total > $previewLimit,
            'kind' => self::KIND_CSV,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{name: string, phone: string, category_name: string, context?: array<string, string>}>
     */
    private function resolveCustomers(array $filters): array
    {
        $query = User::query()
            ->inCustomerDirectory()
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        $active = (string) ($filters['customer_active'] ?? 'active');
        if ($active === 'active') {
            $query->where('is_active', 1);
        } elseif ($active === 'inactive') {
            $query->where('is_active', 0);
        }

        if (!empty($filters['customer_registered_from']) && !empty($filters['customer_registered_to'])) {
            $query->whereBetween('created_at', [
                $filters['customer_registered_from'] . ' 00:00:00',
                $filters['customer_registered_to'] . ' 23:59:59',
            ]);
        }

        $hasBooking = (string) ($filters['customer_has_booking'] ?? 'all');
        if ($hasBooking === 'yes') {
            $query->whereHas('bookings');
        } elseif ($hasBooking === 'no') {
            $query->whereDoesntHave('bookings');
        }

        if (!empty($filters['customer_category_id'])) {
            $categoryId = $filters['customer_category_id'];
            $query->whereHas('bookings', fn (Builder $q) => $q->where('category_id', $categoryId));
        }

        if (!empty($filters['customer_sub_category_id'])) {
            $subCategoryId = $filters['customer_sub_category_id'];
            $query->whereHas('bookings', fn (Builder $q) => $q->where('sub_category_id', $subCategoryId));
        }

        if (!empty($filters['customer_zone_id'])) {
            $zoneId = $filters['customer_zone_id'];
            $query->whereHas('bookings', fn (Builder $q) => $q->where('zone_id', $zoneId));
        }

        if (!empty($filters['customer_last_booking_from']) && !empty($filters['customer_last_booking_to'])) {
            $this->applyLatestBookingDateFilter(
                $query,
                'customer_id',
                'users.id',
                $filters['customer_last_booking_from'],
                $filters['customer_last_booking_to'],
                $filters['customer_booking_status'] ?? null,
                $filters['customer_category_id'] ?? null
            );
        }

        return $query->orderBy('id')->get(['id', 'first_name', 'last_name', 'phone'])->map(function (User $user) {
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            return [
                'name' => $name !== '' ? $name : 'Customer',
                'phone' => (string) $user->phone,
                'category_name' => '',
            ];
        })->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{name: string, phone: string, category_name: string, context?: array<string, string>}>
     */
    private function resolveProviders(array $filters): array
    {
        $query = Provider::query()
            ->ofApproval(1)
            ->ofStatus(1)
            ->whereHas('user', function (Builder $q) {
                $q->whereNotNull('phone')->where('phone', '!=', '');
            })
            ->with(['user:id,first_name,last_name,phone']);

        if (!empty($filters['provider_category_id'])) {
            $categoryId = $filters['provider_category_id'];
            $query->whereHas('subscribed_services', function (Builder $q) use ($categoryId) {
                $q->where('category_id', $categoryId)->where('is_subscribed', 1);
            });
        }

        if (!empty($filters['provider_zone_id'])) {
            $query->where('zone_id', $filters['provider_zone_id']);
        }

        $hasBooking = (string) ($filters['provider_has_booking'] ?? 'all');
        if ($hasBooking === 'yes') {
            $query->whereHas('bookings');
        } elseif ($hasBooking === 'no') {
            $query->whereDoesntHave('bookings');
        }

        if (!empty($filters['provider_last_booking_from']) && !empty($filters['provider_last_booking_to'])) {
            $this->applyLatestBookingDateFilter(
                $query,
                'provider_id',
                'providers.id',
                $filters['provider_last_booking_from'],
                $filters['provider_last_booking_to'],
                $filters['provider_booking_status'] ?? null,
                $filters['provider_booking_category_id'] ?? null
            );
        }

        $categoryName = '';
        if (!empty($filters['provider_category_id'])) {
            $categoryName = (string) (Category::query()->where('id', $filters['provider_category_id'])->value('name') ?? '');
        }

        $list = [];
        foreach ($query->orderBy('id')->get(['id', 'company_name', 'user_id']) as $provider) {
            $user = $provider->user;
            if (!$user || trim((string) $user->phone) === '') {
                continue;
            }
            $name = trim((string) $provider->company_name);
            if ($name === '') {
                $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            }

            $list[] = [
                'name' => $name !== '' ? $name : 'Provider',
                'phone' => (string) $user->phone,
                'category_name' => $categoryName,
            ];
        }

        return $list;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{name: string, phone: string, category_name: string, context?: array<string, string>}>
     */
    private function resolveLeads(array $filters): array
    {
        $leadTypes = (array) ($filters['lead_types'] ?? []);
        if ($leadTypes === []) {
            $leadTypes = self::LEAD_KIND_TYPES;
        }

        $query = Lead::query()
            ->whereIn('lead_type', $leadTypes)
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '');

        if ($filters['lead_source_ids'] ?? []) {
            $query->whereIn('source_id', $filters['lead_source_ids']);
        }
        if ($filters['lead_ad_source_ids'] ?? []) {
            $query->whereIn('ad_source_id', $filters['lead_ad_source_ids']);
        }

        $handledBy = (array) ($filters['lead_handled_by'] ?? []);
        if ($handledBy !== []) {
            $unassigned = in_array(Lead::FILTER_UNASSIGNED_VALUE, $handledBy, true);
            $employeeIds = array_values(array_filter($handledBy, fn (string $v) => $v !== Lead::FILTER_UNASSIGNED_VALUE));
            $query->where(function (Builder $q) use ($unassigned, $employeeIds) {
                if ($unassigned && $employeeIds === []) {
                    $q->whereNull('handled_by')->orWhere('handled_by', '')->orWhere('handled_by', Lead::HANDLED_BY_AI);
                } elseif (!$unassigned && $employeeIds !== []) {
                    $q->whereIn('handled_by', $employeeIds);
                } elseif ($unassigned && $employeeIds !== []) {
                    $q->where(function (Builder $sub) {
                        $sub->whereNull('handled_by')->orWhere('handled_by', '')->orWhere('handled_by', Lead::HANDLED_BY_AI);
                    })->orWhereIn('handled_by', $employeeIds);
                }
            });
        }

        if (!empty($filters['lead_received_from']) && !empty($filters['lead_received_to'])) {
            $query->whereBetween('date_time_of_lead_received', [
                $filters['lead_received_from'] . ' 00:00:00',
                $filters['lead_received_to'] . ' 23:59:59',
            ]);
        }

        if (in_array(Lead::TYPE_CUSTOMER, $leadTypes, true)) {
            $this->applyCustomerLeadHistoryFilters($query, $filters);
        }

        if (in_array(Lead::TYPE_INVALID, $leadTypes, true) && ($filters['invalid_reason_ids'] ?? []) !== []) {
            $this->applyReasonHistoryFilter($query, Lead::TYPE_INVALID, 'invalid_reason_id', $filters['invalid_reason_ids']);
        }

        if (in_array(Lead::TYPE_FUTURE_CUSTOMER, $leadTypes, true) && ($filters['future_customer_reason_ids'] ?? []) !== []) {
            $this->applyReasonHistoryFilter($query, Lead::TYPE_FUTURE_CUSTOMER, 'future_customer_reason_id', $filters['future_customer_reason_ids']);
        }

        if (($filters['customer_lead_tag_ids'] ?? []) !== []) {
            $tagIds = $filters['customer_lead_tag_ids'];
            $query->whereHas('customerLeadTags', fn (Builder $q) => $q->whereIn('customer_lead_tags.id', $tagIds));
        }

        $openStatus = (string) ($filters['lead_open_status'] ?? 'all');
        if ($openStatus !== 'all') {
            $candidateLeads = (clone $query)->get(['id', 'lead_type']);
            $meta = $this->leadOpenStatus->buildLeadStatusMeta($candidateLeads);
            $targetOpen = $openStatus === 'open';
            $matchingIds = $candidateLeads
                ->filter(fn (Lead $lead) => (bool) ($meta[$lead->id]['is_open'] ?? false) === $targetOpen)
                ->pluck('id')
                ->all();
            if ($matchingIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $matchingIds);
            }
        }

        return $query->orderByDesc('id')->get(['id', 'name', 'phone_number', 'lead_type'])->map(function (Lead $lead) {
            return [
                'name' => trim((string) $lead->name) !== '' ? trim((string) $lead->name) : 'Lead',
                'phone' => (string) $lead->phone_number,
                'category_name' => Lead::leadTypes()[$lead->lead_type] ?? $lead->lead_type,
                'context' => [
                    'lead_type' => (string) $lead->lead_type,
                    'customer_name' => trim((string) $lead->name),
                ],
            ];
        })->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyCustomerLeadHistoryFilters(Builder $query, array $filters): void
    {
        $hasFilters = ($filters['customer_lead_status_ids'] ?? []) !== []
            || ($filters['customer_lead_zone_ids'] ?? []) !== []
            || ($filters['customer_lead_category_ids'] ?? []) !== []
            || ($filters['customer_lead_sub_category_ids'] ?? []) !== []
            || (!empty($filters['estimated_service_from']) && !empty($filters['estimated_service_to']));

        if (!$hasFilters) {
            return;
        }

        $customerLeadIds = Lead::query()->where('lead_type', Lead::TYPE_CUSTOMER)->pluck('id')->all();
        if ($customerLeadIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $customerLeadIds)
            ->where('type', Lead::TYPE_CUSTOMER)
            ->orderByDesc('created_at')
            ->get();

        $latestByLead = $histories->groupBy('lead_id')->map(fn ($group) => $group->first());
        $matchingLeadIds = $latestByLead->filter(function (LeadTypeHistory $history) use ($filters) {
            $data = is_array($history->data) ? $history->data : [];

            if (($filters['customer_lead_status_ids'] ?? []) !== []
                && !in_array($data['customer_lead_status_id'] ?? null, $filters['customer_lead_status_ids'], false)) {
                return false;
            }
            if (($filters['customer_lead_zone_ids'] ?? []) !== []
                && !in_array($data['zone_id'] ?? null, $filters['customer_lead_zone_ids'], false)) {
                return false;
            }
            if (($filters['customer_lead_category_ids'] ?? []) !== []
                && !in_array($data['service_category'] ?? null, $filters['customer_lead_category_ids'], false)) {
                return false;
            }
            if (($filters['customer_lead_sub_category_ids'] ?? []) !== []
                && !in_array($data['service_subcategory'] ?? null, $filters['customer_lead_sub_category_ids'], false)) {
                return false;
            }
            if (!empty($filters['estimated_service_from']) && !empty($filters['estimated_service_to'])) {
                $estAt = $data['estimated_service_at'] ?? null;
                if (!$estAt) {
                    return false;
                }
                try {
                    $est = \Carbon\Carbon::parse($estAt);
                    $from = \Carbon\Carbon::parse($filters['estimated_service_from'])->startOfDay();
                    $to = \Carbon\Carbon::parse($filters['estimated_service_to'])->endOfDay();
                    if ($est->lt($from) || $est->gt($to)) {
                        return false;
                    }
                } catch (\Throwable) {
                    return false;
                }
            }

            return true;
        })->keys()->all();

        if ($matchingLeadIds === []) {
            $query->whereRaw('1 = 0');
        } else {
            $query->where(function (Builder $q) use ($matchingLeadIds) {
                $q->where('lead_type', '!=', Lead::TYPE_CUSTOMER)
                    ->orWhereIn('id', $matchingLeadIds);
            });
        }
    }

    /**
     * @param  array<int, int|string>  $reasonIds
     */
    private function applyReasonHistoryFilter(Builder $query, string $type, string $reasonKey, array $reasonIds): void
    {
        $leadIds = Lead::query()->where('lead_type', $type)->pluck('id')->all();
        if ($leadIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->where('type', $type)
            ->orderByDesc('created_at')
            ->get();

        $latestByLead = $histories->groupBy('lead_id')->map(fn ($group) => $group->first());
        $matchingLeadIds = $latestByLead->filter(function (LeadTypeHistory $history) use ($reasonKey, $reasonIds) {
            $data = is_array($history->data) ? $history->data : [];

            return in_array($data[$reasonKey] ?? null, $reasonIds, false);
        })->keys()->all();

        if ($matchingLeadIds === []) {
            $query->whereRaw('1 = 0');
        } else {
            $query->where(function (Builder $q) use ($type, $matchingLeadIds) {
                $q->where('lead_type', '!=', $type)
                    ->orWhereIn('id', $matchingLeadIds);
            });
        }
    }

    private function applyLatestBookingDateFilter(
        Builder $parentQuery,
        string $ownerColumn,
        string $parentKeySql,
        string $from,
        string $to,
        ?string $bookingStatus = null,
        ?string $categoryId = null
    ): void {
        $parentQuery->whereIn($parentKeySql, function ($sub) use ($ownerColumn, $from, $to, $bookingStatus, $categoryId) {
            $sub->select($ownerColumn)
                ->from('bookings as b1')
                ->whereNotNull($ownerColumn)
                ->whereRaw("b1.created_at = (SELECT MAX(b2.created_at) FROM bookings b2 WHERE b2.{$ownerColumn} = b1.{$ownerColumn})")
                ->whereBetween('b1.created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);

            if ($bookingStatus) {
                $sub->where('b1.booking_status', $bookingStatus);
            }
            if ($categoryId) {
                $sub->where('b1.category_id', $categoryId);
            }
        });
    }

    /**
     * @param  array<int, array{name: string, phone: string, category_name?: string, context?: array<string, string>}>  $rows
     * @return array<int, array{name: string, phone: string, category_name: string, context?: array<string, string>}>
     */
    private function uniqueRecipients(array $rows): array
    {
        $cloud = app(WhatsAppCloudService::class);
        $seen = [];
        $out = [];

        foreach ($rows as $row) {
            $norm = $cloud->normalizeRecipientPhone((string) ($row['phone'] ?? ''));
            if ($norm === null || isset($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;
            $row['phone'] = $norm;
            $row['category_name'] = (string) ($row['category_name'] ?? '');
            $out[] = $row;
        }

        return array_values($out);
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : null;
    }

    private function nullableDate(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : null;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, int>
     */
    private function idList(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($v) => is_numeric($v) ? (int) $v : 0,
            $values
        ), static fn (int $id) => $id > 0)));
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function enumOrDefault(mixed $value, array $allowed, string $default): string
    {
        $text = (string) ($value ?? '');

        return in_array($text, $allowed, true) ? $text : $default;
    }
}
