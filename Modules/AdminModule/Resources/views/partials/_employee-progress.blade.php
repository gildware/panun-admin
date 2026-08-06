@php
    $todayItems = $todayDone['items'] ?? [];
    $todayTotal = (int) ($todayDone['total'] ?? 0);
    $monthStats = $monthly['stats'] ?? [];
    $monthLabel = $monthly['period_label'] ?? '';
    $progressTitle = $progressTitle ?? translate('My_Progress');
    $progressSubtitle = $progressSubtitle ?? translate('Progress_dashboard_sub');
    $monthTitle = $monthTitle ?? translate('My_Month_Report');
    $contributionTitle = $contributionTitle ?? translate('My_Contribution_vs_All');
    $contributionSubtitle = $contributionSubtitle ?? translate('Progress_team_share_sub');
    $viewReportUrl = $viewReportUrl ?? route('admin.my-progress');
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
            {{-- Today --}}
            <div class="col-lg-4">
                <div class="progress-card progress-card--compact progress-card--today h-100">
                    <div class="progress-card-header">
                        <div class="progress-card-header-main">
                            <span class="progress-card-title">{{ translate('Todays_Work_Done') }}</span>
                            <span class="progress-card-sub">{{ translate('Progress_actions_today_sub') }}</span>
                        </div>
                        <div class="progress-card-header-action">
                            <div class="progress-summary-badge {{ $todayTotal > 0 ? 'is-active' : '' }}">
                                <span class="val">{{ $todayTotal }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="progress-card-body">
                        <div class="progress-stat-grid progress-stat-grid--compact">
                            @foreach($todayItems as $item)
                                @include('adminmodule::partials._employee-progress-stat-tile', ['item' => $item])
                            @endforeach
                            @if(count($todayItems) % 2 !== 0)
                                <div class="progress-stat-tile progress-stat-tile--compact progress-stat-tile--spacer" aria-hidden="true"></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Month --}}
            <div class="col-lg-4">
                <div class="progress-card progress-card--compact progress-card--month h-100">
                    <div class="progress-card-header">
                        <div class="progress-card-header-main">
                            <span class="progress-card-title">{{ $monthTitle }}</span>
                            @if($monthLabel !== '')
                                <span class="progress-card-sub">{{ $monthLabel }}</span>
                            @endif
                        </div>
                        <div class="progress-card-header-action progress-card-header-action--spacer" aria-hidden="true"></div>
                    </div>
                    <div class="progress-card-body">
                        <div class="progress-stat-grid progress-stat-grid--compact">
                            @foreach($monthStats as $item)
                                @include('adminmodule::partials._employee-progress-stat-tile', ['item' => $item])
                            @endforeach
                            @if(count($monthStats) % 2 !== 0)
                                <div class="progress-stat-tile progress-stat-tile--compact progress-stat-tile--spacer" aria-hidden="true"></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contribution --}}
            <div class="col-lg-4">
                <div class="progress-card progress-card--compact progress-card--contribution h-100">
                    <div class="progress-card-header">
                        <div class="progress-card-header-main">
                            <span class="progress-card-title">{{ $contributionTitle }}</span>
                            <span class="progress-card-sub">{{ $contributionSubtitle }}</span>
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
            </div>
        </div>
    </div>
</div>
