@php
    $heroDisplayName = trim((string) ($repeatChromeCustomerName ?? ''));
    $heroPhone = $repeatChromeCustomerPhone ?? null;
    $heroInitials = '—';
    if ($heroDisplayName !== '') {
        $heroParts = preg_split('/\s+/', $heroDisplayName);
        $heroInitials = strtoupper(mb_substr($heroParts[0], 0, 1) . (isset($heroParts[1]) ? mb_substr($heroParts[1], 0, 1) : ''));
    } elseif (! empty($heroPhone)) {
        $heroInitials = mb_substr(preg_replace('/\D/', '', (string) $heroPhone), -2);
    }
    $statusKey = strtolower((string) ($repeatChromeEntity->booking_status ?? $booking->booking_status ?? 'pending'));
    $statusLabel = ucwords(str_replace('_', ' ', $statusKey));
    $placedAt = $repeatChromePlacedAt ?? ($repeatChromeEntity->created_at ?? $booking->created_at);
    $hasHeroPhone = ! empty($heroPhone);
    $hasSubtitle = ! empty($repeatChromeSubtitle);
    $hasSchedule = ! empty($repeatChromeSchedule);
    $showStopSeries = ! empty($repeatChromeShowStopSeries);
    $hasInvoice = ! empty($repeatChromeInvoiceUrl);
    $showAddVisit = ! empty($repeatChromeShowAddVisit) || ! empty($repeatChromeShowScheduleVisit);
@endphp
<header class="booking-header">
    <div class="booking-header__top">
        <div class="booking-avatar">{{ $heroInitials }}</div>
        <div class="booking-identity">
            <h1 class="booking-title">
                {{ $repeatChromeTitle }}
                <img width="18" height="18" src="{{ asset('assets/admin-module/img/icons/repeat.svg') }}" alt="" class="ms-1">
            </h1>
            <div class="booking-contact">
                @if($heroDisplayName !== '')
                    <span class="booking-contact__name">{{ $heroDisplayName }}</span>
                @endif
                @if($hasHeroPhone)
                    <a href="tel:{{ $heroPhone }}" class="phone">
                        <span class="material-icons">phone</span>
                        {{ $heroPhone }}
                    </a>
                @endif
            </div>
            <div class="booking-meta">
                @if($hasSubtitle)
                    <span>{{ $repeatChromeSubtitle }}</span>
                @endif
                <span>{{ translate('Booking_Placed') }} {{ date('d-M-Y h:ia', strtotime($placedAt)) }}</span>
                @if($hasSchedule)
                    <span>{{ translate('Schedule_Date') }} {{ $repeatChromeSchedule }}</span>
                @endif
            </div>
        </div>
        <div class="booking-header__badges">
            <span class="badge booking-status-badge text-capitalize" data-status="{{ $statusKey }}">{{ $statusLabel }}</span>
        </div>
    </div>
    <div class="booking-header__actions">
        @if($showAddVisit)
            @can('booking_edit')
                <button type="button" class="btn btn-demo-outline btn-sm" data-bs-toggle="modal" data-bs-target="#addRepeatVisitModal" data-visit-kind="scheduled">
                    {{ translate('Add_visit') }}
                </button>
            @endcan
        @endif
        @if($showStopSeries)
            @can('booking_can_manage_status')
                <button type="button" class="btn btn-demo-outline btn-sm" data-bs-toggle="modal" data-bs-target="#stopRepeatSeriesModal">
                    {{ translate('Stop_series_and_complete') }}
                </button>
            @endcan
        @endif
        @if($hasInvoice)
            <a href="{{ $repeatChromeInvoiceUrl }}" class="btn btn-demo-outline btn-sm" target="_blank" rel="noopener">
                <span class="material-icons">description</span>{{ translate('Invoice') }}
            </a>
        @endif
    </div>
</header>
@include('bookingmodule::admin.booking.partials._booking-detail-pipeline', ['booking' => $repeatChromeEntity ?? $booking])
