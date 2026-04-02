<?php

namespace Modules\WhatsAppModule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingCampaign;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingMessage;

class ProcessWhatsAppMarketingCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $campaignId) {}

    public function handle(): void
    {
        $campaign = WhatsAppMarketingCampaign::query()->find($this->campaignId);
        if (!$campaign) {
            return;
        }

        if (!in_array($campaign->status, [
            WhatsAppMarketingCampaign::STATUS_QUEUED,
            WhatsAppMarketingCampaign::STATUS_SCHEDULED,
        ], true)) {
            return;
        }

        $campaign->update([
            'status' => WhatsAppMarketingCampaign::STATUS_PROCESSING,
            'started_at' => $campaign->started_at ?? now(),
        ]);

        $messages = WhatsAppMarketingMessage::query()
            ->where('whatsapp_marketing_campaign_id', $campaign->id)
            ->where('status', WhatsAppMarketingMessage::STATUS_PENDING)
            ->orderBy('id')
            ->pluck('id');

        if ($messages->isEmpty()) {
            $campaign->update([
                'status' => WhatsAppMarketingCampaign::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            return;
        }

        $delay = 0;
        foreach ($messages as $messageId) {
            SendWhatsAppMarketingMessageJob::dispatch($messageId)->delay(now()->addSeconds($delay));
            $delay += 1;
        }
    }
}
