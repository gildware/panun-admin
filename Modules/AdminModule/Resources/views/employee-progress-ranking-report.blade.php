@extends('adminmodule::layouts.new-master')

@section('title', $pageTitle ?? translate('Progress_ranking_marks_report'))

@push('css_or_js')
<link rel="stylesheet" href="{{ asset('assets/admin-module/css/employee-progress-premium.css') }}?v=20260814rk">
<link rel="stylesheet" href="{{ asset('assets/admin-module/css/employee-dashboard.css') }}?v=20260814rk">
<style>
    .ranking-marks-report .page-head-employee .form-select {
        min-width: 180px; max-width: 240px;
        padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px;
        font-size: 12px; min-height: auto; background: #fff;
    }
    .ranking-marks-report .shell-filter .form-select,
    .ranking-marks-report .shell-filter input[type="date"] {
        padding: 5px 8px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 10px;
    }
    .ranking-marks-report .shell-filter .btn-brand,
    .ranking-marks-report .shell-filter .period-link {
        padding: 5px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; text-decoration: none;
    }
    .ranking-marks-report .shell-filter .btn-brand { background: #43466e; border-color: #43466e; color: #fff; }
    .ranking-marks-report .shell-filter .period-link { border: 1px solid #e2e8f0; color: #64748b; background: #fff; }
    .ranking-marks-report .shell-filter .period-link.on { background: #43466e; color: #fff; border-color: #43466e; }
    .ranking-marks-report .rank-score-legend {
        display: flex; flex-wrap: wrap; gap: 6px 10px; margin-bottom: 12px;
        font-size: 11px; color: #64748b;
    }
    .ranking-marks-report .rank-score-legend .is-plus { color: #059669; font-weight: 700; }
    .ranking-marks-report .rank-score-legend .is-minus { color: #dc2626; font-weight: 700; }
    .ranking-marks-report .team-rank-cards--full .rank-item { margin-bottom: 10px; }
</style>
@endpush

@section('content')
<div class="main-content emp-progress-report ranking-marks-report emp-dash">
    <div class="container-fluid premium-wrap">
        <nav class="admin-crumb">
            <a href="{{ route('admin.dashboard') }}">{{ translate('dashboard') }}</a>
            <span class="material-symbols-outlined mso">chevron_right</span>
            <span>{{ $pageTitle }}</span>
        </nav>

        <div class="page-head">
            <div>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $periodLabel }}</p>
            </div>
            @if(! empty($viewingAsAdmin) && ! empty($employeeOptions))
                <form method="get" action="{{ route('admin.my-progress.ranking') }}" class="page-head-employee" data-turbo="false">
                    <input type="hidden" name="period" value="{{ $period }}">
                    @if($period === 'daily')
                        <input type="hidden" name="date" value="{{ $date }}">
                    @else
                        <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                        <input type="hidden" name="date_to" value="{{ $dateTo }}">
                    @endif
                    <label class="visually-hidden" for="ranking-employee">{{ translate('Select_employee') }}</label>
                    <select id="ranking-employee" name="employee_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($employeeOptions as $option)
                            <option value="{{ $option['id'] }}"
                                @selected(
                                    (! empty($viewingAllEmployees) && $option['id'] === '__all__')
                                    || (empty($viewingAllEmployees) && $user && (string) $user->id === (string) $option['id'])
                                )>{{ $option['name'] }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>

        <div class="report-shell">
            <div class="shell-head">
                <div class="shell-tabs">
                    <span class="shell-tab on">{{ translate('Progress_team_ranking') ?? 'Team ranking' }}</span>
                </div>
                <form method="get" action="{{ route('admin.my-progress.ranking') }}" class="shell-filter" data-turbo="false">
                    <input type="hidden" name="period" value="{{ $period }}">
                    @foreach($employeeQuery ?? [] as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    @if($period === 'daily')
                        <input type="date" name="date" value="{{ $date }}">
                    @else
                        <input type="date" name="date_from" value="{{ $dateFrom }}">
                        <span style="color:#94a3b8">–</span>
                        <input type="date" name="date_to" value="{{ $dateTo }}">
                    @endif
                    <a href="{{ route('admin.my-progress.ranking', array_merge($employeeQuery ?? [], ['period' => 'daily', 'date' => $date ?? today()->toDateString()])) }}"
                       class="period-link {{ $period === 'daily' ? 'on' : '' }}"
                       data-turbo="false">{{ translate('Daily_Report') }}</a>
                    <a href="{{ route('admin.my-progress.ranking', array_merge($employeeQuery ?? [], ['period' => 'monthly'])) }}"
                       class="period-link {{ $period === 'monthly' ? 'on' : '' }}"
                       data-turbo="false">{{ translate('Monthly_Report') }}</a>
                    <button type="submit" class="btn btn-brand">{{ translate('Apply') }}</button>
                </form>
            </div>
            <div class="shell-body" style="padding:16px">
                @if(! empty($scoreWeights))
                    <div class="rank-score-legend">
                        @foreach($scoreWeights as $weight)
                            <span class="{{ ($weight['sign'] ?? '+') === '+' ? 'is-plus' : 'is-minus' }}">
                                {{ $weight['sign'] ?? '+' }}{{ $weight['points'] ?? 0 }} {{ $weight['label'] ?? '' }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @include('adminmodule::partials._employee-progress-team-rank-cards', [
                    'rows' => $teamRankRows ?? [],
                    'highlightEmployeeId' => $highlightEmployeeId ?? null,
                    'variant' => 'panel',
                    'rankMetricPeriodParams' => $rankMetricPeriodParams ?? [],
                    'rankMetricEmployeeQuery' => $rankMetricEmployeeQuery ?? [],
                    'rankMetricLinksEnabled' => true,
                ])
            </div>
        </div>
    </div>
</div>
@endsection
