<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_ai_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trigger_whatsapp_message_id')->index();
            $table->string('phone', 64)->index();
            $table->string('status', 24)->index();
            $table->string('outcome', 64)->nullable()->index();
            $table->string('summary', 512)->nullable();
            $table->unsignedBigInteger('outbound_whatsapp_message_id')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->json('steps');
            $table->json('meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_ai_executions');
    }
};
