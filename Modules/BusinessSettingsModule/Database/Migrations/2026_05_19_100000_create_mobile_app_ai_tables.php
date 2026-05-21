<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_app_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('inherit_whatsapp_ai')->default(false);
            $table->boolean('use_full_custom_prompt')->default(false);
            $table->longText('custom_system_prompt')->nullable();
            $table->longText('assistant_persona')->nullable();
            $table->longText('prompt_addendum')->nullable();
            $table->string('gemini_model', 120)->nullable();
            $table->unsignedSmallInteger('max_history_messages')->default(24);
            $table->timestamps();
        });

        Schema::create('mobile_app_ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('last_message_at');
        });

        Schema::create('mobile_app_ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('mobile_app_ai_conversations')->cascadeOnDelete();
            $table->string('role', 20);
            $table->longText('body');
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_app_ai_messages');
        Schema::dropIfExists('mobile_app_ai_conversations');
        Schema::dropIfExists('mobile_app_ai_settings');
    }
};
