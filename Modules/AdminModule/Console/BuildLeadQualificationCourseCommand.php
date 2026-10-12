<?php

namespace Modules\AdminModule\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\AdminModule\Entities\TrainingProgram;
use Modules\AdminModule\Support\LeadQualificationCourseBuilder;

class BuildLeadQualificationCourseCommand extends Command
{
    protected $signature = 'training:build-lead-course';

    protected $description = 'Create or replace the draft Lead management course from the lead qualification training';

    public function handle(LeadQualificationCourseBuilder $builder): int
    {
        if (app()->environment('production')) {
            $this->error('This command does not run on production.');

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

        $program = TrainingProgram::query()->where('name', LeadQualificationCourseBuilder::NAME)->first();
        $assigned = $program && $program->assignments()->exists();

        DB::transaction(function () use ($sections, $program) {
            if ($program) {
                $program->sections()->delete();
                $program->questions()->delete();
                $program->update([
                    'difficulty' => 'advanced',
                    'short_description' => 'The full lead qualification process. Every lesson has questions, and every question must be correct.',
                ]);
            } else {
                $program = TrainingProgram::query()->create([
                    'name' => LeadQualificationCourseBuilder::NAME,
                    'status' => 'draft',
                    'difficulty' => 'advanced',
                    'version' => 1,
                    'short_description' => 'The full lead qualification process. Every lesson has questions, and every question must be correct.',
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

        $this->info('Course “'.LeadQualificationCourseBuilder::NAME.'” is ready: '.count($sections).' sections, '.$lessonCount.' lessons, '.$questionCount.' questions.');
        if ($assigned) {
            $this->warn('It is assigned. Lesson progress was reset because the questions changed.');
        }

        return 0;
    }
}
