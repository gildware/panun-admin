<?php

namespace Modules\ChattingModule\Traits;

use Ramsey\Uuid\Nonstandard\Uuid;

trait ChattingTrait
{
    public function createNewChannel($fromUser, $toUser, $referenceId = null, $referenceType = null)
    {
        $channelIds = $this->channelUser->where(['user_id' => $fromUser])->pluck('channel_id')->toArray();
        $findChannel = $this->channelList
            ->whereIn('id', $channelIds)
            ->whereHas('channelUsers', function ($query) use ($toUser) {
                $query->where(['user_id' => $toUser]);
            })->latest()->first();

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
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'id' => Uuid::uuid4(),
                    'channel_id' => $channel->id,
                    'user_id' => $toUser,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);
            return $channel;
        }

        return $findChannel;
    }

    function formatConversations($channelList): void
    {
        $channelList->each(function ($channel) {
            $lastConversation = $channel?->channelLastConversation;
            $lastConversationFiles = $lastConversation?->conversationLastFiles;
            $channel->last_message_sent_user = $lastConversation?->user->first_name . ' ' . $lastConversation?->user->last_name;
            $channel->last_sent_message = $lastConversation?->message;
            $channel->last_sent_attachment_type = $lastConversationFiles?->last()?->file_type;
            $channel->last_sent_files_count = (int)$lastConversationFiles?->count();
            unset($channel->channelLastConversation);
        });
    }
    function formatConversation($channel): void
    {
        $lastConversation = $channel?->channelLastConversation;
        $lastConversationFiles = $lastConversation?->conversationLastFiles;
        $channel->last_message_sent_user = $lastConversation?->user->first_name . ' ' . $lastConversation?->user->last_name;
        $channel->last_sent_message = $lastConversation?->message;
        $channel->last_sent_attachment_type = $lastConversationFiles?->last()?->file_type;
        $channel->last_sent_files_count = (int)$lastConversationFiles?->count();
        unset($channel->channelLastConversation);
    }

    /**
     * Mark channel read for the current user and paginate messages without a per-row whereHas.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|null
     */
    protected function paginateChannelConversationForUser($request, ?array $with = null)
    {
        $updated = $this->channelUser
            ->where('channel_id', $request['channel_id'])
            ->where('user_id', $request->user()->id)
            ->update(['is_read' => 1]);

        if ($updated === 0) {
            return null;
        }

        $paginator = $this->channelConversation
            ->where('channel_id', $request['channel_id'])
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
