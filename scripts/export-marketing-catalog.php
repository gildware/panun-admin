<?php

/**
 * Export active categories + subcategories for panun-marketing (live DB).
 *
 * Usage (from panun-admin):
 *   LIVE_DB_PASSWORD='...' php artisan tinker scripts/export-marketing-catalog.php [output-path]
 *
 * Optional env:
 *   LIVE_DB_HOST, LIVE_DB_PORT, LIVE_DB_DATABASE, LIVE_DB_USERNAME
 *   MARKETING_ZONE_ID — Srinagar leaf zone on live (auto-detected when possible)
 */

use App\Support\CloudStorageConfigurator;
use Modules\CategoryManagement\Entities\Category;
use Modules\ZoneManagement\Entities\Zone;

CloudStorageConfigurator::apply();

$outputPath = $argv[1] ?? dirname(__DIR__, 2).'/panun-marketing/src/data/catalog.json';

$liveConnection = 'marketing_catalog_export';
config(['database.connections.'.$liveConnection => [
    'driver' => 'mysql',
    'host' => env('LIVE_DB_HOST', '82.25.121.201'),
    'port' => env('LIVE_DB_PORT', '3306'),
    'database' => env('LIVE_DB_DATABASE', 'u397782854_live_pk_dec'),
    'username' => env('LIVE_DB_USERNAME', 'u397782854_live_pk_usr'),
    'password' => env('LIVE_DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
]]);

if ((string) config('database.connections.'.$liveConnection.'.password') === '') {
    throw new RuntimeException('Set LIVE_DB_PASSWORD for live database export.');
}

$zoneId = getenv('MARKETING_ZONE_ID') ?: null;
if (! $zoneId) {
    $zoneId = Zone::on($liveConnection)
        ->where('is_active', 1)
        ->where('name', 'like', '%Srinagar%')
        ->whereDoesntHave('childZones')
        ->value('id');
}

if ($zoneId) {
    config(['zone_id' => $zoneId]);
}

$parents = Category::on($liveConnection)
    ->ofStatus(1)
    ->ofType('main')
    ->ordered()
    ->get();

$catalog = [];

foreach ($parents as $parent) {
    $subcategories = Category::on($liveConnection)
        ->ofStatus(1)
        ->ofType('sub')
        ->withoutGlobalScopes(['zone_wise_data'])
        ->where('parent_id', $parent->id)
        ->ordered()
        ->get()
        ->map(static fn (Category $sub) => [
            'slug' => $sub->slug,
            'name' => $sub->name,
            'imageUrl' => $sub->image_full_path,
        ])
        ->values()
        ->all();

    $catalog[] = [
        'slug' => $parent->slug,
        'name' => $parent->name,
        'imageUrl' => $parent->image_full_path,
        'sortOrder' => (int) $parent->sort_order,
        'subcategories' => $subcategories,
    ];
}

$dir = dirname($outputPath);
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

file_put_contents(
    $outputPath,
    json_encode(
        [
            'exportedAt' => now()->toIso8601String(),
            'source' => 'live',
            'zoneId' => $zoneId,
            'categories' => $catalog,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    )
);

echo 'Exported '.count($catalog).' live categories to '.$outputPath.PHP_EOL;
