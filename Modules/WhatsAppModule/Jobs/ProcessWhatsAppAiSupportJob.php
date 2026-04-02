<?php

namespace Modules\WhatsAppModule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\WhatsAppModule\Services\WhatsAppAiExecutionRecorder;
use Modules\WhatsAppModule\Services\WhatsAppAiSupportOrchestrator;

class ProcessWhatsAppAiSupportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $whatsappMessageId
    ) {}

    public function handle(WhatsAppAiSupportOrchestrator $orchestrator): void
    {
        $recorder = WhatsAppAiExecutionRecorder::begin($this->whatsappMessageId);
        try {
            $orchestrator->handleInboundMessageId($this->whatsappMessageId, $recorder);
        } catch (\Throwable $e) {
            $recorder->fail($e);
            throw $e;
        }
    }
}
