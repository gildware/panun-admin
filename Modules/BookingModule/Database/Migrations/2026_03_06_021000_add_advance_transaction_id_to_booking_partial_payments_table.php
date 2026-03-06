<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_partial_payments', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->after('paid_with');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_partial_payments', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
        });
    }
};

