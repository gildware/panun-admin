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
use Modules\WhatsAppModule\Services\WhatsAppCloudService;

class WhatsAppController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected WhatsAppCloudService $whatsAppCloud
    ) {}
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
                $handlerFilter = $request->get('handler', 'all'); // all | ai | <admin-id> — default 'all' so first load shows all chats
                $allChats = $this->getActiveChatsList();

                // Build handler options: All, AI, then one per admin id present
                $handledByKeys = $allChats
                    ->pluck('handled_by_key')
                    ->unique()
                    ->filter()
                    ->values();

                $chatHandlers = [];
                $chatHandlers[] = ['key' => 'all', 'label' => translate('All Chats')];

                if ($handledByKeys->contains('AI')) {
                    $chatHandlers[] = ['key' => 'ai', 'label' => translate('Handled by AI')];
                }

                $adminIds = $handledByKeys->reject(function ($v) {
                    return $v === 'AI';
                })->values();

                if ($adminIds->isNotEmpty()) {
                    $admins = DB::table('users')
                        ->whereIn('id', $adminIds)
                        ->get(['id', 'first_name', 'last_name']);
                    foreach ($admins as $admin) {
                        $fullName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));
                        $chatHandlers[] = [
                            'key' => (string) $admin->id,
                            'label' => translate('Handled by') . ' ' . ($fullName ?: $admin->id),
                        ];
                    }
                }

                // Apply filter
                $chats = $allChats->filter(function ($chat) use ($handlerFilter) {
                    if ($handlerFilter === 'all') {
                        return true;
                    }
                    if ($handlerFilter === 'ai') {
                        return $chat->handled_by_key === 'AI';
                    }
                    return $chat->handled_by_key === $handlerFilter;
                })->values();
            } catch (\Throwable $e) {
                Toastr::error('Could not load chats. ' . $e->getMessage());
                $chats = collect();
                $chatHandlers = [
                    ['key' => 'all', 'label' => translate('All Chats')],
                    ['key' => 'ai', 'label' => translate('Handled by AI')],
                ];
                $handlerFilter = 'all';
            }
            return view('whatsappmodule::admin.conversations.index', compact('tab', 'chats', 'chatHandlers', 'handlerFilter'));
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
                $leadMetaByPhone = $this->resolveLeadMetaByNormalizedPhone(
                    collect($users->items())->pluck('phone')->filter()->all()
                );
                $users->setCollection(
                    $users->getCollection()->map(function ($user) use ($leadMetaByPhone) {
                        $normalized = $this->normalizeLeadPhone($user->phone ?? null);
                        $meta = $normalized ? ($leadMetaByPhone[$normalized] ?? null) : null;
                        $user->lead_id = $meta['primary_id'] ?? null;
                        $user->lead_count = $meta['count'] ?? 0;
                        return $user;
                    })
                );
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
            $normalized = $this->normalizeLeadPhone($phone);
            $leads = $normalized
                ? Lead::where('phone_number', $normalized)
                    ->orderByDesc('id')
                    ->get(['id', 'lead_type', 'phone_number', 'name', 'date_time_of_lead_received'])
                : collect();
            $leadStatusMap = $this->buildLeadStatusMap($leads);
            $lead = $leads->first();
            return response()->json([
                'user' => $userPayload,
                'bookings' => $bookings,
                'lead' => $lead ? [
                    'id' => $lead->id,
                    'lead_type' => $lead->lead_type,
                    'phone_number' => $lead->phone_number,
                    'url' => route('admin.lead.show', $lead->id),
                    'is_open' => $leadStatusMap[$lead->id]['is_open'] ?? false,
                ] : null,
                'leads' => $leads->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'lead_type' => $item->lead_type,
                    'phone_number' => $item->phone_number,
                    'received_at' => $item->date_time_of_lead_received?->format('M j, Y H:i'),
                    'url' => route('admin.lead.show', $item->id),
                    'is_open' => $leadStatusMap[$item->id]['is_open'] ?? false,
                ])->values(),
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
            'body' => 'nullable|string|max:4096',
            // Support both single and multiple attachments from the UI
            'attachment' => 'nullable|file|max:10240',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        $whatsAppError = null;
        $sentToWhatsApp = false;
        $whatsappGraph = null;

        $rawPhone = trim((string) $request->input('phone'));
        $graphTo = $this->whatsAppCloud->normalizeRecipientPhone($rawPhone);
        if ($graphTo === null) {
            $whatsAppError = 'invalid_phone';
            Toastr::error(translate('Invalid_whatsapp_phone'));
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'OK',
                    'whatsapp_sent' => false,
                    'whatsapp_error' => $whatsAppError,
                    'whatsapp_graph' => ['stage' => 'validate', 'raw_input' => $rawPhone],
                ]);
            }

            return redirect()->route('admin.whatsapp.conversations.chat', ['phone' => $rawPhone]);
        }

        // Keep message rows on the same `phone` string as existing DB thread (full value from list `data-phone`),
        // while Graph API `to` always uses normalized digits ($graphTo).
        $threadPhone = $this->resolveWhatsappThreadPhoneKey($rawPhone, $graphTo);

        try {
            $body = trim((string) $request->input('body', ''));
            $user = $request->user();

            // Normalise attachments: support attachments[] (multiple) and attachment (single)
            $files = [];
            if ($request->hasFile('attachments')) {
                $files = $request->file('attachments');
            } elseif ($request->hasFile('attachment')) {
                $files = [$request->file('attachment')];
            }

            if (empty($files)) {
                // Text-only message
                $message = new WhatsAppMessage();
                $message->phone = $threadPhone;
                $message->message_text = $body;
                $message->direction = 'OUT';
                $message->message_type = 'TEXT';
                if ($user) {
                    $message->sent_by_id = $user->id;
                }
                $message->save();

                $waId = $this->whatsAppCloud->sendOutbound($graphTo, $body, null, $whatsAppError, $whatsappGraph);
                $sentToWhatsApp = $waId !== null;
                if ($waId !== null) {
                    $message->wa_message_id = $waId;
                    $message->save();
                }
            } else {
                // One WhatsAppMessage per attachment; caption only on the first media message
                foreach ($files as $index => $file) {
                    $path = $file->store('whatsapp_attachments', 'public');
                    $ext = strtolower($file->getClientOriginalExtension() ?: pathinfo($path, PATHINFO_EXTENSION));
                    $storedMediaType = $this->whatsappMediaTypeFromExtension($ext);

                    $message = new WhatsAppMessage();
                    $message->phone = $threadPhone;
                    $message->direction = 'OUT';
                    $message->message_type = strtoupper($storedMediaType);
                    $message->media_path = $path;
                    $message->message_text = $body !== '' && $index === 0 ? $body : $file->getClientOriginalName();
                    if ($user) {
                        $message->sent_by_id = $user->id;
                    }
                    $message->save();

                    $caption = $body !== '' && $index === 0 ? $body : '';
                    $waId = $this->whatsAppCloud->sendOutbound($graphTo, $caption, $path, $whatsAppError, $whatsappGraph);
                    $sentToWhatsApp = $sentToWhatsApp || $waId !== null;
                    if ($waId !== null) {
                        $message->wa_message_id = $waId;
                        $message->save();
                    }
                }
            }

            Cache::forget('whatsapp_active_chats_list');
            foreach (array_unique(array_filter([$threadPhone, $rawPhone, $graphTo])) as $p) {
                Cache::forget('whatsapp_chat_full_' . md5($p));
            }
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
                'whatsapp_sent' => $sentToWhatsApp,
                'whatsapp_error' => $whatsAppError,
                'whatsapp_graph' => $whatsappGraph,
            ]);
        }

        return redirect()->route('admin.whatsapp.conversations.chat', ['phone' => $threadPhone]);
    }

    /**
     * Match the exact `phone` column used in whatsapp_messages so OUT rows stay in the same thread as IN (from DB / data-phone).
     */
    private function resolveWhatsappThreadPhoneKey(string $rawSubmitted, string $normalizedDigits): string
    {
        $raw = trim($rawSubmitted);
        $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        foreach (array_unique(array_filter([$raw, $normalizedDigits])) as $candidate) {
            if (DB::table($table)->where('phone', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $normalizedDigits;
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

        $focusMessageId = (int) $request->get('focus_message_id', 0);
        $hasFocus = $focusMessageId > 0;

        $cacheKey = 'whatsapp_chat_full_' . md5($phone);
        $ttlChat = config('whatsappmodule.cache_ttl_chat', 20);
        $markSeen = $request->boolean('mark_seen');
        if ($request->boolean('full') && !$markSeen && $ttlChat > 0 && !$hasFocus) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return response()->json($cached);
            }
        }

        $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        $limit = min((int) $request->get('limit', config('whatsappmodule.messages_limit', 100)), 200);

        $mapRow = static function ($row): array {
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

            $mediaPath = $row['media_path'] ?? null;
            $mediaUrl = null;
            if ($mediaPath && Storage::disk('public')->exists($mediaPath)) {
                $mediaUrl = asset('storage/' . ltrim($mediaPath, '/'));
            }

            return [
                'id' => $row['id'] ?? null,
                'phone' => $row['phone'] ?? '',
                'message_text' => $text,
                'body' => $text,
                'direction' => $row['direction'] ?? 'IN',
                'message_type' => $row['message_type'] ?? 'TEXT',
                'media_url' => $mediaUrl,
                'status' => $row['status'] ?? null,
                'status_detail' => $row['status_detail'] ?? null,
                'sent_by' => $sentBy,
                'created_at' => $created,
            ];
        };

        try {
            $selectCols = ['m.*', 'u.first_name', 'u.last_name'];
            $rows = null;

            if ($hasFocus) {
                $anchor = DB::table($table)->where('phone', $phone)->where('id', $focusMessageId)->first();
                if ($anchor && $anchor->created_at !== null) {
                    $before = DB::table($table . ' as m')
                        ->leftJoin('users as u', 'm.sent_by_id', '=', 'u.id')
                        ->where('m.phone', $phone)
                        ->where('m.created_at', '<=', $anchor->created_at)
                        ->orderByDesc('m.created_at')
                        ->limit(75)
                        ->get($selectCols);
                    $after = DB::table($table . ' as m')
                        ->leftJoin('users as u', 'm.sent_by_id', '=', 'u.id')
                        ->where('m.phone', $phone)
                        ->where('m.created_at', '>', $anchor->created_at)
                        ->orderBy('m.created_at')
                        ->limit(75)
                        ->get($selectCols);
                    $rows = collect($before)->reverse()->values()
                        ->concat($after)
                        ->unique(fn ($r) => $r->id)
                        ->values();
                }
            }

            if ($rows === null) {
                $rows = DB::table($table . ' as m')
                    ->leftJoin('users as u', 'm.sent_by_id', '=', 'u.id')
                    ->where('m.phone', $phone)
                    ->orderByDesc('m.created_at')
                    ->limit($limit)
                    ->get($selectCols);
            }

            $messages = collect($rows)->map($mapRow)->reverse()->values();
        } catch (\Throwable $e) {
            \Log::warning('WhatsApp chatMessages failed.', ['phone' => $phone, 'error' => $e->getMessage()]);
            return response()->json(['data' => [], 'error' => 'Failed to load messages'], 500);
        }

        // If requested, mark all IN messages for this phone as seen by admin (full thread only).
        if ($markSeen) {
            try {
                DB::table($table)
                    ->where('phone', $phone)
                    ->where('direction', 'IN')
                    ->whereNull('admin_seen_at')
                    ->update([
                        'admin_seen_at' => now(),
                    ]);
                // Clear active chats cache so unread counts refresh.
                Cache::forget('whatsapp_active_chats_list');
            } catch (\Throwable $e) {
                \Log::warning('Failed to mark whatsapp messages as admin seen', [
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $payload = ['data' => $messages];

        // Handler info: who currently owns this chat (AI or a specific admin)
        $handler = [
            'type' => 'AI',
            'id' => null,
            'name' => 'AI',
        ];
        try {
            $waUser = WhatsAppUser::where('phone', $phone)->first();
            if ($waUser && $waUser->handled_by) {
                if ($waUser->handled_by === 'AI') {
                    $handler = ['type' => 'AI', 'id' => null, 'name' => 'AI'];
                } else {
                    $adminRow = DB::table('users')->where('id', $waUser->handled_by)->first();
                    $fullName = $adminRow
                        ? trim(($adminRow->first_name ?? '') . ' ' . ($adminRow->last_name ?? ''))
                        : null;
                    $handler = [
                        'type' => 'USER',
                        'id' => $waUser->handled_by,
                        'name' => $fullName ?: 'Agent',
                    ];
                }
            }
        } catch (\Throwable $e) {
            // ignore handler lookup failures
        }
        $payload['handler'] = $handler;

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
            if ($ttlChat > 0 && !$hasFocus) {
                Cache::put($cacheKey, $payload, $ttlChat);
            }
        }

        return response()->json($payload);
    }

    /**
     * Unified search: active chats (name, phone, last-message preview) + message hits across conversations.
     */
    public function conversationsSearch(Request $request): JsonResponse
    {
        $this->authorize('whatsapp_chat_view');

        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['chats' => [], 'messages' => []]);
        }

        try {
            $active = $this->getActiveChatsList();
            $needle = mb_strtolower($q);
            $digitsNeedle = preg_replace('/\D+/', '', $q);
            $qLowerPhone = mb_strtolower($q);

            $matchedChats = $active->filter(function ($row) use ($needle, $digitsNeedle, $qLowerPhone) {
                $name = mb_strtolower(trim((string) ($row->name ?? '')));
                $phone = (string) ($row->phone ?? '');
                $phoneDigits = preg_replace('/\D+/', '', $phone);
                $preview = mb_strtolower((string) ($row->message_text ?? ''));
                if ($name !== '' && str_contains($name, $needle)) {
                    return true;
                }
                if ($phone !== '' && str_contains(mb_strtolower($phone), $qLowerPhone)) {
                    return true;
                }
                if (strlen($digitsNeedle) >= 3 && $phoneDigits !== '' && str_contains($phoneDigits, $digitsNeedle)) {
                    return true;
                }
                if ($preview !== '' && str_contains($preview, $needle)) {
                    return true;
                }

                return false;
            })->take(20);

            $chatsOut = $matchedChats->map(function ($row) {
                $created = $row->created_at ?? null;
                if ($created && !is_string($created) && $created instanceof \DateTimeInterface) {
                    $created = $created->format('c');
                }

                return [
                    'phone' => $row->phone ?? '',
                    'name' => $row->name ?? null,
                    'preview' => $row->message_text ?? '',
                    'created_at' => $created,
                ];
            })->values()->all();

            $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
            $usersTable = config('whatsappmodule.tables.users', 'whatsapp_users');
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q) . '%';

            $msgRows = DB::table($table . ' as m')
                ->leftJoin($usersTable . ' as u', 'u.phone', '=', 'm.phone')
                ->where('m.message_text', 'like', $like)
                ->orderByDesc('m.created_at')
                ->limit(20)
                ->get(['m.id', 'm.phone', 'm.message_text', 'm.created_at', 'u.name']);

            $messagesOut = collect($msgRows)->map(function ($m) {
                $text = (string) ($m->message_text ?? '');
                $created = $m->created_at ?? null;
                if ($created && !is_string($created) && $created instanceof \DateTimeInterface) {
                    $created = $created->format('c');
                }

                return [
                    'id' => $m->id,
                    'phone' => $m->phone ?? '',
                    'name' => $m->name ?? null,
                    'snippet' => mb_strlen($text) > 140 ? mb_substr($text, 0, 140) . '…' : $text,
                    'created_at' => $created,
                ];
            })->values()->all();
        } catch (\Throwable $e) {
            \Log::warning('WhatsApp conversationsSearch failed.', ['error' => $e->getMessage()]);

            return response()->json(['chats' => [], 'messages' => [], 'error' => 'Search failed'], 500);
        }

        return response()->json(['chats' => $chatsOut, 'messages' => $messagesOut]);
    }

    /**
     * Change chat handler between AI and the current admin user.
     */
    public function handoff(Request $request): JsonResponse
    {
        $this->authorize('whatsapp_chat_reply');

        $data = $request->validate([
            'phone' => 'required|string|max:50',
            'mode' => 'required|string|in:take,ai', // take => current admin, ai => hand back to AI
        ]);

        $waUser = WhatsAppUser::firstOrNew(['phone' => $data['phone']]);

        if ($data['mode'] === 'ai') {
            $waUser->handled_by = 'AI';
        } else {
            $admin = $request->user();
            $waUser->handled_by = $admin ? (string) $admin->id : 'AI';
        }
        $waUser->save();

        // Clear caches so next chatMessages call reflects new handler immediately.
        Cache::forget('whatsapp_active_chats_list');
        Cache::forget('whatsapp_chat_full_' . md5($waUser->phone));

        $handler = [
            'type' => 'AI',
            'id' => null,
            'name' => 'AI',
        ];

        if ($waUser->handled_by && $waUser->handled_by !== 'AI') {
            $adminRow = DB::table('users')->where('id', $waUser->handled_by)->first();
            $fullName = $adminRow
                ? trim(($adminRow->first_name ?? '') . ' ' . ($adminRow->last_name ?? ''))
                : null;
            $handler = [
                'type' => 'USER',
                'id' => $waUser->handled_by,
                'name' => $fullName ?: 'Agent',
            ];
        }

        return response()->json([
            'ok' => true,
            'handler' => $handler,
        ]);
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
            SELECT m.phone,
                   m.direction,
                   m.status,
                   LEFT(m.message_text, 80) AS message_text,
                   m.created_at,
                   COALESCE(unread.unread_count, 0) AS unread_count
            FROM {$table} m
            INNER JOIN (
                SELECT phone, MAX(created_at) AS max_created
                FROM {$table}
                WHERE created_at >= ?
                GROUP BY phone
            ) t ON m.phone = t.phone AND m.created_at = t.max_created
            LEFT JOIN (
                SELECT phone, COUNT(*) AS unread_count
                FROM {$table}
                WHERE direction = 'IN'
                  AND (admin_seen_at IS NULL)
                GROUP BY phone
            ) unread ON unread.phone = m.phone
            ORDER BY m.created_at DESC
            LIMIT 100
        ", [$cutoff]);
        $result = collect($rows);

        $phones = $result->pluck('phone')->unique()->filter()->values()->all();
        $names = [];
        $handledByMap = [];
        if (!empty($phones)) {
            $waUsers = WhatsAppUser::whereIn('phone', $phones)->get(['phone', 'name', 'handled_by']);
            foreach ($waUsers as $u) {
                $names[$u->phone] = $u->name;
                $handledByMap[$u->phone] = $u->handled_by ?: 'AI';
            }
        }
        // Preload admin user names for handled_by IDs
        $adminNamesById = [];
        if (!empty($handledByMap)) {
            $adminIds = collect($handledByMap)
                ->filter(fn ($v) => $v && $v !== 'AI')
                ->unique()
                ->values()
                ->all();
            if (!empty($adminIds)) {
                $adminRows = DB::table('users')
                    ->whereIn('id', $adminIds)
                    ->get(['id', 'first_name', 'last_name']);
                foreach ($adminRows as $admin) {
                    $fullName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));
                    $adminNamesById[$admin->id] = $fullName ?: 'Agent';
                }
            }
        }

        $result = $result->map(function ($row) use ($names, $handledByMap, $adminNamesById) {
            $phone = $row->phone ?? null;
            $row->name = $names[$phone] ?? null;
            $handledBy = $handledByMap[$phone] ?? 'AI';
            $row->handled_by_key = $handledBy;
            if ($handledBy === 'AI') {
                $row->handled_by_label = 'AI';
            } else {
                $row->handled_by_label = $adminNamesById[$handledBy] ?? 'Agent';
            }
            return $row;
        });

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

    /**
     * @param array<int, string> $phones
     * @return array<string, array{primary_id:int, count:int}>
     */
    private function resolveLeadMetaByNormalizedPhone(array $phones): array
    {
        $normalizedPhones = collect($phones)
            ->map(fn ($phone) => $this->normalizeLeadPhone($phone))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($normalizedPhones)) {
            return [];
        }

        $rows = Lead::whereIn('phone_number', $normalizedPhones)
            ->orderByDesc('id')
            ->get(['id', 'phone_number']);

        $map = [];
        foreach ($rows as $lead) {
            if (!isset($map[$lead->phone_number])) {
                $map[$lead->phone_number] = [
                    'primary_id' => (int) $lead->id,
                    'count' => 0,
                ];
            }
            $map[$lead->phone_number]['count']++;
        }

        return $map;
    }

    /**
     * @param \Illuminate\Support\Collection<int, Lead> $leads
     * @return array<int, array{is_open: bool}>
     */
    private function buildLeadStatusMap(\Illuminate\Support\Collection $leads): array
    {
        if ($leads->isEmpty()) {
            return [];
        }

        $leadIds = $leads->pluck('id')->all();

        $histories = LeadTypeHistory::whereIn('lead_id', $leadIds)
            ->whereIn('type', [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER])
            ->orderByDesc('created_at')
            ->get();

        $latestByComposite = [];
        foreach ($histories as $history) {
            $compositeKey = $history->lead_id . '|' . $history->type;
            if (!isset($latestByComposite[$compositeKey])) {
                $latestByComposite[$compositeKey] = $history;
            }
        }

        $customerStatusIds = [];
        $providerStatusIds = [];
        foreach ($latestByComposite as $key => $history) {
            $data = is_array($history->data) ? $history->data : [];
            if (str_ends_with((string) $key, '|' . Lead::TYPE_CUSTOMER) && !empty($data['customer_lead_status_id'])) {
                $customerStatusIds[] = (int) $data['customer_lead_status_id'];
            }
            if (str_ends_with((string) $key, '|' . Lead::TYPE_PROVIDER) && !empty($data['provider_lead_status_id'])) {
                $providerStatusIds[] = (int) $data['provider_lead_status_id'];
            }
        }

        $customerStatuses = !empty($customerStatusIds)
            ? CustomerLeadStatus::whereIn('id', array_unique($customerStatusIds))->get()->keyBy('id')
            : collect();
        $providerStatuses = !empty($providerStatusIds)
            ? ProviderLeadStatus::whereIn('id', array_unique($providerStatusIds))->get()->keyBy('id')
            : collect();

        $map = [];
        foreach ($leads as $lead) {
            $history = $latestByComposite[$lead->id . '|' . $lead->lead_type] ?? null;
            $map[$lead->id] = [
                'is_open' => $this->isLeadOpenByTypeHistory($lead, $history, $customerStatuses, $providerStatuses),
            ];
        }

        return $map;
    }

    /**
     * Mirror Lead module rules:
     * - Open: unknown OR customer/provider with base_type not in [completed, cancel]
     * - Closed: invalid, future_customer, or base_type completed/cancel.
     *
     * @param \Illuminate\Support\Collection<int, CustomerLeadStatus>|null $customerStatuses
     * @param \Illuminate\Support\Collection<int, ProviderLeadStatus>|null $providerStatuses
     */
    private function isLeadOpenByTypeHistory(
        Lead $lead,
        ?\Modules\LeadManagement\Entities\LeadTypeHistory $typeHistory,
        ?\Illuminate\Support\Collection $customerStatuses = null,
        ?\Illuminate\Support\Collection $providerStatuses = null
    ): bool {
        if ($lead->lead_type === Lead::TYPE_UNKNOWN) {
            return true;
        }

        if (in_array($lead->lead_type, [Lead::TYPE_INVALID, Lead::TYPE_FUTURE_CUSTOMER], true)) {
            return false;
        }

        $data = ($typeHistory && is_array($typeHistory->data)) ? $typeHistory->data : [];

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $statusId = $data['customer_lead_status_id'] ?? null;
            if (!$statusId) {
                return true;
            }
            $status = $customerStatuses?->get((int) $statusId) ?? CustomerLeadStatus::find($statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
            return !in_array($baseType, ['completed', 'cancel'], true);
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $statusId = $data['provider_lead_status_id'] ?? null;
            if (!$statusId) {
                return true;
            }
            $status = $providerStatuses?->get((int) $statusId) ?? ProviderLeadStatus::find($statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));
            return !in_array($baseType, ['completed', 'cancel'], true);
        }

        return false;
    }
}
