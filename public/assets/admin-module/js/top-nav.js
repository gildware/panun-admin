(function () {
    'use strict';

    var bound = false;

    function getChrome() {
        return document.querySelector('.top-chrome');
    }

    function disposeChromeBootstrapDropdowns(chrome) {
        chrome = chrome || getChrome();
        if (!chrome || !window.bootstrap || typeof window.bootstrap.Dropdown !== 'function') {
            return;
        }

        chrome.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function (toggle) {
            var instance = window.bootstrap.Dropdown.getInstance(toggle);
            if (!instance) {
                return;
            }

            try {
                instance.hide();
            } catch (error) {}

            instance.dispose();
            toggle.setAttribute('aria-expanded', 'false');
        });

        chrome.querySelectorAll('.dropdown.show, .dropdown-menu.show').forEach(function (el) {
            el.classList.remove('show');
        });
    }

    function cleanupStaleAdminOverlays() {
        if (!document.querySelector('.modal.show')) {
            document.querySelectorAll('.modal-backdrop, .offcanvas-backdrop').forEach(function (el) {
                el.remove();
            });

            if (!document.querySelector('.swal2-container.swal2-shown')) {
                document.body.classList.remove('modal-open', 'offcanvas-backdrop');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }
        }
    }

    window.pkAdminDisposeChromeDropdowns = disposeChromeBootstrapDropdowns;
    window.pkAdminCleanupStaleOverlays = cleanupStaleAdminOverlays;

    function resetDropdownPosition(dropdown) {
        if (!dropdown) {
            return;
        }
        dropdown.classList.remove('is-positioned');
        dropdown.style.position = '';
        dropdown.style.top = '';
        dropdown.style.left = '';
        dropdown.style.right = '';
        dropdown.style.minWidth = '';
        dropdown.style.zIndex = '';
    }

    function positionDropdown(item) {
        var dropdown = item.querySelector('.top-nav-dropdown');
        var trigger = item.querySelector('.top-nav-trigger, .top-utility-action-btn, .top-utility-icon-btn');
        if (!dropdown || !trigger) {
            return;
        }

        var rect = trigger.getBoundingClientRect();
        dropdown.classList.add('is-positioned');
        dropdown.style.position = 'fixed';
        dropdown.style.top = (rect.bottom + 6) + 'px';
        dropdown.style.minWidth = '240px';
        dropdown.style.zIndex = '1080';

        if (dropdown.classList.contains('top-nav-dropdown--align-end')) {
            dropdown.style.left = 'auto';
            dropdown.style.right = Math.max(8, window.innerWidth - rect.right) + 'px';
        } else {
            dropdown.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - 260)) + 'px';
            dropdown.style.right = 'auto';
        }
    }

    function closeDropdowns() {
        var chrome = getChrome();
        if (!chrome) {
            return;
        }
        chrome.querySelectorAll('.top-nav-item.is-open, .top-utility-item.is-open').forEach(function (el) {
            el.classList.remove('is-open');
            resetDropdownPosition(el.querySelector('.top-nav-dropdown'));
        });
        syncChromeExpandedState();
    }

    function onDocumentScroll(event) {
        var target = event.target;
        if (target && target.nodeType === 1 && target.closest('.top-nav-dropdown')) {
            return;
        }
        closeDropdowns();
    }

    function bindNavImageFallbacks(root) {
        (root || document).querySelectorAll('img.js-nav-img-fallback[data-fallback]').forEach(function (img) {
            var fallback = img.getAttribute('data-fallback');
            if (!fallback) {
                return;
            }

            img.onerror = function () {
                if (img.src !== fallback) {
                    img.src = fallback;
                }
            };

            if (img.complete && img.naturalWidth === 0 && img.src !== fallback) {
                img.src = fallback;
            }
        });
    }

    var CHROME_MODE_KEY = 'admin_top_chrome_mode';

    function isChromeAutoHideEnabled() {
        return document.body.classList.contains('top-chrome-auto-hide');
    }

    function applyChromeMode(mode) {
        var isAutoHide = mode !== 'fixed';
        document.body.classList.toggle('top-chrome-auto-hide', isAutoHide);

        try {
            localStorage.setItem(CHROME_MODE_KEY, isAutoHide ? 'auto-hide' : 'fixed');
        } catch (error) {}

        var toggle = document.getElementById('top-chrome-mode-toggle');
        if (toggle) {
            toggle.setAttribute('aria-pressed', isAutoHide ? 'false' : 'true');
            var nextAction = isAutoHide
                ? (toggle.getAttribute('data-label-pin') || 'Pin header')
                : (toggle.getAttribute('data-label-unpin') || 'Unpin header');
            toggle.title = nextAction;
            toggle.setAttribute('aria-label', nextAction);

            var pinOption = toggle.querySelector('.top-chrome-mode-option--pin');
            var unpinOption = toggle.querySelector('.top-chrome-mode-option--unpin');
            if (pinOption && unpinOption) {
                pinOption.hidden = !isAutoHide;
                unpinOption.hidden = isAutoHide;
                pinOption.style.setProperty('display', isAutoHide ? 'inline-flex' : 'none', 'important');
                unpinOption.style.setProperty('display', isAutoHide ? 'none' : 'inline-flex', 'important');
            } else {
                var label = toggle.querySelector('.top-chrome-mode-label');
                if (label) {
                    label.textContent = isAutoHide
                        ? (toggle.getAttribute('data-text-pin') || 'Pin')
                        : (toggle.getAttribute('data-text-unpin') || 'Unpin');
                }

                var icon = toggle.querySelector('.top-chrome-mode-icon')
                    || toggle.querySelector('.material-icons');
                if (icon) {
                    icon.style.transform = isAutoHide ? '' : 'rotate(45deg)';
                    icon.style.opacity = isAutoHide ? '' : '0.85';
                }
            }
        }

        if (!isAutoHide) {
            document.body.classList.remove('top-chrome-expanded');
        } else {
            syncChromeExpandedState();
        }
    }

    function bindChromeModeToggle() {
        var toggle = document.getElementById('top-chrome-mode-toggle');
        if (!toggle || toggle.dataset.modeBound === '1') {
            return;
        }

        toggle.dataset.modeBound = '1';
        applyChromeMode(isChromeAutoHideEnabled() ? 'auto-hide' : 'fixed');

        toggle.addEventListener('click', function () {
            applyChromeMode(isChromeAutoHideEnabled() ? 'fixed' : 'auto-hide');
        });
    }

    function syncChromeExpandedState() {
        if (!document.body.classList.contains('nav-top') || !isChromeAutoHideEnabled()) {
            document.body.classList.remove('top-chrome-expanded');
            return;
        }

        var chrome = getChrome();
        if (!chrome) {
            return;
        }

        var keepOpen = document.body.classList.contains('admin-pins-editing')
            || chrome.querySelector('.dropdown.show, .dropdown-menu.show')
            || chrome.querySelector('.top-nav-item.is-open, .top-utility-item.is-open');

        document.body.classList.toggle('top-chrome-expanded', !!keepOpen);
    }

    function bindChromeAutoHide() {
        if (!document.body.classList.contains('nav-top')) {
            return;
        }

        var chrome = getChrome();
        if (!chrome || chrome.dataset.autoHideBound === '1') {
            return;
        }

        chrome.dataset.autoHideBound = '1';

        chrome.addEventListener('show.bs.dropdown', syncChromeExpandedState);
        chrome.addEventListener('hidden.bs.dropdown', syncChromeExpandedState);

        if (typeof MutationObserver === 'function') {
            var observer = new MutationObserver(syncChromeExpandedState);
            observer.observe(chrome, {
                subtree: true,
                attributes: true,
                attributeFilter: ['class']
            });
        }
    }

    function bindTopNav() {
        if (bound || !document.body.classList.contains('nav-top')) {
            return;
        }
        bound = true;
        bindChromeAutoHide();
        bindChromeModeToggle();

        document.addEventListener('click', function (e) {
            if (!document.body.classList.contains('nav-top')) {
                return;
            }

            var chrome = getChrome();
            if (!chrome) {
                return;
            }

            var trigger = e.target.closest('.top-nav-trigger, .top-utility-icon-btn, .top-utility-action-btn');
            if (trigger && chrome.contains(trigger)) {
                if (trigger.classList.contains('js-open-search') || trigger.hasAttribute('data-bs-toggle')) {
                    return;
                }

                var item = trigger.closest('.top-nav-item, .top-utility-item');
                if (item && item.querySelector('.top-nav-dropdown')) {
                    e.preventDefault();
                    e.stopPropagation();

                    var willOpen = !item.classList.contains('is-open');
                    closeDropdowns();
                    if (willOpen) {
                        item.classList.add('is-open');
                        positionDropdown(item);
                    }
                    syncChromeExpandedState();
                    return;
                }
            }

            if (!e.target.closest('.top-chrome')) {
                closeDropdowns();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDropdowns();
            }
        });

        document.addEventListener('hidden.bs.modal', cleanupStaleAdminOverlays);

        window.addEventListener('resize', closeDropdowns);
        window.addEventListener('scroll', onDocumentScroll, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            bindTopNav();
            bindNavImageFallbacks(document.querySelector('.top-chrome'));
        });
    } else {
        bindTopNav();
        bindNavImageFallbacks(document.querySelector('.top-chrome'));
    }

    document.addEventListener('admin:chrome-updated', function () {
        closeDropdowns();
        disposeChromeBootstrapDropdowns();
        cleanupStaleAdminOverlays();
        bindNavImageFallbacks(document.querySelector('.top-chrome'));
        bindChromeAutoHide();
        bindChromeModeToggle();
        syncChromeExpandedState();
    });
})();
