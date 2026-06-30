<?php

use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserFcmDevice;

if (! function_exists('is_valid_fcm_token')) {
    function is_valid_fcm_token(?string $token): bool
    {
        return filled($token) && $token !== '@';
    }
}

if (! function_exists('sync_user_legacy_fcm_token')) {
    function sync_user_legacy_fcm_token(string $userId): void
    {
        $latest = UserFcmDevice::query()
            ->where('user_id', $userId)
            ->orderByDesc('last_seen_at')
            ->orderByDesc('updated_at')
            ->value('fcm_token');

        User::query()->where('id', $userId)->update([
            'fcm_token' => is_valid_fcm_token($latest) ? $latest : null,
        ]);
    }
}

if (! function_exists('register_user_fcm_device')) {
    function register_user_fcm_device(
        string $userId,
        string $fcmToken,
        ?string $deviceId = null,
        ?string $platform = null
    ): void {
        if (! is_valid_fcm_token($fcmToken)) {
            return;
        }

        $deviceId = filled($deviceId)
            ? $deviceId
            : 'legacy:'.substr(hash('sha256', $fcmToken), 0, 32);

        UserFcmDevice::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'device_id' => $deviceId,
            ],
            [
                'fcm_token' => $fcmToken,
                'platform' => $platform,
                'last_seen_at' => now(),
            ]
        );

        User::query()->where('id', $userId)->update(['fcm_token' => $fcmToken]);
    }
}

if (! function_exists('unregister_user_fcm_device')) {
    function unregister_user_fcm_device(
        string $userId,
        ?string $deviceId = null,
        ?string $fcmToken = null
    ): void {
        $query = UserFcmDevice::query()->where('user_id', $userId);

        if (filled($deviceId)) {
            $query->where('device_id', $deviceId)->delete();
        } elseif (is_valid_fcm_token($fcmToken)) {
            $query->where('fcm_token', $fcmToken)->delete();
        } else {
            return;
        }

        sync_user_legacy_fcm_token($userId);
    }
}

if (! function_exists('resolve_fcm_tokens_for_recipient')) {
    /**
     * @return list<string>
     */
    function resolve_fcm_tokens_for_recipient(User|string|null $recipientOrToken): array
    {
        if ($recipientOrToken instanceof User) {
            return user_fcm_device_tokens($recipientOrToken);
        }

        if (is_string($recipientOrToken) && is_valid_fcm_token($recipientOrToken)) {
            return [$recipientOrToken];
        }

        return [];
    }
}

if (! function_exists('user_fcm_device_tokens')) {
    /**
     * @return list<string>
     */
    function user_fcm_device_tokens(User|string|null $user): array
    {
        if ($user === null) {
            return [];
        }

        $userId = $user instanceof User ? $user->id : (string) $user;
        if ($userId === '') {
            return [];
        }

        $tokens = UserFcmDevice::query()
            ->where('user_id', $userId)
            ->orderByDesc('last_seen_at')
            ->orderByDesc('updated_at')
            ->pluck('fcm_token')
            ->filter(fn ($token) => is_valid_fcm_token($token))
            ->unique()
            ->values()
            ->all();

        if ($tokens !== []) {
            return $tokens;
        }

        if ($user instanceof User && is_valid_fcm_token($user->fcm_token)) {
            return [$user->fcm_token];
        }

        $legacyToken = User::query()->where('id', $userId)->value('fcm_token');

        return is_valid_fcm_token($legacyToken) ? [$legacyToken] : [];
    }
}

if (! function_exists('user_has_fcm_devices')) {
    function user_has_fcm_devices(User|string|null $user): bool
    {
        return user_fcm_device_tokens($user) !== [];
    }
}

if (! function_exists('device_notification_for_user')) {
    function device_notification_for_user(
        User|string|null $user,
        $title,
        $description,
        $image,
        $booking_id,
        $type = 'status',
        $channel_id = null,
        $user_id = null,
        $data = null,
        $advertisement_id = null,
        $bookingType = null,
        $repeat_type = null,
        $service_slug = null,
        $service_id = null,
        $booking_status_override = null,
        $push_notification_id = null
    ): void {
        foreach (user_fcm_device_tokens($user) as $fcmToken) {
            device_notification(
                $fcmToken,
                $title,
                $description,
                $image,
                $booking_id,
                $type,
                $channel_id,
                $user_id,
                $data,
                $advertisement_id,
                $bookingType,
                $repeat_type,
                $service_slug,
                $service_id,
                $booking_status_override,
                $push_notification_id
            );
        }
    }
}

if (! function_exists('device_notification_for_bidding_user')) {
    function device_notification_for_bidding_user(
        User|string|null $user,
        $title,
        $description,
        $image,
        $type = 'bidding',
        $booking_id = null,
        $post_id = null,
        $provider_id = null,
        $data = null
    ): void {
        foreach (user_fcm_device_tokens($user) as $fcmToken) {
            device_notification_for_bidding(
                $fcmToken,
                $title,
                $description,
                $image,
                $type,
                $booking_id,
                $post_id,
                $provider_id,
                $data
            );
        }
    }
}

if (! function_exists('device_notification_for_chatting_user')) {
    function device_notification_for_chatting_user(
        User|string|null $user,
        $title,
        $description,
        $image,
        $channel_id,
        $user_name,
        $user_image,
        $user_phone,
        $user_type,
        $type = 'status'
    ): void {
        foreach (user_fcm_device_tokens($user) as $fcmToken) {
            device_notification_for_chatting(
                $fcmToken,
                $title,
                $description,
                $image,
                $channel_id,
                $user_name,
                $user_image,
                $user_phone,
                $user_type,
                $type
            );
        }
    }
}

if (! function_exists('device_notification_for_in_app_call_user')) {
    function device_notification_for_in_app_call_user(
        User|string|null $user,
        $title,
        $description,
        $call_id,
        $channel_id,
        $agora_channel_name,
        $user_name,
        $user_image,
        $user_phone,
        $user_type,
        $type = 'incoming_call'
    ): void {
        foreach (user_fcm_device_tokens($user) as $fcmToken) {
            device_notification_for_in_app_call(
                $fcmToken,
                $title,
                $description,
                $call_id,
                $channel_id,
                $agora_channel_name,
                $user_name,
                $user_image,
                $user_phone,
                $user_type,
                $type
            );
        }
    }
}

if (! function_exists('handle_user_fcm_token_request')) {
    function handle_user_fcm_token_request(\Illuminate\Http\Request $request, string $userId): void
    {
        if ($request->boolean('unregister') || $request->input('fcm_token') === '@') {
            unregister_user_fcm_device(
                $userId,
                $request->input('device_id'),
                $request->input('fcm_token')
            );

            return;
        }

        register_user_fcm_device(
            $userId,
            (string) $request->input('fcm_token'),
            $request->input('device_id'),
            $request->input('platform')
        );
    }
}
