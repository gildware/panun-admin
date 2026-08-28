<?php

/**
 * Seed AC Installation on live DB — content, images, overview, FAQs, and 4 package variants.
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/seed-ac-installation-live.php
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
use Modules\ServiceManagement\Entities\ServiceVariant;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ServiceManagement\Services\ServiceOverviewContentResolver;
use Modules\ZoneManagement\Entities\Zone;

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

$liveConnection = 'live_ac_install';
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

$serviceId = '0affd967-975b-4fc2-94af-4b870bf0945a';
$mediaBase = 'https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod/';
$assetSlug = 'ac-installation';

$shortDescription = 'Expert split and window AC installation by verified technicians — secure mounting, standard copper piping, vacuuming, gas check, and full test run.';
$description = 'From bracket mounting and copper piping to vacuuming, gas checks and cooling tests, every AC is installed with secure fittings and proper alignment — so your unit cools efficiently and is ready to use the same day.';

$variants = [
    [
        'key' => 'split-ac-upto-1-5-ton',
        'title' => 'Split AC Installation (up to 1.5 Ton)',
        'price' => 599,
        'image' => 'split-ac-upto-1-5-ton.png',
        'description' => 'Professional split AC installation for units up to 1.5 ton — ideal for bedrooms and small living areas.',
        'note' => 'Includes standard copper piping up to 3 metres. Extra piping quoted on site if needed.',
    ],
    [
        'key' => 'split-ac-1-5-to-2-ton',
        'title' => 'Split AC Installation (1.5–2 Ton)',
        'price' => 799,
        'image' => 'split-ac-1-5-to-2-ton.png',
        'description' => 'Mid-capacity split AC installation for living rooms, offices, and medium-sized spaces.',
        'note' => 'Includes standard copper piping up to 3 metres. Extra piping quoted on site if needed.',
    ],
    [
        'key' => 'window-ac-install',
        'title' => 'Window AC Installation',
        'price' => 499,
        'image' => 'window-ac-install.png',
        'description' => 'Secure window AC installation with alignment, sealing, and operational test.',
        'note' => 'Frame modification and new wiring are not included in standard scope.',
    ],
    [
        'key' => 'extra-copper-piping',
        'title' => 'Extra Copper Piping (per metre)',
        'price' => 150,
        'image' => 'extra-copper-piping.png',
        'description' => 'Add-on copper refrigerant pipe and insulation beyond your installation package allowance.',
        'note' => 'Book when indoor–outdoor distance exceeds the package allowance (typically 3 m).',
    ],
];

$faqs = [
    ['Do I need to buy the AC before booking installation?', 'Yes. This service covers installation of customer-supplied AC units. Share brand, tonnage, and photos when booking.'],
    ['How long does AC installation usually take?', 'Most split AC installations take about 2–4 hours depending on piping length, wall type, and access to the outdoor unit.'],
    ['Is extra copper piping included?', 'Standard packages include piping up to 3 metres. Additional length is billed per metre after on-site measurement.'],
    ['Do you install both split and window ACs?', 'Yes. Choose the package that matches your AC type and capacity when booking.'],
    ['Is gas refilling included?', 'Basic vacuuming and gas check are included. Extra gas top-up for pre-used units may be quoted on site.'],
    ['Will cooling be tested before handover?', 'Yes. Cooling, airflow, and drain function are checked before the technician leaves.'],
];

$uploadAsset = function (string $sourcePath, string $storageDir, ?string $old = null): string {
    $file = new UploadedFile($sourcePath, basename($sourcePath), 'image/png', null, true);

    return media_file_uploader($storageDir, APPLICATION_IMAGE_FORMAT, $file, $old);
};

$upsertServiceStorage = function (string $serviceModelId, string $column) use ($liveConnection): void {
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

$upsertVariantStorage = function (string $variantId) use ($liveConnection): void {
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

$service = Service::on($liveConnection)->withoutGlobalScopes()->find($serviceId);
if (! $service) {
    throw new RuntimeException("Service not found: {$serviceId}");
}

$assetsDir = base_path('scripts/assets/service-images/'.$assetSlug);
foreach (['thumbnail.png', 'cover.png'] as $file) {
    if (! is_file($assetsDir.'/'.$file)) {
        throw new RuntimeException("Missing service image: {$assetsDir}/{$file}");
    }
}

$storageDir = MediaStoragePath::serviceDir($service);
$newThumbnail = $uploadAsset($assetsDir.'/thumbnail.png', $storageDir, $service->thumbnail);
$newCover = $uploadAsset($assetsDir.'/cover.png', $storageDir, $service->cover_image);
$thumbUrl = $mediaBase.$newThumbnail;
$coverUrl = $mediaBase.$newCover;

$overview = ServiceOverviewContentResolver::normalizeServiceContent([
    'intro' => 'Expert AC installation with secure mounting, proper piping, and thorough cooling checks.',
    'override_top_icons' => false,
    'override_why_choose' => false,
    'top_icons' => [],
    'card_highlights' => [
        ['icon' => 'tools', 'text' => 'Expert Installation', 'color' => 'blue', 'sort_order' => 0],
        ['icon' => 'quality', 'text' => 'Secure Mounting', 'color' => 'green', 'sort_order' => 1],
        ['icon' => 'verified', 'text' => 'Verified Technicians', 'color' => 'purple', 'sort_order' => 2],
    ],
    'why_choose' => ['title' => '', 'items' => []],
    'service_process' => [
        'title' => 'How It Works',
        'items' => [
            ['icon' => 'calendar', 'title' => 'Choose your package', 'description' => 'Select split AC, window AC, or extra piping based on your unit and site needs.', 'sort_order' => 0],
            ['icon' => 'verified', 'title' => 'Technician assigned', 'description' => 'A verified Panun Kaergar technician confirms your visit and arrives with installation tools.', 'sort_order' => 1],
            ['icon' => 'location', 'title' => 'On-site visit', 'description' => 'Technician inspects indoor/outdoor placement, power point, and piping route before mounting.', 'image' => $thumbUrl, 'sort_order' => 2],
            ['icon' => 'tools', 'title' => 'Installation & piping', 'description' => 'Bracket mounting, copper piping, drain setup, and electrical connection completed as per package.', 'sort_order' => 3],
            ['icon' => 'quality', 'title' => 'Vacuum & gas check', 'description' => 'Lines are vacuumed, gas pressure verified, and cooling performance tested.', 'image' => $coverUrl, 'sort_order' => 4],
            ['icon' => 'sparkle', 'title' => 'Test & handover', 'description' => 'Remote demo, basic usage tips, and work-area cleanup before handover.', 'sort_order' => 5],
        ],
    ],
    'perfect_for' => [
        'title' => 'Ideal For',
        'items' => [
            ['icon' => 'home', 'text' => 'New homes', 'sort_order' => 0],
            ['icon' => 'building', 'text' => 'Apartments', 'sort_order' => 1],
            ['icon' => 'building', 'text' => 'Offices', 'sort_order' => 2],
            ['icon' => 'shop', 'text' => 'Shops', 'sort_order' => 3],
            ['icon' => 'sparkle', 'text' => 'Renovation', 'sort_order' => 4],
            ['icon' => 'tools', 'text' => 'Split AC', 'sort_order' => 5],
            ['icon' => 'tools', 'text' => 'Window AC', 'sort_order' => 6],
        ],
    ],
    'whats_included' => [
        'title' => "What's Included",
        'items' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Indoor & outdoor mounting', 'sort_order' => 1],
            ['icon' => 'check', 'title' => 'Standard copper piping', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Drain pipe setup', 'sort_order' => 3],
            ['icon' => 'quality', 'title' => 'Vacuum & gas check', 'sort_order' => 4],
            ['icon' => 'sparkle', 'title' => 'Cooling performance test', 'sort_order' => 5],
            ['icon' => 'verified', 'title' => 'Remote & mode demo', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
    ],
    'good_to_know' => [
        'title' => 'Things to Know',
        'items' => [
            ['icon' => 'check', 'title' => 'Keep the installation area clear before the visit', 'sort_order' => 0],
            ['icon' => 'power', 'title' => 'Ensure a working power point is available near the unit', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Share AC brand, model, and tonnage when booking', 'sort_order' => 2],
            ['icon' => 'location', 'title' => 'Indoor and outdoor locations should be accessible', 'sort_order' => 3],
            ['icon' => 'pricing', 'title' => 'Extra piping or material may affect final cost — explained before work starts', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before for cancellation or rescheduling', 'sort_order' => 5],
        ],
    ],
    'whats_not_included' => [
        'title' => 'Exclusions',
        'items' => [
            ['icon' => 'pricing', 'title' => 'Cost of the AC unit', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Civil work, wall breaking, or core drilling', 'sort_order' => 1],
            ['icon' => 'power', 'title' => 'Major electrical rewiring or new circuit installation', 'sort_order' => 2],
            ['icon' => 'water', 'title' => 'Gas refilling beyond standard installation scope', 'sort_order' => 3],
            ['icon' => 'sparkle', 'title' => 'Annual maintenance or deep cleaning service', 'sort_order' => 4],
            ['icon' => 'door', 'title' => 'Old unit removal and disposal unless agreed on site', 'sort_order' => 5],
            ['icon' => 'building', 'title' => 'Scaffolding, crane, or special height-access equipment', 'sort_order' => 6],
            ['icon' => 'home', 'title' => 'Structural modifications to walls or facades', 'sort_order' => 7],
        ],
    ],
]);

Service::on($liveConnection)->withoutGlobalScopes()->where('id', $serviceId)->update([
    'short_description' => $shortDescription,
    'description' => $description,
    'overview_content' => json_encode($overview),
    'thumbnail' => $newThumbnail,
    'cover_image' => $newCover,
    'min_bidding_price' => 499,
]);

$upsertServiceStorage($serviceId, 'thumbnail');
$upsertServiceStorage($serviceId, 'cover_image');

Translation::on($liveConnection)->updateOrCreate(
    ['translationable_type' => Service::class, 'translationable_id' => $serviceId, 'locale' => 'en', 'key' => 'short_description'],
    ['value' => $shortDescription]
);
Translation::on($liveConnection)->updateOrCreate(
    ['translationable_type' => Service::class, 'translationable_id' => $serviceId, 'locale' => 'en', 'key' => 'description'],
    ['value' => $description]
);

Faq::on($liveConnection)->where('service_id', $serviceId)->delete();
$faqSort = 0;
foreach ($faqs as $faq) {
    Faq::on($liveConnection)->create([
        'question' => $faq[0],
        'answer' => $faq[1],
        'service_id' => $serviceId,
        'is_active' => 1,
        'sort_order' => $faqSort++,
    ]);
}

$oldVariantIds = ServiceVariant::on($liveConnection)->where('service_id', $serviceId)->pluck('id');
if ($oldVariantIds->isNotEmpty()) {
    Translation::on($liveConnection)
        ->where('translationable_type', ServiceVariant::class)
        ->whereIn('translationable_id', $oldVariantIds->all())
        ->delete();
}
Variation::on($liveConnection)->where('service_id', $serviceId)->delete();
ServiceVariant::on($liveConnection)->where('service_id', $serviceId)->delete();

$zones = Zone::on($liveConnection)->where('is_active', 1)->get();
if ($zones->isEmpty()) {
    throw new RuntimeException('No active zones on live DB.');
}

$iconDir = base_path('scripts/assets/variant-icons');
$variationPricing = [];
$sort = 0;

foreach ($variants as $spec) {
    $iconPath = $iconDir.'/'.$spec['image'];
    if (! is_file($iconPath)) {
        throw new RuntimeException("Missing variant icon: {$iconPath}");
    }

    $imageKey = $uploadAsset($iconPath, $storageDir);

    $variant = ServiceVariant::on($liveConnection)->create([
        'service_id' => $serviceId,
        'variant_key' => $spec['key'],
        'title' => $spec['title'],
        'description' => $spec['description'],
        'note' => $spec['note'],
        'image' => $imageKey,
        'sort_order' => $sort++,
        'is_active' => true,
    ]);

    $upsertVariantStorage($variant->id);

    foreach (['title' => $spec['title'], 'description' => $spec['description'], 'note' => $spec['note']] as $field => $value) {
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

    $variationPricing[$spec['key']] = [
        'use_zone_pricing' => false,
        'default_price' => (float) $spec['price'],
    ];

    foreach ($zones as $zone) {
        Variation::on($liveConnection)->create([
            'service_id' => $serviceId,
            'service_variant_id' => $variant->id,
            'variant_key' => $spec['key'],
            'variant' => $spec['title'],
            'zone_id' => $zone->id,
            'price' => (float) $spec['price'],
        ]);
    }

    echo "Variant: {$spec['title']} @ {$spec['price']}\n";
}

Service::on($liveConnection)->withoutGlobalScopes()->where('id', $serviceId)->update([
    'variation_pricing' => json_encode($variationPricing),
]);

if ($prefixSetting && $originalPrefix !== null) {
    $prefixSetting->update(['live_values' => $originalPrefix, 'test_values' => $originalPrefix]);
    StoragePathPrefix::resetCache();
}

echo "AC Installation seeded on live with 4 variants x {$zones->count()} zones.\n";
echo "Next: cd panun-marketing && npm run sync-catalog   # refresh marketing image URLs\n";
