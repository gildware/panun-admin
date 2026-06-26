(function () {
    'use strict';

    var bound = false;

    function getChrome() {
        return document.querySelector('.top-chrome');
    }

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

    function bindTopNav() {
        if (bound || !document.body.classList.contains('nav-top')) {
            return;
        }
        bound = true;

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
        bindNavImageFallbacks(document.querySelector('.top-chrome'));
    });
})();
