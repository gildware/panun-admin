@php
    $logoPlaceholder = admin_nav_placeholder('logo');
    $logo = admin_nav_image_src(
        getBusinessSettingsImageFullPath(key: 'business_logo', settingType: 'business_information', path: 'business/', defaultPath: 'assets/admin-module/img/placeholder.png'),
        'logo'
    );
    $profilePlaceholder = admin_nav_placeholder('profile');
    $profileImage = admin_nav_image_src(auth()->user()?->profile_image_full_path, 'profile');
    $profileName = trim((auth()->user()?->first_name ?? '') . ' ' . (auth()->user()?->last_name ?? ''));
    if ($profileName === '') {
        $profileName = auth()->user()?->email ?? translate('profile');
    }
    $headerPresenceService = app(\Modules\AdminModule\Services\StaffPresenceService::class);
    $currentHeaderPresence = $headerPresenceService->resolveDisplayStatus(auth()->user());
@endphp

<div class="top-chrome">
    <div class="top-utility-bar">
        <div class="top-utility-start">
            <a href="{{ route('admin.dashboard') }}" class="top-utility-brand">
                <img class="top-utility-brand-logo js-nav-img-fallback"
                     src="{{ $logo }}"
                     data-fallback="{{ $logoPlaceholder }}"
                     onerror="this.onerror=null;this.src=this.dataset.fallback||'{{ $logoPlaceholder }}'"
                     alt="{{ translate('image') }}">
                <span class="d-none d-sm-inline">Panun Kaergar Admin</span>
            </a>
        </div>
        <div class="top-utility-end">
            <div class="dropdown top-utility-item top-utility-presence">
                <button type="button"
                        id="staff-header-status-pill"
                        class="staff-header-status-pill staff-header-status-pill--utility dropdown-toggle border-0 rounded align-items-center d-inline-flex gap-1"
                        data-bs-toggle="dropdown"
                        data-bs-offset="0,6"
                        data-bs-popper-config='{"strategy":"fixed"}'
                        data-presence-status="{{ $currentHeaderPresence }}"
                        aria-expanded="false"
                        title="{{ translate('Your_Status') }}">
                    <span class="staff-status-dot"></span>
                    <span id="staff-header-presence-label" class="d-none d-lg-inline">{{ $headerPresenceService->statusLabel($currentHeaderPresence) }}</span>
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

            <button type="button" id="modalOpener" class="top-utility-action-btn top-utility-search-btn" data-bs-toggle="modal" data-bs-target="#staticBackdrop" title="{{ translate('Search') }} (Ctrl+K)">
                <span class="material-symbols-outlined">search</span>
                <span class="top-utility-search-label">{{ translate('Search') }}</span>
                <span class="top-utility-search-kbd d-none d-md-inline">Ctrl+K</span>
            </button>

            <a href="{{ route('admin.chat.index', ['user_type' => 'staff']) }}"
               class="top-utility-icon-btn"
               @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif
               data-bs-toggle="tooltip"
               data-bs-placement="bottom"
               title="{{ translate('Staff_Conversation') }}"
               aria-label="{{ translate('Staff_Conversation') }}">
                <span class="material-symbols-outlined">chat</span>
                <span class="count" id="staff_message_count" @if(($staffUnreadCount ?? 0) < 1) style="display:none;" @endif>{{ $staffUnreadCount ?? 0 }}</span>
            </a>

            @can('whatsapp_chat_view')
                <a href="{{ route('admin.whatsapp.conversations.index', ['channel' => 'whatsapp', 'tab' => 'chats']) }}"
                   class="top-utility-icon-btn"
                   @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif
                   data-bs-toggle="tooltip"
                   data-bs-placement="bottom"
                   title="{{ translate('WhatsApp') }}"
                   aria-label="{{ translate('WhatsApp') }}">
                    <span class="wa-header-whatsapp-icon d-inline-flex align-items-center justify-content-center" style="color: #25D366;" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.881 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </span>
                    <span class="count" id="whatsapp_unread_count">0</span>
                </a>
            @endcan

            <div class="dropdown top-utility-item">
                <button type="button"
                        class="top-utility-icon-btn notification-icon"
                        data-bs-toggle="dropdown"
                        data-bs-offset="0,6"
                        data-bs-popper-config='{"strategy":"fixed"}'
                        title="{{ translate('Notifications') }}"
                        aria-label="{{ translate('Notifications') }}">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="count" id="notification_count" style="display:none;">0</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width:22rem;max-width:26rem;">
                    <div class="show-notification-list" id="show-notification-list" style="max-height:24rem;overflow-y:auto;"></div>
                    <div class="border-top py-2 px-3 text-center bg-white">
                        <a href="{{ route('admin.notifications.index') }}"
                           class="btn btn-sm btn-link text-decoration-none fw-semibold js-view-all-notifications"
                           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                            {{ translate('view_all') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="dropdown top-utility-user-wrap">
                <button type="button"
                        class="top-utility-profile-btn dropdown-toggle border-0"
                        data-bs-toggle="dropdown"
                        data-bs-offset="0,6"
                        data-bs-popper-config='{"strategy":"fixed"}'
                        aria-expanded="false"
                        title="{{ $profileName }}">
                    <span class="top-utility-profile-avatar">
                        <img src="{{ $profileImage }}"
                             data-fallback="{{ $profilePlaceholder }}"
                             onerror="this.onerror=null;this.src=this.dataset.fallback||'{{ $profilePlaceholder }}'"
                             class="js-nav-img-fallback"
                             alt="{{ translate('profile_image') }}">
                    </span>
                    <span class="top-utility-profile-name d-none d-md-inline">{{ Str::limit($profileName, 20) }}</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a href="{{ route('admin.profile_update') }}" class="dropdown-item-text media gap-3 align-items-center text-decoration-none"
                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                        <div class="avatar">
                            <img class="avatar-img rounded-circle aspect-square object-fit-cover js-nav-img-fallback" width="50" height="50"
                                 src="{{ $profileImage }}"
                                 data-fallback="{{ $profilePlaceholder }}"
                                 onerror="this.onerror=null;this.src=this.dataset.fallback||'{{ $profilePlaceholder }}'"
                                 alt="{{ translate('profile-image') }}">
                        </div>
                        <div class="media-body">
                            <h5 class="card-title mb-0">{{ Str::limit(auth()->user()?->first_name, 20) }}</h5>
                            <span class="card-text">{{ Str::limit(auth()->user()?->email, 20) }}</span>
                        </div>
                    </a>
                    <a class="dropdown-item" href="{{ route('admin.profile_update') }}"
                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                        <span class="text-truncate" title="{{ translate('Settings') }}">{{ translate('Settings') }}</span>
                    </a>
                    <a class="dropdown-item admin-logout" data-turbo="false">
                        <span class="text-truncate cursor-pointer" title="{{ translate('Sign Out') }}">{{ translate('Sign_Out') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <nav class="top-nav-bar">
        <div class="top-nav-shell">
            @include('adminmodule::layouts.partials._top-nav-menu')
        </div>
    </nav>

    @include('adminmodule::layouts.partials._top-pinned')

    @include('adminmodule::layouts.partials._top-group-subnav')
</div>

@include('adminmodule::layouts.partials._search-modal')

@push('css_or_js')
    <style>
        .search-loader-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            backdrop-filter: blur(4px);
            background-color: rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
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
        .staff-presence-menu { min-width: 11rem; }
        .staff-presence-menu .dropdown-item.active { font-weight: 600; }
    </style>
@endpush
