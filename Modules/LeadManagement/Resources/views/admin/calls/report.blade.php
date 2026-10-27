@extends('adminmodule::layouts.new-master')

@section('title', translate('Call_report'))

@push('css_or_js')
    <style>
        .calls-report-card .card-body { padding: 0.7rem 0.8rem 0.8rem; }
        .calls-report-head { display: flex; align-items: baseline; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.45rem; }
        .calls-report-title { margin: 0; font-size: 0.92rem; font-weight: 700; color: #111827; }
        .calls-report-note { margin: 0.45rem 0 0; color: #6b7280; font-size: 0.68rem; line-height: 1.35; }
        .calls-report-score { width: 100%; border-collapse: separate; border-spacing: 0; }
        .calls-report-score th,
        .calls-report-score td { padding: 0.28rem 0.4rem; text-align: right; white-space: nowrap; border-bottom: 1px solid #eef0f3; vertical-align: middle; }
        .calls-report-score thead th { color: #6b7280; font-size: 0.66rem; font-weight: 700; letter-spacing: 0.01em; }
        .calls-report-score tbody th { text-align: left; font-size: 0.72rem; font-weight: 700; color: #374151; }
        .calls-report-score .is-today-row { background: #f8fafc; }
        .calls-report-num { font-size: 0.95rem; font-weight: 700; color: #111827; line-height: 1.1; }
        .calls-report-sub { font-size: 0.62rem; color: #6b7280; }
        .calls-tone-answered { color: {{ $typeColors['answered'] }}; }
        .calls-tone-missed { color: {{ $typeColors['missed'] }}; }
        .calls-tone-unallocated { color: {{ $typeColors['unallocated'] }}; }
        .calls-tone-unanswered { color: {{ $typeColors['unanswered'] }}; }
        .calls-tone-channel { color: {{ $typeColors['channel'] }}; }
        .calls-tone-inbound { color: {{ $typeColors['inbound'] }}; }
        .calls-tone-outbound { color: {{ $typeColors['outbound'] }}; }
        .calls-tone-handled { color: #1d4ed8; }
        .calls-tone-duration { color: #4f46e5; }
        .calls-report-tabs { display: inline-flex; align-items: center; gap: 0.15rem; padding: 0.15rem; border-radius: 999px; background: #f3f4f6; }
        .calls-report-tabs button { border: 0; background: transparent; border-radius: 999px; padding: 0.28rem 0.72rem; font-size: 0.72rem; font-weight: 700; color: #4b5563; line-height: 1.2; }
        .calls-report-tabs button.is-active { background: #fff; color: #111827; box-shadow: 0 1px 2px rgba(17, 24, 39, 0.08); }
        .calls-count { display: inline-flex; align-items: center; justify-content: center; min-width: 1.45rem; padding: 0.08rem 0.38rem; border-radius: 999px; font-weight: 700; line-height: 1.2; }
        .calls-count.calls-tone-answered { background: #e7f6ee; }
        .calls-count.calls-tone-missed { background: #fdecea; }
        .calls-count.calls-tone-unallocated { background: #f3e8ff; }
        .calls-count.calls-tone-unanswered { background: #fff4e5; }
        .calls-count.calls-tone-channel { background: #ecfeff; }
        .calls-count.calls-tone-handled { background: #e7f0ff; }
        .calls-count.calls-tone-duration { background: #eef2ff; }
        .calls-count.calls-tone-inbound { background: #e7f0ff; }
        .calls-count.calls-tone-outbound { background: #f1f5f9; }
        .calls-count.is-zero, .calls-report-table .is-zero { color: #c5cad3; font-weight: 500; background: transparent; }
        .calls-report-score thead th[class*="calls-tone-"],
        .calls-report-table thead th[class*="calls-tone-"] { font-weight: 700; }
        .calls-report-legend { display: flex; flex-wrap: wrap; gap: 0.75rem; margin: 0.4rem 0 0; font-size: 0.68rem; font-weight: 700; }
        .calls-report-legend span { display: inline-flex; align-items: center; gap: 0.28rem; }
        .calls-report-legend i { display: inline-block; width: 0.48rem; height: 0.48rem; border-radius: 99px; background: currentColor; }
        .calls-report-chart { min-height: 280px; }
        .calls-report-scroll { max-height: 420px; }
        .calls-report-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.75rem; }
        .calls-report-table th,
        .calls-report-table td { padding: 0.42rem 0.55rem; white-space: nowrap; border-bottom: 1px solid #eef0f3; text-align: right; }
        .calls-report-table thead th { position: sticky; background: #fff; z-index: 2; color: #6b7280; font-size: 0.68rem; font-weight: 700; }
        .calls-report-table thead tr:first-child th { top: 0; text-align: center; font-size: 0.66rem; letter-spacing: 0.03em; text-transform: uppercase; color: #374151; }
        .calls-report-table thead tr:nth-child(2) th { top: 2.15rem; }
        .calls-report-table thead tr:first-child th.is-today { background: #f4f7fb; }
        .calls-report-table thead tr:first-child th.is-overall { background: #fff; }
        .calls-report-table thead th.calls-tone-handled { color: #1d4ed8; background: #e7f0ff; }
        .calls-report-table thead th.calls-tone-answered { color: #0f7a3a; background: #e7f6ee; }
        .calls-report-table thead th.calls-tone-missed { color: #c62828; background: #fdecea; }
        .calls-report-table thead th.calls-tone-unanswered { color: #b45309; background: #fff4e5; }
        .calls-report-table thead th.calls-tone-unallocated { color: #6d28d9; background: #f3e8ff; }
        .calls-report-table thead th.calls-tone-channel { color: #0e7490; background: #ecfeff; }
        .calls-report-table thead th.calls-tone-duration { color: #4f46e5; background: #eef2ff; }
        .calls-status { display: inline-flex; align-items: center; padding: 0.08rem 0.45rem; border-radius: 999px; font-size: 0.68rem; font-weight: 700; }
        .calls-status.is-active { background: #e7f6ee; color: #0f7a3a; }
        .calls-status.is-inactive { background: #f3f4f6; color: #6b7280; }
        .calls-report-table thead th.calls-tone-inbound { color: #1d4ed8; background: #e7f0ff; }
        .calls-report-table thead th.calls-tone-outbound { color: #334155; background: #f1f5f9; }
        .calls-report-table tbody th,
        .calls-report-table thead th:first-child { text-align: left; position: sticky; left: 0; z-index: 3; color: #111827; font-weight: 700; }
        .calls-report-table thead th:first-child { z-index: 4; background: #fff; }
        .calls-report-table tbody tr:nth-child(odd) > td,
        .calls-report-table tbody tr:nth-child(odd) > th { background: #fff; }
        .calls-report-table tbody tr:nth-child(even) > td,
        .calls-report-table tbody tr:nth-child(even) > th { background: #f3f4f6; }
        .calls-report-table .is-overall-start { box-shadow: inset 2px 0 0 #e5e7eb; }
        .calls-report-table tbody tr:hover > td,
        .calls-report-table tbody tr:hover > th { background: #e7ebf2; }
        .calls-report-empty { text-align: center !important; color: #6b7280; padding: 0.8rem !important; }
        .calls-report-tabs a { border: 0; background: transparent; border-radius: 999px; padding: 0.28rem 0.72rem; font-size: 0.72rem; font-weight: 700; color: #4b5563; line-height: 1.2; text-decoration: none; }
        .calls-report-tabs a.is-active { background: #fff; color: #111827; box-shadow: 0 1px 2px rgba(17, 24, 39, 0.08); }
        .calls-lead-filters { display: flex; flex-wrap: wrap; gap: 0.45rem 0.6rem; align-items: flex-end; margin: 0.55rem 0 0.7rem; }
        .calls-lead-filters label { display: flex; flex-direction: column; gap: 0.15rem; min-width: 10rem; font-size: 0.66rem; font-weight: 700; color: #6b7280; }
        .calls-lead-filters select { font-size: 0.75rem; }
        .calls-lead-filters .calls-lead-actions { display: flex; align-items: center; gap: 0.45rem; }
        .calls-lead-reset { font-size: 0.72rem; font-weight: 700; color: #4b5563; }
        .calls-lead-pills { display: flex; flex-wrap: wrap; gap: 0.35rem; margin: 0 0 0.35rem; }
        .calls-lead-pills span { display: inline-flex; align-items: center; gap: 0.28rem; padding: 0.12rem 0.45rem; border-radius: 999px; background: #f3f4f6; font-size: 0.68rem; font-weight: 700; color: #374151; }
        .calls-lead-pills b { color: #111827; }
        .calls-lead-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; }
        .calls-lead-panel { min-width: 0; }
        .calls-lead-panel h3 { margin: 0 0 0.35rem; font-size: 0.78rem; font-weight: 700; color: #111827; }
        .calls-lead-chart { min-height: 240px; }
        .calls-lead-breakdown { min-height: 260px; }
        @media (max-width: 1100px) { .calls-lead-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) { .calls-lead-grid { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    @php
        $reportTabs = [
            'overall' => [
                'label' => translate('Overall'),
                'rateKey' => null,
                'legend' => [
                    'answered' => 'Answered',
                    'missed' => 'Missed',
                    'unallocated' => 'Unallocated',
                    'unanswered' => 'Unanswered',
                    'channel' => 'Channel_not_available',
                ],
                'columns' => [
                    ['key' => 'total', 'label' => translate('Total')],
                    ['key' => 'inbound', 'label' => translate('Inbound'), 'tone' => 'inbound'],
                    ['key' => 'inbound_answered', 'label' => translate('Inbound').' · '.translate('Answered'), 'tone' => 'answered', 'rateKey' => 'inbound_answer_rate'],
                    ['key' => 'inbound_missed', 'label' => translate('Missed'), 'tone' => 'missed'],
                    ['key' => 'inbound_unallocated', 'label' => translate('Unallocated'), 'tone' => 'unallocated'],
                    ['key' => 'outbound', 'label' => translate('Outbound'), 'tone' => 'outbound'],
                    ['key' => 'outbound_answered', 'label' => translate('Outbound').' · '.translate('Answered'), 'tone' => 'answered', 'rateKey' => 'outbound_answer_rate'],
                    ['key' => 'outbound_unanswered', 'label' => translate('Unanswered'), 'tone' => 'unanswered'],
                    ['key' => 'outbound_channel', 'label' => translate('Channel_not_available'), 'tone' => 'channel'],
                    ['key' => 'total_duration_label', 'label' => translate('Duration'), 'tone' => 'duration', 'duration' => true],
                ],
                'employeeColumns' => [
                    ['key' => 'total', 'label' => translate('Handled'), 'title' => translate('Handled'), 'tone' => 'handled'],
                    ['key' => 'inbound', 'label' => translate('Inbound'), 'title' => translate('Inbound'), 'tone' => 'inbound'],
                    ['key' => 'inbound_answered', 'label' => translate('Inbound').' · '.translate('Answered'), 'title' => translate('Inbound').' · '.translate('Answered'), 'tone' => 'answered'],
                    ['key' => 'inbound_missed', 'label' => translate('Missed'), 'title' => translate('Missed'), 'tone' => 'missed'],
                    ['key' => 'inbound_unallocated', 'label' => translate('Unallocated'), 'title' => translate('Unallocated'), 'tone' => 'unallocated'],
                    ['key' => 'outbound', 'label' => translate('Outbound'), 'title' => translate('Outbound'), 'tone' => 'outbound'],
                    ['key' => 'outbound_answered', 'label' => translate('Outbound').' · '.translate('Answered'), 'title' => translate('Outbound').' · '.translate('Answered'), 'tone' => 'answered'],
                    ['key' => 'outbound_unanswered', 'label' => translate('Unanswered'), 'title' => translate('Unanswered'), 'tone' => 'unanswered'],
                    ['key' => 'outbound_channel', 'label' => translate('Channel_not_available'), 'title' => translate('Channel_not_available'), 'tone' => 'channel'],
                    ['key' => 'total_duration_label', 'label' => translate('Duration'), 'title' => translate('Duration'), 'tone' => 'duration'],
                ],
            ],
            'inbound' => [
                'label' => translate('Inbound'),
                'rateKey' => 'inbound_answer_rate',
                'legend' => ['answered' => 'Answered', 'missed' => 'Missed', 'unallocated' => 'Unallocated'],
                'columns' => [
                    ['key' => 'inbound', 'label' => translate('Total')],
                    ['key' => 'inbound_answered', 'label' => translate('Answered'), 'tone' => 'answered', 'rate' => true],
                    ['key' => 'inbound_missed', 'label' => translate('Missed'), 'tone' => 'missed'],
                    ['key' => 'inbound_unallocated', 'label' => translate('Unallocated'), 'tone' => 'unallocated'],
                    ['key' => 'inbound_duration_label', 'label' => translate('Duration'), 'duration' => true],
                ],
                'employeeColumns' => [
                    ['key' => 'inbound', 'label' => translate('Handled'), 'title' => translate('Inbound'), 'tone' => 'handled'],
                    ['key' => 'inbound_answered', 'label' => translate('Answered'), 'title' => translate('Answered'), 'tone' => 'answered'],
                    ['key' => 'inbound_missed', 'label' => translate('Missed'), 'title' => translate('Missed'), 'tone' => 'missed'],
                    ['key' => 'inbound_unallocated', 'label' => translate('Unallocated'), 'title' => translate('Unallocated'), 'tone' => 'unallocated'],
                    ['key' => 'inbound_duration_label', 'label' => translate('Duration'), 'title' => translate('Duration'), 'tone' => 'duration'],
                ],
            ],
            'outbound' => [
                'label' => translate('Outbound'),
                'rateKey' => 'outbound_answer_rate',
                'legend' => ['answered' => 'Answered', 'unanswered' => 'Unanswered', 'channel' => 'Channel_not_available'],
                'columns' => [
                    ['key' => 'outbound', 'label' => translate('Total')],
                    ['key' => 'outbound_answered', 'label' => translate('Answered'), 'tone' => 'answered', 'rate' => true],
                    ['key' => 'outbound_unanswered', 'label' => translate('Unanswered'), 'tone' => 'unanswered'],
                    ['key' => 'outbound_channel', 'label' => translate('Channel_not_available'), 'tone' => 'channel'],
                    ['key' => 'outbound_duration_label', 'label' => translate('Duration'), 'duration' => true],
                ],
                'employeeColumns' => [
                    ['key' => 'outbound', 'label' => translate('Handled'), 'title' => translate('Outbound'), 'tone' => 'handled'],
                    ['key' => 'outbound_answered', 'label' => translate('Answered'), 'title' => translate('Answered'), 'tone' => 'answered'],
                    ['key' => 'outbound_unanswered', 'label' => translate('Unanswered'), 'title' => translate('Unanswered'), 'tone' => 'unanswered'],
                    ['key' => 'outbound_channel', 'label' => translate('Channel_not_available'), 'title' => translate('Channel_not_available'), 'tone' => 'channel'],
                    ['key' => 'outbound_duration_label', 'label' => translate('Duration'), 'title' => translate('Duration'), 'tone' => 'duration'],
                ],
            ],
        ];
    @endphp
    <div class="main-content">
        <div class="container-fluid">
            <div class="card calls-report-card mb-3">
                <div class="card-body">
                    <div class="calls-report-head">
                        <div>
                            <h2 class="calls-report-title">{{ translate('Call_report') }}</h2>
                            <span class="text-muted" style="font-size: 0.72rem;">{{ translate('Today') }} · {{ $todayLabel }}</span>
                        </div>
                        <div class="calls-report-tabs" role="tablist">
                            @foreach($reportTabs as $tabKey => $tab)
                                <button type="button" class="{{ $tabKey === 'overall' ? 'is-active' : '' }}" data-report-tab="{{ $tabKey }}" role="tab">{{ $tab['label'] }}</button>
                            @endforeach
                        </div>
                    </div>
                    <p class="calls-report-note" style="margin-top: 0;">{{ translate('Calls_report_hint') }}</p>
                    @foreach($reportTabs as $tabKey => $tab)
                        <div data-report-panel="{{ $tabKey }}" @if($tabKey !== 'overall') hidden @endif>
                            <div class="table-responsive">
                                <table class="calls-report-score">
                                    <thead>
                                    <tr>
                                        <th></th>
                                        @foreach($tab['columns'] as $column)
                                            <th class="{{ isset($column['tone']) ? 'calls-tone-'.$column['tone'] : '' }}">{{ $column['label'] }}</th>
                                        @endforeach
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach(['today' => translate('Today'), 'overall' => translate('Overall')] as $period => $label)
                                        @php $bucket = $period === 'today' ? $today : $overall; @endphp
                                        <tr class="{{ $period === 'today' ? 'is-today-row' : '' }}">
                                            <th>{{ $label }}</th>
                                            @foreach($tab['columns'] as $column)
                                                <td>
                                                    <div class="calls-report-num {{ isset($column['tone']) ? 'calls-tone-'.$column['tone'] : '' }}">{{ $bucket[$column['key']] }}</div>
                                                    @php $rateKey = $column['rateKey'] ?? (! empty($column['rate']) ? $tab['rateKey'] : null); @endphp
                                                    @if($rateKey)
                                                        <div class="calls-report-sub">{{ $bucket[$rateKey] }}%</div>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="calls-report-legend">
                                @foreach($tab['legend'] as $toneKey => $toneLabel)
                                    <span class="calls-tone-{{ $toneKey }}"><i></i>{{ translate($toneLabel) }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <div id="calls-report-chart" class="calls-report-chart"></div>
                </div>
            </div>

            <div class="card calls-report-card mb-3">
                <div class="card-body">
                    <div class="calls-report-head">
                        <h2 class="calls-report-title">{{ translate('Employee') }}</h2>
                        <div class="calls-report-tabs" role="tablist">
                            @foreach($reportTabs as $tabKey => $tab)
                                <button type="button" class="{{ $tabKey === 'overall' ? 'is-active' : '' }}" data-employee-tab="{{ $tabKey }}" role="tab">{{ $tab['label'] }}</button>
                            @endforeach
                        </div>
                    </div>
                    @foreach($reportTabs as $tabKey => $tab)
                        <div data-employee-panel="{{ $tabKey }}" @if($tabKey !== 'overall') hidden @endif>
                            @include('leadmanagement::admin.calls._report_table', [
                                'rows' => $employees,
                                'nameHeading' => translate('Employee'),
                                'columns' => $tab['employeeColumns'],
                            ])
                        </div>
                    @endforeach
                </div>
            </div>

            @php
                $dateViews = [
                    'day' => ['label' => translate('Day'), 'heading' => translate('Date')],
                    'week' => ['label' => translate('Week'), 'heading' => translate('Week')],
                    'month' => ['label' => translate('Month'), 'heading' => translate('Month')],
                ];
                $dateColumns = [
                    ['key' => 'total', 'label' => translate('Total'), 'tone' => 'handled'],
                    ['key' => 'inbound', 'label' => translate('Inbound'), 'tone' => 'inbound'],
                    ['key' => 'inbound_answered', 'label' => translate('Answered'), 'tone' => 'answered'],
                    ['key' => 'inbound_missed', 'label' => translate('Missed'), 'tone' => 'missed'],
                    ['key' => 'inbound_unallocated', 'label' => translate('Unallocated'), 'tone' => 'unallocated'],
                    ['key' => 'outbound', 'label' => translate('Outbound'), 'tone' => 'outbound'],
                    ['key' => 'outbound_answered', 'label' => translate('Outbound').' · '.translate('Answered'), 'tone' => 'answered'],
                    ['key' => 'outbound_unanswered', 'label' => translate('Unanswered'), 'tone' => 'unanswered'],
                    ['key' => 'outbound_channel', 'label' => translate('Channel_not_available'), 'tone' => 'channel'],
                ];
            @endphp
            <div class="card calls-report-card mb-3">
                <div class="card-body">
                    <div class="calls-report-head">
                        <h2 class="calls-report-title">{{ translate('By_date') }}</h2>
                        <div class="calls-report-tabs" role="tablist">
                            @foreach($dateViews as $viewKey => $view)
                                <button type="button" class="{{ $viewKey === 'day' ? 'is-active' : '' }}" data-date-tab="{{ $viewKey }}" role="tab">{{ $view['label'] }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div id="calls-report-dates" class="calls-report-chart"></div>
                    @foreach($dateViews as $viewKey => $view)
                        <div data-date-panel="{{ $viewKey }}" @if($viewKey !== 'day') hidden @endif>
                            @if($dates[$viewKey]['peak'] !== '')
                                <p class="calls-report-note" style="margin-top: 0;">{{ translate('Most_calls') }} · {{ $dates[$viewKey]['peak'] }}</p>
                            @endif
                            @include('leadmanagement::admin.calls._timing_table', [
                                'rows' => $dates[$viewKey]['rows'],
                                'nameHeading' => $view['heading'],
                                'columns' => $dateColumns,
                            ])
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card calls-report-card mb-3">
                <div class="card-body">
                    <div class="calls-report-head">
                        <h2 class="calls-report-title">{{ translate('By_time') }}</h2>
                        @if($hours['peak'] !== '')
                            <span class="text-muted" style="font-size: 0.72rem;">{{ translate('Most_calls') }} · {{ $hours['peak'] }}</span>
                        @endif
                    </div>
                    <div id="calls-report-hours" class="calls-report-chart"></div>
                    @include('leadmanagement::admin.calls._timing_table', [
                        'rows' => $hours['busy'],
                        'nameHeading' => translate('Time'),
                        'columns' => [
                            ['key' => 'inbound', 'label' => translate('Inbound'), 'tone' => 'inbound'],
                            ['key' => 'inbound_answered', 'label' => translate('Answered'), 'tone' => 'answered'],
                            ['key' => 'inbound_missed', 'label' => translate('Missed'), 'tone' => 'missed'],
                            ['key' => 'inbound_unallocated', 'label' => translate('Unallocated'), 'tone' => 'unallocated'],
                            ['key' => 'outbound', 'label' => translate('Outbound'), 'tone' => 'outbound'],
                            ['key' => 'outbound_unanswered', 'label' => translate('Unanswered'), 'tone' => 'unanswered'],
                            ['key' => 'outbound_channel', 'label' => translate('Channel_not_available'), 'tone' => 'channel'],
                            ['key' => 'total', 'label' => translate('Total'), 'tone' => 'handled'],
                        ],
                    ])
                </div>
            </div>

            <div class="card calls-report-card mb-3">
                <div class="card-body">
                    <div class="calls-report-head">
                        <h2 class="calls-report-title">{{ translate('Employee') }} · {{ translate('By_time') }}</h2>
                    </div>
                    <div class="table-responsive calls-report-scroll">
                        <table class="calls-report-table">
                            <thead>
                            <tr>
                                <th>{{ translate('Employee') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Today') }}</th>
                                <th class="calls-tone-missed">{{ translate('Missed') }}</th>
                                <th>{{ translate('First_call') }}</th>
                                <th>{{ translate('Last_call') }}</th>
                                <th>{{ translate('Usual_time') }}</th>
                                <th class="calls-tone-handled">{{ translate('Handled') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($activity as $person)
                                <tr>
                                    <th>{{ $person['label'] }}</th>
                                    <td><span class="calls-status is-{{ $person['status'] }}">{{ $person['status_label'] }}</span></td>
                                    <td><span class="calls-count calls-tone-inbound {{ (int) $person['today'] === 0 ? 'is-zero' : '' }}">{{ $person['today'] }}</span></td>
                                    <td><span class="calls-count calls-tone-missed {{ (int) $person['missed'] === 0 ? 'is-zero' : '' }}">{{ $person['missed'] }}</span></td>
                                    <td>{{ $person['first'] }}</td>
                                    <td>{{ $person['last'] }}</td>
                                    <td>{{ $person['usual'] }}</td>
                                    <td><span class="calls-count calls-tone-handled {{ (int) $person['total'] === 0 ? 'is-zero' : '' }}">{{ $person['total'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="calls-report-empty">{{ translate('No_calls_yet') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @include('leadmanagement::admin.calls._lead_charts')
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ asset('assets/admin-module/plugins/apex/apexcharts.min.js') }}"></script>
    <script>
        (function () {
            var data = @json($charts);
            var emptyText = @json(translate('No_calls_yet'));

            function sum(values) {
                return (values || []).reduce(function (total, value) { return total + Number(value || 0); }, 0);
            }

            function showEmpty(el) {
                if (!el) return;
                el.innerHTML = '<div class="calls-report-empty">' + emptyText + '</div>';
            }

            var chartEl = document.getElementById('calls-report-chart');
            var chart = null;

            function draw(key) {
                var block = data[key];
                if (!chartEl || !block) return;
                var series = block.series.map(function (item) { return { name: item.name, data: item.data }; });
                var colors = block.series.map(function (item) { return item.color; });
                if (!block.categories.length || !series.some(function (item) { return sum(item.data); })) {
                    if (chart) {
                        chart.destroy();
                        chart = null;
                    }
                    showEmpty(chartEl);
                    return;
                }
                if (chart) {
                    chart.updateOptions({
                        colors: colors,
                        xaxis: { categories: block.categories }
                    });
                    chart.updateSeries(series);
                    return;
                }
                chart = new ApexCharts(chartEl, {
                    series: series,
                    chart: { type: 'bar', height: 280, fontFamily: 'inherit', toolbar: { show: false } },
                    plotOptions: { bar: { borderRadius: 3, columnWidth: '46%' } },
                    colors: colors,
                    dataLabels: { enabled: false },
                    stroke: { show: true, width: 2, colors: ['transparent'] },
                    xaxis: { categories: block.categories, labels: { style: { fontSize: '12px' } } },
                    yaxis: { labels: { style: { fontSize: '11px' } } },
                    legend: { position: 'top', fontSize: '12px' },
                    grid: { strokeDashArray: 4, borderColor: '#eef0f3' }
                });
                chart.render();
            }

            document.querySelectorAll('[data-report-tab]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var key = button.getAttribute('data-report-tab');
                    document.querySelectorAll('[data-report-tab]').forEach(function (item) {
                        item.classList.toggle('is-active', item === button);
                    });
                    document.querySelectorAll('[data-report-panel]').forEach(function (panel) {
                        panel.hidden = panel.getAttribute('data-report-panel') !== key;
                    });
                    draw(key);
                });
            });

            document.querySelectorAll('[data-employee-tab]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var key = button.getAttribute('data-employee-tab');
                    document.querySelectorAll('[data-employee-tab]').forEach(function (item) {
                        item.classList.toggle('is-active', item === button);
                    });
                    document.querySelectorAll('[data-employee-panel]').forEach(function (panel) {
                        panel.hidden = panel.getAttribute('data-employee-panel') !== key;
                    });
                });
            });

            function renderBar(el, block, rotate) {
                if (!el || !block) return null;
                var series = (block.series || []).map(function (item) { return { name: item.name, data: item.data }; });
                var colors = (block.series || []).map(function (item) { return item.color; });
                if (!block.categories.length || !series.some(function (item) { return sum(item.data); })) {
                    showEmpty(el);
                    return null;
                }
                var chart = new ApexCharts(el, {
                    series: series,
                    chart: { type: 'bar', height: 280, fontFamily: 'inherit', toolbar: { show: false } },
                    plotOptions: { bar: { borderRadius: 3, columnWidth: '52%' } },
                    colors: colors,
                    dataLabels: { enabled: false },
                    stroke: { show: true, width: 2, colors: ['transparent'] },
                    xaxis: { categories: block.categories, labels: { rotate: rotate || 0, style: { fontSize: '11px' } } },
                    yaxis: { labels: { style: { fontSize: '11px' } } },
                    legend: { position: 'top', fontSize: '12px' },
                    grid: { strokeDashArray: 4, borderColor: '#eef0f3' }
                });
                chart.render();
                return chart;
            }

            var dateChart = null;
            function drawDates(key) {
                var el = document.getElementById('calls-report-dates');
                if (dateChart) {
                    dateChart.destroy();
                    dateChart = null;
                }
                dateChart = renderBar(el, data.dates[key], -40);
            }

            document.querySelectorAll('[data-date-tab]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var key = button.getAttribute('data-date-tab');
                    document.querySelectorAll('[data-date-tab]').forEach(function (item) {
                        item.classList.toggle('is-active', item === button);
                    });
                    document.querySelectorAll('[data-date-panel]').forEach(function (panel) {
                        panel.hidden = panel.getAttribute('data-date-panel') !== key;
                    });
                    drawDates(key);
                });
            });

            draw('overall');
            drawDates('day');
            renderBar(document.getElementById('calls-report-hours'), data.hours, -40);
        })();
    </script>
@endpush
