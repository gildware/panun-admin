@php
    $iconName = $name ?? $icon ?? 'info';
    $iconClass = trim('material-symbols-outlined ' . ($class ?? ''));
@endphp
<span class="{{ $iconClass }}" aria-hidden="true">{{ $iconName }}</span>
