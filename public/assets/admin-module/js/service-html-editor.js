/**
 * Rich HTML editor for service long description (admin create/edit).
 * Loaded once per page; AI helper scripts must not call tinymce.init again.
 */
(function ($) {
    'use strict';

    if (window.panunServiceHtmlEditorInit) {
        return;
    }
    window.panunServiceHtmlEditorInit = true;

    function descriptionEditorId(lang) {
        return lang === 'default' ? 'default_description' : lang + '_description';
    }

    window.syncServiceDescriptionEditors = function () {
        if (typeof tinymce !== 'undefined') {
            if (tinymce.editors) {
                tinymce.editors.forEach(function (editor) {
                    editor.save();
                });
            }
            tinymce.triggerSave();
        }
    };

    window.showServiceDescriptionEditorForLang = function (lang) {
        if (typeof tinymce === 'undefined' || !tinymce.editors) {
            return;
        }
        var activeId = descriptionEditorId(lang);
        tinymce.editors.forEach(function (editor) {
            if (editor.id === activeId) {
                editor.show();
                editor.fire('ResizeEditor');
            } else {
                editor.hide();
            }
        });
    };

    function initServiceDescriptionEditors() {
        if (typeof tinymce === 'undefined') {
            return;
        }

        tinymce.init({
            selector: 'textarea.ckeditor',
            width: '100%',
            min_height: 300,
            height: 300,
            menubar: false,
            branding: false,
            promotion: false,
            convert_urls: false,
            relative_urls: false,
            plugins: 'lists link image table code fullscreen preview',
            toolbar:
                'undo redo | blocks | bold italic underline strikethrough | ' +
                'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | ' +
                'link image table | removeformat code fullscreen preview',
            content_style:
                'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.6; }',
            setup: function (editor) {
                editor.on('change keyup undo redo', function () {
                    tinymce.triggerSave();
                });
            },
            init_instance_callback: function (editor) {
                editor.fire('ResizeEditor');
                var $el = $(editor.getElement());
                if ($el.closest('.d-none').length) {
                    editor.hide();
                }
            },
        });
    }

    $(document).ready(function () {
        initServiceDescriptionEditors();

        $(document).on('submit', '#service-add-form, #service-create-form, #service-edit-info-form, #form-wizard', function () {
            window.syncServiceDescriptionEditors();
        });
    });
})(jQuery);
