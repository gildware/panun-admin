{{-- Admin: optional WhatsApp preview after booking actions (JSON whatsapp_admin_prompt or session flash). --}}
@php($waBookingPromptFlash = session()->pull('whatsapp_admin_booking_prompt'))
<div class="modal fade" id="waAdminBookingSendPromptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('WhatsApp_admin_send_message_modal_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">{{ translate('WhatsApp_admin_send_message_modal_hint') }}</p>
                <div id="waAdminBookingSendPromptBody"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
            </div>
        </div>
    </div>
</div>
<style>
    /* Match admin marketing template + booking message template previews */
    #waAdminBookingSendPromptModal .wa-tpl-phone-notch { background: rgba(0, 0, 0, 0.2); }
    #waAdminBookingSendPromptModal .wa-tpl-phone-preview { max-width: 320px; }
    #waAdminBookingSendPromptModal .wa-tpl-phone-frame {
        background: linear-gradient(160deg, #075e54 0%, #128c7e 45%, #25d366 100%);
        border: 1px solid rgba(0, 0, 0, 0.08);
    }
    #waAdminBookingSendPromptModal .wa-tpl-phone-body {
        background: #e5ddd5;
        min-height: 160px;
        background-image:
            radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.12) 0, transparent 45%),
            radial-gradient(circle at 80% 70%, rgba(0, 0, 0, 0.04) 0, transparent 40%);
    }
    #waAdminBookingSendPromptModal .wa-tpl-btn-fake { pointer-events: none; cursor: default; }
    #waAdminBookingSendPromptModal .wa-admin-prompt-preview-mount { margin-bottom: 0.75rem; }
</style>

