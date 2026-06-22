<?php

namespace Modules\CallCenterModule\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\CallCenterModule\Entities\CustomerProfile;
use Modules\CallCenterModule\Entities\Task;
use Modules\CallCenterModule\Services\IdempotencyService;
use Modules\CallCenterModule\Support\RespondsWithCallCenterApi;
use Modules\CallCenterModule\Entities\Call;

class TaskController extends Controller
{
    use RespondsWithCallCenterApi;

    public function __construct(private readonly IdempotencyService $idempotency)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $endpoint = 'POST /tasks';
        $replay = $this->idempotency->replayIfExists($request, $endpoint);
        if ($replay) {
            return response()->json($replay['body'], $replay['status']);
        }

        $validator = Validator::make($request->all(), [
            'external_id' => 'required|uuid',
            'customer_id' => 'nullable|integer',
            'call_external_id' => 'nullable|uuid',
            'assigned_agent_external_id' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'nullable|date',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'status' => 'nullable|in:open,in_progress,completed,cancelled',
            'source' => 'nullable|string|max:32',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $existing = Task::query()->where('external_id', $request->input('external_id'))->first();
        if ($existing) {
            return $this->created($this->transformTaskCreated($existing));
        }

        $profile = null;
        $userId = null;
        if ($request->filled('customer_id')) {
            $profile = CustomerProfile::query()->find($request->input('customer_id'));
            $userId = $profile?->user_id;
        }

        $callId = null;
        if ($request->filled('call_external_id')) {
            $callId = Call::query()->where('external_id', $request->input('call_external_id'))->value('id');
        }

        $task = Task::query()->create([
            'external_id' => $request->input('external_id'),
            'customer_profile_id' => $profile?->id,
            'user_id' => $userId,
            'call_id' => $callId,
            'call_external_id' => $request->input('call_external_id'),
            'assigned_agent_external_id' => $request->input('assigned_agent_external_id'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'due_at' => $request->filled('due_at') ? now()->parse($request->input('due_at')) : null,
            'priority' => $request->input('priority', 'normal'),
            'status' => $request->input('status', 'open'),
            'source' => $request->input('source', 'call_center'),
        ]);

        $payload = $this->transformTaskCreated($task);
        $this->idempotency->store(trim((string) $request->header('Idempotency-Key')), $endpoint, 201, $payload);

        return $this->created($payload);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $task = Task::query()->with('customerProfile.user')->find($id);
        if (!$task) {
            return $this->notFound('task_not_found', 'Task not found');
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:open,in_progress,completed,cancelled',
            'completed_at' => 'nullable|date',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'nullable|date',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        if ($request->filled('status')) {
            $task->status = $request->input('status');
        }
        if ($request->filled('completed_at')) {
            $task->completed_at = now()->parse($request->input('completed_at'));
        } elseif ($request->input('status') === 'completed' && !$task->completed_at) {
            $task->completed_at = now();
        }
        if ($request->filled('title')) {
            $task->title = $request->input('title');
        }
        if ($request->has('description')) {
            $task->description = $request->input('description');
        }
        if ($request->filled('due_at')) {
            $task->due_at = now()->parse($request->input('due_at'));
        }
        if ($request->filled('priority')) {
            $task->priority = $request->input('priority');
        }

        $task->save();

        return $this->ok($this->transformTaskFull($task));
    }

    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $query = Task::query()->with('customerProfile.user')->orderByDesc('created_at');

        if ($request->filled('assigned_agent_external_id')) {
            $query->where('assigned_agent_external_id', $request->query('assigned_agent_external_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_profile_id', $request->query('customer_id'));
        }

        $tasks = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $tasks->getCollection()->map(fn (Task $task) => [
            'id' => $task->id,
            'external_id' => $task->external_id,
            'customer_id' => $task->customer_profile_id,
            'customer_name' => $task->customerProfile?->user
                ? trim($task->customerProfile->user->first_name . ' ' . $task->customerProfile->user->last_name)
                : null,
            'title' => $task->title,
            'due_at' => $task->due_at?->utc()->toIso8601String(),
            'priority' => $task->priority,
            'status' => $task->status,
        ])->values()->all();

        return $this->ok([
            'data' => $data,
            'meta' => $this->paginatedMeta($tasks->total(), $page, $perPage),
        ]);
    }

    private function transformTaskCreated(Task $task): array
    {
        return [
            'id' => $task->id,
            'external_id' => $task->external_id,
            'status' => $task->status,
            'created_at' => $task->created_at?->utc()->toIso8601String(),
        ];
    }

    private function transformTaskFull(Task $task): array
    {
        return array_merge($this->transformTaskCreated($task), [
            'customer_id' => $task->customer_profile_id,
            'title' => $task->title,
            'description' => $task->description,
            'due_at' => $task->due_at?->utc()->toIso8601String(),
            'priority' => $task->priority,
            'completed_at' => $task->completed_at?->utc()->toIso8601String(),
        ]);
    }
}
