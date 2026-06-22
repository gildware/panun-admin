<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_center_recordings', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->unsignedBigInteger('call_id');
            $table->text('recording_url');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('format', 16)->nullable();
            $table->string('storage_provider', 32)->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->foreign('call_id')->references('id')->on('call_center_calls')->cascadeOnDelete();
        });

        Schema::create('call_center_ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->unsignedBigInteger('call_id');
            $table->longText('transcript')->nullable();
            $table->text('summary')->nullable();
            $table->string('intent', 64)->nullable();
            $table->string('sentiment', 16)->nullable();
            $table->decimal('sentiment_score', 5, 2)->nullable();
            $table->json('suggested_actions')->nullable();
            $table->text('generated_notes')->nullable();
            $table->string('language', 8)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('call_id')->references('id')->on('call_center_calls')->cascadeOnDelete();
        });

        Schema::create('call_center_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->unsignedBigInteger('customer_profile_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->unsignedBigInteger('call_id')->nullable();
            $table->uuid('call_external_id')->nullable();
            $table->string('assigned_agent_external_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('priority', 16)->default('normal');
            $table->string('status', 32)->default('open');
            $table->string('source', 32)->default('call_center');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_profile_id')->references('id')->on('call_center_customer_profiles')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('call_id')->references('id')->on('call_center_calls')->nullOnDelete();
            $table->index(['assigned_agent_external_id', 'status']);
        });

        Schema::create('call_center_agents', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique()->nullable();
            $table->uuid('user_id')->nullable()->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_center_agents');
        Schema::dropIfExists('call_center_tasks');
        Schema::dropIfExists('call_center_ai_analyses');
        Schema::dropIfExists('call_center_recordings');
    }
};
