<?php

namespace Modules\WhatsAppModule\Services;

use Carbon\Carbon;

class WhatsAppSupportWorkHours
{
    public function __construct(
        protected WhatsAppAiRuntimeResolver $runtime
    ) {}

    public function isWithinSupportHours(): bool
    {
        $tz = $this->runtime->supportTimezone();
        $start = $this->runtime->supportWorkHoursStart();
        $end = $this->runtime->supportWorkHoursEnd();

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
        $start = $this->runtime->supportWorkHoursStart();
        $end = $this->runtime->supportWorkHoursEnd();
        $tz = $this->runtime->supportTimezone();

        return "{$start} – {$end} ({$tz})";
    }
}
