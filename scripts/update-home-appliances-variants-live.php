<?php

/**
 * Update Home Appliances service variants on live (images, keys, descriptions, notes).
 * AC Installation is handled by seed-ac-installation-live.php.
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/update-home-appliances-variants-live.php
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

$liveConnection = 'live_appliance_variants';
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

$services = require base_path('scripts/data/home-appliances-variants.php');

$resolveIconPath = static function (string $slug, string $variantKey): ?string {
    $candidates = [
        base_path('scripts/assets/variant-icons/'.$slug.'-'.$variantKey.'.png'),
        base_path('scripts/assets/variant-icons/'.$variantKey.'.png'),
        base_path('scripts/assets/variant-icons/book-site-inspection.png'),
    ];
    foreach ($candidates as $path) {
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
            ->where('variant_key', $config['variant_key'])
            ->first();
        $oldKeyUsed = $config['variant_key'];
    }

    if (! $variant) {
        $variant = ServiceVariant::on($liveConnection)->where('service_id', $service->id)->first();
        $oldKeyUsed = $variant?->variant_key;
    }

    if (! $variant) {
        echo "SKIP: no variant for [{$slug}]\n";
        continue;
    }

    $iconPath = $resolveIconPath($slug, $config['variant_key']);
    if (! $iconPath) {
        throw new RuntimeException("Missing variant icon for {$slug} / {$config['variant_key']}");
    }

    $storageDir = MediaStoragePath::serviceDir($service);
    $newImage = $uploadAsset($iconPath, $storageDir, $variant->image);

    $updatePayload = [
        'variant_key' => $config['variant_key'],
        'title' => $config['title'],
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

    $newVariantKey = $config['variant_key'];
    if ($oldKeyUsed && $oldKeyUsed !== $newVariantKey) {
        Variation::on($liveConnection)
            ->where('service_id', $service->id)
            ->where('variant_key', $oldKeyUsed)
            ->update(['variant_key' => $newVariantKey, 'variant' => $config['title']]);
    }

    $pricing = is_array($service->variation_pricing) ? $service->variation_pricing : [];
    if ($oldKeyUsed && isset($pricing[$oldKeyUsed]) && $oldKeyUsed !== $newVariantKey) {
        $pricing[$newVariantKey] = $pricing[$oldKeyUsed];
        unset($pricing[$oldKeyUsed]);
        Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([
            'variation_pricing' => json_encode($pricing),
        ]);
    }

    foreach (['title' => $config['title'], 'description' => $config['description']] as $field => $value) {
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
    Translation::on($liveConnection)->updateOrCreate(
        [
            'translationable_type' => ServiceVariant::class,
            'translationable_id' => $variant->id,
            'locale' => 'en',
            'key' => 'note',
        ],
        ['value' => (string) ($config['note'] ?? '')]
    );

    echo "UPDATED: {$service->name} [{$slug}]\n";
    echo "  key: {$oldKeyUsed} -> {$newVariantKey}\n";
    echo "  image: {$newImage}\n";
    echo '  zones: '.Variation::on($liveConnection)->where('service_id', $service->id)->where('variant_key', $newVariantKey)->count()."\n\n";
}

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update(['live_values' => $originalPrefix, 'test_values' => $originalPrefix]);
    StoragePathPrefix::resetCache();
}

echo "Done.\n";
