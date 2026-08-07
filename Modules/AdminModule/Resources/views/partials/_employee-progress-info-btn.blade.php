@php
    $helpKey = $helpKey ?? null;
    $registry = $metricHelpRegistry ?? [];
    $size = $size ?? 'sm';
@endphp
@if($helpKey && isset($registry[$helpKey]))
    <button type="button"
            class="progress-metric-info-btn progress-metric-info-btn--{{ $size }}"
            data-help-key="{{ $helpKey }}"
            aria-expanded="false"
            aria-label="{{ translate('Progress_metric_info') ?? 'Metric information' }}">
        @include('adminmodule::partials._material-icon', ['name' => 'info', 'class' => 'mso'])
    </button>
@endif
