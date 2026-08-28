@php
    $repeatListUrl = $repeatChromeBackUrl ?? route('admin.booking.repeat_list', ['booking_status' => 'all', 'service_type' => 'all']);
    $repeatBackLabel = $repeatChromeBackLabel ?? translate('Back_to_Bookings');
    $repeatCrumb = $repeatChromeCrumb ?? ('#' . ($booking['readable_id'] ?? ''));
    $repeatHasParentCrumb = ! empty($repeatChromeParentCrumb) && ! empty($repeatChromeParentUrl);
@endphp
<div class="booking-detail-topbar">
    <nav class="breadcrumb-bar" aria-label="breadcrumb">
        <a href="{{ $repeatListUrl }}">{{ translate('Repeat_booking') }}</a>
        <span class="material-icons">chevron_right</span>
        @if($repeatHasParentCrumb)
            <a href="{{ $repeatChromeParentUrl }}">{{ $repeatChromeParentCrumb }}</a>
            <span class="material-icons">chevron_right</span>
        @endif
        <span class="breadcrumb-bar__current">{{ $repeatCrumb }}</span>
    </nav>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ $repeatListUrl }}" class="booking-detail-topbar__back">
            <span class="material-icons">arrow_back</span>
            {{ $repeatBackLabel }}
        </a>
    </div>
</div>
