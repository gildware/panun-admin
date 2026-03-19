<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddServiceAvailabilityToProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('providers', 'service_availability')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->boolean('service_availability')->default(1);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('providers', 'service_availability')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('service_availability');
            });
        }
    }
}