<script>
(function () {
    var sendRowUrl = @json(route('admin.booking.whatsapp_automation_prompt.send_row'));
    var skipRowUrl = @json(route('admin.booking.whatsapp_automation_prompt.skip_row'));

    var WABOOKING_PREVIEW_HEADING = @json(translate('WhatsApp_booking_preview_heading'));
    var WA_TEMPLATE_PREVIEW_DISCLAIMER = @json(translate('Template_preview_disclaimer'));
    var PREVIEW_BADGE = @json(translate('preview'));

    window.__waSuppressNextAjaxCompletePrompt = false;

    /**
     * Preview text from server is template title line(s) then blank line then body (see BookingWhatsAppNotificationService).
     */
    function parseBookingWaPreviewText(raw) {
        var s = String(raw || '').trim();
        if (!s) {
            return { header: null, body: '' };
        }
        var idx = s.indexOf('\n\n');
        if (idx === -1) {
            return { header: null, body: s };
        }
        var head = s.slice(0, idx).trim();
        var body = s.slice(idx + 2).trim();
        return { header: head || null, body: body };
    }

    function buildWaPhoneTemplatePreview(parsed) {
        var mount = document.createElement('div');
        mount.className = 'wa-admin-prompt-preview-mount';

        var heading = document.createElement('div');
        heading.className = 'small fw-semibold text-secondary text-uppercase mb-2';
        heading.style.letterSpacing = '0.03em';
        heading.textContent = WABOOKING_PREVIEW_HEADING;
        mount.appendChild(heading);

        var phonePrev = document.createElement('div');
        phonePrev.className = 'wa-tpl-phone-preview mx-auto';

        var frame = document.createElement('div');
        frame.className = 'wa-tpl-phone-frame rounded-4 overflow-hidden shadow';

        var notch = document.createElement('div');
        notch.className = 'wa-tpl-phone-notch d-flex align-items-center justify-content-between px-3 py-2';
        var spPreview = document.createElement('span');
        spPreview.className = 'small fw-semibold text-white-50';
        spPreview.textContent = PREVIEW_BADGE;
        var spWa = document.createElement('span');
        spWa.className = 'small text-white-50';
        spWa.textContent = 'WhatsApp';
        notch.appendChild(spPreview);
        notch.appendChild(spWa);
        frame.appendChild(notch);

        var phoneBody = document.createElement('div');
        phoneBody.className = 'wa-tpl-phone-body p-3';

        var bubble = document.createElement('div');
        bubble.className = 'wa-tpl-bubble rounded-3 p-3 bg-white shadow-sm border border-light';

        if (parsed.header) {
            var hw = document.createElement('div');
            hw.className = 'mb-2';
            var hd = document.createElement('div');
            hd.className = 'fw-semibold small text-break';
            hd.textContent = parsed.header;
            hw.appendChild(hd);
            bubble.appendChild(hw);
        }

        var bodyEl = document.createElement('div');
        bodyEl.className = 'small text-break wa-tpl-body-text';
        bodyEl.style.whiteSpace = 'pre-wrap';
        bodyEl.textContent = parsed.body || '';
        bubble.appendChild(bodyEl);

        phoneBody.appendChild(bubble);

        var disc = document.createElement('p');
        disc.className = 'text-center text-muted mt-2 mb-0';
        disc.style.fontSize = '0.65rem';
        disc.textContent = WA_TEMPLATE_PREVIEW_DISCLAIMER;
        phoneBody.appendChild(disc);

        frame.appendChild(phoneBody);
        phonePrev.appendChild(frame);
        mount.appendChild(phonePrev);

        return mount;
    }

    function getBootstrapModalInstance(modalEl) {
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return null;
        }
        if (typeof bootstrap.Modal.getOrCreateInstance === 'function') {
            return bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        var existing = bootstrap.Modal.getInstance(modalEl);
        if (existing) {
            return existing;
        }
        return new bootstrap.Modal(modalEl);
    }

    function maybeFinishPrompt(modal, remaining) {
        if (remaining > 0) {
            return;
        }
        try {
            modal.hide();
        } catch (e) {}
    }

    /**
     * @param {object} payload — { token, title?, rows }
     * @param {function} [onDone] — when all rows are sent or skipped (or modal closed with no rows left)
     */
    window.openWhatsAppAdminBookingPrompt = function (payload, onDone) {
        if (typeof onDone !== 'function') {
            onDone = function () {};
        }
        if (!payload || !payload.token || !payload.rows || !payload.rows.length) {
            onDone();
            return;
        }
        var body = document.getElementById('waAdminBookingSendPromptBody');
        if (!body) {
            onDone();
            return;
        }

        var modalEl = document.getElementById('waAdminBookingSendPromptModal');
        var modal = modalEl ? getBootstrapModalInstance(modalEl) : null;
        if (!modal) {
            onDone();
            return;
        }

        window.__waSuppressNextAjaxCompletePrompt = true;

        body.innerHTML = '';
        var token = payload.token;

        function currentRowIndex(rowEl) {
            var rows = body.querySelectorAll('.wa-admin-prompt-message-row');
            return Array.prototype.indexOf.call(rows, rowEl);
        }

        function bindRowHandlers() {
            var rows = body.querySelectorAll('.wa-admin-prompt-message-row');
            rows.forEach(function (rowEl) {
                var sendBtn = rowEl.querySelector('.wa-admin-prompt-row-send');
                var skipBtn = rowEl.querySelector('.wa-admin-prompt-row-skip');
                if (sendBtn) {
                    sendBtn.onclick = function () {
                        var idx = currentRowIndex(rowEl);
                        if (idx < 0) {
                            return;
                        }
                        sendBtn.disabled = true;
                        if (skipBtn) {
                            skipBtn.disabled = true;
                        }
                        $.ajax({
                            url: sendRowUrl,
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                token: token,
                                index: idx
                            },
                            success: function (res) {
                                if (res && res.success && typeof toastr !== 'undefined') {
                                    toastr.success(res.message || '');
                                } else if (typeof toastr !== 'undefined') {
                                    toastr.error((res && res.message) ? res.message : 'Error');
                                }
                                rowEl.remove();
                                var rem = typeof res.remaining === 'number' ? res.remaining : body.querySelectorAll('.wa-admin-prompt-message-row').length;
                                maybeFinishPrompt(modal, rem);
                            },
                            error: function (xhr) {
                                var msg = 'Error';
                                try {
                                    var j = xhr.responseJSON;
                                    if (j && j.message) {
                                        msg = j.message;
                                    }
                                } catch (e) {}
                                if (typeof toastr !== 'undefined') {
                                    toastr.error(msg);
                                }
                                sendBtn.disabled = false;
                                if (skipBtn) {
                                    skipBtn.disabled = false;
                                }
                            }
                        });
                    };
                }
                if (skipBtn) {
                    skipBtn.onclick = function () {
                        var idx = currentRowIndex(rowEl);
                        if (idx < 0) {
                            return;
                        }
                        skipBtn.disabled = true;
                        if (sendBtn) {
                            sendBtn.disabled = true;
                        }
                        $.ajax({
                            url: skipRowUrl,
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                token: token,
                                index: idx
                            },
                            success: function (res) {
                                if (res && res.success && typeof toastr !== 'undefined') {
                                    toastr.info(res.message || '');
                                }
                                rowEl.remove();
                                var rem = typeof res.remaining === 'number' ? res.remaining : body.querySelectorAll('.wa-admin-prompt-message-row').length;
                                maybeFinishPrompt(modal, rem);
                            },
                            error: function (xhr) {
                                var msg = 'Error';
                                try {
                                    var j = xhr.responseJSON;
                                    if (j && j.message) {
                                        msg = j.message;
                                    }
                                } catch (e) {}
                                if (typeof toastr !== 'undefined') {
                                    toastr.error(msg);
                                }
                                skipBtn.disabled = false;
                                if (sendBtn) {
                                    sendBtn.disabled = false;
                                }
                            }
                        });
                    };
                }
            });
        }

        payload.rows.forEach(function (row) {
            var card = document.createElement('div');
            card.className = 'card mb-3 wa-admin-prompt-message-row';
            var inner = document.createElement('div');
            inner.className = 'card-body py-3';
            var title = document.createElement('div');
            title.className = 'fw-semibold mb-1';
            title.textContent = (row.recipient_label || '') + (row.to_phone ? ' · ' + row.to_phone : '');
            inner.appendChild(title);
            if (row.template_name) {
                var tplMeta = document.createElement('div');
                tplMeta.className = 'text-muted small mb-2';
                tplMeta.textContent = String(row.template_name);
                inner.appendChild(tplMeta);
            }
            if (row.missing_phone) {
                var warn = document.createElement('div');
                warn.className = 'text-danger small mb-2';
                warn.textContent = @json(translate('WhatsApp_admin_send_message_no_phone'));
                inner.appendChild(warn);
            }
            var parsed = parseBookingWaPreviewText(row.preview_text || '');
            inner.appendChild(buildWaPhoneTemplatePreview(parsed));
            var actions = document.createElement('div');
            actions.className = 'd-flex flex-wrap gap-2 justify-content-end';
            var skipB = document.createElement('button');
            skipB.type = 'button';
            skipB.className = 'btn btn-outline-secondary btn-sm wa-admin-prompt-row-skip';
            skipB.textContent = @json(translate('WhatsApp_admin_send_message_skip'));
            var sendB = document.createElement('button');
            sendB.type = 'button';
            sendB.className = 'btn btn--primary btn-sm wa-admin-prompt-row-send';
            sendB.textContent = @json(translate('WhatsApp_admin_send_message_send'));
            actions.appendChild(skipB);
            actions.appendChild(sendB);
            inner.appendChild(actions);
            card.appendChild(inner);
            body.appendChild(card);
        });

        bindRowHandlers();

        var finalized = false;
        function finalizeOnce() {
            if (finalized) {
                return;
            }
            finalized = true;
            onDone();
        }

        modalEl.addEventListener('hidden.bs.modal', function onWaPromptHidden() {
            modalEl.removeEventListener('hidden.bs.modal', onWaPromptHidden);
            finalizeOnce();
        }, { once: true });

        modal.show();
    };

    window.waAdminAfterAjaxWithOptionalWhatsAppPrompt = function (data, proceed) {
        if (typeof proceed !== 'function') {
            proceed = function () {};
        }
        if (data && data.whatsapp_admin_prompt && typeof window.openWhatsAppAdminBookingPrompt === 'function') {
            window.openWhatsAppAdminBookingPrompt(data.whatsapp_admin_prompt, proceed);
            return;
        }
        proceed();
    };

    $(document).ajaxComplete(function (_event, xhr) {
        if (window.__waSuppressNextAjaxCompletePrompt) {
            window.__waSuppressNextAjaxCompletePrompt = false;
            return;
        }
        try {
            var json = xhr.responseJSON;
            if (json && json.whatsapp_admin_prompt) {
                window.openWhatsAppAdminBookingPrompt(json.whatsapp_admin_prompt, function () {});
            }
        } catch (e) {}
    });

    $(function () {
        @if(!empty($waBookingPromptFlash))
        window.__waAdminBookingPromptFromPage = @json($waBookingPromptFlash);
        @endif
        if (typeof window.__waAdminBookingPromptFromPage !== 'undefined' && window.__waAdminBookingPromptFromPage) {
            window.openWhatsAppAdminBookingPrompt(window.__waAdminBookingPromptFromPage, function () {});
            window.__waAdminBookingPromptFromPage = null;
        }
    });
})();
</script>
