@extends('adminmodule::layouts.master')

@section('title', translate('Payments'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/booking-detail-redesign.css') }}">
    @include('bookingmodule::admin.booking.partials._booking-status-colors-styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Booking_Details') }} </h2>
            </div>
            @php
                extract(repeat_admin_detail_chrome_vars($booking, $customerAddress ?? null, ! empty($canStopRepeatSeries)));
            @endphp
            <div class="row">
                <div class="col-12 booking-detail-v2 booking-detail-v2--{{ $repeatChromeStatusClass }}">
                    <div class="booking-detail-v2__wrap">
                        @include('bookingmodule::admin.booking.partials._repeat-detail-compact-topbar')
                        @include('bookingmodule::admin.booking.partials._repeat-detail-compact-header')

            <div class="d-flex flex-wrap justify-content-between align-items-center flex-xxl-nowrap gap-3 mb-4 booking-detail-nav-wrap">
                @include('bookingmodule::admin.booking.partials._repeat-booking-tabs')
            </div>

            @include('bookingmodule::admin.booking.partials._repeat-payments-board')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
