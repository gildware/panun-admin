<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReceivedByToBookingPartialPayments extends Migration
{
    public function up(): void
    {
        Schema::table('booking_partial_payments', function (Blueprint $table) {
            $table->string('received_by', 32)->nullable()->after('due_amount')
                ->comment('company | provider - who received this payment');
        });
    }

    public function down(): void
    {
        Schema::table('booking_partial_payments', function (Blueprint $table) {
            $table->dropColumn('received_by');
        });
    }
}
