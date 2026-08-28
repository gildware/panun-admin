<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_repeats', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_repeats', 'visit_remarks')) {
                $table->text('visit_remarks')->nullable()->after('service_schedule');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_repeats', function (Blueprint $table) {
            if (Schema::hasColumn('booking_repeats', 'visit_remarks')) {
                $table->dropColumn('visit_remarks');
            }
        });
    }
};
