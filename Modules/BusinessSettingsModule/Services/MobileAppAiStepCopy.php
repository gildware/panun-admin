<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\WhatsAppModule\Services\WhatsAppAiPromptBuilder;

/**
 * Short customer-facing copy for in-app AI chat (details live in UI cards/buttons).
 */
final class MobileAppAiStepCopy
{
    public static function brand(): string
    {
        return WhatsAppAiPromptBuilder::resolveBrandName();
    }

    public static function bookingStart(): string
    {
        return 'What service do you need? (e.g. *AC repair*)';
    }

    public static function serviceAutoSelected(string $serviceName): string
    {
        return '**'.$serviceName.'** — got it.';
    }

    public static function schedulePrompt(?string $prefix = null): string
    {
        $line = '**When** should we visit? (e.g. *kal 10 baje*) or tap below.';

        return $prefix !== null && $prefix !== '' ? trim($prefix.' '.$line) : $line;
    }

    public static function addressPrompt(string $scheduleLabel = ''): string
    {
        if ($scheduleLabel !== '') {
            return '**Where** should we come? ('.$scheduleLabel.')';
        }

        return '**Where** should we come?';
    }

    public static function providerPrompt(): string
    {
        return '**Who** should do the job? (or we can choose)';
    }

    public static function confirmPrompt(): string
    {
        return 'Tap **Add to cart** or type **yes**.';
    }

    public static function troubleshootIntro(): string
    {
        return 'What app issue? (payment, cart, login, address…)';
    }

    public static function helpTipForStep(string $step): ?string
    {
        return null;
    }

    public static function wizardTextHint(string $step): string
    {
        return match ($step) {
            'schedule' => "Try **ASAP**, **kal 10 baje**, or **tomorrow 5pm**.",
            'address' => 'Tap an address above or type *home* / *office*.',
            'provider' => 'Tap a provider or type *you choose*.',
            'ready' => 'Tap **Add to cart** or type **yes**.',
            'service' => 'Tap a service or type what you need.',
            'variation' => 'Tap the type that fits your job.',
            default => __('mobile_app_ai.fallback_reply'),
        };
    }
}
