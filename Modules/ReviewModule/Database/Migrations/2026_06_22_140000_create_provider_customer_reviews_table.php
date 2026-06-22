<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_customer_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id');
            $table->foreignUuid('provider_id');
            $table->foreignUuid('customer_id');
            $table->integer('review_rating')->default(1);
            $table->text('review_comment')->nullable();
            $table->string('readable_id')->nullable();
            $table->dateTime('booking_date')->nullable();
            $table->boolean('is_active')->default(0);
            $table->timestamps();

            $table->unique(['booking_id', 'provider_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('received_avg_rating', 8, 2)->default(0)->after('loyalty_point');
            $table->unsignedInteger('received_rating_count')->default(0)->after('received_avg_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_customer_reviews');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['received_avg_rating', 'received_rating_count']);
        });
    }
};
