<ul class="nav nav--tabs nav--tabs__style2">
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'details' ? 'active' : '' }}"
           href="{{ url()->current() }}?web_page=details">{{ translate('details') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'payments' ? 'active' : '' }}"
           href="{{ url()->current() }}?web_page=payments">{{ translate('Payments') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ ($webPage ?? '') == 'service_log' ? 'active' : '' }}"
           href="{{ url()->current() }}?web_page=service_log">{{ translate('service_log') }}</a>
    </li>
</ul>
