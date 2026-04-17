{{-- Workflow booking status for list columns; expects $booking (\Modules\BookingModule\Entities\Booking). --}}
@php
    $__st = strtolower((string) ($booking->booking_status ?? ''));
    $__hasDisputedSnapshot = !empty($booking->reopen_disputed_snapshot) && is_array($booking->reopen_disputed_snapshot);
    $__disputedSnap = $__hasDisputedSnapshot ? (array) $booking->reopen_disputed_snapshot : null;
    $__dsRetained = $__hasDisputedSnapshot ? round((float) ($__disputedSnap['retained_from_customer'] ?? $__disputedSnap['final_net_to_customer'] ?? 0), 2) : 0.0;
    $__dsZeroRetained = $__hasDisputedSnapshot && $__dsRetained <= 0.009;
    $__badgeClass = match ($__st) {
        'ongoing' => 'warning',
        'on_hold' => 'secondary',
        'completed' => 'success',
        'canceled', 'cancelled' => 'danger',
        default => 'info',
    };
    if ($__hasDisputedSnapshot) {
        $__badgeClass = $__dsZeroRetained ? 'danger' : 'warning-dark';
    }
    $__label = booking_admin_booking_status_display_label($booking);
@endphp
<span class="badge badge-{{ $__badgeClass }} text-capitalize">{{ $__label }}</span>
