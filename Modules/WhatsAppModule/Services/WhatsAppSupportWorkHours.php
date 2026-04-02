<?php

namespace Modules\WhatsAppModule\Services;

use Carbon\Carbon;

class WhatsAppSupportWorkHours
{
    public function isWithinSupportHours(): bool
    {
        $tz = (string) config('whatsappmodule.support_timezone', 'Asia/Kolkata');
        $start = (string) config('whatsappmodule.support_work_hours_start', '09:00');
        $end = (string) config('whatsappmodule.support_work_hours_end', '18:00');

        try {
            $now = Carbon::now($tz);
            [$sh, $sm] = array_map('intval', explode(':', $start) + [0, 0]);
            [$eh, $em] = array_map('intval', explode(':', $end) + [0, 0]);
            $open = $now->copy()->setTime($sh, $sm, 0);
            $close = $now->copy()->setTime($eh, $em, 0);

            return $now->greaterThanOrEqualTo($open) && $now->lessThanOrEqualTo($close);
        } catch (\Throwable) {
            return true;
        }
    }

    public function humanReadableSchedule(): string
    {
        $start = (string) config('whatsappmodule.support_work_hours_start', '09:00');
        $end = (string) config('whatsappmodule.support_work_hours_end', '18:00');
        $tz = (string) config('whatsappmodule.support_timezone', 'Asia/Kolkata');

        return "{$start} – {$end} ({$tz})";
    }
}
