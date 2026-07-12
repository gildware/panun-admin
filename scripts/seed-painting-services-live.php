<?php

/**
 * Seed Painting catalog on live DB:
 * - Creates new painting services
 * - Refreshes content, images, FAQs, and variants for all painting services
 *
 * Prerequisites:
 *   1. Generate photoreal images from prompts into Cursor assets folder
 *   2. python3 scripts/assets/run_painting_pipeline.py
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/seed-painting-services-live.php
 *
 * Optional:
 *   PAINTING_DRY_RUN=1
 *   PAINTING_ONLY_SLUGS=ceiling-painting,exterior-wall-painting
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

require_once base_path('scripts/lib/RemainingServiceContentBuilder.php');

$dryRun = filter_var(env('PAINTING_DRY_RUN', false), FILTER_VALIDATE_BOOLEAN);
$onlySlugs = array_filter(array_map('trim', explode(',', (string) env('PAINTING_ONLY_SLUGS', ''))));
$catalog = require base_path('scripts/data/painting-catalog.php');

if ($onlySlugs !== []) {
    $catalog['services'] = array_values(array_filter(
        $catalog['services'],
        static fn (array $service): bool => in_array($service['slug'], $onlySlugs, true)
    ));
    if ($catalog['services'] === []) {
        throw new RuntimeException('PAINTING_ONLY_SLUGS did not match any catalog services.');
    }
}

$serviceImageDir = base_path('scripts/assets/service-images');
$variantIconDir = base_path('scripts/assets/variant-icons');

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
}
StoragePathPrefix::resetCache();

$liveConnection = 'live_painting';
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
    throw new RuntimeException('Set LIVE_DB_PASSWORD (or DB_PASSWORD) for live database.');
}

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
        }
        $items[] = $item;
    }

    return $items;
};

$variantDescription = static function (string $serviceName): string {
    return "Verified Panun Kaergar team inspects {$serviceName} scope, surface condition, and access on site.";
};

$variantNote = static function (): string {
    return 'Inspection fee is adjusted against the final bill when you proceed with the painting work.';
};

$mainCategory = Category::on($liveConnection)->withoutGlobalScopes()
    ->where('id', $catalog['category']['id'])
    ->first();
if (! $mainCategory) {
    throw new RuntimeException('Painting category not found on live DB.');
}

$subCategoryMap = [];
foreach ($catalog['sub_categories'] as $subSpec) {
    $sub = Category::on($liveConnection)->withoutGlobalScopes()
        ->where('id', $subSpec['id'])
        ->first();
    if (! $sub) {
        throw new RuntimeException("Sub-category not found: {$subSpec['slug']}");
    }
    $subCategoryMap[$subSpec['slug']] = $sub->id;
}

$zones = Zone::on($liveConnection)->where('is_active', 1)->get();
if ($zones->isEmpty()) {
    throw new RuntimeException('No active zones on live DB.');
}

foreach ($catalog['services'] as $serviceSpec) {
    $slug = $serviceSpec['slug'];
    $name = $serviceSpec['name'];
    $subCategoryId = $subCategoryMap[$serviceSpec['sub_category_slug']] ?? null;
    if (! $subCategoryId) {
        throw new RuntimeException("Unknown sub-category for service {$slug}");
    }

    $content = RemainingServiceContentBuilder::build([
        'name' => $name,
        'role' => 'painting',
        'category' => 'painting',
    ]);
    $assetsDir = $serviceImageDir.'/'.$slug;

    $service = null;
    if (! empty($serviceSpec['id'])) {
        $service = Service::on($liveConnection)->withoutGlobalScopes()
            ->where('id', $serviceSpec['id'])
            ->first();
    }
    if (! $service) {
        $service = Service::on($liveConnection)->withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();
    }

    if (! $service) {
        $sortOrder = (int) (Service::on($liveConnection)->withoutGlobalScopes()
            ->where('sub_category_id', $subCategoryId)
            ->max('sort_order') ?? -1) + 1;

        $service = new Service;
        $service->setConnection($liveConnection);
        $service->id = $serviceSpec['id'] ?? (string) Str::uuid();
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
            'slug' => $slug,
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
    $sort = 0;

    foreach ($serviceSpec['variants'] as $variantSpec) {
        $iconPath = $variantIconDir.'/'.$slug.'-'.$variantSpec['variant_key'].'.png';
        $imageKey = $uploadAsset($iconPath, $storageDir);
        $variantTitle = $variantSpec['title'];
        $variantDescriptionText = $variantDescription($name);
        $variantNoteText = $variantNote();

        $variant = ServiceVariant::on($liveConnection)->create([
            'service_id' => $service->id,
            'variant_key' => $variantSpec['variant_key'],
            'title' => $variantTitle,
            'description' => $variantDescriptionText,
            'note' => $variantNoteText,
            'image' => $imageKey,
            'sort_order' => $sort++,
            'is_active' => true,
        ]);

        $upsertVariantStorage($variant->id);

        foreach (['title' => $variantTitle, 'description' => $variantDescriptionText, 'note' => $variantNoteText] as $field => $value) {
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

    echo "  variants: ".count($serviceSpec['variants'])." x {$zones->count()} zones\n";
    echo "  thumb: {$newThumbnail}\n";
    echo "  variant icon: {$slug}-book-site-inspection.png\n\n";
    sleep(1);
}

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update(['live_values' => $originalPrefix, 'test_values' => $originalPrefix]);
    StoragePathPrefix::resetCache();
}

echo "\nPainting catalog seeded: ".count($catalog['services'])." services.\n";
