@extends('adminmodule::layouts.master')

@section('title', translate('Booking_History'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/booking-detail-redesign.css') }}">
    @include('bookingmodule::admin.booking.partials._booking-status-colors-styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Booking_Details') }}</h2>
            </div>

            <div class="row">
                @php
                    $__detailStatusClass = booking_admin_status_css_class($booking);
                @endphp
                <div class="col-12 booking-detail-v2 booking-detail-v2--{{ $__detailStatusClass }}">
                    <div class="booking-detail-v2__wrap">
                        @include('bookingmodule::admin.booking.partials._booking-detail-compact-topbar', ['booking' => $booking])
                        @include('bookingmodule::admin.booking.partials._booking-detail-pipeline', ['booking' => $booking])
                        @include('bookingmodule::admin.booking.partials._booking-detail-subpage-header', ['booking' => $booking])

                        @include('bookingmodule::admin.booking.partials.details._special-financial-settlement-banner', ['booking' => $booking])

                        <div class="d-flex flex-wrap justify-content-between align-items-center flex-xxl-nowrap gap-3 mb-3 booking-detail-nav-wrap">
                            @include('bookingmodule::admin.booking.partials._booking-detail-nav-tabs', [
                                'booking' => $booking,
                                'webPage' => $webPage,
                            ])
                        </div>

                        <div class="card booking-subpage-panel">
                            <div class="card-header">
                                <h5 class="mb-0">{{ translate('Booking_History') }}</h5>
                            </div>
                            <div class="card-body">
                                @include('bookingmodule::admin.booking.partials.booking-change-logs-timeline', ['changeLogs' => $booking->change_logs])
                            </div>
                        </div>
                    @include('bookingmodule::admin.booking.partials._booking-detail-delete-footer', ['booking' => $booking])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
