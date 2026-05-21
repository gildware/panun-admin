<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\Log;
use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\BusinessSettingsModule\Entities\MobileAppAiMessage;
use Modules\UserManagement\Entities\User;

class MobileAppAiSupportService
{
    public function __construct(
        protected MobileAppAiRuntimeResolver $runtime,
        protected MobileAppAiGeminiRunner $runner,
    ) {}

    public function isEnabled(): bool
    {
        return $this->runtime->enabled();
    }

    public function getOrCreateConversation(User $user): MobileAppAiConversation
    {
        return MobileAppAiConversation::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['last_message_at' => now()]
        );
    }

    /**
     * @return array{reply: string, messages: list<array<string, mixed>>}
     */
    public function sendMessage(User $user, string $messageText): array
    {
        if (!$this->isEnabled()) {
            return [
                'reply' => __('mobile_app_ai.disabled'),
                'messages' => [],
            ];
        }

        $text = trim($messageText);
        if ($text === '') {
            return [
                'reply' => '',
                'messages' => $this->formatMessagesForApi($user),
            ];
        }

        $conversation = $this->getOrCreateConversation($user);

        MobileAppAiMessage::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'source' => MobileAppAiMessage::SOURCE_MOBILE_APP,
            'body' => $text,
        ]);

        try {
            $reply = $this->runner->generateReply($user, $conversation);
        } catch (\Throwable $e) {
            Log::error('Mobile app AI failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            $reply = __('mobile_app_ai.fallback_reply');
        }

        MobileAppAiMessage::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'source' => MobileAppAiMessage::SOURCE_MOBILE_APP,
            'body' => $reply,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return [
            'reply' => $reply,
            'messages' => $this->formatMessagesForApi($user),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function formatMessagesForApi(User $user): array
    {
        $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
        if (!$conversation) {
            return [];
        }

        $limit = $this->runtime->maxHistoryMessages();

        return MobileAppAiMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('source', MobileAppAiMessage::SOURCE_MOBILE_APP)
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(static fn (MobileAppAiMessage $m): array => [
                'id' => $m->id,
                'role' => $m->role,
                'body' => $m->body,
                'created_at' => $m->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    public function clearConversation(User $user): void
    {
        $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
        if (!$conversation) {
            return;
        }

        MobileAppAiMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('source', MobileAppAiMessage::SOURCE_MOBILE_APP)
            ->delete();
        $conversation->update(['last_message_at' => now()]);
    }
}
