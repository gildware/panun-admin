<?php

/**
 * Restructure Dry Cleaning & Laundry into two subcategories (Dry Clean, Laundry)
 * and import catalog services from laundry-catalog-manifest.json.
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/restructure-laundry-catalog-live.php
 *
 * Optional:
 *   LAUNDRY_DRY_RUN=1          validate only
 *   IMPORT_CONNECTION=mysql      use default DB instead of live
 */

use App\Support\CloudStorageConfigurator;
use App\Support\MediaStoragePath;
use App\Support\StoragePathPrefix;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Faq;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ServiceManagement\Services\ServiceOverviewContentResolver;
use Modules\ZoneManagement\Entities\Zone;

require_once base_path('scripts/lib/MissingCatalogContentBuilder.php');

$dryRun = filter_var(env('LAUNDRY_DRY_RUN', false), FILTER_VALIDATE_BOOLEAN);
$manifestPath = base_path('scripts/data/laundry-catalog-manifest.json');
$liveConnection = env('IMPORT_CONNECTION', 'live_laundry_catalog');

if (! is_file($manifestPath)) {
    throw new RuntimeException("Manifest not found: {$manifestPath}");
}

$manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

CloudStorageConfigurator::apply();

$prefixSetting = BusinessSettings::query()
    ->where('key_name', 'storage_path_prefix')
    ->where('settings_type', 'storage_settings')
    ->first();

$originalPrefix = $prefixSetting?->live_values;
if (! $dryRun && $prefixSetting) {
    $prefixSetting->update(['live_values' => 'prod', 'test_values' => 'prod']);
    StoragePathPrefix::resetCache();
}

if ($liveConnection !== (string) config('database.default')) {
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
}

if ((string) config('database.connections.'.$liveConnection.'.password') === '' && $liveConnection !== (string) config('database.default')) {
    throw new RuntimeException('Set LIVE_DB_PASSWORD for live database.');
}

DB::connection($liveConnection)->getPdo();

if (! defined('APPLICATION_IMAGE_FORMAT')) {
    define('APPLICATION_IMAGE_FORMAT', 'webp');
}

$mediaBase = 'https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod/';
$categoryId = $manifest['category_id'];
$subcategories = $manifest['subcategories'];
$servicesSpec = $manifest['services'] ?? [];

$uploadCategoryIcon = static function (string $subId, string $slug, string $column) use ($liveConnection, $dryRun): ?string {
    $lightPath = base_path('scripts/category-icons/light/'.$slug.'.png');
    $darkPath = base_path('scripts/category-icons/dark/'.$slug.'.png');
    $source = $column === 'image_dark' ? $darkPath : $lightPath;
    if (! is_file($source)) {
        echo "WARN: missing icon {$source}\n";

        return null;
    }
    if ($dryRun) {
        return "subcategory/{$slug}/dry-run.webp";
    }

    $category = Category::on($liveConnection)->withoutGlobalScopes()->find($subId);
    if (! $category) {
        return null;
    }

    $file = new UploadedFile($source, basename($source), 'image/png', null, true);
    $old = $column === 'image_dark' ? $category->image_dark : $category->image;
    $key = media_file_uploader(MediaStoragePath::categoryDir($category), APPLICATION_IMAGE_FORMAT, $file, $old);

    Category::on($liveConnection)->withoutGlobalScopes()->where('id', $subId)->update([$column => $key]);

    return $key;
};

