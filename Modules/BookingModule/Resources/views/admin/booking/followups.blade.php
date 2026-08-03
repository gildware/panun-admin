@extends('adminmodule::layouts.master')

@section('title', translate('Booking_Followups'))

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/booking-detail-redesign.css') }}">
    @include('bookingmodule::admin.booking.partials._booking-followup-styles')
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
                        @include('bookingmodule::admin.booking.partials._booking-detail-subpage-header', [
                            'booking' => $booking,
                            'followupDetailMeta' => $followupDetailMeta ?? null,
                        ])

                        @include('bookingmodule::admin.booking.partials.details._special-financial-settlement-banner', ['booking' => $booking])

                        <div class="d-flex flex-wrap justify-content-between align-items-center flex-xxl-nowrap gap-3 mb-3 booking-detail-nav-wrap">
                            @include('bookingmodule::admin.booking.partials._booking-detail-nav-tabs', [
                                'booking' => $booking,
                                'webPage' => $webPage,
                            ])
                        </div>

                        @include('bookingmodule::admin.booking.partials._booking-followup-alerts', [
                            'booking' => $booking,
                            'followupDetailMeta' => $followupDetailMeta ?? null,
                        ])

            <div class="booking-followups-preview mb-3">
                <div class="booking-followups-preview__cell">
                    @include('bookingmodule::admin.booking.partials._booking-overview-party-provider', [
                        'booking' => $booking,
                        'followupDetailMeta' => $followupDetailMeta ?? null,
                        'nextFollowupProvider' => $nextFollowupProvider ?? null,
                        'bookingNotEditable' => $bookingNotEditable ?? false,
                    ])
                </div>
                <div class="booking-followups-preview__cell">
                    @include('bookingmodule::admin.booking.partials._booking-overview-party-customer', [
                        'booking' => $booking,
                        'customerName' => $customerName ?? null,
                        'customerPhone' => $customerPhone ?? null,
                        'customerAddress' => $customerAddress ?? null,
                        'followupDetailMeta' => $followupDetailMeta ?? null,
                        'nextFollowupCustomer' => $nextFollowupCustomer ?? null,
                    ])
                </div>
            </div>

            <div class="row gy-3">
                <div class="col-12">
                    <div class="card booking-subpage-panel">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h4 class="mb-0">{{ translate('Follow_up_History') }}</h4>
                            <button type="button" class="btn btn--primary" data-bs-toggle="modal" data-bs-target="#addFollowupModal">
                                <span class="material-icons">add</span>{{ translate('Add_Follow_up') }}
                            </button>
                        </div>
                        <div class="card-body p-0">
                            @include('bookingmodule::admin.booking.partials._booking-followup-history-table', [
                                'booking' => $booking,
                                'followups' => $booking->followups,
                                'followupDelayMeta' => $followupDelayMeta ?? [],
                                'showActionColumn' => true,
                                'showSectionLabel' => false,
                                'tableClass' => 'table table-hover align-middle',
                            ])
                            @include('bookingmodule::admin.booking.partials._booking-scheduled-followup-modals', [
                                'booking' => $booking,
                                'redirectWebPage' => 'followups',
                                'requiresMandatoryNextFollowup' => $requiresMandatoryNextFollowup ?? $booking->requiresMandatoryNextFollowup(),
                                'followupScheduleMinAt' => $followupScheduleMinAt ?? now()->format('Y-m-d\TH:i'),
                            ])
                        </div>
                    </div>
                </div>
            </div>
                    @include('bookingmodule::admin.booking.partials._booking-detail-delete-footer', ['booking' => $booking])
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Follow-up Modal (lead-style — Taken on, recording, next date) --}}
    @include('bookingmodule::admin.booking.partials._booking-add-followup-modal', [
        'booking' => $booking,
        'requiresMandatoryNextFollowup' => $requiresMandatoryNextFollowup ?? $booking->requiresMandatoryNextFollowup(),
        'followupScheduleMinAt' => $followupScheduleMinAt ?? now()->format('Y-m-d\TH:i'),
        'redirectWebPage' => 'followups',
    ])
@endsection

@push('script')
    @include('bookingmodule::admin.booking.partials._booking-add-followup-scripts')
    @include('bookingmodule::admin.booking.partials._booking-take-followup-scripts')
@endpush
