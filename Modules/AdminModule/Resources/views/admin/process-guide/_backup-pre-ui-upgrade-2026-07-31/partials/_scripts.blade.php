<script>
(function () {
    var MIRO_UNIT = 0.01;
    var MIRO_ZOOM_MIN = 0.005;
    var MIRO_ZOOM_MAX = 12;
    var MIRO_WHEEL_STEP = 0.004;
    var MIRO_PINCH_MULTIPLIER = 3;
    var MIRO_BUTTON_STEP = 1;

    function anchor(shape, pos, snap) {
        if (!shape) return { x: 0, y: 0 };
        var x = shape.x, y = shape.y, w = shape.w, h = shape.h;
        if (snap === 'auto') return { x: x, y: y };
        var px = (pos && pos.x != null) ? pos.x : 0.5;
        var py = (pos && pos.y != null) ? pos.y : 0.5;
        if (snap === 'top') return { x: x, y: y - h / 2 };
        if (snap === 'bottom') return { x: x, y: y + h / 2 };
        if (snap === 'left') return { x: x - w / 2, y: y };
        if (snap === 'right') return { x: x + w / 2, y: y };
        return { x: x - w / 2 + w * px, y: y - h / 2 + h * py };
    }

    function connectorPath(a, b, curve) {
        var mx = (a.x + b.x) / 2;
        var my = (a.y + b.y) / 2;
        var dx = b.x - a.x;
        var dy = b.y - a.y;
        var dist = Math.hypot(dx, dy) || 1;
        var nx = -dy / dist;
        var ny = dx / dist;
        var bulge = Math.min(dist * 0.22, 120);
        if (curve === 'elbowed') {
            return 'M' + a.x + ',' + a.y + ' L' + mx + ',' + a.y + ' L' + mx + ',' + b.y + ' L' + b.x + ',' + b.y;
        }
        return 'M' + a.x + ',' + a.y + ' Q' + (mx + nx * bulge) + ',' + (my + ny * bulge) + ' ' + b.x + ',' + b.y;
    }

    function labelFontSize(s) {
        var ratio = s.fontRatio || 0.055;
        var fs = s.h * ratio;
        var lines = (s.text || '').split(/\n/);
        var lineH = fs * 1.18;
        var needed = lines.length * lineH;
        var maxH = s.h * 0.9;
        if (needed > maxH && lines.length > 0) {
            fs = maxH / (lines.length * 1.18);
        }
        return Math.max(fs * 0.92, 1.5);
    }

    function shapeNode(s, onClick) {
        var g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'pg-miro-node');
        g.setAttribute('data-id', s.id);
        g.setAttribute('transform', 'rotate(' + (s.rotation || 0) + ' ' + s.x + ' ' + s.y + ')');

        var fill = s.fill || '#fff';
        var stroke = s.stroke || '#1a1a1a';
        var sw = s.strokeWidth || 2;

        if (s.shape === 'rhombus') {
            var hw = s.w / 2, hh = s.h / 2;
            var poly = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
            poly.setAttribute('points', s.x + ',' + (s.y - hh) + ' ' + (s.x + hw) + ',' + s.y + ' ' + s.x + ',' + (s.y + hh) + ' ' + (s.x - hw) + ',' + s.y);
            poly.setAttribute('fill', fill);
            poly.setAttribute('fill-opacity', s.fillOpacity != null ? s.fillOpacity : 1);
            poly.setAttribute('stroke', stroke);
            poly.setAttribute('stroke-width', sw);
            g.appendChild(poly);
        } else if (s.shape === 'wedge_round_rectangle_callout') {
            var r = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            r.setAttribute('x', s.x - s.w / 2);
            r.setAttribute('y', s.y - s.h / 2);
            r.setAttribute('width', s.w);
            r.setAttribute('height', s.h * 0.88);
            r.setAttribute('rx', Math.min(s.w, s.h) * 0.08);
            r.setAttribute('fill', fill);
            r.setAttribute('fill-opacity', s.fillOpacity != null ? s.fillOpacity : 1);
            r.setAttribute('stroke', stroke);
            r.setAttribute('stroke-width', sw);
            g.appendChild(r);
            var ty = s.y + s.h * 0.38;
            var tail = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
            tail.setAttribute('points', (s.x - s.w * 0.08) + ',' + ty + ' ' + (s.x + s.w * 0.08) + ',' + ty + ' ' + s.x + ',' + (s.y + s.h / 2));
            tail.setAttribute('fill', fill);
            tail.setAttribute('fill-opacity', s.fillOpacity != null ? s.fillOpacity : 1);
            tail.setAttribute('stroke', stroke);
            tail.setAttribute('stroke-width', sw);
            g.appendChild(tail);
        } else {
            var rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            rect.setAttribute('x', s.x - s.w / 2);
            rect.setAttribute('y', s.y - s.h / 2);
            rect.setAttribute('width', s.w);
            rect.setAttribute('height', s.h);
            rect.setAttribute('rx', s.shape === 'round_rectangle' ? Math.min(s.w, s.h) * 0.06 : 0);
            rect.setAttribute('fill', fill);
            rect.setAttribute('fill-opacity', s.fillOpacity != null ? s.fillOpacity : 1);
            rect.setAttribute('stroke', stroke);
            rect.setAttribute('stroke-width', sw);
            g.appendChild(rect);
        }

        if (s.text) {
            var fontSize = labelFontSize(s);
            var tw = s.w * 0.9;
            var th = s.h * 0.9;
            var fo = document.createElementNS('http://www.w3.org/2000/svg', 'foreignObject');
            fo.setAttribute('x', s.x - tw / 2);
            fo.setAttribute('y', s.y - th / 2);
            fo.setAttribute('width', tw);
            fo.setAttribute('height', th);
            var div = document.createElement('div');
            div.setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
            div.className = 'pg-miro-label';
            div.style.color = s.color || '#1a1a1a';
            div.style.fontSize = fontSize + 'px';
            div.style.lineHeight = '1.18';
            div.textContent = s.text;
            fo.appendChild(div);
            g.appendChild(fo);
        }

        g.addEventListener('click', function (e) {
            e.stopPropagation();
            if (onClick) onClick(s, g);
        });
        return g;
    }

    function scaleShape(s, ox, oy) {
        var rawH = s.h || 1;
        return {
            id: s.id,
            x: (s.x - ox) * MIRO_UNIT,
            y: (s.y - oy) * MIRO_UNIT,
            w: s.w * MIRO_UNIT,
            h: s.h * MIRO_UNIT,
            rotation: s.rotation || 0,
            shape: s.shape,
            fill: s.fill,
            stroke: s.stroke,
            strokeWidth: (s.strokeWidth || 2) * MIRO_UNIT,
            fillOpacity: s.fillOpacity,
            color: s.color,
            fontRatio: (s.fontSize || 288) / rawH,
            text: s.text
        };
    }

    function buildSvg(board) {
        var b = board.bounds;
        var ox = b.minX;
        var oy = b.minY;
        var w = (b.maxX - b.minX) * MIRO_UNIT;
        var h = (b.maxY - b.minY) * MIRO_UNIT;

        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'pg-miro-svg');
        svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
        svg.setAttribute('width', '100%');
        svg.setAttribute('height', '100%');
        svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
        svg.setAttribute('shape-rendering', 'geometricPrecision');
        svg.setAttribute('text-rendering', 'geometricPrecision');

        var defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        var pat = document.createElementNS('http://www.w3.org/2000/svg', 'pattern');
        pat.setAttribute('id', 'pg-miro-grid');
        pat.setAttribute('width', '20');
        pat.setAttribute('height', '20');
        pat.setAttribute('patternUnits', 'userSpaceOnUse');
        var gridBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        gridBg.setAttribute('width', '20');
        gridBg.setAttribute('height', '20');
        gridBg.setAttribute('fill', '#f2f2f2');
        pat.appendChild(gridBg);
        var dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        dot.setAttribute('cx', '0');
        dot.setAttribute('cy', '0');
        dot.setAttribute('r', '0.4');
        dot.setAttribute('fill', '#d1d5db');
        pat.appendChild(dot);
        defs.appendChild(pat);
        svg.appendChild(defs);

        var bg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        bg.setAttribute('width', w);
        bg.setAttribute('height', h);
        bg.setAttribute('fill', 'url(#pg-miro-grid)');
        svg.appendChild(bg);

        var map = {};
        board.shapes.forEach(function (s) { map[s.id] = scaleShape(s, ox, oy); });

        var edges = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        board.connectors.forEach(function (c) {
            if (!c.from || !c.to || !map[c.from] || !map[c.to]) return;
            var a = anchor(map[c.from], c.startPos, c.startSnap);
            var bpt = anchor(map[c.to], c.endPos, c.endSnap);
            var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', connectorPath(a, bpt, c.curve));
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', c.stroke || '#333');
            path.setAttribute('stroke-width', (c.strokeWidth || 24) * MIRO_UNIT);
            path.setAttribute('stroke-linecap', 'round');
            edges.appendChild(path);
        });
        svg.appendChild(edges);

        var labelsG = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        board.labels.forEach(function (l) {
            var t = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            t.setAttribute('x', (l.x - ox) * MIRO_UNIT);
            t.setAttribute('y', (l.y - oy) * MIRO_UNIT);
            t.setAttribute('class', 'pg-miro-edge-label');
            t.setAttribute('text-anchor', 'middle');
            t.setAttribute('dominant-baseline', 'middle');
            t.setAttribute('font-size', (l.fontSize || 900) * MIRO_UNIT * 0.92);
            t.textContent = l.text;
            labelsG.appendChild(t);
        });
        svg.appendChild(labelsG);

        var nodes = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        board.shapes.forEach(function (s) {
            nodes.appendChild(shapeNode(map[s.id], function (shape, el) {
                nodes.querySelectorAll('.is-selected').forEach(function (n) { n.classList.remove('is-selected'); });
                el.classList.add('is-selected');
            }));
        });
        svg.appendChild(nodes);
        return svg;
    }

    function copyView(v) { return { x: v.x, y: v.y, w: v.w, h: v.h }; }

    function getView(flow) {
        if (flow._pgView) return copyView(flow._pgView);
        return {
            x: parseFloat(flow.dataset.pgViewX) || 0,
            y: parseFloat(flow.dataset.pgViewY) || 0,
            w: parseFloat(flow.dataset.pgViewW) || parseFloat(flow.dataset.pgBoardW) || 1,
            h: parseFloat(flow.dataset.pgViewH) || parseFloat(flow.dataset.pgBoardH) || 1
        };
    }

    function clampMiroZoom(z) { return Math.min(Math.max(z, MIRO_ZOOM_MIN), MIRO_ZOOM_MAX); }

    function getMiroZoom(flow) {
        var vp = flow.querySelector('.pg-flow-viewport');
        var view = getView(flow);
        if (!vp || !view.w) return 1;
        return vp.clientWidth / view.w;
    }

    function clampView(flow, view) {
        var vp = flow.querySelector('.pg-flow-viewport');
        if (!vp || view.w <= 0) return view;
        var z = clampMiroZoom(vp.clientWidth / view.w);
        var viewW = vp.clientWidth / z;
        var viewH = vp.clientHeight / z;
        var cx = view.x + view.w * 0.5;
        var cy = view.y + view.h * 0.5;
        return { x: cx - viewW * 0.5, y: cy - viewH * 0.5, w: viewW, h: viewH };
    }

    function updateZoomLabel(flow) {
        var lbl = flow.querySelector('[data-pg-zoom-label]');
        if (lbl) lbl.textContent = Math.round(clampMiroZoom(getMiroZoom(flow)) * 100) + '%';
    }

    function applyView(flow, view) {
        var svg = flow.querySelector('.pg-miro-svg');
        if (!svg) return;
        view = clampView(flow, view);
        flow._pgView = copyView(view);
        svg.setAttribute('viewBox', view.x + ' ' + view.y + ' ' + view.w + ' ' + view.h);
        flow.dataset.pgViewX = view.x;
        flow.dataset.pgViewY = view.y;
        flow.dataset.pgViewW = view.w;
        flow.dataset.pgViewH = view.h;
        updateZoomLabel(flow);
    }

    function viewForMiroZoom(flow, miroZoom, fx, fy) {
        var vp = flow.querySelector('.pg-flow-viewport');
        if (!vp) return null;
        miroZoom = clampMiroZoom(miroZoom);
        var viewW = vp.clientWidth / miroZoom;
        var viewH = vp.clientHeight / miroZoom;
        var mx = fx != null ? fx : 0.5;
        var my = fy != null ? fy : 0.5;
        var view = getView(flow);
        var bx = view.x + mx * view.w;
        var by = view.y + my * view.h;
        return { x: bx - mx * viewW, y: by - my * viewH, w: viewW, h: viewH };
    }

    function setMiroZoom(flow, miroZoom, fx, fy) {
        var next = viewForMiroZoom(flow, miroZoom, fx, fy);
        if (next) applyView(flow, next);
    }

    function computeFitView(flow) {
        var vp = flow.querySelector('.pg-flow-viewport');
        var boardW = parseFloat(flow.dataset.pgBoardW);
        var boardH = parseFloat(flow.dataset.pgBoardH);
        if (!vp || !boardW || !boardH) return null;
        var vpW = vp.clientWidth;
        var vpH = vp.clientHeight;
        if (vpW < 10 || vpH < 10) return null;
        var pad = 32;
        var scale = Math.min((vpW - pad * 2) / boardW, (vpH - pad * 2) / boardH);
        return {
            x: (boardW - vpW / scale) / 2,
            y: (boardH - vpH / scale) / 2,
            w: vpW / scale,
            h: vpH / scale
        };
    }

    function fitChart(flow) { var v = computeFitView(flow); if (v) applyView(flow, v); }

    function wheelZoomDelta(deltaY, deltaMode, isPinch) {
        var mult = isPinch ? MIRO_PINCH_MULTIPLIER : 1;
        if (deltaMode === 1) return -deltaY * 0.32 * mult;
        if (deltaMode === 2) return -deltaY * 2.8 * mult;
        return -deltaY * MIRO_WHEEL_STEP * mult;
    }

    function stopCameraAnim(flow) {
        if (flow._pgAnimFrame) cancelAnimationFrame(flow._pgAnimFrame);
        flow._pgAnimFrame = 0;
        flow._pgMomentum = false;
    }

    function bindPanZoom(flow) {
        if (flow.dataset.pgBound === '1') return;
        flow.dataset.pgBound = '1';
        var vp = flow.querySelector('.pg-flow-viewport');
        if (!vp) return;

        var dragging = false, sx = 0, sy = 0, ox = 0, oy = 0, dragView = null;
        var velX = 0, velY = 0, lastX = 0, lastY = 0, lastT = 0;
        var wheelRaf = 0, wheelState = null, panRaf = 0, panPending = null;

        function boardDelta(dx, dy, view) {
            return {
                x: -(dx * (view.w / vp.clientWidth)),
                y: -(dy * (view.h / vp.clientHeight))
            };
        }

        function flushWheelFrame() {
            wheelRaf = 0;
            if (!wheelState) return;
            var state = wheelState;
            wheelState = null;
            if (Math.abs(state.delta) >= 0.01) {
                setMiroZoom(flow, clampMiroZoom(getMiroZoom(flow) + wheelZoomDelta(state.delta, state.deltaMode, state.isPinch)), state.mx, state.my);
            }
            if (wheelState) wheelRaf = requestAnimationFrame(flushWheelFrame);
            else vp.classList.remove('is-zooming');
        }

        vp.addEventListener('wheel', function (e) {
            e.preventDefault();
            stopCameraAnim(flow);
            var rect = vp.getBoundingClientRect();
            if (!wheelState) wheelState = { mx: 0, my: 0, delta: 0, deltaMode: e.deltaMode, isPinch: false };
            wheelState.mx = (e.clientX - rect.left) / rect.width;
            wheelState.my = (e.clientY - rect.top) / rect.height;
            wheelState.delta += e.deltaY;
            wheelState.deltaMode = e.deltaMode;
            wheelState.isPinch = wheelState.isPinch || e.ctrlKey;
            if (!wheelRaf) {
                vp.classList.add('is-zooming');
                wheelRaf = requestAnimationFrame(flushWheelFrame);
            }
        }, { passive: false });

        vp.addEventListener('pointerdown', function (e) {
            if (e.button !== 0 || e.target.closest('button')) return;
            stopCameraAnim(flow);
            dragging = true;
            sx = e.clientX; sy = e.clientY;
            lastX = sx; lastY = sy; lastT = performance.now();
            velX = velY = 0;
            dragView = getView(flow);
            ox = dragView.x; oy = dragView.y;
            panPending = { dx: 0, dy: 0 };
            vp.classList.add('is-dragging');
            vp.setPointerCapture(e.pointerId);
            if (!panRaf) panRaf = requestAnimationFrame(flushPan);
        });

        function flushPan() {
            panRaf = 0;
            if (panPending && dragging && dragView) {
                applyView(flow, { x: ox + panPending.dx, y: oy + panPending.dy, w: dragView.w, h: dragView.h });
            }
            if (dragging) panRaf = requestAnimationFrame(flushPan);
        }

        vp.addEventListener('pointermove', function (e) {
            if (!dragging || !dragView) return;
            var now = performance.now();
            var dt = Math.max(now - lastT, 1);
            velX = 0.85 * velX + 0.15 * ((e.clientX - lastX) / dt);
            velY = 0.85 * velY + 0.15 * ((e.clientY - lastY) / dt);
            lastX = e.clientX; lastY = e.clientY; lastT = now;
            var d = boardDelta(e.clientX - sx, e.clientY - sy, dragView);
            panPending = { dx: d.x, dy: d.y };
        });

        function endDrag() {
            if (!dragging) return;
            dragging = false;
            dragView = null;
            panPending = null;
            if (panRaf) { cancelAnimationFrame(panRaf); panRaf = 0; }
            vp.classList.remove('is-dragging');
        }
        vp.addEventListener('pointerup', endDrag);
        vp.addEventListener('pointercancel', endDrag);

        flow.querySelector('[data-pg-fit-all]')?.addEventListener('click', function () { fitChart(flow); });
        flow.querySelector('[data-pg-recenter]')?.addEventListener('click', function () {
            var view = getView(flow);
            var boardW = parseFloat(flow.dataset.pgBoardW);
            var boardH = parseFloat(flow.dataset.pgBoardH);
            applyView(flow, { x: (boardW - view.w) / 2, y: (boardH - view.h) / 2, w: view.w, h: view.h });
        });
        flow.querySelector('[data-pg-zoom-in]')?.addEventListener('click', function () {
            setMiroZoom(flow, getMiroZoom(flow) + MIRO_BUTTON_STEP, 0.5, 0.5);
        });
        flow.querySelector('[data-pg-zoom-out]')?.addEventListener('click', function () {
            setMiroZoom(flow, getMiroZoom(flow) - MIRO_BUTTON_STEP, 0.5, 0.5);
        });
        flow.querySelector('[data-pg-fullscreen]')?.addEventListener('click', function () {
            if (document.fullscreenElement) document.exitFullscreen();
            else if (flow.requestFullscreen) flow.requestFullscreen();
        });

        if (typeof ResizeObserver !== 'undefined') {
            var ro = new ResizeObserver(function () {
                if (flow.classList.contains('is-ready') && flow.dataset.pgInitialFit !== '1') {
                    var v = computeFitView(flow);
                    if (v) { applyView(flow, v); flow.dataset.pgInitialFit = '1'; ro.disconnect(); }
                }
            });
            ro.observe(vp);
        }
    }

    function loadBoard(flow) {
        var url = flow.getAttribute('data-pg-board-url');
        var stage = flow.querySelector('.pg-flow-stage');
        var vp = flow.querySelector('.pg-flow-viewport');
        flow.classList.remove('is-failed', 'is-ready');
        delete flow.dataset.pgInitialFit;
        delete flow._pgView;
        stopCameraAnim(flow);

        return fetch(url, { credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(function (board) {
                stage.innerHTML = '';
                var b = board.bounds;
                flow.dataset.pgBoardW = (b.maxX - b.minX) * MIRO_UNIT;
                flow.dataset.pgBoardH = (b.maxY - b.minY) * MIRO_UNIT;
                stage.appendChild(buildSvg(board));
                vp.classList.remove('is-loading');
                flow.classList.add('is-ready');
                bindPanZoom(flow);
                requestAnimationFrame(function () { requestAnimationFrame(function () { fitChart(flow); }); });
            })
            .catch(function () {
                flow.classList.add('is-failed');
                vp.classList.remove('is-loading');
            });
    }

    function boot() {
        document.querySelectorAll('.pg-flow.is-miro-canvas').forEach(function (flow) {
            loadBoard(flow);
            var retry = flow.querySelector('[data-pg-retry]');
            if (retry) retry.addEventListener('click', function () { loadBoard(flow); });
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
</script>
