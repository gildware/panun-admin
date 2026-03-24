<div class="mb-3">
    <ul class="nav nav--tabs nav--tabs__style2">
        <li class="nav-item">
            <a class="nav-link {{ ($webPage ?? '') === 'overview' ? 'active' : '' }}"
               href="{{ url()->current() }}?web_page=overview">{{ translate('Overview') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ ($webPage ?? '') === 'bookings' ? 'active' : '' }}"
               href="{{ url()->current() }}?web_page=bookings">{{ translate('Bookings') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ ($webPage ?? '') === 'reviews' ? 'active' : '' }}"
               href="{{ url()->current() }}?web_page=reviews">{{ translate('Reviews') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ ($webPage ?? '') === 'performance' ? 'active' : '' }}"
               href="{{ url()->current() }}?web_page=performance">{{ translate('Performance') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ ($webPage ?? '') === 'payments' ? 'active' : '' }}"
               href="{{ url()->current() }}?web_page=payments">{{ translate('Payment') }}</a>
        </li>
    </ul>
</div>
