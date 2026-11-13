@php
    $webPage = $webPage ?? request('web_page', 'details');
    $commentCount = $commentCount ?? (isset($sortedComments) ? $sortedComments->count() : 0);
    $detailsBase = admin_booking_details_url($booking, 'details');
@endphp
<ul class="nav nav--tabs nav--tabs__style2">
    <li class="nav-item">
        <a class="nav-link {{ $webPage == 'details' ? 'active' : '' }}"
           href="{{ admin_booking_details_url($booking, 'details') }}">{{ translate('details') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $webPage == 'history' ? 'active' : '' }}"
           href="{{ admin_booking_details_url($booking, 'history') }}">{{ translate('History') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $webPage == 'followups' ? 'active' : '' }}"
           href="{{ admin_booking_details_url($booking, 'followups') }}">{{ translate('Followups') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $webPage == 'comments' ? 'active' : '' }}"
           href="{{ admin_booking_details_url($booking, 'comments') }}">
            {{ translate('Comments') }}
            @if(!empty($commentCount) && $commentCount > 0)
                <span class="badge bg-secondary ms-1">{{ $commentCount }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $webPage == 'payments' ? 'active' : '' }}"
           href="{{ $detailsBase }}#repeat-payments">{{ translate('Payments') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $webPage == 'service_log' || $webPage == 'visits' ? 'active' : '' }}"
           href="{{ $detailsBase }}#repeat-service-log">{{ translate('service_log') }}</a>
    </li>
</ul>
