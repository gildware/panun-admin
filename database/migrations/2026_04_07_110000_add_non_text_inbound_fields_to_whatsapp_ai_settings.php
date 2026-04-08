<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            $table->text('db_non_text_inbound_message')->nullable()->after('handoff_message_out_hours');
            $table->json('db_non_text_buttons_json')->nullable()->after('db_non_text_inbound_message');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            $table->dropColumn(['db_non_text_inbound_message', 'db_non_text_buttons_json']);
        });
    }
};
