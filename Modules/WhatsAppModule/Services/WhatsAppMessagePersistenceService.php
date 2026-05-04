<?php

namespace Modules\WhatsAppModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\WhatsAppModule\Support\SocialInboxChannel;
use Modules\WhatsAppModule\Support\WhatsAppActiveChatsListCache;

/**
 * Persists WhatsApp message rows. Inbound (direction IN) runs CRM bootstrap (WhatsApp user + open unknown lead).
 * Outbound automation persists messages only and does not create leads.
 */
class WhatsAppMessagePersistenceService
{
    public function __construct(
        protected WhatsAppCloudService $whatsAppCloud,
        protected WhatsAppCrmBootstrapService $crmBootstrap
    ) {}

    private function downloadInboundSocialAttachmentToPublicDisk(?string $url, ?string $hintExt = null): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        // Only allow http(s) URLs.
        if (!str_starts_with($url, 'https://') && !str_starts_with($url, 'http://')) {
            return null;
        }

        try {
            $resp = Http::timeout(18)->accept('*/*')->get($url);
        } catch (\Throwable) {
            return null;
        }
        if (!$resp->successful()) {
            return null;
        }
        $body = $resp->body();
        if (!is_string($body) || $body === '') {
            return null;
        }
        // Hard cap to avoid storing huge files unexpectedly (20MB).
        if (strlen($body) > 20 * 1024 * 1024) {
            return null;
        }

        $contentType = strtolower(trim((string) $resp->header('Content-Type', '')));
        $ext = $hintExt ? strtolower(preg_replace('/[^a-z0-9]+/i', '', $hintExt)) : '';
        if ($ext === '' || strlen($ext) > 10) {
            $ext = '';
        }
        if ($ext === '' && str_contains($contentType, 'image/')) {
            $ext = explode('/', $contentType, 2)[1] ?? '';
        } elseif ($ext === '' && str_contains($contentType, 'video/')) {
            $ext = explode('/', $contentType, 2)[1] ?? '';
        } elseif ($ext === '' && str_contains($contentType, 'audio/')) {
            $ext = explode('/', $contentType, 2)[1] ?? '';
        } elseif ($ext === '' && (str_contains($contentType, 'application/pdf') || str_contains($contentType, 'application/'))) {
            $ext = 'bin';
        }
        $ext = strtolower((string) $ext);
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if ($ext === '' || strlen($ext) > 10) {
            $ext = 'bin';
        }

        $ch = SocialInboxChannel::current();
        $dir = 'social_inbox_attachments/' . $ch;
        $name = Str::uuid()->toString() . '.' . $ext;
        $path = $dir . '/' . $name;
        try {
            Storage::disk('public')->put($path, $body);
        } catch (\Throwable) {
            return null;
        }

