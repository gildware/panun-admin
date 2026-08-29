@php
    $scopeId = $scopeId ?? '__all__';
    $scope = $scope ?? [];
    $dashboardEmployees = $dashboardEmployees ?? [];
    $hidden = $hidden ?? ($scopeId !== '__all__');
@endphp
<div class="js-progress-scope-panel {{ $hidden ? 'd-none' : '' }}"
     data-scope-id="{{ $scopeId }}">
    @include('adminmodule::partials._employee-progress', [
        'todayDone' => $scope['today_done'] ?? [],
        'monthly' => $scope['monthly'] ?? [],
        'qualityStatsDaily' => $scope['quality_stats_daily'] ?? [],
        'qualityStatsMonthly' => $scope['quality_stats_monthly'] ?? ($scope['quality_stats'] ?? []),
        'leaderboard' => $scope['leaderboard'] ?? [],
        'teamRankRowsDaily' => $scope['team_rank_rows_daily'] ?? ($scope['team_rank_rows'] ?? []),
        'teamRankRowsMonthly' => $scope['team_rank_rows_monthly'] ?? ($scope['team_rank_rows'] ?? []),
        'rankMarksChart' => $scope['rank_marks_chart'] ?? [],
        'progressScopeId' => $scopeId,
        'highlightEmployeeId' => $scope['highlight_employee_id'] ?? ($scopeId !== '__all__' ? $scopeId : null),
        'progressTitle' => $scope['title'] ?? translate('Team_Progress'),
        'progressSubtitle' => $scope['subtitle'] ?? translate('Team_progress_sub'),
        'viewReportUrl' => $scope['view_report_url'] ?? route('admin.my-progress', ['tab' => 'monthly']),
        'progressLayout' => 'admin',
        'chartEmployees' => $dashboardEmployees,
    ])
</div>
