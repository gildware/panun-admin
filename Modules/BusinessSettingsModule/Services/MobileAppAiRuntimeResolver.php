<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiSetting;
use Modules\WhatsAppModule\Services\WhatsAppAiRuntimeResolver;

final class MobileAppAiRuntimeResolver
{
    public function __construct(
        protected WhatsAppAiRuntimeResolver $whatsappRuntime,
    ) {}

    private function row(): MobileAppAiSetting
    {
        return MobileAppAiSetting::singleton();
    }

    public function enabled(): bool
    {
        if (!(bool) $this->row()->is_enabled) {
            return false;
        }

        if ($this->row()->inherit_whatsapp_ai) {
            return $this->whatsappRuntime->aiSupportEnabled();
        }

        return trim((string) config('services.gemini.api_key', '')) !== '';
    }

    public function geminiModel(): string
    {
        $v = $this->row()->gemini_model;
        if (is_string($v) && trim($v) !== '') {
            return trim($v);
        }

        if ($this->row()->inherit_whatsapp_ai) {
            return $this->whatsappRuntime->geminiModel();
        }

        return (string) config('whatsappmodule.gemini_model', 'gemini-2.5-flash');
    }

    public function maxHistoryMessages(): int
    {
        $n = (int) $this->row()->max_history_messages;

        return $n > 0 ? min($n, 60) : 24;
    }
}
