<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_voice_followup_automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')
                ->constrained('whatsapp_voice_followup_automation_rules')
                ->cascadeOnDelete();
            $table->string('status', 32);
            $table->unsignedInteger('contacts_matched')->default(0);
            $table->unsignedInteger('contacts_dispatched')->default(0);
            $table->json('campaign_ids')->nullable();
            $table->string('trigger', 32)->default('cron');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('message')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['rule_id', 'started_at']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_voice_followup_automation_runs');
    }
};
