<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\WhatsAppModule\Services\WhatsAppAiPromptBuilder;

/**
 * Friendly replies for greetings and unclear messages — before Gemini or generic fallback.
 */
final class MobileAppAiConversationalResponder
{
    /**
     * @param  array<string, mixed>  $draft
     * @return array{reply: string, ui: array<string, mixed>}|null
     */
    public static function tryRespond(string $text, array $draft = []): ?array
    {
        $t = trim($text);
        if ($t === '') {
            return null;
        }

        $step = (string) ($draft['step'] ?? 'idle');
        if (MobileAppAiBookingMessageDetector::isActiveBookingWizardStep($step)) {
            return null;
        }

        if (self::isGreeting($t)) {
            return ['reply' => self::greetingMessage(), 'ui' => self::homeActionsUi()];
        }

        if (self::isThanks($t)) {
            return [
                'reply' => "You're welcome! Tell me anytime if you need another booking or help with the app.",
                'ui' => self::homeActionsUi(),
            ];
        }

        if (self::isConfusion($t)) {
            return [
                'reply' => 'I can help with **booking**, **booking status**, or **app issues**. What do you need?',
                'ui' => self::homeActionsUi(),
            ];
        }

        return null;
    }

    public static function isGreeting(string $text): bool
    {
        $t = mb_strtolower(trim($text));

        return (bool) preg_match(
            '/^(hi|hello|hey|hola|namaste|salam|assalam|asalam|good\s*(morning|evening|afternoon)|howdy)\b[!.?\s]*$/iu',
            $t
        ) || $t === 'hlw' || $t === 'hii';
    }

    public static function isConfusion(string $text): bool
    {
        $t = trim($text);

        return $t === '?' || $t === '??' || mb_strtolower($t) === 'what' || $t === 'huh';
    }

    public static function isThanks(string $text): bool
    {
        $t = mb_strtolower(trim($text));

        return (bool) preg_match('/^(thanks|thank you|shukriya|dhanyavad)\b/iu', $t);
    }

    public static function isReaffirmation(string $text): bool
    {
        $lower = mb_strtolower(trim($text));

        if (MobileAppAiBookingMessageDetector::isAffirmative($text)) {
            return true;
        }

        return (bool) preg_match(
            '/\b(bola|boli|bolo|keh\s*diya|kaha|kahaa|same|wahi|yahi|yes|haan|ha|theek|sahi|ok|okay|correct|right|that\s*one|yehi|yahi\s*wala)\b/iu',
            $lower
        );
    }

    public static function greetingMessage(): string
    {
        $brand = WhatsAppAiPromptBuilder::resolveBrandName();

        return "Hi! I'm **{$brand}** support — I can see your cart and bookings and help **book**, **change cart**, **check status**, or **app help**. What do you need?";
    }

    /**
     * @return array<string, mixed>
     */
    public static function homeActionsUi(): array
    {
        return [
            'type' => 'assistant_actions',
            'layout' => 'actions',
            'actions' => [
                ['action' => 'start_booking', 'label' => 'Book a service', 'style' => 'primary', 'icon' => 'home_repair_service'],
                ['action' => 'booking_status', 'label' => 'My bookings', 'style' => 'outline', 'icon' => 'event'],
                ['action' => 'troubleshoot', 'label' => 'App help', 'style' => 'outline', 'icon' => 'help_outline'],
                ['action' => 'open_support', 'label' => 'Talk to support', 'style' => 'text', 'icon' => 'support'],
            ],
        ];
    }
}
