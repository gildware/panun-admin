<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stored db_greeting_message overrides the code default. Clear known superseded templates
 * so Message Configuration and outbound greetings use {@see \Modules\WhatsAppModule\Services\WhatsAppAiSettingsService::defaultGreetingMessageTemplate()}.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_ai_settings') || ! Schema::hasColumn('whatsapp_ai_settings', 'db_greeting_message')) {
            return;
        }

        $normalize = static function (?string $s): string {
            $s = (string) $s;
            $s = str_replace(["\r\n", "\r"], "\n", $s);

            return trim($s);
        };

        $superseded = array_map($normalize, [
            "Hello{customer_name_lead_in}! My Name is Kaera .I'm your AI support assistant for {brand}.\n\nHow can I help you today?",
            "Hello{customer_name_lead_in}! My Name is Kaera. I'm your AI support assistant for {brand}.\n\nHow can I help you today?",
            "Hello{customer_name_lead_in}! I'm {brand}'s AI assistant — I can help you book a service, answer questions, or connect you with our team.\n\nWhat would you like to do today?",
        ]);

        $raw = DB::table('whatsapp_ai_settings')->where('id', 1)->value('db_greeting_message');
        if (! is_string($raw) || trim($raw) === '') {
            return;
        }

        $cur = $normalize($raw);
        $isKaeraBrandTemplate = str_contains($cur, 'My Name is Kaera')
            && str_contains($cur, 'AI support assistant for {brand}')
            && str_contains($cur, 'How can I help you today');

        if (in_array($cur, $superseded, true) || $isKaeraBrandTemplate) {
            DB::table('whatsapp_ai_settings')->where('id', 1)->update([
                'db_greeting_message' => null,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Do not restore outdated greeting copy.
    }
};
