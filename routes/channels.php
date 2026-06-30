<?php

use Illuminate\Support\Facades\Broadcast;
use Modules\InAppCallModule\Entities\InAppCall;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('in-app-call.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('in-app-call.{callId}', function ($user, $callId) {
    return InAppCall::query()
        ->where('id', $callId)
        ->where(function ($query) use ($user) {
            $query->where('caller_user_id', $user->id)
                ->orWhere('callee_user_id', $user->id);
        })
        ->exists();
});
