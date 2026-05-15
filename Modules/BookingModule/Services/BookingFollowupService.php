<?php

namespace Modules\BookingModule\Services;

use Carbon\Carbon;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;

class BookingFollowupService
{
    public function schedule(
        Booking $booking,
        Carbon|string $date,
        string $for = 'customer',
        ?string $reason = null,
        ?int $createdBy = null
    ): BookingFollowup {
        return BookingFollowup::create([
            'booking_id' => $booking->id,
            'date' => Carbon::parse($date)->format('Y-m-d H:i:s'),
            'reason' => $reason,
            'for' => $for,
            'status' => 'scheduled',
            'created_by' => $createdBy ?? auth()->id(),
        ]);
    }
}
