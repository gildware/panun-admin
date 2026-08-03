{{-- Workflow booking status for list columns; expects $booking (\Modules\BookingModule\Entities\Booking). --}}
@php
    $__statusKey = booking_admin_status_display_key($booking);
    $__label = booking_admin_booking_status_display_label($booking);
@endphp
<span class="badge booking-status-badge text-capitalize" data-status="{{ $__statusKey }}">{{ $__label }}</span>
