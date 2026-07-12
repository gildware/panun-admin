<?php

/**
 * Upload light + dark icon pairs for specific category slugs on live.
 *
 * SLUGS=carpentary,carpentry-installation,carpentry-repairs \
 * DB_PASSWORD='...' php artisan tinker scripts/upload-category-icons-live-slugs.php
 */

use App\Support\CloudStorageConfigurator;
use App\Support\MediaStoragePath;
use App\Support\StoragePathPrefix;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
    'host' => env('LIVE_DB_HOST', '82.25.121.201'),
    'port' => env('LIVE_DB_PORT', '3306'),
    'database' => env('LIVE_DB_DATABASE', 'u397782854_live_pk_dec'),
    'username' => env('LIVE_DB_USERNAME', 'u397782854_live_pk_usr'),
    'password' => env('LIVE_DB_PASSWORD', env('DB_PASSWORD', '')),
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

$slugFilter = array_values(array_filter(array_map('trim', explode(',', (string) env('SLUGS', '')))));
$lightDir = base_path('scripts/category-icons/light');
$darkDir = base_path('scripts/category-icons/dark');

$resolveAsset = function (Category $category, string $dir) use ($lightDir, $darkDir): ?string {
    $baseDir = $dir === 'dark' ? $darkDir : $lightDir;
    $slug = (string) ($category->slug ?: '');
    $candidates = array_filter([$slug, \Illuminate\Support\Str::slug((string) $category->name, '-')]);

    foreach (array_unique($candidates) as $base) {
        $path = $baseDir.'/'.$base.'.png';
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

$upsertStorage = function (string $categoryId, string $column) use ($liveConnection): void {
    $exists = DB::connection($liveConnection)->table('storages')
        ->where('model', Category::class)
        ->where('model_id', $categoryId)
        ->where('model_column', $column)
        ->exists();

    if ($exists) {
        DB::connection($liveConnection)->table('storages')
            ->where('model', Category::class)
            ->where('model_id', $categoryId)
            ->where('model_column', $column)
            ->update([
                'storage_type' => 's3',
                'updated_at' => now(),
            ]);

        return;
    }

    DB::connection($liveConnection)->table('storages')->insert([
        'id' => (string) Str::uuid(),
        'model' => Category::class,
        'model_id' => $categoryId,
        'model_column' => $column,
        'storage_type' => 's3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
};

$query = Category::on($liveConnection)->withoutGlobalScopes()->orderBy('position')->orderBy('name');
if ($slugFilter !== []) {
    $query->whereIn('slug', $slugFilter);
}
$categories = $query->get();

echo 'Uploading '.$categories->count()." categories\n";
echo 'Storage prefix: '.StoragePathPrefix::segment()."\n";

$updated = 0;
$skipped = 0;

foreach ($categories as $category) {
    $lightAsset = $resolveAsset($category, 'light');
    $darkAsset = $resolveAsset($category, 'dark');

    if ($lightAsset === null || $darkAsset === null) {
        echo "SKIP (missing asset): {$category->name} [{$category->slug}]\n";
        $skipped++;
        continue;
    }

    $storageDir = MediaStoragePath::categoryDir($category);
    $newImage = $uploadAsset($lightAsset, $storageDir, $category->image);
    $newImageDark = $uploadAsset($darkAsset, $storageDir, $category->image_dark);

    Category::on($liveConnection)
        ->withoutGlobalScopes()
        ->where('id', $category->id)
        ->update([
            'image' => $newImage,
            'image_dark' => $newImageDark,
        ]);

    $upsertStorage($category->id, 'image');
    $upsertStorage($category->id, 'image_dark');

    echo "UPDATED: {$category->name} -> light={$newImage}, dark={$newImageDark}\n";
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
