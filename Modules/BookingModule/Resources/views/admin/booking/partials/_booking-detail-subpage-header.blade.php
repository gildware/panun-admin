@php
    $__subHeaderStatusLabel = function_exists('booking_admin_booking_status_display_label')
        ? booking_admin_booking_status_display_label($booking)
        : ucwords(str_replace('_', ' ', (string) ($booking->booking_status ?? '')));
@endphp
<section class="booking-hero">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h3 class="c1 fw-bold mb-0">{{ translate('Booking') }} # {{ $booking['readable_id'] }}</h3>
                @include('bookingmodule::admin.booking.partials._booking-list-status-badge', ['booking' => $booking])
                @if(!empty($followupDetailMeta['has_any_pending']))
                    <span class="badge {{ !empty($followupDetailMeta['has_any_overdue']) ? 'bg-danger' : 'bg-warning text-dark' }}">
                        {{ !empty($followupDetailMeta['has_any_overdue']) ? translate('Missed_Follow_up') : translate('Pending_Follow_up') }}
                    </span>
                @endif
            </div>
            <p class="opacity-75 fz-12 mb-0">{{ translate('Booking_Placed') }}
                : {{ date('d-M-Y h:ia', strtotime($booking->created_at)) }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.booking.invoice', [$booking->id]) }}" class="btn btn-primary btn-sm" target="_blank">
                <span class="material-icons">description</span>{{ translate('Invoice') }}
            </a>
        </div>
    </div>
</section>
