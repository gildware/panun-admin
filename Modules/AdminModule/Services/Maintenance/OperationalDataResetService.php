<?php

namespace Modules\AdminModule\Services\Maintenance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;

class OperationalDataResetService
{
    public function reset(): void
    {
        DB::transaction(function () {
            $this->clearLeadData();
            $this->clearBookingData();
            $this->clearFinancialData();
        });
    }

    private function clearLeadData(): void
    {
        $tables = [
            'lead_followups',
            'lead_change_logs',
            'lead_customer_tag',
            'lead_outbound_enquiries',
            'leads',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    private function clearBookingData(): void
    {
        $tables = [
            'subscription_subscriber_bookings',
            'booking_repeat_histories',
            'booking_repeat_details',
            'booking_repeats',
            'booking_partial_payments',
            'booking_offline_payments',
            'booking_schedule_histories',
            'booking_status_histories',
            'booking_followups',
            'booking_extra_services',
            'booking_details_amounts',
            'booking_details',
            'bookings',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    private function clearFinancialData(): void
    {
        // Remove all booking-related ledger and transaction rows.
        LedgerTransaction::query()->delete();
        Transaction::query()->delete();

        // Reset all account balances to zero after clearing transactions.
        if (Schema::hasTable('accounts')) {
            $accountKeys = [
                'balance_pending',
                'received_balance',
                'account_payable',
                'account_receivable',
                'total_withdrawn',
            ];

            Account::chunk(100, function ($accounts) use ($accountKeys) {
                foreach ($accounts as $account) {
                    foreach ($accountKeys as $key) {
                        if (array_key_exists($key, $account->getAttributes())) {
                            $account->$key = 0;
                        }
                    }
                    $account->save();
                }
            });
        }
    }
}

