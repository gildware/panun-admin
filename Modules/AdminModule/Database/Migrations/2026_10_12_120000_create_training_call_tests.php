<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_call_tests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 160);
            $table->string('status', 20)->default('draft');
            $table->text('brief')->nullable();
            $table->json('scenarios');
            $table->json('skills');
            $table->unsignedTinyInteger('pass_mark')->default(70);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('training_call_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_call_test_id')->constrained('training_call_tests')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('assigned_by')->nullable();
            $table->date('due_at')->nullable();
            $table->string('assigned_scope', 20)->nullable();
            $table->string('department_name', 160)->nullable();
            $table->timestamps();
            $table->unique(['training_call_test_id', 'user_id']);
        });

        Schema::create('training_call_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_call_assignment_id')->constrained('training_call_assignments')->cascadeOnDelete();
            $table->string('scenario', 80);
            $table->json('transcript')->nullable();
            $table->json('scores')->nullable();
            $table->unsignedTinyInteger('overall')->nullable();
            $table->boolean('passed')->nullable();
            $table->string('summary', 600)->nullable();
            $table->string('scored_by', 20)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->index(['training_call_assignment_id', 'scenario'], 'call_sessions_assignment_scenario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_call_sessions');
        Schema::dropIfExists('training_call_assignments');
        Schema::dropIfExists('training_call_tests');
    }
};
