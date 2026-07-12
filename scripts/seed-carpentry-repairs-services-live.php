<?php

/**
 * Seed all Carpentry Repairs services on live DB (images, overview, description, FAQs).
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/seed-carpentry-repairs-services-live.php
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

$liveConnection = 'live_repair_content';
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
    ['icon' => 'verified', 'title' => 'Carpenter assigned', 'description' => 'A verified Panun Kaergar carpenter confirms your visit and arrives with professional repair tools.'],
    ['icon' => 'location', 'title' => 'On-site visit', 'description' => 'Technician reaches your location on schedule and inspects the damage before work begins.', 'image' => 'thumb'],
    ['icon' => 'sparkle', 'title' => 'Test & handover', 'description' => 'Repair checks completed, work area cleaned, and basic care tips shared with you.', 'image' => 'cover'],
];

$services = [
    [
        'id' => '08d77c18-8b58-4643-800c-88e9c37a9dac',
        'name' => 'Door Repair',
        'short_description' => 'Professional door repair for homes and offices. Panun Kaergar fixes damaged doors, loose hinges, alignment issues, and broken fittings to restore smooth operation and durability.',
        'intro' => 'Reliable door repairs with smooth swing and secure latch.',
        'description' => 'From loose hinges and misaligned frames to damaged panels and faulty locks, wooden doors are repaired with proper alignment and secure fittings — so your door closes smoothly, latches reliably and lasts longer.',
        'card_highlights' => [
            ['icon' => 'tools', 'text' => 'Expert Repair', 'color' => 'blue', 'sort_order' => 0],
            ['icon' => 'quality', 'text' => 'Smooth Alignment', 'color' => 'green', 'sort_order' => 1],
            ['icon' => 'verified', 'text' => 'Verified Carpenters', 'color' => 'purple', 'sort_order' => 2],
        ],
        'process_steps' => array_merge([
            ['icon' => 'calendar', 'title' => 'Book your slot', 'description' => 'Share your address, door type and photos of the damage so the right carpenter and scope can be assigned.'],
            ['icon' => 'door', 'title' => 'Damage check', 'description' => 'Technician inspects hinges, frame alignment, panels and lock hardware before repair begins.'],
            ['icon' => 'quality', 'title' => 'Repair & alignment', 'description' => 'Loose fittings are secured, panels fixed and the door adjusted for smooth open/close movement.'],
        ], $commonProcessTail),
        'perfect_for' => [
            ['icon' => 'home', 'text' => 'Homes', 'sort_order' => 0],
            ['icon' => 'building', 'text' => 'Offices', 'sort_order' => 1],
            ['icon' => 'door', 'text' => 'Loose hinges', 'sort_order' => 2],
            ['icon' => 'tools', 'text' => 'Sticking doors', 'sort_order' => 3],
            ['icon' => 'wood', 'text' => 'Damaged panels', 'sort_order' => 4],
            ['icon' => 'check', 'text' => 'Broken locks', 'sort_order' => 5],
            ['icon' => 'door', 'text' => 'Main entrance', 'sort_order' => 6],
        ],
        'whats_included' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'door', 'title' => 'Hinge tightening or replacement', 'sort_order' => 1],
            ['icon' => 'quality', 'title' => 'Frame & door alignment', 'sort_order' => 2],
            ['icon' => 'wood', 'title' => 'Minor panel repair', 'sort_order' => 3],
            ['icon' => 'check', 'title' => 'Lock & latch adjustment', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Gap correction', 'sort_order' => 5],
            ['icon' => 'sparkle', 'title' => 'Movement testing', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
        'good_to_know' => [
            ['icon' => 'wood', 'title' => 'Please keep any spare hinges, locks or hardware ready before the visit', 'sort_order' => 0],
            ['icon' => 'home', 'title' => 'Clear access to the door and frame helps complete the repair in one visit', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Major damage or warped frames may need replacement parts quoted on site', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Share door type and photos when booking for accurate scoping', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Full door replacement is not part of standard repair scope', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before the slot for cancellation or rescheduling', 'sort_order' => 5],
        ],
        'whats_not_included' => [
            ['icon' => 'pricing', 'title' => 'Cost of a new door, frame or replacement hardware', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Custom door fabrication from raw timber', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Full door or frame replacement', 'sort_order' => 2],
            ['icon' => 'sparkle', 'title' => 'Painting, polishing or post-repair finishing', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Old door removal and disposal unless agreed on site', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Electrical or smart-lock wiring work', 'sort_order' => 5],
            ['icon' => 'home', 'title' => 'Major civil work or wall rebuilding', 'sort_order' => 6],
            ['icon' => 'quality', 'title' => 'Manufacturer warranty claims with the brand or dealer', 'sort_order' => 7],
        ],
        'faqs' => [
            ['Can you fix a door that will not close properly?', 'Yes. Common causes like loose hinges, misalignment and latch issues are repaired within the agreed booking scope.'],
            ['Do I need to buy replacement parts before booking?', 'If you already have hinges, locks or panels, keep them ready. Missing parts may need to be sourced before repair can be completed.'],
            ['How long does door repair usually take?', 'Most standard repairs take about 1–3 hours depending on damage, hardware condition and alignment work required.'],
            ['Is full door replacement included?', 'No. This service covers repair of existing doors. Full replacement is quoted separately if needed.'],
            ['Can you repair main entrance and interior doors?', 'Yes. Both interior and exterior wooden doors can be repaired as per the agreed scope.'],
            ['Will the door be tested before you leave?', 'Yes. Open, close and latch movement are checked before handover.'],
        ],
    ],
    [
        'id' => '93e8f154-8a6c-4feb-a516-894bf73ac2a4',
        'name' => 'Furniture Repair',
        'short_description' => 'Professional furniture repair for homes and offices. Panun Kaergar fixes damaged frames, loose joints, broken parts, and worn fittings to restore strength, stability, and usability.',
        'intro' => 'Durable furniture repairs that restore strength and everyday usability.',
        'description' => 'From loose joints and broken frames to damaged panels and worn fittings, furniture is repaired with secure joints and stable support — so beds, chairs, tables and cabinets are safe to use again.',
        'card_highlights' => [
            ['icon' => 'tools', 'text' => 'Expert Repair', 'color' => 'blue', 'sort_order' => 0],
            ['icon' => 'quality', 'text' => 'Stable Joints', 'color' => 'green', 'sort_order' => 1],
            ['icon' => 'verified', 'text' => 'Verified Carpenters', 'color' => 'purple', 'sort_order' => 2],
        ],
        'process_steps' => array_merge([
            ['icon' => 'calendar', 'title' => 'Book your slot', 'description' => 'Share furniture type, damage details and photos so the visit can be planned correctly.'],
            ['icon' => 'tools', 'title' => 'Damage assessment', 'description' => 'Technician checks joints, panels, hardware and structural stability before repair begins.'],
            ['icon' => 'quality', 'title' => 'Repair & reinforcement', 'description' => 'Loose joints are secured, broken parts fixed and fittings adjusted for stable use.'],
        ], $commonProcessTail),
        'perfect_for' => [
            ['icon' => 'home', 'text' => 'Homes', 'sort_order' => 0],
            ['icon' => 'building', 'text' => 'Offices', 'sort_order' => 1],
            ['icon' => 'tools', 'text' => 'Loose joints', 'sort_order' => 2],
            ['icon' => 'wood', 'text' => 'Broken frames', 'sort_order' => 3],
            ['icon' => 'shop', 'text' => 'Tables & chairs', 'sort_order' => 4],
            ['icon' => 'door', 'text' => 'Beds & cabinets', 'sort_order' => 5],
            ['icon' => 'sparkle', 'text' => 'Worn fittings', 'sort_order' => 6],
        ],
        'whats_included' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'quality', 'title' => 'Joint tightening & reinforcement', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Minor frame or panel repair', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Hardware replacement (customer-supplied)', 'sort_order' => 3],
            ['icon' => 'tools', 'title' => 'Drawer & hinge adjustment', 'sort_order' => 4],
            ['icon' => 'door', 'title' => 'Leg and support fixing', 'sort_order' => 5],
            ['icon' => 'sparkle', 'title' => 'Stability testing', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
        'good_to_know' => [
            ['icon' => 'wood', 'title' => 'Please keep replacement screws, hinges or fittings ready if you have them', 'sort_order' => 0],
            ['icon' => 'home', 'title' => 'Clear space around the furniture for safe repair access', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Severely damaged or termite-affected pieces may need part replacement', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Share furniture type and photos when booking for accurate scoping', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Upholstery, foam and fabric work are not included in standard scope', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before the slot for cancellation or rescheduling', 'sort_order' => 5],
        ],
        'whats_not_included' => [
            ['icon' => 'pricing', 'title' => 'Cost of new furniture or replacement parts', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Custom furniture fabrication from raw timber', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Major reconstruction of heavily damaged units', 'sort_order' => 2],
            ['icon' => 'sparkle', 'title' => 'Polishing, painting or lamination finishing', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Upholstery, foam or fabric replacement', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Electrical work for motorized furniture', 'sort_order' => 5],
            ['icon' => 'home', 'title' => 'Furniture disposal or haul-away', 'sort_order' => 6],
            ['icon' => 'quality', 'title' => 'Manufacturer warranty claims with the brand or dealer', 'sort_order' => 7],
        ],
        'faqs' => [
            ['What types of furniture can you repair?', 'Common items include beds, wardrobes, tables, chairs and cabinets within the agreed booking scope.'],
            ['Do you repair loose or wobbly furniture?', 'Yes. Joint tightening, reinforcement and hardware adjustment are included as per scope.'],
            ['How long does furniture repair take?', 'Most repairs take about 1–3 hours depending on damage, number of joints and hardware involved.'],
            ['Is polishing or painting included?', 'No. Standard scope covers structural repair and fitting work only.'],
            ['What if a part is completely broken?', 'The technician will assess on site. Replacement parts may need to be sourced before repair can be finished.'],
            ['Will stability be checked before handover?', 'Yes. Wobble, movement and basic function are tested before the technician leaves.'],
        ],
    ],
    [
        'id' => '61c0ba1e-bec8-446b-ac39-74e03e35ac67',
        'name' => 'Window Repair',
        'short_description' => 'Professional window repair for homes and offices. Panun Kaergar fixes damaged frames, loose fittings, alignment issues, and worn parts to restore strength and smooth functionality.',
        'intro' => 'Secure window repairs with smooth movement and stable frames.',
        'description' => 'From loose frames and stiff shutters to damaged panels and worn fittings, wooden windows are repaired with proper alignment and secure hardware — so they open smoothly, stay stable and perform reliably.',
        'card_highlights' => [
            ['icon' => 'tools', 'text' => 'Expert Repair', 'color' => 'blue', 'sort_order' => 0],
            ['icon' => 'quality', 'text' => 'Smooth Movement', 'color' => 'green', 'sort_order' => 1],
            ['icon' => 'verified', 'text' => 'Verified Carpenters', 'color' => 'purple', 'sort_order' => 2],
        ],
        'process_steps' => array_merge([
            ['icon' => 'calendar', 'title' => 'Book your slot', 'description' => 'Share window type, damage details and photos so the right carpenter can be assigned.'],
            ['icon' => 'door', 'title' => 'Frame check', 'description' => 'Technician inspects frame condition, hinges, channels and panel alignment before repair.'],
            ['icon' => 'quality', 'title' => 'Repair & adjustment', 'description' => 'Loose fittings are secured, panels fixed and shutters adjusted for smooth movement.'],
        ], $commonProcessTail),
        'perfect_for' => [
            ['icon' => 'home', 'text' => 'Homes', 'sort_order' => 0],
            ['icon' => 'building', 'text' => 'Offices', 'sort_order' => 1],
            ['icon' => 'door', 'text' => 'Stiff windows', 'sort_order' => 2],
            ['icon' => 'tools', 'text' => 'Loose frames', 'sort_order' => 3],
            ['icon' => 'wood', 'text' => 'Damaged panels', 'sort_order' => 4],
            ['icon' => 'check', 'text' => 'Worn fittings', 'sort_order' => 5],
            ['icon' => 'sparkle', 'text' => 'Old wooden windows', 'sort_order' => 6],
        ],
        'whats_included' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'door', 'title' => 'Frame tightening & alignment', 'sort_order' => 1],
            ['icon' => 'quality', 'title' => 'Shutter adjustment', 'sort_order' => 2],
            ['icon' => 'wood', 'title' => 'Minor panel repair', 'sort_order' => 3],
            ['icon' => 'check', 'title' => 'Hinge & hardware fixing', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Channel or latch adjustment', 'sort_order' => 5],
            ['icon' => 'sparkle', 'title' => 'Movement testing', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
        'good_to_know' => [
            ['icon' => 'wood', 'title' => 'Keep any spare hinges, latches or channels ready if available', 'sort_order' => 0],
            ['icon' => 'home', 'title' => 'Clear access to the window from inside and outside where possible', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Swollen or severely rotted frames may need part replacement', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Share window type and photos when booking for accurate scoping', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Glass cutting or replacement is not part of standard carpentry scope', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before the slot for cancellation or rescheduling', 'sort_order' => 5],
        ],
        'whats_not_included' => [
            ['icon' => 'pricing', 'title' => 'Cost of new windows, glass or replacement hardware', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Custom window fabrication from raw timber', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Full window or frame replacement', 'sort_order' => 2],
            ['icon' => 'sparkle', 'title' => 'Painting, polishing or post-repair finishing', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Glass cutting, glazing or mesh installation', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Major civil or masonry corrections', 'sort_order' => 5],
            ['icon' => 'home', 'title' => 'Old window removal and disposal unless agreed on site', 'sort_order' => 6],
            ['icon' => 'quality', 'title' => 'Manufacturer warranty claims with the brand or dealer', 'sort_order' => 7],
        ],
        'faqs' => [
            ['Can you fix windows that are hard to open or close?', 'Yes. Alignment, hinge and channel issues are commonly repaired within standard scope.'],
            ['Is glass replacement included?', 'No. Standard scope covers wooden frame and fitting repair. Glass work requires a separate specialist.'],
            ['How long does window repair take?', 'Most repairs take about 1–3 hours depending on frame condition and number of shutters involved.'],
            ['Can you repair sliding and hinged windows?', 'Yes. Both common wooden window types can be repaired as per the agreed booking scope.'],
            ['What if the frame is badly damaged?', 'The technician will assess on site and explain if replacement parts or a follow-up visit is needed.'],
            ['Will movement be tested before handover?', 'Yes. Open, close and latch movement are checked before the technician leaves.'],
        ],
    ],
    [
        'id' => '5c8844bc-9412-4c33-90af-7de372348690',
        'name' => 'Wardrobe Repair',
        'short_description' => 'Professional wardrobe repair for bedrooms and storage units. Panun Kaergar fixes damaged panels, loose hinges, sliding issues, and broken fittings to restore durability and smooth functionality.',
        'intro' => 'Smooth wardrobe repairs with secure panels and reliable shutters.',
        'description' => 'From loose hinges and misaligned shutters to damaged panels and faulty channels, wardrobes are repaired with secure fittings and smooth movement — so your bedroom storage works quietly and reliably again.',
        'card_highlights' => [
            ['icon' => 'tools', 'text' => 'Expert Repair', 'color' => 'blue', 'sort_order' => 0],
            ['icon' => 'quality', 'text' => 'Smooth Shutters', 'color' => 'green', 'sort_order' => 1],
            ['icon' => 'verified', 'text' => 'Verified Carpenters', 'color' => 'purple', 'sort_order' => 2],
        ],
        'process_steps' => array_merge([
            ['icon' => 'calendar', 'title' => 'Book your slot', 'description' => 'Share wardrobe type, damage details and photos for accurate visit planning.'],
            ['icon' => 'door', 'title' => 'Unit check', 'description' => 'Technician inspects panels, hinges, channels and wall fixing before repair begins.'],
            ['icon' => 'quality', 'title' => 'Repair & adjustment', 'description' => 'Loose fittings are secured, panels fixed and shutters aligned for smooth use.'],
        ], $commonProcessTail),
        'perfect_for' => [
            ['icon' => 'home', 'text' => 'Bedrooms', 'sort_order' => 0],
            ['icon' => 'sparkle', 'text' => 'Guest rooms', 'sort_order' => 1],
            ['icon' => 'door', 'text' => 'Sliding wardrobes', 'sort_order' => 2],
            ['icon' => 'wood', 'text' => 'Hinged wardrobes', 'sort_order' => 3],
            ['icon' => 'tools', 'text' => 'Loose hinges', 'sort_order' => 4],
            ['icon' => 'check', 'text' => 'Stuck shutters', 'sort_order' => 5],
            ['icon' => 'building', 'text' => 'Modular units', 'sort_order' => 6],
        ],
        'whats_included' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'door', 'title' => 'Shutter hinge adjustment', 'sort_order' => 1],
            ['icon' => 'quality', 'title' => 'Channel & hardware fixing', 'sort_order' => 2],
            ['icon' => 'wood', 'title' => 'Minor panel repair', 'sort_order' => 3],
            ['icon' => 'check', 'title' => 'Drawer alignment', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Wall fixing reinforcement', 'sort_order' => 5],
            ['icon' => 'sparkle', 'title' => 'Movement testing', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
        'good_to_know' => [
            ['icon' => 'wood', 'title' => 'Keep replacement channels, hinges or handles ready if you have them', 'sort_order' => 0],
            ['icon' => 'home', 'title' => 'Clear bedroom access and floor space for safe repair work', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Swollen or termite-affected panels may need replacement', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Share wardrobe size and photos when booking for best results', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Internal lighting or electrical work is not included', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before the slot for cancellation or rescheduling', 'sort_order' => 5],
        ],
        'whats_not_included' => [
            ['icon' => 'pricing', 'title' => 'Cost of new wardrobe units, channels or hardware', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Custom wardrobe fabrication from raw timber', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Major panel replacement or full unit rebuild', 'sort_order' => 2],
            ['icon' => 'sparkle', 'title' => 'Polishing, painting or post-repair finishing', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Mirror replacement or glass cutting', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Electrical wiring for lights or automation', 'sort_order' => 5],
            ['icon' => 'home', 'title' => 'Old wardrobe dismantling and disposal unless agreed on site', 'sort_order' => 6],
            ['icon' => 'quality', 'title' => 'Manufacturer warranty claims with the brand or dealer', 'sort_order' => 7],
        ],
        'faqs' => [
            ['Can you fix sliding wardrobe doors that stick?', 'Yes. Channel cleaning, alignment and hardware adjustment are commonly handled within scope.'],
            ['Do you repair hinged wardrobe shutters?', 'Yes. Hinge tightening, gap adjustment and panel fixing are included as per the agreed scope.'],
            ['How long does wardrobe repair take?', 'Most repairs take about 1–3 hours depending on damage, shutter type and hardware involved.'],
            ['Is mirror or glass replacement included?', 'No. Mirror and glass work are excluded from standard carpentry repair scope.'],
            ['Can weak wall fixing be reinforced?', 'Basic reinforcement is included where suitable. Major wall correction may need separate work.'],
            ['Will shutters be tested before handover?', 'Yes. Open, close and sliding movement are checked before the technician leaves.'],
        ],
    ],
    [
        'id' => '484fe2b2-39c8-491f-b14d-91a5a58f0f1e',
        'name' => 'Kitchen Cabinet Repair',
        'short_description' => 'Professional kitchen cabinet repair for homes. Panun Kaergar fixes damaged panels, loose hinges, broken doors, and faulty fittings to restore strength, functionality, and neat appearance.',
        'intro' => 'Neat kitchen cabinet repairs with secure doors and smooth shutters.',
        'description' => 'From loose hinges and misaligned shutters to damaged panels and worn handles, kitchen cabinets are repaired with secure fittings and proper alignment — so your kitchen storage looks neat and works smoothly again.',
        'card_highlights' => [
            ['icon' => 'tools', 'text' => 'Expert Repair', 'color' => 'blue', 'sort_order' => 0],
            ['icon' => 'quality', 'text' => 'Neat Alignment', 'color' => 'green', 'sort_order' => 1],
            ['icon' => 'verified', 'text' => 'Verified Carpenters', 'color' => 'purple', 'sort_order' => 2],
        ],
        'process_steps' => array_merge([
            ['icon' => 'calendar', 'title' => 'Book your slot', 'description' => 'Share cabinet type, damage details and kitchen photos for accurate visit planning.'],
            ['icon' => 'tools', 'title' => 'Cabinet check', 'description' => 'Technician inspects hinges, shutters, panels and wall fixing before repair begins.'],
            ['icon' => 'quality', 'title' => 'Repair & alignment', 'description' => 'Loose fittings are secured, panels fixed and shutters adjusted for even gaps.'],
        ], $commonProcessTail),
        'perfect_for' => [
            ['icon' => 'home', 'text' => 'Home kitchens', 'sort_order' => 0],
            ['icon' => 'sparkle', 'text' => 'Renovation', 'sort_order' => 1],
            ['icon' => 'building', 'text' => 'Modular kitchens', 'sort_order' => 2],
            ['icon' => 'tools', 'text' => 'Loose hinges', 'sort_order' => 3],
            ['icon' => 'wood', 'text' => 'Broken doors', 'sort_order' => 4],
            ['icon' => 'door', 'text' => 'Misaligned shutters', 'sort_order' => 5],
            ['icon' => 'check', 'text' => 'Worn handles', 'sort_order' => 6],
        ],
        'whats_included' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'door', 'title' => 'Hinge tightening or replacement', 'sort_order' => 1],
            ['icon' => 'quality', 'title' => 'Shutter alignment', 'sort_order' => 2],
            ['icon' => 'wood', 'title' => 'Minor panel repair', 'sort_order' => 3],
            ['icon' => 'check', 'title' => 'Handle & hardware fixing', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Cabinet mounting reinforcement', 'sort_order' => 5],
            ['icon' => 'sparkle', 'title' => 'Door movement testing', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
        'good_to_know' => [
            ['icon' => 'wood', 'title' => 'Keep replacement hinges, handles or shutters ready if you have them', 'sort_order' => 0],
            ['icon' => 'home', 'title' => 'Clear the kitchen work area for safe repair access', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Water-damaged or swollen panels may need replacement parts', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Share kitchen photos when booking for accurate scoping', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Plumbing, gas and electrical work are not included', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before the slot for cancellation or rescheduling', 'sort_order' => 5],
        ],
        'whats_not_included' => [
            ['icon' => 'pricing', 'title' => 'Cost of new cabinets, shutters or replacement hardware', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Custom cabinet fabrication from raw material', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Full cabinet replacement or major rebuild', 'sort_order' => 2],
            ['icon' => 'sparkle', 'title' => 'Polishing, lamination or post-repair finishing', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Countertop cutting or appliance connections', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Plumbing, gas line or electrical hook-ups', 'sort_order' => 5],
            ['icon' => 'home', 'title' => 'Old cabinet removal and disposal unless agreed on site', 'sort_order' => 6],
            ['icon' => 'quality', 'title' => 'Manufacturer warranty claims with the brand or dealer', 'sort_order' => 7],
        ],
        'faqs' => [
            ['Can you fix kitchen cabinet doors that do not close evenly?', 'Yes. Hinge adjustment and shutter alignment are commonly repaired within standard scope.'],
            ['Do you repair both wall and base cabinets?', 'Yes. Wall and base cabinet repair is included as per the agreed booking scope.'],
            ['How long does kitchen cabinet repair take?', 'Most repairs take about 1–3 hours depending on the number of units and hardware involved.'],
            ['Are plumbing or electrical connections included?', 'No. Standard scope covers carpentry repair only.'],
            ['What if a shutter or panel needs replacement?', 'The technician will assess on site. Replacement parts may need to be sourced before work can be completed.'],
            ['Will shutters be tested before handover?', 'Yes. Open, close and gap alignment are checked before the technician leaves.'],
        ],
    ],
    [
        'id' => 'd0537fdf-20d1-4eea-9d98-ecb403e39624',
        'name' => 'Wooden Panel Repair',
        'short_description' => 'Professional wooden panel repair for walls and interiors. Panun Kaergar fixes damaged panels, loose fittings, cracks, and alignment issues to restore strength, appearance, and durability.',
        'intro' => 'Neat panel repairs that restore wall finish and secure fixing.',
        'description' => 'From loose panels and visible cracks to misaligned sections and worn fittings, wooden wall panels are repaired with secure fixing and neat alignment — so your interior woodwork looks clean and stays stable.',
        'card_highlights' => [
            ['icon' => 'tools', 'text' => 'Expert Repair', 'color' => 'blue', 'sort_order' => 0],
            ['icon' => 'quality', 'text' => 'Neat Finish', 'color' => 'green', 'sort_order' => 1],
            ['icon' => 'verified', 'text' => 'Verified Carpenters', 'color' => 'purple', 'sort_order' => 2],
        ],
        'process_steps' => array_merge([
            ['icon' => 'calendar', 'title' => 'Book your slot', 'description' => 'Share wall area, panel type and photos of the damage for accurate visit planning.'],
            ['icon' => 'wood', 'title' => 'Panel check', 'description' => 'Technician inspects loose sections, cracks, substrate and alignment before repair begins.'],
            ['icon' => 'quality', 'title' => 'Repair & refixing', 'description' => 'Damaged sections are secured, panels realigned and fittings tightened for a neat finish.'],
        ], $commonProcessTail),
        'perfect_for' => [
            ['icon' => 'home', 'text' => 'Living rooms', 'sort_order' => 0],
            ['icon' => 'building', 'text' => 'Offices', 'sort_order' => 1],
            ['icon' => 'sparkle', 'text' => 'Renovation', 'sort_order' => 2],
            ['icon' => 'wood', 'text' => 'Loose panels', 'sort_order' => 3],
            ['icon' => 'tools', 'text' => 'Cracked sections', 'sort_order' => 4],
            ['icon' => 'check', 'text' => 'Misaligned panels', 'sort_order' => 5],
            ['icon' => 'door', 'text' => 'Interior woodwork', 'sort_order' => 6],
        ],
        'whats_included' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'wood', 'title' => 'Loose panel refixing', 'sort_order' => 1],
            ['icon' => 'quality', 'title' => 'Crack repair (minor)', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Section alignment', 'sort_order' => 3],
            ['icon' => 'tools', 'title' => 'Clip, bead or trim adjustment', 'sort_order' => 4],
            ['icon' => 'door', 'title' => 'Minor section replacement (customer-supplied)', 'sort_order' => 5],
            ['icon' => 'sparkle', 'title' => 'Finish check', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
        'good_to_know' => [
            ['icon' => 'wood', 'title' => 'Keep replacement panels or trim pieces ready if you already have them', 'sort_order' => 0],
            ['icon' => 'home', 'title' => 'Clear wall access and nearby furniture for safe repair work', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Moisture-damaged or warped panels may need replacement', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Share wall photos when booking for accurate scoping', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Painting, polishing or lamination are not included in standard scope', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before the slot for cancellation or rescheduling', 'sort_order' => 5],
        ],
        'whats_not_included' => [
            ['icon' => 'pricing', 'title' => 'Cost of new panels, beads or decorative materials', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Custom panel fabrication from raw boards', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Full wall panel replacement', 'sort_order' => 2],
            ['icon' => 'sparkle', 'title' => 'Painting, polishing or lamination finishing', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Major wall rebuilding or civil corrections', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Electrical conduit or hidden wiring work', 'sort_order' => 5],
            ['icon' => 'home', 'title' => 'Old panel removal and disposal unless agreed on site', 'sort_order' => 6],
            ['icon' => 'quality', 'title' => 'Designer finishing or specialty surface treatments', 'sort_order' => 7],
        ],
        'faqs' => [
            ['Can you fix loose or falling wall panels?', 'Yes. Refixing, alignment and minor section repair are commonly handled within standard scope.'],
            ['Do you repair cracks in wooden panels?', 'Minor crack repair and securing loose sections are included as per the agreed scope.'],
            ['How long does panel repair take?', 'Most repairs take about 1–3 hours depending on wall area and number of affected sections.'],
            ['Is painting or polishing included after repair?', 'No. Standard scope covers carpentry repair and refixing only.'],
            ['What if matching replacement panels are needed?', 'The technician will assess on site. Customer-supplied matching panels can be fitted where agreed.'],
            ['Will alignment be checked before handover?', 'Yes. Panel fit, fixing and basic finish are reviewed before the technician leaves.'],
        ],
    ],
    [
        'id' => 'f71d44a9-b8c4-47ca-a32b-8b5b7d1c2df5',
        'name' => 'Roof Repair',
        'short_description' => 'Professional roof repair for wooden roofs and structures. Panun Kaergar fixes damaged sections, loose supports, and weakened frames to restore strength, safety, and durability.',
        'intro' => 'Stronger wooden roof repairs with secure joints and stable support.',
        'description' => 'From loose rafters and damaged beams to weakened joints and worn supports, wooden roof structures are repaired with secure fixing and proper bracing — so your roof framework is safer and more dependable.',
        'card_highlights' => [
            ['icon' => 'tools', 'text' => 'Expert Repair', 'color' => 'blue', 'sort_order' => 0],
            ['icon' => 'quality', 'text' => 'Secure Joints', 'color' => 'green', 'sort_order' => 1],
            ['icon' => 'verified', 'text' => 'Verified Carpenters', 'color' => 'purple', 'sort_order' => 2],
        ],
        'process_steps' => array_merge([
            ['icon' => 'calendar', 'title' => 'Book your slot', 'description' => 'Share roof type, damage details and photos so the right carpenter and scope can be assigned.'],
            ['icon' => 'wood', 'title' => 'Structure check', 'description' => 'Technician inspects beams, rafters, joints and support points before repair begins.'],
            ['icon' => 'quality', 'title' => 'Repair & bracing', 'description' => 'Loose members are secured, damaged sections fixed and joints reinforced where needed.'],
        ], $commonProcessTail),
        'perfect_for' => [
            ['icon' => 'home', 'text' => 'Homes', 'sort_order' => 0],
            ['icon' => 'building', 'text' => 'Shops', 'sort_order' => 1],
            ['icon' => 'sparkle', 'text' => 'Extensions', 'sort_order' => 2],
            ['icon' => 'wood', 'text' => 'Loose rafters', 'sort_order' => 3],
            ['icon' => 'tools', 'text' => 'Damaged beams', 'sort_order' => 4],
            ['icon' => 'check', 'text' => 'Weak joints', 'sort_order' => 5],
            ['icon' => 'door', 'text' => 'Pergolas & carports', 'sort_order' => 6],
        ],
        'whats_included' => [
            ['icon' => 'tools', 'title' => 'On-site inspection', 'sort_order' => 0],
            ['icon' => 'wood', 'title' => 'Beam & rafter tightening', 'sort_order' => 1],
            ['icon' => 'quality', 'title' => 'Joint reinforcement', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Minor member repair', 'sort_order' => 3],
            ['icon' => 'tools', 'title' => 'Bracing adjustment', 'sort_order' => 4],
            ['icon' => 'door', 'title' => 'Support fixing', 'sort_order' => 5],
            ['icon' => 'sparkle', 'title' => 'Stability check', 'sort_order' => 6],
            ['icon' => 'sparkle', 'title' => 'Work-area cleanup', 'sort_order' => 7],
        ],
        'good_to_know' => [
            ['icon' => 'wood', 'title' => 'Please ensure safe access to the roof work area before the visit', 'sort_order' => 0],
            ['icon' => 'home', 'title' => 'Keep replacement timber or fasteners ready if you already have them', 'sort_order' => 1],
            ['icon' => 'tools', 'title' => 'Major structural damage may need engineering review or extra materials', 'sort_order' => 2],
            ['icon' => 'check', 'title' => 'Share roof type, span and photos when booking for accurate scoping', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Waterproofing, sheeting or tiling are not included in standard scope', 'sort_order' => 4],
            ['icon' => 'calendar', 'title' => 'Notify at least 2 hours before the slot for cancellation or rescheduling', 'sort_order' => 5],
        ],
        'whats_not_included' => [
            ['icon' => 'pricing', 'title' => 'Cost of timber, fasteners, sheets or roofing materials', 'sort_order' => 0],
            ['icon' => 'tools', 'title' => 'Major civil, concrete or steel structural work', 'sort_order' => 1],
            ['icon' => 'wood', 'title' => 'Full roof replacement or large-scale reconstruction', 'sort_order' => 2],
            ['icon' => 'sparkle', 'title' => 'Waterproofing, insulation or roof sheet installation', 'sort_order' => 3],
            ['icon' => 'door', 'title' => 'Scaffolding, crane or special height-access equipment', 'sort_order' => 4],
            ['icon' => 'tools', 'title' => 'Electrical, guttering or plumbing work', 'sort_order' => 5],
            ['icon' => 'home', 'title' => 'Certified structural engineering approvals or drawings', 'sort_order' => 6],
            ['icon' => 'quality', 'title' => 'Old roof dismantling and debris disposal unless agreed on site', 'sort_order' => 7],
        ],
        'faqs' => [
            ['Can you fix loose or damaged wooden roof members?', 'Yes. Tightening, minor member repair and joint reinforcement are included as per the agreed scope.'],
            ['How long does roof repair usually take?', 'Timing varies from 2–5 hours or more depending on access, span and extent of damage.'],
            ['Is waterproofing or roof sheeting included?', 'No. Waterproofing, insulation and sheet installation are excluded from standard carpentry scope.'],
            ['Can you work on pergolas and carports?', 'Yes, for common wooden roof structures within the agreed booking scope. Share photos when booking.'],
            ['What if major structural support is needed?', 'The technician will explain any additional materials, access needs or work before proceeding.'],
            ['Will stability be checked before handover?', 'Yes. Joint fixing and basic rigidity are reviewed before the technician leaves.'],
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

echo "Done. Seeded ".count($services)." carpentry repair services on live.\n";
