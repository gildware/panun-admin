<?php

namespace Modules\ProviderManagement\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\ProviderManagement\Entities\CustomerIncident;
use Modules\UserManagement\Entities\User;

/**
 * When a booking is first classified as loss-making (scaled-to-payments settlement),
 * the customer is auto-suspended (same window as manual performance suspend) and
 * a performance incident applies a -100 score delta.
 */
class CustomerLossMakingSettlementPenaltyService
{
    public const LOSS_MAKING_SCORE_DELTA = -100;

    private const AUTO_NOTES = 'Auto: loss-making (scaled-to-payments) settlement.';

    public function applyWhenBookingBecomesLossMaking(Booking $booking): void
    {
        if (! $booking->exists || ! $booking->customer_id) {
            return;
        }

        if (CustomerIncident::query()
            ->where('booking_id', $booking->id)
            ->where('customer_id', $booking->customer_id)
            ->where('notes', self::AUTO_NOTES)
            ->exists()) {
            return;
        }

        $customer = User::query()->inCustomerDirectory()->find($booking->customer_id);
        if (! $customer) {
            return;
        }

        $status = (string) ($booking->booking_status ?? '');
        $actionType = in_array($status, ['canceled', 'cancelled', 'refunded'], true)
            ? ProviderPerformanceService::ACTION_CANCELLED
            : ProviderPerformanceService::ACTION_COMPLETED;

        DB::transaction(function () use ($booking, $customer, $actionType) {
            CustomerIncident::query()->create([
                'customer_id' => $booking->customer_id,
                'booking_id' => $booking->id,
                'action_type' => $actionType,
                'incident_type' => ProviderPerformanceService::INCIDENT_NON_COMPLAINT,
                'tags' => ['loss_making_booking'],
                'score_delta' => self::LOSS_MAKING_SCORE_DELTA,
                'notes' => self::AUTO_NOTES,
                'created_by' => auth()->id(),
            ]);

            if (($customer->manual_performance_status ?? '') === 'blacklisted') {
                return;
            }

            $customer->manual_performance_status = 'suspended';
            $customer->performance_suspended_until = Carbon::now()->addDays(30);
            $customer->is_active = 1;
            $customer->save();

            $customer->tokens()->update(['revoked' => true]);
        });
    }
}
