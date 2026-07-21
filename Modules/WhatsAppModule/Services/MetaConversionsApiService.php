<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\WhatsAppModule\Entities\WhatsAppMetaCapiEvent;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\WhatsAppModule\Jobs\SendMetaCtwaConversionJob;
use Modules\WhatsAppModule\Support\SocialInboxChannel;

/**
 * Meta Conversions API for Business Messaging (CTWA).
 *
 * @see https://developers.facebook.com/docs/marketing-api/conversions-api/business-messaging/
 */
class MetaConversionsApiService
{
    public const EVENT_LEAD_SUBMITTED = 'LeadSubmitted';

    public const EVENT_SCHEDULE = 'Schedule';

    public const EVENT_PURCHASE = 'Purchase';

    public function __construct(
        protected WhatsAppCtwaAttributionService $attribution
    ) {}

    public function isConfigured(): bool
    {
        if (!filter_var(config('services.meta_conversions.enabled', false), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('whatsapp_meta_capi_events')) {
            return false;
        }

        $token = trim((string) config('services.meta_conversions.access_token', ''));
        $datasetId = trim((string) config('services.meta_conversions.dataset_id', ''));
        $wabaId = trim((string) config('services.meta_conversions.waba_id', ''));

        return $token !== '' && $datasetId !== '' && $wabaId !== '';
    }

    /**
     * Queue (or sync-dispatch) a CTWA conversion when the thread has a ctwa_clid.
     *
     * @param  array{currency?: string, value?: float|int|string}  $customData
     */
    public function reportForPhone(
        string $phone,
        string $eventName,
        ?int $leadId = null,
        ?string $bookingId = null,
        array $customData = [],
        ?string $eventIdSuffix = null
    ): void {
        if (!$this->isConfigured()) {
            return;
        }

        $waUser = WhatsAppUser::query()
            ->where('phone', $phone)
            ->where('channel', SocialInboxChannel::current())
            ->first();

        $ctwaClid = trim((string) ($waUser?->ctwa_clid ?? ''));
        if ($ctwaClid === '') {
            return;
        }

        $suffix = $eventIdSuffix !== null && trim($eventIdSuffix) !== ''
            ? trim($eventIdSuffix)
            : implode('|', array_filter([
                $eventName,
                $leadId !== null ? 'l'.$leadId : null,
                $bookingId !== null && $bookingId !== '' ? 'b'.$bookingId : null,
            ]));
        $eventId = substr(hash('sha256', $phone.'|'.$suffix), 0, 64);

        if (WhatsAppMetaCapiEvent::query()->where('event_id', $eventId)->where('status', WhatsAppMetaCapiEvent::STATUS_SENT)->exists()) {
            return;
        }

        $sync = filter_var(config('services.meta_conversions.dispatch_sync', true), FILTER_VALIDATE_BOOL);
        if ($sync) {
            $this->sendEvent($phone, $eventName, $ctwaClid, $eventId, $leadId, $bookingId, $customData);

            return;
        }

        SendMetaCtwaConversionJob::dispatch($phone, $eventName, $ctwaClid, $eventId, $leadId, $bookingId, $customData);
    }

    /**
     * @param  array{currency?: string, value?: float|int|string}  $customData
     */
    public function sendEvent(
        string $phone,
        string $eventName,
        string $ctwaClid,
        string $eventId,
        ?int $leadId = null,
        ?string $bookingId = null,
        array $customData = []
    ): bool {
        if (!$this->isConfigured()) {
            return false;
        }

        $existing = WhatsAppMetaCapiEvent::query()->where('event_id', $eventId)->first();
        if ($existing && $existing->status === WhatsAppMetaCapiEvent::STATUS_SENT) {
            return true;
        }

        $wabaId = trim((string) config('services.meta_conversions.waba_id'));
        $datasetId = trim((string) config('services.meta_conversions.dataset_id'));
        $token = trim((string) config('services.meta_conversions.access_token'));
        $version = trim((string) config('services.meta_conversions.graph_version', 'v19.0'));
        $partnerAgent = trim((string) config('services.meta_conversions.partner_agent', 'panun_kaergar'));

        $event = [
            'event_name' => $eventName,
            'event_time' => time(),
            'event_id' => $eventId,
            'action_source' => 'business_messaging',
            'messaging_channel' => 'whatsapp',
            'user_data' => [
                'whatsapp_business_account_id' => $wabaId,
                'ctwa_clid' => $ctwaClid,
            ],
        ];

        if ($customData !== []) {
            $event['custom_data'] = $customData;
        }

        $payload = [
            'data' => [$event],
            'partner_agent' => $partnerAgent !== '' ? $partnerAgent : 'panun_kaergar',
        ];

        $row = $existing ?: new WhatsAppMetaCapiEvent();
        $row->fill([
            'channel' => SocialInboxChannel::current(),
            'phone' => $phone,
            'event_name' => $eventName,
            'event_id' => $eventId,
            'ctwa_clid' => $ctwaClid,
            'lead_id' => $leadId,
            'booking_id' => $bookingId,
            'status' => WhatsAppMetaCapiEvent::STATUS_PENDING,
            'request_payload' => $payload,
            'error_message' => null,
        ]);
        $row->save();

        $url = sprintf('https://graph.facebook.com/%s/%s/events', $version, $datasetId);

        try {
            $response = Http::timeout(20)
                ->withToken($token)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            $body = $response->json();
            $row->response_json = is_array($body) ? $body : ['raw' => $response->body()];

            if ($response->successful()) {
                $row->status = WhatsAppMetaCapiEvent::STATUS_SENT;
                $row->sent_at = now();
                $row->save();

                return true;
            }

            $row->status = WhatsAppMetaCapiEvent::STATUS_FAILED;
            $row->error_message = mb_substr((string) ($response->body() ?: 'HTTP '.$response->status()), 0, 2000);
            $row->save();

            Log::warning('Meta CAPI CTWA event failed', [
                'event_name' => $eventName,
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            $row->status = WhatsAppMetaCapiEvent::STATUS_FAILED;
            $row->error_message = mb_substr($e->getMessage(), 0, 2000);
            $row->save();

            Log::warning('Meta CAPI CTWA event exception', [
                'event_name' => $eventName,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }
}
