<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_lead_tags')) {
            Schema::create('customer_lead_tags', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('color', 20)->default('#0d6efd');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lead_customer_tag')) {
            Schema::create('lead_customer_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
                $table->foreignId('customer_lead_tag_id')->constrained('customer_lead_tags')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['lead_id', 'customer_lead_tag_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_customer_tag');
        Schema::dropIfExists('customer_lead_tags');
    }
};
