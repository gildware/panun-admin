<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\WhatsAppModule\Entities\ProviderLead;
use Modules\WhatsAppModule\Entities\WhatsAppBooking;
use Modules\WhatsAppModule\Entities\WhatsAppConversation;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;

class WhatsAppController extends Controller
{
    use AuthorizesRequests;
    /**
     * Tabbed index: Active Chats | Provider Leads | Bookings | Users (all from Neon/WhatsApp DB only).
     */
    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('whatsapp_chat_view');

        $tab = $request->get('tab', 'chats');
        if (!in_array($tab, ['chats', 'leads', 'bookings', 'users'], true)) {
            $tab = 'chats';
        }

        if ($tab === 'chats') {
            try {
                $chats = $this->getActiveChatsList();
            } catch (\Throwable $e) {
                Toastr::error('Could not load chats. ' . $e->getMessage());
                $chats = collect();
            }
            return view('whatsappmodule::admin.conversations.index', compact('tab', 'chats'));
        }

        if ($tab === 'leads') {
            try {
                $page = (int) $request->get('page', 1);
                $cacheKey = 'whatsapp_leads_page1';
                $ttl = config('whatsappmodule.cache_ttl', 60);
                if ($page === 1 && $ttl > 0) {
                    $leads = Cache::remember($cacheKey, $ttl, function () {
                        return ProviderLead::select(['lead_id', 'phone', 'name', 'service', 'status', 'created_at'])
                            ->orderByDesc('created_at')
                            ->simplePaginate(20);
                    });
                    $leads->withPath($request->url())->appends($request->query());
                } else {
                    $leads = ProviderLead::select(['lead_id', 'phone', 'name', 'service', 'status', 'created_at'])
                        ->orderByDesc('created_at')
                        ->simplePaginate(20)->withQueryString();
                }
                $leadsError = null;
            } catch (\Throwable $e) {
                \Log::warning('WhatsApp leads tab failed.', ['error' => $e->getMessage()]);
                $leads = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
                $leadsError = $e->getMessage();
            }
            return view('whatsappmodule::admin.conversations.index', compact('tab', 'leads', 'leadsError'));
        }

        if ($tab === 'bookings') {
            try {
                $page = (int) $request->get('page', 1);
                $cacheKey = 'whatsapp_bookings_page1';
                $ttl = config('whatsappmodule.cache_ttl', 60);
                if ($page === 1 && $ttl > 0) {
                    $bookings = Cache::remember($cacheKey, $ttl, function () {
                        return WhatsAppBooking::select(['booking_id', 'id', 'phone', 'name', 'service', 'status', 'created_at'])
                            ->orderByDesc('created_at')
                            ->simplePaginate(20);
                    });
                    $bookings->withPath($request->url())->appends($request->query());
                } else {
                    $bookings = WhatsAppBooking::select(['booking_id', 'id', 'phone', 'name', 'service', 'status', 'created_at'])
                        ->orderByDesc('created_at')
                        ->simplePaginate(20)->withQueryString();
                }
                $bookingsError = null;
            } catch (\Throwable $e) {
                \Log::warning('WhatsApp bookings tab failed.', ['error' => $e->getMessage()]);
                $bookings = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
                $bookingsError = $e->getMessage();
            }
            return view('whatsappmodule::admin.conversations.index', compact('tab', 'bookings', 'bookingsError'));
        }

