<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\BusinessSettingsModule\Entities\MobileAppAiMessage;
use Modules\UserManagement\Entities\User;

class MobileAppAiChatBookingService
{
    public function __construct(
        protected MobileAppAiBookingSessionService $session,
        protected MobileAppAiBookingUiPresenter $uiPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handleAction(User $user, array $payload): array
    {
        $action = strtolower(trim((string) ($payload['action'] ?? '')));
        $conversation = MobileAppAiConversation::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['last_message_at' => now()]
        );

        $persistUser = $this->shouldPersistUserMessage($payload);
        $persistAssistant = $this->shouldPersistAssistantMessage($payload);

        if ($persistUser) {
            $userLabel = $this->userMessageLabel($action, $payload, $conversation);
            $userBody = $userLabel ?? trim((string) ($payload['message'] ?? $payload['query'] ?? ''));
            if ($userBody !== '') {
                MobileAppAiMessage::query()->create([
                    'conversation_id' => $conversation->id,
                    'role' => 'user',
                    'source' => MobileAppAiMessage::SOURCE_MOBILE_APP,
                    'body' => $userBody,
                ]);
            }
        }

        $result = $this->session->handle($user, $payload);
        $draft = $conversation->fresh()?->booking_draft ?? [];
        if (! is_array($draft)) {
            $draft = [];
        }

        $assistantBody = (string) ($result['customer_message'] ?? '');
        if ($assistantBody === '' && ($result['ok'] ?? false)) {
            $assistantBody = 'Please continue with your booking.';
        }

        $ui = is_array($result['ui'] ?? null) ? $result['ui'] : null;
        if ($ui === null && ($result['ok'] ?? false)) {
            $ui = $this->uiPresenter->buildForDraft($draft);
            if (($result['cart_updated'] ?? false) === true && (string) ($draft['step'] ?? '') === 'done') {
                $ui = $this->uiPresenter->buildForDraft(['step' => 'done']);
            }
        }

        $meta = $this->assistantMeta($draft, $ui);

        if ($persistAssistant) {
            MobileAppAiMessage::query()->create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'source' => MobileAppAiMessage::SOURCE_MOBILE_APP,
                'body' => $assistantBody,
                'meta' => $meta,
            ]);
            $conversation->update(['last_message_at' => now()]);
        }

        return [
            'ok' => $result['ok'] ?? false,
            'reply' => $assistantBody,
            'ui' => $ui,
            'cart_updated' => ($result['cart_updated'] ?? false) === true,
            'wizard_step' => $result['wizard_step'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function shouldPersistUserMessage(array $payload): bool
    {
        if (($payload['persist_chat_messages'] ?? true) === false) {
            return false;
        }

        return ($payload['persist_user_message'] ?? true) !== false;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function shouldPersistAssistantMessage(array $payload): bool
    {
        if (($payload['persist_chat_messages'] ?? true) === false) {
            return false;
        }

        return ($payload['persist_assistant_message'] ?? true) !== false;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function userMessageLabel(string $action, array $payload, MobileAppAiConversation $conversation): ?string
    {
        if ($action === 'start') {
            return 'Book a service';
        }
        if ($action === 'search') {
            $q = trim((string) ($payload['query'] ?? ''));

            return $q !== '' ? $q : 'Search services';
        }

        $choice = trim((string) ($payload['choice'] ?? ''));
        if ($choice === '') {
            return match ($action) {
                'confirm' => 'Yes, add to cart',
                'cancel' => 'Cancel booking',
                'time' => isset($payload['when']) ? 'Visit: '.(string) $payload['when'] : 'ASAP',
                default => null,
            };
        }

        if ($action === 'time' && ($payload['asap'] ?? false)) {
            return 'ASAP';
        }

        if ($action === 'time' && $choice === 'pick_datetime') {
            return 'Pick date & time';
        }

        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
        $step = (string) ($draft['step'] ?? 'service');
        if ($action === 'pick' && $step === 'service') {
            $step = 'service';
        }
        if ($action === 'time') {
            return $choice;
        }

        return $this->resolveChoiceLabel($draft, $step, $choice) ?? $choice;
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    private function resolveChoiceLabel(array $draft, string $step, string $choice): ?string
    {
        $key = match ($step) {
            'service' => 'service',
            'variation' => 'variation',
            'address' => 'address',
            'provider' => 'provider',
            default => null,
        };
        if ($key === null) {
            return null;
        }

        $options = $draft['options'][$key] ?? [];
        if (! is_array($options)) {
            return null;
        }

        if (preg_match('/^\d+$/', $choice)) {
            $n = (int) $choice;
            foreach ($options as $o) {
                if ((int) ($o['pick'] ?? -1) === $n) {
                    return (string) ($o['name'] ?? $o['label'] ?? $o['address'] ?? $choice);
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>|null
     */
    private function assistantMeta(array $draft, ?array $ui): ?array
    {
        $meta = [];
        if ($ui !== null) {
            $meta['ui'] = $ui;
        }
        if ((string) ($draft['step'] ?? '') === 'service_query') {
            $meta['awaiting_input'] = true;
        }

        return $meta !== [] ? $meta : null;
    }
}
