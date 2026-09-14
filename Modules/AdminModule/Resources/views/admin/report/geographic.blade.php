@extends('adminmodule::layouts.new-master')

@section('title', translate('Zone_and_Area_Reports'))

@push('css_or_js')
    <style>
        .geo-report-chart-card { background: #fafbfc; min-height: 100%; }
        .geo-report-donut { height: 220px; min-height: 220px; overflow: hidden; }
        .geo-report-donut .apexcharts-canvas,
        .geo-report-donut .apexcharts-inner { overflow: hidden; }
        .geo-report-legend {
            max-height: 132px;
            overflow-y: auto;
            display: flex;
            flex-wrap: wrap;
            gap: 6px 12px;
            padding-top: 8px;
        }
        .geo-report-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #5a5c69;
            line-height: 1.3;
        }
        .geo-report-legend-swatch {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .geo-report-share-scroll,
        .geo-report-stack-scroll {
            max-height: 380px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .geo-report-table-scroll {
            max-height: 420px;
            overflow: auto;
        }
        .geo-report-table-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f8f9fa;
            box-shadow: 0 1px 0 #e9ecef;
        }
        .geo-report-stack-scroll .apexcharts-legend {
            max-height: 96px !important;
            overflow-y: auto !important;
        }
        .report-filter-offcanvas { display: flex; flex-direction: column; }
        .report-filter-offcanvas .report-filter-form-flex { flex: 1; display: flex; flex-direction: column; min-height: 0; }
        .report-filter-offcanvas .report-filter-body { flex: 1; min-height: 0; }
        .report-filter-offcanvas .report-filter-footer { flex-shrink: 0; }
        .geo-rec-opportunity { border-left: 4px solid #4e73df; }
        .geo-rec-grow { border-left: 4px solid #1cc88a; }
        .geo-rec-risk { border-left: 4px solid #e74a3b; }
        .geo-rec-capture { border-left: 4px solid #f6c23e; }
    </style>
@endpush

@section('content')
    @php
        $summary = $report['summary'] ?? [];
        $geoLabel = $view === 'zone' ? translate('Zone') : translate('Area');
        $filtersAppliedCount = 0;
        if (request()->filled('date_from')) { $filtersAppliedCount++; }
        if (request()->filled('date_to')) { $filtersAppliedCount++; }
        if (!empty($selectedZoneIds)) { $filtersAppliedCount++; }
        if (!empty($selectedAreaIds)) { $filtersAppliedCount++; }
        if (!empty($selectedCategoryIds)) { $filtersAppliedCount++; }
    @endphp

    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-2">
                <div>
                    <h2 class="page-title mb-1">{{ translate('Zone_and_Area_Reports') }}</h2>
                    <p class="text-muted fz-12 mb-0">{{ translate('Zone_and_Area_Reports_help') }}</p>
                </div>
                <button type="button"
                        class="btn btn-outline-primary d-inline-flex align-items-center gap-2 position-relative"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#geoReportFilterDrawer"
                        aria-controls="geoReportFilterDrawer">
                    <span class="material-icons">filter_list</span>
                    {{ translate('Filter') }}
                    @if($filtersAppliedCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $filtersAppliedCount }}</span>
                    @endif
                </button>
            </div>

            <div class="offcanvas offcanvas-end report-filter-offcanvas" tabindex="-1" id="geoReportFilterDrawer" style="width: 560px; max-width: 95vw;">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title">{{ translate('Search_Data') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
                </div>
                <form action="{{ route('admin.report.geographic') }}" method="GET" class="report-filter-form-flex">
                    <input type="hidden" name="view" value="{{ $view }}">
                    <div class="offcanvas-body pt-3 overflow-auto flex-grow-1 report-filter-body">
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <label class="form-label">{{ translate('From_Date') }}</label>
                                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                            </div>
                            <div>
                                <label class="form-label">{{ translate('To_Date') }}</label>
                                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                            </div>
                            <div>
                                <label class="form-label">{{ translate('Zone') }}</label>
                                <select name="zone_ids[]" class="js-select form-select" multiple>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->id }}" {{ in_array((string) $zone->id, array_map('strval', $selectedZoneIds), true) ? 'selected' : '' }}>{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">{{ translate('Area') }}</label>
                                <select name="area_ids[]" class="js-select form-select" multiple>
                                    @foreach($areas as $area)
                                        <option value="{{ $area->id }}" {{ in_array((string) $area->id, array_map('strval', $selectedAreaIds), true) ? 'selected' : '' }}>{{ $area->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">{{ translate('Category') }}</label>
                                <select name="category_ids[]" class="js-select form-select" multiple>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ in_array((string) $category->id, array_map('strval', $selectedCategoryIds), true) ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="report-filter-footer border-top bg-body p-3 flex-shrink-0">
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.report.geographic', ['view' => $view]) }}" class="btn btn--secondary flex-grow-1">{{ translate('Reset') }}</a>
                            <button type="submit" class="btn btn--primary flex-grow-1">{{ translate('Filter') }}</button>
                        </div>
                    </div>
                </form>
            </div>

            <ul class="nav nav--tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ $view === 'area' ? 'active' : '' }}"
                       href="{{ route('admin.report.geographic', array_merge($queryParams, ['view' => 'area'])) }}">
                        {{ translate('Area_Wise') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $view === 'zone' ? 'active' : '' }}"
                       href="{{ route('admin.report.geographic', array_merge($queryParams, ['view' => 'zone'])) }}">
                        {{ translate('Zone_Wise') }}
                    </a>
                </li>
            </ul>

            <div class="row g-3 mb-3">
                <div class="col-lg-3 col-sm-6">
                    <div class="card h-100 border-start border-4 border-primary">
                        <div class="card-body py-3">
                            <span class="fz-12 text-muted">{{ translate('Total_Leads_in_Range') }}</span>
                            <h3 class="mb-0 mt-1">{{ $summary['leads'] ?? 0 }}</h3>
                            <span class="fz-12">{{ translate('Unknown') }}: {{ $summary['unknown'] ?? 0 }} · {{ translate('Invalid') }}: {{ $summary['invalid'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card h-100 border-start border-4 border-warning">
                        <div class="card-body py-3">
                            <span class="fz-12 text-muted">{{ translate('Pending') }} / {{ translate('Hold') }}</span>
                            <h3 class="mb-0 mt-1">{{ ($summary['pending'] ?? 0) + ($summary['hold'] ?? 0) }}</h3>
                            <span class="fz-12">{{ translate('Pending') }}: {{ $summary['pending'] ?? 0 }} · {{ translate('Hold') }}: {{ $summary['hold'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card h-100 border-start border-4 border-success">
                        <div class="card-body py-3">
                            <span class="fz-12 text-muted">{{ translate('Booked') }} / {{ translate('Bookings') }}</span>
                            <h3 class="mb-0 mt-1">{{ $summary['booked'] ?? 0 }} / {{ $summary['bookings'] ?? 0 }}</h3>
                            <span class="fz-12">{{ translate('conversion') }}: {{ $summary['lead_conversion_rate'] ?? 0 }}%</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card h-100 border-start border-4 border-danger">
                        <div class="card-body py-3">
                            <span class="fz-12 text-muted">{{ translate('Cancelled') }}</span>
                            <h3 class="mb-0 mt-1">{{ ($summary['cancelled_leads'] ?? 0) }} / {{ $summary['booking_cancelled'] ?? 0 }}</h3>
                            <span class="fz-12">{{ translate('Leads') }} / {{ translate('Bookings') }} · {{ translate('completed') }}: {{ $summary['booking_completed'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($report['insights']))
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body">
                        <p class="fw-semibold mb-2">{{ translate('Business_Insights_Summary') }}</p>
                        @foreach($report['insights'] as $insight)
                            <p class="mb-1 fz-13 {{ ($insight['type'] ?? '') === 'warning' ? 'text-warning' : ((($insight['type'] ?? '') === 'success') ? 'text-success' : 'text-muted') }}">
                                {{ $insight['text'] }}
                            </p>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <p class="fw-semibold mb-1">{{ translate('Overview') }}</p>
                    <p class="text-muted fz-12 mb-3">{{ translate('Geographic_charts_overview_help') }}</p>
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="card geo-report-chart-card border">
                                <div class="card-body">
                                    <div class="fz-12 text-muted mb-2">{{ translate('Lead_types') }}</div>
                                    <div id="geo-lead-type-chart" class="geo-report-donut"></div>
                                    <div id="geo-lead-type-legend" class="geo-report-legend"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card geo-report-chart-card border">
                                <div class="card-body">
                                    <div class="fz-12 text-muted mb-2">{{ translate('Customer_Lead_Status') }}</div>
                                    <div id="geo-customer-status-chart" class="geo-report-donut"></div>
                                    <div id="geo-customer-status-legend" class="geo-report-legend"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card geo-report-chart-card border">
                                <div class="card-body">
                                    <div class="fz-12 text-muted mb-2">{{ translate('Booking_status') }}</div>
                                    <div id="geo-booking-status-chart" class="geo-report-donut"></div>
                                    <div id="geo-booking-status-legend" class="geo-report-legend"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <p class="fw-semibold mb-1">{{ $geoLabel }} {{ translate('Geographic_share_title') }}</p>
                    <p class="text-muted fz-12 mb-3">{{ translate('Geographic_share_help') }}</p>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="card geo-report-chart-card border">
                                <div class="card-body">
                                    <div class="fz-12 text-muted mb-2">{{ translate('Leads') }}</div>
                                    <div class="geo-report-share-scroll">
                                        <div id="geo-lead-share-chart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card geo-report-chart-card border">
                                <div class="card-body">
                                    <div class="fz-12 text-muted mb-2">{{ translate('Bookings') }}</div>
                                    <div class="geo-report-share-scroll">
                                        <div id="geo-booking-share-chart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <p class="fw-semibold mb-1">{{ translate('Date_Wise') }}</p>
                    <p class="text-muted fz-12 mb-3">{{ translate('Geographic_date_wise_help') }}</p>
                    <div id="geo-daily-bar"></div>
                    @if(!empty($splitDailyByGeo))
                        <div class="row g-3 mt-1">
                            <div class="col-lg-6">
                                <div class="fz-12 text-muted mb-2">{{ str_replace(':geo', $geoLabel, translate('Geographic_date_wise_leads_by_geo')) }}</div>
                                <div class="geo-report-stack-scroll">
                                    <div id="geo-daily-leads-by-geo"></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="fz-12 text-muted mb-2">{{ str_replace(':geo', $geoLabel, translate('Geographic_date_wise_bookings_by_geo')) }}</div>
                                <div class="geo-report-stack-scroll">
                                    <div id="geo-daily-bookings-by-geo"></div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <p class="fw-semibold mb-1">{{ $geoLabel }} {{ translate('Geographic_performance_title') }}</p>
                    <p class="text-muted fz-12 mb-3">{{ translate('Geographic_table_help') }}</p>
                    <div class="table-responsive geo-report-table-scroll">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>{{ $geoLabel }}</th>
                                <th class="text-end">{{ translate('Leads') }}</th>
                                <th class="text-end">{{ translate('Unknown') }}</th>
                                <th class="text-end">{{ translate('Pending') }}</th>
                                <th class="text-end">{{ translate('Hold') }}</th>
                                <th class="text-end">{{ translate('Booked') }}</th>
                                <th class="text-end">{{ translate('Invalid') }}</th>
                                <th class="text-end">{{ translate('Cancelled') }}</th>
                                <th class="text-end">{{ translate('Bookings') }}</th>
                                <th class="text-end">{{ translate('completed') }}</th>
                                <th class="text-end">{{ translate('Canceled') }}</th>
                                <th class="text-end">{{ translate('conversion') }} %</th>
                                <th class="text-end">{{ translate('completion_rate') }} %</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($geo['rows'] ?? [] as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-end">{{ $row['leads'] }}</td>
                                    <td class="text-end">{{ $row['unknown'] }}</td>
                                    <td class="text-end">{{ $row['pending'] }}</td>
                                    <td class="text-end">{{ $row['hold'] }}</td>
                                    <td class="text-end">{{ $row['booked'] }}</td>
                                    <td class="text-end">{{ $row['invalid'] }}</td>
                                    <td class="text-end">{{ $row['cancelled'] }}</td>
                                    <td class="text-end">{{ $row['bookings'] }}</td>
                                    <td class="text-end">{{ $row['booking_completed'] }}</td>
                                    <td class="text-end">{{ $row['booking_cancelled'] }}</td>
                                    <td class="text-end">{{ $row['lead_conversion_rate'] }}</td>
                                    <td class="text-end">{{ $row['booking_completion_rate'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="text-center text-muted py-4">{{ translate('No_data_available') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <p class="fw-semibold mb-1">{{ $geoLabel }} × {{ translate('Category') }}</p>
                    <p class="text-muted fz-12 mb-3">{{ translate('Geographic_category_matrix_help') }}</p>
                    <div class="geo-report-share-scroll mb-3">
                        <div id="geo-category-bar"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>{{ $geoLabel }}</th>
                                <th>{{ translate('Category') }}</th>
                                <th class="text-end">{{ translate('Leads') }}</th>
                                <th class="text-end">{{ translate('Booked') }}</th>
                                <th class="text-end">{{ translate('Bookings') }}</th>
                                <th class="text-end">{{ translate('completed') }}</th>
                                <th class="text-end">{{ translate('Canceled') }}</th>
                                <th class="text-end">{{ translate('conversion') }} %</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($geo['matrix'] ?? [] as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['category_label'] ?? '—' }}</td>
                                    <td class="text-end">{{ $row['leads'] }}</td>
                                    <td class="text-end">{{ $row['booked'] }}</td>
                                    <td class="text-end">{{ $row['bookings'] }}</td>
                                    <td class="text-end">{{ $row['booking_completed'] }}</td>
                                    <td class="text-end">{{ $row['booking_cancelled'] }}</td>
                                    <td class="text-end">{{ $row['lead_conversion_rate'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">{{ translate('No_data_available') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <p class="fw-semibold mb-1">{{ translate('Where_to_target') }}</p>
                    <p class="text-muted fz-12 mb-3">{{ translate('Geographic_targeting_help') }}</p>
                    @forelse($geo['targeting'] ?? [] as $row)
                        <div class="p-3 mb-2 bg-light rounded geo-rec-{{ $row['recommendation']['type'] ?? 'opportunity' }}">
                            <div class="fw-semibold fz-13">{{ $row['label'] }} · {{ $row['category_label'] }}</div>
                            <div class="fz-13">{{ $row['recommendation']['text'] }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ translate('Geographic_targeting_empty') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ asset('assets/admin-module/plugins/apex/apexcharts.min.js') }}"></script>
    <script>
        (function () {
            var report = {!! json_encode($report) !!};
            var geo = {!! json_encode($geo) !!};
            var noData = @json(translate('Data_not_available'));
            var palette = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#fd7e14', '#6f42c1', '#20c997', '#0dcaf0', '#d63384', '#5a5c69'];
            var initFlag = document.getElementById('geo-lead-type-chart');
            if (initFlag && initFlag.getAttribute('data-geo-charts-bound') === '1') {
                return;
            }
            if (initFlag) {
                initFlag.setAttribute('data-geo-charts-bound', '1');
            }

            function sum(values) {
                return (values || []).reduce(function (a, b) { return a + (b || 0); }, 0);
            }

            function showEmpty(el) {
                if (!el) return;
                el.innerHTML = '<div class="text-muted text-center py-5 fz-12">' + noData + '</div>';
            }

            function bindChart(el, options) {
                if (!el) return;
                if (el._pkChart) {
                    try { el._pkChart.destroy(); } catch (e) {}
                    el._pkChart = null;
                }
                el.innerHTML = '';
                var chart = new ApexCharts(el, options);
                el._pkChart = chart;
                chart.render();
            }

            function fillLegend(legendEl, rows) {
                if (!legendEl) return;
                legendEl.innerHTML = '';
                (rows || []).forEach(function (r, i) {
                    var item = document.createElement('span');
                    item.className = 'geo-report-legend-item';
                    var swatch = document.createElement('span');
                    swatch.className = 'geo-report-legend-swatch';
                    swatch.style.background = r.color || palette[i % palette.length];
                    item.appendChild(swatch);
                    item.appendChild(document.createTextNode((r.label || '—') + ' (' + r.total + ')'));
                    legendEl.appendChild(item);
                });
            }

            function pieOptions(type, rows, centerLabel) {
                var values = rows.map(function (r) { return r.total; });
                var labels = rows.map(function (r) { return r.label || '—'; });
                var colors = rows.map(function (r, i) { return r.color || palette[i % palette.length]; });
                var options = {
                    series: values,
                    chart: { type: type, height: 220, width: '100%', fontFamily: 'inherit', toolbar: { show: false } },
                    labels: labels,
                    colors: colors,
                    legend: { show: false },
                    dataLabels: { enabled: false },
                    stroke: { width: 2, colors: ['#fff'] },
                    tooltip: {
                        y: {
                            formatter: function (val) { return val; }
                        }
                    },
                    plotOptions: {
                        pie: {
                            startAngle: 0,
                            endAngle: 360,
                            expandOnClick: false,
                            offsetY: 0,
                            customScale: 0.92
                        }
                    }
                };
                if (type === 'donut') {
                    options.plotOptions.pie.donut = {
                        size: '62%',
                        labels: {
                            show: true,
                            name: { fontSize: '11px' },
                            value: { fontSize: '18px', fontWeight: 600 },
                            total: {
                                show: true,
                                label: centerLabel,
                                fontSize: '11px',
                                formatter: function () { return String(sum(values)); }
                            }
                        }
                    };
                }
                return options;
            }

            function renderDonut(el, legendEl, rows, centerLabel) {
                if (!el) return;
                rows = (rows || []).filter(function (r) { return (r.total || 0) > 0; });
                if (!rows.length) {
                    showEmpty(el);
                    if (legendEl) legendEl.innerHTML = '';
                    return;
                }
                bindChart(el, pieOptions('donut', rows, centerLabel));
                fillLegend(legendEl, rows);
            }

            function renderPie(el, legendEl, rows) {
                if (!el) return;
                rows = (rows || []).filter(function (r) { return (r.total || 0) > 0; });
                if (!rows.length) {
                    showEmpty(el);
                    if (legendEl) legendEl.innerHTML = '';
                    return;
                }
                bindChart(el, pieOptions('pie', rows, ''));
                fillLegend(legendEl, rows);
            }

            function renderShareBar(el, rows, seriesName, color) {
                if (!el) return;
                rows = (rows || []).filter(function (r) { return (r.total || 0) > 0; });
                if (!rows.length) {
                    showEmpty(el);
                    return;
                }
                bindChart(el, {
                    chart: { type: 'bar', height: Math.max(220, rows.length * 32), fontFamily: 'inherit', toolbar: { show: false } },
                    series: [{ name: seriesName, data: rows.map(function (r) { return r.total; }) }],
                    xaxis: { categories: rows.map(function (r) { return r.label || '—'; }) },
                    yaxis: { labels: { maxWidth: 180, trim: false, style: { fontSize: '11px' } } },
                    colors: [color],
                    dataLabels: { enabled: true },
                    grid: { padding: { left: 8, right: 16 } },
                    plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '62%' } },
                    legend: { show: false }
                });
            }

            renderDonut(
                document.querySelector('#geo-lead-type-chart'),
                document.querySelector('#geo-lead-type-legend'),
                report.lead_type_breakdown || [],
                @json(translate('Leads'))
            );
            renderPie(
                document.querySelector('#geo-customer-status-chart'),
                document.querySelector('#geo-customer-status-legend'),
                report.customer_status_breakdown || []
            );
            renderDonut(
                document.querySelector('#geo-booking-status-chart'),
                document.querySelector('#geo-booking-status-legend'),
                report.booking_status_breakdown || [],
                @json(translate('Bookings'))
            );
            renderShareBar(document.querySelector('#geo-lead-share-chart'), geo.lead_share || [], @json(translate('Leads')), '#4e73df');
            renderShareBar(document.querySelector('#geo-booking-share-chart'), geo.booking_share || [], @json(translate('Bookings')), '#1cc88a');

            var daily = report.daily || {};
            var dailyEl = document.querySelector('#geo-daily-bar');
            if (dailyEl) {
                var leadSeries = daily.leads || [];
                var bookingSeries = daily.bookings || [];
                if (!sum(leadSeries) && !sum(bookingSeries)) {
                    showEmpty(dailyEl);
                } else {
                    bindChart(dailyEl, {
                        chart: { type: 'bar', height: 320, stacked: false, fontFamily: 'inherit', toolbar: { show: false } },
                        series: [
                            { name: @json(translate('Leads')), data: leadSeries },
                            { name: @json(translate('Bookings')), data: bookingSeries }
                        ],
                        xaxis: { categories: daily.labels || [] },
                        colors: ['#4e73df', '#1cc88a'],
                        dataLabels: { enabled: false },
                        plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
                        legend: { position: 'top' }
                    });
                }
            }

            function renderStackedGeoDaily(el, seriesRows) {
                if (!el) return;
                seriesRows = seriesRows || [];
                var hasData = seriesRows.some(function (row) { return sum(row.data || []) > 0; });
                if (!hasData) {
                    showEmpty(el);
                    return;
                }
                bindChart(el, {
                    chart: { type: 'bar', height: 320, stacked: true, fontFamily: 'inherit', toolbar: { show: false } },
                    series: seriesRows.map(function (row) {
                        return { name: row.label || '—', data: row.data || [] };
                    }),
                    xaxis: { categories: daily.labels || [] },
                    colors: palette,
                    dataLabels: { enabled: false },
                    plotOptions: { bar: { columnWidth: '60%', borderRadius: 1 } },
                    legend: { position: 'bottom', fontSize: '11px', height: 88 }
                });
            }

            @if(!empty($splitDailyByGeo))
            var geoDaily = daily[@json($view === 'zone' ? 'by_zone' : 'by_area')] || {};
            renderStackedGeoDaily(document.querySelector('#geo-daily-leads-by-geo'), geoDaily.lead_series || []);
            renderStackedGeoDaily(document.querySelector('#geo-daily-bookings-by-geo'), geoDaily.booking_series || []);
            @endif

            var catEl = document.querySelector('#geo-category-bar');
            if (catEl) {
                var matrix = (geo.matrix || []).filter(function (r) {
                    return (r.leads || 0) + (r.bookings || 0) > 0;
                });
                if (!matrix.length) {
                    showEmpty(catEl);
                } else {
                    bindChart(catEl, {
                        chart: { type: 'bar', height: Math.max(280, matrix.length * 28), stacked: true, fontFamily: 'inherit', toolbar: { show: false } },
                        series: [
                            { name: @json(translate('Leads')), data: matrix.map(function (r) { return r.leads || 0; }) },
                            { name: @json(translate('Booked')), data: matrix.map(function (r) { return r.booked || 0; }) },
                            { name: @json(translate('completed')), data: matrix.map(function (r) { return r.booking_completed || 0; }) }
                        ],
                        xaxis: { categories: matrix.map(function (r) { return (r.label || '') + ' / ' + (r.category_label || ''); }) },
                        yaxis: { labels: { maxWidth: 220, trim: false, style: { fontSize: '11px' } } },
                        colors: ['#4e73df', '#1cc88a', '#36b9cc'],
                        dataLabels: { enabled: false },
                        plotOptions: { bar: { horizontal: true, barHeight: '70%' } },
                        legend: { position: 'top', fontSize: '11px' }
                    });
                }
            }
            function closeGeoFilterDrawer() {
                var drawerEl = document.getElementById('geoReportFilterDrawer');
                if (!drawerEl) return;
                $('#geoReportFilterDrawer .select2-hidden-accessible').each(function () {
                    var $el = $(this);
                    if ($el.data('select2')) {
                        $el.select2('close');
                    }
                });
                var bs = bootstrap.Offcanvas.getInstance(drawerEl);
                if (bs) bs.hide();
            }
            $(document).off('submit.geoReportFilter').on('submit.geoReportFilter', '#geoReportFilterDrawer form', closeGeoFilterDrawer);
            var geoDrawer = document.getElementById('geoReportFilterDrawer');
            if (geoDrawer) {
                geoDrawer.addEventListener('shown.bs.offcanvas', function () {
                    if (typeof window.initAdminPageSelect2 === 'function') {
                        window.initAdminPageSelect2(this, { force: true, includeSingle: true });
                    }
                });
            }
        })();
    </script>
@endpush
