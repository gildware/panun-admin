<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_center_customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->unique();
            $table->string('customer_ref', 32)->unique();
            $table->string('customer_type', 32)->default('standard');
            $table->json('tags')->nullable();
            $table->json('alternate_phones')->nullable();
            $table->string('priority', 16)->default('normal');
            $table->unsignedBigInteger('assigned_agent_id')->nullable();
            $table->string('assigned_agent_name')->nullable();
            $table->text('ai_summary')->nullable();
            $table->unsignedInteger('total_calls')->default(0);
            $table->timestamp('last_call_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('call_center_calls', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->unsignedBigInteger('customer_profile_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('direction', 16);
            $table->string('status', 32);
            $table->string('from_number', 32);
            $table->string('to_number', 32);
            $table->string('agent_external_id')->nullable();
            $table->string('agent_name')->nullable();
            $table->string('asterisk_unique_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('disposition', 64)->nullable();
            $table->string('outcome', 64)->nullable();
            $table->json('tags')->nullable();
            $table->text('notes_summary')->nullable();
            $table->string('source', 32)->default('call_center');
            $table->timestamps();

            $table->foreign('customer_profile_id')->references('id')->on('call_center_customer_profiles')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'started_at']);
        });

        Schema::create('call_center_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->unsignedBigInteger('customer_profile_id');
            $table->uuid('user_id');
            $table->unsignedBigInteger('call_id')->nullable();
            $table->uuid('call_external_id')->nullable();
            $table->string('agent_external_id')->nullable();
            $table->string('agent_name')->nullable();
            $table->text('content');
            $table->string('note_type', 32)->default('call_note');
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('noted_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_profile_id')->references('id')->on('call_center_customer_profiles')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('call_id')->references('id')->on('call_center_calls')->nullOnDelete();
        });

        Schema::create('call_center_voicemails', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->unsignedBigInteger('call_id')->nullable();
            $table->uuid('call_external_id')->nullable();
            $table->unsignedBigInteger('customer_profile_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('from_number', 32);
            $table->string('to_number', 32);
            $table->text('recording_url')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status', 32)->default('new');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('listened_at')->nullable();
            $table->uuid('returned_call_external_id')->nullable();
            $table->timestamps();

            $table->foreign('call_id')->references('id')->on('call_center_calls')->nullOnDelete();
            $table->foreign('customer_profile_id')->references('id')->on('call_center_customer_profiles')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'received_at']);
        });

        Schema::create('call_center_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 64);
            $table->string('endpoint', 128);
            $table->unsignedSmallInteger('response_status');
            $table->json('response_body');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['idempotency_key', 'endpoint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_center_idempotency_keys');
        Schema::dropIfExists('call_center_voicemails');
        Schema::dropIfExists('call_center_notes');
        Schema::dropIfExists('call_center_calls');
        Schema::dropIfExists('call_center_customer_profiles');
    }
};
