(function () {
    function showPlvTab(tab) {
        if (tab !== 'map' && tab !== 'cal') {
            return;
        }
        document.querySelectorAll('#plv-tabs [data-plv-tab]').forEach(function (b) {
            b.classList.toggle('on', b.getAttribute('data-plv-tab') === tab);
        });
        const mapUi = document.getElementById('plv-map-ui');
        const calUi = document.getElementById('plv-cal-ui');
        const subMap = document.getElementById('plv-subtitle-map');
        const subCal = document.getElementById('plv-subtitle-cal');
        if (mapUi) {
            mapUi.hidden = tab !== 'map';
        }
        if (calUi) {
            calUi.hidden = tab !== 'cal';
        }
        if (subMap) {
            subMap.hidden = tab !== 'map';
        }
        if (subCal) {
            subCal.hidden = tab !== 'cal';
        }
        if (tab === 'map' && typeof window.plvResizeMap === 'function') {
            window.plvResizeMap();
        }
        if (tab === 'cal' && typeof window.plvRenderCalendar === 'function') {
            window.plvRenderCalendar();
        }
    }
    window.plvShowTab = showPlvTab;
    if (!window.__plvTabsBound) {
        window.__plvTabsBound = true;
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('#plv-tabs [data-plv-tab]');
            if (!btn) {
                return;
            }
            e.preventDefault();
            showPlvTab(btn.getAttribute('data-plv-tab'));
        });
    }
})();

