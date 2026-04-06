<?php

namespace Modules\WhatsAppModule\Services;

use Modules\WhatsAppModule\Entities\WhatsAppAiSetting;

class WhatsAppAiSettingsService
{
    public function __construct(
        protected WhatsAppSupportWorkHours $workHours,
        protected WhatsAppAiRuntimeResolver $runtimeResolver,
    ) {}

    public function settings(): WhatsAppAiSetting
    {
        return WhatsAppAiSetting::singleton();
    }

    /**
     * Values used in customer templates and exposed to the model via get_public_business_info.
     * Empty DB fields fall back to .env / business settings where noted.
     *
     * @return array{
     *     schedule: string,
     *     phone: string,
     *     brand: string,
     *     email: string,
     *     website: string,
     *     address: string,
     *     tagline: string,
     *     custom_1: string,
     *     custom_2: string,
     * }
     */
    public function resolvedMessagePlaceholders(): array
    {
        $row = $this->settings();

        $schedule = trim((string) $row->placeholder_schedule);
        if ($schedule === '') {
            $schedule = $this->workHours->humanReadableSchedule();
        }

        $phone = trim((string) $row->placeholder_phone);
        if ($phone === '') {
            $phone = $this->runtimeResolver->supportPhoneDisplay();
        }

        $brand = trim((string) $row->placeholder_brand);
        if ($brand === '') {
            $brand = WhatsAppAiPromptBuilder::resolveBrandName();
        }

        $email = trim((string) $row->placeholder_email);
        if ($email === '') {
            $email = $this->businessSnippet('email', 'business_information');
        }

        $website = trim((string) $row->placeholder_website);
        if ($website === '') {
            $website = $this->businessSnippet('web_url', 'business_information')
                ?: $this->businessSnippet('website', 'business_information');
        }

        $address = trim((string) $row->placeholder_address);
        if ($address === '') {
            $address = $this->businessSnippet('address', 'business_information');
        }

        return [
            'schedule' => $schedule,
            'phone' => $phone,
            'brand' => $brand,
            'email' => $email,
            'website' => $website,
            'address' => $address,
            'tagline' => trim((string) $row->placeholder_tagline),
            'custom_1' => trim((string) $row->placeholder_custom_1),
            'custom_2' => trim((string) $row->placeholder_custom_2),
        ];
    }

    /**
     * Merge admin-written templates. Placeholders: {schedule}, {phone}, {brand}, {email}, {website}, {address}, {tagline}, {custom_1}, {custom_2}
     */
    public function mergeCustomerMessagePlaceholders(string $template): string
    {
        $p = $this->resolvedMessagePlaceholders();
        $search = [
            '{schedule}', '{phone}', '{brand}', '{email}', '{website}', '{address}',
            '{tagline}', '{custom_1}', '{custom_2}',
        ];
        $replace = [
            $p['schedule'], $p['phone'], $p['brand'], $p['email'], $p['website'], $p['address'],
            $p['tagline'], $p['custom_1'], $p['custom_2'],
        ];

        return trim(str_replace($search, $replace, $template));
    }

    private function businessSnippet(string $key, string $type): string
    {
        try {
            $cfg = business_config($key, $type);
            $v = $cfg?->live_values ?? null;
            if (is_string($v) || is_numeric($v)) {
                return trim((string) $v);
            }
        } catch (\Throwable) {
        }

        return '';
    }

    public function handoffMessageForCustomer(bool $inHours): string
    {
        $row = $this->settings();
        $raw = $inHours
            ? trim((string) $row->handoff_message_in_hours)
            : trim((string) $row->handoff_message_out_hours);
        if ($raw !== '') {
            return $this->mergeCustomerMessagePlaceholders($raw);
        }

        return $this->defaultHandoffMessageForCustomer($inHours);
    }

    public function bookingProviderEscalationMessageForCustomer(): string
    {
        $row = $this->settings();
        $raw = trim((string) $row->booking_provider_escalation_message);
        if ($raw !== '') {
            return $this->mergeCustomerMessagePlaceholders($raw);
        }

        return $this->defaultBookingProviderEscalationMessage();
    }

    public function defaultHandoffMessageForCustomer(bool $inHours): string
    {
        $p = $this->resolvedMessagePlaceholders();
        $schedule = $p['schedule'];
        $displayPhone = $p['phone'];

        if ($inHours) {
            $msg = "We're connecting you with our team.\n\n"
                . "Someone will pick up this chat during support hours ({$schedule}).\n";
            if ($displayPhone !== '') {
                $msg .= "\nYou can also call: {$displayPhone}";
            }

            return $msg;
        }

        $msg = "Our live team is currently outside support hours.\n\n"
            . "We're available {$schedule}.\n";
        if ($displayPhone !== '') {
            $msg .= "\nLeave your message here or call {$displayPhone} and we'll get back to you.";
        } else {
            $msg .= "\nLeave your message here and we'll reply when we're back.";
        }

        return $msg;
    }

