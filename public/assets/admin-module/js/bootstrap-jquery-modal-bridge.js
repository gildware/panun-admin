(function ($) {
    'use strict';

    if (!$ || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        return;
    }

    if ($.fn.modal && $.fn.modal._bs5Bridge) {
        return;
    }

    $.fn.modal = function (action) {
        return this.each(function () {
            var el = this;

            if (action === 'show') {
                bootstrap.Modal.getOrCreateInstance(el).show();
                return;
            }

            if (action === 'hide') {
                var hideInstance = bootstrap.Modal.getInstance(el);
                if (hideInstance) {
                    hideInstance.hide();
                }
                return;
            }

            if (action === 'toggle') {
                bootstrap.Modal.getOrCreateInstance(el).toggle();
                return;
            }

            if (action === 'dispose') {
                var disposeInstance = bootstrap.Modal.getInstance(el);
                if (disposeInstance) {
                    disposeInstance.dispose();
                }
                return;
            }

            bootstrap.Modal.getOrCreateInstance(el, typeof action === 'object' ? action : {});
        });
    };

    $.fn.modal._bs5Bridge = true;
    $.fn.modal.Constructor = bootstrap.Modal;
})(window.jQuery);
