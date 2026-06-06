@extends('adminmodule::layouts.master')

@section('title',translate('chat_list'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/select2/select2.min.css')}}"/>
    <link rel="stylesheet" href="{{asset('assets/css/lightbox.css')}}">
    <style>
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
        .chat-reply-btn {
            line-height: 1;
            opacity: 0.7;
        }
        .chat-message-bubble:hover .chat-reply-btn {
            opacity: 1;
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
                                            <a class="nav-link {{$type=='customer'?'active':''}}"
                                               href="{{url()->current()}}?user_type=customer">
                                                {{translate('customer')}}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{$type=='provider_serviceman'?'active':''}}"
                                               href="{{url()->current()}}?user_type=provider_serviceman">
                                                {{translate('Service Man')}}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link {{$type=='provider_admin'?'active':''}}"
                                               href="{{url()->current()}}?user_type=provider_admin">
                                                {{translate('Provider')}}
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
                                    <div class="inbox_chat staff-conversations-list d-flex flex-column mt-1">
                                        @if(!empty($staffGroupChannel))
                                            @include('chattingmodule::admin.partials._staff-group-conversation-list-item', [
                                                'staffGroupChannel' => $staffGroupChannel,
                                                'memberCount' => $staffGroupMemberCount ?? 0,
                                            ])
                                        @endif
                                        @forelse($chatList as $chat)
                                            @php($fromUser=$chat->channelUsers->where('user_id','!=',auth()->id())->first())
                                            @php($staffPresence = isset($fromUser->user) ? ($staffPresenceById[$fromUser->user->id] ?? null) : null)
                                            <div class="chat_list chat-list-class staff-conversation-item {{$chat->is_read==0?'active':''}}"
                                                 id="chat-{{$chat->id}}"
                                                 data-route="{{route('admin.chat.ajax-conversation',['channel_id'=>$chat->id,'offset'=>1])}}"
                                                 data-chat="{{$chat->id}}">
                                                <div class="chat_people media gap-10">
                                                    <div class="position-relative">
                                                        <img src="{{ $fromUser->user->profile_image_full_path ?? asset('assets/admin-module/img/media/user.png') }}"
                                                             class="avatar rounded-circle" alt="">
                                                        @if($staffPresence)
                                                            <span class="avatar-status {{ $presenceService->statusDotClass($staffPresence['presence_status']) }}"></span>
                                                        @endif
                                                    </div>
                                                    <div class="chat_ib media-body">
                                                        <h5 class="mb-0">{{ $fromUser->user ? trim($fromUser->user->first_name.' '.$fromUser->user->last_name) : translate('no_user_found') }}</h5>
                                                        @if($staffPresence)
                                                            <span class="fz-12 text-muted">{{ $staffPresence['presence_label'] }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if($chat->is_read==0)
                                                    <div class="bg-info text-white radius-50 px-1 fz-12" id="badge-{{$chat->id}}">
                                                        <span class="material-symbols-outlined">swipe_up</span>
                                                    </div>
                                                @endif
                                            </div>
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
                            <div class="inbox_chat d-flex flex-column mt-1">
                                @foreach($chatList as $chat)
                                    @php($fromUser=$chat->channelUsers->where('user_id','!=',auth()->id())->first())
                                    @php($staffPresence = ($type ?? '') === 'staff' && isset($fromUser->user) ? ($staffPresenceById[$fromUser->user->id] ?? null) : null)
                                    <div class="chat_list chat-list-class {{$chat->is_read==0?'active':''}}"
                                         id="chat-{{$chat->id}}"
                                         data-route="{{route('admin.chat.ajax-conversation',['channel_id'=>$chat->id,'offset'=>1])}}"
                                         data-chat="{{$chat->id}}">
                                        <div class="chat_people media gap-10" id="chat_people">
                                            <div class="position-relative">
                                                <img
                                                    @if(isset($fromUser->user) && in_array($fromUser->user->user_type, ADMIN_USER_TYPES))
                                                        src="{{$fromUser->user->profile_image_full_path}}"
                                                    @elseif(isset($fromUser->user) && $fromUser->user->user_type == 'customer')
                                                        src="{{$fromUser->user->profile_image_full_path}}"
                                                    @elseif(isset($fromUser->user) && $fromUser->user->user_type == 'provider-admin')
                                                        src="{{$fromUser->user->provider->logo_full_path}}"
                                                    @elseif(isset($fromUser->user) && $fromUser->user->user_type == 'provider-serviceman')
                                                        src="{{$fromUser->user->profile_image_full_path}}"
                                                    @else
                                                        src="{{onErrorImage(
                                                                'null',
                                                                asset('storage/app/public/serviceman/profile').'/',
                                                                asset('assets/admin-module/img/media/user.png') ,
                                                                'serviceman/profile/')}}"
                                                    @endif
                                                    class="avatar rounded-circle" alt="{{ translate('image') }}">
                                                @if($staffPresence)
                                                    <span class="avatar-status {{ app(\Modules\AdminModule\Services\StaffPresenceService::class)->statusDotClass($staffPresence['presence_status']) }}"></span>
                                                @else
                                                    <span class="avatar-status bg-success"></span>
                                                @endif
                                            </div>
                                            <div class="chat_ib media-body">
                                                <h5 class="">{{isset($fromUser->user) ? ($fromUser->user->provider ? $fromUser->user->provider->company_name : $fromUser->user->first_name . ' ' . $fromUser->user->last_name)  : translate('no_user_found')}}</h5>
                                                <span
                                                    class="fz-12">{{isset($fromUser->user) ? ($fromUser->user->provider ? $fromUser->user->provider->company_phone : $fromUser->user->phone) : ''}}</span>
                                            </div>
                                        </div>
                                        @if($chat->is_read==0)
                                            <div class="bg-info text-white radius-50 px-1 fz-12"
                                                 id="badge-{{$chat->id}}">
                                                <span class="material-symbols-outlined">swipe_up</span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-9 col-lg-8 mt-4 mt-lg-0">
                    <div class="card-header radius-10 mb-1 d-flex justify-content-end">
                        <button class="btn btn--primary" type="button" data-bs-toggle="modal"
                                data-bs-target="#modal-conversation-start">
                            <span class="material-icons">add</span>
                            {{translate('start_conversation')}}
                        </button>
                    </div>
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
                                    <option value="provider-serviceman">{{translate('serviceman')}}</option>
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

                            <div class="form-group mb-30 d--none" id="serviceman">
                                <select class="form-control chat-js-select" name="serviceman_id">
                                    <option value="" selected disabled>{{translate('Select_Serviceman')}}</option>
                                    @foreach($servicemen as $item)
                                        <option value="{{$item->id}}">
                                            {{$item->first_name}} {{$item->last_name}} ({{$item->phone}})
                                        </option>
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
            };
        </script>
        <script src="{{ asset('assets/chatting-module/js/staff-chat-compose.js') }}"></script>
    @endif
    <script src="{{ asset('assets/chatting-module/js/chat-reply.js') }}"></script>
    <script src="{{asset('assets/chatting-module/js/custom.js')}}"></script>

@endpush
