<?php

namespace Modules\CallCenterModule\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\CallCenterModule\Entities\AiAnalysis;
use Modules\CallCenterModule\Entities\Call;
use Modules\CallCenterModule\Entities\Recording;
use Modules\CallCenterModule\Services\IdempotencyService;
use Modules\CallCenterModule\Support\RespondsWithCallCenterApi;

class CallRecordingController extends Controller
{
    use RespondsWithCallCenterApi;

    public function __construct(private readonly IdempotencyService $idempotency)
    {
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $call = Call::query()->find($id);
        if (!$call) {
            return $this->notFound('call_not_found', 'Call not found');
        }

        return $this->attachRecording($request, $call, "POST /calls/{$id}/recordings");
    }

    public function storeByExternalId(Request $request, string $externalId): JsonResponse
    {
        $call = Call::query()->where('external_id', $externalId)->first();
        if (!$call) {
            return $this->notFound('call_not_found', 'Call not found');
        }

        return $this->attachRecording($request, $call, "POST /calls/by-external-id/{$externalId}/recordings");
    }

    private function attachRecording(Request $request, Call $call, string $endpoint): JsonResponse
    {
        $replay = $this->idempotency->replayIfExists($request, $endpoint);
        if ($replay) {
            return response()->json($replay['body'], $replay['status']);
        }

        $validator = Validator::make($request->all(), [
            'external_id' => 'required|uuid',
            'recording_url' => 'required|url|max:2048',
            'duration_seconds' => 'nullable|integer|min:0',
            'file_size_bytes' => 'nullable|integer|min:0',
            'format' => 'nullable|string|max:16',
            'storage_provider' => 'nullable|string|max:32',
            'recorded_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $existing = Recording::query()->where('external_id', $request->input('external_id'))->first();
        if ($existing) {
            return $this->created($this->transformRecording($existing));
        }

        $recordedAt = $request->input('recorded_at') ? now()->parse($request->input('recorded_at')) : now();

        $recording = Recording::query()->create([
            'external_id' => $request->input('external_id'),
            'call_id' => $call->id,
            'recording_url' => $request->input('recording_url'),
            'duration_seconds' => $request->input('duration_seconds'),
            'file_size_bytes' => $request->input('file_size_bytes'),
            'format' => $request->input('format'),
            'storage_provider' => $request->input('storage_provider'),
            'recorded_at' => $recordedAt,
        ]);

        $payload = $this->transformRecording($recording);
        $this->idempotency->store(trim((string) $request->header('Idempotency-Key')), $endpoint, 201, $payload);

        return $this->created($payload);
    }

    private function transformRecording(Recording $recording): array
    {
        return [
            'id' => $recording->id,
            'external_id' => $recording->external_id,
            'call_id' => $recording->call_id,
            'recording_url' => $recording->recording_url,
            'duration_seconds' => $recording->duration_seconds,
        ];
    }
}
