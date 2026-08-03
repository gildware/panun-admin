<ul class="nav nav--tabs nav--tabs__style2">
    <li class="nav-item">
        <a class="nav-link {{ $webPage == 'details' ? 'active' : '' }}"
            href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'details']) }}">{{ translate('details') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $webPage == 'history' ? 'active' : '' }}"
            href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'history']) }}">{{ translate('History') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $webPage == 'followups' ? 'active' : '' }}"
            href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'followups']) }}">{{ translate('Followups') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $webPage == 'comments' ? 'active' : '' }}"
            href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'comments']) }}">
            {{ translate('Comments') }}
            @if(!empty($commentCount) && $commentCount > 0)
                <span class="badge bg-secondary ms-1">{{ $commentCount }}</span>
            @endif
        </a>
    </li>
</ul>
