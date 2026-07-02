<?php

namespace App\Support;

use Modules\ChattingModule\Entities\ChannelConversation;
use Modules\ChattingModule\Entities\ChannelList;
use Modules\UserManagement\Entities\User;

final class AdminHeaderChatCounts
{
    public static function supportUnreadMessages(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        $userId = $user->id;

        $supportUnreadChannelIds = ChannelList::query()
            ->where('reference_type', 'support')
            ->whereHas('channelUsers', fn ($query) => $query->where('user_id', $userId)->where('is_read', 0))
            ->whereHas('channelUsers', function ($query) use ($userId) {
                $query->where('user_id', '!=', $userId)
                    ->whereHas('user', fn ($uq) => $uq->whereIn('user_type', [USER_TYPES[2]['value'], USER_TYPES[4]['value']]));
            })
            ->pluck('id');

        if ($supportUnreadChannelIds->isEmpty()) {
            return 0;
        }

        return ChannelConversation::query()
            ->whereIn('channel_id', $supportUnreadChannelIds)
            ->where('user_id', '!=', $userId)
            ->whereExists(function ($query) use ($userId) {
                $query->selectRaw('1')
                    ->from('channel_users')
                    ->whereColumn('channel_users.channel_id', 'channel_conversations.channel_id')
                    ->where('channel_users.user_id', $userId)
                    ->whereNull('channel_users.deleted_at')
                    ->where(function ($inner) {
                        $inner->whereNull('channel_users.read_at')
                            ->orWhereColumn('channel_conversations.created_at', '>', 'channel_users.read_at');
                    });
            })
            ->count();
    }

    public static function staffUnreadMessages(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        $userId = $user->id;

        $staffUnreadChannelIds = ChannelList::query()
            ->whereHas('channelUsers', fn ($query) => $query->where('user_id', $userId)->where('is_read', 0))
            ->whereHas('channelUsers', function ($query) use ($userId) {
                $query->where('user_id', '!=', $userId)
                    ->whereHas('user', fn ($uq) => $uq->whereIn('user_type', ADMIN_USER_TYPES));
            })
            ->pluck('id');

        if ($staffUnreadChannelIds->isEmpty()) {
            return 0;
        }

        return ChannelConversation::query()
            ->whereIn('channel_id', $staffUnreadChannelIds)
            ->where('user_id', '!=', $userId)
            ->whereExists(function ($query) use ($userId) {
                $query->selectRaw('1')
                    ->from('channel_users')
                    ->whereColumn('channel_users.channel_id', 'channel_conversations.channel_id')
                    ->where('channel_users.user_id', $userId)
                    ->whereNull('channel_users.deleted_at')
                    ->where(function ($inner) {
                        $inner->whereNull('channel_users.read_at')
                            ->orWhereColumn('channel_conversations.created_at', '>', 'channel_users.read_at');
                    });
            })
            ->count();
    }
}
