(function (global) {
    'use strict';

    var charts = {};
    var palette = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#dc2626', '#0891b2', '#db2777', '#65a30d', '#ea580c', '#4f46e5'];

    function parseChart(el) {
        var raw = el.getAttribute('data-chart') || '{}';
        try {
            return JSON.parse(raw);
        } catch (error) {
            return {};
        }
    }

    function isVisible(el) {
        return !!(el && el.offsetParent !== null);
    }

    function destroyChart(el) {
        if (!el || !el.id) {
            return;
        }

        var id = el.id;
        if (charts[id]) {
            try {
                charts[id].destroy();
            } catch (error) {}
            delete charts[id];
        }

        el.innerHTML = '';
        delete el.dataset.rankChartRendered;
    }

    function renderChart(el, force) {
        if (!el || typeof ApexCharts === 'undefined') {
            return;
        }

        var id = el.id;
        if (!id) {
            return;
        }

        if (!isVisible(el)) {
            destroyChart(el);
            return;
        }

        var config = parseChart(el);
        var categories = config.categories || [];
        var series = config.series || [];
        if (categories.length === 0 || series.length === 0) {
            destroyChart(el);
            return;
        }

        var signature = JSON.stringify({ categories: categories, series: series });
        if (!force && el.dataset.rankChartRendered === signature && charts[id]) {
            return;
        }

        destroyChart(el);

        var apexSeries = series.map(function (row, index) {
            return {
                name: row.name || ('Series ' + (index + 1)),
                data: row.data || [],
            };
        });

        try {
            charts[id] = new ApexCharts(el, {
                series: apexSeries,
                chart: {
                    type: 'line',
                    height: el.classList.contains('rank-marks-trend-chart--compact') ? 150 : 190,
                    fontFamily: 'Outfit, sans-serif',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                },
                colors: palette,
                stroke: { width: series.length === 1 ? 3 : 2, curve: 'smooth' },
                markers: {
                    size: series.length === 1 ? 4 : 0,
                    strokeWidth: 2,
                    hover: { size: 5 },
                },
                dataLabels: { enabled: false },
                grid: {
                    borderColor: '#eef2f7',
                    strokeDashArray: 4,
                    padding: { left: 8, right: 8 },
                },
                xaxis: {
                    categories: categories,
                    labels: { style: { fontSize: '10px', fontWeight: 600 } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { fontSize: '10px', fontWeight: 600 },
                        formatter: function (value) {
                            return Math.round(value);
                        },
                    },
                },
                legend: {
                    show: series.length > 1,
                    position: 'top',
                    horizontalAlign: 'left',
                    fontSize: '11px',
                    fontWeight: 600,
                    markers: { width: 8, height: 8, radius: 999 },
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (value) {
                            return String(Math.round(value));
                        },
                    },
                },
            });
            charts[id].render();
            el.dataset.rankChartRendered = signature;
        } catch (error) {
            console.warn('Rank marks chart failed:', id, error);
        }
    }

    function renderVisibleCharts(root, force) {
        var scope = root || document;
        scope.querySelectorAll('.js-rank-marks-chart').forEach(function (el) {
            if (!isVisible(el)) {
                destroyChart(el);
                return;
            }
            renderChart(el, !!force);
        });
    }

    function init() {
        renderVisibleCharts(document, false);
    }

    global.PanunDashboardCharts = {
        init: init,
        refreshVisible: function (root) {
            renderVisibleCharts(root || document, true);
        },
        renderChart: renderChart,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window);
