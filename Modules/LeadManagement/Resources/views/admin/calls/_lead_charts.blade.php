@php
    $leadCharts = $leadCharts ?? [];
    $leadFilters = $leadCharts['filters'] ?? ['period' => 'overall', 'category' => null, 'service' => null, 'zone' => null];
    $leadOptions = $leadCharts['options'] ?? ['categories' => [], 'services' => [], 'zones' => []];
    $periodQuery = request()->except('lead_period');
@endphp
<div class="card calls-report-card mb-3">
    <div class="card-body">
        <div class="calls-report-head">
            <div>
                <h2 class="calls-report-title">{{ translate('Superfone_leads') }}</h2>
                <span class="text-muted" style="font-size: 0.72rem;">{{ translate('Source') }} · {{ \Modules\LeadManagement\Entities\Source::NAME_SUPERFONE }} · {{ $leadCharts['total'] ?? 0 }}</span>
            </div>
            <div class="calls-report-tabs" role="tablist">
                @foreach(['today' => translate('Today'), 'overall' => translate('Overall')] as $periodKey => $periodLabel)
                    <a href="{{ route('admin.calls.report', array_merge($periodQuery, ['lead_period' => $periodKey])) }}"
                       class="{{ ($leadFilters['period'] ?? 'overall') === $periodKey ? 'is-active' : '' }}"
                       style="text-decoration: none;">{{ $periodLabel }}</a>
                @endforeach
            </div>
        </div>
        <p class="calls-report-note" style="margin-top: 0;">{{ translate('Superfone_leads_hint') }}</p>
        <form method="get" action="{{ route('admin.calls.report') }}" class="calls-lead-filters">
            <input type="hidden" name="lead_period" value="{{ $leadFilters['period'] ?? 'overall' }}">
            <label>
                {{ translate('Category') }}
                <select name="lead_category" class="form-select form-select-sm">
                    <option value="">{{ translate('All') }}</option>
                    @foreach($leadOptions['categories'] as $option)
                        <option value="{{ $option['id'] }}" @selected(($leadFilters['category'] ?? null) === $option['id'])>{{ $option['name'] }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                {{ translate('Service') }}
                <select name="lead_service" class="form-select form-select-sm">
                    <option value="">{{ translate('All') }}</option>
                    @foreach($leadOptions['services'] as $option)
                        <option value="{{ $option['id'] }}" @selected(($leadFilters['service'] ?? null) === $option['id'])>{{ $option['name'] }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                {{ translate('Zone') }}
                <select name="lead_zone" class="form-select form-select-sm">
                    <option value="">{{ translate('All') }}</option>
                    @foreach($leadOptions['zones'] as $option)
                        <option value="{{ $option['id'] }}" @selected(($leadFilters['zone'] ?? null) === $option['id'])>{{ $option['name'] }}</option>
                    @endforeach
                </select>
            </label>
            <div class="calls-lead-actions">
                <button type="submit" class="btn btn-sm btn--primary">{{ translate('Filter') }}</button>
                <a class="calls-lead-reset" href="{{ route('admin.calls.report') }}">{{ translate('Reset') }}</a>
            </div>
        </form>
        <div class="calls-lead-grid">
            @foreach([
                'types' => translate('Lead_type'),
                'customer' => translate('Customer'),
                'provider' => translate('Provider'),
                'bookings' => translate('Bookings'),
            ] as $chartKey => $chartTitle)
                <div class="calls-lead-panel">
                    <h3>{{ $chartTitle }}</h3>
                    <div class="calls-lead-pills">
                        @foreach($leadCharts[$chartKey]['summary'] ?? [] as $item)
                            <span><b>{{ $item['count'] }}</b> {{ $item['label'] }}</span>
                        @endforeach
                    </div>
                    <div id="calls-lead-chart-{{ $chartKey }}" class="calls-lead-chart"></div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card calls-report-card mb-3">
    <div class="card-body">
        <div class="calls-report-head">
            <h2 class="calls-report-title">{{ translate('Category') }} · {{ translate('Service') }} · {{ translate('Zone') }}</h2>
            <div class="calls-report-tabs" role="tablist">
                @foreach(['category' => translate('Category'), 'service' => translate('Service'), 'zone' => translate('Zone')] as $tabKey => $tabLabel)
                    <button type="button" class="{{ $tabKey === 'category' ? 'is-active' : '' }}" data-lead-break="{{ $tabKey }}">{{ $tabLabel }}</button>
                @endforeach
            </div>
        </div>
        <p class="calls-report-note" style="margin-top: 0;">{{ translate('Superfone_leads_breakdown_hint') }}</p>
        <div id="calls-lead-breakdown" class="calls-lead-breakdown"></div>
    </div>
</div>

@push('script')
    <script>
        (function () {
            var data = @json($leadCharts);
            var emptyText = @json(translate('No_data_found'));

            function sum(values) {
                return (values || []).reduce(function (total, value) { return total + Number(value || 0); }, 0);
            }

            function showEmpty(el) {
                if (!el) return;
                el.innerHTML = '<div class="calls-report-empty">' + emptyText + '</div>';
            }

            ['types', 'customer', 'provider', 'bookings'].forEach(function (key) {
                var el = document.getElementById('calls-lead-chart-' + key);
                var block = data[key] || {};
                if (!el) return;
                if (!block.series || !block.series.length || !sum(block.series)) {
                    showEmpty(el);
                    return;
                }
                new ApexCharts(el, {
                    series: block.series,
                    labels: block.labels,
                    colors: block.colors,
                    chart: { type: 'donut', height: 250, fontFamily: 'inherit', toolbar: { show: false } },
                    legend: {
                        position: 'bottom',
                        fontSize: '11px',
                        formatter: function (name, opts) {
                            return name + ' (' + opts.w.globals.series[opts.seriesIndex] + ')';
                        }
                    },
                    dataLabels: { enabled: false },
                    stroke: { width: 2, colors: ['#fff'] },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '64%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: @json(translate('Total')),
                                        fontSize: '11px',
                                        formatter: function (w) { return sum(w.globals.series); }
                                    }
                                }
                            }
                        }
                    }
                }).render();
            });

            var breakEl = document.getElementById('calls-lead-breakdown');
            var breakChart = null;

            function drawBreak(key) {
                var block = (data.breakdown || {})[key];
                if (!breakEl || !block) return;
                var series = (block.series || []).map(function (item) { return { name: item.name, data: item.data }; });
                var colors = (block.series || []).map(function (item) { return item.color; });
                if (!block.categories.length || !series.some(function (item) { return sum(item.data); })) {
                    if (breakChart) {
                        breakChart.destroy();
                        breakChart = null;
                    }
                    showEmpty(breakEl);
                    return;
                }
                if (breakChart) {
                    breakChart.destroy();
                    breakChart = null;
                }
                breakChart = new ApexCharts(breakEl, {
                    series: series,
                    colors: colors,
                    chart: {
                        type: 'bar',
                        height: Math.max(260, block.categories.length * 42),
                        stacked: true,
                        fontFamily: 'inherit',
                        toolbar: { show: false }
                    },
                    plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '68%' } },
                    dataLabels: { enabled: false },
                    xaxis: { categories: block.categories, labels: { style: { fontSize: '11px' } } },
                    yaxis: { labels: { style: { fontSize: '11px' }, maxWidth: 160 } },
                    legend: { position: 'top', fontSize: '11px' },
                    grid: { strokeDashArray: 4, borderColor: '#eef0f3' }
                });
                breakChart.render();
            }

            document.querySelectorAll('[data-lead-break]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var key = button.getAttribute('data-lead-break');
                    document.querySelectorAll('[data-lead-break]').forEach(function (item) {
                        item.classList.toggle('is-active', item === button);
                    });
                    drawBreak(key);
                });
            });

            drawBreak('category');
        })();
    </script>
@endpush
