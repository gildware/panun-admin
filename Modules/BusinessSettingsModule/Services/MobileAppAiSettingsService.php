<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiSetting;
use Modules\WhatsAppModule\Services\WhatsAppAiSettingsService;

class MobileAppAiSettingsService
{
    public function __construct(
        protected WhatsAppAiSettingsService $whatsappAiSettings,
        protected MobileAppAiSupportToolPolicy $supportToolPolicy,
    ) {}

    public function settings(): MobileAppAiSetting
    {
        return MobileAppAiSetting::singleton();
    }

    public function resolvedSystemPrompt(): string
    {
        $row = $this->settings();

        if ($row->use_full_custom_prompt && trim((string) $row->custom_system_prompt) !== '') {
            return trim((string) $row->custom_system_prompt);
        }

        $parts = [];
        if ($row->inherit_whatsapp_ai) {
            $parts[] = $this->whatsappAiSettings->resolvedSystemPrompt();
        } else {
            $parts[] = MobileAppAiPromptBuilder::baseSystemPrompt();
        }

        if (trim((string) $row->assistant_persona) !== '') {
            $parts[] = "### Mobile app assistant persona (admin-configured)\n".trim((string) $row->assistant_persona);
        }

        if (trim((string) $row->prompt_addendum) !== '') {
            $parts[] = "### Additional mobile app instructions\n".trim((string) $row->prompt_addendum);
        }

        $parts[] = MobileAppAiPromptBuilder::channelEnforcementAppendix();

        return implode("\n\n", $parts);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function mergedToolDeclarations(): array
    {
        $declarations = MobileAppAiToolExecutor::functionDeclarations();
        if ($this->settings()->inherit_whatsapp_ai) {
            $byName = [];
            foreach (array_merge($this->whatsappAiSettings->mergedToolDeclarations(), $declarations) as $decl) {
                $name = (string) ($decl['name'] ?? '');
                if ($name !== '') {
                    $byName[$name] = $decl;
                }
            }
            $declarations = array_values($byName);
        }

        return $this->supportToolPolicy->filterDeclarations($declarations);
    }
}
