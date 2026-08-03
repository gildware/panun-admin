<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('workflow_step_completions') || ! Schema::hasColumn('workflow_step_completions', 'done_by')) {
            return;
        }

        Schema::table('workflow_step_completions', function (Blueprint $table) {
            $table->uuid('done_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('workflow_step_completions') || ! Schema::hasColumn('workflow_step_completions', 'done_by')) {
            return;
        }

        Schema::table('workflow_step_completions', function (Blueprint $table) {
            $table->unsignedBigInteger('done_by')->nullable()->change();
        });
    }
};
