<?php

namespace Modules\BookingModule\Observers;

use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingExtraService;
use Modules\BookingModule\Services\BookingAuditLogger;

class BookingExtraServiceObserver
{
    /** @var array<int, array<string, mixed>> */
    private static array $originals = [];

    public function updating(BookingExtraService $row): void
    {
        self::$originals[spl_object_id($row)] = $row->getOriginal();
    }

    public function created(BookingExtraService $row): void
    {
        BookingAuditLogger::logBookingExtraServiceChange('created', $row, null);
        self::resyncParentBookingCommission($row);
    }

    public function updated(BookingExtraService $row): void
    {
        $oid = spl_object_id($row);
        $before = self::$originals[$oid] ?? [];
        unset(self::$originals[$oid]);
        $changes = $row->getChanges();
        unset($changes['updated_at']);
        $pairs = [];
        foreach ($changes as $key => $newRaw) {
            $pairs[$key] = [
                'old' => array_key_exists($key, $before) ? $before[$key] : null,
                'new' => $newRaw,
            ];
        }
        BookingAuditLogger::logBookingExtraServiceChange('updated', $row, $pairs);
        self::resyncParentBookingCommission($row);
    }

    public function deleted(BookingExtraService $row): void
    {
        $oid = spl_object_id($row);
        unset(self::$originals[$oid]);
        BookingAuditLogger::logBookingExtraServiceChange('deleted', $row, null);
        self::resyncParentBookingCommission($row);
    }

    private static function resyncParentBookingCommission(BookingExtraService $row): void
    {
        if (!$row->booking_id) {
            return;
        }

        $booking = Booking::query()->find($row->booking_id);
        if (!$booking) {
            return;
        }

        $booking->resyncStoredCommissionAndSettlementSnapshot();
    }
}
