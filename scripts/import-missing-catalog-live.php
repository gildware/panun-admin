<?php

/**
 * Import missing catalog services + variants into live (or local) DB.
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/import-missing-catalog-live.php
 *
 * Optional env:
 *   IMPORT_CONNECTION=live_missing_catalog   (default)
 *   IMPORT_DRY_RUN=1                         (validate only, no writes)
 *   IMPORT_MANIFEST=scripts/data/missing-catalog-manifest.json
 *   IMPORT_SLUG=book-a-carpenter              (process only this slug)
 *   IMPORT_REQUIRE_PHOTO_SOURCE=1             (only services with photorealistic assets)
 *   IMPORT_ONLY_MISSING=1                     (skip services already on live; create missing only)
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
use Modules\ServiceManagement\Entities\Faq;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ServiceManagement\Services\ServiceOverviewContentResolver;
use Modules\ZoneManagement\Entities\Zone;

require_once base_path('scripts/lib/MissingCatalogContentBuilder.php');

$dryRun = filter_var(env('IMPORT_DRY_RUN', false), FILTER_VALIDATE_BOOLEAN);
$refreshExisting = filter_var(env('IMPORT_REFRESH_EXISTING', true), FILTER_VALIDATE_BOOLEAN);
$importLimit = (int) env('IMPORT_LIMIT', 0);
$importSlug = trim((string) env('IMPORT_SLUG', ''));
$requirePhotoSource = filter_var(env('IMPORT_REQUIRE_PHOTO_SOURCE', false), FILTER_VALIDATE_BOOLEAN);
$importOnlyMissing = filter_var(env('IMPORT_ONLY_MISSING', false), FILTER_VALIDATE_BOOLEAN);
$photoAssetsDir = rtrim((string) env('IMPORT_PHOTO_ASSETS_DIR', '/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets'), '/');
$manifestPath = base_path(env('IMPORT_MANIFEST', 'scripts/data/missing-catalog-manifest.json'));
$liveConnection = env('IMPORT_CONNECTION', 'live_missing_catalog');

if (! is_file($manifestPath)) {
    throw new RuntimeException("Manifest not found: {$manifestPath}");
}

$manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
$servicesSpec = $manifest['services'] ?? [];
if ($servicesSpec === []) {
    throw new RuntimeException('Manifest has no services.');
}
if ($importSlug !== '') {
    $servicesSpec = array_values(array_filter(
        $servicesSpec,
        static fn (array $spec): bool => ($spec['slug'] ?? '') === $importSlug
    ));
    if ($servicesSpec === []) {
        throw new RuntimeException("No service found in manifest for IMPORT_SLUG={$importSlug}");
    }
}

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
    throw new RuntimeException('Set LIVE_DB_PASSWORD (or DB_PASSWORD) for the target database.');
}

try {
    DB::connection($liveConnection)->getPdo();
} catch (Throwable $e) {
    throw new RuntimeException('Database connection failed: '.$e->getMessage());
}

if (! defined('APPLICATION_IMAGE_FORMAT')) {
    define('APPLICATION_IMAGE_FORMAT', 'webp');
}

if (! $dryRun && ! Schema::connection($liveConnection)->hasColumn('service_variants', 'icon')) {
    Schema::connection($liveConnection)->table('service_variants', function ($table) {
        $table->string('icon', 64)->nullable()->after('image');
    });
    echo "Added icon column to service_variants.\n";
}

$mediaBase = 'https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod/';
$backupDir = base_path('scripts/backups');
if (! is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$importId = date('Ymd-His');
$backupPath = $backupDir.'/missing-catalog-'.$importId.'.json';
$backup = [
    'import_id' => $importId,
    'created_at' => now()->toIso8601String(),
    'connection' => $liveConnection,
    'manifest' => $manifestPath,
    'dry_run' => $dryRun,
    'services' => [],
];

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

$zones = Zone::on($liveConnection)->where('is_active', 1)->get();
if ($zones->isEmpty()) {
    throw new RuntimeException('No active zones on target DB.');
}

$resolveVariantIconPath = static function (string $slug, string $variantKey) use ($liveConnection): string {
    $iconDir = base_path('scripts/assets/variant-icons');
    $candidates = [
        $iconDir.'/'.$slug.'-'.$variantKey.'.png',
        $iconDir.'/'.$slug.'-book-site-inspection.png',
        $iconDir.'/book-site-inspection.png',
    ];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }
    throw new RuntimeException("Missing variant icon for {$slug} / {$variantKey}");
};

$created = 0;
$skipped = 0;
$refreshed = 0;
$errors = [];

$applyServiceMediaAndOverview = static function (
    Service $service,
    array $spec,
    array $content,
    string $assetsDir,
    string $iconDir,
    $zones,
    string $mediaBase,
    $uploadAsset,
    $upsertServiceStorage,
    $upsertVariantStorage,
    $buildProcessSteps,
    string $slug,
) use ($liveConnection, $resolveVariantIconPath): array {
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

    $minPrice = min(array_column($spec['variants'], 'price'));

    Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([
        'short_description' => $content['short_description'],
        'description' => '<p>'.e($content['description']).'</p>',
        'min_bidding_price' => $minPrice,
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

    Faq::on($liveConnection)->where('service_id', $service->id)->delete();
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

    $oldVariantIds = ServiceVariant::on($liveConnection)->where('service_id', $service->id)->pluck('id');
    if ($oldVariantIds->isNotEmpty()) {
        Translation::on($liveConnection)
            ->where('translationable_type', ServiceVariant::class)
            ->whereIn('translationable_id', $oldVariantIds->all())
            ->delete();
    }
    Variation::on($liveConnection)->where('service_id', $service->id)->delete();
    ServiceVariant::on($liveConnection)->where('service_id', $service->id)->delete();

    $variationPricing = [];
    $variantBackup = [];
    $sort = 0;

    foreach ($spec['variants'] as $variantSpec) {
        $iconPath = $resolveVariantIconPath($slug, $variantSpec['variant_key']);
        $imageKey = $uploadAsset($iconPath, $storageDir);

        $variant = ServiceVariant::on($liveConnection)->create([
            'service_id' => $service->id,
            'variant_key' => $variantSpec['variant_key'],
            'title' => $variantSpec['title'],
            'description' => "{$variantSpec['title']} by verified Panun Kaergar professionals.",
            'note' => 'Final scope and any add-ons are confirmed on site before work begins.',
            'image' => $imageKey,
            'icon' => null,
            'sort_order' => $sort++,
            'is_active' => true,
        ]);

        $upsertVariantStorage($variant->id);

        foreach (['title' => $variantSpec['title'], 'description' => $variant->description, 'note' => $variant->note] as $field => $value) {
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

        $zoneRows = [];
        foreach ($zones as $zone) {
            $zoneRows[] = Variation::on($liveConnection)->create([
                'service_id' => $service->id,
                'service_variant_id' => $variant->id,
                'variant_key' => $variantSpec['variant_key'],
                'variant' => $variantSpec['title'],
                'zone_id' => $zone->id,
                'price' => (float) $variantSpec['price'],
            ]);
        }

        $variantBackup[] = [
            'variant' => $variant->fresh()->toArray(),
            'zone_rows' => collect($zoneRows)->map->toArray()->all(),
        ];
    }

    Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([
        'variation_pricing' => json_encode($variationPricing),
    ]);

    return [
        'uploaded_keys' => ['thumbnail' => $newThumbnail, 'cover_image' => $newCover],
        'variants' => $variantBackup,
    ];
};

foreach ($servicesSpec as $spec) {
    if ($importLimit > 0 && $created + $skipped >= $importLimit) {
        break;
    }
    DB::connection($liveConnection)->reconnect();
    $slug = $spec['slug'];
    $name = $spec['name'];

    if ($requirePhotoSource) {
        if (! is_file($photoAssetsDir.'/'.$slug.'-thumbnail.png') || ! is_file($photoAssetsDir.'/'.$slug.'-cover.png')) {
            echo "SKIP no photorealistic source for {$slug}\n";
            continue;
        }
    }

    $assetsDir = base_path('scripts/assets/service-images/'.$slug);
    foreach (['thumbnail.png', 'cover.png'] as $file) {
        if (! is_file($assetsDir.'/'.$file)) {
            echo "SKIP missing asset {$assetsDir}/{$file} for {$slug}\n";
            continue 2;
        }
    }

    $iconDir = base_path('scripts/assets/variant-icons');
    foreach ($spec['variants'] as $variantSpec) {
        $iconPath = $resolveVariantIconPath($slug, $variantSpec['variant_key']);
        if (! is_file($iconPath)) {
            echo "SKIP missing variant icon {$iconPath} for {$slug}\n";
            continue 2;
        }
    }

    $existing = Service::on($liveConnection)->withoutGlobalScopes()->where('slug', $slug)->first();
    if ($existing && $importOnlyMissing) {
        echo "SKIP already on live: {$slug}\n";
        $skipped++;
        continue;
    }
    if ($existing && $refreshExisting && ! $dryRun) {
        $content = MissingCatalogContentBuilder::build($spec);
        DB::connection($liveConnection)->transaction(function () use (
            $liveConnection, $existing, $spec, $content, $assetsDir, $iconDir, $zones, $mediaBase,
            $uploadAsset, $upsertServiceStorage, $upsertVariantStorage, $buildProcessSteps,
            $applyServiceMediaAndOverview, &$backup, &$refreshed, $slug, $name,
        ) {
            $result = $applyServiceMediaAndOverview(
                $existing, $spec, $content, $assetsDir, $iconDir, $zones, $mediaBase,
                $uploadAsset, $upsertServiceStorage, $upsertVariantStorage, $buildProcessSteps, $slug
            );
            $serviceRow = Service::on($liveConnection)->withoutGlobalScopes()->find($existing->id);
            $backup['services'][] = [
                'service_id' => $existing->id,
                'slug' => $slug,
                'uploaded_keys' => $result['uploaded_keys'],
                'service' => $serviceRow?->toArray(),
                'variants' => $result['variants'],
                'refreshed' => true,
            ];
            echo "REFRESHED: {$name} ({$slug}) variants=".count($spec['variants'])."\n";
            $refreshed++;
        });
        continue;
    }
    if ($existing) {
        echo "SKIP existing slug: {$slug}\n";
        $skipped++;
        continue;
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
        $resolveVariantIconPath,
        &$backup,
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
        $variantBackup = [];
        $sort = 0;

        foreach ($spec['variants'] as $variantSpec) {
            $iconPath = $resolveVariantIconPath($slug, $variantSpec['variant_key']);
            $imageKey = $uploadAsset($iconPath, $storageDir);

            $variant = ServiceVariant::on($liveConnection)->create([
                'service_id' => $service->id,
                'variant_key' => $variantSpec['variant_key'],
                'title' => $variantSpec['title'],
                'description' => "{$variantSpec['title']} by verified Panun Kaergar professionals.",
                'note' => 'Final scope and any add-ons are confirmed on site before work begins.',
                'image' => $imageKey,
                'icon' => null,
                'sort_order' => $sort++,
                'is_active' => true,
            ]);

            $upsertVariantStorage($variant->id);

            foreach (['title' => $variantSpec['title'], 'description' => $variant->description, 'note' => $variant->note] as $field => $value) {
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

            $zoneRows = [];
            foreach ($zones as $zone) {
                $zoneRows[] = Variation::on($liveConnection)->create([
                    'service_id' => $service->id,
                    'service_variant_id' => $variant->id,
                    'variant_key' => $variantSpec['variant_key'],
                    'variant' => $variantSpec['title'],
                    'zone_id' => $zone->id,
                    'price' => (float) $variantSpec['price'],
                ]);
            }

            $variantBackup[] = [
                'variant' => $variant->fresh()->toArray(),
                'zone_rows' => collect($zoneRows)->map->toArray()->all(),
            ];
        }

        Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([
            'variation_pricing' => json_encode($variationPricing),
        ]);

        $serviceRow = Service::on($liveConnection)->withoutGlobalScopes()->find($service->id);
        $backup['services'][] = [
            'service_id' => $service->id,
            'slug' => $slug,
            'uploaded_keys' => [
                'thumbnail' => $newThumbnail,
                'cover_image' => $newCover,
            ],
            'service' => $serviceRow?->toArray(),
            'variants' => $variantBackup,
            'faqs' => Faq::on($liveConnection)->where('service_id', $service->id)->get()->map->toArray()->all(),
            'translations' => Translation::on($liveConnection)
                ->where(function ($q) use ($service, $variantBackup) {
                    $q->where('translationable_id', $service->id)
                        ->where('translationable_type', Service::class);
                })
                ->orWhere(function ($q) use ($variantBackup) {
                    $ids = collect($variantBackup)->pluck('variant.id')->filter()->all();
                    if ($ids !== []) {
                        $q->where('translationable_type', ServiceVariant::class)
                            ->whereIn('translationable_id', $ids);
                    }
                })
                ->get()
                ->map->toArray()
                ->all(),
            'storages' => DB::connection($liveConnection)->table('storages')
                ->where(function ($q) use ($service) {
                    $q->where('model', Service::class)->where('model_id', $service->id);
                })
                ->orWhere(function ($q) use ($variantBackup) {
                    $ids = collect($variantBackup)->pluck('variant.id')->filter()->all();
                    if ($ids !== []) {
                        $q->where('model', ServiceVariant::class)->whereIn('model_id', $ids);
                    }
                })
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
        ];

        echo "CREATED: {$name} ({$slug}) variants=".count($spec['variants'])." zones={$zones->count()}\n";
        $created++;
    });
}

if (! $dryRun) {
    file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Backup saved: {$backupPath}\n";
}

if ($prefixSetting && $originalPrefix !== null && ! $dryRun) {
    $prefixSetting->update(['live_values' => $originalPrefix, 'test_values' => $originalPrefix]);
    StoragePathPrefix::resetCache();
}

echo "\nDone. created={$created} refreshed={$refreshed} skipped={$skipped} errors=".count($errors)."\n";
if ($errors !== []) {
    echo "First errors:\n";
    foreach (array_slice($errors, 0, 20) as $error) {
        echo " - {$error}\n";
    }
    if (count($errors) > 20) {
        echo ' ... and '.(count($errors) - 20)." more\n";
    }
}

if (! $dryRun && $created > 0) {
    echo "\nTo revert this import:\n";
    echo "BACKUP_FILE={$backupPath} LIVE_DB_PASSWORD='...' php artisan tinker scripts/revert-missing-catalog-live.php\n";
}
