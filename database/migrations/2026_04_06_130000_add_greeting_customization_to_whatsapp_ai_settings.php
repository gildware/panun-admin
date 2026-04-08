<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            $table->text('db_greeting_message')->nullable()->after('db_greeting_buttons');
            $table->json('db_greeting_buttons_json')->nullable()->after('db_greeting_message');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            $table->dropColumn(['db_greeting_message', 'db_greeting_buttons_json']);
        });
    }
};
