<?php

namespace Modules\WhatsAppModule\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\WhatsAppModule\Jobs\ProcessWhatsAppAiSupportJob;
use Modules\WhatsAppModule\Services\WhatsAppAiRuntimeResolver;
use Modules\WhatsAppModule\Services\WhatsAppMessagePersistenceService;
use Modules\WhatsAppModule\Support\SocialInboxChannel;
use Modules\WhatsAppModule\Support\SocialThreadPhone;

class MetaSocialMessagingWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppMessagePersistenceService $messagePersistence,
        protected WhatsAppAiRuntimeResolver $aiRuntimeResolver,
    ) {}

    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = trim((string) $request->query('hub_verify_token', $request->query('hub.verify_token', '')));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge', ''));
        $expected = trim((string) config('services.meta_social.webhook_verify_token'));

        if ($expected === '' || $mode !== 'subscribe' || !hash_equals($expected, $token)) {
            return response('Forbidden', 403);
        }

        return response((string) $challenge, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function handle(Request $request): Response
    {
        $secret = (string) config('services.meta_social.app_secret');
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
            $object = (string) ($payload['object'] ?? '');
            if ($object === 'page') {
                SocialInboxChannel::using(SocialInboxChannel::FACEBOOK, function () use ($payload) {
                    $this->processMessengerPayload($payload);
                });
            } elseif ($object === 'instagram') {
                SocialInboxChannel::using(SocialInboxChannel::INSTAGRAM, function () use ($payload) {
                    $this->processInstagramPayload($payload);
                });
            }
        } catch (\Throwable $e) {
            Log::warning('Meta social webhook error', ['error' => $e->getMessage()]);
        }

        return response('OK', 200);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function processMessengerPayload(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            foreach ($entry['messaging'] ?? [] as $ev) {
                if (!is_array($ev)) {
                    continue;
                }
                $from = $ev['sender']['id'] ?? null;
                if (!is_string($from) || $from === '') {
                    continue;
                }
                $phone = SocialThreadPhone::forFacebook($from);

                $msg = $ev['message'] ?? null;
                $postback = $ev['postback'] ?? null;

                if (is_array($msg)) {
                    $mid = isset($msg['mid']) && is_string($msg['mid']) ? $msg['mid'] : null;
                    $text = trim((string) ($msg['text'] ?? ''));
                    if ($text !== '') {
                        $this->persistInboundText($phone, $text, $mid);
                    }

                    $atts = $msg['attachments'] ?? null;
                    if (is_array($atts) && $atts !== []) {
                        $i = 0;
                        foreach ($atts as $att) {
                            $i++;
                            if (!is_array($att)) {
                                continue;
                            }
                            $this->persistInboundAttachment($phone, $att, $mid ? ($mid . ':a' . $i) : null);
                        }
                    }
                } elseif (is_array($postback)) {
                    // Common Messenger “button” interactions.
                    $payloadText = trim((string) ($postback['payload'] ?? $postback['title'] ?? ''));
                    if ($payloadText !== '') {
                        $this->persistInboundText($phone, $payloadText, null);
                    }
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function processInstagramPayload(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            foreach ($entry['messaging'] ?? [] as $ev) {
                if (!is_array($ev)) {
                    continue;
                }
                $from = $ev['sender']['id'] ?? null;
                if (!is_string($from) || $from === '') {
                    continue;
                }
                $phone = SocialThreadPhone::forInstagram($from);

                $msg = $ev['message'] ?? null;
                if (!is_array($msg)) {
                    continue;
                }
                $mid = isset($msg['mid']) && is_string($msg['mid']) ? $msg['mid'] : null;
                $text = trim((string) ($msg['text'] ?? ''));
                if ($text !== '') {
                    $this->persistInboundText($phone, $text, $mid);
                }
                $atts = $msg['attachments'] ?? null;
                if (is_array($atts) && $atts !== []) {
                    $i = 0;
                    foreach ($atts as $att) {
                        $i++;
                        if (!is_array($att)) {
                            continue;
                        }
                        $this->persistInboundAttachment($phone, $att, $mid ? ($mid . ':a' . $i) : null);
                    }
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $att
     */
    private function persistInboundAttachment(string $phone, array $att, ?string $dedupeId): void
    {
        if ($dedupeId) {
            $dup = \Modules\WhatsAppModule\Entities\WhatsAppMessage::withoutGlobalScopes()
                ->where('wa_message_id', $dedupeId)
                ->where('channel', SocialInboxChannel::current())
                ->exists();
            if ($dup) {
                return;
            }
        }

        $type = strtolower(trim((string) ($att['type'] ?? '')));
        $payload = $att['payload'] ?? null;
        $url = is_array($payload) ? trim((string) ($payload['url'] ?? '')) : '';

        $messageType = match ($type) {
            'image' => 'IMAGE',
            'video' => 'VIDEO',
            'audio' => 'AUDIO',
            'file' => 'DOCUMENT',
            default => 'DOCUMENT',
        };

        $extHint = '';
        if ($url !== '') {
            $path = parse_url($url, PHP_URL_PATH);
            if (is_string($path) && str_contains($path, '.')) {
                $extHint = strtolower(trim((string) pathinfo($path, PATHINFO_EXTENSION)));
            }
        }

        $label = trim((string) ($att['title'] ?? ''));
        if ($label === '') {
            $label = $messageType;
        }

        $this->messagePersistence->persist([
            'phone' => $phone,
            'message_text' => $label,
            'direction' => 'IN',
            'message_type' => $messageType,
            'wa_message_id' => $dedupeId,
            'media_url' => $url !== '' ? $url : null,
            'media_ext_hint' => $extHint !== '' ? $extHint : null,
        ]);
    }

    private function persistInboundText(string $phone, string $text, ?string $mid): void
    {
        if ($mid) {
            $dup = \Modules\WhatsAppModule\Entities\WhatsAppMessage::withoutGlobalScopes()
                ->where('wa_message_id', $mid)
                ->where('channel', SocialInboxChannel::current())
                ->exists();
            if ($dup) {
                return;
            }
        }

        $saved = $this->messagePersistence->persist([
            'phone' => $phone,
            'message_text' => $text,
            'direction' => 'IN',
            'message_type' => 'TEXT',
            'wa_message_id' => $mid,
        ]);

        if (!$this->aiRuntimeResolver->aiSupportEnabled()) {
            return;
        }
        if ((string) config('services.gemini.api_key') === '') {
            return;
        }
        if ($this->aiRuntimeResolver->aiDispatchUsesSync()) {
            ProcessWhatsAppAiSupportJob::dispatchSync($saved->id);
        } else {
            ProcessWhatsAppAiSupportJob::dispatch($saved->id);
        }
    }
}
