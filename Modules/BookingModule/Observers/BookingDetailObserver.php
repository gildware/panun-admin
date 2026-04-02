<?php

namespace Modules\BookingModule\Observers;

use Modules\BookingModule\Entities\BookingDetail;
use Modules\BookingModule\Services\BookingAuditLogger;

class BookingDetailObserver
{
    /** @var array<int, array<string, mixed>> */
    private static array $originals = [];

    public function updating(BookingDetail $detail): void
    {
        self::$originals[spl_object_id($detail)] = $detail->getOriginal();
    }

    public function created(BookingDetail $detail): void
    {
        BookingAuditLogger::logBookingDetailChange('created', $detail, null);
    }

    public function updated(BookingDetail $detail): void
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
        BookingAuditLogger::logBookingDetailChange('updated', $detail, $pairs);
    }

    public function deleted(BookingDetail $detail): void
    {
        $oid = spl_object_id($detail);
        unset(self::$originals[$oid]);
        BookingAuditLogger::logBookingDetailChange('deleted', $detail, null);
    }
}
