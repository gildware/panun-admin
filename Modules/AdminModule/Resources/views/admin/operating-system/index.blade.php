@extends('adminmodule::layouts.new-master')

@section('title', translate('Operating_System'))

@php
    $osAssetBase = asset('assets/admin-module/operating-system');
    $osVersion = max(
        (int) @filemtime(public_path('assets/admin-module/operating-system/os-app.js')),
        (int) @filemtime(public_path('assets/admin-module/operating-system/os.css')),
        (int) @filemtime(public_path('assets/admin-module/operating-system/system-os-story.js')),
        (int) @filemtime(public_path('assets/admin-module/operating-system/system-modules-data.js')),
        2026091814
    ) ?: time();
@endphp

@push('css_or_js')
    <meta name="turbo-visit-control" content="reload">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="{{ $osAssetBase }}/os.css?v={{ $osVersion }}">
@endpush

@section('content')
    <div class="main-content os-operating-system-page">
        <div class="os-explorer" id="os-explorer" data-art-base="{{ $osAssetBase }}/art" data-version="{{ $osVersion }}">
            <div class="app">
                <button class="menu-btn" id="os-menu-btn" type="button" aria-label="Open navigation"><span class="mso">menu</span></button>
                <div class="scrim" id="os-scrim"></div>
                <aside class="sidebar-col" id="os-sidebar-col">
                    <div class="os-search-dock">
                        <div class="search-wrap">
                            <span class="mso">search</span>
                            <input id="os-q" type="search" placeholder="Search modules, SOPs, KPIs…" autocomplete="off">
                            <div class="search-results" id="os-results"></div>
                        </div>
                        <span id="os-progress-chip" hidden>Master OS</span>
                    </div>
                    <div class="sidebar" id="os-sidebar"></div>
                </aside>
                <div class="main" id="os-main"></div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ $osAssetBase }}/system-modules-data.js?v={{ $osVersion }}"></script>
    <script src="{{ $osAssetBase }}/system-os-docs.js?v={{ $osVersion }}"></script>
    <script src="{{ $osAssetBase }}/system-os-story.js?v={{ $osVersion }}"></script>
    <script src="{{ $osAssetBase }}/os-app.js?v={{ $osVersion }}"></script>
@endpush
