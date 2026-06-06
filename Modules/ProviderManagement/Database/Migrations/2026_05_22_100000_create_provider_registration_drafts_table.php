<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_registration_drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone')->unique();
            $table->string('registration_token', 64)->unique();
            $table->string('provider_type', 32)->nullable();
            $table->string('current_step', 64)->default('provider_type');
            $table->json('completed_steps')->nullable();
            $table->json('form_data')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_registration_drafts');
    }
};
