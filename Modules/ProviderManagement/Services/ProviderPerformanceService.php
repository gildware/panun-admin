<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\ProviderManagement\Entities\ProviderIncident;

class ProviderPerformanceService
{
    public const INCIDENT_COMPLAINT = 'COMPLAINT';
    public const INCIDENT_NON_COMPLAINT = 'NON_COMPLAINT';
    public const INCIDENT_POSITIVE_FEEDBACK = 'POSITIVE_FEEDBACK';

    public const ACTION_COMPLETED = 'completed';
    public const ACTION_CANCELLED = 'cancelled';
    public const ACTION_PROVIDER_CHANGED = 'provider_changed';
    public const ACTION_REOPENED = 'reopened';

    public const COMPLAINT_TAGS = [
        'no_show',
        'no_response',
        'late_arrival',
        'bad_behaviour',
        'poor_service',
    ];

    public const NON_COMPLAINT_TAGS = [
        'provider_busy',
        'customer_request',
        'scheduling_issue',
        'no_feedback',
    ];

    public const POSITIVE_FEEDBACK_TAGS = [
        'positive_feedback',
        'successful_job',
    ];

    public const SERIOUS_COMPLAINT_TAGS = [
        'no_response',
        'late_arrival',
        'bad_behaviour',
        'poor_service',
    ];

    public function evaluateAndUpdateProviderPerformanceStatus(string $providerId): void
    {
        // New behavior: we only calculate and suggest actions.
        // Suspension/blacklist remains a manual admin action.
        $this->getAggregatedProviderPerformanceMetrics([$providerId]);
    }

    public function getAggregatedProviderPerformanceMetrics(array $providerIds): Collection
    {
        if (empty($providerIds)) {
            return collect();
        }

        $bookingTotals = $this->terminalBookingCountsByProviderIds($providerIds);
        $incidentTotals = $this->incidentAggregatesByProviderIds($providerIds);

        return collect($providerIds)->mapWithKeys(function ($providerId) use ($bookingTotals, $incidentTotals) {
            $incidents = $incidentTotals[$providerId] ?? [
                'performance_score' => 0,
                'complaints_count' => 0,
                'no_show_count' => 0,
                'late_arrival_count' => 0,
                'poor_service_count' => 0,
                'positive_feedback_count' => 0,
                'reopened_bookings_count' => 0,
            ];

            $totals = $bookingTotals[$providerId] ?? ['total' => 0, 'completed' => 0, 'cancelled' => 0];
            $bookingsCompleted = $totals['completed'];
            $bookingsCancelled = $totals['cancelled'];
            $complaints = $incidents['complaints_count'];
            $noShow = $incidents['no_show_count'];
            $score = $incidents['performance_score'];
            $suggestedAction = $this->suggestProviderAction($score, $complaints, $noShow, $bookingsCancelled);

            return [
                $providerId => (object) [
                    'provider_id' => $providerId,
                    'performance_score' => $score,
                    'bookings_count' => $totals['total'],
                    'bookings_completed_count' => $bookingsCompleted,
                    'bookings_cancelled_count' => $bookingsCancelled,
                    'jobs_completed_count' => $bookingsCompleted,
                    'complaints_count' => $complaints,
                    'no_show_count' => $noShow,
                    'late_arrival_count' => $incidents['late_arrival_count'],
                    'poor_service_count' => $incidents['poor_service_count'],
                    'positive_feedback_count' => $incidents['positive_feedback_count'],
                    'reopened_bookings_count' => $incidents['reopened_bookings_count'],
                    'suggested_action' => $suggestedAction,
                ],
            ];
        });
    }

