@extends('adminmodule::layouts.new-master')

@section('title', translate('My_Progress_Report'))

@push('css_or_js')
<style>
    .emp-progress-report {
        --wq-brand: #43466e;
        --wq-brand-soft: #eef0f6;
        --wq-border: #e5e7eb;
        --wq-surface: #f8fafc;
        --wq-text: #1f2937;
        --wq-muted: #64748b;
    }
    .emp-progress-report .report-topbar {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;
        margin-bottom: 14px;
    }
    .emp-progress-report .report-topbar-title {
        font-size: 18px; font-weight: 700; color: var(--wq-text); margin: 0;
    }
    .emp-progress-report .report-topbar-sub {
        font-size: 12px; color: var(--wq-muted); margin-top: 2px;
    }
    .emp-progress-report .report-shell {
        background: #fff; border: 1px solid var(--wq-border); border-radius: 10px; overflow: hidden;
    }
    .emp-progress-report .report-shell-header {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px;
        padding: 12px 14px; background: var(--wq-surface); border-bottom: 1px solid #eef0f3;
    }
    .emp-progress-report .report-tabs { display: flex; gap: 6px; }
    .emp-progress-report .report-tab {
        border: 1px solid var(--wq-border); background: #fff; color: var(--wq-muted);
        padding: 6px 12px; border-radius: 999px; font-size: 11px; font-weight: 600;
        text-decoration: none;
    }
    .emp-progress-report .report-tab.active,
    .emp-progress-report .report-tab:hover {
        background: var(--wq-brand); color: #fff; border-color: var(--wq-brand);
    }
    .emp-progress-report .report-filter {
        display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
    }
    .emp-progress-report .report-filter label {
        font-size: 11px; font-weight: 600; color: var(--wq-muted); margin: 0;
    }
    .emp-progress-report .report-filter input[type="date"] {
        font-size: 12px; padding: 4px 8px; border: 1px solid var(--wq-border); border-radius: 6px;
    }
    .emp-progress-report .report-filter .btn-brand {
        background: var(--wq-brand); border-color: var(--wq-brand); color: #fff;
        font-size: 11px; font-weight: 600; padding: 5px 12px; border-radius: 6px;
    }
    .emp-progress-report .report-body { padding: 14px; }
    .emp-progress-report .month-stat-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px;
        margin-bottom: 14px;
    }
    .emp-progress-report .month-stat-tile {
        border: 1px solid var(--wq-border); border-radius: 8px; padding: 10px;
        background: #fff;
    }
    .emp-progress-report .month-stat-tile .val {
        font-size: 18px; font-weight: 700; color: var(--wq-brand); line-height: 1.2;
    }
    .emp-progress-report .month-stat-tile .lbl {
        font-size: 10px; color: var(--wq-muted); margin-top: 4px; line-height: 1.3;
    }
    .emp-progress-report .report-table-scroll {
        overflow: auto; max-height: 65vh; border: 1px solid var(--wq-border); border-radius: 8px;
    }
    .emp-progress-report .report-daily-table {
        width: 100%; min-width: 960px; border-collapse: collapse; font-size: 12px;
    }
    .emp-progress-report .report-daily-table thead th {
        position: sticky; top: 0; z-index: 1; background: var(--wq-surface);
        padding: 8px 6px; border-bottom: 1px solid var(--wq-border);
        font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--wq-muted);
        white-space: nowrap;
    }
    .emp-progress-report .report-daily-table tbody td {
        padding: 7px 6px; border-bottom: 1px solid #f1f5f9; text-align: center;
    }
    .emp-progress-report .report-daily-table tbody td:first-child { text-align: left; }
    .emp-progress-report .report-daily-table tbody tr:hover { background: #fafafa; }
    .emp-progress-report .report-daily-table .metric-zero { color: #cbd5e1; }
    .emp-progress-report .report-daily-table a.day-link {
        color: var(--wq-brand); font-weight: 600; text-decoration: none;
    }
    .emp-progress-report .report-daily-table a.day-link:hover { text-decoration: underline; }
    .emp-progress-report #day-detail-metrics .border {
        border-color: var(--wq-border) !important; border-radius: 8px !important;
    }
    .emp-progress-report #day-detail-metrics .fw-semibold { color: var(--wq-brand); }
    .emp-progress-report .material-symbols-outlined {
        font-family: 'Material Symbols Outlined';
        font-weight: normal;
        font-style: normal;
        font-size: 1.125rem;
        line-height: 1;
        letter-spacing: normal;
        text-transform: none;
        display: inline-block;
        white-space: nowrap;
        word-wrap: normal;
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        -webkit-font-feature-settings: 'liga';
        font-feature-settings: 'liga';
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        vertical-align: middle;
    }
    .emp-progress-report .full-report-sections { margin-top: 0; }
    .emp-progress-report .report-section { margin-bottom: 22px; }
    .emp-progress-report .report-section-title {
        font-size: 13px; font-weight: 700; color: var(--wq-text);
        margin-bottom: 10px; display: flex; align-items: center; gap: 8px;
    }
    .emp-progress-report .report-badge {
        font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 999px;
        background: var(--wq-brand-soft); color: var(--wq-brand);
    }
    .emp-progress-report .report-badge--danger { background: #fef2f2; color: #dc2626; }
    .emp-progress-report .report-badge--warn { background: #fffbeb; color: #d97706; }
    .emp-progress-report .report-badge--brand { background: var(--wq-brand-soft); color: var(--wq-brand); }
    .emp-progress-report .scorecard-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px;
    }
    .emp-progress-report .scorecard-col {
        border: 1px solid var(--wq-border); border-radius: 8px; padding: 10px; background: #fff;
    }
    .emp-progress-report .scorecard-col--good { border-left: 3px solid #16a34a; }
    .emp-progress-report .scorecard-col--bad { border-left: 3px solid #dc2626; }
    .emp-progress-report .scorecard-col--neutral { border-left: 3px solid #64748b; }
    .emp-progress-report .scorecard-col-head {
        display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700;
        color: var(--wq-muted); margin-bottom: 8px;
    }
    .emp-progress-report .scorecard-col-head-label { text-transform: uppercase; letter-spacing: .03em; }
    .emp-progress-report .scorecard-col-head .material-symbols-outlined { font-size: 16px; flex-shrink: 0; }
    .emp-progress-report .scorecard-item { margin-bottom: 8px; }
    .emp-progress-report .scorecard-item:last-child { margin-bottom: 0; }
    .emp-progress-report .scorecard-item-head {
        display: flex; align-items: center; gap: 6px; font-size: 12px;
    }
    .emp-progress-report .scorecard-item-head .material-symbols-outlined { font-size: 16px; color: var(--wq-brand); flex-shrink: 0; }
    .emp-progress-report .scorecard-item-label { flex: 1; font-weight: 600; color: var(--wq-text); }
    .emp-progress-report .scorecard-item-value { font-weight: 700; color: var(--wq-brand); }
    .emp-progress-report .scorecard-item-detail { font-size: 11px; color: var(--wq-muted); margin-top: 2px; padding-left: 22px; }
    .emp-progress-report .contribution-panel-full { border: 1px solid var(--wq-border); border-radius: 8px; padding: 12px; }
    .emp-progress-report .contribution-row-full { margin-bottom: 10px; }
    .emp-progress-report .contribution-row-full:last-child { margin-bottom: 0; }
    .emp-progress-report .contribution-row-head {
        display: flex; justify-content: space-between; align-items: center; font-size: 12px;
    }
    .emp-progress-report .contribution-row-label {
        display: flex; align-items: center; gap: 6px; font-weight: 600; color: var(--wq-text);
    }
    .emp-progress-report .contribution-row-label .material-symbols-outlined { font-size: 16px; color: var(--wq-brand); flex-shrink: 0; }
    .emp-progress-report .contribution-row-pct { font-weight: 700; color: var(--wq-brand); }
    .emp-progress-report .contribution-row-meta { font-size: 10px; color: var(--wq-muted); margin: 2px 0 4px; }
    .emp-progress-report .contribution-bar {
        height: 6px; background: #eef0f3; border-radius: 999px; overflow: hidden;
    }
    .emp-progress-report .contribution-bar > span {
        display: block; height: 100%; background: var(--wq-brand); border-radius: 999px;
    }
    .emp-progress-report .rank-summary {
        display: flex; align-items: center; gap: 12px; margin-bottom: 10px;
        padding: 10px; border: 1px solid var(--wq-border); border-radius: 8px; background: var(--wq-surface);
    }
    .emp-progress-report .rank-summary-badge {
        font-size: 24px; font-weight: 800; color: var(--wq-brand);
        min-width: 48px; text-align: center;
    }
    .emp-progress-report .rank-summary-label { font-size: 13px; font-weight: 700; color: var(--wq-text); }
    .emp-progress-report .rank-summary-sub { font-size: 11px; color: var(--wq-muted); }
    .emp-progress-report .improvement-list { display: flex; flex-direction: column; gap: 8px; }
    .emp-progress-report .improvement-item {
        display: flex; gap: 10px; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--wq-border);
        background: #fff;
    }
    .emp-progress-report .improvement-item.priority-high { border-left: 3px solid #dc2626; background: #fef2f2; }
    .emp-progress-report .improvement-item.priority-medium { border-left: 3px solid #d97706; background: #fffbeb; }
    .emp-progress-report .improvement-item.priority-low { border-left: 3px solid #16a34a; }
    .emp-progress-report .improvement-icon { font-size: 20px; color: var(--wq-brand); flex-shrink: 0; }
    .emp-progress-report .improvement-title { font-size: 12px; font-weight: 700; color: var(--wq-text); }
    .emp-progress-report .improvement-detail { font-size: 11px; color: var(--wq-muted); margin-top: 2px; }
    .emp-progress-report .report-data-card {
        border: 1px solid var(--wq-border); border-radius: 8px; overflow: hidden; background: #fff; height: 100%;
    }
    .emp-progress-report .report-data-card-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 8px 12px; background: var(--wq-surface); border-bottom: 1px solid #eef0f3;
        font-size: 12px; font-weight: 700; color: var(--wq-text);
    }
    .emp-progress-report .report-data-card-body { padding: 0; }
    .emp-progress-report .report-data-card-footer {
        padding: 6px 12px; border-top: 1px solid #eef0f3; text-align: right;
    }
    .emp-progress-report .report-data-card-footer a {
        font-size: 11px; font-weight: 600; color: var(--wq-brand); text-decoration: none;
    }
    .emp-progress-report .report-table-scroll--compact { max-height: 280px; border: none; border-radius: 0; }
    .emp-progress-report .report-detail-table {
        width: 100%; border-collapse: collapse; font-size: 11px;
    }
    .emp-progress-report .report-detail-table thead th {
        position: sticky; top: 0; background: var(--wq-surface);
        padding: 6px 8px; border-bottom: 1px solid var(--wq-border);
        font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--wq-muted);
        white-space: nowrap;
    }
    .emp-progress-report .report-detail-table tbody td {
        padding: 6px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;
    }
    .emp-progress-report .report-detail-table tbody tr.is-overdue { background: #fef2f2; }
    .emp-progress-report .report-detail-table tbody tr:hover { background: #fafafa; }
    .emp-progress-report .report-row-link {
        color: var(--wq-brand); text-decoration: none; display: block;
    }
    .emp-progress-report .report-row-link:hover { text-decoration: underline; }
    .emp-progress-report .cell-primary { display: block; font-weight: 600; color: var(--wq-text); }
    .emp-progress-report .cell-secondary { display: block; font-size: 10px; color: var(--wq-muted); }
    .emp-progress-report .type-pill {
        display: inline-block; font-size: 10px; padding: 2px 6px; border-radius: 4px;
        background: #f1f5f9; color: var(--wq-muted);
    }
    .emp-progress-report .urgency-pill {
        display: inline-block; font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 4px;
    }
    .emp-progress-report .urgency-pill.urgency-high { background: #fef2f2; color: #dc2626; }
    .emp-progress-report .urgency-pill.urgency-medium { background: #fffbeb; color: #d97706; }
    .emp-progress-report .urgency-pill.urgency-low { background: #f0fdf4; color: #16a34a; }
    .emp-progress-report .report-empty {
        padding: 20px; text-align: center; font-size: 12px; color: var(--wq-muted);
        display: flex; flex-direction: column; align-items: center; gap: 6px;
    }
    .emp-progress-report .report-empty .material-symbols-outlined { font-size: 22px; opacity: .55; }
    .emp-progress-report .report-divider {
        border-top: 2px solid var(--wq-border); margin: 24px 0 18px;
    }
    .emp-progress-report .report-divider-label {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        color: var(--wq-muted); letter-spacing: 0.04em; margin-bottom: 14px;
    }
    .emp-progress-report .report-section-title .material-symbols-outlined {
        font-size: 16px; color: var(--wq-brand); flex-shrink: 0;
    }
    .emp-progress-report .progress-stat-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 8px;
    }
    .emp-progress-report .progress-stat-tile--compact {
        display: flex; flex-direction: row; align-items: center; gap: 6px;
        padding: 8px 10px; min-height: 52px; border: 1px solid var(--wq-border);
        border-radius: 8px; background: #fff; min-width: 0; box-sizing: border-box;
    }
    .emp-progress-report .progress-stat-tile--compact .progress-stat-icon {
        flex-shrink: 0; font-size: 18px; line-height: 1; margin: 0; color: var(--wq-brand);
    }
    .emp-progress-report .progress-stat-tile--compact .progress-stat-tile-content {
        display: flex; flex-direction: column; gap: 0; min-width: 0; flex: 1 1 auto;
    }
    .emp-progress-report .progress-stat-tile--compact .progress-stat-val {
        font-size: 15px; font-weight: 700; line-height: 1.15; color: var(--wq-brand);
    }
    .emp-progress-report .progress-stat-tile--compact .progress-stat-label {
        font-size: 10px; font-weight: 600; line-height: 1.2; color: var(--wq-muted);
    }
    .emp-progress-report .progress-stat-tile--compact .progress-stat-sub {
        font-size: 9px; color: #dc2626; margin-top: 2px; line-height: 1.2;
    }
    .emp-progress-report .progress-stat-tile--compact.is-zero { opacity: .78; }
    .emp-progress-report .progress-stat-tile--compact.is-zero .progress-stat-icon,
    .emp-progress-report .progress-stat-tile--compact.is-zero .progress-stat-val { color: var(--wq-muted); }
    .emp-progress-report .progress-stat-tile--compact.tone-lead { --pt-soft: #eff6ff; --pt-border: #bfdbfe; --pt-text: #1e40af; }
    .emp-progress-report .progress-stat-tile--compact.tone-booking { --pt-soft: #ecfeff; --pt-border: #a5f3fc; --pt-text: #0e7490; }
    .emp-progress-report .progress-stat-tile--compact.tone-task { --pt-soft: #f5f3ff; --pt-border: #ddd6fe; --pt-text: #5b21b6; }
    .emp-progress-report .progress-stat-tile--compact.tone-brand { --pt-soft: #eef0f6; --pt-border: #c7cbe0; --pt-text: #43466e; }
    .emp-progress-report .progress-stat-tile--compact.tone-outbound { --pt-soft: #fffbeb; --pt-border: #fde68a; --pt-text: #b45309; }
    .emp-progress-report .progress-stat-tile--compact.tone-whatsapp { --pt-soft: #f0fdf4; --pt-border: #bbf7d0; --pt-text: #15803d; }
    .emp-progress-report .progress-stat-tile--compact.tone-whatsapp-closed { --pt-soft: #f0fdfa; --pt-border: #99f6e4; --pt-text: #0f766e; }
    .emp-progress-report .progress-stat-tile--compact.tone-sync { --pt-soft: #eef2ff; --pt-border: #c7d2fe; --pt-text: #4338ca; }
    .emp-progress-report .progress-stat-tile--compact.tone-good { --pt-soft: #f0fdf4; --pt-border: #bbf7d0; --pt-text: #15803d; }
    .emp-progress-report .progress-stat-tile--compact.tone-warn { --pt-soft: #fef2f2; --pt-border: #fecaca; --pt-text: #b91c1c; }
    .emp-progress-report .progress-stat-tile--compact.tone-neutral { --pt-soft: #f8fafc; --pt-border: #e2e8f0; --pt-text: #475569; }
    .emp-progress-report .progress-stat-tile--compact[class*="tone-"]:not(.is-zero) {
        background: var(--pt-soft); border-color: var(--pt-border);
    }
    .emp-progress-report .progress-stat-tile--compact[class*="tone-"]:not(.is-zero) .progress-stat-icon,
    .emp-progress-report .progress-stat-tile--compact[class*="tone-"]:not(.is-zero) .progress-stat-val {
        color: var(--pt-text);
    }
</style>
@endpush

@section('content')
<div class="main-content emp-progress-report">
    <div class="container-fluid">
        <div class="report-topbar">
            <div>
                <h1 class="report-topbar-title">{{ translate('My_Progress_Report') }}</h1>
                <div class="report-topbar-sub">{{ trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->email ?? '') }}</div>
            </div>
        </div>

        <div class="report-shell">
            <div class="report-shell-header">
                <div class="report-tabs">
                    <a href="{{ route('admin.my-progress', ['tab' => 'daily', 'date' => $tab === 'daily' ? ($date ?? today()->toDateString()) : today()->toDateString()]) }}"
                       class="report-tab {{ $tab === 'daily' ? 'active' : '' }}">{{ translate('Daily_Report') }}</a>
                    <a href="{{ route('admin.my-progress', ['tab' => 'monthly']) }}"
                       class="report-tab {{ $tab === 'monthly' ? 'active' : '' }}">{{ translate('Monthly_Report') }}</a>
                </div>

                @if($tab === 'daily')
                    <form method="get" action="{{ route('admin.my-progress') }}" class="report-filter">
                        <input type="hidden" name="tab" value="daily">
                        <label for="progress-date">{{ translate('Date') }}</label>
                        <input type="date" id="progress-date" name="date" value="{{ $date }}">
                        <button type="submit" class="btn btn-brand">{{ translate('Apply') }}</button>
                    </form>
                @else
                    <form method="get" action="{{ route('admin.my-progress') }}" class="report-filter">
                        <input type="hidden" name="tab" value="monthly">
                        <label for="progress-from">{{ translate('From') }}</label>
                        <input type="date" id="progress-from" name="date_from" value="{{ $dateFrom }}">
                        <label for="progress-to">{{ translate('To') }}</label>
                        <input type="date" id="progress-to" name="date_to" value="{{ $dateTo }}">
                        <button type="submit" class="btn btn-brand">{{ translate('Apply') }}</button>
                    </form>
                @endif
            </div>

            <div class="report-body">
                @if(! empty($fullReport))
                    @include('adminmodule::partials._employee-progress-full-report', [
                        'fullReport' => $fullReport,
                        'user' => $user,
                    ])
                    <div class="report-divider">
                        <div class="report-divider-label">
                            {{ $tab === 'daily' ? translate('Daily_activity_detail') : translate('Monthly_activity_detail') }}
                        </div>
                    </div>
                @endif

                @if($tab === 'daily' && $detail)
                    <p class="text-muted mb-3" style="font-size:12px">{{ translate('Daily_report_for') }} <strong>{{ $dateLabel }}</strong></p>
                    @include('adminmodule::admin.report.partials.daily-employee-detail-metrics', [
                        'metricColumns' => $metricColumns,
                        'totals' => $detail['totals'],
                    ])
                    <div class="mt-3">
                        @include('adminmodule::admin.report.partials.daily-employee-detail-sections', [
                            'sectionDefs' => $sectionDefs,
                            'sections' => $detail['sections'],
                        ])
                    </div>
                @else
                    <p class="text-muted mb-3" style="font-size:12px">{{ translate('Monthly_report_for') }} <strong>{{ $periodLabel }}</strong></p>

                    @if(! empty($monthly['stats']))
                        <div class="progress-stat-grid mb-3">
                            @foreach($monthly['stats'] as $stat)
                                @include('adminmodule::partials._employee-progress-stat-tile', ['item' => $stat])
                            @endforeach
                            @if(! empty($monthly['discipline_stat']))
                                @include('adminmodule::partials._employee-progress-stat-tile', ['item' => $monthly['discipline_stat']])
                            @endif
                        </div>
                    @endif

                    <h6 class="mb-2" style="font-size:12px;font-weight:700;color:#374151">{{ translate('Daily_activity_breakdown') }}</h6>
                    <div class="report-table-scroll">
                        <table class="report-daily-table">
                            <thead>
                                <tr>
                                    <th>{{ translate('Date') }}</th>
                                    @foreach($metricColumns as $column)
                                        <th title="{{ $column['label'] }}">{{ $column['short'] }}</th>
                                    @endforeach
                                    <th>{{ translate('Online') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dailyRows as $row)
                                    <tr>
                                        <td>
                                            <a class="day-link" href="{{ route('admin.my-progress', ['tab' => 'daily', 'date' => $row['date']]) }}">
                                                {{ $row['date_label'] }}
                                            </a>
                                        </td>
                                        @foreach($metricColumns as $column)
                                            @php $val = (int) ($row[$column['key']] ?? 0); @endphp
                                            <td class="{{ $val === 0 ? 'metric-zero' : '' }}">{{ $val }}</td>
                                        @endforeach
                                        <td>{{ $row['online_hours'] ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($metricColumns) + 2 }}" class="text-center text-muted py-4">
                                            {{ translate('No_activity_in_period') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($dailyRows !== [])
                                <tfoot>
                                    <tr style="background:#f8fafc;font-weight:700">
                                        <td>{{ translate('Total') }}</td>
                                        @foreach($metricColumns as $column)
                                            <td>{{ (int) ($activityTotals[$column['key']] ?? 0) }}</td>
                                        @endforeach
                                        <td>{{ $activityTotals['online_hours'] ?? '—' }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
