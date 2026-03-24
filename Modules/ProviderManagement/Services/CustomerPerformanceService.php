<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\ProviderManagement\Entities\CustomerIncident;

class CustomerPerformanceService
{
    public function getAggregatedCustomerPerformanceMetrics(array $customerIds): Collection
    {
        if (empty($customerIds)) {
            return collect();
        }

        $bookingTotals = $this->terminalBookingCountsByCustomerIds($customerIds);

        $incidents = CustomerIncident::query()
            ->whereIn('customer_id', $customerIds)
            ->get(['customer_id', 'booking_id', 'action_type', 'incident_type', 'tags', 'score_delta']);

        $incidentGroups = $incidents->groupBy('customer_id');

        return collect($customerIds)->mapWithKeys(function ($customerId) use ($bookingTotals, $incidentGroups) {
            $rows = $incidentGroups->get($customerId, collect());

            $complaints = $rows
                ->filter(fn ($row) => $row->incident_type === ProviderPerformanceService::INCIDENT_COMPLAINT)
                ->pluck('booking_id')
                ->unique()
                ->count();

            $positive = $rows
                ->filter(fn ($row) => $row->incident_type === ProviderPerformanceService::INCIDENT_POSITIVE_FEEDBACK)
                ->pluck('booking_id')
                ->unique()
                ->count();

            $score = (int) $rows->sum(fn ($row) => (int) ($row->score_delta ?? 0));

            $totals = $bookingTotals[$customerId] ?? ['completed' => 0, 'cancelled' => 0];
            $bookingsCompleted = $totals['completed'];
            $bookingsCancelled = $totals['cancelled'];
            $suggestedAction = $this->suggestCustomerAction($score, $complaints, $bookingsCancelled);

            return [
                $customerId => (object) [
                    'customer_id' => $customerId,
                    'performance_score' => $score,
                    'bookings_completed_count' => $bookingsCompleted,
                    'bookings_cancelled_count' => $bookingsCancelled,
                    'complaints_count' => $complaints,
                    'positive_feedback_count' => $positive,
                    'suggested_action' => $suggestedAction,
                ],
            ];
        });
    }

    /**
     * Completed / cancelled counts from bookings table (not feedback incidents).
     * Cancelled matches BookingScopes::ofBookingStatus('canceled'): canceled + refunded.
     *
     * @param  array<int|string>  $customerIds
     * @return array<string|int, array{completed: int, cancelled: int}>
     */
    private function terminalBookingCountsByCustomerIds(array $customerIds): array
    {
        $defaults = [];
        foreach ($customerIds as $id) {
            $defaults[$id] = ['completed' => 0, 'cancelled' => 0];
        }

        $rows = Booking::query()
            ->whereIn('customer_id', $customerIds)
            ->selectRaw(
                'customer_id, SUM(CASE WHEN booking_status = ? THEN 1 ELSE 0 END) as completed_count, SUM(CASE WHEN booking_status IN (\'canceled\', \'refunded\') THEN 1 ELSE 0 END) as cancelled_count',
                [ProviderPerformanceService::ACTION_COMPLETED]
            )
            ->groupBy('customer_id')
            ->get();

        foreach ($rows as $row) {
            $cid = $row->customer_id;
            if (array_key_exists($cid, $defaults)) {
                $defaults[$cid] = [
                    'completed' => (int) $row->completed_count,
                    'cancelled' => (int) $row->cancelled_count,
                ];
            }
        }

        return $defaults;
    }

    public function suggestCustomerAction(int $score, int $complaints, int $cancelled): string
    {
        if ($complaints >= 4 || $cancelled >= 4 || $score <= -40) {
            return 'manual_blacklist_review';
        }

        if ($complaints >= 2 || $cancelled >= 2 || $score <= -15) {
            return 'manual_suspend_review';
        }

        if ($score < 0) {
            return 'monitor_closely';
        }

        return 'good_customer';
    }
}

