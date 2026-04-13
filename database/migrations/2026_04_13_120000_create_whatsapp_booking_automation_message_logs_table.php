<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_booking_automation_message_logs')) {
            return;
        }

        Schema::create('whatsapp_booking_automation_message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('message_key', 96);
            $table->string('trigger_event', 190)->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('template_name', 255)->nullable();
            $table->string('recipient_party', 32)->default('unknown');
            $table->string('recipient_phone', 64)->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('booking_repeat_id')->nullable();
            $table->string('wa_message_id', 255)->nullable();
            $table->unsignedBigInteger('local_whatsapp_message_id')->nullable();
            $table->string('result', 24);
            $table->text('error_detail')->nullable();
            $table->unsignedBigInteger('acting_admin_user_id')->nullable();
            $table->json('context_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('message_key', 'wa_bk_autolog_msgkey');
            $table->index('recipient_party', 'wa_bk_autolog_party');
            $table->index('recipient_phone', 'wa_bk_autolog_phone');
            $table->index('booking_id', 'wa_bk_autolog_bkid');
            $table->index('booking_repeat_id', 'wa_bk_autolog_rpt');
            $table->index('wa_message_id', 'wa_bk_autolog_waid');
            $table->index('local_whatsapp_message_id', 'wa_bk_autolog_lcl');
            $table->index('result', 'wa_bk_autolog_result');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_booking_automation_message_logs');
    }
};
