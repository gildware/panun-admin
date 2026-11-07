<?php

/**
 * Force-upload admin-module CSS to R2 under both the dev/ and prod/ prefixes.
 *
 * php artisan tinker --execute="require base_path('scripts/sync-admin-css-dev-prod-r2.php');"
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

$cssGlob = glob(public_path('assets/admin-module/css/*.css'));
if ($cssGlob === false || $cssGlob === []) {
    throw new RuntimeException('No CSS files found under public/assets/admin-module/css');
}

$cssFiles = array_values(array_map(static function (string $path): string {
    return 'admin-module/css/'.basename($path);
}, $cssGlob));

$extraAssets = [
    'chatting-module/css/staff-chat-entity-badges.css',
    'chatting-module/js/staff-chat-compose.js',
];
foreach ($extraAssets as $rel) {
    if (! is_file(public_path('assets/'.$rel))) {
        throw new RuntimeException('Missing asset: '.$rel);
    }
}

$allFiles = array_values(array_unique(array_merge($cssFiles, $extraAssets)));
$paths = implode(',', $allFiles);
echo 'Syncing '.count($allFiles).' asset files (admin CSS + chatting CSS/JS)'.PHP_EOL;

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
    foreach (['dev', 'prod'] as $prefix) {
        if ($prefixSetting) {
            $prefixSetting->update([
                'live_values' => $prefix,
                'test_values' => $prefix,
            ]);
        }
        StoragePathPrefix::resetCache();

        echo 'Uploading with prefix: '.StoragePathPrefix::segment().PHP_EOL;

        $exit = Artisan::call('assets:sync-to-r2', [
            '--force' => true,
            '--paths' => $paths,
        ]);
        echo Artisan::output();

        if ($exit !== 0) {
            throw new RuntimeException('assets:sync-to-r2 failed for '.$prefix.' with exit '.$exit);
        }

        foreach ([
            'admin-module/css/top-nav.css',
            'admin-module/css/lead-detail-redesign.css',
            'admin-module/css/booking-list-compact.css',
            'admin-module/css/style.css',
            'admin-module/css/custom.css',
            'chatting-module/css/staff-chat-entity-badges.css',
            'chatting-module/js/staff-chat-compose.js',
        ] as $rel) {
            $key = $prefix.'/assets/'.$rel;
            $exists = Storage::disk('s3')->exists($key);
            $size = $exists ? strlen((string) Storage::disk('s3')->get($key)) : 0;
            echo ($exists ? 'OK' : 'MISSING')." {$key} size={$size}".PHP_EOL;
        }
    }
} finally {
    $restore();
    echo 'Restored storage prefix to: '.($originalPrefix ?? '(null)').PHP_EOL;
}

echo 'DONE'.PHP_EOL;
