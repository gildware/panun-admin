<?php

namespace Modules\LeadManagement\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\ZoneManagement\Entities\Zone;

class CustomerLeadReportAnalyticsService
{
    /** Single bucket for missing or unresolvable category / zone / sub-category / reason. */
    private const UNSPECIFIED_KEY = '__unspecified__';

    /**
     * @return array<string, mixed>
     */
    public function build(Builder $baseQuery, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $leads = (clone $baseQuery)
            ->where('lead_type', Lead::TYPE_CUSTOMER)
            ->get(['id', 'date_time_of_lead_received', 'source_id', 'ad_source_id']);

        if ($leads->isEmpty()) {
            return $this->emptyPayload();
        }

        $leadIds = $leads->pluck('id')->all();
        $leadsById = $leads->keyBy('id');

        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->where('type', Lead::TYPE_CUSTOMER)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        $bookingsByLead = Booking::query()
            ->whereIn('lead_id', $leadIds)
            ->orderByDesc('created_at')
            ->get(['id', 'lead_id', 'readable_id', 'created_at'])
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        $statusIds = [];
        $zoneIds = [];
        $categoryIds = [];
        $subCategoryIds = [];
        $cancelReasonIds = [];

        foreach ($histories as $history) {
            $data = is_array($history->data) ? $history->data : [];
            if ($id = $this->normalizeReferenceId($data['customer_lead_status_id'] ?? null)) {
                $statusIds[] = $id;
            }
            if ($id = $this->normalizeReferenceId($data['zone_id'] ?? null)) {
                $zoneIds[] = $id;
            }
            if ($id = $this->normalizeReferenceId($data['service_category'] ?? null)) {
                $categoryIds[] = $id;
            }
            if ($id = $this->normalizeReferenceId($data['service_subcategory'] ?? null)) {
                $subCategoryIds[] = $id;
            }
            if ($id = $this->normalizeReferenceId($data['cancellation_reason_id'] ?? null)) {
                $cancelReasonIds[] = $id;
            }
        }

        $statuses = $statusIds !== []
            ? CustomerLeadStatus::whereIn('id', array_unique($statusIds))->get()->keyBy(fn ($row) => (string) $row->id)
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
            ? LeadCancellationReason::whereIn('id', array_unique($cancelReasonIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();

        $bucketKeys = ['pending', 'booked', 'cancelled'];
        $overall = array_fill_keys($bucketKeys, 0);
        $categoryBuckets = [];
        $zoneBuckets = [];
        $subCategoryBuckets = [];
        $bookedCategory = [];
        $bookedZone = [];
        $bookedSubCategory = [];
        $cancelledCategory = [];
        $cancelledZone = [];
        $cancelReasonCounts = [];
        $leadHourCounts = array_fill(0, 24, 0);
        $leadDayCounts = [
            'Mon' => 0, 'Tue' => 0, 'Wed' => 0, 'Thu' => 0, 'Fri' => 0, 'Sat' => 0, 'Sun' => 0,
        ];
        $bookingHourCounts = array_fill(0, 24, 0);
        $bookingDayCounts = array_fill_keys(array_keys($leadDayCounts), 0);
        $bookingDailyRaw = [];

        $missingZone = 0;
        $missingCategory = 0;

        foreach ($leads as $lead) {
            $history = $histories->get($lead->id);
            $data = ($history && is_array($history->data)) ? $history->data : [];
            $statusId = $this->normalizeReferenceId($data['customer_lead_status_id'] ?? null);
            $status = $statusId ? $statuses->get($statusId) : null;
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
            $bookingStatus = strtolower((string) ($data['booking_status'] ?? ''));
            $booking = $bookingsByLead->get($lead->id);
            if (!$booking && ($bookingId = $this->normalizeReferenceId($data['booking_id'] ?? null))) {
                $booking = Booking::find($bookingId);
            }

            $outcome = $this->classifyOutcome($baseType, $bookingStatus, $booking !== null);
            $overall[$outcome]++;

            $zoneId = $this->normalizeReferenceId($data['zone_id'] ?? null);
            $categoryId = $this->normalizeReferenceId($data['service_category'] ?? null);
            $subCategoryId = $this->normalizeReferenceId($data['service_subcategory'] ?? null);

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

            if ($outcome === 'booked') {
                $this->incrementSimple($bookedCategory, $categoryDim['key'], $categoryDim['label']);
                $this->incrementSimple($bookedZone, $zoneDim['key'], $zoneDim['label']);
                $this->incrementSimple($bookedSubCategory, $subCategoryDim['key'], $subCategoryDim['label']);
            } elseif ($outcome === 'cancelled') {
                $this->incrementSimple($cancelledCategory, $categoryDim['key'], $categoryDim['label']);
                $this->incrementSimple($cancelledZone, $zoneDim['key'], $zoneDim['label']);
                $reasonId = $this->normalizeReferenceId($data['cancellation_reason_id'] ?? null);
                $reasonDim = $this->resolveDimension($reasonId, $cancelReasons);
                $this->incrementSimple($cancelReasonCounts, $reasonDim['key'], $reasonDim['label']);
            }

            $receivedAt = $lead->date_time_of_lead_received;
            if ($receivedAt instanceof Carbon) {
                $leadHourCounts[(int) $receivedAt->format('G')]++;
                $leadDayCounts[$receivedAt->format('D')] = ($leadDayCounts[$receivedAt->format('D')] ?? 0) + 1;
            }

            if ($outcome === 'booked' && $booking?->created_at) {
                $bookedAt = $booking->created_at instanceof Carbon
                    ? $booking->created_at
                    : Carbon::parse($booking->created_at);
                $bookingHourCounts[(int) $bookedAt->format('G')]++;
                $dayKey = $bookedAt->format('D');
                $bookingDayCounts[$dayKey] = ($bookingDayCounts[$dayKey] ?? 0) + 1;
                $dayStr = $bookedAt->toDateString();
                $bookingDailyRaw[$dayStr] = ($bookingDailyRaw[$dayStr] ?? 0) + 1;
            }
        }

        $total = $leads->count();
        $booked = $overall['booked'];
        $cancelled = $overall['cancelled'];
        $pending = $overall['pending'];
        $conversionRate = $total > 0 ? round(($booked / $total) * 100, 1) : 0.0;
        $cancelRate = $total > 0 ? round(($cancelled / $total) * 100, 1) : 0.0;

        $bookingTimeline = [];
        $bookingPerDay = [];
        if ($dateFrom && $dateTo) {
            $period = CarbonPeriod::create($dateFrom->copy()->startOfDay(), $dateTo->copy()->startOfDay());
            foreach ($period as $date) {
                $key = $date->toDateString();
                $bookingTimeline[] = $date->format('d M');
                $bookingPerDay[] = (int) ($bookingDailyRaw[$key] ?? 0);
            }
        }

        $categoryWise = $this->finalizeBuckets($categoryBuckets, $total);
        $zoneWise = $this->finalizeBuckets($zoneBuckets, $total);
        $subCategoryWise = $this->finalizeBuckets($subCategoryBuckets, $total);

        $insights = $this->buildInsights(
            $total,
            $booked,
            $cancelled,
            $pending,
            $conversionRate,
            $cancelRate,
            $missingZone,
            $missingCategory,
            $categoryWise,
            $zoneWise,
            $leadHourCounts,
            $bookingHourCounts,
            $cancelReasonCounts
        );

        return [
            'summary' => [
                'total' => $total,
                'booked' => $booked,
                'cancelled' => $cancelled,
                'pending' => $pending,
                'conversion_rate' => $conversionRate,
                'cancel_rate' => $cancelRate,
                'missing_zone' => $missingZone,
                'missing_category' => $missingCategory,
            ],
            'insights' => $insights,
            'outcome_breakdown' => [
                ['label' => translate('Booked'), 'total' => $booked, 'color' => '#1cc88a'],
                ['label' => translate('Cancelled'), 'total' => $cancelled, 'color' => '#e74a3b'],
                ['label' => translate('Pending'), 'total' => $pending, 'color' => '#f6c23e'],
            ],
            'category_wise' => $categoryWise,
            'zone_wise' => $zoneWise,
            'subcategory_wise' => $subCategoryWise,
            'booked' => [
                'category_wise' => $this->finalizeSimple($bookedCategory),
                'zone_wise' => $this->finalizeSimple($bookedZone),
                'subcategory_wise' => $this->finalizeSimple($bookedSubCategory),
            ],
            'cancelled' => [
                'category_wise' => $this->finalizeSimple($cancelledCategory),
                'zone_wise' => $this->finalizeSimple($cancelledZone),
                'reasons' => $this->finalizeSimple($cancelReasonCounts),
            ],
            'lead_received_by_hour' => array_values($leadHourCounts),
            'lead_received_by_hour_labels' => $this->hourLabels(),
            'lead_received_by_day' => array_values($leadDayCounts),
            'lead_received_by_day_labels' => array_keys($leadDayCounts),
            'booking_by_hour' => array_values($bookingHourCounts),
            'booking_by_hour_labels' => $this->hourLabels(),
            'booking_timeline' => $bookingTimeline,
            'booking_per_day' => $bookingPerDay,
        ];
    }

    private function classifyOutcome(string $baseType, string $bookingStatus, bool $hasBooking): string
    {
        if ($baseType === 'cancel' || $bookingStatus === 'cancelled') {
            return 'cancelled';
        }
        if (in_array($baseType, ['completed', 'booked'], true) || $bookingStatus === 'booked' || $hasBooking) {
            return 'booked';
        }

        return 'pending';
    }

    /**
     * Preserve UUID / numeric IDs from JSON history (never cast zones or categories to int).
     */
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
     * Merge empty IDs and orphaned lookups into one "Not specified" bucket (avoids duplicate Unknown rows).
     *
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
     * @param  array<string, array{label: string, pending: int, booked: int, cancelled: int, total: int}>  $buckets
     */
    private function incrementBucket(array &$buckets, string $key, string $label, string $outcome): void
    {
        if (!isset($buckets[$key])) {
            $buckets[$key] = [
                'label' => $label,
                'pending' => 0,
                'booked' => 0,
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
            $bucket[$key] = ['label' => $label, 'total' => 0];
        }
        $bucket[$key]['total']++;
    }

    /**
     * @param  array<string, array{label: string, pending: int, booked: int, cancelled: int, total: int}>  $buckets
     * @return list<array<string, mixed>>
     */
    private function finalizeBuckets(array $buckets, int $grandTotal): array
    {
        $rows = [];
        foreach ($buckets as $row) {
            $total = (int) $row['total'];
            $booked = (int) $row['booked'];
            $rows[] = [
                'label' => $row['label'],
                'total' => $total,
                'booked' => $booked,
                'cancelled' => (int) $row['cancelled'],
                'pending' => (int) $row['pending'],
                'conversion_rate' => $total > 0 ? round(($booked / $total) * 100, 1) : 0.0,
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
     * @param  array<int, int>  $bookingHourCounts
     * @param  array<string, array{label: string, total: int}>  $cancelReasonCounts
     * @return list<array{type: string, text: string}>
     */
    private function buildInsights(
        int $total,
        int $booked,
        int $cancelled,
        int $pending,
        float $conversionRate,
        float $cancelRate,
        int $missingZone,
        int $missingCategory,
        array $categoryWise,
        array $zoneWise,
        array $leadHourCounts,
        array $bookingHourCounts,
        array $cancelReasonCounts
    ): array {
        $insights = [];

        $insights[] = [
            'type' => 'info',
            'text' => sprintf(
                '%d %s: %d %s, %d %s, %d %s (%.1f%% %s).',
                $total,
                translate('customer_leads'),
                $booked,
                translate('Booked'),
                $cancelled,
                translate('Cancelled'),
                $pending,
                translate('Pending'),
                $conversionRate,
                translate('conversion')
            ),
        ];

        if ($conversionRate >= 50) {
            $insights[] = [
                'type' => 'success',
                'text' => sprintf('%s %.1f%% %s.', translate('Strong'), $conversionRate, translate('booking_conversion_rate')),
            ];
        } elseif ($conversionRate < 25 && $total >= 5) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf('%s %.1f%% %s — %s.', translate('Low'), $conversionRate, translate('booking_conversion_rate'), translate('Review_followup_and_qualification')),
            ];
        }

        if ($cancelRate >= 35 && $cancelled > 0) {
            $insights[] = [
                'type' => 'danger',
                'text' => sprintf('%s %.1f%% %s — %s.', translate('High'), $cancelRate, translate('cancellation_rate'), translate('Review_pricing_availability_and_handling')),
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
                    $top['conversion_rate'],
                    translate('booked')
                ),
            ];
            $worst = null;
            foreach ($categoryWise as $row) {
                if (($row['total'] ?? 0) >= 3 && ($worst === null || ($row['conversion_rate'] ?? 100) < ($worst['conversion_rate'] ?? 100))) {
                    $worst = $row;
                }
            }
            if ($worst && ($worst['conversion_rate'] ?? 0) < 20 && ($worst['label'] ?? '') !== ($top['label'] ?? '')) {
                $insights[] = [
                    'type' => 'warning',
                    'text' => sprintf(
                        '%s %s (%.1f%% %s) — %s.',
                        translate('Weak_category'),
                        $worst['label'],
                        $worst['conversion_rate'],
                        translate('booked'),
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
                'text' => sprintf('%s %s %s.', translate('Peak_lead_intake_time'), $peakLeadHour, translate('staff_scheduling_hint')),
            ];
        }

        $peakBookingHour = $this->peakHour($bookingHourCounts);
        if ($peakBookingHour !== null && $booked > 0) {
            $insights[] = [
                'type' => 'info',
                'text' => sprintf('%s %s %s.', translate('Peak_booking_time'), $peakBookingHour, translate('align_confirmation_workflow')),
            ];
        }

        if ($cancelReasonCounts !== []) {
            $reasons = $this->finalizeSimple($cancelReasonCounts);
            $insights[] = [
                'type' => 'danger',
                'text' => sprintf('%s: %s.', translate('Top_cancellation_reason'), $reasons[0]['label']),
            ];
        }

        if ($pending > 0 && $pending >= ($booked + $cancelled)) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf('%d %s %s.', $pending, translate('Pending'), translate('leads_need_followup_to_convert')),
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
                'booked' => 0,
                'cancelled' => 0,
                'pending' => 0,
                'conversion_rate' => 0,
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
            'booked' => ['category_wise' => [], 'zone_wise' => [], 'subcategory_wise' => []],
            'cancelled' => ['category_wise' => [], 'zone_wise' => [], 'reasons' => []],
            'lead_received_by_hour' => array_fill(0, 24, 0),
            'lead_received_by_hour_labels' => $this->hourLabels(),
            'lead_received_by_day' => [0, 0, 0, 0, 0, 0, 0],
            'lead_received_by_day_labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'booking_by_hour' => array_fill(0, 24, 0),
            'booking_by_hour_labels' => $this->hourLabels(),
            'booking_timeline' => [],
            'booking_per_day' => [],
        ];
    }
}
