(function () {
    const dataEl = document.getElementById('provider-live-data');
    const mapEl = document.getElementById('providerLiveMap');
    if (!dataEl || !mapEl || typeof google === 'undefined' || !google.maps) {
        return;
    }

    const payload = JSON.parse(dataEl.textContent || '{}');
    const ZONES = payload.zones || [];
    const PROVIDERS = payload.providers || [];
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
    const infoWindow = new google.maps.InfoWindow();
    const markers = {};
    const heatPolygons = [];
    let mapMode = 'pins';
    let selected = null;
    let didFit = false;

    function statusColor(s) {
        return s === 'available' ? '#22c55e' : s === 'onjob' ? '#d97706' : '#94a3b8';
    }
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

    function filters() {
        return {
            q: (q.value || '').trim().toLowerCase(),
            zone: zoneSel.value,
            cat: catSel.value,
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
        if (f.avail && p.avail !== f.avail) return false;
        if (f.q) {
            const zoneNames = (p.zone_ids || []).map(function (id) {
                return zoneById[id] ? zoneById[id].name : '';
            }).join(' ');
            const catNames = (p.categories || []).map(function (c) { return c.name; }).join(' ');
            const blob = [p.name, p.address, p.phone, zoneNames, catNames].join(' ').toLowerCase();
            if (blob.indexOf(f.q) === -1) return false;
        }
        return true;
    }

    function filtered() {
        return PROVIDERS.filter(matches);
    }

    function markerIcon(p, sel) {
        const size = sel ? 56 : 44;
        if (p.logo) {
            return {
                url: p.logo,
                scaledSize: new google.maps.Size(size, size),
                anchor: new google.maps.Point(size / 2, size)
            };
        }
        const color = statusColor(p.avail);
        const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '">' +
            '<circle cx="' + (size / 2) + '" cy="' + (size / 2) + '" r="' + ((size / 2) - 2) + '" fill="#fff" stroke="' + color + '" stroke-width="4"/>' +
            '<text x="' + (size / 2) + '" y="' + (size / 2 + 5) + '" text-anchor="middle" font-size="13" font-weight="800" fill="#43466e" font-family="Arial">' + escapeHtml(initials(p.name)) + '</text>' +
            '</svg>';
        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
            scaledSize: new google.maps.Size(size, size),
            anchor: new google.maps.Point(size / 2, size)
        };
    }

    function popupCard(p) {
        const photo = p.logo
            ? '<div class="plv-popup-photo"><img src="' + escapeHtml(p.logo) + '" alt=""></div>'
            : '<div class="plv-popup-photo">' + escapeHtml(initials(p.name)) + '</div>';
        return '<div class="plv-popup-card">' +
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

    function openProviderCard(p) {
        selected = p.id;
        highlightListRow();
        Object.keys(markers).forEach(function (id) {
            const row = PROVIDERS.find(function (x) { return x.id === id; });
            if (row && markers[id]) {
                markers[id].setIcon(markerIcon(row, id === selected));
                markers[id].setZIndex(id === selected ? 999 : 1);
            }
        });
        if (markers[p.id]) {
            infoWindow.setContent(popupCard(p));
            infoWindow.open({ map: map, anchor: markers[p.id] });
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
            const marker = new google.maps.Marker({
                position: { lat: Number(p.lat), lng: Number(p.lng) },
                map: mapMode === 'pins' ? map : null,
                icon: markerIcon(p, selected === p.id),
                title: p.name,
                zIndex: selected === p.id ? 999 : 1
            });
            marker.addListener('click', function () {
                openProviderCard(p);
            });
            markers[p.id] = marker;
        });
        if (selected && markers[selected] && mapMode === 'pins') {
            const p = PROVIDERS.find(function (x) { return x.id === selected; });
            if (p) {
                infoWindow.setContent(popupCard(p));
                infoWindow.open({ map: map, anchor: markers[selected] });
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
            infoWindow.close();
        }
    }

    function renderList(rows) {
        listCount.textContent = '(' + rows.length + ')';
        if (!rows.length) {
            listEl.innerHTML = '<div class="provider-live-empty">No providers match these filters.<br>Try another zone or category.</div>';
            return;
        }
        listEl.innerHTML = rows.map(function (p) {
            const catChips = (p.categories || []).map(function (c) {
                return '<span class="provider-live-chip">' + escapeHtml(c.name) + '</span>';
            }).join('');
            const zones = (p.zone_ids || []).slice(0, 4).map(function (id) {
                const z = zoneById[id];
                return z ? '<span class="provider-live-chip">' + escapeHtml(z.name) + '</span>' : '';
            }).join('');
            const img = p.logo
                ? '<img class="provider-live-avatar" src="' + escapeHtml(p.logo) + '" alt="">'
                : '<div class="provider-live-avatar d-flex align-items-center justify-content-center">' + escapeHtml(initials(p.name)) + '</div>';
            const firstCat = (p.categories && p.categories[0] && p.categories[0].name) ? p.categories[0].name : '—';
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
        visibleZones().forEach(function (z) {
            (z.paths || []).forEach(function (pt) {
                bounds.extend(pt);
                hasPoint = true;
            });
            if ((!z.paths || z.paths.length < 3) && z.lat != null && z.lng != null) {
                bounds.extend({ lat: Number(z.lat), lng: Number(z.lng) });
                hasPoint = true;
            }
        });
        rows.forEach(function (p) {
            if (p.lat == null || p.lng == null) return;
            bounds.extend({ lat: Number(p.lat), lng: Number(p.lng) });
            hasPoint = true;
        });
        if (hasPoint) {
            map.fitBounds(bounds, 48);
            google.maps.event.addListenerOnce(map, 'idle', function () {
                if (map.getZoom() > 14) {
                    map.setZoom(14);
                }
                if (map.getZoom() < 10 && zoneSel.value) {
                    map.setZoom(11);
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
    }

    ['input', 'change'].forEach(function (evt) {
        [q, zoneSel, catSel, availSel].forEach(function (el) {
            el.addEventListener(evt, function () {
                didFit = false;
                render();
            });
        });
    });

    document.getElementById('plv-reset').addEventListener('click', function () {
        q.value = '';
        zoneSel.value = DEFAULT_ZONE_ID || '';
        catSel.value = '';
        availSel.value = '';
        selected = null;
        infoWindow.close();
        didFit = false;
        render();
    });

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

    render();
    google.maps.event.addListenerOnce(map, 'idle', function () {
        google.maps.event.trigger(map, 'resize');
    });
})();
