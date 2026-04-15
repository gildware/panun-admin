{{-- Delegates to unified admin status tags (list + details share the same markup). --}}
@include('bookingmodule::admin.booking.partials._booking-admin-status-tags', [
    'booking' => $booking,
    'bookingListTagStacked' => $bookingListTagStacked ?? null,
])
