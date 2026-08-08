@php
    $rankMarksChart = $rankMarksChart ?? [];
    $progressScopeId = $progressScopeId ?? 'default';
    $chartId = 'chart-rank-marks-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $progressScopeId);
    $hasChart = ($rankMarksChart['categories'] ?? []) !== [] && ($rankMarksChart['series'] ?? []) !== [];
@endphp
@if($hasChart)
    <div class="rank-marks-trend">
        <div class="rank-marks-trend-head">
            <span class="rank-marks-trend-title">{{ translate('Progress_rank_marks_trend') ?? 'Daily marks trend' }}</span>
            @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => 'rank_marks_trend', 'size' => 'xs'])
        </div>
        <div
            id="{{ $chartId }}"
            class="js-rank-marks-chart rank-marks-trend-chart {{ ($progressLayout ?? '') === 'employee' ? 'rank-marks-trend-chart--compact' : '' }}"
            data-chart='@json($rankMarksChart)'
            aria-label="{{ translate('Progress_rank_marks_trend') ?? 'Daily marks trend' }}"
        ></div>
    </div>
@endif
