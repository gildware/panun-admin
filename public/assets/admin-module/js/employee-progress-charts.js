(function (global) {
    'use strict';

    var brand = '#43466e';
    var charts = {};
    var sparkCharts = [];
    var H = { trend: 280, bar: 280, line: 260, donut: 240, stacked: 300, funnel: 260 };
    var initTimer = null;

    function cfg() {
        return (global.PanunProgressCharts && global.PanunProgressCharts.config) || { charts: {}, kpis: [], labels: {} };
    }

    function isVisible(el) {
        if (!el) return false;
        var panel = el.closest('.tab-panel');
        if (panel && !panel.classList.contains('on')) return false;
        return el.getClientRects().length > 0 && el.offsetWidth > 0;
    }

    function destroyAll() {
        Object.keys(charts).forEach(function (id) {
            try {
                if (charts[id] && typeof charts[id].destroy === 'function') {
                    charts[id].destroy();
                }
            } catch (e) {}
            delete charts[id];
        });
        sparkCharts.forEach(function (chart) {
            try {
                if (chart && typeof chart.destroy === 'function') {
                    chart.destroy();
                }
            } catch (e) {}
        });
        sparkCharts = [];
        document.querySelectorAll('.progress-spark').forEach(function (el) {
            el.innerHTML = '';
        });
    }

    function legendTop() {
        return {
            position: 'top',
            horizontalAlign: 'right',
            fontFamily: 'Outfit, sans-serif',
            fontSize: '11px',
            fontWeight: 600,
            offsetY: 0,
            itemMargin: { horizontal: 12, vertical: 2 },
            markers: { width: 10, height: 10, radius: 3 }
        };
    }

    function legendBottom() {
        return {
            position: 'bottom',
            horizontalAlign: 'center',
            fontFamily: 'Outfit, sans-serif',
            fontSize: '11px',
            fontWeight: 600,
            offsetY: 4,
            itemMargin: { horizontal: 10, vertical: 4 }
        };
    }

    function axisCats(categories) {
        return {
            categories: categories || [],
            labels: {
                rotate: -35,
                trim: true,
                hideOverlappingLabels: true,
                style: { fontSize: '10px', fontWeight: 600, fontFamily: 'Outfit, sans-serif', colors: '#64748b' }
            },
            tickPlacement: 'on',
            axisBorder: { show: false },
            axisTicks: { show: false }
        };
    }

    function donutOpts(labels, size) {
        return {
            plotOptions: {
                pie: {
                    donut: {
                        size: size || '62%',
                        labels: {
                            show: true,
                            total: { show: true, label: labels.total || 'Total', fontSize: '11px', fontWeight: 700 }
                        }
                    }
                }
            },
            legend: legendBottom(),
            dataLabels: { enabled: false }
        };
    }

    function mk(id, opts) {
        var el = document.querySelector(id);
        if (!el || !isVisible(el)) return;
        if (charts[id]) {
            try {
                charts[id].destroy();
            } catch (e) {}
            delete charts[id];
        }
        el.innerHTML = '';
        var base = {
            chart: {
                fontFamily: 'Outfit, sans-serif',
                toolbar: { show: false },
                animations: { enabled: true, speed: 500 },
                redrawOnParentResize: true
            },
            colors: [brand, '#059669', '#d97706', '#dc2626', '#2563eb', '#7c3aed'],
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4, padding: { left: 10, right: 12, top: 8, bottom: 4 } },
            tooltip: { theme: 'light', style: { fontFamily: 'Outfit, sans-serif', fontSize: '12px' } }
        };
        var merged = Object.assign({}, base, opts || {});
        merged.chart = Object.assign({}, base.chart, (opts && opts.chart) || {});
        try {
            charts[id] = new ApexCharts(el, merged);
            charts[id].render();
        } catch (e) {
            console.warn('Progress chart failed:', id, e);
        }
    }

    function initSparks(config) {
        document.querySelectorAll('.progress-spark').forEach(function (el) {
            if (!isVisible(el)) return;

            var spark = [];
            try {
                spark = JSON.parse(el.getAttribute('data-spark') || '[]');
            } catch (e) {
                spark = [];
            }
            if (!spark.length) {
                var index = parseInt(el.getAttribute('data-index'), 10);
                var kpi = (config.kpis || [])[index];
                if (kpi && kpi.spark && kpi.spark.length) {
                    spark = kpi.spark;
                }
            }
            if (!spark.length) return;

            el.innerHTML = '';
            try {
                var chart = new ApexCharts(el, {
                    series: [{ data: spark }],
                    chart: { type: 'area', height: 28, sparkline: { enabled: true }, fontFamily: 'Outfit,sans-serif' },
                    stroke: { width: 2, curve: 'smooth' },
                    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
                    colors: [el.getAttribute('data-color') || brand]
                });
                chart.render();
                sparkCharts.push(chart);
            } catch (e) {
                console.warn('Progress spark failed', e);
            }
        });
    }

    function init() {
        if (typeof ApexCharts === 'undefined') return;
        if (!document.querySelector('.emp-progress-report')) return;

        destroyAll();

        var config = cfg();
        var c = config.charts || {};
        var lc = config.leadCharts || {};
        var fc = config.followupCharts || {};
        var labels = config.labels || {};

        initSparks(config);

        var bookingTrendOpts = (function () {
            var trend = c.booking_trend_series || [];
            var series = trend.length
                ? trend.map(function (row) {
                    return { name: row.name || row.key || '', data: row.data || [] };
                })
                : [
                    { name: labels.bookings || 'Created', data: c.bookings_series || [] },
                    { name: labels.completed || 'Completed', data: c.completed_series || [] },
                    { name: labels.cancelled || 'Cancelled', data: c.cancelled_series || [] }
                ];
            var colors = trend.length
                ? trend.map(function (row) { return row.color || brand; })
                : [brand, '#059669', '#dc2626'];

            return {
                series: series,
                chart: { type: 'bar', height: H.stacked, stacked: true },
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        columnWidth: '48%',
                        borderRadiusApplication: 'end',
                        borderRadiusWhenStacked: 'last'
                    }
                },
                colors: colors,
                fill: { opacity: 1 },
                stroke: { width: 0 },
                xaxis: axisCats(c.activity_categories || []),
                yaxis: {
                    min: 0,
                    tickAmount: 4,
                    labels: {
                        formatter: function (v) { return Math.round(v); },
                        style: { fontSize: '11px', fontWeight: 600, fontFamily: 'Outfit, sans-serif', colors: '#64748b' }
                    }
                },
                legend: legendTop(),
                dataLabels: { enabled: false },
                tooltip: {
                    shared: true,
                    intersect: false,
                    style: { fontFamily: 'Outfit, sans-serif', fontSize: '12px' },
                    y: { formatter: function (v) { return Math.round(v || 0); } }
                }
            };
        })();
        mk('#chart-bookings-trend', bookingTrendOpts);
        mk('#chart-overview-booking-trend', bookingTrendOpts);

        mk('#chart-leads-trend', {
            series: [{ name: labels.leads || 'Leads', data: c.leads_series || [] }],
            chart: { type: 'area', height: H.line },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.45, opacityTo: 0.05 } },
            xaxis: axisCats(c.activity_categories || [])
        });

        mk('#chart-funnel', {
            series: [{ name: labels.total || 'Total', data: c.funnel_series || [] }],
            chart: { type: 'bar', height: H.funnel },
            plotOptions: { bar: { horizontal: true, distributed: true, barHeight: '58%', borderRadius: 6, dataLabels: { position: 'center' } } },
            dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 700, colors: ['#fff'] } },
            xaxis: { categories: c.funnel_categories || [], labels: { style: { fontSize: '11px', fontWeight: 600 } } },
            legend: { show: false },
            grid: { padding: { left: 8, right: 16, top: 4, bottom: 4 } }
        });

        mk('#chart-mix', Object.assign({
            series: c.outcome_series || [],
            chart: { type: 'donut', height: H.donut },
            labels: c.outcome_labels || []
        }, donutOpts(labels, '68%')));

        if (c.heatmap && c.heatmap.length) {
            mk('#chart-heatmap', {
                series: c.heatmap,
                chart: { type: 'heatmap', height: H.bar },
                plotOptions: { heatmap: { shadeIntensity: 0.4, radius: 4, colorScale: { ranges: [
                    { from: 0, to: 1, name: 'Low', color: '#eef0f6' },
                    { from: 2, to: 5, name: 'Med', color: '#9ca3cf' },
                    { from: 6, to: 100, name: 'High', color: brand }
                ] } } },
                dataLabels: { enabled: false },
                xaxis: { categories: ['8a', '9a', '10a', '11a', '12p', '1p', '2p', '3p', '4p', '5p'] }
            });
        }

        mk('#chart-leads-area', {
            series: [
                { name: labels.leads || 'Leads', data: c.leads_series || [] },
                { name: labels.bookings || 'Bookings', data: c.bookings_series || [] }
            ],
            chart: { type: 'area', height: H.line, stacked: true },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.05 } },
            xaxis: axisCats(c.activity_categories || []),
            legend: legendTop()
        });

        mk('#chart-fu-line', {
            series: [
                { name: labels.followups || 'Follow-ups', data: c.followup_completed_series || [] },
                { name: 'Missed', data: c.followup_missed_series || [] }
            ],
            chart: { type: 'line', height: H.line },
            stroke: { width: [3, 2], curve: 'smooth' },
            markers: { size: 4, strokeWidth: 0 },
            xaxis: axisCats(c.activity_categories || []),
            legend: legendTop()
        });

        mk('#chart-rev-src', {
            series: [{ data: c.revenue_series || c.bookings_series || [] }],
            chart: { type: 'bar', height: H.bar },
            plotOptions: { bar: { borderRadius: 8, columnWidth: '52%', distributed: true } },
            xaxis: axisCats(c.activity_categories || []),
            legend: { show: false }
        });

        mk('#chart-rev-secondary', {
            series: [{ name: labels.bookings || 'Bookings', data: c.revenue_series || [] }],
            chart: { type: 'area', height: H.line },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
            xaxis: axisCats(c.activity_categories || [])
        });

        mk('#chart-score-bar', {
            series: [{ data: c.team_score_series || [] }],
            chart: { type: 'bar', height: H.bar },
            plotOptions: { bar: { horizontal: true, borderRadius: 6, distributed: true, barHeight: '58%' } },
            xaxis: { categories: c.team_score_categories || [], labels: { style: { fontSize: '11px', fontWeight: 600 } } },
            legend: { show: false }
        });

        mk('#chart-team-radar', {
            series: [
                { name: labels.you || 'You', data: c.radar_you || [] },
                { name: labels.teamAvg || 'Team avg', data: c.radar_team || [] }
            ],
            chart: { type: 'radar', height: H.bar },
            xaxis: { categories: c.radar_categories || [] },
            stroke: { width: 2 },
            fill: { opacity: 0.15 },
            markers: { size: 4 },
            legend: legendBottom()
        });

        mk('#chart-daily-act', {
            series: [{ name: 'Actions', data: c.daily_activity_series || [] }],
            chart: { type: 'bar', height: H.bar },
            plotOptions: { bar: { borderRadius: 6, columnWidth: '52%' } },
            xaxis: axisCats(c.daily_activity_categories || []),
            colors: [brand]
        });

        if ((fc.lead_categories && fc.lead_categories.length) || (fc.categories && fc.categories.length)) {
            mk('#chart-followup-lead-trend', {
                series: [
                    { name: labels.done || 'Done', data: fc.lead_done_series || [] },
                    { name: labels.late || 'Late', data: fc.lead_late_series || [] },
                    { name: labels.missed || 'Missed', data: fc.lead_missed_series || [] }
                ],
                chart: { type: 'bar', height: H.bar, stacked: true },
                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%', borderRadiusApplication: 'end' } },
                colors: ['#1cc88a', '#f6c23e', '#e74a3b'],
                xaxis: axisCats(fc.lead_categories || fc.categories || []),
                legend: legendTop(),
                dataLabels: { enabled: false }
            });
        }

        if ((fc.booking_categories && fc.booking_categories.length) || (fc.categories && fc.categories.length)) {
            mk('#chart-followup-booking-trend', {
                series: [
                    { name: labels.done || 'Done', data: fc.booking_done_series || [] },
                    { name: labels.late || 'Late', data: fc.booking_late_series || [] },
                    { name: labels.missed || 'Missed', data: fc.booking_missed_series || [] }
                ],
                chart: { type: 'bar', height: H.bar, stacked: true },
                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%', borderRadiusApplication: 'end' } },
                colors: ['#1cc88a', '#f6c23e', '#e74a3b'],
                xaxis: axisCats(fc.booking_categories || fc.categories || []),
                legend: legendTop(),
                dataLabels: { enabled: false }
            });
        }

        if (lc.customer_outcome_series && lc.customer_outcome_series.length) {
            mk('#chart-customer-outcomes', Object.assign({
                series: lc.customer_outcome_series,
                chart: { type: 'donut', height: H.donut },
                labels: lc.customer_outcome_labels || [],
                colors: ['#059669', '#d97706', '#dc2626']
            }, donutOpts(labels)));
        }

        if (lc.provider_outcome_series && lc.provider_outcome_series.length) {
            mk('#chart-provider-outcomes', Object.assign({
                series: lc.provider_outcome_series,
                chart: { type: 'donut', height: H.donut },
                labels: lc.provider_outcome_labels || [],
                colors: ['#059669', '#d97706', '#dc2626']
            }, donutOpts(labels)));
        }
    }

    function scheduleInit(delay) {
        if (initTimer) {
            clearTimeout(initTimer);
        }
        initTimer = setTimeout(function () {
            initTimer = null;
            init();
        }, typeof delay === 'number' ? delay : 0);
    }

    global.PanunProgressCharts = global.PanunProgressCharts || {};
    global.PanunProgressCharts.init = init;
    global.PanunProgressCharts.destroy = destroyAll;
    global.PanunProgressCharts.refreshVisible = function () {
        scheduleInit(80);
    };
    global.PanunProgressCharts.scheduleInit = scheduleInit;
})(window);
