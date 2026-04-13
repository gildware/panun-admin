<?php

namespace Modules\BookingModule\Services;

use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;

class AdminBookingDeletionService
{
    /**
     * Deletes a booking and all related financial rows, repeats, details, and reviews.
     * Does not wrap in a transaction — caller should use DB::transaction when needed.
     */
    public function deleteBookingAndRelations(Booking $booking): void
    {
        $repeatIds = $booking->repeat->pluck('id')->toArray();

        $txQuery = Transaction::where('booking_id', $booking->id);
        if (! empty($repeatIds)) {
            $txQuery->orWhereIn('booking_repeat_id', $repeatIds);
        }
        $transactions = $txQuery->get();

        $this->reverseAccountsForTransactions($transactions);

        $txDeleteQuery = Transaction::where('booking_id', $booking->id);
        if (! empty($repeatIds)) {
            $txDeleteQuery->orWhereIn('booking_repeat_id', $repeatIds);
        }
        $txDeleteQuery->delete();

        $ledgerQuery = LedgerTransaction::where('booking_id', $booking->id);
        if (! empty($repeatIds)) {
            $ledgerQuery->orWhereIn('booking_repeat_id', $repeatIds);
        }
        $ledgerQuery->delete();

        foreach ($booking->repeat as $repeat) {
            $repeat->detail()->delete();
            $repeat->details_amounts()->delete();
            $repeat->statusHistories()->delete();
            $repeat->scheduleHistories()->delete();
            $repeat->repeatHistories()->delete();
            $repeat->delete();
        }

        $booking->extra_services()->delete();
        $booking->detail()->delete();
        $booking->details_amounts()->delete();
        $booking->schedule_histories()->delete();
        $booking->status_histories()->delete();
        $booking->booking_offline_payments()->delete();
        $booking->ignores()->delete();
        $booking->reviews()->delete();
        $booking->booking_partial_payments()->delete();

        $booking->delete();
    }

    /**
     * Reverse account balances for the given transactions, then delete those rows.
     */
    public function reverseAccountsAndDeleteTransactions(Collection $transactions): void
    {
        $this->reverseAccountsForTransactions($transactions);
        if ($transactions->isEmpty()) {
            return;
        }
        Transaction::whereIn('id', $transactions->pluck('id'))->delete();
    }

    private function reverseAccountsForTransactions(Collection $transactions): void
    {
        $accountDeltas = [];

        foreach ($transactions as $tx) {
            if ($tx->to_user_id && $tx->to_user_account && $tx->credit > 0) {
                $accountDeltas[$tx->to_user_id][$tx->to_user_account] = ($accountDeltas[$tx->to_user_id][$tx->to_user_account] ?? 0) - $tx->credit;
            }
            if ($tx->from_user_id && $tx->from_user_account && $tx->credit > 0 && empty($tx->to_user_account)) {
                $accountDeltas[$tx->from_user_id][$tx->from_user_account] = ($accountDeltas[$tx->from_user_id][$tx->from_user_account] ?? 0) - $tx->credit;
            }
            if ($tx->from_user_id && $tx->from_user_account && $tx->debit > 0) {
                $accountDeltas[$tx->from_user_id][$tx->from_user_account] = ($accountDeltas[$tx->from_user_id][$tx->from_user_account] ?? 0) + $tx->debit;
            }
            if ($tx->to_user_id && $tx->to_user_account && $tx->debit > 0 && empty($tx->from_user_account)) {
                $accountDeltas[$tx->to_user_id][$tx->to_user_account] = ($accountDeltas[$tx->to_user_id][$tx->to_user_account] ?? 0) + $tx->debit;
            }
        }

        foreach ($accountDeltas as $userId => $deltas) {
            $account = Account::where('user_id', $userId)->first();
            if ($account) {
                foreach ($deltas as $accountKey => $delta) {
                    if (in_array($accountKey, ['balance_pending', 'received_balance', 'account_payable', 'account_receivable', 'total_withdrawn'])) {
                        $account->$accountKey = max(0, ($account->$accountKey ?? 0) + $delta);
                    }
                }
                $account->save();
            }
        }
    }
}
