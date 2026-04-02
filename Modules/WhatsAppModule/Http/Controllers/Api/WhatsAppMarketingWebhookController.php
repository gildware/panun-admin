<?php

namespace Modules\WhatsAppModule\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingMessage;
use Modules\WhatsAppModule\Jobs\ProcessWhatsAppAiSupportJob;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;
use Modules\WhatsAppModule\Services\WhatsAppGraphInboundHandler;

class WhatsAppMarketingWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppGraphInboundHandler $graphInboundHandler
    ) {}

    public function verify(Request $request): Response
    {
        // Meta sends hub.mode, hub.verify_token, hub.challenge. PHP often exposes dotted keys as hub_mode, hub_verify_token, hub_challenge.
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = trim((string) $request->query('hub_verify_token', $request->query('hub.verify_token', '')));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge', ''));
        $expected = trim((string) config('services.whatsapp_cloud.webhook_verify_token'));

        if ($expected === '') {
            Log::warning('WhatsApp webhook verify failed: WHATSAPP_WEBHOOK_VERIFY_TOKEN is empty in config (.env).');

            return response('Webhook verify token not configured on server', 503);
        }

        if ($mode === 'subscribe' && $token !== '' && hash_equals($expected, $token)) {
            return response((string) $challenge, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Cache-Control' => 'no-store',
            ]);
        }

        Log::warning('WhatsApp webhook verify rejected', [
            'mode' => $mode,
            'token_length' => strlen($token),
            'expected_length' => strlen($expected),
        ]);

        return response('Forbidden', 403);
    }

    public function handle(Request $request): Response
    {
        $secret = (string) config('services.whatsapp_cloud.app_secret');
        if ($secret !== '') {
            $sig = $request->header('X-Hub-Signature-256');
            if (!is_string($sig) || !str_starts_with($sig, 'sha256=')) {
                return response('Invalid signature', 403);
            }
            $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
            if (!hash_equals($expected, $sig)) {
                return response('Invalid signature', 403);
            }
        }

        $payload = $request->json()->all();
        if (!is_array($payload)) {
            return response('OK', 200);
        }

        try {
            $this->processPayload($payload);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp marketing webhook processing error', ['error' => $e->getMessage()]);
        }

        return response('OK', 200);
    }

    private function processPayload(array $payload): void
    {
        $entries = $payload['entry'] ?? [];
        if (!is_array($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            if (!is_array($changes)) {
                continue;
            }
            foreach ($changes as $change) {
                $value = $change['value'] ?? null;
                if (!is_array($value)) {
                    continue;
                }

                $statuses = $value['statuses'] ?? [];
                if (is_array($statuses)) {
                    foreach ($statuses as $st) {
                        $this->applyStatus($st);
                    }
                }

                $messages = $value['messages'] ?? [];
                if (is_array($messages)) {
                    foreach ($messages as $msg) {
                        $this->applyInbound($msg);
                        $this->persistConversationInboundAndQueueAi($msg);
                    }
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $st
     */
    private function applyStatus(array $st): void
    {
        $waId = $st['id'] ?? null;
        if (!is_string($waId) || $waId === '') {
            return;
        }

        $status = strtolower((string) ($st['status'] ?? ''));
        $ts = isset($st['timestamp']) ? (int) $st['timestamp'] : null;
        $at = $ts ? \Carbon\Carbon::createFromTimestampUTC($ts) : now();

        $row = WhatsAppMarketingMessage::query()->where('wa_message_id', $waId)->first();
        if (!$row) {
            return;
        }

        if ($row->status === WhatsAppMarketingMessage::STATUS_REPLIED) {
            return;
        }

        $errors = $st['errors'] ?? [];
        $failureText = '';
        if (is_array($errors) && $errors !== []) {
            $failureText = json_encode($errors, JSON_UNESCAPED_UNICODE) ?: 'error';
        }

        if ($status === 'failed') {
            $row->update([
                'status' => WhatsAppMarketingMessage::STATUS_FAILED,
                'failure_reason' => mb_substr($failureText !== '' ? $failureText : 'delivery_failed', 0, 6000),
            ]);

            return;
        }

        if ($status === 'sent') {
            $row->update([
                'status' => WhatsAppMarketingMessage::STATUS_SENT,
                'sent_at' => $row->sent_at ?? $at,
            ]);

            return;
        }

        if ($status === 'delivered') {
            $row->update([
                'status' => WhatsAppMarketingMessage::STATUS_DELIVERED,
                'delivered_at' => $at,
            ]);

            return;
        }

        if ($status === 'read') {
            $row->update([
                'status' => WhatsAppMarketingMessage::STATUS_READ,
                'read_at' => $at,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $msg
     */
    private function applyInbound(array $msg): void
    {
        $from = $msg['from'] ?? null;
        if (!is_string($from) || $from === '') {
            return;
        }

        $cloud = app(WhatsAppCloudService::class);
        $normalized = $cloud->normalizeRecipientPhone($from);
        if ($normalized === null) {
            return;
        }

        $latest = WhatsAppMarketingMessage::query()
            ->where('phone_e164', $normalized)
            ->whereIn('status', [
                WhatsAppMarketingMessage::STATUS_SENT,
                WhatsAppMarketingMessage::STATUS_DELIVERED,
                WhatsAppMarketingMessage::STATUS_READ,
            ])
            ->where('created_at', '>=', now()->subDays(30))
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->first();

        if (!$latest) {
            return;
        }

        $latest->update([
            'status' => WhatsAppMarketingMessage::STATUS_REPLIED,
            'replied_at' => now(),
        ]);
    }

    /**
     * Persist customer messages into the operator chat DB and optionally run the AI support agent.
     *
     * @param  array<string, mixed>  $msg
     */
    private function persistConversationInboundAndQueueAi(array $msg): void
    {
        try {
            $saved = $this->graphInboundHandler->persistInbound($msg);
            if (!$saved) {
                return;
            }

            if (!config('whatsappmodule.ai_support_enabled')) {
                return;
            }

            if ((string) config('services.gemini.api_key') === '') {
                return;
            }

            if (config('whatsappmodule.ai_dispatch_sync', true)) {
                ProcessWhatsAppAiSupportJob::dispatchSync($saved->id);
            } else {
                ProcessWhatsAppAiSupportJob::dispatch($saved->id);
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp conversation inbound failed', ['error' => $e->getMessage()]);
        }
    }
}
