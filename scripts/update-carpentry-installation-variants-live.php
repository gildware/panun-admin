<?php

/**
 * Update Book Site Inspection variants for all Carpentry Installation services on live.
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/update-carpentry-installation-variants-live.php
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

$liveConnection = 'live_carpentry_variants';
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

if (! Schema::connection($liveConnection)->hasColumn('service_variants', 'note')) {
    Schema::connection($liveConnection)->table('service_variants', function ($table) {
        $table->text('note')->nullable()->after('description');
    });
    echo "Added note column to service_variants on live.\n";
}

$newVariantKey = 'book-site-inspection';
$title = 'Book Site Inspection';
$iconPath = base_path('scripts/assets/variant-icons/book-site-inspection.png');

$resolveIconPath = static function (string $slug) use ($iconPath): string {
    $specific = base_path('scripts/assets/variant-icons/'.$slug.'-book-site-inspection.png');
    if (is_file($specific)) {
        return $specific;
    }

    return $iconPath;
};

if (! is_file($iconPath)) {
    throw new RuntimeException("Missing fallback icon asset: {$iconPath}");
}

$services = [
    'furniture-installation' => [
        'old_keys' => ['Book-at-Home-Consultation', 'Book--at-Home-Consultation'],
        'description' => 'Verified carpenter inspects your space, furniture pieces and installation points on site.',
        'note' => 'This inspection fee will be adjusted against your final furniture installation bill if you proceed with the full service through Panun Kaergar.',
    ],
    'kitchen-cabinet-installation' => [
        'old_keys' => ['Book-at-Home-Consultation', 'Book--at-Home-Consultation'],
        'description' => 'Verified carpenter inspects your kitchen layout, cabinet measurements and fitment on site.',
        'note' => 'This inspection fee will be adjusted against your final kitchen cabinet installation bill if you proceed with the full service through Panun Kaergar.',
    ],
    'wardrobe-installation' => [
        'old_keys' => ['Book-at-Home-Consultation', 'Book--at-Home-Consultation'],
        'description' => 'Verified carpenter inspects your room dimensions, wardrobe fitment and wall conditions on site.',
        'note' => 'This inspection fee will be adjusted against your final wardrobe installation bill if you proceed with the full service through Panun Kaergar.',
    ],
    'wooden-panel-installation' => [
        'old_keys' => ['Book-at-Home-Consultation', 'Book--at-Home-Consultation'],
        'description' => 'Verified carpenter inspects your wall surface, panel layout and measurements on site.',
        'note' => 'This inspection fee will be adjusted against your final wooden panel installation bill if you proceed with the full service through Panun Kaergar.',
    ],
    'roof-installation' => [
        'old_keys' => ['Book--at-Home-Consultation', 'Book-at-Home-Consultation'],
        'description' => 'Verified carpenter inspects your roof structure, beam layout and installation scope on site.',
        'note' => 'This inspection fee will be adjusted against your final roof installation bill if you proceed with the full service through Panun Kaergar.',
    ],
];

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

foreach ($services as $slug => $config) {
    $service = Service::on($liveConnection)->withoutGlobalScopes()->where('slug', $slug)->first();
    if (! $service) {
        echo "SKIP: service not found [{$slug}]\n";
        continue;
    }

    $variant = null;
    $oldKeyUsed = null;
    foreach ($config['old_keys'] as $oldKey) {
        $variant = ServiceVariant::on($liveConnection)
            ->where('service_id', $service->id)
            ->where('variant_key', $oldKey)
            ->first();
        if ($variant) {
            $oldKeyUsed = $oldKey;
            break;
        }
    }

    if (! $variant) {
        $variant = ServiceVariant::on($liveConnection)
            ->where('service_id', $service->id)
            ->where('variant_key', $newVariantKey)
            ->first();
        $oldKeyUsed = $newVariantKey;
    }

    if (! $variant) {
        $variant = ServiceVariant::on($liveConnection)->where('service_id', $service->id)->first();
        $oldKeyUsed = $variant?->variant_key;
    }

    if (! $variant) {
        echo "SKIP: no variant for [{$slug}]\n";
        continue;
    }

    $storageDir = MediaStoragePath::serviceDir($service);
    $serviceIconPath = $resolveIconPath($slug);
    $newImage = $uploadAsset($serviceIconPath, $storageDir, $variant->image);

    $updatePayload = [
        'variant_key' => $newVariantKey,
        'title' => $title,
        'description' => $config['description'],
        'note' => $config['note'],
        'image' => $newImage,
        'is_active' => true,
    ];
    if (Schema::connection($liveConnection)->hasColumn('service_variants', 'icon')) {
        $updatePayload['icon'] = null;
    }

    ServiceVariant::on($liveConnection)->where('id', $variant->id)->update($updatePayload);
    $upsertStorage($variant->id);

    if ($oldKeyUsed && $oldKeyUsed !== $newVariantKey) {
        Variation::on($liveConnection)
            ->where('service_id', $service->id)
            ->where('variant_key', $oldKeyUsed)
            ->update(['variant_key' => $newVariantKey, 'variant' => $title]);
    }

    $pricing = is_array($service->variation_pricing) ? $service->variation_pricing : [];
    if ($oldKeyUsed && isset($pricing[$oldKeyUsed]) && $oldKeyUsed !== $newVariantKey) {
        $pricing[$newVariantKey] = $pricing[$oldKeyUsed];
        unset($pricing[$oldKeyUsed]);
        Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([
            'variation_pricing' => json_encode($pricing),
        ]);
    }

    foreach (['title' => $title, 'description' => $config['description'], 'note' => $config['note']] as $field => $value) {
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

    echo "UPDATED: {$service->name} [{$slug}]\n";
    echo "  key: {$oldKeyUsed} -> {$newVariantKey}\n";
    echo "  description: {$config['description']}\n";
    echo "  note: {$config['note']}\n";
    echo "  image: {$newImage}\n";
    echo '  zones: '.Variation::on($liveConnection)->where('service_id', $service->id)->where('variant_key', $newVariantKey)->count()."\n\n";
}

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update(['live_values' => $originalPrefix, 'test_values' => $originalPrefix]);
    StoragePathPrefix::resetCache();
}

echo "Done.\n";
