@php
    $__showRefundActionsBox = auth()->check()
        && auth()->user()->can('booking_can_manage_status')
        && in_array($booking->booking_status, ['canceled', 'cancelled', 'refunded'], true)
        && isset($maxRefundAmount)
        && $maxRefundAmount > 0;
    $__showCompensationBox = $booking->adminEligibleForCompensationRecording();
@endphp
@if($__showRefundActionsBox || $__showCompensationBox)
    <div class="row g-2 booking-refund-compensation-row">
        <div class="col-lg-6 booking-refund-compensation-row__cell booking-refund-compensation-row__cell--refund">
            @if($__showRefundActionsBox)
                @include('bookingmodule::admin.booking.partials._booking-refund-actions', [
                    'booking' => $booking,
                    'maxRefundAmount' => $maxRefundAmount,
                ])
            @endif
        </div>
        <div class="col-lg-6 booking-refund-compensation-row__cell booking-refund-compensation-row__cell--compensation">
            @if($__showCompensationBox)
                @include('bookingmodule::admin.booking.partials.details._compensation-box', ['booking' => $booking])
            @endif
        </div>
    </div>
@endif
