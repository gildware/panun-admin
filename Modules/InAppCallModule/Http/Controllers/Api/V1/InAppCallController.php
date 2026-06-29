<?php

namespace Modules\InAppCallModule\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\InAppCallModule\Services\InAppCallService;
use function response;
use function response_formatter;

class InAppCallController extends Controller
{
    public function __construct(
        protected InAppCallService $inAppCallService,
    ) {}

    public function config(): JsonResponse
    {
        return response()->json(response_formatter(DEFAULT_200, $this->inAppCallService->publicConfig()), 200);
    }

    public function initiate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $result = $this->inAppCallService->initiate(
            $request->user(),
            (string) $request->input('channel_id'),
        );

        if (! ($result['ok'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_400, null, [['message' => $result['message'] ?? translate('Failed_to_start_call')]]), 400);
        }

        return response()->json(response_formatter(DEFAULT_200, $result['data']), 200);
    }

    public function show(Request $request, string $callId): JsonResponse
    {
        $result = $this->inAppCallService->show($request->user(), $callId);

        if (! ($result['ok'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_404, null, [['message' => $result['message'] ?? translate('Call_not_found')]]), 404);
        }

        return response()->json(response_formatter(DEFAULT_200, $result['data']), 200);
    }

    public function accept(Request $request, string $callId): JsonResponse
    {
        $result = $this->inAppCallService->accept($request->user(), $callId);

        if (! ($result['ok'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_400, null, [['message' => $result['message'] ?? translate('Failed_to_accept_call')]]), 400);
        }

        return response()->json(response_formatter(DEFAULT_200, $result['data']), 200);
    }

    public function decline(Request $request, string $callId): JsonResponse
    {
        $result = $this->inAppCallService->decline($request->user(), $callId);

        if (! ($result['ok'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_400, null, [['message' => $result['message'] ?? translate('Failed_to_decline_call')]]), 400);
        }

        return response()->json(response_formatter(DEFAULT_200, $result['data']), 200);
    }

    public function cancel(Request $request, string $callId): JsonResponse
    {
        $result = $this->inAppCallService->cancel($request->user(), $callId);

        if (! ($result['ok'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_400, null, [['message' => $result['message'] ?? translate('Failed_to_cancel_call')]]), 400);
        }

        return response()->json(response_formatter(DEFAULT_200, $result['data']), 200);
    }

    public function end(Request $request, string $callId): JsonResponse
    {
        $result = $this->inAppCallService->end($request->user(), $callId);

        if (! ($result['ok'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_400, null, [['message' => $result['message'] ?? translate('Failed_to_end_call')]]), 400);
        }

        return response()->json(response_formatter(DEFAULT_200, $result['data']), 200);
    }

    public function missed(Request $request, string $callId): JsonResponse
    {
        $call = $this->inAppCallService->show($request->user(), $callId);
        if (! ($call['ok'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_404, null, [['message' => translate('Call_not_found')]]), 404);
        }

        $result = $this->inAppCallService->markMissedIfRinging($callId);

        if (! ($result['ok'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_400, null, [['message' => translate('Call_is_no_longer_ringing')]]), 400);
        }

        return response()->json(response_formatter(DEFAULT_200, $result['data']), 200);
    }

    public function postSignal(Request $request, string $callId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'signal_type' => 'required|in:offer,answer,ice',
            'payload' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $result = $this->inAppCallService->postSignal(
            $request->user(),
            $callId,
            (string) $request->input('signal_type'),
            (array) $request->input('payload'),
        );

        if (! ($result['ok'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_400, null, [['message' => $result['message'] ?? translate('Failed_to_send_signal')]]), 400);
        }

        return response()->json(response_formatter(DEFAULT_200, $result['data']), 200);
    }

    public function listSignals(Request $request, string $callId): JsonResponse
    {
        $result = $this->inAppCallService->listSignals(
            $request->user(),
            $callId,
            $request->query('after'),
        );

        if (! ($result['ok'] ?? false)) {
            return response()->json(response_formatter(DEFAULT_404, null, [['message' => $result['message'] ?? translate('Call_not_found')]]), 404);
        }

        return response()->json(response_formatter(DEFAULT_200, $result['data']), 200);
    }
}
