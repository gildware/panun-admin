<?php

namespace Modules\CallCenterModule\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\CallCenterModule\Entities\Call;
use Modules\CallCenterModule\Entities\CustomerProfile;
use Modules\CallCenterModule\Entities\Voicemail;
use Modules\CallCenterModule\Services\CustomerProfileService;
use Modules\CallCenterModule\Services\IdempotencyService;
use Modules\CallCenterModule\Services\PhoneNormalizer;
use Modules\CallCenterModule\Support\RespondsWithCallCenterApi;

class VoicemailController extends Controller
{
    use RespondsWithCallCenterApi;

    public function __construct(
        private readonly CustomerProfileService $profiles,
        private readonly PhoneNormalizer $phoneNormalizer,
        private readonly IdempotencyService $idempotency,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $endpoint = 'POST /voicemails';
        $replay = $this->idempotency->replayIfExists($request, $endpoint);
        if ($replay) {
            return response()->json($replay['body'], $replay['status']);
        }

        $validator = Validator::make($request->all(), [
            'external_id' => 'required|uuid',
            'call_external_id' => 'nullable|uuid',
            'customer_id' => 'nullable|integer',
            'from_number' => 'required|string|max:32',
            'to_number' => 'required|string|max:32',
            'recording_url' => 'nullable|string',
            'duration_seconds' => 'nullable|integer|min:0',
            'status' => 'nullable|in:new,listened,returned,archived',
            'received_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $existing = Voicemail::query()->where('external_id', $request->input('external_id'))->first();
        if ($existing) {
            return $this->created($this->transformVoicemail($existing));
        }

        $callId = null;
        if ($request->filled('call_external_id')) {
            $callId = Call::query()->where('external_id', $request->input('call_external_id'))->value('id');
        }

        $profile = null;
        $userId = null;
        if ($request->filled('customer_id')) {
            $profile = CustomerProfile::query()->find($request->input('customer_id'));
            $userId = $profile?->user_id;
        }

        $receivedAt = $request->input('received_at') ? now()->parse($request->input('received_at')) : now();

        $voicemail = Voicemail::query()->create([
            'external_id' => $request->input('external_id'),
            'call_id' => $callId,
            'call_external_id' => $request->input('call_external_id'),
            'customer_profile_id' => $profile?->id,
            'user_id' => $userId,
            'from_number' => $this->phoneNormalizer->normalize($request->input('from_number')),
            'to_number' => $this->phoneNormalizer->normalize($request->input('to_number')),
            'recording_url' => $request->input('recording_url'),
            'duration_seconds' => $request->input('duration_seconds'),
            'status' => $request->input('status', 'new'),
            'received_at' => $receivedAt,
        ]);

        $payload = $this->transformVoicemail($voicemail);
        $this->idempotency->store(trim((string) $request->header('Idempotency-Key')), $endpoint, 201, $payload);

        return $this->created($payload);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $voicemail = Voicemail::query()->find($id);
        if (!$voicemail) {
            return $this->notFound('voicemail_not_found', 'Voicemail not found');
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:new,listened,returned,archived',
            'listened_at' => 'nullable|date',
            'returned_call_external_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        if ($request->filled('status')) {
            $voicemail->status = $request->input('status');
        }
        if ($request->filled('listened_at')) {
            $voicemail->listened_at = now()->parse($request->input('listened_at'));
        }
        if ($request->filled('returned_call_external_id')) {
            $voicemail->returned_call_external_id = $request->input('returned_call_external_id');
        }

        $voicemail->save();

        return $this->ok($this->transformVoicemail($voicemail));
    }

    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $query = Voicemail::query()->with(['customerProfile.user'])->orderByDesc('received_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_profile_id', $request->query('customer_id'));
        }
        if ($request->filled('from_date')) {
            $query->where('received_at', '>=', now()->parse($request->query('from_date')));
        }
        if ($request->filled('to_date')) {
            $query->where('received_at', '<=', now()->parse($request->query('to_date')));
        }

        $voicemails = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $voicemails->getCollection()->map(function (Voicemail $vm) {
            $user = $vm->customerProfile?->user;
            $name = $user ? trim($user->first_name . ' ' . $user->last_name) : null;

            return [
                'id' => $vm->id,
                'external_id' => $vm->external_id,
                'customer_id' => $vm->customer_profile_id,
                'customer_name' => $name,
                'from_number' => $vm->from_number,
                'recording_url' => $vm->recording_url,
                'duration_seconds' => $vm->duration_seconds,
                'status' => $vm->status,
                'received_at' => $vm->received_at?->utc()->toIso8601String(),
            ];
        })->values()->all();

        return $this->ok([
            'data' => $data,
            'meta' => $this->paginatedMeta($voicemails->total(), $page, $perPage),
        ]);
    }

    private function transformVoicemail(Voicemail $voicemail): array
    {
        return [
            'id' => $voicemail->id,
            'external_id' => $voicemail->external_id,
            'customer_id' => $voicemail->customer_profile_id,
            'status' => $voicemail->status,
            'received_at' => $voicemail->received_at?->utc()->toIso8601String(),
        ];
    }
}
