<?php

/**
 * Revert a missing-catalog import using its backup JSON.
 *
 * BACKUP_FILE=scripts/backups/missing-catalog-YYYYMMDD-HHMMSS.json LIVE_DB_PASSWORD='...' \
 *   php artisan tinker scripts/revert-missing-catalog-live.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\ServiceManagement\Entities\Faq;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\ServiceVariant;
use Modules\ServiceManagement\Entities\Variation;

$backupFile = env('BACKUP_FILE', '');
if ($backupFile === '' || ! is_file($backupFile)) {
  throw new RuntimeException('Set BACKUP_FILE to a valid backup JSON from import-missing-catalog-live.php');
}

$backup = json_decode((string) file_get_contents($backupFile), true, 512, JSON_THROW_ON_ERROR);
$services = $backup['services'] ?? [];
if ($services === []) {
    throw new RuntimeException('Backup contains no services to revert.');
}

$liveConnection = env('IMPORT_CONNECTION', 'live_missing_catalog_revert');
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
    throw new RuntimeException('Set LIVE_DB_PASSWORD for the target database.');
}

try {
    DB::connection($liveConnection)->getPdo();
} catch (Throwable $e) {
    throw new RuntimeException('Database connection failed: '.$e->getMessage());
}

$reverted = 0;

foreach ($services as $entry) {
    $serviceId = (string) ($entry['service_id'] ?? '');
    if ($serviceId === '') {
        continue;
    }

    $service = Service::on($liveConnection)->withoutGlobalScopes()->withTrashed()->find($serviceId);
    if (! $service) {
        echo "SKIP missing service id: {$serviceId}\n";
        continue;
    }

    DB::connection($liveConnection)->transaction(function () use ($liveConnection, $serviceId, $entry, &$reverted, $service) {
        $variantIds = ServiceVariant::on($liveConnection)->where('service_id', $serviceId)->pluck('id')->all();

        Translation::on($liveConnection)
            ->where(function ($q) use ($serviceId) {
                $q->where('translationable_type', Service::class)
                    ->where('translationable_id', $serviceId);
            })
            ->orWhere(function ($q) use ($variantIds) {
                if ($variantIds !== []) {
                    $q->where('translationable_type', ServiceVariant::class)
                        ->whereIn('translationable_id', $variantIds);
                }
            })
            ->delete();

        DB::connection($liveConnection)->table('storages')
            ->where(function ($q) use ($serviceId) {
                $q->where('model', Service::class)->where('model_id', $serviceId);
            })
            ->orWhere(function ($q) use ($variantIds) {
                if ($variantIds !== []) {
                    $q->where('model', ServiceVariant::class)->whereIn('model_id', $variantIds);
                }
            })
            ->delete();

        Variation::on($liveConnection)->where('service_id', $serviceId)->delete();
        ServiceVariant::on($liveConnection)->where('service_id', $serviceId)->delete();
        Faq::on($liveConnection)->where('service_id', $serviceId)->delete();

        $slug = $service->slug;
        Service::on($liveConnection)->withoutGlobalScopes()->where('id', $serviceId)->forceDelete();

        echo "REVERTED: {$slug} ({$serviceId})\n";
        $reverted++;
    });
}

$revertedPath = $backupFile.'.reverted';
file_put_contents($revertedPath, json_encode([
    'reverted_at' => now()->toIso8601String(),
    'backup_file' => $backupFile,
    'import_id' => $backup['import_id'] ?? null,
    'services_reverted' => $reverted,
], JSON_PRETTY_PRINT));

echo "\nRevert complete. services_reverted={$reverted}\n";
echo "Marker written: {$revertedPath}\n";
