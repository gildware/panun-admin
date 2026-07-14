<?php

/**
 * Diagnose admin inbox + mobile push notifications on live.
 *
 * Read-only audit (run on live server or via tinker with LIVE_DB_PASSWORD):
 *   php artisan tinker scripts/diagnose-live-notifications.php
 *
 * Seed admin inbox test rows (no FCM):
 *   SEED=1 php artisan tinker scripts/diagnose-live-notifications.php
 */

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$useLiveConnection = filled(env('LIVE_DB_PASSWORD')) || filled(env('LIVE_DB_HOST'));

if ($useLiveConnection) {
    $connection = 'live_notifications_diag';
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
        throw new RuntimeException('Set LIVE_DB_PASSWORD for remote live database audit.');
    }

    $db = DB::connection($connection);
} else {
    $connection = config('database.default');
    $db = DB::connection($connection);
}

$db->getPdo();

$hasInboxTable = Schema::connection($connection)->hasTable('user_notifications');
$hasDeliveryLogs = Schema::connection($connection)->hasTable('push_notification_delivery_logs');
$hasFcmDevices = Schema::connection($connection)->hasTable('user_fcm_devices');
$hasPushUsers = Schema::connection($connection)->hasTable('push_notification_users');

$inboxCount = $hasInboxTable ? (int) $db->table('user_notifications')->count() : 0;
$inboxToday = $hasInboxTable
    ? (int) $db->table('user_notifications')->whereDate('created_at', now()->toDateString())->count()
    : 0;
$pushCount = (int) $db->table('push_notifications')->count();
$pushToday = (int) $db->table('push_notifications')->whereDate('created_at', now()->toDateString())->count();
$bookingsToday = (int) $db->table('bookings')->whereDate('created_at', now()->toDateString())->count();

$bookingToggle = $db->table('business_settings')
    ->where('key_name', 'booking')
    ->where('settings_type', 'notification_settings')
    ->value('live_values');
$bookingToggle = $bookingToggle ? json_decode((string) $bookingToggle, true) : null;

$firebaseConfig = $db->table('business_settings')
    ->where('key_name', 'push_notification')
    ->where('settings_type', 'third_party')
    ->value('live_values');
$firebaseConfig = $firebaseConfig ? json_decode((string) $firebaseConfig, true) : null;
$hasFirebaseJson = filled(data_get($firebaseConfig, 'service_file_content'));

$fcmDeviceCount = $hasFcmDevices ? (int) $db->table('user_fcm_devices')->count() : 0;
$fcmDevicesToday = $hasFcmDevices
    ? (int) $db->table('user_fcm_devices')->whereDate('updated_at', now()->toDateString())->count()
    : 0;

$failedDeliveries24h = $hasDeliveryLogs
    ? (int) $db->table('push_notification_delivery_logs')
        ->where('status', 'failed')
        ->where('created_at', '>=', now()->subDay())
        ->count()
    : 0;

$recentDeliveryError = $hasDeliveryLogs
    ? $db->table('push_notification_delivery_logs')
        ->where('status', 'failed')
        ->latest('created_at')
        ->value('error_message')
    : null;

$adminCount = (int) $db->table('users')
    ->whereIn('user_type', ['super-admin', 'admin-employee'])
    ->where('is_active', 1)
    ->count();

echo "Live notification audit\n";
echo str_repeat('-', 48)."\n";
echo 'Connection: '.$connection."\n";
echo 'user_notifications table: '.($hasInboxTable ? 'yes' : 'MISSING — run php artisan migrate')."\n";
echo "user_notifications rows: {$inboxCount} (today: {$inboxToday})\n";
echo "push_notifications rows: {$pushCount} (today: {$pushToday})\n";
echo "bookings created today: {$bookingsToday}\n";
echo 'push_notification_booking: '.json_encode($bookingToggle['push_notification_booking'] ?? null)."\n";
echo 'Firebase service account configured: '.($hasFirebaseJson ? 'yes' : 'NO')."\n";
echo 'user_fcm_devices table: '.($hasFcmDevices ? 'yes' : 'MISSING — run php artisan migrate')."\n";
echo "registered FCM devices: {$fcmDeviceCount} (updated today: {$fcmDevicesToday})\n";
echo "active admins: {$adminCount}\n";

if ($hasDeliveryLogs) {
    echo "FCM delivery failures (24h): {$failedDeliveries24h}\n";
    if ($recentDeliveryError) {
        echo 'Latest FCM error: '.substr((string) $recentDeliveryError, 0, 160)."\n";
    }
}

echo "\nRuntime helper availability (this PHP process):\n";
foreach ([
    'admin_inbox_notify_all',
    'scenario_push_notification',
    'device_notification',
    'booking_push_notifications_enabled',
] as $helper) {
    echo '  '.$helper.': '.(function_exists($helper) ? 'loaded' : 'MISSING')."\n";
}

$issues = [];

if (! $hasInboxTable) {
    $issues[] = 'Admin inbox table missing — migrations not applied on live.';
}
if ($inboxCount === 0 && $pushCount > 0) {
    $issues[] = 'Mobile push rows exist but admin inbox is empty — admin helpers likely not dispatching (stale deploy/autoload).';
}
if ($inboxCount === 0 && $pushCount === 0 && $bookingsToday > 0) {
    $issues[] = 'Bookings today but zero notification rows — triggers disabled or code not deployed.';
}
if (! ($bookingToggle['push_notification_booking'] ?? false)) {
    $issues[] = 'push_notification_booking is OFF in admin → Notification settings.';
}
if (! $hasFirebaseJson) {
    $issues[] = 'Firebase service account JSON missing in Third Party settings — device push cannot send.';
}
if ($fcmDeviceCount === 0) {
    $issues[] = 'No FCM device tokens registered — users must log into apps with notifications allowed.';
}
if ($failedDeliveries24h > 0) {
    $issues[] = 'Recent FCM delivery failures — check Admin → Notification → Logs & Status.';
}

if ($issues !== []) {
    echo "\nLikely issues:\n";
    foreach ($issues as $index => $issue) {
        echo '  '.($index + 1).'. '.$issue."\n";
    }

    echo "\nOn the live server run:\n";
    echo "  composer install --no-dev --optimize-autoloader\n";
    echo "  php artisan migrate --force\n";
    echo "  php artisan optimize:clear\n";
    echo "  php artisan notifications:smoke-test\n";
} else {
    echo "\nDatabase and config look healthy. If UI still shows nothing, hard-refresh admin and re-login to mobile apps.\n";
}

if (filter_var(env('SEED', false), FILTER_VALIDATE_BOOLEAN)) {
    if ($useLiveConnection) {
        config([
            'database.connections.mysql.host' => config('database.connections.'.$connection.'.host'),
            'database.connections.mysql.database' => config('database.connections.'.$connection.'.database'),
            'database.connections.mysql.username' => config('database.connections.'.$connection.'.username'),
            'database.connections.mysql.password' => config('database.connections.'.$connection.'.password'),
        ]);
        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    echo "\nRunning notifications:smoke-test...\n";
    Artisan::call('notifications:smoke-test');
    echo trim(Artisan::output())."\n";

    $afterInbox = $hasInboxTable
        ? (int) DB::connection($useLiveConnection ? 'mysql' : $connection)->table('user_notifications')->count()
        : 0;
    echo "user_notifications rows after seed: {$afterInbox}\n";
}
