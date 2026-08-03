<script>
(function () {
    var MIRO_UNIT = 0.01;
    var MIRO_ZOOM_MIN = 0.005;
    var MIRO_ZOOM_MAX = 12;
    var MIRO_WHEEL_STEP = 0.004;
    var MIRO_PINCH_MULTIPLIER = 3;
    var MIRO_BUTTON_STEP = 1;

    var PG_PALETTE = {
        action: { fill: '#EFF6FF', stroke: '#3B82F6', fillOpacity: 1, color: '#1E3A8A' },
        decision: { fill: '#FEF9C3', stroke: '#D97706', fillOpacity: 1, color: '#92400E' },
        end_state: { fill: '#ECFDF5', stroke: '#059669', fillOpacity: 1, color: '#065F46' },
        end_terminal: { fill: '#FEE2E2', stroke: '#DC2626', fillOpacity: 1, color: '#991B1B' },
        message: { fill: '#D1FAE5', stroke: '#10B981', fillOpacity: 1, color: '#065F46' },
        channel: { fill: '#F5F3FF', stroke: '#7C3AED', fillOpacity: 1, color: '#5B21B6' }
    };
    var PG_EDGE = { stroke: '#475569', strokeWidth: 24, labelColor: '#334155' };

    function classifyNode(s) {
        var text = (s.text || '').toUpperCase().trim();
        var shape = s.shape;
        if (shape === 'rhombus') return text ? 'decision' : 'artifact';
        if (shape === 'wedge_round_rectangle_callout') return 'message';
        if ((s.w || 0) < 120 && (s.h || 0) < 120 && !text) return 'artifact';
        if (text.indexOf('CANCEL') >= 0 || text.indexOf('CANEL') >= 0) return 'end_terminal';
        if (/\b(UNKNOWN|CUSTOMER|PROVIDER|FUTURE CUSTOMER|INVALID)\s+LEAD\b/.test(text)) return 'end_state';
        if ((s.y || 0) < 1500 && shape === 'rectangle') return 'channel';
        return 'action';
    }

    function applyNodeTheme(raw, scaled) {
        var kind = classifyNode(raw);
        if (kind === 'artifact') return null;
        var theme = PG_PALETTE[kind];
        scaled.kind = kind;
        scaled.fill = theme.fill;
        scaled.stroke = theme.stroke;
        scaled.fillOpacity = theme.fillOpacity;
        scaled.color = theme.color;
        scaled.strokeWidth = Math.max((raw.strokeWidth || 2) * MIRO_UNIT, 0.08);
        return scaled;
    }

    function parseFlowGroups(flow) {
        try {
            return JSON.parse(flow.getAttribute('data-pg-groups') || '[]');
        } catch (e) {
            return [];
        }
    }

    function nodeMatchesGroup(raw, scaled, group) {
        if (group.nodeIds && group.nodeIds.indexOf(raw.id) >= 0) return true;
        if (group.matchKinds && group.matchKinds.indexOf(scaled.kind) >= 0) return true;
        var text = (raw.text || '').toUpperCase();
        if (group.matchTextContains) {
            for (var i = 0; i < group.matchTextContains.length; i++) {
                if (text.indexOf(String(group.matchTextContains[i]).toUpperCase()) >= 0) return true;
            }
        }
        return false;
    }

    function computeGroupBounds(members, pad) {
        var minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
        members.forEach(function (s) {
            minX = Math.min(minX, s.x - s.w / 2);
            minY = Math.min(minY, s.y - s.h / 2);
            maxX = Math.max(maxX, s.x + s.w / 2);
            maxY = Math.max(maxY, s.y + s.h / 2);
        });
        if (!isFinite(minX)) return null;
        return { x: minX - pad, y: minY - pad, w: maxX - minX + pad * 2, h: maxY - minY + pad * 2 };
    }

    function buildGroupBackdrop(frameX, frameY, frameW, frameH, headerH, headerGap, rx) {
        var g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'pg-flow-group-backdrop');

        var box = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        box.setAttribute('x', frameX);
        box.setAttribute('y', frameY);
        box.setAttribute('width', frameW);
        box.setAttribute('height', frameH);
        box.setAttribute('rx', rx);
        box.setAttribute('fill', 'rgba(237, 233, 254, 0.52)');
        box.setAttribute('stroke', '#7C3AED');
        box.setAttribute('stroke-width', '0.16');
        box.setAttribute('class', 'pg-flow-group-box');
        g.appendChild(box);

        var contentTop = frameY + headerH + headerGap;
        var inset = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        inset.setAttribute('x', frameX + 0.35);
        inset.setAttribute('y', contentTop);
        inset.setAttribute('width', Math.max(frameW - 0.7, 0));
        inset.setAttribute('height', Math.max(frameH - headerH - headerGap - 0.35, 0));
        inset.setAttribute('rx', Math.max(rx - 0.08, 0.12));
        inset.setAttribute('fill', 'rgba(255, 255, 255, 0.38)');
        inset.setAttribute('stroke', 'rgba(124, 58, 237, 0.22)');
        inset.setAttribute('stroke-width', '0.08');
        inset.setAttribute('class', 'pg-flow-group-content-bg');
        g.appendChild(inset);

        return g;
    }

    function buildGroupHeader(group, frameX, frameY, frameW, headerH, pad, rx, onOpen) {
        var g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'pg-flow-group pg-flow-group-header');
        g.setAttribute('data-group-id', group.id);

        var headerBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        headerBg.setAttribute('x', frameX);
        headerBg.setAttribute('y', frameY);
        headerBg.setAttribute('width', frameW);
        headerBg.setAttribute('height', headerH);
        headerBg.setAttribute('rx', rx);
        headerBg.setAttribute('fill', '#6D28D9');
        headerBg.setAttribute('class', 'pg-flow-group-header-bg');
        g.appendChild(headerBg);

        var fontSize = Math.max(headerH * 0.44, 1.8);
        var title = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        title.setAttribute('class', 'pg-flow-group-title');
        title.setAttribute('x', frameX + pad);
        title.setAttribute('y', frameY + headerH * 0.62);
        title.setAttribute('font-size', fontSize);
        title.textContent = 'Step ' + group.step + ' · ' + group.title;
        g.appendChild(title);

        var btnR = Math.max(headerH * 0.34, 0.85);
        var btnCx = frameX + frameW - pad - btnR;
        var btnCy = frameY + headerH * 0.5;
        var infoG = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        infoG.setAttribute('class', 'pg-flow-group-info-btn pg-group-info');
        infoG.setAttribute('role', 'button');
        infoG.setAttribute('aria-label', 'Show ' + group.title + ' details');
        infoG.setAttribute('tabindex', '0');

        var btnBg = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        btnBg.setAttribute('cx', btnCx);
        btnBg.setAttribute('cy', btnCy);
        btnBg.setAttribute('r', btnR);
        btnBg.setAttribute('fill', 'rgba(255, 255, 255, 0.16)');
        btnBg.setAttribute('stroke', 'rgba(255, 255, 255, 0.65)');
        btnBg.setAttribute('stroke-width', '0.08');
        infoG.appendChild(btnBg);

        var btnIcon = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        btnIcon.setAttribute('x', btnCx);
        btnIcon.setAttribute('y', btnCy + fontSize * 0.12);
        btnIcon.setAttribute('text-anchor', 'middle');
        btnIcon.setAttribute('dominant-baseline', 'middle');
        btnIcon.setAttribute('font-size', btnR * 1.05);
        btnIcon.setAttribute('font-weight', '700');
        btnIcon.setAttribute('fill', '#fff');
        btnIcon.setAttribute('pointer-events', 'none');
        btnIcon.textContent = 'i';
        infoG.appendChild(btnIcon);

        infoG.addEventListener('click', function (e) {
            e.stopPropagation();
            if (onOpen) onOpen(group, g);
        });
        infoG.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                e.stopPropagation();
                if (onOpen) onOpen(group, g);
            }
        });
        g.appendChild(infoG);

        return g;
    }

    function buildGroupFrame(group, bounds, maxMemberH, onOpen) {
        var headerH = Math.max(5.5, Math.min(maxMemberH * 0.48, 8));
        var headerGap = 1.15;
        var sidePad = 1.15;
        var rx = 0.4;
        var pad = 0.55;
        var frameX = bounds.x - sidePad;
        var frameY = bounds.y - headerH - headerGap;
        var frameW = bounds.w + sidePad * 2;
        var frameH = bounds.h + headerH + headerGap + sidePad * 0.55;

        return {
            backdrop: buildGroupBackdrop(frameX, frameY, frameW, frameH, headerH, headerGap, rx),
            header: buildGroupHeader(group, frameX, frameY, frameW, headerH, pad, rx, onOpen)
        };
    }

    function renderGroupDetailBody(group) {
        var html = '';
        if (group.intro) {
            html += '<p class="pg-detail-intro">' + group.intro + '</p>';
        }
        if (group.sections && group.sections.length) {
            html += '<div class="pg-detail-sections">';
            group.sections.forEach(function (section) {
                html += '<section class="pg-detail-section"><h5>' + section.title + '</h5>';
                if (section.items && section.items.length) {
                    html += '<ul>';
                    section.items.forEach(function (item) {
                        html += '<li>' + item + '</li>';
                    });
                    html += '</ul>';
                }
                html += '</section>';
            });
            html += '</div>';
        }
        if (group.notes && group.notes.length) {
            html += '<div class="pg-detail-notes"><h5>What to do next</h5><ul>';
            group.notes.forEach(function (note) {
                html += '<li>' + note + '</li>';
            });
            html += '</ul></div>';
        }
        return html;
    }

    function bindGroupDetail(flow, groups) {
        var panel = flow.querySelector('[data-pg-group-detail]');
        if (!panel) return;

        var stepEl = panel.querySelector('[data-pg-detail-step]');
        var titleEl = panel.querySelector('[data-pg-detail-title]');
        var subEl = panel.querySelector('[data-pg-detail-sub]');
        var bodyEl = panel.querySelector('[data-pg-detail-body]');
        var closeBtn = panel.querySelector('[data-pg-detail-close]');

        function setActiveGroupNodes(groupId) {
            flow.querySelectorAll('.pg-miro-node.is-group-active').forEach(function (el) {
                el.classList.remove('is-group-active');
            });
            if (!groupId) return;
            flow.querySelectorAll('.pg-miro-node[data-group-id="' + groupId + '"]').forEach(function (el) {
                el.classList.add('is-group-active');
            });
        }

        function closeDetail() {
            panel.hidden = true;
            panel.setAttribute('aria-hidden', 'true');
            flow.querySelectorAll('.pg-flow-group.is-active').forEach(function (el) {
                el.classList.remove('is-active');
            });
            setActiveGroupNodes(null);
        }

        flow._pgOpenGroupDetail = function (group, frameEl) {
            if (!group) return;
            flow.querySelectorAll('.pg-flow-group.is-active').forEach(function (el) {
                el.classList.remove('is-active');
            });
            if (frameEl) frameEl.classList.add('is-active');
            setActiveGroupNodes(group.id);
            if (stepEl) stepEl.textContent = 'Step ' + group.step;
            if (titleEl) titleEl.textContent = group.title;
            if (subEl) subEl.textContent = group.subtitle || '';
            if (bodyEl) bodyEl.innerHTML = renderGroupDetailBody(group);
            panel.hidden = false;
            panel.setAttribute('aria-hidden', 'false');
        };

        if (closeBtn) closeBtn.addEventListener('click', closeDetail);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.hidden) closeDetail();
        });
    }

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
        if (curve === 'elbowed') {
            return 'M' + a.x + ',' + a.y + ' L' + mx + ',' + a.y + ' L' + mx + ',' + b.y + ' L' + b.x + ',' + b.y;
        }
        return 'M' + a.x + ',' + a.y + ' L' + b.x + ',' + b.y;
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

    function shapeNode(s, onClick, flow) {
        var g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'pg-miro-node pg-node-' + (s.kind || 'action'));
        g.setAttribute('data-id', s.id);
        g.setAttribute('data-kind', s.kind || 'action');
        if (s.groupId) g.setAttribute('data-group-id', s.groupId);
        g.setAttribute('transform', 'rotate(' + (s.rotation || 0) + ' ' + s.x + ' ' + s.y + ')');
        if (flow && flow.dataset.pgEditMode === '1') g.classList.add('is-editable');

        var fill = s.fill || '#fff';
        var stroke = s.stroke || '#1a1a1a';
        var sw = s.strokeWidth || 2;
        var cornerR = Math.min(s.w, s.h);
        if (s.kind === 'end_state' || s.kind === 'end_terminal') cornerR *= 0.22;
        else if (s.shape === 'round_rectangle') cornerR *= 0.06;
        else if (s.kind === 'channel' || s.kind === 'action') cornerR *= 0.04;
        else cornerR = 0;

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
            rect.setAttribute('rx', cornerR);
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
            if (flow && flow._pgOnNodeClick) {
                flow._pgOnNodeClick(s, g, e);
                return;
            }
            if (onClick) onClick(s, g);
        });
        g.addEventListener('dblclick', function (e) {
            e.stopPropagation();
            if (flow && flow.dataset.pgEditMode === '1' && flow._pgOnNodeDblClick) {
                flow._pgOnNodeDblClick(s, g, e);
            }
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

    function buildSvg(board, groups, flow) {
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
        var marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        marker.setAttribute('id', 'pg-arrowhead');
        marker.setAttribute('markerUnits', 'userSpaceOnUse');
        marker.setAttribute('markerWidth', '0.32');
        marker.setAttribute('markerHeight', '0.32');
        marker.setAttribute('refX', '0.28');
        marker.setAttribute('refY', '0.16');
        marker.setAttribute('orient', 'auto');
        var arrowHead = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        arrowHead.setAttribute('d', 'M0,0 L0.32,0.16 L0,0.32 Z');
        arrowHead.setAttribute('fill', PG_EDGE.stroke);
        marker.appendChild(arrowHead);
        defs.appendChild(marker);
        svg.appendChild(defs);

        var bg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        bg.setAttribute('width', w);
        bg.setAttribute('height', h);
        bg.setAttribute('fill', 'url(#pg-miro-grid)');
        svg.appendChild(bg);

        var map = {};
        var rawById = {};
        var edgeStroke = PG_EDGE.strokeWidth * MIRO_UNIT;
        board.shapes.forEach(function (s) {
            rawById[s.id] = s;
            var themed = applyNodeTheme(s, scaleShape(s, ox, oy));
            if (themed) map[s.id] = themed;
        });

        var groupsBgG = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        groupsBgG.setAttribute('class', 'pg-miro-groups-bg');
        var groupsChromeG = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        groupsChromeG.setAttribute('class', 'pg-miro-groups-chrome');
        (groups || []).forEach(function (group) {
            var members = board.shapes
                .filter(function (s) { return map[s.id] && nodeMatchesGroup(s, map[s.id], group); })
                .map(function (s) { return map[s.id]; });
            members.forEach(function (m) { m.groupId = group.id; });
            var bounds = computeGroupBounds(members, 1.25);
            if (!bounds) return;
            var maxMemberH = members.reduce(function (max, s) { return Math.max(max, s.h || 0); }, 1);
            var parts = buildGroupFrame(group, bounds, maxMemberH, function (g, frame) {
                if (flow && flow._pgOpenGroupDetail) flow._pgOpenGroupDetail(g, frame);
            });
            groupsBgG.appendChild(parts.backdrop);
            groupsChromeG.appendChild(parts.header);
        });
        svg.appendChild(groupsBgG);

        var edges = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        edges.setAttribute('class', 'pg-miro-edges');
        board.connectors.forEach(function (c) {
            if (!c.from || !c.to || !map[c.from] || !map[c.to]) return;
            var a = anchor(map[c.from], c.startPos, c.startSnap);
            var bpt = anchor(map[c.to], c.endPos, c.endSnap);
            var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('class', 'pg-miro-edge');
            path.setAttribute('d', connectorPath(a, bpt, c.curve));
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', PG_EDGE.stroke);
            path.setAttribute('stroke-width', edgeStroke);
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('stroke-linejoin', 'round');
            path.setAttribute('marker-end', 'url(#pg-arrowhead)');
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
            t.setAttribute('fill', PG_EDGE.labelColor);
            t.setAttribute('font-size', (l.fontSize || 900) * MIRO_UNIT * 0.92);
            t.textContent = l.text;
            labelsG.appendChild(t);
        });
        svg.appendChild(labelsG);

        var nodes = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        board.shapes.forEach(function (s) {
            if (!map[s.id]) return;
            nodes.appendChild(shapeNode(map[s.id], function (shape, el) {
                nodes.querySelectorAll('.is-selected').forEach(function (n) { n.classList.remove('is-selected'); });
                el.classList.add('is-selected');
            }, flow));
        });
        svg.appendChild(nodes);
        svg.appendChild(groupsChromeG);
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
            if (e.button !== 0 || e.target.closest('button') || e.target.closest('.pg-group-info') || e.target.closest('.pg-flow-group-info-btn')) return;
            if (flow.dataset.pgEditMode === '1' && e.target.closest('.pg-miro-node')) return;
            if (flow._pgNodeDragging) return;
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

    function mountBoard(flow, board, groups, opts) {
        opts = opts || {};
        var stage = flow.querySelector('.pg-flow-stage');
        var vp = flow.querySelector('.pg-flow-viewport');
        var preserveView = opts.preserveView && flow._pgView;
        var savedView = preserveView ? copyView(flow._pgView) : null;

        stage.innerHTML = '';
        var b = board.bounds;
        flow.dataset.pgBoardW = (b.maxX - b.minX) * MIRO_UNIT;
        flow.dataset.pgBoardH = (b.maxY - b.minY) * MIRO_UNIT;
        flow._pgBoard = board;
        flow._pgGroups = groups;
        flow.setAttribute('data-pg-groups', JSON.stringify(groups || []));
        bindGroupDetail(flow, groups);
        stage.appendChild(buildSvg(board, groups, flow));
        vp.classList.remove('is-loading');
        flow.classList.add('is-ready');
        bindPanZoom(flow);
        flow.dispatchEvent(new CustomEvent('pg-board-mounted', { detail: { board: board, groups: groups } }));
        if (savedView) applyView(flow, savedView);
        else requestAnimationFrame(function () { requestAnimationFrame(function () { fitChart(flow); }); });
    }

    function loadBoard(flow) {
        var url = flow.getAttribute('data-pg-board-url');
        var vp = flow.querySelector('.pg-flow-viewport');
        flow.classList.remove('is-failed', 'is-ready');
        delete flow.dataset.pgInitialFit;
        if (!flow.dataset.pgEditMode) delete flow._pgView;
        stopCameraAnim(flow);

        return fetch(url, { credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(function (board) {
                mountBoard(flow, board, parseFlowGroups(flow));
            })
            .catch(function () {
                flow.classList.add('is-failed');
                vp.classList.remove('is-loading');
            });
    }

    window.ProcessGuideCanvas = {
        MIRO_UNIT: MIRO_UNIT,
        getView: getView,
        applyView: applyView,
        remount: function (flow, opts) {
            if (!flow._pgBoard) return;
            mountBoard(flow, flow._pgBoard, flow._pgGroups || parseFlowGroups(flow), opts || { preserveView: true });
        }
    };

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
