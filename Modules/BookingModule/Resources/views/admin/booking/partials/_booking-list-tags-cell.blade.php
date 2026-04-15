{{-- Scenario / reopen tags for list columns; expects $booking (\Modules\BookingModule\Entities\Booking). --}}
<div class="d-inline-flex flex-row flex-nowrap gap-1 align-items-center text-nowrap" style="white-space: nowrap;">
    @if(empty($booking->is_repeated))
        @if($booking->isOpenReopenTicket())
            <span class="badge bg-warning text-dark text-nowrap text-start d-inline-block lh-sm">{{ translate('Reopened') }}</span>
        @elseif($booking->isReopenedTagged() && (empty($booking->reopen_disputed_snapshot) || !is_array($booking->reopen_disputed_snapshot)))
            <span class="badge bg-success text-nowrap text-start d-inline-block lh-sm">{{ translate('Resolved') }}</span>
        @endif
    @endif
    @include('bookingmodule::admin.booking.partials._booking-settlement-list-badge', [
        'booking' => $booking,
        'bookingListTagStacked' => true,
    ])
</div>
