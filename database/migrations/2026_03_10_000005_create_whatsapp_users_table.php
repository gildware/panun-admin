<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('phone', 50)->unique();
            $table->string('name')->nullable();
            $table->string('alternate_phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('type', 20)->nullable(); // CUSTOMER / PROVIDER
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_users');
    }
};

