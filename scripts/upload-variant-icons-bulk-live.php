<?php

/**
 * Bulk-upload fixed variation icons from audit manifest to live DB.
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/upload-variant-icons-bulk-live.php
 *
 * Optional:
 *   MANIFEST=/tmp/pk-variant-fix-manifest.json
 *   LIMIT=50
 */

use App\Support\CloudStorageConfigurator;
use App\Support\MediaStoragePath;
use App\Support\StoragePathPrefix;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;

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

$liveConnection = 'live_variant_bulk';
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
    throw new RuntimeException('Set LIVE_DB_PASSWORD for live database.');
}

if (! defined('APPLICATION_IMAGE_FORMAT')) {
    define('APPLICATION_IMAGE_FORMAT', 'webp');
}

$manifestPath = env('MANIFEST', '/tmp/pk-variant-fix-manifest.json');
if (! is_file($manifestPath)) {
    throw new RuntimeException("Missing manifest: {$manifestPath}");
}

$items = json_decode((string) file_get_contents($manifestPath), true);
if (! is_array($items)) {
    throw new RuntimeException('Invalid manifest JSON.');
}

$limit = (int) env('LIMIT', 0);
if ($limit > 0) {
    $items = array_slice($items, 0, $limit);
}

$assetsDir = base_path('scripts/assets/variant-icons');

$uploadAsset = function (string $sourcePath, string $storageDir, ?string $old = null): string {
    $file = new UploadedFile($sourcePath, basename($sourcePath), 'image/png', null, true);

    return media_file_uploader($storageDir, APPLICATION_IMAGE_FORMAT, $file, $old);
};

$upsertStorage = function (string $variantId) use ($liveConnection): void {
    $exists = DB::connection($liveConnection)->table('storages')
        ->where('model', ServiceVariant::class)
        ->where('model_id', $variantId)
        ->where('model_column', 'image')
        ->exists();

    if ($exists) {
        DB::connection($liveConnection)->table('storages')
            ->where('model', ServiceVariant::class)
            ->where('model_id', $variantId)
            ->where('model_column', 'image')
            ->update([
                'storage_type' => 's3',
                'updated_at' => now(),
            ]);

        return;
    }

    DB::connection($liveConnection)->table('storages')->insert([
        'id' => (string) Str::uuid(),
        'model' => ServiceVariant::class,
        'model_id' => $variantId,
        'model_column' => 'image',
        'storage_type' => 's3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
};

$updated = 0;
$skipped = 0;
$errors = 0;

echo 'Uploading '.count($items)." variant icons\n";
echo 'Storage prefix: '.StoragePathPrefix::segment()."\n";

foreach ($items as $item) {
    $slug = (string) ($item['service_slug'] ?? '');
    $key = (string) ($item['variant_key'] ?? '');
    $assetName = (string) ($item['asset'] ?? ($slug.'-'.$key.'.png'));

    $iconPath = $assetsDir.'/'.$assetName;
    if (! is_file($iconPath)) {
        $iconPath = $assetsDir.'/'.$slug.'-'.$key.'.png';
    }
    if (! is_file($iconPath)) {
        echo "SKIP missing asset: {$slug}/{$key}\n";
        $skipped++;
        continue;
    }

    $service = Service::on($liveConnection)->withoutGlobalScopes()->where('slug', $slug)->first();
    if (! $service) {
        echo "SKIP service not found: {$slug}\n";
        $skipped++;
        continue;
    }

    $variant = ServiceVariant::on($liveConnection)
        ->where('service_id', $service->id)
        ->where('variant_key', $key)
        ->first();

    if (! $variant) {
        echo "SKIP variant not found: {$slug}/{$key}\n";
        $skipped++;
        continue;
    }

    try {
        $storageDir = MediaStoragePath::serviceDir($service);
        $newImage = $uploadAsset($iconPath, $storageDir, $variant->image);

        $updatePayload = ['image' => $newImage];
        if (Schema::connection($liveConnection)->hasColumn('service_variants', 'icon')) {
            $updatePayload['icon'] = null;
        }

        ServiceVariant::on($liveConnection)
            ->where('id', $variant->id)
            ->update($updatePayload);

        $upsertStorage($variant->id);

        echo "UPDATED: {$service->name} / {$variant->title} -> {$newImage}\n";
        $updated++;
    } catch (Throwable $e) {
        echo "ERROR: {$slug}/{$key} ".$e->getMessage()."\n";
        $errors++;
    }
}

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update([
        'live_values' => $originalPrefix,
        'test_values' => $originalPrefix,
    ]);
    StoragePathPrefix::resetCache();
}

echo "\nDone. Updated: {$updated}, Skipped: {$skipped}, Errors: {$errors}\n";
