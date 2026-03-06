<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->date('date');
            $table->string('reason')->nullable();
            $table->string('for')->default('customer'); // customer, provider
            $table->string('status')->default('scheduled'); // scheduled, completed, rescheduled, cancelled
            $table->text('remarks')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_followups');
    }
};
