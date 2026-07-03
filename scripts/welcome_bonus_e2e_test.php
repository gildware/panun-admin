<?php
/**
 * Welcome Bonus E2E Test
 * Run: php scripts/welcome_bonus_e2e_test.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;

const BASE = 'http://127.0.0.1:8000/api/v1';
const BONUS_AMOUNT = 50.0;

$passed = 0;
$failed = 0;

function ok(string $name): void
{
    global $passed;
    $passed++;
    echo "  ✓ PASS: {$name}\n";
}

function fail(string $name, string $detail = ''): void
{
    global $failed;
    $failed++;
    echo "  ✗ FAIL: {$name}" . ($detail ? " — {$detail}" : '') . "\n";
}

function api(string $method, string $path, array $body = []): array
{
    $url = BASE . $path;
    $response = Http::withHeaders([
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ])->{strtolower($method)}($url, $body);

    return [
        'status' => $response->status(),
        'body' => $response->json() ?? [],
        'raw' => $response->body(),
    ];
}

function setCustomerConfig(string $key, mixed $value): mixed
{
    $row = BusinessSettings::query()
        ->where('key_name', $key)
        ->where('settings_type', 'customer_config')
        ->first();

    $previous = $row?->live_values;

    BusinessSettings::updateOrCreate(
        ['key_name' => $key, 'settings_type' => 'customer_config'],
        [
            'key_name' => $key,
            'live_values' => $value,
            'test_values' => $value,
            'settings_type' => 'customer_config',
            'mode' => 'live',
            'is_active' => 1,
        ]
    );

    return $previous;
}

function uniquePhone(): string
{
    return '77777' . random_int(10000, 99999);
}

echo "\n=== Welcome Bonus E2E Test ===\n\n";

$originalSettings = [
    'customer_wallet' => setCustomerConfig('customer_wallet', 1),
    'customer_welcome_bonus' => setCustomerConfig('customer_welcome_bonus', 1),
    'customer_welcome_bonus_amount' => setCustomerConfig('customer_welcome_bonus_amount', BONUS_AMOUNT),
];

echo "Configured welcome bonus: enabled, amount=" . BONUS_AMOUNT . "\n\n";

// ── 1. Registration grants welcome bonus ─────────────────────────
echo "1. Registration flow\n";
$phone = uniquePhone();
$email = 'welcome_bonus_e2e_' . random_int(1000, 9999) . '@test.local';
$register = api('POST', '/customer/auth/registration', [
    'first_name' => 'Welcome',
    'last_name' => 'BonusTest',
    'phone' => $phone,
    'email' => $email,
    'password' => 'Test@12345',
    'confirm_password' => 'Test@12345',
    'gender' => 'male',
]);

if ($register['status'] === 200) {
    ok('Customer registration returns 200');
} else {
    fail('Customer registration', 'status=' . $register['status'] . ' body=' . substr($register['raw'], 0, 300));
}

$user = User::findByContactPhoneScoped($phone, CUSTOMER_USER_TYPES);
if ($user) {
    ok('Registered customer exists in database');
} else {
    fail('Registered customer lookup');
    $user = null;
}

if ($user) {
    $user->refresh();
    if (abs((float) $user->wallet_balance - BONUS_AMOUNT) < 0.01) {
        ok('Wallet credited with configured welcome bonus amount');
    } else {
        fail('Wallet balance after registration', "expected={BONUS_AMOUNT}, got={$user->wallet_balance}");
    }

    $welcomeTx = Transaction::query()
        ->where('to_user_id', $user->id)
        ->where('trx_type', TRX_TYPE['welcome_bonus'])
        ->first();

    if ($welcomeTx) {
        ok('Welcome bonus transaction recorded');
        if (abs((float) $welcomeTx->credit - BONUS_AMOUNT) < 0.01) {
            ok('Transaction credit amount matches bonus');
        } else {
            fail('Transaction credit amount', "expected={BONUS_AMOUNT}, got={$welcomeTx->credit}");
        }
        if (abs((float) $welcomeTx->balance - BONUS_AMOUNT) < 0.01) {
            ok('Transaction balance reflects wallet after credit');
        } else {
            fail('Transaction balance', "expected={BONUS_AMOUNT}, got={$welcomeTx->balance}");
        }
    } else {
        fail('Welcome bonus transaction missing');
    }

    $beforeBalance = (float) $user->wallet_balance;
    grant_customer_welcome_bonus($user);
    $user->refresh();
    $txCount = Transaction::query()
        ->where('to_user_id', $user->id)
        ->where('trx_type', TRX_TYPE['welcome_bonus'])
        ->count();

    if (abs((float) $user->wallet_balance - $beforeBalance) < 0.01 && $txCount === 1) {
        ok('Idempotency: second grant does not double-credit');
    } else {
        fail('Idempotency check', "balance={$user->wallet_balance}, txCount={$txCount}");
    }
}

// ── 2. Disabled bonus does not grant on registration ──────────────
echo "\n2. Disabled welcome bonus\n";
setCustomerConfig('customer_welcome_bonus', 0);
$phoneDisabled = uniquePhone();
$registerDisabled = api('POST', '/customer/auth/registration', [
    'first_name' => 'No',
    'last_name' => 'Bonus',
    'phone' => $phoneDisabled,
    'password' => 'Test@12345',
    'confirm_password' => 'Test@12345',
    'gender' => 'male',
]);

if ($registerDisabled['status'] === 200) {
    ok('Registration succeeds when welcome bonus disabled');
} else {
    fail('Registration when bonus disabled', 'status=' . $registerDisabled['status']);
}

$disabledUser = User::findByContactPhoneScoped($phoneDisabled, CUSTOMER_USER_TYPES);
if ($disabledUser && (float) $disabledUser->wallet_balance === 0.0) {
    ok('No wallet credit when welcome bonus disabled');
} else {
    fail('Wallet should stay zero when bonus disabled', 'balance=' . ($disabledUser->wallet_balance ?? 'n/a'));
}

$disabledTxCount = $disabledUser
    ? Transaction::query()->where('to_user_id', $disabledUser->id)->where('trx_type', TRX_TYPE['welcome_bonus'])->count()
    : -1;
if ($disabledTxCount === 0) {
    ok('No welcome bonus transaction when feature disabled');
} else {
    fail('Unexpected welcome bonus transaction when disabled', "count={$disabledTxCount}");
}

// ── 3. Wallet disabled blocks bonus ──────────────────────────────
echo "\n3. Customer wallet disabled\n";
setCustomerConfig('customer_welcome_bonus', 1);
setCustomerConfig('customer_wallet', 0);
$phoneWalletOff = uniquePhone();
$registerWalletOff = api('POST', '/customer/auth/registration', [
    'first_name' => 'Wallet',
    'last_name' => 'Off',
    'phone' => $phoneWalletOff,
    'password' => 'Test@12345',
    'confirm_password' => 'Test@12345',
    'gender' => 'male',
]);

if ($registerWalletOff['status'] === 200) {
    ok('Registration succeeds when customer wallet disabled');
} else {
    fail('Registration when wallet disabled', 'status=' . $registerWalletOff['status']);
}

$walletOffUser = User::findByContactPhoneScoped($phoneWalletOff, CUSTOMER_USER_TYPES);
if ($walletOffUser && (float) $walletOffUser->wallet_balance === 0.0) {
    ok('No wallet credit when customer wallet disabled');
} else {
    fail('Wallet credit blocked when wallet feature off', 'balance=' . ($walletOffUser->wallet_balance ?? 'n/a'));
}

// ── 4. Report query includes granted bonus ───────────────────────
echo "\n4. Admin report data\n";
setCustomerConfig('customer_wallet', 1);
setCustomerConfig('customer_welcome_bonus', 1);

$reportCount = Transaction::query()
    ->where('trx_type', TRX_TYPE['welcome_bonus'])
    ->where('to_user_account', 'user_wallet')
    ->count();
$reportTotal = (float) Transaction::query()
    ->where('trx_type', TRX_TYPE['welcome_bonus'])
    ->where('to_user_account', 'user_wallet')
    ->sum('credit');

if ($reportCount >= 1) {
    ok('Report source query returns welcome bonus rows');
} else {
    fail('Report source query empty');
}

if ($user && Transaction::query()
    ->where('trx_type', TRX_TYPE['welcome_bonus'])
    ->where('to_user_id', $user->id)
    ->exists()) {
    ok('Test registration appears in welcome bonus report dataset');
} else {
    fail('Test user missing from report dataset');
}

echo "     report rows={$reportCount}, total credited=" . with_currency_symbol($reportTotal) . "\n";

// ── 5. Notification template exists ─────────────────────────────
echo "\n5. Notification setup\n";
$notification = BusinessSettings::query()
    ->where('key_name', 'welcome_bonus_wallet')
    ->where('settings_type', 'customer_notification')
    ->first();

if ($notification) {
    ok('welcome_bonus_wallet notification template exists');
    $values = is_array($notification->live_values) ? $notification->live_values : [];
    if (! empty($values['welcome_bonus_wallet_message'] ?? null)) {
        ok('Notification message configured');
    } else {
        fail('Notification message missing');
    }
} else {
    fail('welcome_bonus_wallet notification template missing (run app update/migration seed)');
}

if ($user && function_exists('send_customer_welcome_bonus_notification')) {
    try {
        send_customer_welcome_bonus_notification($user, BONUS_AMOUNT);
        ok('send_customer_welcome_bonus_notification executes without error');
    } catch (\Throwable $e) {
        fail('Notification sender threw exception', $e->getMessage());
    }
} else {
    fail('Notification sender unavailable');
}

// ── 6. Admin inbox notification ───────────────────────────────────
echo "\n6. Admin inbox notification\n";
if ($user && function_exists('admin_inbox_notify_welcome_bonus')) {
    $adminUser = User::whereIn('user_type', ADMIN_USER_TYPES)->where('is_active', 1)->first();
    if ($adminUser) {
        $adminNotif = \Modules\AdminModule\Entities\UserNotification::query()
            ->where('user_id', $adminUser->id)
            ->where('type', \Modules\AdminModule\Entities\UserNotification::TYPE_WELCOME_BONUS)
            ->where('reference_type', 'welcome_bonus')
            ->where('reference_id', $user->id)
            ->latest('created_at')
            ->first();

        if ($adminNotif) {
            ok('Admin inbox notification created for welcome bonus');
            if (str_contains((string) $adminNotif->body, (string) $user->phone)) {
                ok('Admin notification body includes customer phone');
            } else {
                fail('Admin notification body missing customer phone', (string) $adminNotif->body);
            }
        } else {
            fail('Admin inbox notification missing for welcome bonus grant');
        }
    } else {
        fail('No active admin user found for inbox notification test');
    }
} else {
    fail('admin_inbox_notify_welcome_bonus unavailable');
}

// ── Restore settings ─────────────────────────────────────────────
foreach ($originalSettings as $key => $value) {
    if ($value !== null) {
        setCustomerConfig($key, $value);
    }
}
echo "\nRestored original customer config values.\n";

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n\n";
exit($failed > 0 ? 1 : 0);
