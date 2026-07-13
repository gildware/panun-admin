<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_custom_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_id')->unique();
            $table->uuid('customer_id')->nullable()->index();
            $table->string('name');
            $table->string('phone', 32)->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('category_name')->nullable();
            $table->text('description');
            $table->string('status', 50)->default('PENDING_REVIEW')->index();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_custom_requests');
    }
};
