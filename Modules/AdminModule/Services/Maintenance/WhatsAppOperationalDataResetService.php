<?php

namespace Modules\AdminModule\Services\Maintenance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WhatsAppOperationalDataResetService
{
    /**
     * @param  array{all?: bool, messages?: bool, human_support?: bool, provider_leads?: bool, bookings?: bool, users?: bool}  $options
     */
    public function reset(array $options): void
    {
        $all = (bool) ($options['all'] ?? false);
        $messages = $all || (bool) ($options['messages'] ?? false);
        $humanSupport = $all || (bool) ($options['human_support'] ?? false);
        $providerLeads = $all || (bool) ($options['provider_leads'] ?? false);
        $bookings = $all || (bool) ($options['bookings'] ?? false);
        $users = $all || (bool) ($options['users'] ?? false);

        if ($users) {
            DB::transaction(function () {
                $this->deleteWhatsAppChatStack();
                $this->deleteTableIfExists('whatsapp_provider_leads');
                $this->deleteTableIfExists('whatsapp_bookings');
                $this->deleteTableIfExists('whatsapp_users');
            });

            return;
        }

        DB::transaction(function () use ($messages, $humanSupport, $providerLeads, $bookings) {
            if ($messages) {
                $this->deleteTableIfExists('whatsapp_ai_executions');
                $this->deleteTableIfExists('whatsapp_messages');
            }
            if ($humanSupport && Schema::hasTable('whatsapp_users')) {
                DB::table('whatsapp_users')->update(['human_support_requested_at' => null]);
            }
            if ($providerLeads) {
                $this->deleteTableIfExists('whatsapp_provider_leads');
            }
            if ($bookings) {
                $this->deleteTableIfExists('whatsapp_bookings');
            }
        });
    }

    /**
     * Messages, thread UI meta, conversation state, then users (leads/bookings handled by caller when wiping users).
     */
    private function deleteWhatsAppChatStack(): void
    {
        $this->deleteTableIfExists('whatsapp_ai_executions');
        $this->deleteTableIfExists('whatsapp_messages');
        $this->deleteTableIfExists('whatsapp_chat_thread_tags');
        $this->deleteTableIfExists('whatsapp_chat_thread_meta');
        $this->deleteTableIfExists('whatsapp_conversations');
    }

    private function deleteTableIfExists(string $table): void
    {
        if (Schema::hasTable($table)) {
            DB::table($table)->delete();
        }
    }
}
