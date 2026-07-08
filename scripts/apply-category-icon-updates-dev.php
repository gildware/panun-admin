<?php

/**
 * Apply category image SQL updates to remote dev DB.
 *
 * TARGET_DB_HOST=82.25.121.201 \
 * TARGET_DB_DATABASE=u397782854_dev_pk_dec \
 * TARGET_DB_USERNAME=u397782854_dev_pk_dec_usr \
 * TARGET_DB_PASSWORD='...' \
 * php artisan tinker scripts/apply-category-icon-updates-dev.php
 */

$sqlFile = base_path('scripts/category-icon-updates-dev.sql');
if (! is_file($sqlFile)) {
    throw new RuntimeException("Missing SQL file: {$sqlFile}");
}

config(['database.connections.dev_target' => [
    'driver' => 'mysql',
    'host' => env('TARGET_DB_HOST', '82.25.121.201'),
    'port' => env('TARGET_DB_PORT', '3306'),
    'database' => env('TARGET_DB_DATABASE', 'u397782854_dev_pk_dec'),
    'username' => env('TARGET_DB_USERNAME', 'u397782854_dev_pk_dec_usr'),
    'password' => env('TARGET_DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
]]);

$password = (string) config('database.connections.dev_target.password');
if ($password === '') {
    throw new RuntimeException('Set TARGET_DB_PASSWORD before running.');
}

$target = DB::connection('dev_target');
$lines = array_filter(array_map('trim', file($sqlFile) ?: []));
$applied = 0;

foreach ($lines as $line) {
    if (! str_starts_with(strtoupper($line), 'UPDATE ')) {
        continue;
    }
    $target->statement($line);
    $applied++;
}

echo "Applied {$applied} category image updates on dev DB.\n";
