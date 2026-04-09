<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\Booking;
use Modules\ProviderManagement\Entities\Provider;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;

return new class extends Migration
{
    /**
     * Customer → provider payments were incorrectly stored as ledger IN.
     * Move them to transactions with company_flow NONE and remove ledger rows.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'company_flow')) {
            return;
        }

        LedgerTransaction::query()
            ->where('type', LedgerTransaction::TYPE_IN)
            ->where('received_by', LedgerTransaction::RECEIVED_BY_PROVIDER)
            ->whereNotNull('booking_id')
            ->orderBy('created_at')
            ->chunk(100, function ($rows) {
                foreach ($rows as $ledger) {
                    DB::transaction(function () use ($ledger) {
                        /** @var LedgerTransaction $ledger */
                        $booking = Booking::query()->find($ledger->booking_id);
                        if (! $booking) {
                            $ledger->delete();

                            return;
                        }
                        $providerUserId = Provider::query()->where('id', $booking->provider_id)->value('user_id');
                        if (! $providerUserId) {
                            return;
                        }

                        $refKey = $ledger->booking_partial_payment_id
                            ? 'booking_partial_payment:'.$ledger->booking_partial_payment_id
                            : 'migrated_ledger_provider_in:'.$ledger->id;

                        $exists = Transaction::query()
                            ->where('booking_id', $ledger->booking_id)
                            ->where('reference_note', $refKey)
                            ->exists();
                        if (! $exists) {
                            $ts = $ledger->created_at ?? now();
                            $tsStr = $ts instanceof \Carbon\Carbon ? $ts->format('Y-m-d H:i:s') : (string) $ts;
                            Transaction::query()->insert([
                                'id' => (string) Str::uuid(),
                                'ref_trx_id' => null,
                                'booking_id' => $ledger->booking_id,
                                'booking_repeat_id' => null,
                                'trx_type' => TRX_TYPE['cross_party_booking_payment'],
                                'company_flow' => Transaction::FLOW_NONE,
                                'debit' => (float) $ledger->amount,
                                'credit' => 0,
                                'balance' => 0,
                                'from_user_id' => $booking->customer_id,
                                'to_user_id' => $providerUserId,
                                'from_user_account' => null,
                                'to_user_account' => null,
                                'reference_note' => $refKey,
                                'is_guest' => 0,
                                'created_at' => $tsStr,
                                'updated_at' => $tsStr,
                            ]);
                        }

                        $ledger->delete();
                    });
                }
            });
    }

    public function down(): void
    {
        // Irreversible: ledger rows were deleted; cross-party transactions may coexist with new data.
    }
};
