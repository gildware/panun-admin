{{-- Workflow booking status for list columns; expects $booking (\Modules\BookingModule\Entities\Booking). --}}
@php
    $__st = strtolower((string) ($booking->booking_status ?? ''));
    $__badgeClass = match ($__st) {
        'ongoing' => 'warning',
        'on_hold' => 'secondary',
        'completed' => 'success',
        'canceled', 'cancelled' => 'danger',
        'refunded' => 'success',
        default => 'info',
    };
    $__label = booking_admin_booking_status_display_label($booking);
@endphp
<span class="badge badge-{{ $__badgeClass }} text-capitalize">{{ $__label }}</span>
