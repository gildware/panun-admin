<?php

/**
 * Revert AC content changes on remote DB using local pk_mar_ts_l snapshot.
 * Run: php artisan tinker scripts/revert-ac-dev-content.php
 *
 * Requires TARGET_* env vars for remote DB (defaults to live_pk_dec).
 */

$serviceIds = [
    '0affd967-975b-4fc2-94af-4b870bf0945a',
    'e228f94a-9461-4b93-b5f7-6f1da920ddd0',
    '1151db87-80b4-4257-b4c2-bd40ddc00416',
    '07d83084-21d9-48ca-bce2-643a4cdd38dc',
];
$subCategoryId = '716233b9-7954-4262-a79e-8df58a6a3090';

config(['database.connections.revert_source' => [
    'driver' => 'mysql',
    'host' => env('SOURCE_DB_HOST', '127.0.0.1'),
    'port' => env('SOURCE_DB_PORT', '3306'),
    'database' => env('SOURCE_DB_DATABASE', 'pk_mar_ts_l'),
    'username' => env('SOURCE_DB_USERNAME', 'root'),
    'password' => env('SOURCE_DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
]]);

config(['database.connections.revert_target' => [
    'driver' => 'mysql',
    'host' => env('TARGET_DB_HOST', '82.25.121.201'),
    'port' => env('TARGET_DB_PORT', '3306'),
    'database' => env('TARGET_DB_DATABASE', 'u397782854_live_pk_dec'),
    'username' => env('TARGET_DB_USERNAME', 'u397782854_live_pk_usr'),
    'password' => env('TARGET_DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
]]);

$source = DB::connection('revert_source');
$target = DB::connection('revert_target');

$sub = $source->table('categories')->where('id', $subCategoryId)->first(['description', 'updated_at']);
if ($sub) {
    $target->table('categories')->where('id', $subCategoryId)->update([
        'description' => $sub->description,
        'updated_at' => $sub->updated_at,
    ]);
    echo "Restored subcategory description\n";
}

foreach ($serviceIds as $serviceId) {
    $row = $source->table('services')->where('id', $serviceId)->first();
    if (! $row) {
        echo "Missing local service: {$serviceId}\n";
        continue;
    }

    $target->table('services')->where('id', $serviceId)->update([
        'name' => $row->name,
        'short_description' => $row->short_description,
        'description' => $row->description,
        'min_bidding_price' => $row->min_bidding_price,
        'variation_pricing' => $row->variation_pricing,
        'updated_at' => $row->updated_at,
    ]);

    $remoteVariantIds = $target->table('service_variants')->where('service_id', $serviceId)->pluck('id');
    $translationIds = $remoteVariantIds->push($serviceId)->all();

    $target->table('translations')->whereIn('translationable_id', $translationIds)->delete();
    $target->table('variations')->where('service_id', $serviceId)->delete();
    $target->table('service_variants')->where('service_id', $serviceId)->delete();

    $localVariants = $source->table('service_variants')->where('service_id', $serviceId)->get();
    foreach ($localVariants as $variant) {
        $target->table('service_variants')->insert((array) $variant);
    }

    $localVariations = $source->table('variations')->where('service_id', $serviceId)->get();
    foreach ($localVariations as $variation) {
        $target->table('variations')->insert((array) $variation);
    }

    $localVariantIds = $localVariants->pluck('id')->all();
    $restoreTranslationIds = array_merge([$serviceId], $localVariantIds);
    $localTranslations = $source->table('translations')
        ->whereIn('translationable_id', $restoreTranslationIds)
        ->get();

    foreach ($localTranslations as $translation) {
        $target->table('translations')->insert((array) $translation);
    }

    echo "Restored: {$row->name} ({$localVariants->count()} variants, {$localVariations->count()} zone rows)\n";
}

echo "Revert complete.\n";
