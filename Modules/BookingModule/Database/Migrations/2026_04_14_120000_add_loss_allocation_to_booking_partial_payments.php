<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_partial_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_partial_payments', 'loss_allocation_provider')) {
                $table->decimal('loss_allocation_provider', 24, 3)
                    ->nullable()
                    ->after('received_by')
                    ->comment('Loss-making recovery: portion of paid_amount attributed to provider settlement (optional)');
            }
            if (! Schema::hasColumn('booking_partial_payments', 'loss_allocation_company')) {
                $table->decimal('loss_allocation_company', 24, 3)
                    ->nullable()
                    ->after('loss_allocation_provider')
                    ->comment('Loss-making recovery: portion of paid_amount attributed to company settlement (optional)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_partial_payments', function (Blueprint $table) {
            if (Schema::hasColumn('booking_partial_payments', 'loss_allocation_company')) {
                $table->dropColumn('loss_allocation_company');
            }
            if (Schema::hasColumn('booking_partial_payments', 'loss_allocation_provider')) {
                $table->dropColumn('loss_allocation_provider');
            }
        });
    }
};
