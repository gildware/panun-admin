<?php

namespace Modules\ChattingModule\Services;

use Illuminate\Support\Facades\DB;
use Modules\ChattingModule\Entities\ChannelList;
use Modules\ChattingModule\Entities\ChannelUser;
use Modules\UserManagement\Entities\User;
use Ramsey\Uuid\Uuid;

class StaffGroupChannelService
{
    public const REFERENCE_TYPE = 'staff_group';

    public const REFERENCE_ID = '00000000-0000-4000-8000-000000000001';

    public function isStaffGroupChannel(?ChannelList $channel): bool
    {
        return $channel !== null
            && $channel->reference_type === self::REFERENCE_TYPE
            && (string) $channel->reference_id === self::REFERENCE_ID;
    }

    public function getGroupChannel(): ?ChannelList
    {
        return ChannelList::query()
            ->where('reference_type', self::REFERENCE_TYPE)
            ->where('reference_id', self::REFERENCE_ID)
            ->first();
    }

    public function ensureGroupForUser(User $user): ?ChannelList
    {
        if (! in_array($user->user_type, ADMIN_USER_TYPES, true) || ! $user->is_active) {
            return null;
        }

        return DB::transaction(function () use ($user) {
            $channel = $this->getGroupChannel();

            if (! $channel) {
                $channel = new ChannelList();
                $channel->reference_type = self::REFERENCE_TYPE;
                $channel->reference_id = self::REFERENCE_ID;
                $channel->save();
            }

            $this->syncAllActiveStaffMembers($channel);
            $this->addMemberIfMissing($channel->id, $user->id);

            return $channel->fresh(['channelUsers']);
        });
    }

    public function addMemberIfMissing(string $channelId, string $userId): void
    {
        $exists = ChannelUser::query()
            ->where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            return;
        }

        ChannelUser::query()->create([
            'id' => Uuid::uuid4()->toString(),
            'channel_id' => $channelId,
            'user_id' => $userId,
            'is_read' => 1,
        ]);
    }

    public function syncAllActiveStaffMembers(?ChannelList $channel = null): void
    {
        $channel = $channel ?? $this->getGroupChannel();

        if (! $channel) {
            return;
        }

        User::query()
            ->ofType(ADMIN_USER_TYPES)
            ->where('is_active', 1)
            ->pluck('id')
            ->each(fn (string $staffId) => $this->addMemberIfMissing($channel->id, $staffId));
    }

    public function removeMember(string $userId): void
    {
        $channel = $this->getGroupChannel();

        if (! $channel) {
            return;
        }

        ChannelUser::query()
            ->where('channel_id', $channel->id)
            ->where('user_id', $userId)
            ->delete();
    }

    public function memberCount(ChannelList $channel): int
    {
        return $channel->channelUsers()->count();
    }
}
