<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProviderIdToLedgerTransactions extends Migration
{
    public function up(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->foreignUuid('provider_id')->nullable()->after('booking_repeat_id')->comment('For provider_payout: which provider was paid');
        });
    }

    public function down(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->dropColumn('provider_id');
        });
    }
}
