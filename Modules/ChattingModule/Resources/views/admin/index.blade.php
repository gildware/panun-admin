@extends('adminmodule::layouts.master')

@section('title',translate('chat_list'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/select2/select2.min.css')}}"/>
    <link rel="stylesheet" href="{{asset('assets/css/lightbox.css')}}">
    <style>
        .badge.bg--secondary {
            color: var(--bs-secondary, #6c757d);
        }
        .staff-sidebar-section { padding: 0 0.75rem; }
        .staff-sidebar-heading {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--bs-secondary);
            margin: 0.75rem 0 0.5rem;
            padding: 0 0.25rem;
        }
        .staff-contacts-list {
            max-height: 220px;
            overflow-y: auto;
        }
        .staff-conversations-list {
            max-height: 260px;
            overflow-y: auto;
        }
        .staff-contact-item {
            cursor: pointer;
            border-radius: 0.5rem;
            padding: 0.5rem 0.35rem;
            transition: background-color 0.15s ease;
        }
        .staff-contact-item:hover,
        .staff-contact-item.active {
            background-color: rgba(0, 0, 0, 0.04);
        }
        .chat_list.active-selected {
            background-color: rgba(217, 217, 217, 0.3);
        }
        .staff-chat-presence-dot { width: 10px; height: 10px; flex-shrink: 0; }
        .staff-contacts-toggle {
            cursor: pointer;
            user-select: none;
            width: 100%;
            border: 0;
            background: transparent;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--bs-secondary);
            margin: 0.75rem 0 0.35rem;
            padding: 0 0.25rem;
            line-height: 1.2;
        }
        .staff-contacts-toggle .staff-contacts-chevron {
            font-size: 1.125rem;
            color: var(--bs-secondary);
            transition: transform 0.2s ease;
        }
        .staff-contacts-toggle[aria-expanded="true"] .staff-contacts-chevron {
            transform: rotate(180deg);
        }
        .staff-contacts-title-badge {
            font-size: 0.65rem;
            padding: 0.15rem 0.4rem;
            line-height: 1;
        }
        .staff-group-avatar,
        .staff-group-header-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(13, 110, 253, 0.08);
        }
        .chat-profile-link {
            text-decoration: none;
        }
        .chat-profile-link:hover {
            text-decoration: underline;
            color: var(--bs-primary) !important;
        }
        .input_msg_write {
            overflow: visible;
        }
        .staff-chat-compose-wrap {
            overflow: visible;
        }
        .staff-chat-entity-picker {
            position: absolute;
            left: 0;
            right: 0;
            bottom: calc(100% + 0.35rem);
            z-index: 1050;
            max-height: 280px;
        }
        .staff-chat-entity-results {
            max-height: 180px;
            overflow-y: auto;
        }
        .staff-chat-entity-link {
            font-weight: 500;
        }
        .message_text .staff-chat-entity-link {
            vertical-align: baseline;
        }
        .staff-chat-entity-type {
            font-weight: 600;
            text-transform: capitalize;
        }
        .staff-chat-entity-sep {
            opacity: 0.6;
        }
        .chat-message-bubble {
            position: relative;
        }
        .chat-reply-quote {
            background: rgba(13, 110, 253, 0.06);
            border-radius: 0.35rem;
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }
        .chat-reply-jump {
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .chat-reply-jump:hover,
        .chat-reply-jump:focus {
            background-color: rgba(13, 110, 253, 0.12);
            outline: none;
        }
        .chat-reply-btn {
            line-height: 1;
            opacity: 0.7;
        }
        .chat-message-bubble:hover .chat-reply-btn {
            opacity: 1;
        }
        .chat-pin-btn {
            line-height: 1;
            opacity: 0.6;
        }
        .chat-react-btn {
            line-height: 1;
            opacity: 0.6;
        }
        .chat-message-bubble:hover .chat-react-btn {
            opacity: 1;
        }
        .chat-react-btn:hover,
        .chat-react-btn:focus {
            color: var(--bs-primary, #0d6efd) !important;
            opacity: 1 !important;
        }
        .chat-delete-btn {
            line-height: 1;
            opacity: 0.6;
        }
        .chat-message-bubble:hover .chat-delete-btn {
            opacity: 1;
        }
        .chat-delete-btn:hover,
        .chat-delete-btn:focus {
            color: #dc3545 !important;
            opacity: 1 !important;
        }
        .chat-reaction-wrap {
            position: relative;
            display: inline-flex;
        }
        .chat-reaction-picker {
            position: absolute;
            bottom: calc(100% + 6px);
            left: 50%;
            transform: translateX(-50%) scale(0.95);
            display: none;
            gap: 0.15rem;
            padding: 0.25rem 0.4rem;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 2rem;
            white-space: nowrap;
            z-index: 20;
        }
        .chat-reaction-wrap.is-open .chat-reaction-picker {
            display: inline-flex;
            transform: translateX(-50%) scale(1);
        }
        .chat-reaction-option {
            border: 0;
            background: transparent;
            font-size: 1.1rem;
            line-height: 1;
            padding: 0.15rem 0.25rem;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.1s ease, background-color 0.1s ease;
        }
        .chat-reaction-option:hover {
            transform: scale(1.25);
            background-color: #f1f3f5;
        }
        .chat-message-reactions:empty {
            display: none !important;
        }
        .chat-reaction-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            border: 1px solid #e0e0e0;
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 0.05rem 0.45rem;
            font-size: 0.75rem;
            line-height: 1.4;
            cursor: pointer;
            transition: background-color 0.1s ease, border-color 0.1s ease;
        }
        .chat-reaction-chip:hover {
            border-color: #adb5bd;
        }
        .chat-reaction-chip.reacted {
            background: rgba(13, 110, 253, 0.12);
            border-color: var(--bs-primary, #0d6efd);
        }
        .chat-reaction-chip .chat-reaction-count {
            font-weight: 600;
            color: #495057;
        }
        .chat-message-bubble:hover .chat-pin-btn,
        .chat-message-bubble.is-pinned .chat-pin-btn {
            opacity: 1;
        }
        .chat-pin-btn.text-primary .material-symbols-outlined,
        .chat-pinned-bar .material-symbols-outlined {
            font-variation-settings: 'FILL' 1;
        }
        .chat-pin-btn:hover,
        .chat-pin-btn:focus,
        .chat-pin-btn.text-primary:hover,
        .chat-pin-btn.text-primary:focus {
            color: var(--bs-primary, #0d6efd) !important;
            opacity: 1 !important;
        }
        .chat-pin-btn.text-muted:hover,
        .chat-pin-btn.text-muted:focus,
        .chat-unpin-btn:hover,
        .chat-unpin-btn:focus {
            color: #495057 !important;
            opacity: 1 !important;
        }
        .chat-pinned-jump {
            cursor: pointer;
        }
        .chat-pinned-item {
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }
        .chat-pinned-item:hover {
            background-color: #f8f9fa !important;
            border-color: #adb5bd !important;
        }
        .chat-pinned-toggle {
            cursor: pointer;
            user-select: none;
        }
        .chat-pinned-chevron {
            transition: transform 0.2s ease;
        }
        .chat-pinned-accordion.is-open .chat-pinned-chevron {
            transform: rotate(180deg);
        }
        .chat-message-bubble.bubble-highlight {
            animation: chatBubbleFlash 1.4s ease;
        }
        @keyframes chatBubbleFlash {
            0% { background-color: rgba(13, 110, 253, 0.18); }
            100% { background-color: transparent; }
        }
        .chat-message-bubble.bubble-pinned-active {
            border: 2px solid #f0ad4e;
            border-radius: 0.5rem;
            box-shadow: 0 0 0 3px rgba(240, 173, 78, 0.2);
            padding: 0.5rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .chat-list-last-message {
            max-width: 100%;
        }
        .chat_people--support {
            width: 100%;
            min-width: 0;
        }
        .chat_ib--support {
            flex: 1 1 auto;
            min-width: 0;
        }
        .chat-card-row {
            width: 100%;
            min-width: 0;
        }
        .chat-card-end {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.35rem;
            flex-shrink: 0;
            white-space: nowrap;
            margin-left: auto;
        }
        .chat-card-row--bottom .chat-list-last-meta {
            margin-left: 0;
        }
        .chat-list-last-message-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
            margin-top: 0.15rem;
            max-width: 100%;
            min-width: 0;
        }
        .chat-list-last-message-row .chat-list-last-message {
            margin-top: 0;
            min-width: 0;
            flex: 1 1 auto;
        }
        .chat-list-last-message--empty {
            flex: 1 1 auto;
        }
        .chat-list-last-meta {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            white-space: nowrap;
            margin-left: auto;
        }
        .chat-list-last-time {
            font-size: 0.6875rem;
            line-height: 1.2;
            color: #6c757d;
        }
        .chat-message-status--list {
            font-size: 0.6875rem;
            font-weight: 500;
            line-height: 1.2;
        }
        .chat-list-last-meta .chat-message-status--sent,
        .chat-list-last-meta .chat-message-status--delivered {
            color: #6c757d;
        }
        .chat-list-last-meta .chat-message-status--seen {
            color: #0d6efd;
        }
        .chat-list-last-meta .chat-message-status--seen .material-symbols-outlined {
            font-variation-settings: 'FILL' 1;
        }
        .chat-message-status--compact {
            flex-shrink: 0;
            font-size: 0.6875rem;
            font-weight: 500;
        }
        .chat-list-last-message-row .chat-message-status--seen .material-symbols-outlined {
            font-variation-settings: 'FILL' 1;
        }
        .chat_ib .chat-list-last-message {
            color: var(--bs-secondary, #6c757d) !important;
        }
        .chat_list.active .chat-list-last-message,
        .chat_list.active-selected .chat-list-last-message {
            color: var(--bs-body-color, #212529) !important;
        }
        .outgoing_msg .message_text {
            position: relative;
        }
        .outgoing_msg .message_text__body {
            display: inline;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .outgoing_msg .message_text .chat-message-meta--inline {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            float: right;
            margin-left: 0.45rem;
            margin-top: 0.2rem;
            line-height: 1.2;
            white-space: nowrap;
            vertical-align: bottom;
        }
        .outgoing_msg .message_text .chat-message-meta--inline .time_date {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.6875rem;
        }
        .outgoing_msg .message_text .chat-message-status--inline {
            font-size: 0.6875rem;
        }
        .outgoing_msg .message_text .chat-message-status--inline.chat-message-status--sent,
        .outgoing_msg .message_text .chat-message-status--inline.chat-message-status--delivered {
            color: rgba(255, 255, 255, 0.85);
        }
        .outgoing_msg .message_text .chat-message-status--inline.chat-message-status--seen {
            color: #8ec5ff;
        }
        .outgoing_msg .message_text .chat-message-status--inline.chat-message-status--seen .material-symbols-outlined {
            font-variation-settings: 'FILL' 1;
        }
        .outgoing_msg .message_text--attachment {
            position: relative;
            padding-bottom: 1.5rem;
        }
        .outgoing_msg .message_text--attachment .chat-message-meta--inline {
            position: absolute;
            right: 0.625rem;
            bottom: 0.375rem;
            float: none;
            margin: 0;
            background: rgba(0, 0, 0, 0.28);
            border-radius: 0.35rem;
            padding: 0.1rem 0.35rem;
        }
        .chat-message-status {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            font-size: 0.75rem;
            font-weight: 500;
            line-height: 1.2;
            white-space: nowrap;
        }
        .chat-message-status .material-symbols-outlined {
            font-size: 0.875rem;
            line-height: 1;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title d-flex gap-3 align-items-center">
                    @if(($type ?? '') === 'staff')
                        {{ translate('Staff_Conversation') }}
                    @else
                        {{translate('Messages')}}
                    @endif
                    <span class="badge bg--secondary fs-6">{{$chatList->count()}}</span>
                </h2>
            </div>

            <div class="row gx-1">
                <div class="col-xl-3 col-lg-4">
                    <div class="card card-body px-0 h-100">
                        @if(($type ?? '') !== 'staff')
                            <div class="media align-items-center px-3 gap-3 mb-2">
                                <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                                    <ul class="nav nav--tabs">
                                        <li class="nav-item">
                                            <a class="nav-link {{($filter ?? 'all') === 'all' ? 'active' : ''}}"
                                               href="{{ route('admin.chat.support', array_filter(['filter' => 'all', 'channel_id' => request()->query('channel_id')])) }}">
                                                {{ translate('all') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{($filter ?? 'all') === 'unread' ? 'active' : ''}}"
                                               href="{{ route('admin.chat.support', ['filter' => 'unread']) }}">
                                                {{ translate('unread') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <div class="inbox_people">
                            <div class="d-flex gap-3 align-items-center mx-3 mb-3">
                                <div class="input-group search-form__input_group">
                                        <span class="search-form__icon">
                                            <span class="material-icons">search</span>
                                        </span>
                                    <input type="search" class="h-40 flex-grow-1 search-form__input" id="chat-search"
                                           placeholder="{{ ($type ?? '') === 'staff' ? translate('Search_conversations_or_contacts') : translate('Search') }}">
                                </div>

                                @if(($type ?? '') !== 'staff')
                                <div class="ripple-animation" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ translate('Search by name or phone number to start the conversation') }}" type="button">
                                    <img src="{{asset('/assets/admin-module/img/info.svg')}}" class="svg" alt="">
                                </div>
                                @endif
                            </div>

                            @if(($type ?? '') === 'staff')
                                @php($presenceService = app(\Modules\AdminModule\Services\StaffPresenceService::class))
                                <div class="staff-sidebar-section">
                                    <h6 class="staff-sidebar-heading">{{ translate('Conversations') }}</h6>
                                    <div class="inbox_chat staff-conversations-list d-flex flex-column mt-1" id="admin-chat-sidebar-list">
                                        @if(!empty($staffGroupChannel))
                                            @include('chattingmodule::admin.partials._staff-group-conversation-list-item', [
                                                'staffGroupChannel' => $staffGroupChannel,
                                                'memberCount' => $staffGroupMemberCount ?? 0,
                                            ])
                                        @endif
                                        @forelse($chatList as $chat)
                                            @php($fromUser=$chat->channelUsers->where('user_id','!=',auth()->id())->first())
                                            @php($staffPresence = isset($fromUser->user) ? ($staffPresenceById[$fromUser->user->id] ?? null) : null)
                                            @include('chattingmodule::admin.partials._staff-conversation-list-item', [
                                                'chat' => $chat,
                                                'fromUser' => $fromUser,
                                                'staffPresence' => $staffPresence,
                                                'presenceService' => $presenceService,
                                                'isActive' => ($openChannelId ?? '') === (string) $chat->id,
                                            ])
                                        @empty
                                            <p class="text-muted fs-13 px-2 py-2 mb-0">{{ translate('No_conversations_yet') }}</p>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="staff-sidebar-section border-top mt-1 pt-0">
                                    <button type="button"
                                            class="staff-contacts-toggle d-flex align-items-center justify-content-between"
                                            id="staffContactsHeading"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#staffContactsCollapse"
                                            aria-expanded="false"
                                            aria-controls="staffContactsCollapse">
                                        <span class="d-inline-flex align-items-center gap-2">
                                            {{ translate('Contacts') }}
                                            <span class="badge bg--secondary staff-contacts-title-badge">{{ count($staffMembers ?? []) }}</span>
                                        </span>
                                        <span class="material-symbols-outlined staff-contacts-chevron" aria-hidden="true">expand_more</span>
                                    </button>
                                    <div id="staffContactsCollapse"
                                         class="collapse"
                                         aria-labelledby="staffContactsHeading">
                                        <div class="staff-contacts-list d-flex flex-column gap-1">
                                            @foreach($staffMembers ?? [] as $member)
                                                <button type="button"
                                                        class="staff-contact-item staff-contact-row staff-contact-open border-0 bg-transparent text-dark d-flex align-items-center justify-content-between gap-2 w-100"
                                                        data-staff-id="{{ $member['id'] }}">
                                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                                        <img src="{{ $member['profile_image'] }}" alt="" class="avatar rounded-circle" width="32" height="32">
                                                        <span class="text-truncate">{{ $member['name'] }}</span>
                                                    </div>
                                                    <span class="badge rounded-pill flex-shrink-0 {{ $presenceService->statusBadgeClass($member['presence_status']) }}">{{ $member['presence_label'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @else
                            <div class="inbox_chat d-flex flex-column mt-1" id="admin-chat-sidebar-list">
                                @foreach($chatList as $chat)
                                    @include('chattingmodule::admin.partials._support-chat-list-item', [
                                        'chat' => $chat,
                                        'isActive' => ($openChannelId ?? '') === (string) $chat->id,
                                    ])
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-9 col-lg-8 mt-4 mt-lg-0">
                    <div class="card card-body card-chat justify-content-between" id="set-conversation">
                        <h4 class="d-flex align-items-center justify-content-center my-auto gap-2">
                            <span class="material-icons">chat</span>
                            {{translate('start_conversation')}}
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-conversation-start" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <label for="with-user" class="d-flex gap-2 fw-semibold">
                        <span class="material-icons">chat</span>
                        {{translate('with_user')}}
                    </label>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{route('admin.chat.create-channel')}}" method="post">
                    @csrf
                    <div class="modal-body p-30">
                        @if(($type ?? '') === 'staff')
                            <input type="hidden" name="user_type" value="staff">
                            <div class="form-group mb-30" id="staff">
                                <label class="form-label fw-semibold mb-2">{{ translate('Select_Staff_Member') }}</label>
                                <select class="form-control chat-js-select" name="staff_id">
                                    <option value="" selected disabled>{{ translate('Select_Staff_Member') }}</option>
                                    @foreach($staffMembers ?? [] as $member)
                                        <option value="{{ $member['id'] }}">
                                            {{ $member['name'] }} ({{ $member['presence_label'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="form-group mb-30">
                                <select class="form-control" name="user_type" id="user_type">
                                    <option value="" selected disabled>{{translate('Select_User_Type')}}</option>
                                    <option value="customer">{{translate('customer')}}</option>
                                    <option value="provider-admin">{{translate('provider')}}</option>
                                </select>
                            </div>

                            <div class="form-group mb-30" id="customer">
                                <select class="form-control chat-js-select" name="customer_id">
                                    <option value="" selected disabled>{{translate('Select_Customer')}}</option>
                                    @foreach($customers as $item)
                                        <option value="{{$item->id}}">
                                            {{$item->first_name}} {{$item->last_name}} ({{$item->phone}})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group mb-30 d--none" id="provider">
                                <select class="form-control chat-js-select" name="provider_id">
                                    <option value="" selected disabled>{{translate('Select_Provider')}}</option>
                                    @foreach($providers as $item)
                                        @if($item->provider)
                                            <option value="{{$item->id}}">
                                                {{$item->provider->company_name??''}} ({{$item->provider->company_phone}})
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal"
                                aria-label="Close">{{translate('close')}}</button>
                        <button type="submit" class="btn btn--primary">{{translate('start')}}</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

@endsection

@push('script')
    <script src="{{asset('assets/js/lightbox.min.js')}}"></script>
    <script>

        "use Strict";

        $(document).on('click', '.chat-list-class', function () {
            fetch_conversation($(this).data('route'), $(this).data('chat'));
        });

        $(document).on('click', '.staff-contact-open', function () {
            openStaffContact($(this).data('staff-id'));
        });

        function highlightActiveChat(chatId, staffId) {
            $('.chat-list-class').removeClass('active-selected');
            $('.staff-contact-row').removeClass('active');

            var chatEl = document.getElementById('chat-' + chatId);
            if (chatEl) {
                chatEl.classList.remove('active');
                chatEl.classList.add('active-selected');
                var badge = document.getElementById('badge-' + chatId);
                if (badge) {
                    badge.classList.add('hide-div');
                }
            }

            if (staffId) {
                $('[data-staff-id="' + staffId + '"]').addClass('active');
            }
        }

        function updateStaffChatUrl(channelId) {
            var url = new URL(window.location.href);
            url.searchParams.set('channel_id', channelId);
            if (window.location.pathname.indexOf('/admin/chat/staff') !== -1) {
                history.replaceState({}, '', url.pathname + '?' + url.searchParams.toString());
                return;
            }
            if (!url.searchParams.get('user_type')) {
                url.searchParams.set('filter', url.searchParams.get('filter') || 'all');
            }
            history.replaceState({}, '', url);
        }

        function fetch_conversation(route, chat_id) {
            $.get({
                url: route,
                dataType: 'json',
                data: {},
                success: function (response) {
                    $('#set-conversation').empty().html(response.template);
                    highlightActiveChat(chat_id, null);
                    updateStaffChatUrl(chat_id);
                    if (window.ChatLiveSync) {
                        window.ChatLiveSync.setActiveChannel(chat_id);
                        window.ChatLiveSync.captureConversationCursor();
                    }
                },
                error: function (jqXHR) {
                    if (jqXHR.responseJSON && jqXHR.responseJSON.errors && jqXHR.responseJSON.errors.length > 0) {
                        jqXHR.responseJSON.errors.forEach(function (error) {
                            toastr.error(error.message);
                        });
                    } else {
                        toastr.error("An error occurred.");
                    }
                },
            });
        }

        function openStaffContact(staffId) {
            $.get({
                url: '{{ url('admin/chat/open-staff-ajax') }}/' + staffId,
                dataType: 'json',
                success: function (response) {
                    $('#set-conversation').empty().html(response.template);

                    $('.staff-conversations-list > .text-muted').remove();

                    if (response.list_item && !document.getElementById('chat-' + response.channel_id)) {
                        $('.staff-conversations-list').prepend(response.list_item);
                    }

                    highlightActiveChat(response.channel_id, staffId);
                    updateStaffChatUrl(response.channel_id);
                    if (window.ChatLiveSync) {
                        window.ChatLiveSync.setActiveChannel(response.channel_id);
                        window.ChatLiveSync.captureConversationCursor();
                    }
                },
                error: function (jqXHR) {
                    if (jqXHR.responseJSON && jqXHR.responseJSON.errors && jqXHR.responseJSON.errors.length > 0) {
                        jqXHR.responseJSON.errors.forEach(function (error) {
                            toastr.error(error.message);
                        });
                    } else {
                        toastr.error("An error occurred.");
                    }
                },
            });
        }

        $(document).ready(function () {
            $('.chat-js-select').select2({
                dropdownParent : $('#modal-conversation-start')
            });

            @if(($type ?? '') === 'staff')
            $('#chat-search').on('keyup', function () {
                var value = this.value.toLowerCase().trim();
                $('.staff-conversation-item, .staff-contact-row, .staff-group-item').each(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) !== -1);
                });
            });

            @if(!empty($openChannelId))
            var openChat = document.getElementById('chat-{{ $openChannelId }}');
            if (openChat) {
                openChat.click();
            }
            @endif

            window.openStaffContact = openStaffContact;

            @if(request()->query('open_staff'))
            openStaffContact('{{ request()->query('open_staff') }}');
            @endif
            @else
            @if(!empty($openChannelId))
            var openChat = document.getElementById('chat-{{ $openChannelId }}');
            if (openChat) {
                openChat.click();
            }
            @endif
            @endif
        });

    </script>

    @if(($type ?? '') === 'staff')
        <script>
            window.staffChatEntitySearchUrl = @json(route('admin.chat.entity-search'));
            window.staffChatStaffList = @json(collect($staffMembers ?? [])->map(fn ($m) => ['id' => $m['id'], 'name' => $m['name'], 'presence_label' => $m['presence_label'] ?? ''])->values());
            window.staffChatNoResultsText = @json(translate('No_results_found'));
            window.staffChatTagRegistry = [];
            window.staffChatTypeLabels = {
                staff: @json(translate('Staff')),
                customer: @json(translate('customer')),
                provider: @json(translate('Provider')),
                booking: @json(translate('booking')),
                service: @json(translate('Service')),
                lead: @json(translate('Lead')),
            };
        </script>
        <script src="{{ asset('assets/chatting-module/js/staff-chat-compose.js') }}"></script>
    @endif
    <script>
        window.chatTogglePinUrl = @json(route('admin.chat.toggle-pin'));
        window.chatPinPinLabel = @json(translate('Pin'));
        window.chatPinUnpinLabel = @json(translate('Unpin'));
        window.chatPinnedMessage = @json(translate('Message_pinned'));
        window.chatUnpinnedMessage = @json(translate('Message_unpinned'));
        window.chatToggleReactionUrl = @json(route('admin.chat.toggle-reaction'));
        window.chatDeleteMessageUrl = @json(route('admin.chat.delete-message'));
        window.chatClearConversationUrl = @json(route('admin.chat.clear-conversation'));
        window.chatMessageDeleted = @json(translate('Message_deleted'));
        window.chatConversationCleared = @json(translate('Conversation_cleared'));
        window.chatDeleteMessageConfirm = @json(translate('Delete_this_message?'));
        window.chatClearConversationConfirm = @json(translate('Clear_the_entire_conversation?_This_cannot_be_undone.'));
        window.chatConfirmYes = @json(translate('Yes'));
        window.chatConfirmNo = @json(translate('No'));
        window.chatConfirmTitle = @json(translate('Are_you_sure?'));
        window.chatLiveSyncUrl = @json(route('admin.chat.live-sync'));
        window.chatSidebarMode = @json(($type ?? '') === 'staff' ? 'staff' : 'support');
        window.chatSidebarFilter = @json($filter ?? 'all');
        window.chatActiveChannelId = @json($openChannelId ?? '');
    </script>
    <script src="{{ asset('assets/chatting-module/js/chat-live-sync.js') }}"></script>
    <script src="{{ asset('assets/chatting-module/js/chat-reply.js') }}"></script>
    <script src="{{ asset('assets/chatting-module/js/chat-pin.js') }}"></script>
    <script src="{{ asset('assets/chatting-module/js/chat-reactions.js') }}"></script>
    <script src="{{ asset('assets/chatting-module/js/chat-delete.js') }}"></script>
    <script src="{{asset('assets/chatting-module/js/custom.js')}}"></script>

@endpush
