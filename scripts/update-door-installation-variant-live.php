<?php

/**
 * Update Door Installation "Book Site Inspection" variant on live DB.
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/update-door-installation-variant-live.php
 */

use App\Support\CloudStorageConfigurator;
use App\Support\MediaStoragePath;
use App\Support\StoragePathPrefix;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;
use Modules\ServiceManagement\Entities\Variation;

CloudStorageConfigurator::apply();

$prefixSetting = BusinessSettings::query()
    ->where('key_name', 'storage_path_prefix')
    ->where('settings_type', 'storage_settings')
    ->first();

$originalPrefix = $prefixSetting?->live_values;
if ($prefixSetting) {
    $prefixSetting->update(['live_values' => 'prod', 'test_values' => 'prod']);
}
StoragePathPrefix::resetCache();

$liveConnection = 'live_service_content';
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

if (! Schema::connection($liveConnection)->hasColumn('service_variants', 'note')) {
    Schema::connection($liveConnection)->table('service_variants', function ($table) {
        $table->text('note')->nullable()->after('description');
    });
    echo "Added note column to service_variants on live.\n";
}

if (! defined('APPLICATION_IMAGE_FORMAT')) {
    define('APPLICATION_IMAGE_FORMAT', 'webp');
}

$serviceId = '7ae680f7-97ed-464e-87e1-5da2aaae55c5';
$oldVariantKey = 'Book--at-Home-Consultation';
$newVariantKey = 'book-site-inspection';
$title = 'Book Site Inspection';
$description = 'Verified carpenter inspects your door opening, frame and measurements on site.';
$note = 'This inspection fee will be adjusted against your final door installation bill if you proceed with the full service through Panun Kaergar.';

$assetPath = base_path('scripts/assets/service-images/door-installation/variant-site-inspection.png');
if (! is_file($assetPath)) {
    throw new RuntimeException("Missing variant image asset: {$assetPath}");
}

$service = Service::on($liveConnection)->withoutGlobalScopes()->find($serviceId);
if (! $service) {
    throw new RuntimeException("Service not found: {$serviceId}");
}

$variant = ServiceVariant::on($liveConnection)
    ->where('service_id', $serviceId)
    ->where('variant_key', $oldVariantKey)
    ->first();

if (! $variant) {
    $variant = ServiceVariant::on($liveConnection)
        ->where('service_id', $serviceId)
        ->where('variant_key', $newVariantKey)
        ->first();
}

if (! $variant) {
    $variant = ServiceVariant::on($liveConnection)
        ->where('service_id', $serviceId)
        ->first();
}

if (! $variant) {
    throw new RuntimeException('Service variant not found for Door Installation.');
}

$storageDir = MediaStoragePath::serviceDir($service);
$file = new UploadedFile($assetPath, basename($assetPath), 'image/png', null, true);
$imageKey = media_file_uploader($storageDir, APPLICATION_IMAGE_FORMAT, $file, $variant->image);

$exists = DB::connection($liveConnection)->table('storages')
    ->where('model', ServiceVariant::class)
    ->where('model_id', $variant->id)
    ->where('model_column', 'image')
    ->exists();

if ($exists) {
    DB::connection($liveConnection)->table('storages')
        ->where('model', ServiceVariant::class)
        ->where('model_id', $variant->id)
        ->where('model_column', 'image')
        ->update(['storage_type' => 's3', 'updated_at' => now()]);
} else {
    DB::connection($liveConnection)->table('storages')->insert([
        'id' => (string) Str::uuid(),
        'model' => ServiceVariant::class,
        'model_id' => $variant->id,
        'model_column' => 'image',
        'storage_type' => 's3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

ServiceVariant::on($liveConnection)->where('id', $variant->id)->update([
    'variant_key' => $newVariantKey,
    'title' => $title,
    'description' => $description,
    'note' => $note,
    'image' => $imageKey,
    'is_active' => true,
]);

Variation::on($liveConnection)
    ->where('service_id', $serviceId)
    ->where('variant_key', $oldVariantKey)
    ->update([
        'variant_key' => $newVariantKey,
        'variant' => $title,
    ]);

$pricing = is_array($service->variation_pricing) ? $service->variation_pricing : [];
if (isset($pricing[$oldVariantKey])) {
    $pricing[$newVariantKey] = $pricing[$oldVariantKey];
    unset($pricing[$oldVariantKey]);
} elseif (! isset($pricing[$newVariantKey])) {
    $pricing[$newVariantKey] = [
        'use_zone_pricing' => false,
        'default_price' => 50,
    ];
}

Service::on($liveConnection)->withoutGlobalScopes()->where('id', $serviceId)->update([
    'variation_pricing' => json_encode($pricing),
]);

foreach (['title' => $title, 'description' => $description, 'note' => $note] as $field => $value) {
    Translation::on($liveConnection)->updateOrCreate(
        [
            'translationable_type' => ServiceVariant::class,
            'translationable_id' => $variant->id,
            'locale' => 'en',
            'key' => $field,
        ],
        ['value' => $value]
    );
}

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update(['live_values' => $originalPrefix, 'test_values' => $originalPrefix]);
    StoragePathPrefix::resetCache();
}

echo "Updated Door Installation variant on live.\n";
echo "  title: {$title}\n";
echo "  key: {$newVariantKey}\n";
echo "  description: {$description}\n";
echo "  note: {$note}\n";
echo "  image: {$imageKey}\n";
echo '  zones: '.Variation::on($liveConnection)->where('service_id', $serviceId)->where('variant_key', $newVariantKey)->count()."\n";
