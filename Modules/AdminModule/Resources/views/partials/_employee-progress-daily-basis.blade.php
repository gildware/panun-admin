@php
    $activityMetricColumns = $activityMetricColumns ?? [];
    $activityTotals = $activityTotals ?? [];
    $activityDailyRows = $activityDailyRows ?? [];
    $toneMap = ['good' => 'success', 'warning' => 'warning', 'warn' => 'warning', 'danger' => 'danger'];
    $groupMeta = [
        'leads' => [
            'title' => translate('Leads') ?? 'Leads',
            'icon' => 'group',
            'hint' => translate('Progress_daily_basis_leads_hint') ?? 'New leads, assignments, updates, and follow-ups',
        ],
        'bookings' => [
            'title' => translate('Bookings') ?? 'Bookings',
            'icon' => 'event_note',
            'hint' => translate('Progress_daily_basis_bookings_hint') ?? 'Created, completed, cancelled, updates, and follow-ups',
        ],
        'communication' => [
            'title' => translate('Communication') ?? 'Communication',
            'icon' => 'forum',
            'hint' => translate('Progress_daily_basis_comms_hint') ?? 'WhatsApp assigns, replies, and call logs',
        ],
    ];
    $groupedColumns = collect($activityMetricColumns)->groupBy('group');
    $periodText = $tab === 'daily'
        ? ($dateLabel ?? translate('Daily_Report'))
        : ($periodLabel ?? translate('Monthly_Report'));
    $totalActions = collect($activityMetricColumns)->sum(fn ($col) => (int) ($activityTotals[$col['key']] ?? 0));
@endphp

<div class="daily-basis">
    <div class="daily-basis-intro">
        <div>
            <h3 class="daily-basis-title">{{ translate('Daily_Basis_Report') ?? 'Daily Basis Report' }}</h3>
            <p class="daily-basis-sub">
                {{ translate('Progress_daily_basis_sub') ?? 'Quantitative work done in the selected period' }}
                · {{ $periodText }}
            </p>
        </div>
        <div class="daily-basis-total">
            <span class="daily-basis-total-lbl">{{ translate('Total_actions') ?? 'Total actions' }}</span>
            <span class="daily-basis-total-val">{{ $totalActions }}</span>
        </div>
    </div>

    @foreach($groupedColumns as $groupKey => $columns)
        @php $meta = $groupMeta[$groupKey] ?? ['title' => ucfirst((string) $groupKey), 'icon' => 'insights', 'hint' => '']; @endphp
        <div class="daily-basis-group">
            @include('adminmodule::partials._employee-progress-section-label', [
                'label' => $meta['title'],
                'helpKey' => 'daily_basis_'.$groupKey,
            ])
            @if(! empty($meta['hint']))
                <p class="section-sub">{{ $meta['hint'] }}</p>
            @endif

            <div class="metric-grid daily-basis-grid">
                @foreach($columns as $column)
                    @php
                        $value = (int) ($activityTotals[$column['key']] ?? 0);
                        $cardTone = $toneMap[$column['tone'] ?? ''] ?? '';
                    @endphp
                    <div class="metric-card {{ $cardTone }} {{ $value === 0 ? 'is-zero' : '' }}">
                        <div class="mc-top">
                            <div>
                                <div class="mc-lbl">
                                    <span>{{ $column['label'] }}</span>
                                    @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => 'activity_'.$column['key'], 'size' => 'xs'])
                                </div>
                                <div class="mc-val">{{ $value }}</div>
                            </div>
                            <div class="mc-icon">@include('adminmodule::partials._material-icon', ['name' => $column['icon'] ?? 'insights'])</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    @if($tab === 'monthly')
        <div class="daily-basis-group">
            @include('adminmodule::partials._employee-progress-section-label', [
                'label' => translate('Day_wise_breakdown') ?? 'Day-wise breakdown',
                'helpKey' => 'daily_basis_day_table',
            ])
            <p class="section-sub">{{ translate('Progress_daily_basis_table_sub') ?? 'Each row is a metric; each column is one day' }}</p>

            <div class="data-table-wrap daily-basis-table-wrap">
                @if($activityDailyRows === [])
                    <div class="daily-basis-empty">{{ translate('No_activity_in_period') ?? 'No activity in this period' }}</div>
                @else
                    <table class="data-table daily-basis-table">
                        <thead>
                            <tr>
                                <th class="col-metric">{{ translate('Metric') ?? 'Metric' }}</th>
                                @foreach($activityDailyRows as $row)
                                    <th class="col-day">
                                        <a href="{{ route('admin.my-progress', array_merge($employeeQuery ?? [], [
                                            'tab' => 'daily',
                                            'section' => 'daily-basis',
                                            'date' => $row['date'],
                                        ])) }}" class="daily-basis-date-link" title="{{ $row['date_label'] }}">{{ $row['date_label'] }}</a>
                                    </th>
                                @endforeach
                                <th class="col-total">{{ translate('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activityMetricColumns as $column)
                                <tr>
                                    <th class="col-metric" scope="row">{{ $column['label'] }}</th>
                                    @foreach($activityDailyRows as $row)
                                        @php $val = (int) ($row[$column['key']] ?? 0); @endphp
                                        <td class="col-day {{ $val === 0 ? 'is-zero' : '' }}">{{ $val }}</td>
                                    @endforeach
                                    <td class="col-total">{{ (int) ($activityTotals[$column['key']] ?? 0) }}</td>
                                </tr>
                            @endforeach
                            <tr class="daily-basis-total-row">
                                <th class="col-metric" scope="row">{{ translate('Online') }}</th>
                                @foreach($activityDailyRows as $row)
                                    <td class="col-day">{{ $row['online_hours'] ?? '—' }}</td>
                                @endforeach
                                <td class="col-total">{{ $activityTotals['online_hours'] ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endif
</div>
