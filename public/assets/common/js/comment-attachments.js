(function () {
    'use strict';

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function getComposeState(composeEl) {
        if (!composeEl.__attachmentState) {
            composeEl.__attachmentState = { files: [] };
        }
        return composeEl.__attachmentState;
    }

    function renderPreview(composeEl) {
        var previewEl = composeEl.querySelector('[data-comment-attachments-preview]');
        var state = getComposeState(composeEl);
        if (!previewEl) {
            return;
        }

        var html = '';
        state.files.forEach(function (file, index) {
            html +=
                '<span class="comment-pending-file">' +
                escapeHtml(file.name) +
                ' <button type="button" class="comment-pending-file-remove" data-index="' +
                index +
                '" aria-label="Remove">&times;</button></span>';
        });
        previewEl.innerHTML = html;
    }

    function appendFiles(composeEl, fileList) {
        var state = getComposeState(composeEl);
        Array.from(fileList || []).forEach(function (file) {
            var exists = state.files.some(function (existing) {
                return (
                    existing.name === file.name &&
                    existing.size === file.size &&
                    existing.lastModified === file.lastModified
                );
            });
            if (!exists) {
                state.files.push(file);
            }
        });
        renderPreview(composeEl);
    }

    function removeFile(composeEl, index) {
        var state = getComposeState(composeEl);
        state.files.splice(index, 1);
        renderPreview(composeEl);
    }

    function bindCompose(composeEl) {
        if (!composeEl || composeEl.dataset.attachmentsBound === '1') {
            return;
        }
        composeEl.dataset.attachmentsBound = '1';

        composeEl.addEventListener('click', function (event) {
            var removeBtn = event.target.closest('.comment-pending-file-remove');
            if (removeBtn) {
                event.preventDefault();
                removeFile(composeEl, Number(removeBtn.getAttribute('data-index')));
            }
        });

        var hiddenInput = composeEl.querySelector('.comment-attachments-input');
        if (hiddenInput) {
            hiddenInput.addEventListener('change', function () {
                appendFiles(composeEl, hiddenInput.files);
                hiddenInput.value = '';
            });
        }
    }

    function buildFormData(form, composeEl) {
        var fd = new FormData(form);
        var body = form.querySelector('.staff-chat-message-input, textarea[name="body"]');
        var bodyValue = body ? String(body.value || '') : '';

        if (body && typeof window.resolveStaffChatTags === 'function') {
            bodyValue = window.resolveStaffChatTags(bodyValue);
        }

        fd.set('body', bodyValue);

        var state = composeEl ? getComposeState(composeEl) : { files: [] };
        fd.delete('files[]');
        fd.delete('files');
        state.files.forEach(function (file) {
            fd.append('files[]', file, file.name);
        });

        return { fd: fd, bodyValue: bodyValue, files: state.files };
    }

    function getSubmitButton(form) {
        return form.querySelector('[type="submit"]');
    }

    function setFormSubmitting(form, isSubmitting) {
        var submitBtn = getSubmitButton(form);

        if (isSubmitting) {
            form.classList.add('is-submitting');
            form.setAttribute('aria-busy', 'true');

            if (submitBtn) {
                if (!submitBtn.dataset.originalHtml) {
                    submitBtn.dataset.originalHtml = submitBtn.innerHTML;
                }
                var loadingLabel =
                    form.getAttribute('data-comment-loading-label') ||
                    window.commentAttachmentsLoadingMessage ||
                    'Adding...';
                submitBtn.disabled = true;
                submitBtn.setAttribute('aria-disabled', 'true');
                submitBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' +
                    escapeHtml(loadingLabel);
            }

            form.querySelectorAll(
                'input:not([type="hidden"]), textarea, button:not([type="submit"]), select'
            ).forEach(function (el) {
                if (el.dataset.wasDisabled === undefined) {
                    el.dataset.wasDisabled = el.disabled ? '1' : '0';
                }
                el.disabled = true;
            });
            return;
        }

        form.classList.remove('is-submitting');
        form.removeAttribute('aria-busy');

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.removeAttribute('aria-disabled');
            if (submitBtn.dataset.originalHtml) {
                submitBtn.innerHTML = submitBtn.dataset.originalHtml;
            }
        }

        form.querySelectorAll(
            'input:not([type="hidden"]), textarea, button:not([type="submit"]), select'
        ).forEach(function (el) {
            if (el.dataset.wasDisabled === '0') {
                el.disabled = false;
            }
            delete el.dataset.wasDisabled;
        });
    }

    function bindForm(form) {
        if (!form || form.dataset.attachmentsFormBound === '1') {
            return;
        }
        form.dataset.attachmentsFormBound = '1';

        var composeEl = form.querySelector('[data-comment-attachments-compose]');
        if (composeEl) {
            bindCompose(composeEl);
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (form.classList.contains('is-submitting')) {
                return;
            }

            var payload = buildFormData(form, composeEl);
            if (payload.bodyValue.trim() === '' && payload.files.length === 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.error(
                        window.commentAttachmentsEmptyMessage ||
                            'Please write a comment or attach a file'
                    );
                }
                return;
            }

            setFormSubmitting(form, true);

            fetch(form.action, {
                method: 'POST',
                body: payload.fd,
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            })
                .then(function (response) {
                    return response
                        .json()
                        .catch(function () {
                            return {};
                        })
                        .then(function (data) {
                            if (!response.ok) {
                                var message =
                                    data.message ||
                                    (data.errors &&
                                        Object.values(data.errors)[0] &&
                                        Object.values(data.errors)[0][0]) ||
                                    'Failed to add comment';
                                throw new Error(message);
                            }
                            return data;
                        });
                })
                .then(function (data) {
                    if (data.success === false) {
                        throw new Error(data.message || 'Failed to add comment');
                    }
                    if (composeEl) {
                        getComposeState(composeEl).files = [];
                        renderPreview(composeEl);
                    }
                    window.location.reload();
                })
                .catch(function (error) {
                    setFormSubmitting(form, false);
                    if (typeof toastr !== 'undefined') {
                        toastr.error(error.message || 'Failed to add comment');
                    }
                });
        });
    }

    function initCommentAttachments(root) {
        (root || document)
            .querySelectorAll('[data-comment-attachments-compose]')
            .forEach(bindCompose);
        (root || document)
            .querySelectorAll(
                'form.lead-comment-compose, form.comment-compose, #leadCommentForm, #bookingCommentForm'
            )
            .forEach(bindForm);
    }

    document.addEventListener('DOMContentLoaded', function () {
        initCommentAttachments(document);
    });

    document.addEventListener('admin:page-loaded', function (event) {
        initCommentAttachments(
            event.detail && event.detail.root ? event.detail.root : document
        );
    });

    if (document.readyState !== 'loading') {
        initCommentAttachments(document);
    }

    window.initCommentAttachments = initCommentAttachments;
})();
