<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_bookings') || !Schema::hasTable('leads')) {
            return;
        }
        Schema::table('whatsapp_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_bookings', 'lead_id')) {
                $table->foreignId('lead_id')->nullable()->after('system_booking_id')->constrained('leads')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('whatsapp_bookings') || !Schema::hasColumn('whatsapp_bookings', 'lead_id')) {
            return;
        }
        Schema::table('whatsapp_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lead_id');
        });
    }
};
