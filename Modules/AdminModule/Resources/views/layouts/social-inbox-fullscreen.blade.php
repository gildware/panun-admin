<!DOCTYPE html>
@php
    $site_direction = session()->get('site_direction');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $site_direction }}">

<head>
    <title>@yield('title')</title>

    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($favIcon = getBusinessSettingsImageFullPath(key: 'business_favicon', settingType: 'business_information', path: 'business/',  defaultPath : 'assets/placeholder.png'))
    <link rel="shortcut icon" href="{{ $favIcon }}"/>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap"
        rel="stylesheet">

    <link href="{{ asset('assets/admin-module') }}/css/material-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin-module') }}/css/bootstrap.min.css"/>
    <link rel="stylesheet"
          href="{{ asset('assets/admin-module') }}/plugins/perfect-scrollbar/perfect-scrollbar.min.css"/>

    <link rel="stylesheet" href="{{ asset('assets/admin-module') }}/plugins/apex/apexcharts.css"/>
    <link rel="stylesheet" href="{{ asset('assets/admin-module') }}/plugins/select2/select2.min.css"/>

    <link rel="stylesheet" href="{{ asset('assets/admin-module') }}/css/toastr.css">

    <link rel="stylesheet" href="{{ asset('assets/admin-module') }}/css/style.css"/>
    <link rel="stylesheet" href="{{ asset('assets/admin-module') }}/css/dev.css"/>
    <link rel="stylesheet" href="{{ asset('assets/common') }}/css/common.css"/>
    <link rel="stylesheet" href="{{ asset('assets/provider-module') }}/css/view-guideline.css"/>

    <style>
        body.wa-social-inbox-fullscreen-body {
            overflow-x: hidden;
        }
        body.wa-social-inbox-fullscreen-body .main-area.wa-si-fs-main {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
    </style>

    @stack('css_or_js')
</head>

<body class="wa-social-inbox-fullscreen-body">
<script>
    localStorage.theme && document.querySelector('body').setAttribute("data-bs-theme", localStorage.theme);
</script>

<div class="offcanvas-overlay"></div>

<div class="preloader"></div>

<main class="main-area wa-si-fs-main">
    @yield('content')

    @include('adminmodule::layouts.partials._status-modal')
</main>

<script src="{{ asset('assets/admin-module') }}/js/jquery-3.6.0.min.js"></script>
<script src="{{ asset('assets/admin-module') }}/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/admin-module') }}/plugins/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="{{ asset('assets/admin-module') }}/js/main.js"></script>
<script src="{{ asset('assets/admin-module') }}/js/helper.js"></script>
<script src="{{ asset('assets/common') }}/js/common.js"></script>
<script src="{{ asset('assets/common') }}/js/form-submit-once.js"></script>

<script src="{{ asset('assets/admin-module') }}/plugins/select2/select2.min.js"></script>
<script src="{{ asset('assets/admin-module') }}/js/sweet_alert.js"></script>
<script src="{{ asset('assets/admin-module') }}/js/toastr.js"></script>
<script src="{{ asset('assets/admin-module') }}/js/dev.js"></script>
<script src="{{ asset('assets/admin-module') }}/js/keyword-highlight.js"></script>

<span class="system-default-country-code" data-value="in" data-initial-country="in"></span>
<link rel="stylesheet" href="{{ asset('assets/libs/intl-tel-input/css/intlTelInput.css') }}"/>
<script src="{{ asset('assets/libs/intl-tel-input/js/intlTelInput.js') }}"></script>
<script src="{{ asset('assets/libs/intl-tel-input/js/utils.js') }}"></script>
<script src="{{ asset('assets/libs/intl-tel-input/js/intlTelInout-validation.js') }}"></script>

<script src="{{ asset('assets/common/js/file-size-type-validation.js') }}"></script>
<script src="{{ asset('assets/provider-module/js/multiple-image-upload.js') }}"></script>

{!! Toastr::message() !!}

<audio id="audio-element">
    <source src="{{ asset('assets/provider-module') }}/sound/notification.mp3" type="audio/mpeg">
</audio>

<script>
    "use strict";
    $(document).ready(function () {
        $('.js-select').select2();
    });

    @if ($errors->any())
        @foreach($errors->all() as $error)
        toastr.error(@json($error), @json(translate('error')), {
            CloseButton: true,
            ProgressBar: true
        });
        @endforeach
   @endif

    function handleAdminUpdatedDataResponse(response, opts) {
        opts = opts || {};
        var skipSound = !!opts.skipSound;
        let data = response.data;
        var msgEl = document.getElementById("message_count");
        if (msgEl) {
            msgEl.innerHTML = data.message;
        }

        var waCountEl = document.getElementById("whatsapp_unread_count");
        if (waCountEl) {
            var chats = parseInt(data.whatsapp_unread_chats, 10);
            if (isNaN(chats)) chats = 0;
            waCountEl.innerHTML = chats;

            var msgTotal = parseInt(data.whatsapp_unread_messages, 10);
            if (isNaN(msgTotal)) msgTotal = 0;
            var waPrevKey = 'admin_whatsapp_unread_messages';
            var waPrevRaw = sessionStorage.getItem(waPrevKey);
            var audio = document.getElementById("audio-element");
            if (!skipSound && waPrevRaw !== null && waPrevRaw !== '') {
                var waPrev = parseInt(waPrevRaw, 10) || 0;
                if (msgTotal > waPrev && audio) {
                    audio.play().catch(function () {});
                }
            }
            sessionStorage.setItem(waPrevKey, String(msgTotal));
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
        var adminHeaderPollMs = 15000;
        setInterval(function () {
            $.get({
                url: '{{ route('admin.get_updated_data') }}',
                dataType: 'json',
                success: handleAdminUpdatedDataResponse,
            });
        }, adminHeaderPollMs);
    })();

    $('.form-alert').on('click', function (){
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

    $('.route-alert').on('change', function (event){
        event.preventDefault();
        let $this = $(this);
        let initialState = $this.prop('checked');

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

    $('.route-alert-reload').on('click', function (){
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
</script>

@stack('script')
</body>

</html>
