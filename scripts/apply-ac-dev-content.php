<?php

/**
 * One-off: seed AC service copy + variations on dev DB.
 * Run: DB_HOST=82.25.121.201 DB_DATABASE=u397782854_live_pk_dec DB_USERNAME=u397782854_live_pk_usr DB_PASSWORD='...' php artisan tinker scripts/apply-ac-dev-content.php
 */

use Illuminate\Support\Str;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ZoneManagement\Entities\Zone;
use Modules\BusinessSettingsModule\Entities\Translation;

$subCategoryId = '716233b9-7954-4262-a79e-8df58a6a3090'; // Air Conditioners

$subCategoryHtml = <<<'HTML'
<p>Complete AC care for homes and offices in Srinagar and nearby areas — installation, repair, servicing, and uninstallation by verified technicians with clear inclusions, exclusions, and step-by-step process on every service.</p>
<ul>
<li>Brand-agnostic support for split and window ACs</li>
<li>Transparent pricing with defined scope</li>
<li>Same-day slots where available</li>
</ul>
HTML;

DB::table('categories')->where('id', $subCategoryId)->update([
    'description' => $subCategoryHtml,
    'updated_at' => now(),
]);

$services = [
    '0affd967-975b-4fc2-94af-4b870bf0945a' => [
        'name' => 'AC Installation',
        'min_bidding_price' => 599,
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
            ['key' => 'split-ac-upto-1-5-ton', 'title' => 'Split AC (up to 1.5 Ton)', 'price' => 599, 'description' => 'Standard wall-mount split AC up to 1.5 ton. Includes bracket, piping up to 3 m, vacuum, and test run. Ideal for bedrooms and small living rooms.'],
            ['key' => 'split-ac-1-5-to-2-ton', 'title' => 'Split AC (1.5–2 Ton)', 'price' => 799, 'description' => 'Mid-capacity split installation with standard materials and electrical hook-up. Extra piping quoted per metre on site.'],
            ['key' => 'window-ac-install', 'title' => 'Window AC', 'price' => 499, 'description' => 'Window unit fitting, sealing, and secure installation with operational test.'],
            ['key' => 'extra-copper-piping', 'title' => 'Extra copper piping (per metre)', 'price' => 150, 'description' => 'Additional copper pipe and insulation beyond package allowance. Billed per metre after technician measurement.'],
        ],
    ],
    'e228f94a-9461-4b93-b5f7-6f1da920ddd0' => [
        'name' => 'AC Repair',
        'min_bidding_price' => 299,
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
<li>Repeat visit fee if customer declines quoted repair after diagnosis (may apply as visit charge)</li>
</ul>
<h3>Service process</h3>
<ol>
<li><strong>Report issue</strong> — Share symptoms (no cooling, leak, noise, error light, etc.).</li>
<li><strong>On-site diagnosis</strong> — Technician inspects unit and identifies probable cause.</li>
<li><strong>Quote approval</strong> — For parts/labour beyond basic visit, you approve before work.</li>
<li><strong>Repair</strong> — Fault corrected using suitable parts and standard repair method.</li>
<li><strong>Testing</strong> — Cooling/airflow check and leak re-check where relevant.</li>
<li><strong>Closure</strong> — Summary of work done and maintenance tips.</li>
</ol>
<p><strong>Tip:</strong> If AC is under warranty, keep invoice/serial details ready. Some faults may need brand-authorised parts.</p>
HTML,
        'variants' => [
            ['key' => 'general-inspection', 'title' => 'General inspection & minor fix', 'price' => 299, 'description' => 'Visit, diagnosis, and minor fixes (drain flush, contact cleaning, settings). Best for unclear or minor issues.'],
            ['key' => 'cooling-gas-repair', 'title' => 'Cooling / gas-related repair', 'price' => 499, 'description' => 'Low cooling diagnosis, leak check, and gas-related correction. Gas refill charged separately if required.'],
            ['key' => 'pcb-electrical-repair', 'title' => 'PCB / electrical repair', 'price' => 399, 'description' => 'Control board, capacitor, wiring, remote receiver, or sensor issues. Parts cost on actual basis.'],
            ['key' => 'fan-compressor-repair', 'title' => 'Fan motor / compressor issue', 'price' => 599, 'description' => 'Outdoor/indoor fan or compressor-related faults. Quote provided after diagnosis.'],
        ],
    ],
    '1151db87-80b4-4257-b4c2-bd40ddc00416' => [
        'name' => 'AC Servicing',
        'min_bidding_price' => 399,
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
<li>Chemical damage or corrosion treatment beyond standard clean</li>
<li>Service of non-functional AC without repair booking</li>
</ul>
<h3>Service process</h3>
<ol>
<li><strong>Pre-check</strong> — Technician notes AC type, age, and visible issues.</li>
<li><strong>Protection setup</strong> — Area covered to limit splash/dust during cleaning.</li>
<li><strong>Indoor service</strong> — Filters, coils, drain path cleaned as per package.</li>
<li><strong>Outdoor service</strong> — Condenser cleaned and airflow restored.</li>
<li><strong>Performance test</strong> — Cooling, noise, and drain flow checked.</li>
<li><strong>Recommendations</strong> — Advice on next service interval and any repair needs.</li>
</ol>
<p><strong>Recommended:</strong> Service every 3–6 months, or before peak summer, for best performance.</p>
HTML,
        'variants' => [
            ['key' => 'standard-split-service', 'title' => 'Standard service (Split)', 'price' => 399, 'description' => 'Filter, coil, drain, and basic checks for split AC. Best for routine maintenance.'],
            ['key' => 'deep-jet-wash', 'title' => 'Deep jet wash service', 'price' => 599, 'description' => 'Intensive indoor coil jet cleaning for heavy dirt and odour. Recommended if not serviced 12+ months.'],
            ['key' => 'window-ac-service', 'title' => 'Window AC service', 'price' => 349, 'description' => 'Full clean and check for window units including filter and coil access points.'],
            ['key' => 'anti-rust-coating', 'title' => 'Anti-rust / coil coating (add-on)', 'price' => 199, 'description' => 'Protective coating for humid conditions. Extends coil life.'],
        ],
    ],
    '07d83084-21d9-48ca-bce2-643a4cdd38dc' => [
        'name' => 'AC Uninstallation',
        'min_bidding_price' => 449,
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
<li>Packing guidance / basic wrap for transport (package dependent)</li>
<li>Work area cleanup after removal</li>
</ul>
<h3>What's excluded</h3>
<ul>
<li>Wall hole filling, painting, or plastering (civil work)</li>
<li>Transportation to new location</li>
<li>Re-installation at new address (book installation separately)</li>
<li>Disposal of old/scrap unit unless agreed</li>
<li>Repair of rusted mounts, seized screws, or broken brackets (extra labour)</li>
<li>Crane/ladder special access for high installations</li>
<li>Gas refill at new location (part of new installation scope)</li>
</ul>
<h3>Service process</h3>
<ol>
<li><strong>Confirm scope</strong> — Split/window, floor level, and whether bracket removal is needed.</li>
<li><strong>Safe shutdown</strong> — Power off and system prepared for removal.</li>
<li><strong>Disconnection</strong> — Lines, drain, and cables detached in proper sequence.</li>
<li><strong>Unit removal</strong> — Indoor/outdoor units brought down safely.</li>
<li><strong>Capping & packing</strong> — Lines capped; unit prepared for move/storage.</li>
<li><strong>Handover</strong> — Notes on wall holes, bracket marks, and re-install tips.</li>
</ol>
<p><strong>Note:</strong> For inter-city moves, choose packing add-on and handle transport with care to avoid coil damage.</p>
HTML,
        'variants' => [
            ['key' => 'split-ac-uninstall', 'title' => 'Split AC uninstall', 'price' => 449, 'description' => 'Full split system removal with gas recovery attempt and line capping.'],
            ['key' => 'window-ac-uninstall', 'title' => 'Window AC uninstall', 'price' => 349, 'description' => 'Window unit removal and frame area left accessible for cleaning/repaint.'],
            ['key' => 'uninstall-packing', 'title' => 'Uninstall + packing for transport', 'price' => 549, 'description' => 'Extra protective wrap and handling for shifting homes.'],
            ['key' => 'bracket-removal', 'title' => 'Bracket removal', 'price' => 199, 'description' => 'Wall bracket removal; patch/paint is customer civil work.'],
        ],
    ],
];

