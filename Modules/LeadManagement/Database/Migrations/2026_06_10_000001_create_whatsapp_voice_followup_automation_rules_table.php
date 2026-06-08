<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_voice_followup_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('interval_minutes')->default(60);
            $table->json('filters');
            $table->string('campaign_name');
            $table->unsignedSmallInteger('max_contacts_per_run')->default(50);
            $table->unsignedTinyInteger('concurrent_call_limit')->default(1);
            $table->boolean('enabled_reschedule_call')->default(false);
            $table->boolean('auto_retry')->default(false);
            $table->string('auto_retry_schedule', 32)->nullable();
            $table->unsignedTinyInteger('retry_limit')->default(1);
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('last_run_contacts')->default(0);
            $table->string('last_run_status', 32)->nullable();
            $table->text('last_run_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_voice_followup_automation_rules');
    }
};
