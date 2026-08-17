@php
    $helpKey = $helpKey ?? null;
    $registry = $metricHelpRegistry ?? [];
    if ($registry === []) {
        $registry = \Modules\AdminModule\Services\EmployeeProgressMetricHelp::registry();
    }
    $size = $size ?? 'sm';
    $entry = ($helpKey && isset($registry[$helpKey])) ? $registry[$helpKey] : null;
@endphp
@if($entry)
    <button type="button"
            class="progress-metric-info-btn progress-metric-info-btn--{{ $size }}"
            data-help-key="{{ $helpKey }}"
            data-help-title="{{ $entry['title'] ?? '' }}"
            data-help-summary="{{ $entry['summary'] ?? '' }}"
            data-help-example="{{ $entry['example'] ?? '' }}"
            aria-expanded="false"
            aria-label="{{ translate('Progress_metric_info') ?? 'Metric information' }}">
        @include('adminmodule::partials._material-icon', ['name' => 'info', 'class' => 'mso'])
    </button>
@endif
