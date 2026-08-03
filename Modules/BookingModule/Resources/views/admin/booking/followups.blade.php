@extends('adminmodule::layouts.master')

@section('title', translate('Booking_Followups'))

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/booking-detail-redesign.css') }}">
    @include('bookingmodule::admin.booking.partials._booking-followup-styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Booking_Details') }}</h2>
            </div>

            <div class="row">
                @php
                    $__detailStatusClass = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($booking->booking_status ?? 'pending'))) ?: 'default';
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Follow-up Modal --}}
    <div class="modal fade" id="addFollowupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="{{ route('admin.booking.followup.store', $booking->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Add_Follow_up') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Date_Time') }} <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="date" class="form-control" value="{{ date('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Reason') }}</label>
                            <input type="text" name="reason" class="form-control" placeholder="{{ translate('Reason_for_follow_up') }}" maxlength="500">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('For') }} <span class="text-danger">*</span></label>
                            <select name="for" class="form-select" required>
                                <option value="customer">{{ translate('Customer') }}</option>
                                <option value="provider">{{ translate('Provider') }}</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ translate('Urgency') }}</label>
                            <select name="urgency" class="form-select">
                                <option value="high">{{ translate('High') }}</option>
                                <option value="medium" selected>{{ translate('Medium') }}</option>
                                <option value="low">{{ translate('Low') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('bookingmodule::admin.booking.partials._booking-take-followup-scripts')
@endpush
