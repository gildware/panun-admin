<?php

namespace Modules\LeadManagement\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Entities\Source;
use Modules\ServiceManagement\Entities\Service;
use Modules\ZoneManagement\Entities\Zone;

class SuperfoneLeadCharts
{
    private const UNSPECIFIED = '__unspecified__';

    /**
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $period = $request->query('lead_period') === 'today' ? 'today' : 'overall';
        $filters = [
            'period' => $period,
            'category' => $this->filterId($request->query('lead_category')),
            'service' => $this->filterId($request->query('lead_service')),
            'zone' => $this->filterId($request->query('lead_zone')),
        ];

        $customerLabels = $this->outcomeLabels(CustomerLeadStatus::class, translate('Booked'));
        $providerLabels = $this->outcomeLabels(ProviderLeadStatus::class, translate('Booked'));
        $rows = $this->rows($period);

        $options = [
            'categories' => $this->options($rows, 'category_id', 'category_label'),
            'services' => $this->options($rows, 'service_id', 'service_label'),
            'zones' => $this->zoneOptions($rows),
        ];

        $filtered = array_values(array_filter($rows, fn (array $row) => $this->matches($row, $filters)));

        return [
            'filters' => $filters,
            'options' => $options,
            'total' => count($filtered),
            'types' => $this->typeChart($filtered),
            'customer' => $this->outcomeChart($filtered, Lead::TYPE_CUSTOMER, $customerLabels),
            'provider' => $this->outcomeChart($filtered, Lead::TYPE_PROVIDER, $providerLabels),
            'bookings' => $this->bookingChart($filtered),
            'breakdown' => [
                'category' => $this->breakdown($filtered, 'category', $customerLabels, $providerLabels, $filters),
                'service' => $this->breakdown($filtered, 'service', $customerLabels, $providerLabels, $filters),
                'zone' => $this->breakdown($filtered, 'zone', $customerLabels, $providerLabels, $filters),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(string $period): array
    {
        $sourceIds = $this->superfoneSourceIds();
        if ($sourceIds === []) {
            return [];
        }

        $leads = Lead::query()
            ->whereIn('source_id', $sourceIds)
            ->get(['id', 'lead_type', 'date_time_of_lead_received', 'created_at']);

        $leads = $leads->filter(fn (Lead $lead) => $this->inPeriod($lead, $period))->values();
        if ($leads->isEmpty()) {
            return [];
        }

        $typedIds = $leads
            ->whereIn('lead_type', [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER])
            ->pluck('id')
            ->all();

        $history = $this->latestHistory($typedIds);
        $lookups = $this->lookups($history);

        $rows = [];
        foreach ($leads as $lead) {
            $type = (string) ($lead->lead_type ?: Lead::TYPE_UNKNOWN);
            $data = $history[$lead->id.'|'.$type] ?? [];
            $categoryId = $this->categoryId($type, $data);
            $serviceId = $type === Lead::TYPE_CUSTOMER ? $this->normalizeId($data['service_name'] ?? null) : null;
            $zoneIds = $this->zoneIds($data);
            $statusId = $type === Lead::TYPE_CUSTOMER
                ? $this->normalizeId($data['customer_lead_status_id'] ?? null)
                : ($type === Lead::TYPE_PROVIDER ? $this->normalizeId($data['provider_lead_status_id'] ?? null) : null);
            $status = $statusId ? $lookups['statuses']->get($type.'|'.$statusId) : null;

            $rows[] = [
                'id' => (string) $lead->id,
                'type' => $type,
                'outcome' => $this->outcomeBucket(strtolower((string) ($status?->base_type ?? 'pending'))),
                'category_id' => $categoryId,
                'category_label' => $categoryId ? ($lookups['categories']->get($categoryId)?->name ?? translate('Not_Specified')) : null,
                'service_id' => $serviceId,
                'service_label' => $serviceId ? ($lookups['services']->get($serviceId)?->name ?? translate('Not_Specified')) : null,
                'zone_ids' => $zoneIds,
                'zones' => array_map(function (string $zoneId) use ($lookups) {
                    return [
                        'id' => $zoneId,
                        'label' => $lookups['zones']->get($zoneId)?->name ?? translate('Not_Specified'),
                    ];
                }, $zoneIds),
                'booking_id' => $this->normalizeId($data['booking_id'] ?? null),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<int|string>  $leadIds
     * @return array<string, array<string, mixed>>
     */
    private function latestHistory(array $leadIds): array
    {
        if ($leadIds === []) {
            return [];
        }

        $latest = [];
        foreach (array_chunk(array_values($leadIds), 400) as $chunk) {
            $histories = DB::table('lead_type_histories')
                ->whereIn('lead_id', $chunk)
                ->whereIn('type', [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER])
                ->orderByDesc('id')
                ->get(['lead_id', 'type', 'data']);

            foreach ($histories as $history) {
                $key = $history->lead_id.'|'.$history->type;
                if (isset($latest[$key])) {
                    continue;
                }
                $decoded = json_decode((string) $history->data, true);
                $latest[$key] = is_array($decoded) ? $decoded : [];
            }
        }

        return $latest;
    }

