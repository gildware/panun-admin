<?php

/**
 * Production-grade AC Installation seed for dev DB.
 * DB_HOST=82.25.121.201 DB_DATABASE=u397782854_dev_pk_dec DB_USERNAME=... DB_PASSWORD=... \
 * php artisan tinker scripts/apply-ac-installation-dev.php
 */

use App\Support\CloudStorageConfigurator;
use App\Support\MediaStoragePath;
use Illuminate\Http\UploadedFile;
use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;
use Modules\ServiceManagement\Entities\Tag;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ZoneManagement\Entities\Zone;

CloudStorageConfigurator::apply();
\App\Support\StoragePathPrefix::resetCache();
putenv('STORAGE_PATH_PREFIX=dev');
\App\Support\StoragePathPrefix::resetCache();

if (! defined('APPLICATION_IMAGE_FORMAT')) {
    define('APPLICATION_IMAGE_FORMAT', 'webp');
}

$serviceId = '0affd967-975b-4fc2-94af-4b870bf0945a';
$assetsDir = base_path('scripts/assets/ac-images');

$uploadAsset = function (string $filename, string $storageDir, ?string $old = null) use ($assetsDir): string {
    $path = $assetsDir.'/'.$filename;
    if (! is_file($path)) {
        throw new RuntimeException("Missing image asset: {$filename}");
    }
    $file = new UploadedFile($path, basename($path), mime_content_type($path) ?: 'image/png', null, true);

    return media_file_uploader($storageDir, APPLICATION_IMAGE_FORMAT, $file, $old);
};

$upsertTranslation = function (string $modelId, string $modelClass, string $key, string $value): void {
    Translation::query()->updateOrCreate(
        [
            'translationable_type' => $modelClass,
            'translationable_id' => $modelId,
            'locale' => 'en',
            'key' => $key,
        ],
        ['value' => $value]
    );
};

$shortDescription = 'Expert split & window AC installation in Srinagar and nearby — secure bracket mounting, standard copper piping, vacuuming, gas check, and full test run by verified Panun Kaergar technicians. Transparent packages; add-ons quoted on site.';

$longDescription = <<<'HTML'
<p>Get your new air conditioner installed the right way. Panun Kaergar connects you with verified technicians who follow safe, brand-aware procedures for split and window ACs — so your unit cools efficiently, drains properly, and is ready to use the same day.</p>
<p>Choose a bookable package below (by tonnage or AC type). Each option shows a clear starting price, what is included, and what may cost extra on site.</p>

<h3>What's included (service scope)</h3>
<ul>
<li>Pre-install site check for indoor/outdoor placement and power point</li>
<li>Wall bracket installation for split AC indoor unit (as per package)</li>
<li>Outdoor unit mounting on bracket or stand (where applicable)</li>
<li>Standard copper refrigerant piping and insulation (length per package)</li>
<li>Interconnecting power cable routing and secure fastening</li>
<li>Drain pipe installation with correct slope to prevent leakage</li>
<li>Vacuuming of refrigerant lines and basic leak check</li>
<li>Gas pressure verification and cooling performance test</li>
<li>Remote/mode demo and basic user guidance</li>
<li>Work-area cleanup after installation</li>
</ul>

<h3>What's excluded</h3>
<ul>
<li>Cost of the AC unit (customer-supplied unless purchased separately)</li>
<li>Extra copper pipe, wiring, or drain beyond package allowance (billed per metre on site)</li>
<li>Core cutting in thick walls, marble/granite drilling, or structural changes</li>
<li>Custom fabrication, heavy stands, scaffolding, or crane access</li>
<li>New electrical point, MCB upgrade, or meter-board work</li>
<li>Refrigerant top-up for pre-used or pre-gassed units if required</li>
<li>Repair of poor previous installation, rusted brackets, or damaged wiring</li>
<li>Civil work — hole filling, painting, or plastering after install</li>
</ul>

