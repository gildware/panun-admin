<?php

namespace Modules\CallCenterModule\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\CallCenterModule\Entities\Call;
use Modules\CallCenterModule\Entities\CustomerProfile;
use Modules\CallCenterModule\Services\CustomerProfileService;
use Modules\CallCenterModule\Services\IdempotencyService;
use Modules\CallCenterModule\Services\PhoneNormalizer;
use Modules\CallCenterModule\Support\RespondsWithCallCenterApi;
use Modules\CallCenterModule\Transformers\CallTransformer;

class CallController extends Controller
{
    use RespondsWithCallCenterApi;

    public function __construct(
        private readonly CustomerProfileService $profiles,
        private readonly PhoneNormalizer $phoneNormalizer,
        private readonly CallTransformer $callTransformer,
        private readonly IdempotencyService $idempotency,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $endpoint = 'POST /calls';
        $replay = $this->idempotency->replayIfExists($request, $endpoint);
        if ($replay) {
            return response()->json($replay['body'], $replay['status']);
        }

        $validator = Validator::make($request->all(), [
            'external_id' => 'required|uuid',
            'customer_id' => 'nullable|integer',
            'direction' => 'required|in:inbound,outbound',
            'status' => 'required|string|max:32',
            'from_number' => 'required|string|max:32',
            'to_number' => 'required|string|max:32',
            'agent_external_id' => 'nullable|string|max:255',
            'agent_name' => 'nullable|string|max:255',
            'asterisk_unique_id' => 'nullable|string|max:255',
            'started_at' => 'nullable|date',
            'tags' => 'nullable|array',
            'source' => 'nullable|string|max:32',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $existing = Call::query()->where('external_id', $request->input('external_id'))->first();
        if ($existing) {
            return $this->created($this->callTransformer->transformCreated($existing));
        }

        $profile = null;
        $userId = null;
        if ($request->filled('customer_id')) {
            $profile = CustomerProfile::query()->find($request->input('customer_id'));
            $userId = $profile?->user_id;
        }

        $startedAt = $request->input('started_at') ? now()->parse($request->input('started_at')) : now();

        $call = Call::query()->create([
            'external_id' => $request->input('external_id'),
            'customer_profile_id' => $profile?->id,
            'user_id' => $userId,
            'direction' => $request->input('direction'),
            'status' => $request->input('status'),
            'from_number' => $this->phoneNormalizer->normalize($request->input('from_number')),
            'to_number' => $this->phoneNormalizer->normalize($request->input('to_number')),
            'agent_external_id' => $request->input('agent_external_id'),
            'agent_name' => $request->input('agent_name'),
            'asterisk_unique_id' => $request->input('asterisk_unique_id'),
            'started_at' => $startedAt,
            'tags' => $request->input('tags', []),
            'source' => $request->input('source', 'call_center'),
        ]);

        if ($profile) {
            $profile->increment('total_calls');
            $profile->update(['last_call_at' => $startedAt]);
        }

        $payload = $this->callTransformer->transformCreated($call);
        $this->idempotency->store(trim((string) $request->header('Idempotency-Key')), $endpoint, 201, $payload);

        return $this->created($payload);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $call = Call::query()->find($id);
        if (!$call) {
            return $this->notFound('call_not_found', 'Call not found');
        }

        return $this->ok($this->applyCallUpdate($call, $request));
    }

    public function updateByExternalId(Request $request, string $externalId): JsonResponse
    {
        $call = Call::query()->where('external_id', $externalId)->first();
        if (!$call) {
            return $this->notFound('call_not_found', 'Call not found');
        }

        return $this->ok($this->applyCallUpdate($call, $request));
    }

    public function indexForCustomer(Request $request, int $id): JsonResponse
    {
        $profile = $this->profiles->getProfileByNumericId($id);
        if (!$profile) {
            return $this->notFound('customer_not_found', 'Customer not found');
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $calls = Call::query()
            ->where('customer_profile_id', $profile->id)
            ->orderByDesc('started_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->ok([
            'data' => $calls->getCollection()->map(fn (Call $call) => $this->callTransformer->transformFull($call))->values()->all(),
            'meta' => $this->paginatedMeta($calls->total(), $page, $perPage),
        ]);
    }

    private function applyCallUpdate(Call $call, Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|max:32',
            'agent_external_id' => 'nullable|string|max:255',
            'agent_name' => 'nullable|string|max:255',
            'answered_at' => 'nullable|date',
            'ended_at' => 'nullable|date',
            'duration_seconds' => 'nullable|integer|min:0',
            'disposition' => 'nullable|string|max:64',
            'outcome' => 'nullable|string|max:64',
            'tags' => 'nullable|array',
            'notes_summary' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'error' => [
                        'code' => 'validation_error',
                        'message' => collect($validator->errors())->flatten()->first() ?: 'Validation failed.',
                    ],
                ], 422)
            );
        }

        $updates = $request->only([
            'status', 'agent_external_id', 'agent_name', 'duration_seconds',
            'disposition', 'outcome', 'tags', 'notes_summary',
        ]);

        if ($request->filled('answered_at')) {
            $updates['answered_at'] = now()->parse($request->input('answered_at'));
        }
        if ($request->filled('ended_at')) {
            $updates['ended_at'] = now()->parse($request->input('ended_at'));
        }

        $call->fill(array_filter($updates, fn ($v) => $v !== null))->save();

        return $this->callTransformer->transformFull($call->fresh());
    }
}