(function () {
    const dataEl = document.getElementById('provider-live-data');
    const mapEl = document.getElementById('providerLiveMap');
    if (!dataEl || !mapEl || mapEl.getAttribute('data-plv-ready') === '1') {
        return;
    }
    if (typeof google === 'undefined' || !google.maps) {
        return;
    }
    mapEl.setAttribute('data-plv-ready', '1');

    const payload = JSON.parse(dataEl.textContent || '{}');
    const ZONES = payload.zones || [];
    const PROVIDERS = payload.providers || [];
    const SUBCATEGORIES = payload.subcategories || [];
    const DEFAULT_ZONE_ID = payload.defaultZoneId || '';
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
    Object.keys(descendants).forEach(function (id) {
        descendants[id] = Array.from(new Set(descendants[id]));
    });

    const q = document.getElementById('plv-q');
    const zoneSel = document.getElementById('plv-zone');
    const catSel = document.getElementById('plv-cat');
    const subSel = document.getElementById('plv-sub');
    const availSel = document.getElementById('plv-avail');
    const listEl = document.getElementById('plv-list');
    const listCount = document.getElementById('plv-list-count');

    if (DEFAULT_ZONE_ID && zoneSel && !zoneSel.value) {
        zoneSel.value = DEFAULT_ZONE_ID;
    }

    const DEFAULT_CENTER = { lat: 34.0837, lng: 74.7973 };
    const map = new google.maps.Map(mapEl, {
        center: DEFAULT_CENTER,
        zoom: 12,
        mapTypeId: 'roadmap',
        streetViewControl: false,
        mapTypeControl: false,
        fullscreenControl: true
    });
    const markers = {};
    const heatPolygons = [];
    let mapMode = 'pins';
    let selected = null;
    let didFit = false;

    function statusLabel(s) {
        return s === 'available' ? 'Available now' : s === 'onjob' ? 'On a job' : 'Offline';
    }
    function initials(name) {
        return String(name || '')
            .split(/\s+/)
            .map(function (w) { return w[0]; })
            .filter(Boolean)
            .slice(0, 2)
            .join('')
            .toUpperCase() || '?';
    }
    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
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
        const names = (subs.length ? subs : (p.categories || [])).map(function (c) {
            return '<span class="provider-live-chip">' + escapeHtml(c.name) + '</span>';
        });
        return names.join('');
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

    function filters() {
        return {
            q: (q.value || '').trim().toLowerCase(),
            zone: zoneSel.value,
            cat: catSel.value,
            sub: subSel ? subSel.value : '',
            avail: availSel.value
        };
    }

    function zoneHits(p, zoneId) {
        if (!zoneId) {
            return true;
        }
        const ids = descendants[zoneId] || [zoneId];
        return (p.zone_ids || []).some(function (id) { return ids.indexOf(id) !== -1; });
    }

    function matches(p) {
        const f = filters();
        if (!zoneHits(p, f.zone)) return false;
        if (f.cat && !(p.categories || []).some(function (c) { return c.id === f.cat; })) return false;
        if (f.sub && !(p.subcategories || []).some(function (c) { return c.id === f.sub; })) return false;
        if (f.avail && p.avail !== f.avail) return false;
        if (f.q) {
            const zoneNames = (p.zone_ids || []).map(function (id) {
                return zoneById[id] ? zoneById[id].name : '';
            }).join(' ');
            const catNames = (p.categories || []).map(function (c) { return c.name; }).join(' ');
            const subNames = (p.subcategories || []).map(function (c) { return c.name; }).join(' ');
            const blob = [p.name, p.address, p.phone, zoneNames, catNames, subNames].join(' ').toLowerCase();
            if (blob.indexOf(f.q) === -1) return false;
        }
        return true;
    }

    function filtered() {
        return PROVIDERS.filter(matches);
    }

    function pinInnerHtml(p) {
        const photo = p.logo
            ? '<img src="' + escapeHtml(p.logo) + '" alt="">'
            : escapeHtml(initials(p.name));
        return '<span class="plv-pin plv-pin--' + escapeHtml(p.avail) + '">' + photo + '</span>';
    }

    function PlvHtmlMarker(opts) {
        this.position = opts.position;
        this.provider = opts.provider;
        this.onClick = opts.onClick;
        this.div = null;
        this.setMap(opts.map || null);
    }
    PlvHtmlMarker.prototype = Object.create(google.maps.OverlayView.prototype);
    PlvHtmlMarker.prototype.constructor = PlvHtmlMarker;
    PlvHtmlMarker.prototype.onAdd = function () {
        const div = document.createElement('button');
        div.type = 'button';
        div.className = 'plv-html-pin' + (selected === this.provider.id ? ' is-sel' : '');
        div.title = this.provider.name;
        div.innerHTML = pinInnerHtml(this.provider);
        const self = this;
        div.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            self.onClick();
        });
        this.div = div;
        this.getPanes().overlayMouseTarget.appendChild(div);
    };
    PlvHtmlMarker.prototype.draw = function () {
        if (!this.div || !this.getProjection()) {
            return;
        }
        const pos = this.getProjection().fromLatLngToDivPixel(this.position);
        this.div.style.left = pos.x + 'px';
        this.div.style.top = pos.y + 'px';
    };
    PlvHtmlMarker.prototype.onRemove = function () {
        if (this.div && this.div.parentNode) {
            this.div.parentNode.removeChild(this.div);
        }
        this.div = null;
    };
    PlvHtmlMarker.prototype.getPosition = function () {
        return this.position;
    };
    PlvHtmlMarker.prototype.setSelected = function (on) {
        if (!this.div) {
            return;
        }
        this.div.classList.toggle('is-sel', !!on);
        this.div.style.zIndex = on ? '20' : '1';
    };

    function PlvCardOverlay() {
        this.position = null;
        this.div = null;
    }
    PlvCardOverlay.prototype = Object.create(google.maps.OverlayView.prototype);
    PlvCardOverlay.prototype.constructor = PlvCardOverlay;
    PlvCardOverlay.prototype.onAdd = function () {
        const div = document.createElement('div');
        div.className = 'plv-float-card';
        div.setAttribute('hidden', 'hidden');
        this.div = div;
        this.getPanes().floatPane.appendChild(div);
    };
    PlvCardOverlay.prototype.draw = function () {
        if (!this.div || this.div.hasAttribute('hidden') || !this.position || !this.getProjection()) {
            return;
        }
        const pos = this.getProjection().fromLatLngToDivPixel(this.position);
        this.div.style.left = pos.x + 'px';
        this.div.style.top = pos.y + 'px';
    };
    PlvCardOverlay.prototype.onRemove = function () {
        if (this.div && this.div.parentNode) {
            this.div.parentNode.removeChild(this.div);
        }
        this.div = null;
    };
    PlvCardOverlay.prototype.show = function (html, position) {
        this.position = position;
        if (!this.div) {
            return;
        }
        this.div.innerHTML = html;
        this.div.removeAttribute('hidden');
        this.draw();
        const closeBtn = this.div.querySelector('.plv-popup-close');
        if (closeBtn) {
            closeBtn.onclick = function (e) {
                e.preventDefault();
                e.stopPropagation();
                closeProviderCard();
            };
        }
    };
    PlvCardOverlay.prototype.hide = function () {
        if (this.div) {
            this.div.setAttribute('hidden', 'hidden');
            this.div.innerHTML = '';
        }
    };

    const cardOverlay = new PlvCardOverlay();
    cardOverlay.setMap(map);

    function popupCard(p) {
        const photo = p.logo
            ? '<div class="plv-popup-photo"><img src="' + escapeHtml(p.logo) + '" alt=""></div>'
            : '<div class="plv-popup-photo">' + escapeHtml(initials(p.name)) + '</div>';
        return '<div class="plv-popup-card">' +
            '<button type="button" class="plv-popup-close" aria-label="Close">&times;</button>' +
            photo +
            '<div class="plv-popup-body">' +
            '<div class="plv-popup-name">' + escapeHtml(p.name) + '</div>' +
            '<div class="plv-popup-addr">' + escapeHtml(p.address || 'No address on file') + '</div>' +
            '</div></div>';
    }

    function highlightListRow() {
        if (!listEl) {
            return;
        }
        listEl.querySelectorAll('.provider-live-row').forEach(function (row) {
            row.classList.toggle('sel', row.getAttribute('data-id') === selected);
        });
        const selRow = listEl.querySelector('.provider-live-row.sel');
        if (selRow) {
            selRow.scrollIntoView({ block: 'nearest' });
        }
    }

    function closeProviderCard() {
        selected = null;
        cardOverlay.hide();
        Object.keys(markers).forEach(function (id) {
            if (markers[id] && markers[id].setSelected) {
                markers[id].setSelected(false);
            }
        });
        highlightListRow();
    }

    function openProviderCard(p) {
        selected = p.id;
        highlightListRow();
        Object.keys(markers).forEach(function (id) {
            if (markers[id] && markers[id].setSelected) {
                markers[id].setSelected(id === selected);
            }
        });
        if (markers[p.id]) {
            cardOverlay.show(popupCard(p), markers[p.id].getPosition());
        }
    }

    function clearMarkers() {
        Object.keys(markers).forEach(function (id) {
            markers[id].setMap(null);
            delete markers[id];
        });
    }

    function clearHeat() {
        heatPolygons.forEach(function (poly) { poly.setMap(null); });
        heatPolygons.length = 0;
    }

    function visibleZones() {
        const zoneId = zoneSel.value;
        if (!zoneId) {
            return ZONES;
        }
        const ids = descendants[zoneId] || [zoneId];
        return ZONES.filter(function (z) { return ids.indexOf(z.id) !== -1; });
    }

    function coverageCount(zoneId, rows) {
        const ids = descendants[zoneId] || [zoneId];
        return rows.filter(function (p) {
            return (p.zone_ids || []).some(function (id) { return ids.indexOf(id) !== -1; });
        }).length;
    }

    function renderPins(rows) {
        clearMarkers();
        rows.forEach(function (p) {
            if (p.lat == null || p.lng == null) {
                return;
            }
            const marker = new PlvHtmlMarker({
                position: new google.maps.LatLng(Number(p.lat), Number(p.lng)),
                provider: p,
                map: mapMode === 'pins' ? map : null,
                onClick: function () {
                    openProviderCard(p);
                }
            });
            markers[p.id] = marker;
        });
        if (selected && markers[selected] && mapMode === 'pins') {
            const p = PROVIDERS.find(function (x) { return x.id === selected; });
            if (p) {
                cardOverlay.show(popupCard(p), markers[selected].getPosition());
            }
        }
    }

    function renderHeat(rows) {
        clearHeat();
        visibleZones().forEach(function (z) {
            const n = coverageCount(z.id, rows);
            const t = coverageCount(z.id, PROVIDERS);
            const fill = n === 0 ? '#cbd5e1' : n <= 1 ? '#f59e0b' : n <= 3 ? '#43466e' : '#15803d';
            const paths = (z.paths && z.paths.length > 2) ? z.paths : null;
            if (!paths) {
                return;
            }
            const poly = new google.maps.Polygon({
                paths: paths,
                strokeColor: fill,
                strokeOpacity: mapMode === 'zones' ? 0.95 : 0.35,
                strokeWeight: mapMode === 'zones' ? 2 : 1,
                fillColor: fill,
                fillOpacity: mapMode === 'zones' ? (n === 0 ? 0.08 : 0.28) : 0.06,
                map: map,
                clickable: true,
                zIndex: 0
            });
            poly.addListener('click', function () {
                if (mapMode === 'zones') {
                    zoneSel.value = z.id;
                    didFit = false;
                    render();
                    return;
                }
            });
            heatPolygons.push(poly);
        });
    }

    function applyMapMode() {
        Object.keys(markers).forEach(function (id) {
            markers[id].setMap(mapMode === 'pins' ? map : null);
        });
        heatPolygons.forEach(function (poly) {
            poly.setOptions({
                strokeOpacity: mapMode === 'zones' ? 0.95 : 0.35,
                strokeWeight: mapMode === 'zones' ? 2 : 1,
                fillOpacity: mapMode === 'zones' ? 0.28 : 0.06
            });
        });
        if (mapMode !== 'pins') {
            cardOverlay.hide();
        }
    }

    function renderList(rows) {
        listCount.textContent = '(' + rows.length + ')';
        if (!rows.length) {
            listEl.innerHTML = '<div class="provider-live-empty">No providers match these filters.<br>Try another zone or category.</div>';
            return;
        }
        listEl.innerHTML = rows.map(function (p) {
            const catChips = serviceChips(p);
            const zones = (p.zone_ids || []).slice(0, 4).map(function (id) {
                const z = zoneById[id];
                return z ? '<span class="provider-live-chip">' + escapeHtml(z.name) + '</span>' : '';
            }).join('');
            const img = p.logo
                ? '<span class="provider-live-avatar-wrap"><img class="provider-live-avatar" src="' + escapeHtml(p.logo) + '" alt=""></span>'
                : '<span class="provider-live-avatar-wrap provider-live-avatar-wrap--initials">' + escapeHtml(initials(p.name)) + '</span>';
            const firstCat = primaryServiceName(p);
            return '<div class="provider-live-row' + (selected === p.id ? ' sel' : '') + '" data-id="' + escapeHtml(p.id) + '">' +
                img +
                '<div>' +
                '<div class="provider-live-name">' + escapeHtml(p.name) + '</div>' +
                '<div class="provider-live-meta">' + escapeHtml(firstCat) +
                (p.phone ? ' · ' + escapeHtml(p.phone) : '') +
                (p.rating ? ' · ' + p.rating + '★' : '') +
                '</div>' +
                '<div class="provider-live-addr">' + escapeHtml(p.address) + '</div>' +
                '<div class="provider-live-chips">' + catChips + zones + '</div>' +
                '</div>' +
                '<span class="provider-live-status ' + p.avail + '">' + statusLabel(p.avail) + '</span>' +
                '</div>';
        }).join('');

        listEl.querySelectorAll('.provider-live-row').forEach(function (row) {
            row.addEventListener('click', function () {
                const p = PROVIDERS.find(function (x) { return x.id === row.getAttribute('data-id'); });
                if (!p) return;
                if (p.lat != null && p.lng != null) {
                    map.panTo({ lat: Number(p.lat), lng: Number(p.lng) });
                    map.setZoom(Math.max(map.getZoom(), 14));
                }
                openProviderCard(p);
            });
            row.addEventListener('dblclick', function () {
                const p = PROVIDERS.find(function (x) { return x.id === row.getAttribute('data-id'); });
                if (p && p.details_url) {
                    window.location.href = p.details_url;
                }
            });
        });
    }

    function renderKpis(rows) {
        document.getElementById('plv-k-total').textContent = String(rows.length);
        document.getElementById('plv-k-avail').textContent = String(rows.filter(function (p) { return p.avail === 'available'; }).length);
        document.getElementById('plv-k-job').textContent = String(rows.filter(function (p) { return p.avail === 'onjob'; }).length);
        document.getElementById('plv-k-off').textContent = String(rows.filter(function (p) { return p.avail === 'offline'; }).length);
        const covered = ZONES.filter(function (z) { return coverageCount(z.id, rows) > 0; }).length;
        document.getElementById('plv-k-zones').textContent = String(covered);
        document.getElementById('plv-k-cover').textContent = 'Of ' + ZONES.length + ' live areas';
    }

    function fitIfNeeded(rows) {
        const bounds = new google.maps.LatLngBounds();
        let hasPoint = false;
        const selectedZone = zoneSel.value ? zoneById[zoneSel.value] : null;
        const zonesToFit = selectedZone ? [selectedZone] : visibleZones();
        zonesToFit.forEach(function (z) {
            (z.paths || []).forEach(function (pt) {
                bounds.extend(pt);
                hasPoint = true;
            });
            if ((!z.paths || z.paths.length < 3) && z.lat != null && z.lng != null) {
                bounds.extend({ lat: Number(z.lat), lng: Number(z.lng) });
                hasPoint = true;
            }
        });
        if (!hasPoint) {
            rows.forEach(function (p) {
                if (p.lat == null || p.lng == null) return;
                bounds.extend({ lat: Number(p.lat), lng: Number(p.lng) });
                hasPoint = true;
            });
        }
        google.maps.event.trigger(map, 'resize');
        if (hasPoint) {
            map.fitBounds(bounds, 48);
            google.maps.event.addListenerOnce(map, 'idle', function () {
                if (map.getZoom() > 14) {
                    map.setZoom(14);
                }
                if (map.getZoom() < 11 && zoneSel.value) {
                    map.setZoom(12);
                }
            });
        } else {
            map.setCenter(DEFAULT_CENTER);
            map.setZoom(12);
        }
    }

    function render() {
        const rows = filtered();
        renderKpis(rows);
        renderList(rows);
        renderPins(rows);
        renderHeat(rows);
        applyMapMode();
        if (!didFit) {
            fitIfNeeded(rows);
            didFit = true;
        }
        document.querySelectorAll('.provider-live-kpi[data-avail]').forEach(function (el) {
            el.classList.toggle('on', el.getAttribute('data-avail') === availSel.value && availSel.value !== '');
        });
        focusSearchHit(rows);
    }

    const findInputs = Array.prototype.slice.call(document.querySelectorAll('.js-plv-find'));
    let searchDirty = false;
    function setSearch(value, source) {
        if (q && q !== source) {
            q.value = value;
        }
        findInputs.forEach(function (el) {
            if (el !== source) {
                el.value = value;
            }
        });
    }
    function focusSearchHit(rows) {
        if (!searchDirty) {
            return;
        }
        searchDirty = false;
        const query = ((q && q.value) || '').trim();
        if (query.length < 2 || !rows.length) {
            return;
        }
        const hit = rows[0];
        if (hit.lat != null && hit.lng != null && mapMode === 'pins') {
            map.panTo({ lat: Number(hit.lat), lng: Number(hit.lng) });
            map.setZoom(Math.max(map.getZoom() || 12, 14));
            openProviderCard(hit);
        }
    }

    [q].concat(findInputs).forEach(function (el) {
        if (!el) {
            return;
        }
        el.addEventListener('input', function () {
            setSearch(el.value, el);
            searchDirty = true;
            didFit = false;
            render();
        });
    });

    ['input', 'change'].forEach(function (evt) {
        [zoneSel, catSel, subSel, availSel].forEach(function (el) {
            if (!el) {
                return;
            }
            el.addEventListener(evt, function () {
                if (el === catSel) {
                    fillSubSelect(subSel, catSel.value);
                }
                didFit = false;
                render();
            });
        });
    });

    function resetMapFilters(e) {
        if (e) {
            e.preventDefault();
        }
        const form = (q && q.form) || document.querySelector('#plv-map-ui form');
        if (form) {
            form.reset();
        } else {
            if (q) q.value = '';
            if (zoneSel) zoneSel.value = DEFAULT_ZONE_ID || '';
            if (catSel) catSel.value = '';
            if (availSel) availSel.value = '';
        }
        fillSubSelect(subSel, '');
        setSearch('', null);
        selected = null;
        cardOverlay.hide();
        didFit = false;
        render();
    }
    const mapForm = (q && q.form) || document.querySelector('#plv-map-ui form');
    if (mapForm) {
        mapForm.addEventListener('click', function (e) {
            if (e.target.closest('#plv-reset')) {
                resetMapFilters(e);
            }
        });
    }

    document.querySelectorAll('.provider-live-kpi[data-avail]').forEach(function (k) {
        k.addEventListener('click', function () {
            const v = k.getAttribute('data-avail') || '';
            availSel.value = availSel.value === v ? '' : v;
            didFit = false;
            render();
        });
    });

    document.getElementById('plv-map-mode').addEventListener('click', function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        mapMode = btn.getAttribute('data-mode');
        document.querySelectorAll('#plv-map-mode button').forEach(function (b) {
            b.classList.toggle('on', b === btn);
        });
        applyMapMode();
    });

    fillSubSelect(subSel, catSel ? catSel.value : '');
    render();
    google.maps.event.addListenerOnce(map, 'idle', function () {
        google.maps.event.trigger(map, 'resize');
    });

    window.plvResizeMap = function () {
        google.maps.event.trigger(map, 'resize');
        didFit = false;
        render();
    };

    const mapUi = document.getElementById('plv-map-ui');
    const calUi = document.getElementById('plv-cal-ui');
    const subMap = document.getElementById('plv-subtitle-map');
    const subCal = document.getElementById('plv-subtitle-cal');
    function showTab(tab) {
        if (typeof window.plvShowTab === 'function') {
            window.plvShowTab(tab);
            return;
        }
        document.querySelectorAll('[data-plv-tab]').forEach(function (b) {
            b.classList.toggle('on', b.getAttribute('data-plv-tab') === tab);
        });
        if (mapUi) {
            mapUi.hidden = tab !== 'map';
        }
        if (calUi) {
            calUi.hidden = tab !== 'cal';
        }
        if (subMap) {
            subMap.hidden = tab !== 'map';
        }
        if (subCal) {
            subCal.hidden = tab !== 'cal';
        }
        if (tab === 'map') {
            window.plvResizeMap();
        } else if (typeof window.plvRenderCalendar === 'function') {
            window.plvRenderCalendar();
        }
    }
})();
