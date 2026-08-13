<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Cache;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Support\SocialInboxChannel;
use Modules\WhatsAppModule\Support\WhatsAppActiveChatsListCache;

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
     * @param  array{contacts?: list<array<string, mixed>>, metadata?: array<string, mixed>, field?: ?string}  $webhookContext
     */
    public function persistInbound(array $msg, array $webhookContext = []): ?WhatsAppMessage
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
            $this->applyInboundReaction($msg, $webhookContext);

            return null;
        }

        $waId = isset($msg['id']) && is_string($msg['id']) ? $msg['id'] : null;
        if ($waId) {
            $dup = WhatsAppMessage::withoutGlobalScopes()
                ->where('wa_message_id', $waId)
                ->where('channel', SocialInboxChannel::WHATSAPP)
                ->first();
            if ($dup) {
                return $dup;
            }
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
        } elseif ($type === 'unsupported') {
            $text = $this->unsupportedInboundPlaceholder($msg);
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

        $referral = null;
        if (isset($msg['referral']) && is_array($msg['referral'])) {
            $referral = app(WhatsAppCtwaAttributionService::class)->normalizeReferral($msg['referral']);
        }

        $contact = $this->matchContactForWaId($webhookContext['contacts'] ?? [], $from);
        $profileName = null;
        if (is_array($contact)) {
            $profileName = trim((string) ($contact['profile']['name'] ?? ''));
            if ($profileName === '') {
                $profileName = null;
            }
        }

        $payload = [
            'phone' => $phone,
            'message_text' => $text,
            'direction' => 'IN',
            'message_type' => $this->normalizeInboundMessageType($type),
            'wa_message_id' => $waId,
            'reply_to_wa_message_id' => $replyToWa,
            'created_at' => $createdAt,
            'media_id' => is_string($mediaId) ? $mediaId : null,
            'media_mime_type' => is_string($mime) ? $mime : null,
            'meta_payload' => [
                'message' => $msg,
                'contact' => $contact,
                'contacts' => $webhookContext['contacts'] ?? [],
                'metadata' => $webhookContext['metadata'] ?? [],
                'field' => $webhookContext['field'] ?? null,
            ],
            'profile_name' => $profileName,
        ];

        if ($referral !== null) {
            $payload = array_merge($payload, $referral);
        }

        return $this->messagePersistence->persist($payload);
    }

    /**
     * Meta error 131051 — OTP/verification, polls, GIFs, edited/deleted messages, etc.
     * The Cloud API webhook does not include the original body for these types.
     *
     * @param  array<string, mixed>  $msg
     */
    private function unsupportedInboundPlaceholder(array $msg): string
    {
        $unsupported = is_array($msg['unsupported'] ?? null) ? $msg['unsupported'] : [];
        $subType = strtolower(trim((string) ($unsupported['type'] ?? 'unknown')));
        $errors = is_array($msg['errors'] ?? null) ? $msg['errors'] : [];
        $firstError = is_array($errors[0] ?? null) ? $errors[0] : [];
        $errCode = (int) ($firstError['code'] ?? 0);

        if ($subType === 'edit') {
            return '[Message edited — content not available via WhatsApp API]';
        }

        if ($subType === 'revoke' || $subType === 'delete') {
            return '[Message deleted — content not available via WhatsApp API]';
        }

        if ($errCode === 131051) {
            return '[System message — WhatsApp API did not deliver content (often Facebook/Meta OTP, poll, GIF, sticker, or business-to-business message)]';
        }

        return '[Unsupported message — content not available via WhatsApp API]';
    }

    private function normalizeInboundMessageType(string $type): string
    {
        $normalized = strtoupper(trim($type));

        return $normalized === 'TEXT' ? 'TEXT' : $normalized;
    }

    /**
     * @param  list<array<string, mixed>>|array<int, mixed>  $contacts
     * @return array<string, mixed>|null
     */
    private function matchContactForWaId(array $contacts, string $from): ?array
    {
        foreach ($contacts as $contact) {
            if (!is_array($contact)) {
                continue;
            }
            $waId = (string) ($contact['wa_id'] ?? '');
            if ($waId !== '' && ($waId === $from || str_ends_with($from, $waId) || str_ends_with($waId, $from))) {
                return $contact;
            }
        }

        return is_array($contacts[0] ?? null) ? $contacts[0] : null;
    }

    /**
     * Customer reaction to a message (no new chat row; updates `reactions` on the target).
     *
     * @param  array<string, mixed>  $msg
     * @param  array{contacts?: list<array<string, mixed>>, metadata?: array<string, mixed>, field?: ?string}  $webhookContext
     */
    private function applyInboundReaction(array $msg, array $webhookContext = []): void
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

        $target = WhatsAppMessage::withoutGlobalScopes()
            ->where('wa_message_id', $targetId)
            ->where('channel', SocialInboxChannel::WHATSAPP)
            ->first();
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

        // Append reaction event onto target meta_payload history when column exists.
        if (\Illuminate\Support\Facades\Schema::hasColumn($target->getTable(), 'meta_payload')) {
            $meta = is_array($target->meta_payload) ? $target->meta_payload : [];
            $history = is_array($meta['reaction_events'] ?? null) ? $meta['reaction_events'] : [];
            $history[] = [
                'at' => now()->toIso8601String(),
                'message' => $msg,
                'metadata' => $webhookContext['metadata'] ?? [],
            ];
            // Keep last 20 reaction events to avoid unbounded growth.
            $meta['reaction_events'] = array_slice($history, -20);
            $target->meta_payload = $meta;
        }

        $target->save();

        WhatsAppActiveChatsListCache::forgetAll();
        WhatsAppActiveChatsListCache::forgetChatFull((string) $target->phone);
    }
}
