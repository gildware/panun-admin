<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\WhatsAppModule\Entities\ProviderLead;
use Modules\WhatsAppModule\Entities\WhatsAppBooking;
use Modules\WhatsAppModule\Entities\WhatsAppConversation;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;

class WhatsAppSyncController extends Controller
{
    public function message(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:50',
            // Text body is optional when it's a pure media message
            'message_text' => 'nullable|string',
            'direction' => 'required|string|in:IN,OUT',
            'message_type' => 'nullable|string|max:20',
            'created_at' => 'nullable|date',
            'wa_message_id' => 'nullable|string|max:255',
            'sent_by_id' => 'nullable|string|max:64',
            'sent_by' => 'nullable|string|max:255',
            // Optional media fields for inbound messages from WhatsApp (image/document/video/audio)
            'media_id' => 'nullable|string|max:255',
            'media_url' => 'nullable|string',
            'media_mime_type' => 'nullable|string|max:255',
        ]);

        // If this is a media message and no explicit message_type is provided, infer from mime type.
        $messageType = $data['message_type'] ?? null;
        if (!$messageType && !empty($data['media_mime_type'])) {
            $mime = strtolower($data['media_mime_type']);
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

        // Attempt to download and store media if media id or URL has been provided.
        $mediaPath = null;
        if (!empty($data['media_id']) || !empty($data['media_url'])) {
            $mediaPath = $this->downloadWhatsAppMedia(
                $data['media_id'] ?? null,
                $data['media_url'] ?? null,
                $data['media_mime_type'] ?? null
            );
        }

        $msg = new WhatsAppMessage();
        $msg->fill([
            'phone' => $data['phone'],
            // DB column is non-nullable; store empty string when there is no text (pure media)
            'message_text' => $data['message_text'] ?? '',
            'direction' => $data['direction'],
            'message_type' => $messageType ?? ($mediaPath ? 'IMAGE' : 'TEXT'),
            'wa_message_id' => $data['wa_message_id'] ?? null,
            'sent_by_id' => $data['sent_by_id'] ?? null,
            'sent_by' => $data['sent_by'] ?? ($data['direction'] === 'OUT' ? 'AI' : 'Customer'),
        ]);
        if ($mediaPath) {
            $msg->media_path = $mediaPath;
        }
        if (!empty($data['created_at'])) {
            $msg->created_at = $data['created_at'];
        }
        $msg->save();

        // Ensure inbound chats always have a lead in the main CRM.
        if (($data['direction'] ?? null) === 'IN') {
            $waUser = WhatsAppUser::where('phone', $data['phone'])->first();
            if (!$waUser) {
                $waUser = WhatsAppUser::create([
                    'phone' => $data['phone'],
                    'name' => null,
                    'handled_by' => 'AI',
                ]);
            } elseif (empty($waUser->handled_by)) {
                $waUser->handled_by = 'AI';
                $waUser->save();
            }

            $this->ensureUnknownLeadForPhone(
                $data['phone'],
                $waUser->name ?? null
            );
        }

        // Bust caches so Active Chats list and chat panel pick up new message.
        Cache::forget('whatsapp_active_chats_list');
        Cache::forget('whatsapp_chat_full_' . md5($data['phone']));

        return response()->json(['ok' => true, 'id' => $msg->id]);
    }

    /**
     * Download WhatsApp Cloud media by id or direct URL and store it on the public disk.
     * Returns stored media path relative to the disk (e.g. whatsapp_attachments/xyz.jpg) or null on failure.
     */
    private function downloadWhatsAppMedia(?string $mediaId, ?string $directUrl, ?string $mimeType): ?string
    {
        $token = (string) config('services.whatsapp_cloud.token');
        $phoneId = (string) config('services.whatsapp_cloud.phone_id');
        if (!$token || !$phoneId) {
            \Log::warning('WhatsApp inbound media: missing cloud API config.');
            return null;
        }

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');

        try {
            $url = $directUrl;
            $resolvedMime = $mimeType;

            // If we have a media id, resolve it to a download URL first.
            if ($mediaId && !$url) {
                $metaResp = Http::withToken($token)
                    ->acceptJson()
                    ->get("https://graph.facebook.com/{$version}/{$mediaId}");

                if ($metaResp->failed()) {
                    \Log::warning('WhatsApp inbound media: failed to fetch media metadata', [
                        'media_id' => $mediaId,
                        'status' => $metaResp->status(),
                        'body' => $metaResp->body(),
                    ]);
                    return null;
                }

                $meta = $metaResp->json();
                $url = $meta['url'] ?? null;
                $resolvedMime = $resolvedMime ?: ($meta['mime_type'] ?? null);
            }

            if (!$url) {
                \Log::warning('WhatsApp inbound media: no URL resolved', ['media_id' => $mediaId]);
                return null;
            }

            // Download the actual binary
            $fileResp = Http::withToken($token)->get($url);
            if ($fileResp->failed()) {
                \Log::warning('WhatsApp inbound media: failed to download file', [
                    'media_id' => $mediaId,
                    'status' => $fileResp->status(),
                ]);
                return null;
            }

            $contentType = strtolower($resolvedMime ?: ($fileResp->header('Content-Type') ?? ''));
            $ext = 'bin';
            if (str_starts_with($contentType, 'image/')) {
                $ext = match ($contentType) {
                    'image/jpeg', 'image/jpg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    default => 'jpg',
                };
            } elseif ($contentType === 'application/pdf') {
                $ext = 'pdf';
            } elseif (str_starts_with($contentType, 'video/')) {
                $ext = 'mp4';
            } elseif (str_starts_with($contentType, 'audio/')) {
                $ext = 'mp3';
            }

            $filename = 'in_' . ($mediaId ?: uniqid('', true)) . '.' . $ext;
            $path = 'whatsapp_attachments/' . $filename;
            Storage::disk('public')->put($path, $fileResp->body());

            return $path;
        } catch (\Throwable $e) {
            \Log::warning('WhatsApp inbound media: exception while downloading', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Update message status (sent, delivered, read, failed). Call from n8n when WhatsApp status webhook fires.
     * For failed: pass Meta's errors as JSON string in failure_detail (optional).
     */
    public function messageStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'wa_message_id' => 'required|string|max:255',
            'status' => 'required|string|in:sent,delivered,read,failed',
            'phone' => 'nullable|string|max:50',
            'status_timestamp' => 'nullable|date',
            'failure_detail' => 'nullable|string|max:65000',
        ]);

        $msg = WhatsAppMessage::where('wa_message_id', $data['wa_message_id'])
            ->where('direction', 'OUT')
            ->first();

        if (!$msg) {
            return response()->json(['ok' => false, 'error' => 'Message not found'], 404);
        }

        $msg->status = $data['status'];
        if ($data['status'] === 'failed') {
            $msg->status_detail = $data['failure_detail'] ?? null;
            \Log::warning('WhatsApp message delivery failed (status webhook)', [
                'wa_message_id' => $data['wa_message_id'],
                'phone' => $msg->phone,
                'failure_detail' => $msg->status_detail,
            ]);
        } else {
            $msg->status_detail = null;
        }
        $msg->status_updated_at = !empty($data['status_timestamp'])
            ? $data['status_timestamp']
            : now();
        $msg->save();

        // Clear per-chat and list caches so ticks update.
        Cache::forget('whatsapp_active_chats_list');
        Cache::forget('whatsapp_chat_full_' . md5($msg->phone));

        return response()->json(['ok' => true]);
    }

    /**
     * Check if there are newer inbound messages after a given local message id.
     * Useful for polling from n8n or other services.
     */
    public function hasNewMessages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:50',
            'last_id' => 'required|integer|min:0',
        ]);

        $count = WhatsAppMessage::where('phone', $data['phone'])
            ->where('direction', 'IN')
            ->where('id', '>', $data['last_id'])
            ->count();

        return response()->json([
            'ok' => true,
            'has_new' => $count > 0,
            'count' => $count,
        ]);
    }

    public function conversation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:50',
            'active_module' => 'nullable|string',
            'current_step' => 'nullable|string',
            'after_hours' => 'nullable|boolean',
            'active_booking_id' => 'nullable|string',
            'active_lead_id' => 'nullable|string',
        ]);

        $conv = WhatsAppConversation::firstOrNew(['phone' => $data['phone']]);
        $conv->fill([
            'active_module' => $data['active_module'] ?? $conv->active_module,
            'current_step' => $data['current_step'] ?? $conv->current_step,
            'after_hours' => $data['after_hours'] ?? ($conv->after_hours ?? false),
            'active_booking_id' => $data['active_booking_id'] ?? $conv->active_booking_id,
            'active_lead_id' => $data['active_lead_id'] ?? $conv->active_lead_id,
        ]);
        $conv->save();

        return response()->json([
            'ok' => true,
            'conversation' => $conv,
        ]);
    }

    public function booking(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_id' => 'required|string',
            'phone' => 'required|string|max:50',
            'name' => 'nullable',
            'alt_phone' => 'nullable',
            'address' => 'nullable',
            'service' => 'nullable',
            'prefered_datetime' => 'nullable',
            'status' => 'nullable|string|max:50',
            'location_hint' => 'nullable',
        ]);

        // Normalize literal \"null\" strings to real nulls
        foreach (['name', 'alt_phone', 'address', 'service', 'location_hint'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === 'null') {
                $data[$field] = null;
            }
        }

        $booking = WhatsAppBooking::firstOrNew(['booking_id' => $data['booking_id']]);
        $booking->fill($data);

        // Parse any datetime string into proper DB format; if parse fails, store null
        if (!empty($data['prefered_datetime']) && $data['prefered_datetime'] !== 'null') {
            try {
                $booking->prefered_datetime = Carbon::parse($data['prefered_datetime']);
            } catch (\Throwable $e) {
                $booking->prefered_datetime = null;
            }
        } else {
            $booking->prefered_datetime = null;
        }

        $booking->save();

        // Booking flow should classify this WhatsApp lead as customer.
        $this->ensureLeadTypeForPhone($data['phone'], Lead::TYPE_CUSTOMER, $data['name'] ?? null);

        return response()->json([
            'ok' => true,
            'booking' => $booking,
        ]);
    }

    /**
     * Fetch a WhatsApp booking row by booking_id.
     */
    public function bookingDetails(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_id' => 'required|string',
        ]);

        $booking = WhatsAppBooking::where('booking_id', $data['booking_id'])->first();

        return response()->json([
            'ok' => true,
            'booking' => $booking,
        ]);
    }

    /**
     * Check if there is any active booking (status = DRAFT) for a phone.
     */
    public function activeBooking(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:50',
        ]);

        // Normalize status similar to previous Postgres regexp_replace
        $query = WhatsAppBooking::where('phone', $data['phone'])
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(COALESCE(status, ''), '\n', ''), '\r', ''), '\t', '') = 'DRAFT'"
            );

        $count = (clone $query)->count();
        $booking = $count > 0
            ? $query->orderByDesc('created_at')->first()
            : null;

        return response()->json([
            'ok' => true,
            'has_active' => $count > 0,
            'count' => $count,
            'booking' => $booking,
        ]);
    }

    /**
     * Check if there is any active provider lead (status = DRAFT) for a phone.
     * Mirrors the active-booking check but on whatsapp_provider_leads.
     */
    public function activeProviderLead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:50',
        ]);

        $query = ProviderLead::where('phone', $data['phone'])
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(COALESCE(status, ''), '\n', ''), '\r', ''), '\t', '') = 'DRAFT'"
            );

        $count = (clone $query)->count();
        $lead = $count > 0
            ? $query->orderByDesc('created_at')->first()
            : null;

        return response()->json([
            'ok' => true,
            'has_active' => $count > 0,
            'count' => $count,
            'lead' => $lead,
        ]);
    }

    /**
     * Fetch provider lead details by lead_id.
     */
    public function providerLeadDetails(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id' => 'required|string',
        ]);

        $lead = ProviderLead::where('lead_id', $data['lead_id'])->first();

        return response()->json([
            'ok' => true,
            'lead' => $lead,
        ]);
    }

    public function providerLead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id' => 'required|string',
            'phone' => 'required|string|max:50',
            'name' => 'nullable|string',
            'address' => 'nullable|string',
            'service' => 'nullable|string',
            'form_sent' => 'nullable|boolean',
            'status' => 'nullable|string|max:50',
            'created_at' => 'nullable|date',
        ]);

        $lead = ProviderLead::firstOrNew(['lead_id' => $data['lead_id']]);
        $lead->fill($data);
        if (!empty($data['created_at'])) {
            $lead->created_at = $data['created_at'];
        }
        $lead->save();

        // Provider flow should classify this WhatsApp lead as provider.
        $this->ensureLeadTypeForPhone($data['phone'], Lead::TYPE_PROVIDER, $data['name'] ?? null);

        return response()->json([
            'ok' => true,
            'lead' => $lead,
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:50',
            'name' => 'nullable|string',
            'alternate_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'type' => 'nullable|string|max:20',
            'handled_by' => 'nullable|string|max:64',
        ]);

        $user = WhatsAppUser::firstOrNew(['phone' => $data['phone']]);
        $isNew = !$user->exists;

        // Fill regular attributes (excluding handled_by)
        $fillData = $data;
        unset($fillData['handled_by']);
        $user->fill($fillData);

        // handled_by logic:
        // - On first creation, default to 'AI' if not explicitly provided.
        // - On update, only change if a handled_by value is sent.
        if ($isNew && empty($data['handled_by'])) {
            $user->handled_by = 'AI';
        } elseif (!empty($data['handled_by'])) {
            $user->handled_by = $data['handled_by'];
        }

        $user->save();

        // Ensure there is always a CRM lead for this WhatsApp identity.
        $this->ensureUnknownLeadForPhone($data['phone'], $data['name'] ?? null);

        return response()->json([
            'ok' => true,
            'user' => $user,
        ]);
    }

    private function normalizeLeadPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 10) {
            return null;
        }

        return substr($digits, -10);
    }

    private function ensureUnknownLeadForPhone(string $whatsAppPhone, ?string $name = null): ?Lead
    {
        $leadPhone = $this->normalizeLeadPhone($whatsAppPhone);
        if (!$leadPhone) {
            return null;
        }

        $existing = Lead::where('phone_number', $leadPhone)
            ->orderByDesc('id')
            ->get()
            ->first(fn (Lead $lead) => $this->isLeadOpen($lead));

        if ($existing) {
            if (empty($existing->handled_by)) {
                $existing->handled_by = 'AI';
                $existing->save();
            }
            return $existing;
        }

        return Lead::create([
            'name' => trim((string) ($name ?: ('WhatsApp ' . $leadPhone))),
            'phone_number' => $leadPhone,
            'lead_type' => Lead::TYPE_UNKNOWN,
            'date_time_of_lead_received' => now(),
            'handled_by' => 'AI',
            'created_by' => null,
        ]);
    }

    private function ensureLeadTypeForPhone(string $whatsAppPhone, string $leadType, ?string $name = null): ?Lead
    {
        $leadPhone = $this->normalizeLeadPhone($whatsAppPhone);
        if (!$leadPhone) {
            return null;
        }

        // Reuse only an OPEN lead with the same phone and desired type.
        $existing = Lead::where('phone_number', $leadPhone)
            ->where('lead_type', $leadType)
            ->orderByDesc('id')
            ->get()
            ->first(fn (Lead $lead) => $this->isLeadOpen($lead));

        if ($existing) {
            if (empty($existing->handled_by)) {
                $existing->handled_by = 'AI';
                $existing->save();
            }
            return $existing;
        }

        // Otherwise create a new lead specifically for this type.
        $lead = Lead::create([
            'name' => trim((string) ($name ?: ('WhatsApp ' . $leadPhone))),
            'phone_number' => $leadPhone,
            'lead_type' => $leadType,
            'date_time_of_lead_received' => now(),
            'handled_by' => 'AI',
            'created_by' => null,
        ]);
        $this->seedDefaultTypeHistoryForTypedLead($lead);

        return $lead;
    }

    private function seedDefaultTypeHistoryForTypedLead(Lead $lead): void
    {
        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => Lead::TYPE_CUSTOMER,
                'data' => [
                    'customer_lead_status_id' => CustomerLeadStatus::defaultPendingStatusId(),
                    'booking_status' => 'pending',
                ],
                'created_by' => null,
            ]);
        } elseif ($lead->lead_type === Lead::TYPE_PROVIDER) {
            LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => Lead::TYPE_PROVIDER,
                'data' => [
                    'provider_lead_status_id' => ProviderLeadStatus::defaultPendingStatusId(),
                ],
                'created_by' => null,
            ]);
        }
    }

    private function isLeadOpen(Lead $lead): bool
    {
        if ($lead->lead_type === Lead::TYPE_UNKNOWN) {
            return true;
        }

        if (in_array($lead->lead_type, [Lead::TYPE_INVALID, Lead::TYPE_FUTURE_CUSTOMER], true)) {
            return false;
        }

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $history = LeadTypeHistory::where('lead_id', $lead->id)
                ->where('type', Lead::TYPE_CUSTOMER)
                ->latest()
                ->first();
            $data = ($history && is_array($history->data)) ? $history->data : [];
            $statusId = $data['customer_lead_status_id'] ?? null;
            if (!$statusId) {
                return true; // default pending
            }
            $status = CustomerLeadStatus::find($statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
            return !in_array($baseType, ['completed', 'cancel'], true);
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $history = LeadTypeHistory::where('lead_id', $lead->id)
                ->where('type', Lead::TYPE_PROVIDER)
                ->latest()
                ->first();
            $data = ($history && is_array($history->data)) ? $history->data : [];
            $statusId = $data['provider_lead_status_id'] ?? null;
            if (!$statusId) {
                return true; // default pending
            }
            $status = ProviderLeadStatus::find($statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
            return !in_array($baseType, ['completed', 'cancel'], true);
        }

        return false;
    }

    /**
     * Simple existence check for a WhatsApp user by phone.
     */
    public function userExists(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:50',
        ]);

        $exists = WhatsAppUser::where('phone', $data['phone'])->exists();

        return response()->json([
            'ok' => true,
            'exists' => $exists,
        ]);
    }

    /**
     * Fetch full WhatsApp user details by phone.
     */
    public function userDetails(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:50',
        ]);

        $user = WhatsAppUser::where('phone', $data['phone'])->first();

        return response()->json([
            'ok' => true,
            'user' => $user,
        ]);
    }

    /**
     * Return all newer IN messages for a phone after the last OUT message.
     * Mirrors the previous Postgres CTE behavior.
     */
    public function messagesSinceLastOut(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:50',
            'limit' => 'nullable|integer|min:1|max:500',
        ]);

        $phone = $data['phone'];
        $limit = $data['limit'] ?? 100;

        $lastOutId = WhatsAppMessage::where('phone', $phone)
            ->where('direction', 'OUT')
            ->max('id') ?? 0;

        $messages = WhatsAppMessage::where('phone', $phone)
            ->where('direction', 'IN')
            ->where('id', '>', $lastOutId)
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get(['id', 'phone', 'message_text', 'direction', 'message_type', 'created_at']);

        return response()->json([
            'ok' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Return last N messages (IN + OUT) for a phone, newest first.
     */
    public function lastMessages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:50',
            'limit' => 'nullable|integer|min:1|max:500',
        ]);

        $phone = $data['phone'];
        $limit = $data['limit'] ?? 50;

        $messages = WhatsAppMessage::where('phone', $phone)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get(['id', 'phone', 'message_text', 'direction', 'message_type', 'status', 'created_at']);

        return response()->json([
            'ok' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Fetch current (non-completed) conversation state by phone.
     */
    public function activeConversation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:50',
        ]);

        $conv = WhatsAppConversation::where('phone', $data['phone'])
            ->where('current_step', '<>', 'COMPLETED')
            ->first();

        return response()->json([
            'ok' => true,
            'conversation' => $conv,
        ]);
    }

    /**
     * Update an existing conversation by id + phone.
     */
    public function updateConversation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'conversation_id' => 'required|integer|min:1',
            'phone' => 'required|string|max:50',
            'active_module' => 'nullable|string',
            'current_step' => 'nullable|string',
            'active_booking_id' => 'nullable|string',
            'active_lead_id' => 'nullable|string',
        ]);

        $conv = WhatsAppConversation::where('id', $data['conversation_id'])
            ->where('phone', $data['phone'])
            ->first();

        if (!$conv) {
            return response()->json([
                'ok' => false,
                'error' => 'Conversation not found',
            ], 404);
        }

        if (array_key_exists('active_module', $data)) {
            $conv->active_module = $data['active_module'];
        }
        if (array_key_exists('current_step', $data)) {
            $conv->current_step = $data['current_step'];
        }
        if (array_key_exists('active_booking_id', $data)) {
            $conv->active_booking_id = $data['active_booking_id'];
        }
        if (array_key_exists('active_lead_id', $data)) {
            $conv->active_lead_id = $data['active_lead_id'];
        }

        $conv->save();

        return response()->json([
            'ok' => true,
            'conversation' => $conv,
        ]);
    }
}

