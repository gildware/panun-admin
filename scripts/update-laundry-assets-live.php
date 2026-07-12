<?php

/**
 * Re-upload laundry service images, variant icons, and How It Works overview on live.
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/update-laundry-assets-live.php
 *
 * Optional: SERVICE_SLUG=suit-dry-clean to update one service only.
 * Optional: VARIANTS_ONLY=1 to upload variant icons only (skip service thumb/cover).
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

require_once base_path('scripts/lib/MissingCatalogContentBuilder.php');

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

$liveConnection = 'live_laundry_assets';
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

$manifest = json_decode((string) file_get_contents(base_path('scripts/data/laundry-catalog-manifest.json')), true, 512, JSON_THROW_ON_ERROR);
$onlySlug = trim((string) env('SERVICE_SLUG', ''));
$variantsOnly = filter_var(env('VARIANTS_ONLY', false), FILTER_VALIDATE_BOOLEAN);
$mediaBase = 'https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod/';

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

$refreshOverview = static function (Service $service, string $thumbUrl, string $coverUrl, array $spec) use ($liveConnection, $buildProcessSteps): void {
    $content = MissingCatalogContentBuilder::build($spec);
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
            'items' => $buildProcessSteps($content['process_steps'], $thumbUrl, $coverUrl),
        ],
        'perfect_for' => ['title' => 'Ideal For', 'items' => $content['perfect_for']],
        'whats_included' => ['title' => "What's Included", 'items' => $content['whats_included']],
        'good_to_know' => ['title' => 'Things to Know', 'items' => $content['good_to_know']],
        'whats_not_included' => ['title' => 'Exclusions', 'items' => $content['whats_not_included']],
    ]);

    Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([
        'overview_content' => $overview,
    ]);
    echo "  refreshed How It Works overview\n";
};

$manifestBySlug = [];
foreach ($manifest['services'] as $row) {
    $manifestBySlug[$row['slug']] = $row;
}

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

$resolveVariantIcon = static function (string $slug, string $variantKey): string {
    $candidates = [
        base_path('scripts/assets/variant-icons/'.$slug.'-'.$variantKey.'.png'),
        base_path('scripts/assets/variant-icons/'.$slug.'.png'),
        base_path('scripts/assets/variant-icons/'.$variantKey.'.png'),
    ];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }
    throw new RuntimeException("Missing variant icon for {$slug}/{$variantKey}");
};

$updatedServices = 0;
$updatedVariants = 0;

$serviceSlugs = array_column($manifest['services'], 'slug');
if ($onlySlug !== '') {
    $serviceSlugs = in_array($onlySlug, $serviceSlugs, true) ? [$onlySlug] : [];
    if ($serviceSlugs === []) {
        throw new RuntimeException("Unknown SERVICE_SLUG: {$onlySlug}");
    }
}

foreach ($serviceSlugs as $slug) {
    $service = Service::on($liveConnection)->withoutGlobalScopes()->where('slug', $slug)->first();
    if (! $service) {
        echo "SKIP missing service: {$slug}\n";
        continue;
    }

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
        $thumbUrl = $mediaBase.$thumbKey;
        $coverUrl = $mediaBase.$coverKey;
        if (isset($manifestBySlug[$slug])) {
            $refreshOverview($service, $thumbUrl, $coverUrl, $manifestBySlug[$slug]);
        }
        echo "UPDATED service images: {$slug}\n";
        $updatedServices++;
    }

    $variants = ServiceVariant::on($liveConnection)->where('service_id', $service->id)->get();
    foreach ($variants as $variant) {
        $iconPath = $resolveVariantIcon($slug, (string) $variant->variant_key);
        $storageDir = MediaStoragePath::serviceDir($service);
        $imageKey = $uploadAsset($iconPath, $storageDir, $variant->image);
        ServiceVariant::on($liveConnection)->where('id', $variant->id)->update(['image' => $imageKey]);
        $upsertVariantStorage($variant->id);
        echo "  variant {$variant->variant_key} -> {$imageKey}\n";
        $updatedVariants++;
    }
}

// Lehenga was seeded earlier — refresh service images + variant icons to PK format
if ($onlySlug === '' || $onlySlug === 'lehenga-dry-clean') {
    $lehenga = Service::on($liveConnection)->withoutGlobalScopes()->where('slug', 'lehenga-dry-clean')->first();
    if ($lehenga) {
        $assetsDir = base_path('scripts/assets/service-images/lehenga-dry-clean');
        if (! $variantsOnly) {
            $thumbKey = $coverKey = null;
            foreach (['thumbnail' => 'thumbnail.png', 'cover_image' => 'cover.png'] as $column => $file) {
                $path = $assetsDir.'/'.$file;
                if (is_file($path)) {
                    $storageDir = MediaStoragePath::serviceDir($lehenga);
                    $old = $column === 'thumbnail' ? $lehenga->thumbnail : $lehenga->cover_image;
                    $key = $uploadAsset($path, $storageDir, $old);
                    if ($column === 'thumbnail') {
                        $thumbKey = $key;
                    } else {
                        $coverKey = $key;
                    }
                    Service::on($liveConnection)->withoutGlobalScopes()->where('id', $lehenga->id)->update([$column => $key]);
                    $upsertServiceStorage($lehenga->id, $column);
                }
            }
            if ($thumbKey && $coverKey) {
                $lehengaSpec = [
                    'name' => 'Lehenga Dry Clean',
                    'slug' => 'lehenga-dry-clean',
                    'category_slug' => 'laundry',
                    'sub_slug' => 'dry-clean',
                ];
                $refreshOverview($lehenga, $mediaBase.$thumbKey, $mediaBase.$coverKey, $lehengaSpec);
            }
            echo "UPDATED service images: lehenga-dry-clean\n";
            $updatedServices++;
        }

        $iconPath = base_path('scripts/assets/variant-icons/lehenga-dry-clean.png');
        if (! is_file($iconPath)) {
            echo "WARN: missing lehenga-dry-clean.png package icon\n";
        } else {
            foreach (ServiceVariant::on($liveConnection)->where('service_id', $lehenga->id)->get() as $variant) {
                $storageDir = MediaStoragePath::serviceDir($lehenga);
                $imageKey = $uploadAsset($iconPath, $storageDir, $variant->image);
                ServiceVariant::on($liveConnection)->where('id', $variant->id)->update(['image' => $imageKey]);
                $upsertVariantStorage($variant->id);
                echo "  lehenga variant {$variant->variant_key} -> {$imageKey}\n";
                $updatedVariants++;
            }
        }
    }
}

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update(['live_values' => $originalPrefix, 'test_values' => $originalPrefix]);
    StoragePathPrefix::resetCache();
}

echo "Done. services={$updatedServices} variants={$updatedVariants}\n";
