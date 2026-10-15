/**
 * Save, Submit, and Confirm stay disabled while a request runs, and show a
 * spinner so the click does not look frozen.
 * Opt out of the duplicate-submit guard: data-allow-duplicate-submit on the form.
 * Opt out of the spinner: data-no-loading on the button.
 */
(function ($) {
    'use strict';

    var armed = null;
    var armedUntil = 0;
    var style = document.createElement('style');
    style.textContent = [
        'button.is-loading, input.is-loading { cursor: progress; }',
        'button.is-loading:disabled, input.is-loading:disabled { opacity: 1; }',
        'button.is-loading .spinner-border { vertical-align: -0.125em; }'
    ].join('');
    document.head.appendChild(style);

    function labelOf(el) {
        return String(el.innerText || el.value || '').replace(/\s+/g, ' ').trim();
    }

    function isActionButton(el) {
        if (!el || el.hasAttribute('data-no-loading')) {
            return false;
        }
        var tag = el.nodeName;
        if (tag !== 'BUTTON' && !(tag === 'INPUT' && (el.type === 'submit' || el.type === 'button'))) {
            return false;
        }
        if (el.type === 'submit') {
            return true;
        }
        var hint = (labelOf(el) + ' ' + (el.id || '') + ' ' + (el.className || '')).toLowerCase();
        return /\b(save|submit|confirm)\b/.test(hint);
    }

    function start(el) {
        if (!el || el.dataset.loading === '1' || !isActionButton(el)) {
            return;
        }
        el.dataset.loading = '1';
        el.classList.add('is-loading');
        el.setAttribute('aria-busy', 'true');
        if (el.nodeName === 'BUTTON') {
            el.dataset.originalHtml = el.innerHTML;
            var text = labelOf(el);
            el.textContent = '';
            var spin = document.createElement('span');
            spin.className = 'spinner-border spinner-border-sm me-1';
            spin.setAttribute('role', 'status');
            spin.setAttribute('aria-hidden', 'true');
            var label = document.createElement('span');
            label.textContent = text;
            el.appendChild(spin);
            if (text) {
                el.appendChild(document.createTextNode(' '));
                el.appendChild(label);
            }
        } else {
            el.dataset.originalValue = el.value;
            el.value = labelOf(el);
        }
        el.disabled = true;
    }

    function stop(el) {
        if (!el || el.dataset.loading !== '1') {
            return;
        }
        delete el.dataset.loading;
        el.classList.remove('is-loading');
        el.removeAttribute('aria-busy');
        if (el.dataset.originalHtml != null) {
            el.innerHTML = el.dataset.originalHtml;
            delete el.dataset.originalHtml;
        }
        if (el.dataset.originalValue != null) {
            el.value = el.dataset.originalValue;
            delete el.dataset.originalValue;
        }
    }

    function remember(el) {
        if (!isActionButton(el) || el.disabled || el.dataset.loading === '1') {
            return;
        }
        armed = el;
        armedUntil = Date.now() + 2000;
    }

    function maybeStart(el) {
        if (el && el === armed && Date.now() <= armedUntil) {
            armed = null;
            start(el);
        }
    }

    document.addEventListener('click', function (event) {
        var el = event.target && event.target.closest
            ? event.target.closest('button, input[type="submit"], input[type="button"]')
            : null;
        remember(el);
    }, true);

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || form.nodeName !== 'FORM' || form.hasAttribute('data-allow-duplicate-submit')) {
            return;
        }

        queueMicrotask(function () {
            if (event.defaultPrevented) {
                return;
            }

            var fid = form.id || '';
            var buttons = Array.prototype.slice.call(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
            if (fid && /^[A-Za-z][\w:-]*$/.test(fid)) {
                buttons = buttons.concat(Array.prototype.slice.call(document.querySelectorAll('button[form="' + fid + '"], input[form="' + fid + '"]')));
            }

            buttons.forEach(function (button) {
                if (button.disabled && button.dataset.loading !== '1') {
                    return;
                }
                if (button === armed || button === document.activeElement) {
                    start(button);
                    return;
                }
                if (!button.disabled) {
                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');
                }
            });
            armed = null;
        });
    }, false);

    if ($) {
        var originalProp = $.fn.prop;
        $.fn.prop = function (name, value) {
            if (name === 'disabled' && arguments.length >= 2) {
                var result = originalProp.apply(this, arguments);
                this.each(function () {
                    if (value) {
                        maybeStart(this);
                    } else {
                        stop(this);
                    }
                });
                return result;
            }
            return originalProp.apply(this, arguments);
        };

        $(document).ajaxComplete(function (_event, xhr) {
            if (xhr && xhr.status >= 400 && armed) {
                armed = null;
            }
        });
    }
})(window.jQuery);
