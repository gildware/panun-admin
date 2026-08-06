@php
    $scorecard = $fullReport['scorecard'] ?? ['good' => [], 'bad' => [], 'neutral' => []];
    $contribution = $fullReport['contribution'] ?? [];
    $leaderboard = $fullReport['leaderboard'] ?? [];
    $missedFollowups = $fullReport['missed_followups'] ?? ['leads' => ['total' => 0, 'items' => []], 'bookings' => ['total' => 0, 'items' => []], 'total' => 0];
    $pendingFollowups = $fullReport['pending_followups'] ?? ['leads' => ['total' => 0, 'items' => []], 'bookings' => ['total' => 0, 'items' => []], 'total' => 0];
    $pipeline = $fullReport['pipeline'] ?? ['leads' => ['total' => 0, 'items' => []], 'bookings' => ['total' => 0, 'items' => []]];
    $improvements = $fullReport['improvements'] ?? [];
@endphp

<div class="full-report-sections">
    {{-- Scorecard: Good / Bad / Neutral --}}
    <div class="report-section">
        <h6 class="report-section-title">
            @include('adminmodule::partials._material-icon', ['name' => 'insights', 'class' => ''])
            {{ translate('Progress_performance_summary') }}
        </h6>
        <div class="scorecard-grid">
            @if(! empty($scorecard['good']))
                <div class="scorecard-col scorecard-col--good">
                    <div class="scorecard-col-head">
                        @include('adminmodule::partials._material-icon', ['name' => 'thumb_up'])
                        <span class="scorecard-col-head-label">{{ translate('Progress_doing_well') }}</span>
                    </div>
                    @foreach($scorecard['good'] as $item)
                        <div class="scorecard-item">
                            <div class="scorecard-item-head">
                                @include('adminmodule::partials._material-icon', ['name' => $item['icon'] ?? 'check_circle'])
                                <span class="scorecard-item-label">{{ $item['label'] }}</span>
                                <span class="scorecard-item-value">{{ $item['value'] }}</span>
                            </div>
                            @if(! empty($item['detail']))
                                <div class="scorecard-item-detail">{{ $item['detail'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
            @if(! empty($scorecard['bad']))
                <div class="scorecard-col scorecard-col--bad">
                    <div class="scorecard-col-head">
                        @include('adminmodule::partials._material-icon', ['name' => 'warning'])
                        <span class="scorecard-col-head-label">{{ translate('Progress_needs_attention') }}</span>
                    </div>
                    @foreach($scorecard['bad'] as $item)
                        <div class="scorecard-item">
                            <div class="scorecard-item-head">
                                @include('adminmodule::partials._material-icon', ['name' => $item['icon'] ?? 'error'])
                                <span class="scorecard-item-label">{{ $item['label'] }}</span>
                                <span class="scorecard-item-value">{{ $item['value'] }}</span>
                            </div>
                            @if(! empty($item['detail']))
                                <div class="scorecard-item-detail">{{ $item['detail'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
            @if(! empty($scorecard['neutral']))
                <div class="scorecard-col scorecard-col--neutral">
                    <div class="scorecard-col-head">
                        @include('adminmodule::partials._material-icon', ['name' => 'info'])
                        <span class="scorecard-col-head-label">{{ translate('Progress_on_track') }}</span>
                    </div>
                    @foreach($scorecard['neutral'] as $item)
                        <div class="scorecard-item">
                            <div class="scorecard-item-head">
                                @include('adminmodule::partials._material-icon', ['name' => $item['icon'] ?? 'info'])
                                <span class="scorecard-item-label">{{ $item['label'] }}</span>
                                <span class="scorecard-item-value">{{ $item['value'] }}</span>
                            </div>
                            @if(! empty($item['detail']))
                                <div class="scorecard-item-detail">{{ $item['detail'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Team comparison + Ranking --}}
    <div class="report-section">
        <div class="row g-3">
            <div class="col-lg-6">
                <h6 class="report-section-title">
                    @include('adminmodule::partials._material-icon', ['name' => 'groups'])
                    {{ translate('My_Contribution_vs_All') }}
                </h6>
                <div class="contribution-panel-full">
                    @forelse($contribution as $row)
                        <div class="contribution-row-full">
                            <div class="contribution-row-head">
                                <span class="contribution-row-label">
                                    @include('adminmodule::partials._material-icon', ['name' => $row['icon'] ?? 'leaderboard'])
                                    {{ $row['label'] }}
                                </span>
                                <span class="contribution-row-pct">{{ $row['pct'] }}%</span>
                            </div>
                            <div class="contribution-row-meta">{{ $row['mine'] }} / {{ $row['all'] }} {{ translate('Progress_team_total') }}</div>
                            <div class="contribution-bar"><span style="width: {{ min(100, $row['pct']) }}%"></span></div>
                        </div>
                    @empty
                        <div class="report-empty">{{ translate('No_data_available') }}</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-6">
                <h6 class="report-section-title">
                    @include('adminmodule::partials._material-icon', ['name' => 'leaderboard'])
                    {{ translate('Progress_team_ranking') }}
                </h6>
                @if(($leaderboard['total_employees'] ?? 1) > 1)
                    <div class="rank-summary">
                        <div class="rank-summary-badge">#{{ $leaderboard['overall_rank'] ?? '—' }}</div>
                        <div>
                            <div class="rank-summary-label">{{ translate('Progress_overall_team_rank') }}</div>
                            <div class="rank-summary-sub">{{ translate('Progress_out_of') }} {{ $leaderboard['total_employees'] ?? 0 }} {{ translate('Progress_employees') }}</div>
                        </div>
                    </div>
                    <div class="report-table-scroll report-table-scroll--compact">
                        <table class="report-detail-table">
                            <thead>
                                <tr>
                                    <th>{{ translate('Metric') }}</th>
                                    <th>{{ translate('Progress_your_rank') }}</th>
                                    <th>{{ translate('Progress_yours') }}</th>
                                    <th>{{ translate('Progress_team_avg') }}</th>
                                    <th>{{ translate('Progress_vs_avg') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leaderboard['metrics'] ?? [] as $metric)
                                    @php $vsAvg = $metric['vs_avg'] ?? 0; @endphp
                                    <tr>
                                        <td>{{ $metric['label'] }}</td>
                                        <td>#{{ $metric['rank'] }} / {{ $metric['total_employees'] }}</td>
                                        <td>{{ $metric['value'] }}</td>
                                        <td>{{ $metric['team_avg'] }}</td>
                                        <td class="{{ $vsAvg > 0 ? 'text-success' : ($vsAvg < 0 ? 'text-danger' : '') }}">
                                            {{ $vsAvg > 0 ? '+' : '' }}{{ $vsAvg }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="report-empty">{{ translate('Progress_solo_team') }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Room for improvement --}}
    @if($improvements !== [])
        <div class="report-section">
            <h6 class="report-section-title">
                @include('adminmodule::partials._material-icon', ['name' => 'lightbulb'])
                {{ translate('Progress_room_for_improvement') }}
            </h6>
            <div class="improvement-list">
                @foreach($improvements as $item)
                    <div class="improvement-item priority-{{ $item['priority'] ?? 'low' }}">
                        @include('adminmodule::partials._material-icon', ['name' => $item['icon'] ?? 'lightbulb', 'class' => 'improvement-icon'])
                        <div>
                            <div class="improvement-title">{{ $item['title'] }}</div>
                            <div class="improvement-detail">{{ $item['detail'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Missing follow-ups --}}
    <div class="report-section">
        <h6 class="report-section-title">
            @include('adminmodule::partials._material-icon', ['name' => 'event_busy'])
            {{ translate('Progress_missed_followups_title') }}
            @if(($missedFollowups['total'] ?? 0) > 0)
                <span class="report-badge report-badge--danger">{{ $missedFollowups['total'] }}</span>
            @endif
        </h6>
        <div class="row g-3">
            <div class="col-lg-6">
                @include('adminmodule::partials._employee-progress-report-table', [
                    'title' => translate('Progress_missed_lead_followups'),
                    'count' => $missedFollowups['leads']['total'] ?? 0,
                    'items' => $missedFollowups['leads']['items'] ?? [],
                    'emptyLabel' => translate('Progress_no_missed_leads'),
                    'viewAllUrl' => route('admin.lead.todays_followups', ['handled_by' => $user->id]),
                ])
            </div>
            <div class="col-lg-6">
                @include('adminmodule::partials._employee-progress-report-table', [
                    'title' => translate('Progress_missed_booking_followups'),
                    'count' => $missedFollowups['bookings']['total'] ?? 0,
                    'items' => $missedFollowups['bookings']['items'] ?? [],
                    'emptyLabel' => translate('Progress_no_missed_bookings'),
                    'viewAllUrl' => route('admin.booking.todays_followups', ['assignee_id' => $user->id]),
                ])
            </div>
        </div>
    </div>

    {{-- Due today follow-ups --}}
    <div class="report-section">
        <h6 class="report-section-title">
            @include('adminmodule::partials._material-icon', ['name' => 'today'])
            {{ translate('Progress_due_today_followups') }}
            @if(($pendingFollowups['total'] ?? 0) > 0)
                <span class="report-badge report-badge--warn">{{ $pendingFollowups['total'] }}</span>
            @endif
        </h6>
        <div class="row g-3">
            <div class="col-lg-6">
                @include('adminmodule::partials._employee-progress-report-table', [
                    'title' => translate('Progress_lead_followups_due_today'),
                    'count' => $pendingFollowups['leads']['total'] ?? 0,
                    'items' => $pendingFollowups['leads']['items'] ?? [],
                    'emptyLabel' => translate('Progress_no_leads_due_today'),
                    'viewAllUrl' => route('admin.lead.todays_followups', ['handled_by' => $user->id]),
                ])
            </div>
            <div class="col-lg-6">
                @include('adminmodule::partials._employee-progress-report-table', [
                    'title' => translate('Progress_booking_followups_due_today'),
                    'count' => $pendingFollowups['bookings']['total'] ?? 0,
                    'items' => $pendingFollowups['bookings']['items'] ?? [],
                    'emptyLabel' => translate('Progress_no_bookings_due_today'),
                    'viewAllUrl' => route('admin.booking.todays_followups', ['assignee_id' => $user->id]),
                ])
            </div>
        </div>
    </div>

    {{-- Current pipeline: Leads & Bookings --}}
    <div class="report-section">
        <h6 class="report-section-title">
            @include('adminmodule::partials._material-icon', ['name' => 'account_tree'])
            {{ translate('Progress_current_pipeline') }}
        </h6>
        <div class="row g-3">
            <div class="col-lg-6">
                @include('adminmodule::partials._employee-progress-report-table', [
                    'title' => translate('Progress_open_leads'),
                    'count' => $pipeline['leads']['total'] ?? 0,
                    'items' => $pipeline['leads']['items'] ?? [],
                    'emptyLabel' => translate('Progress_no_open_leads'),
                    'viewAllUrl' => route('admin.lead.index', ['handled_by' => [$user->id]]),
                ])
            </div>
            <div class="col-lg-6">
                @include('adminmodule::partials._employee-progress-report-table', [
                    'title' => translate('Progress_active_bookings'),
                    'count' => $pipeline['bookings']['total'] ?? 0,
                    'items' => $pipeline['bookings']['items'] ?? [],
                    'emptyLabel' => translate('Progress_no_active_bookings'),
                    'viewAllUrl' => route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all', 'assignee_ids' => [$user->id]]),
                ])
            </div>
        </div>
    </div>
</div>
