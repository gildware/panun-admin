<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\LeadManagement\Entities\LeadOutboundEnquiryStatus;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\Source;
use Modules\LeadManagement\Services\CustomerLeadReportAnalyticsService;
use Modules\LeadManagement\Services\ProviderLeadReportAnalyticsService;
use Modules\UserManagement\Entities\User;

class EmployeeLeadProgressAnalyticsService
{
    public function __construct(
        private readonly CustomerLeadReportAnalyticsService $customerLeadAnalytics,
        private readonly ProviderLeadReportAnalyticsService $providerLeadAnalytics,
    ) {}

    /**
     * @param  Collection<int, User>  $employees
     * @return array<string, mixed>
     */
    public function build(Collection $employees, Carbon $periodStart, Carbon $periodEnd): array
    {
        $employeeIds = $employees->pluck('id')->map(fn ($id) => (string) $id)->filter()->values()->all();

        if ($employeeIds === []) {
            return $this->emptyPayload();
        }

        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();
        $baseQuery = $this->leadBaseQuery($employeeIds, $rangeStart, $rangeEnd);

        $totalHandled = (clone $baseQuery)->count();
        $typeBreakdown = $this->buildTypeBreakdown($baseQuery, $totalHandled);
        $customer = $this->buildCustomerSection($baseQuery);
        $provider = $this->buildProviderSection($baseQuery);
        $futureCustomerReasons = $this->buildFutureCustomerReasons($baseQuery);
        $invalidReasons = $this->buildInvalidReasons($baseQuery);
        $outbound = $this->buildOutboundSection($employeeIds, $rangeStart, $rangeEnd);
        $sources = $this->buildSourceSection($baseQuery, $totalHandled);

        $typeOnlyRows = collect($typeBreakdown)->filter(fn (array $row) => ($row['key'] ?? '') !== 'handled')->values();

        return [
            'total_handled' => $totalHandled,
            'type_breakdown' => $typeBreakdown,
            'customer' => $customer,
            'provider' => $provider,
            'future_customer_reasons' => $futureCustomerReasons,
            'invalid_reasons' => $invalidReasons,
            'outbound' => $outbound,
            'sources' => $sources,
            'charts' => [
                'lead_type_labels' => $typeOnlyRows->pluck('label')->all(),
                'lead_type_series' => $typeOnlyRows->pluck('count')->all(),
                'source_labels' => collect($sources['rows'] ?? [])->pluck('source')->all(),
                'source_series' => collect($sources['rows'] ?? [])->pluck('total')->all(),
                'customer_outcome_labels' => [
                    translate('Bookings_completed'),
                    translate('Pending'),
                    translate('Cancelled'),
                ],
                'customer_outcome_series' => [
                    (int) ($customer['booked'] ?? 0),
                    (int) ($customer['pending'] ?? 0),
                    (int) ($customer['cancelled'] ?? 0),
                ],
                'provider_outcome_labels' => [
                    translate('Progress_provider_registered') ?? translate('completed'),
                    translate('Pending'),
                    translate('Cancelled'),
                ],
                'provider_outcome_series' => [
                    (int) ($provider['registered'] ?? 0),
                    (int) ($provider['pending'] ?? 0),
                    (int) ($provider['cancelled'] ?? 0),
                ],
            ],
        ];
    }

