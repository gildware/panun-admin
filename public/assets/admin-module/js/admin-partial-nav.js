(function () {
    'use strict';

    if (!document.body.classList.contains('nav-top') || document.body.getAttribute('data-partial-nav') !== '1') {
        return;
    }

    var FRAME_ID = 'admin-main';
    var progressEl = null;
    var activeController = null;
    var progressShownAt = 0;
    var progressHideTimer = null;
    var MIN_PROGRESS_MS = 320;

    function getProgressEl() {
        if (!progressEl) {
            progressEl = document.getElementById('admin-partial-progress');
        }
        return progressEl;
    }

    function showProgress() {
        if (progressHideTimer) {
            clearTimeout(progressHideTimer);
            progressHideTimer = null;
        }

        progressShownAt = Date.now();
        var el = getProgressEl();
        if (el) {
            el.classList.add('is-active');
            el.setAttribute('aria-hidden', 'false');
            void el.offsetWidth;
        }
        if (window.jQuery) {
            window.jQuery('.preloader').hide();
        }
    }

    function hideProgress() {
        var el = getProgressEl();
        if (el) {
            el.classList.remove('is-active');
            el.setAttribute('aria-hidden', 'true');
        }
    }

    function showFullPageLoader() {
        document.documentElement.classList.remove('admin-skip-preloader');
        if (window.jQuery) {
            window.jQuery('.preloader').show();
        }
    }

    function hideProgressSoon() {
        var wait = Math.max(0, MIN_PROGRESS_MS - (Date.now() - progressShownAt));
        if (progressHideTimer) {
            clearTimeout(progressHideTimer);
        }
        progressHideTimer = setTimeout(function () {
            progressHideTimer = null;
            hideProgress();
        }, wait);
    }

    function cleanupModalBackdrops() {
        if (typeof window.pkAdminDisposeChromeDropdowns === 'function') {
            window.pkAdminDisposeChromeDropdowns();
        }

        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            document.querySelectorAll('.modal.show').forEach(function (modalEl) {
                var instance = window.bootstrap.Modal.getInstance(modalEl);
                if (instance) {
                    instance.hide();
                    instance.dispose();
                }
            });
        }

        document.querySelectorAll('.modal-backdrop, .offcanvas-backdrop')
            .forEach(function (el) {
                el.remove();
            });

        document.body.classList.remove('modal-open', 'offcanvas-backdrop');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');

        if (typeof window.pkAdminCleanupStaleOverlays === 'function') {
            window.pkAdminCleanupStaleOverlays();
        }
    }

    function runFlashToastsFromHtml(html) {
        if (!html || typeof window.toastr === 'undefined') {
            return;
        }

        var doc = new DOMParser().parseFromString(html, 'text/html');
        doc.querySelectorAll('script').forEach(function (script) {
            var content = (script.textContent || '').trim();
            if (!content || content.indexOf('toastr.') === -1) {
                return;
            }

            var runner = document.createElement('script');
            runner.textContent = content;
            document.body.appendChild(runner);
            runner.remove();
        });
    }

    function initPageWidgets(root) {
        root = root || document.getElementById(FRAME_ID);
        if (!root) {
            return;
        }

        if (window.jQuery) {
            if (typeof window.initAdminPageSelect2 === 'function') {
                window.initAdminPageSelect2(root);
            } else if (typeof window.jQuery.fn.select2 === 'function') {
                var $root = window.jQuery(root);
                $root.find('.js-select').each(function () {
                    var $el = window.jQuery(this);
                    if ($el.hasClass('select2-hidden-accessible')) {
                        return;
                    }
                    $el.select2();
                });
            }

            if (typeof window.jQuery.fn.tooltip === 'function') {
                var $root = window.jQuery(root);
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

    function syncChromeFromHtml(html) {
        if (!html) {
            return;
        }

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

        if (typeof window.pkAdminDisposeChromeDropdowns === 'function') {
            window.pkAdminDisposeChromeDropdowns(chrome);
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

    function activateScripts(root) {
        if (!root) {
            return;
        }

        root.querySelectorAll('script').forEach(function (oldScript) {
            var script = document.createElement('script');
            Array.prototype.forEach.call(oldScript.attributes, function (attr) {
                script.setAttribute(attr.name, attr.value);
            });
            script.textContent = oldScript.textContent;
            oldScript.replaceWith(script);
        });
    }

    function extractFrameDocument(html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var frame = doc.querySelector('turbo-frame#' + FRAME_ID + ', #' + FRAME_ID + '.admin-main-frame');

        if (frame) {
            return { doc: doc, frame: frame };
        }

        return { doc: doc, frame: null };
    }

    function isPartialNavLink(link) {
        if (!link || !link.getAttribute('href') || link.getAttribute('href') === '#') {
            return false;
        }

        if (link.getAttribute('data-turbo') === 'false') {
            return false;
        }

        if (link.target === '_blank' || link.hasAttribute('download')) {
            return false;
        }

        return link.getAttribute('data-turbo-frame') === FRAME_ID;
    }

    async function loadPartialPage(url, options) {
        options = options || {};
        var frame = document.getElementById(FRAME_ID);
        if (!frame) {
            window.location.href = url;
            return;
        }

        if (activeController) {
            activeController.abort();
        }

        activeController = new AbortController();
        showProgress();
        cleanupModalBackdrops();

        try {
            var response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'text/html',
                    'Turbo-Frame': FRAME_ID,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeController.signal,
            });

            if (!response.ok) {
                throw new Error('Request failed');
            }

            var html = await response.text();
            var parsed = extractFrameDocument(html);

            if (!parsed.frame) {
                showFullPageLoader();
                window.location.href = url;
                return;
            }

            syncChromeFromHtml(html);
            runFlashToastsFromHtml(html);

            frame.innerHTML = parsed.frame.innerHTML;
            activateScripts(frame);

            if (options.advance !== false) {
                window.history.pushState({ adminPartialNav: true }, '', url);
            }

            hideProgressSoon();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            initPageWidgets(frame);
            markFullPageLinks();

            try {
                sessionStorage.setItem('admin_shell_ready', '1');
            } catch (e) {}
        } catch (error) {
            if (error && error.name === 'AbortError') {
                hideProgressSoon();
                return;
            }

            hideProgress();
            cleanupModalBackdrops();
            showFullPageLoader();
            window.location.href = url;
        } finally {
            activeController = null;
        }
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!isPartialNavLink(link)) {
            return;
        }

        var url;
        try {
            url = new URL(link.href, window.location.origin);
        } catch (e) {
            return;
        }

        if (url.origin !== window.location.origin) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        loadPartialPage(url.href, {
            advance: link.getAttribute('data-turbo-action') === 'advance',
        });
    }, true);

    window.addEventListener('popstate', function () {
        if (!window.location.pathname.startsWith('/admin')) {
            return;
        }

        loadPartialPage(window.location.href, { advance: false });
    });

    document.addEventListener('admin:chrome-updated', function () {
        markFullPageLinks();
        initChromeTooltips();
    });

    markFullPageLinks();
    initPageWidgets(document.getElementById(FRAME_ID));

    try {
        sessionStorage.setItem('admin_shell_ready', '1');
    } catch (e) {}
})();
