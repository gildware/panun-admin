<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\BusinessSettingsModule\Entities\MobileAppAiMessage;
use Modules\UserManagement\Entities\User;

class MobileAppAiSupportService
{
    public function __construct(
        protected MobileAppAiRuntimeResolver $runtime,
        protected MobileAppAiGeminiHealthService $geminiHealth,
        protected MobileAppAiOrchestrator $orchestrator,
    ) {}

    /**
     * AI chat is available only when configured AND Gemini responds to a health probe.
     */
    public function isEnabled(bool $forceHealthProbe = false): bool
    {
        if (! $this->runtime->enabled()) {
            return false;
        }

        return $this->geminiHealth->isHealthy($forceHealthProbe);
    }

    public function getOrCreateConversation(User $user): MobileAppAiConversation
    {
        return MobileAppAiConversation::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['last_message_at' => now()]
        );
    }

    /**
     * @return array{reply: string, messages: list<array<string, mixed>>, cart_updated: bool, ui?: mixed}
     */
    public function sendMessage(User $user, string $messageText): array
    {
        if (! $this->isEnabled()) {
            return $this->unavailablePayload($user);
        }

        $text = trim($messageText);
        if ($text === '') {
            return [
                'reply' => '',
                'messages' => $this->formatMessagesForApi($user),
                'cart_updated' => false,
            ];
        }

        $conversation = $this->getOrCreateConversation($user);

        return $this->orchestrator->handleUserMessage($user, $conversation, $text);
    }

    /**
     * @return array{reply: string, messages: list<array<string, mixed>>, cart_updated: bool, ui?: mixed}
     */
    public function quickIntent(User $user, string $intent, ?string $query = null): array
    {
        if (! $this->isEnabled()) {
            return $this->unavailablePayload($user);
        }

        $conversation = $this->getOrCreateConversation($user);
        $message = match ($intent) {
            'start_booking' => $query !== null && trim($query) !== ''
                ? 'book '.trim($query)
                : 'I want to book a service',
            'booking_status' => $query !== null && trim($query) !== ''
                ? 'booking status '.trim($query)
                : 'show my bookings',
            'human_support' => 'talk to human support',
            'troubleshoot' => $query !== null && trim($query) !== ''
                ? trim($query)
                : 'help with the app',
            default => trim((string) $query) !== '' ? trim((string) $query) : $intent,
        };

        return $this->orchestrator->handleUserMessage($user, $conversation, $message);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function formatMessagesForApi(User $user): array
    {
        return $this->orchestrator->formatMessages($user);
    }

    public function clearConversation(User $user): void
    {
        $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
        if (! $conversation) {
            return;
        }

        MobileAppAiMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('source', MobileAppAiMessage::SOURCE_MOBILE_APP)
            ->delete();
        $conversation->update([
            'last_message_at' => now(),
            'booking_draft' => null,
        ]);
    }

    /**
     * @return array{reply: string, messages: list<array<string, mixed>>, cart_updated: bool}
     */
    private function unavailablePayload(User $user): array
    {
        return [
            'reply' => __('mobile_app_ai.service_unavailable'),
            'messages' => $this->formatMessagesForApi($user),
            'cart_updated' => false,
        ];
    }
}