        if ($tab === 'users') {
            try {
                $page = (int) $request->get('page', 1);
                $cacheKey = 'whatsapp_users_page1';
                $ttl = config('whatsappmodule.cache_ttl', 60);
                if ($page === 1 && $ttl > 0) {
                    $users = Cache::remember($cacheKey, $ttl, function () {
                        return WhatsAppUser::select(['id', 'phone', 'name', 'alternate_phone', 'address', 'type', 'created_at', 'updated_at'])
                            ->orderByDesc('created_at')
                            ->simplePaginate(20);
                    });
                    $users->withPath($request->url())->appends($request->query());
                } else {
                    $users = WhatsAppUser::select(['id', 'phone', 'name', 'alternate_phone', 'address', 'type', 'created_at', 'updated_at'])
                        ->orderByDesc('created_at')
                        ->simplePaginate(20)->withQueryString();
                }
                $usersError = null;
            } catch (\Throwable $e) {
                \Log::warning('WhatsApp users tab failed.', ['error' => $e->getMessage()]);
                $users = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
                $usersError = $e->getMessage();
            }
            return view('whatsappmodule::admin.conversations.index', compact('tab', 'users', 'usersError'));
        }

        return redirect()->route('admin.whatsapp.conversations.index', ['tab' => 'chats']);
    }

    /**
     * JSON: full user details + bookings for the "View more" modal (Neon only). Lookup by phone.
     */
    public function userDetails(Request $request): JsonResponse
    {
        $this->authorize('whatsapp_chat_view');

        $phone = $request->get('phone');
        if (empty($phone)) {
            return response()->json(['error' => 'Phone is required'], 400);
        }

        try {
            $user = WhatsAppUser::where('phone', $phone)->firstOrFail();
            $bookings = WhatsAppBooking::where('phone', $phone)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'booking_id' => $b->booking_id ?? $b->id,
                    'service' => $b->service ?? '—',
                    'status' => $b->status ?? '—',
                    'created_at' => $b->created_at?->format('M j, Y H:i'),
                ]);
            $userPayload = $user->only(['phone', 'name', 'alternate_phone', 'address', 'type']);
            $userPayload['created_at'] = $user->created_at?->format('M j, Y H:i');
            $userPayload['updated_at'] = $user->updated_at?->format('M j, Y H:i');
            return response()->json([
                'user' => $userPayload,
                'bookings' => $bookings,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Active chat: messages for a phone + reply box.
     */
    public function chat(Request $request): View|RedirectResponse
    {
        $this->authorize('whatsapp_chat_view');

        $phone = $request->get('phone');
        if (empty($phone)) {
            Toastr::warning('Phone is required.');
            return redirect()->route('admin.whatsapp.conversations.index', ['tab' => 'chats']);
        }

        try {
            $messages = WhatsAppMessage::where('phone', $phone)->orderBy('created_at')->get();
            $conversationState = WhatsAppConversation::where('phone', $phone)->first();
            $bookingLink = $this->resolveBookingLinkForPhone($phone);
        } catch (\Throwable $e) {
            Toastr::error('Could not load chat.');
            return redirect()->route('admin.whatsapp.conversations.index', ['tab' => 'chats']);
        }

        return view('whatsappmodule::admin.conversations.chat', compact('phone', 'messages', 'conversationState', 'bookingLink'));
    }

    /**
     * Send reply (agent message) for a phone. Saves to messages table with direction OUT.
     */
    public function sendReply(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('whatsapp_chat_reply');

        $request->validate([
            'phone' => 'required|string|max:50',
            'body' => 'required|string|max:4096',
        ]);

        $whatsAppError = null;

        try {
            $message = new WhatsAppMessage();
            $message->phone = $request->input('phone');
            $message->message_text = $request->input('body');
            $message->direction = 'OUT';
            $message->message_type = 'TEXT';
            $user = $request->user();
            if ($user) {
                $message->sent_by_id = $user->id;
            }
            $message->save();

            // Also send to real WhatsApp via WhatsApp Cloud API (if configured).
            $waId = $this->sendWhatsAppText($message->phone, $message->message_text, $whatsAppError);
            $sentToWhatsApp = $waId !== null;
            if ($waId !== null) {
                $message->wa_message_id = $waId;
                $message->save();
            }

            Cache::forget('whatsapp_active_chats_list');
            Cache::forget('whatsapp_chat_full_' . md5($request->input('phone')));
            if ($sentToWhatsApp) {
                Toastr::success('Reply sent to WhatsApp.');
            } else {
                Toastr::warning('Reply saved, but WhatsApp API failed.');
            }
        } catch (\Throwable $e) {
            Toastr::error('Failed to send reply.');
            $whatsAppError = $whatsAppError ?: $e->getMessage();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'OK',
                'whatsapp_sent' => $sentToWhatsApp ?? false,
                'whatsapp_error' => $whatsAppError,
            ]);
        }
        return redirect()->route('admin.whatsapp.conversations.chat', ['phone' => $request->input('phone')]);
    }

    /**
     * Send via WhatsApp Cloud API using config/services.php.
     * Returns wa_message_id on success, null on failure (and logs errors).
     */
    private function sendWhatsAppText(string $phone, string $body, ?string &$error = null): ?string
    {
        $token = (string) config('services.whatsapp_cloud.token');
        $phoneId = (string) config('services.whatsapp_cloud.phone_id');
        if (!$token || !$phoneId) {
            \Log::warning('WhatsApp outbound not configured (missing services.whatsapp_cloud config).');
            $error = 'missing_config';
            return null;
        }

        $version = (string) config('services.whatsapp_cloud.version', 'v19.0');
        $url = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => true,
                        'body' => $body,
                    ],
                ]);

            if ($response->failed()) {
                $error = 'status:' . $response->status() . ' body:' . $response->body();
                \Log::warning('WhatsApp Cloud send failed', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $payload = $response->json();
            $waId = $payload['messages'][0]['id'] ?? null;

            \Log::info('WhatsApp Cloud send ok', [
                'phone' => $phone,
                'status' => $response->status(),
                'wa_message_id' => $waId,
            ]);
            return $waId;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            \Log::warning('WhatsApp Cloud send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * AJAX: get messages for a phone. Optional: booking_link and conversation_state for right panel.
     * Caches full response per phone to avoid Neon round trips on repeat opens.
     */
    public function chatMessages(Request $request): JsonResponse
    {
        $this->authorize('whatsapp_chat_view');

        $phone = $request->get('phone');
        if (empty($phone)) {
            return response()->json(['data' => []], 400);
        }

        $cacheKey = 'whatsapp_chat_full_' . md5($phone);
        $ttlChat = config('whatsappmodule.cache_ttl_chat', 20);
        if ($request->boolean('full') && $ttlChat > 0) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return response()->json($cached);
            }
        }

        $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        $limit = min((int) $request->get('limit', config('whatsappmodule.messages_limit', 100)), 200);
        try {
            $rows = DB::table($table . ' as m')
                ->leftJoin('users as u', 'm.sent_by_id', '=', 'u.id')
                ->where('m.phone', $phone)
                ->orderByDesc('m.created_at')
                ->limit($limit)
                ->get([
                    'm.*',
                    'u.first_name',
                    'u.last_name',
                ]);
            $messages = collect($rows)->map(function ($row) {
                $row = (array) $row;
                $text = $row['message_text'] ?? '';
                $created = $row['created_at'] ?? null;
                if ($created && !is_string($created)) {
                    $created = $created instanceof \DateTimeInterface ? $created->format('c') : (string) $created;
                }
                $sentBy = $row['sent_by'] ?? null;
                $first = $row['first_name'] ?? null;
                $last = $row['last_name'] ?? null;
                $fullName = trim(trim((string) $first) . ' ' . trim((string) $last));
                if ($fullName !== '') {
                    $sentBy = $fullName;
                }

                return [
                    'id' => $row['id'] ?? null,
                    'phone' => $row['phone'] ?? '',
                    'message_text' => $text,
                    'body' => $text,
                    'direction' => $row['direction'] ?? 'IN',
                    'message_type' => $row['message_type'] ?? 'TEXT',
                    'status' => $row['status'] ?? null,
                    'sent_by' => $sentBy,
                    'created_at' => $created,
                ];
            })->reverse()->values();
        } catch (\Throwable $e) {
            \Log::warning('WhatsApp chatMessages failed.', ['phone' => $phone, 'error' => $e->getMessage()]);
            return response()->json(['data' => [], 'error' => 'Failed to load messages'], 500);
        }

        $payload = ['data' => $messages];

        if ($request->boolean('full')) {
            $payload['booking_link'] = null;
            $payload['conversation_state'] = null;
            $bookingsTable = config('whatsappmodule.tables.bookings', 'whatsapp_bookings');
            $conversationTable = config('whatsappmodule.tables.conversation', 'whatsapp_conversations');
            try {
                $extra = DB::selectOne("
                    SELECT
                        (SELECT row_to_json(b) FROM (
                            SELECT booking_id, id FROM {$bookingsTable} WHERE phone = ? ORDER BY created_at DESC LIMIT 1
                        ) b) AS booking,
                        (SELECT row_to_json(c) FROM (
                            SELECT active_module, current_step, after_hours FROM {$conversationTable} WHERE phone = ? LIMIT 1
                        ) c) AS conv
                ", [$phone, $phone]);
                if ($extra && $extra->booking) {
                    $b = is_string($extra->booking) ? json_decode($extra->booking, true) : (array) $extra->booking;
                    $id = $b['booking_id'] ?? $b['id'] ?? null;
                    if ($id !== null) {
                        $payload['booking_link'] = route('admin.booking.details', [$id, 'web_page' => 'details']);
                    }
                }
                if ($extra && $extra->conv) {
                    $c = is_string($extra->conv) ? json_decode($extra->conv, true) : (array) $extra->conv;
                    $payload['conversation_state'] = [
                        'active_module' => $c['active_module'] ?? null,
                        'current_step' => $c['current_step'] ?? null,
                        'after_hours' => (bool) ($c['after_hours'] ?? false),
                    ];
                }
            } catch (\Throwable $e) {
                // ignore
            }
            if ($ttlChat > 0) {
                Cache::put($cacheKey, $payload, $ttlChat);
            }
        }

        return response()->json($payload);
    }

    /**
     * List of active chats: one row per phone with last message. Last 30 days, max 100 chats.
     * Cached to reduce round trips to remote WhatsApp DB.
     */
    private function getActiveChatsList(): \Illuminate\Support\Collection
    {
        $ttl = config('whatsappmodule.cache_ttl', 30);
        $cacheKey = 'whatsapp_active_chats_list';

        if ($ttl > 0) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        $cutoff = now()->subDays(30)->format('Y-m-d H:i:s');
        $rows = DB::select("
            SELECT m.phone, m.direction, m.status, LEFT(m.message_text, 80) AS message_text, m.created_at
            FROM {$table} m
            INNER JOIN (
                SELECT phone, MAX(created_at) AS max_created
                FROM {$table}
                WHERE created_at >= ?
                GROUP BY phone
            ) t ON m.phone = t.phone AND m.created_at = t.max_created
            ORDER BY m.created_at DESC
            LIMIT 100
        ", [$cutoff]);
        $result = collect($rows);

        $phones = $result->pluck('phone')->unique()->filter()->values()->all();
        if (!empty($phones)) {
            $names = WhatsAppUser::whereIn('phone', $phones)->pluck('name', 'phone');
            $result = $result->map(function ($row) use ($names) {
                $row->name = $names[$row->phone] ?? null;
                return $row;
            });
        }

        if ($ttl > 0) {
            Cache::put($cacheKey, $result, $ttl);
        }

        return $result;
    }

    private function resolveBookingLinkForPhone(string $phone): ?string
    {
        try {
            $booking = WhatsAppBooking::where('phone', $phone)->orderByDesc('created_at')->first();
            if (!$booking) {
                return null;
            }
            $id = $booking->booking_id ?? $booking->id;
            if ($id === null) {
                return null;
            }
            return route('admin.booking.details', [$id, 'web_page' => 'details']);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
