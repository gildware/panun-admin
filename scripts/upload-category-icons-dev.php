<?php

/**
 * Upload navy-blue category icons to R2 (dev prefix) and update category.image in DB.
 *
 * Run on dev DB:
 *   DB_HOST=82.25.121.201 DB_DATABASE=u397782854_dev_pk_dec \
 *   DB_USERNAME=u397782854_dev_pk_dec_usr DB_PASSWORD='...' \
 *   php artisan tinker scripts/upload-category-icons-dev.php
 *
 * Local dry-run (uploads to dev/ on R2, updates connected DB):
 *   php artisan tinker scripts/upload-category-icons-dev.php
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
        'live_values' => 'dev',
        'test_values' => 'dev',
    ]);
}

StoragePathPrefix::resetCache();

if (! defined('APPLICATION_IMAGE_FORMAT')) {
    define('APPLICATION_IMAGE_FORMAT', 'webp');
}

$assetsDir = base_path('scripts/assets/category-icons');
$dryRun = filter_var(env('DRY_RUN', false), FILTER_VALIDATE_BOOLEAN);
$sqlOnly = filter_var(env('SQL_ONLY', false), FILTER_VALIDATE_BOOLEAN);

$slugAliases = [
    'home-appliances' => 'home-appliance',
    'geyser-service' => 'geysers',
];

$resolveAsset = function (Category $category) use ($assetsDir, $slugAliases): ?string {
    $candidates = [];
    $slug = (string) ($category->slug ?: '');
    if ($slug !== '') {
        $candidates[] = $slug;
        if (isset($slugAliases[$slug])) {
            $candidates[] = $slugAliases[$slug];
        }
    }
    $candidates[] = \Illuminate\Support\Str::slug((string) $category->name, '-');

    foreach (array_unique(array_filter($candidates)) as $base) {
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

$categories = Category::withoutGlobalScopes()
    ->orderBy('position')
    ->orderBy('name')
    ->get();

$updated = 0;
$skipped = 0;
$sql = [];

foreach ($categories as $category) {
    $asset = $resolveAsset($category);
    if ($asset === null) {
        echo "SKIP (no asset): {$category->name} [{$category->slug}]\n";
        $skipped++;
        continue;
    }

    $storageDir = MediaStoragePath::categoryDir($category);
    $type = (int) $category->position === 1 ? 'MAIN' : 'SUB';

    if ($dryRun) {
        echo "DRY RUN {$type}: {$category->name} <= ".basename($asset)." -> {$storageDir}\n";
        $updated++;
        continue;
    }

    if ($sqlOnly) {
        $fakeName = $storageDir.now()->toDateString().'-'.uniqid().'.webp';
        $sql[] = "UPDATE categories SET image = ".DB::getPdo()->quote($fakeName)." WHERE id = ".DB::getPdo()->quote($category->id).";";
        echo "SQL ONLY {$type}: {$category->name}\n";
        $updated++;
        continue;
    }

    $category->image = $uploadAsset($asset, $storageDir, $category->image);
    $category->save();

    echo "UPDATED {$type}: {$category->name} -> {$category->image}\n";
    $sql[] = "UPDATE categories SET image = ".DB::getPdo()->quote($category->image)." WHERE id = ".DB::getPdo()->quote($category->id).";";
    $updated++;
}

echo "\nDone. Updated: {$updated}, Skipped: {$skipped}\n";
echo 'Storage prefix: '.StoragePathPrefix::segment()."\n";

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update([
        'live_values' => $originalPrefix,
        'test_values' => $originalPrefix,
    ]);
    StoragePathPrefix::resetCache();
}

if ($sql !== []) {
    $sqlPath = base_path('scripts/category-icon-updates-dev.sql');
    file_put_contents($sqlPath, implode("\n", $sql)."\n");
    echo "Wrote SQL: {$sqlPath}\n";
}
