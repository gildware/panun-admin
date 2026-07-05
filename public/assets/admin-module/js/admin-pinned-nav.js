(function () {
    'use strict';

    var catalog = [];
    var savedKeys = [];
    var draftKeys = [];
    var isEditing = false;
    var isSaving = false;

    function readJson(id) {
        var el = document.getElementById(id);
        if (!el) {
            return [];
        }

        try {
            return JSON.parse(el.textContent || '[]');
        } catch (error) {
            return [];
        }
    }

    function getMount() {
        return document.getElementById('admin-pinned-mount');
    }

    function getActiveKeys() {
        return isEditing ? draftKeys.slice() : savedKeys.slice();
    }

    function pathMatches(pattern) {
        if (!pattern) {
            return false;
        }

        var path = window.location.pathname.replace(/^\//, '');
        pattern = String(pattern).replace(/^\//, '');

        if (pattern.indexOf('*') === -1) {
            return path === pattern;
        }

        var regex = new RegExp('^' + pattern.replace(/\*/g, '.*') + '$');
        return regex.test(path);
    }

    function isLinkActive(item) {
        if (!item.paths || !item.paths.length) {
            return false;
        }

        return item.paths.some(pathMatches);
    }

    function findCatalogItem(pinKey) {
        return catalog.find(function (item) {
            return item.pin_key === pinKey;
        });
    }

    function createPinButton(pinKey, isPinned, options) {
        options = options || {};
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'top-nav-pin-btn' + (isPinned ? ' is-pinned' : '');
        if (options.inPill) {
            button.className += ' top-nav-pin-btn--in-pill';
        }
        button.setAttribute('data-pin-key', pinKey);
        button.setAttribute('aria-pressed', isPinned ? 'true' : 'false');
        button.title = isPinned ? 'Unpin from shortcuts' : 'Pin to shortcuts';
        button.setAttribute('aria-label', isPinned ? 'Unpin' : 'Pin');

        var icon = document.createElement('span');
        icon.className = 'material-icons';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = options.inPill && isPinned ? 'close' : 'push_pin';
        button.appendChild(icon);

        return button;
    }

    function usesPartialNav() {
        return document.body.classList.contains('nav-top')
            && document.body.getAttribute('data-partial-nav') === '1';
    }

    function usesLightPinnedStyle(mount) {
        return !!(mount && mount.closest('.top-group-subnav'));
    }

    function renderPinnedBar() {
        var mount = getMount();
        if (!mount) {
            return;
        }

        mount.innerHTML = '';
        var keys = getActiveKeys();
        var lightStyle = usesLightPinnedStyle(mount);
        var linkClass = lightStyle ? 'top-group-subnav-link' : 'top-sub-nav-link';
        var emptyClass = lightStyle ? 'top-group-subnav-empty' : 'top-sub-nav-empty';

        keys.forEach(function (pinKey) {
            var item = findCatalogItem(pinKey);
            if (!item) {
                return;
            }

            var row = document.createElement('div');
            row.className = 'top-pinned-link-row';

            if (isEditing) {
                var pill = document.createElement('div');
                pill.className = linkClass + ' top-sub-nav-link--editable' + (isLinkActive(item) ? ' active-menu' : '');

                var link = document.createElement('a');
                link.href = item.url;
                link.className = 'top-pinned-link-text';
                link.textContent = item.label;

                if (usesPartialNav()) {
                    link.setAttribute('data-turbo-frame', 'admin-main');
                    link.setAttribute('data-turbo-action', 'advance');
                }

                if (item.count) {
                    var badge = document.createElement('span');
                    badge.className = 'badge-count';
                    badge.textContent = String(item.count);
                    link.appendChild(badge);
                }

                pill.appendChild(link);
                pill.appendChild(createPinButton(pinKey, true, { inPill: true }));
                row.appendChild(pill);
            } else {
                var link = document.createElement('a');
                link.href = item.url;
                link.className = linkClass + (isLinkActive(item) ? ' active-menu' : '');
                link.textContent = item.label;

                if (usesPartialNav()) {
                    link.setAttribute('data-turbo-frame', 'admin-main');
                    link.setAttribute('data-turbo-action', 'advance');
                }

                if (item.count) {
                    var countBadge = document.createElement('span');
                    countBadge.className = 'badge-count';
                    countBadge.textContent = String(item.count);
                    link.appendChild(countBadge);
                }

                row.appendChild(link);
            }

            mount.appendChild(row);
        });

        if (keys.length === 0) {
            var empty = document.createElement('span');
            empty.className = emptyClass;
            empty.textContent = mount.getAttribute('data-empty-hint') || 'Pin links from the menu using the pushpin icon';
            mount.appendChild(empty);
        }
    }

    function syncPinButtons() {
        var keys = getActiveKeys();

        document.querySelectorAll('.top-nav-pin-btn[data-pin-key]').forEach(function (button) {
            var pinKey = button.getAttribute('data-pin-key');
            var isPinned = keys.indexOf(pinKey) !== -1;
            var inPill = button.classList.contains('top-nav-pin-btn--in-pill');
            button.classList.toggle('is-pinned', isPinned);
            button.setAttribute('aria-pressed', isPinned ? 'true' : 'false');
            button.title = isPinned ? 'Unpin from shortcuts' : 'Pin to shortcuts';

            var icon = button.querySelector('.material-icons');
            if (icon) {
                icon.textContent = inPill && isPinned ? 'close' : 'push_pin';
            }
        });
    }

    function setEditing(enabled) {
        isEditing = !!enabled;
        document.body.classList.toggle('admin-pins-editing', isEditing);

        var editBtn = document.getElementById('admin-pins-edit-btn');
        var saveBtn = document.getElementById('admin-pins-save-btn');

        if (editBtn) {
            editBtn.hidden = isEditing;
        }

        if (saveBtn) {
            saveBtn.hidden = !isEditing;
            saveBtn.disabled = isSaving;
        }

        if (isEditing) {
            draftKeys = savedKeys.slice();
        }

        renderPinnedBar();
        syncPinButtons();
    }

    function togglePin(pinKey) {
        if (!isEditing) {
            return;
        }

        var keys = draftKeys.slice();
        var index = keys.indexOf(pinKey);

        if (index === -1) {
            keys.push(pinKey);
        } else {
            keys.splice(index, 1);
        }

        draftKeys = keys;
        renderPinnedBar();
        syncPinButtons();
    }

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function notifySuccess(message) {
        if (window.toastr) {
            window.toastr.success(message, 'Success', { CloseButton: true, ProgressBar: true });
        }
    }

    function notifyError(message) {
        if (window.toastr) {
            window.toastr.error(message, 'Error', { CloseButton: true, ProgressBar: true });
        }
    }

    function savePinnedKeys() {
        if (!isEditing || isSaving) {
            return;
        }

        var mount = getMount();
        var saveUrl = mount ? mount.getAttribute('data-save-url') : '';
        if (!saveUrl) {
            return;
        }

        isSaving = true;
        var saveBtn = document.getElementById('admin-pins-save-btn');
        if (saveBtn) {
            saveBtn.disabled = true;
        }

        fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ pins: draftKeys }),
            credentials: 'same-origin',
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        throw new Error((data && data.message) || 'Save failed');
                    }

                    return data;
                });
            })
            .then(function (data) {
                savedKeys = Array.isArray(data.data && data.data.pins) ? data.data.pins.slice() : draftKeys.slice();
                draftKeys = savedKeys.slice();
                isSaving = false;
                setEditing(false);
                notifySuccess(data.message || 'Saved successfully');
            })
            .catch(function (error) {
                isSaving = false;
                if (saveBtn) {
                    saveBtn.disabled = false;
                }
                notifyError(error.message || 'Could not save pinned shortcuts');
            });
    }

    function bindChromeControls() {
        var editBtn = document.getElementById('admin-pins-edit-btn');
        var saveBtn = document.getElementById('admin-pins-save-btn');

        if (editBtn && !editBtn.dataset.bound) {
            editBtn.dataset.bound = '1';
            editBtn.addEventListener('click', function () {
                setEditing(true);
            });
        }

        if (saveBtn && !saveBtn.dataset.bound) {
            saveBtn.dataset.bound = '1';
            saveBtn.addEventListener('click', function () {
                savePinnedKeys();
            });
        }
    }

    function reloadData() {
        catalog = readJson('admin-pinned-catalog');
        savedKeys = readJson('admin-pinned-user');
        if (!Array.isArray(savedKeys)) {
            savedKeys = [];
        }

        if (!isEditing) {
            draftKeys = savedKeys.slice();
        }

        renderPinnedBar();
        syncPinButtons();
        bindChromeControls();
        document.body.classList.toggle('admin-pins-editing', isEditing);
    }

    function init() {
        if (!document.body.classList.contains('nav-top')) {
            return;
        }

        reloadData();
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.top-nav-pin-btn[data-pin-key]');
        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        togglePin(button.getAttribute('data-pin-key'));
    });

    document.addEventListener('admin:chrome-updated', function () {
        reloadData();

        var editBtn = document.getElementById('admin-pins-edit-btn');
        var saveBtn = document.getElementById('admin-pins-save-btn');
        if (editBtn) {
            delete editBtn.dataset.bound;
        }
        if (saveBtn) {
            delete saveBtn.dataset.bound;
        }

        bindChromeControls();

        if (isEditing) {
            document.body.classList.add('admin-pins-editing');
            if (editBtn) {
                editBtn.hidden = true;
            }
            if (saveBtn) {
                saveBtn.hidden = false;
            }
        }
    });

    document.addEventListener('admin:page-loaded', function () {
        renderPinnedBar();
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
