<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Cache;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;

/**
 * Maps Meta Cloud API inbound message payloads into persisted rows (shared webhook path).
 */
class WhatsAppGraphInboundHandler
{
    public function __construct(
        protected WhatsAppCloudService $whatsAppCloud,
        protected WhatsAppMessagePersistenceService $messagePersistence
    ) {}

    /**
     * @param  array<string, mixed>  $msg
     */
    public function persistInbound(array $msg): ?WhatsAppMessage
    {
        $from = $msg['from'] ?? null;
        if (!is_string($from) || $from === '') {
            return null;
        }

        $phone = $this->whatsAppCloud->normalizeRecipientPhone($from);
        if ($phone === null) {
            return null;
        }

        $type = (string) ($msg['type'] ?? 'text');
        if ($type === 'reaction') {
            $this->applyInboundReaction($msg);

            return null;
        }

        $waId = isset($msg['id']) && is_string($msg['id']) ? $msg['id'] : null;
        if ($waId && WhatsAppMessage::where('wa_message_id', $waId)->exists()) {
            return WhatsAppMessage::where('wa_message_id', $waId)->first();
        }

        $text = '';
        $mediaId = null;
        $mime = null;

        if ($type === 'text') {
            $text = (string) ($msg['text']['body'] ?? '');
        } elseif ($type === 'interactive') {
            $interactive = $msg['interactive'] ?? [];
            $iType = $interactive['type'] ?? '';
            if ($iType === 'button_reply') {
                $br = $interactive['button_reply'] ?? [];
                $text = trim((string) ($br['title'] ?? '') . ' [' . (string) ($br['id'] ?? '') . ']');
            } elseif ($iType === 'list_reply') {
                $lr = $interactive['list_reply'] ?? [];
                $text = trim((string) ($lr['title'] ?? '') . ' [' . (string) ($lr['id'] ?? '') . ']');
            } else {
                $text = '[Interactive]';
            }
        } elseif ($type === 'image') {
            $text = trim((string) ($msg['image']['caption'] ?? ''));
            if ($text === '') {
                $text = '[Image received]';
            }
            $mediaId = $msg['image']['id'] ?? null;
            $mime = $msg['image']['mime_type'] ?? null;
        } elseif ($type === 'document') {
            $text = trim((string) ($msg['document']['caption'] ?? '') ?: (string) ($msg['document']['filename'] ?? '[Document]'));
            $mediaId = $msg['document']['id'] ?? null;
            $mime = $msg['document']['mime_type'] ?? null;
        } elseif ($type === 'video') {
            $text = trim((string) ($msg['video']['caption'] ?? '') ?: '[Video received]');
            $mediaId = $msg['video']['id'] ?? null;
            $mime = $msg['video']['mime_type'] ?? null;
        } elseif ($type === 'audio') {
            $text = '[Voice message]';
            $mediaId = $msg['audio']['id'] ?? null;
            $mime = $msg['audio']['mime_type'] ?? null;
        } else {
            $text = '[' . strtoupper($type) . ']';
        }

        // Meta sends Unix seconds in UTC. Store the same local wall-clock as server-created OUT rows (app.timezone).
        $ts = isset($msg['timestamp']) ? (int) $msg['timestamp'] : null;
        $createdAt = null;
        if ($ts > 0) {
            $createdAt = \Carbon\Carbon::createFromTimestamp($ts, 'UTC')
                ->timezone(config('whatsappmodule.message_timezone', config('app.timezone')));
        }

        $replyToWa = null;
        $ctx = $msg['context'] ?? null;
        if (is_array($ctx) && !empty($ctx['id']) && is_string($ctx['id'])) {
            $replyToWa = $ctx['id'];
        }

        return $this->messagePersistence->persist([
            'phone' => $phone,
            'message_text' => $text,
            'direction' => 'IN',
            'message_type' => strtoupper($type) === 'TEXT' ? 'TEXT' : strtoupper($type),
            'wa_message_id' => $waId,
            'reply_to_wa_message_id' => $replyToWa,
            'created_at' => $createdAt,
            'media_id' => is_string($mediaId) ? $mediaId : null,
            'media_mime_type' => is_string($mime) ? $mime : null,
        ]);
    }

    /**
     * Customer reaction to a message (no new chat row; updates `reactions` on the target).
     *
     * @param  array<string, mixed>  $msg
     */
    private function applyInboundReaction(array $msg): void
    {
        $reaction = $msg['reaction'] ?? null;
        if (!is_array($reaction)) {
            return;
        }
        $targetId = $reaction['message_id'] ?? null;
        if (!is_string($targetId) || $targetId === '') {
            return;
        }
        $emoji = trim((string) ($reaction['emoji'] ?? ''));

        $target = WhatsAppMessage::query()->where('wa_message_id', $targetId)->first();
        if (!$target) {
            return;
        }

        $reactions = is_array($target->reactions) ? $target->reactions : [];
        if ($emoji === '') {
            unset($reactions['customer']);
        } else {
            $reactions['customer'] = $emoji;
        }
        if ($reactions === []) {
            $target->reactions = null;
        } else {
            $target->reactions = $reactions;
        }
        $target->save();

        Cache::forget('whatsapp_active_chats_list');
        Cache::forget('whatsapp_chat_full_v2_' . md5((string) $target->phone));
    }
}
