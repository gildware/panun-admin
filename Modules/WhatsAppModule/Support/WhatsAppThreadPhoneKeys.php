<?php

namespace Modules\WhatsAppModule\Support;

/**
 * Phone variants used to match WhatsApp users, messages, and thread meta.
 */
final class WhatsAppThreadPhoneKeys
{
    /**
     * @return list<string>
     */
    public static function keys(?string $phone): array
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return [];
        }

        $strippedPlus = ltrim($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw) ?: '';
        $keys = [$raw, $strippedPlus];

        if ($digits !== '') {
            $keys[] = $digits;
            $keys[] = '+'.$digits;
            if (strlen($digits) > 10) {
                $keys[] = substr($digits, -10);
            }
        }

        return array_values(array_unique(array_filter($keys, static fn (string $key): bool => $key !== '')));
    }

    public static function matches(?string $left, ?string $right): bool
    {
        $leftKeys = self::keys($left);
        $rightKeys = self::keys($right);
        if ($leftKeys === [] || $rightKeys === []) {
            return false;
        }
        if (array_intersect($leftKeys, $rightKeys) !== []) {
            return true;
        }

        $leftDigits = preg_replace('/\D+/', '', (string) $left) ?: '';
        $rightDigits = preg_replace('/\D+/', '', (string) $right) ?: '';
        if ($leftDigits === '' || $rightDigits === '') {
            return false;
        }
        if ($leftDigits === $rightDigits) {
            return true;
        }
        if (strlen($leftDigits) >= 10 && strlen($rightDigits) >= 10) {
            return substr($leftDigits, -10) === substr($rightDigits, -10);
        }

        return str_ends_with($leftDigits, $rightDigits) || str_ends_with($rightDigits, $leftDigits);
    }

    /**
     * Match a stored WhatsApp phone column to a lead last-10, including non-India
     * country prefixes (e.g. lead 6548613184 ↔ thread 966548613184).
     *
     * Exact keys (India-style 10 / 0… / 91… / +91…) are tried first via whereIn;
     * suffix LIKE covers any other country code Meta may store.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  list<string>  $exactKeys
     */
    public static function constrainQueryToLast10($query, string $column, string $last10, array $exactKeys = []): void
    {
        $digits = preg_replace('/\D+/', '', $last10) ?: '';
        if (strlen($digits) >= 10) {
            $digits = substr($digits, -10);
        }
        if ($digits === '') {
            $query->whereRaw('0 = 1');

            return;
        }

        $exactKeys = array_values(array_unique(array_filter(
            $exactKeys,
            static fn ($key): bool => is_string($key) && $key !== ''
        )));

        $query->where(function ($q) use ($column, $exactKeys, $digits) {
            if ($exactKeys !== []) {
                $q->whereIn($column, $exactKeys)
                    ->orWhere($column, 'like', '%'.$digits);
            } else {
                $q->where($column, 'like', '%'.$digits);
            }
        });
    }

    /**
     * @param  list<string|null>  $phones
     * @return list<string>
     */
    public static function expand(array $phones): array
    {
        $out = [];
        foreach ($phones as $phone) {
            foreach (self::keys($phone) as $key) {
                $out[] = $key;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  iterable<int, object|array<string, mixed>>  $records
     * @return array<string, mixed>
     */
    public static function indexByPhoneKeys(iterable $records): array
    {
        $map = [];
        foreach ($records as $record) {
            $phone = is_array($record)
                ? (string) ($record['phone'] ?? '')
                : (string) ($record->phone ?? '');
            foreach (self::keys($phone) as $key) {
                $map[$key] ??= $record;
            }
        }

        return $map;
    }

    public static function lookup(array $map, ?string $phone): mixed
    {
        foreach (self::keys($phone) as $key) {
            if (array_key_exists($key, $map)) {
                return $map[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $keySet
     */
    public static function matchesKeySet(?string $phone, array $keySet): bool
    {
        if ($keySet === []) {
            return false;
        }

        foreach (self::keys($phone) as $key) {
            if (isset($keySet[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, true>  $closedKeySet
     */
    public static function isClosed(?string $phone, array $closedKeySet): bool
    {
        return self::matchesKeySet($phone, $closedKeySet);
    }
}
