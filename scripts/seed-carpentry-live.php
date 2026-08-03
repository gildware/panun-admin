<?php

/**
 * Seed / replace Carpentry Services catalog on live DB.
 *
 * - Upserts main Carpentry Services category (`carpentary`) + 4 sub-categories
 * - Creates/refreshes services with overview, FAQs, images, variants @ ₹50
 * - Deactivates old carpentry services not in the new catalog (no hard delete)
 *
 * Prerequisites:
 *   python3 scripts/assets/carpentry_icon_prompts.py
 *   python3 scripts/assets/carpentry_photo_prompts.py
 *   # Generate AI icons + photos into Cursor assets/, then:
 *   python3 scripts/prepare_carpentry_ai_icons.py
 *   python3 scripts/assets/prepare_carpentry_assets.py
 *   python3 scripts/assets/category-icons/make_theme_pairs.py carpentary carpentry-installation carpentry-making carpentry-repairs roofing-works
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/seed-carpentry-live.php
 *
 * Optional:
 *   CARPENTRY_DRY_RUN=1
 *   CARPENTRY_ONLY_SLUGS=door-installation,bed-making
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
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Faq;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ServiceManagement\Services\ServiceOverviewContentResolver;
use Modules\ZoneManagement\Entities\Zone;

require_once base_path('scripts/lib/CarpentryContentBuilder.php');

$dryRun = filter_var(env('CARPENTRY_DRY_RUN', false), FILTER_VALIDATE_BOOLEAN);
$onlySlugs = array_filter(array_map('trim', explode(',', (string) env('CARPENTRY_ONLY_SLUGS', ''))));
$catalog = require base_path('scripts/data/carpentry-catalog.php');

if ($onlySlugs !== []) {
    $catalog['services'] = array_values(array_filter(
        $catalog['services'],
        static fn (array $service): bool => in_array($service['slug'], $onlySlugs, true)
    ));
    if ($catalog['services'] === []) {
        throw new RuntimeException('CARPENTRY_ONLY_SLUGS did not match any catalog services.');
    }
}

$withDbRetry = static function (callable $callback, int $attempts = 5) {
    $last = null;
    for ($i = 1; $i <= $attempts; $i++) {
        try {
            return $callback();
        } catch (Throwable $e) {
            $last = $e;
            if (! str_contains($e->getMessage(), '1205') || $i === $attempts) {
                throw $e;
            }
            echo "Lock wait — retry {$i}/{$attempts} in 5s...\n";
            sleep(5);
        }
    }

    throw $last ?? new RuntimeException('Retry failed.');
};

$categoryIconDir = base_path('scripts/assets/category-icons');
$serviceImageDir = base_path('scripts/assets/service-images');
$variantIconDir = base_path('scripts/assets/variant-icons');

foreach ([$catalog['category']['slug'] ?? 'carpentary'] as $slug) {
    if (! is_file($categoryIconDir.'/'.$slug.'.png')) {
        throw new RuntimeException("Missing category icon: {$categoryIconDir}/{$slug}.png");
    }
}
foreach ($catalog['sub_categories'] as $subSpec) {
    if (! is_file($categoryIconDir.'/'.$subSpec['slug'].'.png')) {
        throw new RuntimeException("Missing category icon: {$categoryIconDir}/{$subSpec['slug']}.png");
    }
}

foreach ($catalog['services'] as $serviceSpec) {
    $slug = $serviceSpec['slug'];
    $assetsDir = $serviceImageDir.'/'.$slug;
    foreach (['thumbnail.png', 'cover.png'] as $file) {
        if (! is_file($assetsDir.'/'.$file)) {
            throw new RuntimeException("Missing service asset {$assetsDir}/{$file}");
        }
    }
    foreach ($serviceSpec['variants'] as $variantSpec) {
        $iconPath = $variantIconDir.'/'.$slug.'-'.$variantSpec['variant_key'].'.png';
        if (! is_file($iconPath)) {
            throw new RuntimeException("Missing variant icon {$iconPath}");
        }
    }
}

echo 'Asset validation passed for '.count($catalog['services'])." services.\n";

if ($dryRun) {
    echo "DRY RUN complete — assets OK, no database writes.\n";
    exit(0);
}

CloudStorageConfigurator::apply();

$prefixSetting = BusinessSettings::query()
    ->where('key_name', 'storage_path_prefix')
    ->where('settings_type', 'storage_settings')
    ->first();

$originalPrefix = $prefixSetting?->live_values;
if ($prefixSetting) {
    $prefixSetting->update(['live_values' => 'prod', 'test_values' => 'prod']);
    StoragePathPrefix::resetCache();
}

$liveConnection = 'live_carpentry';
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

DB::connection($liveConnection)->getPdo();

if (! defined('APPLICATION_IMAGE_FORMAT')) {
    define('APPLICATION_IMAGE_FORMAT', 'webp');
}

if (Schema::connection($liveConnection)->hasTable('service_variants')
    && ! Schema::connection($liveConnection)->hasColumn('service_variants', 'note')) {
    Schema::connection($liveConnection)->table('service_variants', function ($table) {
        $table->text('note')->nullable()->after('description');
    });
    echo "Added note column to service_variants.\n";
}

$mediaBase = 'https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod/';

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
        } elseif (! empty($step['image']) && ! in_array($step['image'], ['thumb', 'cover'], true)) {
            $item['image'] = $step['image'];
        }
        $items[] = $item;
    }

    return $items;
};

$upsertCategoryTranslation = static function (Category $category, string $key, string $value) use ($liveConnection): void {
    Translation::on($liveConnection)->updateOrCreate(
        [
            'translationable_type' => Category::class,
            'translationable_id' => $category->id,
            'locale' => 'en',
            'key' => $key,
        ],
        ['value' => $value]
    );
};

$ensureCategory = static function (array $spec, ?string $parentId, int $position) use (
    $liveConnection,
    $categoryIconDir,
    $uploadAsset,
    $upsertCategoryTranslation
): Category {
    $slug = $spec['slug'];
    $iconPath = $categoryIconDir.'/'.$slug.'.png';
    if (! is_file($iconPath)) {
        throw new RuntimeException("Missing category icon: {$iconPath}");
    }

    $category = Category::on($liveConnection)->withoutGlobalScopes()->where('slug', $slug)->first();
    if (! $category) {
        $category = new Category;
        $category->setConnection($liveConnection);
        $category->id = (string) Str::uuid();
        $category->save();
    }

    $category->name = $spec['name'];
    $category->slug = $slug;
    $category->parent_id = $parentId;
    $category->position = $position;
    $category->description = $spec['description'] ?? null;
    $category->is_active = 1;
    $category->sort_order = $spec['sort_order'] ?? 0;
    if ($position === 1) {
        $category->is_featured = $spec['is_featured'] ?? 0;
    }
    $category->save();

    $storageDir = MediaStoragePath::categoryDir($category);
    $newImage = $uploadAsset($iconPath, $storageDir, $category->image);
    Category::on($liveConnection)->withoutGlobalScopes()->where('id', $category->id)->update(['image' => $newImage]);

    $upsertCategoryTranslation($category, 'name', $spec['name']);
    if (! empty($spec['description'])) {
        $upsertCategoryTranslation($category, 'description', $spec['description']);
    }

    echo "Category ready: {$spec['name']} ({$slug})\n";

    return $category->fresh();
};

$zones = Zone::on($liveConnection)->where('is_active', 1)->get();
if ($zones->isEmpty()) {
    throw new RuntimeException('No active zones on target DB.');
}

$leafZoneIds = $zones->pluck('id')->all();

$mainCategory = $ensureCategory($catalog['category'], null, 1);
$mainCategory->zones()->sync($leafZoneIds);

$subCategoryMap = [];
foreach ($catalog['sub_categories'] as $subSpec) {
    $sub = $ensureCategory($subSpec, $mainCategory->id, 2);
    $subCategoryMap[$subSpec['slug']] = $sub->id;
}

foreach ($catalog['deactivate_sub_slugs'] ?? [] as $oldSubSlug) {
    $updated = Category::on($liveConnection)->withoutGlobalScopes()
        ->where('slug', $oldSubSlug)
        ->where('parent_id', $mainCategory->id)
        ->update(['is_active' => 0]);
    if ($updated) {
        echo "Deactivated subcategory: {$oldSubSlug}\n";
    }
}

$newServiceSlugs = array_column($catalog['services'], 'slug');

foreach ($catalog['services'] as $serviceSpec) {
    $slug = $serviceSpec['slug'];
    $name = $serviceSpec['name'];
    $subCategoryId = $subCategoryMap[$serviceSpec['sub_category_slug']] ?? null;
    if (! $subCategoryId) {
        throw new RuntimeException("Unknown sub-category for service {$slug}");
    }

    $content = CarpentryContentBuilder::build($serviceSpec);
    $assetsDir = $serviceImageDir.'/'.$slug;

    $service = Service::on($liveConnection)->withoutGlobalScopes()->where('slug', $slug)->first();
    if (! $service) {
        $sortOrder = (int) (Service::on($liveConnection)->withoutGlobalScopes()
            ->where('sub_category_id', $subCategoryId)
            ->max('sort_order') ?? -1) + 1;

        $service = new Service;
        $service->setConnection($liveConnection);
        $service->name = $name;
        $service->slug = $slug;
        $service->category_id = $mainCategory->id;
        $service->sub_category_id = $subCategoryId;
        $service->short_description = $content['short_description'];
        $service->description = '<p>'.e($content['description']).'</p>';
        $service->min_bidding_price = (float) $serviceSpec['base_price'];
        $service->tax = 0;
        $service->is_active = 1;
        $service->sort_order = $sortOrder;
        $service->save();
        echo "CREATED service: {$name}\n";
    } else {
        Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([
            'name' => $name,
            'category_id' => $mainCategory->id,
            'sub_category_id' => $subCategoryId,
            'short_description' => $content['short_description'],
            'description' => '<p>'.e($content['description']).'</p>',
            'min_bidding_price' => (float) $serviceSpec['base_price'],
            'is_active' => 1,
        ]);
        echo "UPDATED service: {$name}\n";
    }

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
        'good_to_know' => ['title' => 'Good to Know', 'items' => $content['good_to_know']],
        'whats_not_included' => ['title' => "What's Not Included", 'items' => $content['whats_not_included']],
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

    $withDbRetry(function () use ($liveConnection, $service, $content): void {
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
    });

    $withDbRetry(function () use ($liveConnection, $service): void {
        $oldVariantIds = ServiceVariant::on($liveConnection)->where('service_id', $service->id)->pluck('id');
        if ($oldVariantIds->isNotEmpty()) {
            Translation::on($liveConnection)
                ->where('translationable_type', ServiceVariant::class)
                ->whereIn('translationable_id', $oldVariantIds->all())
                ->delete();
        }
        Variation::on($liveConnection)->where('service_id', $service->id)->delete();
        ServiceVariant::on($liveConnection)->where('service_id', $service->id)->delete();
    });

    $variationPricing = [];
    $sort = 0;

    foreach ($serviceSpec['variants'] as $variantSpec) {
        $iconPath = $variantIconDir.'/'.$slug.'-'.$variantSpec['variant_key'].'.png';
        $imageKey = $uploadAsset($iconPath, $storageDir);
        $variantTitle = $variantSpec['title'];
        $variantDescription = CarpentryContentBuilder::variantDescription($name, $variantTitle);
        $variantNote = CarpentryContentBuilder::variantNote($slug);

        $variant = ServiceVariant::on($liveConnection)->create([
            'service_id' => $service->id,
            'variant_key' => $variantSpec['variant_key'],
            'title' => $variantTitle,
            'description' => $variantDescription,
            'note' => $variantNote,
            'image' => $imageKey,
            'sort_order' => $sort++,
            'is_active' => true,
        ]);

        $upsertVariantStorage($variant->id);

        foreach (['title' => $variantTitle, 'description' => $variantDescription, 'note' => $variantNote] as $field => $value) {
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
                'variant' => $variantTitle,
                'zone_id' => $zone->id,
                'price' => (float) $variantSpec['price'],
            ]);
        }
    }

    Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([
        'variation_pricing' => json_encode($variationPricing),
    ]);

    echo '  variants: '.count($serviceSpec['variants'])." x {$zones->count()} zones\n";
    sleep(1);
}

$oldServices = Service::on($liveConnection)->withoutGlobalScopes()
    ->where('category_id', $mainCategory->id)
    ->whereNotIn('slug', $newServiceSlugs)
    ->get(['id', 'slug', 'name', 'is_active']);

foreach ($oldServices as $old) {
    if ((int) $old->is_active === 1) {
        Service::on($liveConnection)->withoutGlobalScopes()->where('id', $old->id)->update(['is_active' => 0]);
        echo "Deactivated old service: {$old->slug}\n";
    }
}

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update(['live_values' => $originalPrefix, 'test_values' => $originalPrefix]);
    StoragePathPrefix::resetCache();
}

echo "\nCarpentry catalog seeded: 1 category, ".count($catalog['sub_categories']).' sub-categories, '.count($catalog['services'])." services.\n";
echo 'Deactivated obsolete services: '.$oldServices->where('is_active', 1)->count()." (plus any already inactive).\n";
