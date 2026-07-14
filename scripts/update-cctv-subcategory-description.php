<?php

/**
 * Fix CCTV subcategory description (placeholder "cctv" -> proper catalog copy).
 *
 * Default (local) DB:
 *   php artisan tinker scripts/update-cctv-subcategory-description.php
 *
 * Live DB:
 *   LIVE_DB_PASSWORD='...' php artisan tinker scripts/update-cctv-subcategory-description.php
 *
 * Remote dev DB:
 *   TARGET_DB_PASSWORD='...' TARGET_DB_DATABASE=u397782854_dev_pk_dec \
 *   TARGET_DB_USERNAME=u397782854_dev_pk_dec_usr php artisan tinker scripts/update-cctv-subcategory-description.php
 */

use Illuminate\Support\Facades\DB;
use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\CategoryManagement\Entities\Category;

$subCategoryId = 'fa2cef00-a558-4d84-be56-95588ced8b38';
$subCategorySlug = 'cctv';

$description = 'Professional CCTV services including installation, repair, camera setup, and system maintenance. Panun Kaergar ensures reliable surveillance coverage, clear recording, and secure monitoring for your home, shop, or office. 📹🔧';

$connection = env('IMPORT_CONNECTION');
if ($connection === null || $connection === '') {
    if ((string) env('LIVE_DB_PASSWORD', '') !== '') {
        $connection = 'live_cctv_description';
        config(['database.connections.'.$connection => [
            'driver' => 'mysql',
            'host' => env('LIVE_DB_HOST', '82.25.121.201'),
            'port' => env('LIVE_DB_PORT', '3306'),
            'database' => env('LIVE_DB_DATABASE', 'u397782854_live_pk_dec'),
            'username' => env('LIVE_DB_USERNAME', 'u397782854_live_pk_usr'),
            'password' => env('LIVE_DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ]]);
    } elseif ((string) env('TARGET_DB_PASSWORD', '') !== '') {
        $connection = 'dev_cctv_description';
        config(['database.connections.'.$connection => [
            'driver' => 'mysql',
            'host' => env('TARGET_DB_HOST', '82.25.121.201'),
            'port' => env('TARGET_DB_PORT', '3306'),
            'database' => env('TARGET_DB_DATABASE', 'u397782854_dev_pk_dec'),
            'username' => env('TARGET_DB_USERNAME', 'u397782854_dev_pk_dec_usr'),
            'password' => env('TARGET_DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ]]);
    } else {
        $connection = (string) config('database.default');
    }
}

DB::connection($connection)->getPdo();

$subCategory = Category::on($connection)
    ->withoutGlobalScopes()
    ->where(function ($query) use ($subCategoryId, $subCategorySlug) {
        $query->where('id', $subCategoryId)->orWhere('slug', $subCategorySlug);
    })
    ->first();

if (! $subCategory) {
    throw new RuntimeException("CCTV subcategory not found on connection [{$connection}].");
}

$previous = strip_tags((string) $subCategory->getRawOriginal('description'));
$previous = preg_replace('/\s+/', ' ', trim($previous));

Category::on($connection)->withoutGlobalScopes()->where('id', $subCategory->id)->update([
    'description' => $description,
    'updated_at' => now(),
]);

Translation::on($connection)->updateOrCreate(
    [
        'translationable_type' => Category::class,
        'translationable_id' => $subCategory->id,
        'locale' => 'en',
        'key' => 'description',
    ],
    ['value' => $description]
);

echo "Updated CCTV subcategory on [{$connection}]\n";
echo "  id: {$subCategory->id}\n";
echo "  slug: {$subCategory->slug}\n";
echo '  before: '.($previous !== '' ? $previous : '(empty)')."\n";
echo "  after: {$description}\n";
