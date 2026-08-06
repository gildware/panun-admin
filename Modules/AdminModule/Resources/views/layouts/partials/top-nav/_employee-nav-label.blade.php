@php
    $badgeCount = (int) ($count ?? 0);
@endphp
<span class="top-nav-label-wrap{{ $badgeCount > 0 ? ' top-nav-label-wrap--badged' : '' }}">
    <span class="top-nav-label">{{ $label }}</span>
    @if($badgeCount > 0)
        <span class="top-nav-count-badge">{{ $badgeCount > 99 ? '99+' : $badgeCount }}</span>
    @endif
</span>
