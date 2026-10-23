@if($booking->trashed())
    @php
        $deletedByUser = $booking->relationLoaded('deletedByUser') ? $booking->deletedByUser : $booking->deletedByUser()->first();
        $deletedByName = trim((string) (($deletedByUser->first_name ?? '').' '.($deletedByUser->last_name ?? '')));
        if ($deletedByName === '' && $deletedByUser) {
            $deletedByName = (string) ($deletedByUser->email ?: $deletedByUser->phone);
        }
    @endphp
    <div class="alert alert-warning mb-3">
        <div class="fw-medium mb-1">{{ translate('This_booking_is_deleted') }}</div>
        <div>{{ translate('Deletion_reason') }}: {{ $booking->deletion_reason ?: '—' }}</div>
        <div class="small text-muted mt-1">
            {{ translate('Deleted_by') }}: {{ $deletedByName !== '' ? $deletedByName : '—' }}
            · {{ translate('Deleted_at') }}: {{ optional($booking->deleted_at)->format('d M Y, h:i a') }}
        </div>
        <a href="{{ route('admin.booking.deleted') }}" class="btn btn-sm btn--secondary mt-2">{{ translate('Back_to_deleted_bookings') }}</a>
    </div>
@endif
