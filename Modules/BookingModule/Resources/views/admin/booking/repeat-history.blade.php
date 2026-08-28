@extends('adminmodule::layouts.master')

@section('title', translate('Booking_History'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/booking-detail-redesign.css') }}">
    @include('bookingmodule::admin.booking.partials._booking-status-colors-styles')
@endpush

@section('content')
    @php
        extract(repeat_admin_detail_chrome_vars($booking, $customerAddress ?? null));
    @endphp
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Booking_Details') }} </h2>
            </div>
            <div class="row">
                <div class="col-12 booking-detail-v2 booking-detail-v2--{{ $repeatChromeStatusClass }}">
                    <div class="booking-detail-v2__wrap">
                        @include('bookingmodule::admin.booking.partials._repeat-detail-compact-topbar')
                        @include('bookingmodule::admin.booking.partials._repeat-detail-compact-header')

            <div class="d-flex flex-wrap justify-content-between align-items-center flex-xxl-nowrap gap-3 mb-4 booking-detail-nav-wrap">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'details' ? 'active' : '' }}"
                            href="{{ url()->current() }}?web_page=details">{{ translate('details') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'history' ? 'active' : '' }}"
                            href="{{ url()->current() }}?web_page=history">{{ translate('History') }}</a>
                    </li>
                </ul>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ translate('Booking_History') }}</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">{{ translate('Booking_change_history_repeat_note') }}</p>
                    @include('bookingmodule::admin.booking.partials.booking-change-logs-timeline', ['changeLogs' => $changeLogs ?? collect()])
                </div>
            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
