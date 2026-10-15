<?php

namespace Modules\AdminModule\Support;

use Modules\AI\AIProviders\GeminiProvider;
use Modules\AdminModule\Entities\TrainingLesson;
use RuntimeException;

class TrainingQuestionGenerator
{
    /**
     * @return list<array{prompt: string, options: list<string>, correct: int, explanation: ?string}>
     */
    public function fromLesson(TrainingLesson $lesson): array
    {
        $source = trim(html_entity_decode(strip_tags((string) $lesson->body)));
        $source = preg_replace('/\s+/', ' ', $source) ?? $source;
        if (mb_strlen($source) < 40) {
            throw new RuntimeException('Add the lesson text first. Questions are generated from that text.');
        }

        $gemini = new GeminiProvider();
        $raw = $gemini->generate($this->prompt($lesson->title, mb_substr($source, 0, 12000)));

        return $this->parse($raw);
    }

    private function prompt(string $title, string $source): string
    {
        return <<<PROMPT
Write 3 multiple-choice questions for an internal employee training lesson.
Use only facts that appear in the lesson text. Do not add outside knowledge.
Each question has exactly 3 options. One option is correct.
Return only a JSON array, with no markdown, in this shape:
[{"prompt":"...","options":["...","...","..."],"correct":0,"explanation":"..."}]
correct is the index of the right option, starting at 0.

Lesson title: {$title}
Lesson text: {$source}
PROMPT;
    }

    /**
     * @return list<array{prompt: string, options: list<string>, correct: int, explanation: ?string}>
     */
    private function parse(string $raw): array
    {
        $json = trim($raw);
        $json = preg_replace('/^```(?:json)?|```$/m', '', $json) ?? $json;
        if (! preg_match('/\[.*\]/s', $json, $match)) {
            throw new RuntimeException('The generator did not return questions. Try again.');
        }

        $rows = json_decode($match[0], true);
        if (! is_array($rows) || $rows === []) {
            throw new RuntimeException('The generator did not return questions. Try again.');
        }

        $questions = [];
        foreach (array_slice($rows, 0, 5) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $prompt = trim((string) ($row['prompt'] ?? ''));
            $options = array_values(array_filter(array_map(
                fn ($option) => trim((string) $option),
                is_array($row['options'] ?? null) ? $row['options'] : []
            ), fn ($option) => $option !== ''));
            if ($prompt === '' || count($options) < 2) {
                continue;
            }
            $options = array_slice($options, 0, 4);
            $correct = min(max((int) ($row['correct'] ?? 0), 0), count($options) - 1);
            $questions[] = [
                'prompt' => mb_substr($prompt, 0, 1000),
                'options' => $options,
                'correct' => $correct,
                'explanation' => filled($row['explanation'] ?? null) ? mb_substr(trim((string) $row['explanation']), 0, 500) : null,
            ];
        }

        if ($questions === []) {
            throw new RuntimeException('The generator did not return questions. Try again.');
        }

        return $questions;
    }
}
