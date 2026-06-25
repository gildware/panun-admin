<?php

namespace App\Support;

use Illuminate\Support\Facades\Config;

/**
 * Applies S3-compatible disk settings (Cloudflare R2, AWS S3, etc.) from admin DB or .env.
 */
class CloudStorageConfigurator
{
    public static function apply(): void
    {
        $disk = self::resolveDiskConfig();
        if ($disk === null) {
            return;
        }

        Config::set('filesystems.disks.s3', $disk);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function resolveDiskConfig(): ?array
    {
        $fromDb = self::credentialsFromBusinessSettings();
        if ($fromDb !== null) {
            return self::normalizeDiskConfig($fromDb);
        }

        $fromEnv = self::credentialsFromEnv();
        if ($fromEnv !== null) {
            return self::normalizeDiskConfig($fromEnv);
        }

        return null;
    }

    public static function publicBaseUrl(): ?string
    {
        $url = (string) Config::get('filesystems.disks.s3.url', '');
        $url = rtrim($url, '/');

        return $url !== '' ? $url : null;
    }

    public static function isConfigured(): bool
    {
        return self::resolveDiskConfig() !== null;
    }

    /**
     * @return array<string, string>|null
     */
    private static function credentialsFromBusinessSettings(): ?array
    {
        if (! function_exists('business_config')) {
            return null;
        }

        $record = business_config('s3_storage_credentials', 'storage_settings');
        if ($record === null || empty($record->live_values)) {
            return null;
        }

        $values = json_decode($record->live_values, true);
        if (! is_array($values)) {
            return null;
        }

        $key = trim((string) ($values['key'] ?? ''));
        $secret = trim((string) ($values['secret'] ?? ''));
        $bucket = trim((string) ($values['bucket'] ?? ''));

        if ($key === '' || $secret === '' || $bucket === '') {
            return null;
        }

        return [
            'key' => $key,
            'secret' => $secret,
            'region' => trim((string) ($values['region'] ?? 'auto')) ?: 'auto',
            'bucket' => $bucket,
            'url' => rtrim(trim((string) ($values['url'] ?? '')), '/'),
            'endpoint' => rtrim(trim((string) ($values['endpoint'] ?? '')), '/'),
            'use_path_style_endpoint' => self::pathStyleFlag($values['use_path_style_endpoint'] ?? true),
        ];
    }

    /**
     * @return array<string, string>|null
     */
    private static function credentialsFromEnv(): ?array
    {
        $key = trim((string) env('AWS_ACCESS_KEY_ID', ''));
        $secret = trim((string) env('AWS_SECRET_ACCESS_KEY', ''));
        $bucket = trim((string) env('AWS_BUCKET', ''));
        $endpoint = trim((string) env('AWS_ENDPOINT', ''));

        if ($key === '' || $secret === '' || $bucket === '' || $endpoint === '') {
            return null;
        }

        return [
            'key' => $key,
            'secret' => $secret,
            'region' => trim((string) env('AWS_DEFAULT_REGION', 'auto')) ?: 'auto',
            'bucket' => $bucket,
            'url' => rtrim(trim((string) env('AWS_URL', '')), '/'),
            'endpoint' => rtrim($endpoint, '/'),
            'use_path_style_endpoint' => self::pathStyleFlag(env('AWS_USE_PATH_STYLE_ENDPOINT', true)),
        ];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>
     */
    private static function normalizeDiskConfig(array $credentials): array
    {
        return [
            'driver' => 's3',
            'key' => $credentials['key'],
            'secret' => $credentials['secret'],
            'region' => $credentials['region'] ?: 'auto',
            'bucket' => $credentials['bucket'],
            'url' => rtrim((string) ($credentials['url'] ?? ''), '/'),
            'endpoint' => rtrim((string) ($credentials['endpoint'] ?? ''), '/'),
            'use_path_style_endpoint' => self::pathStyleFlag($credentials['use_path_style_endpoint'] ?? true),
            'visibility' => 'public',
            'throw' => false,
            'http' => [
                'connect_timeout' => 2,
                'timeout' => 3,
            ],
        ];
    }

    private static function pathStyleFlag(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return ! in_array($normalized, ['0', 'false', 'no', 'off'], true);
    }
}
