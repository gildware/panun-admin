<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('settlement_outcome', 64)->nullable()->after('reopen_resolve_remarks');
            $table->json('settlement_config')->nullable()->after('settlement_outcome');
            $table->json('settlement_snapshot')->nullable()->after('settlement_config');
            $table->boolean('allow_complete_without_full_payment')->default(false)->after('settlement_snapshot');
            $table->text('settlement_remarks')->nullable()->after('allow_complete_without_full_payment');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'settlement_outcome',
                'settlement_config',
                'settlement_snapshot',
                'allow_complete_without_full_payment',
                'settlement_remarks',
            ]);
        });
    }
};
