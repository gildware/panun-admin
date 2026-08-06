@php
    $qualityItems = $qualityItems ?? ($fullReport['quality_stats'] ?? []);
@endphp

@if($qualityItems !== [])
    <div class="report-section">
        <h6 class="report-section-title">
            @include('adminmodule::partials._material-icon', ['name' => 'analytics', 'class' => ''])
            {{ translate('Progress_quality_metrics') }}
        </h6>
        <div class="progress-stat-grid">
            @foreach($qualityItems as $stat)
                @include('adminmodule::partials._employee-progress-stat-tile', ['item' => $stat])
            @endforeach
        </div>
    </div>
@endif
