<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            $table->json('db_handoff_in_buttons_json')->nullable()->after('handoff_message_in_hours');
            $table->json('db_handoff_out_buttons_json')->nullable()->after('handoff_message_out_hours');
            $table->json('db_booking_escalation_buttons_json')->nullable()->after('booking_provider_escalation_message');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            $table->dropColumn([
                'db_handoff_in_buttons_json',
                'db_handoff_out_buttons_json',
                'db_booking_escalation_buttons_json',
            ]);
        });
    }
};
