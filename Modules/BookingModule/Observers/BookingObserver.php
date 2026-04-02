<?php

namespace Modules\BookingModule\Observers;

use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Services\BookingAuditLogger;

class BookingObserver
{
    /** @var array<int, array<string, mixed>> */
    private static array $originals = [];

    public function updating(Booking $booking): void
    {
        self::$originals[spl_object_id($booking)] = $booking->getOriginal();
    }

    public function created(Booking $booking): void
    {
        BookingAuditLogger::logBookingCreated($booking);
    }

    public function updated(Booking $booking): void
    {
        $oid = spl_object_id($booking);
        $before = self::$originals[$oid] ?? [];
        unset(self::$originals[$oid]);
        BookingAuditLogger::logBookingUpdatedFromDiff($booking, $before, $booking->getChanges());
    }

    public function deleting(Booking $booking): void
    {
        $oid = spl_object_id($booking);
        unset(self::$originals[$oid]);
        // Must run before the row is removed: booking_change_logs.booking_id FK references bookings.id.
        BookingAuditLogger::logBookingDeleted($booking);
    }
}
