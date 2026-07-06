@php
    $badgeCount = (int) ($count ?? 0);
    $alwaysShowNumber = ! empty($alwaysShowNumber);
    $showNumber = $badgeCount > 0 && ($alwaysShowNumber || $badgeCount < 9);
    $showDot = $badgeCount >= 9 && ! $alwaysShowNumber;
    $displayNumber = $alwaysShowNumber && $badgeCount > 99 ? '99+' : (string) $badgeCount;
@endphp
<span class="count" id="{{ $id }}" @if(! $showNumber) style="display:none;" @endif>{{ $showNumber ? ($alwaysShowNumber ? $displayNumber : $badgeCount) : '' }}</span>
<span class="header-unread-dot" id="{{ $id }}_dot" @if(! $showDot) style="display:none;" @endif aria-hidden="true"></span>
