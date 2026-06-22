<?php

namespace Modules\CallCenterModule\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\CallCenterModule\Entities\AiAnalysis;
use Modules\CallCenterModule\Entities\Call;
use Modules\CallCenterModule\Services\IdempotencyService;
use Modules\CallCenterModule\Support\RespondsWithCallCenterApi;

class CallAiAnalysisController extends Controller
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

        return $this->attachAnalysis($request, $call, "POST /calls/{$id}/ai-analysis");
    }

    public function storeByExternalId(Request $request, string $externalId): JsonResponse
    {
        $call = Call::query()->where('external_id', $externalId)->first();
        if (!$call) {
            return $this->notFound('call_not_found', 'Call not found');
        }

        return $this->attachAnalysis($request, $call, "POST /calls/by-external-id/{$externalId}/ai-analysis");
    }

    private function attachAnalysis(Request $request, Call $call, string $endpoint): JsonResponse
    {
        $replay = $this->idempotency->replayIfExists($request, $endpoint);
        if ($replay) {
            return response()->json($replay['body'], $replay['status']);
        }

        $validator = Validator::make($request->all(), [
            'external_id' => 'required|uuid',
            'transcript' => 'nullable|string',
            'summary' => 'nullable|string',
            'intent' => 'nullable|string|max:64',
            'sentiment' => 'nullable|in:positive,neutral,negative',
            'sentiment_score' => 'nullable|numeric',
            'suggested_actions' => 'nullable|array',
            'generated_notes' => 'nullable|string',
            'language' => 'nullable|string|max:8',
            'processed_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $existing = AiAnalysis::query()->where('external_id', $request->input('external_id'))->first();
        if ($existing) {
            return $this->created([
                'id' => $existing->id,
                'external_id' => $existing->external_id,
                'call_id' => $existing->call_id,
            ]);
        }

        $processedAt = $request->input('processed_at') ? now()->parse($request->input('processed_at')) : now();

        $analysis = AiAnalysis::query()->create([
            'external_id' => $request->input('external_id'),
            'call_id' => $call->id,
            'transcript' => $request->input('transcript'),
            'summary' => $request->input('summary'),
            'intent' => $request->input('intent'),
            'sentiment' => $request->input('sentiment'),
            'sentiment_score' => $request->input('sentiment_score'),
            'suggested_actions' => $request->input('suggested_actions'),
            'generated_notes' => $request->input('generated_notes'),
            'language' => $request->input('language'),
            'processed_at' => $processedAt,
        ]);

        if ($request->filled('summary') && $call->customer_profile_id) {
            \Modules\CallCenterModule\Entities\CustomerProfile::query()
                ->where('id', $call->customer_profile_id)
                ->update(['ai_summary' => $request->input('summary')]);
        }

        $payload = [
            'id' => $analysis->id,
            'external_id' => $analysis->external_id,
            'call_id' => $analysis->call_id,
        ];

        $this->idempotency->store(trim((string) $request->header('Idempotency-Key')), $endpoint, 201, $payload);

        return $this->created($payload);
    }
}
