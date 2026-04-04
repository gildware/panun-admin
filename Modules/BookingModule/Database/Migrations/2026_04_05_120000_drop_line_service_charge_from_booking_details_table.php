<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_details', function (Blueprint $table) {
            if (Schema::hasColumn('booking_details', 'line_service_charge')) {
                $table->dropColumn('line_service_charge');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_details', function (Blueprint $table) {
            $table->decimal('line_service_charge', 24, 3)->default(0)->after('total_cost');
        });
    }
};
