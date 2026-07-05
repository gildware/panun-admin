<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_app_call_signals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('call_id');
            $table->uuid('sender_user_id');
            $table->string('signal_type', 16);
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['call_id', 'created_at']);
            $table->foreign('call_id')->references('id')->on('in_app_calls')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_app_call_signals');
    }
};
