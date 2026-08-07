@php
    $analytics = $analytics ?? [];
    $charts = $analytics['charts'] ?? [];
    $kpis = $analytics['kpis'] ?? [];
    $topPerformers = $analytics['top_performers'] ?? [];
    $insights = $analytics['insights'] ?? [];
    $summary = $analytics['summary'] ?? [];
    $leaderboard = $fullReport['leaderboard'] ?? [];
    $toneMap = ['good' => 'success', 'warning' => 'warning', 'warn' => 'warning', 'danger' => 'danger'];
@endphp

@if($kpis !== [])
    <div class="metric-grid">
        @foreach($kpis as $index => $kpi)
            @php
                $cardTone = $toneMap[$kpi['tone'] ?? 'brand'] ?? '';
                $sparkColor = match ($kpi['tone'] ?? 'brand') {
                    'good' => '#059669',
                    'danger' => '#dc2626',
                    'warning', 'warn' => '#d97706',
                    default => '#43466e',
                };
            @endphp
            <div class="metric-card {{ $cardTone }}">
                <div class="mc-top">
                    <div>
                        <div class="mc-lbl">{{ $kpi['label'] }}</div>
                        <div class="mc-val">{{ $kpi['value'] }}</div>
                    </div>
                    <div class="mc-icon">
                        @include('adminmodule::partials._material-icon', ['name' => $kpi['icon'] ?? 'insights'])
                    </div>
                </div>
                <div class="mc-foot">
                    <span class="trend flat">{{ $kpi['footer'] ?? '' }}</span>
                    <div class="mc-spark" id="progress_spark_{{ $index }}" data-color="{{ $sparkColor }}"></div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="layout-main">
    <div class="chart-card">
        <div class="chart-card-head">
            <div>
                <h3>
                    @include('adminmodule::partials._material-icon', ['name' => 'show_chart'])
                    {{ translate('Daily_activity_breakdown') }}
                </h3>
                <p>{{ translate('Bookings_created') }} · {{ translate('Leads_added') }} · {{ translate('Follow_ups') }}</p>
            </div>
        </div>
        <div class="chart-card-body">
            <div id="progress_analytics_activity_chart" class="chart-h"></div>
        </div>
    </div>

    <div class="side-stack">
        @if(! empty($viewingAllEmployees) && $topPerformers !== [])
            <div class="rank-card">
                <div class="rank-head">{{ translate('Progress_team_ranking') }}</div>
                @foreach($topPerformers as $index => $performer)
                    @php
                        $initials = collect(explode(' ', $performer['name']))->filter()->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
                        $avatarClass = match ($index) {
                            1 => 'silver',
                            2 => 'bronze',
                            default => '',
                        };
                        $maxScore = max(1, (int) ($topPerformers[0]['score'] ?? 1));
                        $barWidth = round(((int) $performer['score'] / $maxScore) * 100);
                    @endphp
                    <div class="rank-item">
                        <div class="avatar {{ $avatarClass }}">{{ $initials ?: '—' }}</div>
                        <div class="rank-meta">
                            <div class="rank-name">{{ $performer['name'] }}</div>
                            <div class="rank-sub">
                                {{ $performer['bookings'] }} {{ translate('Bookings_created_short') ?? translate('Bookings_created') }}
                                · {{ $performer['followups'] }} {{ translate('Follow_ups') }}
                            </div>
                            <div class="rank-bar"><i style="width: {{ $barWidth }}%"></i></div>
                        </div>
                        <div class="rank-val">{{ $performer['score'] }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rank-card">
                <div class="rank-head">{{ translate('Progress_team_ranking') }}</div>
                @if(($leaderboard['total_employees'] ?? 1) > 1 && ($leaderboard['overall_rank'] ?? 0) > 0)
                    <div class="rank-item">
                        <div class="avatar">#{{ $leaderboard['overall_rank'] }}</div>
                        <div class="rank-meta">
                            <div class="rank-name">{{ translate('Progress_overall_team_rank') }}</div>
                            <div class="rank-sub">
                                {{ translate('Progress_out_of') }} {{ $leaderboard['total_employees'] }} {{ translate('Progress_employees') }}
                            </div>
                            <div class="rank-bar"><i style="width: {{ min(100, round((($leaderboard['total_employees'] - $leaderboard['overall_rank'] + 1) / max(1, $leaderboard['total_employees'])) * 100)) }}%"></i></div>
                        </div>
                        <div class="rank-val">#{{ $leaderboard['overall_rank'] }}</div>
                    </div>
                @else
                    <div class="rank-item">
                        <div class="rank-meta">
                            <div class="rank-name">{{ translate('Progress_solo_team') }}</div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="chart-card">
                <div class="chart-card-head"><strong>{{ translate('Booking_report_summary') ?? translate('Business_Insights_Summary') }}</strong></div>
                <div class="chart-card-body">
                    <div id="progress_analytics_outcome_chart" class="chart-m"></div>
                </div>
            </div>
        @endif

        @if($insights !== [])
            <div class="insight-list">
                @foreach($insights as $insight)
                    @php
                        $insightClass = match ($insight['priority'] ?? 'low') {
                            'high' => 'danger',
                            'medium' => 'warning',
                            default => 'success',
                        };
                        $insightIcon = match ($insight['priority'] ?? 'low') {
                            'high' => 'error',
                            'medium' => 'warning',
                            default => 'check_circle',
                        };
                    @endphp
                    <div class="insight-item {{ $insightClass }}">
                        @include('adminmodule::partials._material-icon', ['name' => $insightIcon, 'class' => 'mso'])
                        <div>
                            <strong>{{ $insight['title'] }}</strong>
                            @if(! empty($insight['detail']))
                                — {{ $insight['detail'] }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="grid-2">
    <div class="chart-card">
        <div class="chart-card-head">
            <div>
                <h3>{{ translate('Progress_activity_metrics') }}</h3>
                <p>{{ translate('Leads_added') }} → {{ translate('Bookings_created') }} → {{ translate('Bookings_completed') }}</p>
            </div>
        </div>
        <div class="chart-card-body">
            <div id="progress_analytics_funnel_chart" class="chart-m"></div>
        </div>
    </div>
    <div class="chart-card">
        <div class="chart-card-head">
            <div>
                <h3>{{ translate('Booking_report_summary') ?? translate('Operations') }}</h3>
                <p>{{ translate('completed') }} / {{ translate('Pending') }} / {{ translate('Cancelled') }}</p>
            </div>
        </div>
        <div class="chart-card-body">
            <div id="progress_analytics_mix_chart" class="chart-m"></div>
        </div>
    </div>
</div>

@push('script')
<script src="{{ asset('assets/admin-module/plugins/apex/apexcharts.min.js') }}"></script>
<script>
(function () {
    if (typeof ApexCharts === 'undefined') return;

    var charts = @json($charts);
    var kpis = @json($kpis);
    var brand = '#43466e';
    var sparkColors = { brand: brand, good: '#059669', warning: '#d97706', danger: '#dc2626' };

    kpis.forEach(function (kpi, index) {
        var el = document.querySelector('#progress_spark_' + index);
        if (!el || !kpi.spark || !kpi.spark.length) return;
        var color = el.getAttribute('data-color') || brand;
        new ApexCharts(el, {
            series: [{ data: kpi.spark }],
            chart: { type: 'area', height: 36, sparkline: { enabled: true }, fontFamily: 'Outfit,sans-serif' },
            stroke: { width: 2, curve: 'smooth' },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
            colors: [color]
        }).render();
    });

    if (document.querySelector('#progress_analytics_activity_chart') && charts.activity_categories) {
        new ApexCharts(document.querySelector('#progress_analytics_activity_chart'), {
            chart: { type: 'line', height: 320, toolbar: { show: false }, fontFamily: 'Outfit,sans-serif' },
            colors: [brand, '#059669', '#d97706'],
            stroke: { width: [3, 3, 2], curve: 'smooth' },
            series: [
                { name: @json(translate('Bookings_created')), data: charts.bookings_series || [] },
                { name: @json(translate('Leads_added')), data: charts.leads_series || [] },
                { name: @json(translate('Follow_ups')), data: charts.followups_series || [] }
            ],
            xaxis: { categories: charts.activity_categories || [], labels: { style: { fontSize: '11px', fontWeight: 600 } } },
            legend: { position: 'top', horizontalAlign: 'right', fontSize: '11px', fontWeight: 600 },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4, padding: { left: 8, right: 8 } }
        }).render();
    }

    function donutChart(selector, series, labels) {
        var el = document.querySelector(selector);
        if (!el || !series) return;
        new ApexCharts(el, {
            chart: { type: 'donut', height: 280, fontFamily: 'Outfit,sans-serif' },
            colors: ['#059669', '#d97706', '#dc2626', '#2563eb'],
            labels: labels || [],
            series: series || [],
            legend: { position: 'bottom', fontSize: '11px', fontWeight: 600 },
            plotOptions: { pie: { donut: { size: '68%', labels: { show: true, total: { show: true, label: @json(translate('Total')), fontSize: '11px' } } } } }
        }).render();
    }

    donutChart('#progress_analytics_outcome_chart', charts.outcome_series, charts.outcome_labels);
    donutChart('#progress_analytics_mix_chart', charts.outcome_series, charts.outcome_labels);

    var funnelEl = document.querySelector('#progress_analytics_funnel_chart');
    if (funnelEl && charts.funnel_series) {
        new ApexCharts(funnelEl, {
            series: [{ name: @json(translate('Total')), data: charts.funnel_series || [] }],
            chart: { type: 'bar', height: 280, fontFamily: 'Outfit,sans-serif', toolbar: { show: false } },
            colors: [brand, '#059669', '#2563eb'],
            plotOptions: { bar: { horizontal: true, distributed: true, barHeight: '72%', borderRadius: 6, dataLabels: { position: 'center' } } },
            dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 700, colors: ['#fff'] } },
            xaxis: { categories: charts.funnel_categories || [] },
            legend: { show: false },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
        }).render();
    }
})();
</script>
@endpush
