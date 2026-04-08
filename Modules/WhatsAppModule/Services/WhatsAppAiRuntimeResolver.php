<?php

namespace Modules\WhatsAppModule\Services;

use Modules\WhatsAppModule\Entities\WhatsAppAiSetting;

/**
 * Operational WhatsApp AI settings: database overrides with .env / config fallback.
 * GEMINI_API_KEY stays in environment only (see config/services.php).
 */
final class WhatsAppAiRuntimeResolver
{
    private function row(): WhatsAppAiSetting
    {
        return WhatsAppAiSetting::singleton();
    }

    public function aiSupportEnabled(): bool
    {
        $v = $this->row()->db_ai_support_enabled;

        return $v !== null ? (bool) $v : (bool) config('whatsappmodule.ai_support_enabled');
    }

    public function geminiModel(): string
    {
        $v = $this->row()->db_gemini_model;
        if (is_string($v) && trim($v) !== '') {
            return trim($v);
        }

        return (string) config('whatsappmodule.gemini_model', 'gemini-2.5-flash');
    }

    public function greetingButtons(): bool
    {
        $v = $this->row()->db_greeting_buttons;

        return $v !== null ? (bool) $v : (bool) config('whatsappmodule.ai_greeting_buttons', true);
    }

    public function supportWorkHoursStart(): string
    {
        $v = $this->row()->db_support_hours_start;
        if (is_string($v) && trim($v) !== '') {
            return trim($v);
        }

        return (string) config('whatsappmodule.support_work_hours_start', '09:00');
    }

    public function supportWorkHoursEnd(): string
    {
        $v = $this->row()->db_support_hours_end;
        if (is_string($v) && trim($v) !== '') {
            return trim($v);
        }

        return (string) config('whatsappmodule.support_work_hours_end', '18:00');
    }

    /**
     * ISO weekdays when live support is available: 1 = Monday … 7 = Sunday.
     *
     * @return list<int>
     */
    public function supportWorkDays(): array
    {
        $raw = $this->row()->db_support_days;
        if (is_array($raw) && $raw !== []) {
            $days = array_values(array_unique(array_map('intval', $raw)));
            sort($days);
            $days = array_values(array_filter($days, static fn (int $d): bool => $d >= 1 && $d <= 7));
            if ($days !== []) {
                return $days;
            }
        }

        $cfg = config('whatsappmodule.support_work_days');
        if (is_array($cfg) && $cfg !== []) {
            $days = array_values(array_unique(array_map('intval', $cfg)));
            sort($days);
            $days = array_values(array_filter($days, static fn (int $d): bool => $d >= 1 && $d <= 7));

            return $days !== [] ? $days : [1, 2, 3, 4, 5];
        }

        return [1, 2, 3, 4, 5];
    }

    /**
     * Customer-facing support times are always interpreted in India Standard Time (IST).
     */
    public function supportTimezone(): string
    {
        return (string) config('whatsappmodule.support_timezone', 'Asia/Kolkata');
    }

    public function supportPhoneDisplay(): string
    {
        $v = $this->row()->db_support_phone_display;
        if (is_string($v) && trim($v) !== '') {
            return trim($v);
        }

        return trim((string) config('whatsappmodule.support_phone_display', ''));
    }

    public function aiDispatchUsesSync(): bool
    {
        $v = $this->row()->db_ai_dispatch_sync;

        return $v !== null ? (bool) $v : (bool) config('whatsappmodule.ai_dispatch_sync', true);
    }

    /**
     * When not using sync dispatch: explicit queue connection, or null to use Laravel's default.
     */
    public function queueConnectionForDispatch(): ?string
    {
        if ($this->aiDispatchUsesSync()) {
            return null;
        }
        $q = $this->row()->db_queue_connection;
        if (is_string($q) && trim($q) !== '') {
            return trim($q);
        }

        return null;
    }

    public function effectiveQueueLabel(): string
    {
        if ($this->aiDispatchUsesSync()) {
            return 'sync';
        }
        $c = $this->queueConnectionForDispatch();

        return $c !== null && $c !== '' ? $c : (string) config('queue.default', 'sync');
    }

    /**
     * @return array<string, mixed>
     */
    public function adminRuntimeStatus(): array
    {
        $row = $this->row();
        $key = (string) config('services.gemini.api_key');

        return [
            'ai_support_enabled' => $this->aiSupportEnabled(),
            'ai_support_enabled_src' => $row->db_ai_support_enabled !== null ? 'db' : 'env',
            'gemini_key_set' => $key !== '',
            'gemini_model' => $this->geminiModel(),
            'gemini_model_src' => (is_string($row->db_gemini_model) && trim($row->db_gemini_model) !== '') ? 'db' : 'env',
            'greeting_buttons' => $this->greetingButtons(),
            'greeting_buttons_src' => $row->db_greeting_buttons !== null ? 'db' : 'env',
            'support_hours' => $this->supportWorkHoursStart().'–'.$this->supportWorkHoursEnd()
                .' '.$this->supportTimezone(),
            'support_hours_src' => (
                (is_string($row->db_support_hours_start) && trim($row->db_support_hours_start) !== '')
                || (is_string($row->db_support_hours_end) && trim($row->db_support_hours_end) !== '')
                || (is_array($row->db_support_days) && $row->db_support_days !== [])
            ) ? 'db' : 'env',
            'support_phone_display' => $this->supportPhoneDisplay(),
            'support_phone_display_src' => (is_string($row->db_support_phone_display) && trim($row->db_support_phone_display) !== '') ? 'db' : 'env',
            'queue_connection' => $this->effectiveQueueLabel(),
            'queue_src' => (
                $row->db_ai_dispatch_sync !== null
                || (is_string($row->db_queue_connection) && trim($row->db_queue_connection) !== '')
            ) ? 'db' : 'env',
            /** Laravel default queue driver name (sync = inline, database = DB table + worker). */
            'queue_default_driver' => (string) config('queue.default', 'sync'),
            /**
             * AI set to queued but app default queue is sync — jobs still run inside the request.
             * Set QUEUE_CONNECTION=database and run a worker.
             */
            'queue_async_but_driver_is_sync' => ! $this->aiDispatchUsesSync()
                && (string) config('queue.default', 'sync') === 'sync',
        ];
    }
}