$restructureSubcategories = static function () use ($liveConnection, $dryRun, $subcategories, $categoryId, $uploadCategoryIcon): void {
    echo "=== Restructuring subcategories ===\n";

    foreach (['dry-clean', 'wash-laundry'] as $slug) {
        $spec = $subcategories[$slug];
        echo "UPDATE subcategory {$spec['name']} ({$slug}) id={$spec['id']}\n";
        if (! $dryRun) {
            Category::on($liveConnection)->withoutGlobalScopes()->where('id', $spec['id'])->update([
                'name' => $spec['name'],
                'slug' => $slug,
                'parent_id' => $categoryId,
                'position' => 2,
                'sort_order' => $spec['sort_order'],
                'is_active' => 1,
            ]);
            Translation::on($liveConnection)->updateOrCreate(
                ['translationable_type' => Category::class, 'translationable_id' => $spec['id'], 'locale' => 'en', 'key' => 'name'],
                ['value' => $spec['name']]
            );
            $uploadCategoryIcon($spec['id'], $slug, 'image');
            $uploadCategoryIcon($spec['id'], $slug, 'image_dark');
        }
    }

    foreach ($subcategories['deactivate'] as $deactivateId) {
        echo "DEACTIVATE subcategory id={$deactivateId}\n";
        if (! $dryRun) {
            Category::on($liveConnection)->withoutGlobalScopes()->where('id', $deactivateId)->update([
                'is_active' => 0,
            ]);
            DB::connection($liveConnection)->table('subscribed_services')
                ->where('sub_category_id', $deactivateId)
                ->update(['is_subscribed' => 0, 'updated_at' => now()]);
        }
    }
};

$migrateExisting = static function () use ($liveConnection, $dryRun, $manifest): void {
    foreach ($manifest['migrate_existing'] ?? [] as $row) {
        echo "MIGRATE existing {$row['slug']} -> sub {$row['sub_slug']}\n";
        if (! $dryRun) {
            Service::on($liveConnection)->withoutGlobalScopes()->where('id', $row['id'])->update([
                'sub_category_id' => $row['sub_category_id'],
                'category_id' => $manifest['category_id'],
            ]);
        }
    }
};

$uploadAsset = static function (string $sourcePath, string $storageDir, ?string $old = null): string {
    $file = new UploadedFile($sourcePath, basename($sourcePath), 'image/png', null, true);

    return media_file_uploader($storageDir, APPLICATION_IMAGE_FORMAT, $file, $old);
};

