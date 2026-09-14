<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\CustomerLeadArea;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\ZoneManagement\Entities\Zone;

class GeographicBusinessReportAnalyticsService
{
    public const UNSPECIFIED_KEY = '__unspecified__';

    public const LEAD_TYPES = [
        Lead::TYPE_UNKNOWN,
        Lead::TYPE_CUSTOMER,
        Lead::TYPE_PROVIDER,
        Lead::TYPE_INVALID,
        Lead::TYPE_FUTURE_CUSTOMER,
    ];

    public const CUSTOMER_STATUSES = ['pending', 'hold', 'booked', 'cancelled'];

    public const BOOKING_GROUPS = ['pending', 'completed', 'cancelled'];

    /**
     * @param  array<string, mixed>  $filters  zone_ids, area_ids, category_ids
     * @return array<string, mixed>
     */
    public function build(Carbon $from, Carbon $to, array $filters = []): array
    {
        $leads = Lead::query()
            ->whereBetween('date_time_of_lead_received', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get(['id', 'lead_type', 'date_time_of_lead_received', 'next_followup_at']);

        $bookings = Booking::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get(['id', 'zone_id', 'area_id', 'category_id', 'booking_status', 'total_booking_amount', 'created_at', 'lead_id']);

        $leadRows = $this->mapLeads($leads);
        $bookingRows = $this->mapBookings($bookings);

        $zoneIds = array_values(array_filter((array) ($filters['zone_ids'] ?? [])));
        $areaIds = array_values(array_filter((array) ($filters['area_ids'] ?? [])));
        $categoryIds = array_values(array_filter((array) ($filters['category_ids'] ?? [])));

        if ($zoneIds !== []) {
            $allowed = array_map('strval', $zoneIds);
            $leadRows = array_values(array_filter($leadRows, fn (array $row) => in_array((string) $row['zone_key'], $allowed, true)));
            $bookingRows = array_values(array_filter($bookingRows, fn (array $row) => in_array((string) $row['zone_key'], $allowed, true)));
        }
        if ($areaIds !== []) {
            $allowed = array_map('strval', $areaIds);
            $leadRows = array_values(array_filter($leadRows, fn (array $row) => in_array((string) $row['area_key'], $allowed, true)));
            $bookingRows = array_values(array_filter($bookingRows, fn (array $row) => in_array((string) $row['area_key'], $allowed, true)));
        }
        if ($categoryIds !== []) {
            $allowed = array_map('strval', $categoryIds);
            $leadRows = array_values(array_filter($leadRows, fn (array $row) => in_array((string) $row['category_key'], $allowed, true)));
            $bookingRows = array_values(array_filter($bookingRows, fn (array $row) => in_array((string) $row['category_key'], $allowed, true)));
        }

        return $this->aggregate($leadRows, $bookingRows, $from, $to);
    }

    /**
     * @param  list<array<string, mixed>>  $leads
     * @param  list<array<string, mixed>>  $bookings
     * @return array<string, mixed>
     */
    public function aggregate(array $leads, array $bookings, Carbon $from, Carbon $to): array
    {
        $leadTypeCounts = array_fill_keys(self::LEAD_TYPES, 0);
        $customerStatusCounts = array_fill_keys(self::CUSTOMER_STATUSES, 0);
        $bookingGroupCounts = array_fill_keys(self::BOOKING_GROUPS, 0);
        $bookingStatusCounts = [];

        $zoneBuckets = [];
        $areaBuckets = [];
        $zoneCategory = [];
        $areaCategory = [];

        $missingLeadZone = 0;
        $missingLeadArea = 0;
        $missingBookingZone = 0;
        $missingBookingArea = 0;

        $dayKeys = $this->dayKeys($from, $to);
        $dailyLeads = array_fill_keys($dayKeys, 0);
        $dailyBookings = array_fill_keys($dayKeys, 0);
        $dayKeySet = array_fill_keys($dayKeys, true);
        $dailyAreaLeads = [];
        $dailyAreaBookings = [];
        $dailyZoneLeads = [];
        $dailyZoneBookings = [];
        $areaDayLabels = [];
        $zoneDayLabels = [];

        foreach ($leads as $lead) {
            $type = $this->normalizeLeadType((string) ($lead['lead_type'] ?? Lead::TYPE_UNKNOWN));
            $leadTypeCounts[$type]++;

            $status = $this->normalizeCustomerStatus($lead['customer_status'] ?? null);
            if ($type === Lead::TYPE_CUSTOMER && $status !== null) {
                $customerStatusCounts[$status]++;
            }

            $zoneKey = (string) ($lead['zone_key'] ?? self::UNSPECIFIED_KEY);
            $areaKey = (string) ($lead['area_key'] ?? self::UNSPECIFIED_KEY);
            if ($zoneKey === '' || $zoneKey === self::UNSPECIFIED_KEY) {
                $missingLeadZone++;
                $zoneKey = self::UNSPECIFIED_KEY;
            }
            if ($areaKey === '' || $areaKey === self::UNSPECIFIED_KEY) {
                $missingLeadArea++;
                $areaKey = self::UNSPECIFIED_KEY;
            }

            $zoneLabel = (string) ($lead['zone_label'] ?? translate('Not_Specified'));
            $areaLabel = (string) ($lead['area_label'] ?? translate('Not_Specified'));
            $areaDayLabels[$areaKey] = $areaLabel !== '' ? $areaLabel : translate('Not_Specified');
            $zoneDayLabels[$zoneKey] = $zoneLabel !== '' ? $zoneLabel : translate('Not_Specified');

            $day = $this->bucketDay($lead['received_at'] ?? null, $dayKeySet);
            if ($day !== null) {
                $dailyLeads[$day]++;
            }
            $this->bumpDailyGeo($dailyAreaLeads, $dayKeys, $areaKey, $day);
            $this->bumpDailyGeo($dailyZoneLeads, $dayKeys, $zoneKey, $day);
            $categoryKey = (string) ($lead['category_key'] ?? self::UNSPECIFIED_KEY);
            $categoryLabel = (string) ($lead['category_label'] ?? translate('Not_Specified'));
            if ($categoryKey === '') {
                $categoryKey = self::UNSPECIFIED_KEY;
            }

            $this->touchGeo($zoneBuckets, $zoneKey, $zoneLabel);
            $this->touchGeo($areaBuckets, $areaKey, $areaLabel);
            $zoneBuckets[$zoneKey]['leads']++;
            $areaBuckets[$areaKey]['leads']++;
            $zoneBuckets[$zoneKey][$type]++;
            $areaBuckets[$areaKey][$type]++;
            if ($status !== null && $type === Lead::TYPE_CUSTOMER) {
                $zoneBuckets[$zoneKey][$status]++;
                $areaBuckets[$areaKey][$status]++;
            }

            $this->touchMatrix($zoneCategory, $zoneKey, $zoneLabel, $categoryKey, $categoryLabel);
            $this->touchMatrix($areaCategory, $areaKey, $areaLabel, $categoryKey, $categoryLabel);
            $zoneCategory[$this->matrixKey($zoneKey, $categoryKey)]['leads']++;
            $areaCategory[$this->matrixKey($areaKey, $categoryKey)]['leads']++;
            $zoneCategory[$this->matrixKey($zoneKey, $categoryKey)][$type]++;
            $areaCategory[$this->matrixKey($areaKey, $categoryKey)][$type]++;
            if ($status !== null && $type === Lead::TYPE_CUSTOMER) {
                $zoneCategory[$this->matrixKey($zoneKey, $categoryKey)][$status]++;
                $areaCategory[$this->matrixKey($areaKey, $categoryKey)][$status]++;
            }
        }

        foreach ($bookings as $booking) {
            $status = $this->normalizeBookingStatus((string) ($booking['status'] ?? 'pending'));
            $group = $this->bookingGroup($status);
            $bookingGroupCounts[$group]++;
            $bookingStatusCounts[$status] = ($bookingStatusCounts[$status] ?? 0) + 1;

            $amount = (float) ($booking['amount'] ?? 0);
            $zoneKey = (string) ($booking['zone_key'] ?? self::UNSPECIFIED_KEY);
            $areaKey = (string) ($booking['area_key'] ?? self::UNSPECIFIED_KEY);
            if ($zoneKey === '' || $zoneKey === self::UNSPECIFIED_KEY) {
                $missingBookingZone++;
                $zoneKey = self::UNSPECIFIED_KEY;
            }
            if ($areaKey === '' || $areaKey === self::UNSPECIFIED_KEY) {
                $missingBookingArea++;
                $areaKey = self::UNSPECIFIED_KEY;
            }

            $zoneLabel = (string) ($booking['zone_label'] ?? translate('Not_Specified'));
            $areaLabel = (string) ($booking['area_label'] ?? translate('Not_Specified'));
            $areaDayLabels[$areaKey] = $areaLabel !== '' ? $areaLabel : translate('Not_Specified');
            $zoneDayLabels[$zoneKey] = $zoneLabel !== '' ? $zoneLabel : translate('Not_Specified');

            $day = $this->bucketDay($booking['created_at'] ?? null, $dayKeySet);
            if ($day !== null) {
                $dailyBookings[$day]++;
            }
            $this->bumpDailyGeo($dailyAreaBookings, $dayKeys, $areaKey, $day);
            $this->bumpDailyGeo($dailyZoneBookings, $dayKeys, $zoneKey, $day);
            $categoryKey = (string) ($booking['category_key'] ?? self::UNSPECIFIED_KEY);
            $categoryLabel = (string) ($booking['category_label'] ?? translate('Not_Specified'));
            if ($categoryKey === '') {
                $categoryKey = self::UNSPECIFIED_KEY;
            }

            $this->touchGeo($zoneBuckets, $zoneKey, $zoneLabel);
            $this->touchGeo($areaBuckets, $areaKey, $areaLabel);
            $this->applyBookingToGeo($zoneBuckets[$zoneKey], $group, $status, $amount);
            $this->applyBookingToGeo($areaBuckets[$areaKey], $group, $status, $amount);

            $this->touchMatrix($zoneCategory, $zoneKey, $zoneLabel, $categoryKey, $categoryLabel);
            $this->touchMatrix($areaCategory, $areaKey, $areaLabel, $categoryKey, $categoryLabel);
            $this->applyBookingToGeo($zoneCategory[$this->matrixKey($zoneKey, $categoryKey)], $group, $status, $amount);
            $this->applyBookingToGeo($areaCategory[$this->matrixKey($areaKey, $categoryKey)], $group, $status, $amount);
        }

        $zoneRows = $this->finalizeGeo($zoneBuckets);
        $areaRows = $this->finalizeGeo($areaBuckets);
        $zoneMatrix = $this->finalizeMatrix($zoneCategory);
        $areaMatrix = $this->finalizeMatrix($areaCategory);

        $totalLeads = count($leads);
        $totalBookings = count($bookings);

        return [
            'summary' => [
                'leads' => $totalLeads,
                'unknown' => $leadTypeCounts[Lead::TYPE_UNKNOWN],
                'customer' => $leadTypeCounts[Lead::TYPE_CUSTOMER],
                'provider' => $leadTypeCounts[Lead::TYPE_PROVIDER],
                'invalid' => $leadTypeCounts[Lead::TYPE_INVALID],
                'future_customer' => $leadTypeCounts[Lead::TYPE_FUTURE_CUSTOMER],
                'pending' => $customerStatusCounts['pending'],
                'hold' => $customerStatusCounts['hold'],
                'booked' => $customerStatusCounts['booked'],
                'cancelled_leads' => $customerStatusCounts['cancelled'],
                'bookings' => $totalBookings,
                'booking_pending' => $bookingGroupCounts['pending'],
                'booking_completed' => $bookingGroupCounts['completed'],
                'booking_cancelled' => $bookingGroupCounts['cancelled'],
                'missing_lead_zone' => $missingLeadZone,
                'missing_lead_area' => $missingLeadArea,
                'missing_booking_zone' => $missingBookingZone,
                'missing_booking_area' => $missingBookingArea,
                'lead_conversion_rate' => $this->pct($customerStatusCounts['booked'], $leadTypeCounts[Lead::TYPE_CUSTOMER]),
                'booking_completion_rate' => $this->pct($bookingGroupCounts['completed'], $totalBookings),
            ],
            'lead_type_breakdown' => $this->chartRows([
                ['key' => Lead::TYPE_UNKNOWN, 'label' => translate('Unknown'), 'total' => $leadTypeCounts[Lead::TYPE_UNKNOWN], 'color' => '#858796'],
                ['key' => Lead::TYPE_CUSTOMER, 'label' => translate('Customer'), 'total' => $leadTypeCounts[Lead::TYPE_CUSTOMER], 'color' => '#4e73df'],
                ['key' => Lead::TYPE_PROVIDER, 'label' => translate('Provider'), 'total' => $leadTypeCounts[Lead::TYPE_PROVIDER], 'color' => '#36b9cc'],
                ['key' => Lead::TYPE_INVALID, 'label' => translate('Invalid'), 'total' => $leadTypeCounts[Lead::TYPE_INVALID], 'color' => '#e74a3b'],
                ['key' => Lead::TYPE_FUTURE_CUSTOMER, 'label' => translate('Future_Customer'), 'total' => $leadTypeCounts[Lead::TYPE_FUTURE_CUSTOMER], 'color' => '#6f42c1'],
            ]),
            'customer_status_breakdown' => $this->chartRows([
                ['key' => 'pending', 'label' => translate('Pending'), 'total' => $customerStatusCounts['pending'], 'color' => '#f6c23e'],
                ['key' => 'hold', 'label' => translate('Hold'), 'total' => $customerStatusCounts['hold'], 'color' => '#fd7e14'],
                ['key' => 'booked', 'label' => translate('Booked'), 'total' => $customerStatusCounts['booked'], 'color' => '#1cc88a'],
                ['key' => 'cancelled', 'label' => translate('Cancelled'), 'total' => $customerStatusCounts['cancelled'], 'color' => '#e74a3b'],
            ]),
            'booking_group_breakdown' => $this->chartRows([
                ['key' => 'completed', 'label' => translate('completed'), 'total' => $bookingGroupCounts['completed'], 'color' => '#1cc88a'],
                ['key' => 'cancelled', 'label' => translate('Cancelled'), 'total' => $bookingGroupCounts['cancelled'], 'color' => '#e74a3b'],
                ['key' => 'pending', 'label' => translate('Pending'), 'total' => $bookingGroupCounts['pending'], 'color' => '#f6c23e'],
            ]),
            'booking_status_breakdown' => $this->bookingStatusChart($bookingStatusCounts),
            'daily' => [
                'labels' => $this->displayDayLabels($dayKeys),
                'keys' => $dayKeys,
                'leads' => array_values($dailyLeads),
                'bookings' => array_values($dailyBookings),
                'by_area' => $this->dailyGeoSeries($dailyAreaLeads, $dailyAreaBookings, $areaDayLabels, $dayKeys),
                'by_zone' => $this->dailyGeoSeries($dailyZoneLeads, $dailyZoneBookings, $zoneDayLabels, $dayKeys),
            ],
            'zone' => [
                'rows' => $zoneRows,
                'lead_share' => $this->shareChart($zoneRows, 'leads'),
                'booking_share' => $this->shareChart($zoneRows, 'bookings'),
                'matrix' => $zoneMatrix,
                'targeting' => $this->targetingRows($zoneMatrix),
            ],
            'area' => [
                'rows' => $areaRows,
                'lead_share' => $this->shareChart($areaRows, 'leads'),
                'booking_share' => $this->shareChart($areaRows, 'bookings'),
                'matrix' => $areaMatrix,
                'targeting' => $this->targetingRows($areaMatrix),
            ],
            'insights' => $this->buildInsights($areaMatrix, $zoneMatrix, $missingLeadArea, $missingBookingArea),
        ];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @return list<array<string, mixed>>
     */
    private function mapLeads(Collection $leads): array
    {
        if ($leads->isEmpty()) {
            return [];
        }

        $leadIds = $leads->pluck('id')->all();
        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('lead_id');

        $zoneIds = [];
        $areaIds = [];
        $categoryIds = [];
        $statusIds = [];
        $picked = [];

        foreach ($leads as $lead) {
            $group = $histories->get($lead->id) ?? collect();
            $history = $group->first(fn ($row) => (string) $row->type === (string) $lead->lead_type)
                ?? $group->first();
            $data = ($history && is_array($history->data)) ? $history->data : [];
            $picked[(string) $lead->id] = $data;

            if ($id = $this->normalizeId($data['zone_id'] ?? null)) {
                $zoneIds[] = $id;
            }
            foreach ($this->zoneIdsFromData($data) as $zid) {
                $zoneIds[] = $zid;
            }
            if ($id = $this->normalizeId($data['area_id'] ?? null)) {
                $areaIds[] = $id;
            }
            foreach (['service_category', 'provider_service_category'] as $catField) {
                if ($id = $this->normalizeId($data[$catField] ?? null)) {
                    $categoryIds[] = $id;
                }
            }
            if ($id = $this->normalizeId($data['customer_lead_status_id'] ?? null)) {
                $statusIds[] = $id;
            }
        }

        $zones = $this->lookupZones($zoneIds);
        $areas = $this->lookupAreas($areaIds);
        $categories = $this->lookupCategories($categoryIds);
        $statuses = $statusIds !== []
            ? CustomerLeadStatus::whereIn('id', array_unique($statusIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();

        $bookingLeadIds = Booking::query()
            ->whereIn('lead_id', $leadIds)
            ->pluck('lead_id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->all();
        $bookingLeadMap = array_fill_keys($bookingLeadIds, true);

        $rows = [];
        foreach ($leads as $lead) {
            $data = $picked[(string) $lead->id] ?? [];
            $zoneId = $this->normalizeId($data['zone_id'] ?? null)
                ?? ($this->zoneIdsFromData($data)[0] ?? null);
            $areaId = $this->normalizeId($data['area_id'] ?? null);
            $categoryId = $this->normalizeId($data['service_category'] ?? null)
                ?? $this->normalizeId($data['provider_service_category'] ?? null);
            $statusId = $this->normalizeId($data['customer_lead_status_id'] ?? null);
            $status = $statusId ? $statuses->get($statusId) : null;
            $baseType = strtolower((string) ($status?->base_type ?? ''));
            $bookingStatus = strtolower((string) ($data['booking_status'] ?? ''));
            $hasBooking = isset($bookingLeadMap[(string) $lead->id]) || $this->normalizeId($data['booking_id'] ?? null);

            $rows[] = [
                'id' => (string) $lead->id,
                'lead_type' => (string) $lead->lead_type,
                'received_at' => $lead->date_time_of_lead_received,
                'zone_key' => $zoneId ?? self::UNSPECIFIED_KEY,
                'zone_label' => $this->labelFrom($zoneId, $zones),
                'area_key' => $areaId ?? self::UNSPECIFIED_KEY,
                'area_label' => $this->labelFrom($areaId, $areas),
                'category_key' => $categoryId ?? self::UNSPECIFIED_KEY,
                'category_label' => $this->labelFrom($categoryId, $categories),
                'customer_status' => $this->classifyCustomerStatus(
                    (string) $lead->lead_type,
                    $baseType,
                    $bookingStatus,
                    (bool) $hasBooking,
                    (string) ($status?->name ?? ''),
                    $lead->next_followup_at
                ),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @return list<array<string, mixed>>
     */
    private function mapBookings(Collection $bookings): array
    {
        if ($bookings->isEmpty()) {
            return [];
        }

        $zoneIds = [];
        $areaIds = [];
        $categoryIds = [];
        foreach ($bookings as $booking) {
            if ($id = $this->normalizeId($booking->zone_id)) {
                $zoneIds[] = $id;
            }
            if ($id = $this->normalizeId($booking->area_id)) {
                $areaIds[] = $id;
            }
            if ($id = $this->normalizeId($booking->category_id)) {
                $categoryIds[] = $id;
            }
        }

        $zones = $this->lookupZones($zoneIds);
        $areas = $this->lookupAreas($areaIds);
        $categories = $this->lookupCategories($categoryIds);

        $rows = [];
        foreach ($bookings as $booking) {
            $zoneId = $this->normalizeId($booking->zone_id);
            $areaId = $this->normalizeId($booking->area_id);
            $categoryId = $this->normalizeId($booking->category_id);
            $rows[] = [
                'id' => (string) $booking->id,
                'created_at' => $booking->created_at,
                'zone_key' => $zoneId ?? self::UNSPECIFIED_KEY,
                'zone_label' => $this->labelFrom($zoneId, $zones),
                'area_key' => $areaId ?? self::UNSPECIFIED_KEY,
                'area_label' => $this->labelFrom($areaId, $areas),
                'category_key' => $categoryId ?? self::UNSPECIFIED_KEY,
                'category_label' => $this->labelFrom($categoryId, $categories),
                'status' => $this->normalizeBookingStatus((string) $booking->booking_status),
                'amount' => (float) ($booking->total_booking_amount ?? 0),
            ];
        }

        return $rows;
    }

    public function classifyCustomerStatus(
        string $leadType,
        string $baseType,
        string $bookingStatus,
        bool $hasBooking,
        string $statusName,
        mixed $nextFollowupAt
    ): ?string {
        if ($leadType !== Lead::TYPE_CUSTOMER) {
            return null;
        }

        if ($baseType === 'cancel' || in_array($bookingStatus, ['cancelled', 'canceled'], true)) {
            return 'cancelled';
        }
        if (in_array($baseType, ['completed', 'booked'], true) || $bookingStatus === 'booked' || $hasBooking) {
            return 'booked';
        }
        if ($statusName !== '' && str_contains(strtolower($statusName), 'hold')) {
            return 'hold';
        }
        if ($nextFollowupAt instanceof Carbon && $nextFollowupAt->isFuture()) {
            return 'hold';
        }

        return 'pending';
    }

    /**
     * @param  array<string, array<string, mixed>>  $buckets
     */
    private function touchGeo(array &$buckets, string $key, string $label): void
    {
        if (isset($buckets[$key])) {
            return;
        }

        $buckets[$key] = $this->emptyGeoRow($key, $label);
    }

    /**
     * @param  array<string, array<string, mixed>>  $buckets
     */
    private function touchMatrix(array &$buckets, string $geoKey, string $geoLabel, string $categoryKey, string $categoryLabel): void
    {
        $key = $this->matrixKey($geoKey, $categoryKey);
        if (isset($buckets[$key])) {
            return;
        }

        $row = $this->emptyGeoRow($geoKey, $geoLabel);
        $row['category_key'] = $categoryKey;
        $row['category_label'] = $categoryLabel;
        $buckets[$key] = $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyGeoRow(string $key, string $label): array
    {
        $row = [
            'key' => $key,
            'label' => $label !== '' ? $label : translate('Not_Specified'),
            'leads' => 0,
            'bookings' => 0,
            'booking_completed' => 0,
            'booking_cancelled' => 0,
            'booking_pending' => 0,
            'booking_amount_completed' => 0.0,
            'booking_statuses' => [],
        ];
        foreach (self::LEAD_TYPES as $type) {
            $row[$type] = 0;
        }
        foreach (self::CUSTOMER_STATUSES as $status) {
            $row[$status] = 0;
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function applyBookingToGeo(array &$row, string $group, string $status, float $amount): void
    {
        $row['bookings']++;
        $row['booking_'.$group]++;
        if ($group === 'completed') {
            $row['booking_amount_completed'] += $amount;
        }
        $row['booking_statuses'][$status] = ($row['booking_statuses'][$status] ?? 0) + 1;
    }

    /**
     * @param  array<string, array<string, mixed>>  $buckets
     * @return list<array<string, mixed>>
     */
    private function finalizeGeo(array $buckets): array
    {
        $rows = array_values($buckets);
        usort($rows, function (array $a, array $b) {
            $scoreA = (int) $a['leads'] + (int) $a['bookings'];
            $scoreB = (int) $b['leads'] + (int) $b['bookings'];
            if ($scoreA === $scoreB) {
                return strcasecmp((string) $a['label'], (string) $b['label']);
            }

            return $scoreB <=> $scoreA;
        });

        foreach ($rows as &$row) {
            $row['lead_conversion_rate'] = $this->pct((int) $row['booked'], (int) $row['customer']);
            $row['booking_completion_rate'] = $this->pct((int) $row['booking_completed'], (int) $row['bookings']);
            $row['booking_cancel_rate'] = $this->pct((int) $row['booking_cancelled'], (int) $row['bookings']);
            $row['booking_amount_completed'] = round((float) $row['booking_amount_completed'], 2);
            ksort($row['booking_statuses']);
        }
        unset($row);

        return $rows;
    }

    /**
     * @param  array<string, array<string, mixed>>  $buckets
     * @return list<array<string, mixed>>
     */
    private function finalizeMatrix(array $buckets): array
    {
        $rows = $this->finalizeGeo($buckets);
        usort($rows, function (array $a, array $b) {
            $geo = strcasecmp((string) $a['label'], (string) $b['label']);
            if ($geo !== 0) {
                return $geo;
            }

            return ((int) $b['leads'] + (int) $b['bookings']) <=> ((int) $a['leads'] + (int) $a['bookings']);
        });

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $matrix
     * @return list<array<string, mixed>>
     */
    private function targetingRows(array $matrix): array
    {
        $rows = [];
        foreach ($matrix as $row) {
            if (($row['key'] ?? '') === self::UNSPECIFIED_KEY) {
                continue;
            }
            if (($row['category_key'] ?? '') === self::UNSPECIFIED_KEY) {
                continue;
            }

            $recommendation = $this->recommend($row);
            if ($recommendation === null) {
                continue;
            }

            $rows[] = array_merge($row, ['recommendation' => $recommendation]);
        }

        usort($rows, function (array $a, array $b) {
            $order = ['opportunity' => 0, 'grow' => 1, 'risk' => 2, 'capture' => 3];
            $typeA = $order[$a['recommendation']['type'] ?? ''] ?? 9;
            $typeB = $order[$b['recommendation']['type'] ?? ''] ?? 9;
            if ($typeA !== $typeB) {
                return $typeA <=> $typeB;
            }

            return ((int) $b['leads'] + (int) $b['bookings']) <=> ((int) $a['leads'] + (int) $a['bookings']);
        });

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{type: string, text: string}|null
     */
    public function recommend(array $row): ?array
    {
        $leads = (int) ($row['leads'] ?? 0);
        $customer = (int) ($row['customer'] ?? 0);
        $booked = (int) ($row['booked'] ?? 0);
        $unknown = (int) ($row['unknown'] ?? 0);
        $invalid = (int) ($row['invalid'] ?? 0);
        $bookings = (int) ($row['bookings'] ?? 0);
        $completed = (int) ($row['booking_completed'] ?? 0);
        $cancelled = (int) ($row['booking_cancelled'] ?? 0);
        $conversion = $customer > 0 ? $this->pct($booked, $customer) : 0.0;
        $completion = $bookings > 0 ? $this->pct($completed, $bookings) : 0.0;
        $cancelRate = $bookings > 0 ? $this->pct($cancelled, $bookings) : 0.0;

        if ($leads < 3 && $bookings < 3) {
            return null;
        }

        $geo = (string) ($row['label'] ?? translate('Not_Specified'));
        $category = (string) ($row['category_label'] ?? translate('Not_Specified'));

        if ($customer >= 5 && $conversion < 20.0) {
            return [
                'type' => 'opportunity',
                'text' => sprintf(
                    '%s × %s: %d %s but only %.1f%% %s. %s',
                    $geo,
                    $category,
                    $customer,
                    translate('customer_leads'),
                    $conversion,
                    translate('conversion'),
                    translate('Geographic_target_improve_closing')
                ),
            ];
        }

        if ($completed >= 5 && $completion >= 60.0 && $cancelRate <= 35.0) {
            return [
                'type' => 'grow',
                'text' => sprintf(
                    '%s × %s: %d %s (%.1f%% %s). %s',
                    $geo,
                    $category,
                    $completed,
                    translate('completed'),
                    $completion,
                    translate('completion_rate'),
                    translate('Geographic_target_increase_ads')
                ),
            ];
        }

        if ($bookings >= 5 && $cancelRate >= 40.0) {
            return [
                'type' => 'risk',
                'text' => sprintf(
                    '%s × %s: %.1f%% %s. %s',
                    $geo,
                    $category,
                    $cancelRate,
                    translate('cancellation_rate'),
                    translate('Geographic_target_review_fulfillment')
                ),
            ];
        }

        if ($leads >= 5 && ($unknown + $invalid) >= (int) ceil($leads * 0.4)) {
            return [
                'type' => 'capture',
                'text' => sprintf(
                    '%s × %s: %d/%d %s. %s',
                    $geo,
                    $category,
                    $unknown + $invalid,
                    $leads,
                    translate('Unknown').'/'.translate('Invalid'),
                    translate('Geographic_target_qualify_earlier')
                ),
            ];
        }

        if ($leads >= 5 && $bookings === 0 && $booked === 0) {
            return [
                'type' => 'opportunity',
                'text' => sprintf(
                    '%s × %s: %d %s %s. %s',
                    $geo,
                    $category,
                    $leads,
                    translate('Leads'),
                    translate('with_no_bookings'),
                    translate('Geographic_target_improve_closing')
                ),
            ];
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $areaMatrix
     * @param  list<array<string, mixed>>  $zoneMatrix
     * @return list<array{type: string, text: string}>
     */
    private function buildInsights(array $areaMatrix, array $zoneMatrix, int $missingLeadArea, int $missingBookingArea): array
    {
        $insights = [];
        if ($missingLeadArea > 0 || $missingBookingArea > 0) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf(
                    '%d %s, %d %s. %s',
                    $missingLeadArea,
                    translate('leads_missing_area'),
                    $missingBookingArea,
                    translate('bookings_missing_area'),
                    translate('Geographic_capture_area_hint')
                ),
            ];
        }

        $topArea = $this->targetingRows($areaMatrix)[0] ?? null;
        if ($topArea) {
            $insights[] = [
                'type' => $topArea['recommendation']['type'] === 'grow' ? 'success' : 'info',
                'text' => $topArea['recommendation']['text'],
            ];
        } else {
            $topZone = $this->targetingRows($zoneMatrix)[0] ?? null;
            if ($topZone) {
                $insights[] = [
                    'type' => 'info',
                    'text' => $topZone['recommendation']['text'],
                ];
            }
        }

        return $insights;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{key: string, label: string, total: int, color: string}>
     */
    private function shareChart(array $rows, string $field): array
    {
        $palette = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#fd7e14', '#6f42c1', '#20c997', '#0dcaf0'];
        $chart = [];
        $i = 0;
        foreach ($rows as $row) {
            $total = (int) ($row[$field] ?? 0);
            if ($total <= 0) {
                continue;
            }
            $chart[] = [
                'key' => (string) $row['key'],
                'label' => (string) $row['label'],
                'total' => $total,
                'color' => $palette[$i % count($palette)],
            ];
            $i++;
        }

        return $chart;
    }

    /**
     * @param  list<array{key: string, label: string, total: int, color: string}>  $rows
     * @return list<array{key: string, label: string, total: int, color: string}>
     */
    private function chartRows(array $rows): array
    {
        return array_values(array_filter($rows, fn (array $row) => (int) $row['total'] > 0));
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array{key: string, label: string, total: int, color: string}>
     */
    private function bookingStatusChart(array $counts): array
    {
        $palette = [
            'pending' => '#f6c23e',
            'accepted' => '#4e73df',
            'ongoing' => '#36b9cc',
            'on_hold' => '#fd7e14',
            'pending_cancellation' => '#e83e8c',
            'completed' => '#1cc88a',
            'canceled' => '#e74a3b',
            'cancelled' => '#e74a3b',
            'refunded' => '#858796',
        ];
        $rows = [];
        arsort($counts);
        foreach ($counts as $status => $total) {
            if ($total <= 0) {
                continue;
            }
            $rows[] = [
                'key' => $status,
                'label' => $this->bookingStatusLabel($status),
                'total' => $total,
                'color' => $palette[$status] ?? '#5a5c69',
            ];
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function dayKeys(Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $days = $start->diffInDays($end) + 1;
        if ($days > 62) {
            $keys = [];
            $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
            while ($cursor->lte($end)) {
                $keys[] = $cursor->format('o-\WW');
                $cursor->addWeek();
            }

            return $keys;
        }

        $keys = [];
        foreach (CarbonPeriod::create($start, $end) as $day) {
            $keys[] = $day->format('Y-m-d');
        }

        return $keys;
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    private function displayDayLabels(array $keys): array
    {
        return array_map(function (string $key) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $key)) {
                return Carbon::parse($key)->format('d M');
            }
            if (preg_match('/^(\d{4})-W(\d{2})$/', $key, $m)) {
                return 'W'.$m[2];
            }

            return $key;
        }, $keys);
    }

    /**
     * @param  array<string, true>  $dayKeySet
     */
    private function bucketDay(mixed $value, array $dayKeySet): ?string
    {
        $ymd = $this->dayKey($value);
        if ($ymd === null) {
            return null;
        }
        if (isset($dayKeySet[$ymd])) {
            return $ymd;
        }

        try {
            $week = Carbon::parse($ymd)->startOfWeek(Carbon::MONDAY)->format('o-\WW');
        } catch (\Throwable) {
            return null;
        }

        return isset($dayKeySet[$week]) ? $week : null;
    }

    private function dayKey(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            $date = $value instanceof Carbon ? $value : Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }

        return $date->format('Y-m-d');
    }

    /**
     * @param  array<string, array<string, int>>  $store
     * @param  list<string>  $dayKeys
     */
    private function bumpDailyGeo(array &$store, array $dayKeys, string $geoKey, ?string $day): void
    {
        if ($day === null) {
            return;
        }
        if (! isset($store[$geoKey])) {
            $store[$geoKey] = array_fill_keys($dayKeys, 0);
        }
        if (isset($store[$geoKey][$day])) {
            $store[$geoKey][$day]++;
        }
    }

    /**
     * @param  array<string, array<string, int>>  $leadByGeo
     * @param  array<string, array<string, int>>  $bookingByGeo
     * @param  array<string, string>  $labels
     * @param  list<string>  $dayKeys
     * @return array{lead_series: list<array{key: string, label: string, data: list<int>}>, booking_series: list<array{key: string, label: string, data: list<int>}>}
     */
    private function dailyGeoSeries(array $leadByGeo, array $bookingByGeo, array $labels, array $dayKeys): array
    {
        $keys = array_values(array_unique(array_merge(array_keys($leadByGeo), array_keys($bookingByGeo))));
        $scored = [];
        foreach ($keys as $key) {
            $scored[$key] = array_sum($leadByGeo[$key] ?? []) + array_sum($bookingByGeo[$key] ?? []);
        }
        arsort($scored);

        $zeros = array_fill_keys($dayKeys, 0);
        $leadSeries = [];
        $bookingSeries = [];
        foreach (array_keys($scored) as $key) {
            $label = $labels[$key] ?? translate('Not_Specified');
            $leadSeries[] = [
                'key' => $key,
                'label' => $label,
                'data' => array_values(array_replace($zeros, $leadByGeo[$key] ?? [])),
            ];
            $bookingSeries[] = [
                'key' => $key,
                'label' => $label,
                'data' => array_values(array_replace($zeros, $bookingByGeo[$key] ?? [])),
            ];
        }

        return [
            'lead_series' => $leadSeries,
            'booking_series' => $bookingSeries,
        ];
    }

    private function matrixKey(string $geoKey, string $categoryKey): string
    {
        return $geoKey.'|'.$categoryKey;
    }

    private function normalizeLeadType(string $type): string
    {
        return in_array($type, self::LEAD_TYPES, true) ? $type : Lead::TYPE_UNKNOWN;
    }

    private function normalizeCustomerStatus(mixed $status): ?string
    {
        $value = is_string($status) ? $status : null;
        if ($value === null || ! in_array($value, self::CUSTOMER_STATUSES, true)) {
            return null;
        }

        return $value;
    }

    private function normalizeBookingStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if ($status === 'cancelled') {
            return 'canceled';
        }

        return $status !== '' ? $status : 'pending';
    }

    private function bookingStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => translate('Pending'),
            'accepted' => translate('Accepted'),
            'ongoing' => translate('Ongoing'),
            'on_hold' => translate('On_hold'),
            'pending_cancellation' => translate('Pending_cancellation'),
            'completed' => translate('completed'),
            'canceled', 'cancelled' => translate('Canceled'),
            'refunded' => translate('Refunded'),
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function bookingGroup(string $status): string
    {
        if (in_array($status, ['canceled', 'cancelled', 'refunded'], true)) {
            return 'cancelled';
        }
        if ($status === 'completed') {
            return 'completed';
        }

        return 'pending';
    }

    private function pct(int $part, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($part / $total) * 100, 1);
    }

    private function normalizeId(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === '0' || $value === 0) {
            return null;
        }
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $value;
        }
        if (is_int($value) || is_float($value)) {
            return (string) (int) $value;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function zoneIdsFromData(array $data): array
    {
        $ids = [];
        if (! empty($data['zone_ids']) && is_array($data['zone_ids'])) {
            foreach ($data['zone_ids'] as $zid) {
                if ($id = $this->normalizeId($zid)) {
                    $ids[] = $id;
                }
            }
        }

        return $ids;
    }

    /**
     * @param  list<string>  $ids
     * @return Collection<string, mixed>
     */
    private function lookupZones(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return collect();
        }

        return Zone::withoutGlobalScopes()->whereIn('id', $ids)->get()->keyBy(fn ($row) => (string) $row->id);
    }

    /**
     * @param  list<string>  $ids
     * @return Collection<string, mixed>
     */
    private function lookupAreas(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return collect();
        }

        return CustomerLeadArea::query()->whereIn('id', $ids)->get()->keyBy(fn ($row) => (string) $row->id);
    }

    /**
     * @param  list<string>  $ids
     * @return Collection<string, mixed>
     */
    private function lookupCategories(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return collect();
        }

        return Category::withoutGlobalScopes()->whereIn('id', $ids)->get()->keyBy(fn ($row) => (string) $row->id);
    }

    private function labelFrom(?string $id, Collection $entities): string
    {
        if (! $id) {
            return translate('Not_Specified');
        }
        $name = $entities->get($id)?->name ?? null;
        if ($name === null || trim((string) $name) === '') {
            return translate('Not_Specified');
        }

        return (string) $name;
    }
}
