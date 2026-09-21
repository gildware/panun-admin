@extends('adminmodule::layouts.new-master')

@section('title', translate('Business_System'))

@php
    $osAssetBase = asset('assets/admin-module/operating-system');
    $bsAssetBase = asset('assets/admin-module/business-system');
    $bsVersion = max(
        (int) @filemtime(public_path('assets/admin-module/business-system/bs-app.js')),
        (int) @filemtime(public_path('assets/admin-module/business-system/bs.css')),
        (int) @filemtime(public_path('assets/admin-module/operating-system/os.css')),
        2026092110
    ) ?: time();
@endphp

@push('css_or_js')
    <meta name="turbo-visit-control" content="reload">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="{{ $osAssetBase }}/os.css?v={{ $bsVersion }}">
    <link rel="stylesheet" href="{{ $bsAssetBase }}/bs.css?v={{ $bsVersion }}">
@endpush

@section('content')
    <div class="main-content os-operating-system-page">
        <div class="os-explorer bs-full" id="bs-explorer" data-art-base="{{ $bsAssetBase }}/art" data-version="{{ $bsVersion }}">
            <div class="app">
                <div class="main" id="bs-main"></div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ $bsAssetBase }}/bs-app.js?v={{ $bsVersion }}"></script>
@endpush
