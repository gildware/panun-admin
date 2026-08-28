<?php

namespace Modules\BookingModule\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\BookingModule\Entities\Booking;

class AdminRepeatBookingScheduleService
{
    public const MAX_VISITS = 120;

    public const OPEN_ENDED_DAILY_VISITS = 14;

    public const OPEN_ENDED_WEEKLY_VISITS = 12;

    public const OPEN_ENDED_MONTHLY_VISITS = 6;

    public const TYPES = ['daily', 'weekly', 'monthly', 'yearly'];

    public const MAX_VISITS_PER_PERIOD = [
        'daily' => 8,
        'weekly' => 14,
        'monthly' => 31,
        'yearly' => 52,
    ];

    public const WEEKDAY_KEYS = [
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
        7 => 'sunday',
    ];

    /**
     * @return array{is_repeat: bool, type: string|null, dates: list<Carbon>, until_stopped: bool, weekdays: list<int>}
     */
    public function resolveCreateRequest(Request $request, bool $allowRepeat): array
    {
        $isRepeat = (int) $request->input('is_repeat_booking', 0) === 1;
        if (! $allowRepeat || ! $isRepeat) {
            return [
                'is_repeat' => false,
                'type' => null,
                'dates' => [],
                'until_stopped' => false,
                'weekdays' => [],
                'month_days' => [],
                'planned_visits' => 0,
                'visits_per_period' => 0,
                'end_date' => null,
            ];
        }

        return $this->resolveCadenceRequest($request, $this->parseDateTime($request->input('service_schedule'), 'service_schedule'));
    }

    /**
     * @return array{is_repeat: bool, type: string, dates: list<Carbon>, until_stopped: bool, weekdays: list<int>}
     */
    public function resolveConvertRequest(Request $request, Booking $booking): array
    {
        if (! $this->canConvert($booking)) {
            throw ValidationException::withMessages([
                'repeat_booking_type' => [translate('This_booking_cannot_be_converted_to_repeat')],
            ]);
        }

        $plan = $this->resolveCadenceRequest($request, Carbon::parse($booking->service_schedule));
        $plan['dates'] = [Carbon::parse($booking->service_schedule)];

        return $plan;
    }

    /**
     * Visit dates are not pre-generated. The series stores a cadence label and a planned
     * visit count; actual visits are added when the provider attends.
     *
     * @return array{is_repeat: bool, type: string, dates: list<Carbon>, until_stopped: bool, weekdays: list<int>, month_days: list<int>, planned_visits: int}
     */
    public function resolveCadenceRequest(Request $request, Carbon $start): array
    {
        $type = strtolower(trim((string) $request->input('repeat_booking_type', '')));
        if (! in_array($type, self::TYPES, true)) {
            throw ValidationException::withMessages([
                'repeat_booking_type' => [translate('Select_a_repeat_booking_type')],
            ]);
        }

        $endDate = $this->parseOptionalEndDate($request, $start);
        $untilStopped = $endDate === null;
        $visitsPerPeriod = $this->parseVisitsPerPeriod($request, $type);

        return [
            'is_repeat' => true,
            'type' => $type,
            'dates' => [],
            'until_stopped' => $untilStopped,
            'weekdays' => [],
            'month_days' => [],
            'planned_visits' => $visitsPerPeriod,
            'visits_per_period' => $visitsPerPeriod,
            'end_date' => $endDate?->toDateString(),
            'start' => $start,
        ];
    }

    public function isUntilStopped(Request $request): bool
    {
        $raw = $request->input('repeat_until_stopped', 0);
        if (is_array($raw)) {
            $raw = end($raw);
        }

        return $raw === true || $raw === 1 || $raw === '1' || $raw === 'on';
    }

