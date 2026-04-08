<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_ai_settings', 'db_support_days')) {
                $table->json('db_support_days')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_ai_settings', 'db_support_days')) {
                $table->dropColumn('db_support_days');
            }
        });
    }
};
