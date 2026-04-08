<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clear stored non-text inbound copy when it still matches older built-in defaults so admin and sends use
 * {@see \Modules\WhatsAppModule\Services\WhatsAppAiSettingsService::defaultNonTextInboundMessageTemplate()}
 * and {@see \Modules\WhatsAppModule\Services\WhatsAppAiSettingsService::defaultNonTextMetaButtons()}.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_ai_settings')) {
            return;
        }

        $normalize = static function (?string $s): string {
            $s = (string) $s;
            $s = str_replace(["\r\n", "\r"], "\n", $s);

            return trim($s);
        };

        $oldMessage = $normalize(
            "I'm an automated assistant and I can't view images, voice notes, or files in this chat.\n\n"
            ."Please type your message here. To reach our team by phone, use the button below — or tap *Chat with agent* to connect with us."
        );

        $row = DB::table('whatsapp_ai_settings')->where('id', 1)->first();
        if ($row === null) {
            return;
        }

        $updates = [];
        $msg = is_string($row->db_non_text_inbound_message ?? null) ? $normalize($row->db_non_text_inbound_message) : '';
        if ($msg !== '' && $msg === $oldMessage) {
            $updates['db_non_text_inbound_message'] = null;
        }

        $rawButtons = $row->db_non_text_buttons_json ?? null;
        if ($this->isSupersededNonTextButtonsJson($rawButtons)) {
            $updates['db_non_text_buttons_json'] = null;
        }

        if ($updates !== []) {
            $updates['updated_at'] = now();
            DB::table('whatsapp_ai_settings')->where('id', 1)->update($updates);
        }
    }

    /**
     * @param  mixed  $rawButtons
     */
    private function isSupersededNonTextButtonsJson($rawButtons): bool
    {
        if ($rawButtons === null || $rawButtons === '') {
            return false;
        }
        $decoded = is_string($rawButtons) ? json_decode($rawButtons, true) : $rawButtons;
        if (! is_array($decoded) || $decoded === []) {
            return false;
        }

        $qr = null;
        $phone = null;
        foreach ($decoded as $b) {
            if (! is_array($b)) {
                continue;
            }
            $type = strtoupper((string) ($b['type'] ?? ''));
            if ($type === 'QUICK_REPLY' && $qr === null) {
                $qr = mb_strtolower(trim((string) ($b['text'] ?? '')));
            }
            if ($type === 'PHONE_NUMBER' && $phone === null) {
                $phone = mb_strtolower(trim((string) ($b['text'] ?? '')));
            }
        }

        return $qr === mb_strtolower('Chat with agent')
            && $phone === mb_strtolower('Call support');
    }

    public function down(): void
    {
        // Do not restore outdated copy.
    }
};
