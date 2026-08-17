(function () {
    'use strict';

    function getDropdown() {
        return document.getElementById('progress-metric-info-dropdown');
    }

    function ensureDropdown() {
        var dropdown = getDropdown();
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.id = 'progress-metric-info-dropdown';
            dropdown.className = 'progress-metric-info-dropdown';
            dropdown.setAttribute('hidden', '');
            dropdown.setAttribute('aria-hidden', 'true');
            dropdown.setAttribute('role', 'tooltip');
            dropdown.innerHTML = '<div class="progress-metric-info-dropdown-arrow" aria-hidden="true"></div>'
                + '<strong id="progress-metric-info-title"></strong>'
                + '<p id="progress-metric-info-summary"></p>'
                + '<div class="progress-metric-info-example" id="progress-metric-info-example-wrap" hidden>'
                + '<span class="progress-metric-info-example-label">Example</span>'
                + '<p id="progress-metric-info-example"></p>'
                + '</div>';
        }
        if (dropdown.parentElement !== document.body) {
            document.body.appendChild(dropdown);
        }
        return dropdown;
    }

    function summaryText(entry) {
        if (!entry) {
            return '';
        }
        if (entry.summary) {
            return entry.summary;
        }
        return [entry.what, entry.how].filter(Boolean).join(' ');
    }

    function entryFromButton(btn) {
        var key = btn.getAttribute('data-help-key');
        var registry = window.PanunProgressHelp || {};
        if (key && registry[key]) {
            return registry[key];
        }

        return {
            title: btn.getAttribute('data-help-title') || '',
            summary: btn.getAttribute('data-help-summary') || '',
            example: btn.getAttribute('data-help-example') || '',
        };
    }

    function setExpanded(btn, expanded) {
        document.querySelectorAll('.progress-metric-info-btn').forEach(function (node) {
            node.setAttribute('aria-expanded', node === btn && expanded ? 'true' : 'false');
        });
    }

    function closeDropdown() {
        var dropdown = getDropdown();
        if (!dropdown) {
            return;
        }
        dropdown.hidden = true;
        dropdown.setAttribute('aria-hidden', 'true');
        setExpanded(null, false);
        dropdown._activeBtn = null;
    }

    function positionDropdown(btn, dropdown) {
        dropdown.classList.remove('is-above');
        dropdown.hidden = false;
        dropdown.style.visibility = 'hidden';
        dropdown.style.pointerEvents = 'none';

        var rect = btn.getBoundingClientRect();
        var gap = 8;
        var padding = 12;
        var dropdownRect = dropdown.getBoundingClientRect();
        var width = dropdownRect.width || 340;
        var height = dropdownRect.height || 120;

        var left = rect.left + (rect.width / 2) - (width / 2);
        left = Math.max(padding, Math.min(left, window.innerWidth - width - padding));

        var top = rect.bottom + gap;
        var arrowLeft = rect.left + (rect.width / 2) - left;

        if (top + height > window.innerHeight - padding) {
            top = rect.top - height - gap;
            dropdown.classList.add('is-above');
        }

        dropdown.style.left = left + 'px';
        dropdown.style.top = Math.max(padding, top) + 'px';
        dropdown.style.setProperty('--arrow-left', Math.max(12, Math.min(arrowLeft, width - 12)) + 'px');
        dropdown.style.visibility = '';
        dropdown.style.pointerEvents = '';
    }

    function openDropdown(btn) {
        var dropdown = ensureDropdown();
        var titleEl = document.getElementById('progress-metric-info-title');
        var summaryEl = document.getElementById('progress-metric-info-summary');
        var exampleWrap = document.getElementById('progress-metric-info-example-wrap');
        var exampleEl = document.getElementById('progress-metric-info-example');
        var entry = entryFromButton(btn);
        if (!dropdown || !titleEl || !summaryEl) {
            return;
        }
        if (!(entry.title || entry.summary || entry.example)) {
            return;
        }

        if (dropdown._activeBtn === btn && !dropdown.hidden) {
            closeDropdown();
            return;
        }

        dropdown._ignoreScrollUntil = Date.now() + 500;
        dropdown._activeBtn = btn;
        titleEl.textContent = entry.title || '';
        summaryEl.textContent = summaryText(entry);
        if (exampleWrap && exampleEl) {
            if (entry.example) {
                exampleEl.textContent = entry.example;
                exampleWrap.hidden = false;
            } else {
                exampleEl.textContent = '';
                exampleWrap.hidden = true;
            }
        }
        dropdown.hidden = false;
        dropdown.setAttribute('aria-hidden', 'false');
        setExpanded(btn, true);
        positionDropdown(btn, dropdown);
    }

    function onDocumentClick(event) {
        var btn = event.target && event.target.closest
            ? event.target.closest('.progress-metric-info-btn')
            : null;
        if (btn) {
            event.preventDefault();
            event.stopPropagation();
            openDropdown(btn);
            return;
        }

        if (!event.target.closest || !event.target.closest('#progress-metric-info-dropdown')) {
            closeDropdown();
        }
    }

    function bindOnce() {
        if (window.__panunProgressInfoBound) {
            return;
        }
        window.__panunProgressInfoBound = true;

        document.addEventListener('click', onDocumentClick, true);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeDropdown();
            }
        });

        window.addEventListener('resize', closeDropdown);
        window.addEventListener('scroll', function () {
            var dropdown = getDropdown();
            if (dropdown && Date.now() < (dropdown._ignoreScrollUntil || 0)) {
                return;
            }
            closeDropdown();
        }, true);
    }

    function boot() {
        bindOnce();
        ensureDropdown();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
    document.addEventListener('admin:page-loaded', boot);
})();
