<div class="booking-detail-topbar">
    <nav class="breadcrumb-bar" aria-label="breadcrumb">
        <a href="{{ route('admin.booking.list') }}">{{ translate('Bookings') }}</a>
        <span class="material-icons">chevron_right</span>
        <span class="breadcrumb-bar__current">#{{ $booking['readable_id'] ?? $booking->id }}</span>
        @if(!empty($booking->lead_id))
            <span class="material-icons">chevron_right</span>
            <a href="{{ route('admin.lead.show', $booking->lead_id) }}">{{ translate('Lead_ID') }} #{{ $booking->lead_id }}</a>
        @endif
    </nav>
    <div class="d-flex align-items-center gap-2">
        @can('booking_delete')
            <button type="button"
                    class="action-btn btn--danger rounded-circle"
                    style="--size: 30px"
                    data-bs-toggle="modal"
                    data-bs-target="#bookingDeleteModal--{{ $booking['id'] }}"
                    title="{{ translate('Delete') }}">
                <span class="material-symbols-outlined" style="font-size:16px">delete</span>
            </button>
        @endcan
        <a href="{{ route('admin.booking.list') }}" class="booking-detail-topbar__back">
            <span class="material-icons">arrow_back</span>
            {{ translate('Back_to_Bookings') }}
        </a>
    </div>
</div>
