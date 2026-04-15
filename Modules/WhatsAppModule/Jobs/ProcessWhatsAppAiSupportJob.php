<?php

namespace Modules\WhatsAppModule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\WhatsAppModule\Services\WhatsAppAiExecutionRecorder;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Services\WhatsAppAiSupportOrchestrator;
use Modules\WhatsAppModule\Support\SocialInboxChannel;

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
        $trigger = WhatsAppMessage::withoutGlobalScopes()->find($this->whatsappMessageId);
        $ch = $trigger && is_string($trigger->channel ?? null) && SocialInboxChannel::isValid((string) $trigger->channel)
            ? (string) $trigger->channel
            : SocialInboxChannel::WHATSAPP;

        $recorder = WhatsAppAiExecutionRecorder::begin($this->whatsappMessageId);
        try {
            SocialInboxChannel::using($ch, function () use ($orchestrator, $recorder) {
                $orchestrator->handleInboundMessageId($this->whatsappMessageId, $recorder);
            });
        } catch (\Throwable $e) {
            $recorder->fail($e);
            throw $e;
        }
    }
}
