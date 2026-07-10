<?php

/**
 * Seed all Carpentry Installation services on live DB (images, overview, description, FAQs).
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/seed-carpentry-installation-services-live.php
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

$liveConnection = 'live_service_content';
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

$commonProcessTail = [
    ['icon' => 'verified', 'title' => 'Carpenter assigned', 'description' => 'A verified Panun Kaergar carpenter confirms your visit and arrives with professional tools.'],
    ['icon' => 'location', 'title' => 'On-site visit', 'description' => 'Technician reaches your location on schedule and inspects the site before work begins.', 'image' => 'thumb'],
    ['icon' => 'sparkle', 'title' => 'Test & handover', 'description' => 'Final checks completed, work area cleaned, and basic care tips shared with you.', 'image' => 'cover'],
];

$services = [
    [
        'id' => '35c0ce60-f483-4f83-b73c-994b04c29769',
        'name' => 'Furniture Installation',
        'short_description' => 'Expert furniture assembly by verified carpenters at your home.',
        'intro' => 'Safe furniture setup with sturdy fittings and balanced alignment.',
        'description' => 'From beds and wardrobes to tables and cabinets, every piece is assembled with proper alignment, secure fittings and stable support — so your furniture is ready to use, wobble-free and built to last.',
        'duration' => '2–4 hrs',
        'lead_title' => 'Professional furniture assembly with stable fittings and neat finish',
        'card_highlights' => [
            ['icon' => 'tools', 'text' => 'Expert Assembly', 'color' => 'blue', 'sort_order' => 0],
            ['icon' => 'quality', 'text' => 'Sturdy Fittings', 'color' => 'green', 'sort_order' => 1],
            ['icon' => 'verified', 'text' => 'Verified Carpenters', 'color' => 'purple', 'sort_order' => 2],
        ],
        'process_steps' => array_merge([
            ['icon' => 'calendar', 'title' => 'Book your slot', 'description' => 'Share your address, furniture type and photos so the right carpenter and scope can be assigned.'],
            ['icon' => 'tools', 'title' => 'Parts check', 'description' => 'Technician verifies all panels, hardware and fittings before assembly begins.'],
            ['icon' => 'quality', 'title' => 'Assembly & fixing', 'description' => 'Furniture is assembled, aligned and secured to walls or floors where required.'],
        ], $commonProcessTail),
        'perfect_for' => [
            ['icon' => 'home', 'text' => 'New homes', 'sort_order' => 0],
            ['icon' => 'building', 'text' => 'Offices', 'sort_order' => 1],
            ['icon' => 'sparkle', 'text' => 'Renovation', 'sort_order' => 2],
            ['icon' => 'tools', 'text' => 'Beds & wardrobes', 'sort_order' => 3],
            ['icon' => 'shop', 'text' => 'Tables & desks', 'sort_order' => 4],
            ['icon' => 'wood', 'text' => 'Flat-pack furniture', 'sort_order' => 5],
            ['icon' => 'home', 'text' => 'Pre-purchased units', 'sort_order' => 6],
        ],
        'whats_included' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Furniture assembly', 'sort_order' => 1],
            ['icon' => 'quality', 'title' => 'Level alignment', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Hardware & hinge fixing', 'sort_order' => 3],
            ['icon' => 'tools', 'title' => 'Wall/floor anchoring', 'sort_order' => 4],
            ['icon' => 'door', 'title' => 'Drawer & door adjustment', 'sort_order' => 5],
            ['icon' => 'sparkle', 'title' => 'Stability testing', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
        'good_to_know' => [
            ['icon' => 'wood', 'title' => 'Please keep all furniture parts, screws and hardware ready before the visit', 'sort_order' => 0],
            ['icon' => 'home', 'title' => 'Clear access to the assembly area helps complete the job in one visit', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Complex or missing parts may need extra time or a follow-up visit', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Share brand, model and photos when booking for accurate scoping', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Wall mounting is included only where agreed in the booking scope', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before the slot for cancellation or rescheduling', 'sort_order' => 5],
        ],
        'whats_not_included' => [
            ['icon' => 'pricing', 'title' => 'Cost of the furniture unit or replacement hardware', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Custom carpentry, fabrication or modification from raw timber', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Repair of damaged, warped or incomplete flat-pack panels', 'sort_order' => 2],
            ['icon' => 'sparkle', 'title' => 'Polishing, painting, lamination or post-install finishing', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Old furniture dismantling and disposal unless agreed on site', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Electrical work for motorized or smart furniture', 'sort_order' => 5],
            ['icon' => 'home', 'title' => 'Civil work, tile drilling beyond standard fixing scope', 'sort_order' => 6],
            ['icon' => 'quality', 'title' => 'Manufacturer assembly warranty claims with the brand/dealer', 'sort_order' => 7],
        ],
        'ideal_chips' => ['New homes', 'Offices', 'Renovation', 'Beds & wardrobes', 'Tables & desks', 'Flat-pack furniture', 'Pre-purchased units'],
        'included_cards' => [
            ['On-site inspection', 'Furniture parts, hardware and assembly area assessed before work begins.'],
            ['Furniture assembly', 'Customer-supplied units assembled as per manufacturer or agreed scope.'],
            ['Level alignment', 'Panels squared and joints tightened for stable everyday use.'],
            ['Hardware & hinge fixing', 'Handles, hinges, channels and connectors fitted securely.'],
            ['Wall/floor anchoring', 'Anti-tip or wall fixing where applicable and agreed.'],
            ['Drawer & door adjustment', 'Smooth open/close movement on drawers, shutters and doors.'],
            ['Stability testing', 'Wobble check and basic functional test before handover.'],
            ['Work-area cleanup', 'Packaging and debris cleared after assembly is complete.'],
        ],
        'feature_cards' => [
            ['Verified carpenters', 'Experienced in home and office furniture assembly.'],
            ['Stable fittings', 'Proper anchoring and alignment for everyday use.'],
            ['Tested handover', 'Drawers, doors and stability checked before leaving.'],
        ],
        'good_to_know_text' => [
            'Please keep all <strong>furniture parts, screws and hardware</strong> ready before the visit.',
            'Clear access to the assembly area helps complete the job in one visit.',
            'Complex or missing parts may need extra time or a follow-up visit.',
            'Share brand, model and photos when booking for accurate scoping.',
            'Wall mounting is included only where agreed in the booking scope.',
            'Notify at least 2 hours before the slot for cancellation or rescheduling.',
        ],
        'exclusions_text' => [
            'Cost of the furniture unit or replacement hardware',
            'Custom carpentry, fabrication or modification from raw timber',
            'Repair of damaged, warped or incomplete flat-pack panels',
            'Polishing, painting, lamination or post-install finishing',
            'Old furniture dismantling and disposal unless agreed on site',
            'Electrical work for motorized or smart furniture',
            'Civil work or tile drilling beyond standard fixing scope',
            'Manufacturer assembly warranty claims with the brand/dealer',
        ],
        'why_choose' => [
            ['Verified carpenters', 'Skilled in beds, wardrobes, tables and modular furniture.'],
            ['Neat assembly', 'Aligned panels, secure joints and stable fittings.'],
            ['Transparent scope', 'Clear inclusions and exclusions before work starts.'],
            ['Reliable handover', 'Functional checks and cleanup before the technician leaves.'],
        ],
        'related' => ['Door Installation', 'Kitchen Cabinet', 'Wardrobe Installation', 'Wooden Panel', 'Roof Installation'],
        'overview_sub' => 'Furniture Installation by <strong>Panun Kaergar</strong> connects you with verified carpenters for expert assembly of home and office furniture.',
        'overview_body' => 'From flat-pack beds and wardrobes to tables, cabinets and shelves, every piece is assembled with proper alignment, secure fittings and stable support for long-lasting everyday use.',
        'overview_booking' => 'Book an at-home visit and your technician will verify parts, complete assembly as per scope, and hand over a ready-to-use setup.',
        'cta_title' => 'Ready to assemble your furniture?',
        'cta_sub' => 'Book a verified carpenter for safe, stable assembly at your home or office.',
        'faqs' => [
            ['Do I need to buy the furniture before booking?', 'Yes. This service covers assembly of customer-supplied furniture. Share brand, model and photos when booking so the right scope can be assigned.'],
            ['How long does furniture assembly usually take?', 'Most single-unit assemblies take about 2–4 hours depending on size, complexity and wall-fixing requirements. Multiple items may take longer.'],
            ['Do you assemble flat-pack and branded furniture?', 'Yes. Our carpenters assemble common flat-pack and branded furniture. Share product details when booking for accurate planning.'],
            ['Is wall mounting included?', 'Basic wall anchoring or anti-tip fixing is included only where agreed in the booking scope. Extra drilling in tiles or concrete may be quoted on site.'],
            ['What if parts are missing or damaged?', 'The technician will check parts before starting. Missing or damaged components may require replacement from the supplier before assembly can be completed.'],
            ['Will you test the furniture before leaving?', 'Yes. Stability, drawers, doors and basic movement are checked before handover.'],
        ],
    ],
    [
        'id' => 'b7463b8a-c2c0-4445-8a64-4548091578ce',
        'name' => 'Kitchen Cabinet Installation',
        'short_description' => 'Expert kitchen cabinet fitting by verified carpenters at home.',
        'intro' => 'Precise cabinet mounting with level alignment and secure fixing.',
        'description' => 'From wall and base cabinets to shutters, hinges and alignment, every unit is mounted with level accuracy and secure fixing — so your kitchen storage is neat, sturdy and ready for everyday use.',
        'duration' => '3–6 hrs',
        'lead_title' => 'Professional kitchen cabinet fitting for organized, durable storage',
        'card_highlights' => [
            ['icon' => 'tools', 'text' => 'Expert Fitting', 'color' => 'blue', 'sort_order' => 0],
            ['icon' => 'quality', 'text' => 'Level Alignment', 'color' => 'green', 'sort_order' => 1],
            ['icon' => 'verified', 'text' => 'Verified Carpenters', 'color' => 'purple', 'sort_order' => 2],
        ],
        'process_steps' => array_merge([
            ['icon' => 'calendar', 'title' => 'Book your slot', 'description' => 'Share kitchen layout, cabinet type and photos so the visit can be planned correctly.'],
            ['icon' => 'tools', 'title' => 'Layout check', 'description' => 'Technician measures the space and confirms cabinet positions before fixing.'],
            ['icon' => 'quality', 'title' => 'Cabinet mounting', 'description' => 'Wall and base cabinets are aligned, fixed and adjusted for smooth use.'],
        ], $commonProcessTail),
        'perfect_for' => [
            ['icon' => 'home', 'text' => 'New kitchens', 'sort_order' => 0],
            ['icon' => 'sparkle', 'text' => 'Renovation', 'sort_order' => 1],
            ['icon' => 'building', 'text' => 'Modular kitchens', 'sort_order' => 2],
            ['icon' => 'tools', 'text' => 'Wall cabinets', 'sort_order' => 3],
            ['icon' => 'wood', 'text' => 'Base units', 'sort_order' => 4],
            ['icon' => 'shop', 'text' => 'Pantry storage', 'sort_order' => 5],
            ['icon' => 'home', 'text' => 'Pre-purchased sets', 'sort_order' => 6],
        ],
        'whats_included' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'quality', 'title' => 'Level marking & alignment', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Wall cabinet mounting', 'sort_order' => 2],
            ['icon' => 'wood', 'title' => 'Base cabinet fixing', 'sort_order' => 3],
            ['icon' => 'check', 'title' => 'Hinge & shutter adjustment', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Handle & hardware fitting', 'sort_order' => 5],
            ['icon' => 'sparkle', 'title' => 'Door alignment testing', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
        'good_to_know' => [
            ['icon' => 'wood', 'title' => 'Please ensure cabinets, hardware and countertop support plan are ready before the visit', 'sort_order' => 0],
            ['icon' => 'home', 'title' => 'Clear the kitchen work area for safe fixing and easier alignment', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Uneven walls or weak substrates may need extra shims or brackets', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Share kitchen dimensions and photos when booking for best results', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Plumbing, gas and electrical hook-ups are not part of standard scope', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before the slot for cancellation or rescheduling', 'sort_order' => 5],
        ],
        'whats_not_included' => [
            ['icon' => 'pricing', 'title' => 'Cost of cabinets, countertops, sinks or appliances', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Plumbing, gas line or electrical appliance connections', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Custom cabinet fabrication from raw material', 'sort_order' => 2],
            ['icon' => 'sparkle', 'title' => 'Polishing, lamination or post-install finishing work', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Old cabinet removal and disposal unless agreed on site', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Major wall rebuilding, tiling or civil corrections', 'sort_order' => 5],
            ['icon' => 'home', 'title' => 'Granite/marble countertop cutting and installation', 'sort_order' => 6],
            ['icon' => 'quality', 'title' => 'Waterproofing or chimney duct fabrication beyond basic scope', 'sort_order' => 7],
        ],
        'ideal_chips' => ['New kitchens', 'Renovation', 'Modular kitchens', 'Wall cabinets', 'Base units', 'Pantry storage', 'Pre-purchased sets'],
        'included_cards' => [
            ['On-site inspection', 'Kitchen layout, wall condition and cabinet positions reviewed first.'],
            ['Level marking & alignment', 'Spirit-level accuracy for straight cabinet rows.'],
            ['Wall cabinet mounting', 'Upper units fixed securely to suitable wall substrates.'],
            ['Base cabinet fixing', 'Lower units aligned and secured for stable use.'],
            ['Hinge & shutter adjustment', 'Doors adjusted for even gaps and smooth movement.'],
            ['Handle & hardware fitting', 'Handles, channels and basic hardware installed where applicable.'],
            ['Door alignment testing', 'Shutters checked for smooth open/close before handover.'],
            ['Work-area cleanup', 'Site tidied after installation is complete.'],
        ],
        'feature_cards' => [
            ['Verified carpenters', 'Experienced in modular and standard kitchen cabinet fitting.'],
            ['Level alignment', 'Straight rows and even gaps for a neat kitchen finish.'],
            ['Tested handover', 'Shutters, hinges and alignment checked before leaving.'],
        ],
        'good_to_know_text' => [
            'Please ensure <strong>cabinets, hardware and support plan</strong> are ready before the visit.',
            'Clear the kitchen work area for safe fixing and easier alignment.',
            'Uneven walls or weak substrates may need extra shims or brackets.',
            'Share kitchen dimensions and photos when booking for best results.',
            'Plumbing, gas and electrical hook-ups are not part of standard scope.',
            'Notify at least 2 hours before the slot for cancellation or rescheduling.',
        ],
        'exclusions_text' => [
            'Cost of cabinets, countertops, sinks or appliances',
            'Plumbing, gas line or electrical appliance connections',
            'Custom cabinet fabrication from raw material',
            'Polishing, lamination or post-install finishing work',
            'Old cabinet removal and disposal unless agreed on site',
            'Major wall rebuilding, tiling or civil corrections',
            'Granite/marble countertop cutting and installation',
            'Waterproofing or chimney duct fabrication beyond basic scope',
        ],
        'why_choose' => [
            ['Verified carpenters', 'Skilled in modular kitchen cabinet installation.'],
            ['Precise fitting', 'Level rows, secure fixing and neat shutter alignment.'],
            ['Transparent scope', 'Clear inclusions and exclusions before work starts.'],
            ['Organized finish', 'A cleaner, more functional kitchen storage setup.'],
        ],
        'related' => ['Door Installation', 'Furniture Installation', 'Wardrobe Installation', 'Wooden Panel', 'Roof Installation'],
        'overview_sub' => 'Kitchen Cabinet Installation by <strong>Panun Kaergar</strong> connects you with verified carpenters for organized, secure kitchen storage fitting.',
        'overview_body' => 'From wall and base cabinets to shutters, handles and alignment work, every unit is mounted with level accuracy and secure fixing for smooth everyday kitchen use.',
        'overview_booking' => 'Book an at-home visit and your technician will assess the kitchen, complete cabinet mounting as per scope, and hand over a ready-to-use setup.',
        'cta_title' => 'Ready to fit your kitchen cabinets?',
        'cta_sub' => 'Book a verified carpenter for precise kitchen cabinet installation at home.',
        'faqs' => [
            ['Do I need to buy cabinets before booking?', 'Yes. This service covers fitting of customer-supplied cabinets. Share layout photos and dimensions when booking.'],
            ['How long does kitchen cabinet installation take?', 'A typical kitchen may take 3–6 hours depending on the number of units, wall condition and hardware complexity.'],
            ['Are plumbing and electrical connections included?', 'No. Standard scope covers carpentry installation only. Plumbing, gas and electrical work require separate specialists.'],
            ['Can you install both wall and base cabinets?', 'Yes. Wall and base cabinet mounting and alignment are included as per the agreed booking scope.'],
            ['What if my kitchen wall is uneven?', 'Minor shimming and adjustment are included. Major wall correction or rebuilding will be explained and quoted before proceeding.'],
            ['Will shutters be aligned before handover?', 'Yes. Hinges, gaps and open/close movement are checked before the technician leaves.'],
        ],
    ],
    [
        'id' => '0dab2d0c-89e0-4f91-a084-4fdbc0a9fbe4',
        'name' => 'Wardrobe Installation',
        'short_description' => 'Expert wardrobe fitting by verified carpenters at your home.',
        'intro' => 'Secure wardrobe mounting with smooth doors and sturdy support.',
        'description' => 'From wall fixing and channel fitting to shutter alignment and drawer adjustment, every wardrobe is installed with secure support and smooth movement — so your bedroom storage works quietly and reliably from day one.',
        'duration' => '3–5 hrs',
        'lead_title' => 'Professional wardrobe fitting with secure mounting and smooth shutters',
        'card_highlights' => [
            ['icon' => 'tools', 'text' => 'Expert Fitting', 'color' => 'blue', 'sort_order' => 0],
            ['icon' => 'quality', 'text' => 'Secure Mounting', 'color' => 'green', 'sort_order' => 1],
            ['icon' => 'verified', 'text' => 'Verified Carpenters', 'color' => 'purple', 'sort_order' => 2],
        ],
        'process_steps' => array_merge([
            ['icon' => 'calendar', 'title' => 'Book your slot', 'description' => 'Share room dimensions, wardrobe type and photos for accurate visit planning.'],
            ['icon' => 'door', 'title' => 'Space check', 'description' => 'Technician checks wall condition, floor level and wardrobe position before fixing.'],
            ['icon' => 'quality', 'title' => 'Wardrobe mounting', 'description' => 'Unit is aligned, wall-fixed and adjusted for smooth shutter movement.'],
        ], $commonProcessTail),
        'perfect_for' => [
            ['icon' => 'home', 'text' => 'Bedrooms', 'sort_order' => 0],
            ['icon' => 'sparkle', 'text' => 'Renovation', 'sort_order' => 1],
            ['icon' => 'building', 'text' => 'Guest rooms', 'sort_order' => 2],
            ['icon' => 'door', 'text' => 'Sliding wardrobes', 'sort_order' => 3],
            ['icon' => 'wood', 'text' => 'Hinged wardrobes', 'sort_order' => 4],
            ['icon' => 'tools', 'text' => 'Modular units', 'sort_order' => 5],
            ['icon' => 'home', 'text' => 'Pre-purchased sets', 'sort_order' => 6],
        ],
        'whats_included' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'quality', 'title' => 'Level alignment', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Wardrobe wall fixing', 'sort_order' => 2],
            ['icon' => 'door', 'title' => 'Shutter hinge adjustment', 'sort_order' => 3],
            ['icon' => 'check', 'title' => 'Channel & hardware fitting', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Drawer alignment', 'sort_order' => 5],
            ['icon' => 'sparkle', 'title' => 'Movement testing', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
        'good_to_know' => [
            ['icon' => 'wood', 'title' => 'Please ensure the wardrobe unit and hardware are on site before the visit', 'sort_order' => 0],
            ['icon' => 'home', 'title' => 'Clear bedroom access and floor space for safe installation', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Weak walls may need extra brackets or alternate fixing methods', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Share wardrobe size and photos of the wall area when booking', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Internal lighting or electrical work is not included in standard scope', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before the slot for cancellation or rescheduling', 'sort_order' => 5],
        ],
        'whats_not_included' => [
            ['icon' => 'pricing', 'title' => 'Cost of the wardrobe unit, channels or hardware', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Custom wardrobe fabrication from raw timber or boards', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Repair of damaged, swollen or termite-affected panels', 'sort_order' => 2],
            ['icon' => 'sparkle', 'title' => 'Polishing, painting or post-install finishing', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Old wardrobe dismantling and disposal unless agreed on site', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Electrical wiring for lights, sensors or automation', 'sort_order' => 5],
            ['icon' => 'home', 'title' => 'Major wall rebuilding or civil corrections', 'sort_order' => 6],
            ['icon' => 'quality', 'title' => 'Mirror replacement or glass cutting beyond basic scope', 'sort_order' => 7],
        ],
        'ideal_chips' => ['Bedrooms', 'Renovation', 'Guest rooms', 'Sliding wardrobes', 'Hinged wardrobes', 'Modular units', 'Pre-purchased sets'],
        'included_cards' => [
            ['On-site inspection', 'Wall, floor and wardrobe position assessed before fixing.'],
            ['Level alignment', 'Unit squared and positioned for balanced shutter movement.'],
            ['Wardrobe wall fixing', 'Secure anchoring to suitable wall surfaces.'],
            ['Shutter hinge adjustment', 'Doors aligned for even gaps and smooth swing or slide.'],
            ['Channel & hardware fitting', 'Sliding channels, hinges and basic hardware installed.'],
            ['Drawer alignment', 'Drawers adjusted for smooth open/close where applicable.'],
            ['Movement testing', 'Shutters and drawers tested before handover.'],
            ['Work-area cleanup', 'Packaging and debris cleared after installation.'],
        ],
        'feature_cards' => [
            ['Verified carpenters', 'Experienced in bedroom wardrobe installation.'],
            ['Secure mounting', 'Stable wall fixing for everyday use.'],
            ['Smooth shutters', 'Aligned doors and drawers for quiet operation.'],
        ],
        'good_to_know_text' => [
            'Please ensure the <strong>wardrobe unit and hardware</strong> are on site before the visit.',
            'Clear bedroom access and floor space for safe installation.',
            'Weak walls may need extra brackets or alternate fixing methods.',
            'Share wardrobe size and photos of the wall area when booking.',
            'Internal lighting or electrical work is not included in standard scope.',
            'Notify at least 2 hours before the slot for cancellation or rescheduling.',
        ],
        'exclusions_text' => [
            'Cost of the wardrobe unit, channels or hardware',
            'Custom wardrobe fabrication from raw timber or boards',
            'Repair of damaged, swollen or termite-affected panels',
            'Polishing, painting or post-install finishing',
            'Old wardrobe dismantling and disposal unless agreed on site',
            'Electrical wiring for lights, sensors or automation',
            'Major wall rebuilding or civil corrections',
            'Mirror replacement or glass cutting beyond basic scope',
        ],
        'why_choose' => [
            ['Verified carpenters', 'Skilled in hinged and sliding wardrobe fitting.'],
            ['Secure installation', 'Stable fixing and neat alignment for daily use.'],
            ['Transparent scope', 'Clear inclusions and exclusions before work starts.'],
            ['Neat handover', 'Shutters, drawers and cleanup completed before leaving.'],
        ],
        'related' => ['Door Installation', 'Furniture Installation', 'Kitchen Cabinet', 'Wooden Panel', 'Roof Installation'],
        'overview_sub' => 'Wardrobe Installation by <strong>Panun Kaergar</strong> connects you with verified carpenters for secure bedroom storage fitting.',
        'overview_body' => 'From hinged and sliding wardrobes to drawers, channels and wall fixing, every unit is installed with level alignment and secure support for smooth everyday use.',
        'overview_booking' => 'Book an at-home visit and your technician will assess the space, complete wardrobe mounting as per scope, and hand over a ready-to-use unit.',
        'cta_title' => 'Ready to install your wardrobe?',
        'cta_sub' => 'Book a verified carpenter for secure wardrobe fitting at home.',
        'faqs' => [
            ['Should I buy the wardrobe before booking installation?', 'Yes. This service covers fitting of customer-supplied wardrobes. Share dimensions and photos when booking.'],
            ['How long does wardrobe installation take?', 'Most single wardrobes take about 3–5 hours depending on size, wall condition and hardware type.'],
            ['Do you install sliding and hinged wardrobes?', 'Yes. Both common sliding and hinged wardrobe types can be installed as per the agreed scope.'],
            ['Is internal lighting installation included?', 'No. Electrical lighting or automation work is excluded from standard carpentry installation scope.'],
            ['What if my wall is weak or uneven?', 'The technician will assess fixing options on site. Extra brackets or civil correction may be quoted before proceeding.'],
            ['Will shutters and drawers be tested before leaving?', 'Yes. Movement, alignment and basic function are checked before handover.'],
        ],
    ],
    [
        'id' => '169b86b1-347a-40fb-b5bf-a734974e956a',
        'name' => 'Wooden Panel Installation',
        'short_description' => 'Expert wooden panel fitting by verified carpenters at home.',
        'intro' => 'Neat panel mounting with level alignment and clean finishing.',
        'description' => 'From layout marking and panel cutting to adhesive fixing and joint neatening, every wall is finished with level alignment and secure mounting — so your interior panels look clean, straight and professionally fitted.',
        'duration' => '2–4 hrs',
        'lead_title' => 'Professional wooden panel fitting for neat walls and interiors',
        'card_highlights' => [
            ['icon' => 'tools', 'text' => 'Expert Fitting', 'color' => 'blue', 'sort_order' => 0],
            ['icon' => 'sparkle', 'text' => 'Clean Finish', 'color' => 'green', 'sort_order' => 1],
            ['icon' => 'verified', 'text' => 'Verified Carpenters', 'color' => 'purple', 'sort_order' => 2],
        ],
        'process_steps' => array_merge([
            ['icon' => 'calendar', 'title' => 'Book your slot', 'description' => 'Share wall dimensions, panel type and photos so the installation can be planned correctly.'],
            ['icon' => 'wood', 'title' => 'Surface check', 'description' => 'Technician inspects the wall surface and layout before panel fixing begins.'],
            ['icon' => 'quality', 'title' => 'Panel mounting', 'description' => 'Panels are cut to fit, aligned and fixed with neat joints and secure support.'],
        ], $commonProcessTail),
        'perfect_for' => [
            ['icon' => 'home', 'text' => 'Living rooms', 'sort_order' => 0],
            ['icon' => 'sparkle', 'text' => 'Renovation', 'sort_order' => 1],
            ['icon' => 'building', 'text' => 'Offices', 'sort_order' => 2],
            ['icon' => 'wood', 'text' => 'Feature walls', 'sort_order' => 3],
            ['icon' => 'door', 'text' => 'TV backdrops', 'sort_order' => 4],
            ['icon' => 'shop', 'text' => 'Retail interiors', 'sort_order' => 5],
            ['icon' => 'home', 'text' => 'Pre-purchased panels', 'sort_order' => 6],
        ],
        'whats_included' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'wood', 'title' => 'Panel layout marking', 'sort_order' => 1],
            ['icon' => 'quality', 'title' => 'Level alignment', 'sort_order' => 2],
            ['icon' => 'tools', 'title' => 'Panel cutting & fitting', 'sort_order' => 3],
            ['icon' => 'check', 'title' => 'Adhesive/screw fixing', 'sort_order' => 4],
            ['icon' => 'sparkle', 'title' => 'Joint finishing', 'sort_order' => 5],
            ['icon' => 'door', 'title' => 'Edge neatening', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
        'good_to_know' => [
            ['icon' => 'wood', 'title' => 'Please ensure panels and fixing materials are available before the visit', 'sort_order' => 0],
            ['icon' => 'home', 'title' => 'Walls should be reasonably dry and accessible for proper fixing', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Uneven or damp walls may need extra preparation or alternate fixing', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Share wall size, panel type and photos when booking', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Painting, polishing or lacquer work is not included unless agreed', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before the slot for cancellation or rescheduling', 'sort_order' => 5],
        ],
        'whats_not_included' => [
            ['icon' => 'pricing', 'title' => 'Cost of wooden panels, adhesives or decorative mouldings', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Major wall plastering, waterproofing or damp treatment', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Custom panel fabrication from raw timber on site', 'sort_order' => 2],
            ['icon' => 'sparkle', 'title' => 'Painting, polishing, veneer pressing or lacquer finishing', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Old panel removal and disposal unless agreed on site', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Electrical conduit chasing or hidden wiring work', 'sort_order' => 5],
            ['icon' => 'home', 'title' => 'False ceiling integration beyond basic panel scope', 'sort_order' => 6],
            ['icon' => 'quality', 'title' => 'Designer CNC carving or complex decorative carpentry', 'sort_order' => 7],
        ],
        'ideal_chips' => ['Living rooms', 'Renovation', 'Offices', 'Feature walls', 'TV backdrops', 'Retail interiors', 'Pre-purchased panels'],
        'included_cards' => [
            ['On-site inspection', 'Wall surface and panel layout reviewed before work begins.'],
            ['Panel layout marking', 'Positions marked for straight rows and neat joints.'],
            ['Level alignment', 'Panels fitted with level accuracy across the wall.'],
            ['Panel cutting & fitting', 'On-site trimming and fitting to suit the opening.'],
            ['Adhesive/screw fixing', 'Secure fixing using suitable method for the substrate.'],
            ['Joint finishing', 'Basic joint neatening for a cleaner visual finish.'],
            ['Edge neatening', 'Visible edges trimmed and aligned where applicable.'],
            ['Work-area cleanup', 'Dust and debris cleared after installation.'],
        ],
        'feature_cards' => [
            ['Verified carpenters', 'Experienced in interior wooden panel fitting.'],
            ['Neat alignment', 'Straight rows and clean joints for a polished look.'],
            ['Careful handover', 'Fixing quality and finish checked before leaving.'],
        ],
        'good_to_know_text' => [
            'Please ensure <strong>panels and fixing materials</strong> are available before the visit.',
            'Walls should be reasonably dry and accessible for proper fixing.',
            'Uneven or damp walls may need extra preparation or alternate fixing.',
            'Share wall size, panel type and photos when booking.',
            'Painting, polishing or lacquer work is not included unless agreed.',
            'Notify at least 2 hours before the slot for cancellation or rescheduling.',
        ],
        'exclusions_text' => [
            'Cost of wooden panels, adhesives or decorative mouldings',
            'Major wall plastering, waterproofing or damp treatment',
            'Custom panel fabrication from raw timber on site',
            'Painting, polishing, veneer pressing or lacquer finishing',
            'Old panel removal and disposal unless agreed on site',
            'Electrical conduit chasing or hidden wiring work',
            'False ceiling integration beyond basic panel scope',
            'Designer CNC carving or complex decorative carpentry',
        ],
        'why_choose' => [
            ['Verified carpenters', 'Skilled in wall and interior panel installation.'],
            ['Neat finish', 'Aligned panels with cleaner joints and edges.'],
            ['Transparent scope', 'Clear inclusions and exclusions before work starts.'],
            ['Reliable service', 'Professional fixing and cleanup at your location.'],
        ],
        'related' => ['Door Installation', 'Furniture Installation', 'Kitchen Cabinet', 'Wardrobe Installation', 'Roof Installation'],
        'overview_sub' => 'Wooden Panel Installation by <strong>Panun Kaergar</strong> connects you with verified carpenters for neat interior panel fitting.',
        'overview_body' => 'From feature walls and TV backdrops to office and retail interiors, panels are mounted with level alignment, secure fixing and a cleaner finished look.',
        'overview_booking' => 'Book an at-home visit and your technician will assess the wall, complete panel installation as per scope, and hand over a neat finished surface.',
        'cta_title' => 'Ready to install wooden panels?',
        'cta_sub' => 'Book a verified carpenter for neat panel fitting at your home or office.',
        'faqs' => [
            ['Do I need to buy the panels before booking?', 'Yes. This service covers installation of customer-supplied panels. Share dimensions and photos when booking.'],
            ['How long does wooden panel installation take?', 'Most feature walls take about 2–4 hours depending on area size, wall condition and cutting requirements.'],
            ['Can you install panels on uneven walls?', 'Minor adjustment is possible, but major wall preparation or plastering is excluded and may need separate work first.'],
            ['Is painting or polishing included?', 'No. Post-install painting, polishing or lacquer finishing is excluded from standard scope.'],
            ['Do you remove old panels?', 'Old panel removal is not included unless agreed as an add-on on site.'],
            ['Will the panels be aligned before handover?', 'Yes. Level alignment, fixing quality and basic joint neatening are checked before the technician leaves.'],
        ],
    ],
    [
        'id' => '9b5663a7-bc43-4b1c-882a-92d9dad58b3b',
        'name' => 'Roof Installation',
        'short_description' => 'Expert wooden roof fitting by verified carpenters at home.',
        'intro' => 'Strong roof structure fitting with secure joints and support.',
        'description' => 'From beam and rafter fitting to bracing and member alignment, every wooden roof structure is installed with secure joints and proper support — so your roof framework is stable, aligned and ready for the next stage.',
        'duration' => '4–8 hrs',
        'lead_title' => 'Professional wooden roof fitting with secure joints and sturdy support',
        'card_highlights' => [
            ['icon' => 'tools', 'text' => 'Expert Fitting', 'color' => 'blue', 'sort_order' => 0],
            ['icon' => 'quality', 'text' => 'Strong Joints', 'color' => 'green', 'sort_order' => 1],
            ['icon' => 'verified', 'text' => 'Verified Carpenters', 'color' => 'purple', 'sort_order' => 2],
        ],
        'process_steps' => array_merge([
            ['icon' => 'calendar', 'title' => 'Book your slot', 'description' => 'Share structure type, dimensions and site photos for proper planning and safety review.'],
            ['icon' => 'building', 'title' => 'Site assessment', 'description' => 'Technician inspects support points, access and material readiness before work starts.'],
            ['icon' => 'wood', 'title' => 'Roof structure fitting', 'description' => 'Wooden members are aligned, joined and secured as per the agreed installation scope.'],
        ], $commonProcessTail),
        'perfect_for' => [
            ['icon' => 'home', 'text' => 'Homes', 'sort_order' => 0],
            ['icon' => 'building', 'text' => 'Extensions', 'sort_order' => 1],
            ['icon' => 'shop', 'text' => 'Shops', 'sort_order' => 2],
            ['icon' => 'wood', 'text' => 'Pergolas', 'sort_order' => 3],
            ['icon' => 'tools', 'text' => 'Carports', 'sort_order' => 4],
            ['icon' => 'sparkle', 'text' => 'Renovation', 'sort_order' => 5],
            ['icon' => 'home', 'text' => 'Pre-cut timber sets', 'sort_order' => 6],
        ],
        'whats_included' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'building', 'title' => 'Structure layout check', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Beam & rafter fitting', 'sort_order' => 2],
            ['icon' => 'quality', 'title' => 'Level alignment', 'sort_order' => 3],
            ['icon' => 'check', 'title' => 'Joint fixing & bracing', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Member securing', 'sort_order' => 5],
            ['icon' => 'sparkle', 'title' => 'Stability check', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
        'good_to_know' => [
            ['icon' => 'wood', 'title' => 'Please ensure timber members and fixing hardware are on site before the visit', 'sort_order' => 0],
            ['icon' => 'home', 'title' => 'Safe access to the work area is required for installation', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Structural changes or weak supports may need extra materials or engineering review', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Share roof type, span and photos when booking for accurate scoping', 'sort_order' => 3],
            ['icon' => 'building', 'title' => 'Waterproofing, sheeting or tiling is not included in standard carpentry scope', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before the slot for cancellation or rescheduling', 'sort_order' => 5],
        ],
        'whats_not_included' => [
            ['icon' => 'pricing', 'title' => 'Cost of timber, fasteners, sheets or roofing materials', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Major civil, concrete or steel structural work', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Custom timber milling or fabrication from raw logs', 'sort_order' => 2],
            ['icon' => 'sparkle', 'title' => 'Waterproofing, insulation or roof sheet installation', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Old roof dismantling and debris disposal unless agreed on site', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Scaffolding, crane or special height-access equipment', 'sort_order' => 5],
            ['icon' => 'home', 'title' => 'Electrical, guttering or plumbing work', 'sort_order' => 6],
            ['icon' => 'quality', 'title' => 'Certified structural engineering approvals or drawings', 'sort_order' => 7],
        ],
        'ideal_chips' => ['Homes', 'Extensions', 'Shops', 'Pergolas', 'Carports', 'Renovation', 'Pre-cut timber sets'],
        'included_cards' => [
            ['On-site inspection', 'Support points, access and material readiness checked first.'],
            ['Structure layout check', 'Beam and rafter positions reviewed before fixing.'],
            ['Beam & rafter fitting', 'Wooden members installed as per agreed carpentry scope.'],
            ['Level alignment', 'Members aligned for proper slope and support.'],
            ['Joint fixing & bracing', 'Connections secured with suitable fasteners and bracing.'],
            ['Member securing', 'Timber members fixed to supports where applicable.'],
            ['Stability check', 'Basic rigidity and alignment checked before handover.'],
            ['Work-area cleanup', 'Offcuts and debris cleared from the work area.'],
        ],
        'feature_cards' => [
            ['Verified carpenters', 'Experienced in wooden roof structure installation.'],
            ['Strong joints', 'Secure fixing and bracing for better stability.'],
            ['Safe handover', 'Alignment and basic stability checked before leaving.'],
        ],
        'good_to_know_text' => [
            'Please ensure <strong>timber members and fixing hardware</strong> are on site before the visit.',
            'Safe access to the work area is required for installation.',
            'Structural changes or weak supports may need extra materials or engineering review.',
            'Share roof type, span and photos when booking for accurate scoping.',
            'Waterproofing, sheeting or tiling is not included in standard carpentry scope.',
            'Notify at least 2 hours before the slot for cancellation or rescheduling.',
        ],
        'exclusions_text' => [
            'Cost of timber, fasteners, sheets or roofing materials',
            'Major civil, concrete or steel structural work',
            'Custom timber milling or fabrication from raw logs',
            'Waterproofing, insulation or roof sheet installation',
            'Old roof dismantling and debris disposal unless agreed on site',
            'Scaffolding, crane or special height-access equipment',
            'Electrical, guttering or plumbing work',
            'Certified structural engineering approvals or drawings',
        ],
        'why_choose' => [
            ['Verified carpenters', 'Skilled in wooden roof member installation.'],
            ['Secure fitting', 'Proper joints, bracing and member alignment.'],
            ['Transparent scope', 'Clear inclusions and exclusions before work starts.'],
            ['Reliable execution', 'Professional installation with on-site assessment first.'],
        ],
        'related' => ['Door Installation', 'Furniture Installation', 'Kitchen Cabinet', 'Wardrobe Installation', 'Wooden Panel'],
        'overview_sub' => 'Roof Installation by <strong>Panun Kaergar</strong> connects you with verified carpenters for wooden roof structure fitting.',
        'overview_body' => 'From beams and rafters to bracing and member alignment, wooden roof structures are installed with secure joints and proper support for dependable performance.',
        'overview_booking' => 'Book an on-site visit and your technician will assess the structure, complete installation as per scope, and hand over a stable fitted framework.',
        'cta_title' => 'Ready to install your wooden roof structure?',
        'cta_sub' => 'Book a verified carpenter for secure roof structure fitting at your site.',
        'faqs' => [
            ['Do I need to arrange timber before booking?', 'Yes. This service covers installation of customer-supplied timber members and hardware within the agreed scope.'],
            ['How long does wooden roof installation take?', 'Timing varies from 4–8 hours or more depending on span, height access and complexity.'],
            ['Is waterproofing or roof sheeting included?', 'No. Waterproofing, insulation and sheet installation are excluded from standard carpentry scope.'],
            ['Can you work on extensions, pergolas and carports?', 'Yes, for common wooden roof structures within the agreed booking scope. Share photos and dimensions when booking.'],
            ['What if extra structural support is needed?', 'The technician will explain any additional materials, access needs or work before proceeding.'],
            ['Will the structure be checked before handover?', 'Yes. Alignment, joint fixing and basic stability are reviewed before the technician leaves.'],
        ],
    ],
];

foreach ($services as $cfg) {
    $service = Service::on($liveConnection)->withoutGlobalScopes()->find($cfg['id']);
    if (! $service) {
        throw new RuntimeException("Service not found: {$cfg['id']}");
    }

    $paths = $uploadServiceImages($service);
    $service = Service::on($liveConnection)->withoutGlobalScopes()->find($cfg['id']);
    $thumbUrl = $mediaBase.$paths['thumbnail'];
    $coverUrl = $mediaBase.$paths['cover'];

    $overview = $buildOverview($cfg, $thumbUrl, $coverUrl);
    $description = $cfg['description'];

    Service::on($liveConnection)->withoutGlobalScopes()->where('id', $cfg['id'])->update([
        'short_description' => $cfg['short_description'],
        'overview_content' => json_encode($overview),
        'description' => $description,
    ]);

    Translation::on($liveConnection)->updateOrCreate(
        ['translationable_type' => Service::class, 'translationable_id' => $cfg['id'], 'locale' => 'en', 'key' => 'short_description'],
        ['value' => $cfg['short_description']]
    );
    Translation::on($liveConnection)->updateOrCreate(
        ['translationable_type' => Service::class, 'translationable_id' => $cfg['id'], 'locale' => 'en', 'key' => 'description'],
        ['value' => $description]
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

echo "Done. Seeded ".count($services)." carpentry installation services on live.\n";
