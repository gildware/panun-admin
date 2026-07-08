<?php

/**
 * Upload filled main-category icons to R2 (prod prefix) and update live DB only.
 *
 * DB_HOST=82.25.121.201 DB_DATABASE=u397782854_live_pk_dec \
 * DB_USERNAME=u397782854_live_pk_usr DB_PASSWORD='...' \
 * php artisan tinker scripts/upload-category-icons-live-main.php
 */

use App\Support\CloudStorageConfigurator;
use App\Support\MediaStoragePath;
use App\Support\StoragePathPrefix;
use Illuminate\Http\UploadedFile;
use Modules\CategoryManagement\Entities\Category;

CloudStorageConfigurator::apply();

$prefixSetting = \Modules\BusinessSettingsModule\Entities\BusinessSettings::query()
    ->where('key_name', 'storage_path_prefix')
    ->where('settings_type', 'storage_settings')
    ->first();

$originalPrefix = $prefixSetting?->live_values;
if ($prefixSetting) {
    $prefixSetting->update([
        'live_values' => 'prod',
        'test_values' => 'prod',
    ]);
}
StoragePathPrefix::resetCache();

$liveConnection = 'live_category_upload';
config(['database.connections.'.$liveConnection => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '82.25.121.201'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'u397782854_live_pk_dec'),
    'username' => env('DB_USERNAME', 'u397782854_live_pk_usr'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
]]);

if ((string) config('database.connections.'.$liveConnection.'.password') === '') {
    throw new RuntimeException('Set DB_PASSWORD for live database.');
}

if (! defined('APPLICATION_IMAGE_FORMAT')) {
    define('APPLICATION_IMAGE_FORMAT', 'webp');
}

$assetsDir = base_path('scripts/assets/category-icons');

$resolveAsset = function (Category $category) use ($assetsDir): ?string {
    $slug = (string) ($category->slug ?: '');
    $candidates = array_filter([$slug, \Illuminate\Support\Str::slug((string) $category->name, '-')]);

    foreach (array_unique($candidates) as $base) {
        $path = $assetsDir.'/'.$base.'.png';
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
};

$uploadAsset = function (string $sourcePath, string $storageDir, ?string $old = null): string {
    $file = new UploadedFile($sourcePath, basename($sourcePath), 'image/png', null, true);

    return media_file_uploader($storageDir, APPLICATION_IMAGE_FORMAT, $file, $old);
};

$categories = Category::on($liveConnection)
    ->withoutGlobalScopes()
    ->where('position', 1)
    ->orderBy('name')
    ->get();

echo 'Main categories on live: '.$categories->count()."\n";
echo 'Storage prefix: '.StoragePathPrefix::segment()."\n";

$updated = 0;
$skipped = 0;

foreach ($categories as $category) {
    $asset = $resolveAsset($category);
    if ($asset === null) {
        echo "SKIP (no asset): {$category->name} [{$category->slug}]\n";
        $skipped++;
        continue;
    }

    $storageDir = MediaStoragePath::categoryDir($category);
    $newImage = $uploadAsset($asset, $storageDir, $category->image);

    Category::on($liveConnection)
        ->withoutGlobalScopes()
        ->where('id', $category->id)
        ->update(['image' => $newImage]);

    echo "UPDATED MAIN: {$category->name} -> {$newImage}\n";
    $updated++;
}

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update([
        'live_values' => $originalPrefix,
        'test_values' => $originalPrefix,
    ]);
    StoragePathPrefix::resetCache();
}

echo "\nDone. Updated: {$updated}, Skipped: {$skipped}\n";
