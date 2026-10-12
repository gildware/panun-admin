<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Entities\TrainingLesson;
use Modules\AdminModule\Entities\TrainingProgram;
use Modules\AdminModule\Entities\TrainingSection;
use Modules\AdminModule\Support\TrainingQuestionGenerator;
use RuntimeException;

class TrainingCurriculumController extends Controller
{
    public function show(Request $request, TrainingProgram $program): View
    {
        $this->ensureManager();
        $program->load(['sections.lessons.questions', 'sections.lessons.markingPoints']);

        return view('adminmodule::admin.training.manage.curriculum', [
            'program' => $program,
            'openLesson' => (string) $request->query('lesson', ''),
            'addSection' => (string) $request->query('add', ''),
            'renameSection' => (string) $request->query('rename', ''),
            'newSection' => $request->boolean('new'),
        ]);
    }

    public function storeSection(Request $request, TrainingProgram $program): RedirectResponse
    {
        $this->ensureManager();
        $data = $request->validate(['title' => 'required|string|max:160']);
        $program->sections()->create([
            'title' => $data['title'],
            'sort_order' => ((int) $program->sections()->max('sort_order')) + 1,
        ]);
        Toastr::success('Section added.');

        return redirect()->route('admin.training.manage.curriculum', $program);
    }

    public function updateSection(Request $request, TrainingProgram $program, TrainingSection $section): RedirectResponse
    {
        $this->ensureManager();
        $this->ownedSection($program, $section);
        $data = $request->validate(['title' => 'required|string|max:160']);
        $section->update(['title' => $data['title']]);
        Toastr::success('Section saved.');

        return redirect()->route('admin.training.manage.curriculum', $program);
    }

    public function moveSection(TrainingProgram $program, TrainingSection $section, string $direction): RedirectResponse
    {
        $this->ensureManager();
        $this->ownedSection($program, $section);
        $this->swapSort($program->sections()->orderBy('sort_order')->get()->values(), $section, $direction);

        return redirect()->route('admin.training.manage.curriculum', $program);
    }

    public function destroySection(TrainingProgram $program, TrainingSection $section): RedirectResponse
    {
        $this->ensureManager();
        $this->ownedSection($program, $section);
        $section->delete();
        Toastr::success('Section removed.');

        return redirect()->route('admin.training.manage.curriculum', $program);
    }

    public function storeLesson(Request $request, TrainingProgram $program, TrainingSection $section): RedirectResponse
    {
        $this->ensureManager();
        $this->ownedSection($program, $section);
        $data = $request->validate([
            'title' => 'required|string|max:160',
            'type' => 'required|in:'.implode(',', array_keys(TrainingLesson::TYPES)),
        ]);
        $lesson = $section->lessons()->create([
            'title' => $data['title'],
            'type' => $data['type'],
            'pass_mark' => 1,
            'sort_order' => ((int) $section->lessons()->max('sort_order')) + 1,
        ]);
        Toastr::success('Lesson added. Fill in its content.');

        return redirect()->route('admin.training.manage.curriculum', [$program, 'lesson' => $lesson->id]);
    }

    public function updateLesson(Request $request, TrainingProgram $program, TrainingLesson $lesson): RedirectResponse
    {
        $this->ensureManager();
        $this->ownedLesson($program, $lesson);
        $data = $request->validate([
            'title' => 'required|string|max:160',
            'type' => 'nullable|in:'.implode(',', array_keys(TrainingLesson::TYPES)),
            'body' => 'nullable|string|max:50000',
            'media_kind' => 'nullable|in:url,upload',
            'media_url' => 'nullable|string|max:500',
            'media_file' => 'nullable|file|max:51200',
            'pass_mark' => 'nullable|integer|min:1|max:20',
            'questions' => 'array',
            'questions.*.prompt' => 'required|string|max:1000',
            'questions.*.options' => 'required|array|min:2|max:4',
            'questions.*.options.*' => 'nullable|string|max:500',
            'questions.*.correct' => 'required|integer|min:0|max:3',
            'questions.*.explanation' => 'nullable|string|max:500',
            'points' => 'array',
            'points.*.label' => 'required|string|max:255',
            'points.*.keywords' => 'required|string|max:500',
            'new_question_prompt' => 'nullable|string|max:1000',
            'new_question_options' => 'array',
            'new_question_options.*' => 'nullable|string|max:500',
            'new_question_correct' => 'nullable|integer|min:0|max:3',
            'new_question_explanation' => 'nullable|string|max:500',
            'new_point_label' => 'nullable|string|max:255',
            'new_point_keywords' => 'nullable|string|max:500',
        ]);

        $media = $this->storeMedia($request, $lesson);
        if ($media instanceof RedirectResponse) {
            return $media;
        }

        $fields = [
            'title' => $data['title'],
            'pass_mark' => $data['pass_mark'] ?? $lesson->pass_mark,
            ...$media,
        ];
        if (filled($data['type'] ?? null)) {
            $fields['type'] = $data['type'];
        }
        if ($request->exists('body')) {
            $fields['body'] = $this->cleanBody($data['body'] ?? null);
        }
        $lesson->update($fields);
        $lesson->refresh();

        if (in_array($lesson->type, ['text', 'audio', 'video', 'quiz'], true)) {
            $this->saveQuestions($program, $lesson, $data);
        }
        if ($lesson->type === 'ai') {
            $this->savePoints($program, $lesson, $data);
        }

        Toastr::success('Lesson saved.');

        return redirect()->route('admin.training.manage.curriculum', [$program, 'lesson' => $lesson->id]);
    }