    /**
     * @param  list<int>  $weekdays
     * @param  list<string>  $existingYmd
     * @param  list<int>  $monthDays
     * @return list<Carbon>
     */
    public function generateFollowingDates(
        Carbon $afterExclusive,
        string $type,
        array $weekdays,
        int $count,
        array $existingYmd = [],
        array $monthDays = []
    ): array {
        $count = max(1, min(self::MAX_VISITS, $count));
        $existing = array_flip(array_map('strval', $existingYmd));
        $dates = [];

        if ($type === 'monthly') {
            $days = $this->normalizeMonthDays($monthDays, $afterExclusive);
            $cursor = $afterExclusive->copy()->startOfMonth();
            $guard = 0;
            while (count($dates) < $count && $guard < 48) {
                foreach ($days as $day) {
                    $candidate = $this->onMonthDay($afterExclusive, (int) $cursor->year, (int) $cursor->month, $day);
                    $ymd = $candidate->format('Y-m-d');
                    if ($candidate->gt($afterExclusive) && ! isset($existing[$ymd])) {
                        $dates[] = $candidate;
                        $existing[$ymd] = true;
                        if (count($dates) >= $count) {
                            break;
                        }
                    }
                }
                $cursor->addMonthNoOverflow();
                $guard++;
            }

            return $dates;
        }

        $cursor = $afterExclusive->copy()->startOfDay()->addDay();
        $guard = 0;
        while (count($dates) < $count && $guard < 800) {
            $iso = (int) $cursor->isoWeekday();
            $matches = $type === 'daily' || ($type === 'weekly' && in_array($iso, $weekdays, true));
            $ymd = $cursor->format('Y-m-d');
            if ($matches && ! isset($existing[$ymd])) {
                $dates[] = $afterExclusive->copy()->setDate($cursor->year, $cursor->month, $cursor->day);
            }
            $cursor->addDay();
            $guard++;
        }

        return $dates;
    }

    public function openEndedVisitCountForType(string $type): int
    {
        return match ($type) {
            'daily' => self::OPEN_ENDED_DAILY_VISITS,
            'weekly' => self::OPEN_ENDED_WEEKLY_VISITS,
            default => self::OPEN_ENDED_MONTHLY_VISITS,
        };
    }

    /**
     * @param  array{type?: string, weekdays?: list<int>, until_stopped?: bool}  $plan
     * @return array{until_stopped: bool, type: string, weekdays: list<int>, time: string}
     */
    public function cadenceMetaFromPlan(array $plan, Carbon $firstVisit): array
    {
        $visitsPerPeriod = max(1, (int) ($plan['visits_per_period'] ?? $plan['planned_visits'] ?? 1));
        $endDate = $plan['end_date'] ?? null;
        if ($endDate instanceof Carbon) {
            $endDate = $endDate->toDateString();
        }
        $endDate = is_string($endDate) && $endDate !== '' ? $endDate : null;
        $untilStopped = $endDate === null;

        return [
            'until_stopped' => $untilStopped,
            'type' => (string) ($plan['type'] ?? ''),
            'weekdays' => array_values(array_map('intval', $plan['weekdays'] ?? [])),
            'month_days' => array_values(array_map('intval', $plan['month_days'] ?? [])),
            'visits_per_period' => $visitsPerPeriod,
            'planned_visits' => $visitsPerPeriod,
            'start_date' => $firstVisit->toDateString(),
            'end_date' => $endDate,
            'time' => $firstVisit->format('H:i:s'),
        ];
    }

    public function periodKey(string $type, Carbon $at): string
    {
        return match ($type) {
            'daily' => $at->format('Y-m-d'),
            'weekly' => $at->isoWeekYear() . '-W' . str_pad((string) $at->isoWeek(), 2, '0', STR_PAD_LEFT),
            'yearly' => $at->format('Y'),
            default => $at->format('Y-m'),
        };
    }

