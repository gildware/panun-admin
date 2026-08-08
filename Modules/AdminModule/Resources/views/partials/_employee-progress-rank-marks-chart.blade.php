@php
    $rankMarksChart = $rankMarksChart ?? [];
    $progressScopeId = $progressScopeId ?? 'default';
    $chartId = 'chart-rank-marks-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $progressScopeId);
    $chartDataId = $chartId.'-data';
    $months = $rankMarksChart['months'] ?? [];
    $currentMonth = (string) ($rankMarksChart['month'] ?? now()->format('Y-m'));
    $chartEmployees = $chartEmployees ?? [];
    $isAdminChart = ($progressLayout ?? '') === 'admin' && $chartEmployees !== [];
    $employeeScope = $isAdminChart
        ? '__all__'
        : (string) ($highlightEmployeeId ?? ($progressScopeId ?? ''));
    $chartUrl = route('admin.dashboard.rank-marks-chart');
    $chartPayload = [
        'categories' => $rankMarksChart['categories'] ?? [],
        'series' => $rankMarksChart['series'] ?? [],
        'month' => $currentMonth,
        'period_label' => $rankMarksChart['period_label'] ?? '',
    ];
@endphp
<div class="rank-marks-trend rank-marks-trend--bottom">
    <div class="rank-marks-trend-head">
        <span class="rank-marks-trend-title">{{ translate('Progress_rank_marks_trend') ?? 'Daily marks trend' }}</span>
        @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => 'rank_marks_trend', 'size' => 'xs'])
    </div>
    @if($isAdminChart || $months !== [])
        <div class="rank-marks-trend-filters">
            @if($isAdminChart)
                <select class="form-select form-select-sm js-rank-marks-employee" aria-label="{{ translate('Select_employee') ?? 'Employee' }}">
                    <option value="__all__" @selected($employeeScope === '' || $employeeScope === '__all__')>{{ translate('All') }}</option>
                    @foreach($chartEmployees as $employee)
                        <option value="{{ $employee['id'] ?? '' }}" @selected(($employee['id'] ?? '') === $employeeScope)>
                            {{ $employee['name'] ?? '' }}
                        </option>
                    @endforeach
                </select>
            @endif
            @if($months !== [])
                <select class="form-select form-select-sm js-rank-marks-month" aria-label="{{ translate('Progress_rank_marks_month') ?? 'Month' }}">
                    @foreach($months as $monthOption)
                        <option value="{{ $monthOption['value'] ?? '' }}" @selected(($monthOption['value'] ?? '') === $currentMonth)>
                            {{ $monthOption['label'] ?? '' }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
    @endif
    <script type="application/json" id="{{ $chartDataId }}" class="js-rank-marks-chart-data">@json($chartPayload)</script>
    <div
        id="{{ $chartId }}"
        class="js-rank-marks-chart rank-marks-trend-chart {{ ($progressLayout ?? '') === 'employee' ? 'rank-marks-trend-chart--compact' : '' }}"
        data-chart-id="{{ $chartDataId }}"
        data-chart-url="{{ $chartUrl }}"
        data-employee-scope="{{ $employeeScope === '' ? '__all__' : $employeeScope }}"
        aria-label="{{ translate('Progress_rank_marks_trend') ?? 'Daily marks trend' }}"
    ></div>
</div>
