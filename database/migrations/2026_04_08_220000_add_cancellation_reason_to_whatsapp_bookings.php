<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_bookings')) {
            return;
        }
        Schema::table('whatsapp_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_bookings', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_bookings') || ! Schema::hasColumn('whatsapp_bookings', 'cancellation_reason')) {
            return;
        }
        Schema::table('whatsapp_bookings', function (Blueprint $table) {
            $table->dropColumn('cancellation_reason');
        });
    }
};
