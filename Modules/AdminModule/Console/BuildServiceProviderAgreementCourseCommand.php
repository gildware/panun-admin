<?php

namespace Modules\AdminModule\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\AdminModule\Entities\TrainingAssignment;
use Modules\AdminModule\Entities\TrainingProgram;
use Modules\AdminModule\Support\ServiceProviderAgreementCourseBuilder;
use Modules\AdminModule\Support\TrainingPeople;

class BuildServiceProviderAgreementCourseCommand extends Command
{
    protected $signature = 'training:build-sp-agreement-course
                            {--force : Allow rebuild even when APP_ENV is production}
                            {--publish : Publish the course after building}
                            {--assign-all : Assign the published course to every active staff member}
                            {--due= : Deadline date Y-m-d when using --assign-all (default: next Monday)}';

    protected $description = 'Create or replace the Service Provider Form & Agreement training course';

    public function handle(ServiceProviderAgreementCourseBuilder $builder): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('This command does not run on production. Re-run with --force after review if you intend to rebuild there.');

            return 1;
        }

        $sections = $builder->build();
        $lessonCount = 0;
        $questionCount = 0;
        foreach ($sections as $section) {
            foreach ($section['lessons'] as $lesson) {
                $lessonCount++;
                $questionCount += count($lesson['questions']);
                if ($lesson['questions'] === [] || count($lesson['questions']) > 12) {
                    $this->error('Lesson “'.$lesson['title'].'” has '.count($lesson['questions']).' questions.');

                    return 1;
                }
            }
        }

        $program = TrainingProgram::query()->where('name', ServiceProviderAgreementCourseBuilder::NAME)->first();
        $hadAssignments = $program && $program->assignments()->exists();
        $description = 'Registration form, onboarding questionnaire, and Service Provider Agreement. Answers are not shown after a fail — retake until every question is correct.';

        DB::transaction(function () use ($sections, &$program, $description) {
            if ($program) {
                $program->sections()->delete();
                $program->questions()->delete();
                $program->update([
                    'status' => 'draft',
                    'difficulty' => 'standard',
                    'short_description' => $description,
                    'question_pass_mark' => 1,
                    'ai_pass_mark' => 1,
                ]);
            } else {
                $program = TrainingProgram::query()->create([
                    'name' => ServiceProviderAgreementCourseBuilder::NAME,
                    'status' => 'draft',
                    'difficulty' => 'standard',
                    'version' => 1,
                    'short_description' => $description,
                    'question_pass_mark' => 1,
                    'ai_pass_mark' => 1,
                ]);
            }

            foreach ($sections as $sectionIndex => $section) {
                $sectionRow = $program->sections()->create([
                    'title' => $section['title'],
                    'sort_order' => $sectionIndex + 1,
                ]);
                foreach ($section['lessons'] as $lessonIndex => $lesson) {
                    $lessonRow = $sectionRow->lessons()->create([
                        'title' => $lesson['title'],
                        'type' => 'text',
                        'body' => $lesson['body'],
                        'pass_mark' => count($lesson['questions']),
                        'sort_order' => $lessonIndex + 1,
                    ]);
                    foreach ($lesson['questions'] as $questionIndex => $question) {
                        $lessonRow->questions()->create([
                            'training_program_id' => $program->id,
                            'prompt' => $question['prompt'],
                            'options' => $question['options'],
                            'correct_index' => $question['correct'],
                            'explanation' => $question['explanation'],
                            'sort_order' => $questionIndex + 1,
                        ]);
                    }
                }
            }
        });

        $program = $program->fresh();
        $this->info('Course “'.ServiceProviderAgreementCourseBuilder::NAME.'” built: '.count($sections).' sections, '.$lessonCount.' lessons, '.$questionCount.' questions.');
        if ($hadAssignments) {
            $this->warn('Existing assignments kept; lesson progress should be restarted for a clean attempt.');
        }

        if ($this->option('publish')) {
            $program->status = 'published';
            $program->save();
            $this->info('Published.');
        }

        if ($this->option('assign-all')) {
            if (! $program->isPublished()) {
                $this->error('Publish the course first (pass --publish).');

                return 1;
            }
            $due = $this->option('due') ?: now()->next('Monday')->toDateString();
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) {
                $this->error('Use --due=YYYY-MM-DD');

                return 1;
            }
            [$created, $updated] = $this->assignAll($program, $due);
            $this->info('Assigned to all staff. New: '.$created.'. Updated deadline: '.$updated.'. Due '.$due.'.');
        } elseif (! $this->option('publish')) {
            $this->info('Left as draft. Use --publish --assign-all --due=YYYY-MM-DD when ready.');
        }

        return 0;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function assignAll(TrainingProgram $program, string $dueAt): array
    {
        $staff = TrainingPeople::staff();
        $assigner = $staff->firstWhere('email', 'admin@panunkaergar.com')?->id
            ?? auth()->id()
            ?? $staff->first()?->id;
        $created = 0;
        $updated = 0;

        foreach ($staff as $person) {
            $assignment = TrainingAssignment::query()->firstOrNew([
                'training_program_id' => $program->id,
                'user_id' => $person->id,
            ]);

            if (! $assignment->exists) {
                $assignment->fill([
                    'assigned_by' => $assigner,
                    'due_at' => $dueAt,
                    'assigned_scope' => 'employee',
                    'department_name' => null,
                ]);
                $assignment->save();
                $assignment->progress()->create([]);
                $created++;

                continue;
            }

            $assignment->due_at = $dueAt;
            $assignment->save();
            $assignment->lessonProgress()->delete();
            if ($assignment->progress) {
                $assignment->progress->update([
                    'started_at' => null,
                    'content_done_at' => null,
                    'quiz_correct' => null,
                    'quiz_answers' => null,
                    'ai_answer' => null,
                    'ai_hits' => null,
                    'ai_score' => null,
                    'certified_at' => null,
                ]);
            } else {
                $assignment->progress()->create([]);
            }
            $updated++;
        }

        return [$created, $updated];
    }
}
