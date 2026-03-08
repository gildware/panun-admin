<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;

class CleanOrphanBookingData extends Command
{
    protected $signature = 'booking:clean-orphans
                            {--dry-run : Show what would be deleted without making changes}';

    protected $description = 'Remove orphan booking_details_amounts, transactions and ledger rows (no existing booking), then recalc account balances.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN – no changes will be made.');
        }

        $bookingIds = Booking::pluck('id')->toArray();
        $repeatIds = BookingRepeat::pluck('id')->toArray();

        // 1. Orphan BookingDetailsAmount (booking_id missing or booking_repeat_id missing)
        $detailsOrphanQuery = BookingDetailsAmount::query()
            ->where(function ($q) use ($bookingIds, $repeatIds) {
                $q->whereNotIn('booking_id', $bookingIds);
                if (!empty($repeatIds)) {
                    $q->orWhere(function ($q2) use ($repeatIds) {
                        $q2->whereNotNull('booking_repeat_id')->whereNotIn('booking_repeat_id', $repeatIds);
                    });
                } else {
                    $q->orWhereNotNull('booking_repeat_id');
                }
            });
        $detailsCount = $detailsOrphanQuery->count();
        if ($detailsCount > 0) {
            $this->info("Orphan booking_details_amounts: {$detailsCount}");
            if (!$dryRun) {
                $detailsOrphanQuery->delete();
            }
        }

        // 2. Orphan Transaction (booking_id or booking_repeat_id points to deleted booking/repeat)
        $txOrphanQuery = Transaction::query()->where(function ($q) use ($bookingIds, $repeatIds) {
            $q->where(function ($q2) use ($bookingIds) {
                $q2->whereNotNull('booking_id')->whereNotIn('booking_id', $bookingIds);
            });
            $q->orWhere(function ($q2) use ($repeatIds) {
                $q2->whereNotNull('booking_repeat_id');
                if (!empty($repeatIds)) {
                    $q2->whereNotIn('booking_repeat_id', $repeatIds);
                } else {
                    $q2->whereRaw('1=1');
                }
            });
        });
        $txCount = $txOrphanQuery->count();
        if ($txCount > 0) {
            $this->info("Orphan transactions: {$txCount}");
            if (!$dryRun) {
                $txOrphanQuery->delete();
            }
        }

        // 3. Orphan LedgerTransaction
        $ledgerOrphanQuery = LedgerTransaction::query()->where(function ($q) use ($bookingIds, $repeatIds) {
            $q->where(function ($q2) use ($bookingIds) {
                $q2->whereNotNull('booking_id')->whereNotIn('booking_id', $bookingIds);
            });
            $q->orWhere(function ($q2) use ($repeatIds) {
                $q2->whereNotNull('booking_repeat_id');
                if (!empty($repeatIds)) {
                    $q2->whereNotIn('booking_repeat_id', $repeatIds);
                } else {
                    $q2->whereRaw('1=1');
                }
            });
        });
        $ledgerCount = $ledgerOrphanQuery->count();
        if ($ledgerCount > 0) {
            $this->info("Orphan ledger_transactions: {$ledgerCount}");
            if (!$dryRun) {
                $ledgerOrphanQuery->delete();
            }
        }

        if ($dryRun) {
            $this->info('Dry run complete. Run without --dry-run to apply.');
            return 0;
        }

        // 4. Recalculate account balances from remaining transactions
        $this->info('Recalculating account balances from transactions...');
        $this->recalculateAccountBalances();

        $this->info('Orphan cleanup and balance recalc done.');
        return 0;
    }

    private function recalculateAccountBalances(): void
    {
        $accountKeys = ['balance_pending', 'received_balance', 'account_payable', 'account_receivable', 'total_withdrawn'];

        $credits = Transaction::query()
            ->whereNotNull('to_user_id')
            ->whereNotNull('to_user_account')
            ->whereIn('to_user_account', $accountKeys)
            ->select('to_user_id as user_id', 'to_user_account as account', DB::raw('SUM(credit) as total'))
            ->groupBy('to_user_id', 'to_user_account')
            ->get();

        $debits = Transaction::query()
            ->whereNotNull('from_user_id')
            ->whereNotNull('from_user_account')
            ->whereIn('from_user_account', $accountKeys)
            ->select('from_user_id as user_id', 'from_user_account as account', DB::raw('SUM(debit) as total'))
            ->groupBy('from_user_id', 'from_user_account')
            ->get();

        $balances = [];
        foreach ($credits as $row) {
            $key = $row->user_id . '|' . $row->account;
            $balances[$key] = ($balances[$key] ?? 0) + (float) $row->total;
        }
        foreach ($debits as $row) {
            $key = $row->user_id . '|' . $row->account;
            $balances[$key] = ($balances[$key] ?? 0) - (float) $row->total;
        }

        $userAccounts = [];
        foreach ($balances as $combo => $balance) {
            [$userId, $accountKey] = explode('|', $combo);
            $userAccounts[$userId][$accountKey] = max(0, round($balance, 2));
        }

        // Update all accounts: set each key from recalc or 0
        Account::chunk(100, function ($accounts) use ($accountKeys, $userAccounts) {
            foreach ($accounts as $account) {
                $userId = $account->user_id;
                foreach ($accountKeys as $key) {
                    $account->$key = $userAccounts[$userId][$key] ?? 0;
                }
                $account->save();
            }
        });
    }
}
