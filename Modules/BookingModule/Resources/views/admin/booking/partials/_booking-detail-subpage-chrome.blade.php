@php
    $isRepeatSeries = (int) ($booking->is_repeated ?? 0) === 1;
@endphp
@if($isRepeatSeries)
    @php
        extract(repeat_admin_detail_chrome_vars($booking, $customerAddress ?? $booking->service_address ?? null));
    @endphp
    @include('bookingmodule::admin.booking.partials._repeat-detail-compact-topbar')
    @include('bookingmodule::admin.booking.partials._repeat-detail-compact-header')
@else
    @include('bookingmodule::admin.booking.partials._booking-detail-compact-topbar', ['booking' => $booking])
    @include('bookingmodule::admin.booking.partials._booking-detail-pipeline', ['booking' => $booking])
    @include('bookingmodule::admin.booking.partials._booking-detail-subpage-header', [
        'booking' => $booking,
        'followupDetailMeta' => $followupDetailMeta ?? null,
    ])
@endif

@include('bookingmodule::admin.booking.partials.details._special-financial-settlement-banner', ['booking' => $booking])

<div class="d-flex flex-wrap justify-content-between align-items-center flex-xxl-nowrap gap-3 mb-3 booking-detail-nav-wrap">
    @if($isRepeatSeries)
        @include('bookingmodule::admin.booking.partials._repeat-booking-tabs', [
            'booking' => $booking,
            'webPage' => $webPage,
            'commentCount' => $commentCount ?? null,
            'sortedComments' => $sortedComments ?? null,
        ])
    @else
        @include('bookingmodule::admin.booking.partials._booking-detail-nav-tabs', [
            'booking' => $booking,
            'webPage' => $webPage,
            'commentCount' => $commentCount ?? null,
        ])
    @endif
</div>
