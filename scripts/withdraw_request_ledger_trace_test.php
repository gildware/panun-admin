<?php
/**
 * Trace company ledger rows through: provider withdraw submit → admin approve.
 * Uses a DB transaction and rolls back — no permanent data changes.
 *
 * Run: php scripts/withdraw_request_ledger_trace_test.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\WithdrawRequest;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\UserManagement\Entities\User;

$passed = 0;
$failed = 0;

function ok(string $name): void
{
    global $passed;
    $passed++;
    echo "  ✓ {$name}\n";
}

function fail(string $name, string $detail = ''): void
{
    global $failed;
    $failed++;
    echo "  ✗ {$name}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
}

function ledgerInForProvider(string $providerId): int
{
    return LedgerTransaction::query()
        ->where('provider_id', $providerId)
        ->where('type', LedgerTransaction::TYPE_IN)
        ->count();
}

function ledgerInCollectFromProvider(string $providerId): int
{
    return LedgerTransaction::query()
        ->where('provider_id', $providerId)
        ->where('type', LedgerTransaction::TYPE_IN)
        ->where('payment_method', 'collect_from_provider')
        ->count();
}

function describeLedgerRows(string $providerId, string $label): void
{
    $rows = LedgerTransaction::query()
        ->where('provider_id', $providerId)
        ->orderBy('created_at')
        ->get();

    echo "\n  [{$label}] ledger rows for provider: {$rows->count()}\n";
    foreach ($rows as $row) {
        $flow = method_exists($row, 'counterpartyFlowKey') ? $row->counterpartyFlowKey() : '?';
        echo "    - {$row->type} | amt={$row->amount} | pm={$row->payment_method} | reason={$row->reason} | flow={$flow} | at={$row->created_at}\n";
    }
}

echo "\n=== Withdraw request → admin approve ledger trace ===\n\n";

$admin = User::query()->where('user_type', 'super-admin')->first();
if (!$admin) {
    echo "No super-admin user found. Aborting.\n";
    exit(1);
}

DB::beginTransaction();

try {
    $providerUserId = (string) Str::uuid();

    $providerUser = new User();
    $providerUser->id = $providerUserId;
    $providerUser->first_name = 'Ledger';
    $providerUser->last_name = 'TraceTest';
    $providerUser->email = 'ledger-trace-' . Str::random(8) . '@example.test';
    $providerUser->phone = '9' . random_int(100000000, 999999999);
    $providerUser->password = bcrypt('password');
    $providerUser->user_type = 'provider-admin';
    $providerUser->is_active = 1;
    $providerUser->save();

    $provider = new Provider();
    $provider->user_id = $providerUserId;
    $provider->company_name = 'Ledger Trace Provider';
    $provider->company_phone = '9999999999';
    $provider->company_address = 'Test';
    $provider->is_active = 1;
    $provider->save();
    $providerId = (string) $provider->id;

    $account = new Account();
    $account->id = (string) Str::uuid();
    $account->user_id = $providerUserId;
    $account->account_receivable = 1000;
    $account->account_payable = 200;
    $account->balance_pending = 0;
    $account->received_balance = 0;
    $account->total_withdrawn = 0;
    $account->save();

    $withdrawAmount = 500.0;
    $payable = 200.0;

    $inBefore = ledgerInForProvider($providerId);
    $collectBefore = ledgerInCollectFromProvider($providerId);

    echo "Setup: receivable=1000, payable=200, withdraw={$withdrawAmount}\n";
    echo "Ledger IN before submit: {$inBefore} (collect_from_provider: {$collectBefore})\n";

    // --- Step 1: Provider submits withdraw (same as WithdrawController::store) ---
    echo "\n1. Provider submits withdraw request\n";

    withdrawRequestTransaction($providerUserId, $withdrawAmount);

    if ($payable > 0) {
        withdrawRequestAcceptForAdjustTransaction($providerUserId, $payable);
        collectCashTransaction($providerId, $payable, null, null, null, false);
    }

    $withdrawRequest = WithdrawRequest::query()->create([
        'user_id' => $providerUserId,
        'request_updated_by' => $providerUserId,
        'amount' => $withdrawAmount,
        'request_status' => 'pending',
        'is_paid' => 0,
        'note' => 'ledger trace test',
    ]);

    $inAfterSubmit = ledgerInForProvider($providerId);
    $collectAfterSubmit = ledgerInCollectFromProvider($providerId);
    $inDeltaSubmit = $inAfterSubmit - $inBefore;
    $collectDeltaSubmit = $collectAfterSubmit - $collectBefore;

    describeLedgerRows($providerId, 'after submit');

    if ($inDeltaSubmit === 0 && $collectDeltaSubmit === 0) {
        ok('Submit did not create a ledger IN row (commission netting is internal only)');
    } else {
        fail('Submit ledger IN count', "expected 0 IN on submit, got IN delta={$inDeltaSubmit}, collect delta={$collectDeltaSubmit}");
    }

    $accountAfterSubmit = Account::query()->where('user_id', $providerUserId)->first();
    echo "  Account after submit: receivable={$accountAfterSubmit->account_receivable}, payable={$accountAfterSubmit->account_payable}, pending={$accountAfterSubmit->balance_pending}\n";

    // --- Step 2: Admin approves (same as WithdrawRequestController::updateStatus approved) ---
    echo "\n2. Admin approves withdraw request\n";

    $withdrawRequest->request_status = 'approved';
    $withdrawRequest->request_updated_by = $admin->id;
    $withdrawRequest->is_paid = 0;
    $withdrawRequest->save();

    $inAfterApprove = ledgerInForProvider($providerId);
    $collectAfterApprove = ledgerInCollectFromProvider($providerId);
    $inDeltaApprove = $inAfterApprove - $inAfterSubmit;

    describeLedgerRows($providerId, 'after approve');

    if ($inDeltaApprove === 0) {
        ok('Approve did NOT add any new ledger IN row');
    } else {
        fail('Approve ledger IN count', "expected 0 new IN rows on approve, got +{$inDeltaApprove}");
    }

    // --- Step 3: Admin settles (for comparison) ---
    echo "\n3. Admin settles withdraw request (reference)\n";

    settleWithdrawRequestPayout($withdrawRequest, 'TEST-TXN-' . Str::random(6));

    $outCount = LedgerTransaction::query()
        ->where('provider_id', $providerId)
        ->where('type', LedgerTransaction::TYPE_OUT)
        ->where('reason', LedgerTransaction::REASON_PROVIDER_PAYOUT)
        ->count();

    describeLedgerRows($providerId, 'after settle');

    if ($outCount === 1) {
        ok('Settle created 1 ledger OUT (provider_payout)');
    } else {
        fail('Settle ledger OUT count', "expected 1 OUT, got {$outCount}");
    }

    echo "\n--- Conclusion ---\n";
    echo "The commission payable is still netted internally on submit (account balances),\n";
    echo "but no company ledger IN is written until admin uses Collect Cash or similar.\n";
    echo "Payout ledger OUT is recorded only on settle.\n";

    DB::rollBack();
    echo "\n(DB transaction rolled back — no test data persisted.)\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo "\nError: {$e->getMessage()}\n{$e->getTraceAsString()}\n";
    exit(1);
}

echo "\nResults: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
