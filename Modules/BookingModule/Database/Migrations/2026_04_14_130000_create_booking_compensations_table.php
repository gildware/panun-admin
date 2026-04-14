<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NOTE: This project uses the singular table name `booking_compensation` for this model.
        Schema::create('booking_compensation', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('booking_id')->index();
            $table->uuid('customer_id')->nullable()->index();
            $table->uuid('provider_id')->nullable()->index();

            // company|provider
            $table->string('from_party', 20)->index();
            // customer|provider
            $table->string('to_party', 20)->index();

            $table->decimal('amount', 12, 2)->default(0);
            $table->string('transaction_id', 100)->nullable()->index();
            $table->text('reference_note')->nullable();
            $table->date('date')->nullable()->index();

            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_compensation');
    }
};

