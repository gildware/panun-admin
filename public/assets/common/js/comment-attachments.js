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

    function stripComposeInvisibles(value) {
        return String(value || '')
            .replace(/[\u200b\u200c\u200d\u2060\ufeff]/g, '')
            .replace(/\u00a0/g, ' ');
    }

    function composeTextIsEmpty(value) {
        return stripComposeInvisibles(value).trim() === '';
    }

    function editableText(editable) {
        if (!editable) {
            return '';
        }
        var raw = '';
        if (typeof window.serializeStaffChatEditable === 'function') {
            raw = String(window.serializeStaffChatEditable(editable) || '');
        }
        if (composeTextIsEmpty(raw)) {
            raw = String(editable.innerText || editable.textContent || '');
        }
        return stripComposeInvisibles(raw);
    }

    function readComposeBody(form) {
        var body = form.querySelector(
            'textarea[name="body"], textarea.staff-chat-message-input'
        );
        // Snapshot before sync — empty contenteditable placeholders (ZWSP)
        // used to overwrite plain typed text when no staff/provider/service
        // tag had forced a real editor sync.
        var beforeSync = body ? stripComposeInvisibles(body.value) : '';
        var editable = form.querySelector('.staff-chat-compose-editable');
        var beforeEditable = editableText(editable);

        if (typeof window.syncStaffChatComposeForm === 'function') {
            window.syncStaffChatComposeForm(form);
        }

        var fromTextarea = body ? stripComposeInvisibles(body.value) : '';
        var fromEditable = editableText(editable);

        var candidates = [fromEditable, fromTextarea, beforeEditable, beforeSync];
        var bodyValue = '';
        for (var i = 0; i < candidates.length; i++) {
            if (!composeTextIsEmpty(candidates[i])) {
                bodyValue = candidates[i];
                break;
            }
        }

        if (body && bodyValue !== body.value) {
            body.value = bodyValue;
        }

        if (typeof window.resolveStaffChatTags === 'function') {
            bodyValue = window.resolveStaffChatTags(bodyValue);
        }

        return stripComposeInvisibles(bodyValue);
    }

    function buildFormData(form, composeEl) {
        var bodyValue = readComposeBody(form);
        var fd = new FormData(form);
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
                    'Adding comment...';
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

        // Sync before the click becomes a submit, so the last keystroke is captured.
        form.addEventListener('mousedown', function (event) {
            var submitBtn = event.target.closest('[type="submit"]');
            if (submitBtn && form.contains(submitBtn)) {
                readComposeBody(form);
            }
        });

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
                    var bodyField = form.querySelector(
                        'textarea[name="body"], textarea.staff-chat-message-input'
                    );
                    if (bodyField) {
                        bodyField.value = '';
                    }
                    var editable = form.querySelector('.staff-chat-compose-editable');
                    if (editable) {
                        editable.innerHTML = '';
                    }
                    // Restore button before reload so a slow/failed reload
                    // does not leave the form stuck on "Adding...".
                    setFormSubmitting(form, false);
                    if (typeof toastr !== 'undefined') {
                        toastr.success(
                            window.commentAttachmentsSuccessMessage ||
                                'Comment added'
                        );
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
