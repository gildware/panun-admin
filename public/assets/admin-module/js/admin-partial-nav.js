(function () {
    'use strict';

    if (!document.body.classList.contains('nav-top') || document.body.getAttribute('data-partial-nav') !== '1') {
        return;
    }

    var FRAME_ID = 'admin-main';
    var FULL_PAGE_PATHS = [
        '/admin/provider/create',
    ];
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

    function requiresFullPageNavigation(href) {
        if (!href) {
            return false;
        }

        try {
            var path = new URL(href, window.location.origin).pathname.replace(/\/+$/, '') || '/';

            return FULL_PAGE_PATHS.indexOf(path) !== -1;
        } catch (e) {
            return false;
        }
    }

    function markFullPageLinks() {
        document.querySelectorAll('a.admin-logout, a[data-turbo="false"]').forEach(function (link) {
            link.setAttribute('data-turbo', 'false');
        });

        document.querySelectorAll('a[href]').forEach(function (link) {
            if (!requiresFullPageNavigation(link.href)) {
                return;
            }

            link.setAttribute('data-turbo', 'false');
            link.removeAttribute('data-turbo-frame');
            link.removeAttribute('data-turbo-action');
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

    function clearPartialStyles() {
        document.querySelectorAll('head [data-admin-partial-style]').forEach(function (el) {
            el.remove();
        });
    }

    function resolveStylesheetHref(href) {
        try {
            return new URL(href, window.location.href).href;
        } catch (e) {
            return null;
        }
    }

    function findHeadStylesheet(href) {
        var absoluteHref = resolveStylesheetHref(href);
        if (!absoluteHref) {
            return null;
        }

        var links = document.querySelectorAll('link[rel="stylesheet"]');
        for (var i = 0; i < links.length; i++) {
            if (resolveStylesheetHref(links[i].getAttribute('href') || links[i].href) === absoluteHref) {
                return links[i];
            }
        }

        return null;
    }

    function stylesheetIsReady(link) {
        if (!link) {
            return false;
        }

        try {
            return !!link.sheet;
        } catch (e) {
            return false;
        }
    }

    function waitForStylesheetApplied(link) {
        return new Promise(function (resolve) {
            if (stylesheetIsReady(link)) {
                requestAnimationFrame(function () {
                    resolve();
                });
                return;
            }

            var attempts = 0;
            function poll() {
                if (stylesheetIsReady(link) || attempts++ > 60) {
                    requestAnimationFrame(function () {
                        resolve();
                    });
                    return;
                }

                requestAnimationFrame(poll);
            }

            poll();
        });
    }

    function loadStylesheetIntoHead(href) {
        return new Promise(function (resolve) {
            var absoluteHref = resolveStylesheetHref(href);
            if (!absoluteHref) {
                resolve();
                return;
            }

            var existing = findHeadStylesheet(absoluteHref);
            if (existing) {
                waitForStylesheetApplied(existing).then(resolve);
                return;
            }

            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = absoluteHref;
            link.setAttribute('data-admin-partial-style', '1');
            link.addEventListener('load', function () {
                waitForStylesheetApplied(link).then(resolve);
            }, { once: true });
            link.addEventListener('error', function () {
                resolve();
            }, { once: true });
            document.head.appendChild(link);
        });
    }

    function getHeadStylesheetHrefs(doc) {
        var hrefs = [];
        var root = doc ? doc.head : document.head;

        root.querySelectorAll('link[rel="stylesheet"][href]').forEach(function (link) {
            var abs = resolveStylesheetHref(link.getAttribute('href'));
            if (abs && hrefs.indexOf(abs) === -1) {
                hrefs.push(abs);
            }
        });

        return hrefs;
    }

    function copyNewInlineStyles(sourceDoc, frameEl) {
        document.querySelectorAll('head style[data-admin-partial-style]').forEach(function (style) {
            style.remove();
        });

        var existingStyleHashes = new Set();
        document.querySelectorAll('head style:not([data-admin-partial-style])').forEach(function (style) {
            var css = (style.textContent || '').trim();
            if (css) {
                existingStyleHashes.add(css);
            }
        });

        function appendStyle(css) {
            if (!css || existingStyleHashes.has(css)) {
                return;
            }

            var style = document.createElement('style');
            style.setAttribute('data-admin-partial-style', '1');
            style.textContent = css;
            document.head.appendChild(style);
            existingStyleHashes.add(css);
        }

        if (sourceDoc) {
            sourceDoc.querySelectorAll('head style').forEach(function (style) {
                appendStyle((style.textContent || '').trim());
            });
        }

        if (frameEl) {
            frameEl.querySelectorAll('style').forEach(function (style) {
                appendStyle((style.textContent || '').trim());
            });
        }
    }

    async function applyPartialStylesFromDocument(doc, frameEl) {
        var existing = new Set(getHeadStylesheetHrefs(document));
        var needed = new Set(getHeadStylesheetHrefs(doc));
        var toLoad = [];

        if (frameEl) {
            frameEl.querySelectorAll('link[rel="stylesheet"][href]').forEach(function (link) {
                var abs = resolveStylesheetHref(link.getAttribute('href'));
                if (abs) {
                    needed.add(abs);
                }
            });
        }

        needed.forEach(function (abs) {
            if (!existing.has(abs)) {
                toLoad.push(abs);
            }
        });

        await Promise.all(toLoad.map(loadStylesheetIntoHead));
        copyNewInlineStyles(doc, frameEl);

        document.querySelectorAll('[data-admin-partial-style]').forEach(function (el) {
            if (el.tagName !== 'LINK') {
                return;
            }

            var href = resolveStylesheetHref(el.getAttribute('href'));
            if (href && !needed.has(href)) {
                el.remove();
            }
        });
    }

    function waitForDocumentStyles() {
        return new Promise(function (resolve) {
            var links = document.querySelectorAll('link[rel="stylesheet"][href]');
            var pending = [];

            links.forEach(function (link) {
                if (!stylesheetIsReady(link)) {
                    pending.push(new Promise(function (res) {
                        link.addEventListener('load', function () {
                            waitForStylesheetApplied(link).then(res);
                        }, { once: true });
                        link.addEventListener('error', res, { once: true });
                    }));
                }
            });

            function finish() {
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        resolve();
                    });
                });
            }

            if (pending.length === 0) {
                finish();
                return;
            }

            Promise.all(pending).then(finish);
        });
    }

    function revealAdminShell() {
        document.documentElement.classList.add('admin-shell-ready');
    }

    function frameContentWithoutAssets(frameEl) {
        var clone = frameEl.cloneNode(true);
        clone.querySelectorAll('link[rel="stylesheet"], style').forEach(function (el) {
            el.remove();
        });
        return clone.innerHTML;
    }

    function setFrameLoading(frame, loading) {
        if (!frame) {
            return;
        }

        frame.classList.toggle('admin-main-frame--loading', !!loading);
        frame.setAttribute('aria-busy', loading ? 'true' : 'false');
    }

    async function prepareFrameContent(doc, frameEl) {
        await applyPartialStylesFromDocument(doc, frameEl);
        return frameContentWithoutAssets(frameEl);
    }

    async function hoistInitialFrameStyles(frame) {
        if (!frame) {
            revealAdminShell();
            return;
        }

        setFrameLoading(frame, true);
        try {
            if (frame.querySelector('link[rel="stylesheet"], style')) {
                frame.innerHTML = await prepareFrameContent(document, frame);
            }
            await waitForDocumentStyles();
        } finally {
            setFrameLoading(frame, false);
            revealAdminShell();
        }
    }

    function isPartialNavLink(link) {
        if (!link || !link.getAttribute('href') || link.getAttribute('href') === '#') {
            return false;
        }

        if (link.getAttribute('data-turbo') === 'false') {
            return false;
        }

        if (requiresFullPageNavigation(link.href)) {
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

            setFrameLoading(frame, true);
            var frameHtml = await prepareFrameContent(parsed.doc, parsed.frame);
            frame.innerHTML = frameHtml;
            await waitForDocumentStyles();
            setFrameLoading(frame, false);
            revealAdminShell();
            activateScripts(frame);

            if (options.advance !== false) {
                window.history.pushState({ adminPartialNav: true }, '', url);
            }

            hideProgressSoon();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            initPageWidgets(frame);
            markFullPageLinks();
            markPartialNavLinks(frame);

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

    function markPartialNavLinks(root) {
        root = root || document.getElementById(FRAME_ID) || document;

        root.querySelectorAll('a[href]').forEach(function (link) {
            if (link.getAttribute('data-turbo-frame') === FRAME_ID) {
                return;
            }

            if (link.getAttribute('data-turbo') === 'false') {
                return;
            }

            if (link.target === '_blank' || link.hasAttribute('download')) {
                return;
            }

            if (link.classList.contains('admin-logout')) {
                return;
            }

            var href = link.getAttribute('href');
            if (!href || href === '#') {
                return;
            }

            try {
                var url = new URL(href, window.location.origin);
                if (url.origin !== window.location.origin) {
                    return;
                }

                if (!url.pathname.startsWith('/admin')) {
                    return;
                }

                if (requiresFullPageNavigation(url.href)) {
                    return;
                }
            } catch (e) {
                return;
            }

            link.setAttribute('data-turbo-frame', FRAME_ID);
            if (!link.hasAttribute('data-turbo-action')) {
                link.setAttribute('data-turbo-action', 'advance');
            }
        });
    }

    window.adminPartialNavLoad = loadPartialPage;

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

        if (requiresFullPageNavigation(window.location.href)) {
            window.location.reload();
            return;
        }

        loadPartialPage(window.location.href, { advance: false });
    });

    document.addEventListener('admin:chrome-updated', function () {
        markFullPageLinks();
        markPartialNavLinks();
        initChromeTooltips();
    });

    markFullPageLinks();
    markPartialNavLinks();

    var initialFrame = document.getElementById(FRAME_ID);
    if (initialFrame) {
        hoistInitialFrameStyles(initialFrame).finally(function () {
            markPartialNavLinks(initialFrame);
            initPageWidgets(initialFrame);
        });
    } else {
        initPageWidgets(initialFrame);
    }

    try {
        sessionStorage.setItem('admin_shell_ready', '1');
    } catch (e) {}
})();
