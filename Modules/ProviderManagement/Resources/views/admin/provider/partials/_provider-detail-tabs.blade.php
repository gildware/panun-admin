<ul class="nav nav--tabs nav--tabs__style2">
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'overview' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=overview">{{ translate('Overview') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'subscribed_services' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscribed_services">{{ translate('Subscribed_Services') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bookings">{{ translate('Bookings') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'special_bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=special_bookings">{{ translate('Special_Bookings') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'payment' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=payment">{{ translate('Payment') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'reviews' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=reviews">{{ translate('Reviews') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'performance' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=performance">{{ translate('Performance') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'bank_information' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bank_information">{{ translate('Bank_Information') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'serviceman_list' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=serviceman_list">{{ translate('Service_Man_List') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'subscription' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscription&provider_id={{ request()->id ?? request()->provider_id }}">{{ translate('Business Plan') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'settings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=settings">{{ translate('Settings') }}</a>
    </li>
</ul>
