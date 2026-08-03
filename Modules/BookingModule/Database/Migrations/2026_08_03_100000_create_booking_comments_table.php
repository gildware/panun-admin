<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('booking_comments')) {
            return;
        }

        Schema::create('booking_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('created_by', 64)->nullable();
            $table->text('body');
            $table->json('mentioned_user_ids')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('pinned_at')->nullable();
            $table->string('pinned_by', 64)->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'is_pinned', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_comments');
    }
};
