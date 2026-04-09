@extends('adminmodule::layouts.master')

@section('title', translate('Booking_Status'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Booking_Details') }} </h2>
            </div>

            <div class="pb-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <h3 class="c1">{{ translate('Booking') }} # {{ $booking['readable_id'] }}</h3>
                        <span class="badge badge-{{
                            $booking->booking_status == 'ongoing' ? 'warning' :
                            ($booking->booking_status == 'completed' ? 'success' :
                            ($booking->booking_status == 'canceled' ? 'danger' : 'info'))
                        }}">
                            {{ ucwords($booking->booking_status) }}
                        </span>
                        @if($booking->isOpenReopenTicket())
                            <span class="badge bg-warning text-dark">{{ translate('Reopened') }}</span>
                        @elseif($booking->isReopenedTagged())
                            <span class="badge bg-success">{{ translate('Resolved') }}</span>
                        @endif
                    </div>
                    <p class="opacity-75 fz-12">{{ translate('Booking_Placed') }}
                        : {{ date('d-M-Y h:ia', strtotime($booking->created_at)) }}</p>
                </div>
                <div class="d-flex flex-wrap flex-xxl-nowrap gap-3 ms-auto align-items-end align-items-xxl-center">
                    <div class="d-flex flex-wrap gap-3 justify-content-end">
{{--                        @if ($booking['payment_method'] == 'offline_payment' && !$booking['is_paid'])--}}
{{--                            <span class="btn btn--primary offline-payment" data-id="{{ $booking->id }}">--}}
{{--                                <span class="material-icons">done</span>{{ translate('Verify Offline Payment') }}--}}
{{--                            </span>--}}
{{--                        @endif--}}
                        @php($maxBookingAmount = business_config('max_booking_amount', 'booking_setup')->live_values)

                        @if (
                            $booking['payment_method'] == 'cash_after_service' &&
                                $booking->is_verified == '0' &&
                                $booking->total_booking_amount >= $maxBookingAmount)
                            @can('booking_can_approve_or_deny')
                                <span class="btn btn--primary verify-booking-request" data-id="{{ $booking->id }}"
                                    data-bs-toggle="modal" data-bs-target="#exampleModal--{{ $booking->id }}">
                                    <span class="material-icons">done</span>
                                    {{ translate('verify booking request') }}
                                </span>
                            @endcan

                            <div class="modal fade" id="exampleModal--{{ $booking->id }}" tabindex="-1"
                                aria-labelledby="exampleModalLabel--{{ $booking->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-body p-4 py-5">
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                            <div class="text-center mb-4 pb-3">
                                                <img class="mb-4"
                                                    src="{{ asset('/assets/admin-module/img/booking-req-status.png') }}"
                                                    alt="">
                                                <h3 class="mb-1 fw-medium">
                                                    {{ translate('Verify the booking request status?') }}</h3>
                                                <p class="text-start fs-12 fw-medium text-muted">
                                                    {{ translate('Need verification for max booking amount') }}</p>
                                            </div>
                                            <form method="post"
                                                action="{{ route('admin.booking.verification-status', [$booking->id]) }}">
                                                @csrf
                                                <div class="c1-light-bg p-4 rounded">
                                                    <h5 class="mb-3">{{ translate('Request Status') }}</h5>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <div class="form-check-inline">
                                                            <input
                                                                class="form-check-input approve-request check-approve-status"
                                                                checked type="radio" name="status" id="inlineRadio1"
                                                                value="approve">
                                                            <label class="form-check-label"
                                                                for="inlineRadio1">{{ translate('Approve the Request') }}</label>
                                                        </div>
                                                        <div class="form-check-inline">
                                                            <input class="form-check-input deny-request check-deny-status"
                                                                type="radio" name="status" id="inlineRadio2"
                                                                value="deny">
                                                            <label class="form-check-label"
                                                                for="inlineRadio2">{{ translate('Deny the Request') }}</label>
                                                        </div>
                                                    </div>
                                                    <div class="mt-4 cancellation-note">
                                                        <textarea class="form-control h-69px" placeholder="{{ translate('Cancellation Note') }}" name="booking_deny_note"
                                                            id="add-your-note" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-center mt-4">
                                                    <button type="submit"
                                                        class="btn btn--primary">{{ translate('submit') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if ($booking['payment_method'] == 'cash_after_service' && $booking->is_verified == '2')
                            <span class="btn btn--primary change-booking-request" data-id="{{ $booking->id }}"
                                data-bs-toggle="modal" data-bs-target="#exampleModal--{{ $booking->id }}">
                                <span class="material-icons">done</span>{{ translate('Change Request Status') }}
                            </span>

                            <div class="modal fade" id="exampleModal--{{ $booking->id }}" tabindex="-1"
                                aria-labelledby="exampleModalLabel--{{ $booking->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-body p-4 py-5">
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                            <div class="text-center mb-4 pb-3">
                                                <img class="mb-4"
                                                    src="{{ asset('/assets/admin-module/img/booking-req-status.png') }}"
                                                    alt="">
                                                <h3>{{ translate('Verify the booking request status?') }}</h3>
                                                <p>{{ translate('Need verification for max booking amount') }}</p>
                                            </div>
                                            <form method="post"
                                                action="{{ route('admin.booking.verification-status', [$booking->id]) }}">
                                                @csrf
                                                <div class="c1-light-bg p-4 rounded">
                                                    <h5 class="mb-3">{{ translate('Request Status') }}</h5>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <div class="form-check-inline">
                                                            <input class="form-check-input approve-request" checked
                                                                type="radio" name="status" id="changeReqStatusApprove--{{ $booking->id }}"
                                                                value="approve">
                                                            <label class="form-check-label"
                                                                for="changeReqStatusApprove--{{ $booking->id }}">{{ translate('Approve the Request') }}</label>
                                                        </div>
                                                        <div class="form-check-inline">
                                                            <input class="form-check-input deny-request" type="radio"
                                                                name="status" id="changeReqStatusDeny--{{ $booking->id }}"
                                                                value="deny">
                                                            <label class="form-check-label"
                                                                for="changeReqStatusDeny--{{ $booking->id }}">{{ translate('Deny the Request') }}</label>
                                                        </div>
                                                    </div>
                                                    <div class="mt-4 cancellation-note" style="display: none;">
                                                        <textarea class="form-control h-69px" placeholder="{{ translate('Cancellation Note') }}" name="booking_deny_note"
                                                            id="changeReqDenyNote--{{ $booking->id }}"></textarea>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-center mt-4">
                                                    <button type="submit"
                                                        class="btn btn--primary">{{ translate('submit') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (in_array($booking['booking_status'], ['pending', 'accepted', 'ongoing']) &&
                                $booking->booking_partial_payments->isEmpty() && $booking['payment_method'] == 'cash_after_service' && !$booking['is_paid'] && empty($booking->customizeBooking))
                            @can('booking_edit')
                                <button class="btn btn--primary" data-bs-toggle="modal"
                                    data-bs-target="#serviceUpdateModal--{{ $booking['id'] }}" data-toggle="tooltip"
                                    title="{{ translate('Add or remove services') }}">
                                    <span class="material-symbols-outlined">edit</span>{{ translate('Edit Services') }}
                                </button>
                            @endcan
                        @endif
                        <a href="{{ route('admin.booking.invoice', [$booking->id]) }}" class="btn btn-primary"
                            target="_blank">
                            <span class="material-icons">description</span>{{ translate('Invoice') }}
                        </a>
                        @if(
                            ($booking->booking_status ?? '') === 'completed'
                            && !empty($booking->provider_id)
                            && !\Modules\ProviderManagement\Entities\ProviderIncident::query()
                                ->where('booking_id', $booking->id)
                                ->where('provider_id', $booking->provider_id)
                                ->where('action_type', \Modules\ProviderManagement\Services\ProviderPerformanceService::ACTION_COMPLETED)
                                ->exists()
                        )
                            <button type="button" class="btn btn--primary open-feedback-manual">
                                <span class="material-icons">feedback</span>{{ translate('Provide Feedback') }}
                            </button>
                        @endif
                        @can('booking_can_manage_status')
                            @if((int)($booking->is_repeated ?? 0) === 0 && ($booking->booking_status ?? '') === 'completed')
                                <button type="button" class="btn btn--secondary" data-bs-toggle="modal"
                                    data-bs-target="#bookingReopenModal--{{ $booking->id }}">
                                    <span class="material-icons">restore</span>{{ translate('Reopen_or_complaint') }}
                                </button>
                            @endif
                            @if($booking->canMarkReopenResolved())
                                <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#reopenResolveModal--{{ $booking->id }}">
                                    <span class="material-icons">check_circle</span>{{ translate('Mark_reopen_resolved') }}
                                </button>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>

            @include('bookingmodule::admin.booking.partials._reopen-from-completed-modal')
            @include('bookingmodule::admin.booking.partials._reopen-resolve-modal', [
                'modalId' => 'reopenResolveModal--' . $booking->id,
                'formId' => 'reopenResolveForm--' . $booking->id,
                'formAction' => route('admin.booking.reopen-resolve', $booking->id),
            ])
            @if((int)($booking->is_repeated ?? 0) === 0 && $booking->isOpenReopenTicket())
                @include('bookingmodule::admin.booking.partials._reopen-scenarios-modal')
            @endif
            @php
                $__reopenErrResolve = $errors->has('reopen_resolve_remarks');
                $__reopenErrResolveComplete = $errors->has('reopen_resolve_complete_remarks');
                $__reopenErrDispute = $errors->has('reopen_dispute_remarks')
                    || $errors->has('refund_company_amount')
                    || $errors->has('refund_provider_amount')
                    || $errors->has('refund_company_transaction_id')
                    || $errors->has('refund_provider_transaction_id')
                    || $errors->has('final_net_to_customer')
                    || $errors->has('final_admin_commission')
                    || $errors->has('final_provider_earning');
            @endphp
            @if($__reopenErrResolve || $__reopenErrResolveComplete || $__reopenErrDispute)
                @push('script')
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            if (window.bootstrap && bootstrap.Modal) {
                                @if($__reopenErrDispute)
                                    var d = document.getElementById('reopenDisputeModal--{{ $booking->id }}');
                                    if (d) bootstrap.Modal.getOrCreateInstance(d).show();
                                @elseif($__reopenErrResolveComplete)
                                    var c = document.getElementById('reopenResolveCompleteModal--{{ $booking->id }}');
                                    if (c) bootstrap.Modal.getOrCreateInstance(c).show();
                                @elseif($__reopenErrResolve)
                                    var el = document.getElementById('reopenResolveModal--{{ $booking->id }}');
                                    if (el) bootstrap.Modal.getOrCreateInstance(el).show();
                                @endif
                            }
                        });
                    </script>
                @endpush
            @endif

            <div class="d-flex flex-wrap justify-content-between align-items-center flex-xxl-nowrap gap-3 mb-4">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'details' ? 'active' : '' }}"
                            href="{{ url()->current() }}?web_page=details">{{ translate('details') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'status' ? 'active' : '' }}"
                            href="{{ url()->current() }}?web_page=status">{{ translate('status') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'followups' ? 'active' : '' }}"
                            href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'followups']) }}">{{ translate('Followups') }}</a>
                    </li>
                </ul>

                @php($max_booking_amount = business_config('max_booking_amount', 'booking_setup')->live_values ?? 0)

                @if (
                    $booking->is_verified == 2 &&
                        $booking->payment_method == 'cash_after_service' &&
                        $max_booking_amount <= $booking->total_booking_amount)
                    <div class="border border-danger-light bg-soft-danger rounded py-3 px-3 text-dark">
                        <span class="text-danger"># {{ translate('Note: ') }}</span>
                        <span>{{ $booking?->bookingDeniedNote?->value }}</span>
                    </div>
                @endif

                @if (
                    $booking->is_verified == 0 &&
                        $booking->payment_method == 'cash_after_service' &&
                        $max_booking_amount <= $booking->total_booking_amount)
                    <div class="border border-danger-light bg-soft-danger rounded py-3 px-3 text-dark">
                        <span class="text-danger"># {{ translate('Note: ') }}</span>
                        <span>
                            {{ translate('You have to verify the booking because of maximum amount exceed') }}
                        </span>
                        <span>{{ $booking?->bookingDeniedNote?->value }}</span>
                    </div>
                @endif

                @if ($booking->booking_offline_payments->isNotEmpty() && $booking->payment_method == 'offline_payment' && $booking?->booking_offline_payments?->first()?->payment_status != 'approved')
                    <div class="border border-danger-light bg-soft-danger rounded py-3 px-3 text-dark">
                        @if($booking?->booking_offline_payments?->first()?->payment_status == 'pending')
                            <span>
                                <span class="text-danger fw-semibold"> # {{ translate('Note: ') }} </span>
                                {{ translate('Please Check & Verify the payment information weather it is correct or not before confirm the booking. ') }}
                            </span>
                        @endif
                        @if($booking?->booking_offline_payments?->first()?->payment_status == 'denied')
                            <span>
                                <span class="text-danger fw-semibold"> # {{ translate('Denied Note: ') }} </span>
                                {{ $booking?->booking_offline_payments?->first()?->denied_note }}
                            </span>
                        @endif

                    </div>
                @endif

            </div>

            <div class="row mb-3 g-3">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body c1-light-bg">
                            <h5 class="mb-2">{{ translate('Next_Follow_up_Date_Provider') }}</h5>
                            @if($booking->provider)
                                <p class="mb-1 fw-semibold">{{ $booking->provider->company_name ?? '' }}</p>
                                <p class="mb-1 small">
                                    <a href="tel:{{ $booking->provider->contact_person_phone ?? $booking->provider->company_phone ?? '' }}">{{ $booking->provider->contact_person_phone ?? $booking->provider->company_phone ?? '—' }}</a>
                                </p>
                            @endif
                            @if($nextFollowupProvider ?? null)
                                <p class="mb-0 fw-semibold">{{ $nextFollowupProvider->date->format('d-M-Y h:ia') }}
                                    @if($nextFollowupProvider->reason)
                                        <span class="text-muted">({{ Str::limit($nextFollowupProvider->reason, 60) }})</span>
                                    @endif
                                </p>
                            @else
                                <p class="mb-0 text-muted">—</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body c1-light-bg">
                            <h5 class="mb-2">{{ translate('Next_Follow_up_Date_Customer') }}</h5>
                            @if(($customerName ?? '') || ($customerPhone ?? ''))
                                <p class="mb-1 fw-semibold">{{ ($customerName ?? '') ?: '—' }}</p>
                                <p class="mb-1 small">
                                    @if($customerPhone ?? null)
                                        <a href="tel:{{ $customerPhone }}">{{ $customerPhone }}</a>
                                    @else
                                        —
                                    @endif
                                </p>
                            @endif
                            @if($nextFollowupCustomer ?? null)
                                <p class="mb-0 fw-semibold">{{ $nextFollowupCustomer->date->format('d-M-Y h:ia') }}
                                    @if($nextFollowupCustomer->reason)
                                        <span class="text-muted">({{ Str::limit($nextFollowupCustomer->reason, 60) }})</span>
                                    @endif
                                </p>
                            @else
                                <p class="mb-0 text-muted">—</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row gy-3">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="border-bottom pb-3 mb-3">
                                <div
                                    class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-3 flex-wrap">
                                    <div>
                                        <h4 class="mb-2">{{ translate('Payment Method') }}</h4>
                                        <h5 class="c1 mb-2"><span
                                                class="text-capitalize">{{ str_replace(['_', '-'], ' ', $booking->payment_method) }}
                                                @if ($booking->payment_method == 'offline_payment' && $booking?->booking_offline_payments?->first()?->method_name)
                                                    ({{ $booking?->booking_offline_payments?->first()?->method_name }})
                                                @endif
                                            </span>
                                        </h5>
                                        <p>
                                            <span>{{ translate('Amount') }} : </span>
                                            {{ with_currency_symbol($booking->total_booking_amount) }}
                                        </p>
                                    </div>
                                    <div class="text-start text-sm-end">
                                        @if($booking->payment_method == 'offline_payment' && $booking->booking_offline_payments->isNotEmpty())
                                            <p class="mb-2"><span>{{ translate('Request Verify Status') }} :</span>
                                                @if($booking->booking_offline_payments?->first()?->payment_status == 'pending')
                                                    <span class="text-info text-capitalize fw-bold">{{ translate('Pending') }}</span>
                                                @endif
                                                @if($booking->booking_offline_payments?->first()?->payment_status == 'denied')
                                                    <span class="text-danger text-capitalize fw-bold">{{ translate('Denied') }}</span>
                                                @endif
                                                @if($booking->booking_offline_payments?->first()?->payment_status == 'approved')
                                                    <span class="text-primary text-capitalize fw-bold">{{ translate('Approved') }}</span>
                                                @endif
                                            </p>
                                        @endif

                                        <p class="mb-2">
                                            <span>{{ translate('Payment_Status') }} : </span>
                                            <span class="text-{{ $booking->is_paid ? 'success' : 'danger' }}"
                                                id="payment_status__span">{{ $booking->is_paid ? translate('Paid') : translate('Unpaid') }}</span>
                                            @if (!$booking->is_paid && $booking->booking_partial_payments->isNotEmpty())
                                                <span
                                                    class="small badge badge-info text-success p-1 fz-10">{{ translate('Partially paid') }}</span>
                                            @endif
                                        </p>

                                        <h5 class="d-flex gap-1 flex-wrap align-items-center">
                                            <div>{{ translate('Schedule_Date') }} :</div>
                                            <div id="service_schedule__span">
                                                <div>{{ date('d-M-Y h:ia', strtotime($booking->service_schedule)) }} <span
                                                        class="text-secondary">{{ $booking?->schedule_histories->count() > 1 ? '(' . translate('Edited') . ')' : '' }}</span>
                                                </div>

                                                <div class="timeline-container">
                                                    <ul class="timeline-sessions">
                                                        <p class="fs-14">{{ translate('Schedule Change Log') }}</p>
                                                        @foreach ($booking?->schedule_histories()->orderBy('created_at', 'desc')->get() as $history)
                                                            <li
                                                                class="{{ $booking->service_schedule == $history->schedule ? 'active' : '' }}">
                                                                <div class="timeline-date">
                                                                    {{ \Carbon\Carbon::parse($history->schedule)->format('d-M-Y') }}
                                                                </div>
                                                                <div class="timeline-time">
                                                                    {{ \Carbon\Carbon::parse($history->schedule)->format('h:i A') }}
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </h5>
                                    </div>
                                </div>
                            </div>

                            <div class="timeline-wrapper mt-4 ps-xl-5">
                                <div class="timeline-steps m-0">
                                    <div class="timeline-step completed">
                                        <div class="timeline-number">
                                            <svg viewBox="0 0 512 512" width="100" title="check">
                                                <path
                                                    d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z" />
                                            </svg>
                                        </div>
                                        <div class="timeline-info">
                                            <p class="timeline-title text-capitalize">{{ translate('Booking_Placed') }}
                                            </p>
                                            <p class="timeline-text">
                                                {{ date('d-M-Y h:ia', strtotime($booking->created_at)) }}</p>
                                            <p class="timeline-text">By
                                                -
                                                {{ isset($booking->customer) ? Str::limit($booking->customer->first_name . ' ' . $booking->customer->last_name, 30) : translate('Not_Available') }}
                                            </p>
                                        </div>
                                    </div>
                                    @foreach ($booking->status_histories as $status_history)
                                        <div class="timeline-step completed">
                                            <div class="timeline-number">
                                                <svg viewBox="0 0 512 512" width="100" title="check">
                                                    <path
                                                        d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z" />
                                                </svg>
                                            </div>
                                            <div class="timeline-info">
                                                <p class="timeline-title text-capitalize">
                                                    {{ $status_history->booking_status }}</p>
                                                <p class="timeline-text">
                                                    {{ date('d-M-Y h:ia', strtotime($status_history->created_at)) }}</p>
                                                <p class="timeline-text">{{ translate('By') }}
                                                    @if (isset($status_history->user->provider))
                                                        -
                                                        {{ Str::limit($status_history?->user?->provider?->company_name, 30) }}
                                                    @else
                                                        -
                                                        {{ isset($status_history->user) ? Str::limit($status_history->user->first_name . ' ' . $status_history->user->last_name, 30) : '' }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="c1">{{ translate('Booking Setup') }}</h3>
                            <hr>

                            <div class="py-3 d-flex flex-column gap-3 mb-2">

                                @if($booking->payment_method == 'offline_payment')
                                    <div class="border border-color-primary">
                                        <div class="card text-center">
                                            <div class="card-header">
                                                <h5 class="font-weight-bold">{{ translate('Verification of Offline Payment') }}</h5>
                                            </div>
                                            <div class="card-body">
                                                @if($booking->booking_offline_payments->isNotEmpty())
                                                    <div class="d-flex gap-1 flex-column">
                                                        @php($offlinePaymentNote = '')
                                                        @foreach ($booking?->booking_offline_payments?->first()?->customer_information ?? [] as $key => $item)
                                                            <div class="d-flex gap-2">
                                                                @if ($key != 'payment_note' )
                                                                    <span class="w-100px d-flex justify-content-start">{{ translate($key) }}</span>
                                                                    <span>: {{ translate($item) }}</span>
                                                                @endif
                                                            </div>
                                                                <?php
                                                                if ($key == 'payment_note' ){
                                                                    $offlinePaymentNote = $item;
                                                                }
                                                                ?>
                                                        @endforeach
                                                    </div>
                                                    @if($offlinePaymentNote != '')
                                                        <div class="badge-warning px-3 py-3 rounded title-color mt-3">
                                                        <span>
                                                            <span class="fw-semibold"> # {{ translate('Payment Note') }}:  </span>
                                                            {{ $offlinePaymentNote }}
                                                        </span>
                                                        </div>
                                                    @endif

                                                    @if($booking->booking_offline_payments?->first()?->payment_status == 'pending')
                                                        <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                                                            <button class="btn badge-danger flex-grow-1 py-3" data-bs-toggle="modal"
                                                                    data-bs-target="#deniedModal-{{$booking->id}}">{{ translate('deny') }}</button>
                                                            <button class="btn badge-info flex-grow-1 py-3 offline-payment">{{ translate('approve') }}</button>
                                                        </div>
                                                    @elseif($booking->booking_offline_payments?->first()?->payment_status == 'denied')
                                                        @if($booking['booking_status'] != 'canceled')
                                                            <div class="d-flex flex-column gap-2 mt-4">
                                                                <button class="btn badge-info w-100 py-3 switch-to-cash-after-service">{{ translate('Switch to Cash after Service') }}</button>
                                                            </div>
                                                        @endif
                                                    @endif

                                                @else
                                                    <img src="{{ asset('assets/admin-module/img/offline-payment.png') }}" alt="Payment Icon" class="mb-3">
                                                    <p class="text-muted">{{ translate('Customer did not submit any payment information yet') }}</p>
                                                    @if($booking['booking_status'] != 'canceled')
                                                        <div class="d-flex flex-column gap-2 mt-4">
                                                            <button class="btn badge-info w-100 py-3 switch-to-cash-after-service">{{ translate('Switch to Cash after Service') }}</button>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="c1-light-bg radius-10">
                                    <div class="border-bottom d-flex align-items-center justify-content-between gap-2 py-3 px-4 mb-2">
                                        <h4 class="d-flex align-items-center gap-2">
                                            <span class="material-icons title-color">person</span>
                                            {{ translate('Customer_Information') }}
                                        </h4>

                                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                            @can('whatsapp_chat_view')
                                                @if (!empty($customerPhone))
                                                    <button type="button"
                                                            class="btn btn-link p-0 border-0 d-inline-flex align-items-center wa-open-admin-chat"
                                                            data-phone="{{ e($customerPhone) }}"
                                                            data-prepare-url="{{ route('admin.whatsapp.conversations.prepare-open') }}"
                                                            title="{{ translate('WhatsApp') }} — {{ translate('chat_with_Customer') }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#25D366" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                                    </button>
                                                @endif
                                            @endcan
                                        </div>
                                    </div>

                                    <div class="py-3 px-4">
                                        <div class="media gap-2 flex-wrap">
                                            @if (!$booking?->is_guest && $booking?->customer)
                                                <img width="58" height="58"
                                                    class="rounded-circle border border-white aspect-square object-fit-cover"
                                                    src="{{ $booking?->customer?->profile_image_full_path }}"
                                                    alt="{{ translate('user_image') }}">
                                            @else
                                                <img width="58" height="58"
                                                    class="rounded-circle border border-white aspect-square object-fit-cover"
                                                    src="{{ asset('assets/provider-module/img/user2x.png') }}"
                                                    alt="{{ translate('user_image') }}">
                                            @endif
                                            <div class="media-body">
                                                <h5 class="c1 mb-3">
                                                    @if (!$booking?->is_guest && $booking?->customer)
                                                        <a href="{{ route('admin.customer.detail', [$booking?->customer?->id, 'web_page' => 'overview']) }}"
                                                            class="c1">{{ Str::limit($customerName ?? '', 30) }}</a>
                                                    @else
                                                        <span>{{ Str::limit($customerName ?? '', 30) }}</span>
                                                    @endif
                                                </h5>
                                                <ul class="list-info">
                                                    @if ($customerPhone ?? null)
                                                        <li>
                                                            <span class="material-icons">phone_iphone</span>
                                                            <a href="tel:{{ $customerPhone }}">{{ $customerPhone }}</a>
                                                        </li>
                                                    @endif
                                                    <li>
                                                        <span class="material-icons">map</span>
                                                        <p>{{ Str::limit($booking?->service_address?->address ?? translate('not_available'), 100) }}
                                                        </p>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="c1-light-bg radius-10 provider-information">
                                    <div
                                        class="border-bottom d-flex align-items-center justify-content-between gap-2 py-3 px-4 mb-2">
                                        <h4 class="d-flex align-items-center gap-2">
                                            <span class="material-icons title-color">person</span>
                                            {{ translate('Provider_Information') }}
                                        </h4>
                                        @if (isset($booking->provider))
                                            @php
                                                $providerWaPhoneStatus = trim((string) ($booking->provider->contact_person_phone ?? $booking->provider->company_phone ?? ''));
                                            @endphp
                                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                                @can('whatsapp_chat_view')
                                                    @if ($providerWaPhoneStatus !== '')
                                                        <button type="button"
                                                                class="btn btn-link p-0 border-0 d-inline-flex align-items-center wa-open-admin-chat"
                                                                data-phone="{{ e($providerWaPhoneStatus) }}"
                                                                data-prepare-url="{{ route('admin.whatsapp.conversations.prepare-open') }}"
                                                                title="{{ translate('WhatsApp') }} — {{ translate('chat_with_Provider') }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#25D366" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                                        </button>
                                                    @endif
                                                @endcan
                                                @if (in_array($booking->booking_status, ['ongoing', 'accepted']))
                                                    @can('booking_can_manage_status')
                                                        <span class="cursor-pointer d-inline-flex align-items-center" role="button" tabindex="0" data-bs-target="#providerModal" data-bs-toggle="modal" title="{{ translate('change_Provider') }}">
                                                            <span class="material-symbols-outlined">manage_history</span>
                                                        </span>
                                                    @endcan
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    @if (isset($booking->provider))
                                        <div class="py-3 px-4">
                                            <div class="media gap-2 flex-wrap">
                                                <img width="58" height="58"
                                                    class="rounded-circle border border-white aspect-square object-fit-cover"
                                                    src="{{ $booking?->provider?->logo_full_path }}"
                                                    alt="{{ translate('provider') }}">
                                                <div class="media-body">
                                                    <a
                                                        href="{{ route('admin.provider.details', [$booking?->provider?->id, 'web_page' => 'overview']) }}">
                                                        <h5 class="c1 mb-3">
                                                            {{ Str::limit($booking->provider->company_name ?? '', 30) }}
                                                        </h5>
                                                    </a>
                                                    <ul class="list-info">
                                                        <li>
                                                            <span class="material-icons">phone_iphone</span>
                                                            <a
                                                                href="tel:{{ $booking->provider->contact_person_phone ?? '' }}">{{ $booking->provider->contact_person_phone ?? '' }}</a>
                                                        </li>
                                                        <li>
                                                            <span class="material-icons">map</span>
                                                            <p>{{ Str::limit($booking->provider->company_address ?? '', 100) }}
                                                            </p>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="d-flex flex-column gap-2 mt-30 align-items-center">
                                            <span class="material-icons text-muted fs-2">account_circle</span>
                                            <p class="text-muted text-center fw-medium mb-3">
                                                {{ translate('No Provider Information') }}</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="c1-light-bg radius-10 serviceman-information">
                                    <div
                                        class="border-bottom d-flex align-items-center justify-content-between gap-2 py-3 px-4 mb-2">
                                        <h4 class="d-flex align-items-center gap-2">
                                            <span class="material-icons title-color">person</span>
                                            {{ translate('Serviceman_Information') }}
                                        </h4>
                                        @if (isset($booking->serviceman))
                                            <div class="btn-group">
                                                @if (in_array($booking->booking_status, ['ongoing', 'accepted']))

                                                    <div class="cursor-pointer" data-bs-toggle="dropdown"
                                                         aria-expanded="false">
                                                        <span class="material-symbols-outlined">more_vert</span>
                                                    </div>
                                                    <ul class="dropdown-menu dropdown-menu__custom border-none dropdown-menu-end">
                                                        <li>
                                                            <div class="d-flex align-items-center gap-2 cursor-pointer provider-chat">
                                                                <span class="material-symbols-outlined">chat</span>
                                                                {{ translate('chat_with_Serviceman') }}
                                                                <form action="{{ route('admin.chat.create-channel') }}"
                                                                      method="post" id="chatForm-{{ $booking->id }}">
                                                                    @csrf
                                                                    <input type="hidden" name="serviceman_id"
                                                                           value="{{ $booking?->serviceman?->user?->id }}">
                                                                    <input type="hidden" name="type" value="booking">
                                                                    <input type="hidden" name="user_type"
                                                                           value="provider-serviceman">
                                                                </form>
                                                            </div>
                                                        </li>
                                                        @can('booking_can_manage_status')
                                                            <li>
                                                                <div class="d-flex align-items-center gap-2"
                                                                     data-bs-target="#servicemanModal" data-bs-toggle="modal">
                                                                    <span
                                                                        class="material-symbols-outlined">manage_history</span>
                                                                    {{ translate('change serviceman') }}
                                                                </div>
                                                            </li>
                                                        @endcan
                                                    </ul>
                                                @endif
                                            </div>
                                        @endif

                                    </div>
                                    @if (isset($booking->serviceman))
                                        <div class="py-3 px-4">
                                            <div class="media gap-2 flex-wrap">
                                                <img width="58" height="58"
                                                    class="rounded-circle border border-white aspect-square object-fit-cover"
                                                    src="{{ $booking?->serviceman?->user?->profile_image_full_path }}"
                                                    alt="{{ translate('serviceman') }}">
                                                <div class="media-body">
                                                    <h5 class="c1 mb-3">
                                                        {{ Str::limit($booking->serviceman && $booking->serviceman->user ? $booking->serviceman->user->first_name . ' ' . $booking->serviceman->user->last_name : '', 30) }}
                                                    </h5>
                                                    <ul class="list-info">
                                                        <li>
                                                            <span class="material-icons">phone_iphone</span>
                                                            <a
                                                                href="tel:{{ $booking->serviceman && $booking->serviceman->user ? $booking->serviceman->user->phone : '' }}">
                                                                {{ $booking->serviceman && $booking->serviceman->user ? $booking->serviceman->user->phone : '' }}
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="d-flex flex-column gap-2 mt-30 align-items-center">
                                            <span class="material-icons text-muted fs-2">account_circle</span>
                                            <p class="text-muted text-center fw-medium mb-3">
                                                {{ translate('No Serviceman Information') }}</p>
                                        </div>

                                        <div class="text-center pb-4">
                                            <button
                                                class="btn btn--primary"
                                                data-bs-target="#servicemanModal"
                                                data-bs-toggle="modal"
                                                @if($booking['booking_status'] == 'completed' || $booking['booking_status'] == 'canceled' || !isset($booking->provider))
                                                    disabled
                                                @endif>
                                                {{ translate('assign Serviceman') }}
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('bookingmodule::admin.booking.partials.details._service-address-modal')

    @include('bookingmodule::admin.booking.partials.details._service-modal')

    <div class="modal fade" id="providerModal" tabindex="-1" aria-labelledby="providerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-content-data" id="modal-data-info">
                @include('bookingmodule::admin.booking.partials.details.provider-info-modal-data')
            </div>
        </div>
    </div>

    <div class="modal fade" id="servicemanModal" tabindex="-1" aria-labelledby="servicemanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-content-data1" id="modal-data-info1">
                @include('bookingmodule::admin.booking.partials.details.serviceman-info-modal-data')
            </div>
        </div>
    </div>

    @include('providermanagement::admin.partials.provider-performance-feedback-modal')

    <div class="modal fade" id="changeScheduleModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="changeScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.booking.schedule_update', [$booking->id]) }}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="changeScheduleModalLabel">{{ translate('Change_Booking_Schedule') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="datetime-local" id="service_schedule" name="service_schedule" class="form-control"
                            value="{{ $booking->service_schedule }}">
                    </div>
                    <div class="p-3 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ translate('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('Submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deniedModal-{{$booking->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body pt-5 p-md-5">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="d-flex justify-content-center mb-4">
                        <img width="75" height="75" src="{{asset('assets/admin-module/img/icons/info-round.svg')}}" class="rounded-circle" alt="">
                    </div>

                    <h3 class="text-start mb-1 fw-medium text-center">{{translate('Are you sure you want to deny?')}}</h3>
                    <p class="text-start fs-12 fw-medium text-muted text-center">{{translate('Please insert the deny note for this payment request')}}</p>
                    <form method="post" action="{{route('admin.booking.offline-payment.verify',['booking_id' => $booking->id, 'payment_status' => 'denied'])}}">
                        @csrf
                        <div class="form-floating">
                            <textarea class="form-control h-69px" placeholder="{{translate('Type here your note')}}" name="note" id="add-your-note" maxlength="255" required></textarea>
                            <label for="add-your-note" class="d-flex align-items-center gap-1">{{translate('Deny Note')}}</label>
                            <div class="d-flex justify-content-center mt-3 gap-3">
                                <button type="button" class="btn btn--secondary min-w-92px px-2" data-bs-dismiss="modal" aria-label="Close">{{translate('Not Now')}}</button>
                                <button type="submit" class="btn btn-primary min-w-92px">{{translate('Submit')}}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        'use strict'

        // Provider performance feedback must be submitted before proceeding with completion/cancellation/provider reassign.
        let pendingReassignProviderId = null;
        let pendingPostFeedbackAction = null; // 'reload' | 'reassign'

        const bookingContextId = @json($booking->id);
        const bookingCurrentProviderId = @json($booking->provider_id);

        function openProviderPerformanceFeedbackModal(evaluatedProviderId, actionType = 'completed') {
            $('#providerPerformanceContextBookingId').val(bookingContextId);
            $('#providerPerformanceProviderId').val(evaluatedProviderId);
            $('#providerPerformanceActionType').val(actionType === 'canceled' ? 'cancelled' : actionType);
            $('#providerPerformanceNotes').val('');
            $('#providerPerformanceFeedbackForm input[type="radio"]').prop('checked', false);
            $('#providerPerformanceFeedbackForm input[type="checkbox"]').prop('checked', false);

            // Prevent multiple Bootstrap modal backdrops from blocking clicks
            // (e.g. provider reassign modal still open when opening feedback).
            document.querySelectorAll('.modal.show').forEach((m) => {
                if (m?.id !== 'providerPerformanceFeedbackModal') {
                    bootstrap.Modal.getInstance(m)?.hide();
                }
            });
            $('.modal-backdrop').remove();

            const modalEl = document.getElementById('providerPerformanceFeedbackModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }

        function reassignProviderAfterFeedback(providerId) {
            const bookingId = "{{ $booking->id }}";
            const route = '{{ url('admin/provider/reassign-provider') }}' + '/' + bookingId;
            const sortOption = document.querySelector('input[name="sort"]:checked')?.value ?? 'default';
            const searchTerm = $('.search-form-input').val() ?? '';

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: route,
                type: 'PUT',
                dataType: 'json',
                data: {
                    sort_by: sortOption,
                    booking_id: bookingId,
                    search: searchTerm,
                    provider_id: providerId
                },
                beforeSend: function () {
                    toastr.info('{{ translate('Processing request...') }}');
                },
                success: function (response) {
                    if (response?.view) {
                        $('.modal-content-data').html(response.view);
                    }
                    toastr.success('{{ translate('Successfully reassign provider') }}');
                    setTimeout(function () {
                        location.reload();
                    }, 600);
                },
                error: function () {
                    toastr.error('{{ translate('Failed to load') }}');
                }
            });
        }

        $('#providerPerformanceFeedbackForm').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const route = $form.data('feedback-route');

            // Some actions may open the modal without setting pendingPostFeedbackAction.
            // Default to 'reassign' if a provider id is queued, otherwise just reload.
            if (!pendingPostFeedbackAction) {
                pendingPostFeedbackAction = pendingReassignProviderId ? 'reassign' : 'reload';
            }

            $.ajax({
                url: route,
                type: 'POST',
                dataType: 'json',
                data: $form.serialize(),
                beforeSend: function () {
                    $('#providerPerformanceFeedbackSubmit').prop('disabled', true);
                },
                success: function () {
                    $('#providerPerformanceFeedbackSubmit').prop('disabled', false);
                    const modalEl = document.getElementById('providerPerformanceFeedbackModal');
                    bootstrap.Modal.getInstance(modalEl)?.hide();

                    if (pendingPostFeedbackAction === 'reassign') {
                        const providerId = pendingReassignProviderId;
                        pendingReassignProviderId = null;
                        pendingPostFeedbackAction = null;
                        if (providerId) {
                            if (typeof window.updateProvider === 'function') {
                                window.updateProvider(providerId);
                            } else {
                                reassignProviderAfterFeedback(providerId);
                            }
                            return;
                        }
                    }

                    pendingReassignProviderId = null;
                    pendingPostFeedbackAction = null;
                    location.reload();
                },
                error: function (xhr) {
                    $('#providerPerformanceFeedbackSubmit').prop('disabled', false);
                    toastr.error(xhr?.responseJSON?.message ?? '{{ translate('Failed to store feedback') }}');
                }
            });
        });

        $(".switch-to-cash-after-service").on('click', function() {
            var payment_method = 'cash_after_service';
            var route = '{{ route('admin.booking.switch-payment-method', [$booking->id]) }}' + '?payment_method=' + payment_method;
            update_booking_details(route, '{{ translate('want_to_switch_payment_method_to_cash_after_service') }}', 'payment_method', payment_method);
        });

        function update_booking_details(route, message, componentId, updatedValue) {
            Swal.fire({
                title: "{{ translate('are_you_sure') }}?",
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'var(--bs-secondary)',
                confirmButtonColor: 'var(--bs-primary)',
                cancelButtonText: '{{ translate('Cancel') }}',
                confirmButtonText: '{{ translate('Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.get({
                        url: route,
                        dataType: 'json',
                        data: {},
                        beforeSend: function() {},
                        success: function(data) {
                            toastr.success(data.message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                            if (componentId === 'booking_status' && (updatedValue === 'completed' || updatedValue === 'canceled' || updatedValue === 'cancelled')) {
                                if (bookingCurrentProviderId) {
                                    pendingPostFeedbackAction = 'reload';
                                    openProviderPerformanceFeedbackModal(bookingCurrentProviderId, updatedValue);
                                    return;
                                }
                            }

                            location.reload();
                        },
                        complete: function() {},
                    });
                }
            })
        }


        $(document).on('click', '.reassign-provider', function() {
            let newProviderId = $(this).data('provider-reassign');
            pendingReassignProviderId = newProviderId;
            pendingPostFeedbackAction = 'reassign';

            const evaluatedProviderId = bookingCurrentProviderId ?? newProviderId;
            if (!evaluatedProviderId) {
                toastr.error('{{ translate('Provider not found for feedback.') }}');
                return;
            }

            openProviderPerformanceFeedbackModal(evaluatedProviderId, 'provider_changed');
        })

        $('.open-feedback-manual').on('click', function() {
            if (!bookingCurrentProviderId) {
                toastr.error('{{ translate('Provider not found for feedback.') }}');
                return;
            }
            pendingPostFeedbackAction = 'reload';
            openProviderPerformanceFeedbackModal(bookingCurrentProviderId, 'completed');
        });

        $('.reassign-serviceman').on('click', function() {
            let id = $(this).data('serviceman-reassign');
            updateServiceman(id)
        })

        function updateServiceman(servicemanId) {
            const bookingId = "{{ $booking->id }}";
            const route = '{{ url('admin/booking/serviceman-update') }}' + '/' + bookingId;
            const searchTerm = $('.search-form-input1').val();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: route,
                type: 'PUT',
                dataType: 'json',
                data: {
                    booking_id: bookingId,
                    search: searchTerm,
                    serviceman_id: servicemanId
                },
                beforeSend: function() {
                    toastr.info('{{ translate('Processing request...') }}');
                },
                success: function(response) {
                    $('.modal-content-data').html(response.view);
                    toastr.success('{{ translate('Successfully reassign provider') }}');
                    setTimeout(function() {
                        location.reload()
                    }, 600);
                },
                complete: function() {},
                error: function() {
                    toastr.error('{{ translate('Failed to load') }}');
                }
            });
        }

        $('.offline-payment').on('click', function() {
            let route = '{{ route('admin.booking.offline-payment.verify', ['booking_id' => $booking->id]) }}'+ '&payment_status=' + 'approved';
            route_alert_reload(route, '{{ translate('Want to verify the payment') }}', true);
        })

        $(document).ready(function() {
            $('#category_selector__select').select2({
                dropdownParent: "#serviceUpdateModal--{{ $booking['id'] }}"
            });
            $('#sub_category_selector__select').select2({
                dropdownParent: "#serviceUpdateModal--{{ $booking['id'] }}"
            });
            $('#service_selector__select').select2({
                dropdownParent: "#serviceUpdateModal--{{ $booking['id'] }}"
            });
            $('#service_variation_selector__select').select2({
                dropdownParent: "#serviceUpdateModal--{{ $booking['id'] }}"
            });
        });

        $("#service_selector__select").on('change', function() {
            $("#service_variation_selector__select").html(
                '<option value="" selected disabled>{{ translate('Select Service Variant') }}</option>');

            const serviceId = this.value;
            const route = '{{ route('admin.booking.service.ajax-get-variant') }}' + '?service_id=' + serviceId +
                '&zone_id=' + "{{ $booking->zone_id }}";

            $.get({
                url: route,
                dataType: 'json',
                data: {},
                beforeSend: function() {
                    $('.preloader').show();
                },
                success: function(response) {
                    var selectString =
                        '<option value="" selected disabled>{{ translate('Select Service Variant') }}</option>';
                    response.content.forEach((item) => {
                        selectString +=
                            `<option value="${item.variant_key}">${item.variant}</option>`;
                    });
                    $("#service_variation_selector__select").html(selectString)
                },
                complete: function() {
                    $('.preloader').hide();
                },
                error: function() {
                    toastr.error('{{ translate('Failed to load') }}')
                }
            });
        })

        $("#serviceUpdateModal--{{ $booking['id'] }}").on('hidden.bs.modal', function() {
            $('#service_selector__select').prop('selectedIndex', 0);
            $("#service_variation_selector__select").html(
                '<option value="" selected disabled>{{ translate('Select Service Variant') }}</option>');
            $("#service_quantity").val('');
        });

        $("#add-service").on('click', function() {
            const service_id = $("[name='service_id']").val();
            const variant_key = $("[name='variant_key']").val();
            const quantity = parseInt($("[name='service_quantity']").val());
            const zone_id = '{{ $booking->zone_id }}';


            if (service_id === '' || service_id === null) {
                toastr.error('{{ translate('Select a service') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            } else if (variant_key === '' || variant_key === null) {
                toastr.error('{{ translate('Select a variation') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            } else if (quantity < 1) {
                toastr.error('{{ translate('Quantity must not be empty') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            }

            let variant_key_array = [];
            $('input[name="variant_keys[]"]').each(function() {
                variant_key_array.push($(this).val());
            });

            if (variant_key_array.includes(variant_key)) {
                const decimal_point = parseInt(
                    '{{ business_config('currency_decimal_point', 'business_information')->live_values ?? 2 }}'
                    );

                const old_qty = parseInt($(`#qty-${variant_key}`).val());
                const updated_qty = old_qty + quantity;

                const old_total_cost = parseFloat($(`#total-cost-${variant_key}`).text());
                const updated_total_cost = ((old_total_cost * updated_qty) / old_qty).toFixed(decimal_point);

                const old_discount_amount = parseFloat($(`#discount-amount-${variant_key}`).text());
                const updated_discount_amount = ((old_discount_amount * updated_qty) / old_qty).toFixed(
                    decimal_point);


                $(`#qty-${variant_key}`).val(updated_qty);
                $(`#total-cost-${variant_key}`).text(updated_total_cost);
                $(`#discount-amount-${variant_key}`).text(updated_discount_amount);

                toastr.success('{{ translate('Added successfully') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            }

            let query_string = 'service_id=' + service_id + '&variant_key=' + variant_key + '&quantity=' +
                quantity + '&zone_id=' + zone_id;
            $.ajax({
                type: 'GET',
                url: "{{ route('admin.booking.service.ajax-get-service-info') }}" + '?' + query_string,
                data: {},
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('.preloader').show();
                },
                success: function(response) {
                    $("#service-edit-tbody").append(response.view);
                    toastr.success('{{ translate('Added successfully') }}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                complete: function() {
                    $('.preloader').hide();
                },
            });
        })

        $(".remove-service-row").on('click', function() {
            let row = $(this).data('row');
            removeServiceRow(row)
        })

        function removeServiceRow(row) {
            const row_count = $('#service-edit-tbody tr').length;
            if (row_count <= 1) {
                toastr.error('{{ translate('Can not remove the only service') }}');
                return;
            }

            Swal.fire({
                title: "{{ translate('are_you_sure') }}?",
                text: '{{ translate('want to remove the service from the booking') }}',
                type: 'warning',
                showCloseButton: true,
                showCancelButton: true,
                cancelButtonColor: 'var(--bs-secondary)',
                confirmButtonColor: 'var(--bs-primary)',
                cancelButtonText: 'Cancel',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $(`#${row}`).remove();
                }
            })
        }
    </script>


    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ business_config('google_map', 'third_party')?->live_values['map_api_key_client'] }}&libraries=places&v=3.45.8">
    </script>
    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    $('#viewer').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function() {
            readURL(this);
        });


        $(document).ready(function() {
            function initAutocomplete() {
                let myLatLng = {

                    lat: 23.811842872190343,
                    lng: 90.356331
                };
                const map = new google.maps.Map(document.getElementById("location_map_canvas"), {
                    center: {
                        lat: 23.811842872190343,
                        lng: 90.356331
                    },
                    zoom: 13,
                    mapTypeId: "roadmap",
                });

                let marker = new google.maps.Marker({
                    position: myLatLng,
                    map: map,
                });

                marker.setMap(map);
                var geocoder = geocoder = new google.maps.Geocoder();
                google.maps.event.addListener(map, 'click', function(mapsMouseEvent) {
                    var coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
                    var coordinates = JSON.parse(coordinates);
                    var latlng = new google.maps.LatLng(coordinates['lat'], coordinates['lng']);
                    marker.setPosition(latlng);
                    map.panTo(latlng);

                    document.getElementById('latitude').value = coordinates['lat'];
                    document.getElementById('longitude').value = coordinates['lng'];


                    geocoder.geocode({
                        'latLng': latlng
                    }, function(results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            if (results[1]) {
                                document.getElementById('address').innerHtml = results[1]
                                    .formatted_address;
                            }
                        }
                    });
                });

                const input = document.getElementById("pac-input");
                const searchBox = new google.maps.places.SearchBox(input);
                map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);

                map.addListener("bounds_changed", () => {
                    searchBox.setBounds(map.getBounds());
                });
                let markers = [];

                searchBox.addListener("places_changed", () => {
                    const places = searchBox.getPlaces();

                    if (places.length == 0) {
                        return;
                    }

                    markers.forEach((marker) => {
                        marker.setMap(null);
                    });
                    markers = [];

                    const bounds = new google.maps.LatLngBounds();
                    places.forEach((place) => {
                        if (!place.geometry || !place.geometry.location) {
                            console.log("Returned place contains no geometry");
                            return;
                        }
                        var mrkr = new google.maps.Marker({
                            map,
                            title: place.name,
                            position: place.geometry.location,
                        });
                        google.maps.event.addListener(mrkr, "click", function(event) {
                            document.getElementById('latitude').value = this.position.lat();
                            document.getElementById('longitude').value = this.position
                        .lng();
                        });

                        markers.push(mrkr);

                        if (place.geometry.viewport) {
                            bounds.union(place.geometry.viewport);
                        } else {
                            bounds.extend(place.geometry.location);
                        }
                    });
                    map.fitBounds(bounds);
                });
            };
            initAutocomplete();
        });


        $('.__right-eye').on('click', function() {
            if ($(this).hasClass('active')) {
                $(this).removeClass('active')
                $(this).find('i').removeClass('tio-invisible')
                $(this).find('i').addClass('tio-hidden-outlined')
                $(this).siblings('input').attr('type', 'password')
            } else {
                $(this).addClass('active')
                $(this).siblings('input').attr('type', 'text')


                $(this).find('i').addClass('tio-invisible')
                $(this).find('i').removeClass('tio-hidden-outlined')
            }
        })
    </script>

    <script>
        $(document).ready(function() {

            $(document).on('click', '.sort-by-class', function() {
                console.log('hi')
                const route = '{{ url('admin/provider/available-provider') }}'
                var sortOption = document.querySelector('input[name="sort"]:checked').value;
                var bookingId = "{{ $booking->id }}"

                $.get({
                    url: route,
                    dataType: 'json',
                    data: {
                        sort_by: sortOption,
                        booking_id: bookingId
                    },
                    beforeSend: function() {

                    },
                    success: function(response) {
                        $('.modal-content-data').html(response.view);
                    },
                    complete: function() {},
                    error: function() {
                        toastr.error('{{ translate('Failed to load') }}')
                    }
                });
            })
        });

        $(document).ready(function() {
            $(document).on('keyup', '.search-form-input', function() {
                const route = '{{ url('admin/provider/available-provider') }}';
                let sortOption = document.querySelector('input[name="sort"]:checked').value;
                let bookingId = "{{ $booking->id }}";
                let searchTerm = $('.search-form-input').val();

                $.get({
                    url: route,
                    dataType: 'json',
                    data: {
                        sort_by: sortOption,
                        booking_id: bookingId,
                        search: searchTerm,
                    },
                    beforeSend: function() {},
                    success: function(response) {
                        $('.modal-content-data').html(response.view);


                        var cursorPosition = searchTerm.lastIndexOf(searchTerm.charAt(searchTerm
                            .length - 1)) + 1;
                        $('.search-form-input').focus().get(0).setSelectionRange(cursorPosition,
                            cursorPosition);
                    },
                    complete: function() {},
                    error: function() {
                        toastr.error('{{ translate('Failed to load') }}');
                    }
                });
            });
        });

        function updateProvider(providerId) {
            const bookingId = "{{ $booking->id }}";
            const route = '{{ url('admin/provider/reassign-provider') }}' + '/' + bookingId;
            const sortOption = document.querySelector('input[name="sort"]:checked').value;
            const searchTerm = $('.search-form-input').val();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: route,
                type: 'PUT',
                dataType: 'json',
                data: {
                    sort_by: sortOption,
                    booking_id: bookingId,
                    search: searchTerm,
                    provider_id: providerId
                },
                beforeSend: function() {

                },
                success: function(response) {
                    $('.modal-content-data').html(response.view);
                    toastr.success('{{ translate('Successfully reassign provider') }}');
                    setTimeout(function() {
                        location.reload()
                    }, 600);
                },
                complete: function() {},
                error: function() {
                    toastr.error('{{ translate('Failed to load') }}');
                }
            });
        }

        $(document).ready(function() {
            $('.your-button-selector').on('click', function() {
                updateSearchResults();
            });

            $('.cancellation-note').hide();

            $('.deny-request').click(function() {
                $('.cancellation-note').show();
            });

            $('.approve-request').click(function() {
                $('.cancellation-note').hide();
            });
        });

        $('.customer-chat').on('click', function() {
            $(this).find('form').submit();
        });

        $('.provider-chat').on('click', function() {
            $(this).find('form').submit();
        });

        $(document).on('click', '.wa-open-admin-chat', function (e) {
            e.preventDefault();
            var $btn = $(this);
            if ($btn.data('wa-opening')) {
                return;
            }
            var phone = $btn.data('phone');
            var url = $btn.data('prepare-url');
            if (!phone || !url) {
                return;
            }
            $btn.data('wa-opening', true);
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    phone: String(phone),
                },
                success: function (res) {
                    if (res && res.redirect_url) {
                        window.location.href = res.redirect_url;
                        return;
                    }
                    $btn.data('wa-opening', false);
                    toastr.error('{{ translate('Something went wrong') }}');
                },
                error: function (xhr) {
                    $btn.data('wa-opening', false);
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : '{{ translate('Something went wrong') }}';
                    toastr.error(msg);
                },
            });
        });


        $(document).ready(function() {
            var check_aprove_model_status = $('.check-approve-status').val();
            if (check_aprove_model_status == "approve") {
                $('#add-your-note').removeAttr('required');
            }
            $('.check-approve-status').change(function() {
                check_aprove_model_status = $('.check-approve-status').val();
                if (check_aprove_model_status == "approve") {
                    $('#add-your-note').removeAttr('required');
                }
            });
            $('.check-deny-status').change(function() {
                $('#add-your-note').attr('required', true);
            });
        });
    </script>
@endpush
