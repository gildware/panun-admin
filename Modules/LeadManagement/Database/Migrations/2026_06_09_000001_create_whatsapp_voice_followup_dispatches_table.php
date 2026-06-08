<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_voice_followup_dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('wa_phone', 32)->index();
            $table->string('to_number_e164', 32)->index();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->string('lead_type', 32)->nullable();
            $table->unsignedBigInteger('omnidim_campaign_id')->nullable()->index();
            $table->unsignedBigInteger('omnidim_request_id')->nullable();
            $table->string('call_status', 64)->nullable();
            $table->json('call_context')->nullable();
            $table->string('source', 32)->default('manual');
            $table->foreignUuid('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_voice_followup_dispatches');
    }
};
