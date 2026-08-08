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

    function resolveEmployeeScope(chartEl) {
        var wrap = chartEl.closest('.rank-marks-trend');
        var employeeSelect = wrap ? wrap.querySelector('.js-rank-marks-employee') : null;
        if (employeeSelect) {
            return employeeSelect.value || '__all__';
        }

        var scope = chartEl.getAttribute('data-employee-scope') || '__all__';
        return scope === '' ? '__all__' : scope;
    }

    function resolveChartMonth(chartEl, monthOverride) {
        if (monthOverride) {
            return monthOverride;
        }

        var wrap = chartEl.closest('.rank-marks-trend');
        var monthSelect = wrap ? wrap.querySelector('.js-rank-marks-month') : null;
        return monthSelect ? (monthSelect.value || '') : '';
    }

    function buildChartRequestUrl(chartEl, month) {
        var url = chartEl.getAttribute('data-chart-url') || '';
        var monthValue = resolveChartMonth(chartEl, month);
        var employeeScope = resolveEmployeeScope(chartEl);
        if (url === '' || monthValue === '') {
            return '';
        }

        var requestUrl = url + '?month=' + encodeURIComponent(monthValue);
        if (employeeScope !== '' && employeeScope !== '__all__') {
            requestUrl += '&employee_id=' + encodeURIComponent(employeeScope);
        } else {
            requestUrl += '&employee_id=__all__';
        }

        return requestUrl;
    }

    function reloadChartFromServer(chartEl, month, forceRender) {
        if (!chartEl) {
            return Promise.resolve();
        }

        var wrap = chartEl.closest('.rank-marks-trend');
        var monthValue = resolveChartMonth(chartEl, month);
        var requestUrl = buildChartRequestUrl(chartEl, monthValue);

        if (requestUrl === '') {
            if (forceRender) {
                renderChart(chartEl, true);
            }
            return Promise.resolve();
        }

        if (wrap) {
            wrap.classList.add('is-loading');
        }

        return fetch(requestUrl, {
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
                    month: payload.month || monthValue,
                    period_label: payload.period_label || '',
                });
                renderChart(chartEl, true);
            })
            .catch(function (error) {
                console.warn('Rank marks chart reload failed:', error);
                if (forceRender) {
                    renderChart(chartEl, true);
                }
            })
            .finally(function () {
                if (wrap) {
                    wrap.classList.remove('is-loading');
                }
            });
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

    function visibleScopeChart() {
        var panel = document.querySelector('.js-progress-scope-panel:not(.d-none)');
        if (panel) {
            var monthlyPanel = panel.querySelector('[data-panel="ranking-monthly"]');
            if (monthlyPanel && !monthlyPanel.classList.contains('active')) {
                return null;
            }

            return panel.querySelector('.js-rank-marks-chart');
        }

        var employeeProgress = document.getElementById('section-progress');
        if (!employeeProgress) {
            return null;
        }

        var employeeMonthlyPanel = employeeProgress.querySelector('[data-panel="ranking-monthly"]');
        if (employeeMonthlyPanel && !employeeMonthlyPanel.classList.contains('active')) {
            return null;
        }

        return employeeProgress.querySelector('.js-rank-marks-chart');
    }

    function syncScopeChartEmployeeFilter(scopeValue) {
        var chartEl = visibleScopeChart();
        if (!chartEl) {
            return null;
        }

        var wrap = chartEl.closest('.rank-marks-trend');
        var employeeSelect = wrap ? wrap.querySelector('.js-rank-marks-employee') : null;
        var employeeScope = scopeValue === '__all__' || scopeValue === '' ? '__all__' : scopeValue;

        if (employeeSelect) {
            employeeSelect.value = employeeScope;
        }

        chartEl.setAttribute('data-employee-scope', employeeScope);

        return chartEl;
    }

    function reloadVisibleScopeChart(scopeValue, forceServerReload) {
        var chartEl = syncScopeChartEmployeeFilter(scopeValue);
        if (!chartEl) {
            renderVisibleCharts(document, true);
            return Promise.resolve();
        }

        document.querySelectorAll('.js-rank-marks-chart').forEach(function (el) {
            if (el !== chartEl) {
                destroyChart(el);
            }
        });

        if (forceServerReload) {
            return reloadChartFromServer(chartEl, '', true);
        }

        renderChart(chartEl, true);
        return Promise.resolve();
    }

    function init() {
        bindMonthPickers();
        bindEmployeePickers();
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

    function bindEmployeePickers() {
        if (global.__rankMarksEmployeeBound) {
            return;
        }
        global.__rankMarksEmployeeBound = true;

        document.addEventListener('change', function (event) {
            var select = event.target.closest('.js-rank-marks-employee');
            if (!select) {
                return;
            }

            var wrap = select.closest('.rank-marks-trend');
            var chartEl = wrap ? wrap.querySelector('.js-rank-marks-chart') : null;
            if (!chartEl) {
                return;
            }

            chartEl.setAttribute('data-employee-scope', select.value || '__all__');
            reloadChartFromServer(chartEl, '', true);
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

            reloadChartFromServer(chartEl, select.value || '', true);
        });
    }

    global.PanunDashboardCharts = {
        init: init,
        refreshVisible: function (root) {
            renderVisibleCharts(root || document, true);
        },
        reloadVisibleScopeChart: reloadVisibleScopeChart,
        renderChart: renderChart,
    };
})(window);
