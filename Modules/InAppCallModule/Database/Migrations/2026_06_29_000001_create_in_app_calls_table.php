<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_app_calls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('channel_id');
            $table->uuid('caller_user_id');
            $table->uuid('callee_user_id');
            $table->string('agora_channel_name', 128);
            $table->string('status', 32)->default('ringing');
            $table->string('reference_id', 64)->nullable();
            $table->string('reference_type', 32)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('end_reason', 32)->nullable();
            $table->timestamps();

            $table->index(['channel_id', 'status']);
            $table->index(['caller_user_id', 'created_at']);
            $table->index(['callee_user_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_app_calls');
    }
};
