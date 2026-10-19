@php
    $badgeCount = (int) ($count ?? 0);
    $badgeId = $badgeId ?? null;
@endphp
<span class="top-nav-label-wrap{{ $badgeCount > 0 ? ' top-nav-label-wrap--badged' : '' }}">
    <span class="top-nav-label">{{ $label }}</span>
    @if($badgeId || $badgeCount > 0)
        <span class="top-nav-count-badge" @if($badgeId) id="{{ $badgeId }}" @endif @if($badgeCount < 1) style="display:none" @endif>{{ $badgeCount > 0 ? ($badgeCount > 99 ? '99+' : $badgeCount) : '' }}</span>
    @endif
</span>
