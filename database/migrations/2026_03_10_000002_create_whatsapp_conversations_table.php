<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('phone', 50)->unique();
            $table->string('active_module')->nullable();
            $table->string('current_step')->nullable();
            $table->boolean('after_hours')->default(false);
            $table->string('active_booking_id')->nullable();
            $table->string('active_lead_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};

