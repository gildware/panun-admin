{{-- Shared chart drilldown helpers for booking + lead report graphs --}}
<script>
window.ReportChartDrilldown = (function () {
    const noDataLabel = @json(translate('Data_not_available'));

    function attachLegendViewButtons(chartEl, labels, idsBySlice, onView) {
        if (!chartEl || !idsBySlice || !idsBySlice.length) return;
        setTimeout(function () {
            var legendItems = chartEl.querySelectorAll('.apexcharts-legend-series');
            legendItems.forEach(function (item, index) {
                var ids = idsBySlice[index] || [];
                if (!ids.length || item.querySelector('.chart-drilldown-view-btn')) return;
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-link btn-sm p-0 ms-1 chart-drilldown-view-btn';
                btn.title = @json(translate('View'));
                btn.innerHTML = '<span class="material-icons fz-14">visibility</span>';
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    onView(labels[index] || '—', ids);
                });
                var textEl = item.querySelector('.apexcharts-legend-text');
                if (textEl && textEl.parentNode) {
                    textEl.parentNode.insertBefore(btn, textEl.nextSibling);
                }
            });
        }, 150);
    }

    function renderCustomLegend(containerEl, labels, values, idsBySlice, onView) {
        if (!containerEl) return;
        var host = containerEl.closest('.booking-report-chart-card, .customer-report-chart-card, .provider-report-chart-card, .border');
        if (!host) host = containerEl.parentElement;
        if (!host) return;

        var existing = host.querySelector('.chart-drilldown-custom-legend');
        if (existing) existing.remove();

        var wrap = document.createElement('div');
        wrap.className = 'chart-drilldown-custom-legend mt-2 d-flex flex-wrap gap-2 justify-content-center';
        (labels || []).forEach(function (label, index) {
            var count = values[index] || 0;
            var ids = idsBySlice[index] || [];
            if (!count || !ids.length) return;
            var item = document.createElement('div');
            item.className = 'd-inline-flex align-items-center gap-1 fz-11 text-muted';
            item.innerHTML = '<span>' + (label || '—') + ' (' + count + ')</span>';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-link btn-sm p-0 chart-drilldown-view-btn';
            btn.title = @json(translate('View'));
            btn.innerHTML = '<span class="material-icons fz-14">visibility</span>';
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                onView(label || '—', ids);
            });
            item.appendChild(btn);
            wrap.appendChild(item);
        });
        if (wrap.childElementCount) {
            host.appendChild(wrap);
        }
    }

    function resolveSliceIds(drilldownMap, slice) {
        return (slice.keys || []).map(function (key) {
            if (key === '__others__') {
                return (slice.otherKeys || []).reduce(function (acc, otherKey) {
                    return acc.concat(drilldownMap[otherKey] || []);
                }, []);
            }
            return drilldownMap[key] || [];
        });
    }

    function topSlices(rows, limit, othersLabel, palette) {
        rows = (rows || []).filter(function (r) { return (r.total || 0) > 0; });
        if (!rows.length) {
            return { labels: [], values: [], colors: [], keys: [], otherKeys: [] };
        }
        var sorted = rows.slice().sort(function (a, b) { return (b.total || 0) - (a.total || 0); });
        if (sorted.length <= limit) {
            return {
                labels: sorted.map(function (r) { return r.label || '—'; }),
                values: sorted.map(function (r) { return r.total || 0; }),
                colors: palette.slice(0, sorted.length),
                keys: sorted.map(function (r) { return r.key || ''; }),
                otherKeys: [],
            };
        }
        var top = sorted.slice(0, limit);
        var rest = sorted.slice(limit);
        var otherTotal = rest.reduce(function (s, r) { return s + (r.total || 0); }, 0);
        return {
            labels: top.map(function (r) { return r.label || '—'; }).concat([othersLabel]),
            values: top.map(function (r) { return r.total || 0; }).concat([otherTotal]),
            colors: palette.slice(0, limit).concat(['#ced4da']),
            keys: top.map(function (r) { return r.key || ''; }).concat(['__others__']),
            otherKeys: rest.map(function (r) { return r.key || ''; }),
        };
    }

    function labelsWithCounts(labels, values) {
        return labels.map(function (l, i) {
            return (l || '—') + ' (' + (values[i] || 0) + ')';
        });
    }

    function sumValues(arr) {
        return (arr || []).reduce(function (s, v) { return s + (v || 0); }, 0);
    }

    function showEmpty(el) {
        if (!el) return;
        el.innerHTML = '<div class="chart-empty-msg">' + noDataLabel + '</div>';
    }

    function idsFromKeys(keys, drilldownMap) {
        return (keys || []).map(function (key) { return drilldownMap[key] || []; });
    }

    function attachBarChartDrilldown(chart, labels, idsBySlice, onView) {
        if (!chart || !onView) return;
        chart.addEventListener('dataPointSelection', function (event, ctx, config) {
            var index = config.dataPointIndex;
            var ids = idsBySlice[index] || [];
            if (ids.length) {
                onView(labels[index] || '—', ids);
            }
        });
    }

    return {
        attachLegendViewButtons: attachLegendViewButtons,
        renderCustomLegend: renderCustomLegend,
        resolveSliceIds: resolveSliceIds,
        topSlices: topSlices,
        labelsWithCounts: labelsWithCounts,
        sumValues: sumValues,
        showEmpty: showEmpty,
        idsFromKeys: idsFromKeys,
        attachBarChartDrilldown: attachBarChartDrilldown,
    };
})();

