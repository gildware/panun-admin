<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_progress', function (Blueprint $table) {
            $table->unsignedSmallInteger('restart_count')->default(0)->after('certified_at');
        });

        Schema::create('training_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_assignment_id')->constrained('training_assignments')->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('content_done_at')->nullable();
            $table->timestamp('certified_at')->nullable();
            $table->timestamp('restarted_at');
            $table->unsignedTinyInteger('quiz_correct')->nullable();
            $table->json('quiz_answers')->nullable();
            $table->text('ai_answer')->nullable();
            $table->json('ai_hits')->nullable();
            $table->unsignedTinyInteger('ai_score')->nullable();
            $table->json('lessons');
            $table->timestamps();
            $table->unique(['training_assignment_id', 'attempt_number'], 'training_attempts_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_attempts');
        Schema::table('training_progress', function (Blueprint $table) {
            $table->dropColumn('restart_count');
        });
    }
};
