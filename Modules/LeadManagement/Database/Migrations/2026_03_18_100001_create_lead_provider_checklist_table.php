<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('lead_provider_checklist')) {
            Schema::create('lead_provider_checklist', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
                $table->foreignId('provider_checklist_item_id')->constrained('provider_checklist_items')->cascadeOnDelete();
                $table->boolean('is_done')->default(false);
                $table->timestamps();

                $table->unique(['lead_id', 'provider_checklist_item_id'], 'lead_provider_checklist_lead_item_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_provider_checklist');
    }
};
