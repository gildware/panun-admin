<!DOCTYPE html>
@php
    $site_direction = session()->get('site_direction');
    $adminUsesTopNav = admin_uses_top_nav();
    $adminUsesPartialNav = admin_uses_partial_nav();
    $adminAssetVersion = max(
        (int) @filemtime(public_path('assets/admin-module/css/style.css')),
        (int) @filemtime(public_path('assets/admin-module/css/dev.css')),
        (int) @filemtime(public_path('assets/admin-module/js/custom.js')),
        (int) @filemtime(public_path('assets/admin-module/css/top-nav.css')),
        (int) @filemtime(public_path('assets/admin-module/js/top-nav.js')),
        (int) @filemtime(public_path('assets/admin-module/js/admin-partial-nav.js')),
        (int) @filemtime(public_path('assets/admin-module/js/admin-image-fallback.js')),
        (int) @filemtime(public_path('assets/admin-module/js/admin-global-search.js')),
        (int) @filemtime(public_path('assets/admin-module/js/bootstrap-jquery-modal-bridge.js')),
        2026080327,
    ) ?: time();
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{$site_direction}}">

<head>
    <title>@yield('title')</title>

    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="description" content=""/>
    <meta name="keywords" content=""/>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($favIcon = getBusinessSettingsImageFullPath(key: 'business_favicon', settingType: 'business_information', path: 'business/',  defaultPath : 'assets/placeholder.png'))
    <link rel="shortcut icon" href="{{ $favIcon }}"/>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">


    <link href="{{asset('assets/admin-module')}}/css/material-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/bootstrap.min.css"/>
    <link rel="stylesheet"
          href="{{asset('assets/admin-module')}}/plugins/perfect-scrollbar/perfect-scrollbar.min.css"/>


    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/apex/apexcharts.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>

    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/toastr.css">

    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/style.css?v={{$adminAssetVersion}}"/>
    <link rel="stylesheet" href="{{ asset('assets/chatting-module/css/staff-chat-entity-badges.css') }}?v={{ @filemtime(public_path('assets/chatting-module/css/staff-chat-entity-badges.css')) ?: time() }}"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/dev.css?v={{$adminAssetVersion}}"/>
    @if($adminUsesTopNav)
        <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/top-nav.css?v={{$adminAssetVersion}}"/>
        <style>
            /* Inline: pin/unpin must show one label only (avoids stale CDN CSS cache on live). */
            #top-chrome-mode-toggle .top-chrome-mode-option--unpin { display: none !important; }
            body:not(.top-chrome-auto-hide) #top-chrome-mode-toggle .top-chrome-mode-option--pin { display: none !important; }
            body:not(.top-chrome-auto-hide) #top-chrome-mode-toggle .top-chrome-mode-option--unpin { display: inline-flex !important; }
            body.top-chrome-auto-hide #top-chrome-mode-toggle .top-chrome-mode-option--pin { display: inline-flex !important; }
            body.top-chrome-auto-hide #top-chrome-mode-toggle .top-chrome-mode-option--unpin { display: none !important; }
        </style>
    @endif
    @if($adminUsesPartialNav)
        <style>
            html:not(.admin-shell-ready) body .main-area {
                opacity: 0 !important;
                pointer-events: none;
            }
            turbo-frame#admin-main.admin-main-frame--loading,
            #admin-main.admin-main-frame--loading { visibility: hidden; }
        </style>
        <script>
            if (sessionStorage.getItem('admin_shell_ready') === '1') {
                document.documentElement.classList.add('admin-skip-preloader');
            }
        </script>
    @endif
    <link rel="stylesheet" href="{{asset('assets/common')}}/css/common.css"/>
    <link rel="stylesheet" href="{{asset('assets/common')}}/plugins/cropperjs/cropper.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/common')}}/css/image-crop-upload.css?v={{ @filemtime(public_path('assets/common/css/image-crop-upload.css')) ?: time() }}"/>
    <link rel="stylesheet" href="{{asset('assets/provider-module')}}/css/view-guideline.css"/>

    @stack('css_or_js')