    public function visitsLoggedInPeriod(Booking $booking, Carbon $at, ?string $exceptRepeatId = null): int
    {
        $type = $booking->repeatCadenceType() ?: 'monthly';
        $key = $this->periodKey($type, $at);
        $booking->loadMissing('repeat');

        return $booking->repeat->filter(function ($repeat) use ($type, $key, $exceptRepeatId) {
            if ($exceptRepeatId !== null && (string) $repeat->id === (string) $exceptRepeatId) {
                return false;
            }
            if (in_array((string) $repeat->booking_status, ['canceled', 'cancelled'], true)) {
                return false;
            }
            if (empty($repeat->service_schedule)) {
                return false;
            }

            return $this->periodKey($type, Carbon::parse($repeat->service_schedule)) === $key;
        })->count();
    }

    public function assertVisitFitsCadence(Booking $booking, Carbon $at, ?string $exceptRepeatId = null): void
    {
        $meta = is_array($booking->repeat_cadence_meta) ? $booking->repeat_cadence_meta : [];
        $startRaw = (string) ($meta['start_date'] ?? '');
        if ($startRaw !== '') {
            $start = Carbon::parse($startRaw)->startOfDay();
            if ($at->copy()->startOfDay()->lt($start)) {
                throw ValidationException::withMessages([
                    'service_schedule' => [translate('Repeat_visit_before_start_date')],
                ]);
            }
        }

        $endRaw = (string) ($meta['end_date'] ?? '');
        if ($endRaw !== '' && empty($booking->repeat_until_stopped)) {
            $end = Carbon::parse($endRaw)->endOfDay();
            if ($at->gt($end)) {
                throw ValidationException::withMessages([
                    'service_schedule' => [translate('Repeat_visit_after_end_date')],
                ]);
            }
        }

        $perPeriod = $booking->visitsPerPeriod();
        if ($perPeriod > 0 && $this->visitsLoggedInPeriod($booking, $at, $exceptRepeatId) >= $perPeriod) {
            throw ValidationException::withMessages([
                'service_schedule' => [translate('Repeat_period_visit_limit')],
            ]);
        }
    }

    public function canConvert(Booking $booking): bool
    {
        if ((int) ($booking->is_repeated ?? 0) !== 0) {
            return false;
        }
        $status = (string) ($booking->booking_status ?? '');
        if (in_array($status, ['completed', 'canceled', 'cancelled', 'refunded'], true)) {
            return false;
        }
        $outcome = trim((string) ($booking->settlement_outcome ?? ''));
        if ($outcome !== '') {
            return false;
        }

        return true;
    }

    /**
     * @param  list<Carbon>  $dates
     * @return list<string>
     */
    public function toStorageStrings(array $dates): array
    {
        return array_values(array_map(
            static fn (Carbon $d) => $d->format('Y-m-d H:i:s'),
            $dates
        ));
    }

    /**
     * @return list<Carbon>
     */
    public function generateDaily(Carbon $start, Carbon $end): array
    {
        $dates = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();
        while ($cursor->lte($last)) {
            $dates[] = $start->copy()->setDate($cursor->year, $cursor->month, $cursor->day);
            $cursor->addDay();
            if (count($dates) > self::MAX_VISITS) {
                break;
            }
        }

        return $dates;
    }

    /**
     * @param  list<int>  $weekdays  ISO weekdays 1 (Mon) – 7 (Sun)
     * @return list<Carbon>
     */
    public function generateWeekly(Carbon $start, Carbon $end, array $weekdays): array
    {
        $weekdays = array_values(array_unique(array_map('intval', $weekdays)));
        $dates = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();
        while ($cursor->lte($last)) {
            if (in_array((int) $cursor->isoWeekday(), $weekdays, true)) {
                $dates[] = $start->copy()->setDate($cursor->year, $cursor->month, $cursor->day);
            }
            $cursor->addDay();
            if (count($dates) > self::MAX_VISITS) {
                break;
            }
        }

        return $dates;
    }

    /**
     * @return list<Carbon>
     */
    public function generateDailyUntilCount(Carbon $start, int $count): array
    {
        $count = max(2, min(self::MAX_VISITS, $count));
        $dates = [];
        for ($i = 0; $i < $count; $i++) {
            $dates[] = $start->copy()->addDays($i);
        }

        return $dates;
    }

