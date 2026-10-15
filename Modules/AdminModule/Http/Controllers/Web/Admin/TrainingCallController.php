<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\AdminModule\Entities\PeopleDepartment;
use Modules\AdminModule\Entities\TrainingCallAssignment;
use Modules\AdminModule\Entities\TrainingCallSession;
use Modules\AdminModule\Entities\TrainingCallTest;
use Modules\AdminModule\Support\CallSimulationCaller;
use Modules\AdminModule\Support\CallSimulationCatalog;
use Modules\AdminModule\Support\CallSimulationScorer;
use Modules\AdminModule\Support\CallSimulationSpeaker;
use Modules\AdminModule\Support\TrainingPeople;

class TrainingCallController extends Controller
{
    public function __construct(
        private readonly CallSimulationCaller $caller,
        private readonly CallSimulationScorer $scorer,
        private readonly CallSimulationSpeaker $speaker,
    ) {}

    public function index(): View
    {
        $assignments = TrainingCallAssignment::query()
            ->where('user_id', auth()->id())
            ->whereHas('test', fn ($query) => $query->where('status', 'published'))
            ->with(['test', 'sessions'])
            ->latest()
            ->get();

        return view('adminmodule::admin.training.calls.index', [
            'assignments' => $assignments,
        ]);
    }

