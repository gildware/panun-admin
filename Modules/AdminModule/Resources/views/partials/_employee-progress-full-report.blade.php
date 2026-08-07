@php
    $scorecard = $fullReport['scorecard'] ?? ['good' => [], 'bad' => [], 'neutral' => []];
    $contribution = $fullReport['contribution'] ?? [];
    $leaderboard = $fullReport['leaderboard'] ?? [];
    $teamRankRows = $fullReport['team_rank_rows'] ?? [];
    $viewingTeam = ! empty($fullReport['viewing_team']);
    $qualityStats = $fullReport['quality_stats'] ?? [];
    $improvements = $fullReport['improvements'] ?? [];
    $allScoreItems = array_merge($scorecard['good'] ?? [], $scorecard['neutral'] ?? [], $scorecard['bad'] ?? []);
@endphp

<div class="full-report-sections">
    @include('adminmodule::partials._employee-progress-quality-metrics', [
        'qualityItems' => $qualityStats,
    ])

    @if($allScoreItems !== [])
        <div class="section-label">{{ translate('Progress_performance_summary') }}</div>
        <div class="score-grid mb-3">
            @foreach($allScoreItems as $item)
                <div class="score-tile">
                    <div class="sv">{{ $item['value'] ?? '—' }}</div>
                    <div class="sl">{{ $item['label'] ?? '' }}</div>
                    @if(! empty($item['detail']))
                        <div class="sw">{{ $item['detail'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid-2">
        <div class="chart-card">
            <div class="chart-card-head">
                <h3>
                    @include('adminmodule::partials._material-icon', ['name' => 'groups'])
                    {{ $viewingTeam ? translate('All_Employees') : translate('My_Contribution_vs_All') }}
                </h3>
            </div>
            <div class="chart-card-body">
                @forelse($contribution as $row)
                    <div class="rank-item">
                        <div class="avatar">
                            @include('adminmodule::partials._material-icon', ['name' => $row['icon'] ?? 'person'])
                        </div>
                        <div class="rank-meta">
                            <div class="rank-name">{{ $row['label'] }}</div>
                            <div class="rank-sub">{{ $row['mine'] }} / {{ $row['all'] }} {{ translate('Progress_team_total') }}</div>
                            <div class="rank-bar"><i style="width: {{ min(100, $row['pct']) }}%"></i></div>
                        </div>
                        <div class="rank-val">{{ $row['pct'] }}%</div>
                    </div>
                @empty
                    <div style="padding:16px;text-align:center;color:#64748b;font-size:12px">{{ translate('No_data_available') }}</div>
                @endforelse
            </div>
        </div>

        <div class="rank-card">
            <div class="rank-head">{{ translate('Progress_team_ranking') }}</div>
            @if($viewingTeam && $teamRankRows !== [])
                @foreach($teamRankRows as $row)
                    <div class="rank-item">
                        <div class="avatar">#{{ $row['rank'] }}</div>
                        <div class="rank-meta">
                            <div class="rank-name">{{ $row['label'] }}</div>
                            <div class="rank-sub">{{ translate('Progress_team_rank') }}</div>
                        </div>
                        <div class="rank-val">{{ $row['score'] }}</div>
                    </div>
                @endforeach
            @elseif(($leaderboard['total_employees'] ?? 1) > 1)
                <div class="rank-item">
                    <div class="avatar">#{{ $leaderboard['overall_rank'] ?? '—' }}</div>
                    <div class="rank-meta">
                        <div class="rank-name">{{ translate('Progress_overall_team_rank') }}</div>
                        <div class="rank-sub">{{ translate('Progress_out_of') }} {{ $leaderboard['total_employees'] ?? 0 }} {{ translate('Progress_employees') }}</div>
                    </div>
                    <div class="rank-val">#{{ $leaderboard['overall_rank'] ?? '—' }}</div>
                </div>
                <div class="data-table-wrap" style="max-height:280px;border:none;border-radius:0">
                    <table class="data-table" style="min-width:0">
                        <thead>
                            <tr>
                                <th>{{ translate('Metric') }}</th>
                                <th>{{ translate('Progress_your_rank') }}</th>
                                <th>{{ translate('Progress_yours') }}</th>
                                <th>{{ translate('Progress_team_avg') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leaderboard['metrics'] ?? [] as $metric)
                                <tr>
                                    <td>{{ $metric['label'] }}</td>
                                    <td>#{{ $metric['rank'] }} / {{ $metric['total_employees'] }}</td>
                                    <td>{{ $metric['value'] }}</td>
                                    <td>{{ $metric['team_avg'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="padding:16px;text-align:center;color:#64748b;font-size:12px">{{ translate('Progress_solo_team') }}</div>
            @endif
        </div>
    </div>
</div>
