<?php

namespace Modules\ChattingModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\ChattingModule\Entities\ChannelConversation;
use Modules\ChattingModule\Entities\ChannelList;
use Modules\ChattingModule\Entities\ChannelUser;
use Modules\ChattingModule\Entities\ConversationFile;
use Modules\ChattingModule\Entities\ConversationReaction;
use Modules\ChattingModule\Traits\ChattingTrait;
use Modules\AdminModule\Services\StaffPresenceService;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\ChattingModule\Services\StaffGroupChannelService;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;
use Ramsey\Uuid\Nonstandard\Uuid;

class ChattingController extends Controller
{
    use ChattingTrait;

    protected ChannelList $channelList;
    protected ChannelUser $channelUser;
    protected ChannelConversation $channelConversation;
    protected ConversationFile $conversationFile;
    protected ConversationReaction $conversationReaction;
    protected User $user;
    protected StaffPresenceService $staffPresenceService;
    protected StaffGroupChannelService $staffGroupChannelService;

    public function __construct(User $user, ChannelList $channelList, ChannelUser $channelUser, ChannelConversation $channelConversation, ConversationFile $conversationFile, ConversationReaction $conversationReaction, StaffPresenceService $staffPresenceService, StaffGroupChannelService $staffGroupChannelService)
    {
        $this->channelList = $channelList;
        $this->channelUser = $channelUser;
        $this->channelConversation = $channelConversation;
        $this->conversationFile = $conversationFile;
        $this->conversationReaction = $conversationReaction;
        $this->user = $user;
        $this->staffPresenceService = $staffPresenceService;
        $this->staffGroupChannelService = $staffGroupChannelService;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Factory|View|Application|RedirectResponse
     */
    public function index(Request $request): RedirectResponse
    {
        if ($request->query('user_type') === 'staff') {
            return redirect()->route('admin.chat.staff', array_filter([
                'channel_id' => $request->query('channel_id'),
                'open_staff' => $request->query('open_staff'),
            ]));
        }

        return redirect()->route('admin.chat.support', array_filter([
            'filter' => $request->query('filter', 'all'),
            'channel_id' => $request->query('channel_id'),
        ]));
    }

    public function staffIndex(Request $request): Factory|View|Application
    {
        return $this->renderChatPage($request, true);
    }

    public function supportIndex(Request $request): Factory|View|Application
    {
        return $this->renderChatPage($request, false);
    }

    private function renderChatPage(Request $request, bool $isStaffMode): Factory|View|Application
    {
        $request->validate([
            'filter' => 'nullable|in:all,unread',
        ]);

        $filter = $request->query('filter', 'all');
        if (! in_array($filter, ['all', 'unread'], true)) {
            $filter = 'all';
        }

        if ($isStaffMode) {
            $chatListQuery = $this->channelList
                ->with($this->channelListEagerLoads())
                ->whereHas('channelUsers', function ($query) use ($request) {
                    $query->where(['user_id' => $request->user()->id]);
                })
                ->whereHas('channelUsers', function ($channelQuery) use ($request) {
                    $channelQuery->where('user_id', '!=', $request->user()->id)
                        ->whereHas('user', fn ($userQuery) => $userQuery->whereIn('user_type', ADMIN_USER_TYPES));
                });
        } else {
            $chatListQuery = $this->supportInboxListQuery($request, $filter);
        }

        $chatList = $chatListQuery->orderBy('updated_at', 'DESC')->get();

        if (! $isStaffMode) {
            foreach ($chatList as $chat) {
                ensure_support_channel_user($chat->id, (string) $request->user()->id);
            }
            $chatList->load($this->channelListEagerLoads());
        }

        $chatList->map(function ($chat) use ($request) {
            $chat['is_read'] = $chat->channelUsers->where('user_id', $request->user()->id)->first()?->is_read ?? 1;
        });

        $type = $isStaffMode ? 'staff' : 'support';
        $staffGroupChannel = null;
        $staffGroupMemberCount = 0;

        if ($type === 'staff' && in_array($request->user()->user_type, ADMIN_USER_TYPES, true)) {
            $staffGroupChannel = $this->staffGroupChannelService->ensureGroupForUser($request->user());

            if ($staffGroupChannel) {
                $staffGroupChannel->load([
                    'channelLastConversation.user',
                    'channelLastConversation.conversationLastFiles',
                ]);
                $staffGroupMemberCount = $this->staffGroupChannelService->memberCount($staffGroupChannel);
                $staffGroupChannel['is_read'] = $staffGroupChannel->channelUsers
                    ->where('user_id', $request->user()->id)
                    ->first()
                    ->is_read ?? 1;
            }

            $chatList = $chatList
                ->filter(fn ($chat) => ! $this->staffGroupChannelService->isStaffGroupChannel($chat))
                ->values();
        }

        // Staff tab needs the directory; support start-conversation loads customers/providers
        // via admin.chat.entity-search (AJAX select2) so we do not hydrate full directories here.
        $customers = collect();
        $providers = collect();
        $servicemen = collect();
        $staffMembers = collect();
        $staffPresenceById = collect();

        if ($type === 'staff') {
            $staffMembers = $this->staffPresenceService->listStaffPresence($request->user()->id);
            $staffPresenceById = $staffMembers->keyBy('id');
        }

        $openChannelId = $request->query('channel_id');

        return view('chattingmodule::admin.index', compact('chatList', 'customers', 'providers', 'servicemen', 'staffMembers', 'staffPresenceById', 'type', 'filter', 'openChannelId', 'staffGroupChannel', 'staffGroupMemberCount'));
    }

    public function openStaffConversation(Request $request, string $staffId): RedirectResponse
    {
        $staffUser = $this->user->where('id', $staffId)->whereIn('user_type', ADMIN_USER_TYPES)->first();

        if (! $staffUser) {
            Toastr::error(translate('Receiver not found'));
            return back();
        }

        if ((string) $staffId === (string) $request->user()->id) {
            Toastr::error(translate('You cannot start a conversation with yourself'));
            return back();
        }

        $result = $this->findOrCreateChannelBetween($request->user()->id, $staffId);

        return redirect()->route('admin.chat.staff', [
            'channel_id' => $result['channel']->id,
        ]);
    }

    public function openStaffConversationAjax(Request $request, string $staffId): JsonResponse
    {
        $staffUser = $this->user->where('id', $staffId)->whereIn('user_type', ADMIN_USER_TYPES)->first();

        if (! $staffUser) {
            return response()->json(response_formatter(DEFAULT_404, null, [['message' => translate('Receiver not found')]]), 404);
        }

        if ((string) $staffId === (string) $request->user()->id) {
            return response()->json(response_formatter(DEFAULT_400, null, [['message' => translate('You cannot start a conversation with yourself')]]), 400);
        }

        $result = $this->findOrCreateChannelBetween($request->user()->id, $staffId);
        $channel = $result['channel'];

        $this->channelUser->where('channel_id', $channel->id)->where('user_id', $request->user()->id)
            ->update(['is_read' => 1, 'read_at' => now()]);

        $conversation = $this->channelConversation->where(['channel_id' => $channel->id])
            ->with($this->conversationEagerLoads())->whereHas('channel.channelUsers', function ($query) use ($request) {
                $query->where(['user_id' => $request->user()->id]);
            })->latest()->paginate(100, ['*'], 'offset', 1);

        $fromUser = $this->channelUser->where('channel_id', $channel->id)
            ->where('user_id', '!=', $request->user()->id)
            ->with($this->channelMemberEagerLoads())
            ->first();

        $channelId = $channel->id;
        $supportChannelType = $channel->reference_type;
        $presenceContext = $this->staffPresenceContextForFromUser($fromUser);
        $messagingContext = $this->staffMessagingViewContext($channel, $fromUser);
        $pinnedMessages = $this->pinnedMessagesFor($channelId);

        $response = [
            'channel_id' => $channelId,
            'ajax_route' => route('admin.chat.ajax-conversation', ['channel_id' => $channelId, 'offset' => 1]),
            'template' => view('chattingmodule::admin.partials._conversations', array_merge(
                compact('fromUser', 'conversation', 'channelId', 'pinnedMessages', 'supportChannelType'),
                $presenceContext,
                $messagingContext,
                ['recipientChannelUsers' => $this->recipientChannelUsersFor($channelId, (string) $request->user()->id)],
            ))->render(),
        ];

        $staffPresence = $presenceContext['staffPresence'] ?? null;

        if ($result['created']) {
            $chat = $this->channelList->with($this->channelListEagerLoads())->find($channel->id);
            $presenceService = $this->staffPresenceService;
            $response['list_item'] = view('chattingmodule::admin.partials._staff-conversation-list-item', compact('chat', 'fromUser', 'staffPresence', 'presenceService'))->render();
        }

        return response()->json($response);
    }

    /**
     * @return array{channel: ChannelList, created: bool}
     */
    private function findOrCreateChannelBetween(string $currentUserId, string $otherUserId): array
    {
        $otherUser = $this->user->find($otherUserId);
        $supportReferenceType = $this->supportReferenceTypeForUserType($otherUser?->user_type);
        if ($supportReferenceType !== null) {
            return $this->findOrCreateAdminSupportChannel($currentUserId, $otherUserId, $supportReferenceType);
        }

        $channelIds = $this->channelUser->where('user_id', $currentUserId)->pluck('channel_id')->toArray();

        $existing = $this->channelList->whereIn('id', $channelIds)
            ->where(function ($query) {
                $query->whereNull('reference_type')
                    ->orWhere('reference_type', '!=', StaffGroupChannelService::REFERENCE_TYPE);
            })
            ->whereHas('channelUsers', function ($query) use ($otherUserId) {
                $query->where('user_id', $otherUserId);
            })
            ->has('channelUsers', '=', 2)
            ->latest()
            ->first();

        if ($existing) {
            return ['channel' => $existing, 'created' => false];
        }

        $channel = $this->channelList;
        $channel->reference_id = null;
        $channel->reference_type = null;
        $channel->save();

        $this->channelUser->insert([
            [
                'id' => Uuid::uuid4(),
                'channel_id' => $channel->id,
                'user_id' => $currentUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4(),
                'channel_id' => $channel->id,
                'user_id' => $otherUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return ['channel' => $channel, 'created' => true];
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function channelList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $chatList = $this->channelList->withCount(['channelUsers'])
            ->with(['channelUsers.user'])
            ->whereHas('channelUsers', function ($query) use ($request) {
                $query->where(['user_id' => $request->user()->id]);
            })->orderBy('updated_at', 'DESC')
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $chatList), 200);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function referencedChannelList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
            'reference_id' => 'required',
            'reference_type' => 'required|in:booking_id',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $chatList = $this->channelList->withCount(['channelUsers'])->with(['channelUsers.user'])
            ->where(['reference_id' => $request['reference_id'], 'reference_type' => $request['reference_type']])
            ->whereHas('channelUsers', function ($query) use ($request) {
                $query->where(['user_id' => $request->user()->id]);
            })->orderBy('updated_at', 'DESC')
            ->paginate($request['limit'], ['*'], 'offset', $request['offset'])->withPath('');

        return response()->json(response_formatter(DEFAULT_200, $chatList), 200);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     */
    public function createChannel(Request $request): RedirectResponse
    {
        $request->validate([
            'reference_id' => '',
            'reference_type' => 'in:booking_id',
            'user_type' => 'in:customer,provider-admin,staff',
        ]);

        if ($request['user_type'] == 'customer') {
            $request['to_user'] = $request['customer_id'];
        } elseif ($request['user_type'] == 'provider-admin') {
            $request['to_user'] = $request['provider_id'];
        } elseif ($request['user_type'] == 'staff') {
            $request['to_user'] = $request['staff_id'];
        }

        if (!$this->user->where('id', $request['to_user'])->exists()) {
            Toastr::error(translate('Receiver not found'));
            return back();
        }

        if ((string) $request['to_user'] === (string) $request->user()->id) {
            Toastr::error(translate('You cannot start a conversation with yourself'));
            return back();
        }

        $result = $this->findOrCreateChannelBetween($request->user()->id, $request['to_user']);
        $channel = $result['channel'];

        if ($request['reference_id'] ?? null) {
            $channel->update([
                'reference_id' => $request['reference_id'],
                'reference_type' => $request['reference_type'] ?? null,
            ]);
        }

        Toastr::success(translate('you_can_start_conversation_now'));

        $userType = $request['user_type'];

        if ($userType === 'staff') {
            return redirect()->route('admin.chat.staff', [
                'channel_id' => $channel->id,
            ]);
        }

        if (in_array($userType, ['customer', 'provider-admin'], true)) {
            return redirect()->route('admin.chat.support', [
                'filter' => 'all',
                'channel_id' => $channel->id,
            ]);
        }

        return back();
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => is_null($request['files']) ? 'required' : '',
            'channel_id' => 'required|uuid',
            'reply_to_conversation_id' => 'nullable|uuid',
            'files' => is_null($request['message']) ? 'required|array' : 'array',
            'files.*' => 'max:'. uploadMaxFileSizeInKB('file') .'|mimes:' . implode(',', array_column(FILE_TYPE, 'key'))
        ], [
            'files.required' => 'The files field is required when message is not provided.',
            'files.array' => 'The files must be an array.',
            'files.*.max' => 'The maximum file size allowed is :max kilobytes.',
            'files.*.mimes' => 'Invalid file type. Allowed types are: ' . implode(', ', array_column(FILE_TYPE, 'key'))
        ]);


        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $channelId = $this->resolveAdminSupportChannelForSend(
            (string) $request['channel_id'],
            (string) $request->user()->id
        );
        $this->healSupportChannelMembership($channelId, (string) $request->user()->id);

        $replyToId = $this->resolveReplyToConversationId(
            $channelId,
            $request->input('reply_to_conversation_id')
        );

        if ($request->filled('reply_to_conversation_id') && ! $replyToId) {
            return response()->json(response_formatter(DEFAULT_400, null, [[
                'message' => translate('Invalid_reply_message'),
            ]]), 400);
        }

        DB::transaction(function () use ($request, $replyToId, $channelId) {
            $this->channelList->where('id', $channelId)->update([
                'updated_at' => now()
            ]);
            $this->channelUser->where('channel_id', $channelId)->where('user_id', '!=', $request->user()->id)
                ->update([
                    'is_read' => 0
                ]);

            $channelConversation = new ChannelConversation();
            $channelConversation->channel_id = $channelId;
            $channelConversation->message = $request['message'];
            $channelConversation->user_id = $request->user()->id;
            $channelConversation->reply_to_conversation_id = $replyToId;
            $channelConversation->save();

            if ($request->has('files')) {
                foreach ($request->file('files') as $file) {
                    $extension = $file->getClientOriginalExtension();
                    $originalName = $file->getClientOriginalName();

                    $this->conversationFile->create([
                        'conversation_id' => $channelConversation->id,
                        'original_file_name' => $originalName,
                        'stored_file_name' => file_uploader('conversation/', $extension, $file),
                        'file_type' => $extension,
                    ]);
                }
            }
        });

        $conversation = $this->channelConversation->where(['channel_id' => $channelId])
            ->with($this->conversationEagerLoads())->whereHas('channel.channelUsers', function ($query) use ($request) {
                $query->where(['user_id' => $request->user()->id]);
            })->latest()->paginate(100, ['*'], 'offset', $request['offset']);

        $channel = $this->channelList->with($this->channelListEagerLoads())
            ->find($channelId);
        $fromUser = $this->peerChannelUserFor(
            $channel,
            $this->channelUser->where('channel_id', $channelId)
                ->where('user_id', '!=', $request->user()->id)
                ->with($this->channelMemberEagerLoads())
                ->get(),
            (string) $request->user()->id
        );
        $messagingContext = $this->staffMessagingViewContext($channel, $fromUser);
        if ($channel) {
            $channel['is_read'] = 1;
        }

        $recipientChannelUsers = $this->recipientChannelUsersFor($channelId, (string) $request->user()->id);

        return response()->json([
            'template' => view('chattingmodule::admin.partials._conversation-messages-only', array_merge(
                compact('conversation'),
                $messagingContext,
                ['recipientChannelUsers' => $recipientChannelUsers],
            ))->render(),
            'sidebar' => $channel ? $this->sidebarChannelPayload($channel, (string) $request->user()->id, true) : null,
            'channel_id' => $channelId !== (string) $request['channel_id'] ? $channelId : null,
            'active_conversation' => [
                'changed' => true,
                'last_message_at' => $conversation->first()?->created_at?->toIso8601String(),
                'read_fingerprint' => $this->recipientReadFingerprint($recipientChannelUsers),
            ],
        ]);
    }

    public function liveSync(Request $request): JsonResponse
    {
        $request->validate([
            'mode' => 'nullable|in:support,staff',
            'filter' => 'nullable|in:all,unread',
            'active_channel_id' => 'nullable|uuid',
            'last_message_at' => 'nullable|string',
            'read_fingerprint' => 'nullable|string',
        ]);

        $activeChannelId = $request->query('active_channel_id');
        $chatList = $this->buildLiveSyncChatList($request);

        $channels = $chatList->map(function ($chat) use ($request, $activeChannelId) {
            return $this->sidebarChannelPayload(
                $chat,
                (string) $request->user()->id,
                $activeChannelId && (string) $activeChannelId === (string) $chat->id
            );
        })->values();

        $response = [
            'channels' => $channels,
            'order' => $chatList->pluck('id')->values(),
        ];

        if ($activeChannelId) {
            $response['active_conversation'] = $this->activeConversationUpdate(
                $request,
                (string) $activeChannelId,
                $request->query('last_message_at'),
                $request->query('read_fingerprint')
            );
        }

        return response()->json($response);
    }

    public function entitySearch(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|max:120',
            'type' => 'required|in:staff,customer,provider,provider-admin,booking,service,lead',
        ]);