<h3>Service process</h3>
<ol>
<li><strong>Book online</strong> — Select your package (split tonnage / window AC / add-ons) and share address, AC brand, and tonnage.</li>
<li><strong>Technician assigned</strong> — Verified professional confirms slot and carries standard tools/materials.</li>
<li><strong>On-site survey</strong> — Indoor/outdoor location, piping route, drain path, and power point checked.</li>
<li><strong>Installation</strong> — Bracket, units, piping, wiring, and drain completed per package scope.</li>
<li><strong>Vacuum & test</strong> — Line vacuum, gas check, cooling test, and leak observation.</li>
<li><strong>Handover</strong> — Demo + written/verbal note on any optional add-ons (extra pipe, core cutting, etc.).</li>
</ol>

<h3>Terms &amp; conditions</h3>
<ul>
<li>Prices shown are <strong>labour + standard materials</strong> for the selected package in your service zone. Final bill may change if extra materials or special wall work is required — technician will explain <strong>before</strong> proceeding.</li>
<li>Customer must provide a <strong>stable power point</strong> and clear access to install areas. Delays due to inaccessible sites may incur a revisit charge.</li>
<li>Warranty on workmanship is as per Panun Kaergar policy; <strong>manufacturer warranty</strong> on the AC unit remains with the brand/dealer.</li>
<li>Panun Kaergar is not liable for pre-existing electrical faults, hidden wall conditions, or unit defects in customer-supplied ACs.</li>
<li>Cancellation/reschedule: please notify at least 2 hours before the slot where possible.</li>
<li>By booking, you agree that add-on work (extra piping, core cutting, electrical upgrades) is optional and charged separately after on-site assessment.</li>
</ul>
HTML;

$tags = [
    'AC installation',
    'split AC install',
    'window AC install',
    'AC fitting',
    'air conditioner installation',
    'home appliances',
    'Srinagar',
    'Panun Kaergar',
];

$variants = [
    [
        'key' => 'split-ac-upto-1-5-ton',
        'title' => 'Split AC Installation (up to 1.5 Ton)',
        'price' => 599,
        'image' => 'variant-split-1-5-ton.png',
        'description' => <<<'TEXT'
Book professional split AC installation for units up to 1.5 ton — perfect for bedrooms, guest rooms, and small living areas.

What's included:
• Wall bracket for indoor unit
• Outdoor unit mounting on bracket
• Copper piping & insulation up to 3 metres
• Drain pipe with proper slope
• Vacuuming, gas check & cooling test
• Basic demo of remote and modes

Ideal for: 0.75–1.5 ton split AC (customer-provided unit).

Not included: AC unit cost, piping beyond 3 m, core cutting, new electrical point, MCB upgrade.

Add-on available: Extra copper piping (per metre) — book separately or confirm on site.
TEXT,
    ],
    [
        'key' => 'split-ac-1-5-to-2-ton',
        'title' => 'Split AC Installation (1.5–2 Ton)',
        'price' => 799,
        'image' => 'variant-split-2-ton.png',
        'description' => <<<'TEXT'
Book mid-capacity split AC installation (1.5–2 ton) for living rooms, offices, and medium-sized spaces.

What's included:
• Heavy-duty bracket mounting (indoor + outdoor)
• Standard copper piping & insulation (up to 3 m)
• Power cable routing and drain installation
• Vacuuming, leak check & performance test
• Technician guidance on optimal usage

Ideal for: 1.5–2 ton split systems from all major brands.

Not included: Unit purchase, extra piping per metre, scaffolding, structural modifications.

Note: If indoor–outdoor distance exceeds 3 m, extra copper piping is quoted on site before work starts.
TEXT,
    ],
    [
        'key' => 'window-ac-install',
        'title' => 'Window AC Installation',
        'price' => 499,
        'image' => 'variant-window-ac.png',
        'description' => <<<'TEXT'
Book secure window AC installation with sealing and operational test.

What's included:
• Window AC placement and alignment in frame
• Basic sealing to reduce air gaps
• Power connection to existing point
• Operational test (cooling + modes)
• Safety check of mounting stability

Ideal for: Standard window AC units in homes, shops, and small offices.

Not included: Frame modification, new wiring from DB, unit purchase, exterior grill fabrication.

Best for: Quick cooling solutions where split AC is not preferred.
TEXT,
    ],
    [
        'key' => 'extra-copper-piping',
        'title' => 'Extra Copper Piping (per metre)',
        'price' => 150,
        'image' => 'variant-copper-pipe.png',
        'description' => <<<'TEXT'
Add-on bookable item — additional copper refrigerant pipe and insulation beyond your installation package allowance.

What's included (per metre booked):
• Copper pipe + insulation for measured length
• Fitted as part of installation visit

When to book: When technician confirms distance between indoor and outdoor units exceeds package allowance (typically 3 m).

Billed per metre after on-site measurement. Can be added during installation visit — technician will show exact length before proceeding.

Not a standalone visit — pair with a split AC installation package.
TEXT,
    ],
];

