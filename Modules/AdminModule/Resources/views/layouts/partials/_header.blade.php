
@push('css_or_js')

    <style>
        /* Loader overlay */
        .search-loader-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            backdrop-filter: blur(4px); /* blur background */
            background-color: rgba(255, 255, 255, 0.6); /* semi-transparent */
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        /* Spinner */
        .loader-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .header-right {
            width: 100%;
            min-width: 0;
        }
        .header-right > .nav {
            flex-wrap: nowrap;
            width: 100%;
            margin-bottom: 0;
        }
        .header-right > .nav > .nav-item,
        .header-right > .nav > li {
            flex-shrink: 0;
        }
        .staff-header-status-pill {
            font-size: inherit;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
        }
        .staff-header-status-pill::after {
            margin-inline-start: 0.35rem;
        }
        .staff-header-status-pill .staff-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
            background: currentColor;
            opacity: 0.95;
        }
        .staff-presence-menu {
            min-width: 11rem;
        }
        .staff-presence-menu .dropdown-item.active {
            font-weight: 600;
        }

    </style>

@endpush
<header class="header fixed-top">
    <div class="container-fluid">
        <div class="row align-items-center justify-content-between">
            <div class="col-auto">
                <div class="header-toogle-menu">
                    <button class="toggle-menu-button aside-toggle border-0 bg-transparent p-0 dark-color">
                        <span class="material-icons">menu</span>
                    </button>
                </div>
            </div>
            <div class="col min-w-0">
                <div class="header-right">
                    @php
                        $headerPresenceService = app(\Modules\AdminModule\Services\StaffPresenceService::class);
                        $currentHeaderPresence = $headerPresenceService->resolveDisplayStatus(auth()->user());
                    @endphp
                    <ul class="nav justify-content-end align-items-center gap-3 gap-md-4">
                        @if(!is_admin_employee())
                        <li class="nav-item max-sm-m-0">
                            <a href="{{ route('admin.process-guides.index') }}"
                               class="title-color bg--secondary border-0 rounded align-items-center py-2 px-2 px-md-3 d-inline-flex gap-1 text-decoration-none"
                               @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif
                               title="{{ translate('Process_Guides') }}">
                                <span class="material-symbols-outlined" aria-hidden="true">menu_book</span>
                                <span class="d-none d-md-block">{{ translate('Process_Guides') }}</span>
                            </a>
                        </li>
                        <li class="nav-item max-sm-m-0">
                            <a href="{{ route('admin.task-board.index') }}"
                               class="title-color bg--secondary border-0 rounded align-items-center py-2 px-2 px-md-3 d-inline-flex gap-1 text-decoration-none position-relative"
                               @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif
                               title="{{ translate('Task_Board') }}">
                                <span class="material-symbols-outlined" aria-hidden="true">view_kanban</span>
                                <span class="d-none d-md-block">{{ translate('Task_Board') }}</span>
                                @php
                                    $taskBoardAssignedTotal = (int) (($taskBoardAssignedCounts['total'] ?? 0));
                                @endphp
                                @if($taskBoardAssignedTotal > 0)
                                    <span class="count d-flex">{{ $taskBoardAssignedTotal > 99 ? '99+' : $taskBoardAssignedTotal }}</span>
                                @endif
                            </a>
                        </li>
                        @endif
                        <li class="nav-item max-sm-m-0">
                            <div class="dropdown">
                                <button type="button"
                                        id="staff-header-status-pill"
                                        class="staff-header-status-pill dropdown-toggle border-0 rounded align-items-center py-2 px-2 px-md-3 d-inline-flex gap-1 {{ $headerPresenceService->statusPillClass($currentHeaderPresence) }}"
                                        data-bs-toggle="dropdown"
                                        data-bs-offset="0,12"
                                        data-presence-status="{{ $currentHeaderPresence }}"
                                        aria-expanded="false"
                                        title="{{ translate('Your_Status') }}">
                                    <span class="staff-status-dot"></span>
                                    <span id="staff-header-presence-label" class="d-none d-md-block">{{ $headerPresenceService->statusLabel($currentHeaderPresence) }}</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end staff-presence-menu py-2">
                                    <li class="px-3 pb-2 mb-1 border-bottom">
                                        <span class="small text-muted">{{ translate('Your_Status') }}</span>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 staff-presence-btn {{ $currentHeaderPresence === 'online' ? 'active' : '' }}" data-status="online">
                                            <span class="rounded-circle bg-success" style="width:8px;height:8px;"></span>
                                            {{ translate('Online') }}
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 staff-presence-btn {{ $currentHeaderPresence === 'away' ? 'active' : '' }}" data-status="away">
                                            <span class="rounded-circle bg-warning" style="width:8px;height:8px;"></span>
                                            {{ translate('Away') }}
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 staff-presence-btn {{ $currentHeaderPresence === 'on_break' ? 'active' : '' }}" data-status="on_break">
                                            <span class="rounded-circle bg-info" style="width:8px;height:8px;"></span>
                                            {{ translate('On_Break') }}
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        @if(is_super_admin())
                        <li class="nav-item max-sm-m-0">
                            <a href="{{ route('admin.settings.home-cache') }}" class="title-color bg--secondary border-0 rounded align-items-center py-2 px-2 px-md-3 d-inline-flex gap-1 text-decoration-none">
                                <span class="material-symbols-outlined" aria-hidden="true">cached</span>
                                <span class="d-none d-sm-inline">{{ translate('Reset_home_cache') }}</span>
                            </a>
                        </li>
                        @endif
                        <li class="nav-item max-sm-m-0">
                            <a href="{{ route('admin.business-ai.index') }}" class="btn btn--success border-0 rounded align-items-center py-2 px-2 px-md-3 d-inline-flex gap-1 text-decoration-none">
                                <span class="material-symbols-outlined" aria-hidden="true">psychology</span>
                                <span class="d-none d-md-block">{{ translate('Talk_With_AI') }}</span>
                            </a>
                        </li>
                        @can('booking_view')
                        <li class="nav-item max-sm-m-0">
                            <a href="{{ route('admin.booking.create') }}" class="title-color bg--secondary border-0 rounded align-items-center py-2 px-2 px-md-3 d-inline-flex gap-1 text-decoration-none">
                                <span class="material-symbols-outlined" aria-hidden="true">add_circle</span>
                                <span class="d-none d-md-block">{{ translate('Add_New_Booking') }}</span>
                            </a>
                        </li>
                        @endcan
                        <li class="nav-item max-sm-m-0">
                            <button type="button" id="modalOpener" class="title-color bg--secondary border-0 rounded align-items-center py-2 px-2 px-md-3 d-flex gap-1" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                                <span class="material-symbols-outlined">search</span>
                                <span class="d-none d-md-block">{{translate('Search')}}</span>
                                <span class="bg-card text-muted border rounded-3 p-1 fs-12 fw-bold lh-1 ms-1 ctrlplusk d-none d-md-block">Ctrl+K</span>
                            </button>
                        </li>
                        <li class="nav-item max-sm-m-0">
                            <div class="messages pe--12">
                                <a href="{{ route('admin.chat.staff') }}"
                                   class="header-icon count-btn"
                                   data-bs-toggle="tooltip"
                                   data-bs-placement="bottom"
                                   title="{{ translate('Staff_Conversation') }}"
                                   aria-label="{{ translate('Staff_Conversation') }}">
                                    <span class="material-symbols-outlined">chat</span>
                                    <span class="count" id="staff_message_count" style="display:{{ ($staffUnreadCount ?? 0) > 0 ? 'flex' : 'none' }};">{{ ($staffUnreadCount ?? 0) > 0 ? $staffUnreadCount : '' }}</span>
                                </a>
                            </div>
                        </li>
                        <li class="nav-item max-sm-m-0">
                            <div class="messages pe--12">
                                <a href="{{ route('admin.chat.support', ['filter' => 'all']) }}"
                                   class="header-icon count-btn"
                                   data-bs-toggle="tooltip"
                                   data-bs-placement="bottom"
                                   title="{{ translate('Support_Messages') }}"
                                   aria-label="{{ translate('Support_Messages') }}">
                                    <span class="material-symbols-outlined">support_agent</span>
                                    @include('adminmodule::layouts.partials._header-unread-badge', ['id' => 'support_message_count', 'count' => $supportUnreadCount ?? 0])
                                </a>
                            </div>
                        </li>
                        @can('whatsapp_chat_view')
                        <li class="nav-item max-sm-m-0">
                            <div class="whatsapp-header-messages pe--12">
                                <a href="{{ route('admin.whatsapp.conversations.index', ['channel' => 'whatsapp', 'tab' => 'chats']) }}"
                                   class="header-icon count-btn wa-header-icon-link"
                                   data-bs-toggle="tooltip"
                                   data-bs-placement="bottom"
                                   title="{{ translate('WhatsApp') }}"
                                   aria-label="{{ translate('WhatsApp') }}">
                                    <span class="wa-header-whatsapp-icon d-inline-flex align-items-center justify-content-center" style="color: #25D366;" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.881 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                        </svg>
                                    </span>
                                    @include('adminmodule::layouts.partials._header-unread-badge', ['id' => 'whatsapp_unread_count', 'count' => $whatsappUnreadCount ?? 0, 'alwaysShowNumber' => true])
                                </a>
                            </div>
                        </li>
                        @endcan
                        <li class="nav-item max-sm-m-0">
                            @include('adminmodule::layouts.partials._notification-dropdown', [
                                'category' => \Modules\AdminModule\Entities\UserNotification::CATEGORY_EXTERNAL,
                            ])
                        </li>
                        <li class="nav-item max-sm-m-0">
                            @include('adminmodule::layouts.partials._notification-dropdown', [
                                'category' => \Modules\AdminModule\Entities\UserNotification::CATEGORY_INTERNAL,
                            ])
                        </li>
                        <li class="nav-item max-sm-m-0">
                            <div class="user mt-n1">
                                <a href="#" class="header-icon user-icon" data-bs-toggle="dropdown">
                                    <img width="30" height="30"
                                         src="{{auth()->user()->profile_image_full_path}}"

                                         class="rounded-circle aspect-square object-fit-cover" alt="{{ translate('profile_image') }}">
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="{{route('admin.profile_update')}}"
                                       class="dropdown-item-text media gap-3 align-items-center">
                                        <div class="avatar">
                                            <img class="avatar-img rounded-circle aspect-square object-fit-cover" width="50" height="50"
                                                 src="{{auth()->user()->profile_image_full_path}}"
                                                 alt="{{ translate('profile-image') }}">
                                        </div>
                                        <div class="media-body ">
                                            <h5 class="card-title">{{ Str::limit(auth()->user()?->first_name, 20) }}</h5>
                                            <span class="card-text">{{ Str::limit(auth()->user()?->email, 20) }}</span>
                                        </div>
                                    </a>
                                    <a class="dropdown-item" href="{{route('admin.profile_update')}}">
                                        <span class="text-truncate" title="{{translate('Settings')}}">{{translate('Settings')}}</span>
                                    </a>
                                    <a class="dropdown-item admin-logout">
                                        <span class="text-truncate cursor-pointer" title="{{translate('Sign Out')}}">{{translate('Sign_Out')}}</span>
                                    </a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>

@include('adminmodule::layouts.partials._search-modal')
