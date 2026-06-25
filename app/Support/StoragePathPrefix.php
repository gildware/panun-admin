<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Environment segment inside a shared R2/S3 bucket (local / dev / prod).
 */
class StoragePathPrefix
{
    private static ?string $resolved = null;

    /**
     * Prefix with trailing slash, e.g. "dev/", or empty when disabled.
     */
    public static function get(): string
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $segment = self::configuredSegment();
        self::$resolved = $segment === '' ? '' : $segment.'/';

        return self::$resolved;
    }

    public static function segment(): string
    {
        return rtrim(self::get(), '/');
    }

    public static function apply(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');
        $prefix = self::get();

        if ($prefix === '' || $relativePath === '') {
            return $relativePath;
        }

        if (str_starts_with($relativePath, $prefix)) {
            return $relativePath;
        }

        return $prefix.$relativePath;
    }

    public static function strip(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');
        $prefix = self::get();

        if ($prefix !== '' && str_starts_with($relativePath, $prefix)) {
            return substr($relativePath, strlen($prefix));
        }

        return $relativePath;
    }

    /**
     * Keys to try when reading or deleting (prefixed + legacy unprefixed).
     *
     * @return list<string>
     */
    public static function keyVariants(string $relativePath): array
    {
        $relativePath = ltrim($relativePath, '/');
        if ($relativePath === '') {
            return [];
        }

        $variants = array_values(array_unique(array_filter([
            $relativePath,
            self::apply($relativePath),
            self::strip($relativePath),
        ])));

        return $variants;
    }

    public static function resetCache(): void
    {
        self::$resolved = null;
    }

    private static function configuredSegment(): string
    {
        if (function_exists('business_config')) {
            $record = business_config('storage_path_prefix', 'storage_settings');
            if ($record !== null && $record->live_values !== null && $record->live_values !== '') {
                $normalized = self::normalize((string) $record->live_values);
                if ($normalized !== '') {
                    return $normalized;
                }
            }
        }

        $fromEnv = env('STORAGE_PATH_PREFIX');
        if ($fromEnv !== null && $fromEnv !== '') {
            $normalized = self::normalize((string) $fromEnv);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return self::defaultSegmentFromAppEnv();
    }

    private static function defaultSegmentFromAppEnv(): string
    {
        return match (strtolower((string) env('APP_ENV', 'production'))) {
            'local' => 'local',
            'production', 'live' => 'prod',
            default => 'dev',
        };
    }

    private static function normalize(string $value): string
    {
        $value = strtolower(trim($value, " \t\n\r\0\x0B/"));
        if ($value === '') {
            return '';
        }

        $slug = Str::slug($value, '-');
        if ($slug !== '') {
            return $slug;
        }

        return preg_replace('/[^a-z0-9_-]/', '', $value) ?? '';
    }
}
