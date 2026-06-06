<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\WhatsAppModule\Services\WhatsAppAiSettingsService;
use Modules\WhatsAppModule\Services\WhatsAppSupportWorkHours;

/**
 * Human support handoff copy for mobile in-app chat.
 */
class MobileAppAiHandoffService
{
    public function __construct(
        protected WhatsAppAiSettingsService $aiSettings,
        protected WhatsAppSupportWorkHours $workHours,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildHandoffResult(?string $topic = null): array
    {
        $inHours = $this->workHours->isWithinSupportHours();
        $customerText = $this->aiSettings->handoffMessageForCustomer($inHours);
        $p = $this->aiSettings->resolvedMessagePlaceholders();

        return [
            'ok' => true,
            'within_support_hours' => $inHours,
            'customer_message' => $customerText,
            'public_support_phone' => $p['phone'] ?? '',
            'schedule' => $p['schedule'] ?? '',
            'topic' => $topic ?? '',
            'ui' => [
                'type' => 'assistant_actions',
                'layout' => 'actions',
                'actions' => [
                    ['action' => 'open_support', 'label' => 'Help & Support', 'style' => 'primary', 'icon' => 'support'],
                    ['action' => 'call_support', 'label' => 'Call support', 'style' => 'outline', 'icon' => 'phone'],
                ],
            ],
            'orchestrator_finalize' => [
                'send_exact_customer_text' => $customerText,
            ],
        ];
    }

    public function unclearFallbackMessage(): string
    {
        $p = $this->aiSettings->resolvedMessagePlaceholders();
        $phone = trim((string) ($p['phone'] ?? ''));
        $schedule = trim((string) ($p['schedule'] ?? ''));

        $msg = "I'm sorry I didn't quite catch that.\n\n";
        $msg .= "You can ask me to **book a service**, check **booking status**, or help with **payment, cart, or sign-in** issues.";
        if ($phone !== '') {
            $msg .= "\n\nFor direct help, call us: **{$phone}**";
        }
        if ($schedule !== '') {
            $msg .= "\nSupport hours: {$schedule}";
        }
        $msg .= "\n\nOr tap **Help & Support** in the menu.";

        return $msg;
    }
}
