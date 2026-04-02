<?php

namespace Modules\WhatsAppModule\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * WhatsApp admin chat timestamps: storage + API/Blade display in {@see displayTimezone()}.
 */
final class WhatsAppMessageTime
{
    public static function storageTimezone(): string
    {
        return (string) config('whatsappmodule.message_timezone', config('app.timezone'));
    }

    public static function displayTimezone(): string
    {
        return (string) config('whatsappmodule.message_timezone', config('app.timezone'));
    }

    /**
     * ISO 8601 with offset for JSON (client formats in {@see displayTimezone()}).
     */
    public static function toDisplayIso(mixed $created): ?string
    {
        if ($created === null || $created === '') {
            return null;
        }
        $tz = self::storageTimezone();
        $outTz = self::displayTimezone();
        try {
            $c = $created instanceof CarbonInterface
                ? Carbon::instance($created)
                : Carbon::parse((string) $created, $tz);

            return $c->copy()->timezone($outTz)->toIso8601String();
        } catch (\Throwable) {
            return is_string($created) ? $created : null;
        }
    }

    public static function formatListLabel(mixed $created, string $format = 'M j, Y g:i A'): string
    {
        if ($created === null || $created === '') {
            return '—';
        }
        try {
            $c = $created instanceof CarbonInterface
                ? Carbon::instance($created)
                : Carbon::parse((string) $created, self::storageTimezone());

            return $c->copy()->timezone(self::displayTimezone())->format($format);
        } catch (\Throwable) {
            return '—';
        }
    }

    public static function formatBlade(mixed $dt, string $format = 'M j, H:i'): string
    {
        if ($dt === null || $dt === '') {
            return '—';
        }
        if (! $dt instanceof CarbonInterface) {
            try {
                $dt = Carbon::parse((string) $dt, self::storageTimezone());
            } catch (\Throwable) {
                return '—';
            }
        }

        return $dt->copy()->timezone(self::displayTimezone())->format($format);
    }
}
