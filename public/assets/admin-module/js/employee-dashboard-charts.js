(function (global) {
    'use strict';

    var charts = {};
    var palette = ['#3b82f6', '#22c55e', '#f59e0b', '#a855f7', '#ef4444', '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#6366f1'];

    function isDarkTheme() {
        var root = document.documentElement;
        return root.getAttribute('data-bs-theme') === 'dark'
            || document.body.classList.contains('dark')
            || document.body.classList.contains('dark-mode');
    }

    function chartThemeColors() {
        if (isDarkTheme()) {
            return {
                foreColor: '#94a3b8',
                gridBorder: '#334155',
                legendColor: '#cbd5e1',
            };
        }

        return {
            foreColor: '#64748b',
            gridBorder: '#eef2f7',
            legendColor: '#475569',
        };
    }

    function readChartConfig(el) {
        var dataId = el.getAttribute('data-chart-id');
        if (dataId) {
            var node = document.getElementById(dataId);
            if (node && node.textContent) {
                try {
                    return JSON.parse(node.textContent);
                } catch (error) {}
            }
        }

        var raw = el.getAttribute('data-chart') || '{}';
        try {
            return JSON.parse(raw);
        } catch (error) {
            return {};
        }
    }

    function writeChartConfig(el, config) {
        var dataId = el.getAttribute('data-chart-id');
        if (dataId) {
            var node = document.getElementById(dataId);
            if (node) {
                node.textContent = JSON.stringify(config);
                return;
            }
        }

        el.setAttribute('data-chart', JSON.stringify(config));
    }

    function isVisible(el) {
        if (!el || el.offsetParent === null) {
            return false;
        }

        var panel = el.closest('[data-panel], .js-progress-scope-panel, .activity-panel');
        if (panel) {
            if (panel.classList.contains('d-none')) {
                return false;
            }
            if (panel.hasAttribute('data-panel') && !panel.classList.contains('active')) {
                return false;
            }
        }

        return el.clientWidth > 0;
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

        var config = readChartConfig(el);
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

        var theme = chartThemeColors();
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
                    height: el.classList.contains('rank-marks-trend-chart--compact') ? 150 : 210,
                    fontFamily: 'Outfit, sans-serif',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    foreColor: theme.foreColor,
                },
                colors: palette,
                stroke: { width: 2.5, curve: 'smooth' },
                markers: {
                    size: 3,
                    strokeWidth: 2,
                    hover: { size: 5 },
                },
                dataLabels: { enabled: false },
                grid: {
                    borderColor: theme.gridBorder,
                    strokeDashArray: 4,
                    padding: { left: 8, right: 8 },
                },
                xaxis: {
                    categories: categories,
                    labels: { style: { fontSize: '10px', fontWeight: 600, colors: theme.foreColor } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { fontSize: '10px', fontWeight: 600, colors: theme.foreColor },
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
                    labels: { colors: theme.legendColor },
                    markers: { width: 8, height: 8, radius: 999 },
                },
                tooltip: {
                    theme: isDarkTheme() ? 'dark' : 'light',
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (value) {
                            return String(Math.round(value));
                        },
                    },
                },
            });
            charts[id].render().then(function () {
                if (charts[id]) {
                    charts[id].updateOptions({}, false, true);
                }
            });
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
        bindMonthPickers();
        bindResizeRefresh();
    }

    function bindResizeRefresh() {
        if (global.__rankMarksResizeBound) {
            return;
        }
        global.__rankMarksResizeBound = true;

        global.addEventListener('load', function () {
            setTimeout(function () {
                renderVisibleCharts(document, true);
            }, 120);
        });

        global.addEventListener('resize', function () {
            renderVisibleCharts(document, true);
        });
    }

    function bindMonthPickers() {
        if (global.__rankMarksMonthBound) {
            return;
        }
        global.__rankMarksMonthBound = true;

        document.addEventListener('change', function (event) {
            var select = event.target.closest('.js-rank-marks-month');
            if (!select) {
                return;
            }

            var wrap = select.closest('.rank-marks-trend');
            var chartEl = wrap ? wrap.querySelector('.js-rank-marks-chart') : null;
            if (!chartEl) {
                return;
            }

            var url = chartEl.getAttribute('data-chart-url') || '';
            var month = select.value || '';
            var employeeScope = chartEl.getAttribute('data-employee-scope') || '';
            if (url === '' || month === '') {
                return;
            }

            wrap.classList.add('is-loading');
            var requestUrl = url + '?month=' + encodeURIComponent(month);
            if (employeeScope !== '') {
                requestUrl += '&employee_id=' + encodeURIComponent(employeeScope);
            }

            fetch(requestUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Failed to load chart');
                    }
                    return response.json();
                })
                .then(function (payload) {
                    writeChartConfig(chartEl, {
                        categories: payload.categories || [],
                        series: payload.series || [],
                        month: payload.month || month,
                        period_label: payload.period_label || '',
                    });
                    renderChart(chartEl, true);
                })
                .catch(function (error) {
                    console.warn('Rank marks month load failed:', error);
                })
                .finally(function () {
                    wrap.classList.remove('is-loading');
                });
        });
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
