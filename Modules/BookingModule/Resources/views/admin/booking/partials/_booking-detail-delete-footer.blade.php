@can('booking_delete')
    <div class="booking-detail-delete-footer">
        <button type="button"
                class="btn btn-outline-danger btn-sm booking-detail-delete-footer__btn"
                data-bs-toggle="modal"
                data-bs-target="#bookingDeleteModal--{{ $booking['id'] }}">
            <span class="material-symbols-outlined" aria-hidden="true">delete</span>
            {{ translate('Delete') }}
        </button>
    </div>

    @include('bookingmodule::admin.booking.partials._booking-detail-delete-modal', ['booking' => $booking])
@endcan
