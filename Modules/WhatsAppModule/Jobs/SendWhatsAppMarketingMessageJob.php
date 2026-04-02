<?php

namespace Modules\WhatsAppModule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingCampaign;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingMessage;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;

class SendWhatsAppMarketingMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $messageId) {}

    public function handle(WhatsAppCloudService $cloud): void
    {
        $message = null;

        DB::transaction(function () use (&$message) {
            $m = WhatsAppMarketingMessage::query()->lockForUpdate()->find($this->messageId);
            if (!$m || $m->status !== WhatsAppMarketingMessage::STATUS_PENDING) {
                return;
            }
            $m->update(['status' => WhatsAppMarketingMessage::STATUS_SENDING]);
            $message = $m;
        });

        if (!$message) {
            return;
        }

        $message->load(['campaign.template']);
        $campaign = $message->campaign;
        $template = $campaign?->template;
        if (!$campaign || !$template) {
            $this->markFailed($message, 'missing_campaign_or_template');

            return;
        }

        $bodyParams = is_array($message->body_parameters) ? $message->body_parameters : [];
        $bodyStrings = array_map(static fn ($v) => (string) $v, $bodyParams);

        $error = null;
        $graphContext = null;
        $waId = $cloud->sendTemplateMessage(
            $message->phone_e164,
            $template->name,
            $template->language,
            $bodyStrings,
            $error,
            $graphContext
        );

        if ($waId) {
            DB::transaction(function () use ($message, $waId) {
                $message->refresh();
                $message->update([
                    'status' => WhatsAppMarketingMessage::STATUS_SENT,
                    'wa_message_id' => $waId,
                    'sent_at' => now(),
                    'failure_reason' => null,
                ]);
            });
        } else {
            $detail = $error ?? 'send_failed';
            Log::warning('WhatsApp marketing send failed', [
                'message_id' => $message->id,
                'detail' => $detail,
                'graph' => $graphContext,
            ]);
            $this->markFailed($message, $detail);
        }

        $this->tryFinalizeCampaign($campaign->id);
    }

    private function markFailed(WhatsAppMarketingMessage $message, string $reason): void
    {
        DB::transaction(function () use ($message, $reason) {
            $message->refresh();
            $message->update([
                'status' => WhatsAppMarketingMessage::STATUS_FAILED,
                'failure_reason' => mb_substr($reason, 0, 6000),
            ]);
        });
    }

    private function tryFinalizeCampaign(int $campaignId): void
    {
        DB::transaction(function () use ($campaignId) {
            $campaign = WhatsAppMarketingCampaign::query()->lockForUpdate()->find($campaignId);
            if (!$campaign || $campaign->status !== WhatsAppMarketingCampaign::STATUS_PROCESSING) {
                return;
            }

            $stillOpen = WhatsAppMarketingMessage::query()
                ->where('whatsapp_marketing_campaign_id', $campaignId)
                ->whereIn('status', [
                    WhatsAppMarketingMessage::STATUS_PENDING,
                    WhatsAppMarketingMessage::STATUS_SENDING,
                ])
                ->exists();

            if (!$stillOpen) {
                $campaign->update([
                    'status' => WhatsAppMarketingCampaign::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
            }
        });
    }
}
