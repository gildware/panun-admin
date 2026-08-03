<script>
(function () {
    var MIRO_UNIT = window.ProcessGuideCanvas ? window.ProcessGuideCanvas.MIRO_UNIT : 0.01;

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function uid(prefix) {
        return (prefix || 'pg') + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 7);
    }

    function findRawShape(flow, id) {
        var board = flow._pgBoard;
        if (!board) return null;
        for (var i = 0; i < board.shapes.length; i++) {
            if (board.shapes[i].id === id) return board.shapes[i];
        }
        return null;
    }

    function recomputeBounds(board) {
        var xs = [], ys = [];
        (board.shapes || []).forEach(function (s) {
            xs.push(s.x - s.w / 2, s.x + s.w / 2);
            ys.push(s.y - s.h / 2, s.y + s.h / 2);
        });
        (board.labels || []).forEach(function (l) {
            xs.push(l.x);
            ys.push(l.y);
        });
        if (!xs.length) return;
        var pad = 500;
        board.bounds = {
            minX: Math.min.apply(null, xs) - pad,
            minY: Math.min.apply(null, ys) - pad,
            maxX: Math.max.apply(null, xs) + pad,
            maxY: Math.max.apply(null, ys) + pad
        };
    }

    function viewCenterRaw(flow) {
        var view = window.ProcessGuideCanvas.getView(flow);
        var cx = view.x + view.w * 0.5;
        var cy = view.y + view.h * 0.5;
        var b = flow._pgBoard.bounds;
        return {
            x: cx / MIRO_UNIT + b.minX,
            y: cy / MIRO_UNIT + b.minY
        };
    }

    function screenDeltaToRaw(flow, dx, dy) {
        var vp = flow.querySelector('.pg-flow-viewport');
        var view = window.ProcessGuideCanvas.getView(flow);
        return {
            x: dx * (view.w / vp.clientWidth) / MIRO_UNIT,
            y: dy * (view.h / vp.clientHeight) / MIRO_UNIT
        };
    }

    function initEditor(flow) {
        if (flow.dataset.pgEditorInit === '1') return;
        flow.dataset.pgEditorInit = '1';

        var panel = flow.closest('.pg-flow-layout')?.querySelector('[data-pg-editor-panel]');
        if (!panel) return;

        var editToggle = flow.querySelector('[data-pg-edit-toggle]');
        var closeBtn = panel.querySelector('[data-pg-edit-close]');
        var saveBtn = panel.querySelector('[data-pg-save]');
        var saveStatus = panel.querySelector('[data-pg-save-status]');
        var noSel = panel.querySelector('[data-pg-edit-no-selection]');
        var selFields = panel.querySelector('[data-pg-edit-selection]');
        var nodeText = panel.querySelector('[data-pg-edit-node-text]');
        var nodeShape = panel.querySelector('[data-pg-edit-node-shape]');
        var deleteNodeBtn = panel.querySelector('[data-pg-edit-delete-node]');
        var groupsList = panel.querySelector('[data-pg-groups-list]');
        var groupForm = panel.querySelector('[data-pg-group-form]');
        var groupStep = panel.querySelector('[data-pg-group-step]');
        var groupTitle = panel.querySelector('[data-pg-group-title]');
        var groupSubtitle = panel.querySelector('[data-pg-group-subtitle]');
        var groupIntro = panel.querySelector('[data-pg-group-intro]');
        var groupNotes = panel.querySelector('[data-pg-group-notes]');
        var groupSections = panel.querySelector('[data-pg-group-sections]');
        var addGroupBtn = panel.querySelector('[data-pg-add-group]');
        var assignGroupBtn = panel.querySelector('[data-pg-assign-group]');
        var deleteGroupBtn = panel.querySelector('[data-pg-delete-group]');

        var selectedIds = [];
        var activeGroupId = null;
        var dirty = false;

        function setDirty(on) {
            dirty = on;
            if (saveStatus && on) saveStatus.textContent = 'Unsaved changes';
        }

        function setEditMode(on) {
            flow.dataset.pgEditMode = on ? '1' : '';
            flow.classList.toggle('is-edit-mode', on);
            panel.hidden = !on;
            panel.setAttribute('aria-hidden', on ? 'false' : 'true');
            flow.closest('.pg-flow-layout')?.classList.toggle('is-editing', on);
            var hint = flow.querySelector('.pg-flow-toolbar-hint');
            if (hint) {
                hint.textContent = on
                    ? 'Edit mode · drag shapes · double-click to edit text · save when done'
                    : 'Drag to pan · scroll or +/− to zoom · click ⓘ on a group for details';
            }
            if (editToggle) {
                editToggle.textContent = on ? 'Done editing' : 'Edit flowchart';
                editToggle.classList.toggle('is-active', on);
            }
            if (!on) {
                selectedIds = [];
                syncSelectionUI();
            }
            window.ProcessGuideCanvas.remount(flow, { preserveView: true });
        }

        function syncSelectionUI() {
            var has = selectedIds.length === 1;
            if (noSel) noSel.hidden = has;
            if (selFields) selFields.hidden = !has;
            if (!has) return;
            var raw = findRawShape(flow, selectedIds[0]);
            if (!raw) return;
            if (nodeText) nodeText.value = raw.text || '';
            if (nodeShape) nodeShape.value = raw.shape || 'rectangle';
            flow.querySelectorAll('.pg-miro-node.is-selected').forEach(function (n) { n.classList.remove('is-selected'); });
            var el = flow.querySelector('.pg-miro-node[data-id="' + selectedIds[0] + '"]');
            if (el) el.classList.add('is-selected');
        }

        function selectNode(id, additive) {
            if (!additive) selectedIds = [];
            if (selectedIds.indexOf(id) < 0) selectedIds.push(id);
            else if (additive) selectedIds = selectedIds.filter(function (x) { return x !== id; });
            else selectedIds = [id];
            syncSelectionUI();
        }

        flow._pgOnNodeClick = function (scaled, el, e) {
            if (flow.dataset.pgEditMode !== '1') return;
            selectNode(scaled.id, e.shiftKey);
        };

        flow._pgOnNodeDblClick = function (scaled) {
            var raw = findRawShape(flow, scaled.id);
            if (!raw) return;
            var next = window.prompt('Edit label text:', raw.text || '');
            if (next === null) return;
            raw.text = next;
            setDirty(true);
            window.ProcessGuideCanvas.remount(flow, { preserveView: true });
            selectNode(scaled.id, false);
        };

        if (nodeText) {
            nodeText.addEventListener('change', function () {
                if (selectedIds.length !== 1) return;
                var raw = findRawShape(flow, selectedIds[0]);
                if (!raw) return;
                raw.text = nodeText.value;
                setDirty(true);
                window.ProcessGuideCanvas.remount(flow, { preserveView: true });
            });
        }

        if (nodeShape) {
            nodeShape.addEventListener('change', function () {
                if (selectedIds.length !== 1) return;
                var raw = findRawShape(flow, selectedIds[0]);
                if (!raw) return;
                raw.shape = nodeShape.value;
                setDirty(true);
                window.ProcessGuideCanvas.remount(flow, { preserveView: true });
            });
        }

        if (deleteNodeBtn) {
            deleteNodeBtn.addEventListener('click', function () {
                if (!selectedIds.length || !flow._pgBoard) return;
                if (!window.confirm('Delete selected shape(s)?')) return;
                var ids = selectedIds.slice();
                flow._pgBoard.shapes = flow._pgBoard.shapes.filter(function (s) {
                    return ids.indexOf(s.id) < 0;
                });
                flow._pgBoard.connectors = (flow._pgBoard.connectors || []).filter(function (c) {
                    return ids.indexOf(c.from) < 0 && ids.indexOf(c.to) < 0;
                });
                (flow._pgGroups || []).forEach(function (g) {
                    if (g.nodeIds) g.nodeIds = g.nodeIds.filter(function (id) { return ids.indexOf(id) < 0; });
                });
                selectedIds = [];
                recomputeBounds(flow._pgBoard);
                setDirty(true);
                renderGroupsList();
                syncSelectionUI();
                window.ProcessGuideCanvas.remount(flow, { preserveView: true });
            });
        }

        panel.querySelectorAll('[data-pg-add-shape]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var shapeType = btn.getAttribute('data-pg-add-shape');
                var center = viewCenterRaw(flow);
                var defaults = {
                    rectangle: { w: 2200, h: 900, text: 'New step' },
                    rhombus: { w: 1800, h: 1400, text: 'Decision?' },
                    wedge_round_rectangle_callout: { w: 2000, h: 800, text: 'Message' }
                };
                var d = defaults[shapeType] || defaults.rectangle;
                flow._pgBoard.shapes.push({
                    id: uid('shape'),
                    x: center.x,
                    y: center.y,
                    w: d.w,
                    h: d.h,
                    shape: shapeType,
                    text: d.text,
                    fill: '#ffffff',
                    stroke: '#1a1a1a',
                    strokeWidth: 2,
                    rotation: 0
                });
                recomputeBounds(flow._pgBoard);
                setDirty(true);
                window.ProcessGuideCanvas.remount(flow, { preserveView: true });
            });
        });

        function renderGroupsList() {
            if (!groupsList) return;
            var groups = flow._pgGroups || [];
            groupsList.innerHTML = '';
            groups.forEach(function (g) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'pg-editor-group-item' + (g.id === activeGroupId ? ' is-active' : '');
                btn.textContent = 'Step ' + g.step + ' · ' + g.title;
                btn.addEventListener('click', function () {
                    activeGroupId = g.id;
                    fillGroupForm(g);
                    renderGroupsList();
                });
                groupsList.appendChild(btn);
            });
            if (!groups.length && groupForm) groupForm.hidden = true;
        }

        function fillGroupForm(g) {
            if (!groupForm) return;
            groupForm.hidden = false;
            if (groupStep) groupStep.value = g.step || 1;
            if (groupTitle) groupTitle.value = g.title || '';
            if (groupSubtitle) groupSubtitle.value = g.subtitle || '';
            if (groupIntro) groupIntro.value = g.intro || '';
            if (groupNotes) groupNotes.value = (g.notes || []).join('\n');
            if (groupSections) groupSections.value = JSON.stringify(g.sections || [], null, 2);
        }

        function activeGroup() {
            return (flow._pgGroups || []).find(function (g) { return g.id === activeGroupId; }) || null;
        }

        function persistGroupForm() {
            var g = activeGroup();
            if (!g) return;
            g.step = parseInt(groupStep.value, 10) || 1;
            g.title = groupTitle.value.trim() || 'Untitled group';
            g.subtitle = groupSubtitle.value.trim();
            g.intro = groupIntro.value.trim();
            g.notes = groupNotes.value.split('\n').map(function (l) { return l.trim(); }).filter(Boolean);
            try {
                g.sections = JSON.parse(groupSections.value || '[]');
            } catch (e) { /* keep previous */ }
            setDirty(true);
            renderGroupsList();
        }

        [groupStep, groupTitle, groupSubtitle, groupIntro, groupNotes, groupSections].forEach(function (el) {
            if (!el) return;
            el.addEventListener('change', persistGroupForm);
            el.addEventListener('blur', persistGroupForm);
        });

        if (addGroupBtn) {
            addGroupBtn.addEventListener('click', function () {
                var groups = flow._pgGroups || (flow._pgGroups = []);
                var g = {
                    id: uid('group'),
                    step: groups.length + 1,
                    title: 'New group',
                    subtitle: '',
                    intro: '',
                    sections: [],
                    notes: [],
                    nodeIds: []
                };
                groups.push(g);
                activeGroupId = g.id;
                fillGroupForm(g);
                renderGroupsList();
                setDirty(true);
            });
        }

        if (assignGroupBtn) {
            assignGroupBtn.addEventListener('click', function () {
                var g = activeGroup();
                if (!g) { window.alert('Select a group first.'); return; }
                if (!selectedIds.length) { window.alert('Select one or more shapes first (Shift+click for multiple).'); return; }
                g.nodeIds = g.nodeIds || [];
                selectedIds.forEach(function (id) {
                    if (g.nodeIds.indexOf(id) < 0) g.nodeIds.push(id);
                });
                setDirty(true);
                window.ProcessGuideCanvas.remount(flow, { preserveView: true });
            });
        }

        if (deleteGroupBtn) {
            deleteGroupBtn.addEventListener('click', function () {
                if (!activeGroupId) return;
                if (!window.confirm('Delete this group?')) return;
                flow._pgGroups = (flow._pgGroups || []).filter(function (g) { return g.id !== activeGroupId; });
                activeGroupId = null;
                if (groupForm) groupForm.hidden = true;
                renderGroupsList();
                setDirty(true);
                window.ProcessGuideCanvas.remount(flow, { preserveView: true });
            });
        }

        if (editToggle) {
            editToggle.addEventListener('click', function () {
                setEditMode(flow.dataset.pgEditMode !== '1');
            });
        }
        if (closeBtn) closeBtn.addEventListener('click', function () { setEditMode(false); });

        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                persistGroupForm();
                saveBtn.disabled = true;
                if (saveStatus) saveStatus.textContent = 'Saving…';
                var boardUrl = flow.getAttribute('data-pg-board-save-url');
                var groupsUrl = flow.getAttribute('data-pg-groups-save-url');
                var headers = {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken()
                };
                Promise.all([
                    fetch(boardUrl, { method: 'POST', credentials: 'same-origin', headers: headers, body: JSON.stringify(flow._pgBoard) }),
                    fetch(groupsUrl, { method: 'POST', credentials: 'same-origin', headers: headers, body: JSON.stringify({ groups: flow._pgGroups || [] }) })
                ])
                    .then(function (responses) {
                        return Promise.all(responses.map(function (r) {
                            if (!r.ok) throw new Error('Save failed');
                            return r.json();
                        }));
                    })
                    .then(function () {
                        dirty = false;
                        if (saveStatus) saveStatus.textContent = 'Saved';
                    })
                    .catch(function () {
                        if (saveStatus) saveStatus.textContent = 'Save failed — try again';
                    })
                    .finally(function () {
                        saveBtn.disabled = false;
                    });
            });
        }

        flow.addEventListener('pg-board-mounted', function () {
            bindNodeDrag(flow, function () { setDirty(true); });
            renderGroupsList();
        });

        window.addEventListener('beforeunload', function (e) {
            if (dirty && flow.dataset.pgEditMode === '1') {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    function bindNodeDrag(flow, onChange) {
        var vp = flow.querySelector('.pg-flow-viewport');
        if (!vp || vp.dataset.pgDragBound === '1') return;
        vp.dataset.pgDragBound = '1';

        var drag = null;

        vp.addEventListener('pointerdown', function (e) {
            if (flow.dataset.pgEditMode !== '1') return;
            var node = e.target.closest('.pg-miro-node');
            if (!node || e.button !== 0) return;
            e.stopPropagation();
            e.preventDefault();
            var id = node.getAttribute('data-id');
            var raw = findRawShape(flow, id);
            if (!raw) return;
            flow._pgNodeDragging = true;
            drag = { id: id, sx: e.clientX, sy: e.clientY, ox: raw.x, oy: raw.y };
            vp.setPointerCapture(e.pointerId);
        }, true);

        vp.addEventListener('pointermove', function (e) {
            if (!drag) return;
            var raw = findRawShape(flow, drag.id);
            if (!raw) return;
            var d = screenDeltaToRaw(flow, e.clientX - drag.sx, e.clientY - drag.sy);
            raw.x = drag.ox + d.x;
            raw.y = drag.oy + d.y;
            var el = flow.querySelector('.pg-miro-node[data-id="' + drag.id + '"]');
            if (el) {
                el.setAttribute('transform', 'rotate(' + (raw.rotation || 0) + ' ' + ((raw.x - flow._pgBoard.bounds.minX) * MIRO_UNIT) + ' ' + ((raw.y - flow._pgBoard.bounds.minY) * MIRO_UNIT) + ')');
                el.querySelectorAll('rect, polygon').forEach(function (shape) {
                    var sx = (raw.x - flow._pgBoard.bounds.minX) * MIRO_UNIT;
                    var sy = (raw.y - flow._pgBoard.bounds.minY) * MIRO_UNIT;
                    if (shape.tagName === 'polygon') {
                        var hw = raw.w * MIRO_UNIT / 2, hh = raw.h * MIRO_UNIT / 2;
                        shape.setAttribute('points', sx + ',' + (sy - hh) + ' ' + (sx + hw) + ',' + sy + ' ' + sx + ',' + (sy + hh) + ' ' + (sx - hw) + ',' + sy);
                    } else {
                        shape.setAttribute('x', sx - raw.w * MIRO_UNIT / 2);
                        shape.setAttribute('y', sy - raw.h * MIRO_UNIT / 2);
                    }
                });
                var fo = el.querySelector('foreignObject');
                if (fo) {
                    var sx = (raw.x - flow._pgBoard.bounds.minX) * MIRO_UNIT;
                    var sy = (raw.y - flow._pgBoard.bounds.minY) * MIRO_UNIT;
                    fo.setAttribute('x', sx - raw.w * MIRO_UNIT * 0.45);
                    fo.setAttribute('y', sy - raw.h * MIRO_UNIT * 0.45);
                }
            }
        });

        function endDrag() {
            if (!drag) return;
            recomputeBounds(flow._pgBoard);
            drag = null;
            flow._pgNodeDragging = false;
            if (onChange) onChange();
            window.ProcessGuideCanvas.remount(flow, { preserveView: true });
        }

        vp.addEventListener('pointerup', endDrag);
        vp.addEventListener('pointercancel', endDrag);
    }

    function boot() {
        document.querySelectorAll('.pg-flow.is-miro-canvas').forEach(function (flow) {
            if (flow.dataset.pgEditorReady === '1') return;
            flow.addEventListener('pg-board-mounted', function () {
                initEditor(flow);
            }, { once: false });
            flow.dataset.pgEditorReady = '1';
            if (flow.classList.contains('is-ready')) initEditor(flow);
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
</script>
