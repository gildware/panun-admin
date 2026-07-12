<?php

/**
 * Upload service variant icon images to R2 (prod) and update live DB.
 *
 * LIVE_DB_PASSWORD='...' SERVICE_SLUG=door-installation VARIANT_KEY=book-site-inspection \
 * php artisan tinker scripts/upload-variant-icons-live.php
 *
 * Optional: SERVICE_ID=uuid to target by id instead of slug.
 * Asset path: scripts/assets/variant-icons/{variant-key}.png
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

$liveConnection = 'live_variant_upload';
config(['database.connections.'.$liveConnection => [
    'driver' => 'mysql',
    'host' => '82.25.121.201',
    'port' => '3306',
    'database' => 'u397782854_live_pk_dec',
    'username' => 'u397782854_live_pk_usr',
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

$slug = trim((string) env('SERVICE_SLUG', ''));
$serviceId = trim((string) env('SERVICE_ID', ''));
$variantKey = trim((string) env('VARIANT_KEY', ''));

if (($slug === '' && $serviceId === '') || $variantKey === '') {
    throw new RuntimeException('Set SERVICE_SLUG (or SERVICE_ID) and VARIANT_KEY.');
}

$query = Service::on($liveConnection)->withoutGlobalScopes();
$service = $serviceId !== ''
    ? $query->where('id', $serviceId)->first()
    : $query->where('slug', $slug)->first();

if (! $service) {
    throw new RuntimeException('Service not found on live DB.');
}

$variant = ServiceVariant::on($liveConnection)
    ->where('service_id', $service->id)
    ->where('variant_key', $variantKey)
    ->first();

if (! $variant) {
    throw new RuntimeException("Variant not found on live DB: {$variantKey}");
}

$iconPath = base_path('scripts/assets/variant-icons/'.$service->slug.'-'.$variantKey.'.png');
if (! is_file($iconPath)) {
    $iconPath = base_path('scripts/assets/variant-icons/'.$variantKey.'.png');
}
if (! is_file($iconPath)) {
    throw new RuntimeException("Missing variant icon asset for {$service->slug}/{$variantKey}");
}

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

$previousImage = $variant->image;
$storageDir = MediaStoragePath::serviceDir($service);
$newImage = $uploadAsset($iconPath, $storageDir, $previousImage);

$updatePayload = ['image' => $newImage];
if (Schema::connection($liveConnection)->hasColumn('service_variants', 'icon')) {
    $updatePayload['icon'] = null;
}

ServiceVariant::on($liveConnection)
    ->where('id', $variant->id)
    ->update($updatePayload);

$upsertStorage($variant->id);

echo "UPDATED: {$service->name} / {$variant->title} [{$variantKey}]\n";
echo "  image={$newImage}\n";
if (isset($updatePayload['icon'])) {
    echo "  icon=cleared\n";
}

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update([
        'live_values' => $originalPrefix,
        'test_values' => $originalPrefix,
    ]);
    StoragePathPrefix::resetCache();
}

echo "Done.\n";
