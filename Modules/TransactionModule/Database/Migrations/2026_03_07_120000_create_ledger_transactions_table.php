<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLedgerTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     * Simple IN/OUT transaction ledger for company money flow.
     */
    public function up(): void
    {
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('amount', 24, 2)->default(0);
            $table->string('type', 8); // IN | OUT
            $table->string('transaction_id', 100)->nullable()->comment('Payment gateway or manual reference');
            $table->foreignUuid('booking_id')->nullable();
            $table->foreignUuid('booking_repeat_id')->nullable();
            $table->string('payment_method', 64)->nullable()->comment('For IN: digital_payment, offline_payment, wallet_payment, cash_after_service, etc.');
            $table->string('reason', 64)->nullable()->comment('For OUT: refund, provider_payout');
            $table->date('date');
            $table->string('received_by', 32)->nullable()->comment('company | provider - who received payment');
            $table->text('reference_note')->nullable();
            $table->timestamps();

            $table->index(['type', 'date']);
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_transactions');
    }
}
