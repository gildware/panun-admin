<?php

namespace Modules\BookingModule\Observers;

use Illuminate\Support\Facades\Log;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;
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

        $changes = $booking->getChanges();
        if (! array_key_exists('booking_status', $changes)) {
            return;
        }

        $previousStatus = (string) ($before['booking_status'] ?? '');
        $newStatus = (string) ($booking->booking_status ?? '');
        if ($previousStatus === $newStatus) {
            return;
        }

        // Repeat-series bookings use sendBookingRepeatStatusChange from repeat save paths; parent sync
        // would duplicate those notifications if we also fired here.
        if (BookingRepeat::query()->where('booking_id', $booking->id)->exists()) {
            return;
        }

        if (! class_exists(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)) {
            return;
        }

        try {
            app(\Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService::class)
                ->sendBookingStatusChange($booking, $previousStatus);
        } catch (\Throwable $e) {
            Log::warning('Booking WhatsApp status notification failed', [
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function deleting(Booking $booking): void
    {
        $oid = spl_object_id($booking);
        unset(self::$originals[$oid]);
        // Must run before the row is removed: booking_change_logs.booking_id FK references bookings.id.
        BookingAuditLogger::logBookingDeleted($booking);
    }
}