    /**
     * @param  array<int|string>  $providerIds
     * @return array<string|int, array{total: int, completed: int, cancelled: int}>
     */
    private function terminalBookingCountsByProviderIds(array $providerIds): array
    {
        $defaults = [];
        foreach ($providerIds as $id) {
            $defaults[$id] = ['total' => 0, 'completed' => 0, 'cancelled' => 0];
        }

        $rows = Booking::query()
            ->whereIn('provider_id', $providerIds)
            ->selectRaw(
                'provider_id,
                COUNT(*) as total_count,
                SUM(CASE WHEN booking_status = ? THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN booking_status IN (\'canceled\', \'refunded\') THEN 1 ELSE 0 END) as cancelled_count',
                [self::ACTION_COMPLETED]
            )
            ->groupBy('provider_id')
            ->get();

        foreach ($rows as $row) {
            $pid = $row->provider_id;
            if (array_key_exists($pid, $defaults)) {
                $defaults[$pid] = [
                    'total' => (int) $row->total_count,
                    'completed' => (int) $row->completed_count,
                    'cancelled' => (int) $row->cancelled_count,
                ];
            }
        }

        return $defaults;
    }

    /**
     * Aggregate incident metrics in SQL (same semantics as unique booking_id filters in PHP).
     *
     * @param  array<int|string>  $providerIds
     * @return array<string|int, array{
     *     performance_score: int,
     *     complaints_count: int,
     *     no_show_count: int,
     *     late_arrival_count: int,
     *     poor_service_count: int,
     *     positive_feedback_count: int,
     *     reopened_bookings_count: int
     * }>
     */
    private function incidentAggregatesByProviderIds(array $providerIds): array
    {
        $defaults = [];
        foreach ($providerIds as $id) {
            $defaults[$id] = [
                'performance_score' => 0,
                'complaints_count' => 0,
                'no_show_count' => 0,
                'late_arrival_count' => 0,
                'poor_service_count' => 0,
                'positive_feedback_count' => 0,
                'reopened_bookings_count' => 0,
            ];
        }

        $tagContains = static fn (string $tag): string => "JSON_CONTAINS(COALESCE(tags, JSON_ARRAY()), JSON_QUOTE('{$tag}'))";

        $rows = ProviderIncident::query()
            ->whereIn('provider_id', $providerIds)
            ->groupBy('provider_id')
            ->selectRaw(
                'provider_id,
                COALESCE(SUM(score_delta), 0) as performance_score,
                COUNT(DISTINCT CASE WHEN incident_type = ? THEN booking_id END) as complaints_count,
                COUNT(DISTINCT CASE WHEN '.$tagContains('no_show').' THEN booking_id END) as no_show_count,
                COUNT(DISTINCT CASE WHEN '.$tagContains('late_arrival').' THEN booking_id END) as late_arrival_count,
                COUNT(DISTINCT CASE WHEN '.$tagContains('poor_service').' THEN booking_id END) as poor_service_count,
                COUNT(DISTINCT CASE WHEN incident_type = ? THEN booking_id END) as positive_feedback_count,
                COUNT(DISTINCT CASE WHEN action_type = ? OR '.$tagContains('reopened').' THEN booking_id END) as reopened_bookings_count',
                [self::INCIDENT_COMPLAINT, self::INCIDENT_POSITIVE_FEEDBACK, self::ACTION_REOPENED]
            )
            ->get();

        foreach ($rows as $row) {
            $pid = $row->provider_id;
            if (! array_key_exists($pid, $defaults)) {
                continue;
            }

            $defaults[$pid] = [
                'performance_score' => (int) $row->performance_score,
                'complaints_count' => (int) $row->complaints_count,
                'no_show_count' => (int) $row->no_show_count,
                'late_arrival_count' => (int) $row->late_arrival_count,
                'poor_service_count' => (int) $row->poor_service_count,
                'positive_feedback_count' => (int) $row->positive_feedback_count,
                'reopened_bookings_count' => (int) $row->reopened_bookings_count,
            ];
        }

        return $defaults;
    }

    public function suggestProviderAction(int $score, int $complaints, int $noShow, int $cancelled): string
    {
        if ($noShow >= 2 || $complaints >= 5 || $score <= -50) {
            return 'manual_blacklist_review';
        }

        if ($noShow >= 1 || $complaints >= 3 || $cancelled >= 5 || $score <= -20) {
            return 'manual_suspend_review';
        }

        if ($complaints >= 2 || $score < 0) {
            return 'monitor_closely';
        }

        return 'keep_active';
    }
}
