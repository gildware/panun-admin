<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_columns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120);
            $table->string('color', 32)->default('#64748b');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('task_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('column_id');
            $table->string('title', 255);
            $table->longText('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['column_id', 'position']);
            $table->index('end_date');
        });

        Schema::create('task_ticket_assignees', function (Blueprint $table) {
            $table->uuid('ticket_id');
            $table->uuid('user_id');
            $table->timestamps();

            $table->primary(['ticket_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('task_ticket_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->string('linkable_type', 32);
            $table->string('linkable_id', 64);
            $table->timestamps();

            $table->unique(['ticket_id', 'linkable_type', 'linkable_id'], 'task_ticket_links_unique');
            $table->index(['linkable_type', 'linkable_id']);
        });

        Schema::create('task_ticket_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('user_id');
            $table->longText('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index('ticket_id');
        });

        Schema::create('task_ticket_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('uploaded_by')->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('stored_name', 255);
            $table->string('disk', 32)->default('public');
            $table->timestamps();
            $table->softDeletes();

            $table->index('ticket_id');
        });

        Schema::create('task_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id')->nullable()->index();
            $table->uuid('actor_id')->nullable()->index();
            $table->string('action', 64);
            $table->string('subject_type', 64)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });

        $now = now();
        $defaults = config('taskboardmodule.default_columns', [
            ['name' => 'To Do', 'color' => '#64748b', 'position' => 0],
            ['name' => 'In Progress', 'color' => '#2563eb', 'position' => 1],
            ['name' => 'Review', 'color' => '#d97706', 'position' => 2],
            ['name' => 'Done', 'color' => '#16a34a', 'position' => 3],
        ]);

        foreach ($defaults as $column) {
            DB::table('task_columns')->insert([
                'id' => (string) Str::uuid(),
                'name' => $column['name'],
                'color' => $column['color'],
                'position' => $column['position'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_activity_logs');
        Schema::dropIfExists('task_ticket_attachments');
        Schema::dropIfExists('task_ticket_comments');
        Schema::dropIfExists('task_ticket_links');
        Schema::dropIfExists('task_ticket_assignees');
        Schema::dropIfExists('task_tickets');
        Schema::dropIfExists('task_columns');
    }
};
