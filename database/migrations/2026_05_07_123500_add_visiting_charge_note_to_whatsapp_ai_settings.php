<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_ai_settings', 'db_visiting_charge_note')) {
                $table->string('db_visiting_charge_note', 2000)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_ai_settings', 'db_visiting_charge_note')) {
                $table->dropColumn('db_visiting_charge_note');
            }
        });
    }
};