</head>

<body class="{{ $adminUsesTopNav ? 'nav-top' : '' }}"
      data-admin-img-placeholder="{{ admin_nav_placeholder() }}"
      data-admin-profile-placeholder="{{ admin_nav_placeholder('profile') }}"
      data-admin-logo-placeholder="{{ admin_nav_placeholder('logo') }}"
      @if($adminUsesPartialNav) data-partial-nav="1"@endif>
<script>
    localStorage.theme && document.querySelector('body').setAttribute("data-bs-theme", localStorage.theme);
    (function () {
        if (!document.body.classList.contains('nav-top')) {
            return;
        }
        if (localStorage.getItem('admin_top_chrome_mode') === 'auto-hide') {
            document.body.classList.add('top-chrome-auto-hide');
        }
    })();
</script>

<div class="offcanvas-overlay"></div>


<div class="preloader"></div>

@if($adminUsesPartialNav)
    <div id="admin-partial-progress" class="admin-partial-progress" aria-hidden="true"></div>
@endif

@include('adminmodule::layouts.partials._nav-layout')


@include('adminmodule::layouts.partials._settings-sidebar')

@if($adminUsesPartialNav)
    @include('adminmodule::layouts.partials._core-js')
@endif

<main class="main-area">
    @if($adminUsesPartialNav)
        <turbo-frame id="admin-main" class="admin-main-frame admin-main-frame--loading" data-turbo-cache="false" aria-busy="true">
    @endif

    @if(admin_in_settings_module() && ! request()->routeIs('admin.settings.index', 'admin.settings.home-cache'))
        <div class="settings-module settings-module--embedded">
            @include('adminmodule::settings.partials._sidebar')
            <div class="settings-module-main settings-module-main--embedded">
                @yield('content')
            </div>
        </div>
    @elseif(admin_in_marketing_module() && ! request()->routeIs('admin.marketing.index'))
        <div class="settings-module settings-module--embedded">
            @include('adminmodule::marketing.partials._sidebar')
            <div class="settings-module-main settings-module-main--embedded">
                @yield('content')
            </div>
        </div>
    @elseif(admin_in_reports_module() && ! request()->routeIs('admin.reports.index'))
        <div class="settings-module settings-module--embedded">
            @include('adminmodule::reports.partials._sidebar')
            <div class="settings-module-main settings-module-main--embedded">
                @yield('content')
            </div>
        </div>
    @else
        @yield('content')
    @endif

    @include('adminmodule::layouts.partials._footer')

    @if(env('APP_ENV') == 'demo')
        <div class="alert alert--message-2 alert-dismissible fade show" id="demo-reset-warning">
            <img width="28" class="align-self-start" src="{{ asset('assets/admin-module/img/info-2.png') }}" alt="">
            <div class="w-0 flex-grow-1">
                <h6>{{ translate('warning').'!'}}</h6>
                <span class="warning-message">
            {{translate('though_it_is_a_demo_site').'.'.translate('_our_system_automatically_reset_after_one_hour_&_that_is_why_you_logged_out').'.'}}
        </span>
            </div>
            <button type="button" class="btn-close p-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @include('adminmodule::layouts.partials._status-modal')

    @include('adminmodule::layouts.partials._notification-detail-modal')

    @if($adminUsesPartialNav)
            @stack('script')
        </turbo-frame>
    @endif
</main>


