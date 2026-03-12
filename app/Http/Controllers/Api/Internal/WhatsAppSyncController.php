<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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
            'message_text' => 'required|string',
            'direction' => 'required|string|in:IN,OUT',
            'message_type' => 'nullable|string|max:20',
            'created_at' => 'nullable|date',
            'wa_message_id' => 'nullable|string|max:255',
            'sent_by_id' => 'nullable|string|max:64',
            'sent_by' => 'nullable|string|max:255',
        ]);

        $msg = new WhatsAppMessage();
        $msg->fill([
            'phone' => $data['phone'],
            'message_text' => $data['message_text'],
            'direction' => $data['direction'],
            'message_type' => $data['message_type'] ?? 'TEXT',
            'wa_message_id' => $data['wa_message_id'] ?? null,
            'sent_by_id' => $data['sent_by_id'] ?? null,
            'sent_by' => $data['sent_by'] ?? ($data['direction'] === 'OUT' ? 'AI' : 'Customer'),
        ]);
        if (!empty($data['created_at'])) {
            $msg->created_at = $data['created_at'];
        }
        $msg->save();

        // Bust caches so Active Chats list and chat panel pick up new message.
        Cache::forget('whatsapp_active_chats_list');
        Cache::forget('whatsapp_chat_full_' . md5($data['phone']));

        return response()->json(['ok' => true, 'id' => $msg->id]);
    }

    /**
     * Update message status (sent, delivered, read). Call from n8n when WhatsApp status webhook fires.
     */
    public function messageStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'wa_message_id' => 'required|string|max:255',
            'status' => 'required|string|in:sent,delivered,read',
            'phone' => 'nullable|string|max:50',
            'status_timestamp' => 'nullable|date',
        ]);

        $msg = WhatsAppMessage::where('wa_message_id', $data['wa_message_id'])
            ->where('direction', 'OUT')
            ->first();

        if (!$msg) {
            return response()->json(['ok' => false, 'error' => 'Message not found'], 404);
        }

        $msg->status = $data['status'];
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
        ]);

        $user = WhatsAppUser::firstOrNew(['phone' => $data['phone']]);
        $user->fill($data);
        $user->save();

        return response()->json(['ok' => true]);
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

