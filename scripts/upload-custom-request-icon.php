<?php

/**
 * Upload custom_request menu icons (light + dark) to storage and local DB.
 *
 * php artisan tinker scripts/upload-custom-request-icon.php
 *
 * Optional:
 *   MENU_ICONS_DRY_RUN=1
 */

use Illuminate\Http\UploadedFile;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Services\MobileAppManagementService;

$dryRun = (string) env('MENU_ICONS_DRY_RUN', '0') === '1';

if (! defined('APPLICATION_IMAGE_FORMAT')) {
    define('APPLICATION_IMAGE_FORMAT', 'webp');
}

$assetsRoot = realpath(base_path('../design-previews/menu-icons/customer'));
if ($assetsRoot === false) {
    throw new RuntimeException('design-previews/menu-icons/customer folder not found.');
}

$iconKey = 'custom_request';
$app = 'customer';

$resolveAsset = function (string $variant) use ($assetsRoot, $iconKey): ?string {
    $path = $assetsRoot.'/'.$iconKey.'_icon_'.$variant.'.png';
    if (is_file($path)) {
        return $path;
    }

    return null;
};

$uploadAsset = function (string $sourcePath, ?string $old = null): string {
    $file = new UploadedFile($sourcePath, basename($sourcePath), 'image/png', null, true);

    return file_uploader('mobile-app/', APPLICATION_IMAGE_FORMAT, $file, $old);
};

$normalizeEntry = function (mixed $value): array {
    return MobileAppManagementService::normalizeIconEntry($value);
};

$settingsRow = BusinessSettings::query()
    ->where('key_name', MobileAppManagementService::ICONS_KEY)
    ->where('settings_type', MobileAppManagementService::SETTINGS_TYPE)
    ->first();

$stored = ['customer' => [], 'provider' => []];
$raw = $settingsRow?->live_values;
if (is_string($raw)) {
    $decoded = json_decode($raw, true);
    $raw = is_array($decoded) ? $decoded : [];
}
if (is_array($raw)) {
    foreach (['customer', 'provider'] as $appKey) {
        foreach ($raw[$appKey] ?? [] as $key => $value) {
            $stored[$appKey][$key] = $normalizeEntry($value);
        }
    }
}

echo 'Upload custom_request menu icons'.($dryRun ? ' (DRY RUN)' : '')."\n";
echo 'Assets root: '.$assetsRoot."\n\n";

$updated = 0;
$missing = 0;

foreach (MobileAppManagementService::ICON_VARIANTS as $variant) {
    $asset = $resolveAsset($variant);
    if ($asset === null) {
        echo "MISSING: {$app}/{$iconKey}/{$variant}\n";
        $missing++;
        continue;
    }

    $existing = $stored[$app][$iconKey][$variant] ?? null;

    if ($dryRun) {
        echo "DRY RUN: {$app}/{$iconKey}/{$variant} <- {$asset}\n";
        $updated++;
        continue;
    }

    $filename = $uploadAsset($asset, $existing);
    if (! $filename || $filename === 'def.png') {
        echo "SKIP (upload failed): {$app}/{$iconKey}/{$variant}\n";
        continue;
    }

    $stored[$app][$iconKey] = $stored[$app][$iconKey] ?? ['light' => null, 'dark' => null];
    $stored[$app][$iconKey][$variant] = $filename;
    echo "UPDATED: {$app}/{$iconKey}/{$variant} -> {$filename}\n";
    $updated++;
}

if (! $dryRun) {
    BusinessSettings::query()->updateOrCreate(
        [
            'key_name' => MobileAppManagementService::ICONS_KEY,
            'settings_type' => MobileAppManagementService::SETTINGS_TYPE,
        ],
        [
            'live_values' => $stored,
            'test_values' => $stored,
            'mode' => 'live',
            'is_active' => 1,
        ],
    );
}

echo "\nDone. Updated: {$updated}, Missing: {$missing}\n";