    /**
     * @param  list<int>  $weekdays
     * @return list<Carbon>
     */
    public function generateWeeklyUntilCount(Carbon $start, array $weekdays, int $count): array
    {
        $weekdays = array_values(array_unique(array_map('intval', $weekdays)));
        $count = max(2, min(self::MAX_VISITS, $count));
        $dates = [$start->copy()];
        $seen = [$start->format('Y-m-d') => true];
        $cursor = $start->copy()->startOfDay()->addDay();
        $guard = 0;
        while (count($dates) < $count && $guard < 800) {
            if (in_array((int) $cursor->isoWeekday(), $weekdays, true) && ! isset($seen[$cursor->format('Y-m-d')])) {
                $dates[] = $start->copy()->setDate($cursor->year, $cursor->month, $cursor->day);
                $seen[$cursor->format('Y-m-d')] = true;
            }
            $cursor->addDay();
            $guard++;
        }

        return $dates;
    }

    /**
     * @param  list<int>  $monthDays  Days of month (1–31). Empty uses the first visit’s day only.
     * @return list<Carbon>
     */
    public function generateMonthly(Carbon $start, int $monthCount, array $monthDays = []): array
    {
        $monthCount = max(1, min(self::MAX_VISITS, $monthCount));
        $days = $this->normalizeMonthDays($monthDays, $start);
        $dates = [];
        $seen = [];
        for ($i = 0; $i < $monthCount; $i++) {
            $base = $start->copy()->addMonthsNoOverflow($i);
            foreach ($days as $day) {
                $candidate = $this->onMonthDay($start, (int) $base->year, (int) $base->month, $day);
                if ($candidate->lt($start->copy()->startOfDay())) {
                    continue;
                }
                $ymd = $candidate->format('Y-m-d');
                if (isset($seen[$ymd])) {
                    continue;
                }
                $seen[$ymd] = true;
                $dates[] = $candidate;
                if (count($dates) >= self::MAX_VISITS) {
                    return $dates;
                }
            }
        }

        return $dates;
    }

    /**
     * @param  list<int>  $monthDays
     * @return list<int>
     */
    public function normalizeMonthDays(array $monthDays, Carbon $start): array
    {
        $days = [];
        foreach ($monthDays as $day) {
            $n = (int) $day;
            if ($n >= 1 && $n <= 31) {
                $days[] = $n;
            }
        }
        $days[] = (int) $start->day;
        $days = array_values(array_unique($days));
        sort($days);

        return $days === [] ? [(int) $start->day] : $days;
    }

    /**
     * @param  list<string>  $extraDatetimes
     * @return list<Carbon>
     */
    public function generateCustom(Carbon $first, array $extraDatetimes): array
    {
        $unique = [$first->format('Y-m-d H:i:s') => $first->copy()];
        foreach ($extraDatetimes as $raw) {
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }
            try {
                $dt = Carbon::parse(str_replace('T', ' ', $raw));
            } catch (\Throwable) {
                continue;
            }
            $unique[$dt->format('Y-m-d H:i:s')] = $dt;
        }
        $dates = array_values($unique);
        usort($dates, static fn (Carbon $a, Carbon $b) => $a->timestamp <=> $b->timestamp);

