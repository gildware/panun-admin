<?php

/**
 * Update remaining live service variants (images, keys, descriptions, notes).
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/update-remaining-categories-variants-live.php
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

$liveConnection = 'live_remaining_variants';
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

$services = require base_path('scripts/data/remaining-variants-manifest.php');

$resolveIconPath = static function (string $slug, string $variantKey): ?string {
    $candidates = [
        base_path('scripts/assets/variant-icons/'.$slug.'-'.$variantKey.'.png'),
        base_path('scripts/assets/variant-icons/'.$variantKey.'.png'),
    ];

    if ($variantKey === 'book-site-inspection') {
        $candidates[] = base_path('scripts/assets/variant-icons/book-site-inspection.png');
    }

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

$matchesToken = static function (ServiceVariant $variant, string $token): bool {
    $haystack = strtolower(trim($variant->variant_key.' '.$variant->title));

    return str_contains($haystack, strtolower($token));
};

$pickVariant = static function ($variants, array $config, array $usedIds) use ($matchesToken): ?ServiceVariant {
    foreach (array_merge([$config['variant_key']], $config['old_keys']) as $candidateKey) {
        $match = $variants->first(function (ServiceVariant $variant) use ($candidateKey, $usedIds) {
            return ! in_array($variant->id, $usedIds, true)
                && $variant->variant_key === $candidateKey;
        });
        if ($match) {
            return $match;
        }
    }

    foreach (['basic', 'premium', 'inspection'] as $token) {
        if (! str_contains($config['variant_key'], $token)) {
            continue;
        }
        $match = $variants->first(function (ServiceVariant $variant) use ($token, $usedIds, $matchesToken) {
            return ! in_array($variant->id, $usedIds, true) && $matchesToken($variant, $token);
        });
        if ($match) {
            return $match;
        }
    }

    return $variants->first(fn (ServiceVariant $variant) => ! in_array($variant->id, $usedIds, true));
};

$createVariant = function (Service $service, array $config, int $sortOrder) use ($liveConnection): array {
    $variantId = (string) Str::uuid();

    DB::connection($liveConnection)->table('service_variants')->insert([
        'id' => $variantId,
        'service_id' => $service->id,
        'variant_key' => $config['variant_key'],
        'title' => $config['title'],
        'description' => $config['description'],
        'note' => $config['note'],
        'image' => null,
        'sort_order' => $sortOrder,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $templateRows = Variation::on($liveConnection)
        ->where('service_id', $service->id)
        ->orderBy('id')
        ->get();
    $templateKey = (string) ($templateRows->pluck('variant_key')->filter()->first() ?? '');

    if ($templateKey !== '') {
        $rowsToClone = Variation::on($liveConnection)
            ->where('service_id', $service->id)
            ->where('variant_key', $templateKey)
            ->get();

        foreach ($rowsToClone as $row) {
            Variation::on($liveConnection)->create([
                'variant' => $config['title'],
                'variant_key' => $config['variant_key'],
                'zone_id' => $row->zone_id,
                'price' => $row->price,
                'service_id' => $service->id,
                'service_variant_id' => $variantId,
            ]);
        }
    }

    $pricing = is_array($service->variation_pricing) ? $service->variation_pricing : [];
    if (! isset($pricing[$config['variant_key']])) {
        if ($templateKey !== '' && isset($pricing[$templateKey])) {
            $pricing[$config['variant_key']] = $pricing[$templateKey];
        } else {
            $pricing[$config['variant_key']] = ['use_zone_pricing' => true, 'default_price' => 0];
        }
        Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([
            'variation_pricing' => json_encode($pricing),
        ]);
    }

    return [
        ServiceVariant::on($liveConnection)->where('id', $variantId)->firstOrFail(),
        $templateKey !== '' ? $templateKey : null,
    ];
};

foreach ($services as $slug => $serviceConfig) {
    $service = Service::on($liveConnection)->withoutGlobalScopes()->where('slug', $slug)->first();
    if (! $service) {
        echo "SKIP: service not found [{$slug}]\n";
        continue;
    }

    $existingVariants = ServiceVariant::on($liveConnection)
        ->withoutGlobalScopes()
        ->where('service_id', $service->id)
        ->orderBy('sort_order')
        ->get();

    $usedVariantIds = [];

    foreach ($serviceConfig['variants'] as $index => $config) {
        $variant = $pickVariant($existingVariants, $config, $usedVariantIds);
        $oldKeyUsed = $variant?->variant_key;
        $created = false;

        if (! $variant && ! empty($config['create_if_missing'])) {
            [$variant, $oldKeyUsed] = $createVariant($service, $config, $index);
            $existingVariants->push($variant);
            $created = true;
        }

        if (! $variant) {
            echo "SKIP: no variant for [{$slug}] / [{$config['variant_key']}]\n";
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
            'sort_order' => $index,
            'is_active' => true,
        ];
        if (Schema::connection($liveConnection)->hasColumn('service_variants', 'icon')) {
            $updatePayload['icon'] = null;
        }

        ServiceVariant::on($liveConnection)->where('id', $variant->id)->update($updatePayload);
        $upsertStorage($variant->id);

        if ($oldKeyUsed && $oldKeyUsed !== $config['variant_key']) {
            Variation::on($liveConnection)
                ->where('service_id', $service->id)
                ->where('variant_key', $oldKeyUsed)
                ->update([
                    'variant_key' => $config['variant_key'],
                    'variant' => $config['title'],
                    'service_variant_id' => $variant->id,
                ]);
        } elseif ($created) {
            Variation::on($liveConnection)
                ->where('service_id', $service->id)
                ->where('variant_key', $config['variant_key'])
                ->update([
                    'variant' => $config['title'],
                    'service_variant_id' => $variant->id,
                ]);
        }

        $service = Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->first();
        $pricing = is_array($service->variation_pricing) ? $service->variation_pricing : [];
        if ($oldKeyUsed && isset($pricing[$oldKeyUsed]) && $oldKeyUsed !== $config['variant_key']) {
            $pricing[$config['variant_key']] = $pricing[$oldKeyUsed];
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

        $usedVariantIds[] = $variant->id;

        echo ($created ? 'CREATED' : 'UPDATED').": {$service->name} [{$slug}]\n";
        echo '  key: '.($oldKeyUsed ?: 'new').' -> '.$config['variant_key']."\n";
        echo "  image: {$newImage}\n";
        echo '  zones: '.Variation::on($liveConnection)->where('service_id', $service->id)->where('variant_key', $config['variant_key'])->count()."\n\n";
    }
}

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update(['live_values' => $originalPrefix, 'test_values' => $originalPrefix]);
    StoragePathPrefix::resetCache();
}

echo "Done.\n";
