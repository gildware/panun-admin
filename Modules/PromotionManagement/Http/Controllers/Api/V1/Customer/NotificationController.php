<?php

namespace Modules\PromotionManagement\Http\Controllers\Api\V1\Customer;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Modules\PromotionManagement\Entities\PushNotification;
use Modules\UserManagement\Entities\Guest;

class NotificationController extends Controller
{
    private PushNotification $pushNotification;
    private mixed $customer_user_id;
    private bool $is_customer_logged_in;

    public function __construct(PushNotification $pushNotification, Request $request)
    {
        $this->pushNotification = $pushNotification;
        $this->is_customer_logged_in = (bool) auth('api')->user();
        $this->customer_user_id = $this->is_customer_logged_in ? auth('api')->user()->id : $request['guest_id'];
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        if (! $this->customer_user_id) {
            return response()->json(response_formatter(DEFAULT_400, null, [['error_code' => 'guest_id', 'message' => 'guest_id required']]), 400);
        }

        $pushNotification = $this->customerInboxQuery()
            ->latest()
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])
            ->withPath('');

        mobile_inbox_enrich_paginator($pushNotification, (string) $this->customer_user_id);

        return response()->json(response_formatter(DEFAULT_200, $pushNotification), 200);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        if (! $this->customer_user_id) {
            return response()->json(response_formatter(DEFAULT_400, null, [['error_code' => 'guest_id', 'message' => 'guest_id required']]), 400);
        }

        $count = mobile_inbox_unread_count($this->customerInboxQuery(), (string) $this->customer_user_id);

        return response()->json(response_formatter(DEFAULT_200, ['unread_count' => $count]), 200);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        if (! $this->customer_user_id) {
            return response()->json(response_formatter(DEFAULT_400, null, [['error_code' => 'guest_id', 'message' => 'guest_id required']]), 400);
        }

        $visible = $this->customerInboxQuery()->where('id', $id)->exists();
        if (! $visible) {
            return response()->json(response_formatter(DEFAULT_404), 404);
        }

        mobile_inbox_mark_read($id, (string) $this->customer_user_id);

        return response()->json(response_formatter(DEFAULT_UPDATE_200), 200);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        if (! $this->customer_user_id) {
            return response()->json(response_formatter(DEFAULT_400, null, [['error_code' => 'guest_id', 'message' => 'guest_id required']]), 400);
        }

        $marked = mobile_inbox_mark_all_read($this->customerInboxQuery(), (string) $this->customer_user_id);

        return response()->json(response_formatter(DEFAULT_200, ['marked' => $marked]), 200);
    }

    private function customerInboxQuery(): Builder
    {
        $createdAt = null;
        if ($this->is_customer_logged_in) {
            $createdAt = auth('api')->user()->created_at;
        } else {
            $createdAt = Guest::find($this->customer_user_id)?->created_at;
        }

        return $this->pushNotification->ofStatus(1)
            ->when(! is_null(Config::get('zone_id')), function ($query) {
                $query->whereJsonContains('zone_ids', Config::get('zone_id'));
            })
            ->where(function ($query) {
                $query->whereDoesntHave('pushNotificationUser')
                    ->orWhereHas('pushNotificationUser', function ($query) {
                        $query->where('user_id', $this->customer_user_id);
                    });
            })
            ->when($createdAt, function ($query) use ($createdAt) {
                $query->where('created_at', '>=', $createdAt);
            })
            ->where('to_users', 'like', '%"customer"%');
    }
}