$zones = Zone::query()->where('is_active', 1)->get();
if ($zones->isEmpty()) {
    throw new RuntimeException('No active zones found');
}

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

foreach ($services as $serviceId => $payload) {
    $service = Service::withoutGlobalScopes()->find($serviceId);
    if (! $service) {
        echo "Missing service: {$serviceId}\n";
        continue;
    }

    $service->name = $payload['name'];
    $service->short_description = $payload['short_description'];
    $service->description = $payload['description'];
    $service->min_bidding_price = $payload['min_bidding_price'];
    $service->is_active = 1;
    $service->save();

    $upsertTranslation($serviceId, Service::class, 'name', $payload['name']);
    $upsertTranslation($serviceId, Service::class, 'short_description', $payload['short_description']);
    $upsertTranslation($serviceId, Service::class, 'description', $payload['description']);

    Variation::query()->where('service_id', $serviceId)->delete();
    ServiceVariant::query()->where('service_id', $serviceId)->delete();

    $variationPricing = [];
    $sort = 0;

    foreach ($payload['variants'] as $variantSpec) {
        $key = $variantSpec['key'];
        $variant = ServiceVariant::query()->create([
            'service_id' => $serviceId,
            'variant_key' => $key,
            'title' => $variantSpec['title'],
            'description' => $variantSpec['description'],
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

    echo "Updated: {$payload['name']} (" . count($payload['variants']) . " variants x {$zones->count()} zones)\n";
}

echo "Done.\n";