        $query = trim((string) $request->input('q', ''));
        $type = $request->type;
        $like = $query !== '' ? '%'.$query.'%' : null;

        $results = match ($type) {
            'staff' => $this->staffPresenceService->listStaffPresence($request->user()->id)
                ->when($like, fn ($c) => $c->filter(fn ($m) => stripos($m['name'], $query) !== false))
                ->take(12)
                ->map(fn ($m) => [
                    'type' => 'staff',
                    'id' => $m['id'],
                    'label' => $m['name'],
                    'subtitle' => $m['presence_label'],
                ])
                ->values(),
            'customer' => $this->user->ofStatus(1)->inCustomerDirectory()
                ->when($like, function ($q) use ($like) {
                    $q->where(function ($inner) use ($like) {
                        $inner->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
                })
                ->orderBy('first_name')
                ->limit(12)
                ->get()
                ->map(fn (User $user) => [
                    'type' => 'customer',
                    'id' => $user->id,
                    'label' => trim($user->first_name.' '.$user->last_name),
                    'subtitle' => $user->phone,
                ])
                ->values(),
            // Mentions / tagging use provider company ids; start-conversation needs provider-admin user ids.
            'provider-admin' => $this->user->ofStatus(1)->where('user_type', 'provider-admin')
                ->with(['provider'])
                ->whereHas('provider')
                ->when($like, function ($q) use ($like) {
                    $q->where(function ($inner) use ($like) {
                        $inner->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhereHas('provider', function ($providerQuery) use ($like) {
                                $providerQuery->where('company_name', 'like', $like)
                                    ->orWhere('company_phone', 'like', $like)
                                    ->orWhere('contact_person_name', 'like', $like);
                            });
                    });
                })
                ->orderBy('first_name')
                ->limit(12)
                ->get()
                ->map(fn (User $user) => [
                    'type' => 'provider-admin',
                    'id' => $user->id,
                    'label' => $user->provider?->company_name ?: trim($user->first_name.' '.$user->last_name),
                    'subtitle' => $user->provider?->company_phone ?: $user->phone,
                ])
                ->values(),
            'provider' => Provider::query()
                ->when($like, fn ($q) => $q->where(function ($inner) use ($like) {
                    $inner->where('company_name', 'like', $like)
                        ->orWhere('company_phone', 'like', $like)
                        ->orWhere('contact_person_name', 'like', $like);
                }))
                ->orderBy('company_name')
                ->limit(12)
                ->get()
                ->map(fn (Provider $provider) => [
                    'type' => 'provider',
                    'id' => $provider->id,
                    'label' => $provider->company_name,
                    'subtitle' => $provider->company_phone,
                ])
                ->values(),
            'booking' => Booking::query()
                ->when($like, fn ($q) => $q->where(function ($inner) use ($like, $query) {
                    $inner->where('readable_id', 'like', $like)
                        ->orWhere('id', 'like', $like);
                    if (ctype_digit($query)) {
                        $inner->orWhere('readable_id', 'like', '%'.$query.'%');
                    }
                }))
                ->latest()
                ->limit(12)
                ->get(['id', 'readable_id', 'booking_status'])
                ->map(fn (Booking $booking) => [
                    'type' => 'booking',
                    'id' => $booking->id,
                    'label' => $booking->readable_id ?: translate('booking'),
                    'subtitle' => translate($booking->booking_status),
                ])
                ->values(),
            'lead' => Lead::query()
                ->when($like, fn ($q) => $q->where(function ($inner) use ($like, $query) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('phone_number', 'like', $like);
                    if (ctype_digit($query)) {
                        $inner->orWhere('id', $query);
                    }
                }))
                ->latest()
                ->limit(12)
                ->get(['id', 'name', 'phone_number', 'lead_type'])
                ->map(fn (Lead $lead) => [
                    'type' => 'lead',
                    'id' => (string) $lead->id,
                    'label' => $lead->name ?: ('#'.$lead->id),
                    'subtitle' => trim(($lead->phone_number ?? '').' · '.translate($lead->lead_type ?? ''), ' ·'),
                ])
                ->values(),
            'service' => Service::query()
                ->with('category')
                ->where('is_active', 1)
                ->when($like, fn ($q) => $q->where('name', 'like', $like))
                ->orderBy('name')
                ->limit(12)
                ->get(['id', 'name', 'category_id'])
                ->map(fn (Service $service) => [
                    'type' => 'service',
                    'id' => $service->id,
                    'label' => $service->name,
                    'subtitle' => $service->category?->name,
                ])
                ->values(),
            default => collect(),
        };

        return response()->json(['results' => $results]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function conversation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel_id' => 'required|uuid',
            'offset' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $this->healSupportChannelMembership((string) $request['channel_id'], (string) $request->user()->id);

        $this->channelUser->where('channel_id', $request['channel_id'])->where('user_id', $request->user()->id)
            ->update([
                'is_read' => 1,
                'read_at' => now(),
            ]);

        $conversation = $this->channelConversation->where(['channel_id' => $request['channel_id']])
            ->with($this->conversationEagerLoads())->whereHas('channel.channelUsers', function ($query) use ($request) {
                $query->where(['user_id' => $request->user()->id]);
            })->latest()->paginate(100, ['*'], 'offset', $request['offset']);

        $channelId = $request['channel_id'];
        $channel = $this->channelList->withCount('channelUsers')->find($channelId);
        $fromUser = $this->peerChannelUserFor(
            $channel,
            $this->channelUser->where('channel_id', $request['channel_id'])
                ->where('user_id', '!=', $request->user()->id)
                ->with($this->channelMemberEagerLoads())
                ->get(),
            (string) $request->user()->id
        );
        $supportChannelType = $channel?->reference_type;
        $messagingContext = $this->staffMessagingViewContext($channel, $fromUser);
        $presenceContext = ($messagingContext['isStaffGroup'] ?? false)
            ? []
            : $this->staffPresenceContextForFromUser($fromUser);
        $pinnedMessages = $this->pinnedMessagesFor($channelId);

        return response()->json([
            'template' => view('chattingmodule::admin.partials._conversations', array_merge(
                compact('fromUser', 'conversation', 'channelId', 'pinnedMessages', 'supportChannelType'),
                $presenceContext,
                $messagingContext,
                ['recipientChannelUsers' => $this->recipientChannelUsersFor($channelId, (string) $request->user()->id)],
            ))->render(),
        ]);
    }

    /**
     * Toggle the pinned state of a message within a channel the current user belongs to.
     * @param Request $request
     * @return JsonResponse
     */
    public function togglePin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel_id' => 'required|uuid',
            'conversation_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $isMember = $this->channelUser->where('channel_id', $request['channel_id'])
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json(response_formatter(DEFAULT_400, null, [['message' => translate('Unauthorized')]]), 403);
        }

