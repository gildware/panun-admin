<?php

namespace Modules\WhatsAppModule\Services;

use Modules\WhatsAppModule\Entities\WhatsAppAiSetting;

class WhatsAppAiSettingsService
{
    public function settings(): WhatsAppAiSetting
    {
        return WhatsAppAiSetting::singleton();
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
        $key = (string) config('services.gemini.api_key');

        return [
            'ai_support_enabled' => (bool) config('whatsappmodule.ai_support_enabled'),
            'gemini_key_set' => $key !== '',
            'gemini_model' => (string) config('whatsappmodule.gemini_model', 'gemini-2.5-flash'),
            'greeting_buttons' => (bool) config('whatsappmodule.ai_greeting_buttons', true),
            'support_hours' => (string) config('whatsappmodule.support_work_hours_start') . '–' . (string) config('whatsappmodule.support_work_hours_end')
                . ' ' . (string) config('whatsappmodule.support_timezone'),
            'support_phone_display' => (string) config('whatsappmodule.support_phone_display'),
            'queue_connection' => (string) config('queue.default'),
        ];
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
