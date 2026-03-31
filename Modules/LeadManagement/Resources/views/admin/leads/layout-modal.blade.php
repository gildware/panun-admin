<!DOCTYPE html>
@php
    $site_direction = session()->get('site_direction', 'ltr');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $site_direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', translate('Lead_Details'))</title>
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/bootstrap.min.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/material-icons.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/select2/select2.min.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/style.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/toastr.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/common/css/common.css') }}"/>
    @stack('css_or_js')
</head>
<body class="bg-light">
    <div class="container-fluid py-3">
        @yield('content')
    </div>
    <script src="{{ asset('assets/admin-module/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/admin-module/plugins/select2/select2.min.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/toastr.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/sweet_alert.js') }}"></script>
    <script src="{{ asset('assets/common/js/common.js') }}"></script>
    <script src="{{ asset('assets/common/js/form-submit-once.js') }}"></script>
    @stack('script')
    <script>
        $(document).ready(function () { $('.js-select').select2({ width: '100%' }); });
    </script>
</body>
</html>
