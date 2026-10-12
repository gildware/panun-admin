<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\AdminModule\Entities\TrainingAssignment;
use Modules\AdminModule\Entities\TrainingLesson;
use Modules\AdminModule\Entities\TrainingLessonProgress;
use Modules\AdminModule\Entities\TrainingQuestion;
use Modules\AdminModule\Support\TrainingAnswerScorer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TrainingController extends Controller
{
    public function index(): View
    {
        $assignments = TrainingAssignment::query()
            ->where('user_id', auth()->id())
            ->whereHas('program', fn ($query) => $query->where('status', 'published'))
            ->withCount(['lessonProgress as lessons_done_count' => fn ($query) => $query->whereNotNull('completed_at')])
            ->with(['program' => fn ($query) => $query->withCount(['questions', 'markingPoints', 'lessons']), 'progress'])
            ->latest()
            ->get();

        return view('adminmodule::admin.training.index', [
            'assignments' => $assignments,
        ]);
    }

    public function media(TrainingLesson $lesson): BinaryFileResponse
    {
        $lesson->loadMissing('section.program');
        $program = $lesson->section?->program;
        abort_unless($program && $lesson->media_kind === 'upload' && filled($lesson->media_path), 404);
        if (! is_super_admin()) {
            $assigned = TrainingAssignment::query()
                ->where('user_id', auth()->id())
                ->where('training_program_id', $program->id)
                ->exists();
            abort_unless($assigned && $program->isPublished(), 403);
        }
        abort_unless(Storage::disk('local')->exists($lesson->media_path), 404);
        $path = Storage::disk('local')->path($lesson->media_path);
        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
        ]);
    }

    public function restart(TrainingAssignment $assignment): RedirectResponse
    {
        $assignment = $this->own($assignment);
        $this->archiveAttempt($assignment);
        $assignment->lessonProgress()->delete();
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
        Toastr::success('Training restarted. The earlier attempt is saved. You are back at the first lesson.');

        return redirect()->route('admin.training.content', $assignment);
    }

    public function attempts(TrainingAssignment $assignment): View
    {
        if (is_super_admin() && $assignment->user_id !== auth()->id()) {
            $assignment->load(['program', 'user', 'progress']);
            abort_unless($assignment->program, 404);
            if (! $assignment->progress) {
                $assignment->progress()->create([]);
                $assignment->load('progress');
            }
        } else {
            $assignment = $this->own($assignment);
        }
        $assignment->load('user');

        return view('adminmodule::admin.training.attempts', [
            'assignment' => $assignment,
            'program' => $assignment->program,
            'progress' => $assignment->progress,
            'attempts' => $assignment->attempts()->orderByDesc('attempt_number')->get(),
        ]);
    }

    public function content(Request $request, TrainingAssignment $assignment): View|RedirectResponse
    {
        $assignment = $this->own($assignment);
        $assignment->progress->markStarted();
        $program = $assignment->program;
        if ($program->sections()->exists()) {
            return $this->player($request, $assignment);
        }

        return view('adminmodule::admin.training.content', [
            'assignment' => $assignment,
            'program' => $program,
        ]);
    }

    public function markContentSeen(TrainingAssignment $assignment, TrainingLesson $lesson): RedirectResponse
    {
        $assignment = $this->own($assignment);
        $this->ownedLesson($assignment, $lesson);
        abort_unless(in_array($lesson->type, ['text', 'audio', 'video'], true), 404);
        $row = $this->lessonRow($assignment, $lesson);
        if (! $row->content_seen_at) {
            $row->content_seen_at = now();
            $row->save();
        }
        if ($lesson->questions()->exists()) {
            return redirect()->route('admin.training.content', [$assignment, 'lesson' => $lesson->id, 'step' => 'questions']);
        }
        if (! $row->completed_at) {
            $row->completed_at = now();
            $row->save();
            $this->syncCourse($assignment);
            Toastr::success('Lesson finished.');
        }
        if ($assignment->progress->certified_at) {
            return redirect()->route('admin.training.certificate', $assignment);
        }

        return redirect()->route('admin.training.content', [$assignment, 'lesson' => $this->nextLessonId($assignment, $lesson)]);
    }

    public function completeLesson(TrainingAssignment $assignment, TrainingLesson $lesson): RedirectResponse
    {
        $assignment = $this->own($assignment);
        $this->ownedLesson($assignment, $lesson);
        abort_unless(in_array($lesson->type, ['text', 'audio', 'video'], true), 404);
        if ($lesson->questions()->exists()) {
            Toastr::error('Answer the questions to finish this lesson.');

            return redirect()->route('admin.training.content', [$assignment, 'lesson' => $lesson->id]);
        }
        if (in_array($lesson->type, ['audio', 'video'], true) && ! $this->lessonRow($assignment, $lesson)->content_seen_at) {
            Toastr::error('Play this lesson through to the end first.');

            return redirect()->route('admin.training.content', [$assignment, 'lesson' => $lesson->id, 'step' => 'watch']);
        }
        $this->lessonRow($assignment, $lesson)->update(['completed_at' => now()]);
        $this->syncCourse($assignment);
        Toastr::success('Lesson finished.');
        if ($assignment->progress->certified_at) {
            return redirect()->route('admin.training.certificate', $assignment);
        }

        return redirect()->route('admin.training.content', [$assignment, 'lesson' => $this->nextLessonId($assignment, $lesson)]);
    }

    public function submitLessonQuiz(Request $request, TrainingAssignment $assignment, TrainingLesson $lesson): RedirectResponse
    {
        $assignment = $this->own($assignment);
        $this->ownedLesson($assignment, $lesson);
        abort_unless(in_array($lesson->type, ['text', 'audio', 'video', 'quiz'], true), 404);
        if ($lesson->questions->isEmpty()) {
            Toastr::error('This lesson has no questions yet.');

            return back();
        }
        $row = $this->lessonRow($assignment, $lesson);
        if ($row->completed_at) {
            return redirect()->route('admin.training.content', [$assignment, 'lesson' => $lesson->id, 'step' => 'result']);
        }
        if (in_array($lesson->type, ['text', 'audio', 'video'], true) && ! $row->content_seen_at) {
            Toastr::error($lesson->type === 'text' ? 'Read the lesson before the questions.' : 'Play this lesson through to the end before the questions.');

            return redirect()->route('admin.training.content', [$assignment, 'lesson' => $lesson->id, 'step' => 'watch']);
        }
        $questions = $lesson->questions;
        $answers = [];
        $correct = 0;
        foreach ($questions as $question) {
            $picked = $request->input('q.'.$question->id);
            if ($picked === null || $picked === '') {
                Toastr::error('Answer every question.');

                return back();
            }
            $picked = (int) $picked;
            $answers[$question->id] = $picked;
            if ($picked === (int) $question->correct_index) {
                $correct++;
            }
        }
        $passed = $questions->isEmpty() || $correct >= (int) $lesson->pass_mark;
        $row->quiz_answers = $answers;
        $row->quiz_correct = $correct;
        $row->completed_at = $passed ? now() : null;
        $row->save();
        $assignment->progress->quiz_correct = $correct;
        $assignment->progress->quiz_answers = $answers;
        $assignment->progress->save();
        $this->syncCourse($assignment);
        Toastr::success($passed ? 'Questions passed.' : 'Below the pass mark.');

        return redirect()->route('admin.training.content', [$assignment, 'lesson' => $lesson->id, 'step' => 'result']);
    }

    public function submitLessonAi(Request $request, TrainingAssignment $assignment, TrainingLesson $lesson): RedirectResponse
    {
        $assignment = $this->own($assignment);
        $this->ownedLesson($assignment, $lesson);
        abort_unless($lesson->type === 'ai', 404);
        if ($this->lessonRow($assignment, $lesson)->completed_at) {
            return redirect()->route('admin.training.content', [$assignment, 'lesson' => $lesson->id]);
        }
        $answer = trim((string) $request->input('answer'));
        if ($answer === '') {
            Toastr::error('Write an answer first.');

            return back();
        }
        $points = $lesson->markingPoints;
        $hits = TrainingAnswerScorer::score($answer, $points);
        $score = collect($hits)->where('pass', true)->count();
        $passed = $points->isEmpty() || $score >= (int) $lesson->pass_mark;
        $row = $this->lessonRow($assignment, $lesson);
        $row->ai_answer = $answer;
        $row->ai_hits = $hits;
        $row->ai_score = $score;
        $row->completed_at = $passed ? now() : null;
        $row->save();
        $assignment->progress->ai_answer = $answer;
        $assignment->progress->ai_hits = $hits;
        $assignment->progress->ai_score = $score;
        $assignment->progress->save();
        $this->syncCourse($assignment);

        return redirect()->route('admin.training.content', [$assignment, 'lesson' => $lesson->id, 'step' => 'result']);
    }

    public function completeContent(TrainingAssignment $assignment): RedirectResponse
    {
        $assignment = $this->own($assignment);
        $progress = $assignment->progress;
        $progress->markStarted();
        if (! $progress->content_done_at) {
            $progress->content_done_at = now();
            $progress->save();
        }
        $progress->refreshCertificate();
        Toastr::success('Content finished.');

        return redirect()->route($this->nextRoute($assignment), $assignment);
    }

    public function questions(TrainingAssignment $assignment): View|RedirectResponse
    {
        $assignment = $this->own($assignment);
        if (! $assignment->progress->content_done_at) {
            Toastr::error('Finish the content first.');

            return redirect()->route('admin.training.content', $assignment);
        }

        return view('adminmodule::admin.training.questions', [
            'assignment' => $assignment,
            'program' => $assignment->program,
            'progress' => $assignment->progress,
        ]);
    }

    public function submitQuestions(Request $request, TrainingAssignment $assignment): RedirectResponse
    {
        $assignment = $this->own($assignment);
        $progress = $assignment->progress;
        if (! $progress->content_done_at) {
            return redirect()->route('admin.training.content', $assignment);
        }

        $questions = $assignment->program->questions;
        if ($questions->isEmpty()) {
            return redirect()->route($this->nextRoute($assignment), $assignment);
        }

        $answers = [];
        $correct = 0;
        foreach ($questions as $question) {
            $picked = $request->input('q.'.$question->id);
            if ($picked === null || $picked === '') {
                Toastr::error('Answer every question.');

                return back();
            }
            $picked = (int) $picked;
            $answers[$question->id] = $picked;
            if ($picked === (int) $question->correct_index) {
                $correct++;
            }
        }

        $progress->quiz_answers = $answers;
        $progress->quiz_correct = $correct;
        $progress->save();
        $progress->refreshCertificate();
        $passed = $correct >= (int) $assignment->program->question_pass_mark;
        Toastr::success($passed ? 'Questions passed.' : 'Below the pass mark. You can submit again.');

        return redirect()->route('admin.training.questions', $assignment);
    }

    public function ai(TrainingAssignment $assignment): View|RedirectResponse
    {
        $assignment = $this->own($assignment);
        $snap = $assignment->progress->snapshot();
        if (! $assignment->progress->content_done_at || $snap['quiz_blocking']) {
            Toastr::error('Finish the earlier steps first.');

            return redirect()->route($this->nextRoute($assignment), $assignment);
        }

        return view('adminmodule::admin.training.ai', [
            'assignment' => $assignment,
            'program' => $assignment->program,
            'progress' => $assignment->progress,
        ]);
    }

    public function submitAi(Request $request, TrainingAssignment $assignment): RedirectResponse
    {
        $assignment = $this->own($assignment);
        $snap = $assignment->progress->snapshot();
        if (! $assignment->progress->content_done_at || $snap['quiz_blocking']) {
            return redirect()->route($this->nextRoute($assignment), $assignment);
        }

        $answer = trim((string) $request->input('answer'));
        if ($answer === '') {
            Toastr::error('Write an answer first.');

            return back();
        }

        $points = $assignment->program->markingPoints;
        $hits = TrainingAnswerScorer::score($answer, $points);
        $score = collect($hits)->where('pass', true)->count();
        $progress = $assignment->progress;
        $progress->ai_answer = $answer;
        $progress->ai_hits = $hits;
        $progress->ai_score = $score;
        $progress->save();
        $progress->refreshCertificate();
        $passed = $points->isEmpty() || $score >= (int) $assignment->program->ai_pass_mark;
        Toastr::success($passed ? 'AI test passed.' : 'Below the AI pass mark. You can submit again.');

        return redirect()->route('admin.training.ai', $assignment);
    }

    public function certificate(TrainingAssignment $assignment): View
    {
        $assignment = $this->own($assignment);
        $assignment->progress->refreshCertificate();
        $assignment->load('progress');

        return view('adminmodule::admin.training.certificate', [
            'assignment' => $assignment,
            'program' => $assignment->program,
            'progress' => $assignment->progress,
            'snap' => $assignment->progress->snapshot(),
        ]);
    }

    private function archiveAttempt(TrainingAssignment $assignment): void
    {
        $progress = $assignment->progress;
        $assignment->loadMissing('program.sections.lessons');
        $order = $assignment->program->sections->flatMap->lessons->pluck('id')->values()->flip();
        $rows = $assignment->lessonProgress()->with(['lesson.questions', 'lesson.markingPoints'])->get()
            ->sortBy(fn (TrainingLessonProgress $row) => $order[$row->training_lesson_id] ?? 999)
            ->values();
        $number = ((int) $progress->restart_count) + 1;
        $assignment->attempts()->create([
            'attempt_number' => $number,
            'started_at' => $progress->started_at,
            'content_done_at' => $progress->content_done_at,
            'certified_at' => $progress->certified_at,
            'restarted_at' => now(),
            'quiz_correct' => $progress->quiz_correct,
            'quiz_answers' => $progress->quiz_answers,
            'ai_answer' => $progress->ai_answer,
            'ai_hits' => $progress->ai_hits,
            'ai_score' => $progress->ai_score,
            'lessons' => $rows->map(function (TrainingLessonProgress $row) {
                $lesson = $row->lesson;
                $answers = is_array($row->quiz_answers) ? $row->quiz_answers : [];

                return [
                    'lesson_id' => $row->training_lesson_id,
                    'title' => $lesson?->title,
                    'type' => $lesson?->type,
                    'pass_mark' => $lesson?->pass_mark,
                    'completed_at' => $row->completed_at?->toDateTimeString(),
                    'content_seen_at' => $row->content_seen_at?->toDateTimeString(),
                    'quiz_correct' => $row->quiz_correct,
                    'question_count' => $lesson?->questions->count() ?? 0,
                    'ai_answer' => $row->ai_answer,
                    'ai_score' => $row->ai_score,
                    'ai_hits' => $row->ai_hits,
                    'questions' => $lesson ? $lesson->questions->map(function (TrainingQuestion $question) use ($answers) {
                        $picked = array_key_exists($question->id, $answers) ? $answers[$question->id] : null;
                        $options = $question->options ?? [];

                        return [
                            'prompt' => $question->prompt,
                            'picked_text' => $picked === null ? null : ($options[(int) $picked] ?? null),
                            'correct' => $picked !== null && (int) $picked === (int) $question->correct_index,
                        ];
                    })->values()->all() : [],
                ];
            })->values()->all(),
        ]);
        $progress->restart_count = $number;
        $progress->save();
    }

    private function own(TrainingAssignment $assignment): TrainingAssignment
    {
        abort_unless($assignment->user_id === auth()->id(), 404);
        abort_unless($assignment->program && $assignment->program->isPublished(), 404);
        $assignment->load(['program.questions', 'program.markingPoints', 'progress']);
        if (! $assignment->progress) {
            $assignment->progress()->create([]);
            $assignment->load('progress');
        }

        return $assignment;
    }

    private function nextRoute(TrainingAssignment $assignment): string
    {
        $assignment->load(['program.questions', 'program.markingPoints', 'progress']);
        $snap = $assignment->progress->snapshot();
        if (! $assignment->progress->content_done_at) {
            return 'admin.training.content';
        }
        if ($snap['quiz_blocking']) {
            return 'admin.training.questions';
        }
        if ($snap['ai_blocking']) {
            return 'admin.training.ai';
        }

        return 'admin.training.certificate';
    }

    private function player(Request $request, TrainingAssignment $assignment): View|RedirectResponse
    {
        $assignment->load(['program.sections.lessons.questions', 'program.sections.lessons.markingPoints', 'lessonProgress']);
        $lessons = $assignment->program->sections->flatMap->lessons->values();
        $doneIds = $assignment->lessonProgress->whereNotNull('completed_at')->pluck('training_lesson_id');
        $certified = (bool) $assignment->progress->certified_at;
        $current = $lessons->firstWhere('id', (string) $request->query('lesson'));
        if (! $current) {
            $current = $lessons->first(fn (TrainingLesson $lesson) => ! $certified && ! $doneIds->contains($lesson->id)) ?? $lessons->first();
        }
        if ($current && ! $certified && ! $this->lessonUnlocked($lessons, $doneIds, $current)) {
            $current = $lessons->first(fn (TrainingLesson $lesson) => ! $doneIds->contains($lesson->id)) ?? $lessons->first();
        }
        $requested = (string) $request->query('lesson');
        if ($requested !== '' && $current && $requested !== $current->id) {
            return redirect()->route('admin.training.content', [$assignment, 'lesson' => $current->id]);
        }

        $lessonProgress = $current ? $assignment->lessonProgress->firstWhere('training_lesson_id', $current->id) : null;
        $stage = $current ? $this->lessonStage($request, $current, $lessonProgress, $certified) : null;
        $requestedStep = (string) $request->query('step');
        $retakeOpensQuestion = $requestedStep === 'retake' && in_array($stage, ['ai', 'questions'], true);
        if ($current && $requestedStep !== '' && $requestedStep !== $stage && ! $retakeOpensQuestion) {
            return redirect()->route('admin.training.content', [$assignment, 'lesson' => $current->id, 'step' => $stage]);
        }

        return view('adminmodule::admin.training.player', [
            'assignment' => $assignment,
            'program' => $assignment->program,
            'lessons' => $lessons,
            'doneIds' => $doneIds,
            'certified' => $certified,
            'current' => $current,
            'lessonProgress' => $lessonProgress,
            'stage' => $stage,
            'nextLessonId' => $current ? $this->nextLessonId($assignment, $current) : null,
        ]);
    }

    private function lessonStage(Request $request, TrainingLesson $lesson, ?TrainingLessonProgress $row, bool $certified): string
    {
        $step = (string) $request->query('step');
        if ($lesson->type === 'ai') {
            $passed = $certified || (bool) ($row?->completed_at);
            $attempted = is_array($row?->ai_hits);
            if (! $passed && $step === 'retake') {
                return 'ai';
            }
            if ($passed || $attempted) {
                return 'result';
            }

            return 'ai';
        }
        $passed = $certified || (bool) ($row?->completed_at);
        $hasQuestions = $lesson->questions->isNotEmpty();
        if (! $passed && $step === 'retake' && $hasQuestions) {
            return 'questions';
        }
        $seen = (bool) ($row?->content_seen_at);
        $attempted = is_array($row?->quiz_answers) && $row->quiz_answers !== [];

        if ($lesson->type === 'quiz') {
            if ($step === 'questions') {
                return 'questions';
            }
            if ($passed || $attempted) {
                return 'result';
            }

            return 'questions';
        }
        if ($step === 'result' && ($passed || $attempted)) {
            return 'result';
        }
        if ($step === 'questions' && $hasQuestions && ($seen || $passed)) {
            return 'questions';
        }

        return 'watch';
    }

    private function orderedLessons(TrainingAssignment $assignment)
    {
        $assignment->loadMissing('program.sections.lessons');

        return $assignment->program->sections->flatMap->lessons->values();
    }

    private function lessonUnlocked($lessons, $doneIds, TrainingLesson $current): bool
    {
        foreach ($lessons as $lesson) {
            if ($lesson->id === $current->id) {
                return true;
            }
            if (! $doneIds->contains($lesson->id)) {
                return false;
            }
        }

        return false;
    }

    private function ownedLesson(TrainingAssignment $assignment, TrainingLesson $lesson): void
    {
        $lesson->loadMissing('section');
        abort_unless($lesson->section && $lesson->section->training_program_id === $assignment->training_program_id, 404);
        $lessons = $this->orderedLessons($assignment);
        $doneIds = $assignment->lessonProgress()->whereNotNull('completed_at')->pluck('training_lesson_id');
        if ($assignment->progress->certified_at || $this->lessonUnlocked($lessons, $doneIds, $lesson)) {
            return;
        }
        abort(404);
    }

    private function lessonRow(TrainingAssignment $assignment, TrainingLesson $lesson): TrainingLessonProgress
    {
        return TrainingLessonProgress::query()->firstOrCreate([
            'training_assignment_id' => $assignment->id,
            'training_lesson_id' => $lesson->id,
        ]);
    }

    private function syncCourse(TrainingAssignment $assignment): void
    {
        $lessons = $this->orderedLessons($assignment);
        $doneIds = TrainingLessonProgress::query()
            ->where('training_assignment_id', $assignment->id)
            ->whereNotNull('completed_at')
            ->pluck('training_lesson_id');
        if ($lessons->isEmpty() || $lessons->contains(fn (TrainingLesson $lesson) => ! $doneIds->contains($lesson->id))) {
            return;
        }
        $progress = $assignment->progress;
        $progress->content_done_at = $progress->content_done_at ?? now();
        $progress->certified_at = $progress->certified_at ?? now();
        $progress->save();
    }

    private function nextLessonId(TrainingAssignment $assignment, TrainingLesson $lesson): string
    {
        $lessons = $this->orderedLessons($assignment);
        $index = $lessons->search(fn (TrainingLesson $row) => $row->id === $lesson->id);
        $next = $lessons->get($index + 1);

        return $next?->id ?? $lesson->id;
    }
}
