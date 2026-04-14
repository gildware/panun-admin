@extends('adminmodule::layouts.master')

@section('title', translate('Booking_Service_log'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Booking_Details') }} </h2>
            </div>

            <div class="pb-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <h3 class="c1 d-flex align-items-center gap-1">
                            {{ translate('Repeat_Booking') }} # {{ $booking['readable_id'] }}
                            <img width="34" height="34"
                                src="{{ asset('assets/admin-module/img/icons/repeat.svg') }}"
                                class="rounded-circle repeat-icon" alt="{{ translate('repeat') }}">
                        </h3>
                        <span class="badge badge-{{
                            $booking->booking_status == 'ongoing' ? 'warning' :
                            ($booking->booking_status == 'completed' ? 'success' :
                            ($booking->booking_status == 'canceled' ? 'danger' : 'info'))
                        }}">
                            {{ ucwords($booking->booking_status) }}
                        </span>
                    </div>
                    <p class="opacity-75 fz-12">{{ translate('Booking_Placed') }}
                        : {{ date('d-M-Y h:ia', strtotime($booking->created_at)) }}</p>
                </div>
                <div class="d-flex flex-wrap flex-xxl-nowrap gap-3">
                    <div class="d-flex flex-wrap gap-3">
                        @if ($booking['payment_method'] == 'offline_payment' && !$booking['is_paid'])
                            @can('booking_can_approve_or_deny')
                                <span class="btn btn--primary offline-payment" data-id="{{ $booking->id }}">
                                    <span class="material-icons">done</span>{{ translate('Verify Offline Payment') }}
                                </span>
                            @endcan
                        @endif
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
                                                <div class="text-center">
                                                    <img class="mb-4"
                                                        src="{{ asset('/assets/admin-module/img/booking-req-status.png') }}"
                                                        alt="">
                                                </div>
                                                <h3 class="mb-1 fw-medium">
                                                    {{ translate('Verify the booking request status?') }}</h3>
                                                <p class="fs-12 fw-medium text-muted">
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
                                                            <input class="form-check-input deny-request" type="radio"
                                                                name="status" id="inlineRadio2" value="deny">
                                                            <label class="form-check-label"
                                                                for="inlineRadio2">{{ translate('Deny the Request') }}</label>
                                                        </div>
                                                    </div>
                                                    <div class="mt-4 cancellation-note" style="display: none;">
                                                        <textarea class="form-control h-69px" placeholder="{{ translate('Cancellation Note ...') }}" name="booking_deny_note"
                                                            id="add-your-note"></textarea>
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
                        @if (
                            $booking['payment_method'] == 'cash_after_service' &&
                                $booking->is_verified == '2' &&
                                $booking->total_booking_amount >= $maxBookingAmount)
                            @can('booking_can_manage_status')
                                <span class="btn btn--primary change-booking-request" data-id="{{ $booking->id }}"
                                    data-bs-toggle="modal" data-bs-target="#exampleModals--{{ $booking->id }}">
                                    <span class="material-icons">done</span>{{ translate('Change Request Status') }}
                                </span>
                            @endcan

                            <div class="modal fade" id="exampleModals--{{ $booking->id }}" tabindex="-1"
                                aria-labelledby="exampleModalLabels--{{ $booking->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-body pt-5 p-md-5">
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
                                                            <input class="form-check-input approve-request" checked
                                                                type="radio" name="status" id="inlineRadio1"
                                                                value="approve">
                                                            <label class="form-check-label"
                                                                for="inlineRadio1">{{ translate('Approve the Request') }}</label>
                                                        </div>
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

                            @if (in_array($booking['booking_status'], ['accepted', 'ongoing']) && !is_null($booking->nextService) && !$booking->nextService['is_paid'] && $booking->nextService['payment_method'] == 'cash_after_service')
                            @can('booking_edit')
                                <button class="btn btn--primary" data-bs-toggle="modal"
                                        data-bs-target="#serviceUpdateModal--{{ $booking['id'] }}" data-toggle="tooltip"
                                        title="{{ translate('Add or remove services') }}">
                                    <span class="material-symbols-outlined">edit</span>{{ translate('Edit Services') }}
                                </button>
                            @endcan
                        @endif
                        <a href="{{ route('admin.booking.full_repeat_invoice', [$booking->id]) }}" class="btn btn-primary"
                            target="_blank">
                            <span class="material-icons">description</span>{{ translate('Invoice') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center flex-xxl-nowrap gap-3 mb-4">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'details' ? 'active' : '' }}"
                           href="{{ url()->current() }}?web_page=details">{{ translate('details') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'service_log' ? 'active' : '' }}"
                           href="{{ url()->current() }}?web_page=service_log">{{ translate('service_log') }}</a>
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

                @if ($booking->is_paid == 0 && $booking->payment_method == 'offline_payment')
                    <div class="border border-danger-light bg-soft-danger rounded py-3 px-3 text-dark">
                        <span>
                            <span class="text-danger fw-semibold"> # {{ translate('Note: ') }} </span>
                            {{ translate('Please Check & Verify the payment information weather it is correct or not before confirm the booking. ') }}
                        </span>
                    </div>
                @endif

            </div>

            <div class="row gy-3">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            @if (!empty($booking['nextService'] && $booking['booking_status'] != 'pending'))
                                <h5 class="mb-3">{{ translate('Ongoing') }}</h5>
                                <div class="p-4 mb-3 d-flex flex-column gap-3">
                                    <div class="card card-border">
                                        <div class="card-body">
                                            <div class="d-flex flex-wrap justify-content-between align-content-center gap-3">
                                                <div>
                                                    <h6 class="fz-13 mb-2">{{ translate('Booking Id') }}
                                                        #{{ $booking->nextService['readable_id'] }}
                                                    </h6>
                                                    <p class="fz-12 mb-1">{{ date('d-M-Y h:ia', strtotime($booking->nextService['service_schedule'])) }}</p>
                                                    @if($booking->nextService['is_reassign'])
                                                        <span class="text-primary fw-semibold">
                                                                (
                                                                <span class="title-color opacity-75">{{ translate('Reassigned to') }}</span>
                                                                {{ $booking->provider->company_name }}
                                                                )
                                                            </span>
                                                    @endif
                                                </div>
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <a href="{{ route('admin.booking.single_invoice', [$booking->nextService['id']]) }}" type="button" target="_blank"
                                                        class="action-btn btn--light-primary text-primary fw-medium text-capitalize fz-14"
                                                        style="--size: 30px">
                                                        <span class="material-icons">description</span>
                                                    </a>
                                                    <a href="{{ route('admin.booking.repeat_single_details', [$booking->nextService['id'], 'web_page' => 'details'])}}" type="button"
                                                        class="action-btn btn--light-primary fw-medium text-capitalize fz-14"
                                                        style="--size: 30px">
                                                        <span class="material-icons">visibility</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(!empty($booking['completeCancel']))
                                <h5 class="mb-3">{{ translate('Completed & Canceled') }}</h5>
                                @foreach($booking['completeCancel'] as $data)
                                    <div class="d-flex align-items-center mb-3 gap-20">
                                        <div>
                                            <span class="fz-14 color-93A2AE">#{{ $loop->iteration }}</span>
                                        </div>
                                        <div class="card card-border w-100">
                                            <div class="card-body">
                                                <div class="d-flex flex-wrap justify-content-between align-content-center gap-3">
                                                    <div>
                                                        <h6 class="fz-13 mb-2">{{ translate('Booking Id') }}
                                                            #{{ $data['readable_id']}}
                                                        </h6>
                                                        <p class="fz-12">{{ date('d-M-Y h:ia', strtotime($data['service_schedule'])) }}</p>
                                                    </div>
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <a href="{{ route('admin.booking.single_invoice', [$data['id']]) }}" type="button"
                                                           class="action-btn btn--light-primary text-primary fw-medium text-capitalize fz-14" target="_blank"
                                                           style="--size: 30px">
                                                            <span class="material-icons">description</span>
                                                        </a>
                                                        <a href="{{ route('admin.booking.repeat_single_details', [$data['id'], 'web_page' => 'details'])}}" type="button"
                                                           class="action-btn btn--light-primary fw-medium text-capitalize fz-14"
                                                           style="--size: 30px">
                                                            <span class="material-icons">visibility</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            @if(!empty($booking['upComing']))
                                <h5 class="mb-3">{{ translate('Upcoming') }}</h5>
                                @foreach($booking['upComing'] as $upComing)
                                    <div class="d-flex align-items-center mb-3 gap-20">
                                        <div>
                                            <span class="fz-14 color-93A2AE">#{{ $loop->iteration }}</span>
                                        </div>
                                        <div class="card card-border w-100 shadow-none">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center gap-3">
                                                    <div>
                                                        <h6 class="fz-13 mb-2">{{ translate('Booking Id') }}
                                                            #{{ $upComing['readable_id'] }}
                                                            @if(count($upComing['schedule_histories']) > 1)
                                                            <span class="title-color opacity-75">({{ translate('Rescheduled') }})</span>
                                                            @endif
                                                        </h6>
                                                        <p class="fz-12 mb-1">{{ date('d-M-Y h:ia', strtotime($upComing['service_schedule'])) }}</p>
                                                        @if($upComing['is_reassign'])
                                                            <span class="text-primary fw-semibold">
                                                                (
                                                                <span class="title-color opacity-75">{{ translate('Reassigned to') }}</span>
                                                                {{ $booking->provider->company_name }}
                                                                )
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="dropstart">
                                                        <a href="javascript:" class="title-color" data-bs-toggle="dropdown">
                                                            <span class="material-icons">more_vert</span>
                                                        </a>
                                                        <ul class="dropdown-menu border-none dropdown-menu-left p-2">
                                                            <li><a class="dropdown-item d-flex align-items-center gap-1" type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reschedule-{{$upComing['id']}}"
                                                                    href="javascript:">
                                                                    <span class="material-icons">
                                                                        pending_actions
                                                                    </span>
                                                                    {{ translate('Reschedule') }}
                                                                </a>
                                                            </li>
                                                            <li><a class="dropdown-item d-flex align-items-center gap-1" target="_blank"
                                                                    href="{{ route('admin.booking.single_invoice', [$upComing['id']]) }}">
                                                                    <span class="material-icons">
                                                                        download
                                                                    </span>
                                                                    {{ translate('download_invoice') }}
                                                                </a>
                                                            </li>
                                                            @if(isset($upComing['booking_status']) && booking_admin_status_transition_allowed($upComing['booking_status'], 'on_hold'))
                                                            <li>
                                                                <button type="button"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#upcomingHoldModal-{{ $upComing['id'] }}"
                                                                        class="dropdown-item d-flex align-items-center gap-1 {{ env('APP_ENV') != 'demo' ? '' : 'demo_check' }}">
                                                                    <span class="material-icons">pause_circle</span>
                                                                    {{ translate('Put_on_hold') }}
                                                                </button>
                                                            </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="upcomingHoldModal-{{ $upComing['id'] }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form method="post" action="{{ route('admin.booking.up_coming_booking_cancel', [$upComing['id']]) }}">
                                                    @csrf
                                                    <input type="hidden" name="booking_status" value="on_hold">
                                                    <div class="modal-header border-0">
                                                        <h5 class="modal-title">{{ translate('Put_on_hold') }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                                                    </div>
                                                    <div class="modal-body pt-0">
                                                        <p class="small text-muted mb-3">{{ translate('want_to_update_status') }}</p>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ translate('Hold_reason') }} <span class="text-danger">*</span></label>
                                                            <select name="booking_hold_reopen_reason_id" class="form-select" required>
                                                                <option value="">{{ translate('Select') }}</option>
                                                                @foreach($bookingHoldReasons ?? [] as $r)
                                                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-0">
                                                            <label class="form-label">{{ translate('Status_change_remarks') }}</label>
                                                            <textarea name="status_change_remarks" class="form-control" rows="2" maxlength="2000"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0">
                                                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                                        <button type="submit" class="btn btn--primary">{{ translate('Confirm') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="reschedule-{{$upComing['id']}}" data-bs-backdrop="static" tabindex="-1" aria-labelledby="" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="">{{translate('Repeat Booking reschedule')}}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                    <form action="{{ route('admin.booking.up_coming_booking_schedule_update', [$upComing['id']]) }}" method="post">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <input type="datetime-local" class="form-control h-45" name="service_schedule"
                                                                   value="{{$upComing['service_schedule']}}" min="<?php echo date('Y-m-d\TH:i'); ?>">
                                                            <div class="pt-3 text-end">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{translate('Close')}}</button>
                                                                <button type="submit" class="btn btn--primary">{{translate('Save changes')}}</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="c1">{{ translate('Booking Setup') }}</h3>
                            <hr>
                            <div class="mt-3">
                                @can('booking_can_manage_status')
                                    <div class="mt-3">
                                        @php
                                            $__slStatusNext = booking_admin_allowed_next_statuses_for_booking($booking);
                                            $maxBookingAmountSl = business_config('max_booking_amount', 'booking_setup')->live_values ?? 0;
                                            $__slStatusCashBlock = $booking['payment_method'] == 'cash_after_service' && $booking->is_verified == '2' && $booking->total_booking_amount >= $maxBookingAmountSl;
                                        @endphp
                                        <select class="js-select without-search" id="booking_status" data-current="{{ $booking->booking_status }}" data-can-complete="{{ booking_can_be_completed($booking) ? '1' : '0' }}">
                                            <option value="0" disabled selected>{{ translate('Booking_Status') }}: {{ ucwords(str_replace('_', ' ', $booking->booking_status)) }}</option>
                                            @foreach ($__slStatusNext as $__selSt)
                                                @php
                                                    $__slOptDisabled = $__slStatusCashBlock && in_array($__selSt, ['pending', 'ongoing', 'completed'], true);
                                                    if ($__selSt === 'completed' && ! booking_can_be_completed($booking)) {
                                                        $__slOptDisabled = true;
                                                    }
                                                    $__slOptLabel = match ($__selSt) {
                                                        'accepted' => translate('Accept_Booking'),
                                                        'pending' => translate('Mark_as_Pending'),
                                                        'ongoing' => translate('Mark_as_Ongoing'),
                                                        'on_hold' => ($booking->booking_status ?? '') === 'ongoing' ? translate('Hold_after_visit') : translate('Put_on_hold'),
                                                        'completed' => translate('Complete_Booking'),
                                                        'canceled', 'cancelled' => translate('Cancel_Booking'),
                                                        default => ucwords(str_replace('_', ' ', $__selSt)),
                                                    };
                                                @endphp
                                                <option value="{{ $__selSt }}" @if($__slOptDisabled) disabled @endif>{{ $__slOptLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endcan
                            </div>

                            <div class="py-3 d-flex flex-column gap-3 mb-2">
                                <div class="c1-light-bg radius-10">
                                    <div
                                        class="border-bottom d-flex align-items-center justify-content-between gap-2 py-3 px-4 mb-2">
                                        <h4 class="d-flex align-items-center gap-2">
                                            <span class="material-icons title-color">person</span>
                                            {{ translate('Customer_Information') }}
                                        </h4>

                                        <div class="btn-group">
                                            @if ($booking['booking_status'] == 'pending' ||
                                                      $booking['booking_status'] == 'accepted' ||
                                                       $booking['booking_status'] == 'ongoing')
                                                <div class="cursor-pointer" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span class="material-symbols-outlined">more_vert</span>
                                                </div>
                                                <ul class="dropdown-menu dropdown-menu__custom border-none dropdown-menu-end">
                                                    <li data-bs-toggle="modal"
                                                        data-bs-target="#serviceAddressModal--{{ $booking['id'] }}"
                                                        data-toggle="tooltip" data-placement="top">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="material-symbols-outlined">edit_square</span>
                                                            {{ translate('Edit_Details') }}
                                                        </div>
                                                    </li>
                                                </ul>
                                            @endif

                                        </div>
                                    </div>

                                    <div class="py-3 px-4">
                                        @php($customer_name = booking_display_customer_name($booking, $customerAddress))
                                        @php($customer_phone = booking_display_customer_phone($booking, $customerAddress))

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
                                                            class="c1">{{ Str::limit($customer_name, 30) }}</a>
                                                    @else
                                                        <span>{{ Str::limit($customer_name ?? '', 30) }}</span>
                                                    @endif
                                                </h5>
                                                <ul class="list-info">
                                                    @if ($customer_phone)
                                                        <li>
                                                            <span class="material-icons">phone_iphone</span>
                                                            <a
                                                                href="tel:{{ $customer_phone }}">{{ $customer_phone }}</a>
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
                                            <div class="btn-group">
                                                <div class="cursor-pointer" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <span class="material-symbols-outlined">more_vert</span>
                                                </div>
                                                <ul
                                                    class="dropdown-menu dropdown-menu__custom border-none dropdown-menu-end">
                                                    @if (booking_admin_can_reassign_provider($booking) && in_array($booking->booking_status, ['accepted', 'pending', 'on_hold']))
                                                        <li>
                                                            <div class="d-flex align-items-center gap-2"
                                                                data-bs-target="#providerModal" data-bs-toggle="modal">
                                                                <span
                                                                    class="material-symbols-outlined">manage_history</span>
                                                                {{ translate('change_Provider') }}
                                                            </div>
                                                        </li>
                                                    @endif
                                                    <li>
                                                        <a class="d-flex align-items-center gap-2 cursor-pointer p-0"
                                                            href="{{ route('admin.provider.details', [$booking?->provider?->id, 'web_page' => 'overview']) }}">
                                                            <span class="material-icons">person</span>
                                                            {{ translate('View_Details') }}
                                                        </a>
                                                    </li>
                                                </ul>
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
                                        @if($booking['booking_status'] != 'canceled')
                                            <div class="text-center pb-4">
                                                <button class="btn btn--primary" data-bs-target="#providerModal" data-bs-toggle="modal">{{ translate('assign provider') }}</button>
                                            </div>
                                        @endif
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

    @include('bookingmodule::admin.booking.partials._booking-status-reason-modal')

    <div class="modal fade" id="providerModal" tabindex="-1" aria-labelledby="providerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-content-data" id="modal-data-info">
                @include('bookingmodule::admin.booking.partials.details.provider-info-modal-data')
            </div>
        </div>
    </div>

    @include('providermanagement::admin.partials.provider-performance-feedback-modal')
    @if(!is_null($booking->nextService))
        <div class="modal fade" id="serviceUpdateModal--{{ $booking['id'] }}" tabindex="-1"
             aria-labelledby="serviceUpdateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header px-4 pt-4 border-0 pb-1">
                        <h3 class="text-capitalize">{{ translate('update_booking_list') }}</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4">
                        <div class="d-flex align-items-end justify-content-between gap-3 flex-wrap mb-4">
                            <div>
                                <h5 class="mb-2">
                                    {{ translate('Booking') }} # {{ $booking['readable_id'] }}
                                </h5>
                                <h3 class="c1 fw-bold mb-2">{{ translate('Sub-Booking') }} # {{ $booking->nextService['readable_id']}}
                                </h3>
                            </div>
                            <h5 class="d-flex gap-1 flex-wrap align-items-center justify-content-end fw-normal mb-2">
                                <div>{{ translate('Schedule_Date') }} :</div>
                                <div id="service_schedule__span">
                                    <div class="fw-semibold">{{ date('d-M-Y h:ia', strtotime($booking->created_at)) }}</div>
                                </div>
                            </h5>
                        </div>

                        <div class="bg-F8F8F8 p-3 mb-3">
                            <h4 class="mb-3"> {{ translate('Service') }} : {{ translate('AC_Repairing') }}
                            </h4>
                            <div class="d-flex flex-wrap gap-3">
                                <h4> {{ translate('Category') }} : {{ $booking->category->name }}</h4>
                                <h4> {{ translate('SubCategory') }} : {{ $booking->subCategory->name }}</h4>
                            </div>
                        </div>

                        <div class="mb-30">
                            <span class="c1 fw-semibold"> # {{ translate('Note') }}:</span>
                            <span class="title-color">
                            {{ translate('Please provide extra layer in the packaging') }}</span>
                        </div>

                        <form action="{{ route('admin.booking.service.update_repeat_booking_service') }}" method="POST"
                              id="booking-edit-table" class="mb-30">
                            <div class="table-responsive">
                                <table class="table text-nowrap align-middle mb-0" id="service-edit-table">
                                    @csrf
                                    @method('put')
                                    <thead>
                                    <tr>
                                        <th class="ps-lg-3 fw-bold">{{ translate('Service') }}</th>
                                        <th class="fw-bold text--end">{{ translate('Price') }}</th>
                                        <th class="fw-bold text-center">{{ translate('Qty') }}</th>
                                        <th class="fw-bold text--end">{{ translate('Discount') }}</th>
                                        <th class="fw-bold text--end">{{ translate('Total') }}</th>
                                    </tr>
                                    </thead>

                                    <tbody id="service-edit-tbody">
                                    @php($sub_total = 0)
                                    @foreach ($booking->nextService['detail'] as $key => $detail)
                                        <tr id="service-row--{{ $detail['variant_key'] }}">
                                            <td class="text-wrap ps-lg-3">
                                                @if (isset($detail['service']))
                                                    <div class="d-flex flex-column">
                                                        <a href="{{ route('admin.service.detail', [$detail['service']['id']]) }}"
                                                           class="fw-bold">{{ Str::limit($detail['service']['name'], 30) }}</a>
                                                        <div>{{ Str::limit($detail ? $detail['variant_key'] : '', 50) }}
                                                        </div>
                                                    </div>
                                                @else
                                                    <span
                                                        class="badge badge-pill badge-danger">{{ translate('Service_unavailable') }}</span>
                                                @endif
                                            </td>
                                            <td class="text--end" id="service-cost-{{ $detail['variant_key'] }}">
                                                {{ currency_symbol() . ' ' . $detail['service_cost'] }}</td>
                                            <td>
                                                <input type="number" min="1" name="qty[]"
                                                       class="form-control qty-width dark-color-bo m-auto"
                                                       id="qty-{{ $detail['variant_key'] }}"
                                                       value="{{ $detail['quantity'] }}"
                                                       oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                            </td>
                                            <td class="text--end" id="discount-amount-{{ $detail['variant_key'] }}">
                                                {{ currency_symbol() . ' ' . $detail['discount_amount'] }}</td>
                                            <td class="text--end" id="total-cost-{{ $detail['variant_key'] }}">
                                                {{ currency_symbol() . ' ' . $detail['total_cost'] }}
                                            </td>
                                            <input type="hidden" name="service_ids[]"
                                                   value="{{ $detail['service']['id'] }}">
                                            <input type="hidden" name="variant_keys[]"
                                                   value="{{ $detail['variant_key'] }}">
                                        </tr>
                                        @php($sub_total += $detail['service_cost'] * $detail['quantity'])
                                    @endforeach
                                    <input type="hidden" name="zone_id" value="{{ $booking->zone_id }}">
                                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                                    <input type="hidden" name="booking_repeat_id" value="{{ $booking?->nextService['id'] }}">
                                    </tbody>
                                </table>
                            </div>

                            <div class="bg-F8F8F8 p-3 mb-30">
                                <div class="form-check d-flex align-items-center gap-1">
                                    <input class="form-check-input check-28" type="checkbox" name="next_all_booking_change" value="1">
                                    <label class="form-check-label lh-lg" for="">
                                        {{ translate('Check the box') }}
                                        <br>
                                        {{ translate(' If want to Update it for all upcoming bookings') }}

                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer d-flex justify-content-end gap-3 border-0 pt-0 pb-4">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal"
                                aria-label="Close">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary"
                                form="booking-edit-table">{{ translate('update_cart') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('script')
    <script>
        "use strict";

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

        $('.switcher_input').on('click', function() {
            let paymentStatus = $(this).is(':checked') === true ? 1 : 0;
            payment_status_change(paymentStatus)
        })

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

        $('.offline-payment').on('click', function() {
            let route = '{{ route('admin.booking.offline-payment.verify', ['booking_id' => $booking->id]) }}';
            route_alert_reload(route, '{{ translate('Want to verify the payment') }}', true);
        })

        @if ($booking->booking_status == 'pending')
        $(document).ready(function() {
            selectElementVisibility('serviceman_assign', false);
            selectElementVisibility('payment_status', false);
        });
        @endif

        $("#booking_status").change(function() {
            var $sel = $("#booking_status");
            var booking_status = $sel.val();
            var previous_status = $sel.data('current');
            if (booking_status && booking_status !== '0' && parseInt(booking_status, 10) !== 0) {
                if (typeof bookingAdminStatusNeedsReason === 'function' && bookingAdminStatusNeedsReason(booking_status, previous_status)) {
                    $sel.val(previous_status);
                    if ($sel.next('.select2-container').length) {
                        $sel.next('.select2-container').find('.select2-selection__rendered').text($sel.find('option:selected').text());
                    }
                    if (typeof bookingAdminOpenStatusReasonModal === 'function') {
                        bookingAdminOpenStatusReasonModal(booking_status, previous_status);
                    }
                    return;
                }
                var route = '{{ route('admin.booking.status_update', [$booking->id]) }}' + '?booking_status=' + booking_status;
                update_booking_details(route, '{{ translate('want_to_update_status') }}', 'booking_status',
                    booking_status);
            } else {
                toastr.error('{{ translate('choose_proper_status') }}');
            }
        });

        $("#serviceman_assign").change(function() {
            var serviceman_id = $("#serviceman_assign option:selected").val();
            if (serviceman_id !== 'no_serviceman') {
                var route = '{{ route('admin.booking.serviceman_update', [$booking->id]) }}' + '?serviceman_id=' +
                    serviceman_id;

                update_booking_details(route, '{{ translate('want_to_assign_the_serviceman') }}?',
                    'serviceman_assign', serviceman_id);
            } else {
                toastr.error('{{ translate('choose_proper_serviceman') }}');
            }
        });

        function payment_status_change(payment_status) {
            var route = '{{ route('admin.booking.payment_update', [$booking->id]) }}' + '?payment_status=' +
                payment_status;
            update_booking_details(route, '{{ translate('want_to_update_status') }}', 'payment_status', payment_status);
        }

        function service_schedule_update() {
            var service_schedule = $("#service_schedule").val();
            var route = '{{ route('admin.booking.schedule_update', [$booking->id]) }}' + '?service_schedule=' +
                service_schedule;

            update_booking_details(route, '{{ translate('want_to_update_the_booking_schedule') }}', 'service_schedule',
                service_schedule);
        }

        function update_booking_details(route, message, componentId, updatedValue, title = "{{ translate('are_you_sure') }}?") {
            Swal.fire({
                title: title,
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
                    var revertStatus = componentId === 'booking_status' ? $('#booking_status').data('current') : undefined;
                    var ajaxOpts = {
                        dataType: 'json',
                        beforeSend: function() {
                            toastr.info('{{ translate('Processing request...') }}');
                        },
                        success: function(data) {
                            if (componentId === 'booking_status') {
                                $('#booking_status').data('current', updatedValue);
                            }
                            update_component(componentId, updatedValue);
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

                            if (componentId === 'booking_status' || componentId === 'payment_status' ||
                                componentId === 'service_schedule' || componentId ===
                                'serviceman_assign') {
                                location.reload();
                            }
                        },
                        error: function(xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ translate('Something went wrong. Please try again.') }}';
                            if (componentId === 'booking_status' && revertStatus !== undefined) {
                                $('#booking_status').val(revertStatus);
                                $('#booking_status').data('current', revertStatus);
                                if ($('#booking_status').next('.select2-container').length) {
                                    $('#booking_status').next('.select2-container').find('.select2-selection__rendered').text($('#booking_status option:selected').text());
                                }
                            }
                            toastr.error(msg, { CloseButton: true, ProgressBar: true });
                        },
                        complete: function() {},
                    };
                    if (componentId === 'booking_status') {
                        ajaxOpts.url = '{{ route('admin.booking.status_update', [$booking->id]) }}';
                        ajaxOpts.method = 'POST';
                        ajaxOpts.data = {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            booking_status: updatedValue
                        };
                        ajaxOpts.headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
                        $.ajax(ajaxOpts);
                    } else {
                        ajaxOpts.url = route;
                        ajaxOpts.method = 'GET';
                        ajaxOpts.data = {};
                        $.ajax(ajaxOpts);
                    }
                }
            })
        }

        function update_component(componentId, updatedValue) {

            if (componentId === 'booking_status') {
                $("#booking_status__span").html(updatedValue);

                selectElementVisibility('serviceman_assign', true);
                selectElementVisibility('payment_status', true);

            } else if (componentId === 'payment_status') {
                $("#payment_status__span").html(updatedValue);
                if (updatedValue === 'paid') {
                    $("#payment_status__span").addClass('text-success').removeClass('text-danger');
                } else if (updatedValue === 'unpaid') {
                    $("#payment_status__span").addClass('text-danger').removeClass('text-success');
                }

            }
        }

        function selectElementVisibility(componentId, visibility) {
            if (visibility === true) {
                $('#' + componentId).next(".select2-container").show();
            } else if (visibility === false) {
                $('#' + componentId).next(".select2-container").hide();
            } else {}
        }
    </script>

    <script>
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
                    lat: {{ $customerAddress->lat ?? 23.811842872190343 }},
                    lng: {{ $customerAddress->lon ?? 90.356331 }}
                };
                const map = new google.maps.Map(document.getElementById("location_map_canvas"), {
                    center: myLatLng,
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
                                document.getElementById('address').value = results[1]
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
                    toastr.info('{{ translate('Processing request...') }}');
                },
                success: function(response) {
                    $('.modal-content-data').html(response.view);
                    toastr.success('{{ translate('Successfully reassign provider') }}');
                    setTimeout(function() {
                        location.reload()
                    }, 600);
                },
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

        document.addEventListener('DOMContentLoaded', function() {
            const denyRequestRadio = document.querySelector('.deny-request');
            const cancellationNote = document.querySelector('.cancellation-note');

            denyRequestRadio.addEventListener('change', function() {
                if (this.checked) {
                    cancellationNote.style.display = 'block';
                    document.querySelector('textarea[name="booking_deny_note"]').required = true;
                } else {
                    cancellationNote.style.display = 'none';
                    document.querySelector('textarea[name="booking_deny_note"]').required = false;
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('.without-search').select2({
                minimumResultsForSearch: Infinity
            });
        });
    </script>
@endpush
