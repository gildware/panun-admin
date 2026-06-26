(function () {
    'use strict';

    var BOUND_FLAG = 'adminImgFallbackBound';
    var placeholders = null;
    var observer = null;

    function getPlaceholders() {
        if (placeholders) {
            return placeholders;
        }

        var body = document.body;
        placeholders = {
            default: body.getAttribute('data-admin-img-placeholder') || '',
            profile: body.getAttribute('data-admin-profile-placeholder') || '',
            logo: body.getAttribute('data-admin-logo-placeholder') || '',
        };

        if (!placeholders.default) {
            placeholders.default = placeholders.logo || placeholders.profile || '';
        }

        return placeholders;
    }

    function isPlaceholderSrc(src) {
        if (!src) {
            return true;
        }

        var list = getPlaceholders();
        return [list.default, list.profile, list.logo].some(function (placeholder) {
            return placeholder && (src === placeholder || src.indexOf(placeholder) !== -1);
        });
    }

    function resolveFallback(img) {
        var custom = img.getAttribute('data-fallback');
        if (custom) {
            return custom;
        }

        var list = getPlaceholders();

        if (
            img.classList.contains('avatar') ||
            img.classList.contains('avatar-img') ||
            (img.closest && img.closest('.avatar'))
        ) {
            return list.profile || list.default;
        }

        if (img.classList.contains('top-utility-brand-logo') || img.classList.contains('brand-logo')) {
            return list.logo || list.default;
        }

        return list.default;
    }

    function shouldSkip(img) {
        if (!img || img.nodeName !== 'IMG') {
            return true;
        }

        if (img.getAttribute('data-no-img-fallback') === '1' || img.getAttribute('data-no-img-fallback') === 'true') {
            return true;
        }

        if (img.hasAttribute('onerror')) {
            return true;
        }

        var src = img.currentSrc || img.src || '';
        if (src.indexOf('blob:') === 0 || src.indexOf('data:') === 0) {
            return true;
        }

        return false;
    }

    function applyFallback(img) {
        var fallback = resolveFallback(img);
        if (!fallback || isPlaceholderSrc(img.src)) {
            return;
        }

        img.onerror = null;
        img.src = fallback;
    }

    function bindImage(img) {
        if (shouldSkip(img) || img.getAttribute(BOUND_FLAG) === '1') {
            return;
        }

        var fallback = resolveFallback(img);
        if (!fallback) {
            return;
        }

        img.setAttribute(BOUND_FLAG, '1');

        img.addEventListener('error', function () {
            applyFallback(img);
        });

        if (img.complete && img.naturalWidth === 0 && img.src && !isPlaceholderSrc(img.src)) {
            applyFallback(img);
        }
    }

    function bindAll(root) {
        root = root || document;
        if (!root.querySelectorAll) {
            return;
        }

        root.querySelectorAll('img').forEach(bindImage);
    }

    function ensureObserver() {
        if (observer || !document.body) {
            return;
        }

        observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeName === 'IMG') {
                        bindImage(node);
                        return;
                    }

                    if (node.querySelectorAll) {
                        bindAll(node);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    function init() {
        bindAll(document);
        ensureObserver();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.addEventListener('admin:page-loaded', function (event) {
        bindAll(event.detail && event.detail.root ? event.detail.root : document);
    });

    document.addEventListener('admin:chrome-updated', function () {
        var chrome = document.querySelector('.top-chrome');
        if (chrome) {
            bindAll(chrome);
        }
    });

    window.pkBindAdminImageFallbacks = bindAll;
})();