    /**
     * @param  array<string, array<string, mixed>>  $history
     * @return array{statuses: Collection, categories: Collection, services: Collection, zones: Collection}
     */
    private function lookups(array $history): array
    {
        $customerStatusIds = [];
        $providerStatusIds = [];
        $categoryIds = [];
        $serviceIds = [];
        $zoneIds = [];

        foreach ($history as $key => $data) {
            $type = str_ends_with($key, '|'.Lead::TYPE_PROVIDER) ? Lead::TYPE_PROVIDER : Lead::TYPE_CUSTOMER;
            if ($type === Lead::TYPE_CUSTOMER) {
                if ($id = $this->normalizeId($data['customer_lead_status_id'] ?? null)) {
                    $customerStatusIds[] = $id;
                }
                if ($id = $this->normalizeId($data['service_name'] ?? null)) {
                    $serviceIds[] = $id;
                }
            } elseif ($id = $this->normalizeId($data['provider_lead_status_id'] ?? null)) {
                $providerStatusIds[] = $id;
            }
            if ($id = $this->categoryId($type, $data)) {
                $categoryIds[] = $id;
            }
            foreach ($this->zoneIds($data) as $zoneId) {
                $zoneIds[] = $zoneId;
            }
        }

        $statuses = collect();
        if ($customerStatusIds !== []) {
            foreach (CustomerLeadStatus::query()->whereIn('id', array_unique($customerStatusIds))->get() as $status) {
                $statuses->put(Lead::TYPE_CUSTOMER.'|'.$status->id, $status);
            }
        }
        if ($providerStatusIds !== []) {
            foreach (ProviderLeadStatus::query()->whereIn('id', array_unique($providerStatusIds))->get() as $status) {
                $statuses->put(Lead::TYPE_PROVIDER.'|'.$status->id, $status);
            }
        }

        return [
            'statuses' => $statuses,
            'categories' => $categoryIds === []
                ? collect()
                : Category::withoutGlobalScopes()->whereIn('id', array_unique($categoryIds))->get()->keyBy(fn ($row) => (string) $row->id),
            'services' => $serviceIds === []
                ? collect()
                : Service::withTrashed()->whereIn('id', array_unique($serviceIds))->get(['id', 'name'])->keyBy(fn ($row) => (string) $row->id),
            'zones' => $zoneIds === []
                ? collect()
                : Zone::withoutGlobalScopes()->whereIn('id', array_unique($zoneIds))->get()->keyBy(fn ($row) => (string) $row->id),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function typeChart(array $rows): array
    {
        $defs = [
            Lead::TYPE_UNKNOWN => ['label' => translate('Unknown'), 'color' => '#6b7280'],
            Lead::TYPE_CUSTOMER => ['label' => translate('Customer'), 'color' => '#1d4ed8'],
            Lead::TYPE_PROVIDER => ['label' => translate('Provider'), 'color' => '#0f7a3a'],
            Lead::TYPE_FUTURE_CUSTOMER => ['label' => translate('Future_Customer'), 'color' => '#b45309'],
            Lead::TYPE_FUTURE_PROVIDER => ['label' => translate('Future_Provider'), 'color' => '#6d28d9'],
            Lead::TYPE_INVALID => ['label' => translate('Invalid'), 'color' => '#c62828'],
        ];
        $counts = array_fill_keys(array_keys($defs), 0);
        foreach ($rows as $row) {
            $type = $defs[$row['type']] ?? null ? $row['type'] : Lead::TYPE_UNKNOWN;
            $counts[$type]++;
        }

        $summary = [];
        foreach ($defs as $key => $def) {
            if ($key === Lead::TYPE_INVALID && $counts[$key] === 0) {
                continue;
            }
            $summary[] = [
                'key' => $key,
                'label' => $def['label'],
                'count' => $counts[$key],
                'color' => $def['color'],
            ];
        }

        return ['summary' => $summary] + $this->donut($summary);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, string>  $labels
     * @return array<string, mixed>
     */
    private function outcomeChart(array $rows, string $type, array $labels): array
    {
        $colors = [
            'booked' => '#0f7a3a',
            'pending' => '#b45309',
            'cancelled' => '#c62828',
        ];
        $counts = ['booked' => 0, 'pending' => 0, 'cancelled' => 0];
        foreach ($rows as $row) {
            if ($row['type'] !== $type) {
                continue;
            }
            $counts[$row['outcome']]++;
        }

        $summary = [];
        foreach (['booked', 'pending', 'cancelled'] as $key) {
            $summary[] = [
                'key' => $key,
                'label' => $labels[$key],
                'count' => $counts[$key],
                'color' => $colors[$key],
            ];
        }

        return [
            'total' => array_sum($counts),
            'summary' => $summary,
        ] + $this->donut($summary);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function bookingChart(array $rows): array
    {
        $bookedLeadIds = [];
        $historyBookingIds = [];
        foreach ($rows as $row) {
            if ($row['type'] !== Lead::TYPE_CUSTOMER || $row['outcome'] !== 'booked') {
                continue;
            }
            $bookedLeadIds[] = $row['id'];
            if ($row['booking_id']) {
                $historyBookingIds[] = $row['booking_id'];
            }
        }

        $counts = [];
        foreach ($this->bookings($bookedLeadIds, $historyBookingIds) as $booking) {
            $bucket = $this->bookingBucket((string) $booking->booking_status);
            $counts[$bucket] = ($counts[$bucket] ?? 0) + 1;
        }

        $order = ['pending', 'accepted', 'ongoing', 'hold', 'completed', 'cancelled', 'pending_cancellation', 'refunded'];
        $colors = [
            'pending' => '#b45309',
            'accepted' => '#1d4ed8',
            'ongoing' => '#0e7490',
            'hold' => '#6d28d9',
            'completed' => '#0f7a3a',
            'cancelled' => '#c62828',
            'pending_cancellation' => '#9a3412',
            'refunded' => '#334155',
        ];
        $always = ['pending', 'hold', 'completed', 'cancelled'];
        $keys = array_values(array_unique(array_merge(
            $order,
            array_keys($counts)
        )));
        usort($keys, function (string $a, string $b) use ($order): int {
            $ai = array_search($a, $order, true);
            $bi = array_search($b, $order, true);

            return ($ai === false ? 99 : $ai) <=> ($bi === false ? 99 : $bi);
        });

        $summary = [];
        foreach ($keys as $key) {
            $count = (int) ($counts[$key] ?? 0);
            if ($count === 0 && ! in_array($key, $always, true)) {
                continue;
            }
            $summary[] = [
                'key' => $key,
                'label' => $this->bookingLabel($key),
                'count' => $count,
                'color' => $colors[$key] ?? '#6b7280',
            ];
        }

        return [
            'total' => array_sum($counts),
            'lead_count' => count($bookedLeadIds),
            'summary' => $summary,
        ] + $this->donut($summary);
    }

    /**
     * @param  list<string>  $leadIds
     * @param  list<string>  $bookingIds
     */
    private function bookings(array $leadIds, array $bookingIds): Collection
    {
        if ($leadIds === [] && $bookingIds === []) {
            return collect();
        }

        return Booking::query()
            ->where(function ($query) use ($leadIds, $bookingIds) {
                if ($leadIds !== []) {
                    $query->whereIn('lead_id', $leadIds);
                }
                if ($bookingIds !== []) {
                    $method = $leadIds === [] ? 'whereIn' : 'orWhereIn';
                    $query->{$method}('id', $bookingIds);
                }
            })
            ->get(['id', 'lead_id', 'booking_status'])
            ->unique('id')
            ->values();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, string>  $customerLabels
     * @param  array<string, string>  $providerLabels
     * @param  array{period: string, category: ?string, service: ?string, zone: ?string}  $filters
     * @return array{categories: list<string>, series: list<array<string, mixed>>}
     */
    private function breakdown(array $rows, string $dimension, array $customerLabels, array $providerLabels, array $filters): array
    {
        $keys = $dimension === 'service'
            ? ['customer_booked', 'customer_pending', 'customer_cancelled']
            : ['customer_booked', 'customer_pending', 'customer_cancelled', 'provider_booked', 'provider_pending', 'provider_cancelled'];

        $defs = [
            ['key' => 'customer_booked', 'name' => translate('Customer').' · '.$customerLabels['booked'], 'color' => '#0f7a3a'],
            ['key' => 'customer_pending', 'name' => translate('Customer').' · '.$customerLabels['pending'], 'color' => '#b45309'],
            ['key' => 'customer_cancelled', 'name' => translate('Customer').' · '.$customerLabels['cancelled'], 'color' => '#c62828'],
            ['key' => 'provider_booked', 'name' => translate('Provider').' · '.$providerLabels['booked'], 'color' => '#1d4ed8'],
            ['key' => 'provider_pending', 'name' => translate('Provider').' · '.$providerLabels['pending'], 'color' => '#6d28d9'],
            ['key' => 'provider_cancelled', 'name' => translate('Provider').' · '.$providerLabels['cancelled'], 'color' => '#334155'],
        ];
        $defs = array_values(array_filter($defs, fn (array $def) => in_array($def['key'], $keys, true)));

        $buckets = [];
        foreach ($rows as $row) {
            if (! in_array($row['type'], [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER], true)) {
                continue;
            }
            if ($dimension === 'service' && $row['type'] !== Lead::TYPE_CUSTOMER) {
                continue;
            }
            $seriesKey = $row['type'].'_'.$row['outcome'];
            if ($dimension === 'zone') {
                $zones = $row['zones'];
                if ($filters['zone']) {
                    $zones = array_values(array_filter($zones, fn (array $zone) => $zone['id'] === $filters['zone']));
                }
                if ($zones === []) {
                    $this->bump($buckets, self::UNSPECIFIED, translate('Not_Specified'), $seriesKey, $keys);
                    continue;
                }
                foreach ($zones as $zone) {
                    $this->bump($buckets, $zone['id'], $zone['label'], $seriesKey, $keys);
                }
                continue;
            }

            $id = $dimension === 'category' ? $row['category_id'] : $row['service_id'];
            $label = $dimension === 'category' ? $row['category_label'] : $row['service_label'];
            if (! $id) {
                $this->bump($buckets, self::UNSPECIFIED, translate('Not_Specified'), $seriesKey, $keys);
                continue;
            }
            $this->bump($buckets, $id, (string) $label, $seriesKey, $keys);
        }

        return $this->finalizeBreakdown($buckets, $defs);
    }

    /**
     * @param  array<string, array<string, mixed>>  $buckets
     * @param  list<string>  $keys
     */
    private function bump(array &$buckets, string $key, string $label, string $seriesKey, array $keys): void
    {
        if (! isset($buckets[$key])) {
            $buckets[$key] = ['label' => $label, 'total' => 0];
            foreach ($keys as $series) {
                $buckets[$key][$series] = 0;
            }
        }
        if (! isset($buckets[$key][$seriesKey])) {
            return;
        }
        $buckets[$key][$seriesKey]++;
        $buckets[$key]['total']++;
    }

    /**
     * @param  array<string, array<string, mixed>>  $buckets
     * @param  list<array{key: string, name: string, color: string}>  $defs
     * @return array{categories: list<string>, series: list<array<string, mixed>>}
     */
    private function finalizeBreakdown(array $buckets, array $defs): array
    {
        $unspecified = $buckets[self::UNSPECIFIED] ?? null;
        unset($buckets[self::UNSPECIFIED]);
        $rows = array_values($buckets);
        usort($rows, fn (array $a, array $b) => $b['total'] <=> $a['total'] ?: strcasecmp((string) $a['label'], (string) $b['label']));

        $limit = 12;
        if (count($rows) > $limit) {
            $rest = array_slice($rows, $limit);
            $rows = array_slice($rows, 0, $limit);
            $other = ['label' => translate('Other'), 'total' => 0];
            foreach ($defs as $def) {
                $other[$def['key']] = 0;
            }
            foreach ($rest as $row) {
                $other['total'] += (int) $row['total'];
                foreach ($defs as $def) {
                    $other[$def['key']] += (int) ($row[$def['key']] ?? 0);
                }
            }
            if ($other['total'] > 0) {
                $rows[] = $other;
            }
        }
        if ($unspecified && (int) $unspecified['total'] > 0) {
            $rows[] = $unspecified;
        }

        $series = [];
        foreach ($defs as $def) {
            $series[] = [
                'name' => $def['name'],
                'color' => $def['color'],
                'data' => array_map(fn (array $row) => (int) ($row[$def['key']] ?? 0), $rows),
            ];
        }

        return [
            'categories' => array_map(fn (array $row) => (string) $row['label'], $rows),
            'series' => $series,
        ];
    }

    /**
     * @param  list<array{key: string, label: string, count: int, color: string}>  $summary
     * @return array{labels: list<string>, series: list<int>, colors: list<string>}
     */
    private function donut(array $summary): array
    {
        $labels = [];
        $series = [];
        $colors = [];
        foreach ($summary as $item) {
            if ((int) $item['count'] <= 0) {
                continue;
            }
            $labels[] = $item['label'];
            $series[] = (int) $item['count'];
            $colors[] = $item['color'];
        }

        return compact('labels', 'series', 'colors');
    }

    /**
     * @param  class-string<CustomerLeadStatus|ProviderLeadStatus>  $model
     * @return array<string, string>
     */
    private function outcomeLabels(string $model, string $bookedFallback): array
    {
        $labels = [
            'pending' => translate('Pending'),
            'booked' => $bookedFallback,
            'cancelled' => translate('Cancelled'),
        ];
        $chosen = [];
        foreach ($model::query()->orderBy('id')->get() as $status) {
            $bucket = $this->outcomeBucket(strtolower((string) $status->base_type));
            $active = (bool) $status->is_active;
            if (isset($chosen[$bucket]) && $chosen[$bucket] && ! $active) {
                continue;
            }
            if ($status->name) {
                $labels[$bucket] = (string) $status->name;
                $chosen[$bucket] = $active;
            }
        }

        return $labels;
    }

    private function outcomeBucket(string $baseType): string
    {
        if (in_array($baseType, ['cancel', 'cancelled', 'canceled'], true)) {
            return 'cancelled';
        }
        if (in_array($baseType, ['completed', 'booked'], true)) {
            return 'booked';
        }

        return 'pending';
    }

    private function bookingBucket(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'canceled', 'cancelled' => 'cancelled',
            'on_hold', 'hold' => 'hold',
            'refund_request', 'refunded' => 'refunded',
            'pending_cancellation' => 'pending_cancellation',
            'accepted' => 'accepted',
            'ongoing' => 'ongoing',
            'completed' => 'completed',
            'pending', '' => 'pending',
            default => $status !== '' ? $status : 'pending',
        };
    }

    private function bookingLabel(string $bucket): string
    {
        return match ($bucket) {
            'pending' => translate('Pending'),
            'accepted' => translate('Accepted'),
            'ongoing' => translate('Ongoing'),
            'hold' => translate('Hold'),
            'completed' => translate('Completed'),
            'cancelled' => translate('Cancelled'),
            'pending_cancellation' => translate('Pending_cancellation'),
            'refunded' => translate('Refunded'),
            default => ucfirst(str_replace('_', ' ', $bucket)),
        };
    }

    /**
     * @return list<int>
     */
    private function superfoneSourceIds(): array
    {
        return Source::query()
            ->get(['id', 'name'])
            ->filter(function (Source $source) {
                $name = strtolower(trim((string) $source->name));
                $compact = str_replace([' ', '-', '_'], '', $name);

                return $name === strtolower(Source::NAME_SUPERFONE) || $compact === 'callsuperfone';
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function inPeriod(Lead $lead, string $period): bool
    {
        if ($period !== 'today') {
            return true;
        }

        $at = $lead->date_time_of_lead_received ?? $lead->created_at;
        if (! $at instanceof Carbon) {
            return false;
        }

        $at = $at->copy()->timezone('Asia/Kolkata');
        $start = now('Asia/Kolkata')->startOfDay();
        $end = now('Asia/Kolkata')->endOfDay();

        return $at->greaterThanOrEqualTo($start) && $at->lessThanOrEqualTo($end);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function categoryId(string $type, array $data): ?string
    {
        $key = $type === Lead::TYPE_PROVIDER ? 'provider_service_category' : 'service_category';

        return $this->normalizeId($data[$key] ?? null);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function zoneIds(array $data): array
    {
        $ids = [];
        if (! empty($data['zone_ids']) && is_array($data['zone_ids'])) {
            foreach ($data['zone_ids'] as $zoneId) {
                if ($id = $this->normalizeId($zoneId)) {
                    $ids[] = $id;
                }
            }
        }
        if ($id = $this->normalizeId($data['zone_id'] ?? null)) {
            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{id: string, name: string}>
     */
    private function options(array $rows, string $idKey, string $labelKey): array
    {
        $options = [];
        foreach ($rows as $row) {
            if (! $row[$idKey]) {
                continue;
            }
            $options[$row[$idKey]] = (string) ($row[$labelKey] ?: $row[$idKey]);
        }
        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        $list = [];
        foreach ($options as $id => $name) {
            $list[] = ['id' => (string) $id, 'name' => $name];
        }

        return $list;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{id: string, name: string}>
     */
    private function zoneOptions(array $rows): array
    {
        $options = [];
        foreach ($rows as $row) {
            foreach ($row['zones'] as $zone) {
                $options[$zone['id']] = (string) $zone['label'];
            }
        }
        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        $list = [];
        foreach ($options as $id => $name) {
            $list[] = ['id' => (string) $id, 'name' => $name];
        }

        return $list;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{period: string, category: ?string, service: ?string, zone: ?string}  $filters
     */
    private function matches(array $row, array $filters): bool
    {
        if ($filters['category'] && $row['category_id'] !== $filters['category']) {
            return false;
        }
        if ($filters['service'] && $row['service_id'] !== $filters['service']) {
            return false;
        }
        if ($filters['zone'] && ! in_array($filters['zone'], $row['zone_ids'], true)) {
            return false;
        }

        return true;
    }

    private function filterId(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || ! preg_match('/^[A-Za-z0-9\-]+$/', $value)) {
            return null;
        }

        return $value;
    }

    private function normalizeId(mixed $value): ?string
    {
        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' || $value === '0' ? null : $value;
    }
}
