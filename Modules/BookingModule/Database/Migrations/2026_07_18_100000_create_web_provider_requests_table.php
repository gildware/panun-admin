<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('web_provider_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_id')->unique();
            $table->string('name');
            $table->string('phone', 32)->index();
            $table->string('service_category')->nullable();
            $table->string('area')->nullable();
            $table->text('details')->nullable();
            $table->string('experience')->nullable();
            $table->string('status', 50)->default('PENDING_REVIEW')->index();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_provider_requests');
    }
};
