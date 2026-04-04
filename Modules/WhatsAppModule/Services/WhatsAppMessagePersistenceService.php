<?php

namespace Modules\WhatsAppModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;

/**
 * Persists WhatsApp message rows and runs the same CRM bootstrap as the internal sync API.
 */
class WhatsAppMessagePersistenceService
{
    public function __construct(
        protected WhatsAppCloudService $whatsAppCloud,
        protected WhatsAppCrmBootstrapService $crmBootstrap
    ) {}

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
            $mediaPath = $this->whatsAppCloud->downloadInboundMediaToPublicDisk(
                $data['media_id'] ?? null,
                $data['media_url'] ?? null,
                $data['media_mime_type'] ?? null
            );
        }

        $msg = new WhatsAppMessage();
        $msg->fill([
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

        Cache::forget('whatsapp_active_chats_list');
        Cache::forget('whatsapp_chat_full_v2_' . md5((string) $data['phone']));

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
        $this->crmBootstrap->bootstrapInboundThread($normalizedPhone);

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

        Cache::forget('whatsapp_active_chats_list');
        Cache::forget('whatsapp_chat_full_v2_' . md5($normalizedPhone));

        return $msg;
    }
}
