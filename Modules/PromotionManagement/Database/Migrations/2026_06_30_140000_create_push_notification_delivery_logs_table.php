<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('push_notification_delivery_logs')) {
            return;
        }

        Schema::create('push_notification_delivery_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_id', 64)->nullable();
            $table->string('fcm_token_hash', 64)->nullable();
            $table->string('fcm_token_preview', 32)->nullable();
            $table->string('delivery_target', 16)->default('device');
            $table->string('topic', 128)->nullable();
            $table->string('notification_type', 64)->nullable();
            $table->string('title', 255)->nullable();
            $table->string('status', 16);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('error_message')->nullable();
            $table->uuid('push_notification_id')->nullable();
            $table->string('booking_id', 64)->nullable();
            $table->timestamps();

            $table->index(['created_at', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_delivery_logs');
    }
};
