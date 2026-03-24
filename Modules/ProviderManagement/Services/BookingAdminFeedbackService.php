<?php

namespace Modules\ProviderManagement\Services;

use Modules\BookingModule\Entities\Booking;
use Modules\ProviderManagement\Entities\CustomerIncident;
use Modules\ProviderManagement\Entities\ProviderIncident;

class BookingAdminFeedbackService
{
    public function isTerminalBooking(Booking $booking): bool
    {
        return in_array($booking->booking_status ?? '', ['completed', 'canceled'], true);
    }

    public function expectedProviderActionType(Booking $booking): ?string
    {
        return match ($booking->booking_status ?? '') {
            'completed' => ProviderPerformanceService::ACTION_COMPLETED,
            'canceled' => ProviderPerformanceService::ACTION_CANCELLED,
            default => null,
        };
    }

    public function providerFeedbackResolved(Booking $booking): bool
    {
        if (!$this->isTerminalBooking($booking)) {
            return true;
        }

        if (!empty($booking->admin_provider_feedback_skipped_at)) {
            return true;
        }

        if (empty($booking->provider_id)) {
            return true;
        }

        $expected = $this->expectedProviderActionType($booking);
        if ($expected === null) {
            return true;
        }

        return ProviderIncident::query()
            ->where('booking_id', $booking->id)
            ->where('provider_id', $booking->provider_id)
            ->where(function ($q) use ($expected) {
                $q->where('action_type', $expected);
                if ($expected === ProviderPerformanceService::ACTION_CANCELLED) {
                    $q->orWhere('action_type', 'canceled');
                }
            })
            ->exists();
    }

    public function customerFeedbackResolved(Booking $booking): bool
    {
        if (!$this->isTerminalBooking($booking)) {
            return true;
        }

        if (!empty($booking->admin_customer_feedback_skipped_at)) {
            return true;
        }

        if (empty($booking->customer_id)) {
            return true;
        }

        return CustomerIncident::query()
            ->where('booking_id', $booking->id)
            ->exists();
    }
}
