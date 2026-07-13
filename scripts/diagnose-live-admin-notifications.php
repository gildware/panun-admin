<?php

/**
 * Diagnose and optionally seed admin inbox notifications on live DB.
 *
 * Read-only audit:
 *   LIVE_DB_PASSWORD='...' php artisan tinker scripts/diagnose-live-admin-notifications.php
 *
 * Seed inbox rows (no FCM):
 *   LIVE_DB_PASSWORD='...' SEED=1 php artisan tinker scripts/diagnose-live-admin-notifications.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$connection = 'live_admin_notifications_diag';
config(['database.connections.'.$connection => [
    'driver' => 'mysql',
    'host' => env('LIVE_DB_HOST', '82.25.121.201'),
    'port' => env('LIVE_DB_PORT', '3306'),
    'database' => env('LIVE_DB_DATABASE', 'u397782854_live_pk_dec'),
    'username' => env('LIVE_DB_USERNAME', 'u397782854_live_pk_usr'),
    'password' => env('LIVE_DB_PASSWORD', env('DB_PASSWORD', '')),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
]]);

if ((string) config('database.connections.'.$connection.'.password') === '') {
    throw new RuntimeException('Set LIVE_DB_PASSWORD for live database.');
}

$db = DB::connection($connection);
$db->getPdo();

$hasInboxTable = Schema::connection($connection)->hasTable('user_notifications');
$inboxCount = $hasInboxTable ? (int) $db->table('user_notifications')->count() : 0;
$pushCount = (int) $db->table('push_notifications')->count();
$bookingsToday = (int) $db->table('bookings')->whereDate('created_at', now()->toDateString())->count();

$bookingToggle = $db->table('business_settings')
    ->where('key_name', 'booking')
    ->where('settings_type', 'notification_settings')
    ->value('live_values');
$bookingToggle = $bookingToggle ? json_decode((string) $bookingToggle, true) : null;

echo "Live admin notification audit\n";
echo str_repeat('-', 40)."\n";
echo 'user_notifications table: '.($hasInboxTable ? 'yes' : 'NO')."\n";
echo "user_notifications rows: {$inboxCount}\n";
echo "push_notifications rows: {$pushCount}\n";
echo "bookings created today: {$bookingsToday}\n";
echo 'push_notification_booking: '.json_encode($bookingToggle['push_notification_booking'] ?? null)."\n";

$adminCount = (int) $db->table('users')
    ->whereIn('user_type', ['super-admin', 'admin-employee'])
    ->where('is_active', 1)
    ->count();
echo "active admins: {$adminCount}\n";

if ($inboxCount === 0 && $pushCount > 0) {
    echo "\nLikely cause: live app runtime is not dispatching admin inbox listeners.\n";
    echo "On the live server run:\n";
    echo "  php artisan optimize:clear\n";
    echo "  composer dump-autoload -o\n";
    echo "Then place a test booking and confirm user_notifications grows.\n";
}

if (filter_var(env('SEED', false), FILTER_VALIDATE_BOOLEAN)) {
    echo "\nRunning notifications:smoke-test against live DB...\n";
    config([
        'database.connections.mysql.host' => config('database.connections.'.$connection.'.host'),
        'database.connections.mysql.database' => config('database.connections.'.$connection.'.database'),
        'database.connections.mysql.username' => config('database.connections.'.$connection.'.username'),
        'database.connections.mysql.password' => config('database.connections.'.$connection.'.password'),
    ]);
    DB::purge('mysql');
    DB::reconnect('mysql');

    Artisan::call('notifications:smoke-test');
    echo trim(Artisan::output())."\n";
    echo 'user_notifications rows after seed: '
        .(int) DB::connection('mysql')->table('user_notifications')->count()."\n";
}
