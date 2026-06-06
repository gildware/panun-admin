<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;

/**
 * Working memory stored in conversation booking_draft.conversation_state.
 */
class MobileAppAiConversationStateService
{
    /**
     * @return array<string, mixed>
     */
    public function read(?MobileAppAiConversation $conversation): array
    {
        if ($conversation === null) {
            return $this->emptyState();
        }

        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
        $state = is_array($draft['conversation_state'] ?? null) ? $draft['conversation_state'] : [];

        return array_merge($this->emptyState(), $state);
    }

    /**
     * @param  array<string, mixed>  $patch
     */
    public function write(MobileAppAiConversation $conversation, array $patch): void
    {
        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
        $current = is_array($draft['conversation_state'] ?? null) ? $draft['conversation_state'] : [];
        $draft['conversation_state'] = array_merge($this->emptyState(), $current, $patch);
        $conversation->booking_draft = $draft;
        $conversation->save();
    }

    public function recordTurn(
        MobileAppAiConversation $conversation,
        string $intent,
        string $handler,
        ?string $activeProblem = null,
        ?string $activeService = null,
        ?string $pendingQuestion = null,
        ?string $pendingEntity = null,
    ): void {
        $this->write($conversation, array_filter([
            'last_intent' => $intent,
            'last_handler' => $handler,
            'active_problem' => $activeProblem,
            'active_service' => $activeService,
            'pending_question' => $pendingQuestion,
            'pending_entity' => $pendingEntity,
        ], static fn ($v): bool => $v !== null && $v !== ''));
    }

    public function clearPendingQuestion(MobileAppAiConversation $conversation): void
    {
        $this->write($conversation, [
            'pending_question' => '',
            'pending_entity' => '',
        ]);
    }

    public function hasPendingQuestion(?MobileAppAiConversation $conversation): bool
    {
        $state = $this->read($conversation);

        return trim((string) ($state['pending_question'] ?? '')) !== '';
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyState(): array
    {
        return [
            'active_problem' => '',
            'active_service' => '',
            'active_booking' => '',
            'pending_question' => '',
            'pending_entity' => '',
            'last_intent' => '',
            'last_handler' => '',
        ];
    }
}
