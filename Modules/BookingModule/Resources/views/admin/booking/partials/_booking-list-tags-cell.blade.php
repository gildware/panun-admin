{{-- Scenario / reopen tags for list columns; expects $booking (\Modules\BookingModule\Entities\Booking). --}}
<div class="d-inline-flex flex-row flex-nowrap gap-1 align-items-center text-nowrap" style="white-space: nowrap;">
    @include('bookingmodule::admin.booking.partials._booking-admin-status-tags', [
        'booking' => $booking,
        'bookingListTagStacked' => true,
    ])
</div>
