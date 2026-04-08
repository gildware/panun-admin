@pushOnce('script', 'lead-open-phone-check')
    <script>
        (function ($) {
            const OPEN_PHONE_URL = @json(route('admin.lead.open-by-phone'));
            const MSG_EXISTING = @json(translate('Lead_existing_open_leads_same_phone'));
            const MSG_STILL_CREATE = @json(translate('Lead_still_want_create_new'));
            const MSG_LEAD_ID = @json(translate('Lead_ID'));
            let debounceTimer = null;

            function escapeHtml(s) {
                return String(s)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function renderAlert($wrap, leads) {
                $wrap.empty();
                if (!leads || !leads.length) {
                    $wrap.addClass('d-none');
                    return;
                }
                let html = '<div class="alert alert-warning border border-warning rounded mb-0">' +
                    '<p class="fw-semibold mb-2">' + escapeHtml(MSG_EXISTING) + '</p>' +
                    '<p class="small text-muted mb-3">' + escapeHtml(MSG_STILL_CREATE) + '</p>' +
                    '<ul class="list-unstyled mb-0 small">';
                leads.forEach(function (l) {
                    const namePart = l.name ? ' — ' + escapeHtml(String(l.name)) : '';
                    const typePart = l.lead_type_label ? ' <span class="text-muted">(' + escapeHtml(String(l.lead_type_label)) + ')</span>' : '';
                    const when = l.created_at ? ' <span class="text-muted">' + escapeHtml(String(l.created_at)) + '</span>' : '';
                    html += '<li class="mb-2">' +
                        '<a href="' + escapeHtml(String(l.url)) + '" target="_blank" rel="noopener" class="fw-medium">' +
                        escapeHtml(MSG_LEAD_ID) + ' #' + escapeHtml(String(l.id)) + '</a>' +
                        namePart + typePart + when +
                        '</li>';
                });
                html += '</ul></div>';
                $wrap.html(html).removeClass('d-none');
            }

            function checkPhone($input) {
                const $wrap = $input.closest('form').find('[data-lead-open-duplicates-alert]');
                if (!$wrap.length) {
                    return;
                }
                const raw = ($input.val() || '').trim();
                if (raw.length < 4) {
                    clearTimeout(debounceTimer);
                    $wrap.empty().addClass('d-none');
                    return;
                }
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    $.ajax({
                        url: OPEN_PHONE_URL,
                        method: 'GET',
                        data: { phone: raw },
                        dataType: 'json',
                    }).done(function (res) {
                        renderAlert($wrap, res.leads || []);
                    }).fail(function () {
                        $wrap.empty().addClass('d-none');
                    });
                }, 400);
            }

            $(document).on('input', '.js-lead-create-phone', function () {
                checkPhone($(this));
            });
        })(jQuery);
    </script>
@endPushOnce
