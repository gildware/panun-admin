<?php

/**
 * Seed AC catalog content on dev DB (u397782854_dev_pk_dec).
 * Run:
 * DB_HOST=82.25.121.201 DB_DATABASE=u397782854_dev_pk_dec DB_USERNAME=u397782854_dev_pk_dec_usr DB_PASSWORD='...' \
 * php artisan tinker scripts/apply-ac-dev-content.php
 */

use App\Support\CloudStorageConfigurator;
use App\Support\MediaStoragePath;
use Illuminate\Http\UploadedFile;
use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ZoneManagement\Entities\Zone;

CloudStorageConfigurator::apply();

if (! defined('APPLICATION_IMAGE_FORMAT')) {
    define('APPLICATION_IMAGE_FORMAT', 'webp');
}

$assetsDir = base_path('scripts/assets/ac-images');

$uploadAsset = function (string $filename, string $storageDir, ?string $old = null) use ($assetsDir): string {
    $path = $assetsDir.'/'.$filename;
    if (! is_file($path)) {
        throw new RuntimeException("Missing image asset: {$filename}");
    }
    $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
    $file = new UploadedFile($path, basename($path), mime_content_type($path) ?: 'image/jpeg', null, true);

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

$mainCategoryId = '028602bc-174a-41f9-b583-ae8f4850e646'; // Home Appliances
$subCategoryId = '716233b9-7954-4262-a79e-8df58a6a3090'; // Air Conditioners

$mainCategoryHtml = <<<'HTML'
<p>Book trusted home appliance services in Srinagar and nearby — installation, repair, servicing, and uninstallation by verified technicians with upfront pricing and clear scope on every job.</p>
<ul>
<li>Split & window AC, electrical, plumbing, carpentry, and more</li>
<li>Zone-wise transparent rates — pick the package that fits your need</li>
<li>Same-day slots where available</li>
</ul>
HTML;

$subCategoryHtml = <<<'HTML'
<p>Complete air conditioner care for homes and offices — installation, repair, deep servicing, and safe uninstallation. Every service lists what is included, what is excluded, and the step-by-step process so you know exactly what you are booking.</p>
<ul>
<li>Split & window AC — all major brands</li>
<li>Bookable packages with fixed starting prices</li>
<li>Add-ons (extra piping, packing, coating) quoted clearly on site</li>
</ul>
<p>Choose a package below or browse by service type. Each option is a bookable item with its own price, description, and photo.</p>
HTML;

$mainCategory = Category::withoutGlobalScopes()->find($mainCategoryId);
if ($mainCategory) {
    $mainCategory->description = $mainCategoryHtml;
    $mainCategory->image = $uploadAsset('home-appliances.jpg', MediaStoragePath::categoryDirFromName('Home Appliances', false), $mainCategory->image);
    $mainCategory->save();
    $upsertTranslation($mainCategoryId, Category::class, 'name', $mainCategory->name);
    $upsertTranslation($mainCategoryId, Category::class, 'description', $mainCategoryHtml);
    echo "Updated category: Home Appliances\n";
}

$subCategory = Category::withoutGlobalScopes()->find($subCategoryId);
if ($subCategory) {
    $subCategory->description = $subCategoryHtml;
    $subCategory->image = $uploadAsset('air-conditioners.jpg', MediaStoragePath::categoryDirFromName('Air Conditioners', true), $subCategory->image);
    $subCategory->save();
    $upsertTranslation($subCategoryId, Category::class, 'name', $subCategory->name);
    $upsertTranslation($subCategoryId, Category::class, 'description', $subCategoryHtml);
    echo "Updated subcategory: Air Conditioners\n";
}

$services = [
    '0affd967-975b-4fc2-94af-4b870bf0945a' => [
        'name' => 'AC Installation',
        'min_bidding_price' => 599,
        'cover_image' => 'split-ac.jpg',
        'thumbnail' => 'split-ac.jpg',
        'short_description' => 'Professional split & window AC installation with bracket mounting, standard copper piping, vacuuming, gas check, and test run by verified technicians.',
        'description' => <<<'HTML'
<p>Book expert AC installation for your new split or window air conditioner. Our certified technicians follow brand-safe procedures so your unit runs efficiently from day one — with clear scope, transparent add-ons, and a proper test run before they leave.</p>
<h3>What's included</h3>
<ul>
<li>Site assessment and safe mounting location check</li>
<li>Wall bracket installation for indoor unit (split AC)</li>
<li>Outdoor unit placement on bracket / stand (as applicable)</li>
<li>Standard copper piping and insulation (up to included length per package)</li>
<li>Interconnecting cable routing and secure fastening</li>
<li>Drain pipe installation with proper slope</li>
<li>Vacuuming of refrigerant lines and leak check</li>
<li>Gas pressure verification and cooling test run</li>
<li>Basic operational demo (modes, remote, temperature settings)</li>
<li>Work area cleanup after installation</li>
</ul>
<h3>What's excluded</h3>
<ul>
<li>AC unit purchase cost (customer-provided unit unless booked separately)</li>
<li>Extra copper piping, wiring, or drain pipe beyond included length</li>
<li>Core cutting in thick walls, marble/granite drilling surcharges</li>
<li>Structural modifications, custom stands, or heavy fabrication</li>
<li>MCB upgrade, new electrical point, or meter board work</li>
<li>Refrigerant top-up for pre-used or pre-gassed units (if required)</li>
<li>Scaffolding, crane, or high-rise special access arrangements</li>
<li>Repair of existing wiring, brackets, or previous poor installation</li>
</ul>
<h3>Service process</h3>
<ol>
<li><strong>Booking confirmed</strong> — You share AC type, tonnage, brand, and install address.</li>
<li><strong>Technician visit</strong> — On-site survey for indoor/outdoor placement and power point.</li>
<li><strong>Installation</strong> — Bracket, piping, wiring, drain, and unit mounting completed.</li>
<li><strong>Vacuum & testing</strong> — Line vacuum, gas check, and cooling performance test.</li>
<li><strong>Handover</strong> — Demo + note on any optional add-ons (extra pipe, core cutting, etc.).</li>
</ol>
<p><strong>Note:</strong> Final cost may vary if extra materials or special wall work is needed. Technician will explain before proceeding.</p>
HTML,
        'variants' => [
            [
                'key' => 'split-ac-upto-1-5-ton',
                'title' => 'Split AC Installation (up to 1.5 Ton)',
                'price' => 599,
                'image' => 'split-ac.jpg',
                'description' => "Book split AC installation for units up to 1.5 ton — ideal for bedrooms and small living rooms.\n\nWhat's included: wall bracket, standard copper piping up to 3 m, drain line, interconnect cable, vacuuming, gas check, and cooling test run.\n\nIdeal for: 1–1.5 ton split AC (customer-provided unit).\n\nNot included: extra piping beyond 3 m, core cutting, new electrical point, AC unit cost.",
            ],
            [
                'key' => 'split-ac-1-5-to-2-ton',
                'title' => 'Split AC Installation (1.5–2 Ton)',
                'price' => 799,
                'image' => 'ac-outdoor.jpg',
                'description' => "Book mid-capacity split AC installation (1.5–2 ton) for living rooms and medium spaces.\n\nWhat's included: bracket mounting, standard materials, electrical hook-up, vacuum, and performance test.\n\nIdeal for: 1.5–2 ton split systems.\n\nNote: Extra copper piping quoted per metre on site if your indoor/outdoor distance exceeds the package allowance.",
            ],
            [
                'key' => 'window-ac-install',
                'title' => 'Window AC Installation',
                'price' => 499,
                'image' => 'window-ac.jpg',
                'description' => "Book window AC fitting and secure installation with operational test.\n\nWhat's included: unit placement in window frame, basic sealing, power connection, and test run.\n\nIdeal for: standard window AC units.\n\nNot included: frame modification, new electrical wiring, or unit purchase.",
            ],
            [
                'key' => 'extra-copper-piping',
                'title' => 'Extra Copper Piping (per metre)',
                'price' => 150,
                'image' => 'copper-pipe.jpg',
                'description' => "Add-on bookable item for additional copper pipe and insulation beyond your installation package.\n\nBilled per metre after technician measures on site.\n\nIncludes: copper pipe + insulation for the measured length.\n\nBook along with installation or when technician confirms extra length is required.",
            ],
        ],
    ],
    'e228f94a-9461-4b93-b5f7-6f1da920ddd0' => [
        'name' => 'AC Repair',
        'min_bidding_price' => 299,
        'cover_image' => 'ac-repair.jpg',
        'thumbnail' => 'ac-repair.jpg',
        'short_description' => 'Reliable AC repair for not cooling, water leakage, noise, error codes, and electrical faults — diagnosis first, clear quote before major work.',
        'description' => <<<'HTML'
<p>Is your AC not cooling, leaking water, making noise, or showing an error code? Book a repair visit for proper diagnosis and fix by experienced technicians. We focus on root-cause repair, not temporary patches.</p>
<h3>What's included</h3>
<ul>
<li>Technician visit and fault diagnosis</li>
<li>Basic inspection of indoor/outdoor unit, filters, drain, and airflow</li>
<li>Electrical checks (power supply, capacitor, connections where accessible)</li>
<li>Gas pressure check and leak detection (as applicable)</li>
<li>Minor fixes where possible in same visit (clogged drain, loose wiring, settings)</li>
<li>Cooling performance test after repair</li>
<li>Clear explanation of issue and recommended solution</li>
</ul>
<h3>What's excluded</h3>
<ul>
<li>Spare parts cost (PCB, capacitor, motor, sensor, compressor, etc.) unless explicitly included in quote</li>
<li>Refrigerant gas refill charges (billed separately if low gas confirmed)</li>
<li>Full dismantling for major compressor replacement without approval</li>
<li>Wall damage, rusted brackets, or civil work from old installation</li>
<li>Manufacturer warranty claim processing (brand service centre scope)</li>
<li>Replacement of entire indoor/outdoor unit</li>
</ul>
<h3>Service process</h3>
<ol>
<li><strong>Report issue</strong> — Share symptoms (no cooling, leak, noise, error light, etc.).</li>
<li><strong>On-site diagnosis</strong> — Technician inspects unit and identifies probable cause.</li>
<li><strong>Quote approval</strong> — For parts/labour beyond basic visit, you approve before work.</li>
<li><strong>Repair</strong> — Fault corrected using suitable parts and standard repair method.</li>
<li><strong>Testing</strong> — Cooling/airflow check and leak re-check where relevant.</li>
</ol>
HTML,
        'variants' => [
            [
                'key' => 'general-inspection',
                'title' => 'AC Inspection & Minor Fix',
                'price' => 299,
                'image' => 'ac-inspection.jpg',
                'description' => "Book a technician visit for diagnosis and minor fixes.\n\nCovers: on-site inspection, drain flush, contact cleaning, settings check, and small fixes possible in one visit.\n\nBest for: unclear issues, weak cooling, water drip, or remote/sensor problems.\n\nParts and gas refill quoted separately if needed.",
            ],
            [
                'key' => 'cooling-gas-repair',
                'title' => 'Low Cooling / Gas Issue Repair',
                'price' => 499,
                'image' => 'ac-outdoor.jpg',
                'description' => "Book when your AC runs but does not cool properly.\n\nIncludes: low-cooling diagnosis, leak check, and gas-related correction labour.\n\nGas refill charged separately if low refrigerant is confirmed.\n\nIdeal for: reduced cooling, ice formation, or suspected gas leak.",
            ],
            [
                'key' => 'pcb-electrical-repair',
                'title' => 'PCB / Electrical Repair',
                'price' => 399,
                'image' => 'ac-pcb.jpg',
                'description' => "Book for control board, capacitor, wiring, or sensor faults.\n\nIncludes: electrical diagnosis and repair labour.\n\nPCB, capacitor, and spare parts billed on actual basis after approval.\n\nIdeal for: unit not starting, display errors, tripping, or fan not running.",
            ],
            [
                'key' => 'fan-compressor-repair',
                'title' => 'Fan Motor / Compressor Repair',
                'price' => 599,
                'image' => 'ac-repair.jpg',
                'description' => "Book for outdoor/indoor fan noise, seized fan, or compressor-related faults.\n\nIncludes: advanced diagnosis and repair labour.\n\nFinal quote provided on site after inspection — parts extra.\n\nIdeal for: loud outdoor unit, no airflow, or compressor not kicking in.",
            ],
        ],
    ],
    '1151db87-80b4-4257-b4c2-bd40ddc00416' => [
        'name' => 'AC Servicing',
        'min_bidding_price' => 399,
        'cover_image' => 'ac-service.jpg',
        'thumbnail' => 'ac-service.jpg',
        'short_description' => 'Complete AC service — deep cleaning of filters & coils, drain flush, gas check — for stronger cooling, cleaner air, and lower electricity use.',
        'description' => <<<'HTML'
<p>Regular AC servicing improves cooling, reduces power consumption, and prevents breakdowns. Our service covers hygiene cleaning, performance checks, and preventive care for split and window units.</p>
<h3>What's included</h3>
<ul>
<li>Front panel removal and filter cleaning</li>
<li>Indoor evaporator coil cleaning (standard or deep jet, as per package)</li>
<li>Outdoor condenser coil cleaning and dust removal</li>
<li>Drain pipe flushing to reduce water leakage risk</li>
<li>Blower/fan area cleaning (accessible parts)</li>
<li>Electrical connection tightening and basic safety check</li>
<li>Gas pressure / cooling performance check</li>
<li>Remote and mode function check</li>
<li>Reassembly and test run</li>
</ul>
<h3>What's excluded</h3>
<ul>
<li>Gas top-up or major gas leak repair (charged separately if needed)</li>
<li>PCB, motor, or compressor replacement</li>
<li>Anti-rust / antimicrobial coating unless add-on selected</li>
<li>Full dismantling of sealed units for heavy mould remediation</li>
<li>Replacement of damaged drain pipe, insulation, or wiring</li>
</ul>
<h3>Service process</h3>
<ol>
<li><strong>Pre-check</strong> — Technician notes AC type, age, and visible issues.</li>
<li><strong>Indoor service</strong> — Filters, coils, drain path cleaned as per package.</li>
<li><strong>Outdoor service</strong> — Condenser cleaned and airflow restored.</li>
<li><strong>Performance test</strong> — Cooling, noise, and drain flow checked.</li>
</ol>
HTML,
        'variants' => [
            [
                'key' => 'standard-split-service',
                'title' => 'Standard AC Service (Split)',
                'price' => 399,
                'image' => 'ac-service.jpg',
                'description' => "Book routine split AC servicing for better cooling and hygiene.\n\nIncludes: filter wash, coil clean (standard), drain flush, basic electrical check, and test run.\n\nRecommended every 3–6 months.\n\nIdeal for: regularly used split AC in homes and offices.",
            ],
            [
                'key' => 'deep-jet-wash',
                'title' => 'Deep Jet Wash Service',
                'price' => 599,
                'image' => 'ac-outdoor.jpg',
                'description' => "Book intensive indoor coil jet cleaning for heavy dirt, odour, or poor airflow.\n\nIncludes: deep jet wash of evaporator coil, filter service, drain flush, outdoor condenser clean, and performance test.\n\nBest if AC not serviced for 12+ months.",
            ],
            [
                'key' => 'window-ac-service',
                'title' => 'Window AC Service',
                'price' => 349,
                'image' => 'window-ac.jpg',
                'description' => "Book full service for window AC units.\n\nIncludes: filter cleaning, accessible coil clean, drain check, and operational test.\n\nIdeal for: window-mounted units in bedrooms and shops.",
            ],
            [
                'key' => 'anti-rust-coating',
                'title' => 'Anti-Rust Coil Coating (Add-on)',
                'price' => 199,
                'image' => 'copper-pipe.jpg',
                'description' => "Add-on bookable coating for humid or coastal conditions.\n\nHelps protect coils and extend unit life.\n\nBook with any servicing package.\n\nApplied after cleaning — technician will confirm suitability on site.",
            ],
        ],
    ],
    '07d83084-21d9-48ca-bce2-643a4cdd38dc' => [
        'name' => 'AC Uninstallation',
        'min_bidding_price' => 449,
        'cover_image' => 'ac-uninstall.jpg',
        'thumbnail' => 'ac-uninstall.jpg',
        'short_description' => 'Safe AC removal with gas recovery (where applicable), line capping, and careful packing — ideal when shifting home or replacing your unit.',
        'description' => <<<'HTML'
<p>Moving out or upgrading your AC? Our uninstallation service removes your unit safely, minimises damage to walls and pipes, and prepares the AC for storage or transport.</p>
<h3>What's included</h3>
<ul>
<li>Power isolation and safe shutdown procedure</li>
<li>Recovery of refrigerant where applicable and method allows</li>
<li>Indoor and outdoor unit detachment</li>
<li>Copper line disconnection and proper capping/sealing</li>
<li>Bracket removal (if included in selected package)</li>
<li>Basic cleaning of removed unit exterior</li>
<li>Work area cleanup after removal</li>
</ul>
<h3>What's excluded</h3>
<ul>
<li>Wall hole filling, painting, or plastering (civil work)</li>
<li>Transportation to new location</li>
<li>Re-installation at new address (book installation separately)</li>
<li>Disposal of old/scrap unit unless agreed</li>
<li>Crane/ladder special access for high installations</li>
</ul>
<h3>Service process</h3>
<ol>
<li><strong>Confirm scope</strong> — Split/window, floor level, bracket removal needs.</li>
<li><strong>Safe shutdown</strong> — Power off and system prepared for removal.</li>
<li><strong>Disconnection & removal</strong> — Units brought down safely, lines capped.</li>
<li><strong>Handover</strong> — Notes on wall marks and re-install tips.</li>
</ol>
HTML,
        'variants' => [
            [
                'key' => 'split-ac-uninstall',
                'title' => 'Split AC Uninstallation',
                'price' => 449,
                'image' => 'ac-uninstall.jpg',
                'description' => "Book full split system removal.\n\nIncludes: safe shutdown, line disconnection, gas recovery attempt, indoor/outdoor unit removal, and line capping.\n\nIdeal for: home shifting or AC replacement.\n\nBracket left on wall unless bracket-removal add-on is booked.",
            ],
            [
                'key' => 'window-ac-uninstall',
                'title' => 'Window AC Uninstallation',
                'price' => 349,
                'image' => 'window-ac.jpg',
                'description' => "Book window AC removal.\n\nIncludes: unit detachment from frame, power disconnect, and safe removal.\n\nFrame area left accessible for cleaning or repainting.\n\nIdeal for: window units in apartments and shops.",
            ],
            [
                'key' => 'uninstall-packing',
                'title' => 'Uninstall + Packing for Transport',
                'price' => 549,
                'image' => 'ac-packing.jpg',
                'description' => "Book uninstallation with extra protective packing for shifting homes.\n\nIncludes: full removal plus wrap/protection for transport.\n\nIdeal for: inter-city or inter-district moves.\n\nTransport arranged separately by customer.",
            ],
            [
                'key' => 'bracket-removal',
                'title' => 'Wall Bracket Removal (Add-on)',
                'price' => 199,
                'image' => 'ac-repair.jpg',
                'description' => "Add-on to remove wall brackets after split AC uninstall.\n\nIncludes: bracket removal from wall.\n\nPatching/painting is customer civil work.\n\nBook with split uninstall if you want a clean wall surface.",
            ],
        ],
    ],
];

$zones = Zone::query()->where('is_active', 1)->get();
if ($zones->isEmpty()) {
    throw new RuntimeException('No active zones found');
}

foreach ($services as $serviceId => $payload) {
    $service = Service::withoutGlobalScopes()->find($serviceId);
    if (! $service) {
        echo "Missing service: {$serviceId}\n";
        continue;
    }

    $serviceDir = MediaStoragePath::serviceDir($service->name ?? $payload['name']);

    $service->name = $payload['name'];
    $service->short_description = $payload['short_description'];
    $service->description = $payload['description'];
    $service->min_bidding_price = $payload['min_bidding_price'];
    $service->is_active = 1;
    $service->cover_image = $uploadAsset($payload['cover_image'], $serviceDir, $service->cover_image);
    $service->thumbnail = $uploadAsset($payload['thumbnail'], $serviceDir, $service->thumbnail);
    $service->save();

    $upsertTranslation($serviceId, Service::class, 'name', $payload['name']);
    $upsertTranslation($serviceId, Service::class, 'short_description', $payload['short_description']);
    $upsertTranslation($serviceId, Service::class, 'description', $payload['description']);

    $remoteVariantIds = ServiceVariant::query()->where('service_id', $serviceId)->pluck('id');
    if ($remoteVariantIds->isNotEmpty()) {
        Translation::query()->whereIn('translationable_id', $remoteVariantIds->all())->delete();
    }
    Variation::query()->where('service_id', $serviceId)->delete();
    ServiceVariant::query()->where('service_id', $serviceId)->delete();

    $variationPricing = [];
    $sort = 0;

    foreach ($payload['variants'] as $variantSpec) {
        $key = $variantSpec['key'];
        $imageKey = $uploadAsset($variantSpec['image'], $serviceDir);

        $variant = ServiceVariant::query()->create([
            'service_id' => $serviceId,
            'variant_key' => $key,
            'title' => $variantSpec['title'],
            'description' => $variantSpec['description'],
            'image' => $imageKey,
            'sort_order' => $sort++,
            'is_active' => true,
        ]);

        $upsertTranslation($variant->id, ServiceVariant::class, 'title', $variantSpec['title']);
        $upsertTranslation($variant->id, ServiceVariant::class, 'description', $variantSpec['description']);

        $variationPricing[$key] = [
            'use_zone_pricing' => false,
            'default_price' => (float) $variantSpec['price'],
        ];

        foreach ($zones as $zone) {
            Variation::query()->create([
                'service_id' => $serviceId,
                'service_variant_id' => $variant->id,
                'variant_key' => $key,
                'variant' => $variantSpec['title'],
                'zone_id' => $zone->id,
                'price' => (float) $variantSpec['price'],
            ]);
        }
    }

    $service->variation_pricing = $variationPricing;
    $service->save();

    echo "Updated: {$payload['name']} (" . count($payload['variants']) . " bookable variants x {$zones->count()} zones, images uploaded)\n";
}

echo "Done.\n";
