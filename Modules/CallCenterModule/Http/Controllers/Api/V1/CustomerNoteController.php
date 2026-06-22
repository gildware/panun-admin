<?php

namespace Modules\CallCenterModule\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\CallCenterModule\Entities\Call;
use Modules\CallCenterModule\Entities\Note;
use Modules\CallCenterModule\Services\CustomerProfileService;
use Modules\CallCenterModule\Services\IdempotencyService;
use Modules\CallCenterModule\Support\RespondsWithCallCenterApi;

class CustomerNoteController extends Controller
{
    use RespondsWithCallCenterApi;

    public function __construct(
        private readonly CustomerProfileService $profiles,
        private readonly IdempotencyService $idempotency,
    ) {
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $endpoint = "POST /customers/{$id}/notes";
        $replay = $this->idempotency->replayIfExists($request, $endpoint);
        if ($replay) {
            return response()->json($replay['body'], $replay['status']);
        }

        $profile = $this->profiles->getProfileByNumericId($id);
        if (!$profile) {
            return $this->notFound('customer_not_found', 'Customer not found');
        }

        $validator = Validator::make($request->all(), [
            'external_id' => 'required|uuid',
            'call_external_id' => 'nullable|uuid',
            'agent_external_id' => 'nullable|string|max:255',
            'agent_name' => 'nullable|string|max:255',
            'content' => 'required|string',
            'note_type' => 'nullable|in:call_note,general,complaint,follow_up',
            'is_pinned' => 'nullable|boolean',
            'created_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $existing = Note::query()->where('external_id', $request->input('external_id'))->first();
        if ($existing) {
            $payload = [
                'id' => $existing->id,
                'external_id' => $existing->external_id,
                'customer_id' => $profile->id,
                'content' => $existing->content,
                'created_at' => ($existing->noted_at ?? $existing->created_at)?->utc()->toIso8601String(),
            ];

            return $this->created($payload);
        }

        $callId = null;
        if ($request->filled('call_external_id')) {
            $callId = Call::query()->where('external_id', $request->input('call_external_id'))->value('id');
        }

        $notedAt = $request->input('created_at') ? now()->parse($request->input('created_at')) : now();

        $note = Note::query()->create([
            'external_id' => $request->input('external_id'),
            'customer_profile_id' => $profile->id,
            'user_id' => $profile->user_id,
            'call_id' => $callId,
            'call_external_id' => $request->input('call_external_id'),
            'agent_external_id' => $request->input('agent_external_id'),
            'agent_name' => $request->input('agent_name'),
            'content' => $request->input('content'),
            'note_type' => $request->input('note_type', 'call_note'),
            'is_pinned' => (bool) $request->input('is_pinned', false),
            'noted_at' => $notedAt,
        ]);

        $payload = [
            'id' => $note->id,
            'external_id' => $note->external_id,
            'customer_id' => $profile->id,
            'content' => $note->content,
            'created_at' => $notedAt->utc()->toIso8601String(),
        ];

        $this->idempotency->store(trim((string) $request->header('Idempotency-Key')), $endpoint, 201, $payload);

        return $this->created($payload);
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $profile = $this->profiles->getProfileByNumericId($id);
        if (!$profile) {
            return $this->notFound('customer_not_found', 'Customer not found');
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $notes = Note::query()
            ->where('customer_profile_id', $profile->id)
            ->orderByDesc('noted_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $data = $notes->getCollection()->map(fn (Note $note) => [
            'id' => $note->id,
            'external_id' => $note->external_id,
            'agent_name' => $note->agent_name,
            'content' => $note->content,
            'note_type' => $note->note_type,
            'is_pinned' => $note->is_pinned,
            'created_at' => ($note->noted_at ?? $note->created_at)?->utc()->toIso8601String(),
        ])->values()->all();

        return $this->ok([
            'data' => $data,
            'meta' => $this->paginatedMeta($notes->total(), $page, $perPage),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $note = Note::query()->find($id);
        if (!$note) {
            return $this->notFound('note_not_found', 'Note not found');
        }

        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string',
            'is_pinned' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $note->fill($request->only(['content', 'is_pinned']))->save();

        return $this->ok([
            'id' => $note->id,
            'external_id' => $note->external_id,
            'content' => $note->content,
            'is_pinned' => $note->is_pinned,
            'created_at' => ($note->noted_at ?? $note->created_at)?->utc()->toIso8601String(),
        ]);
    }
}
