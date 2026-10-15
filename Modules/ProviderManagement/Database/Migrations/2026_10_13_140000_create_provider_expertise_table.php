<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('provider_expertise')) {
            return;
        }

        Schema::create('provider_expertise', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignUuid('sub_category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignUuid('service_id')->nullable()->constrained('services')->cascadeOnDelete();
            $table->string('scope', 20);
            $table->timestamps();

            $table->index(['provider_id', 'sub_category_id'], 'provider_expertise_provider_sub_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_expertise');
    }
};
