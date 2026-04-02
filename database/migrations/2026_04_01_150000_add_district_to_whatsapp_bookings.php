<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_bookings', 'district')) {
                $table->string('district', 191)->nullable()->after('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_bookings', 'district')) {
                $table->dropColumn('district');
            }
        });
    }
};
