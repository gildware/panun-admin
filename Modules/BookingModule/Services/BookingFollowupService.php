<?php

namespace Modules\BookingModule\Services;

use Carbon\Carbon;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;

class BookingFollowupService
{
    public const SUPERSEDED_NOTE = 'Superseded by newer follow-up';

    public const BOOKING_CLOSED_NOTE = 'Booking closed — follow-up cancelled';

    /**
     * Cancel open scheduled follow-ups for one party on a booking.
     */
    public function cancelScheduledForParty(
        Booking $booking,
        string $for,
        string $reason = self::SUPERSEDED_NOTE
    ): int {
        $rows = BookingFollowup::query()
            ->where('booking_id', $booking->id)
            ->where('for', $for)
            ->where('status', 'scheduled')
            ->get();

        foreach ($rows as $row) {
            $this->markCancelled($row, $reason);
        }

        return $rows->count();
    }

    /**
     * Cancel all open scheduled follow-ups on a booking (e.g. completed / canceled).
     */
    public function cancelAllScheduled(
        Booking $booking,
        string $reason = self::BOOKING_CLOSED_NOTE
    ): int {
        $rows = BookingFollowup::query()
            ->where('booking_id', $booking->id)
            ->where('status', 'scheduled')
            ->get();

        foreach ($rows as $row) {
            $this->markCancelled($row, $reason);
        }

        return $rows->count();
    }

    public function schedule(
        Booking $booking,
        Carbon|string $date,
        string $for = 'customer',
        ?string $reason = null,
        ?int $createdBy = null,
        ?string $urgency = null
    ): BookingFollowup {
        $this->cancelScheduledForParty($booking, $for);

        return BookingFollowup::create([
            'booking_id' => $booking->id,
            'date' => Carbon::parse($date)->format('Y-m-d H:i:s'),
            'reason' => $reason,
            'for' => $for,
            'status' => 'scheduled',
            'urgency' => $urgency ?? BookingFollowup::URGENCY_MEDIUM,
            'created_by' => $createdBy ?? auth()->id(),
        ]);
    }

    private function markCancelled(BookingFollowup $row, string $reason): void
    {
        $remarks = trim((string) ($row->remarks ?? ''));

        $row->update([
            'status' => 'cancelled',
            'remarks' => $remarks === '' ? $reason : $remarks.' | '.$reason,
        ]);
    }
}
