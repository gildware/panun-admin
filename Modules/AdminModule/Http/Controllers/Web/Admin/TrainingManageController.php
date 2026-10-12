<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Modules\AdminModule\Entities\PeopleDepartment;
use Modules\AdminModule\Entities\TrainingAssignment;
use Modules\AdminModule\Entities\TrainingMarkingPoint;
use Modules\AdminModule\Entities\TrainingProgram;
use Modules\AdminModule\Entities\TrainingQuestion;
use Modules\AdminModule\Support\TrainingPeople;

class TrainingManageController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureManager();
        $difficulty = $request->query('difficulty');
        if (! is_string($difficulty) || ! array_key_exists($difficulty, TrainingProgram::DIFFICULTIES)) {
            $difficulty = null;
        }

        $programs = TrainingProgram::query()
            ->withCount(['assignments', 'lessons', 'sections'])
            ->when($difficulty, fn ($query) => $query->where('difficulty', $difficulty))
            ->orderBy('name')
            ->get()
            ->sortBy(fn (TrainingProgram $program) => array_search($program->difficulty, array_keys(TrainingProgram::DIFFICULTIES), true))
            ->values();

        $counts = TrainingProgram::query()
            ->selectRaw('difficulty, COUNT(*) as total')
            ->groupBy('difficulty')
            ->pluck('total', 'difficulty');

        return view('adminmodule::admin.training.manage.index', [
            'programs' => $programs,
            'difficulty' => $difficulty,
            'counts' => $counts,
            'total' => (int) $counts->sum(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManager();
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'difficulty' => 'required|in:'.implode(',', array_keys(TrainingProgram::DIFFICULTIES)),
        ]);

        $program = TrainingProgram::query()->create([
            'name' => $data['name'],
            'difficulty' => $data['difficulty'],
            'status' => 'draft',
            'version' => 1,
            'created_by' => auth()->id(),
        ]);
        Toastr::success($program->name.' was created. Add the curriculum next.');

        return redirect()->route('admin.training.manage.curriculum', $program);
    }

    public function edit(TrainingProgram $program): View
    {
        $this->ensureManager();

        $program->loadCount(['lessons', 'sections', 'assignments']);

        return view('adminmodule::admin.training.manage.edit', [
            'program' => $program,
        ]);
    }

    public function update(Request $request, TrainingProgram $program): RedirectResponse
    {
        $this->ensureManager();
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'difficulty' => 'required|in:'.implode(',', array_keys(TrainingProgram::DIFFICULTIES)),
            'short_description' => 'nullable|string|max:500',
        ]);

        $program->update([
            'name' => $data['name'],
            'difficulty' => $data['difficulty'],
            'short_description' => $data['short_description'] ?? null,
        ]);

        Toastr::success('Course saved.');

        return redirect()->route('admin.training.manage.edit', $program);
    }

    public function publish(TrainingProgram $program): RedirectResponse
    {
        $this->ensureManager();
        $program->load('sections.lessons.questions', 'sections.lessons.markingPoints');
        $hasLesson = $program->sections->contains(fn ($section) => $section->lessons->isNotEmpty());
        if (! $hasLesson && ! filled($program->text_body)) {
            Toastr::error('Add at least one lesson before publishing.');

            return redirect()->route('admin.training.manage.curriculum', $program);
        }
        $missing = $program->sections
            ->flatMap->lessons
            ->reject(fn ($lesson) => $lesson->hasContent())
            ->pluck('title');
        if ($missing->isNotEmpty()) {
            Toastr::error('These lessons still need content: '.$missing->implode(', '));

            return redirect()->route('admin.training.manage.curriculum', $program);
        }

        $program->status = 'published';
        $program->save();
        Toastr::success($program->name.' is published. You can assign it.');

        return redirect()->route('admin.training.manage');
    }

    public function destroyQuestion(TrainingProgram $program, TrainingQuestion $question): RedirectResponse
    {
        $this->ensureManager();
        abort_unless($question->training_program_id === $program->id, 404);
        $lessonId = $question->training_lesson_id;
        $question->delete();
        Toastr::success('Question removed.');

        if ($lessonId) {
            return redirect()->route('admin.training.manage.curriculum', [$program, 'lesson' => $lessonId]);
        }

        return redirect()->route('admin.training.manage.edit', $program);
    }

    public function destroyPoint(TrainingProgram $program, TrainingMarkingPoint $point): RedirectResponse
    {
        $this->ensureManager();
        abort_unless($point->training_program_id === $program->id, 404);
        $lessonId = $point->training_lesson_id;
        $point->delete();
        Toastr::success('Marking point removed.');

        if ($lessonId) {
            return redirect()->route('admin.training.manage.curriculum', [$program, 'lesson' => $lessonId]);
        }

        return redirect()->route('admin.training.manage.edit', $program);
    }

    public function assignForm(Request $request): View
    {
        $this->ensureManager();
        $assignments = $this->assignmentRows();
        $programId = $request->query('program');
        if (! is_string($programId) || ! $assignments->contains('training_program_id', $programId)) {
            $programId = null;
        }

        $visible = $programId
            ? $assignments->where('training_program_id', $programId)->values()
            : $assignments;

        return view('adminmodule::admin.training.manage.assign', [
            'programs' => TrainingProgram::query()->where('status', 'published')->orderBy('name')->get(),
            'staff' => TrainingPeople::staff(),
            'departments' => TrainingPeople::departments(),
            'groups' => $this->statusGroups($visible),
            'filters' => $assignments->groupBy('training_program_id')->map(fn (Collection $rows) => $rows->first()->program)->filter(),
            'programId' => $programId,
            'totals' => $this->statusTotals($visible),
        ]);
    }

    public function assign(Request $request): RedirectResponse
    {
        $this->ensureManager();
        $data = $request->validate([
            'training_program_id' => 'required|uuid',
            'scope' => 'required|in:employee,department',
            'user_id' => 'required_if:scope,employee|nullable|uuid',
            'department_id' => 'required_if:scope,department|nullable|uuid',
            'due_at' => 'required|date|after_or_equal:today',
        ]);

        $program = TrainingProgram::query()->where('status', 'published')->find($data['training_program_id']);
        if (! $program) {
            Toastr::error('Publish the course before assigning it.');

            return back();
        }

        if ($data['scope'] === 'department') {
            $department = PeopleDepartment::query()->find($data['department_id']);
            if (! $department) {
                Toastr::error('Choose a department.');

                return back();
            }

            $people = TrainingPeople::inDepartment($department);
            if ($people->isEmpty()) {
                Toastr::error($department->name.' has no active employees.');

                return back();
            }

            [$created, $updated] = $this->giveCourse($program, $people, $data['due_at'], 'department', $department->name);
            $due = \Illuminate\Support\Carbon::parse($data['due_at'])->format('j M Y');
            if ($created === 0) {
                Toastr::success('Everyone in '.$department->name.' already has '.$program->name.'. Deadline is now '.$due.'.');
            } else {
                $message = 'Assigned '.$program->name.' to '.$created.' '.($created === 1 ? 'person' : 'people').' in '.$department->name.'. Deadline '.$due.'.';
                if ($updated > 0) {
                    $message .= ' '.$updated.' already had it; their deadline was updated.';
                }
                Toastr::success($message);
            }

            return redirect()->route('admin.training.assign', ['program' => $program->id]);
        }

        $user = TrainingPeople::staff()->firstWhere('id', $data['user_id']);
        if (! $user) {
            Toastr::error('Choose an employee.');

            return back();
        }

        [$created] = $this->giveCourse($program, collect([$user]), $data['due_at'], 'employee', null);
        $due = \Illuminate\Support\Carbon::parse($data['due_at'])->format('j M Y');
        if ($created === 0) {
            Toastr::success(TrainingPeople::name($user).' already has '.$program->name.'. Deadline is now '.$due.'.');
        } else {
            Toastr::success(TrainingPeople::name($user).' is assigned '.$program->name.'. Deadline '.$due.'.');
        }

        return redirect()->route('admin.training.assign', ['program' => $program->id]);
    }

    public function live(): View
    {
        $this->ensureManager();
        $assignments = TrainingAssignment::query()
            ->withCount(['lessonProgress as lessons_done_count' => fn ($query) => $query->whereNotNull('completed_at')])
            ->with(['program' => fn ($query) => $query->withCount(['questions', 'markingPoints', 'lessons']), 'user', 'progress'])
            ->latest()
            ->get();

        $live = $assignments->filter(fn (TrainingAssignment $assignment) => $assignment->progress && $assignment->progress->snapshot()['live']);
        $idle = $assignments->filter(function (TrainingAssignment $assignment) {
            if (! $assignment->progress) {
                return true;
            }
            $snap = $assignment->progress->snapshot();

            return ! $snap['live'] && $snap['where'] === 'Not started';
        });
        $finished = $assignments->filter(fn (TrainingAssignment $assignment) => $assignment->progress && $assignment->progress->snapshot()['where'] === 'Finished');

        return view('adminmodule::admin.training.manage.live', [
            'live' => $live,
            'idle' => $idle,
            'finished' => $finished,
        ]);
    }

    private function ensureManager(): void
    {
        abort_unless(is_super_admin(), 403);
    }

    /**
     * @param  Collection<int, \Modules\UserManagement\Entities\User>  $people
     * @return array{0: int, 1: int}
     */
    private function giveCourse(TrainingProgram $program, Collection $people, string $dueAt, string $scope, ?string $departmentName): array
    {
        $created = 0;
        $updated = 0;

        foreach ($people as $person) {
            $assignment = TrainingAssignment::query()->firstOrNew([
                'training_program_id' => $program->id,
                'user_id' => $person->id,
            ]);

            if (! $assignment->exists) {
                $assignment->fill([
                    'assigned_by' => auth()->id(),
                    'due_at' => $dueAt,
                    'assigned_scope' => $scope,
                    'department_name' => $departmentName,
                ]);
                $assignment->save();
                $assignment->progress()->create([]);
                $created++;

                continue;
            }

            $assignment->due_at = $dueAt;
            $assignment->save();
            $updated++;
        }

        return [$created, $updated];
    }

    /**
     * @return Collection<int, TrainingAssignment>
     */
    private function assignmentRows(): Collection
    {
        return TrainingAssignment::query()
            ->withCount(['lessonProgress as lessons_done_count' => fn ($query) => $query->whereNotNull('completed_at')])
            ->with([
                'program' => fn ($query) => $query->withCount(['questions', 'markingPoints', 'lessons']),
                'user.peopleProfile',
                'progress',
            ])
            ->latest()
            ->get();
    }

    /**
     * @param  Collection<int, TrainingAssignment>  $assignments
     * @return Collection<int, array<string, mixed>>
     */
    private function statusGroups(Collection $assignments): Collection
    {
        return $assignments
            ->groupBy('training_program_id')
            ->map(function (Collection $rows) {
                $people = $rows->map(fn (TrainingAssignment $assignment) => $this->personStatus($assignment))->values();

                return [
                    'program' => $rows->first()->program,
                    'people' => $people,
                    'totals' => $this->statusTotals($rows),
                ];
            })
            ->sortBy(fn (array $group) => $group['program']?->name ?? '')
            ->values();
    }

    /**
     * @param  Collection<int, TrainingAssignment>  $assignments
     * @return array{assigned: int, finished: int, in_progress: int, not_started: int, overdue: int, percent: int}
     */
    private function statusTotals(Collection $assignments): array
    {
        $finished = 0;
        $inProgress = 0;
        $notStarted = 0;
        $overdue = 0;

        foreach ($assignments as $assignment) {
            $status = $this->personStatus($assignment);
            if ($status['finished']) {
                $finished++;
            } elseif ($status['started']) {
                $inProgress++;
            } else {
                $notStarted++;
            }
            if ($status['overdue']) {
                $overdue++;
            }
        }

        $assigned = $assignments->count();

        return [
            'assigned' => $assigned,
            'finished' => $finished,
            'in_progress' => $inProgress,
            'not_started' => $notStarted,
            'overdue' => $overdue,
            'percent' => $assigned === 0 ? 0 : (int) round($finished / $assigned * 100),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function personStatus(TrainingAssignment $assignment): array
    {
        if ($assignment->relationLoaded('progress') && $assignment->progress) {
            $assignment->progress->setRelation('assignment', $assignment);
        }

        $snap = $assignment->progress?->snapshot() ?? [
            'where' => 'Not started',
            'message' => 'Not opened yet',
            'where_tone' => 'muted',
            'steps' => 0,
        ];
        $lessonTotal = (int) ($assignment->program->lessons_count ?? 0);
        $lessonDone = $assignment->progress?->certified_at ? $lessonTotal : (int) $assignment->lessons_done_count;
        $percent = $lessonTotal > 0
            ? (int) round(min($lessonDone, $lessonTotal) / $lessonTotal * 100)
            : (int) round(min((int) ($snap['steps'] ?? 0), 4) / 4 * 100);
        $finished = ($snap['where'] ?? '') === 'Finished';
        $started = $finished || ($snap['where'] ?? 'Not started') !== 'Not started' || $lessonDone > 0;
        $overdue = ! $finished
            && $assignment->due_at
            && $assignment->due_at->toDateString() < now()->toDateString();
        $finishedLate = $finished
            && $assignment->due_at
            && $assignment->progress?->certified_at
            && $assignment->progress->certified_at->toDateString() > $assignment->due_at->toDateString();

        return [
            'assignment' => $assignment,
            'name' => TrainingPeople::name($assignment->user),
            'department' => TrainingPeople::department($assignment->user),
            'where' => $overdue ? 'Past the deadline' : ($snap['message'] ?? 'Not opened yet'),
            'where_tone' => $overdue ? 'bad' : ($snap['where_tone'] ?? 'muted'),
            'lesson_done' => $lessonDone,
            'lesson_total' => $lessonTotal,
            'percent' => $finished ? 100 : $percent,
            'finished' => $finished,
            'started' => $started,
            'overdue' => $overdue,
            'finished_late' => $finishedLate,
        ];
    }
}
