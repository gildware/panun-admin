@extends('adminmodule::layouts.new-master')

@section('title', translate('Booking_Configuration'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-2">
                        <h2 class="page-title mb-1">{{ translate('Booking_Configuration') }}</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            @include('bookingmodule::admin.configuration.partials._booking-config-card', [
                                'title' => translate('Booking_cancellation_reasons'),
                                'type' => 'booking_cancellation_reason',
                                'items' => $bookingCancellationReasons,
                            ])
                        </div>
                        <div class="col-lg-6">
                            @include('bookingmodule::admin.configuration.partials._booking-config-card', [
                                'title' => translate('Booking_dispute_reasons'),
                                'type' => 'booking_dispute_reason',
                                'items' => $bookingDisputeReasons,
                            ])
                        </div>
                        <div class="col-lg-6">
                            @include('bookingmodule::admin.configuration.partials._booking-config-card', [
                                'title' => translate('Booking_hold_reasons'),
                                'type' => 'booking_hold_reason',
                                'items' => $bookingHoldReasons,
                            ])
                        </div>
                        <div class="col-lg-6">
                            @include('bookingmodule::admin.configuration.partials._booking-config-card', [
                                'title' => translate('Booking_reopen_reasons'),
                                'type' => 'booking_reopen_reason',
                                'items' => $bookingReopenReasons,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
