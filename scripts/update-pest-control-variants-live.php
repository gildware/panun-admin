<?php

/**
 * Replace all Pest Control variants with a single Book On Site Inspection (₹100) per service.
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/update-pest-control-variants-live.php
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
use Modules\ZoneManagement\Entities\Zone;

require_once base_path('scripts/lib/PestControlContentBuilder.php');

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

$liveConnection = 'live_pest_variants';
config(['database.connections.'.$liveConnection => [
    'driver' => 'mysql',
    'host' => env('IMPORT_DB_HOST', env('TARGET_DB_HOST', '82.25.121.201')),
    'port' => env('IMPORT_DB_PORT', env('TARGET_DB_PORT', '3306')),
    'database' => env('IMPORT_DB_DATABASE', env('TARGET_DB_DATABASE', 'u397782854_live_pk_dec')),
    'username' => env('IMPORT_DB_USERNAME', env('TARGET_DB_USERNAME', 'u397782854_live_pk_usr')),
    'password' => env('LIVE_DB_PASSWORD', env('IMPORT_DB_PASSWORD', env('TARGET_DB_PASSWORD', env('DB_PASSWORD', '')))),
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

$catalog = require base_path('scripts/data/pest-control-catalog.php');
$variantIconDir = base_path('scripts/assets/variant-icons');
$inspectionPrice = 100.0;
$variantKey = 'book-site-inspection';
$variantTitle = 'Book On Site Inspection';

$resolveIconPath = static function (string $slug) use ($variantIconDir): string {
    $candidates = [
        $variantIconDir.'/'.$slug.'-book-site-inspection.png',
        $variantIconDir.'/book-site-inspection.png',
    ];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    throw new RuntimeException("Missing inspection icon for {$slug}");
};

$uploadAsset = static function (string $sourcePath, string $storageDir, ?string $old = null): string {
    $file = new UploadedFile($sourcePath, basename($sourcePath), 'image/png', null, true);

    return media_file_uploader($storageDir, APPLICATION_IMAGE_FORMAT, $file, $old);
};

$upsertVariantStorage = static function (string $variantId) use ($liveConnection): void {
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
            ->update(['storage_type' => 's3', 'updated_at' => now()]);

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

$zones = Zone::on($liveConnection)->where('is_active', 1)->get();
if ($zones->isEmpty()) {
    throw new RuntimeException('No active zones on target DB.');
}

foreach ($catalog['services'] as $serviceSpec) {
    $slug = $serviceSpec['slug'];
    $name = $serviceSpec['name'];
    $service = Service::on($liveConnection)->withoutGlobalScopes()->where('slug', $slug)->first();

    if (! $service) {
        echo "SKIP missing service: {$slug}\n";
        continue;
    }

    $oldVariantIds = ServiceVariant::on($liveConnection)->where('service_id', $service->id)->pluck('id');
    if ($oldVariantIds->isNotEmpty()) {
        Translation::on($liveConnection)
            ->where('translationable_type', ServiceVariant::class)
            ->whereIn('translationable_id', $oldVariantIds->all())
            ->delete();
    }
    Variation::on($liveConnection)->where('service_id', $service->id)->delete();
    ServiceVariant::on($liveConnection)->where('service_id', $service->id)->delete();

    $description = PestControlContentBuilder::variantDescription($name, $variantTitle);
    $note = PestControlContentBuilder::variantNote($slug);
    $iconPath = $resolveIconPath($slug);
    $storageDir = MediaStoragePath::serviceDir($service);
    $imageKey = $uploadAsset($iconPath, $storageDir);

    $variant = ServiceVariant::on($liveConnection)->create([
        'service_id' => $service->id,
        'variant_key' => $variantKey,
        'title' => $variantTitle,
        'description' => $description,
        'note' => $note,
        'image' => $imageKey,
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $upsertVariantStorage($variant->id);

    foreach (['title' => $variantTitle, 'description' => $description, 'note' => $note] as $field => $value) {
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

    foreach ($zones as $zone) {
        Variation::on($liveConnection)->create([
            'service_id' => $service->id,
            'service_variant_id' => $variant->id,
            'variant_key' => $variantKey,
            'variant' => $variantTitle,
            'zone_id' => $zone->id,
            'price' => $inspectionPrice,
        ]);
    }

    Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([
        'min_bidding_price' => $inspectionPrice,
        'variation_pricing' => json_encode([
            $variantKey => [
                'use_zone_pricing' => false,
                'default_price' => $inspectionPrice,
            ],
        ]),
    ]);

    echo "UPDATED: {$name} — 1 variant @ ₹{$inspectionPrice} (removed {$oldVariantIds->count()} old)\n";
}

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update(['live_values' => $originalPrefix, 'test_values' => $originalPrefix]);
    StoragePathPrefix::resetCache();
}

echo "Done. All pest control services now have Book On Site Inspection @ ₹100.\n";