        return array_slice($dates, 0, self::MAX_VISITS);
    }

    private function parseDateTime(mixed $value, string $field): Carbon
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            throw ValidationException::withMessages([
                $field => [translate('This_field_required')],
            ]);
        }
        try {
            return Carbon::parse(str_replace('T', ' ', $raw));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => [translate('Please_enter_a_valid_service_schedule')],
            ]);
        }
    }

    private function parseEndDate(Request $request, Carbon $start): Carbon
    {
        $raw = trim((string) $request->input('repeat_end_date', ''));
        if ($raw === '') {
            throw ValidationException::withMessages([
                'repeat_end_date' => [translate('Repeat_end_date_required')],
            ]);
        }
        try {
            $end = Carbon::parse($raw)->endOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'repeat_end_date' => [translate('Please_enter_a_valid_end_date')],
            ]);
        }
        if ($end->lt($start->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'repeat_end_date' => [translate('Repeat_end_date_must_be_on_or_after_start')],
            ]);
        }

        return $end;
    }

    /**
     * @return list<int>
     */
    private function parseWeekdays(Request $request): array
    {
        $raw = $request->input('repeat_weekdays', []);
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }
        if (! is_array($raw)) {
            $raw = [];
        }
        $days = [];
        foreach ($raw as $item) {
            if (is_numeric($item)) {
                $n = (int) $item;
                if ($n >= 1 && $n <= 7) {
                    $days[] = $n;
                }
                continue;
            }
            $key = strtolower(trim((string) $item));
            $found = array_search($key, self::WEEKDAY_KEYS, true);
            if ($found !== false) {
                $days[] = (int) $found;
            }
        }
        $days = array_values(array_unique($days));
        if ($days === []) {
            throw ValidationException::withMessages([
                'repeat_weekdays' => [translate('Select_at_least_one_weekday')],
            ]);
        }

        return $days;
    }

    private function parseVisitsPerPeriod(Request $request, string $type): int
    {
        $count = (int) $request->input('repeat_planned_visits', 0);
        $max = self::MAX_VISITS_PER_PERIOD[$type] ?? 31;
        if ($count < 1 || $count > $max) {
            throw ValidationException::withMessages([
                'repeat_planned_visits' => [translate('Repeat_planned_visits_invalid')],
            ]);
        }

        return $count;
    }

    private function parseOptionalEndDate(Request $request, Carbon $start): ?Carbon
    {
        $raw = trim((string) $request->input('repeat_end_date', ''));
        if ($raw === '') {
            return null;
        }
        try {
            $end = Carbon::parse($raw)->endOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'repeat_end_date' => [translate('Please_enter_a_valid_end_date')],
            ]);
        }
        if ($end->lt($start->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'repeat_end_date' => [translate('Repeat_end_date_must_be_on_or_after_start')],
            ]);
        }

        return $end;
    }

    private function parseMonthCount(Request $request): int
    {
        $count = (int) $request->input('repeat_month_count', 0);
        if ($count < 1 || $count > self::MAX_VISITS) {
            throw ValidationException::withMessages([
                'repeat_month_count' => [translate('Repeat_month_count_invalid')],
            ]);
        }

        return $count;
    }

    /**
     * @return list<int>
     */
    private function parseMonthDays(Request $request, Carbon $start): array
    {
        $raw = $request->input('repeat_month_days', []);
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }
        if (! is_array($raw)) {
            $raw = [];
        }

        return $this->normalizeMonthDays(array_map('intval', $raw), $start);
    }

    private function onMonthDay(Carbon $timeSource, int $year, int $month, int $day): Carbon
    {
        $lastDay = (int) Carbon::create($year, $month, 1)->endOfMonth()->day;
        $useDay = min(max(1, $day), $lastDay);

        return $timeSource->copy()->setDate($year, $month, $useDay);
    }

    /**
     * @return list<string>
     */
    private function parseCustomDates(Request $request): array
    {
        $raw = $request->input('repeat_custom_dates', []);
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_map('strval', $raw));
    }

    /**
     * @param  list<Carbon>  $dates
     */
    private function assertVisitCount(array $dates, string $field): void
    {
        $count = count($dates);
        if ($count < 2) {
            throw ValidationException::withMessages([
                $field => [translate('Repeat_booking_needs_at_least_two_visits')],
            ]);
        }
        if ($count > self::MAX_VISITS) {
            throw ValidationException::withMessages([
                $field => [translate('Repeat_booking_visit_limit')],
            ]);
        }
    }

    private function errorFieldForType(string $type): string
    {
        return match ($type) {
            'daily', 'weekly' => 'repeat_end_date',
            'monthly' => 'repeat_month_count',
            default => 'repeat_custom_dates',
        };
    }
}
