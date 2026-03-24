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

        $incidents = ProviderIncident::query()
            ->whereIn('provider_id', $providerIds)
            ->get(['provider_id', 'booking_id', 'action_type', 'incident_type', 'tags', 'score_delta']);

        $incidentGroups = $incidents->groupBy('provider_id');

        return collect($providerIds)->mapWithKeys(function ($providerId) use ($bookingTotals, $incidentGroups) {
            $rows = $incidentGroups->get($providerId, collect());

            $complaints = $rows
                ->filter(fn ($row) => $row->incident_type === self::INCIDENT_COMPLAINT)
                ->pluck('booking_id')
                ->unique()
                ->count();

            $noShow = $rows
                ->filter(fn ($row) => in_array('no_show', (array) ($row->tags ?? []), true))
                ->pluck('booking_id')
                ->unique()
                ->count();

            $lateArrival = $rows
                ->filter(fn ($row) => in_array('late_arrival', (array) ($row->tags ?? []), true))
                ->pluck('booking_id')
                ->unique()
                ->count();

            $poorService = $rows
                ->filter(fn ($row) => in_array('poor_service', (array) ($row->tags ?? []), true))
                ->pluck('booking_id')
                ->unique()
                ->count();

            $positiveFeedback = $rows
                ->filter(fn ($row) => $row->incident_type === self::INCIDENT_POSITIVE_FEEDBACK)
                ->pluck('booking_id')
                ->unique()
                ->count();

            $score = (int) $rows->sum(fn ($row) => (int) ($row->score_delta ?? 0));

            $totals = $bookingTotals[$providerId] ?? ['completed' => 0, 'cancelled' => 0];
            $bookingsCompleted = $totals['completed'];
            $bookingsCancelled = $totals['cancelled'];
            $suggestedAction = $this->suggestProviderAction($score, $complaints, $noShow, $bookingsCancelled);

            return [
                $providerId => (object) [
                    'provider_id' => $providerId,
                    'performance_score' => $score,
                    'bookings_completed_count' => $bookingsCompleted,
                    'bookings_cancelled_count' => $bookingsCancelled,
                    'jobs_completed_count' => $bookingsCompleted,
                    'complaints_count' => $complaints,
                    'no_show_count' => $noShow,
                    'late_arrival_count' => $lateArrival,
                    'poor_service_count' => $poorService,
                    'positive_feedback_count' => $positiveFeedback,
                    'suggested_action' => $suggestedAction,
                ],
            ];
        });
    }

    /**
     * @param  array<int|string>  $providerIds
     * @return array<string|int, array{completed: int, cancelled: int}>
     */
    private function terminalBookingCountsByProviderIds(array $providerIds): array
    {
        $defaults = [];
        foreach ($providerIds as $id) {
            $defaults[$id] = ['completed' => 0, 'cancelled' => 0];
        }

        $rows = Booking::query()
            ->whereIn('provider_id', $providerIds)
            ->selectRaw(
                'provider_id, SUM(CASE WHEN booking_status = ? THEN 1 ELSE 0 END) as completed_count, SUM(CASE WHEN booking_status IN (\'canceled\', \'refunded\') THEN 1 ELSE 0 END) as cancelled_count',
                [self::ACTION_COMPLETED]
            )
            ->groupBy('provider_id')
            ->get();

        foreach ($rows as $row) {
            $pid = $row->provider_id;
            if (array_key_exists($pid, $defaults)) {
                $defaults[$pid] = [
                    'completed' => (int) $row->completed_count,
                    'cancelled' => (int) $row->cancelled_count,
                ];
            }
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

