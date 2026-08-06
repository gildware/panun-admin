@php
    $todayItems = $todayDone['items'] ?? [];
    $todayTotal = (int) ($todayDone['total'] ?? 0);
    $monthStats = $monthly['stats'] ?? [];
    $monthLabel = $monthly['period_label'] ?? '';
    $qualityStatsDaily = $qualityStatsDaily ?? ($quality_stats_daily ?? []);
    $qualityStatsMonthly = $qualityStatsMonthly ?? ($quality_stats_monthly ?? ($qualityStats ?? ($monthly['quality_stats'] ?? [])));
    $todayLabel = translate('Today');
    $progressTitle = $progressTitle ?? translate('My_Progress');
    $progressSubtitle = $progressSubtitle ?? translate('Progress_dashboard_sub');
    $viewReportUrl = $viewReportUrl ?? route('admin.my-progress');
    $progressSidePanel = $progressSidePanel ?? 'contribution';
    $leaderboard = $leaderboard ?? [];
    $teamRankRows = $teamRankRows ?? [];
    $highlightEmployeeId = $highlightEmployeeId ?? null;
@endphp

<div class="progress-shell js-progress-shell">
    <div class="progress-shell-header">
        <div>
            <h5 class="progress-shell-title">{{ $progressTitle }}</h5>
            <span class="progress-shell-sub">{{ $progressSubtitle }}</span>
        </div>
        <a href="{{ $viewReportUrl }}" class="progress-view-report-btn">
            <span class="material-symbols-outlined">analytics</span>
            {{ translate('View_full_progress_report') }}
        </a>
    </div>

    <div class="progress-shell-body">
        <div class="row g-2 progress-cards-row">
            {{-- Box 1: Quality metrics --}}
            <div class="col-lg-4">
                <div class="progress-card progress-card--compact progress-card--quality h-100">
                    <div class="progress-card-header">
                        <div class="progress-card-header-main">
                            <span class="progress-card-title">{{ translate('Progress_quality_metrics') }}</span>
                        </div>
                        <div class="progress-card-header-action">
                            <div class="progress-tabs" data-tabs="quality">
                                <button type="button" class="progress-tab active" data-tab="quality-daily">{{ translate('Daily') }}</button>
                                <button type="button" class="progress-tab" data-tab="quality-monthly">{{ translate('Monthly') }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="progress-card-body">
                        <div data-panel="quality-daily" class="activity-panel active">
                            <div class="activity-panel-meta">
                                <span>{{ $todayLabel }}</span>
                            </div>
                            <div class="progress-stat-grid progress-stat-grid--compact">
                                @forelse($qualityStatsDaily as $item)
                                    @include('adminmodule::partials._employee-progress-stat-tile', ['item' => $item])
                                @empty
                                    <div class="progress-empty">{{ translate('No_data_available') }}</div>
                                @endforelse
                            </div>
                        </div>
                        <div data-panel="quality-monthly" class="activity-panel">
                            @if($monthLabel !== '')
                                <div class="activity-panel-meta">
                                    <span>{{ $monthLabel }}</span>
                                </div>
                            @endif
                            <div class="progress-stat-grid progress-stat-grid--compact">
                                @forelse($qualityStatsMonthly as $item)
                                    @include('adminmodule::partials._employee-progress-stat-tile', ['item' => $item])
                                @empty
                                    <div class="progress-empty">{{ translate('No_data_available') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Box 2: Work activity detail --}}
            <div class="col-lg-4">
                <div class="progress-card progress-card--compact progress-card--activity h-100">
                    <div class="progress-card-header">
                        <div class="progress-card-header-main">
                            <span class="progress-card-title">{{ translate('Progress_work_activity_detail') }}</span>
                        </div>
                        <div class="progress-card-header-action">
                            <div class="progress-tabs" data-tabs="activity">
                                <button type="button" class="progress-tab active" data-tab="activity-daily">{{ translate('Daily') }}</button>
                                <button type="button" class="progress-tab" data-tab="activity-monthly">{{ translate('Monthly') }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="progress-card-body">
                        <div data-panel="activity-daily" class="activity-panel active">
                            <div class="activity-panel-meta">
                                <span>{{ translate('Todays_Work_Done') }}</span>
                                <span class="progress-summary-badge {{ $todayTotal > 0 ? 'is-active' : '' }}">{{ $todayTotal }}</span>
                            </div>
                            <div class="progress-stat-grid progress-stat-grid--compact">
                                @forelse($todayItems as $item)
                                    @include('adminmodule::partials._employee-progress-stat-tile', ['item' => $item])
                                @empty
                                    <div class="progress-empty">{{ translate('No_data_available') }}</div>
                                @endforelse
                            </div>
                        </div>
                        <div data-panel="activity-monthly" class="activity-panel">
                            @if($monthLabel !== '')
                                <div class="activity-panel-meta">
                                    <span>{{ $monthLabel }}</span>
                                </div>
                            @endif
                            <div class="progress-stat-grid progress-stat-grid--compact">
                                @forelse($monthStats as $item)
                                    @include('adminmodule::partials._employee-progress-stat-tile', ['item' => $item])
                                @empty
                                    <div class="progress-empty">{{ translate('No_data_available') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Box 3: Team rank (admin) or Contribution (employee) --}}
            <div class="col-lg-4">
                @if($progressSidePanel === 'team_rank')
                    <div class="progress-card progress-card--compact progress-card--rank h-100">
                        <div class="progress-card-header">
                            <div class="progress-card-header-main">
                                <span class="progress-card-title">{{ translate('Progress_overall_team_rank') }}</span>
                                @php
                                    $highlightedRankRow = $highlightEmployeeId
                                        ? collect($teamRankRows)->firstWhere('employee_id', (string) $highlightEmployeeId)
                                        : null;
                                @endphp
                                @if($highlightedRankRow)
                                    <span class="progress-card-sub">
                                        #{{ $highlightedRankRow['rank'] }} {{ translate('Progress_out_of') }} {{ count($teamRankRows) }}
                                    </span>
                                @elseif(($leaderboard['total_employees'] ?? 0) > 1 && ($leaderboard['overall_rank'] ?? 0) > 0)
                                    <span class="progress-card-sub">
                                        #{{ $leaderboard['overall_rank'] }} {{ translate('Progress_out_of') }} {{ $leaderboard['total_employees'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="progress-card-body">
                            @if(($leaderboard['total_employees'] ?? 0) > 1 && ($leaderboard['overall_rank'] ?? 0) > 0 && empty($teamRankRows))
                                <div class="rank-summary rank-summary--compact">
                                    <div class="rank-summary-badge">#{{ $leaderboard['overall_rank'] }}</div>
                                    <div>
                                        <div class="rank-summary-label">{{ translate('Progress_overall_team_rank') }}</div>
                                        <div class="rank-summary-sub">{{ translate('Progress_out_of') }} {{ $leaderboard['total_employees'] }} {{ translate('Progress_employees') }}</div>
                                    </div>
                                </div>
                            @endif

                            @if($teamRankRows !== [])
                                <div class="team-rank-list">
                                    @foreach($teamRankRows as $row)
                                        @php
                                            $isHighlighted = $highlightEmployeeId && (string) $highlightEmployeeId === (string) ($row['employee_id'] ?? '');
                                        @endphp
                                        <div class="team-rank-row {{ $isHighlighted ? 'is-highlighted' : '' }}">
                                            <span class="team-rank-num">#{{ $row['rank'] }}</span>
                                            <span class="team-rank-name">{{ $row['label'] }}</span>
                                            <span class="team-rank-score">{{ $row['score'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif(($leaderboard['metrics'] ?? []) !== [])
                                <div class="team-rank-metrics">
                                    @foreach(array_slice($leaderboard['metrics'], 0, 5) as $metric)
                                        <div class="team-rank-metric-row">
                                            <span class="team-rank-metric-label">{{ $metric['label'] }}</span>
                                            <span class="team-rank-metric-rank">#{{ $metric['rank'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="progress-empty">{{ translate('Progress_solo_team') }}</div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="progress-card progress-card--compact progress-card--contribution h-100">
                        <div class="progress-card-header">
                            <div class="progress-card-header-main">
                                <span class="progress-card-title">{{ translate('My_Contribution_vs_All') }}</span>
                                <span class="progress-card-sub">{{ translate('Progress_team_share_sub') }}</span>
                            </div>
                            <div class="progress-card-header-action">
                                <div class="progress-tabs" data-tabs="contribution">
                                    <button type="button" class="progress-tab active" data-tab="contribution-today">{{ translate('Today') }}</button>
                                    <button type="button" class="progress-tab" data-tab="contribution-monthly">{{ translate('Monthly') }}</button>
                                </div>
                            </div>
                        </div>
                        <div class="progress-card-body">
                            <div data-panel="contribution-today" class="contribution-panel active">
                                @forelse($contributionToday as $row)
                                    <div class="contribution-row">
                                        <div class="contribution-row-head">
                                            <span class="contribution-row-label">
                                                <span class="material-symbols-outlined">{{ $row['icon'] ?? 'leaderboard' }}</span>
                                                {{ $row['label'] }}
                                            </span>
                                            <span class="contribution-row-pct">{{ $row['pct'] }}%</span>
                                        </div>
                                        <div class="contribution-row-meta">{{ $row['mine'] }} / {{ $row['all'] }} {{ translate('Progress_team_total') }}</div>
                                        <div class="contribution-bar"><span style="width: {{ min(100, $row['pct']) }}%"></span></div>
                                    </div>
                                @empty
                                    <div class="progress-empty">{{ translate('No_data_available') }}</div>
                                @endforelse
                            </div>
                            <div data-panel="contribution-monthly" class="contribution-panel">
                                @forelse($contributionMonthly as $row)
                                    <div class="contribution-row">
                                        <div class="contribution-row-head">
                                            <span class="contribution-row-label">
                                                <span class="material-symbols-outlined">{{ $row['icon'] ?? 'leaderboard' }}</span>
                                                {{ $row['label'] }}
                                            </span>
                                            <span class="contribution-row-pct">{{ $row['pct'] }}%</span>
                                        </div>
                                        <div class="contribution-row-meta">{{ $row['mine'] }} / {{ $row['all'] }} {{ translate('Progress_team_total') }}</div>
                                        <div class="contribution-bar"><span style="width: {{ min(100, $row['pct']) }}%"></span></div>
                                    </div>
                                @empty
                                    <div class="progress-empty">{{ translate('No_data_available') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
