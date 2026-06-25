(function () {
    'use strict';

    if (!document.body.classList.contains('nav-top')) {
        return;
    }

    if (window.Turbo && window.Turbo.session) {
        window.Turbo.session.drive = false;
    }

    var FRAME_ID = 'admin-main';
    var progressEl = null;

    function getProgressEl() {
        if (!progressEl) {
            progressEl = document.getElementById('admin-partial-progress');
        }
        return progressEl;
    }

    function showProgress() {
        var el = getProgressEl();
        if (el) {
            el.classList.add('is-active');
            el.setAttribute('aria-hidden', 'false');
        }
    }

    function hideProgress() {
        var el = getProgressEl();
        if (el) {
            el.classList.remove('is-active');
            el.setAttribute('aria-hidden', 'true');
        }
    }

    function initPageWidgets(root) {
        root = root || document.getElementById(FRAME_ID);
        if (!root) {
            return;
        }

        if (window.jQuery) {
            var $root = window.jQuery(root);
            $root.find('.js-select').each(function () {
                var $el = window.jQuery(this);
                if ($el.data('select2')) {
                    $el.select2('destroy');
                }
                $el.select2();
            });

            if (typeof window.jQuery.fn.tooltip === 'function') {
                $root.find('[data-bs-toggle="tooltip"]').each(function () {
                    var instance = bootstrap.Tooltip.getInstance(this);
                    if (instance) {
                        instance.dispose();
                    }
                    new bootstrap.Tooltip(this);
                });
            }
        }

        document.dispatchEvent(new CustomEvent('admin:page-loaded', { detail: { root: root } }));
    }

    async function syncChromeFromResponse(fetchResponse) {
        if (!fetchResponse || !fetchResponse.response) {
            return;
        }

        var html = await fetchResponse.response.clone().text();
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var title = doc.querySelector('title');
        if (title && title.textContent) {
            document.title = title.textContent.trim();
        }

        var newChrome = doc.querySelector('.top-chrome');
        var chrome = document.querySelector('.top-chrome');
        if (!newChrome || !chrome) {
            return;
        }

        chrome.innerHTML = newChrome.innerHTML;
        chrome.className = newChrome.className;

        document.dispatchEvent(new CustomEvent('admin:chrome-updated'));
    }

    function initChromeTooltips() {
        var chrome = document.querySelector('.top-chrome');
        if (!chrome || !window.bootstrap || typeof window.bootstrap.Tooltip !== 'function') {
            return;
        }

        chrome.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            var instance = bootstrap.Tooltip.getInstance(el);
            if (instance) {
                instance.dispose();
            }
            new bootstrap.Tooltip(el);
        });
    }

    function markFullPageLinks() {
        document.querySelectorAll('a.admin-logout, a[data-turbo="false"]').forEach(function (link) {
            link.setAttribute('data-turbo', 'false');
        });

        document.querySelectorAll('form[enctype="multipart/form-data"]').forEach(function (form) {
            form.setAttribute('data-turbo', 'false');
        });
    }

    document.addEventListener('turbo:click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link) {
            return;
        }

        if (link.hasAttribute('data-turbo') && link.getAttribute('data-turbo') === 'false') {
            return;
        }

        if (link.target === '_blank' || link.hasAttribute('download')) {
            event.preventDefault();
            window.open(link.href, '_blank');
        }
    });

    document.addEventListener('turbo:before-fetch-request', function (event) {
        if (event.target && event.target.id === FRAME_ID) {
            showProgress();
            if (window.jQuery) {
                window.jQuery('.preloader').hide();
            }
        }
    });

    document.addEventListener('turbo:frame-render', function (event) {
        if (event.target.id !== FRAME_ID) {
            return;
        }

        hideProgress();
        window.scrollTo({ top: 0, behavior: 'smooth' });

        var frame = document.getElementById(FRAME_ID);
        if (frame) {
            initPageWidgets(frame);
        }

        markFullPageLinks();
    });

    document.addEventListener('turbo:before-frame-render', function (event) {
        if (event.target.id !== FRAME_ID) {
            return;
        }

        var fetchResponse = event.detail.fetchResponse;
        syncChromeFromResponse(fetchResponse);
    });

    document.addEventListener('turbo:fetch-request-error', function (event) {
        if (event.target && event.target.id === FRAME_ID) {
            hideProgress();
        }
    });

    document.addEventListener('turbo:load', function () {
        markFullPageLinks();
        initPageWidgets(document.getElementById(FRAME_ID));
    });

    document.addEventListener('admin:chrome-updated', function () {
        markFullPageLinks();
        initChromeTooltips();
    });

    markFullPageLinks();
})();
