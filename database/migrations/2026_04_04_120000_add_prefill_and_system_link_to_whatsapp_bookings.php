<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_bookings', 'service_description')) {
                $table->text('service_description')->nullable()->after('service');
            }
            if (!Schema::hasColumn('whatsapp_bookings', 'admin_prefill_json')) {
                $table->json('admin_prefill_json')->nullable()->after('location_hint');
            }
            if (!Schema::hasColumn('whatsapp_bookings', 'system_booking_id')) {
                $table->uuid('system_booking_id')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_bookings', function (Blueprint $table) {
            foreach (['service_description', 'admin_prefill_json', 'system_booking_id'] as $col) {
                if (Schema::hasColumn('whatsapp_bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
