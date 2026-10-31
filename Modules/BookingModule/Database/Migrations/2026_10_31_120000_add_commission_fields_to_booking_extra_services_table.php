<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommissionFieldsToBookingExtraServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_extra_services', function (Blueprint $table) {
            $table->boolean('commission_included')->default(false)->after('total');
            $table->string('commission_amount_type', 16)->nullable()->after('commission_included'); // percentage | amount
            $table->decimal('commission_amount', 24, 3)->default(0)->after('commission_amount_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_extra_services', function (Blueprint $table) {
            $table->dropColumn(['commission_included', 'commission_amount_type', 'commission_amount']);
        });
    }
}
