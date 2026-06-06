<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\WhatsAppModule\Services\WhatsAppAiPromptBuilder;

/**
 * Structured chat UI for in-app booking wizard (cards + buttons, no ids shown).
 */
class MobileAppAiBookingUiPresenter
{
    /** @var list<string> */
    private const FLOW_STEPS = ['service', 'variation', 'schedule', 'address', 'provider', 'confirm'];

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>|null
     */
    public function buildForDraft(array $draft): ?array
    {
        $step = (string) ($draft['step'] ?? 'idle');
        if ($step === 'idle' || $step === 'done') {
            return $step === 'done' ? $this->doneUi() : null;
        }

        // Free-text step: message only — customer uses the chat input (no cards/buttons).
        if ($step === 'service_query') {
            return null;
        }

        if ($step === 'service_triage') {
            return [
                'type' => 'service_triage',
                'step' => 'service_triage',
                'compact' => true,
                'layout' => 'actions',
                'actions' => [
                    ['action' => 'proceed_booking', 'choice' => 'yes', 'label' => 'Book this service', 'style' => 'primary', 'icon' => 'home_repair_service'],
                    ['action' => 'more_triage_tips', 'choice' => 'yes', 'label' => 'More troubleshooting', 'style' => 'outline', 'icon' => 'help_outline'],
                ],
            ];
        }

        if ($step === 'cart_confirm') {
            return [
                'type' => 'cart_confirm',
                'step' => 'cart_confirm',
                'compact' => true,
                'layout' => 'actions',
                'actions' => [
                    ['action' => 'confirm_cart_action', 'choice' => 'yes', 'label' => 'Yes, go ahead', 'style' => 'primary', 'icon' => 'check'],
                    ['action' => 'cancel_cart_action', 'choice' => 'no', 'label' => 'Cancel', 'style' => 'outline', 'icon' => 'close'],
                ],
            ];
        }

        if ($step === 'service_confirm') {
            return [
                'type' => 'service_confirm',
                'step' => 'service_confirm',
                'compact' => true,
                'layout' => 'actions',
                'title' => (string) ($draft['choices']['pending_service_name'] ?? ''),
                'actions' => [
                    ['action' => 'confirm_service', 'choice' => 'yes', 'label' => 'Yes, book this', 'style' => 'primary', 'icon' => 'check'],
                    ['action' => 'show_service_options', 'choice' => 'no', 'label' => 'Show other options', 'style' => 'outline', 'icon' => 'list'],
                ],
            ];
        }

        $ui = [
            'type' => 'booking_wizard',
            'step' => $step,
            'compact' => true,
        ];

        return match ($step) {
            'service' => array_merge($ui, $this->serviceStepUi($draft)),
            'variation' => array_merge($ui, $this->variationStepUi($draft)),
            'schedule' => array_merge($ui, $this->scheduleStepUi()),
            'address' => array_merge($ui, $this->addressStepUi($draft)),
            'provider' => array_merge($ui, $this->providerStepUi($draft)),
            'ready' => array_merge($ui, $this->confirmStepUi($draft)),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function doneUi(): array
    {
        return [
            'type' => 'booking_done',
            'actions' => [
                ['action' => 'open_cart', 'label' => 'Open cart & pay', 'style' => 'primary', 'icon' => 'cart'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function serviceStepUi(array $draft): array
    {
        return [
            'layout' => 'cards',
            'cards' => $this->mapOptionCards($draft['options']['service'] ?? [], 'pick', 'name', 'home_repair_service'),
            'footer_actions' => [
                ['action' => 'start', 'label' => 'Different service', 'style' => 'text', 'icon' => 'search'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function variationStepUi(array $draft): array
    {
        $cards = [];
        foreach ($draft['options']['variation'] ?? [] as $o) {
            $cards[] = [
                'choice' => (string) ($o['pick'] ?? ''),
                'title' => (string) ($o['label'] ?? ''),
                'icon' => 'tune',
            ];
        }

        return [
            'layout' => 'cards',
            'cards' => $cards,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function scheduleStepUi(): array
    {
        $instant = (int) (business_config('instant_booking', 'booking_setup')?->live_values ?? 0) === 1;
        $actions = [];
        if ($instant) {
            $actions[] = ['action' => 'time', 'choice' => 'asap', 'label' => 'ASAP', 'style' => 'primary', 'icon' => 'schedule'];
        }
        $actions[] = ['action' => 'time', 'choice' => 'pick_datetime', 'label' => 'Pick date & time', 'style' => 'outline', 'icon' => 'event'];

        return [
            'layout' => 'actions',
            'actions' => $actions,
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function addressStepUi(array $draft): array
    {
        $cards = [];
        foreach ($draft['options']['address'] ?? [] as $o) {
            $label = trim((string) ($o['address_label'] ?? ''));
            $line = trim((string) ($o['address'] ?? ''));
            $cards[] = [
                'choice' => (string) ($o['pick'] ?? ''),
                'title' => $label !== '' ? $label : 'Service address',
                'subtitle' => $line,
                'icon' => 'location_on',
            ];
        }

        return [
            'layout' => 'cards',
            'cards' => $cards,
            'footer_actions' => [
                ['action' => 'pick', 'choice' => 'new', 'label' => 'Add new address', 'style' => 'text', 'icon' => 'add'],
                ['action' => 'pick', 'choice' => 'done', 'label' => "I've added my address", 'style' => 'text', 'icon' => 'check'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function providerStepUi(array $draft): array
    {
        $cards = [];
        foreach ($draft['options']['provider'] ?? [] as $o) {
            $pick = (int) ($o['pick'] ?? -1);
            $cards[] = [
                'choice' => (string) $pick,
                'title' => (string) ($o['name'] ?? ''),
                'subtitle' => $pick === 0 ? 'We assign the best available provider' : null,
                'icon' => $pick === 0 ? 'auto_awesome' : 'engineering',
                'highlight' => $pick === 0,
            ];
        }

        return [
            'layout' => 'cards',
            'cards' => $cards,
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function confirmStepUi(array $draft): array
    {
        $lines = [];
        $c = $draft['choices'] ?? [];
        $lines[] = ['label' => 'Service', 'value' => (string) ($c['service_name'] ?? '')];
        $lines[] = ['label' => 'Type', 'value' => (string) ($c['variation_label'] ?? '')];
        $lines[] = ['label' => 'When', 'value' => (string) ($c['schedule_label'] ?? '')];
        $addr = trim((string) ($c['address_label'] ?? ''));
        $line = trim((string) ($c['address_line'] ?? ''));
        $lines[] = ['label' => 'Where', 'value' => ($addr !== '' ? $addr.' — ' : '').$line];
        if ($c['let_company_choose'] ?? false) {
            $brand = WhatsAppAiPromptBuilder::resolveBrandName();
            $lines[] = ['label' => 'Provider', 'value' => $brand.' will choose for you'];
        } else {
            $lines[] = ['label' => 'Provider', 'value' => (string) ($c['provider_name'] ?? '')];
        }

        return [
            'layout' => 'summary',
            'summary_lines' => $lines,
            'actions' => [
                ['action' => 'confirm', 'choice' => 'yes', 'label' => 'Add to cart', 'style' => 'primary', 'icon' => 'shopping_cart'],
                ['action' => 'cancel', 'choice' => 'no', 'label' => 'Cancel', 'style' => 'outline', 'icon' => 'close'],
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @return list<array<string, mixed>>
     */
    private function mapOptionCards(array $options, string $pickKey, string $titleKey, string $icon): array
    {
        $cards = [];
        foreach ($options as $o) {
            $cards[] = [
                'choice' => (string) ($o[$pickKey] ?? ''),
                'title' => (string) ($o[$titleKey] ?? ''),
                'icon' => $icon,
            ];
        }

        return $cards;
    }

    /**
     * @return list<array{key: string, label: string, active: bool, done: bool}>
     */
    private function flowStepLabels(string $currentStep): array
    {
        $labels = [
            'service' => 'Service',
            'variation' => 'Type',
            'schedule' => 'Time',
            'address' => 'Address',
            'provider' => 'Provider',
            'confirm' => 'Confirm',
        ];
        $mapped = $currentStep === 'service_query' ? 'service' : ($currentStep === 'ready' ? 'confirm' : $currentStep);
        $idx = $this->stepIndex($mapped);
        $out = [];
        $i = 0;
        foreach (self::FLOW_STEPS as $key) {
            $out[] = [
                'key' => $key,
                'label' => $labels[$key] ?? $key,
                'active' => $i === $idx,
                'done' => $i < $idx,
            ];
            $i++;
        }

        return $out;
    }

    private function stepIndex(string $step): int
    {
        if ($step === 'ready') {
            return 5;
        }
        $pos = array_search($step, self::FLOW_STEPS, true);

        return $pos === false ? 0 : (int) $pos;
    }
}
