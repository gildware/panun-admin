@php
    $zoneCoverageParentId = $zoneCoverageParentId ?? '';
    $coverageMapKey = optional(business_config('google_map', 'third_party'))->live_values ?? [];
    $coverageLabels = [
        'covered' => translate('Zone_coverage_covered'),
        'parent' => translate('Zone_coverage_parent'),
        'inactive' => translate('Inactive'),
        'notDrawn' => translate('Zone_coverage_not_drawn'),
        'loading' => translate('Zone_coverage_loading'),
        'empty' => translate('Zone_coverage_empty'),
        'allDrawn' => translate('Zone_coverage_all_drawn'),
        'failed' => translate('Zone_coverage_failed'),
        'parentGap' => translate('Zone_coverage_parent_gap'),
        'view' => translate('View'),
        'active' => translate('active'),
        'parentZone' => translate('Parent_zone'),
        'mapView' => translate('Map_view'),
        'listView' => translate('List_view'),
        'children' => translate('Zone_coverage_children'),
        'showInside' => translate('Zone_coverage_show_inside'),
        'focusParent' => translate('Zone_coverage_focus_parent'),
        'uncovered' => translate('Zone_coverage_uncovered'),
        'coveredHint' => translate('Zone_coverage_covered_hint'),
        'uncoveredHint' => translate('Zone_coverage_hint'),
        'noGap' => translate('Zone_coverage_no_gap'),
        'wholeUncovered' => translate('Zone_coverage_whole_uncovered'),
        'locating' => translate('Zone_coverage_locating'),
    ];
@endphp
<div id="zone-coverage-panel"
     class="zone-coverage d-none"
     data-url="{{ route('admin.zone.coverage-map') }}"
     data-parent-id="{{ $zoneCoverageParentId }}"
     data-maps-key="{{ $coverageMapKey['map_api_key_client'] ?? '' }}"
     data-default-lat="{{ session('location.lat', '23.757989') }}"
     data-default-lng="{{ session('location.lng', '90.360587') }}">
    <div class="zone-coverage__map">
        <div id="zone-coverage-canvas" class="zone-coverage-canvas"></div>
        <div id="zone-coverage-status" class="zone-coverage-status d-none" role="status"></div>
    </div>
    <aside class="zone-coverage__side">
        <label class="zone-coverage-focus-label" for="zone-coverage-parent">{{ translate('Zone_coverage_focus_parent') }}</label>
        <select id="zone-coverage-parent" class="form-select form-select-sm zone-coverage-parent"></select>
        <div class="zone-coverage-mode" role="group" aria-label="{{ translate('Map_view') }}">
            <button type="button" class="zone-coverage-mode__btn is-active" data-coverage-mode="covered" aria-pressed="true">{{ translate('Zone_coverage_covered') }}</button>
            <button type="button" class="zone-coverage-mode__btn" data-coverage-mode="uncovered" aria-pressed="false">{{ translate('Zone_coverage_uncovered') }}</button>
        </div>
        <p class="zone-coverage-hint" id="zone-coverage-hint">{{ translate('Zone_coverage_covered_hint') }}</p>
        <div id="zone-coverage-uncovered-block" class="d-none">
            <h3 class="zone-coverage-side-title">{{ translate('Zone_coverage_uncovered') }}</h3>
            <div id="zone-coverage-uncovered" class="zone-coverage-gaps"></div>
        </div>
        <div id="zone-coverage-children-block">
            <h3 class="zone-coverage-side-title">{{ translate('Zone_coverage_children') }}</h3>
            <div id="zone-coverage-gaps" class="zone-coverage-gaps"></div>
        </div>
    </aside>
</div>
<script type="application/json" id="zone-coverage-labels">@json($coverageLabels)</script>