    public function defaultBookingProviderEscalationMessage(): string
    {
        $p = $this->resolvedMessagePlaceholders();
        $schedule = $p['schedule'];
        $phone = $p['phone'];
        $msg = "I'm sorry to hear that the provider is not picking up.\n\n"
            . "Since your booking is confirmed, we can escalate this to our support team so they can help you get in touch with the provider or arrange an alternative. Would you like us to do that?\n\n"
            . "Our support hours are {$schedule}.";
        if ($phone !== '') {
            $msg .= "\nYou can also reach our support team directly at {$phone}.";
        }

        return $msg;
    }

    /**
     * Full system instruction sent to Gemini (DB overrides / addenda applied).
     */
    public function resolvedSystemPrompt(): string
    {
        $row = $this->settings();

        if ($row->use_full_custom_prompt && trim((string) $row->custom_system_prompt) !== '') {
            return trim((string) $row->custom_system_prompt);
        }

        $parts = [WhatsAppAiPromptBuilder::baseSystemPrompt()];

        if (trim((string) $row->assistant_persona) !== '') {
            $parts[] = "### Assistant identity, tone, and behaviour (admin-configured)\n" . trim((string) $row->assistant_persona);
        }

        if (trim((string) $row->allowed_policy) !== '') {
            $parts[] = "### Configured policy: allowed access / behaviour\n" . trim((string) $row->allowed_policy);
        }

        if (trim((string) $row->forbidden_policy) !== '') {
            $parts[] = "### Configured policy: forbidden access / behaviour\n" . trim((string) $row->forbidden_policy);
        }

        if (trim((string) $row->prompt_addendum) !== '') {
            $parts[] = "### Additional instructions (admin-configured)\n" . trim((string) $row->prompt_addendum);
        }

        return implode("\n\n", $parts);
    }

    /**
     * Tool schemas sent to Gemini: code defaults merged with admin enable/disable and description overrides.
     *
     * @return list<array<string, mixed>>
     */
    public function mergedToolDeclarations(): array
    {
        $base = WhatsAppAiToolExecutor::functionDeclarations();
        $cfg = $this->settings()->tools_config;
        if (!is_array($cfg) || $cfg === []) {
            return $base;
        }

        $out = [];
        foreach ($base as $decl) {
            $name = (string) ($decl['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $entry = $cfg[$name] ?? [];
            $enabled = $entry['enabled'] ?? true;
            if ($enabled === false || $enabled === 0 || $enabled === '0') {
                continue;
            }
            $copy = $decl;
            $desc = $entry['description'] ?? null;
            if (is_string($desc) && trim($desc) !== '') {
                $copy['description'] = trim($desc);
            }
            $out[] = $copy;
        }

        return $out;
    }

    /**
     * @return list<array{name: string, default_description: string, enabled: bool, description_override: string}>
     */
    public function toolReferenceForAdmin(): array
    {
        $cfg = $this->settings()->tools_config ?? [];
        $out = [];
        foreach (WhatsAppAiToolExecutor::functionDeclarations() as $decl) {
            $name = (string) ($decl['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $c = is_array($cfg) ? ($cfg[$name] ?? []) : [];
            $enabled = $c['enabled'] ?? true;
            $enabled = !($enabled === false || $enabled === 0 || $enabled === '0');
            $ov = $c['description'] ?? null;

            $out[] = [
                'name' => $name,
                'default_description' => (string) ($decl['description'] ?? ''),
                'enabled' => $enabled,
                'description_override' => is_string($ov) ? $ov : '',
            ];
        }

        return $out;
    }

    public function flowMermaidSource(): string
    {
        $custom = trim((string) $this->settings()->flow_mermaid);

        return $custom !== '' ? $custom : WhatsAppAiPromptBuilder::defaultFlowMermaid();
    }

    /**
     * @return array<string, mixed>
     */
    public function adminRuntimeStatus(): array
    {
        return $this->runtimeResolver->adminRuntimeStatus();
    }

    /**
     * @return list<array{name: string, description: string}>
     */
    public function toolReference(): array
    {
        $out = [];
        foreach ($this->mergedToolDeclarations() as $decl) {
            $out[] = [
                'name' => (string) ($decl['name'] ?? ''),
                'description' => (string) ($decl['description'] ?? ''),
            ];
        }

        return $out;
    }
}
