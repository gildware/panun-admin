<?php

/**
 * Export all active service images from live DB as JSON.
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/export-service-images-live.php
 */

use Illuminate\Support\Facades\DB;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;

$liveConnection = env('IMPORT_CONNECTION', 'live_missing_catalog');
$mediaBase = 'https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod/';

config(['database.connections.'.$liveConnection => [
    'driver' => 'mysql',
    'host' => env('IMPORT_DB_HOST', env('TARGET_DB_HOST', '82.25.121.201')),
    'port' => env('IMPORT_DB_PORT', env('TARGET_DB_PORT', '3306')),
    'database' => env('IMPORT_DB_DATABASE', env('TARGET_DB_DATABASE', 'u397782854_live_pk_dec')),
    'username' => env('IMPORT_DB_USERNAME', env('TARGET_DB_USERNAME', 'u397782854_live_pk_usr')),
    'password' => env('LIVE_DB_PASSWORD', env('IMPORT_DB_PASSWORD', env('TARGET_DB_PASSWORD', env('DB_PASSWORD', '')))),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
]]);

if ((string) config('database.connections.'.$liveConnection.'.password') === '') {
    throw new RuntimeException('Set LIVE_DB_PASSWORD or DB_PASSWORD.');
}

DB::connection($liveConnection)->getPdo();

$fullUrl = static function (?string $key) use ($mediaBase): ?string {
    if ($key === null || $key === '') {
        return null;
    }
    if (str_starts_with($key, 'http')) {
        return $key;
    }

    return $mediaBase.ltrim($key, '/');
};

$services = Service::on($liveConnection)->withoutGlobalScopes()
    ->where('is_active', 1)
    ->with(['category', 'subCategory'])
    ->orderBy('name')
    ->get();

$out = [];
foreach ($services as $service) {
    $variants = ServiceVariant::on($liveConnection)
        ->where('service_id', $service->id)
        ->where('is_active', 1)
        ->orderBy('sort_order')
        ->get();

    $overviewImages = [];
    $overview = json_decode((string) ($service->overview_content ?? ''), true);
    if (is_array($overview)) {
        $items = $overview['service_process']['items'] ?? [];
        foreach ($items as $item) {
            if (! empty($item['image']) && is_string($item['image'])) {
                $overviewImages[] = $item['image'];
            }
        }
    }

    $images = [];
    if ($thumb = $fullUrl($service->thumbnail)) {
        $images[] = ['type' => 'thumbnail', 'url' => $thumb, 'key' => $service->thumbnail];
    }
    if ($cover = $fullUrl($service->cover_image)) {
        $images[] = ['type' => 'cover', 'url' => $cover, 'key' => $service->cover_image];
    }
    foreach ($variants as $variant) {
        if ($vimg = $fullUrl($variant->image)) {
            $images[] = [
                'type' => 'variant',
                'variant_key' => $variant->variant_key,
                'variant_title' => $variant->title,
                'url' => $vimg,
                'key' => $variant->image,
            ];
        }
    }
    foreach (array_unique($overviewImages) as $idx => $url) {
        $images[] = ['type' => 'overview_process', 'url' => $url, 'key' => "overview-{$idx}"];
    }

    $out[] = [
        'id' => $service->id,
        'name' => $service->name,
        'slug' => $service->slug,
        'category' => $service->category?->name,
        'sub_category' => $service->subCategory?->name,
        'images' => $images,
    ];
}

$path = base_path('scripts/backups/service-images-export-'.date('Ymd-His').'.json');
file_put_contents($path, json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Exported ".count($out)." services to {$path}\n";
