<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_change_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('change_type', 32); // profile, business_settings, services
            $table->unsignedTinyInteger('status')->default(2); // 2=pending, 1=approved, 0=denied
            $table->json('payload');
            $table->foreignUuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'status']);
            $table->index(['change_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_change_requests');
    }
};
