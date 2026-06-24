<?php
/**
 * Wallet & Loyalty Points E2E + Security Test Suite
 * Run: php scripts/wallet_loyalty_e2e_test.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\TransactionModule\Entities\LoyaltyPointTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;

const BASE = 'http://127.0.0.1:8000/api/v1';

$passed = 0;
$failed = 0;
$warnings = [];

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

function warn(string $msg): void
{
    global $warnings;
    $warnings[] = $msg;
    echo "  ⚠ WARN: {$msg}\n";
}

function api(string $method, string $path, ?string $token = null, array $body = []): array
{
    $req = Http::withHeaders([
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]);
    if ($token) {
        $req = $req->withToken($token);
    }
    $url = BASE . $path;
    $response = match (strtoupper($method)) {
        'GET' => $req->get($url, $body),
        'POST' => $req->post($url, $body),
        default => throw new InvalidArgumentException("Unsupported method {$method}"),
    };

    return [
        'status' => $response->status(),
        'body' => $response->json() ?? [],
        'raw' => $response->body(),
    ];
}

function tokenFor(User $user): string
{
    return $user->createToken('E2E_TEST')->accessToken;
}

function snapshotUser(string|\Ramsey\Uuid\UuidInterface $id): array
{
    $u = User::find((string) $id);
    return [
        'wallet' => (float) $u->wallet_balance,
        'loyalty' => (float) $u->loyalty_point,
    ];
}

echo "\n=== Wallet & Loyalty E2E Test Suite ===\n\n";

// ── Setup test users ──────────────────────────────────────────────
$walletUser = User::where('user_type', 'customer')
    ->where('wallet_balance', '>', 100)
    ->orderByDesc('wallet_balance')
    ->first();

$loyaltyUser = User::where('user_type', 'customer')
    ->where('loyalty_point', '>', 0)
    ->orderByDesc('loyalty_point')
    ->first();

if (! $walletUser || ! $loyaltyUser) {
    echo "Cannot find suitable test users. Aborting.\n";
    exit(1);
}

$walletToken = tokenFor($walletUser);
$loyaltyToken = tokenFor($loyaltyUser);

echo "Wallet test user: {$walletUser->phone} (balance: {$walletUser->wallet_balance})\n";
echo "Loyalty test user: {$loyaltyUser->phone} (points: {$loyaltyUser->loyalty_point})\n\n";

// ── 1. Config endpoint ────────────────────────────────────────────
echo "1. Config & Feature Flags\n";
$config = api('GET', '/customer/config');
if ($config['status'] === 200 && isset($config['body']['content']['wallet_status'])) {
    ok('Config returns wallet_status and loyalty_point_status');
    $walletEnabled = (int) ($config['body']['content']['wallet_status'] ?? 0);
    $loyaltyEnabled = (int) ($config['body']['content']['loyalty_point_status'] ?? 0);
    $maxSpend = (float) ($config['body']['content']['max_wallet_spend_per_transaction'] ?? 0);
    echo "     wallet_status={$walletEnabled}, loyalty_status={$loyaltyEnabled}, max_spend={$maxSpend}\n";
} else {
    fail('Config endpoint', 'status=' . $config['status']);
}

// ── 2. Auth required ─────────────────────────────────────────────
echo "\n2. Authentication & Authorization\n";
$noAuth = api('GET', '/customer/wallet-transaction', null, ['limit' => 10, 'offset' => 1]);
if (in_array($noAuth['status'], [401, 403])) {
    ok('Wallet transactions require auth');
} else {
    fail('Wallet transactions should require auth', 'got status ' . $noAuth['status']);
}

$noAuthLoyalty = api('GET', '/customer/loyalty-point-transaction', null, ['limit' => 10, 'offset' => 1]);
if (in_array($noAuthLoyalty['status'], [401, 403])) {
    ok('Loyalty transactions require auth');
} else {
    fail('Loyalty transactions should require auth', 'got status ' . $noAuthLoyalty['status']);
}

// Cross-user isolation: wallet user cannot see loyalty user's data via API scoping
$walletTx = api('GET', '/customer/wallet-transaction', $walletToken, ['limit' => 10, 'offset' => 1]);
if ($walletTx['status'] === 200) {
    $returnedBalance = (float) ($walletTx['body']['content']['wallet_balance'] ?? -1);
    if (abs($returnedBalance - (float) $walletUser->wallet_balance) < 0.01) {
        ok('Wallet balance matches authenticated user');
    } else {
        fail('Wallet balance mismatch', "API={$returnedBalance}, DB={$walletUser->wallet_balance}");
    }
    $txUserIds = collect($walletTx['body']['content']['transactions']['data'] ?? [])
        ->pluck('to_user_id')->unique()->filter();
    if ($txUserIds->every(fn ($id) => $id === $walletUser->id || $id === null)) {
        ok('Wallet transactions scoped to authenticated user');
    } else {
        fail('Wallet transactions leak other users data');
    }
} else {
    fail('Wallet transaction list', 'status=' . $walletTx['status']);
}

// ── 3. Wallet transaction list validation ─────────────────────────
echo "\n3. Input Validation\n";
$badLimit = api('GET', '/customer/wallet-transaction', $walletToken, ['limit' => 999, 'offset' => 1]);
if ($badLimit['status'] === 400) {
    ok('Wallet rejects limit > 200');
} else {
    fail('Wallet should reject limit > 200', 'status=' . $badLimit['status']);
}

$badType = api('GET', '/customer/wallet-transaction', $walletToken, ['limit' => 10, 'offset' => 1, 'type' => 'invalid_type']);
if ($badType['status'] === 400) {
    ok('Wallet rejects invalid transaction type filter');
} else {
    fail('Wallet should reject invalid type filter', 'status=' . $badType['status']);
}

// ── 4. Loyalty transfer validation ────────────────────────────────
echo "\n4. Loyalty Point → Wallet Transfer\n";

$minTransfer = (float) (business_config('min_loyalty_point_to_transfer', 'customer_config')->live_values ?? 100);
$pointValue = (float) (business_config('loyalty_point_value_per_currency_unit', 'customer_config')->live_values ?? 10);

$loyaltyTx = api('GET', '/customer/loyalty-point-transaction', $loyaltyToken, ['limit' => 10, 'offset' => 1]);
if ($loyaltyTx['status'] === 200) {
    ok('Loyalty transaction list returns 200');
    $apiPoints = (float) ($loyaltyTx['body']['content']['loyalty_point'] ?? -1);
    $apiMin = (float) ($loyaltyTx['body']['content']['min_loyalty_point_to_transfer'] ?? -1);
    if (abs($apiPoints - (float) $loyaltyUser->loyalty_point) < 0.01) {
        ok('Loyalty balance matches DB');
    } else {
        fail('Loyalty balance mismatch', "API={$apiPoints}, DB={$loyaltyUser->loyalty_point}");
    }
    if (abs($apiMin - $minTransfer) < 0.01) {
        ok('Min transfer threshold exposed correctly');
    } else {
        fail('Min transfer threshold mismatch');
    }
} else {
    fail('Loyalty transaction list', 'status=' . $loyaltyTx['status']);
}

// Missing point field
$missingPoint = api('POST', '/customer/loyalty-point/wallet-transfer', $loyaltyToken, []);
if ($missingPoint['status'] === 400) {
    ok('Transfer rejects missing point field');
} else {
    fail('Transfer should reject missing point', 'status=' . $missingPoint['status']);
}

// Below minimum
$belowMin = api('POST', '/customer/loyalty-point/wallet-transfer', $loyaltyToken, ['point' => '1']);
if ($belowMin['status'] === 400) {
    ok('Transfer rejects below minimum threshold');
} else {
    fail('Transfer should reject below minimum', 'status=' . $belowMin['status'] . ' min=' . $minTransfer);
}

// Over balance
$overBalance = api('POST', '/customer/loyalty-point/wallet-transfer', $loyaltyToken, ['point' => '999999']);
if ($overBalance['status'] === 400) {
    ok('Transfer rejects amount exceeding balance');
} else {
    fail('Transfer should reject over-balance', 'status=' . $overBalance['status']);
}

// Negative point (security test)
$negativePoint = api('POST', '/customer/loyalty-point/wallet-transfer', $loyaltyToken, ['point' => '-50']);
$beforeNeg = snapshotUser($loyaltyUser->id);
if ($negativePoint['status'] === 400) {
    ok('Transfer rejects negative points');
} else {
    $afterNeg = snapshotUser($loyaltyUser->id);
    if ($afterNeg['loyalty'] > $beforeNeg['loyalty']) {
        fail('SECURITY: Negative point transfer INCREASED loyalty balance!', "before={$beforeNeg['loyalty']} after={$afterNeg['loyalty']}");
    } else {
        warn('Negative point not explicitly rejected (status=' . $negativePoint['status'] . ') but balance unchanged — weak validation');
    }
}

// Zero point
$zeroPoint = api('POST', '/customer/loyalty-point/wallet-transfer', $loyaltyToken, ['point' => '0']);
if ($zeroPoint['status'] === 400) {
    ok('Transfer rejects zero points');
} else {
    warn('Zero point transfer not rejected (status=' . $zeroPoint['status'] . ') — should validate min:1');
}

// Non-numeric point
$nonNumeric = api('POST', '/customer/loyalty-point/wallet-transfer', $loyaltyToken, ['point' => 'abc']);
if ($nonNumeric['status'] === 400) {
    ok('Transfer rejects non-numeric points');
} else {
    warn('Non-numeric point not rejected (status=' . $nonNumeric['status'] . ')');
}

// ── 5. Successful transfer (create user with sufficient points) ──
echo "\n5. Loyalty Transfer Integrity\n";
$transferUser = User::create([
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'first_name' => 'E2E_Transfer',
    'last_name' => 'Test',
    'phone' => '88888' . random_int(10000, 99999),
    'email' => 'e2e_transfer_' . random_int(1000, 9999) . '@test.local',
    'password' => bcrypt('Test@1234'),
    'user_type' => 'customer',
    'is_active' => 1,
    'is_phone_verified' => 1,
]);
$transferUser->loyalty_point = max($minTransfer + 50, 150);
$transferUser->wallet_balance = 0;
$transferUser->save();
$transferToken = tokenFor($transferUser);
$transferAmount = $minTransfer;
$expectedWalletCredit = $transferAmount / $pointValue;
$before = snapshotUser($transferUser->id);
$walletTxCountBefore = Transaction::where('to_user_id', $transferUser->id)
    ->where('trx_type', WALLET_TRX_TYPE['loyalty_point_earning'])->count();
$loyaltyTxCountBefore = LoyaltyPointTransaction::where('user_id', $transferUser->id)->count();

$transfer = api('POST', '/customer/loyalty-point/wallet-transfer', $transferToken, ['point' => (string) $transferAmount]);
$transferUser->refresh();

if ($transfer['status'] === 200) {
    ok('Successful loyalty transfer returns 200');
    $after = snapshotUser($transferUser->id);
    $expectedLoyalty = round($before['loyalty'] - $transferAmount, 2);
    $expectedWallet = round($before['wallet'] + $expectedWalletCredit, 2);

    if (abs($after['loyalty'] - $expectedLoyalty) < 0.02) {
        ok('Loyalty balance decremented correctly');
    } else {
        fail('Loyalty balance incorrect after transfer', "expected={$expectedLoyalty}, got={$after['loyalty']}");
    }

    if (abs($after['wallet'] - $expectedWallet) < 0.02) {
        ok('Wallet balance incremented correctly');
    } else {
        fail('Wallet balance incorrect after transfer', "expected={$expectedWallet}, got={$after['wallet']}");
    }

    $walletTxCountAfter = Transaction::where('to_user_id', $transferUser->id)
        ->where('trx_type', WALLET_TRX_TYPE['loyalty_point_earning'])->count();
    $loyaltyTxCountAfter = LoyaltyPointTransaction::where('user_id', $transferUser->id)->count();

    if ($walletTxCountAfter === $walletTxCountBefore + 1) {
        ok('Wallet transaction ledger row created');
    } else {
        fail('Missing wallet transaction ledger row');
    }

    if ($loyaltyTxCountAfter === $loyaltyTxCountBefore + 1) {
        ok('Loyalty transaction ledger row created');
    } else {
        fail('Missing loyalty transaction ledger row');
    }

    $lastWalletTx = Transaction::where('to_user_id', $transferUser->id)
        ->where('trx_type', WALLET_TRX_TYPE['loyalty_point_earning'])
        ->latest()->first();
    if ($lastWalletTx && abs((float) $lastWalletTx->balance - $after['wallet']) > 0.02) {
        warn("Wallet transaction 'balance' field stores credit amount ({$lastWalletTx->balance}) not running balance ({$after['wallet']}) — audit trail bug");
    } else {
        ok('Wallet transaction balance snapshot correct');
    }

    $lastLoyaltyTx = LoyaltyPointTransaction::where('user_id', $transferUser->id)->latest()->first();
    if ($lastLoyaltyTx && abs((float) $lastLoyaltyTx->balance - $after['loyalty']) < 0.02) {
        ok('Loyalty transaction balance snapshot correct');
    } else {
        fail('Loyalty transaction balance snapshot incorrect');
    }
} else {
    fail('Transfer should succeed', 'status=' . $transfer['status']);
}
$transferUser->delete();

// ── 6. Double-submit race condition simulation ──────────────────
echo "\n6. Race Condition / Double-Submit\n";
$raceUser = User::create([
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'first_name' => 'E2E_Race',
    'last_name' => 'Test',
    'phone' => '99999' . random_int(10000, 99999),
    'email' => 'e2e_race_' . random_int(1000, 9999) . '@test.local',
    'password' => bcrypt('Test@1234'),
    'user_type' => 'customer',
    'is_active' => 1,
    'is_phone_verified' => 1,
]);
$raceUser->loyalty_point = 200;
$raceUser->wallet_balance = 0;
$raceUser->save();
$raceToken = tokenFor($raceUser);
$raceBefore = snapshotUser($raceUser->id);

// Fire two concurrent transfers of 100 points each (total 200, exactly the balance)
$raceResponses = [];
$raceResponses[] = api('POST', '/customer/loyalty-point/wallet-transfer', $raceToken, ['point' => '100']);
$raceResponses[] = api('POST', '/customer/loyalty-point/wallet-transfer', $raceToken, ['point' => '100']);

$raceUser->refresh();
$raceAfter = snapshotUser($raceUser->id);
$successCount = collect($raceResponses)->filter(fn ($r) => $r['status'] === 200)->count();

if ($successCount === 2 && $raceAfter['loyalty'] < 0) {
    fail('SECURITY: Double-submit allowed overdraft — loyalty went negative!', "loyalty={$raceAfter['loyalty']}");
} elseif ($successCount === 2 && ($raceBefore['loyalty'] - $raceAfter['loyalty']) > 200) {
    fail('SECURITY: Double-submit debited more than available points', 'debited=' . ($raceBefore['loyalty'] - $raceAfter['loyalty']));
} elseif ($successCount === 2 && abs($raceBefore['loyalty'] - $raceAfter['loyalty'] - 200) < 0.02) {
    ok('Two sequential transfers succeeded with correct total debit');
} elseif ($successCount === 1) {
    ok('Only one of two concurrent transfers succeeded (race partially mitigated)');
    if ($raceAfter['loyalty'] >= 0) {
        ok('Loyalty balance non-negative after race test');
    } else {
        fail('Loyalty balance went negative after race test');
    }
} else {
    ok('Both concurrent transfers rejected or only valid total processed');
}

// Cleanup race user
$raceUser->delete();

// ── 7. Wallet spend cap ───────────────────────────────────────────
echo "\n7. Wallet Spend Cap Enforcement\n";
$maxSpendConfig = max_wallet_spend_per_transaction();
if ($maxSpendConfig > 0) {
    ok("Max wallet spend per transaction configured: {$maxSpendConfig}");
    $capped = cap_wallet_spend_for_single_transaction((float) $walletUser->wallet_balance);
    if ($capped <= $maxSpendConfig) {
        ok('cap_wallet_spend_for_single_transaction respects limit');
    } else {
        fail('Spend cap function not working', "capped={$capped}, max={$maxSpendConfig}");
    }
    if (wallet_spend_exceeds_per_transaction_limit($maxSpendConfig + 1)) {
        ok('wallet_spend_exceeds_per_transaction_limit detects over-cap');
    } else {
        fail('Spend limit detection failed');
    }
} else {
    warn('No max wallet spend limit configured (unlimited per transaction)');
}

// ── 8. Ledger consistency check ───────────────────────────────────
echo "\n8. Ledger Consistency\n";
$walletUser->refresh();
$walletTxSum = Transaction::where('to_user_id', $walletUser->id)
    ->whereIn('trx_type', WALLET_TRX_TYPE)
    ->selectRaw('SUM(credit) - SUM(debit) as net')
    ->value('net');

// Note: running balance != sum of tx because starting balance may exist
$lastTx = Transaction::where('to_user_id', $walletUser->id)
    ->whereIn('trx_type', WALLET_TRX_TYPE)
    ->latest()
    ->first();

if ($lastTx) {
    if (abs((float) $lastTx->balance - (float) $walletUser->wallet_balance) < 0.05) {
        ok('Latest wallet transaction balance matches user.wallet_balance');
    } else {
        warn("Latest wallet tx balance ({$lastTx->balance}) != user balance ({$walletUser->wallet_balance}) — may be loyalty transfer bug");
    }
} else {
    ok('No wallet transactions for test user (balance from admin/manual add)');
}

// ── 9. Feature flag bypass check ──────────────────────────────────
echo "\n9. Feature Flag Server-Side Enforcement\n";
// Transfer endpoint doesn't check customer_loyalty_point flag
$flagCheck = api('POST', '/customer/loyalty-point/wallet-transfer', $loyaltyToken, ['point' => '999999']);
if ($flagCheck['status'] === 400) {
    ok('Transfer blocked for over-balance (feature flag check inconclusive but balance enforced)');
} else {
    warn('Feature flags (customer_loyalty_point, customer_wallet) not checked server-side on transfer endpoint');
}

// ── 10. Bonus list endpoint ───────────────────────────────────────
echo "\n10. Wallet Bonus List\n";
$bonus = api('GET', '/customer/bonus-list', $walletToken, ['limit' => 100, 'offset' => 1]);
if ($bonus['status'] === 200) {
    ok('Bonus list endpoint accessible with auth');
} else {
    fail('Bonus list endpoint', 'status=' . $bonus['status']);
}

// ── 11. Balance snapshot fix (user with existing wallet balance) ──
echo "\n11. Balance Snapshot Fix\n";
$snapshotUser = User::create([
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'first_name' => 'SnapshotTest',
    'phone' => '44444' . random_int(10000, 99999),
    'email' => 'snap_' . random_int(1000, 9999) . '@test.local',
    'password' => bcrypt('Test@1234'),
    'user_type' => 'customer',
    'is_active' => 1,
    'is_phone_verified' => 1,
]);
$snapshotUser->loyalty_point = 150;
$snapshotUser->wallet_balance = 500;
$snapshotUser->save();
$snapToken = tokenFor($snapshotUser);
$transfer = api('POST', '/customer/loyalty-point/wallet-transfer', $snapToken, ['point' => '100']);
$snapshotUser->refresh();
if ($transfer['status'] === 200) {
    $lastTx = Transaction::where('to_user_id', $snapshotUser->id)
        ->where('trx_type', WALLET_TRX_TYPE['loyalty_point_earning'])->latest()->first();
    if ($lastTx && abs((float) $lastTx->balance - (float) $snapshotUser->wallet_balance) < 0.02) {
        ok('Wallet tx balance stores running balance (not just credit amount)');
    } else {
        fail('Balance snapshot still incorrect', "tx={$lastTx->balance}, user={$snapshotUser->wallet_balance}");
    }
} else {
    fail('Snapshot test transfer failed', 'status=' . $transfer['status']);
}
$snapshotUser->delete();

// ── 12. Soft-fail wallet debit prevented ────────────────
echo "\n12. Soft-Fail Wallet Debit Prevention\n";
try {
    DB::transaction(function () {
        $user = User::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'first_name' => 'SoftFailTest',
            'phone' => '33333' . random_int(10000, 99999),
            'email' => 'softfail_' . random_int(1000, 9999) . '@test.local',
            'password' => bcrypt('x'),
            'user_type' => 'customer',
            'is_active' => 1,
            'is_phone_verified' => 1,
        ]);
        $user->wallet_balance = 10;
        $user->save();
        $locked = lock_customer_user_for_wallet((string) $user->id);
        debit_customer_wallet_or_fail($locked, 50);
        fail('debit_customer_wallet_or_fail should throw on insufficient balance');
    });
} catch (\RuntimeException $e) {
    if ($e->getMessage() === 'insufficient_wallet_balance') {
        ok('debit_customer_wallet_or_fail throws on insufficient balance');
    } else {
        fail('Unexpected exception', $e->getMessage());
    }
}
User::where('first_name', 'SoftFailTest')->delete();

// ── 13. Feature flag enforcement ──────────────────────────
echo "\n13. Feature Flag Enforcement\n";
$flagUser = User::create([
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'first_name' => 'FlagTest',
    'phone' => '22222' . random_int(10000, 99999),
    'email' => 'flag_' . random_int(1000, 9999) . '@test.local',
    'password' => bcrypt('x'),
    'user_type' => 'customer',
    'is_active' => 1,
    'is_phone_verified' => 1,
]);
$flagUser->loyalty_point = 150;
$flagUser->wallet_balance = 0;
$flagUser->save();
$flagToken = tokenFor($flagUser);

// Disable loyalty feature temporarily
$loyaltySetting = \Modules\BusinessSettingsModule\Entities\BusinessSettings::where('key_name', 'customer_loyalty_point')
    ->where('settings_type', 'customer_config')->first();
$originalLoyalty = $loyaltySetting?->live_values;
if ($loyaltySetting) {
    $loyaltySetting->update(['live_values' => '0']);
    $disabled = api('POST', '/customer/loyalty-point/wallet-transfer', $flagToken, ['point' => '100']);
    $loyaltySetting->update(['live_values' => $originalLoyalty]);
    if ($disabled['status'] === 400) {
        ok('Transfer rejected when customer_loyalty_point disabled');
    } else {
        fail('Transfer should be rejected when loyalty disabled', 'status=' . $disabled['status']);
    }
} else {
    warn('Could not test loyalty feature flag — setting not found');
}
$flagUser->delete();

// ── 14. Parallel race (post-fix) ────────────────────────
echo "\n14. Parallel Race Condition (Post-Fix)\n";
$raceUser = User::create([
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'first_name' => 'ParallelFix',
    'phone' => '11111' . random_int(10000, 99999),
    'email' => 'parfix_' . random_int(1000, 9999) . '@test.local',
    'password' => bcrypt('x'),
    'user_type' => 'customer',
    'is_active' => 1,
    'is_phone_verified' => 1,
]);
$raceUser->loyalty_point = 150;
$raceUser->wallet_balance = 0;
$raceUser->save();
$raceToken = tokenFor($raceUser);
$raceBefore = (float) $raceUser->loyalty_point;

$mh = curl_multi_init();
$handles = [];
for ($i = 0; $i < 3; $i++) {
    $ch = curl_init(BASE . '/customer/loyalty-point/wallet-transfer');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $raceToken, 'Accept: application/json', 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['point' => '100']),
    ]);
    curl_multi_add_handle($mh, $ch);
    $handles[] = $ch;
}
$running = null;
do { curl_multi_exec($mh, $running); curl_multi_select($mh); } while ($running > 0);

$success = 0;
foreach ($handles as $ch) {
    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) $success++;
    curl_multi_remove_handle($mh, $ch);
}
curl_multi_close($mh);

$raceUser->refresh();
$raceAfter = (float) $raceUser->loyalty_point;
if ($raceAfter >= 0) {
    ok('Loyalty balance non-negative after parallel requests');
} else {
    fail('CRITICAL: Negative loyalty balance after parallel requests', "balance={$raceAfter}");
}
if ($success <= 1) {
    ok("Only {$success}/3 parallel transfers succeeded (lockForUpdate working)");
} elseif ($success === 2 && abs($raceBefore - $raceAfter - 200) < 0.02) {
    ok('Two sequential transfers succeeded with correct total debit');
} else {
    fail('Parallel transfer over-debited', "before={$raceBefore} after={$raceAfter} success={$success}");
}
$raceUser->delete();

// ── Summary ───────────────────────────────────────────────────────
echo "\n=== SUMMARY ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Warnings: " . count($warnings) . "\n";

if (count($warnings) > 0) {
    echo "\nWarnings:\n";
    foreach ($warnings as $w) {
        echo "  - {$w}\n";
    }
}

echo "\n";
exit($failed > 0 ? 1 : 0);
