<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('workflow_step_completions')) {
            Schema::create('workflow_step_completions', function (Blueprint $table) {
                $table->id();
                $table->string('entity_type', 32);
                $table->unsignedBigInteger('entity_id');
                $table->string('step_key', 128);
                $table->boolean('is_done')->default(false);
                $table->uuid('done_by')->nullable();
                $table->timestamp('done_at')->nullable();
                $table->timestamps();

                $table->unique(['entity_type', 'entity_id', 'step_key'], 'workflow_step_entity_key_unique');
                $table->index(['entity_type', 'entity_id'], 'workflow_step_entity_idx');
                $table->index(['entity_type', 'is_done'], 'workflow_step_type_done_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_step_completions');
    }
};
