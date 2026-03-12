<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('booking_id')->index();
            $table->string('phone', 50)->index();
            $table->string('name')->nullable();
            $table->string('alt_phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('service')->nullable();
            $table->dateTime('prefered_datetime')->nullable();
            $table->string('status', 50)->nullable();
            $table->string('location_hint')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_bookings');
    }
};

