<?php

/**
 * Seed remaining live services (images, overview, description, FAQs).
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/seed-remaining-categories-live.php
 */

use App\Support\CloudStorageConfigurator;
use App\Support\MediaStoragePath;
use App\Support\StoragePathPrefix;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\ServiceManagement\Entities\Faq;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Services\ServiceOverviewContentResolver;

require_once base_path('scripts/lib/RemainingServiceContentBuilder.php');

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

$liveConnection = 'live_remaining_content';
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

$mediaBase = 'https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod/';
$services = require base_path('scripts/data/remaining-services-manifest.php');

$uploadServiceImages = function (Service $service) use ($liveConnection): array {
    $assetSlug = $service->slug ?: Str::slug((string) $service->name, '-');
    $assetsDir = base_path('scripts/assets/service-images/'.$assetSlug);

    foreach (['thumbnail' => 'thumbnail.png', 'cover' => 'cover.png'] as $label => $file) {
        if (! is_file($assetsDir.'/'.$file)) {
            throw new RuntimeException("Missing {$label} asset for {$assetSlug}");
        }
    }

    $uploadAsset = function (string $sourcePath, string $storageDir, ?string $old = null): string {
        $file = new UploadedFile($sourcePath, basename($sourcePath), 'image/png', null, true);

        return media_file_uploader($storageDir, APPLICATION_IMAGE_FORMAT, $file, $old);
    };

    $upsertStorage = function (string $serviceModelId, string $column) use ($liveConnection): void {
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

    $storageDir = MediaStoragePath::serviceDir($service);
    $newThumbnail = $uploadAsset($assetsDir.'/thumbnail.png', $storageDir, $service->thumbnail);
    $newCover = $uploadAsset($assetsDir.'/cover.png', $storageDir, $service->cover_image);

    Service::on($liveConnection)->withoutGlobalScopes()->where('id', $service->id)->update([
        'thumbnail' => $newThumbnail,
        'cover_image' => $newCover,
    ]);

    $upsertStorage($service->id, 'thumbnail');
    $upsertStorage($service->id, 'cover_image');

    return ['thumbnail' => $newThumbnail, 'cover' => $newCover];
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

$buildOverview = static function (array $cfg, string $thumbUrl, string $coverUrl) use ($buildProcessSteps): array {
    return ServiceOverviewContentResolver::normalizeServiceContent([
        'intro' => $cfg['intro'],
        'override_top_icons' => false,
        'override_why_choose' => false,
        'top_icons' => [],
        'card_highlights' => $cfg['card_highlights'],
        'why_choose' => ['title' => '', 'items' => []],
        'service_process' => [
            'title' => 'How It Works',
            'items' => $buildProcessSteps($cfg['process_steps'], $thumbUrl, $coverUrl),
        ],
        'perfect_for' => ['title' => 'Ideal For', 'items' => $cfg['perfect_for']],
        'whats_included' => ['title' => "What's Included", 'items' => $cfg['whats_included']],
        'good_to_know' => ['title' => 'Things to Know', 'items' => $cfg['good_to_know']],
        'whats_not_included' => ['title' => 'Exclusions', 'items' => $cfg['whats_not_included']],
    ]);
};

foreach ($services as $serviceCfg) {
    $service = Service::on($liveConnection)->withoutGlobalScopes()->find($serviceCfg['id']);
    if (! $service) {
        throw new RuntimeException("Service not found: {$serviceCfg['id']}");
    }

    $content = RemainingServiceContentBuilder::build($serviceCfg);
    $cfg = array_merge($serviceCfg, $content);
    $paths = $uploadServiceImages($service);
    $thumbUrl = $mediaBase.$paths['thumbnail'];
    $coverUrl = $mediaBase.$paths['cover'];
    $overview = $buildOverview($cfg, $thumbUrl, $coverUrl);

    Service::on($liveConnection)->withoutGlobalScopes()->where('id', $cfg['id'])->update([
        'short_description' => $cfg['short_description'],
        'overview_content' => json_encode($overview),
        'description' => $cfg['description'],
    ]);

    Translation::on($liveConnection)->updateOrCreate(
        ['translationable_type' => Service::class, 'translationable_id' => $cfg['id'], 'locale' => 'en', 'key' => 'short_description'],
        ['value' => $cfg['short_description']]
    );
    Translation::on($liveConnection)->updateOrCreate(
        ['translationable_type' => Service::class, 'translationable_id' => $cfg['id'], 'locale' => 'en', 'key' => 'description'],
        ['value' => $cfg['description']]
    );

    Faq::on($liveConnection)->where('service_id', $cfg['id'])->delete();
    $sort = 0;
    foreach ($cfg['faqs'] as $faq) {
        Faq::on($liveConnection)->create([
            'question' => $faq[0],
            'answer' => $faq[1],
            'service_id' => $cfg['id'],
            'is_active' => 1,
            'sort_order' => $sort++,
        ]);
    }

    echo "UPDATED: {$cfg['name']}\n";
    echo "  thumb={$paths['thumbnail']}\n";
    echo "  cover={$paths['cover']}\n";
    echo '  faqs='.count($cfg['faqs'])."\n";
}

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update(['live_values' => $originalPrefix, 'test_values' => $originalPrefix]);
    StoragePathPrefix::resetCache();
}

echo 'Done. Seeded '.count($services)." remaining services on live.\n";
