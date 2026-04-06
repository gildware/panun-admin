<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_ai_settings', 'handoff_message_in_hours')) {
                $table->longText('handoff_message_in_hours')->nullable();
            }
            if (! Schema::hasColumn('whatsapp_ai_settings', 'handoff_message_out_hours')) {
                $table->longText('handoff_message_out_hours')->nullable();
            }
            if (! Schema::hasColumn('whatsapp_ai_settings', 'booking_provider_escalation_message')) {
                $table->longText('booking_provider_escalation_message')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            $cols = [];
            foreach (['handoff_message_in_hours', 'handoff_message_out_hours', 'booking_provider_escalation_message'] as $c) {
                if (Schema::hasColumn('whatsapp_ai_settings', $c)) {
                    $cols[] = $c;
                }
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
