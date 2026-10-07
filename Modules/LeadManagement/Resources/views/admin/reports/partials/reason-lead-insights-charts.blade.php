@if(!empty($reasonLeadAnalytics))
(function () {
    var DD = window.ReportChartDrilldown;
    var showLead = window.LeadChartDrilldown.show;
    var analytics = {!! json_encode($reasonLeadAnalytics) !!};
    var othersLabel = @json(translate('Others'));
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
        var chart = new ApexCharts(el, {
            series: values,
            chart: { type: 'donut', height: 260, fontFamily: 'inherit' },
            labels: DD.labelsWithCounts(labels, values),
            colors: colors || palette,
            legend: { position: 'left', horizontalAlign: 'left', fontSize: '11px', itemMargin: { horizontal: 6, vertical: 3 }, height: 260 },
            plotOptions: { pie: { donut: { size: '62%' } } },
            dataLabels: { enabled: false },
            stroke: { width: 1, colors: ['#fff'] },
        });
        chart.render().then(function () {
            if (options.idsBySlice && options.idsBySlice.length) {
                DD.attachLegendViewButtons(el, labels, options.idsBySlice, showLead);
            }
        });
    }

    function renderDrilldownDonut(el, rows, drilldownMap) {
        var slice = DD.topSlices(rows, 10, othersLabel, palette);
        renderDonut(el, slice.values, slice.labels, slice.colors, {
            idsBySlice: DD.resolveSliceIds(drilldownMap || {}, slice),
        });
    }

    renderDrilldownDonut(document.querySelector('#typed-reason-chart'), analytics.reasons || [], drilldown.reasons || {});
    renderDrilldownDonut(document.querySelector('#typed-area-chart'), analytics.areas || [], drilldown.areas || {});
    renderDrilldownDonut(document.querySelector('#typed-source-chart'), analytics.sources || [], drilldown.sources || {});
})();
@endif