window.BookingChartDrilldown = (function () {
    const drilldownUrl = @json(route('admin.report.booking.drilldown'));
    const csrfToken = @json(csrf_token());
    const openLabel = @json(translate('Open'));
    const noDataLabel = @json(translate('Data_not_available'));

    function show(title, bookingIds) {
        var modalEl = document.getElementById('bookingChartDrilldownModal');
        if (!modalEl) return;
        var titleEl = document.getElementById('bookingChartDrilldownModalLabel');
        var loadingEl = document.getElementById('booking-chart-drilldown-loading');
        var emptyEl = document.getElementById('booking-chart-drilldown-empty');
        var tableEl = document.getElementById('booking-chart-drilldown-table');
        var bodyEl = document.getElementById('booking-chart-drilldown-body');
        if (titleEl) titleEl.textContent = title + ' (' + (bookingIds || []).length + ')';
        if (loadingEl) loadingEl.classList.remove('d-none');
        if (emptyEl) emptyEl.classList.add('d-none');
        if (tableEl) tableEl.classList.add('d-none');
        if (bodyEl) bodyEl.innerHTML = '';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        fetch(drilldownUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ booking_ids: bookingIds || [] }),
        }).then(function (r) { return r.json(); }).then(function (data) {
            var bookings = data.bookings || [];
            if (loadingEl) loadingEl.classList.add('d-none');
            if (!bookings.length) { if (emptyEl) emptyEl.classList.remove('d-none'); return; }
            if (tableEl) tableEl.classList.remove('d-none');
            if (bodyEl) {
                bodyEl.innerHTML = bookings.map(function (b) {
                    return '<tr><td><a href="' + b.details_url + '">' + b.readable_id + '</a></td>'
                        + '<td><span class="badge badge-info text-capitalize">' + String(b.booking_status).replace(/_/g, ' ') + '</span></td>'
                        + '<td><div class="fw-medium">' + b.customer_name + '</div>' + (b.customer_phone ? '<div class="fz-12">' + b.customer_phone + '</div>' : '') + '</td>'
                        + '<td><div class="fw-medium">' + b.provider_name + '</div>' + (b.provider_phone ? '<div class="fz-12">' + b.provider_phone + '</div>' : '') + '</td>'
                        + '<td>' + b.total_booking_amount + '</td><td class="text-nowrap">' + b.created_at + '</td>'
                        + '<td><a class="btn btn-sm btn--primary" href="' + b.details_url + '">' + openLabel + '</a></td></tr>';
                }).join('');
            }
        }).catch(function () {
            if (loadingEl) loadingEl.classList.add('d-none');
            if (emptyEl) { emptyEl.textContent = noDataLabel; emptyEl.classList.remove('d-none'); }
        });
    }

    return { show: show };
})();

window.LeadChartDrilldown = (function () {
    const drilldownUrl = @json(route('admin.lead.reports.drilldown'));
    const csrfToken = @json(csrf_token());
    const openLabel = @json(translate('Open'));
    const noDataLabel = @json(translate('Data_not_available'));

    function show(title, leadIds) {
        var modalEl = document.getElementById('leadChartDrilldownModal');
        if (!modalEl) return;
        var titleEl = document.getElementById('leadChartDrilldownModalLabel');
        var loadingEl = document.getElementById('lead-chart-drilldown-loading');
        var emptyEl = document.getElementById('lead-chart-drilldown-empty');
        var tableEl = document.getElementById('lead-chart-drilldown-table');
        var bodyEl = document.getElementById('lead-chart-drilldown-body');
        if (titleEl) titleEl.textContent = title + ' (' + (leadIds || []).length + ')';
        if (loadingEl) loadingEl.classList.remove('d-none');
        if (emptyEl) emptyEl.classList.add('d-none');
        if (tableEl) tableEl.classList.add('d-none');
        if (bodyEl) bodyEl.innerHTML = '';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        fetch(drilldownUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ lead_ids: leadIds || [] }),
        }).then(function (r) {
            if (!r.ok) {
                return r.json().then(function (err) { throw err; });
            }
            return r.json();
        }).then(function (data) {
            var leads = data.leads || [];
            if (loadingEl) loadingEl.classList.add('d-none');
            if (!leads.length) { if (emptyEl) emptyEl.classList.remove('d-none'); return; }
            if (tableEl) tableEl.classList.remove('d-none');
            if (bodyEl) {
                bodyEl.innerHTML = leads.map(function (l) {
                    return '<tr><td class="fw-medium">' + l.name + '</td><td>' + (l.phone_number || '—') + '</td>'
                        + '<td class="text-capitalize">' + String(l.lead_type || '').replace(/_/g, ' ') + '</td>'
                        + '<td>' + l.source_name + '</td><td class="text-nowrap">' + l.received_at + '</td>'
                        + '<td><a class="btn btn-sm btn--primary" href="' + l.details_url + '">' + openLabel + '</a></td></tr>';
                }).join('');
            }
        }).catch(function () {
            if (loadingEl) loadingEl.classList.add('d-none');
            if (emptyEl) { emptyEl.textContent = noDataLabel; emptyEl.classList.remove('d-none'); }
        });
    }

    return { show: show };
})();
</script>
