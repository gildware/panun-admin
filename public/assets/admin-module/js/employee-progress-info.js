(function () {
    'use strict';

    var bound = false;

    function getDropdown() {
        return document.getElementById('progress-metric-info-dropdown');
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
        var width = dropdownRect.width || 300;
        var height = dropdownRect.height || 100;

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
        dropdown.style.setProperty('--arrow-left', arrowLeft + 'px');
        dropdown.style.visibility = '';
        dropdown.style.pointerEvents = '';
    }

    function openDropdown(btn, key) {
        var registry = window.PanunProgressHelp || {};
        var dropdown = getDropdown();
        var titleEl = document.getElementById('progress-metric-info-title');
        var summaryEl = document.getElementById('progress-metric-info-summary');
        var entry = registry[key];
        if (!entry || !dropdown || !titleEl || !summaryEl) {
            return;
        }

        if (dropdown._activeBtn === btn && !dropdown.hidden) {
            closeDropdown();
            return;
        }

        dropdown._activeBtn = btn;
        titleEl.textContent = entry.title || '';
        summaryEl.textContent = summaryText(entry);
        dropdown.hidden = false;
        dropdown.setAttribute('aria-hidden', 'false');
        setExpanded(btn, true);
        positionDropdown(btn, dropdown);
    }

    function bindOnce() {
        if (bound) {
            return;
        }
        bound = true;

        document.addEventListener('click', function (event) {
            var btn = event.target.closest('.progress-metric-info-btn');
            if (btn) {
                event.preventDefault();
                event.stopPropagation();
                openDropdown(btn, btn.getAttribute('data-help-key'));
                return;
            }

            if (!event.target.closest('#progress-metric-info-dropdown')) {
                closeDropdown();
            }
        });

        document.addEventListener('keydown', function (event) {
            var dropdown = getDropdown();
            if (event.key === 'Escape' && dropdown && !dropdown.hidden) {
                closeDropdown();
            }
        });

        window.addEventListener('resize', closeDropdown);
        window.addEventListener('scroll', closeDropdown, true);
    }

    function boot() {
        bindOnce();
        closeDropdown();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
    document.addEventListener('admin:page-loaded', boot);
})();
