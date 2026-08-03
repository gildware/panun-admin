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
        <a href="{{ route('admin.booking.list') }}" class="booking-detail-topbar__back">
            <span class="material-icons">arrow_back</span>
            {{ translate('Back_to_Bookings') }}
        </a>
    </div>
</div>
