<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('web_bookings') || ! Schema::hasColumn('web_bookings', 'status')) {
            return;
        }

        Schema::table('web_bookings', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('web_bookings') || Schema::hasColumn('web_bookings', 'status')) {
            return;
        }

        Schema::table('web_bookings', function (Blueprint $table) {
            $table->string('status', 50)->default('PENDING_REVIEW')->index()->after('preferred_date');
        });
    }
};
