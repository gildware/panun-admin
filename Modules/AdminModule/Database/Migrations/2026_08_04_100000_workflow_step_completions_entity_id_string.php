<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('workflow_step_completions')) {
            return;
        }

        Schema::table('workflow_step_completions', function (Blueprint $table) {
            $table->string('entity_id', 36)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('workflow_step_completions')) {
            return;
        }

        Schema::table('workflow_step_completions', function (Blueprint $table) {
            $table->unsignedBigInteger('entity_id')->change();
        });
    }
};
