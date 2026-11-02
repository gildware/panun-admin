{{-- Inline critical top-chrome styles/scripts so Hide/Show still works when CDN top-nav.css/js is stale. --}}
<style>
    /* Hide/Show header — solid navy so it stays visible on the bar AND on white when hidden. */
    #top-chrome-visibility-toggle.top-chrome-visibility-toggle {
        position: fixed !important;
        z-index: 1040 !important;
        top: 0.4rem !important;
        left: 0.5rem !important;
        width: 2.15rem !important;
        height: 2.15rem !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        border: 1px solid #1a1c38 !important;
        border-radius: 0.4rem !important;
        background: #25274d !important;
        color: #ffffff !important;
        cursor: pointer !important;
        box-shadow: 0 0.25rem 0.7rem rgba(37, 39, 77, 0.45) !important;
    }
    #top-chrome-visibility-toggle.top-chrome-visibility-toggle:hover,
    #top-chrome-visibility-toggle.top-chrome-visibility-toggle:focus-visible {
        background: #32355f !important;
        color: #ffffff !important;
        outline: 2px solid #ffffff !important;
        outline-offset: 1px !important;
    }
    #top-chrome-visibility-toggle .material-icons {
        font-size: 1.3rem !important;
        line-height: 1 !important;
        color: #ffffff !important;
        opacity: 1 !important;
    }
    #top-chrome-visibility-toggle .top-chrome-visibility-icon--show {
        display: none !important;
    }
    body.nav-top.top-chrome-hidden #top-chrome-visibility-toggle.top-chrome-visibility-toggle {
        background: #25274d !important;
        border-color: #1a1c38 !important;
        box-shadow: 0 0.35rem 0.95rem rgba(37, 39, 77, 0.55) !important;
    }
    body.nav-top.top-chrome-hidden #top-chrome-visibility-toggle .top-chrome-visibility-icon--hide {
        display: none !important;
    }
    body.nav-top.top-chrome-hidden #top-chrome-visibility-toggle .top-chrome-visibility-icon--show {
        display: inline-flex !important;
        color: #ffffff !important;
    }
    body.nav-top .top-utility-bar {
        padding-left: 3.15rem !important;
    }

    /* Hide behavior (also often missing from stale CDN top-nav.css). */
    body.nav-top.top-chrome-hidden .top-chrome {
        transform: translateY(-100%) !important;
        pointer-events: none !important;
        box-shadow: none !important;
    }
    body.nav-top.top-chrome-hidden .main-area {
        padding-block-start: 2.75rem !important;
    }

    /* Pin/unpin must show one label only (avoids stale CDN CSS cache on live). */
    #top-chrome-mode-toggle .top-chrome-mode-option--unpin { display: none !important; }
    body:not(.top-chrome-auto-hide) #top-chrome-mode-toggle .top-chrome-mode-option--pin { display: none !important; }
    body:not(.top-chrome-auto-hide) #top-chrome-mode-toggle .top-chrome-mode-option--unpin { display: inline-flex !important; }
    body.top-chrome-auto-hide #top-chrome-mode-toggle .top-chrome-mode-option--pin { display: inline-flex !important; }
    body.top-chrome-auto-hide #top-chrome-mode-toggle .top-chrome-mode-option--unpin { display: none !important; }
</style>
<script>
(function () {
    var KEY = 'admin_top_chrome_hidden';

    function bind() {
        var toggle = document.getElementById('top-chrome-visibility-toggle');
        if (!toggle || toggle.dataset.visibilityBound === '1') {
            return;
        }

        function isHidden() {
            try { return localStorage.getItem(KEY) === '1'; } catch (e) { return false; }
        }

        function apply(hidden) {
            document.body.classList.toggle('top-chrome-hidden', !!hidden);
            try { localStorage.setItem(KEY, hidden ? '1' : '0'); } catch (e) {}
            toggle.setAttribute('aria-expanded', hidden ? 'false' : 'true');
            var label = hidden
                ? (toggle.getAttribute('data-label-show') || 'Show header')
                : (toggle.getAttribute('data-label-hide') || 'Hide header');
            toggle.title = label;
            toggle.setAttribute('aria-label', label);
            var chrome = document.querySelector('.top-chrome');
            if (chrome) {
                if (hidden) {
                    chrome.setAttribute('aria-hidden', 'true');
                    chrome.setAttribute('inert', '');
                } else {
                    chrome.removeAttribute('aria-hidden');
                    chrome.removeAttribute('inert');
                }
            }
        }

        toggle.dataset.visibilityBound = '1';
        apply(isHidden());
        toggle.addEventListener('click', function () {
            apply(!isHidden());
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
</script>
