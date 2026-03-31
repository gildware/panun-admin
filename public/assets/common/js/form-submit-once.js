/**
 * Disable submit buttons/inputs as soon as a form will actually submit (prevents double POST).
 * Runs in bubbling phase after other handlers: skipped if default was prevented (e.g. client validation).
 * Opt out: add data-allow-duplicate-submit on the <form>.
 * Covers buttons outside the form that use form="form-id".
 */
(function ($) {
    'use strict';

    $(document).on('submit', 'form', function (e) {
        if (e.isDefaultPrevented()) {
            return;
        }
        var form = e.target;
        if (!form || form.nodeName !== 'FORM') {
            return;
        }
        if (form.hasAttribute('data-allow-duplicate-submit')) {
            return;
        }

        var fid = form.id || '';
        var $buttons = $(form).find('button[type="submit"], input[type="submit"]');
        if (fid) {
            $buttons = $buttons.add($('button[form="' + fid + '"], input[form="' + fid + '"]'));
        }

        $buttons.each(function () {
            if (this.disabled) {
                return;
            }
            this.disabled = true;
            this.setAttribute('aria-busy', 'true');
        });
    });
})(jQuery);
