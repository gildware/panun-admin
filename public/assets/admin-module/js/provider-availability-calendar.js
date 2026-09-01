(function () {
    const dataEl = document.getElementById('provider-live-data');
    if (!dataEl) {
        return;
    }
    const payload = JSON.parse(dataEl.textContent || '{}');
    const ZONES = payload.zones || [];
    const PROVIDERS = payload.providers || [];
    const SUBCATEGORIES = payload.subcategories || [];
    const DEFAULT_ZONE_ID = payload.defaultZoneId || '';
    const CAL_FROM_DT = payload.calendarFromDt || '';
    const CAL_TO_DT = payload.calendarToDt || '';
    const HORIZON_DAYS = Math.max(1, Number(payload.calendarWindowDays || payload.calendarHorizonDays) || 90);
    const START_MAX_DAYS = Math.max(HORIZON_DAYS, Number(payload.calendarStartMaxDays) || 365);
    const DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    const zoneById = {};
    ZONES.forEach(function (z) { zoneById[z.id] = z; });
    const descendants = {};
    ZONES.forEach(function (z) { descendants[z.id] = [z.id]; });
    ZONES.forEach(function (z) {
        let parent = z.parent_id;
        const guard = {};
        while (parent && zoneById[parent] && !guard[parent]) {
            guard[parent] = true;
            descendants[parent].push(z.id);
            parent = zoneById[parent].parent_id;
        }
    });

    const q = document.getElementById('plc-q');
    const fromEl = document.getElementById('plc-from');
    const toEl = document.getElementById('plc-to');
    const zoneEl = document.getElementById('plc-zone');
    const catEl = document.getElementById('plc-cat');
    const subEl = document.getElementById('plc-sub');
    const listEl = document.getElementById('plc-list');
    const calEl = document.getElementById('plc-cal');
    if (!fromEl || !toEl || !listEl || !calEl) {
        return;
    }

    let selected = null;
    let kpiFilter = '';

    function parseDateTime(s) {
        const m = String(s || '').match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/);
        if (!m) {
            return null;
        }
        return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]), Number(m[4]), Number(m[5]));
    }
    function ymd(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function addDays(d, n) {
        const x = new Date(d);
        x.setDate(x.getDate() + n);
        return x;
    }
    function startOfDay(d) {
        return new Date(d.getFullYear(), d.getMonth(), d.getDate());
    }
    function toMin(hhmm) {
        const parts = String(hhmm || '00:00').split(':');
        return (Number(parts[0]) || 0) * 60 + (Number(parts[1]) || 0);
    }
    function minsOf(d) {
        return d.getHours() * 60 + d.getMinutes();
    }
    function fmtDt(d) {
        return d.toLocaleString('en-IN', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
    }
    function overlaps(a0, a1, b0, b1) {
        return a0 < b1 && b0 < a1;
    }
    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
    function initials(name) {
        return String(name || '').split(/\s+/).map(function (w) { return w[0]; }).filter(Boolean).slice(0, 2).join('').toUpperCase() || '?';
    }
    function fillSubSelect(selectEl, parentCatId) {
        if (!selectEl) {
            return;
        }
        const enabled = !!parentCatId;
        const placeholder = selectEl.getAttribute(enabled ? 'data-placeholder-on' : 'data-placeholder-off')
            || (enabled ? 'All subcategories' : 'Select a category');
        const rows = enabled
            ? SUBCATEGORIES.filter(function (s) { return s.parent_id === parentCatId; })
            : [];
        selectEl.disabled = !enabled;
        selectEl.innerHTML = '<option value="">' + escapeHtml(placeholder) + '</option>' + rows.map(function (s) {
            return '<option value="' + escapeHtml(s.id) + '">' + escapeHtml(s.name) + '</option>';
        }).join('');
        selectEl.value = '';
    }
    function serviceChips(p) {
        const subs = p.subcategories || [];
        return (subs.length ? subs : (p.categories || [])).map(function (c) {
            return '<span class="provider-live-chip">' + escapeHtml(c.name) + '</span>';
        }).join('');
    }
    function primaryServiceName(p) {
        if (p.subcategories && p.subcategories[0] && p.subcategories[0].name) {
            return p.subcategories[0].name;
        }
        if (p.categories && p.categories[0] && p.categories[0].name) {
            return p.categories[0].name;
        }
        return '—';
    }
    function toDateTimeLocal(d) {
        return ymd(d) + 'T' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }
    function endOfDay(d) {
        return new Date(d.getFullYear(), d.getMonth(), d.getDate(), 23, 59);
    }
    function applyStartCap() {
        const now = new Date();
        const todayStart = startOfDay(now);
        const startMax = endOfDay(addDays(todayStart, START_MAX_DAYS));
        fromEl.min = toDateTimeLocal(todayStart);
        fromEl.max = toDateTimeLocal(startMax);
        const start = parseDateTime(fromEl.value);
        if (!start || start < todayStart) {
            const fallback = new Date(now);
            fallback.setHours(9, 0, 0, 0);
            fromEl.value = toDateTimeLocal(fallback);
        } else if (start > startMax) {
            fromEl.value = toDateTimeLocal(startMax);
        }
    }
    function applyEndCap() {
        const start = parseDateTime(fromEl.value);
        if (!start) {
            return;
        }
        const endMax = endOfDay(addDays(start, HORIZON_DAYS));
        toEl.min = fromEl.value;
        toEl.max = toDateTimeLocal(endMax);
        const end = parseDateTime(toEl.value);
        if (!end || end < start) {
            const fallback = addDays(start, 6);
            fallback.setHours(18, 0, 0, 0);
            toEl.value = toDateTimeLocal(fallback > endMax ? endMax : fallback);
        } else if (end > endMax) {
            toEl.value = toDateTimeLocal(endMax);
        }
    }
    function daysInRange() {
        const a = parseDateTime(fromEl.value);
        const b = parseDateTime(toEl.value);
        const out = [];
        if (!a || !b || b < a) {
            return out;
        }
        for (let d = startOfDay(a); d <= startOfDay(b); d = addDays(d, 1)) {
            out.push(new Date(d));
        }
        return out.slice(0, HORIZON_DAYS + 1);
    }
    function windowForDay(dateObj) {
        const a = parseDateTime(fromEl.value);
        const b = parseDateTime(toEl.value);
        if (!a || !b) {
            return null;
        }
        const dayStart = startOfDay(dateObj);
        const dayEnd = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), 23, 59);
        const winStart = a > dayStart ? a : dayStart;
        const winEnd = b < dayEnd ? b : dayEnd;
        if (winEnd <= winStart) {
            return null;
        }
        return { win0: minsOf(winStart), win1: minsOf(winEnd) };
    }
    function zoneHits(p, zoneId) {
        if (!zoneId) {
            return true;
        }
        const ids = descendants[zoneId] || [zoneId];
        return (p.zone_ids || []).some(function (id) { return ids.indexOf(id) !== -1; });
    }
    function hoursOf(p) {
        return {
            start: (p.hours && p.hours.start) ? p.hours.start : '09:00',
            end: (p.hours && p.hours.end) ? p.hours.end : '18:00'
        };
    }
    function dayState(p, dateObj) {
        const key = ymd(dateObj);
        const dayName = DAYS[dateObj.getDay()];
        const win = windowForDay(dateObj);
        if (!win) {
            return { kind: 'off', label: 'Outside window', jobs: [] };
        }
        const win0 = win.win0;
        const win1 = win.win1;
        const hrs = hoursOf(p);
        const work0 = toMin(hrs.start);
        const work1 = toMin(hrs.end);
        const weekends = p.weekends || [];

        if (!p.appOn) {
            return { kind: 'off', label: 'App off', jobs: [] };
        }
        if (weekends.indexOf(dayName) !== -1) {
            return { kind: 'off', label: 'Weekend off', jobs: [] };
        }
        if (!overlaps(win0, win1, work0, work1)) {
            return { kind: 'off', label: 'Outside hours', jobs: [] };
        }
        const jobs = (p.jobs || []).filter(function (j) { return j.date === key; });
        const covering = jobs.filter(function (j) {
            return overlaps(win0, win1, toMin(j.start), toMin(j.end));
        });
        if (!covering.length) {
            return { kind: 'free', label: hrs.start + '–' + hrs.end, jobs: [] };
        }
        const ong = covering.some(function (j) { return j.status === 'ongoing'; });
        const coversAll = covering.some(function (j) {
            return toMin(j.start) <= Math.max(win0, work0) && toMin(j.end) >= Math.min(win1, work1);
        });
        if (coversAll) {
            return { kind: ong ? 'ong' : 'sched', label: covering[0].title, jobs: covering };
        }
        return { kind: 'partial', label: covering[0].start + ' job, rest free', jobs: covering };
    }
    function rangeVerdict(p) {
        const days = daysInRange();
        if (!days.length) {
            return 'off';
        }
        const kinds = days.map(function (d) { return dayState(p, d).kind; });
        if (kinds.every(function (k) { return k === 'off'; })) {
            return 'off';
        }
        if (kinds.every(function (k) { return k === 'ong' || k === 'sched'; })) {
            return 'busy';
        }
        if (kinds.some(function (k) { return k === 'free'; }) && kinds.every(function (k) { return k === 'free' || k === 'off'; })) {
            return 'free';
        }
        return 'partial';
    }
    function compactVerdict(v) {
        return v === 'free' ? 'Free' : v === 'partial' ? 'Partial' : v === 'busy' ? 'Booked' : 'Off';
    }
    function verdictLabel(v) {
        return v === 'free' ? 'Can take work' : v === 'partial' ? 'Partial' : v === 'busy' ? 'Booked in window' : 'Not available';
    }
    function searchBlob(p) {
        const zoneNames = (p.zone_ids || []).map(function (id) {
            return zoneById[id] ? zoneById[id].name : '';
        }).join(' ');
        const catNames = (p.categories || []).map(function (c) { return c.name; }).join(' ');
        const subNames = (p.subcategories || []).map(function (c) { return c.name; }).join(' ');
        return [p.name, p.address, p.phone, zoneNames, catNames, subNames].join(' ').toLowerCase();
    }
    function baseMatch(p) {
        if (!zoneHits(p, zoneEl.value)) {
            return false;
        }
        if (catEl && catEl.value && !(p.categories || []).some(function (c) { return c.id === catEl.value; })) {
            return false;
        }
        if (subEl && subEl.value && !(p.subcategories || []).some(function (c) { return c.id === subEl.value; })) {
            return false;
        }
        const query = (q.value || '').trim().toLowerCase();
        if (query && searchBlob(p).indexOf(query) === -1) {
            return false;
        }
        return true;
    }
    function matches(p) {
        if (!baseMatch(p)) {
            return false;
        }
        const v = rangeVerdict(p);
        if (kpiFilter && v !== kpiFilter) {
            return false;
        }
        return true;
    }

    function render() {
        const days = daysInRange();
        const rangeLabel = document.getElementById('plc-range-label');
        if (rangeLabel) {
            const a = parseDateTime(fromEl.value);
            const b = parseDateTime(toEl.value);
            rangeLabel.textContent = days.length && a && b
                ? fmtDt(a) + ' → ' + fmtDt(b)
                : 'Pick a valid start and end';
        }

        const all = PROVIDERS.filter(baseMatch);
        const rows = PROVIDERS.filter(matches);
        const counts = { free: 0, partial: 0, busy: 0, off: 0 };
        all.forEach(function (p) {
            const v = rangeVerdict(p);
            if (counts[v] != null) {
                counts[v]++;
            }
        });

        const kpis = document.getElementById('plc-kpis');
        if (kpis) {
            kpis.innerHTML = [
                ['', all.length, 'In this search', ''],
                ['free', counts.free, 'App on, free in window', 'good'],
                ['partial', counts.partial, 'Job plus a free slot', 'warn'],
                ['busy', counts.busy, 'Scheduled or ongoing', ''],
                ['off', counts.off, 'App off / weekend / hours', 'bad']
            ].map(function (x) {
                return '<div class="provider-live-kpi ' + x[3] + (kpiFilter === x[0] ? ' on' : '') + '" data-v="' + x[0] + '">' +
                    '<div class="l">' + (x[0] ? verdictLabel(x[0]) : 'Matching') + '</div>' +
                    '<div class="v">' + x[1] + '</div><div class="s">' + x[2] + '</div></div>';
            }).join('');
            kpis.querySelectorAll('.provider-live-kpi').forEach(function (el) {
                el.onclick = function () {
                    const v = el.getAttribute('data-v') || '';
                    kpiFilter = kpiFilter === v ? '' : v;
                    render();
                };
            });
        }

        const listCount = document.getElementById('plc-list-count');
        if (listCount) {
            listCount.textContent = '(' + rows.length + ')';
        }
        if (!rows.length) {
            listEl.innerHTML = '<div class="provider-live-empty">No providers match this range. Try another date or time.</div>';
        } else {
            listEl.innerHTML = rows.map(function (p) {
                const v = rangeVerdict(p);
                const hrs = hoursOf(p);
                const img = p.logo
                    ? '<span class="provider-live-avatar-wrap"><img class="provider-live-avatar" src="' + escapeHtml(p.logo) + '" alt=""></span>'
                    : '<span class="provider-live-avatar-wrap provider-live-avatar-wrap--initials">' + escapeHtml(initials(p.name)) + '</span>';
                return '<div class="provider-live-row' + (selected === p.id ? ' sel' : '') + '" data-id="' + escapeHtml(p.id) + '">' +
                    img + '<div>' +
                    '<div class="provider-live-name">' + escapeHtml(p.name) + '</div>' +
                    '<div class="provider-live-meta">' + escapeHtml(primaryServiceName(p)) +
                    ' · ' + escapeHtml(hrs.start) + '–' + escapeHtml(hrs.end) + '</div>' +
                    '</div><span class="provider-live-status ' + v + '">' + compactVerdict(v) + '</span></div>';
            }).join('');
            listEl.querySelectorAll('.provider-live-row').forEach(function (row) {
                row.addEventListener('click', function () {
                    selected = row.getAttribute('data-id');
                    render();
                });
                row.addEventListener('dblclick', function () {
                    const p = PROVIDERS.find(function (x) { return x.id === row.getAttribute('data-id'); });
                    if (p && p.details_url) {
                        window.location.href = p.details_url;
                    }
                });
            });
        }

        if (!days.length) {
            calEl.innerHTML = '<div class="provider-live-empty">Choose a start and end date-time.</div>';
            return;
        }
        const head = '<th class="sticky">Provider</th>' + days.map(function (d) {
            return '<th>' + d.toLocaleDateString('en-IN', { weekday: 'short' }) +
                '<br>' + d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' }) + '</th>';
        }).join('');
        const body = rows.map(function (p) {
            const cells = days.map(function (d) {
                const st = dayState(p, d);
                const kindLabel = st.kind === 'free' ? 'Free' : st.kind === 'partial' ? 'Partial' : st.kind === 'ong' ? 'Ongoing' : st.kind === 'sched' ? 'Scheduled' : 'Off';
                return '<td class="cell"><div class="provider-cal-block ' + st.kind + '">' + kindLabel + '<small>' + escapeHtml(st.label) + '</small></div></td>';
            }).join('');
            return '<tr' + (selected === p.id ? ' style="outline:2px solid #43466e"' : '') + '><td class="name">' + escapeHtml(p.name) + '</td>' + cells + '</tr>';
        }).join('');
        calEl.innerHTML = '<table class="provider-cal-table"><thead><tr>' + head + '</tr></thead><tbody>' +
            (body || '<tr><td colspan="' + (days.length + 1) + '"><div class="provider-live-empty">No rows</div></td></tr>') +
            '</tbody></table>';

        const det = document.getElementById('plc-detail');
        const sel = PROVIDERS.find(function (p) { return p.id === selected; });
        if (!det) {
            return;
        }
        if (!sel) {
            det.hidden = true;
            det.innerHTML = '';
            return;
        }
        const jobs = [];
        days.forEach(function (d) {
            dayState(sel, d).jobs.forEach(function (j) { jobs.push(j); });
        });
        const hrs = hoursOf(sel);
        det.hidden = false;
        det.innerHTML = '<strong>' + escapeHtml(sel.name) + '</strong> in this window' +
            '<div class="provider-live-thin" style="margin-top:4px">App hours ' + escapeHtml(hrs.start) + '–' + escapeHtml(hrs.end) +
            ((sel.weekends || []).length ? ' · off: ' + escapeHtml((sel.weekends || []).join(', ')) : ' · no weekend off') +
            ' · switch: ' + (sel.appOn ? 'available' : 'unavailable') + '</div>' +
            (jobs.length
                ? '<div style="margin-top:8px;display:flex;flex-direction:column;gap:6px">' + jobs.map(function (j) {
                    const inner = '<div><strong>' + escapeHtml(j.title) + '</strong><div class="provider-live-thin">' + escapeHtml(j.date) + ' · ' + escapeHtml(j.start) + '–' + escapeHtml(j.end) + '</div></div>' +
                        '<span class="provider-live-status ' + (j.status === 'ongoing' ? 'busy' : 'partial') + '">' + escapeHtml(j.status) + '</span>';
                    return j.url
                        ? '<a class="provider-live-row" href="' + escapeHtml(j.url) + '" style="text-decoration:none">' + inner + '</a>'
                        : '<div class="provider-live-row">' + inner + '</div>';
                }).join('') + '</div>'
                : '<p class="provider-live-thin" style="margin-top:8px">No scheduled or ongoing jobs overlapping this range.</p>');
    }

    ['input', 'change'].forEach(function (evt) {
        [q, fromEl, toEl, zoneEl, catEl, subEl].forEach(function (el) {
            if (el) {
                el.addEventListener(evt, function () {
                    if (el === fromEl) {
                        applyStartCap();
                        applyEndCap();
                    }
                    if (el === catEl) {
                        fillSubSelect(subEl, catEl.value);
                    }
                    render();
                });
            }
        });
    });
    function resetCalFilters(e) {
        if (e) {
            e.preventDefault();
        }
        const form = (fromEl && fromEl.form) || document.querySelector('#plv-cal-ui form');
        if (form) {
            form.reset();
        } else {
            if (q) q.value = '';
            fromEl.value = CAL_FROM_DT;
            toEl.value = CAL_TO_DT;
            if (zoneEl) zoneEl.value = DEFAULT_ZONE_ID || '';
            if (catEl) catEl.value = '';
        }
        fillSubSelect(subEl, '');
        applyStartCap();
        applyEndCap();
        kpiFilter = '';
        selected = null;
        render();
    }
    const calForm = (fromEl && fromEl.form) || document.querySelector('#plv-cal-ui form');
    if (calForm) {
        calForm.addEventListener('click', function (e) {
            if (e.target.closest('#plc-reset')) {
                resetCalFilters(e);
            }
        });
    }

    fillSubSelect(subEl, catEl ? catEl.value : '');
    applyStartCap();
    applyEndCap();
    window.plvRenderCalendar = render;
    render();
    if (window.location.hash === '#calendar') {
        const calBtn = document.querySelector('[data-plv-tab="cal"]');
        if (calBtn) {
            calBtn.click();
        }
    }
})();
