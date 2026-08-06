@php
    $tileValue = $item['value'] ?? $item['count'] ?? '0';
    $isZero = array_key_exists('is_zero', $item)
        ? ! empty($item['is_zero'])
        : (is_numeric($tileValue) && (float) $tileValue <= 0);
@endphp
<div class="progress-stat-tile progress-stat-tile--compact tone-{{ $item['tone'] ?? 'neutral' }} {{ $isZero ? 'is-zero' : '' }}">
    @include('adminmodule::partials._material-icon', ['name' => $item['icon'] ?? 'info', 'class' => 'progress-stat-icon'])
    <div class="progress-stat-tile-content">
        <span class="progress-stat-val">{{ $tileValue }}</span>
        <span class="progress-stat-label">{{ $item['label'] ?? '' }}</span>
        @if(! empty($item['sub']))
            <span class="progress-stat-sub">{{ $item['sub'] }}</span>
        @endif
    </div>
</div>
