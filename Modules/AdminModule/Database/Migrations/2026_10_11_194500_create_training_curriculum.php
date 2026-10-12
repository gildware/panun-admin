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
        Schema::table('training_programs', function (Blueprint $table) {
            $table->string('short_description', 500)->nullable()->after('name');
        });

        Schema::create('training_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_program_id')->constrained('training_programs')->cascadeOnDelete();
            $table->string('title', 160);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('training_lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_section_id')->constrained('training_sections')->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('type', 20);
            $table->text('body')->nullable();
            $table->unsignedTinyInteger('pass_mark')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('training_questions', function (Blueprint $table) {
            $table->foreignUuid('training_lesson_id')->nullable()->after('training_program_id')->constrained('training_lessons')->cascadeOnDelete();
        });

        Schema::table('training_marking_points', function (Blueprint $table) {
            $table->foreignUuid('training_lesson_id')->nullable()->after('training_program_id')->constrained('training_lessons')->cascadeOnDelete();
        });

        Schema::create('training_lesson_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_assignment_id')->constrained('training_assignments')->cascadeOnDelete();
            $table->foreignUuid('training_lesson_id')->constrained('training_lessons')->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('quiz_correct')->nullable();
            $table->json('quiz_answers')->nullable();
            $table->text('ai_answer')->nullable();
            $table->json('ai_hits')->nullable();
            $table->unsignedTinyInteger('ai_score')->nullable();
            $table->timestamps();
            $table->unique(['training_assignment_id', 'training_lesson_id'], 'training_lesson_progress_unique');
        });

        $this->backfillExistingCourses();
    }

    public function down(): void
    {
        Schema::dropIfExists('training_lesson_progress');
        Schema::table('training_marking_points', function (Blueprint $table) {
            $table->dropConstrainedForeignId('training_lesson_id');
        });
        Schema::table('training_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('training_lesson_id');
        });
        Schema::dropIfExists('training_lessons');
        Schema::dropIfExists('training_sections');
        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropColumn('short_description');
        });
    }

    private function backfillExistingCourses(): void
    {
        $programs = DB::table('training_programs')->get();
        foreach ($programs as $program) {
            $hasLessonMaterial = filled($program->text_body) || filled($program->audio_script) || filled($program->video_note) || filled($program->ai_situation);
            $questionIds = DB::table('training_questions')->where('training_program_id', $program->id)->orderBy('sort_order')->pluck('id');
            $pointIds = DB::table('training_marking_points')->where('training_program_id', $program->id)->orderBy('sort_order')->pluck('id');
            if (! $hasLessonMaterial && $questionIds->isEmpty() && $pointIds->isEmpty()) {
                continue;
            }

            $now = now();
            $sectionId = (string) Str::uuid();
            DB::table('training_sections')->insert([
                'id' => $sectionId,
                'training_program_id' => $program->id,
                'title' => 'Course',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $order = 1;
            $insert = function (string $type, string $title, ?string $body, int $passMark) use ($sectionId, &$order, $now) {
                $id = (string) Str::uuid();
                DB::table('training_lessons')->insert([
                    'id' => $id,
                    'training_section_id' => $sectionId,
                    'title' => $title,
                    'type' => $type,
                    'body' => $body,
                    'pass_mark' => $passMark,
                    'sort_order' => $order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $order++;

                return $id;
            };

            if (filled($program->text_body) || filled($program->text_title)) {
                $insert('text', $program->text_title ?: 'Text', $program->text_body, 1);
            }
            if (filled($program->audio_script)) {
                $insert('audio', 'Audio', $program->audio_script, 1);
            }
            if (filled($program->video_note)) {
                $insert('video', 'Video', $program->video_note, 1);
            }
            if ($questionIds->isNotEmpty()) {
                $lessonId = $insert('quiz', 'Questions', null, (int) $program->question_pass_mark);
                DB::table('training_questions')->whereIn('id', $questionIds)->update(['training_lesson_id' => $lessonId]);
            }
            if ($pointIds->isNotEmpty() || filled($program->ai_situation)) {
                $lessonId = $insert('ai', 'AI test', $program->ai_situation, (int) $program->ai_pass_mark);
                if ($pointIds->isNotEmpty()) {
                    DB::table('training_marking_points')->whereIn('id', $pointIds)->update(['training_lesson_id' => $lessonId]);
                }
            }
        }
    }
};