    public function manage(): View
    {
        $this->ensureManager();

        return view('adminmodule::admin.training.calls.manage.index', [
            'tests' => TrainingCallTest::query()->withCount('assignments')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->ensureManager();

        return view('adminmodule::admin.training.calls.manage.create', [
            'test' => new TrainingCallTest([
                'pass_mark' => 70,
                'difficulty' => 'standard',
                'tone' => 'warm',
                'speed' => 'normal',
                'default_language' => 'en',
                'starter' => 'caller',
                'max_minutes' => 8,
                'scenarios' => [],
                'skills' => [],
            ]),
            'scenarios' => CallSimulationCatalog::scenarios(),
            'skills' => CallSimulationCatalog::skills(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManager();
        $test = TrainingCallTest::query()->create($this->validatedTest($request) + [
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);
        Toastr::success($test->name.' was created.');

        return redirect()->route('admin.training.calls.manage');
    }

    public function edit(TrainingCallTest $test): View
    {
        $this->ensureManager();
        return view('adminmodule::admin.training.calls.manage.edit', [
            'test' => $test,
            'scenarios' => CallSimulationCatalog::scenarios(),
            'skills' => CallSimulationCatalog::skills(),
        ]);
    }

    public function update(Request $request, TrainingCallTest $test): RedirectResponse
    {
        $this->ensureManager();
        $data = $this->validatedTest($request);
        $test->update($data);
        Toastr::success('Call test saved.');

        return redirect()->route('admin.training.calls.manage.edit', $test);
    }

    public function publish(TrainingCallTest $test): RedirectResponse
    {
        $this->ensureManager();
        if ($this->selectedIds($test->scenarios, 'scenario') === [] || $this->selectedIds($test->skills, 'skill') === []) {
            Toastr::error('Choose at least one scenario and one skill before publishing.');

            return redirect()->route('admin.training.calls.manage.edit', $test);
        }

        $test->status = 'published';
        $test->save();
        Toastr::success($test->name.' is published. You can assign it.');

        return redirect()->route('admin.training.calls.manage.edit', $test);
    }

    public function assignForm(TrainingCallTest $test): View
    {
        $this->ensureManager();
        $assignments = $test->assignments()
            ->with(['user.peopleProfile', 'sessions'])
            ->latest()
            ->get();

        return view('adminmodule::admin.training.calls.manage.assign', [
            'test' => $test,
            'assignments' => $assignments,
            'staff' => TrainingPeople::staff(),
            'departments' => TrainingPeople::departments(),
        ]);
    }

    public function assign(Request $request, TrainingCallTest $test): RedirectResponse
    {
        $this->ensureManager();
        if (! $test->isPublished()) {
            Toastr::error('Publish the call test before assigning it.');

            return back();
        }

        $data = $request->validate([
            'scope' => 'required|in:employee,department',
            'user_id' => 'required_if:scope,employee|nullable|uuid',
            'department_id' => 'required_if:scope,department|nullable|uuid',
            'due_at' => 'required|date|after_or_equal:today',
        ]);

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
            [$created, $updated] = $this->giveTest($test, $people, $data['due_at'], 'department', $department->name);
            $due = \Illuminate\Support\Carbon::parse($data['due_at'])->format('j M Y');
            if ($created === 0) {
                Toastr::success('Everyone in '.$department->name.' already has this test. Deadline is now '.$due.'.');
            } else {
                $message = 'Assigned '.$test->name.' to '.$created.' '.($created === 1 ? 'person' : 'people').' in '.$department->name.'. Deadline '.$due.'.';
                if ($updated > 0) {
                    $message .= ' '.$updated.' already had it and kept the new deadline.';
                }
                Toastr::success($message);
            }

            return redirect()->route('admin.training.calls.manage.assign', $test);
        }

        $person = TrainingPeople::staff()->firstWhere('id', $data['user_id']);
        if (! $person) {
            Toastr::error('Choose an employee.');

            return back();
        }

        [$created] = $this->giveTest($test, collect([$person]), $data['due_at'], 'employee', null);
        $due = \Illuminate\Support\Carbon::parse($data['due_at'])->format('j M Y');
        Toastr::success(
            $created === 0
                ? TrainingPeople::name($person).' already has this test. Deadline is now '.$due.'.'
                : TrainingPeople::name($person).' is assigned '.$test->name.'. Deadline '.$due.'.'
        );

        return redirect()->route('admin.training.calls.manage.assign', $test);
    }

    public function show(TrainingCallAssignment $assignment): View
    {
        $this->ensureReader($assignment);
        $assignment->load(['test', 'sessions', 'user']);

        return view('adminmodule::admin.training.calls.show', [
            'assignment' => $assignment,
            'rollup' => $assignment->rollup(),
            'scenarios' => CallSimulationCatalog::scenarios(),
            'skills' => CallSimulationCatalog::skills(),
        ]);
    }

    public function room(Request $request, TrainingCallAssignment $assignment): View|RedirectResponse
    {
        $this->ensureAssignee($assignment);
        $assignment->load('test');
        $scenarioId = $this->scenarioFromRequest($request, $assignment);
        if ($scenarioId === null) {
            Toastr::error('This test has no scenario left to call.');

            return redirect()->route('admin.training.calls.show', $assignment);
        }

        $scenario = CallSimulationCatalog::scenario($scenarioId);

        return view('adminmodule::admin.training.calls.room', [
            'assignment' => $assignment,
            'scenarioId' => $scenarioId,
            'scenario' => $scenario,
        ]);
    }

    public function start(Request $request, TrainingCallAssignment $assignment): JsonResponse
    {
        $this->ensureAssignee($assignment);
        $assignment->load('test');
        $scenarioId = $this->scenarioFromRequest($request, $assignment);
        if ($scenarioId === null) {
            return response()->json(['message' => 'Choose a scenario on this test.'], 422);
        }

        $scenario = CallSimulationCatalog::scenario($scenarioId);
        $open = $this->openSession($assignment, $scenarioId);
        if ($request->boolean('fresh') && $open) {
            $open->ended_at = now();
            $open->summary = 'Left before the call was scored.';
            $open->save();
            $open = null;
        }

        $created = false;
        $lang = 'en';
        $source = 'script';
        $tone = (string) ($assignment->test->tone ?: 'warm');
        $starter = (string) ($assignment->test->starter ?: 'caller');
        $spoken = null;
        if (! $open) {
            if ($starter === 'employee') {
                $open = $assignment->sessions()->create([
                    'scenario' => $scenarioId,
                    'transcript' => [],
                    'started_at' => now(),
                ]);
                $created = true;
                $source = 'ai';
            } else {
                $spoken = $this->caller->opening(
                    $scenarioId,
                    $this->listenLang($request),
                    $this->scenarioPrompt($assignment->test, $scenarioId),
                    (string) ($assignment->test->difficulty ?: 'standard'),
                    $tone
                );
                $lang = $spoken['lang'];
                $source = $spoken['source'];
                $open = $assignment->sessions()->create([
                    'scenario' => $scenarioId,
                    'transcript' => [[
                        'role' => 'customer',
                        'text' => $spoken['line'],
                    ]],
                    'started_at' => now(),
                ]);
                $created = true;
            }
        }

        $speak = $created ? ($open->transcript[0]['text'] ?? null) : null;

        return response()->json([
            'session_id' => $open->id,
            'speak' => $speak,
            'lang' => $lang,
            'source' => $source,
            'reason' => $created ? ($spoken['reason'] ?? null) : null,
            'transcript' => $open->transcript ?? [],
            'caller_name' => $scenario['caller_name'],
            'caller_role' => $scenario['caller_role'],
        ]);
    }

    public function turn(Request $request, TrainingCallAssignment $assignment): JsonResponse
    {
        $this->ensureAssignee($assignment);
        $data = $request->validate([
            'session_id' => 'required|uuid',
            'text' => 'required|string|max:2000',
        ]);
        $session = $this->ownedSession($assignment, $data['session_id']);
        if ($session->ended_at) {
            return response()->json(['message' => 'This call has already ended.'], 422);
        }

        $text = trim(preg_replace('/\s+/', ' ', $data['text']) ?? '');
        if ($text === '') {
            return response()->json(['message' => 'Say something before the caller replies.'], 422);
        }

        $assignment->loadMissing('test');
        $transcript = $session->transcript ?? [];
        $transcript[] = ['role' => 'employee', 'text' => $text];
        $reply = $this->caller->reply(
            $session->scenario,
            $transcript,
            $this->selectedIds($assignment->test->skills ?? [], 'skill'),
            '',
            $this->listenLang($request),
            $this->scenarioPrompt($assignment->test, (string) $session->scenario),
            (string) ($assignment->test->difficulty ?: 'standard'),
            (string) ($assignment->test->tone ?: 'warm')
        );
        $transcript[] = ['role' => 'customer', 'text' => $reply['line']];
        $session->transcript = $transcript;
        $session->save();

        return response()->json([
            'line' => $reply['line'],
            'done' => $reply['done'],
            'lang' => $reply['lang'],
            'source' => $reply['source'],
            'reason' => $reply['reason'] ?? null,
            'transcript' => $transcript,
        ]);
    }

    public function voice(Request $request, TrainingCallAssignment $assignment): JsonResponse
    {
        $this->ensureAssignee($assignment);
        $data = $request->validate([
            'line' => 'required|string|max:800',
            'lang' => 'nullable|in:en,hi,ur',
            'scenario' => 'required|string|max:80',
        ]);
        $assignment->loadMissing('test');
        $scenarioId = $data['scenario'];
        abort_unless(in_array($scenarioId, $this->selectedIds($assignment->test->scenarios ?? [], 'scenario'), true), 404);
        $scenario = CallSimulationCatalog::scenario($scenarioId);
        abort_unless($scenario, 404);
        $clip = $this->speaker->clip(
            $data['line'],
            $data['lang'] ?? 'en',
            (string) ($scenario['caller_name'] ?? ''),
            $scenarioId,
            (string) ($assignment->test->difficulty ?: 'standard'),
            (string) ($assignment->test->tone ?: 'warm')
        );

        return response()->json([
            'audio' => $clip['audio'] ?? null,
            'mime' => $clip['mime'] ?? null,
        ]);
    }

    public function finish(Request $request, TrainingCallAssignment $assignment): JsonResponse
    {
        $this->ensureAssignee($assignment);
        $data = $request->validate([
            'session_id' => 'required|uuid',
        ]);
        $session = $this->ownedSession($assignment, $data['session_id']);
        $assignment->loadMissing('test');

        if ($session->overall === null) {
            $result = $this->scorer->score(
                $session->scenario,
                $this->selectedIds($assignment->test->skills ?? [], 'skill'),
                $session->transcript ?? [],
                (int) $assignment->test->pass_mark,
                '',
                $this->scenarioPrompt($assignment->test, (string) $session->scenario),
                (string) ($assignment->test->difficulty ?: 'standard'),
                is_array($assignment->test->skill_marks) ? $assignment->test->skill_marks : []
            );
            $session->fill([
                'scores' => $result['scores'],
                'overall' => $result['overall'],
                'passed' => $result['passed'],
                'summary' => $result['summary'],
                'scored_by' => $result['scored_by'],
                'ended_at' => now(),
            ]);
            $session->save();
        }

        return response()->json([
            'url' => route('admin.training.calls.result', [$assignment, 'scenario' => $session->scenario, 'session' => $session->id]),
        ]);
    }

    public function result(Request $request, TrainingCallAssignment $assignment): View
    {
        $this->ensureReader($assignment);
        $assignment->load(['test', 'sessions', 'user']);
        $rollup = $assignment->rollup();
        $session = null;
        $sessionId = $request->query('session');
        if (is_string($sessionId)) {
            $session = $assignment->sessions->firstWhere('id', $sessionId);
        }
        if (! $session) {
            $scenarioId = $request->query('scenario');
            if (is_string($scenarioId)) {
                $session = $rollup['sessions'][$scenarioId] ?? null;
            }
        }

        return view('adminmodule::admin.training.calls.result', [
            'assignment' => $assignment,
            'rollup' => $rollup,
            'session' => $session,
            'scenarios' => CallSimulationCatalog::scenarios(),
            'skills' => CallSimulationCatalog::skills(),
        ]);
    }

    private function listenLang(Request $request): string
    {
        $lang = $request->input('listen', 'en');

        return in_array($lang, ['en', 'hi', 'ur'], true) ? $lang : 'en';
    }

    private function ensureManager(): void
    {
        abort_unless(is_super_admin(), 403);
    }

    private function ensureAssignee(TrainingCallAssignment $assignment): void
    {
        abort_unless($assignment->user_id === auth()->id(), 403);
        abort_unless($assignment->test()->where('status', 'published')->exists(), 404);
    }

    private function ensureReader(TrainingCallAssignment $assignment): void
    {
        abort_unless($assignment->user_id === auth()->id() || is_super_admin(), 403);
    }

    private function scenarioFromRequest(Request $request, TrainingCallAssignment $assignment): ?string
    {
        $allowed = $this->selectedIds($assignment->test->scenarios ?? [], 'scenario');
        $requested = $request->input('scenario', $request->query('scenario'));
        if (is_string($requested) && in_array($requested, $allowed, true)) {
            return $requested;
        }

        return $assignment->rollup()['next'] ?? ($allowed[0] ?? null);
    }

    private function openSession(TrainingCallAssignment $assignment, string $scenarioId): ?TrainingCallSession
    {
        return $assignment->sessions()
            ->where('scenario', $scenarioId)
            ->whereNull('ended_at')
            ->latest()
            ->first();
    }

    private function ownedSession(TrainingCallAssignment $assignment, string $sessionId): TrainingCallSession
    {
        $session = $assignment->sessions()->whereKey($sessionId)->first();
        abort_unless($session, 404);

        return $session;
    }

    /**
     * @return array{name: string, prompt: ?string, difficulty: string, scenarios: list<string>, skills: list<string>, pass_mark: int}
     */
    private function validatedTest(Request $request): array
    {
        $scenarioIds = array_keys(CallSimulationCatalog::scenarios());
        $skillIds = array_keys(CallSimulationCatalog::skills());
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'prompt' => 'nullable|string|max:1500',
            'scenario_prompts' => 'nullable|array',
            'scenario_prompts.*' => 'nullable|string|max:1500',
            'difficulty' => 'required|in:foundation,standard,advanced',
            'tone' => 'required|in:calm,warm,firm,upset',
            'speed' => 'required|in:slow,normal,fast',
            'default_language' => 'required|in:en,hi,ur',
            'starter' => 'required|in:caller,employee',
            'max_minutes' => 'required|integer|min:1|max:60',
            'pass_mark' => 'required|integer|min:1|max:65535',
            'scenarios' => 'required|array|size:1',
            'scenarios.*' => ['string', Rule::in($scenarioIds)],
            'skills' => 'required|array|min:1',
            'skills.*' => ['string', Rule::in($skillIds)],
            'skill_marks' => 'nullable|array',
            'skill_marks.*' => 'nullable|integer|min:1|max:100',
        ]);

        $scenarios = array_values(array_unique($data['scenarios']));
        $prompts = [];
        $rawPrompts = is_array($data['scenario_prompts'] ?? null) ? $data['scenario_prompts'] : [];
        foreach ($scenarios as $scenarioId) {
            $text = trim((string) ($rawPrompts[$scenarioId] ?? ''));
            if ($text !== '') {
                $prompts[$scenarioId] = mb_substr($text, 0, 1500);
            }
        }

        $skills = array_values(array_unique($data['skills']));
        $marks = [];
        foreach ($skills as $skillId) {
            $marks[$skillId] = max(1, min(100, (int) ($data['skill_marks'][$skillId] ?? 10)));
        }
        $total = array_sum($marks);
        if ((int) $data['pass_mark'] > $total) {
            throw ValidationException::withMessages([
                'pass_mark' => 'Pass mark must be '.$total.' or less, the total of the skill marks.',
            ]);
        }

        return [
            'name' => $data['name'],
            'prompt' => filled($data['prompt'] ?? null) ? trim($data['prompt']) : null,
            'scenario_prompts' => $prompts === [] ? null : $prompts,
            'difficulty' => $data['difficulty'],
            'tone' => $data['tone'],
            'speed' => $data['speed'],
            'default_language' => $data['default_language'],
            'starter' => $data['starter'],
            'max_minutes' => (int) $data['max_minutes'],
            'pass_mark' => (int) $data['pass_mark'],
            'scenarios' => $scenarios,
            'skills' => $skills,
            'skill_marks' => $marks,
        ];
    }

    private function scenarioPrompt(TrainingCallTest $test, string $scenarioId): string
    {
        $map = is_array($test->scenario_prompts) ? $test->scenario_prompts : [];
        $own = trim((string) ($map[$scenarioId] ?? ''));

        return $own !== '' ? $own : trim((string) $test->prompt);
    }

    /**
     * @param  list<mixed>  $ids
     * @return list<string>
     */
    private function selectedIds(array $ids, string $kind): array
    {
        $clean = [];
        foreach ($ids as $id) {
            if (! is_string($id)) {
                continue;
            }
            $known = $kind === 'scenario'
                ? CallSimulationCatalog::scenario($id)
                : CallSimulationCatalog::skill($id);
            if ($known) {
                $clean[] = $id;
            }
        }

        return array_values(array_unique($clean));
    }

    /**
     * @param  Collection<int, \Modules\UserManagement\Entities\User>  $people
     * @return array{0: int, 1: int}
     */
    private function giveTest(TrainingCallTest $test, Collection $people, string $dueAt, string $scope, ?string $departmentName): array
    {
        $created = 0;
        $updated = 0;
        foreach ($people as $person) {
            $assignment = TrainingCallAssignment::query()->firstOrNew([
                'training_call_test_id' => $test->id,
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
                $created++;

                continue;
            }
            $assignment->due_at = $dueAt;
            $assignment->save();
            $updated++;
        }

        return [$created, $updated];
    }
}