@push('script')
    <script>
        (function () {
            const panel = document.getElementById('zone-coverage-panel');
            const button = document.getElementById('zone-map-view-btn');
            const table = document.getElementById('ListTableContainer');
            const labelsEl = document.getElementById('zone-coverage-labels');
            if (!panel || !button || !table || !labelsEl) {
                return;
            }

            const labels = JSON.parse(labelsEl.textContent || '{}');
            const canvas = document.getElementById('zone-coverage-canvas');
            const statusEl = document.getElementById('zone-coverage-status');
            const gapsEl = document.getElementById('zone-coverage-gaps');
            const uncoveredEl = document.getElementById('zone-coverage-uncovered');
            const uncoveredBlock = document.getElementById('zone-coverage-uncovered-block');
            const childrenBlock = document.getElementById('zone-coverage-children-block');
            const hintEl = document.getElementById('zone-coverage-hint');
            const parentSelect = document.getElementById('zone-coverage-parent');
            const buttonLabel = button.querySelector('.zone-map-view-btn__label');
            const buttonIcon = button.querySelector('.material-icons');

            let map = null;
            let infoWindow = null;
            let overlays = [];
            let open = false;
            let pinned = false;
            let suppressMapClick = false;
            let loadSeq = 0;
            let searchTimer = null;
            let mapsWaitersHooked = false;
            let fillingParents = false;
            let viewMode = 'covered';
            let paintParentAsGap = false;
            let lastZones = null;
            let lastGaps = null;

            function escapeHtml(value) {
                return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
                    return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'})[char];
                });
            }

            function setStatus(message, mode) {
                if (!statusEl) {
                    return;
                }
                statusEl.textContent = message || '';
                statusEl.classList.toggle('d-none', !message);
                statusEl.classList.toggle('zone-coverage-status--banner', mode === 'banner');
            }

            function hslToHex(hue, saturation, lightness) {
                const s = saturation / 100;
                const l = lightness / 100;
                const a = s * Math.min(l, 1 - l);
                const channel = function (n) {
                    const k = (n + hue / 30) % 12;
                    const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
                    return Math.round(255 * color).toString(16).padStart(2, '0');
                };
                return '#' + channel(0) + channel(8) + channel(4);
            }

            function childColor(index) {
                const hue = Math.round((index * 137.508) % 360);
                return {
                    fill: hslToHex(hue, 82, 64),
                    stroke: hslToHex(hue, 72, 32),
                };
            }

            function isFocusParent(zone) {
                return !!zone.is_focus;
            }

            function isDrawn(zone) {
                return Array.isArray(zone.paths) && zone.paths.length >= 3;
            }

            function styleFor(zone) {
                if (isFocusParent(zone)) {
                    if (paintParentAsGap) {
                        return {
                            strokeColor: '#e65100',
                            strokeOpacity: 1,
                            strokeWeight: 3,
                            fillColor: '#ffb300',
                            fillOpacity: 0.72,
                            zIndex: 2,
                        };
                    }
                    return {
                        strokeColor: '#0d47a1',
                        strokeOpacity: 1,
                        strokeWeight: 3,
                        fillColor: '#0d47a1',
                        fillOpacity: 0,
                        zIndex: viewMode === 'uncovered' ? 1 : 5,
                    };
                }
                if (zone.is_gap) {
                    return {
                        strokeColor: '#e65100',
                        strokeOpacity: 1,
                        strokeWeight: 2,
                        fillColor: '#ffb300',
                        fillOpacity: 0.72,
                        zIndex: 4,
                    };
                }
                return {
                    strokeColor: zone.is_active ? '#1b5e20' : '#616161',
                    strokeOpacity: 1,
                    strokeWeight: 2,
                    fillColor: zone.is_active ? '#2e7d32' : '#9e9e9e',
                    fillOpacity: zone.is_active ? 0.55 : 0.35,
                    zIndex: 4,
                };
            }

            function applyListFilter() {
                const searchInput = document.querySelector('.zone-search-input');
                const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
                gapsEl.querySelectorAll('.zone-coverage-gap-row').forEach(function (row) {
                    const button = row.querySelector('[data-zone-name]');
                    const name = button ? (button.getAttribute('data-zone-name') || '').toLowerCase() : '';
                    row.classList.toggle('d-none', query !== '' && name.indexOf(query) === -1);
                });
            }

            function renderChildren(children) {
                if (!children.length) {
                    gapsEl.innerHTML = '<p class="zone-coverage-empty-note">' + escapeHtml(labels.empty) + '</p>';
                    return;
                }
                gapsEl.innerHTML = children.map(function (zone) {
                    const swatch = zone._color
                        ? '<i class="zone-coverage-swatch zone-coverage-swatch--covered" data-fill="' + zone._color.fill + '" data-stroke="' + zone._color.stroke + '"></i>'
                        : '<i class="zone-coverage-swatch zone-coverage-swatch--missing"></i>';
                    const notes = [];
                    if (!isDrawn(zone)) {
                        notes.push(labels.notDrawn);
                    }
                    if (!zone.is_active) {
                        notes.push(labels.inactive);
                    }
                    const note = notes.length ? '<small>' + escapeHtml(notes.join(' · ')) + '</small>' : '';
                    const inside = (zone.child_count || 0) > 0
                        ? '<button type="button" class="zone-coverage-inside" data-focus-id="' + escapeHtml(zone.id) + '">' + escapeHtml(labels.showInside) + '</button>'
                        : '';
                    return '<div class="zone-coverage-gap-row">'
                        + '<button type="button" class="zone-coverage-gap" data-zone-id="' + escapeHtml(zone.id) + '" data-zone-name="' + escapeHtml(zone.name || '') + '">'
                        + swatch
                        + '<span class="zone-coverage-gap__text"><span>' + escapeHtml(zone.name || '—') + '</span>' + note + '</span>'
                        + '</button>'
                        + inside
                        + '</div>';
                }).join('');
                applyListFilter();
            }

            function infoHtml(zone, byId) {
                if (zone.is_gap) {
                    const place = zone.place ? '<p>' + escapeHtml(zone.place) + '</p>' : '';
                    return '<div class="zone-coverage-info"><strong>' + escapeHtml(labels.uncovered) + '</strong>'
                        + place
                        + '<p>' + escapeHtml(zone.area_label || '') + '</p></div>';
                }
                const parent = zone.parent_id && byId[zone.parent_id] ? byId[zone.parent_id].name : '';
                let detail = labels.covered;
                if (isFocusParent(zone)) {
                    detail = labels.parentGap;
                } else if (!isDrawn(zone)) {
                    detail = labels.notDrawn;
                } else if (!zone.is_active) {
                    detail = labels.inactive;
                }
                const parentLine = parent
                    ? '<p>' + escapeHtml(labels.parentZone) + ': ' + escapeHtml(parent) + '</p>'
                    : '';
                return '<div class="zone-coverage-info"><strong>' + escapeHtml(zone.name || '—') + '</strong>'
                    + parentLine
                    + '<p>' + escapeHtml(detail) + '</p>'
                    + '<a href="' + escapeHtml(zone.view_url) + '">' + escapeHtml(labels.view) + '</a></div>';
            }

            function clearOverlays() {
                overlays.forEach(function (item) {
                    if (item.polygon) {
                        item.polygon.setMap(null);
                    }
                    if (item.marker) {
                        item.marker.setMap(null);
                    }
                });
                overlays = [];
                if (infoWindow) {
                    infoWindow.close();
                }
                pinned = false;
            }

            function showInfo(zone, byId, position) {
                if (!infoWindow || !map) {
                    return;
                }
                infoWindow.setContent(infoHtml(zone, byId));
                infoWindow.setPosition(position);
                infoWindow.open(map);
            }

            function fillParents(parents, focusId) {
                if (!parentSelect) {
                    return;
                }
                fillingParents = true;
                parentSelect.innerHTML = (parents || []).map(function (parent) {
                    return '<option value="' + escapeHtml(parent.id) + '">' + escapeHtml(parent.name || '—') + '</option>';
                }).join('');
                if (focusId) {
                    parentSelect.value = focusId;
                }
                fillingParents = false;
            }

            function restyleOverlay(entry, highlighted) {
                const zone = entry.zone;
                entry.polygon.__coveragePinned = !!highlighted;
                if (!zone.is_gap && !isFocusParent(zone) && zone._color) {
                    const paint = highlighted
                        ? {stroke: zone._color.stroke, fill: zone._color.fill, opacity: 0.95}
                        : {
                            stroke: zone.is_active ? '#1b5e20' : '#616161',
                            fill: zone.is_active ? '#2e7d32' : '#9e9e9e',
                            opacity: zone.is_active ? 0.55 : 0.35,
                        };
                    entry.polygon.setOptions({
                        strokeColor: paint.stroke,
                        fillColor: paint.fill,
                        fillOpacity: paint.opacity,
                        strokeWeight: highlighted ? entry.baseWeight + 2 : entry.baseWeight,
                        zIndex: highlighted ? 6 : 4,
                    });
                    return;
                }
                entry.polygon.setOptions({
                    strokeWeight: highlighted ? entry.baseWeight + 2 : entry.baseWeight,
                });
            }

            function syncListHighlight(zoneId) {
                if (!gapsEl) {
                    return;
                }
                gapsEl.querySelectorAll('.zone-coverage-gap').forEach(function (button) {
                    const selected = !!zoneId && button.getAttribute('data-zone-id') === zoneId;
                    button.classList.toggle('is-selected', selected);
                    const swatch = button.querySelector('.zone-coverage-swatch');
                    if (!swatch || !swatch.getAttribute('data-fill')) {
                        button.style.background = '';
                        button.style.borderColor = '';
                        return;
                    }
                    if (selected) {
                        button.style.background = swatch.getAttribute('data-fill');
                        button.style.borderColor = swatch.getAttribute('data-stroke');
                        swatch.style.background = swatch.getAttribute('data-fill');
                        swatch.style.borderColor = swatch.getAttribute('data-stroke');
                    } else {
                        button.style.background = '';
                        button.style.borderColor = '';
                        swatch.style.background = '';
                        swatch.style.borderColor = '';
                    }
                });
            }

            function clearCoverageSelection() {
                pinned = false;
                overlays.forEach(function (entry) {
                    restyleOverlay(entry, false);
                });
                syncListHighlight('');
                if (infoWindow) {
                    infoWindow.close();
                }
            }

            function highlightZone(zoneId, byId, position) {
                const item = overlays.find(function (entry) {
                    return entry.zone.id === zoneId;
                });
                if (!item) {
                    return;
                }
                const childHighlight = !item.zone.is_gap && !isFocusParent(item.zone);
                pinned = true;
                overlays.forEach(function (entry) {
                    restyleOverlay(entry, childHighlight && entry.zone.id === zoneId);
                });
                if (!childHighlight) {
                    restyleOverlay(item, true);
                }
                syncListHighlight(childHighlight ? zoneId : '');
                let point = position || null;
                if (!point) {
                    const bounds = new google.maps.LatLngBounds();
                    (item.zone.paths || []).forEach(function (pathPoint) {
                        bounds.extend(pathPoint);
                    });
                    if (!bounds.isEmpty()) {
                        point = bounds.getCenter();
                    }
                }
                if (point) {
                    showInfo(item.zone, byId, point);
                }
            }

            function areaLabel(km2) {
                const value = Number(km2);
                if (!Number.isFinite(value) || value <= 0) {
                    return '';
                }
                return (value < 1 ? value.toFixed(1) : String(Math.round(value))) + ' km²';
            }

            function placeFromGeocode(results, parentName) {
                const parentKey = (parentName || '').trim().toLowerCase();
                const prefer = ['neighborhood', 'sublocality_level_2', 'sublocality_level_1', 'sublocality', 'locality', 'administrative_area_level_3', 'administrative_area_level_2'];
                const ranked = [];
                (results || []).forEach(function (result) {
                    (result.address_components || []).forEach(function (component) {
                        const name = (component.long_name || '').trim();
                        if (!name || name.indexOf('+') !== -1 || (component.types || []).indexOf('plus_code') !== -1) {
                            return;
                        }
                        if (name.toLowerCase() === parentKey) {
                            return;
                        }
                        prefer.forEach(function (type, rank) {
                            if ((component.types || []).indexOf(type) !== -1) {
                                ranked.push({rank: rank, name: name});
                            }
                        });
                    });
                });
                ranked.sort(function (a, b) {
                    return a.rank - b.rank;
                });
                return ranked.length ? ranked[0].name : labels.uncovered;
            }

            function renderUncovered(pieces, whole) {
                if (!uncoveredEl) {
                    return;
                }
                if (whole && pieces.length) {
                    uncoveredEl.innerHTML = '<p class="zone-coverage-empty-note">' + escapeHtml(labels.wholeUncovered) + '</p>';
                    return;
                }
                if (!pieces.length) {
                    uncoveredEl.innerHTML = '<p class="zone-coverage-empty-note">' + escapeHtml(labels.noGap) + '</p>';
                    return;
                }
                uncoveredEl.innerHTML = pieces.map(function (piece, index) {
                    return '<button type="button" class="zone-coverage-gap zone-coverage-gap--uncovered" data-zone-id="gap-' + index + '">'
                        + '<i class="zone-coverage-swatch zone-coverage-swatch--missing"></i>'
                        + '<span class="zone-coverage-gap__text"><span class="zone-coverage-gap__place">' + escapeHtml(labels.locating) + '</span>'
                        + '<small>' + escapeHtml(areaLabel(piece.area_km2)) + '</small></span>'
                        + '</button>';
                }).join('');
            }

            function compassLabel(origin, point) {
                if (!origin) {
                    return labels.uncovered;
                }
                const angle = Math.atan2(point.lng - origin.lng, point.lat - origin.lat) * 180 / Math.PI;
                const dirs = ['North side', 'Northeast side', 'East side', 'Southeast side', 'South side', 'Southwest side', 'West side', 'Northwest side'];
                const index = Math.round((((angle % 360) + 360) % 360) / 45) % 8;
                return dirs[index];
            }

            function ringCenter(paths) {
                if (!paths || !paths.length) {
                    return null;
                }
                let lat = 0;
                let lng = 0;
                paths.forEach(function (point) {
                    lat += Number(point.lat);
                    lng += Number(point.lng);
                });
                return {lat: lat / paths.length, lng: lng / paths.length};
            }

            function nameUncoveredPieces(pieces, parentName, origin) {
                const geocoder = window.google && google.maps && google.maps.Geocoder ? new google.maps.Geocoder() : null;
                pieces.forEach(function (piece, index) {
                    const apply = function (label) {
                        piece.zone.place = label;
                        const row = uncoveredEl && uncoveredEl.querySelector('[data-zone-id="gap-' + index + '"] .zone-coverage-gap__place');
                        if (row) {
                            row.textContent = label;
                        }
                        if (piece.marker) {
                            piece.marker.setTitle(label);
                        }
                    };
                    const fallback = compassLabel(origin, piece);
                    if (!geocoder) {
                        apply(fallback);
                        return;
                    }
                    geocoder.geocode({location: {lat: piece.lat, lng: piece.lng}}, function (results, status) {
                        const place = status === 'OK' ? placeFromGeocode(results, parentName) : '';
                        apply(place && place !== labels.uncovered ? place : fallback);
                    });
                });
            }

            function applyModeChrome() {
                const covered = viewMode === 'covered';
                if (uncoveredBlock) {
                    uncoveredBlock.classList.toggle('d-none', covered);
                }
                if (childrenBlock) {
                    childrenBlock.classList.toggle('d-none', !covered);
                }
                if (hintEl) {
                    hintEl.textContent = covered ? (labels.coveredHint || '') : (labels.uncoveredHint || '');
                }
                panel.querySelectorAll('[data-coverage-mode]').forEach(function (modeButton) {
                    const active = modeButton.getAttribute('data-coverage-mode') === viewMode;
                    modeButton.classList.toggle('is-active', active);
                    modeButton.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            }

            function drawGaps(gapPayload, parentZone) {
                const parentName = parentZone ? parentZone.name : '';
                const payload = gapPayload || {};
                const pieces = Array.isArray(payload.pieces) ? payload.pieces : [];
                renderUncovered(pieces, !!payload.whole);
                if (viewMode !== 'uncovered' || payload.whole) {
                    return;
                }
                const named = [];
                pieces.forEach(function (piece, index) {
                    const rings = Array.isArray(piece.rings) ? piece.rings : [];
                    if (!rings.length) {
                        return;
                    }
                    const zone = {
                        id: 'gap-' + index,
                        is_gap: true,
                        name: labels.uncovered,
                        place: '',
                        area_label: areaLabel(piece.area_km2),
                        paths: rings[0],
                        view_url: '',
                    };
                    const style = styleFor(zone);
                    const polygon = new google.maps.Polygon(Object.assign({
                        paths: rings.length > 1 ? rings : rings[0],
                        map: map,
                        clickable: true,
                        editable: false,
                        draggable: false,
                    }, style));
                    const marker = new google.maps.Marker({
                        position: {lat: Number(piece.lat), lng: Number(piece.lng)},
                        map: map,
                        clickable: false,
                        title: labels.uncovered,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 7,
                            fillColor: '#e65100',
                            fillOpacity: 1,
                            strokeColor: '#ffffff',
                            strokeWeight: 2,
                        },
                    });
                    polygon.addListener('click', function (event) {
                        suppressMapClick = true;
                        pinned = true;
                        if (event && event.latLng) {
                            showInfo(zone, {}, event.latLng);
                        }
                    });
                    overlays.push({polygon: polygon, marker: marker, zone: zone, baseWeight: style.strokeWeight || 2});
                    named.push({lat: Number(piece.lat), lng: Number(piece.lng), zone: zone, marker: marker});
                });
                const origin = ringCenter(parentZone && parentZone.paths);
                nameUncoveredPieces(named, parentName, origin);
            }

            function drawZones(zones, gapPayload) {
                clearOverlays();
                paintParentAsGap = viewMode === 'uncovered' && !!(gapPayload && gapPayload.whole);
                const byId = {};
                zones.forEach(function (zone) {
                    byId[zone.id] = zone;
                });

                const children = zones.filter(function (zone) {
                    return !isFocusParent(zone);
                });
                let colorIndex = 0;
                children.forEach(function (zone) {
                    zone._color = isDrawn(zone) ? childColor(colorIndex++) : null;
                });
                renderChildren(children);

                const drawable = zones.filter(function (zone) {
                    if (!isDrawn(zone)) {
                        return false;
                    }
                    if (viewMode === 'uncovered' && !isFocusParent(zone)) {
                        return false;
                    }
                    return true;
                }).sort(function (a, b) {
                    return (isFocusParent(a) ? 0 : 1) - (isFocusParent(b) ? 0 : 1);
                });

                if (!drawable.length) {
                    setStatus(labels.empty, 'banner');
                    return;
                }
                setStatus('', null);

                const bounds = new google.maps.LatLngBounds();
                const focus = zones.find(isFocusParent);
                const frame = focus && isDrawn(focus) ? [focus] : drawable;
                drawable.forEach(function (zone) {
                    const style = styleFor(zone);
                    const polygon = new google.maps.Polygon(Object.assign({
                        paths: zone.paths,
                        map: map,
                        clickable: !isFocusParent(zone) || paintParentAsGap,
                        editable: false,
                        draggable: false,
                    }, style));
                    if (frame.indexOf(zone) !== -1 || !focus) {
                        zone.paths.forEach(function (point) {
                            bounds.extend(point);
                        });
                    }
                    polygon.addListener('mouseover', function (event) {
                        polygon.setOptions({strokeWeight: (style.strokeWeight || 2) + 2});
                        if (!pinned && event && event.latLng) {
                            showInfo(zone, byId, event.latLng);
                        }
                    });
                    polygon.addListener('mouseout', function () {
                        if (polygon.__coveragePinned) {
                            return;
                        }
                        polygon.setOptions({strokeWeight: style.strokeWeight || 2});
                        if (!pinned) {
                            infoWindow.close();
                        }
                    });
                    polygon.addListener('click', function (event) {
                        suppressMapClick = true;
                        highlightZone(zone.id, byId, event && event.latLng);
                    });
                    overlays.push({polygon: polygon, zone: zone, baseWeight: style.strokeWeight || 2});
                });

                if (!bounds.isEmpty()) {
                    map.fitBounds(bounds, 48);
                }

                const focusZone = zones.find(isFocusParent);
                drawGaps(gapPayload, focusZone || null);

                gapsEl.onclick = function (event) {
                    const inside = event.target.closest('[data-focus-id]');
                    if (inside && parentSelect) {
                        parentSelect.value = inside.getAttribute('data-focus-id');
                        loadCoverage();
                        return;
                    }
                    const row = event.target.closest('[data-zone-id]');
                    if (row) {
                        highlightZone(row.getAttribute('data-zone-id'), byId);
                    }
                };
                if (uncoveredEl) {
                    uncoveredEl.onclick = function (event) {
                        const row = event.target.closest('[data-zone-id]');
                        if (row) {
                            highlightZone(row.getAttribute('data-zone-id'), byId);
                        }
                    };
                }
            }

            function loadCoverage() {
                const seq = ++loadSeq;
                setStatus(labels.loading, 'overlay');
                const params = new URLSearchParams();
                const parentId = (parentSelect && parentSelect.value) || panel.dataset.parentId || '';
                if (parentId) {
                    params.set('parent_id', parentId);
                }
                const url = panel.dataset.url + (params.toString() ? '?' + params.toString() : '');
                fetch(url, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}})
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('coverage');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        if (seq !== loadSeq || !map) {
                            return;
                        }
                        fillParents(Array.isArray(data.parents) ? data.parents : [], data.focus_id || '');
                        lastZones = Array.isArray(data.zones) ? data.zones : [];
                        lastGaps = data.gaps || {};
                        drawZones(lastZones, lastGaps);
                    })
                    .catch(function () {
                        if (seq !== loadSeq) {
                            return;
                        }
                        setStatus(labels.failed, 'banner');
                        if (window.toastr) {
                            toastr.error(labels.failed);
                        }
                    });
            }

            function ensureMap() {
                if (map || !canvas || !(window.google && google.maps)) {
                    return;
                }
                const lat = Number(panel.dataset.defaultLat);
                const lng = Number(panel.dataset.defaultLng);
                map = new google.maps.Map(canvas, {
                    center: {
                        lat: Number.isFinite(lat) ? lat : 23.757989,
                        lng: Number.isFinite(lng) ? lng : 90.360587,
                    },
                    zoom: 11,
                    mapTypeControl: true,
                    mapTypeControlOptions: {position: google.maps.ControlPosition.TOP_RIGHT},
                    zoomControl: true,
                    zoomControlOptions: {position: google.maps.ControlPosition.RIGHT_BOTTOM},
                    streetViewControl: false,
                    fullscreenControl: true,
                    fullscreenControlOptions: {position: google.maps.ControlPosition.TOP_RIGHT},
                    gestureHandling: 'greedy',
                    clickableIcons: false,
                });
                infoWindow = new google.maps.InfoWindow();
                map.addListener('click', function () {
                    if (suppressMapClick) {
                        suppressMapClick = false;
                        return;
                    }
                    clearCoverageSelection();
                });
            }

            function whenMapsReady(done) {
                if (window.google && window.google.maps && window.google.maps.Map) {
                    done();
                    return;
                }
                const waiters = window.__zoneCoverageMapWaiters || (window.__zoneCoverageMapWaiters = []);
                waiters.push(done);
                if (mapsWaitersHooked) {
                    return;
                }
                mapsWaitersHooked = true;
                const previous = window.initZoneGoogleMap;
                window.initZoneGoogleMap = function () {
                    window.__zoneGoogleMapsReady = true;
                    if (typeof previous === 'function') {
                        previous();
                    }
                    const queued = window.__zoneCoverageMapWaiters || [];
                    window.__zoneCoverageMapWaiters = [];
                    queued.forEach(function (fn) {
                        fn();
                    });
                };
                const existing = document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]');
                if (!existing) {
                    const script = document.createElement('script');
                    script.async = true;
                    script.defer = true;
                    script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(panel.dataset.mapsKey || '') + '&v=3.64&callback=initZoneGoogleMap';
                    document.head.appendChild(script);
                } else if (window.__zoneGoogleMapsReady) {
                    window.initZoneGoogleMap();
                }
            }

            function resizeMap() {
                if (!map || !window.google) {
                    return;
                }
                google.maps.event.trigger(map, 'resize');
            }

            function setOpen(next) {
                open = next;
                panel.classList.toggle('d-none', !open);
                table.classList.toggle('d-none', open);
                button.setAttribute('aria-pressed', open ? 'true' : 'false');
                if (buttonLabel) {
                    buttonLabel.textContent = open ? labels.listView : labels.mapView;
                }
                if (buttonIcon) {
                    buttonIcon.textContent = open ? 'view_list' : 'map';
                }
                if (!open) {
                    return;
                }
                whenMapsReady(function () {
                    ensureMap();
                    resizeMap();
                    loadCoverage();
                    window.setTimeout(resizeMap, 60);
                });
            }

            applyModeChrome();

            button.addEventListener('click', function () {
                setOpen(!open);
            });

            panel.addEventListener('click', function (event) {
                const modeButton = event.target.closest('[data-coverage-mode]');
                if (!modeButton) {
                    return;
                }
                const next = modeButton.getAttribute('data-coverage-mode');
                if (!next || next === viewMode) {
                    return;
                }
                viewMode = next;
                applyModeChrome();
                if (open && map && lastZones) {
                    drawZones(lastZones, lastGaps || {});
                }
            });

            if (parentSelect) {
                parentSelect.addEventListener('change', function () {
                    if (fillingParents || !open) {
                        return;
                    }
                    loadCoverage();
                });
            }

            document.addEventListener('input', function (event) {
                if (!open || !event.target || !event.target.classList || !event.target.classList.contains('zone-search-input')) {
                    return;
                }
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(applyListFilter, 150);
            });
        })();
    </script>
@endpush
