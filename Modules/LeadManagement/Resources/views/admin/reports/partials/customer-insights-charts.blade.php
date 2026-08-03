@if(!empty($customerLeadAnalytics))
@php
    $customerStatusTab = $customerStatusTab ?? 'overview';
    $analytics = $customerLeadAnalytics;
@endphp
(function () {
    var DD = window.ReportChartDrilldown;
    var showLead = window.LeadChartDrilldown.show;
    var analytics = {!! json_encode($customerLeadAnalytics) !!};
    var activeTab = @json($customerStatusTab);
    var othersLabel = @json(translate('Others'));
    var leadsLabel = @json(translate('Leads'));
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
            legend: { position: 'left', horizontalAlign: 'left', fontSize: '11px', itemMargin: { horizontal: 6, vertical: 3 }, height: options.height || 220 },
            plotOptions: { pie: { donut: { size: '62%', labels: { show: !!options.showCenter, total: { show: !!options.showCenter, label: options.centerLabel || leadsLabel, fontSize: '11px', formatter: function () { return String(DD.sumValues(values)); } } } } } },
            dataLabels: { enabled: false },
            stroke: { width: 1, colors: ['#fff'] },
        });
        chart.render().then(function () {
            if (options.idsBySlice && options.idsBySlice.length) {
                DD.attachLegendViewButtons(el, labels, options.idsBySlice, showLead);
            }
        });
        return chart;
    }

    function renderDrilldownDonut(el, rows, limit, drilldownMap, height) {
        var slice = DD.topSlices(rows, limit, othersLabel, palette);
        renderDonut(el, slice.values, slice.labels, slice.colors, {
            idsBySlice: DD.resolveSliceIds(drilldownMap || {}, slice),
            height: height || 300,
        });
    }

    function renderOutcomeDonut(el, outcomeRows, outcomeMap, height) {
        var labels = outcomeRows.map(function (o) { return o.label; });
        var values = outcomeRows.map(function (o) { return o.total; });
        var colors = outcomeRows.map(function (o) { return o.color; });
        var idsBySlice = outcomeRows.map(function (o) { return outcomeMap[o.key] || []; });
        renderDonut(el, values, labels, colors, { showCenter: true, centerLabel: leadsLabel, idsBySlice: idsBySlice, height: height || 260 });
    }

    var breakdownChartHeight = 260;

    if (activeTab === 'overview') {
        var outcome = analytics.outcome_breakdown || [];
        renderOutcomeDonut(document.querySelector('#customer-outcome-chart'), outcome, drilldown.outcome || {}, breakdownChartHeight);
        renderDrilldownDonut(document.querySelector('#customer-category-chart'), analytics.category_wise || [], 12, drilldown.category_wise || {}, breakdownChartHeight);
        renderDrilldownDonut(document.querySelector('#customer-zone-chart'), analytics.zone_wise || [], 12, drilldown.zone_wise || {}, breakdownChartHeight);
    }

    if (activeTab === 'booked') {
        var booked = analytics.booked || {};
        var bookedDrilldown = drilldown.booked || {};
        renderDrilldownDonut(document.querySelector('#customer-booked-category-chart'), booked.category_wise || [], 10, bookedDrilldown.category_wise || {}, breakdownChartHeight);
        renderDrilldownDonut(document.querySelector('#customer-booked-zone-chart'), booked.zone_wise || [], 10, bookedDrilldown.zone_wise || {}, breakdownChartHeight);
        renderDrilldownDonut(document.querySelector('#customer-booked-subcategory-chart'), booked.subcategory_wise || [], 10, bookedDrilldown.subcategory_wise || {}, breakdownChartHeight);
    }

    if (activeTab === 'cancelled') {
        var cancelled = analytics.cancelled || {};
        var cancelledDrilldown = drilldown.cancelled || {};
        renderDrilldownDonut(document.querySelector('#customer-cancelled-category-chart'), cancelled.category_wise || [], 10, cancelledDrilldown.category_wise || {}, breakdownChartHeight);
        renderDrilldownDonut(document.querySelector('#customer-cancelled-zone-chart'), cancelled.zone_wise || [], 10, cancelledDrilldown.zone_wise || {}, breakdownChartHeight);
        renderDrilldownDonut(document.querySelector('#customer-cancel-reason-chart'), cancelled.reasons || [], 10, cancelledDrilldown.reasons || {}, breakdownChartHeight);
    }

    if (activeTab === 'hold') {
        var hold = analytics.hold || {};
        var holdDrilldown = drilldown.hold || {};
        renderDrilldownDonut(document.querySelector('#customer-hold-category-chart'), hold.category_wise || [], 10, holdDrilldown.category_wise || {}, breakdownChartHeight);
        renderDrilldownDonut(document.querySelector('#customer-hold-zone-chart'), hold.zone_wise || [], 10, holdDrilldown.zone_wise || {}, breakdownChartHeight);
        renderDrilldownDonut(document.querySelector('#customer-hold-subcategory-chart'), hold.subcategory_wise || [], 10, holdDrilldown.subcategory_wise || {}, breakdownChartHeight);
    }

    if (activeTab === 'pending') {
        var pending = analytics.pending || {};
        var pendingDrilldown = drilldown.pending || {};
        renderDrilldownDonut(document.querySelector('#customer-pending-category-chart'), pending.category_wise || [], 10, pendingDrilldown.category_wise || {}, breakdownChartHeight);
        renderDrilldownDonut(document.querySelector('#customer-pending-zone-chart'), pending.zone_wise || [], 10, pendingDrilldown.zone_wise || {}, breakdownChartHeight);
        renderDrilldownDonut(document.querySelector('#customer-pending-subcategory-chart'), pending.subcategory_wise || [], 10, pendingDrilldown.subcategory_wise || {}, breakdownChartHeight);
    }
})();
@endif
