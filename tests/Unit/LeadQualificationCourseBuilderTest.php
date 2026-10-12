<?php

namespace Tests\Unit;

use Modules\AdminModule\Support\LeadQualificationCourseBuilder;
use Modules\AdminModule\Support\LeadQualificationExam;
use Modules\AdminModule\Support\LeadQualificationTrainingGuide;
use Tests\TestCase;

class LeadQualificationCourseBuilderTest extends TestCase
{
    public function test_every_lesson_has_described_exam_questions(): void
    {
        $sections = (new LeadQualificationCourseBuilder())->build();
        $this->assertCount(13, $sections);

        $single = 0;
        $multiple = 0;
        $questionCount = 0;
        $bodies = '';
        $prompts = [];
        $empty = [];

        foreach ($sections as $section) {
            $this->assertNotSame('', $section['title']);
            foreach ($section['lessons'] as $lesson) {
                $bodies .= ' '.$lesson['body'];
                $count = count($lesson['questions']);
                if ($count === 0) {
                    $empty[] = $section['title'].' || '.$lesson['title'];
                    continue;
                }
                $this->assertLessThanOrEqual(12, $count, $lesson['title']);
                foreach ($lesson['questions'] as $question) {
                    $questionCount++;
                    $options = $question['options'];
                    $this->assertGreaterThanOrEqual(2, count($options), $question['prompt']);
                    $this->assertLessThanOrEqual(4, count($options), $question['prompt']);
                    $this->assertArrayHasKey($question['correct'], $options);
                    $this->assertSame(count($options), count(array_unique($options)), $question['prompt']);
                    $this->assertStringNotContainsString('Which of these is required', $question['prompt']);
                    $this->assertStringContainsString('?', $question['prompt']);
                    $this->assertGreaterThan(20, mb_strlen($question['prompt']));
                    $this->assertNotSame('', $question['explanation']);
                    $prompts[] = $question['prompt'];
                    if (count($options) === 2) {
                        $single++;
                    }
                    if (count($options) >= 3) {
                        $multiple++;
                    }
                }
            }
        }

        $this->assertSame([], $empty, "Lessons with no exam questions:\n".implode("\n", $empty));
        $this->assertGreaterThan(20, $single);
        $this->assertGreaterThan(20, $multiple);
        $this->assertGreaterThan(200, $questionCount);

        foreach (LeadQualificationExam::all() as $section => $lessons) {
            foreach (array_keys($lessons) as $title) {
                $this->assertTrue($this->lessonExists($sections, $section, $title), $section.' || '.$title);
            }
        }

        $this->assertTrue(collect($prompts)->contains(fn ($prompt) => str_contains($prompt, 'business phone rings')));

        foreach ($this->imageFiles() as $file) {
            $this->assertStringContainsString($file, $bodies, $file);
        }
    }

    /**
     * @param  array<int, array{title: string, lessons: array<int, array<string, mixed>>}>  $sections
     */
    private function lessonExists(array $sections, string $sectionTitle, string $lessonTitle): bool
    {
        foreach ($sections as $section) {
            if ($section['title'] !== $sectionTitle) {
                continue;
            }
            foreach ($section['lessons'] as $lesson) {
                if ($lesson['title'] === $lessonTitle || str_starts_with($lesson['title'], $lessonTitle.' — questions')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function imageFiles(): array
    {
        $wanted = [];
        $walk = function (mixed $node) use (&$walk, &$wanted): void {
            if (is_string($node) && str_ends_with($node, '.png')) {
                $wanted[$node] = $node;
            } elseif (is_array($node)) {
                foreach ($node as $value) {
                    $walk($value);
                }
            }
        };
        $walk(LeadQualificationTrainingGuide::slides());
        $present = [];
        foreach ($wanted as $file) {
            if (is_file(public_path('assets/admin-module/process-guide/training/examples/'.$file))) {
                $present[] = $file;
            }
        }

        return $present;
    }
}
