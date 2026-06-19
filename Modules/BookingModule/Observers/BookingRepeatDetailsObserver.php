<?php

namespace Modules\BookingModule\Observers;

use Modules\BookingModule\Entities\BookingRepeatDetails;
use Modules\BookingModule\Services\BookingAuditLogger;

class BookingRepeatDetailsObserver
{
    /** @var array<int, array<string, mixed>> */
    private static array $originals = [];

    public function updating(BookingRepeatDetails $detail): void
    {
        self::$originals[spl_object_id($detail)] = $detail->getOriginal();
    }

    public function created(BookingRepeatDetails $detail): void
    {
        BookingAuditLogger::logBookingRepeatDetailChange('created', $detail, null);
    }

    public function updated(BookingRepeatDetails $detail): void
    {
        $oid = spl_object_id($detail);
        $before = self::$originals[$oid] ?? [];
        unset(self::$originals[$oid]);
        $changes = $detail->getChanges();
        unset($changes['updated_at']);
        $pairs = [];
        foreach ($changes as $key => $newRaw) {
            $pairs[$key] = [
                'old' => array_key_exists($key, $before) ? $before[$key] : null,
                'new' => $newRaw,
            ];
        }
        BookingAuditLogger::logBookingRepeatDetailChange('updated', $detail, $pairs);
    }

    public function deleted(BookingRepeatDetails $detail): void
    {
        $oid = spl_object_id($detail);
        unset(self::$originals[$oid]);
        BookingAuditLogger::logBookingRepeatDetailChange('deleted', $detail, null);
    }
}
