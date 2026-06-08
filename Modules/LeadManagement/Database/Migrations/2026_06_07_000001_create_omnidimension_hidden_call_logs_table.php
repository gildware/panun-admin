<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('omnidimension_hidden_call_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('omnidim_call_log_id')->unique();
            $table->foreignUuid('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['omnidim_call_log_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('omnidimension_hidden_call_logs');
    }
};
