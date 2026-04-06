<?php

namespace Modules\BookingModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Allocates the same readable_id format as {@see \Modules\BookingModule\Entities\Booking} (e.g. PK07MAR26001).
 * Used when WhatsApp drafts need an ID before a row exists in `bookings`.
 */
final class BookingReadableIdAllocator
{
    public static function allocateNext(?Carbon $forDate = null): string
    {
        $today = ($forDate ?? Carbon::today())->copy()->startOfDay();

        return (string) DB::transaction(function () use ($today) {
            try {
                $dateKey = $today->format('Y-m-d');
                $row = DB::table('booking_readable_id_daily')->where('booking_date', $dateKey)->lockForUpdate()->first();
                if (!$row) {
                    DB::table('booking_readable_id_daily')->insert(['booking_date' => $dateKey, 'next_value' => 1]);
                    $seq = 1;
                    DB::table('booking_readable_id_daily')->where('booking_date', $dateKey)->update(['next_value' => 2]);
                } else {
                    $seq = (int) $row->next_value;
                    DB::table('booking_readable_id_daily')->where('booking_date', $dateKey)->update(['next_value' => $seq + 1]);
                }
                $dd = $today->format('d');
                $mon = strtoupper($today->format('M'));
                $yy = $today->format('y');
                $nnn = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

                return 'PK' . $dd . $mon . $yy . $nnn;
            } catch (Throwable) {
                $count = (int) DB::table('bookings')->whereDate('created_at', $today)->count();
                $seq = $count + 1;
                $dd = $today->format('d');
                $mon = strtoupper($today->format('M'));
                $yy = $today->format('y');
                $nnn = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

                return 'PK' . $dd . $mon . $yy . $nnn;
            }
        });
    }

    public static function isAppReadableIdFormat(string $id): bool
    {
        return (bool) preg_match('/^PK\d{2}[A-Z]{3}\d{2}\d{3}$/', trim($id));
    }
}
