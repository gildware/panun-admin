<?php

namespace Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\BusinessSettingsModule\Services\MobileAppAiSupportService;
use function response;
use function response_formatter;
use function user_can_use_customer_app;

class MobileAppAiChatController extends Controller
{
    public function __construct(
        protected MobileAppAiSupportService $aiSupport,
    ) {}

    public function conversation(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! user_can_use_customer_app($user)) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        return response()->json(response_formatter(DEFAULT_200, [
            'enabled' => $this->aiSupport->isEnabled(),
            'messages' => $this->aiSupport->formatMessagesForApi($user),
        ]), 200);
    }

    public function send(Request $request): JsonResponse
    {
        set_time_limit(120);

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:4000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $user = $request->user();
        if (! user_can_use_customer_app($user)) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $result = $this->aiSupport->sendMessage($user, (string) $request->input('message'));

        return response()->json(response_formatter(DEFAULT_200, [
            'enabled' => $this->aiSupport->isEnabled(),
            'reply' => $result['reply'],
            'messages' => $result['messages'],
        ]), 200);
    }

    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! user_can_use_customer_app($user)) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $this->aiSupport->clearConversation($user);

        return response()->json(response_formatter(DEFAULT_200, [
            'messages' => [],
        ]), 200);
    }
}