    public function generateQuestions(TrainingProgram $program, TrainingLesson $lesson): RedirectResponse
    {
        $this->ensureManager();
        $this->ownedLesson($program, $lesson);
        abort_unless(in_array($lesson->type, ['text', 'audio', 'video'], true), 404);

        try {
            $rows = (new TrainingQuestionGenerator())->fromLesson($lesson);
        } catch (RuntimeException $exception) {
            Toastr::error($this->generationError($exception->getMessage()));

            return redirect()->route('admin.training.manage.curriculum', [$program, 'lesson' => $lesson->id]);
        }

        $before = $lesson->questions()->count();
        foreach ($rows as $row) {
            $lesson->questions()->create([
                'training_program_id' => $program->id,
                'prompt' => $row['prompt'],
                'options' => $row['options'],
                'correct_index' => $row['correct'],
                'explanation' => $row['explanation'],
                'sort_order' => ((int) $lesson->questions()->max('sort_order')) + 1,
            ]);
        }
        if ($before === 0) {
            $lesson->update(['pass_mark' => count($rows)]);
        }
        Toastr::success(count($rows).' questions added from this lesson.');

        return redirect()->route('admin.training.manage.curriculum', [$program, 'lesson' => $lesson->id]);
    }

    public function destroyLesson(TrainingProgram $program, TrainingLesson $lesson): RedirectResponse
    {
        $this->ensureManager();
        $this->ownedLesson($program, $lesson);
        $lesson->delete();
        Toastr::success('Lesson removed.');

        return redirect()->route('admin.training.manage.curriculum', $program);
    }

