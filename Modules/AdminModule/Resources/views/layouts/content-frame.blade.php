<!DOCTYPE html>
@php
    $site_direction = session()->get('site_direction', 'ltr');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $site_direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', translate('Details'))</title>
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/bootstrap.min.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/material-icons.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/select2/select2.min.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/style.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/toastr.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/common/css/common.css') }}"/>
    <style>
        body { background: #f4f5f7; }
        .main-content {
            margin: 0 !important;
            padding: 0 !important;
            min-height: 0 !important;
            width: 100% !important;
        }
    </style>
    @stack('css_or_js')
</head>
<body>
    <div class="container-fluid py-3">
        @yield('content')
    </div>
    <script src="{{ asset('assets/admin-module/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/bootstrap-jquery-modal-bridge.js') }}"></script>
    <script src="{{ asset('assets/admin-module/plugins/select2/select2.min.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/toastr.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/sweet_alert.js') }}"></script>
    <script src="{{ asset('assets/common/js/common.js') }}"></script>
    <script src="{{ asset('assets/common/js/form-submit-once.js') }}"></script>
    {!! str_replace('<script type="text/javascript">', '<script type="text/javascript" data-admin-flash-toasts="1">', Toastr::message()) !!}
    @stack('script')
    <script>
        $(document).ready(function () { $('.js-select').not('.js-select-manual').select2({ width: '100%' }); });
        document.querySelectorAll('form').forEach(function (form) {
            if (form.querySelector('input[name="in_modal"]')) {
                return;
            }
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'in_modal';
            input.value = '1';
            form.appendChild(input);
        });
        document.querySelectorAll('a[href]').forEach(function (link) {
            if (link.target === '_blank' || link.hasAttribute('download')) {
                return;
            }
            let url;
            try {
                url = new URL(link.getAttribute('href'), window.location.origin);
            } catch (e) {
                return;
            }
            if (url.origin !== window.location.origin || !url.pathname.startsWith('/admin')) {
                return;
            }
            if (!url.searchParams.has('in_modal')) {
                url.searchParams.set('in_modal', '1');
                link.setAttribute('href', url.pathname + url.search + url.hash);
            }
        });
    </script>
    @include('whatsappmodule::admin.booking-whatsapp-send-prompt')
</body>
</html>