    /**
     * @param  list<string>  $employeeIds
     */
    private function leadBaseQuery(array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): Builder
    {
        return Lead::query()
            ->whereIn('handled_by', $employeeIds)
            ->whereBetween('date_time_of_lead_received', [$rangeStart, $rangeEnd])
            ->whereNotNull('handled_by')
            ->where('handled_by', '!=', Lead::HANDLED_BY_AI);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildTypeBreakdown(Builder $baseQuery, int $totalHandled): array
    {
        $counts = (clone $baseQuery)
            ->select('lead_type', DB::raw('count(*) as total'))
            ->groupBy('lead_type')
            ->pluck('total', 'lead_type')
            ->all();

        $pct = fn (int $count): float => $totalHandled > 0 ? round(($count / $totalHandled) * 100, 1) : 0.0;

        $definitions = [
            Lead::TYPE_CUSTOMER => ['label' => translate('Customer'), 'tone' => 'good', 'icon' => 'person'],
            Lead::TYPE_PROVIDER => ['label' => translate('Provider'), 'tone' => 'brand', 'icon' => 'engineering'],
            Lead::TYPE_UNKNOWN => ['label' => translate('Unknown'), 'tone' => 'warning', 'icon' => 'help'],
            Lead::TYPE_INVALID => ['label' => translate('Invalid'), 'tone' => 'danger', 'icon' => 'block'],
            Lead::TYPE_FUTURE_CUSTOMER => ['label' => translate('Future_Customer') ?? 'Future Customer', 'tone' => 'warning', 'icon' => 'schedule'],
        ];

        $rows = [[
            'key' => 'handled',
            'label' => translate('Progress_leads_handled') ?? translate('Leads_added'),
            'sublabel' => translate('Progress_leads_handled_sub') ?? translate('Total'),
            'count' => $totalHandled,
            'total' => $totalHandled,
            'pct' => $totalHandled > 0 ? 100.0 : 0.0,
            'tone' => 'brand',
            'icon' => 'contact_page',
        ]];

        foreach ($definitions as $type => $meta) {
            $count = (int) ($counts[$type] ?? 0);

            $rows[] = [
                'key' => $type,
                'label' => $meta['label'],
                'sublabel' => translate('Progress_of_handled_leads') ?? translate('Share'),
                'count' => $count,
                'total' => $totalHandled,
                'pct' => $pct($count),
                'tone' => $meta['tone'],
                'icon' => $meta['icon'],
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCustomerSection(Builder $baseQuery): array
    {
        $payload = $this->customerLeadAnalytics->build(clone $baseQuery, null, null);
        $summary = $payload['summary'] ?? [];
        $total = (int) ($summary['total'] ?? 0);
        $cancelReasons = $this->withPercentages($payload['cancelled']['reasons'] ?? [], max(1, (int) ($summary['cancelled'] ?? 0)));

        $outcomeItems = [
            ['key' => 'booked', 'label' => translate('Bookings_completed'), 'count' => (int) ($summary['booked'] ?? 0), 'tone' => 'good', 'icon' => 'check_circle'],
            ['key' => 'pending', 'label' => translate('Pending'), 'count' => (int) ($summary['pending'] ?? 0), 'tone' => 'warning', 'icon' => 'hourglass_top'],
            ['key' => 'cancelled', 'label' => translate('Cancelled'), 'count' => (int) ($summary['cancelled'] ?? 0), 'tone' => 'danger', 'icon' => 'cancel'],
        ];

        return [
            'total' => $total,
            'booked' => (int) ($summary['booked'] ?? 0),
            'pending' => (int) ($summary['pending'] ?? 0),
            'hold' => (int) ($summary['hold'] ?? 0),
            'cancelled' => (int) ($summary['cancelled'] ?? 0),
            'conversion_rate' => (float) ($summary['conversion_rate'] ?? 0),
            'cancel_rate' => (float) ($summary['cancel_rate'] ?? 0),
            'cancel_reasons' => $cancelReasons,
            'outcome_rows' => $this->outcomeRows($outcomeItems, max(1, $total)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProviderSection(Builder $baseQuery): array
    {
        $payload = $this->providerLeadAnalytics->build(clone $baseQuery, null, null);
        $summary = $payload['summary'] ?? [];
        $total = (int) ($summary['total'] ?? 0);
        $cancelReasons = $this->withPercentages($payload['cancelled']['reasons'] ?? [], max(1, (int) ($summary['cancelled'] ?? 0)));

        $outcomeItems = [
            ['key' => 'registered', 'label' => translate('Progress_provider_registered') ?? translate('completed'), 'count' => (int) ($summary['completed'] ?? 0), 'tone' => 'good', 'icon' => 'how_to_reg'],
            ['key' => 'pending', 'label' => translate('Pending'), 'count' => (int) ($summary['pending'] ?? 0), 'tone' => 'warning', 'icon' => 'hourglass_top'],
            ['key' => 'cancelled', 'label' => translate('Cancelled'), 'count' => (int) ($summary['cancelled'] ?? 0), 'tone' => 'danger', 'icon' => 'cancel'],
        ];

        return [
            'total' => $total,
            'registered' => (int) ($summary['completed'] ?? 0),
            'pending' => (int) ($summary['pending'] ?? 0),
            'cancelled' => (int) ($summary['cancelled'] ?? 0),
            'completion_rate' => (float) ($summary['completion_rate'] ?? 0),
            'cancel_rate' => (float) ($summary['cancel_rate'] ?? 0),
            'cancel_reasons' => $cancelReasons,
            'outcome_rows' => $this->outcomeRows($outcomeItems, max(1, $total)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildInvalidReasons(Builder $baseQuery): array
    {
        $leadIds = (clone $baseQuery)
            ->where('lead_type', Lead::TYPE_INVALID)
            ->pluck('id')
            ->all();

        if ($leadIds === []) {
            return [];
        }

        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->where('type', Lead::TYPE_INVALID)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        $reasonIds = [];
        foreach ($histories as $history) {
            $data = is_array($history->data) ? $history->data : [];
            $id = $data['invalid_reason_id'] ?? null;
            if ($id !== null && $id !== '') {
                $reasonIds[] = (string) $id;
            }
        }

        $reasons = $reasonIds !== []
            ? LeadInvalidReason::query()->whereIn('id', array_unique($reasonIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();

        $counts = [];
        foreach ($histories as $history) {
            $data = is_array($history->data) ? $history->data : [];
            $reasonId = isset($data['invalid_reason_id']) ? (string) $data['invalid_reason_id'] : '';
            $label = $reasonId !== '' && $reasons->has($reasonId)
                ? (string) $reasons->get($reasonId)->name
                : translate('Not_Specified');
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        arsort($counts);
        $total = array_sum($counts);

        return collect($counts)->map(function (int $count, string $label) use ($total) {
            return [
                'label' => $label,
                'count' => $count,
                'total' => $total,
                'pct' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        })->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildFutureCustomerReasons(Builder $baseQuery): array
    {
        $leadIds = (clone $baseQuery)
            ->where('lead_type', Lead::TYPE_FUTURE_CUSTOMER)
            ->pluck('id')
            ->all();

        if ($leadIds === []) {
            return [];
        }

        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->where('type', Lead::TYPE_FUTURE_CUSTOMER)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        $reasonIds = [];
        foreach ($histories as $history) {
            $data = is_array($history->data) ? $history->data : [];
            $id = $data['future_customer_reason_id'] ?? null;
            if ($id !== null && $id !== '') {
                $reasonIds[] = (string) $id;
            }
        }

        $reasons = $reasonIds !== []
            ? LeadFutureCustomerReason::query()->whereIn('id', array_unique($reasonIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();

        $counts = [];
        foreach ($histories as $history) {
            $data = is_array($history->data) ? $history->data : [];
            $reasonId = isset($data['future_customer_reason_id']) ? (string) $data['future_customer_reason_id'] : '';
            $label = $reasonId !== '' && $reasons->has($reasonId)
                ? (string) $reasons->get($reasonId)->name
                : translate('Not_Specified');
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        arsort($counts);
        $total = array_sum($counts);

        return collect($counts)->map(function (int $count, string $label) use ($total) {
            return [
                'label' => $label,
                'count' => $count,
                'total' => $total,
                'pct' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        })->values()->all();
    }

    /**
     * @param  list<string>  $employeeIds
     * @return array<string, mixed>
     */
    private function buildOutboundSection(array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $query = LeadOutboundEnquiry::query()
            ->with(['lead:id,lead_type', 'statusConfig:id,name,link_type'])
            ->whereIn('handled_by', $employeeIds)
            ->whereBetween('contacted_at', [$rangeStart, $rangeEnd]);

        $enquiries = $query->get();
        $total = $enquiries->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'converted_to_customer' => 0,
                'converted_to_booking' => 0,
                'still_fc' => 0,
                'open' => 0,
                'conversion_rate' => 0.0,
                'by_status' => [],
                'by_channel' => [
                    ['label' => translate('Call'), 'count' => 0, 'total' => 0, 'pct' => 0.0],
                    ['label' => translate('Message'), 'count' => 0, 'total' => 0, 'pct' => 0.0],
                ],
                'summary_rows' => $this->outcomeRows([
                    ['key' => 'total', 'label' => translate('Outbound_Enquiries'), 'count' => 0, 'tone' => 'brand', 'icon' => 'call_made'],
                    ['key' => 'converted_customer', 'label' => translate('Progress_outbound_to_customer') ?? translate('Customer'), 'count' => 0, 'tone' => 'good', 'icon' => 'person_add'],
                    ['key' => 'converted_booking', 'label' => translate('Progress_outbound_to_booking') ?? translate('Bookings_completed'), 'count' => 0, 'tone' => 'good', 'icon' => 'event_available'],
                    ['key' => 'still_fc', 'label' => translate('Future_Customer') ?? 'Future Customer', 'count' => 0, 'tone' => 'warning', 'icon' => 'schedule'],
                    ['key' => 'open', 'label' => translate('Pending'), 'count' => 0, 'tone' => 'warning', 'icon' => 'pending_actions'],
                ], 1),
            ];
        }

        $convertedCustomer = 0;
        $convertedBooking = 0;
        $stillFc = 0;
        $open = 0;
        $statusCounts = [];
        $channelCounts = ['call' => 0, 'message' => 0];

        foreach ($enquiries as $enquiry) {
            $channel = (string) ($enquiry->contacted_through ?? '');
            if (isset($channelCounts[$channel])) {
                $channelCounts[$channel]++;
            }

            $statusLabel = $enquiry->statusConfig?->name ?? ($enquiry->status ?: translate('Not_Specified'));
            $statusCounts[$statusLabel] = ($statusCounts[$statusLabel] ?? 0) + 1;

            if ($enquiry->related_lead_id) {
                $convertedCustomer++;
            } elseif ($enquiry->booking_id) {
                $convertedBooking++;
            } elseif ($enquiry->lead?->lead_type === Lead::TYPE_FUTURE_CUSTOMER) {
                $stillFc++;
            } else {
                $open++;
            }
        }

        $convertedTotal = $convertedCustomer + $convertedBooking;
        $conversionRate = $total > 0 ? round(($convertedTotal / $total) * 100, 1) : 0.0;

        $summaryRows = $this->outcomeRows([
            ['key' => 'total', 'label' => translate('Outbound_Enquiries'), 'count' => $total, 'tone' => 'brand', 'icon' => 'call_made'],
            ['key' => 'converted_customer', 'label' => translate('Progress_outbound_to_customer') ?? translate('Customer'), 'count' => $convertedCustomer, 'tone' => 'good', 'icon' => 'person_add'],
            ['key' => 'converted_booking', 'label' => translate('Progress_outbound_to_booking') ?? translate('Bookings_completed'), 'count' => $convertedBooking, 'tone' => 'good', 'icon' => 'event_available'],
            ['key' => 'still_fc', 'label' => translate('Future_Customer') ?? 'Future Customer', 'count' => $stillFc, 'tone' => 'warning', 'icon' => 'schedule'],
            ['key' => 'open', 'label' => translate('Pending'), 'count' => $open, 'tone' => 'warning', 'icon' => 'pending_actions'],
        ], $total);

        arsort($statusCounts);

        return [
            'total' => $total,
            'converted_to_customer' => $convertedCustomer,
            'converted_to_booking' => $convertedBooking,
            'still_fc' => $stillFc,
            'open' => $open,
            'conversion_rate' => $conversionRate,
            'by_status' => collect($statusCounts)->map(fn ($count, $label) => [
                'label' => $label,
                'count' => $count,
                'total' => $total,
                'pct' => round(($count / $total) * 100, 1),
            ])->values()->all(),
            'by_channel' => [
                ['label' => translate('Call'), 'count' => $channelCounts['call'], 'total' => $total, 'pct' => $total > 0 ? round(($channelCounts['call'] / $total) * 100, 1) : 0.0],
                ['label' => translate('Message'), 'count' => $channelCounts['message'], 'total' => $total, 'pct' => $total > 0 ? round(($channelCounts['message'] / $total) * 100, 1) : 0.0],
            ],
            'summary_rows' => $summaryRows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSourceSection(Builder $baseQuery, int $totalHandled): array
    {
        $rows = (clone $baseQuery)
            ->select('source_id', 'lead_type', DB::raw('count(*) as total'))
            ->groupBy('source_id', 'lead_type')
            ->get();

        if ($rows->isEmpty()) {
            return ['rows' => [], 'totals' => []];
        }

        $sourceIds = $rows->pluck('source_id')->filter()->unique()->values()->all();
        $sources = $sourceIds !== []
            ? Source::query()->whereIn('id', $sourceIds)->get(['id', 'name'])->keyBy('id')
            : collect();

        $matrix = [];
        foreach ($rows as $row) {
            $sourceId = $row->source_id;
            $sourceName = $sourceId && $sources->has($sourceId)
                ? (string) $sources->get($sourceId)->name
                : translate('Not_Specified');
            $type = (string) ($row->lead_type ?? Lead::TYPE_UNKNOWN);
            $count = (int) $row->total;

            if (! isset($matrix[$sourceName])) {
                $matrix[$sourceName] = [
                    'source' => $sourceName,
                    'total' => 0,
                    'customer' => 0,
                    'provider' => 0,
                    'unknown' => 0,
                    'invalid' => 0,
                    'future_customer' => 0,
                ];
            }

            $matrix[$sourceName]['total'] += $count;
            if (isset($matrix[$sourceName][$type])) {
                $matrix[$sourceName][$type] += $count;
            }
        }

        $result = collect($matrix)
            ->map(function (array $row) use ($totalHandled) {
                $row['pct'] = $totalHandled > 0 ? round(($row['total'] / $totalHandled) * 100, 1) : 0.0;

                return $row;
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'rows' => $result,
            'totals' => [
                'handled' => $totalHandled,
                'sources' => count($result),
            ],
        ];
    }

    /**
     * @param  list<array{label: string, total?: int, count?: int}>  $rows
     * @return list<array{label: string, count: int, pct: float}>
     */
    private function withPercentages(array $rows, int $denominator): array
    {
        return collect($rows)->map(function (array $row) use ($denominator) {
            $count = (int) ($row['total'] ?? $row['count'] ?? 0);

            return [
                'label' => (string) ($row['label'] ?? translate('Not_Specified')),
                'count' => $count,
                'total' => $denominator,
                'pct' => $denominator > 0 ? round(($count / $denominator) * 100, 1) : 0.0,
            ];
        })->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function outcomeRows(array $items, int $denominator): array
    {
        $pct = fn (int $count): float => $denominator > 0 ? round(($count / $denominator) * 100, 1) : 0.0;

        return collect($items)->map(function (array $item) use ($pct, $denominator) {
            $count = (int) ($item['count'] ?? 0);

            return array_merge($item, [
                'total' => $denominator,
                'pct' => $pct($count),
            ]);
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        $emptyQuery = Lead::query()->whereRaw('1 = 0');

        return [
            'total_handled' => 0,
            'type_breakdown' => $this->buildTypeBreakdown($emptyQuery, 0),
            'customer' => [
                'total' => 0,
                'booked' => 0,
                'pending' => 0,
                'hold' => 0,
                'cancelled' => 0,
                'conversion_rate' => 0.0,
                'cancel_rate' => 0.0,
                'cancel_reasons' => [],
                'outcome_rows' => $this->outcomeRows([
                    ['key' => 'booked', 'label' => translate('Bookings_completed'), 'count' => 0, 'tone' => 'good', 'icon' => 'check_circle'],
                    ['key' => 'pending', 'label' => translate('Pending'), 'count' => 0, 'tone' => 'warning', 'icon' => 'hourglass_top'],
                    ['key' => 'cancelled', 'label' => translate('Cancelled'), 'count' => 0, 'tone' => 'danger', 'icon' => 'cancel'],
                ], 1),
            ],
            'provider' => [
                'total' => 0,
                'registered' => 0,
                'pending' => 0,
                'cancelled' => 0,
                'completion_rate' => 0.0,
                'cancel_rate' => 0.0,
                'cancel_reasons' => [],
                'outcome_rows' => $this->outcomeRows([
                    ['key' => 'registered', 'label' => translate('Progress_provider_registered') ?? translate('completed'), 'count' => 0, 'tone' => 'good', 'icon' => 'how_to_reg'],
                    ['key' => 'pending', 'label' => translate('Pending'), 'count' => 0, 'tone' => 'warning', 'icon' => 'hourglass_top'],
                    ['key' => 'cancelled', 'label' => translate('Cancelled'), 'count' => 0, 'tone' => 'danger', 'icon' => 'cancel'],
                ], 1),
            ],
            'invalid_reasons' => [],
            'future_customer_reasons' => [],
            'outbound' => $this->buildOutboundSection([], now(), now()),
            'sources' => ['rows' => [], 'totals' => []],
            'charts' => [
                'lead_type_labels' => [],
                'lead_type_series' => [],
                'source_labels' => [],
                'source_series' => [],
                'customer_outcome_labels' => [
                    translate('Bookings_completed'),
                    translate('Pending'),
                    translate('Cancelled'),
                ],
                'customer_outcome_series' => [0, 0, 0],
                'provider_outcome_labels' => [
                    translate('Progress_provider_registered') ?? translate('completed'),
                    translate('Pending'),
                    translate('Cancelled'),
                ],
                'provider_outcome_series' => [0, 0, 0],
            ],
        ];
    }
}
