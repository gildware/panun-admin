<?php

/**
 * Force-upload top-nav.css / top-nav.js to R2 under the prod/ prefix (live CDN).
 *
 * php artisan tinker scripts/sync-top-nav-assets-prod-r2.php
 */

use App\Support\CloudStorageConfigurator;
use App\Support\StoragePathPrefix;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;

CloudStorageConfigurator::apply();

if (! CloudStorageConfigurator::isConfigured()) {
    throw new RuntimeException('R2/S3 is not configured (Admin → Storage Connection).');
}

$prefixSetting = BusinessSettings::query()
    ->where('key_name', 'storage_path_prefix')
    ->where('settings_type', 'storage_settings')
    ->first();

$originalPrefix = $prefixSetting?->live_values;
$restored = false;

$restore = static function () use ($prefixSetting, $originalPrefix, &$restored): void {
    if ($restored || ! $prefixSetting) {
        return;
    }
    $prefixSetting->update([
        'live_values' => $originalPrefix,
        'test_values' => $originalPrefix,
    ]);
    StoragePathPrefix::resetCache();
    $restored = true;
};

try {
    if ($prefixSetting) {
        $prefixSetting->update([
            'live_values' => 'prod',
            'test_values' => 'prod',
        ]);
    }
    StoragePathPrefix::resetCache();

    echo 'Uploading with prefix: '.StoragePathPrefix::segment().PHP_EOL;

    $exit = Artisan::call('assets:sync-to-r2', [
        '--force' => true,
        '--paths' => 'admin-module/css/top-nav.css,admin-module/js/top-nav.js',
    ]);
    echo Artisan::output();

    $disk = Storage::disk('s3');
    foreach ([
        'prod/assets/admin-module/css/top-nav.css',
        'prod/assets/admin-module/js/top-nav.js',
    ] as $key) {
        $exists = $disk->exists($key);
        $size = $exists ? strlen((string) $disk->get($key)) : 0;
        echo ($exists ? 'OK' : 'MISSING')." {$key} size={$size}".PHP_EOL;
    }

    if ($exit !== 0) {
        throw new RuntimeException('assets:sync-to-r2 failed with exit '.$exit);
    }
} finally {
    $restore();
    echo 'Restored storage prefix to: '.($originalPrefix ?? '(null)').PHP_EOL;
}

echo 'DONE'.PHP_EOL;