$service = Service::withoutGlobalScopes()->find($serviceId);
if (! $service) {
    throw new RuntimeException("Service not found: {$serviceId}");
}

$serviceDir = MediaStoragePath::serviceDir('AC Installation');

$service->name = 'AC Installation';
$service->short_description = $shortDescription;
$service->description = $longDescription;
$service->min_bidding_price = 599;
$service->is_active = 1;
$service->cover_image = $uploadAsset('ac-installation-cover.png', $serviceDir, $service->cover_image);
$service->thumbnail = $uploadAsset('ac-installation-thumb.png', $serviceDir, $service->thumbnail);
$service->save();

$upsertTranslation($serviceId, Service::class, 'name', 'AC Installation');
$upsertTranslation($serviceId, Service::class, 'short_description', $shortDescription);
$upsertTranslation($serviceId, Service::class, 'description', $longDescription);

$tagIds = [];
foreach ($tags as $tagName) {
    $tag = Tag::query()->firstOrCreate(['tag' => $tagName]);
    $tagIds[] = $tag->id;
}
$service->tags()->sync($tagIds);

$remoteVariantIds = ServiceVariant::query()->where('service_id', $serviceId)->pluck('id');
if ($remoteVariantIds->isNotEmpty()) {
    Translation::query()->whereIn('translationable_id', $remoteVariantIds->all())->delete();
}
Variation::query()->where('service_id', $serviceId)->delete();
ServiceVariant::query()->where('service_id', $serviceId)->delete();

$zones = Zone::query()->where('is_active', 1)->get();
if ($zones->isEmpty()) {
    throw new RuntimeException('No active zones');
}

$variationPricing = [];
$sort = 0;

foreach ($variants as $spec) {
    $imageKey = $uploadAsset($spec['image'], $serviceDir);

    $variant = ServiceVariant::query()->create([
        'service_id' => $serviceId,
        'variant_key' => $spec['key'],
        'title' => $spec['title'],
        'description' => $spec['description'],
        'image' => $imageKey,
        'sort_order' => $sort++,
        'is_active' => true,
    ]);

    $upsertTranslation($variant->id, ServiceVariant::class, 'title', $spec['title']);
    $upsertTranslation($variant->id, ServiceVariant::class, 'description', $spec['description']);

    $variationPricing[$spec['key']] = [
        'use_zone_pricing' => false,
        'default_price' => (float) $spec['price'],
    ];

    foreach ($zones as $zone) {
        Variation::query()->create([
            'service_id' => $serviceId,
            'service_variant_id' => $variant->id,
            'variant_key' => $spec['key'],
            'variant' => $spec['title'],
            'zone_id' => $zone->id,
            'price' => (float) $spec['price'],
        ]);
    }

    echo "Variant: {$spec['title']} @ ₹{$spec['price']}\n";
}

$service->variation_pricing = $variationPricing;
$service->save();

echo "AC Installation complete — cover, thumbnail, " . count($variants) . " variants x {$zones->count()} zones, " . count($tags) . " tags.\n";
