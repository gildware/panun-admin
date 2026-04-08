<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\WhatsAppModule\Services\WhatsAppAiPlaygroundRunner;

class WhatsAppAiPlaygroundCommand extends Command
{
    protected $signature = 'whatsapp:ai-playground
                            {message : Customer message text to simulate}
                            {--phone= : Optional sandbox phone (default AI_TEST_SANDBOX)}';

    protected $description = 'Run one sandbox WhatsApp AI turn (real Gemini + DB; no WhatsApp Cloud send)';

    public function handle(WhatsAppAiPlaygroundRunner $runner): int
    {
        $msg = trim((string) $this->argument('message'));
        $phone = $this->option('phone') ? trim((string) $this->option('phone')) : null;

        $result = $runner->runCustomerText($msg, $phone);

        if (! ($result['ok'] ?? false)) {
            $this->error($result['error'] ?? 'Unknown error');

            return self::FAILURE;
        }

        $this->line('<info>Phone:</info> '.($result['phone'] ?? ''));
        $this->line('<info>Inbound ID:</info> '.($result['inbound_message_id'] ?? ''));
        $this->line('<info>Execution:</info> '.($result['execution_id'] ?? '').' ('.($result['execution_outcome'] ?? '').')');
        $this->newLine();
        $this->line((string) ($result['reply_text'] ?? ''));

        return self::SUCCESS;
    }
}
