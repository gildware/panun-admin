@php
    $badgeCount = (int) ($count ?? 0);
    $showNumber = $badgeCount > 0 && $badgeCount < 9;
    $showDot = $badgeCount >= 9;
@endphp
<span class="count" id="{{ $id }}" @if(! $showNumber) style="display:none;" @endif>{{ $showNumber ? $badgeCount : '' }}</span>
<span class="header-unread-dot" id="{{ $id }}_dot" @if(! $showDot) style="display:none;" @endif aria-hidden="true"></span>
