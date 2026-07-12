<?php

/**
 * Re-upload aluminium & steel works service images + variant icons on live (no variant recreate).
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/update-aluminium-steel-assets-live.php
 *
 * Optional: SERVICE_SLUG=acp-cladding-installation
 * Optional: VARIANTS_ONLY=1
 */

use App\Support\CloudStorageConfigurator;
use App\Support\MediaStoragePath;
use App\Support\StoragePathPrefix;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;
use Modules\ServiceManagement\Services\ServiceOverviewContentResolver;

require_once base_path('scripts/lib/AluminiumSteelContentBuilder.php');

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

$liveConnection = 'live_pest_assets';
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

$catalog = require base_path('scripts/data/aluminium-steel-catalog.php');
$onlySlug = trim((string) env('SERVICE_SLUG', ''));
$variantsOnly = filter_var(env('VARIANTS_ONLY', false), FILTER_VALIDATE_BOOLEAN);
$mediaBase = 'https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod/';

$uploadAsset = static function (string $sourcePath, string $storageDir, ?string $old = null): string {
    $file = new UploadedFile($sourcePath, basename($sourcePath), 'image/png', null, true);

    return media_file_uploader($storageDir, APPLICATION_IMAGE_FORMAT, $file, $old);
};

$upsertServiceStorage = static function (string $serviceId, string $column) use ($liveConnection): void {
    $exists = DB::connection($liveConnection)->table('storages')
        ->where('model', Service::class)
        ->where('model_id', $serviceId)
        ->where('model_column', $column)
        ->exists();

    if ($exists) {
        DB::connection($liveConnection)->table('storages')
            ->where('model', Service::class)
            ->where('model_id', $serviceId)
            ->where('model_column', $column)
            ->update(['storage_type' => 's3', 'updated_at' => now()]);

        return;
    }

    DB::connection($liveConnection)->table('storages')->insert([
        'id' => (string) Str::uuid(),
        'model' => Service::class,
        'model_id' => $serviceId,
        'model_column' => $column,
        'storage_type' => 's3',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
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

$buildProcessSteps = static function (array $steps, string $thumbUrl, string $coverUrl): array {
    $items = [];
    foreach ($steps as $index => $step) {
        $item = [
            'icon' => $step['icon'],
            'title' => $step['title'],
            'description' => $step['description'],
            'sort_order' => $index,
        ];
        if (($step['image'] ?? null) === 'thumb') {
            $item['image'] = $thumbUrl;
        } elseif (($step['image'] ?? null) === 'cover') {
            $item['image'] = $coverUrl;
        }
        $items[] = $item;
    }

    return $items;
};

$serviceSlugs = array_column($catalog['services'], 'slug');
if ($onlySlug !== '') {
    $serviceSlugs = in_array($onlySlug, $serviceSlugs, true) ? [$onlySlug] : [];
    if ($serviceSlugs === []) {
        throw new RuntimeException("Unknown SERVICE_SLUG: {$onlySlug}");
    }
}

$updatedServices = 0;
$updatedVariants = 0;

foreach ($serviceSlugs as $slug) {
    $service = Service::on($liveConnection)->withoutGlobalScopes()->where('slug', $slug)->first();
    if (! $service) {
        echo "SKIP missing service: {$slug}\n";
        continue;
    }

    $spec = collect($catalog['services'])->firstWhere('slug', $slug);
    $assetsDir = base_path('scripts/assets/service-images/'.$slug);
    $storageDir = MediaStoragePath::serviceDir($service);

    if (! $variantsOnly) {
        $thumbKey = $coverKey = null;
        foreach (['thumbnail' => 'thumbnail.png', 'cover_image' => 'cover.png'] as $column => $file) {
            $path = $assetsDir.'/'.$file;
            if (! is_file($path)) {
                throw new RuntimeException("Missing {$path}");
            }
            $old = $column === 'thumbnail' ? $service->thumbnail : $service->cover_image;
            $key = $uploadAsset($path, $storageDir, $old);
            if ($column === 'thumbnail') {
                $thumbKey = $key;
            } else {
                $coverKey = $key;
            }
            Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([$column => $key]);
            $upsertServiceStorage($service->id, $column);
        }

        if ($spec) {
            $content = AluminiumSteelContentBuilder::build($spec);
            $existing = is_array($service->overview_content)
                ? $service->overview_content
                : (json_decode((string) ($service->overview_content ?? '{}'), true) ?: []);
            $overview = ServiceOverviewContentResolver::normalizeServiceContent([
                'intro' => $content['intro'],
                'override_top_icons' => $existing['override_top_icons'] ?? false,
                'override_why_choose' => $existing['override_why_choose'] ?? false,
                'top_icons' => $existing['top_icons'] ?? [],
                'card_highlights' => $content['card_highlights'],
                'why_choose' => $existing['why_choose'] ?? ['title' => '', 'items' => []],
                'service_process' => [
                    'title' => 'How It Works',
                    'items' => $buildProcessSteps($content['process_steps'], $mediaBase.$thumbKey, $mediaBase.$coverKey),
                ],
                'perfect_for' => ['title' => 'Ideal For', 'items' => $content['perfect_for']],
                'whats_included' => ['title' => "What's Included", 'items' => $content['whats_included']],
                'good_to_know' => ['title' => 'Good to Know', 'items' => $content['good_to_know']],
                'whats_not_included' => ['title' => "What's Not Included", 'items' => $content['whats_not_included']],
            ]);
            Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([
                'overview_content' => json_encode($overview),
            ]);
        }
        echo "UPDATED service images: {$slug}\n";
        $updatedServices++;
    }

    foreach (ServiceVariant::on($liveConnection)->where('service_id', $service->id)->orderBy('sort_order')->get() as $variant) {
        $iconPath = base_path('scripts/assets/variant-icons/'.$slug.'-'.$variant->variant_key.'.png');
        if (! is_file($iconPath)) {
            throw new RuntimeException("Missing variant icon {$iconPath}");
        }
        $imageKey = $uploadAsset($iconPath, $storageDir, $variant->image);
        ServiceVariant::on($liveConnection)->where('id', $variant->id)->update(['image' => $imageKey]);
        $upsertVariantStorage($variant->id);
        echo "  variant {$variant->variant_key} -> {$imageKey}\n";
        $updatedVariants++;
    }
}

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update(['live_values' => $originalPrefix, 'test_values' => $originalPrefix]);
    StoragePathPrefix::resetCache();
}

echo "Done. services={$updatedServices} variants={$updatedVariants}\n";
