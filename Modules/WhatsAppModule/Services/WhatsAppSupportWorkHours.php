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
        $days = $this->runtime->supportWorkDays();
        $start = $this->runtime->supportWorkHoursStart();
        $end = $this->runtime->supportWorkHoursEnd();

        try {
            $now = Carbon::now($tz);
            $dow = (int) $now->dayOfWeekIso;
            if (! in_array($dow, $days, true)) {
                return false;
            }
            [$sh, $sm] = array_map('intval', explode(':', $start) + [0, 0]);
            [$eh, $em] = array_map('intval', explode(':', $end) + [0, 0]);
            $open = $now->copy()->setTime($sh, $sm, 0);
            $close = $now->copy()->setTime($eh, $em, 0);
            if ($close->lessThanOrEqualTo($open)) {
                return false;
            }

            return $now->greaterThanOrEqualTo($open) && $now->lessThanOrEqualTo($close);
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * Human-readable line for templates, AI tools, and admin (IST; weekdays + time range).
     * Times are shown in 12-hour AM/PM (storage remains 24-hour H:i).
     */
    public function humanReadableSchedule(): string
    {
        $daysPart = $this->formatIsoWeekdaysShort($this->runtime->supportWorkDays());
        $start = $this->formatTime12HourAmPm($this->runtime->supportWorkHoursStart());
        $end = $this->formatTime12HourAmPm($this->runtime->supportWorkHoursEnd());

        return "{$daysPart}, {$start}–{$end} IST";
    }

    /**
     * @param  string  $hhmm  24-hour "H:i" or "HH:MM" as stored in settings
     */
    private function formatTime12HourAmPm(string $hhmm): string
    {
        $hhmm = trim($hhmm);
        try {
            $t = Carbon::createFromFormat('H:i', $hhmm);

            return $t->format('g:i A');
        } catch (\Throwable) {
            return $hhmm;
        }
    }

    /**
     * @param  list<int>  $days
     */
    private function formatIsoWeekdaysShort(array $days): string
    {
        $days = array_values(array_unique(array_filter(array_map('intval', $days), static fn (int $d): bool => $d >= 1 && $d <= 7)));
        sort($days);
        if ($days === []) {
            return '—';
        }
        if (count($days) === 7) {
            return 'Every day';
        }

        $labels = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

        $runs = [];
        $runStart = $days[0];
        $prev = $days[0];
        $n = count($days);
        for ($i = 1; $i < $n; $i++) {
            if ($days[$i] === $prev + 1) {
                $prev = $days[$i];

                continue;
            }
            $runs[] = [$runStart, $prev];
            $runStart = $days[$i];
            $prev = $days[$i];
        }
        $runs[] = [$runStart, $prev];

        $parts = [];
        foreach ($runs as [$a, $b]) {
            if ($a === $b) {
                $parts[] = $labels[$a];
            } else {
                $parts[] = $labels[$a].'–'.$labels[$b];
            }
        }

        return implode(', ', $parts);
    }
}
