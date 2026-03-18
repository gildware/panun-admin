<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings') && !Schema::hasColumn('bookings', 'lead_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                // Leads are integer IDs in LeadManagement module
                $table->unsignedBigInteger('lead_id')->nullable()->after('booking_source');
                $table->index('lead_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'lead_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropIndex(['lead_id']);
                $table->dropColumn('lead_id');
            });
        }
    }
};

