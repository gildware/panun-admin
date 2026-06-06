<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Reusable confirm/cancel action panels for AI agent flows.
 */
final class MobileAppAiConfirmUi
{
    /**
     * @return array<string, mixed>
     */
    public static function confirmCancel(string $type, string $actionKey, string $userText = ''): array
    {
        $hinglish = MobileAppAiReplyStyle::prefersHinglish($userText);

        return [
            'type' => $type,
            'step' => $actionKey,
            'compact' => true,
            'layout' => 'actions',
            'actions' => [
                [
                    'action' => 'confirm_'.$actionKey.'_action',
                    'choice' => 'yes',
                    'label' => $hinglish ? 'Haan, kar do' : 'Yes, go ahead',
                    'style' => 'primary',
                    'icon' => 'check',
                ],
                [
                    'action' => 'cancel_'.$actionKey.'_action',
                    'choice' => 'no',
                    'label' => 'Cancel',
                    'style' => 'outline',
                    'icon' => 'close',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function agentMenu(): array
    {
        return [
            'type' => 'assistant_actions',
            'layout' => 'actions',
            'actions' => [
                ['action' => 'open_cart', 'label' => 'Open cart', 'style' => 'primary', 'icon' => 'shopping_cart'],
                ['action' => 'open_bookings', 'label' => 'My bookings', 'style' => 'outline', 'icon' => 'event'],
                ['action' => 'open_biddings', 'label' => 'My biddings', 'style' => 'outline', 'icon' => 'gavel'],
                ['action' => 'start_booking', 'label' => 'Book a service', 'style' => 'outline', 'icon' => 'home_repair_service'],
            ],
        ];
    }
}