$upsertServiceStorage = static function (string $serviceModelId, string $column) use ($liveConnection): void {
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
            ->update(['storage_type' => 's3', 'updated_at' => now()]);

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

$restructureSubcategories();
$migrateExisting();

$zones = Zone::on($liveConnection)->where('is_active', 1)->get();
if ($zones->isEmpty()) {
    throw new RuntimeException('No active zones on target DB.');
}

$iconDir = base_path('scripts/assets/variant-icons');
$created = 0;
$skipped = 0;
$errors = [];

echo "\n=== Importing services ===\n";

foreach ($servicesSpec as $spec) {
    $slug = $spec['slug'];
    $name = $spec['name'];

    $existing = Service::on($liveConnection)->withoutGlobalScopes()->where('slug', $slug)->first();
    if ($existing) {
        echo "SKIP existing slug: {$slug}\n";
        $skipped++;
        continue;
    }

    $assetsDir = base_path('scripts/assets/service-images/'.$slug);
    foreach (['thumbnail.png', 'cover.png'] as $file) {
        if (! is_file($assetsDir.'/'.$file)) {
            $errors[] = "Missing asset {$assetsDir}/{$file} for {$slug}";
            continue 2;
        }
    }

    foreach ($spec['variants'] as $variantSpec) {
        $iconPath = $iconDir.'/'.$slug.'-'.$variantSpec['variant_key'].'.png';
        if (! is_file($iconPath)) {
            $errors[] = "Missing variant icon {$iconPath} for {$slug}";
            continue 2;
        }
    }

    if ($dryRun) {
        echo "DRY RUN ok: {$name} ({$slug}) variants=".count($spec['variants'])."\n";
        $created++;
        continue;
    }

    $content = MissingCatalogContentBuilder::build($spec);
    $minPrice = min(array_column($spec['variants'], 'price'));

    DB::connection($liveConnection)->transaction(function () use (
        $liveConnection,
        $spec,
        $content,
        $minPrice,
        $assetsDir,
        $iconDir,
        $zones,
        $mediaBase,
        $uploadAsset,
        $upsertServiceStorage,
        $upsertVariantStorage,
        $buildProcessSteps,
        &$created,
        $name,
        $slug,
    ) {
        $sortOrder = (int) (Service::on($liveConnection)->withoutGlobalScopes()
            ->where('sub_category_id', $spec['sub_category_id'])
            ->max('sort_order') ?? -1) + 1;

        $service = new Service;
        $service->setConnection($liveConnection);
        $service->name = $name;
        $service->slug = $slug;
        $service->category_id = $spec['category_id'];
        $service->sub_category_id = $spec['sub_category_id'];
        $service->short_description = $content['short_description'];
        $service->description = '<p>'.e($content['description']).'</p>';
        $service->min_bidding_price = $minPrice;
        $service->tax = 0;
        $service->is_active = 1;
        $service->sort_order = $sortOrder;
        $service->save();

        $storageDir = MediaStoragePath::serviceDir($service);
        $newThumbnail = $uploadAsset($assetsDir.'/thumbnail.png', $storageDir, $service->thumbnail);
        $newCover = $uploadAsset($assetsDir.'/cover.png', $storageDir, $service->cover_image);
        $thumbUrl = $mediaBase.$newThumbnail;
        $coverUrl = $mediaBase.$newCover;

        $overview = ServiceOverviewContentResolver::normalizeServiceContent([
            'intro' => $content['intro'],
            'override_top_icons' => false,
            'override_why_choose' => false,
            'top_icons' => [],
            'card_highlights' => $content['card_highlights'],
            'why_choose' => ['title' => '', 'items' => []],
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
            'thumbnail' => $newThumbnail,
            'cover_image' => $newCover,
            'overview_content' => json_encode($overview),
        ]);

        $upsertServiceStorage($service->id, 'thumbnail');
        $upsertServiceStorage($service->id, 'cover_image');

        foreach (['short_description' => $content['short_description'], 'description' => $content['description']] as $field => $value) {
            Translation::on($liveConnection)->updateOrCreate(
                [
                    'translationable_type' => Service::class,
                    'translationable_id' => $service->id,
                    'locale' => 'en',
                    'key' => $field,
                ],
                ['value' => $value]
            );
        }

        $faqSort = 0;
        foreach ($content['faqs'] as $faq) {
            Faq::on($liveConnection)->create([
                'question' => $faq[0],
                'answer' => $faq[1],
                'service_id' => $service->id,
                'is_active' => 1,
                'sort_order' => $faqSort++,
            ]);
        }

        $variationPricing = [];
        $sort = 0;

        foreach ($spec['variants'] as $variantSpec) {
            $iconPath = $iconDir.'/'.$slug.'-'.$variantSpec['variant_key'].'.png';
            $imageKey = $uploadAsset($iconPath, $storageDir);

            $variant = ServiceVariant::on($liveConnection)->create([
                'service_id' => $service->id,
                'variant_key' => $variantSpec['variant_key'],
                'title' => $variantSpec['title'],
                'description' => $content['short_description'],
                'note' => null,
                'image' => $imageKey,
                'sort_order' => $sort++,
                'is_active' => true,
            ]);

            $upsertVariantStorage($variant->id);

            foreach (['title' => $variantSpec['title'], 'description' => $variant->description] as $field => $value) {
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

            $variationPricing[$variantSpec['variant_key']] = [
                'use_zone_pricing' => false,
                'default_price' => (float) $variantSpec['price'],
            ];

            foreach ($zones as $zone) {
                Variation::on($liveConnection)->create([
                    'service_id' => $service->id,
                    'service_variant_id' => $variant->id,
                    'variant_key' => $variantSpec['variant_key'],
                    'variant' => $variantSpec['title'],
                    'zone_id' => $zone->id,
                    'price' => (float) $variantSpec['price'],
                ]);
            }
        }

        Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([
            'variation_pricing' => json_encode($variationPricing),
        ]);

        echo "CREATED: {$name} ({$slug}) variants=".count($spec['variants'])." zones={$zones->count()}\n";
        $created++;
    });
}

if ($prefixSetting && $originalPrefix !== null && ! $dryRun) {
    $prefixSetting->update(['live_values' => $originalPrefix, 'test_values' => $originalPrefix]);
    StoragePathPrefix::resetCache();
}

echo "\nDone. created={$created} skipped={$skipped} errors=".count($errors).($dryRun ? ' (dry run)' : '')."\n";
if ($errors !== []) {
    foreach ($errors as $error) {
        echo " - {$error}\n";
    }
    throw new RuntimeException('Laundry catalog import had missing assets.');
}
