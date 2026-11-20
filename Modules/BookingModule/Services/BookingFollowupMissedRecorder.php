<?php

namespace Modules\BookingModule\Services;

use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;

/**
 * Persist "follow-up missed" for open bookings once the due time has passed.
 * Does not run for completed / canceled / refunded bookings (follow-ups no longer required).
 */
class BookingFollowupMissedRecorder
{
    public function recordOverdueMisses(int $chunkSize = 100): int
    {
        $recorded = 0;

        BookingFollowup::query()
            ->where('status', 'scheduled')
            ->whereNotNull('date')
            ->where('date', '<=', now())
            ->orderBy('id')
            ->chunkById($chunkSize, function ($followups) use (&$recorded) {
                $bookingIds = $followups->pluck('booking_id')->unique()->filter()->values();
                if ($bookingIds->isEmpty()) {
                    return;
                }

                $openBookingIds = Booking::query()
                    ->whereIn('id', $bookingIds)
                    ->get(['id', 'booking_status'])
                    ->filter(fn (Booking $booking) => $booking->requiresMandatoryNextFollowup())
                    ->pluck('id')
                    ->all();

                if ($openBookingIds === []) {
                    return;
                }

                $openLookup = array_fill_keys($openBookingIds, true);

                foreach ($followups as $followup) {
                    if (! isset($openLookup[(string) $followup->booking_id])) {
                        continue;
                    }

                    if (BookingAuditLogger::recordFollowupMissedIfPast(
                        (string) $followup->booking_id,
                        $followup->date,
                        now(),
                        $followup->for
                    )) {
                        $recorded++;
                    }
                }
            });

        return $recorded;
    }
}
