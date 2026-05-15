<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\CategoryManagement\Entities\Category;
use Modules\ZoneManagement\Entities\Zone;

class BookingReportAnalyticsService
{
    private const UNSPECIFIED_KEY = '__unspecified__';

    /**
     * @return array<string, mixed>
     */
    public function build(Builder $baseQuery): array
    {
        $bookings = (clone $baseQuery)->get([
            'id',
            'zone_id',
            'category_id',
            'sub_category_id',
            'booking_status',
            'total_booking_amount',
            'created_at',
            'reopen_disputed_snapshot',
        ]);

        if ($bookings->isEmpty()) {
            return $this->emptyPayload();
        }

        $zoneIds = [];
        $categoryIds = [];
        $subCategoryIds = [];

        foreach ($bookings as $booking) {
            if ($id = $this->normalizeReferenceId($booking->zone_id)) {
                $zoneIds[] = $id;
            }
            if ($id = $this->normalizeReferenceId($booking->category_id)) {
                $categoryIds[] = $id;
            }
            if ($id = $this->normalizeReferenceId($booking->sub_category_id)) {
                $subCategoryIds[] = $id;
            }
        }

        $zones = $zoneIds !== []
            ? Zone::withoutGlobalScopes()->whereIn('id', array_unique($zoneIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();
        $categories = $categoryIds !== []
            ? Category::withoutGlobalScopes()->whereIn('id', array_unique($categoryIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();
        $subCategories = $subCategoryIds !== []
            ? Category::withoutGlobalScopes()->ofType('sub')->whereIn('id', array_unique($subCategoryIds))->get()->keyBy(fn ($row) => (string) $row->id)
            : collect();

        $overall = ['pending' => 0, 'completed' => 0, 'cancelled' => 0];
        $categoryBuckets = [];
        $zoneBuckets = [];
        $subCategoryBuckets = [];
        $completedCategory = [];
        $completedZone = [];
        $completedSubCategory = [];
        $cancelledCategory = [];
        $cancelledZone = [];
        $leadHourCounts = array_fill(0, 24, 0);
        $leadDayCounts = [
            'Mon' => 0, 'Tue' => 0, 'Wed' => 0, 'Thu' => 0, 'Fri' => 0, 'Sat' => 0, 'Sun' => 0,
        ];
        $completedAmount = 0.0;
        $cancelledAmount = 0.0;
        $pendingAmount = 0.0;

        $missingZone = 0;
        $missingCategory = 0;

        foreach ($bookings as $booking) {
            $outcome = $this->classifyOutcome((string) $booking->booking_status);
            $overall[$outcome]++;
            $amount = (float) ($booking->total_booking_amount ?? 0);

            if ($outcome === 'completed') {
                $completedAmount += $amount;
            } elseif ($outcome === 'cancelled') {
                $cancelledAmount += $amount;
            } else {
                $pendingAmount += $amount;
            }

            $zoneId = $this->normalizeReferenceId($booking->zone_id);
            $categoryId = $this->normalizeReferenceId($booking->category_id);
            $subCategoryId = $this->normalizeReferenceId($booking->sub_category_id);

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

            if ($outcome === 'completed') {
                $this->incrementSimple($completedCategory, $categoryDim['key'], $categoryDim['label']);
                $this->incrementSimple($completedZone, $zoneDim['key'], $zoneDim['label']);
                $this->incrementSimple($completedSubCategory, $subCategoryDim['key'], $subCategoryDim['label']);
            } elseif ($outcome === 'cancelled') {
                $this->incrementSimple($cancelledCategory, $categoryDim['key'], $categoryDim['label']);
                $this->incrementSimple($cancelledZone, $zoneDim['key'], $zoneDim['label']);
            }

            $createdAt = $booking->created_at;
            if ($createdAt instanceof Carbon) {
                $leadHourCounts[(int) $createdAt->format('G')]++;
                $leadDayCounts[$createdAt->format('D')] = ($leadDayCounts[$createdAt->format('D')] ?? 0) + 1;
            }
        }

        $total = $bookings->count();
        $completed = $overall['completed'];
        $cancelled = $overall['cancelled'];
        $pending = $overall['pending'];
        $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;
        $cancelRate = $total > 0 ? round(($cancelled / $total) * 100, 1) : 0.0;

        $categoryWise = $this->finalizeBuckets($categoryBuckets, $total);
        $zoneWise = $this->finalizeBuckets($zoneBuckets, $total);
        $subCategoryWise = $this->finalizeBuckets($subCategoryBuckets, $total);

        $insights = $this->buildInsights(
            $total,
            $completed,
            $cancelled,
            $pending,
            $completionRate,
            $cancelRate,
            $completedAmount,
            $cancelledAmount,
            $pendingAmount,
            $missingZone,
            $missingCategory,
            $categoryWise,
            $zoneWise,
            $leadHourCounts
        );

        return [
            'summary' => [
                'total' => $total,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'pending' => $pending,
                'completion_rate' => $completionRate,
                'cancel_rate' => $cancelRate,
                'completed_amount' => round($completedAmount, 2),
                'cancelled_amount' => round($cancelledAmount, 2),
                'pending_amount' => round($pendingAmount, 2),
                'missing_zone' => $missingZone,
                'missing_category' => $missingCategory,
            ],
            'insights' => $insights,
            'outcome_breakdown' => [
                ['label' => translate('completed'), 'total' => $completed, 'color' => '#1cc88a'],
                ['label' => translate('Cancelled'), 'total' => $cancelled, 'color' => '#e74a3b'],
                ['label' => translate('Pending'), 'total' => $pending, 'color' => '#f6c23e'],
            ],
            'category_wise' => $categoryWise,
            'zone_wise' => $zoneWise,
            'subcategory_wise' => $subCategoryWise,
            'completed' => [
                'category_wise' => $this->finalizeSimple($completedCategory),
                'zone_wise' => $this->finalizeSimple($completedZone),
                'subcategory_wise' => $this->finalizeSimple($completedSubCategory),
            ],
            'cancelled' => [
                'category_wise' => $this->finalizeSimple($cancelledCategory),
                'zone_wise' => $this->finalizeSimple($cancelledZone),
            ],
            'booking_created_by_hour' => array_values($leadHourCounts),
            'booking_created_by_hour_labels' => $this->hourLabels(),
            'booking_created_by_day' => array_values($leadDayCounts),
            'booking_created_by_day_labels' => array_keys($leadDayCounts),
        ];
    }

    private function classifyOutcome(string $bookingStatus): string
    {
        $status = strtolower(trim($bookingStatus));

        if (in_array($status, ['canceled', 'cancelled', 'refunded'], true)) {
            return 'cancelled';
        }
        if ($status === 'completed') {
            return 'completed';
        }

        return 'pending';
    }

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
     * @param  array<string, array{label: string, pending: int, completed: int, cancelled: int, total: int}>  $buckets
     */
    private function incrementBucket(array &$buckets, string $key, string $label, string $outcome): void
    {
        if (!isset($buckets[$key])) {
            $buckets[$key] = [
                'label' => $label,
                'pending' => 0,
                'completed' => 0,
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
     * @param  array<string, array{label: string, pending: int, completed: int, cancelled: int, total: int}>  $buckets
     * @return list<array<string, mixed>>
     */
    private function finalizeBuckets(array $buckets, int $grandTotal): array
    {
        $rows = [];
        foreach ($buckets as $row) {
            $total = (int) $row['total'];
            $completed = (int) $row['completed'];
            $rows[] = [
                'label' => $row['label'],
                'total' => $total,
                'completed' => $completed,
                'cancelled' => (int) $row['cancelled'],
                'pending' => (int) $row['pending'],
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0.0,
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
     * @param  array<int, int>  $hourCounts
     * @return list<array{type: string, text: string}>
     */
    private function buildInsights(
        int $total,
        int $completed,
        int $cancelled,
        int $pending,
        float $completionRate,
        float $cancelRate,
        float $completedAmount,
        float $cancelledAmount,
        float $pendingAmount,
        int $missingZone,
        int $missingCategory,
        array $categoryWise,
        array $zoneWise,
        array $hourCounts
    ): array {
        $insights = [];

        $insights[] = [
            'type' => 'info',
            'text' => sprintf(
                '%d %s: %d %s, %d %s, %d %s (%.1f%% %s).',
                $total,
                translate('bookings'),
                $completed,
                translate('completed'),
                $cancelled,
                translate('Cancelled'),
                $pending,
                translate('Pending'),
                $completionRate,
                translate('completion_rate')
            ),
        ];

        if ($completedAmount > 0) {
            $insights[] = [
                'type' => 'success',
                'text' => sprintf(
                    '%s %s %s %s.',
                    translate('Completed'),
                    translate('booking_amount'),
                    with_currency_symbol($completedAmount),
                    translate('in_selected_range')
                ),
            ];
        }

        if ($completionRate >= 50) {
            $insights[] = [
                'type' => 'success',
                'text' => sprintf('%s %.1f%% %s.', translate('Strong'), $completionRate, translate('booking_completion_rate')),
            ];
        } elseif ($completionRate < 25 && $total >= 5) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf('%s %.1f%% %s — %s.', translate('Low'), $completionRate, translate('booking_completion_rate'), translate('Review_booking_funnel_and_cancellations')),
            ];
        }

        if ($cancelRate >= 35 && $cancelled > 0) {
            $insights[] = [
                'type' => 'danger',
                'text' => sprintf(
                    '%s %.1f%% %s (%s %s) — %s.',
                    translate('High'),
                    $cancelRate,
                    translate('cancellation_rate'),
                    with_currency_symbol($cancelledAmount),
                    translate('booking_amount'),
                    translate('Review_pricing_availability_and_handling')
                ),
            ];
        }

        if ($missingCategory > 0 && ($missingCategory / max(1, $total)) >= 0.15) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf('%d %s %s.', $missingCategory, translate('bookings'), translate('missing_service_category_capture_on_intake')),
            ];
        }

        if ($missingZone > 0 && ($missingZone / max(1, $total)) >= 0.15) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf('%d %s %s.', $missingZone, translate('bookings'), translate('missing_zone_capture_for_area_insights')),
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
                    translate('Bookings'),
                    $top['completion_rate'],
                    translate('completed')
                ),
            ];
        }

        if ($zoneWise !== []) {
            $topZone = $zoneWise[0];
            $insights[] = [
                'type' => 'info',
                'text' => sprintf('%s: %s (%d %s).', translate('Top_zone'), $topZone['label'], $topZone['total'], translate('Bookings')),
            ];
        }

        $peakHour = $this->peakHour($hourCounts);
        if ($peakHour !== null) {
            $insights[] = [
                'type' => 'info',
                'text' => sprintf('%s %s %s.', translate('Peak_booking_creation_time'), $peakHour, translate('staff_scheduling_hint')),
            ];
        }

        if ($pending > 0 && $pendingAmount > 0) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf(
                    '%d %s %s (%s %s).',
                    $pending,
                    translate('Pending'),
                    translate('bookings'),
                    with_currency_symbol($pendingAmount),
                    translate('booking_amount')
                ),
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
                'completed' => 0,
                'cancelled' => 0,
                'pending' => 0,
                'completion_rate' => 0,
                'cancel_rate' => 0,
                'completed_amount' => 0,
                'cancelled_amount' => 0,
                'pending_amount' => 0,
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
            'completed' => ['category_wise' => [], 'zone_wise' => [], 'subcategory_wise' => []],
            'cancelled' => ['category_wise' => [], 'zone_wise' => []],
            'booking_created_by_hour' => array_fill(0, 24, 0),
            'booking_created_by_hour_labels' => $this->hourLabels(),
            'booking_created_by_day' => [0, 0, 0, 0, 0, 0, 0],
            'booking_created_by_day_labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        ];
    }
}
