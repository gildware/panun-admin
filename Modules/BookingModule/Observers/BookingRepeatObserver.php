<?php

namespace Modules\BookingModule\Observers;

use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Services\BookingAuditLogger;

class BookingRepeatObserver
{
    /** @var array<int, array<string, mixed>> */
    private static array $originals = [];

    public function updating(BookingRepeat $repeat): void
    {
        self::$originals[spl_object_id($repeat)] = $repeat->getOriginal();
    }

    public function created(BookingRepeat $repeat): void
    {
        BookingAuditLogger::logBookingRepeatCreated($repeat);
    }

    public function updated(BookingRepeat $repeat): void
    {
        $oid = spl_object_id($repeat);
        $before = self::$originals[$oid] ?? [];
        unset(self::$originals[$oid]);
        BookingAuditLogger::logBookingRepeatUpdatedFromDiff($repeat, $before, $repeat->getChanges());
    }

    public function deleted(BookingRepeat $repeat): void
    {
        $oid = spl_object_id($repeat);
        unset(self::$originals[$oid]);
        BookingAuditLogger::logBookingRepeatDeleted($repeat);
    }
}