    public function reorderSections(Request $request, TrainingProgram $program): JsonResponse
    {
        $this->ensureManager();
        $order = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'uuid',
        ])['order'];
        $existing = $program->sections()->pluck('id')->all();
        $given = $order;
        sort($existing);
        sort($given);
        abort_unless($existing === $given, 422);
        foreach ($order as $index => $id) {
            TrainingSection::query()->where('id', $id)->where('training_program_id', $program->id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['ok' => true]);
    }

    public function reorderLessons(Request $request, TrainingProgram $program, TrainingSection $section): JsonResponse
    {
        $this->ensureManager();
        $this->ownedSection($program, $section);
        $order = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'uuid',
        ])['order'];
        $existing = $section->lessons()->pluck('id')->all();
        $given = $order;
        sort($existing);
        sort($given);
        abort_unless($existing === $given, 422);
        foreach ($order as $index => $id) {
            TrainingLesson::query()->where('id', $id)->where('training_section_id', $section->id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['ok' => true]);
    }

    public function moveLesson(TrainingProgram $program, TrainingLesson $lesson, string $direction): RedirectResponse
    {
        $this->ensureManager();
        $this->ownedLesson($program, $lesson);
        $this->swapSort($lesson->section->lessons()->orderBy('sort_order')->get()->values(), $lesson, $direction);

        return redirect()->route('admin.training.manage.curriculum', $program);
    }

    private function swapSort($siblings, $row, string $direction): void
    {
        $index = $siblings->search(fn ($item) => $item->id === $row->id);
        $other = $siblings->get($direction === 'up' ? $index - 1 : $index + 1);
        if (! $other) {
            return;
        }
        $current = (int) $row->sort_order;
        $next = (int) $other->sort_order;
        if ($current === $next) {
            $next = $direction === 'up' ? $current - 1 : $current + 1;
        }
        $row->update(['sort_order' => $next]);
        $other->update(['sort_order' => $current]);
    }

    /**
     * @return array<string, ?string>|RedirectResponse
     */
    private function storeMedia(Request $request, TrainingLesson $lesson): array|RedirectResponse
    {
        if (! in_array($lesson->type, ['audio', 'video'], true)) {
            return [];
        }

        $kind = (string) $request->input('media_kind', $lesson->media_kind ?: 'url');
        if ($kind === 'url') {
            $url = trim((string) $request->input('media_url', ''));
            if ($url === '') {
                return [];
            }
            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                Toastr::error('Enter a full media link, including https://');

                return back()->withInput();
            }
            $lesson->deleteStoredMedia();

            return ['media_kind' => 'url', 'media_path' => $url];
        }

        if (! $request->hasFile('media_file')) {
            return [];
        }

        $mimes = $lesson->type === 'audio' ? 'mp3,wav,ogg,m4a,aac' : 'mp4,webm,mov,ogg';
        $request->validate([
            'media_file' => 'file|max:51200|mimes:'.$mimes,
        ]);
        $lesson->deleteStoredMedia();
        $path = $request->file('media_file')->store('training-media/'.$lesson->id, 'local');

        return ['media_kind' => 'upload', 'media_path' => $path];
    }

    private function generationError(string $message): string
    {
        if (str_contains($message, 'lesson text first')) {
            return $message;
        }
        if (str_contains($message, '402') || str_contains($message, 'RESOURCE_EXHAUSTED') || str_contains($message, 'credits')) {
            return 'Question generation is unavailable because the AI account has no credits left. Write the questions here, or try again after billing is updated.';
        }
        if (str_contains($message, 'API key') || str_contains($message, 'No active AI')) {
            return 'Question generation needs an AI key in settings. You can still write the questions here.';
        }

        return 'Question generation did not complete. Write the questions here, or try again.';
    }

    private function cleanBody(?string $body): ?string
    {
        if ($body === null) {
            return null;
        }
        $clean = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $body) ?? $body;

        return trim($clean) === '' ? null : $clean;
    }

    private function saveQuestions(TrainingProgram $program, TrainingLesson $lesson, array $data): void
    {
        foreach ($data['questions'] ?? [] as $id => $row) {
            $question = $lesson->questions()->whereKey($id)->first();
            if (! $question) {
                continue;
            }
            $options = array_values(array_filter(array_map('trim', $row['options'] ?? []), fn ($option) => $option !== ''));
            if (count($options) < 2) {
                continue;
            }
            $question->update([
                'prompt' => $row['prompt'],
                'options' => $options,
                'correct_index' => min((int) $row['correct'], count($options) - 1),
                'explanation' => $row['explanation'] ?? null,
            ]);
        }

        $newOptions = array_values(array_filter(array_map('trim', $data['new_question_options'] ?? []), fn ($option) => $option !== ''));
        $newPrompt = trim((string) ($data['new_question_prompt'] ?? ''));
        if ($newPrompt !== '' && count($newOptions) >= 2) {
            $lesson->questions()->create([
                'training_program_id' => $program->id,
                'prompt' => $newPrompt,
                'options' => $newOptions,
                'correct_index' => min((int) ($data['new_question_correct'] ?? 0), count($newOptions) - 1),
                'explanation' => $data['new_question_explanation'] ?? null,
                'sort_order' => ((int) $lesson->questions()->max('sort_order')) + 1,
            ]);
        }
    }

    private function savePoints(TrainingProgram $program, TrainingLesson $lesson, array $data): void
    {
        foreach ($data['points'] ?? [] as $id => $row) {
            $lesson->markingPoints()->whereKey($id)->update([
                'label' => $row['label'],
                'keywords' => $row['keywords'],
            ]);
        }

        $newLabel = trim((string) ($data['new_point_label'] ?? ''));
        $newKeywords = trim((string) ($data['new_point_keywords'] ?? ''));
        if ($newLabel !== '' && $newKeywords !== '') {
            $lesson->markingPoints()->create([
                'training_program_id' => $program->id,
                'label' => $newLabel,
                'keywords' => $newKeywords,
                'sort_order' => ((int) $lesson->markingPoints()->max('sort_order')) + 1,
            ]);
        }
    }

    private function ownedSection(TrainingProgram $program, TrainingSection $section): void
    {
        abort_unless($section->training_program_id === $program->id, 404);
    }

    private function ownedLesson(TrainingProgram $program, TrainingLesson $lesson): void
    {
        $lesson->loadMissing('section');
        abort_unless($lesson->section && $lesson->section->training_program_id === $program->id, 404);
    }

    private function ensureManager(): void
    {
        abort_unless(is_super_admin(), 403);
    }
}
