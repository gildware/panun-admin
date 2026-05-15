@if(!empty($bookingReportAnalytics))
(function () {
    const analytics = {!! json_encode($bookingReportAnalytics) !!};
    const othersLabel = @json(translate('Others'));
    const bookingsLabel = @json(translate('Bookings'));
    const completedLabel = @json(translate('completed'));
    const noDataLabel = @json(translate('Data_not_available'));

    const palette = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796',
        '#5a5c69', '#fd7e14', '#6f42c1', '#20c997', '#0dcaf0', '#d63384',
    ];

    function sumValues(arr) {
        return (arr || []).reduce(function (s, v) { return s + (v || 0); }, 0);
    }

    function topSlices(rows, limit) {
        rows = (rows || []).filter(function (r) { return (r.total || 0) > 0; });
        if (!rows.length) {
            return { labels: [], values: [], colors: [] };
        }
        var sorted = rows.slice().sort(function (a, b) { return (b.total || 0) - (a.total || 0); });
        if (sorted.length <= limit) {
            return {
                labels: sorted.map(function (r) { return r.label || '—'; }),
                values: sorted.map(function (r) { return r.total || 0; }),
                colors: palette.slice(0, sorted.length),
            };
        }
        var top = sorted.slice(0, limit);
        var rest = sorted.slice(limit);
        var otherTotal = rest.reduce(function (s, r) { return s + (r.total || 0); }, 0);
        return {
            labels: top.map(function (r) { return r.label || '—'; }).concat([othersLabel]),
            values: top.map(function (r) { return r.total || 0; }).concat([otherTotal]),
            colors: palette.slice(0, limit).concat(['#ced4da']),
        };
    }

    function labelsWithCounts(labels, values) {
        return labels.map(function (l, i) {
            return (l || '—') + ' (' + (values[i] || 0) + ')';
        });
    }

    function showEmpty(el) {
        if (!el) return;
        el.innerHTML = '<div class=\"chart-empty-msg\">' + noDataLabel + '</div>';
    }

    function renderDonut(el, values, labels, colors, options) {
        options = options || {};
        if (!el) return;
        values = values || [];
        if (!values.length || sumValues(values) === 0) {
            showEmpty(el);
            return;
        }
        var chartLabels = options.legendWithCounts !== false
            ? labelsWithCounts(labels, values)
            : labels;

        new ApexCharts(el, {
            series: values,
            chart: {
                type: 'donut',
                height: options.height || 220,
                fontFamily: 'inherit',
            },
            labels: chartLabels,
            colors: colors || palette,
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                fontSize: '11px',
                itemMargin: { horizontal: 6, vertical: 2 },
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '62%',
                        labels: {
                            show: !!options.showCenter,
                            total: {
                                show: !!options.showCenter,
                                label: options.centerLabel || bookingsLabel,
                                fontSize: '11px',
                                formatter: function () {
                                    return String(sumValues(values));
                                },
                            },
                        },
                    },
                },
            },
            dataLabels: { enabled: false },
            stroke: { width: 1, colors: ['#fff'] },
            tooltip: {
                y: {
                    formatter: function (val, opts) {
                        var name = labels[opts.seriesIndex] || '';
                        var pct = sumValues(values) > 0
                            ? Math.round((val / sumValues(values)) * 1000) / 10
                            : 0;
                        return name + ': ' + val + ' (' + pct + '%)';
                    },
                },
            },
        }).render();
    }

    function renderAreaLine(el, categories, values, color) {
        if (!el) return;
        if (!values.length || sumValues(values) === 0) {
            showEmpty(el);
            return;
        }
        new ApexCharts(el, {
            series: [{ name: completedLabel, data: values }],
            chart: { type: 'area', height: 200, toolbar: { show: false }, sparkline: { enabled: false } },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: { opacityFrom: 0.35, opacityTo: 0.05 },
            },
            colors: [color || '#1cc88a'],
            dataLabels: { enabled: false },
            xaxis: {
                categories: categories || [],
                labels: { style: { fontSize: '10px' }, rotate: -45 },
            },
            yaxis: { labels: { style: { fontSize: '10px' } } },
            grid: { strokeDashArray: 4 },
            tooltip: { x: { show: true } },
        }).render();
    }

    function renderCompactHourBars(el, categories, values, color) {
        if (!el) return;
        if (!values.length || sumValues(values) === 0) {
            showEmpty(el);
            return;
        }
        new ApexCharts(el, {
            series: [{ name: bookingsLabel, data: values }],
            chart: { type: 'bar', height: 200, toolbar: { show: false } },
            plotOptions: { bar: { columnWidth: '85%', borderRadius: 3 } },
            colors: [color || '#6F8AED'],
            dataLabels: { enabled: false },
            xaxis: {
                categories: categories,
                labels: {
                    style: { fontSize: '9px' },
                    rotate: -60,
                    hideOverlappingLabels: true,
                },
                tickAmount: 12,
            },
            yaxis: { labels: { style: { fontSize: '10px' } } },
            grid: { strokeDashArray: 4 },
            legend: { show: false },
        }).render();
    }

    var outcome = analytics.outcome_breakdown || [];
    renderDonut(
        document.querySelector('#booking-outcome-chart'),
        outcome.map(function (o) { return o.total; }),
        outcome.map(function (o) { return o.label; }),
        outcome.map(function (o) { return o.color; }),
        { showCenter: true, centerLabel: bookingsLabel }
    );

    var catSlices = topSlices(analytics.category_wise || [], 6);
    renderDonut(
        document.querySelector('#booking-category-chart'),
        catSlices.values,
        catSlices.labels,
        catSlices.colors
    );

    var zoneSlices = topSlices(analytics.zone_wise || [], 6);
    renderDonut(
        document.querySelector('#booking-zone-chart'),
        zoneSlices.values,
        zoneSlices.labels,
        zoneSlices.colors
    );

    var dayLabels = analytics.booking_created_by_day_labels || [];
    var dayValues = analytics.booking_created_by_day || [];
    renderDonut(
        document.querySelector('#booking-day-chart'),
        dayValues,
        dayLabels,
        palette.slice(0, 7)
    );

    renderCompactHourBars(
        document.querySelector('#booking-hour-chart'),
        analytics.booking_created_by_hour_labels || [],
        analytics.booking_created_by_hour || [],
        '#6F8AED'
    );

    var completed = analytics.completed || {};
    var completedCat = topSlices(completed.category_wise || [], 5);
    var completedZone = topSlices(completed.zone_wise || [], 5);
    var completedSub = topSlices(completed.subcategory_wise || [], 5);

    renderDonut(document.querySelector('#booking-completed-category-chart'), completedCat.values, completedCat.labels, completedCat.colors);
    renderDonut(document.querySelector('#booking-completed-zone-chart'), completedZone.values, completedZone.labels, completedZone.colors);
    renderDonut(document.querySelector('#booking-completed-subcategory-chart'), completedSub.values, completedSub.labels, completedSub.colors);

    var cancelled = analytics.cancelled || {};
    var cancelledCat = topSlices(cancelled.category_wise || [], 5);
    var cancelledZone = topSlices(cancelled.zone_wise || [], 5);
    renderDonut(
        document.querySelector('#booking-cancelled-category-chart'),
        cancelledCat.values,
        cancelledCat.labels,
        cancelledCat.colors
    );
    renderDonut(
        document.querySelector('#booking-cancelled-zone-chart'),
        cancelledZone.values,
        cancelledZone.labels,
        cancelledZone.colors
    );
})();
@endif
