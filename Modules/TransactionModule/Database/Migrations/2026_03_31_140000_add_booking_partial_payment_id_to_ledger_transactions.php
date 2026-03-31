<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\TransactionModule\Entities\LedgerTransaction;

return new class extends Migration
{
    /**
     * Link ledger rows to booking partial payments so provider-received customer payments appear in transactions.
     */
    public function up(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->foreignUuid('booking_partial_payment_id')
                ->nullable()
                ->after('booking_repeat_id')
                ->constrained('booking_partial_payments')
                ->nullOnDelete();
            $table->unique('booking_partial_payment_id');
        });

        $partials = BookingPartialPayment::query()
            ->where('received_by', LedgerTransaction::RECEIVED_BY_PROVIDER)
            ->where('paid_amount', '>', 0)
            ->orderBy('created_at')
            ->get();

        foreach ($partials as $p) {
            if (LedgerTransaction::query()->where('booking_partial_payment_id', $p->id)->exists()) {
                continue;
            }

            $legacy = LedgerTransaction::query()
                ->where('booking_id', $p->booking_id)
                ->where('type', LedgerTransaction::TYPE_IN)
                ->where('received_by', LedgerTransaction::RECEIVED_BY_PROVIDER)
                ->whereRaw('ABS(amount - ?) < 0.01', [(float) $p->paid_amount])
                ->whereNull('booking_partial_payment_id')
                ->whereDate('date', Carbon::parse($p->created_at)->toDateString())
                ->orderBy('created_at')
                ->first();

            if ($legacy) {
                $legacy->update(['booking_partial_payment_id' => $p->id]);
                continue;
            }

            LedgerTransaction::create([
                'amount' => (float) $p->paid_amount,
                'type' => LedgerTransaction::TYPE_IN,
                'transaction_id' => $p->transaction_id,
                'booking_id' => $p->booking_id,
                'payment_method' => $p->paid_with ?: 'partial_payment',
                'date' => Carbon::parse($p->created_at)->toDateString(),
                'received_by' => LedgerTransaction::RECEIVED_BY_PROVIDER,
                'booking_partial_payment_id' => $p->id,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->dropForeign(['booking_partial_payment_id']);
            $table->dropUnique(['booking_partial_payment_id']);
            $table->dropColumn('booking_partial_payment_id');
        });
    }
};
