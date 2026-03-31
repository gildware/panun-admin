@extends('adminmodule::layouts.master')

@section('title', translate('Booking_Details'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/swiper/swiper-bundle.min.css') }}">
@endpush

@section('content')
    @php
        $bookingDetail = $booking->detail ?? collect();
        $totalPaidFromPartials = (float) ($booking->booking_partial_payments ?? collect())->sum('paid_amount');
        $bookingTotalForPayment = get_booking_total_amount($booking);
        if (!isset($remainingDueForAddPayment)) {
            $remainingDueForAddPayment = round(max(0, $bookingTotalForPayment - $totalPaidFromPartials), 2);
        }
        $paymentFullyCovered = $booking->booking_partial_payments->isEmpty()
            ? (bool) $booking->is_paid
            : (round($totalPaidFromPartials, 2) >= round($bookingTotalForPayment, 2));
        $displayPaidAmount = $booking->booking_partial_payments->isNotEmpty()
            ? $totalPaidFromPartials
            : (($paymentFullyCovered && (bool) $booking->is_paid) ? $bookingTotalForPayment : 0);
        $showAsAmountPaidLabel = $booking->booking_status == 'completed' || $paymentFullyCovered;
        $advanceOffline = ($booking->booking_partial_payments ?? collect())->where('paid_with', 'offline')->first();
        $subTotal = 0;
        $extraServicesTotal = 0;
        $extraServicesServiceTotal = 0;
        $extraServicesSpareTotal = 0;
        $serviceAmountExclVat = 0;
        $grandTotalCalculated = (float)($booking->total_tax_amount ?? 0) + (float)($booking->extra_fee ?? 0);
        $bookingHasTax = (float)($booking->total_tax_amount ?? 0) > 0;
        $bookingNotEditable = in_array($booking->booking_status ?? '', ['completed', 'canceled', 'refunded']);
    @endphp
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h2 class="page-title mb-0">{{ translate('Booking_Details') }} </h2>
                    @if(!empty($booking->lead_id))
                        <span class="badge bg-info">
                            {{ translate('Lead_ID') }}:
                            <a href="{{ route('admin.lead.show', $booking->lead_id) }}" class="text-white text-decoration-underline">#{{ $booking->lead_id }}</a>
                        </span>
                    @endif
                </div>
                @can('booking_delete')
                    <button type="button"
                            class="action-btn btn--danger rounded-circle"
                            style="--size: 34px"
                            data-bs-toggle="modal"
                            data-bs-target="#bookingDeleteModal--{{ $booking['id'] }}">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                @endcan
            </div>

            <div class="pb-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <h3 class="c1 fw-bold">{{ translate('Booking') }} # {{ $booking['readable_id'] }}</h3>
                        <span class="badge badge-{{
                            $booking->booking_status == 'ongoing' ? 'warning' :
                            ($booking->booking_status == 'completed' ? 'success' :
                            ($booking->booking_status == 'canceled' ? 'danger' :
                            ($booking->booking_status == 'refunded' ? 'secondary' : 'info')))
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
                <div class="d-flex flex-wrap flex-xxl-nowrap gap-3 align-items-xxl-center flex-column flex-xxl-row">
                    @php
                        $bookingFeedbackSvc = app(\Modules\ProviderManagement\Services\BookingAdminFeedbackService::class);
                        $terminalBooking = $bookingFeedbackSvc->isTerminalBooking($booking);
                        $needProviderAdminFb = $terminalBooking && !empty($booking->provider_id) && !$bookingFeedbackSvc->providerFeedbackResolved($booking);
                        $needCustomerAdminFb = $terminalBooking && !empty($booking->customer_id) && !$bookingFeedbackSvc->customerFeedbackResolved($booking);
                    @endphp
                    @if($needProviderAdminFb || $needCustomerAdminFb)
                        <div class="alert alert-warning mb-0 w-100" role="alert" style="max-width: 640px;">
                            <div class="fw-semibold mb-2">{{ translate('Internal_booking_feedback') }}</div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                @if($needProviderAdminFb)
                                    <button type="button" class="btn btn-sm btn--primary open-feedback-manual">
                                        <span class="material-icons">engineering</span>{{ translate('Provider_feedback') }}
                                    </button>
                                @endif
                                @if($needCustomerAdminFb)
                                    <button type="button" class="btn btn-sm btn--primary open-customer-feedback-manual">
                                        <span class="material-icons">person</span>{{ translate('Customer_feedback') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                    <div class="d-flex flex-wrap gap-3">
                        @php
                            $maxBookingAmount = business_config('max_booking_amount', 'booking_setup')->live_values;
                        @endphp
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
                                                            <input class="form-check-input approve-request check-approve-status booking-verification-status"
                                                                checked type="radio" name="status" id="inlineRadio1"
                                                                value="approve">
                                                            <label class="form-check-label"
                                                                for="inlineRadio1">{{ translate('Approve the Request') }}</label>
                                                        </div>
                                                        <div class="form-check-inline">
                                                            <input class="form-check-input deny-request booking-verification-status" type="radio"
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

                        @if (in_array($booking['booking_status'], ['pending', 'accepted', 'ongoing']))
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
                            @elseif($booking->isOpenReopenTicket())
                                <span class="badge bg-info text-dark align-self-center">{{ translate('Complete_booking_then_mark_resolved') }}</span>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>

            @if($booking->reopenEvents->isNotEmpty() || !empty($booking->originated_from_booking_id) || $booking->spawnedFollowupBookings->isNotEmpty())
                <div class="card mb-3 border-warning">
                    <div class="card-header bg-soft-warning border-warning">
                        <h5 class="mb-0">{{ translate('Reopen_and_complaint_history') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($booking->reopen_resolved_at)
                            <p class="alert alert-success py-2 mb-3">
                                <span class="fw-semibold">{{ translate('Resolved') }}:</span>
                                {{ $booking->reopen_resolved_at->format('d-M-Y H:i') }}
                                @if($booking->reopenCaseResolvedByUser)
                                    — {{ $booking->reopenCaseResolvedByUser->first_name }} {{ $booking->reopenCaseResolvedByUser->last_name }}
                                @endif
                                @if(!empty($booking->reopen_resolve_remarks))
                                    <span class="d-block mt-2 small fw-normal text-dark">
                                        <span class="fw-semibold">{{ translate('Reopen_resolve_remarks') }}:</span><br>
                                        {!! nl2br(e($booking->reopen_resolve_remarks)) !!}
                                    </span>
                                @endif
                            </p>
                        @endif
                        @if(!empty($booking->originated_from_booking_id) && $booking->originatedFromBooking)
                            <p class="mb-2">
                                <span class="fw-semibold">{{ translate('Originated_from_booking') }}:</span>
                                <a href="{{ route('admin.booking.details', [$booking->originated_from_booking_id, 'web_page' => 'details']) }}">
                                    #{{ $booking->originatedFromBooking->readable_id ?? $booking->originated_from_booking_id }}
                                </a>
                            </p>
                        @endif
                        @if($booking->spawnedFollowupBookings->isNotEmpty())
                            <p class="fw-semibold mb-2">{{ translate('Follow_up_bookings') }}:</p>
                            <ul class="mb-3">
                                @foreach($booking->spawnedFollowupBookings as $child)
                                    <li>
                                        <a href="{{ route('admin.booking.details', [$child->id, 'web_page' => 'details']) }}">
                                            #{{ $child->readable_id }}</a>
                                        — <span class="text-capitalize">{{ $child->booking_status }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if($booking->reopenEvents->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>{{ translate('When') }}</th>
                                            <th>{{ translate('By') }}</th>
                                            <th>{{ translate('Resolution') }}</th>
                                            <th>{{ translate('Notes') }}</th>
                                            <th>{{ translate('Linked_booking') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($booking->reopenEvents as $ev)
                                            <tr>
                                                <td>{{ $ev->created_at?->format('d-M-Y H:i') }}</td>
                                                <td>{{ $ev->actor?->first_name }} {{ $ev->actor?->last_name }}</td>
                                                <td class="text-capitalize">{{ str_replace('_', ' ', $ev->resolution) }}</td>
                                                <td class="small">{{ \Illuminate\Support\Str::limit($ev->complaint_notes ?? '', 120) }}</td>
                                                <td>
                                                    @if($ev->child_booking_id)
                                                        <a href="{{ route('admin.booking.details', [$ev->child_booking_id, 'web_page' => 'details']) }}">{{ translate('Open') }}</a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @include('bookingmodule::admin.booking.partials._reopen-from-completed-modal')
            @include('bookingmodule::admin.booking.partials._reopen-resolve-modal', [
                'modalId' => 'reopenResolveModal--' . $booking->id,
                'formId' => 'reopenResolveForm--' . $booking->id,
                'formAction' => route('admin.booking.reopen-resolve', $booking->id),
            ])
            @if($errors->has('reopen_resolve_remarks'))
                @push('script')
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            var el = document.getElementById('reopenResolveModal--{{ $booking->id }}');
                            if (el && window.bootstrap && bootstrap.Modal) {
                                bootstrap.Modal.getOrCreateInstance(el).show();
                            }
                        });
                    </script>
                @endpush
            @endif

            @can('booking_delete')
                <div class="modal fade" id="bookingDeleteModal--{{ $booking['id'] }}" tabindex="-1"
                     aria-labelledby="bookingDeleteModalLabel--{{ $booking['id'] }}" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-body pt-5 p-md-5">
                                <button type="button" class="btn-close"
                                        data-bs-dismiss="modal"
                                        aria-label="Close"></button>

                                <div class="d-flex justify-content-center mb-4">
                                    <img width="75" height="75"
                                         src="{{ asset('assets/admin-module/img/media/delete.png') }}"
                                         class="rounded-circle" alt="">
                                </div>

                                <h3 class="text-center mb-2 fw-medium">
                                    {{ translate('Are_you_sure_you_want_to_delete_this_booking?') }}
                                </h3>
                                <p class="text-center fs-12 fw-medium text-muted">
                                    {{ translate('This_action_will_permanently_remove_the_booking_and_its_related_data.') }}
                                </p>

                                <form method="POST"
                                      action="{{ route('admin.booking.delete', [$booking->id]) }}">
                                    @csrf
                                    @method('DELETE')

                                    <div class="d-flex justify-content-center gap-3 mt-3">
                                        <button type="button"
                                                class="btn btn--secondary"
                                                data-bs-dismiss="modal">
                                            {{ translate('cancel') }}
                                        </button>
                                        <button type="submit"
                                                class="btn btn-danger">
                                            {{ translate('Delete') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            @can('booking_edit')
            @if(!$bookingNotEditable)
            <div class="modal fade" id="bookingInfoModal--{{ $booking->id }}" tabindex="-1" aria-labelledby="bookingInfoModalLabel--{{ $booking->id }}" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('admin.booking.info-update', [$booking->id]) }}" method="POST" id="booking-info-form--{{ $booking->id }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title" id="bookingInfoModalLabel--{{ $booking->id }}">{{ translate('Update_Booking_Information') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Assignee') }}</label>
                                    <select name="assignee_id" class="form-control js-select">
                                        <option value="">{{ translate('Unassigned') }}</option>
                                        @foreach($assignees ?? [] as $a)
                                            <option value="{{ $a->id }}" {{ (old('assignee_id', $booking->assignee_id) == $a->id) ? 'selected' : '' }}>
                                                {{ $a->first_name }} {{ $a->last_name }} ({{ $a->user_type === 'super-admin' ? translate('Admin') : translate('Employee') }}) — {{ $a->email ?? $a->phone }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Source') }}</label>
                                    <select name="booking_source" class="form-control">
                                        <option value="whatsapp" {{ (old('booking_source', $booking->booking_source ?? 'whatsapp') == 'whatsapp') ? 'selected' : '' }}>{{ translate('Whatsapp') }}</option>
                                        <option value="call" {{ (old('booking_source', $booking->booking_source) == 'call') ? 'selected' : '' }}>{{ translate('Call') }}</option>
                                        <option value="social_media" {{ (old('booking_source', $booking->booking_source) == 'social_media') ? 'selected' : '' }}>{{ translate('Social_Media') }}</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Service_Additional_Details') }}</label>
                                    <textarea name="service_description" class="form-control" rows="3" maxlength="2000" placeholder="{{ translate('Optional_additional_notes_about_the_service') }}">{{ old('service_description', $booking->service_description) }}</textarea>
                                    <small class="text-muted">{{ translate('Max_2000_characters') }}</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                <button type="submit" class="btn btn--primary">{{ translate('Update') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="addExtraServiceModal--{{ $booking->id }}" tabindex="-1" aria-labelledby="addExtraServiceModalLabel--{{ $booking->id }}" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('admin.booking.extra-service.store', [$booking->id]) }}" method="POST" id="extra-service-form--{{ $booking->id }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="addExtraServiceModalLabel--{{ $booking->id }}">{{ translate('Add_Extra_Service') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required maxlength="255" placeholder="{{ translate('Title') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Details_of_Service') }}</label>
                                    <textarea name="details" class="form-control" rows="2" maxlength="2000" placeholder="{{ translate('Details_of_Service') }}">{{ old('details') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Type') }} <span class="text-danger">*</span></label>
                                    <select name="type" class="form-control" required>
                                        <option value="service" {{ old('type', 'service') == 'service' ? 'selected' : '' }}>{{ translate('Service') }}</option>
                                        <option value="spare_part" {{ old('type') == 'spare_part' ? 'selected' : '' }}>{{ translate('Spare_Part') }}</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ translate('Qty') }} <span class="text-danger">*</span></label>
                                        <input type="number" name="quantity" class="form-control" value="{{ old('quantity', 1) }}" required min="1" step="1">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ translate('Price') }} <span class="text-danger">*</span></label>
                                        <input type="number" name="price" class="form-control extra-price-input" value="{{ old('price', 0) }}" required min="0" step="0.01">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ translate('Discount') }}</label>
                                        <input type="number" name="discount" class="form-control extra-discount-input" value="{{ old('discount', 0) }}" min="0" step="0.01">
                                    </div>
                                </div>
                                <p class="mb-0 small text-muted">{{ translate('Total') }} = ({{ translate('Qty') }} × {{ translate('Price') }}) − {{ translate('Discount') }}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                <button type="submit" class="btn btn--primary">{{ translate('Add') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
            @endcan

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
                @php
                    $max_booking_amount = business_config('max_booking_amount', 'booking_setup')->live_values ?? 0;
                @endphp

                @if ($booking->is_verified == 2 && $booking->payment_method == 'cash_after_service' && $max_booking_amount <= $booking->total_booking_amount)
                    <div class="border border-danger-light bg-soft-danger rounded py-3 px-3 text-dark">
                        <span class="text-danger"># {{ translate('Note: ') }}</span>
                        <span>{{ $booking?->bookingDeniedNote?->value }}</span>
                    </div>
                @endif

                @if ($booking->is_verified == 0 && $booking->payment_method == 'cash_after_service' && $max_booking_amount <= $booking->total_booking_amount)
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
                    <div class="card mb-3">
                        <div class="card-body pb-5">
                            <div class="border-bottom pb-3 mb-3">
                                <div
                                    class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-3 flex-wrap mb-40">
                                    <div>
                                        <h4 class="mb-2">{{ translate('Payment_Method') }}</h4>
                                        <h5 class="c1 mb-2 fw-bold"><span
                                                class="text-capitalize">{{ str_replace(['_', '-'], ' ', $booking->payment_method) }}
                                                @if ($booking->payment_method == 'offline_payment' && $booking?->booking_offline_payments?->first()?->method_name)
                                                    ({{ $booking?->booking_offline_payments?->first()?->method_name }})
                                                @endif
                                            </span>
                                        </h5>
                                        <p>
                                            <span>{{ translate('Total_Amount') }} : </span>
                                            <span
                                                class="c1">{{ with_currency_symbol($bookingTotalForPayment) }}</span>
                                        </p>
                                        @if($displayPaidAmount > 0)
                                            <p class="mb-1">
                                                <span>{{ $showAsAmountPaidLabel ? translate('Amount_Paid') : translate('Advance_Paid') }} : </span>
                                                <span class="c1">{{ with_currency_symbol($displayPaidAmount) }}</span>
                                                @if($advanceOffline && $advanceOffline->transaction_id)
                                                    <span class="small text-muted">({{ translate('Txn') }}: {{ $advanceOffline->transaction_id }})</span>
                                                @endif
                                            </p>
                                            <p class="mb-0">
                                                <span>{{ translate('Due_Balance') }} : </span>
                                                <span class="c1">{{ with_currency_symbol(max(0, $bookingTotalForPayment - $displayPaidAmount)) }}</span>
                                            </p>
                                        @endif
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

                                        @if ($booking->is_verified == '0' && $booking->payment_method == 'cash_after_service' && $booking->total_booking_amount >= $maxBookingAmount)
                                            <p class="mb-2"><span>{{ translate('Request Verify Status:') }} :</span>
                                                <span class="c1 text-capitalize">{{ translate('Pending') }}</span>
                                            </p>
                                        @elseif($booking->is_verified == '2' &&  $booking->payment_method == 'cash_after_service' && $booking->total_booking_amount >= $maxBookingAmount)
                                            <p class="mb-2"><span>{{ translate('Request Verify Status:') }} :</span>
                                                <span class="text-danger text-capitalize"
                                                    id="booking_status__span">{{ translate('Denied') }}</span>
                                            </p>
                                        @endif

                                        <p class="mb-2">
                                            <span>{{ translate('Payment_Status') }} : </span>
                                            @if(in_array($booking->booking_status, ['canceled', 'refunded']))
                                                <span class="ms-3 badge badge-secondary" id="payment_status__span">{{ translate('Refunded') }}</span>
                                            @else
                                                <span class="ms-3 badge badge-{{ $paymentFullyCovered ? 'success' : 'danger' }}"
                                                    id="payment_status__span">{{ $paymentFullyCovered ? translate('Paid') : translate('Unpaid') }}</span>
                                                @if (!$paymentFullyCovered && $booking->booking_partial_payments->isNotEmpty())
                                                    <span
                                                        class="small badge badge-info text-success p-1 fz-10">{{ translate('Partially paid') }}</span>
                                                @endif
                                            @endif
                                        </p>
                                        <p class="mb-2"><span>{{ translate('Booking_Otp') }} :</span> <span
                                                class="c1 text-capitalize">{{ $booking?->booking_otp ?? '' }}</span></p>
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

                            <div class="d-flex justify-content-start gap-2">
                                <h3 class="mb-3">{{ translate('Booking_Summary') }}</h3>
                            </div>

                            <div class="table-responsive border-bottom">
                                <table class="table text-nowrap align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-lg-3">{{ translate('Service') }}</th>
                                            <th>{{ translate('Price') }}</th>
                                            <th>{{ translate('Qty') }}</th>
                                            <th>{{ translate('Discount') }}</th>
                                            @if($bookingHasTax)
                                            <th>{{ translate('Vat') }}</th>
                                            @endif
                                            <th class="text--end">{{ translate('Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $subTotal = 0;
                                            $extraServicesTotal = 0;
                                            $extraServicesServiceTotal = 0;
                                            $extraServicesSpareTotal = 0;
                                        @endphp
                                        @foreach ($bookingDetail as $detail)
                                            @php
                                                $detailLineTotal = round(($detail->service_cost * $detail->quantity) - ($detail->discount_amount ?? 0) - ($detail->campaign_discount_amount ?? 0) + ($detail->tax_amount ?? 0), 2);
                                            @endphp
                                            <tr>
                                                <td class="text-wrap ps-lg-3">
                                                    @if (isset($detail->service))
                                                        <div class="d-flex flex-column">
                                                            <a href="{{ route('admin.service.detail', [$detail->service->id]) }}"
                                                                class="fw-bold">{{ Str::limit($detail->service->name, 30) }}</a>
                                                            <div class="text-capitalize">
                                                                @if(isset($detail->variation) && $detail->variation)
                                                                    {{ Str::limit($detail->variation->variant ?? $detail->variant_key, 50) }}
                                                                @else
                                                                    {{ Str::limit($detail->variant_key ?? '', 50) }}
                                                                @endif
                                                            </div>
                                                            <span class="badge badge-primary mt-1" style="width: fit-content;">{{ translate('Service') }}</span>
                                                            @if ($detail->overall_coupon_discount_amount > 0)
                                                                <small
                                                                    class="fz-10 text-capitalize">{{ translate('coupon_discount') }}
                                                                    :
                                                                    -{{ with_currency_symbol($detail->overall_coupon_discount_amount) }}</small>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span
                                                            class="badge badge-pill badge-danger">{{ translate('Service_unavailable') }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ with_currency_symbol($detail->service_cost) }}</td>
                                                <td>
                                                    <span>{{ $detail->quantity }}</span>
                                                </td>
                                                <td>
                                                    @if ($detail?->discount_amount > 0)
                                                        {{ with_currency_symbol($detail->discount_amount) }}
                                                    @elseif($detail?->campaign_discount_amount > 0)
                                                        {{ with_currency_symbol($detail->campaign_discount_amount) }}
                                                        <br><span
                                                            class="fz-12 text-capitalize">{{ translate('campaign') }}</span>
                                                    @else
                                                        {{ with_currency_symbol(0) }}
                                                    @endif

                                                </td>
                                                @if($bookingHasTax)
                                                <td>{{ with_currency_symbol($detail->tax_amount) }}</td>
                                                @endif
                                                <td class="text--end">{{ with_currency_symbol($detailLineTotal) }}</td>
                                            </tr>
                                            @php
                                                $subTotal += $detail->service_cost * $detail->quantity;
                                            @endphp
                                        @endforeach
                                        @foreach ($booking->extra_services ?? [] as $extra)
                                            <tr class="table-light">
                                                <td class="text-wrap ps-lg-3">
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold">{{ Str::limit($extra->title, 40) }}</span>
                                                        @if($extra->details)
                                                            <small class="text-muted">{{ Str::limit($extra->details, 60) }}</small>
                                                        @endif
                                                        <span class="badge badge-{{ $extra->type === 'spare_part' ? 'info' : 'primary' }} mt-1" style="width: fit-content;">
                                                            {{ $extra->type === 'spare_part' ? translate('Spare_Part') : translate('Service') }}
                                                        </span>
                                                        @can('booking_edit')
                                                        @if(!$bookingNotEditable)
                                                        <form method="post" action="{{ route('admin.booking.extra-service.destroy', [$booking->id, $extra->id]) }}" class="d-inline mt-1" onsubmit="return confirm('{{ translate('Remove_this_item') }}?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0">{{ translate('Remove') }}</button>
                                                        </form>
                                                        @endif
                                                        @endcan
                                                    </div>
                                                </td>
                                                <td>{{ with_currency_symbol($extra->price) }}</td>
                                                <td>{{ $extra->quantity }}</td>
                                                <td>{{ with_currency_symbol($extra->discount) }}</td>
                                                @if($bookingHasTax)
                                                <td>—</td>
                                                @endif
                                                <td class="text--end">{{ with_currency_symbol($extra->total) }}</td>
                                            </tr>
                                            @php
                                                $extraServicesTotal += $extra->total;
                                                if ($extra->type === \Modules\BookingModule\Entities\BookingExtraService::TYPE_SPARE_PART) {
                                                    $extraServicesSpareTotal += $extra->total;
                                                } else {
                                                    $extraServicesServiceTotal += $extra->total;
                                                }
                                            @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @can('booking_edit')
                            @if(!$bookingNotEditable)
                            <div class="mt-3 mb-3">
                                <button type="button" class="btn btn-sm btn--primary" data-bs-toggle="modal" data-bs-target="#addExtraServiceModal--{{ $booking->id }}">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
                                    {{ translate('Add_Extra_Service') }}
                                </button>
                            </div>
                            @endif
                            @endcan
                            @php
                                $serviceAmountExclVat = $subTotal + $extraServicesServiceTotal;
                                $grandTotalCalculated = $serviceAmountExclVat + $extraServicesSpareTotal + (float)$booking->total_tax_amount + (float)$booking->extra_fee;
                            @endphp
                            <div class="row justify-content-end mt-3">
                                <div class="col-sm-10 col-md-6 col-xl-5">
                                    <div class="table-responsive">
                                        <table class="table-md title-color align-right w-100">
                                            <tbody>
                                                <tr>
                                                    <td class="text-capitalize">{{ translate('service_amount') }}@if($bookingHasTax) <small
                                                            class="fz-12">({{ translate('Vat_Excluded') }})</small>@endif</td>
                                                    <td class="text--end pe--4">{{ with_currency_symbol($serviceAmountExclVat) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-capitalize">{{ translate('service_discount') }}</td>
                                                    <td class="text--end pe--4">
                                                        {{ with_currency_symbol($booking->total_discount_amount) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-capitalize">{{ translate('coupon_discount') }}</td>
                                                    <td class="text--end pe--4">
                                                        {{ with_currency_symbol($booking->total_coupon_discount_amount) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-capitalize">{{ translate('campaign_discount') }}</td>
                                                    <td class="text--end pe--4">
                                                        {{ with_currency_symbol($booking->total_campaign_discount_amount) }}
                                                    </td>
                                                </tr>
                                                @if($booking->total_referral_discount_amount > 0)
                                                    <tr>
                                                        <td class="text-capitalize">{{ translate('Referral Discount') }}</td>
                                                        <td class="text--end pe--4">
                                                            {{ with_currency_symbol($booking->total_referral_discount_amount) }}
                                                        </td>
                                                    </tr>
                                                @endif
                                                @if($bookingHasTax)
                                                <tr>
                                                    <td class="text-capitalize">{{ translate('vat_/_tax') }}</td>
                                                    <td class="text--end pe--4">
                                                        {{ with_currency_symbol($booking->total_tax_amount) }}</td>
                                                </tr>
                                                @endif
                                                @if ($extraServicesSpareTotal > 0)
                                                    <tr>
                                                        <td class="text-capitalize">{{ translate('Spare_Parts') }}</td>
                                                        <td class="text--end pe--4">{{ with_currency_symbol($extraServicesSpareTotal) }}</td>
                                                    </tr>
                                                @endif
                                                @if ($booking->extra_fee > 0)
                                                    @php
                                                        $additional_charge_label_name = business_config('additional_charge_label_name', 'booking_setup')->live_values ?? 'Visiting Charges';
                                                    @endphp
                                                    <tr>
                                                        <td class="text-capitalize">{{ $additional_charge_label_name }}
                                                        </td>
                                                        <td class="text--end pe--4">
                                                            {{ with_currency_symbol($booking->extra_fee) }}</td>
                                                    </tr>
                                                @endif
                                                @if($extraServicesServiceTotal > 0)
                                                    <tr>
                                                        <td class="text-capitalize">{{ translate('Extra_Services') }}</td>
                                                        <td class="text--end pe--4">{{ with_currency_symbol($extraServicesServiceTotal) }}</td>
                                                    </tr>
                                                @endif

                                                <tr>
                                                    <td><strong>{{ translate('Grand_Total') }}</strong></td>
                                                    <td class="text--end pe--4">
                                                        <strong>{{ with_currency_symbol($grandTotalCalculated) }}</strong>
                                                    </td>
                                                </tr>

                                                @if ($booking->booking_partial_payments->isNotEmpty())
                                                    @foreach ($booking->booking_partial_payments as $partial)
                                                        <tr>
                                                            <td>
                                                                @if($partial->paid_with === 'offline')
                                                                    {{ translate('Advance_Paid') }} ({{ translate('offline') }})
                                                                    @if($partial->transaction_id)
                                                                        <span class="small text-muted">— {{ $partial->transaction_id }}</span>
                                                                    @endif
                                                                @else
                                                                    {{ translate('Paid_by') }} {{ str_replace('_', ' ', $partial->paid_with) }}
                                                                @endif
                                                            </td>
                                                            <td class="text--end pe--4">
                                                                {{ with_currency_symbol($partial->paid_amount) }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif

                                                @php
                                                $dueAmount = 0;
                                                if (!$paymentFullyCovered) {
                                                    $dueAmount = $grandTotalCalculated - $totalPaidFromPartials;
                                                }
                                                if (in_array($booking->booking_status, ['pending', 'accepted', 'ongoing']) && $booking->payment_method != 'cash_after_service' && $booking->additional_charge > 0) {
                                                    $dueAmount += $booking->additional_charge;
                                                }
                                                @endphp

                                                @if ($dueAmount > 0)
                                                    <tr>
                                                        <td>{{ translate('Due_Amount') }}</td>
                                                        <td class="text--end pe--4">
                                                            {{ with_currency_symbol($dueAmount) }}</td>
                                                    </tr>
                                                @endif

                                                @if ($booking->payment_method != 'cash_after_service' && $booking->additional_charge < 0)
                                                    <tr>
                                                        <td>{{ translate('Refund') }}</td>
                                                        <td class="text--end pe--4">
                                                            {{ with_currency_symbol(abs($booking->additional_charge)) }}
                                                        </td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    @php
                        $revenueSettlement = get_booking_received_and_settlement($booking);
                    @endphp
                    {{-- Revenue split: what company keeps vs provider gets, and who owes whom --}}
                    <div class="card mb-3 border-primary">
                        <div class="card-body">
                            <h3 class="c1 mb-3">{{ translate('Revenue_&_Settlement') }}</h3>
                            <hr>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                    <span class="title-color">{{ translate('Company_share') }} ({{ translate('Commission') }})</span>
                                    <strong class="text-primary">{{ with_currency_symbol($revenueSettlement['company_share']) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center p-3 rounded c1-light-bg">
                                    <span class="title-color">{{ translate('Provider_share') }}</span>
                                    <strong>{{ with_currency_symbol($revenueSettlement['provider_share']) }}</strong>
                                </div>
                                <div class="small text-muted border-top pt-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>{{ translate('Received_by_company') }}:</span>
                                        <span>{{ with_currency_symbol($revenueSettlement['amount_received_by_company']) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>{{ translate('Received_by_provider') }}:</span>
                                        <span>{{ with_currency_symbol($revenueSettlement['amount_received_by_provider']) }}</span>
                                    </div>
                                </div>
                                @if($revenueSettlement['pay_to_provider'] > 0)
                                    <div class="alert alert-info mb-0 py-2 px-3 d-flex justify-content-between align-items-center">
                                        <span>{{ translate('Pay_to_provider') }}:</span>
                                        <strong>{{ with_currency_symbol($revenueSettlement['pay_to_provider']) }}</strong>
                                    </div>
                                @elseif($revenueSettlement['provider_owes_company'] > 0)
                                    <div class="alert alert-warning mb-0 py-2 px-3 d-flex justify-content-between align-items-center">
                                        <span>{{ translate('Provider_owes_you') }}:</span>
                                        <strong>{{ with_currency_symbol($revenueSettlement['provider_owes_company']) }}</strong>
                                    </div>
                                @else
                                    <div class="alert alert-secondary mb-0 py-2 px-3 small">
                                        {{ $revenueSettlement['total_paid'] >= $bookingTotalForPayment ? translate('Settled') : translate('Unpaid_or_partially_paid') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Booking information: Assignee, Source, Additional service info --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                <h3 class="c1 mb-0">{{ translate('Booking_Information') }}</h3>
                                @can('booking_edit')
                                    @if(!$bookingNotEditable)
                                    <button type="button" class="btn btn-sm btn--primary" data-bs-toggle="modal"
                                            data-bs-target="#bookingInfoModal--{{ $booking->id }}">
                                        <span class="material-symbols-outlined" style="font-size: 18px;">edit_square</span>
                                        {{ translate('Update') }}
                                    </button>
                                    @endif
                                @endcan
                            </div>
                            <hr>
                            <div class="d-flex flex-column gap-2">
                                <div>
                                    <span class="title-color">{{ translate('Assignee') }}:</span>
                                    <span id="booking-assignee-display">
                                        @if($booking->assignee_id && $booking->assignee)
                                            {{ $booking->assignee->first_name }} {{ $booking->assignee->last_name }}
                                            ({{ $booking->assignee->user_type === 'super-admin' ? translate('Admin') : translate('Employee') }})
                                            — {{ $booking->assignee->email ?? $booking->assignee->phone }}
                                        @else
                                            <span class="text-muted">{{ translate('Unassigned') }}</span>
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <span class="title-color">{{ translate('Source') }}:</span>
                                    <span id="booking-source-display">
                                        @switch(strtolower((string)($booking->booking_source ?? 'app')))
                                            @case('app'){{ translate('App') }}@break
                                            @case('call'){{ translate('Call') }}@break
                                            @case('whatsapp'){{ translate('Whatsapp') }}@break
                                            @case('social_media'){{ translate('Social_Media') }}@break
                                            @default{{ ucfirst(strtolower((string)($booking->booking_source ?? 'app'))) }}
                                        @endswitch
                                    </span>
                                </div>
                                <div>
                                    <span class="title-color">{{ translate('Service_Additional_Details') }}:</span>
                                    <span id="booking-service-description-display" class="text-break">
                                        {{ $booking->service_description ?: translate('Not_specified') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h3 class="c1">{{ translate('Booking Setup') }}</h3>
                            <hr>
                            @php
                                $bookingNotEditable = in_array($booking->booking_status, ['completed', 'canceled', 'refunded']);
                            @endphp
                            @can('booking_can_manage_status')
                                <div class="d-flex justify-content-between align-items-center gap-10 form-control h-45">
                                    <span class="title-color">{{ translate('Payment') }}</span>
                                    @if(in_array($booking->booking_status, ['canceled', 'refunded']))
                                        <span class="text-muted">{{ translate('Refunded') }}</span>
                                    @elseif(!$bookingNotEditable && !$paymentFullyCovered)
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-muted">{{ translate('Due_Amount') }}: <strong>{{ with_currency_symbol($remainingDueForAddPayment) }}</strong></span>
                                            <button type="button" class="btn btn--primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal-{{ $booking->id }}">{{ translate('Add payment') }}</button>
                                        </div>
                                    @else
                                        <span class="text-muted">{{ $paymentFullyCovered ? translate('Paid') : '' }}</span>
                                    @endif
                                </div>
                                @if(!$bookingNotEditable && !$paymentFullyCovered)
                                    <div class="modal fade" id="addPaymentModal-{{ $booking->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="post" action="{{ route('admin.booking.add-payment', $booking->id) }}" class="add-payment-form" data-due-amount="{{ $remainingDueForAddPayment }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">{{ translate('Add payment') }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ translate('Amount') }} <span class="text-danger">*</span> <small class="text-muted">({{ translate('Due amount') }}: {{ with_currency_symbol($remainingDueForAddPayment) }})</small></label>
                                                            <input type="number" step="0.01" min="0.01" max="{{ $remainingDueForAddPayment }}" name="amount" class="form-control add-payment-amount" required placeholder="{{ translate('Max') }} {{ with_currency_symbol($remainingDueForAddPayment) }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ translate('Received by') }} <span class="text-danger">*</span></label>
                                                            <select name="received_by" class="form-control form-select add-payment-received-by" required>
                                                                <option value="company">{{ translate('Company') }}</option>
                                                                <option value="provider">{{ translate('Provider') }}</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3 add-payment-txn-wrap">
                                                            <label class="form-label">{{ translate('Transaction ID') }} <span class="text-danger">*</span> ({{ translate('if received by company') }})</label>
                                                            <input type="text" name="transaction_id" class="form-control" maxlength="100" placeholder="{{ translate('Gateway or manual reference') }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ translate('Date') }}</label>
                                                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                                        <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endcan

                            @can('booking_can_manage_status')
                                @if($booking->booking_status == 'canceled' && isset($maxRefundAmount) && $maxRefundAmount > 0)
                                    <div class="d-flex justify-content-between align-items-center gap-10 form-control h-45 mt-3">
                                        <span class="title-color">{{ translate('Refund') }}</span>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-muted">{{ translate('Max_refund') }}: <strong>{{ with_currency_symbol($maxRefundAmount) }}</strong></span>
                                            <button type="button" class="btn btn--danger btn-sm" data-bs-toggle="modal" data-bs-target="#refundModal-{{ $booking->id }}">{{ translate('Refund customer') }}</button>
                                        </div>
                                    </div>
                                    <div class="modal fade" id="refundModal-{{ $booking->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="post" action="{{ route('admin.booking.refund', $booking->id) }}" class="refund-form" data-max-amount="{{ $maxRefundAmount }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">{{ translate('Refund customer') }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ translate('Refund amount') }} <span class="text-danger">*</span> <small class="text-muted">({{ translate('Max') }}: {{ with_currency_symbol($maxRefundAmount) }})</small></label>
                                                            <input type="number" step="0.01" min="0.01" max="{{ $maxRefundAmount }}" name="amount" class="form-control refund-amount" required placeholder="{{ translate('Max') }} {{ with_currency_symbol($maxRefundAmount) }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ translate('Refunded by (Transaction ID)') }} <span class="text-danger">*</span></label>
                                                            <input type="text" name="transaction_id" class="form-control" maxlength="100" required placeholder="{{ translate('Gateway or manual reference') }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ translate('Date') }}</label>
                                                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                                                        </div>
                                                        <p class="small text-muted">{{ translate('This will be recorded as an out transaction and booking status will be set to Refunded.') }}</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                                        <button type="submit" class="btn btn--danger">{{ translate('Refund') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endcan

                            @can('booking_can_manage_status')
                                <div class="mt-3">
                                    @if($bookingNotEditable)
                                        <div class="form-control h-45 d-flex align-items-center title-color">
                                            {{ translate('Booking_Status') }}: {{ translate(ucfirst($booking->booking_status)) }}
                                        </div>
                                    @else
                                        <select class="js-select without-search" id="booking_status" data-current="{{ $booking->booking_status }}" data-can-complete="{{ booking_can_be_completed($booking) ? '1' : '0' }}">
                                            @if ($booking->booking_status == 'pending')
                                                <option value="0" disabled selected>{{ translate('Booking_Status') }}: {{ translate('Pending') }}</option>
                                                <option value="accepted">{{ translate('Accept') }} {{ translate('Booking') }}</option>
                                                <option value="canceled">{{ translate('Cancel') }} {{ translate('Booking') }}</option>
                                            @else
                                                <option value="0" disabled {{ $booking['booking_status'] == 'accepted' ? 'selected' : '' }}>
                                                    {{ translate('Booking_Status') }}: {{ translate('Accepted') }}</option>
                                                <option value="ongoing"  @if ($booking['payment_method'] == 'cash_after_service' && $booking->is_verified == '2' && $booking->total_booking_amount >= $maxBookingAmount ) disabled @endif
                                                    {{ $booking['booking_status'] == 'ongoing' ? 'selected' : '' }}>
                                                    {{ translate('Booking_Status') }}: {{ translate('Ongoing') }}</option>
                                                <option value="completed"  @if ($booking['payment_method'] == 'cash_after_service' && $booking->is_verified == '2' && $booking->total_booking_amount >= $maxBookingAmount ) disabled @endif
                                                    {{ $booking['booking_status'] == 'completed' ? 'selected' : '' }}>
                                                    {{ translate('Booking_Status') }}: {{ translate('Completed') }}</option>
                                                @if ($booking->booking_status != 'completed')
                                                <option value="canceled"  @if ($booking['payment_method'] == 'cash_after_service' && $booking->is_verified == '2' && $booking->total_booking_amount >= $maxBookingAmount ) disabled @endif
                                                    {{ $booking['booking_status'] == 'canceled' ? 'selected' : '' }}>
                                                    {{ translate('Booking_Status') }}: {{ translate('Canceled') }}</option>
                                                @endif
                                            @endif
                                        </select>
                                    @endif
                                </div>
                            @endcan

                            <div class="mt-3">
                                @if (!$bookingNotEditable && !in_array($booking->booking_status, ['ongoing', 'completed']))
                                    @can('booking_can_manage_status')
                                        <input type="datetime-local" class="form-control h-45"
                                               name="service_schedule"
                                               value="{{ $booking->service_schedule }}"
                                               id="service_schedule"
                                               data-original="{{ $booking->service_schedule }}"
                                               min="{{ date('Y-m-d\TH:i') }}"
                                               onchange="service_schedule_update()">
                                    @endcan
                                @endif
                            </div>


                            @if($booking->payment_method == 'offline_payment')
                                <div class="mt-3 border border-color-primary">
                                    <div class="card text-center">
                                        <div class="card-header">
                                            <h5 class="font-weight-bold">{{ translate('Verification of Offline Payment') }}</h5>
                                        </div>
                                        <div class="card-body">
                                            @if($booking->booking_offline_payments->isNotEmpty())
                                                <div class="d-flex gap-1 flex-column">
                                                    @php
                                                        $offlinePaymentNote = '';
                                                    @endphp
                                                    @foreach ($booking?->booking_offline_payments?->first()?->customer_information ?? [] as $key => $item)
                                                        <div class="d-flex gap-2">
                                                            @if ($key != 'payment_note' )
                                                                <span class="w-100px d-flex justify-content-start">{{ translate($key) }}</span>
                                                                <span>: {{ translate($item) }}</span>
                                                            @endif
                                                        </div>
                                                        @php
                                                            $offlinePaymentNote = ($key == 'payment_note') ? $item : $offlinePaymentNote;
                                                        @endphp
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
                                                            <button class="btn badge-danger w-100 py-3 change-booking-status">{{ translate('Cancel Booking') }}</button>
                                                        </div>
                                                    @endif
                                                @endif

                                            @else
                                                <img src="{{ asset('assets/admin-module/img/offline-payment.png') }}" alt="Payment Icon" class="mb-3">
                                                <p class="text-muted">{{ translate('Customer did not submit any payment information yet') }}</p>
                                                @if($booking['booking_status'] != 'canceled')
                                                    <div class="d-flex flex-column gap-2 mt-4">
                                                        <button class="btn badge-info w-100 py-3 switch-to-cash-after-service">{{ translate('Switch to Cash after Service') }}</button>
                                                        <button class="btn badge-danger w-100 py-3 change-booking-status">{{ translate('Cancel Booking') }}</button>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="py-3 d-flex flex-column gap-3 mb-2">
                                @if ($booking->evidence_photos)
                                    <div class="c1-light-bg radius-10 py-3 px-4">
                                        <div class="d-flex justify-content-start gap-2">
                                            <h4 class="mb-2">{{ translate('uploaded_Images') }}</h4>
                                        </div>

                                        <div class="py-3 px-4">
                                            <div class="d-flex flex-wrap gap-3 justify-content-lg-start">
                                                @foreach ($booking->evidence_photos_full_path ?? [] as $key => $img)
                                                    <img width="100" class="max-height-100"
                                                        src="{{ $img }}"
                                                        alt="{{ translate('evidence-photo') }}">
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @php
                                    $serviceAtProviderPlace = (int)((business_config('service_at_provider_place', 'provider_config'))->live_values ?? 0);
                                @endphp
                                <div class="c1-light-bg radius-10">
                                    <div class="border-bottom d-flex align-items-center justify-content-between gap-2 py-3 px-4 mb-2">
                                        <h4 class="d-flex align-items-center gap-2">
                                            <span class="material-icons title-color">map</span>
                                            {{ translate('Service_location') }}
                                        </h4>
                                        @if($serviceAtProviderPlace == 1)
                                            @if($booking->provider_id)
                                                @php
                                                    $serviceLocation = getProviderSettings(providerId: $booking->provider_id, key: 'service_location', type: 'provider_config');
                                                @endphp
                                                @if(in_array('customer', $serviceLocation) && in_array('provider', $serviceLocation))
                                                    <div class="btn-group">
                                                        @can('booking_edit')
                                                            @if(!$bookingNotEditable)
                                                            <div data-bs-toggle="modal"
                                                                 data-bs-target="#serviceLocationModal--{{ $booking['id'] }}"
                                                                 data-toggle="tooltip" data-placement="top">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span class="material-symbols-outlined">edit_square</span>
                                                                </div>
                                                            </div>
                                                            @endif
                                                        @endcan
                                                    </div>
                                                @endif
                                            @else
                                                <div class="btn-group">
                                                    @can('booking_edit')
                                                        @if(!$bookingNotEditable)
                                                        <div data-bs-toggle="modal"
                                                             data-bs-target="#serviceLocationModal--{{ $booking['id'] }}"
                                                             data-toggle="tooltip" data-placement="top">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="material-symbols-outlined">edit_square</span>
                                                            </div>
                                                        </div>
                                                        @endif
                                                    @endcan
                                                </div>
                                            @endif
                                        @endif
                                    </div>

                                    <div class="py-3 px-4">
                                        @if($booking->service_location == 'provider')
                                            <div class="bg-warning p-3 rounded">
                                                <h5>{{ translate('Customer has to go to the Provider Location to receive the service') }}</h5>
                                            </div>
                                            <div class="mt-3">
                                                @if($booking->provider_id != null)
                                                    @if($booking->provider)
                                                        <h5 class="mb-1">{{ translate('Service Location') }}:</h5>
                                                        <div class="d-flex justify-content-between">
                                                            <p>{{ Str::limit($booking?->provider?->company_address ?? translate('not_available'), 100) }}</p>
                                                            <span class="material-icons">map</span>
                                                        </div>
                                                    @else
                                                        <p>{{ translate('Provider Unavailable') }}</p>
                                                    @endif
                                                @else
                                                    <h5 class="mb-1">{{ translate('Service Location') }}:</h5>
                                                    <p>{{ translate('The Service Location will be available after this booking accepts or assign to a provider') }}</p>
                                                @endif
                                            </div>
                                        @else
                                            <div class="bg-warning p-3 rounded">
                                                <h5>{{ translate('Provider has to go to the Customer Location to provide the service') }}</h5>
                                            </div>
                                            <div class="mt-3">
                                                <h5 class="mb-1">{{ translate('Service Location') }}:</h5>
                                                <div class="d-flex justify-content-between">
                                                    <p>{{ Str::limit($booking?->service_address?->address ?? translate('not_available'), 100) }}</p>
                                                    <span class="material-icons">map</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="c1-light-bg radius-10">
                                    <div class="border-bottom d-flex align-items-center justify-content-between gap-2 py-3 px-4 mb-2">
                                        <h4 class="d-flex align-items-center gap-2">
                                            <span class="material-icons title-color">person</span>
                                            {{ translate('Customer_Information') }}
                                        </h4>

                                        <div class="btn-group">
                                            @if (in_array($booking->booking_status, ['completed', 'cancelled']))
                                                @if (!$booking?->is_guest)
                                                    <div
                                                        class="d-flex align-items-center gap-2 cursor-pointer customer-chat">
                                                        <span class="material-symbols-outlined">chat</span>
                                                        <form action="{{ route('admin.chat.create-channel') }}"
                                                            method="post" id="chatForm-{{ $booking->id }}">
                                                            @csrf
                                                            <input type="hidden" name="customer_id"
                                                                value="{{ $booking?->customer?->id }}">
                                                            <input type="hidden" name="type" value="booking">
                                                            <input type="hidden" name="user_type" value="customer">
                                                        </form>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="cursor-pointer" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <span class="material-symbols-outlined">more_vert</span>
                                                </div>
                                                <ul
                                                    class="dropdown-menu dropdown-menu__custom border-none dropdown-menu-end">
                                                    @can('booking_edit')
                                                        @if(!$bookingNotEditable)
                                                        <li data-bs-toggle="modal"
                                                            data-bs-target="#serviceAddressModal--{{ $booking['id'] }}"
                                                            data-toggle="tooltip" data-placement="top">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="material-symbols-outlined">edit_square</span>
                                                                {{ translate('Edit_Details') }}
                                                            </div>
                                                        </li>
                                                        @endif
                                                    @endcan
                                                    @if (!$booking?->is_guest)
                                                        <li>
                                                            <div
                                                                class="d-flex align-items-center gap-2 cursor-pointer customer-chat">
                                                                <span class="material-symbols-outlined">chat</span>
                                                                {{ translate('chat_with_Customer') }}
                                                                <form action="{{ route('admin.chat.create-channel') }}"
                                                                    method="post" id="chatForm-{{ $booking->id }}">
                                                                    @csrf
                                                                    <input type="hidden" name="customer_id"
                                                                        value="{{ $booking?->customer?->id }}">
                                                                    <input type="hidden" name="type" value="booking">
                                                                    <input type="hidden" name="user_type"
                                                                        value="customer">
                                                                </form>
                                                            </div>
                                                        </li>
                                                    @endif
                                                </ul>
                                            @endif
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
                                                            <a
                                                                href="tel:{{ $customerPhone }}">{{ $customerPhone }}</a>
                                                        </li>
                                                    @endif
                                                    @if(!empty($booking?->service_address?->address))
                                                            <li>
                                                                <span class="material-icons">map</span>
                                                                <p>{{ Str::limit($booking?->service_address?->address ?? translate('not_available'), 100) }}
                                                                </p>
                                                            </li>
                                                    @endif
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
                                                <div class="cursor-pointer" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span class="material-symbols-outlined">more_vert</span>
                                                </div>
                                                <ul class="dropdown-menu dropdown-menu__custom border-none dropdown-menu-end">
                                                    <li>
                                                        <div
                                                            class="d-flex align-items-center gap-2 cursor-pointer provider-chat">
                                                            <span class="material-symbols-outlined">chat</span>
                                                            {{ translate('chat_with_Provider') }}
                                                            <form action="{{ route('admin.chat.create-channel') }}"
                                                                method="post" id="chatForm-{{ $booking->id }}">
                                                                @csrf
                                                                <input type="hidden" name="provider_id"
                                                                    value="{{ $booking?->provider?->owner?->id ?? $booking?->provider?->user_id }}">
                                                                <input type="hidden" name="type" value="booking">
                                                                <input type="hidden" name="user_type"
                                                                    value="provider-admin">
                                                            </form>
                                                        </div>
                                                    </li>
                                                    @if (in_array($booking->booking_status, ['ongoing', 'accepted']))
                                                        @can('booking_can_manage_status')
                                                            <li>
                                                                <div class="d-flex align-items-center gap-2"
                                                                    data-bs-target="#providerModal" data-bs-toggle="modal">
                                                                    <span
                                                                        class="material-symbols-outlined">manage_history</span>
                                                                    {{ translate('change_Provider') }}
                                                                </div>
                                                            </li>
                                                        @endcan
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
                                        @if($booking->is_verified != 2)
                                            <div class="text-center pb-4">
                                                <button class="btn btn--primary" data-bs-target="#providerModal" data-bs-toggle="modal">{{ translate('assign provider') }}</button>
                                            </div>
                                        @endif
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
                                                            <div
                                                                class="d-flex align-items-center gap-2 cursor-pointer provider-chat">
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

    @include('bookingmodule::admin.booking.partials.details._update-customer-address-modal')
    @if($booking->service_address_id)
        @include('bookingmodule::admin.booking.partials.details._service-address-modal')
    @endif

    @include('bookingmodule::admin.booking.partials.details._service-location-modal')


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
    @include('providermanagement::admin.partials.customer-performance-feedback-modal')

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
        "use strict";

        $('.switcher_input').on('click', function() {
            let paymentStatus = $(this).is(':checked') === true ? 1 : 0;
            payment_status_change(paymentStatus)
        })

        // Provider performance feedback must be submitted before reassigning/changing provider.
        let pendingReassignProviderId = null;
        let pendingPostFeedbackAction = null; // 'reload' | 'reassign'

        const bookingContextId = @json($booking->id);
        const bookingCurrentProviderId = @json($booking->provider_id);
        const bookingCustomerId = @json($booking->customer_id);

        const skipBookingFeedbackUrl = @json(route('admin.provider.booking-admin-feedback.skip'));

        function postBookingAdminFeedbackSkip(side, done) {
            $.ajax({
                url: skipBookingFeedbackUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    booking_id: bookingContextId,
                    side: side
                },
                success: function () {
                    if (typeof done === 'function') {
                        done();
                    } else {
                        location.reload();
                    }
                },
                error: function (xhr) {
                    toastr.error(xhr?.responseJSON?.message ?? '{{ translate('Something went wrong') }}');
                }
            });
        }

        $('#customerPerformanceFeedbackSkip').on('click', function () {
            postBookingAdminFeedbackSkip('customer', function () {
                const modalEl = document.getElementById('customerPerformanceFeedbackModal');
                bootstrap.Modal.getInstance(modalEl)?.hide();
                location.reload();
            });
        });

        function openCustomerPerformanceFeedbackModal(customerId, actionType = 'completed') {
            if (!customerId) {
                toastr.error('{{ translate('Customer not found for feedback.') }}');
                return;
            }
            $('#customerPerformanceContextBookingId').val(bookingContextId);
            $('#customerPerformanceCustomerId').val(customerId);
            $('#customerPerformanceActionType').val(actionType === 'canceled' ? 'cancelled' : actionType);
            $('#customerPerformanceNotes').val('');
            $('#customerPerformanceFeedbackForm input[type="radio"]').prop('checked', false);
            $('#customerPerformanceFeedbackForm input[type="checkbox"]').prop('checked', false);

            document.querySelectorAll('.modal.show').forEach((m) => {
                if (m?.id !== 'customerPerformanceFeedbackModal') {
                    bootstrap.Modal.getInstance(m)?.hide();
                }
            });
            $('.modal-backdrop').remove();

            const modalEl = document.getElementById('customerPerformanceFeedbackModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }

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

        $('.reassign-provider').on('click', function() {
            let newProviderId = $(this).data('provider-reassign');
            pendingReassignProviderId = newProviderId;
            pendingPostFeedbackAction = 'reassign';

            // Evaluate the currently assigned provider (if present); otherwise evaluate the provider being assigned.
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
            const st = @json($booking->booking_status);
            const at = st === 'canceled' ? 'canceled' : 'completed';
            openProviderPerformanceFeedbackModal(bookingCurrentProviderId, at);
        });

        $('.open-customer-feedback-manual').on('click', function() {
            pendingPostFeedbackAction = 'reload';
            const st = @json($booking->booking_status);
            const at = st === 'canceled' ? 'canceled' : 'completed';
            openCustomerPerformanceFeedbackModal(bookingCustomerId, at);
        });

        $('#customerPerformanceFeedbackForm').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const type = $form.find('input[name="incident_type"]:checked').val();
            if (!type) {
                toastr.error('{{ translate('Please select a feedback type.') }}');
                return;
            }
            const route = $form.data('feedback-route');
            pendingPostFeedbackAction = 'reload';

            $.ajax({
                url: route,
                type: 'POST',
                dataType: 'json',
                data: $form.serialize(),
                beforeSend: function () {
                    $('#customerPerformanceFeedbackSubmit').prop('disabled', true);
                },
                success: function () {
                    $('#customerPerformanceFeedbackSubmit').prop('disabled', false);
                    const modalEl = document.getElementById('customerPerformanceFeedbackModal');
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                    location.reload();
                },
                error: function (xhr) {
                    $('#customerPerformanceFeedbackSubmit').prop('disabled', false);
                    toastr.error(xhr?.responseJSON?.message ?? '{{ translate('Failed to store feedback') }}');
                }
            });
        });

        $('.reassign-serviceman').on('click', function() {
            let id = $(this).data('serviceman-reassign');
            updateServiceman(id)
        })

        $('.offline-payment').on('click', function() {
            let route = '{{ route('admin.booking.offline-payment.verify', ['booking_id' => $booking->id]) }}'+ '&payment_status=' + 'approved';
            route_alert_reload(route, '{{ translate('Want to verify the payment') }}', true);
        })

        @if ($booking->booking_status == 'pending')
            $(document).ready(function() {
                selectElementVisibility('serviceman_assign', false);
                selectElementVisibility('payment_status', false);
            });
        @endif

        $("#booking_status").change(function() {
            var $select = $("#booking_status");
            var booking_status = $select.val();
            var previous_status = $select.data('current');
            if (booking_status && booking_status !== '0') {
                if (booking_status === 'completed' && $select.data('can-complete') === '0') {
                    toastr.error('{{ translate('Booking cannot be completed until full payment is received.') }}', { CloseButton: true, ProgressBar: true });
                    $select.val(previous_status).trigger('change');
                    if ($select.next(".select2-container").length) {
                        $select.next(".select2-container").find(".select2-selection__rendered").text($select.find("option:selected").text());
                    }
                    return;
                }
                var route = '{{ route('admin.booking.status_update', [$booking->id]) }}' + '?booking_status=' + booking_status;
                var message = booking_status === 'canceled'
                    ? '{{ translate('Please contact the customer before proceeding with the cancellation process.') }}'
                    : '{{ translate('want_to_update_status') }}';
                update_booking_details(route, message, 'booking_status', booking_status, previous_status);
            } else {
                toastr.error('{{ translate('choose_proper_status') }}');
            }
        });

        $(".change-booking-status").on('click', function() {
            var booking_status = 'canceled';
            var route = '{{ route('admin.booking.status_update', [$booking->id]) }}' + '?booking_status=' + booking_status;
            update_booking_details(route, '{{ translate('want_to_cancel_booking_status') }}', 'booking_status', booking_status);
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
            var $input = $("#service_schedule");
            var service_schedule = $input.val();
            var original = $input.data('original');

            if (!service_schedule) {
                $input.val(original);
                return;
            }

            // Normalize formats (replace space with 'T' for parsing)
            var newDate = new Date(service_schedule);
            var originalDate = new Date(original.replace(" ", "T"));
            var now = new Date();

            // Compare with current time
            if (newDate < now) {
                toastr.error("Reschedule cannot be earlier than the current time");
                $input.val(original);
                return;
            }

            // Compare with original schedule
            if (newDate < originalDate) {
                toastr.error("Reschedule cannot be earlier than the original schedule");
                $input.val(original);
                return;
            }

            var route = '{{ route('admin.booking.schedule_update', [$booking->id]) }}' + '?service_schedule=' + service_schedule;

            update_booking_details(route, '{{ translate('want_to_update_the_booking_schedule') }}', 'service_schedule', service_schedule);
        }

        $(".switch-to-cash-after-service").on('click', function() {
            var payment_method = 'cash_after_service';
            var route = '{{ route('admin.booking.switch-payment-method', [$booking->id]) }}' + '?payment_method=' + payment_method;
            update_booking_details(route, '{{ translate('want_to_switch_payment_method_to_cash_after_service') }}', 'payment_method', payment_method);
        });

        $(document).on('submit', '.add-payment-form', function(e) {
            var $form = $(this);
            var dueAmount = parseFloat($form.data('due-amount')) || 0;
            var amount = parseFloat($form.find('.add-payment-amount').val()) || 0;
            if (dueAmount > 0 && amount > dueAmount) {
                e.preventDefault();
                toastr.error('{{ translate('Amount cannot exceed the due amount. Due amount') }}: ' + dueAmount.toFixed(2));
                return false;
            }
        });

        $(document).on('submit', '.refund-form', function(e) {
            var $form = $(this);
            var maxAmount = parseFloat($form.data('max-amount')) || 0;
            var amount = parseFloat($form.find('.refund-amount').val()) || 0;
            if (maxAmount > 0 && amount > maxAmount) {
                e.preventDefault();
                toastr.error('{{ translate('Refund amount cannot exceed amount paid by customer. Max') }}: ' + maxAmount.toFixed(2));
                return false;
            }
        });

        function update_booking_details(route, message, componentId, updatedValue, revertValue) {
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
                            if (componentId === 'booking_status') {
                                $("#booking_status").data('current', updatedValue);
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
                                componentId === 'service_schedule' || componentId === 'serviceman_assign' || componentId === 'payment_method' ) {
                                location.reload();
                            }
                        },
                        error: function(xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ translate('Something went wrong. Please try again.') }}';
                            if (componentId === 'booking_status' && revertValue !== undefined) {
                                $("#booking_status").val(revertValue).trigger('change');
                                $("#booking_status").data('current', revertValue);
                                if ($("#booking_status").next(".select2-container").length) {
                                    $("#booking_status").next(".select2-container").find(".select2-selection__rendered").text($("#booking_status option:selected").text());
                                }
                            }
                            toastr.error(msg, { CloseButton: true, ProgressBar: true });
                        },
                        complete: function() {},
                    });
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
        });

        $(document).on('change', '#serviceUpdateModal--{{ $booking["id"] }} .row-service-select', function() {
            const $row = $(this).closest('tr');
            const $variantSelect = $row.find('.row-variant-select');
            const serviceId = $(this).val();
            const zoneId = $(this).data('zone-id') || '{{ $booking->zone_id }}';
            if (!serviceId) {
                $variantSelect.html('<option value="" selected disabled>{{ translate('Select Service Variant') }}</option>');
                return;
            }
            const route = '{{ route('admin.booking.service.ajax-get-variant') }}' + '?service_id=' + serviceId + '&zone_id=' + zoneId;
            $.get({
                url: route,
                dataType: 'json',
                success: function(response) {
                    let options = '<option value="" selected disabled>{{ translate('Select Service Variant') }}</option>';
                    (response.content || []).forEach(function(item) {
                        options += '<option value="' + item.variant_key + '">' + (item.variant || item.variant_key) + '</option>';
                    });
                    $variantSelect.html(options);
                },
                error: function() {
                    toastr.error('{{ translate('Failed to load') }}');
                }
            });
        });

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

        // for update customer location from service address modal
        $(document).ready(function() {
            function initAutocomplete() {
                let myLatLng = {
                    lat: {{ $customerAddress?->lat ?? 23.811842872190343 }},
                    lng: {{ $customerAddress?->lon ?? 90.356331 }}
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

        // for update service location from update customer address modal
        $(document).ready(function() {
            function addressMap() {
                let myLatLng = {
                    lat: {{ $booking->service_address?->lat ?? 23.811842872190343 }},
                    lng: {{ $booking->service_address?->lon ?? 90.356331 }}
                };
                const map = new google.maps.Map(document.getElementById("address_location_map_canvas"), {
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

                    document.getElementById('address_latitude').value = coordinates['lat'];
                    document.getElementById('address_longitude').value = coordinates['lng'];


                    geocoder.geocode({
                        'latLng': latlng
                    }, function(results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            if (results[1]) {
                                document.getElementById('address_address').value = results[1].formatted_address;
                            }
                        }
                    });
                });

                const input = document.getElementById("address_pac-input");
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
                            document.getElementById('address_latitude').value = this.position.lat();
                            document.getElementById('address_longitude').value = this.position.lng();
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
            addressMap();
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
                complete: function() {},
                error: function() {
                    toastr.error('{{ translate('Failed to load') }}');
                }
            });
        }

        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $(document).on('keyup', '.search-form-input1', function() {
                const route = '{{ url('admin/booking/serviceman-update', $booking->id) }}';
                let searchTerm = $('.search-form-input1').val();

                $.ajax({
                    url: route,
                    type: 'PUT',
                    dataType: 'json',
                    data: {
                        booking_id: "{{ $booking->id }}",
                        search: searchTerm,
                    },
                    beforeSend: function() {},
                    success: function(response) {
                        $('.modal-content-data1').html(response.view);
                    },
                    complete: function() {},
                    error: function(xhr) {
                        if (xhr.status === 419) {
                            toastr.error('{{ translate('Session expired, please refresh the page.') }}');
                        } else {
                            toastr.error('{{ translate('Failed to load') }}');
                        }
                    }
                });
            });
        });


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

        $(document).ready(function() {
            // Hide all cancellation notes initially
            $('.cancellation-note').hide();

            // When a radio button changes in the modal
            $('.booking-verification-status').change(function() {
                const $modal = $(this).closest('.modal');
                const $cancellationNote = $modal.find('.cancellation-note textarea');

                if ($(this).hasClass('deny-request') && $(this).is(':checked')) {
                    $modal.find('.cancellation-note').show();
                    $cancellationNote.prop('required', true);
                } else if ($(this).hasClass('approve-request') && $(this).is(':checked')) {
                    $modal.find('.cancellation-note').hide();
                    $cancellationNote.prop('required', false);
                }
            });
        });

        $(document).on('click', '.customer-chat, .provider-chat', function(e) {
            e.preventDefault();
            $(this).find('form').trigger('submit');
        });


        // for update service location from update customer address modal
        $(document).ready(function() {
            function addressMap() {
                let myLatLng = {
                    lat: {{ $booking->service_address?->lat ?? 23.811842872190343 }},
                    lng: {{ $booking->service_address?->lon ?? 90.356331 }}
                };
                const map = new google.maps.Map(document.getElementById("address_location_map_canvas"), {
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

                    document.getElementById('address_latitude').value = coordinates['lat'];
                    document.getElementById('address_longitude').value = coordinates['lng'];


                    geocoder.geocode({
                        'latLng': latlng
                    }, function(results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            if (results[1]) {
                                document.getElementById('address_address').value = results[1].formatted_address;
                            }
                        }
                    });
                });

                const input = document.getElementById("address_pac-input");
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
                            document.getElementById('address_latitude').value = this.position.lat();
                            document.getElementById('address_longitude').value = this.position.lng();
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
            addressMap();
        });

        $(document).ready(function() {
            // Get booking ID dynamically
            var bookingId = "{{ $booking['id'] }}";

            function toggleServiceLocation() {
                if ($('#customer_location').is(':checked')) {
                    $('.customer-details').show();
                    $('.provider-details').hide();
                } else {
                    $('.customer-details').hide();
                    $('.provider-details').show();
                }
            }

            // Run toggle function on radio button change
            $('input[name="service_location"]').on('change', function() {
                toggleServiceLocation();
            });

            // Run toggle function when the modal is opened
            $('#serviceLocationModal--' + bookingId).on('shown.bs.modal', function () {
                toggleServiceLocation();
            });

            // When the address modal opens, hide the first modal
            $('#customerAddressModal--' + bookingId).on('show.bs.modal', function () {
                $('#serviceLocationModal--' + bookingId).modal('hide'); // Hide the first modal
            });

            // When the address modal closes, reopen the service location modal and update the address
            $('#customerAddressModal--' + bookingId).on('hidden.bs.modal', function () {
                $('#serviceLocationModal--' + bookingId).modal('show'); // Show the first modal again
            });
        });

        $(document).ready(function () {
            $("#customerAddressModalSubmit").on("submit", function (e) {
                e.preventDefault(); // Prevent form submission

                var bookingId = "{{ $booking['id'] }}";

                let customerAddressModal = $("#customerAddressModal--" + bookingId);
                let serviceLocationModal = $("#serviceLocationModal--" + bookingId);

                // Copy updated data from customerAddressModal inputs
                let contactPersonName = customerAddressModal.find("input[name='contact_person_name']").val();
                let contactPersonNumber = customerAddressModal.find("input[name='contact_person_number']").val();
                let addressLabel = customerAddressModal.find("select[name='address_label']").val();
                let address = customerAddressModal.find("input[name='address']").val();
                let latitude = customerAddressModal.find("input[name='latitude']").val();
                let longitude = customerAddressModal.find("input[name='longitude']").val();
                let city = customerAddressModal.find("input[name='city']").val();
                let street = customerAddressModal.find("input[name='street']").val();
                let zipCode = customerAddressModal.find("input[name='zip_code']").val();
                let country = customerAddressModal.find("input[name='country']").val();

                // Update the corresponding hidden inputs in serviceLocationModal
                serviceLocationModal.find("input[name='contact_person_name']").val(contactPersonName);
                serviceLocationModal.find("input[name='contact_person_number']").val(contactPersonNumber);
                serviceLocationModal.find("input[name='address_label']").val(addressLabel);
                serviceLocationModal.find("input[name='address']").val(address);
                serviceLocationModal.find("input[name='latitude']").val(latitude);
                serviceLocationModal.find("input[name='longitude']").val(longitude);
                serviceLocationModal.find("input[name='city']").val(city);
                serviceLocationModal.find("input[name='street']").val(street);
                serviceLocationModal.find("input[name='zip_code']").val(zipCode);
                serviceLocationModal.find("input[name='country']").val(country);

                $('.updated_customer_name').text(contactPersonName); // Update the customer name
                $('#updated_customer_phone').text(contactPersonNumber); // Update the customer
                $('#customer_service_location').removeClass('text-danger'); // Update the customer service location
                $('#customer_service_location').text(address); // Update the customer service location
                $('.customer-address-update-btn').removeAttr('disabled'); // Update the customer service location update button

               // Close the customerAddressModal
                customerAddressModal.modal("hide");

                // Open the serviceLocationModal to show updated data
                serviceLocationModal.modal("show");
            });
        });

        $(".customer-address-reset-btn").on("click", function (e) {
            e.preventDefault(); // prevent default behavior

            // Reset the form (visible inputs)
            $("#customerAddressModalSubmit")[0].reset();

            // Restore hidden inputs to original values from server
            $("input[name='contact_person_name']").val("{{ $booking->service_address->contact_person_name ?? '' }}");
            $("input[name='contact_person_number']").val("{{ $booking->service_address->contact_person_number ?? '' }}");
            $("input[name='address_label']").val("{{ $booking->service_address->label ?? '' }}");
            $("input[name='address']").val("{{ $booking->service_address->address ?? '' }}");
            $("input[name='latitude']").val("{{ $booking->service_address->latitude ?? '' }}");
            $("input[name='longitude']").val("{{ $booking->service_address->longitude ?? '' }}");
            $("input[name='city']").val("{{ $booking->service_address->city ?? '' }}");
            $("input[name='street']").val("{{ $booking->service_address->street ?? '' }}");
            $("input[name='zip_code']").val("{{ $booking->service_address->zip_code ?? '' }}");
            $("input[name='country']").val("{{ $booking->service_address->country ?? '' }}");

            // Update the UI
            let name = {!! json_encode($customerName ?? '') !!};
            let phone = {!! json_encode($customerPhone ?? '') !!};
            let customerAddress = "{{ $booking?->service_address?->address }}";

            $('.updated_customer_name').text(name); // Update the customer name
            $('#updated_customer_phone').text(phone); // Update the customer phone

            if (customerAddress) {
                $('#customer_service_location').text(customerAddress);
                $('#customer_service_location').removeClass('text-danger');
                $('.customer-address-update-btn').removeAttr('disabled');
            } else {
                $('#customer_service_location').text("No address found");
                $('#customer_service_location').addClass('text-danger');
                $('.customer-address-update-btn').attr('disabled', true);
            }
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
