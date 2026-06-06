<?php

namespace Modules\BusinessSettingsModule\Services;

use Carbon\Carbon;

/**
 * Parse Roman Urdu / Hinglish / English visit-time phrases for the booking wizard.
 */
final class MobileAppAiSchedulePhraseParser
{
    /**
     * @return array{ok: bool, schedule?: string, schedule_type?: string, label?: string, error?: string}
     */
    public static function parse(string $text): array
    {
        $raw = trim($text);
        if ($raw === '') {
            return ['ok' => false, 'error' => 'empty'];
        }

        $lower = mb_strtolower($raw);

        if (self::meansAsap($lower)) {
            $dt = Carbon::now()->addMinutes(2);

            return [
                'ok' => true,
                'schedule' => $dt->format('Y-m-d H:i:s'),
                'schedule_type' => 'asap',
                'label' => 'ASAP (earliest available)',
            ];
        }

        $day = self::resolveRelativeDay($lower);
        if ($day !== null) {
            $time = self::resolveTimeOfDay($lower) ?? ['hour' => 10, 'minute' => 0];
            $dt = $day->copy()->setTime($time['hour'], $time['minute'], 0);
            if ($dt->lt(Carbon::now()->addHours(2))) {
                $dt = Carbon::now()->addHours(2)->addMinutes(15);
            }

            return [
                'ok' => true,
                'schedule' => $dt->format('Y-m-d H:i:s'),
                'schedule_type' => 'custom',
                'label' => $dt->format('j M Y, g:i A'),
            ];
        }

        try {
            $dt = Carbon::parse($raw);
            if ($dt->lt(Carbon::now()->addHours(2))) {
                return ['ok' => false, 'error' => 'schedule_too_soon'];
            }

            return [
                'ok' => true,
                'schedule' => $dt->format('Y-m-d H:i:s'),
                'schedule_type' => 'custom',
                'label' => $dt->format('j M Y, g:i A'),
            ];
        } catch (\Throwable) {
            return ['ok' => false, 'error' => 'invalid_format'];
        }
    }

    public static function looksLikeSchedulePhrase(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return false;
        }

        if (self::meansAsap($t)) {
            return true;
        }

        if (self::resolveRelativeDay($t) !== null) {
            return true;
        }

        return MobileAppAiBookingMessageDetector::hasTimeHint($text);
    }

    private static function meansAsap(string $lower): bool
    {
        return (bool) preg_match(
            '/\b(asap|as soon|earliest|jaldi|abhi|turant|foran|urgent|urgently|quickly|fast)\b/iu',
            $lower
        );
    }

    private static function resolveRelativeDay(string $lower): ?Carbon
    {
        $today = Carbon::now()->startOfDay();

        if (preg_match('/\b(parson|day after tomorrow|day after)\b/iu', $lower)) {
            return $today->copy()->addDays(2);
        }

        if (preg_match('/\b(kal|tomorrow|next day)\b/iu', $lower)) {
            return $today->copy()->addDay();
        }

        if (preg_match('/\b(aaj|today|tonight)\b/iu', $lower)) {
            return $today->copy();
        }

        if (preg_match('/\b(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/iu', $lower, $m)) {
            return Carbon::parse('next '.$m[1])->startOfDay();
        }

        return null;
    }

    /**
     * @return array{hour: int, minute: int}|null
     */
    private static function resolveTimeOfDay(string $lower): ?array
    {
        if (preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/i', $lower, $m)) {
            $h = (int) $m[1];
            $min = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;

            return ['hour' => self::to24Hour($h, mb_strtolower($m[3])), 'minute' => $min];
        }

        if (preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*(?:baje|bje|bajey|bajhe)\b/iu', $lower, $m)
            || preg_match('/\b(?:baje|bje|bajey)\s*(\d{1,2})\b/iu', $lower, $m)) {
            $h = (int) ($m[1] ?? 0);
            $min = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;

            return ['hour' => self::inferHourFromBaje($lower, $h), 'minute' => $min];
        }

        if (preg_match('/\b(?:at\s+)?(\d{1,2}):(\d{2})\b/', $lower, $m)) {
            return ['hour' => self::inferHourFromBaje($lower, (int) $m[1]), 'minute' => (int) $m[2]];
        }

        if (preg_match('/\b(?:at\s+)?(\d{1,2})\b/', $lower, $m)) {
            $h = (int) $m[1];
            if ($h >= 0 && $h <= 23) {
                return ['hour' => self::inferHourFromBaje($lower, $h), 'minute' => 0];
            }
        }

        if (preg_match('/\b(subah|morning)\b/iu', $lower)) {
            return ['hour' => 10, 'minute' => 0];
        }
        if (preg_match('/\b(dopahar|afternoon)\b/iu', $lower)) {
            return ['hour' => 14, 'minute' => 0];
        }
        if (preg_match('/\b(sham|evening)\b/iu', $lower)) {
            return ['hour' => 17, 'minute' => 0];
        }
        if (preg_match('/\b(raat|night)\b/iu', $lower)) {
            return ['hour' => 19, 'minute' => 0];
        }

        return null;
    }

    private static function to24Hour(int $hour, string $ampm): int
    {
        if ($ampm === 'pm' && $hour < 12) {
            return $hour + 12;
        }
        if ($ampm === 'am' && $hour === 12) {
            return 0;
        }

        return $hour;
    }

    /**
     * Roman Urdu "10 baje" is usually 10:00 AM unless evening words suggest PM.
     */
    private static function inferHourFromBaje(string $lower, int $hour): int
    {
        if (preg_match('/\b(am)\b/i', $lower)) {
            return self::to24Hour($hour, 'am');
        }
        if (preg_match('/\b(pm)\b/i', $lower)) {
            return self::to24Hour($hour, 'pm');
        }

        $evening = (bool) preg_match('/\b(sham|evening|raat|night|dopahar|afternoon)\b/iu', $lower);
        $morning = (bool) preg_match('/\b(subah|morning)\b/iu', $lower);

        if ($evening && $hour >= 1 && $hour <= 11) {
            return $hour + 12;
        }
        if ($morning) {
            return $hour === 12 ? 12 : $hour;
        }
        if ($hour >= 8 && $hour <= 11) {
            return $hour;
        }
        if ($hour >= 1 && $hour <= 7) {
            return $hour + 12;
        }
        if ($hour === 12) {
            return 12;
        }

        return min(23, max(0, $hour));
    }
}
