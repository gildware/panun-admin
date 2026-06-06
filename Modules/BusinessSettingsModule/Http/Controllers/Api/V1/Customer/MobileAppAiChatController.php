<?php

namespace Modules\BusinessSettingsModule\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\BusinessSettingsModule\Services\MobileAppAiChatBookingService;
use Modules\BusinessSettingsModule\Services\MobileAppAiSupportService;
use function response;
use function response_formatter;
use function user_can_use_customer_app;

class MobileAppAiChatController extends Controller
{
    public function __construct(
        protected MobileAppAiSupportService $aiSupport,
        protected MobileAppAiChatBookingService $chatBooking,
    ) {}

    public function conversation(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! user_can_use_customer_app($user)) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        return response()->json(response_formatter(DEFAULT_200, [
            'enabled' => $this->aiSupport->isEnabled(true),
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
            'cart_updated' => $result['cart_updated'] ?? false,
            'ui' => $result['ui'] ?? null,
        ]), 200);
    }

    public function bookingAction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:start,search,apply,submit,complete,pick,choose,time,schedule,confirm,finalize,cancel,status,proceed_booking,book_now,triage_issue,more_triage_tips,confirm_service,show_service_options,clarify_step,confirm_cart_action,cancel_cart_action,pick_cart_remove,confirm_coupon_action,cancel_coupon_action,confirm_bid_action,cancel_bid_action,confirm_booking_cancel_action,cancel_booking_cancel_action,confirm_cart_qty_action,cancel_cart_qty_action',
            'query' => 'nullable|string|max:500',
            'message' => 'nullable|string|max:4000',
            'choice' => 'nullable|string|max:500',
            'asap' => 'nullable|boolean',
            'when' => 'nullable|string|max:64',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $user = $request->user();
        if (! user_can_use_customer_app($user)) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        if (! $this->aiSupport->isEnabled()) {
            return response()->json(response_formatter(DEFAULT_200, [
                'enabled' => false,
                'ok' => false,
                'reply' => __('mobile_app_ai.service_unavailable'),
                'ui' => null,
                'cart_updated' => false,
                'messages' => $this->aiSupport->formatMessagesForApi($user),
            ]), 200);
        }

        $result = $this->chatBooking->handleAction($user, $request->all());

        return response()->json(response_formatter(DEFAULT_200, [
            'enabled' => $this->aiSupport->isEnabled(),
            'ok' => $result['ok'] ?? false,
            'reply' => $result['reply'] ?? '',
            'ui' => $result['ui'] ?? null,
            'cart_updated' => $result['cart_updated'] ?? false,
            'messages' => $this->aiSupport->formatMessagesForApi($user),
        ]), 200);
    }

    public function quickIntent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'intent' => 'required|string|in:start_booking,booking_status,human_support,troubleshoot',
            'query' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $user = $request->user();
        if (! user_can_use_customer_app($user)) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        $result = $this->aiSupport->quickIntent(
            $user,
            (string) $request->input('intent'),
            $request->input('query') !== null ? (string) $request->input('query') : null,
        );

        return response()->json(response_formatter(DEFAULT_200, [
            'enabled' => $this->aiSupport->isEnabled(),
            'reply' => $result['reply'],
            'messages' => $result['messages'],
            'cart_updated' => $result['cart_updated'] ?? false,
            'ui' => $result['ui'] ?? null,
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