        return $path;
    }

    /**
     * Same `phone` key as {@see \Modules\WhatsAppModule\Http\Controllers\Web\Admin\WhatsAppController::chat} expects (matches existing thread rows when present).
     */
    public function resolveAdminChatPhoneKey(?string $rawPhone): ?string
    {
        if ($rawPhone === null || trim($rawPhone) === '') {
            return null;
        }
        $raw = trim($rawPhone);
        $normalized = $this->whatsAppCloud->normalizeRecipientPhone($raw);
        if ($normalized === null) {
            return null;
        }
        $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        $ch = SocialInboxChannel::current();
        foreach (array_unique(array_filter([$raw, $normalized])) as $candidate) {
            if (DB::table($table)->where('phone', $candidate)->where('channel', $ch)->exists()) {
                return $candidate;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $data
     *                         phone, direction, message_text?, message_type?, wa_message_id?,
     *                         reply_to_wa_message_id?, sent_by_id?, sent_by?, created_at?,
     *                         media_id?, media_url?, media_mime_type?
     */
    public function persist(array $data): WhatsAppMessage
    {
        $messageType = $data['message_type'] ?? null;
        if (!$messageType && !empty($data['media_mime_type'])) {
            $mime = strtolower((string) $data['media_mime_type']);
            if (str_starts_with($mime, 'image/')) {
                $messageType = 'IMAGE';
            } elseif ($mime === 'application/pdf' || str_starts_with($mime, 'application/')) {
                $messageType = 'DOCUMENT';
            } elseif (str_starts_with($mime, 'video/')) {
                $messageType = 'VIDEO';
            } elseif (str_starts_with($mime, 'audio/')) {
                $messageType = 'AUDIO';
            }
        }

        $mediaPath = null;
        if (!empty($data['media_id']) || !empty($data['media_url'])) {
            if (($data['channel'] ?? SocialInboxChannel::current()) === SocialInboxChannel::WHATSAPP) {
                $mediaPath = $this->whatsAppCloud->downloadInboundMediaToPublicDisk(
                    $data['media_id'] ?? null,
                    $data['media_url'] ?? null,
                    $data['media_mime_type'] ?? null
                );
            } else {
                $mediaPath = $this->downloadInboundSocialAttachmentToPublicDisk(
                    $data['media_url'] ?? null,
                    $data['media_ext_hint'] ?? null
                );
            }
        }

        $msg = new WhatsAppMessage();
        $msg->fill([
            'channel' => $data['channel'] ?? SocialInboxChannel::current(),
            'phone' => $data['phone'],
            'message_text' => $data['message_text'] ?? '',
            'direction' => $data['direction'],
            'message_type' => $messageType ?? ($mediaPath ? 'IMAGE' : 'TEXT'),
            'wa_message_id' => $data['wa_message_id'] ?? null,
            'reply_to_wa_message_id' => $data['reply_to_wa_message_id'] ?? null,
            'sent_by_id' => $data['sent_by_id'] ?? null,
            'sent_by' => $data['sent_by'] ?? (($data['direction'] ?? '') === 'OUT' ? 'AI' : 'Customer'),
        ]);
        if ($mediaPath) {
            $msg->media_path = $mediaPath;
        }
        if (!empty($data['created_at'])) {
            $msg->created_at = $data['created_at'];
        } elseif (($data['direction'] ?? '') === 'OUT') {
            $msg->created_at = Carbon::now(
                (string) config('whatsappmodule.message_timezone', config('app.timezone'))
            );
        }
        $msg->save();

        if (($data['direction'] ?? null) === 'IN') {
            $this->crmBootstrap->bootstrapInboundThread((string) $data['phone']);
        }

        WhatsAppActiveChatsListCache::forgetAll();
        WhatsAppActiveChatsListCache::forgetChatFull((string) $data['phone']);

        return $msg;
    }

    /**
     * Persist an outbound row (e.g. AI reply) and optionally attach Graph message id after send.
     */
    public function persistOutboundPlaceholder(string $phone, string $body, string $sentBy = 'AI'): WhatsAppMessage
    {
        return $this->persist([
            'phone' => $phone,
            'message_text' => $body,
            'direction' => 'OUT',
            'message_type' => 'TEXT',
            'sent_by' => $sentBy,
        ]);
    }

    public function attachWaMessageId(WhatsAppMessage $message, string $waMessageId): void
    {
        $message->wa_message_id = $waMessageId;
        $message->save();
    }

    /**
     * Store outbound rows for booking automation, marketing, etc. so admin conversations show the same thread.
     * When $actingAdminUserId is set (e.g. logged-in admin triggered the booking action), assigns the thread to that admin.
     *
     * Does not run CRM lead bootstrap: outbound sends must not create {@see \Modules\LeadManagement\Entities\Lead} rows.
     * Leads are created only for inbound traffic via {@see self::persist()} (direction IN).
     */
    public function persistOutboundAutomation(
        string $normalizedPhone,
        string $body,
        ?string $waMessageId,
        string $sentByLabel,
        ?int $actingAdminUserId = null,
        string $messageType = 'TEXT',
        ?string $mediaPath = null
    ): WhatsAppMessage {
        $msg = new WhatsAppMessage();
        $msg->fill([
            'phone' => $normalizedPhone,
            'message_text' => $body,
            'direction' => 'OUT',
            'message_type' => $messageType,
            'wa_message_id' => $waMessageId,
            'sent_by_id' => $actingAdminUserId,
            'sent_by' => $actingAdminUserId === null ? $sentByLabel : null,
        ]);
        if ($mediaPath !== null && $mediaPath !== '') {
            $msg->media_path = $mediaPath;
        }
        $msg->created_at = Carbon::now(
            (string) config('whatsappmodule.message_timezone', config('app.timezone'))
        );
        $msg->save();

        if ($actingAdminUserId !== null) {
            $waUser = WhatsAppUser::firstOrNew(['phone' => $normalizedPhone]);
            $waUser->handled_by = (string) $actingAdminUserId;
            $waUser->save();
        }

        WhatsAppActiveChatsListCache::forgetAll();
        WhatsAppActiveChatsListCache::forgetChatFull($normalizedPhone);

        return $msg;
    }
}
