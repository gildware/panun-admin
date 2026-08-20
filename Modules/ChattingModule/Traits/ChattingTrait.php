<?php

namespace Modules\ChattingModule\Traits;

use Modules\ChattingModule\Entities\ChannelList;
use Ramsey\Uuid\Nonstandard\Uuid;

trait ChattingTrait
{
    public function createNewChannel($fromUser, $toUser, $referenceId = null, $referenceType = null)
    {
        $channelIds = $this->channelUser->where(['user_id' => $fromUser])->pluck('channel_id')->toArray();
        $findChannelQuery = $this->channelList
            ->whereIn('id', $channelIds)
            ->whereHas('channelUsers', function ($query) use ($toUser) {
                $query->where(['user_id' => $toUser]);
            });

        if ($referenceType !== null) {
            $findChannelQuery->where('reference_type', $referenceType);
        }

        $findChannel = $findChannelQuery->latest()->first();

        if (!isset($findChannel)) {
            $channel = $this->channelList;
            $channel->reference_id = $referenceId;
            $channel->reference_type = $referenceType;
            $channel->save();

            $this->channelUser->insert([
                [
                    'id' => Uuid::uuid4(),
                    'channel_id' => $channel->id,
                    'user_id' => $fromUser,
                    'is_read' => 1,
                    'read_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'id' => Uuid::uuid4(),
                    'channel_id' => $channel->id,
                    'user_id' => $toUser,
                    'is_read' => 1,
                    'read_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);
            return $channel;
        }

        return $findChannel;
    }

    protected function findChannelBetweenUsers(string $fromUser, string $toUser, string $referenceType)
    {
        $channelIds = $this->channelUser->where(['user_id' => $fromUser])->pluck('channel_id')->toArray();

        return $this->channelList
            ->whereIn('id', $channelIds)
            ->where('reference_type', $referenceType)
            ->whereHas('channelUsers', function ($query) use ($toUser) {
                $query->where(['user_id' => $toUser]);
            })
            ->latest()
            ->first();
    }

    /**
     * Resolve the app-specific support channel, upgrading legacy "support" threads in place.
     */
    protected function findOrCreateSupportChannel(string $fromUser, string $toUser, string $app)
    {
        $referenceType = support_channel_reference_type_for_app($app);

        $existing = $this->findChannelBetweenUsers($fromUser, $toUser, $referenceType);
        if ($existing) {
            ensure_all_admin_staff_on_support_channel($existing->id);

            return $existing;
        }

        if ($referenceType !== 'support') {
            $legacy = $this->findChannelBetweenUsers($fromUser, $toUser, 'support');
            if ($legacy) {
                $legacy->reference_type = $referenceType;
                $legacy->save();
                ensure_all_admin_staff_on_support_channel($legacy->id);

                return $legacy->fresh();
            }
        }

        $channel = $this->createNewChannel($fromUser, $toUser, '', $referenceType);
        ensure_all_admin_staff_on_support_channel($channel->id);

        return $channel;
    }

    /**
     * @return array{channel: ChannelList, created: bool}
     */
    protected function findOrCreateAdminSupportChannel(string $adminUserId, string $supportUserId, string $referenceType): array
    {
        return find_or_create_admin_support_channel($adminUserId, $supportUserId, $referenceType);
    }

    protected function supportReferenceTypeForUserType(?string $userType): ?string
    {
        return support_channel_reference_type_for_user_type($userType);
    }

    protected function ensureChannelUser(string $channelId, string $userId): void
    {
        ensure_support_channel_user($channelId, $userId);
    }

    protected function resolveAdminSupportChannelForSend(string $channelId, string $adminUserId): string
    {
        return resolve_admin_support_channel_for_send($channelId, $adminUserId);
    }

    function formatConversations($channelList): void
    {
        $channelList->each(function ($channel) {
            $this->formatConversation($channel);
        });
    }

    function formatConversation($channel): void
    {
        $lastConversation = $channel?->channelLastConversation;
        $lastConversationFiles = $lastConversation?->conversationLastFiles;
        $lastUser = $lastConversation?->user;
        $channel->last_message_sent_user = $lastUser
            ? trim(($lastUser->first_name ?? '').' '.($lastUser->last_name ?? ''))
            : null;
        $channel->last_sent_message = $lastConversation?->message;
        $channel->last_sent_attachment_type = $lastConversationFiles?->last()?->file_type;
        $channel->last_sent_files_count = (int) $lastConversationFiles?->count();
        unset($channel->channelLastConversation);
    }

    /**
     * Mark channel read for the current user and paginate messages without a per-row whereHas.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|null
     */
    protected function paginateChannelConversationForUser($request, ?array $with = null)
    {
        $channelId = (string) $request['channel_id'];
        $userId = (string) $request->user()->id;

        $updated = $this->channelUser
            ->where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->update([
                'is_read' => 1,
                'read_at' => now(),
            ]);

        if ($updated === 0 && ! $this->healSupportChannelMembership($channelId, $userId)) {
            return null;
        }

        if ($updated === 0) {
            $this->channelUser
                ->where('channel_id', $channelId)
                ->where('user_id', $userId)
                ->update([
                    'is_read' => 1,
                    'read_at' => now(),
                ]);
        }

        $paginator = $this->channelConversation
            ->where('channel_id', $channelId)
            ->with($with ?? $this->conversationApiEagerLoads())
            ->latest()
            ->paginate(
                $request['limit'] ?? 30,
                ['*'],
                'offset',
                $request['offset'] ?? 1
            )
            ->withPath('');

        $this->prepareConversationMessagesForApi($paginator->getCollection());

        return $paginator;
    }

    protected function healSupportChannelMembership(string $channelId, string $userId): bool
    {
        $channel = $this->channelList->find($channelId);
        if (! $channel || ! is_support_channel_reference_type($channel->reference_type)) {
            return false;
        }

        $userType = $this->user->where('id', $userId)->value('user_type');
        if (in_array((string) $userType, ADMIN_USER_TYPES, true)) {
            ensure_all_admin_staff_on_support_channel($channelId);

            return $this->channelUser
                ->where('channel_id', $channelId)
                ->where('user_id', $userId)
                ->exists();
        }

        // Never attach a different provider/customer to another account's support thread.
        $otherNonAdminMemberExists = $this->channelUser
            ->where('channel_id', $channelId)
            ->where('user_id', '!=', $userId)
            ->whereHas('user', fn ($query) => $query->whereNotIn('user_type', ADMIN_USER_TYPES))
            ->exists();

        if ($otherNonAdminMemberExists) {
            return false;
        }

        $this->ensureChannelUser($channelId, $userId);

        return true;
    }

    /**
     * Empty channels should not appear unread — fixes legacy rows created before is_read defaulted to 0.
     */
    protected function markChannelReadWhenEmpty(?ChannelList $channel, string $userId): void
    {
        if (! $channel) {
            return;
        }

        $hasMessages = $this->channelConversation
            ->where('channel_id', $channel->id)
            ->exists();

        if ($hasMessages) {
            return;
        }

        $this->channelUser
            ->where('channel_id', $channel->id)
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update(['is_read' => 1, 'read_at' => now()]);

        $membership = $channel->channelUsers?->firstWhere('user_id', $userId);
        if ($membership) {
            $membership->is_read = 1;
        }
    }

    protected function conversationApiEagerLoads(): array
    {
        return [
            'user:id,first_name,last_name,profile_image,user_type',
            'user.storage',
            'conversationFiles:id,conversation_id,original_file_name,stored_file_name,file_type,created_at',
            'conversationFiles.storage',
        ];
    }

    /**
     * Avoid expensive appended accessors (file_size hits disk per attachment).
     */
    protected function prepareConversationMessagesForApi($messages): void
    {
        $messages->each(function ($message) {
            if ($message->relationLoaded('user') && $message->user) {
                $message->user->setAppends(['profile_image_full_path']);
                $message->user->makeHidden([
                    'identification_image_full_path',
                    'identification_image',
                    'password',
                ]);
            }

            if ($message->relationLoaded('conversationFiles')) {
                $message->conversationFiles->each(function ($file) {
                    $file->setAppends(['stored_file_name_full_path']);
                });
            }
        });
    }
}