@if(!$adminUsesPartialNav)
<script src="{{asset('assets/admin-module')}}/js/jquery-3.6.0.min.js"></script>
<script src="{{asset('assets/admin-module')}}/js/bootstrap.bundle.min.js"></script>
<script src="{{asset('assets/admin-module')}}/js/bootstrap-jquery-modal-bridge.js?v={{$adminAssetVersion}}"></script>
@endif
<script src="{{asset('assets/common')}}/plugins/cropperjs/cropper.min.js"></script>
<script src="{{asset('assets/common')}}/js/image-crop-upload.js?v={{ @filemtime(public_path('assets/common/js/image-crop-upload.js')) ?: time() }}"></script>
<script src="{{asset('assets/admin-module')}}/plugins/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="{{asset('assets/admin-module')}}/js/main.js"></script>
<script src="{{asset('assets/admin-module')}}/js/admin-global-search.js?v={{$adminAssetVersion}}"></script>
<script src="{{asset('assets/admin-module')}}/js/custom.js?v={{$adminAssetVersion}}"></script>
<script src="{{asset('assets/admin-module')}}/js/admin-image-fallback.js?v={{$adminAssetVersion}}"></script>
@if($adminUsesTopNav)
    <script src="{{asset('assets/admin-module')}}/js/top-nav.js?v={{$adminAssetVersion}}"></script>
@endif
@if($adminUsesPartialNav)
    <script src="{{asset('assets/admin-module')}}/js/admin-partial-nav.js?v={{$adminAssetVersion}}"></script>
    <script>
        (function () {
            function revealAdminShellFallback() {
                document.documentElement.classList.add('admin-shell-ready');
                var frame = document.getElementById('admin-main');
                if (frame) {
                    frame.classList.remove('admin-main-frame--loading');
                    frame.setAttribute('aria-busy', 'false');
                }
            }

            window.setTimeout(function () {
                if (!document.documentElement.classList.contains('admin-shell-ready')) {
                    revealAdminShellFallback();
                }
            }, 3000);
        })();
    </script>
@endif
<script src="{{asset('assets/admin-module')}}/js/helper.js"></script>
<script src="{{asset('assets/common')}}/js/common.js"></script>
<script src="{{asset('assets/common')}}/js/form-submit-once.js"></script>

@if(!$adminUsesPartialNav)
<script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
@endif
<script src="{{asset('assets/admin-module')}}/js/sweet_alert.js"></script>
<script src="{{asset('assets/admin-module')}}/js/toastr.js"></script>
<script src="{{asset('assets/admin-module')}}/js/dev.js"></script>
<script src="{{asset('assets/admin-module')}}/js/keyword-highlight.js"></script>

{{--country code --}}
<span class="system-default-country-code" data-value="in" data-initial-country="in"></span>
<link rel="stylesheet" href="{{asset('assets/libs/intl-tel-input/css/intlTelInput.css')}}"/>
<script src="{{ asset('assets/libs/intl-tel-input/js/intlTelInput.js') }}"></script>
<script src="{{ asset('assets/libs/intl-tel-input/js/utils.js') }}"></script>
<script src="{{ asset('assets/libs/intl-tel-input/js/intlTelInout-validation.js') }}"></script>

<script src="{{ asset('assets/common/js/file-size-type-validation.js') }}"></script>
<script src="{{asset('assets/common')}}/js/common-image-upload.js?v={{ @filemtime(public_path('assets/common/js/common-image-upload.js')) ?: time() }}"></script>
<script src="{{ asset('assets/provider-module/js/multiple-image-upload.js') }}"></script>

{!! str_replace('<script type="text/javascript">', '<script type="text/javascript" data-admin-flash-toasts="1">', Toastr::message()) !!}

@if ($errors->any())
<script data-admin-flash-toasts="1">
    @foreach($errors->all() as $error)
    toastr.error(@json($error), @json(translate('error')), {
        CloseButton: true,
        ProgressBar: true
    });
    @endforeach
</script>
@endif

<audio id="audio-element">
    <source src="{{asset('assets/provider-module')}}/sound/notification.mp3" type="audio/mpeg">
</audio>

