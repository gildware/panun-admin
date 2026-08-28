@extends('adminmodule::layouts.master')

@section('title', translate('Visits'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/booking-detail-redesign.css') }}?v=2026082414">
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
                $canExtend = ! empty($canExtendRepeatSeries);
                $showStopModal = ! empty($canStopRepeatSeries);
                $repeatChromeShowScheduleVisit = $canExtend;
                $repeatChromeShowAddVisit = $canExtend;
            @endphp
            <div class="row">
                <div class="col-12 booking-detail-v2 booking-detail-v2--{{ $repeatChromeStatusClass }}">
                    <div class="booking-detail-v2__wrap">
                        @include('bookingmodule::admin.booking.partials._repeat-detail-compact-topbar')
                        @include('bookingmodule::admin.booking.partials._repeat-detail-compact-header')

            <div class="d-flex flex-wrap justify-content-between align-items-center flex-xxl-nowrap gap-3 mb-4 booking-detail-nav-wrap">
                @include('bookingmodule::admin.booking.partials._repeat-booking-tabs')
            </div>

            @include('bookingmodule::admin.booking.partials._repeat-visits-board')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($showStopModal)
        @can('booking_can_manage_status')
            <div class="modal fade" id="stopRepeatSeriesModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.booking.stop_repeat', $booking->id) }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">{{ translate('Stop_series_and_complete') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-0">{{ translate('Stop_repeat_series_confirm') }}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                <button type="submit" class="btn btn--danger">{{ translate('Stop_series_and_complete') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endif

    @include('bookingmodule::admin.booking.partials._add-repeat-visit-modal')
    @include('bookingmodule::admin.booking.partials._booking-status-reason-modal')
@endsection

@push('script')
    @include('bookingmodule::admin.booking.partials._repeat-visit-status-script')
@endpush
