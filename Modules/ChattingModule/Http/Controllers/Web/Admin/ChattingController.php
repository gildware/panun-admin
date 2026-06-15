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
     * @return Factory|View|Application
     */
    public function index(Request $request): Factory|View|Application
    {
        $request->validate([
            'user_type' => 'nullable|in:customer,provider_admin,provider_serviceman,staff'
        ]);

        $chatList = $this->channelList->withCount(['channelUsers'])
            ->with(['channelUsers.user.provider'])
            ->whereHas('channelUsers', function ($query) use ($request) {
                $query->where(['user_id' => $request->user()->id]);
            })
            ->when($request->has('user_type'), function ($query) use ($request) {
                $type = $request['user_type'];
                $query->whereHas('channelUsers', function ($channelQuery) use ($type, $request) {
                    $channelQuery->where('user_id', '!=', $request->user()->id)
                        ->whereHas('user', function ($userQuery) use ($type) {
                            $userQuery->where(function ($query) use ($type) {
                                if ($type == 'customer') {
                                    $query->where(function ($q) {
                                        $q->whereIn('user_type', CUSTOMER_USER_TYPES)
                                            ->orWhere(function ($q2) {
                                                $q2->where('user_type', 'provider-admin')
                                                    ->where('customer_app_access', 1);
                                            });
                                    });
                                } elseif ($type == 'provider_admin') {
                                    $query->where('user_type', 'provider-admin');
                                } elseif ($type == 'provider_serviceman') {
                                    $query->where('user_type', 'provider-serviceman');
                                } elseif ($type == 'staff') {
                                    $query->whereIn('user_type', ADMIN_USER_TYPES);
                                }
                            });
                        });
                });
            })
            ->orderBy('updated_at', 'DESC')->get();

        $chatList->map(function ($chat) use ($request) {
            $chat['is_read'] = $chat->channelUsers->where('user_id', $request->user()->id)->first()->is_read;
        });

        $type = $request['user_type'] ?? 'customer';
        $staffGroupChannel = null;
        $staffGroupMemberCount = 0;

        if ($type === 'staff' && in_array($request->user()->user_type, ADMIN_USER_TYPES, true)) {
            $staffGroupChannel = $this->staffGroupChannelService->ensureGroupForUser($request->user());

            if ($staffGroupChannel) {
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

        // Only load the data each tab actually renders. The staff tab uses the
        // staff directory, while the other tabs use the customer/provider/serviceman
        // directories. Loading all of them on every request makes this page slow.
        $customers = collect();
        $providers = collect();
        $servicemen = collect();
        $staffMembers = collect();
        $staffPresenceById = collect();

        if ($type === 'staff') {
            $staffMembers = $this->staffPresenceService->listStaffPresence($request->user()->id);
            $staffPresenceById = $staffMembers->keyBy('id');
        } else {
            $customers = $this->user->ofStatus(1)->inCustomerDirectory()->get();
            $providers = $this->user->ofStatus(1)->where(['user_type' => 'provider-admin'])->with(['provider'])->get();
            $servicemen = $this->user->ofStatus(1)->where(['user_type' => 'provider-serviceman'])->get();
        }

        $openChannelId = $request->query('channel_id');

        return view('chattingmodule::admin.index', compact('chatList', 'customers', 'providers', 'servicemen', 'staffMembers', 'staffPresenceById', 'type', 'openChannelId', 'staffGroupChannel', 'staffGroupMemberCount'));
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

        return redirect()->route('admin.chat.index', [
            'user_type' => 'staff',
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
            ->update(['is_read' => 1]);

        $conversation = $this->channelConversation->where(['channel_id' => $channel->id])
            ->with($this->conversationEagerLoads())->whereHas('channel.channelUsers', function ($query) use ($request) {
                $query->where(['user_id' => $request->user()->id]);
            })->latest()->paginate(100, ['*'], 'offset', 1);

        $fromUser = $this->channelUser->where('channel_id', $channel->id)
            ->where('user_id', '!=', $request->user()->id)
            ->with('user')
            ->first();

        $channelId = $channel->id;
        $presenceContext = $this->staffPresenceContextForFromUser($fromUser);
        $messagingContext = $this->staffMessagingViewContext($channel, $fromUser);
        $pinnedMessages = $this->pinnedMessagesFor($channelId);

        $response = [
            'channel_id' => $channelId,
            'ajax_route' => route('admin.chat.ajax-conversation', ['channel_id' => $channelId, 'offset' => 1]),
            'template' => view('chattingmodule::admin.partials._conversations', array_merge(
                compact('fromUser', 'conversation', 'channelId', 'pinnedMessages'),
                $presenceContext,
                $messagingContext
            ))->render(),
        ];

        $staffPresence = $presenceContext['staffPresence'] ?? null;

        if ($result['created']) {
            $chat = $this->channelList->with(['channelUsers.user'])->find($channel->id);
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
            'user_type' => 'in:customer,provider-admin,provider-serviceman,staff',
        ]);

        if ($request['user_type'] == 'customer') {
            $request['to_user'] = $request['customer_id'];
        } elseif ($request['user_type'] == 'provider-admin') {
            $request['to_user'] = $request['provider_id'];
        } elseif ($request['user_type'] == 'provider-serviceman') {
            $request['to_user'] = $request['serviceman_id'];
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

        $userTypeRoutes = [
            'customer' => 'customer',
            'provider-admin' => 'provider_admin',
            'provider-serviceman' => 'provider_serviceman',
            'staff' => 'staff',
        ];

        $userType = $request['user_type'];

        if (array_key_exists($userType, $userTypeRoutes)) {
            return redirect()->route('admin.chat.index', ['user_type' => $userTypeRoutes[$userType]]);
        } else {
            return back();
        }
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

        $replyToId = $this->resolveReplyToConversationId(
            $request['channel_id'],
            $request->input('reply_to_conversation_id')
        );

        if ($request->filled('reply_to_conversation_id') && ! $replyToId) {
            return response()->json(response_formatter(DEFAULT_400, null, [[
                'message' => translate('Invalid_reply_message'),
            ]]), 400);
        }

        DB::transaction(function () use ($request, $replyToId) {
            $this->channelList->where('id', $request['channel_id'])->update([
                'updated_at' => now()
            ]);
            $this->channelUser->where('channel_id', $request['channel_id'])->where('user_id', '!=', $request->user()->id)
                ->update([
                    'is_read' => 0
                ]);

            $channelConversation = $this->channelConversation;
            $channelConversation->channel_id = $request->channel_id;
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

        $conversation = $this->channelConversation->where(['channel_id' => $request['channel_id']])
            ->with($this->conversationEagerLoads())->whereHas('channel.channelUsers', function ($query) use ($request) {
                $query->where(['user_id' => $request->user()->id]);
            })->latest()->paginate(100, ['*'], 'offset', $request['offset']);

        $channel = $this->channelList->find($request['channel_id']);
        $fromUser = $this->channelUser->where('channel_id', $request['channel_id'])
            ->where('user_id', '!=', $request->user()->id)
            ->with('user')
            ->first();
        $messagingContext = $this->staffMessagingViewContext($channel, $fromUser);

        return response()->json([
            'template' => view('chattingmodule::admin.partials._conversation-messages-only', array_merge(
                compact('conversation'),
                $messagingContext
            ))->render(),
        ]);
    }

    public function entitySearch(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|max:120',
            'type' => 'required|in:staff,customer,provider,booking,service,lead',
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

        $this->channelUser->where('channel_id', $request['channel_id'])->where('user_id', $request->user()->id)
            ->update([
                'is_read' => 1
            ]);

        $conversation = $this->channelConversation->where(['channel_id' => $request['channel_id']])
            ->with($this->conversationEagerLoads())->whereHas('channel.channelUsers', function ($query) use ($request) {
                $query->where(['user_id' => $request->user()->id]);
            })->latest()->paginate(100, ['*'], 'offset', $request['offset']);

        $fromUser = $this->channelUser->where('channel_id', $request['channel_id'])
            ->where('user_id', '!=', $request->user()->id)
            ->with('user')
            ->first();

        $channelId = $request['channel_id'];
        $channel = $this->channelList->withCount('channelUsers')->find($channelId);
        $messagingContext = $this->staffMessagingViewContext($channel, $fromUser);
        $presenceContext = ($messagingContext['isStaffGroup'] ?? false)
            ? []
            : $this->staffPresenceContextForFromUser($fromUser);
        $pinnedMessages = $this->pinnedMessagesFor($channelId);

        return response()->json([
            'template' => view('chattingmodule::admin.partials._conversations', array_merge(
                compact('fromUser', 'conversation', 'channelId', 'pinnedMessages'),
                $presenceContext,
                $messagingContext
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
}