<script>
    "use strict";
    $(document).ready(function () {
        if (typeof window.initAdminPageSelect2 === 'function') {
            window.initAdminPageSelect2(document);
        } else if ($.fn.select2) {
            $('.js-select').each(function () {
                var $el = $(this);
                if (!$el.hasClass('select2-hidden-accessible')) {
                    $el.select2();
                }
            });
        }
    });

    function checkDemoResetTime() {
        let currentMinute = new Date().getMinutes();
        if (currentMinute > 55 && currentMinute <= 60) {
            $('#demo-reset-warning').addClass('active');
        } else {
            $('#demo-reset-warning').removeClass('active');
        }
    }
    checkDemoResetTime();
    setInterval(checkDemoResetTime, 60000);

    $(document).on('click', '.form-alert', function (){
        let id = $(this).data('id');
        let message = $(this).data('message');
        form_alert(id, message)
    });

    function form_alert(id, message) {
        Swal.fire({
            title: "{{translate('are_you_sure')}}?",
            text: message,
            type: 'warning',
            showCloseButton: true,
            showCancelButton: true,
            cancelButtonColor: 'var(--bs-secondary)',
            confirmButtonColor: 'var(--bs-primary)',
            cancelButtonText: 'Cancel',
            confirmButtonText: 'Yes',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                $('#' + id).submit()
            }
        })
    }

    $(document).on('change', '.route-alert', function (event){
        event.preventDefault();
        let $this = $(this);
        let initialState = $this.prop('checked'); // Save initial state

        let route = $(this).data('route');
        let message = $(this).data('message');

        route_alert(route, message, $this, initialState)
    });

    function route_alert(route, message, $this = false, initialState = false) {
        Swal.fire({
            title: "{{translate('are_you_sure')}}?",
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'var(--bs-secondary)',
            confirmButtonColor: 'var(--bs-primary)',
            cancelButtonText: 'Cancel',
            confirmButtonText: 'Yes',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                $.get({
                    url: route,
                    dataType: 'json',
                    success: function (data) {
                        toastr.success(data.message, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    },
                });
            }else{
                $this.prop('checked', !initialState);
            }
        })
    }

    $(document).on('click', '.route-alert-reload', function (){
        let route = $(this).data('route');
        let message = $(this).data('message');
        route_alert_reload(route, message, true);
    });

    function route_alert_reload(route, message, reload, status = null, id = null) {
        Swal.fire({
            title: "{{translate('are_you_sure')}}?",
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'var(--bs-secondary)',
            confirmButtonColor: 'var(--bs-primary)',
            cancelButtonText: 'Cancel',
            confirmButtonText: 'Yes',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                $.get({
                    url: route,
                    dataType: 'json',
                    data: {},
                    beforeSend: function () {

                    },
                    success: function (data) {
                        if (reload) {
                            setTimeout(location.reload.bind(location), 1000);
                        }
                        toastr.success(data.message, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    },
                    complete: function () {

                    },
                });
            }else {
                if (status === 1) $(`#${id}`).prop('checked', false);
                if (status === 0) $(`#${id}`).prop('checked', true);
            }
        })
    }

    var audio = document.getElementById("audio-element");

    function playAudio(status) {
        status ? audio.play() : audio.pause();
    }

    // Distinct staff-chat notification chime (Web Audio, different from the WhatsApp mp3).
    (function () {
        var staffAudioCtx = null;

        function ensureStaffAudioCtx() {
            if (!staffAudioCtx) {
                var Ctx = window.AudioContext || window.webkitAudioContext;
                if (Ctx) {
                    staffAudioCtx = new Ctx();
                }
            }
            if (staffAudioCtx && staffAudioCtx.state === 'suspended') {
                staffAudioCtx.resume();
            }
            return staffAudioCtx;
        }

        // Browsers block audio until the user interacts with the page; unlock on first gesture.
        ['click', 'keydown', 'touchstart'].forEach(function (evt) {
            document.addEventListener(evt, ensureStaffAudioCtx, { once: true, passive: true });
        });

        window.pkPlayStaffNotificationSound = function () {
            var ctx = ensureStaffAudioCtx();
            if (!ctx) return;
            var now = ctx.currentTime;
            var tones = [
                { freq: 660, start: 0,    dur: 0.16 },
                { freq: 990, start: 0.13, dur: 0.24 }
            ];
            tones.forEach(function (t) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(t.freq, now + t.start);
                gain.gain.setValueAtTime(0.0001, now + t.start);
                gain.gain.exponentialRampToValueAtTime(0.28, now + t.start + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + t.start + t.dur);
                osc.connect(gain).connect(ctx.destination);
                osc.start(now + t.start);
                osc.stop(now + t.start + t.dur + 0.02);
            });
        };
    })();

    @include('adminmodule::layouts.partials._header-unread-badge-scripts')

    function handleAdminUpdatedDataResponse(response, opts) {
        opts = opts || {};
        var skipSound = !!opts.skipSound;
        let data = response && response.data ? response.data : null;
        if (!data) {
            return;
        }
        var staffCountEl = document.getElementById("staff_message_count");
        if (staffCountEl) {
            var staffMsgCount = parseInt(data.staff_unread_messages, 10);
            if (isNaN(staffMsgCount)) staffMsgCount = 0;
            staffCountEl.innerHTML = staffMsgCount > 0 ? staffMsgCount : '';
            staffCountEl.style.display = staffMsgCount > 0 ? 'flex' : 'none';

            var staffPrevKey = 'admin_staff_unread_messages';
            var staffPrevRaw = sessionStorage.getItem(staffPrevKey);
            if (!skipSound && staffPrevRaw !== null && staffPrevRaw !== '') {
                var staffPrev = parseInt(staffPrevRaw, 10) || 0;
                if (staffMsgCount > staffPrev && typeof window.pkPlayStaffNotificationSound === 'function') {
                    window.pkPlayStaffNotificationSound();
                }
            }
            sessionStorage.setItem(staffPrevKey, String(staffMsgCount));
        }

        var supportCountEl = document.getElementById("support_message_count");
        if (supportCountEl) {
            var supportMsgCount = parseInt(data.customer_provider_unread_messages, 10);
            if (isNaN(supportMsgCount)) supportMsgCount = 0;
            if (typeof window.pkUpdateHeaderUnreadBadge === 'function') {
                window.pkUpdateHeaderUnreadBadge(supportCountEl, supportMsgCount);
            }

            var supportPrevKey = 'admin_support_unread_messages';
            var supportPrevRaw = sessionStorage.getItem(supportPrevKey);
            if (!skipSound && supportPrevRaw !== null && supportPrevRaw !== '') {
                var supportPrev = parseInt(supportPrevRaw, 10) || 0;
                if (supportMsgCount > supportPrev && typeof window.pkPlayStaffNotificationSound === 'function') {
                    window.pkPlayStaffNotificationSound();
                }
            }
            sessionStorage.setItem(supportPrevKey, String(supportMsgCount));
        }

        if (data.presence_label) {
            window.pkUpdateStaffPresenceUI(data);
        }

        var waCountEl = document.getElementById("whatsapp_unread_count");
        if (waCountEl) {
            var waUnread = parseInt(data.whatsapp_unread_chats, 10);
            if (isNaN(waUnread)) waUnread = 0;
            if (typeof window.pkUpdateWhatsAppHeaderBadge === 'function') {
                window.pkUpdateWhatsAppHeaderBadge(waCountEl, waUnread);
            } else if (typeof window.pkUpdateHeaderUnreadBadge === 'function') {
                window.pkUpdateHeaderUnreadBadge(waCountEl, waUnread);
            }

            var msgTotal = parseInt(data.whatsapp_unread_messages, 10);
            if (isNaN(msgTotal)) msgTotal = 0;
            var waPrevKey = 'admin_whatsapp_unread_messages';
            var waPrevRaw = sessionStorage.getItem(waPrevKey);
            if (!skipSound && waPrevRaw !== null && waPrevRaw !== '') {
                var waPrev = parseInt(waPrevRaw, 10) || 0;
                if (msgTotal > waPrev && audio) {
                    audio.play().catch(function () {});
                }
            }
            sessionStorage.setItem(waPrevKey, String(msgTotal));
        }

        if (typeof window.pkHandleAdminInboxNotifications === 'function') {
            window.pkHandleAdminInboxNotifications(data, opts);
        }
    }

    window.pkAdminRefreshWhatsAppUnread = function (opts) {
        $.get({
            url: '{{ route('admin.get_updated_data') }}',
            dataType: 'json',
            success: function (response) {
                handleAdminUpdatedDataResponse(response, opts || {});
            },
        });
    };

    $(function () {
        $.get({
            url: '{{ route('admin.get_updated_data') }}',
            dataType: 'json',
            success: function (response) {
                handleAdminUpdatedDataResponse(response, { skipSound: true });
            },
        });
    });

    (function () {
        // Keep polling light: Hostinger shared PHP chokes when every open admin tab hits every 10s.
        var adminHeaderPollMs = 45000;
        try {
            if (/\/admin\/(whatsapp|social-inbox)\//i.test(window.location.pathname || '')) {
                adminHeaderPollMs = 60000;
            }
        } catch (e) {}
        function pollHeader() {
            if (document.hidden) {
                return;
            }
            $.get({
                url: '{{ route('admin.get_updated_data') }}',
                dataType: 'json',
                success: handleAdminUpdatedDataResponse,
            });
        }
        setInterval(pollHeader, adminHeaderPollMs);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                pollHeader();
            }
        });
    })();

    window.pkStaffPresencePillClass = function (status) {
        return ({
            online: 'bg-success text-white',
            away: 'bg-warning text-dark',
            on_break: 'bg-info text-dark',
            offline: 'bg-secondary text-white',
        })[status] || 'bg-secondary text-white';
    };

    window.pkUpdateStaffPresenceUI = function (data) {
        if (!data || !data.presence_label) return;
        var labelEl = document.getElementById('staff-header-presence-label');
        if (labelEl) {
            labelEl.textContent = data.presence_label;
        }
        var pillEl = document.getElementById('staff-header-status-pill');
        if (pillEl) {
            var isUtility = document.body.classList.contains('nav-top');
            if (isUtility) {
                pillEl.className = 'staff-header-status-pill staff-header-status-pill--utility dropdown-toggle border-0 rounded align-items-center d-inline-flex gap-1';
                pillEl.setAttribute('data-presence-status', data.presence_status || '');
                if (labelEl) {
                    labelEl.classList.add('d-none', 'd-lg-inline');
                }
            } else {
                pillEl.className = 'staff-header-status-pill dropdown-toggle border-0 rounded align-items-center py-2 px-2 px-md-3 d-inline-flex gap-1 ' + window.pkStaffPresencePillClass(data.presence_status);
                pillEl.setAttribute('data-presence-status', data.presence_status || '');
                if (labelEl) {
                    labelEl.classList.add('d-none', 'd-md-block');
                }
            }
        }
        document.querySelectorAll('.staff-presence-btn').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-status') === data.presence_status);
        });
    };

    (function () {
        var heartbeatMs = 60000;
        function pkCurrentAdminPageLabel() {
            var title = (document.title || '').replace(/\s*[|\-–].*$/, '').trim();
            return title || window.location.pathname || '';
        }
        function sendStaffHeartbeat() {
            if (document.hidden) {
                return;
            }
            $.ajax({
                url: '{{ route('admin.staff-presence.heartbeat') }}',
                type: 'POST',
                dataType: 'json',
                data: { page: pkCurrentAdminPageLabel() },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            });
        }
        sendStaffHeartbeat();
        setInterval(sendStaffHeartbeat, heartbeatMs);

        $(document).on('click', '.staff-presence-btn', function (e) {
            e.preventDefault();
            var status = $(this).data('status');
            var $btn = $(this);
            $.ajax({
                url: '{{ route('admin.staff-presence.status') }}',
                type: 'POST',
                dataType: 'json',
                data: { status: status },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    if (response.data) {
                        window.pkUpdateStaffPresenceUI(response.data);
                    }
                    var $dropdown = $btn.closest('.dropdown');
                    if ($dropdown.length) {
                        var toggle = $dropdown.find('[data-bs-toggle="dropdown"]')[0];
                        if (toggle && window.bootstrap && bootstrap.Dropdown) {
                            bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
                        }
                    }
                },
            });
        });
    })();


    $("#search-form__input").on("keyup", function () {
        var value = this.value.toLowerCase().trim();
        $(".show-search-result a").show().filter(function () {
            return $(this).text().toLowerCase().trim().indexOf(value) == -1;
        }).hide();
    });

    function demo_mode() {
        toastr.info('This function is disable for demo mode', {
            CloseButton: true,
            ProgressBar: true
        });
    }

    $('.demo_check').on('click', function (event) {
        if ('{{env('APP_ENV')=='demo'}}') {
            event.preventDefault();
            demo_mode()
        }
    });

    $(document).on('click', '.admin-logout', function (event) {
        Swal.fire({
            title: "{{translate('are_you_sure')}}?",
            text: "{{translate('want_to_logout')}}",
            type: 'warning',
            showCloseButton: true,
            showCancelButton: true,
            cancelButtonColor: 'var(--bs-secondary)',
            confirmButtonColor: 'var(--bs-primary)',
            cancelButtonText: 'Cancel',
            confirmButtonText: 'Yes',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                location.href = "{{route('admin.auth.logout')}}"
            }
        })
    });

    $(document).ready(function (){
        const platform = navigator.platform;
        let shortcutText = '';
        let isMac = false;

        if (platform.toLowerCase().includes('mac')) {
            shortcutText = 'Cmd+K';
            isMac = true;
        } else if (platform.toLowerCase().includes('linux') || platform.toLowerCase().includes('win')) {
            shortcutText = 'Ctrl+K';
            isMac = false;
        } else {
            shortcutText = 'Ctrl+K';
            isMac = false;
        }
        $('.ctrlplusk').text(shortcutText);
    });

    $(document).ready(function(){
        $('.admin-renew-package').on('click', function() {
            var packageId = $(this).data('id');
            var providerId = $(this).data('provider');

            $.ajax({
                url: '{{ route("admin.provider.subscription-package.renew.ajax") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: packageId,
                    providerId: providerId
                },
                success: function(response) {
                    $('.admin-append-renew').html(response);
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        });
    });

    $(document).ready(function(){
        $('.admin-shift-package').on('click', function() {
            var packageId = $(this).data('id');
            var providerId = $(this).data('provider');

            $.ajax({
                url: '{{ route("admin.provider.subscription-package.shift.ajax") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: packageId,
                    providerId: providerId
                },
                success: function(response) {
                    $('.admin-append-shift').html(response);
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        });
    });

    $(document).ready(function(){
        $('.admin-purchase-package').on('click', function() {
            var packageId = $(this).data('id');
            var providerId = $(this).data('provider');

            $.ajax({
                url: '{{ route("admin.provider.subscription-package.purchase.ajax") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: packageId,
                    providerId: providerId
                },
                success: function(response) {
                    $('.admin-append-purchase').html(response);
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        });
    });

</script>

@include('adminmodule::layouts.partials._admin-notification-scripts')

@unless($adminUsesPartialNav)
@stack('script')
@endunless

@include('whatsappmodule::admin.booking-whatsapp-send-prompt')

</body>

</html>
