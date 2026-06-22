<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('razorpay_webhook_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event')->nullable();
            $table->string('razorpay_payment_id')->nullable()->index();
            $table->string('razorpay_order_id')->nullable()->index();
            $table->uuid('payment_request_id')->nullable()->index();
            $table->boolean('signature_valid')->default(false);
            $table->string('result', 64)->nullable()->index();
            $table->unsignedSmallInteger('http_status')->default(200);
            $table->string('booking_readable_id')->nullable();
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('razorpay_webhook_logs');
    }
};
