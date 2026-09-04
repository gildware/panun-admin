<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_hunting_interests')) {
            return;
        }

        Schema::create('lead_hunting_interests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->uuid('provider_id');
            $table->string('status', 32)->default('interested');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['lead_id', 'provider_id']);
            $table->index('provider_id');
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_hunting_interests');
    }
};
