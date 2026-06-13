@if(!empty($bookingReportAnalytics))
(function () {
    var DD = window.ReportChartDrilldown;
    var showBooking = window.BookingChartDrilldown.show;
    var analytics = {!! json_encode($bookingReportAnalytics) !!};
    var othersLabel = @json(translate('Others'));
    var bookingsLabel = @json(translate('Bookings'));
    var completedLabel = @json(translate('completed'));
    var palette = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796',
        '#5a5c69', '#fd7e14', '#6f42c1', '#20c997', '#0dcaf0', '#d63384',
    ];
    var drilldown = analytics.drilldown || {};

    function renderDonut(el, values, labels, colors, options) {
        options = options || {};
        if (!el) return null;
        values = values || [];
        if (!values.length || DD.sumValues(values) === 0) {
            DD.showEmpty(el);
            return null;
        }
        var chartLabels = options.legendWithCounts !== false ? DD.labelsWithCounts(labels, values) : labels;
        var chart = new ApexCharts(el, {
            series: values,
            chart: { type: 'donut', height: options.height || 220, fontFamily: 'inherit' },
            labels: chartLabels,
            colors: colors || palette,
            legend: { position: 'bottom', horizontalAlign: 'center', fontSize: '11px', itemMargin: { horizontal: 6, vertical: 2 } },
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
                                formatter: function () { return String(DD.sumValues(values)); },
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
                        var pct = DD.sumValues(values) > 0 ? Math.round((val / DD.sumValues(values)) * 1000) / 10 : 0;
                        return name + ': ' + val + ' (' + pct + '%)';
                    },
                },
            },
        });
        chart.render().then(function () {
            if (options.idsBySlice && options.idsBySlice.length) {
                DD.attachLegendViewButtons(el, labels, options.idsBySlice, showBooking);
            }
        });
        return chart;
    }

    function renderDrilldownDonut(el, rows, limit, drilldownMap) {
        var slice = DD.topSlices(rows, limit, othersLabel, palette);
        renderDonut(el, slice.values, slice.labels, slice.colors, {
            idsBySlice: DD.resolveSliceIds(drilldownMap || {}, slice),
        });
    }

    function renderCompactHourBars(el, categories, values, drilldownMap) {
        if (!el) return;
        if (!values.length || DD.sumValues(values) === 0) {
            DD.showEmpty(el);
            return;
        }
        var idsBySlice = (values || []).map(function (_, index) {
            return drilldownMap[String(index)] || drilldownMap[index] || [];
        });
        var chart = new ApexCharts(el, {
            series: [{ name: bookingsLabel, data: values }],
            chart: { type: 'bar', height: 200, toolbar: { show: false }, events: {
                dataPointSelection: function (event, ctx, config) {
                    var ids = idsBySlice[config.dataPointIndex] || [];
                    if (ids.length) showBooking(categories[config.dataPointIndex] || '—', ids);
                },
            }},
            plotOptions: { bar: { columnWidth: '85%', borderRadius: 3 } },
            colors: ['#6F8AED'],
            dataLabels: { enabled: false },
            xaxis: { categories: categories, labels: { style: { fontSize: '9px' }, rotate: -60, hideOverlappingLabels: true }, tickAmount: 12 },
            yaxis: { labels: { style: { fontSize: '10px' } } },
            grid: { strokeDashArray: 4 },
            legend: { show: false },
        });
        chart.render().then(function () {
            DD.renderCustomLegend(el, categories, values, idsBySlice, showBooking);
        });
    }

    function renderDayDonut(el, labels, values, drilldownMap) {
        var idsBySlice = (labels || []).map(function (label) { return drilldownMap[label] || []; });
        renderDonut(el, values, labels, palette.slice(0, 7), { idsBySlice: idsBySlice });
    }

    function renderOutcomeDonut(el, outcomeRows, outcomeMap) {
        var labels = outcomeRows.map(function (o) { return o.label; });
        var values = outcomeRows.map(function (o) { return o.total; });
        var colors = outcomeRows.map(function (o) { return o.color; });
        var idsBySlice = outcomeRows.map(function (o) { return outcomeMap[o.key] || []; });
        renderDonut(el, values, labels, colors, { showCenter: true, centerLabel: bookingsLabel, idsBySlice: idsBySlice });
    }

    var outcome = analytics.outcome_breakdown || [];
    renderOutcomeDonut(document.querySelector('#booking-outcome-chart'), outcome, drilldown.outcome || {});
    renderDrilldownDonut(document.querySelector('#booking-category-chart'), analytics.category_wise || [], 6, drilldown.category_wise || {});
    renderDrilldownDonut(document.querySelector('#booking-zone-chart'), analytics.zone_wise || [], 6, drilldown.zone_wise || {});
    renderDayDonut(
        document.querySelector('#booking-day-chart'),
        analytics.booking_created_by_day_labels || [],
        analytics.booking_created_by_day || [],
        drilldown.booking_created_by_day || {}
    );
    renderCompactHourBars(
        document.querySelector('#booking-hour-chart'),
        analytics.booking_created_by_hour_labels || [],
        analytics.booking_created_by_hour || [],
        drilldown.booking_created_by_hour || {}
    );

    var completed = analytics.completed || {};
    var completedDrilldown = drilldown.completed || {};
    var cancelledDrilldown = drilldown.cancelled || {};
    renderDrilldownDonut(document.querySelector('#booking-completed-category-chart'), completed.category_wise || [], 5, completedDrilldown.category_wise || {});
    renderDrilldownDonut(document.querySelector('#booking-completed-zone-chart'), completed.zone_wise || [], 5, completedDrilldown.zone_wise || {});
    renderDrilldownDonut(document.querySelector('#booking-completed-subcategory-chart'), completed.subcategory_wise || [], 5, completedDrilldown.subcategory_wise || {});

    var cancelled = analytics.cancelled || {};
    renderDrilldownDonut(document.querySelector('#booking-cancelled-category-chart'), cancelled.category_wise || [], 5, cancelledDrilldown.category_wise || {});
    renderDrilldownDonut(document.querySelector('#booking-cancelled-zone-chart'), cancelled.zone_wise || [], 5, cancelledDrilldown.zone_wise || {});
})();
@endif