        $conversation = $this->channelConversation->where('id', $request['conversation_id'])
            ->where('channel_id', $request['channel_id'])
            ->first();

        if (! $conversation) {
            return response()->json(response_formatter(DEFAULT_404, null, [['message' => translate('Message_not_found')]]), 404);
        }

        $nowPinned = ! $conversation->is_pinned;

        $conversation->update([
            'is_pinned' => $nowPinned,
            'pinned_at' => $nowPinned ? now() : null,
            'pinned_by' => $nowPinned ? $request->user()->id : null,
        ]);

        $pinnedMessages = $this->pinnedMessagesFor($request['channel_id']);

        return response()->json([
            'is_pinned' => $nowPinned,
            'conversation_id' => $conversation->id,
            'pinned_bar' => view('chattingmodule::admin.partials._chat-pinned-bar', compact('pinnedMessages'))->render(),
        ]);
    }

    public const REACTION_EMOJIS = ['👍', '👎', '✅', '🎉', '🙏', '👀', '😄'];

    public function toggleReaction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel_id' => 'required|uuid',
            'conversation_id' => 'required|uuid',
            'emoji' => ['required', 'string', Rule::in(self::REACTION_EMOJIS)],
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $isMember = $this->channelUser->where('channel_id', $request['channel_id'])
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json(response_formatter(DEFAULT_400, null, [['message' => translate('Unauthorized')]]), 403);
        }

        $conversation = $this->channelConversation->where('id', $request['conversation_id'])
            ->where('channel_id', $request['channel_id'])
            ->first();

        if (! $conversation) {
            return response()->json(response_formatter(DEFAULT_404, null, [['message' => translate('Message_not_found')]]), 404);
        }

        $existing = $this->conversationReaction
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $request->user()->id)
            ->where('emoji', $request['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
            $reacted = false;
        } else {
            $this->conversationReaction->create([
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'emoji' => $request['emoji'],
            ]);
            $reacted = true;
        }

        $conversation->load('reactions.user');

        return response()->json([
            'reacted' => $reacted,
            'conversation_id' => $conversation->id,
            'reactions_html' => view('chattingmodule::admin.partials._chat-message-reactions', [
                'chat' => $conversation,
            ])->render(),
        ]);
    }

    public function deleteMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel_id' => 'required|uuid',
            'conversation_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $isMember = $this->channelUser->where('channel_id', $request['channel_id'])
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json(response_formatter(DEFAULT_400, null, [['message' => translate('Unauthorized')]]), 403);
        }

        $conversation = $this->channelConversation->where('id', $request['conversation_id'])
            ->where('channel_id', $request['channel_id'])
            ->first();

        if (! $conversation) {
            return response()->json(response_formatter(DEFAULT_404, null, [['message' => translate('Message_not_found')]]), 404);
        }

        if ($conversation->user_id !== $request->user()->id) {
            return response()->json(response_formatter(DEFAULT_400, null, [['message' => translate('You_can_only_delete_your_own_messages')]]), 403);
        }

        $this->conversationReaction->where('conversation_id', $conversation->id)->delete();
        $conversation->delete();

        $pinnedMessages = $this->pinnedMessagesFor($request['channel_id']);

        return response()->json([
            'deleted' => true,
            'conversation_id' => $conversation->id,
            'pinned_bar' => view('chattingmodule::admin.partials._chat-pinned-bar', compact('pinnedMessages'))->render(),
        ]);
    }

    public function clearConversation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(response_formatter(DEFAULT_400, null, error_processor($validator)), 400);
        }

        $isMember = $this->channelUser->where('channel_id', $request['channel_id'])
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json(response_formatter(DEFAULT_400, null, [['message' => translate('Unauthorized')]]), 403);
        }

        $conversationIds = $this->channelConversation->where('channel_id', $request['channel_id'])->pluck('id');

        if ($conversationIds->isNotEmpty()) {
            $this->conversationReaction->whereIn('conversation_id', $conversationIds)->delete();
            $this->channelConversation->where('channel_id', $request['channel_id'])->delete();
        }

        return response()->json([
            'cleared' => true,
            'channel_id' => $request['channel_id'],
        ]);
    }

    /**
     * Pinned messages for a channel, newest pin first.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function pinnedMessagesFor(string $channelId)
    {
        return $this->channelConversation->where('channel_id', $channelId)
            ->where('is_pinned', true)
            ->with(['user', 'conversationFiles'])
            ->orderByDesc('pinned_at')
            ->get();
    }

    /**
     * @return list<string>
     */
    private function channelListEagerLoads(): array
    {
        return [
            'channelUsers.user.storage',
            'channelUsers.user.provider.storage',
            'channelLastConversation.user',
            'channelLastConversation.conversationLastFiles',
        ];
    }

    /**
     * @return list<string>
     */
    private function channelMemberEagerLoads(): array
    {
        return [
            'user.storage',
            'user.provider.storage',
        ];
    }

    /**
     * @return list<string>
     */
    private function conversationEagerLoads(): array
    {
        return ['user', 'conversationFiles', 'replyTo.user', 'replyTo.conversationFiles', 'reactions.user'];
    }

    private function resolveReplyToConversationId(string $channelId, ?string $replyToId): ?string
    {
        if (! $replyToId) {
            return null;
        }

        $parent = $this->channelConversation
            ->where('id', $replyToId)
            ->where('channel_id', $channelId)
            ->first();

        return $parent?->id;
    }

    /**
     * @return array{enableStaffMessaging: bool, isStaffGroup: bool, memberCount: int}
     */
    private function staffMessagingViewContext(?ChannelList $channel, ?ChannelUser $fromUser = null): array
    {
        $isStaffGroup = $channel && $this->staffGroupChannelService->isStaffGroupChannel($channel);
        $enableStaffMessaging = $isStaffGroup
            || ($fromUser?->user && in_array($fromUser->user->user_type, ADMIN_USER_TYPES, true));

        return [
            'enableStaffMessaging' => $enableStaffMessaging,
            'isStaffGroup' => (bool) $isStaffGroup,
            'memberCount' => ($isStaffGroup && $channel) ? $this->staffGroupChannelService->memberCount($channel) : 0,
        ];
    }

    /**
     * @return array{staffPresence: ?array, presenceService: StaffPresenceService}
     */
    private function staffPresenceContextForFromUser(?ChannelUser $fromUser): array
    {
        $presenceService = $this->staffPresenceService;
        $staffPresence = null;

        if ($fromUser?->user && in_array($fromUser->user->user_type, ADMIN_USER_TYPES, true)) {
            $staffUser = $this->user
                ->select(['id', 'first_name', 'last_name', 'email', 'phone', 'profile_image', 'user_type', 'staff_presence_status', 'last_seen_at'])
                ->find($fromUser->user->id);

            if ($staffUser) {
                $staffPresence = $presenceService->formatStaffMember($staffUser);
            }
        }

        return compact('staffPresence', 'presenceService');
    }

    private function recipientChannelUsersFor(string $channelId, string $senderUserId): \Illuminate\Support\Collection
    {
        $channel = $this->channelList->find($channelId);
        $query = $this->channelUser->query()
            ->where('channel_id', $channelId)
            ->where('user_id', '!=', $senderUserId)
            ->with($this->channelMemberEagerLoads());

        if ($channel && is_support_channel_reference_type($channel->reference_type)) {
            $query->whereHas('user', fn ($userQuery) => $userQuery->whereNotIn('user_type', ADMIN_USER_TYPES));
        }

        return $query->get(['id', 'user_id', 'is_read', 'read_at']);
    }

    private function buildLiveSyncChatList(Request $request): \Illuminate\Support\Collection
    {
        $mode = $request->query('mode', 'support');
        $filter = $request->query('filter', 'all');

        if ($mode === 'staff') {
            $list = $this->buildStaffChatListForSync($request);

            if (in_array($request->user()->user_type, ADMIN_USER_TYPES, true)) {
                $group = $this->staffGroupChannelService->ensureGroupForUser($request->user());
                if ($group) {
                    $group->load($this->channelListEagerLoads());
                    $group['is_read'] = $group->channelUsers
                        ->where('user_id', $request->user()->id)
                        ->first()
                        ?->is_read ?? 1;

                    return collect([$group])->concat($list)->values();
                }
            }

            return $list;
        }

        return $this->buildSupportChatListForSync($request, $filter);
    }

    private function buildSupportChatListForSync(Request $request, string $filter): \Illuminate\Support\Collection
    {
        if (! in_array($filter, ['all', 'unread'], true)) {
            $filter = 'all';
        }

        $chatList = $this->supportInboxListQuery($request, $filter, true)
            ->orderBy('updated_at', 'DESC')
            ->get();

        foreach ($chatList as $chat) {
            ensure_support_channel_user($chat->id, (string) $request->user()->id);
        }
        $chatList->load($this->channelListEagerLoads());

        return $chatList->map(function ($chat) use ($request) {
            $chat['is_read'] = $chat->channelUsers->where('user_id', $request->user()->id)->first()?->is_read ?? 1;

            return $chat;
        });
    }

    private function supportInboxListQuery(Request $request, string $filter, bool $withCount = false)
    {
        $query = $withCount
            ? $this->channelList->withCount(['channelUsers'])->with($this->channelListEagerLoads())
            : $this->channelList->with($this->channelListEagerLoads());

        return $query
            ->whereIn('reference_type', support_channel_reference_types())
            ->whereHas('channelUsers.user', function ($userQuery) {
                $userQuery->where(function ($query) {
                    $query->whereIn('user_type', CUSTOMER_USER_TYPES)
                        ->orWhere('user_type', 'provider-admin');
                });
            })
            ->when($filter === 'unread', function ($query) use ($request) {
                $query->whereHas('channelUsers', fn ($q) => $q
                    ->where('user_id', $request->user()->id)
                    ->where('is_read', 0));
            });
    }

    private function peerChannelUserFor(?ChannelList $channel, $channelUsers, string $currentUserId): ?ChannelUser
    {
        if ($channel && is_support_channel_reference_type($channel->reference_type)) {
            return support_inbox_peer_channel_user($channelUsers, $currentUserId);
        }

        return collect($channelUsers)->first();
    }

    private function buildStaffChatListForSync(Request $request): \Illuminate\Support\Collection
    {
        $chatList = $this->channelList->withCount(['channelUsers'])
            ->with($this->channelListEagerLoads())
            ->whereHas('channelUsers', function ($query) use ($request) {
                $query->where(['user_id' => $request->user()->id]);
            })
            ->whereHas('channelUsers', function ($channelQuery) use ($request) {
                $channelQuery->where('user_id', '!=', $request->user()->id)
                    ->whereHas('user', fn ($userQuery) => $userQuery->whereIn('user_type', ADMIN_USER_TYPES));
            })
            ->orderBy('updated_at', 'DESC')
            ->get()
            ->filter(fn ($chat) => ! $this->staffGroupChannelService->isStaffGroupChannel($chat))
            ->values();

        return $chatList->map(function ($chat) use ($request) {
            $chat['is_read'] = $chat->channelUsers->where('user_id', $request->user()->id)->first()?->is_read ?? 1;

            return $chat;
        });
    }

    private function sidebarChannelPayload(ChannelList $chat, string $currentUserId, bool $isActive): array
    {
        return [
            'id' => $chat->id,
            'updated_at' => $chat->updated_at?->toIso8601String(),
            'is_read' => (int) ($chat['is_read'] ?? $chat->channelUsers->where('user_id', $currentUserId)->first()?->is_read ?? 1),
            'show_unread_badge' => (int) ($chat['is_read'] ?? 1) === 0 && ! $isActive,
            'preview_html' => view('chattingmodule::admin.partials._chat-list-last-message', [
                'chat' => $chat,
                'section' => 'preview',
            ])->render(),
            'meta_html' => view('chattingmodule::admin.partials._chat-list-last-message', [
                'chat' => $chat,
                'section' => 'meta',
            ])->render(),
        ];
    }

    private function recipientReadFingerprint(\Illuminate\Support\Collection $recipientChannelUsers): string
    {
        return $recipientChannelUsers
            ->map(fn ($recipient) => ($recipient->user_id ?? '').':'.($recipient->read_at?->timestamp ?? 0).':'.((int) ($recipient->is_read ?? 0)))
            ->sort()
            ->implode('|');
    }

    private function activeConversationUpdate(
        Request $request,
        string $channelId,
        ?string $lastMessageAt,
        ?string $readFingerprint
    ): array {
        $isMember = $this->channelUser->where('channel_id', $channelId)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return ['changed' => false];
        }

        $recipients = $this->recipientChannelUsersFor($channelId, (string) $request->user()->id);
        $newFingerprint = $this->recipientReadFingerprint($recipients);

        $latest = $this->channelConversation->where('channel_id', $channelId)
            ->latest('created_at')
            ->first();

        $latestAt = $latest?->created_at?->toIso8601String();
        $messageChanged = ! $lastMessageAt || ($latestAt && $latestAt > $lastMessageAt);
        $statusChanged = $readFingerprint !== $newFingerprint;

        if (! $messageChanged && ! $statusChanged) {
            return [
                'changed' => false,
                'last_message_at' => $latestAt,
                'read_fingerprint' => $newFingerprint,
            ];
        }

        if ($messageChanged) {
            $this->channelUser->where('channel_id', $channelId)
                ->where('user_id', $request->user()->id)
                ->update([
                    'is_read' => 1,
                    'read_at' => now(),
                ]);
        }

        $conversation = $this->channelConversation->where(['channel_id' => $channelId])
            ->with($this->conversationEagerLoads())
            ->whereHas('channel.channelUsers', function ($query) use ($request) {
                $query->where(['user_id' => $request->user()->id]);
            })->latest()->paginate(100, ['*'], 'offset', 1);

        $channel = $this->channelList->withCount('channelUsers')->find($channelId);
        $fromUser = $this->peerChannelUserFor(
            $channel,
            $this->channelUser->where('channel_id', $channelId)
                ->where('user_id', '!=', $request->user()->id)
                ->with($this->channelMemberEagerLoads())
                ->get(),
            (string) $request->user()->id
        );
        $messagingContext = $this->staffMessagingViewContext($channel, $fromUser);

        return [
            'changed' => true,
            'last_message_at' => $latestAt,
            'read_fingerprint' => $newFingerprint,
            'messages_html' => view('chattingmodule::admin.partials._conversation-messages-only', array_merge(
                compact('conversation'),
                $messagingContext,
                ['recipientChannelUsers' => $recipients],
            ))->render(),
        ];
    }
}
