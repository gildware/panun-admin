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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\ProviderLead;
use Modules\WhatsAppModule\Entities\WhatsAppConversationTemplate;
use Modules\WhatsAppModule\Entities\WhatsAppAiExecution;
use Modules\WhatsAppModule\Entities\WhatsAppBooking;
use Modules\WhatsAppModule\Entities\WhatsAppChatStatus;
use Modules\WhatsAppModule\Entities\WhatsAppChatTag;
use Modules\WhatsAppModule\Entities\WhatsAppChatThreadMeta;
use Modules\WhatsAppModule\Entities\WhatsAppConversation;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;
use Modules\WhatsAppModule\Support\WhatsAppMessageTime;

class WhatsAppController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected WhatsAppCloudService $whatsAppCloud
    ) {}

    /**
     * @return \Illuminate\Support\Collection<int, WhatsAppConversationTemplate>
     */
    private function conversationQuickTemplatesForChat(): \Illuminate\Support\Collection
    {
        if (!Schema::hasTable('whatsapp_conversation_templates')) {
            return collect();
        }

        return WhatsAppConversationTemplate::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'title', 'body']);
    }

    private function waAgentDisplayNameForTemplates(?\Illuminate\Contracts\Auth\Authenticatable $user): string
    {
        if ($user === null) {
            return '';
        }
        $fn = $user->first_name ?? '';
        $ln = $user->last_name ?? '';
        $name = trim($fn . ' ' . $ln);

        return $name !== '' ? $name : (string) ($user->email ?? '');
    }
    /**
     * Tabbed index: chats, human support, leads, bookings, users, quick replies, chat config (WhatsApp DB).
     */
    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('whatsapp_chat_view');

        $tab = $request->get('tab', 'chats');
        if (!in_array($tab, ['chats', 'human_support', 'leads', 'bookings', 'users', 'quick_replies', 'chat_config'], true)) {
            $tab = 'chats';
        }

        if ($tab === 'chats' || $tab === 'human_support') {
            try {
                $humanSupportTab = $tab === 'human_support';
                $handlerFilter = $request->get('handler', 'all'); // all | ai | <admin-id> — default 'all' so first load shows all chats

                if ($humanSupportTab) {
                    $chats = $this->getHumanSupportChatsList();
                    $chatHandlers = [
                        ['key' => 'all', 'label' => translate('Human support requests')],
                    ];
                    $handlerFilter = 'all';
                } else {
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
                }
            } catch (\Throwable $e) {
                Toastr::error('Could not load chats. ' . $e->getMessage());
                $chats = collect();
                $chatHandlers = [
                    ['key' => 'all', 'label' => translate('All Chats')],
                    ['key' => 'ai', 'label' => translate('Handled by AI')],
                ];
                $handlerFilter = 'all';
                $humanSupportTab = $tab === 'human_support';
            }
            $conversationQuickTemplates = $this->conversationQuickTemplatesForChat();
            $waAgentDisplayNameForTemplates = auth()->check()
                ? $this->waAgentDisplayNameForTemplates(auth()->user())
                : '';
            $waQuickTplPayload = $conversationQuickTemplates
                ->map(static fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'body' => $t->body,
                ])
                ->values()
                ->all();

            return view('whatsappmodule::admin.conversations.index', compact(
                'tab',
                'chats',
                'chatHandlers',
                'handlerFilter',
                'humanSupportTab',
                'conversationQuickTemplates',
                'waAgentDisplayNameForTemplates',
                'waQuickTplPayload'
            ));
        }

        if ($tab === 'leads') {
            try {
                $page = (int) $request->get('page', 1);
                $cacheKey = 'whatsapp_leads_page1';
                $ttl = config('whatsappmodule.cache_ttl', 60);
                if ($page === 1 && $ttl > 0) {
                    $leads = Cache::remember($cacheKey, $ttl, function () {
                        return ProviderLead::select(['lead_id', 'phone', 'name', 'address', 'service', 'status', 'created_at'])
                            ->orderByDesc('created_at')
                            ->simplePaginate(20);
                    });
                    $leads->withPath($request->url())->appends($request->query());
                } else {
                    $leads = ProviderLead::select(['lead_id', 'phone', 'name', 'address', 'service', 'status', 'created_at'])
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
                $cacheKey = 'whatsapp_bookings_page1_v2';
                $ttl = config('whatsappmodule.cache_ttl', 60);
                if ($page === 1 && $ttl > 0) {
                    $bookings = Cache::remember($cacheKey, $ttl, function () {
                        return WhatsAppBooking::select(['booking_id', 'id', 'phone', 'name', 'service', 'status', 'system_booking_id', 'created_at'])
                            ->orderByDesc('created_at')
                            ->simplePaginate(20);
                    });
                    $bookings->withPath($request->url())->appends($request->query());
                } else {
                    $bookings = WhatsAppBooking::select(['booking_id', 'id', 'phone', 'name', 'service', 'status', 'system_booking_id', 'created_at'])
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
                        $user->system_link = $this->resolveSystemLinkForRawPhone($user->phone ?? null);

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

        if ($tab === 'quick_replies') {
            $this->authorize('whatsapp_message_template_update');

            $conversationTemplates = collect();
            if (Schema::hasTable('whatsapp_conversation_templates')) {
                $conversationTemplates = WhatsAppConversationTemplate::query()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();
            }

            return view('whatsappmodule::admin.conversations.index', compact('tab', 'conversationTemplates'));
        }

        if ($tab === 'chat_config') {
            $this->authorize('whatsapp_message_template_update');

            $chatStatusesForConfig = collect();
            $chatTagsForConfig = collect();
            if (Schema::hasTable('whatsapp_chat_statuses')) {
                $chatStatusesForConfig = WhatsAppChatStatus::query()
                    ->orderBy('bucket')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();
            }
            if (Schema::hasTable('whatsapp_chat_tags')) {
                $chatTagsForConfig = WhatsAppChatTag::query()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();
            }

            return view('whatsappmodule::admin.conversations.index', compact(
                'tab',
                'chatStatusesForConfig',
                'chatTagsForConfig'
            ));
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
                'system_link' => $this->resolveSystemLinkForRawPhone($phone),
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
            $waUserChat = WhatsAppUser::where('phone', $phone)->first();
            $systemLinkChat = $this->resolveSystemLinkForRawPhone($phone);
            $waCustomerNameForTemplates = $this->resolveContactNameForTemplates($waUserChat, $systemLinkChat);
        } catch (\Throwable $e) {
            Toastr::error('Could not load chat.');
            return redirect()->route('admin.whatsapp.conversations.index', ['tab' => 'chats']);
        }

        try {
            $messagesTable = config('whatsappmodule.tables.messages', 'whatsapp_messages');
            DB::table($messagesTable)
                ->where('phone', $phone)
                ->where('direction', 'IN')
                ->whereNull('admin_seen_at')
                ->update([
                    'admin_seen_at' => now(),
                ]);
            Cache::forget('whatsapp_active_chats_list');
        } catch (\Throwable $e) {
            // non-fatal: header unread may lag until next poll
        }

        $conversationQuickTemplates = $this->conversationQuickTemplatesForChat();
        $waAgentDisplayNameForTemplates = auth()->check()
            ? $this->waAgentDisplayNameForTemplates(auth()->user())
            : '';
        $chatMetaPayload = $this->buildChatMetaPayloadForPhone($phone);

        return view('whatsappmodule::admin.conversations.chat', compact(
            'phone',
            'messages',
            'conversationState',
            'bookingLink',
            'conversationQuickTemplates',
            'waAgentDisplayNameForTemplates',
            'waCustomerNameForTemplates',
            'chatMetaPayload'
        ));
    }

    /**
     * Ensure the number is well-formed, then (when Cloud is configured) confirm with Meta that the user is on WhatsApp
     * before redirecting to the conversations UI. Existing inbound threads skip Graph checks.
     */
    public function prepareOpenChat(Request $request): JsonResponse
    {
        $this->authorize('whatsapp_chat_view');

        $data = $request->validate([
            'phone' => 'required|string|max:50',
        ]);

        $rawPhone = trim((string) $data['phone']);
        $normalized = $this->whatsAppCloud->normalizeRecipientPhone($rawPhone);
        if ($normalized === null) {
            return response()->json([
                'ok' => false,
                'message' => translate('whatsapp_invalid_phone_format'),
                'code' => 'invalid_phone_format',
            ], 422);
        }

        $threadPhone = $this->resolveWhatsappThreadPhoneKey($rawPhone, $normalized);
        $phoneKeys = [$rawPhone, $normalized, $threadPhone];

        $token = (string) config('services.whatsapp_cloud.token');
        $phoneId = (string) config('services.whatsapp_cloud.phone_id');
        $cloudOk = $token !== '' && $phoneId !== '';
        $useContacts = (bool) config('services.whatsapp_cloud.open_chat_use_contacts', true);
        $probeEnabled = (bool) config('services.whatsapp_cloud.open_chat_probe_enabled', true);
        $probeAfterContacts = (bool) config('services.whatsapp_cloud.open_chat_probe_fallback_after_contacts', false);
        $allowBypass = (bool) config('services.whatsapp_cloud.allow_open_without_graph_verify', false);

        $hasInbound = $this->whatsappThreadHasInboundMessage($phoneKeys);

        if (!$hasInbound && !$cloudOk && !$allowBypass) {
            return response()->json([
                'ok' => false,
                'message' => translate('whatsapp_open_requires_cloud_api'),
                'code' => 'cloud_not_configured',
            ], 422);
        }

        $verifyNeeded = !$hasInbound && $cloudOk && !$allowBypass;
        $hasVerifyMethod = $useContacts || $probeEnabled;

        if ($verifyNeeded && !$hasVerifyMethod) {
            return response()->json([
                'ok' => false,
                'message' => translate('whatsapp_open_verify_method_required'),
                'code' => 'verify_method_required',
            ], 422);
        }

        if ($verifyNeeded) {
            $probeErr = null;
            $probeCtx = null;
            $accepted = false;

            if ($useContacts) {
                $accepted = $this->whatsAppCloud->checkRecipientRegisteredViaContacts($normalized, $probeErr, $probeCtx);
                if (!$accepted && $probeAfterContacts && $probeEnabled) {
                    $probeErr = null;
                    $probeCtx = null;
                    $accepted = $this->whatsAppCloud->probeRecipientAcceptsWhatsApp($normalized, $probeErr, $probeCtx);
                }
            } elseif ($probeEnabled) {
                $accepted = $this->whatsAppCloud->probeRecipientAcceptsWhatsApp($normalized, $probeErr, $probeCtx);
            }

            if (!$accepted) {
                if ($probeErr === 'not_on_whatsapp') {
                    return response()->json([
                        'ok' => false,
                        'message' => translate('whatsapp_number_not_registered'),
                        'code' => 'not_on_whatsapp',
                    ], 422);
                }

                return response()->json([
                    'ok' => false,
                    'message' => translate('whatsapp_open_chat_verify_failed'),
                    'code' => 'verify_failed',
                ], 422);
            }

            $threadPhone = $this->resolveWhatsappThreadPhoneKey($rawPhone, $normalized);
        }

        if ($request->user()?->can('whatsapp_chat_assign')) {
            $waUser = WhatsAppUser::firstOrNew(['phone' => $threadPhone]);
            $waUser->handled_by = $request->user() ? (string) $request->user()->id : 'AI';
            $waUser->human_support_requested_at = null;
            $waUser->save();

            Cache::forget('whatsapp_active_chats_list');
            Cache::forget('whatsapp_chat_full_v2_' . md5($threadPhone));
        }

        return response()->json([
            'ok' => true,
            'redirect_url' => route('admin.whatsapp.conversations.index', [
                'tab' => 'chats',
                'phone' => $threadPhone,
            ]),
            'phone' => $threadPhone,
        ]);
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
            'reply_to_wa_message_id' => 'nullable|string|max:255',
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
        $messagesTable = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        $replyToWa = trim((string) $request->input('reply_to_wa_message_id', ''));
        if ($replyToWa !== '' && !DB::table($messagesTable)->where('phone', $threadPhone)->where('wa_message_id', $replyToWa)->exists()) {
            $replyToWa = '';
        }
        $replyGraphId = $replyToWa !== '' ? $replyToWa : null;

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
                $message->reply_to_wa_message_id = $replyGraphId;
                if ($user) {
                    $message->sent_by_id = $user->id;
                }
                $message->save();

                $waId = $this->whatsAppCloud->sendOutbound($graphTo, $body, null, $whatsAppError, $whatsappGraph, $replyGraphId);
                $sentToWhatsApp = $waId !== null;
                if ($waId !== null) {
                    $message->wa_message_id = $waId;
                    $message->status = 'sent';
                    $message->status_detail = null;
                    $message->status_updated_at = now();
                    $message->save();
                } else {
                    $this->markWhatsAppMessageSendFailed($message, $whatsAppError, $whatsappGraph);
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
                    $message->reply_to_wa_message_id = ($replyGraphId !== null && $index === 0) ? $replyGraphId : null;
                    if ($user) {
                        $message->sent_by_id = $user->id;
                    }
                    $message->save();

                    $caption = $body !== '' && $index === 0 ? $body : '';
                    $waCtx = ($replyGraphId !== null && $index === 0) ? $replyGraphId : null;
                    $waId = $this->whatsAppCloud->sendOutbound($graphTo, $caption, $path, $whatsAppError, $whatsappGraph, $waCtx);
                    $sentToWhatsApp = $sentToWhatsApp || $waId !== null;
                    if ($waId !== null) {
                        $message->wa_message_id = $waId;
                        $message->status = 'sent';
                        $message->status_detail = null;
                        $message->status_updated_at = now();
                        $message->save();
                    } else {
                        $this->markWhatsAppMessageSendFailed($message, $whatsAppError, $whatsappGraph);
                    }
                }
            }

            Cache::forget('whatsapp_active_chats_list');
            foreach (array_unique(array_filter([$threadPhone, $rawPhone, $graphTo])) as $p) {
                Cache::forget('whatsapp_chat_full_v2_' . md5($p));
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
     * React to a specific message in the thread (WhatsApp Cloud API). Updates stored `reactions` on success.
     */
    public function sendMessageReaction(Request $request): JsonResponse
    {
        $this->authorize('whatsapp_chat_reply');

        $data = $request->validate([
            'phone' => 'required|string|max:50',
            'target_wa_message_id' => 'required|string|max:255',
            'emoji' => 'nullable|string|max:32',
        ]);

        $rawPhone = trim((string) $data['phone']);
        $graphTo = $this->whatsAppCloud->normalizeRecipientPhone($rawPhone);
        if ($graphTo === null) {
            return response()->json([
                'ok' => false,
                'error' => 'invalid_phone',
            ], 422);
        }

        $threadPhone = $this->resolveWhatsappThreadPhoneKey($rawPhone, $graphTo);

        $target = WhatsAppMessage::query()
            ->where('phone', $threadPhone)
            ->where('wa_message_id', $data['target_wa_message_id'])
            ->first();

        if (!$target) {
            return response()->json(['ok' => false, 'error' => 'message_not_found'], 404);
        }

        if (strtoupper((string) $target->direction) !== 'IN') {
            return response()->json(['ok' => false, 'error' => 'reactions_only_inbound'], 422);
        }

        $emoji = trim((string) ($data['emoji'] ?? ''));
        if ($emoji !== '' && mb_strlen($emoji) > 16) {
            return response()->json(['ok' => false, 'error' => 'invalid_emoji'], 422);
        }

        $err = null;
        $graph = null;
        $ok = $this->whatsAppCloud->sendReaction($graphTo, $data['target_wa_message_id'], $emoji, $err, $graph);

        if (!$ok) {
            return response()->json([
                'ok' => false,
                'error' => $err ?? 'send_failed',
                'whatsapp_graph' => $graph,
            ], 502);
        }

        $reactions = is_array($target->reactions) ? $target->reactions : [];
        if ($emoji === '') {
            unset($reactions['agent']);
        } else {
            $reactions['agent'] = $emoji;
        }
        if ($reactions === []) {
            $target->reactions = null;
        } else {
            $target->reactions = $reactions;
        }
        $target->save();

        Cache::forget('whatsapp_active_chats_list');
        foreach (array_unique(array_filter([$threadPhone, $rawPhone, $graphTo])) as $p) {
            Cache::forget('whatsapp_chat_full_v2_' . md5($p));
        }

        return response()->json([
            'ok' => true,
            'reactions' => $target->reactions ?? [],
            'whatsapp_graph' => $graph,
        ]);
    }

    /**
     * Graph did not return a message id — keep row for audit but mark so UI is not mistaken for delivered.
     *
     * @param  array<string, mixed>|null  $graphContext
     */
    private function markWhatsAppMessageSendFailed(WhatsAppMessage $message, ?string $error, ?array $graphContext): void
    {
        $parts = [];
        if ($error !== null && $error !== '') {
            $parts[] = $error;
        }
        if ($graphContext !== null && $graphContext !== []) {
            $encoded = json_encode($graphContext, JSON_UNESCAPED_UNICODE);
            if ($encoded !== false) {
                $parts[] = $encoded;
            }
        }
        $detail = $parts !== [] ? implode(' ', $parts) : 'send_failed';
        $message->status = 'failed';
        $message->status_detail = mb_substr($detail, 0, 6000);
        $message->status_updated_at = now();
        $message->save();
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
     * @param  array<int, string|null>  $phoneKeys
     */
    private function whatsappThreadHasInboundMessage(array $phoneKeys): bool
    {
        $keys = array_values(array_unique(array_filter($phoneKeys, static fn ($p) => $p !== null && $p !== '')));
        if ($keys === []) {
            return false;
        }
        $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        try {
            return DB::table($table)
                ->whereIn('phone', $keys)
                ->whereRaw("UPPER(COALESCE(direction, '')) = 'IN'")
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * AJAX: get messages for a phone. Optional: booking_link and conversation_state for right panel.
     * Caches full response per phone to avoid Neon round trips on repeat opens.
     */
    public function chatMessages(Request $request): JsonResponse
    {
        $this->authorize('whatsapp_chat_view');

        $rawRequestPhone = trim((string) $request->get('phone'));
        if ($rawRequestPhone === '') {
            return response()->json(['data' => []], 400);
        }

        $normalizedForThread = $this->whatsAppCloud->normalizeRecipientPhone($rawRequestPhone);
        $phone = $normalizedForThread !== null
            ? $this->resolveWhatsappThreadPhoneKey($rawRequestPhone, $normalizedForThread)
            : $rawRequestPhone;

        $focusMessageId = (int) $request->get('focus_message_id', 0);
        $hasFocus = $focusMessageId > 0;

        $cacheKey = 'whatsapp_chat_full_v2_' . md5($phone);
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
            if ($created !== null && $created !== '') {
                $created = WhatsAppMessageTime::toDisplayIso($created) ?? $created;
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

            $rx = $row['reactions'] ?? null;
            if (is_string($rx) && $rx !== '') {
                $decoded = json_decode($rx, true);
                $rx = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($rx)) {
                $rx = [];
            }

            return [
                'id' => $row['id'] ?? null,
                'phone' => $row['phone'] ?? '',
                'message_text' => $text,
                'body' => $text,
                'direction' => $row['direction'] ?? 'IN',
                'message_type' => $row['message_type'] ?? 'TEXT',
                'media_url' => $mediaUrl,
                'wa_message_id' => $row['wa_message_id'] ?? null,
                'reply_to_wa_message_id' => $row['reply_to_wa_message_id'] ?? null,
                'reactions' => $rx,
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

            $list = collect($rows)->map($mapRow)->reverse()->values()->all();
            $waToPreview = [];
            foreach ($list as $m) {
                if (!empty($m['wa_message_id'])) {
                    $t = (string) ($m['message_text'] ?? '');
                    $waToPreview[$m['wa_message_id']] = mb_strlen($t) > 160 ? mb_substr($t, 0, 160) . '…' : $t;
                }
            }
            foreach ($list as $k => $m) {
                $rid = $m['reply_to_wa_message_id'] ?? null;
                $list[$k]['reply_preview'] = ($rid && isset($waToPreview[$rid])) ? $waToPreview[$rid] : null;
            }
            $messages = $list;
        } catch (\Throwable $e) {
            \Log::warning('WhatsApp chatMessages failed.', ['phone' => $phone, 'raw' => $rawRequestPhone, 'error' => $e->getMessage()]);
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

        $payload = [
            'data' => $messages,
            'thread_phone' => $phone,
            'requested_phone' => $rawRequestPhone,
        ];

        // Handler info: who currently owns this chat (AI or a specific admin)
        $handler = [
            'type' => 'AI',
            'id' => null,
            'name' => 'AI',
        ];
        $waUser = null;
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
        $systemLink = $this->resolveSystemLinkForRawPhone($phone);
        $payload['system_link'] = $systemLink;
        $payload['display_line'] = $this->formatWhatsappChatDisplayLine(
            $phone,
            $waUser?->name,
            $systemLink
        );
        $payload['customer_name'] = $this->resolveContactNameForTemplates($waUser, $systemLink);

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
            $metaBlock = $this->buildChatMetaPayloadForPhone($phone);
            foreach ($metaBlock as $k => $v) {
                $payload[$k] = $v;
            }
            if ($ttlChat > 0 && !$hasFocus) {
                Cache::put($cacheKey, $payload, $ttlChat);
            }
        }

        return response()->json($payload);
    }

    /**
     * Set admin chat status (open/closed bucket labels) for a thread.
     */
    public function updateThreadChatStatus(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('whatsapp_chat_reply');

        if (!Schema::hasTable('whatsapp_chat_thread_meta')) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => 'not_configured'], 404);
            }
            abort(404);
        }

        $data = $request->validate([
            'phone' => 'required|string|max:50',
            'whatsapp_chat_status_id' => 'nullable|integer|exists:whatsapp_chat_statuses,id',
        ]);

        $phone = trim($data['phone']);
        $meta = WhatsAppChatThreadMeta::firstOrCreateForPhone($phone);
        $meta->whatsapp_chat_status_id = $data['whatsapp_chat_status_id'] ?? null;
        $meta->save();

        $this->forgetWhatsappChatCaches($phone);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'chat_meta' => $this->buildChatMetaPayloadForPhone($phone),
            ]);
        }

        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.conversations.chat', ['phone' => $phone]);
    }

    /**
     * Replace thread tags (multi-select).
     */
    public function updateThreadChatTags(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('whatsapp_chat_reply');

        if (!Schema::hasTable('whatsapp_chat_thread_tags')) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => 'not_configured'], 404);
            }
            abort(404);
        }

        $data = $request->validate([
            'phone' => 'required|string|max:50',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:whatsapp_chat_tags,id',
        ]);

        $phone = trim($data['phone']);
        $meta = WhatsAppChatThreadMeta::firstOrCreateForPhone($phone);
        $ids = array_values(array_unique(array_map('intval', $data['tag_ids'] ?? [])));
        $meta->tags()->sync($ids);

        $this->forgetWhatsappChatCaches($phone);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'chat_meta' => $this->buildChatMetaPayloadForPhone($phone),
            ]);
        }

        Toastr::success(translate('successfully_updated'));

        return redirect()->route('admin.whatsapp.conversations.chat', ['phone' => $phone]);
    }

    /**
     * Unified search: active chats (name, phone, last-message preview) + message hits across conversations.
     */
    public function conversationsSearch(Request $request): JsonResponse
    {
        $this->authorize('whatsapp_chat_view');

        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([
                'chats' => [],
                'messages' => [],
                'leads' => [],
                'bookings' => [],
                'users' => [],
                'quick_replies' => [],
                'chat_config' => [],
            ]);
        }

        try {
            $byPhone = $this->getActiveChatsList()->keyBy(fn ($r) => (string) ($r->phone ?? ''));
            foreach ($this->getHumanSupportChatsList() as $row) {
                $k = (string) ($row->phone ?? '');
                if ($k !== '' && !$byPhone->has($k)) {
                    $byPhone->put($k, $row);
                }
            }
            $active = $byPhone->values();

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
                $created = WhatsAppMessageTime::toDisplayIso($row->created_at ?? null) ?? $row->created_at;

                return [
                    'phone' => $row->phone ?? '',
                    'name' => $row->name ?? null,
                    'display_line' => $row->display_line ?? null,
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
                $created = WhatsAppMessageTime::toDisplayIso($m->created_at ?? null) ?? $m->created_at;

                return [
                    'id' => $m->id,
                    'phone' => $m->phone ?? '',
                    'name' => $m->name ?? null,
                    'snippet' => mb_strlen($text) > 140 ? mb_substr($text, 0, 140) . '…' : $text,
                    'created_at' => $created,
                ];
            })->values()->all();

            $leadsOut = [];
            $bookingsOut = [];
            $usersOut = [];

            try {
                $leadsOut = ProviderLead::query()
                    ->where(function ($w) use ($like, $digitsNeedle) {
                        $w->where('name', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('service', 'like', $like)
                            ->orWhere('status', 'like', $like)
                            ->orWhere('lead_id', 'like', $like)
                            ->orWhere('address', 'like', $like);
                        if (strlen($digitsNeedle) >= 3) {
                            $w->orWhere('phone', 'like', '%' . $digitsNeedle . '%');
                        }
                    })
                    ->orderByDesc('created_at')
                    ->limit(15)
                    ->get()
                    ->map(function ($row) {
                        $parts = array_filter([
                            (string) ($row->service ?? ''),
                            (string) ($row->status ?? ''),
                        ]);

                        return [
                            'lead_id' => (string) ($row->lead_id ?? ''),
                            'phone' => $row->phone ?? '',
                            'name' => $row->name ?? null,
                            'snippet' => implode(' · ', $parts) ?: '—',
                            'row_anchor' => 'wa-s-l-' . md5((string) ($row->lead_id ?? '')),
                        ];
                    })->values()->all();
            } catch (\Throwable $e) {
                \Log::debug('WhatsApp search leads skipped.', ['error' => $e->getMessage()]);
            }

            try {
                $bookingsOut = WhatsAppBooking::query()
                    ->where(function ($w) use ($like, $digitsNeedle) {
                        $w->where('name', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('service', 'like', $like)
                            ->orWhere('status', 'like', $like)
                            ->orWhere('booking_id', 'like', $like);
                        if (strlen($digitsNeedle) >= 3) {
                            $w->orWhere('phone', 'like', '%' . $digitsNeedle . '%');
                        }
                    })
                    ->orderByDesc('created_at')
                    ->limit(15)
                    ->get()
                    ->map(function ($row) {
                        $bid = $row->booking_id ?? $row->id;
                        $svc = trim((string) ($row->service ?? ''));
                        $st = trim((string) ($row->status ?? ''));
                        $snippet = $svc !== '' && $st !== '' ? $svc . ' · ' . $st : ($svc !== '' ? $svc : $st);

                        return [
                            'id' => (string) ($row->id ?? ''),
                            'booking_id' => $bid !== null ? (string) $bid : '',
                            'phone' => $row->phone ?? '',
                            'name' => $row->name ?? null,
                            'snippet' => $snippet !== '' ? $snippet : '—',
                            'row_anchor' => 'wa-s-b-' . (int) ($row->id ?? 0),
                        ];
                    })->values()->all();
            } catch (\Throwable $e) {
                \Log::debug('WhatsApp search bookings skipped.', ['error' => $e->getMessage()]);
                $bookingsOut = [];
            }

            try {
                $usersOut = WhatsAppUser::query()
                    ->where(function ($w) use ($like, $digitsNeedle) {
                        $w->where('name', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('alternate_phone', 'like', $like)
                            ->orWhere('address', 'like', $like)
                            ->orWhere('type', 'like', $like);
                        if (strlen($digitsNeedle) >= 3) {
                            $w->orWhere('phone', 'like', '%' . $digitsNeedle . '%')
                                ->orWhere('alternate_phone', 'like', '%' . $digitsNeedle . '%');
                        }
                    })
                    ->orderByDesc('created_at')
                    ->limit(15)
                    ->get()
                    ->map(function ($row) {
                        return [
                            'id' => (int) $row->id,
                            'phone' => $row->phone ?? '',
                            'name' => $row->name ?? null,
                            'snippet' => (string) ($row->type ?? ''),
                            'row_anchor' => 'wa-s-u-' . (int) ($row->id ?? 0),
                        ];
                    })->values()->all();
            } catch (\Throwable $e) {
                \Log::debug('WhatsApp search users skipped.', ['error' => $e->getMessage()]);
            }

            $quickOut = [];
            $cfgOut = [];
            if ($request->user()?->can('whatsapp_message_template_update')) {
                try {
                    if (Schema::hasTable('whatsapp_conversation_templates')) {
                        $quickOut = WhatsAppConversationTemplate::query()
                            ->where(function ($w) use ($like) {
                                $w->where('title', 'like', $like)
                                    ->orWhere('body', 'like', $like);
                            })
                            ->orderBy('sort_order')
                            ->orderBy('id')
                            ->limit(15)
                            ->get()
                            ->map(function ($t) {
                                $body = (string) ($t->body ?? '');
                                $snip = mb_strlen($body) > 120 ? mb_substr($body, 0, 120) . '…' : $body;

                                return [
                                    'id' => (int) $t->id,
                                    'title' => (string) ($t->title ?? ''),
                                    'snippet' => $snip,
                                    'row_anchor' => 'wa-s-qr-' . (int) $t->id,
                                ];
                            })->values()->all();
                    }
                } catch (\Throwable $e) {
                    \Log::debug('WhatsApp search templates skipped.', ['error' => $e->getMessage()]);
                }

                try {
                    if (Schema::hasTable('whatsapp_chat_statuses')) {
                        foreach (WhatsAppChatStatus::query()
                            ->where('name', 'like', $like)
                            ->orderBy('bucket')
                            ->orderBy('sort_order')
                            ->limit(10)
                            ->get() as $st) {
                            $cfgOut[] = [
                                'kind' => 'status',
                                'id' => (int) $st->id,
                                'name' => (string) ($st->name ?? ''),
                                'detail' => (string) ($st->bucket ?? ''),
                                'row_anchor' => 'wa-s-cs-' . (int) $st->id,
                            ];
                        }
                    }
                    if (Schema::hasTable('whatsapp_chat_tags')) {
                        foreach (WhatsAppChatTag::query()
                            ->where(function ($w) use ($like) {
                                $w->where('name', 'like', $like)
                                    ->orWhere('color', 'like', $like);
                            })
                            ->orderBy('sort_order')
                            ->limit(10)
                            ->get() as $tg) {
                            $cfgOut[] = [
                                'kind' => 'tag',
                                'id' => (int) $tg->id,
                                'name' => (string) ($tg->name ?? ''),
                                'detail' => (string) ($tg->color ?? ''),
                                'row_anchor' => 'wa-s-ct-' . (int) $tg->id,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::debug('WhatsApp search chat config skipped.', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('WhatsApp conversationsSearch failed.', ['error' => $e->getMessage()]);

            return response()->json([
                'chats' => [],
                'messages' => [],
                'leads' => [],
                'bookings' => [],
                'users' => [],
                'quick_replies' => [],
                'chat_config' => [],
                'error' => 'Search failed',
            ], 500);
        }

        return response()->json([
            'chats' => $chatsOut,
            'messages' => $messagesOut,
            'leads' => $leadsOut,
            'bookings' => $bookingsOut,
            'users' => $usersOut,
            'quick_replies' => $quickOut,
            'chat_config' => $cfgOut,
        ]);
    }

    /**
     * Active chats for forwarding a message (same pool as the left list; optional exclude = current thread).
     */
    public function activeChatsForForward(Request $request): JsonResponse
    {
        $this->authorize('whatsapp_chat_reply');

        $exclude = trim((string) $request->query('exclude_phone', ''));
        try {
            $rows = $this->getActiveChatsList()->map(function ($row) {
                return [
                    'phone' => (string) ($row->phone ?? ''),
                    'display_line' => (string) ($row->display_line ?? ''),
                ];
            })->values();
            if ($exclude !== '') {
                $rows = $rows->filter(fn (array $r) => ($r['phone'] ?? '') !== $exclude)->values();
            }
        } catch (\Throwable $e) {
            \Log::warning('WhatsApp activeChatsForForward failed.', ['error' => $e->getMessage()]);

            return response()->json(['data' => [], 'error' => 'Failed to load chats'], 500);
        }

        return response()->json(['data' => $rows->all()]);
    }

    /**
     * Change chat handler between AI and the current admin user.
     */
    public function handoff(Request $request): JsonResponse
    {
        $this->authorize('whatsapp_chat_assign');

        $data = $request->validate([
            'phone' => 'required|string|max:50',
            'mode' => 'required|string|in:take,ai', // take => current admin, ai => hand back to AI
        ]);

        $rawPhone = trim((string) $data['phone']);
        $graphTo = $this->whatsAppCloud->normalizeRecipientPhone($rawPhone);
        if ($graphTo === null) {
            return response()->json([
                'ok' => false,
                'message' => translate('Invalid_whatsapp_phone'),
            ], 422);
        }

        // Same thread key as whatsapp_messages / sendReply so handled_by applies to the open conversation.
        $threadPhone = $this->resolveWhatsappThreadPhoneKey($rawPhone, $graphTo);

        $waUser = WhatsAppUser::firstOrNew(['phone' => $threadPhone]);

        if ($data['mode'] === 'ai') {
            $waUser->handled_by = 'AI';
        } else {
            $admin = $request->user();
            $waUser->handled_by = $admin ? (string) $admin->id : 'AI';
        }
        $waUser->human_support_requested_at = null;
        $waUser->save();

        Cache::forget('whatsapp_active_chats_list');
        foreach (array_unique(array_filter([$threadPhone, $rawPhone, $graphTo])) as $p) {
            Cache::forget('whatsapp_chat_full_v2_' . md5($p));
        }

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
     * Remove all stored messages for a phone, bot conversation row, AI execution logs, and reset handler
     * fields on whatsapp_users (fresh thread: AI handling, no human-support flag). Does not delete the user
     * profile row, bookings, or provider leads.
     */
    public function deleteChatHistory(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('whatsapp_chat_delete');

        $request->validate([
            'phone' => 'required|string|max:50',
        ]);

        $rawPhone = trim((string) $request->input('phone'));
        $normalized = $this->whatsAppCloud->normalizeRecipientPhone($rawPhone) ?? '';
        $threadKey = $this->resolveWhatsappThreadPhoneKey($rawPhone, $normalized);
        $phoneKeys = array_values(array_unique(array_filter([$rawPhone, $normalized, $threadKey])));

        $messagesTable = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        $conversationTable = config('whatsappmodule.tables.conversation', 'whatsapp_conversations');
        $usersTable = config('whatsappmodule.tables.users', 'whatsapp_users');

        try {
            DB::transaction(function () use ($phoneKeys, $messagesTable, $conversationTable, $usersTable) {
                $messages = DB::table($messagesTable)->whereIn('phone', $phoneKeys)->get(['id', 'media_path']);
                $ids = $messages->pluck('id')->all();

                foreach ($messages as $row) {
                    $path = trim((string) ($row->media_path ?? ''));
                    if ($path !== '') {
                        try {
                            Storage::disk('public')->delete($path);
                        } catch (\Throwable $e) {
                            // ignore missing or unreadable files
                        }
                    }
                }

                if (Schema::hasTable('whatsapp_ai_executions')) {
                    WhatsAppAiExecution::query()
                        ->where(function ($q) use ($phoneKeys, $ids) {
                            $q->whereIn('phone', $phoneKeys);
                            if ($ids !== []) {
                                $q->orWhereIn('trigger_whatsapp_message_id', $ids)
                                    ->orWhereIn('outbound_whatsapp_message_id', $ids);
                            }
                        })
                        ->delete();
                }

                DB::table($messagesTable)->whereIn('phone', $phoneKeys)->delete();
                DB::table($conversationTable)->whereIn('phone', $phoneKeys)->delete();

                // Reset who handles the chat and human-support queue so admin UI and webhooks match a new thread.
                DB::table($usersTable)
                    ->whereIn('phone', $phoneKeys)
                    ->update([
                        'handled_by' => 'AI',
                        'human_support_requested_at' => null,
                        'updated_at' => now(),
                    ]);
            });
        } catch (\Throwable $e) {
            \Log::warning('WhatsApp deleteChatHistory failed.', ['phone' => $rawPhone, 'error' => $e->getMessage()]);
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Failed to delete chat history'], 500);
            }
            Toastr::error(translate('whatsapp_chat_history_delete_failed'));

            return redirect()->back();
        }

        Cache::forget('whatsapp_active_chats_list');
        foreach ($phoneKeys as $p) {
            Cache::forget('whatsapp_chat_full_v2_' . md5($p));
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        Toastr::success(translate('whatsapp_chat_history_deleted'));

        return redirect()->route('admin.whatsapp.conversations.index', ['tab' => 'chats']);
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
        $humanSupportAt = [];
        if (!empty($phones)) {
            $waUsers = WhatsAppUser::whereIn('phone', $phones)->get(['phone', 'name', 'handled_by', 'human_support_requested_at']);
            foreach ($waUsers as $u) {
                $names[$u->phone] = $u->name;
                $handledByMap[$u->phone] = $u->handled_by ?: 'AI';
                if ($u->human_support_requested_at) {
                    $humanSupportAt[$u->phone] = $u->human_support_requested_at;
                }
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

        $result = $result->map(function ($row) use ($names, $handledByMap, $adminNamesById, $humanSupportAt) {
            $phone = $row->phone ?? null;
            $row->name = $names[$phone] ?? null;
            $handledBy = $handledByMap[$phone] ?? 'AI';
            $row->handled_by_key = $handledBy;
            if ($handledBy === 'AI') {
                $row->handled_by_label = 'AI';
            } else {
                $row->handled_by_label = $adminNamesById[$handledBy] ?? 'Agent';
            }
            $row->human_support_requested_at = $humanSupportAt[$phone] ?? null;
            $systemLink = $this->resolveSystemLinkForRawPhone($phone);
            $row->system_link = $systemLink;
            $waName = isset($row->name) ? trim((string) $row->name) : '';
            $row->display_line = $this->formatWhatsappChatDisplayLine((string) $phone, $waName, $systemLink);

            return $row;
        });

        $result = $this->attachChatMetaToPhoneRows($result);

        if ($ttl > 0) {
            Cache::put($cacheKey, $result, $ttl);
        }

        return $result;
    }

    /**
     * Chats where the customer asked for a human and the thread is still with AI (not yet taken by staff).
     */
    private function getHumanSupportChatsList(): \Illuminate\Support\Collection
    {
        $pendingUsers = WhatsAppUser::query()
            ->whereNotNull('human_support_requested_at')
            ->where(function ($q) {
                $q->whereNull('handled_by')->orWhere('handled_by', 'AI');
            })
            ->orderByDesc('human_support_requested_at')
            ->get();

        if ($pendingUsers->isEmpty()) {
            return collect();
        }

        $activeByPhone = $this->getActiveChatsList()->keyBy('phone');

        $adminIds = $pendingUsers->pluck('handled_by')
            ->filter(fn ($v) => $v !== null && $v !== '' && $v !== 'AI')
            ->unique()
            ->values()
            ->all();
        $adminNamesById = [];
        if ($adminIds !== []) {
            $adminRows = DB::table('users')
                ->whereIn('id', $adminIds)
                ->get(['id', 'first_name', 'last_name']);
            foreach ($adminRows as $admin) {
                $fullName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));
                $adminNamesById[$admin->id] = $fullName ?: 'Agent';
            }
        }

        return $this->attachChatMetaToPhoneRows(
            $pendingUsers->map(function ($u) use ($activeByPhone, $adminNamesById) {
                $phone = $u->phone;
                $base = $activeByPhone->get($phone);
                if ($base) {
                    $row = json_decode(json_encode($base), false);
                } else {
                    $requested = $u->human_support_requested_at;
                    $row = (object) [
                        'phone' => $phone,
                        'direction' => 'IN',
                        'status' => null,
                        'message_text' => '',
                        'unread_count' => 0,
                        'created_at' => $requested ? $requested->format('Y-m-d H:i:s') : null,
                    ];
                }
                $row->name = $u->name ?: ($row->name ?? null);
                $handledBy = $u->handled_by ?: 'AI';
                $row->handled_by_key = $handledBy;
                $row->handled_by_label = $handledBy === 'AI' ? 'AI' : ($adminNamesById[$handledBy] ?? 'Agent');
                $row->human_support_requested_at = $u->human_support_requested_at;
                if ($u->human_support_requested_at) {
                    $row->created_at = $u->human_support_requested_at->format('Y-m-d H:i:s');
                }
                if (empty($row->system_link)) {
                    $row->system_link = $this->resolveSystemLinkForRawPhone($phone);
                }
                // Deep array: clone via json_decode(..., false) leaves nested stdClass; views use [] on customer/provider.
                $sl = json_decode(json_encode($row->system_link ?? []), true);
                $row->system_link = is_array($sl) ? $sl : [];
                $waName = trim((string) ($row->name ?? ''));
                $row->display_line = $this->formatWhatsappChatDisplayLine((string) $phone, $waName, $row->system_link);

                return $row;
            })->values()
        );
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

    /**
     * Normalize a phone for matching app customers/providers and CRM leads.
     * Strips a leading international 00, then if exactly 12 digits starting with 91 (India) removes 91,
     * then uses the last 10 digits (typical national mobile).
     */
    private function normalizePhoneForSystemMatch(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) < 10) {
            return null;
        }

        return substr($digits, -10);
    }

    private function normalizeLeadPhone(?string $phone): ?string
    {
        return $this->normalizePhoneForSystemMatch($phone);
    }

    /**
     * Normalized 10-digit key → first matching customer (id, display name).
     *
     * @return array<string, array{id: string, name: string}>
     */
    private function getSystemMatchCustomerPhoneMap(): array
    {
        $ttl = (int) config('whatsappmodule.system_phone_match_cache_ttl', 300);
        if ($ttl > 0) {
            return Cache::remember(
                'whatsapp_admin_phone_match_customers_v2',
                $ttl,
                fn () => $this->buildSystemMatchCustomerPhoneMap()
            );
        }

        return $this->buildSystemMatchCustomerPhoneMap();
    }

    /**
     * @return array<string, array{id: string, name: string}>
     */
    private function buildSystemMatchCustomerPhoneMap(): array
    {
        $map = [];
        $users = User::query()
            ->inCustomerDirectory()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('id')
            ->get(['id', 'first_name', 'last_name', 'phone']);
        foreach ($users as $u) {
            $n = $this->normalizePhoneForSystemMatch($u->phone);
            if ($n === null || isset($map[$n])) {
                continue;
            }
            $name = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
            $map[$n] = [
                'id' => (string) $u->id,
                'name' => $name !== '' ? $name : translate('Customer'),
            ];
        }

        return $map;
    }

    /**
     * Normalized 10-digit key → first matching provider (id, display name).
     *
     * @return array<string, array{id: string, name: string}>
     */
    private function getSystemMatchProviderPhoneMap(): array
    {
        $ttl = (int) config('whatsappmodule.system_phone_match_cache_ttl', 300);
        if ($ttl > 0) {
            return Cache::remember(
                'whatsapp_admin_phone_match_providers_v2',
                $ttl,
                fn () => $this->buildSystemMatchProviderPhoneMap()
            );
        }

        return $this->buildSystemMatchProviderPhoneMap();
    }

    /**
     * @return array<string, array{id: string, name: string}>
     */
    private function buildSystemMatchProviderPhoneMap(): array
    {
        $map = [];
        $rows = Provider::query()
            ->orderBy('id')
            ->get(['id', 'company_name', 'contact_person_name', 'company_phone', 'contact_person_phone']);
        foreach ($rows as $r) {
            $displayName = (string) ($r->company_name ?: $r->contact_person_name ?: '');
            if ($displayName === '') {
                $displayName = translate('Provider');
            }
            foreach ([$r->company_phone ?? '', $r->contact_person_phone ?? ''] as $p) {
                $n = $this->normalizePhoneForSystemMatch($p);
                if ($n === null || isset($map[$n])) {
                    continue;
                }
                $map[$n] = [
                    'id' => (string) $r->id,
                    'name' => $displayName,
                ];
            }
        }

        return $map;
    }

    /**
     * @return array{
     *     kind: string,
     *     customer: ?array{id: string, name: string, url: string},
     *     provider: ?array{id: string, name: string, url: string}
     * }
     */
    private function resolveSystemLinkForRawPhone(?string $rawPhone): array
    {
        $norm = $this->normalizePhoneForSystemMatch($rawPhone);
        if ($norm === null) {
            return [
                'kind' => 'none',
                'customer' => null,
                'provider' => null,
            ];
        }

        $custMap = $this->getSystemMatchCustomerPhoneMap();
        $provMap = $this->getSystemMatchProviderPhoneMap();
        $c = $custMap[$norm] ?? null;
        $p = $provMap[$norm] ?? null;

        $customer = $c ? [
            'id' => $c['id'],
            'name' => $c['name'],
            'url' => route('admin.customer.detail', [$c['id'], 'web_page' => 'overview']),
        ] : null;

        $provider = $p ? [
            'id' => $p['id'],
            'name' => $p['name'],
            'url' => route('admin.provider.details', [$p['id'], 'web_page' => 'overview']),
        ] : null;

        $kind = 'none';
        if ($customer !== null && $provider !== null) {
            $kind = 'both';
        } elseif ($customer !== null) {
            $kind = 'customer';
        } elseif ($provider !== null) {
            $kind = 'provider';
        }

        return [
            'kind' => $kind,
            'customer' => $customer,
            'provider' => $provider,
        ];
    }

    /**
     * Title line for chat list / header: customer or provider name from CRM when linked,
     * otherwise WhatsApp user name, otherwise digits only.
     *
     * @param  array<string, mixed>  $systemLink
     */
    private function formatWhatsappChatDisplayLine(string $rawPhone, ?string $whatsappUserName, array $systemLink): string
    {
        $digits = preg_replace('/\D+/', '', $rawPhone) ?? '';
        $phoneDisplay = $digits === '' ? '—' : (strlen($digits) > 10 ? substr($digits, -10) : $digits);
        $wa = trim((string) ($whatsappUserName ?? ''));
        $kind = $systemLink['kind'] ?? 'none';
        $cust = $systemLink['customer'] ?? null;
        $prov = $systemLink['provider'] ?? null;
        $custName = is_array($cust) ? trim((string) ($cust['name'] ?? '')) : '';
        $provName = is_array($prov) ? trim((string) ($prov['name'] ?? '')) : '';

        if ($kind === 'customer') {
            $label = $custName !== '' ? $custName : ($wa !== '' ? $wa : '');

            return $label !== '' ? $label . ' (' . $phoneDisplay . ')' : $phoneDisplay;
        }
        if ($kind === 'provider') {
            $label = $provName !== '' ? $provName : ($wa !== '' ? $wa : '');

            return $label !== '' ? $label . ' (' . $phoneDisplay . ')' : $phoneDisplay;
        }
        if ($kind === 'both') {
            $parts = array_values(array_filter([$custName !== '' ? $custName : null, $provName !== '' ? $provName : null]));
            if ($parts !== []) {
                return implode(' · ', $parts) . ' (' . $phoneDisplay . ')';
            }
        }
        if ($wa !== '') {
            return $wa . ' (' . $phoneDisplay . ')';
        }

        return $phoneDisplay;
    }

    /**
     * Display name for quick-reply placeholder {customer_name}: CRM customer if linked,
     * else provider (when only provider / fallback when both), else WhatsApp profile name.
     *
     * @param  array<string, mixed>  $systemLink
     */
    private function resolveContactNameForTemplates(?WhatsAppUser $waUser, array $systemLink): string
    {
        $kind = $systemLink['kind'] ?? 'none';
        $customer = $systemLink['customer'] ?? null;
        $provider = $systemLink['provider'] ?? null;
        $custName = is_array($customer) ? trim((string) ($customer['name'] ?? '')) : '';
        $provName = is_array($provider) ? trim((string) ($provider['name'] ?? '')) : '';

        if (($kind === 'customer' || $kind === 'both') && $custName !== '') {
            return $custName;
        }
        if (($kind === 'both' || $kind === 'provider') && $provName !== '') {
            return $provName;
        }
        $wa = trim((string) ($waUser?->name ?? ''));

        return $wa !== '' ? $wa : '';
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

    private function chatConfigurationTablesPresent(): bool
    {
        return Schema::hasTable('whatsapp_chat_statuses')
            && Schema::hasTable('whatsapp_chat_tags')
            && Schema::hasTable('whatsapp_chat_thread_meta')
            && Schema::hasTable('whatsapp_chat_thread_tags');
    }

    private function forgetWhatsappChatCaches(?string $phone = null): void
    {
        Cache::forget('whatsapp_active_chats_list');
        if ($phone !== null && $phone !== '') {
            Cache::forget('whatsapp_chat_full_v2_' . md5($phone));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildChatMetaPayloadForPhone(string $phone): array
    {
        if (!$this->chatConfigurationTablesPresent()) {
            return [
                'chat_status' => null,
                'chat_tags' => [],
                'chat_status_applied_id' => null,
                'chat_statuses_all' => [],
                'chat_tags_all' => [],
            ];
        }

        $defaultOpen = WhatsAppChatStatus::query()
            ->where('bucket', 'open')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        $meta = WhatsAppChatThreadMeta::query()->where('phone', $phone)->with('status')->first();
        $appliedId = $meta?->whatsapp_chat_status_id;
        $statusModel = $meta?->status ?? $defaultOpen;

        $chatStatus = null;
        if ($statusModel) {
            $chatStatus = [
                'id' => (int) $statusModel->id,
                'name' => (string) $statusModel->name,
                'bucket' => (string) $statusModel->bucket,
                'is_implicit' => $appliedId === null && $defaultOpen && (int) $statusModel->id === (int) $defaultOpen->id,
            ];
        }

        $tags = [];
        if ($meta) {
            $tags = $meta->tags()->get(['whatsapp_chat_tags.id', 'whatsapp_chat_tags.name', 'whatsapp_chat_tags.color'])
                ->map(static fn ($t) => [
                    'id' => (int) $t->id,
                    'name' => (string) $t->name,
                    'color' => (string) $t->color,
                ])
                ->values()
                ->all();
        }

        return [
            'chat_status' => $chatStatus,
            'chat_tags' => $tags,
            'chat_status_applied_id' => $appliedId !== null ? (int) $appliedId : null,
            'chat_statuses_all' => WhatsAppChatStatus::query()
                ->orderBy('bucket')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'bucket'])
                ->map(static fn ($s) => [
                    'id' => (int) $s->id,
                    'name' => (string) $s->name,
                    'bucket' => (string) $s->bucket,
                ])
                ->values()
                ->all(),
            'chat_tags_all' => WhatsAppChatTag::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'color'])
                ->map(static fn ($t) => [
                    'id' => (int) $t->id,
                    'name' => (string) $t->name,
                    'color' => (string) $t->color,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int, object> $rows
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function attachChatMetaToPhoneRows(\Illuminate\Support\Collection $rows): \Illuminate\Support\Collection
    {
        if (!$this->chatConfigurationTablesPresent() || $rows->isEmpty()) {
            return $rows;
        }

        $phones = $rows->pluck('phone')->unique()->filter()->values()->all();
        if ($phones === []) {
            return $rows;
        }

        $defaultOpen = WhatsAppChatStatus::query()
            ->where('bucket', 'open')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        $metas = WhatsAppChatThreadMeta::query()
            ->whereIn('phone', $phones)
            ->with('status')
            ->get()
            ->keyBy('phone');

        $pivotTags = DB::table('whatsapp_chat_thread_tags as tt')
            ->join('whatsapp_chat_tags as t', 'tt.whatsapp_chat_tag_id', '=', 't.id')
            ->whereIn('tt.phone', $phones)
            ->orderBy('t.sort_order')
            ->orderBy('t.id')
            ->get(['tt.phone', 't.id', 't.name', 't.color']);

        $tagsByPhone = $pivotTags->groupBy('phone');

        return $rows->map(function ($row) use ($metas, $tagsByPhone, $defaultOpen) {
            $phone = $row->phone ?? null;
            if ($phone === null || $phone === '') {
                $row->chat_status = null;
                $row->chat_tags = [];

                return $row;
            }

            $meta = $metas->get($phone);
            $appliedId = $meta?->whatsapp_chat_status_id;
            $statusModel = $meta?->status ?? $defaultOpen;

            if ($statusModel) {
                $row->chat_status = [
                    'id' => (int) $statusModel->id,
                    'name' => (string) $statusModel->name,
                    'bucket' => (string) $statusModel->bucket,
                    'is_implicit' => $appliedId === null && $defaultOpen && (int) $statusModel->id === (int) $defaultOpen->id,
                ];
            } else {
                $row->chat_status = null;
            }

            $row->chat_tags = collect($tagsByPhone->get($phone, collect()))
                ->map(static fn ($t) => [
                    'id' => (int) $t->id,
                    'name' => (string) $t->name,
                    'color' => (string) $t->color,
                ])
                ->values()
                ->all();

            return $row;
        });
    }
}
