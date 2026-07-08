<?php

/**
 * Upload service thumbnail + cover images to R2 (prod) and update live DB.
 *
 * SERVICE_SLUG=door-installation \
 * DB_PASSWORD='...' php artisan tinker scripts/upload-service-images-live.php
 *
 * Optional: SERVICE_ID=uuid to target by id instead of slug.
 */

use App\Support\CloudStorageConfigurator;
use App\Support\MediaStoragePath;
use App\Support\StoragePathPrefix;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\ServiceManagement\Entities\Service;

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

$liveConnection = 'live_service_upload';
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

$slug = trim((string) env('SERVICE_SLUG', ''));
$serviceId = trim((string) env('SERVICE_ID', ''));

if ($slug === '' && $serviceId === '') {
    throw new RuntimeException('Set SERVICE_SLUG or SERVICE_ID.');
}

$query = Service::on($liveConnection)->withoutGlobalScopes();
$service = $serviceId !== ''
    ? $query->where('id', $serviceId)->first()
    : $query->where('slug', $slug)->first();

if (! $service) {
    throw new RuntimeException('Service not found on live DB.');
}

$assetSlug = $service->slug ?: Str::slug((string) $service->name, '-');
$assetsDir = base_path('scripts/assets/service-images/'.$assetSlug);

$thumbnailPath = $assetsDir.'/thumbnail.png';
$coverPath = $assetsDir.'/cover.png';

foreach (['thumbnail' => $thumbnailPath, 'cover' => $coverPath] as $label => $path) {
    if (! is_file($path)) {
        throw new RuntimeException("Missing {$label} asset: {$path}");
    }
}

$uploadAsset = function (string $sourcePath, string $storageDir, ?string $old = null): string {
    $file = new UploadedFile($sourcePath, basename($sourcePath), 'image/png', null, true);

    return media_file_uploader($storageDir, APPLICATION_IMAGE_FORMAT, $file, $old);
};

$upsertStorage = function (string $serviceModelId, string $column) use ($liveConnection): void {
    $exists = DB::connection($liveConnection)->table('storages')
        ->where('model', Service::class)
        ->where('model_id', $serviceModelId)
        ->where('model_column', $column)
        ->exists();

    if ($exists) {
        DB::connection($liveConnection)->table('storages')
            ->where('model', Service::class)
            ->where('model_id', $serviceModelId)
            ->where('model_column', $column)
            ->update([
                'storage_type' => 's3',
                'updated_at' => now(),
            ]);

        return;
    }

    DB::connection($liveConnection)->table('storages')->insert([
        'id' => (string) Str::uuid(),
        'model' => Service::class,
        'model_id' => $serviceModelId,
        'model_column' => $column,
        'storage_type' => 's3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
};

$storageDir = MediaStoragePath::serviceDir($service);
$newThumbnail = $uploadAsset($thumbnailPath, $storageDir, $service->thumbnail);
$newCover = $uploadAsset($coverPath, $storageDir, $service->cover_image);

Service::on($liveConnection)
    ->withoutGlobalScopes()
    ->where('id', $service->id)
    ->update([
        'thumbnail' => $newThumbnail,
        'cover_image' => $newCover,
    ]);

$upsertStorage($service->id, 'thumbnail');
$upsertStorage($service->id, 'cover_image');

echo "UPDATED: {$service->name} [{$assetSlug}]\n";
echo "  thumbnail={$newThumbnail}\n";
echo "  cover_image={$newCover}\n";

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update([
        'live_values' => $originalPrefix,
        'test_values' => $originalPrefix,
    ]);
    StoragePathPrefix::resetCache();
}

echo "Done.\n";
