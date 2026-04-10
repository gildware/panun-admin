@extends('adminmodule::layouts.master')

@section('title', translate('Send_Bulk_Message'))

@push('css_or_js')
    <style>
        .wa-csv-dropzone-hit {
            min-height: 160px;
            transition: border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
        }
        .wa-csv-dropzone-hit.is-dragover {
            border-color: var(--bs-primary) !important;
            background-color: rgba(var(--bs-primary-rgb), 0.08) !important;
            box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.12);
        }
        .wa-csv-file-input {
            cursor: pointer;
            z-index: 2;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('WhatsApp_Marketing') }} — {{ translate('Send_Bulk_Message') }}</h2>
                <p class="text-muted mb-0">Panun Kaergar</p>
            </div>

            <form id="bulk_marketing_form" action="{{ route('admin.whatsapp.marketing.bulk.store') }}" method="post"
                  enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="recipient_adjustments" id="recipient_adjustments" value="">
                <div class="card mb-3">
                    <div class="card-body">
                        <h4 class="mb-3">{{ translate('Campaign') }}</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="campaign_name" class="form-label">{{ translate('Campaign_name') }} *</label>
                                <input type="text" name="campaign_name" id="campaign_name" class="form-control"
                                       value="{{ old('campaign_name', $duplicate?->name) }}" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label for="template_id" class="form-label">{{ translate('Templates') }} *</label>
                                <select name="template_id" id="template_id" class="form-select js-select w-100" required>
                                    <option value="">{{ translate('Select') }} {{ translate('Templates') }}</option>
                                    @foreach($templates as $tpl)
                                        <option value="{{ $tpl->id }}"
                                                data-params="{{ (int) $tpl->body_parameter_count }}"
                                                data-preview="{{ e($tpl->preview_text ?? '') }}"
                                            {{ (string) old('template_id', $duplicate?->whatsapp_marketing_template_id) === (string) $tpl->id ? 'selected' : '' }}>
                                            {{ $tpl->name }} ({{ $tpl->language }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-light rounded d-none" id="template_preview_wrap">
                            <div class="fw-medium mb-2">{{ translate('preview') }}</div>
                            <pre class="mb-0 small text-break" id="template_preview_text"></pre>
                        </div>

                        <div class="mt-4 d-none" id="variable_mapping_section">
                            <h5 class="mb-2">{{ translate('Template_variables') }}</h5>
                            <p class="text-muted small">{{ translate('Map_template_placeholders') }}</p>
                            <div id="variable_rows"></div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h4 class="mb-3">{{ translate('Audience') }}</h4>
                        <p class="text-muted small mb-3">{{ translate('Audience_choose_hint') }}</p>

                        @php
                            $selectedAudience = old('audience_type', $duplicate?->audience_type);
                        @endphp
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label for="audience_type" class="form-label">{{ translate('Recipients') }} *</label>
                                <select name="audience_type" id="audience_type" class="form-select js-select w-100" required>
                                    <option value="" {{ $selectedAudience === null || $selectedAudience === '' ? 'selected' : '' }}>
                                        {{ translate('Select') }} {{ translate('Recipients') }}
                                    </option>
                                    <option value="all_customers" {{ (string) $selectedAudience === 'all_customers' ? 'selected' : '' }}>
                                        {{ translate('All_Customers') }} — {{ $audienceCounts['all_customers'] }} {{ translate('with_valid_phone') }}
                                    </option>
                                    <option value="all_providers" {{ (string) $selectedAudience === 'all_providers' ? 'selected' : '' }}>
                                        {{ translate('All_Providers') }} — {{ $audienceCounts['all_providers'] }} {{ translate('with_valid_phone') }}
                                    </option>
                                    <option value="providers_by_category" {{ (string) $selectedAudience === 'providers_by_category' ? 'selected' : '' }}>
                                        {{ translate('Providers_by_Category') }}
                                    </option>
                                    <option value="csv_import" {{ (string) $selectedAudience === 'csv_import' ? 'selected' : '' }}>
                                        {{ translate('Import_Contacts_CSV') }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-lg-6 d-none" id="category_wrap">
                                <label for="category_id" class="form-label">{{ translate('category') }} *</label>
                                <select name="category_id" id="category_id" class="form-select js-select w-100">
                                    <option value="">{{ translate('Select') }} {{ translate('category') }}</option>
                                    @foreach($categories as $cat)
                                        @php $cid = (string) $cat->id; @endphp
                                        <option value="{{ $cat->id }}"
                                                data-recipient-count="{{ (int) ($categoryRecipientCounts[$cid] ?? 0) }}"
                                            {{ (string) old('category_id', $duplicate?->category_id) === $cid ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                            ({{ (int) ($categoryRecipientCounts[$cid] ?? 0) }} {{ translate('providers') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 d-none" id="csv_wrap">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <label class="form-label mb-0" for="contacts_csv">{{ translate('CSV_file') }} *</label>
                                    <a href="{{ route('admin.whatsapp.marketing.bulk.sample-csv') }}"
                                       class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                        <span class="material-icons fz-18">download</span>
                                        {{ translate('Download_sample_CSV') }}
                                    </a>
                                </div>
                                <div class="wa-csv-dropzone card border-0 shadow-sm mb-2">
                                    <div class="wa-csv-dropzone-hit position-relative rounded-3 border border-dashed p-4 p-lg-4 text-center bg-body-secondary bg-opacity-25">
                                        <input type="file"
                                               name="contacts_csv"
                                               id="contacts_csv"
                                               class="wa-csv-file-input position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                               accept=".csv,.txt,text/csv,text/plain"
                                               aria-label="{{ translate('CSV_file') }}">
                                        <div class="position-relative" style="z-index: 0; pointer-events: none;">
                                            <span class="material-icons text-primary mb-2 d-block" style="font-size: 3.5rem; line-height: 1;">cloud_upload</span>
                                            <h5 class="fw-normal mb-2 fz-16">
                                                <span class="text-primary fw-semibold">{{ translate('Drop_CSV_here') }}</span>
                                            </h5>
                                            <p class="text-muted fz-12 mb-0">{{ translate('or_browse_file') }}</p>
                                        </div>
                                    </div>
                                    <div id="wa_csv_file_strip" class="card-body border-top d-none py-3">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                            <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
                                                <span class="material-icons text-primary flex-shrink-0">description</span>
                                                <div class="min-w-0">
                                                    <div class="wa-csv-file-name text-truncate fw-semibold"></div>
                                                    <div class="wa-csv-file-size text-muted fz-12"></div>
                                                </div>
                                            </div>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary wa-csv-remove flex-shrink-0">
                                                {{ translate('Remove_file') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted d-block">
                                    {{ translate('CSV_format_hint') }}
                                </small>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3 mb-0 d-none" id="audience_summary" role="status"></div>
                    </div>
                </div>

                <div class="card mb-3 d-none" id="recipient_preview_card">
                    <div class="card-body p-0">
                        <div class="accordion" id="waRecipientPreviewAccordion">
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header" id="waRecipientPreviewHeading">
                                    <button class="accordion-button collapsed shadow-none rounded-0" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#waRecipientPreviewCollapse"
                                            aria-expanded="false" aria-controls="waRecipientPreviewCollapse">
                                        {{ translate('preview') }} — {{ translate('Recipients') }}
                                    </button>
                                </h2>
                                <div id="waRecipientPreviewCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="waRecipientPreviewHeading" data-bs-parent="#waRecipientPreviewAccordion">
                                    <div class="accordion-body border-top">
                                        <p class="text-muted small mb-2" id="recipient_preview_subtitle"></p>
                                        <div class="d-none text-muted small mb-2" id="recipient_preview_loading">
                                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            <span class="align-middle">{{ translate('Loading...') }}</span>
                                        </div>
                                        <div class="table-responsive d-none" id="recipient_preview_table_wrap">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="table-light">
                                                <tr>
                                                    <th>{{ translate('name') }}</th>
                                                    <th>{{ translate('phone') }}</th>
                                                    <th class="recipient-preview-cat-col d-none">{{ translate('category') }}</th>
                                                    <th class="recipient-preview-action-col text-end" style="width: 1%">{{ translate('action') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody id="recipient_preview_tbody"></tbody>
                                            </table>
                                        </div>
                                        <p class="text-muted small mb-0 d-none" id="recipient_preview_empty"></p>
                                        <div class="mt-3 pt-3 border-top d-none" id="recipient_preview_add_wrap">
                                            <div class="fw-medium small mb-2">{{ translate('Recipient_preview_add_manual') }}</div>
                                            <div class="row g-2 align-items-end">
                                                <div class="col-md-4">
                                                    <label class="form-label small mb-0" for="wa_preview_add_name">{{ translate('name') }}</label>
                                                    <input type="text" class="form-control form-control-sm" id="wa_preview_add_name"
                                                           maxlength="255" autocomplete="name">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small mb-0" for="wa_preview_add_phone">{{ translate('phone') }}</label>
                                                    <input type="text" class="form-control form-control-sm" id="wa_preview_add_phone"
                                                           maxlength="32" inputmode="tel" autocomplete="tel">
                                                </div>
                                                <div class="col-md-4">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" id="wa_preview_add_btn">
                                                        {{ translate('add_new') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h4 class="mb-3">{{ translate('Schedule') }}</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="send_option" class="form-label">{{ translate('When_to_send') }} *</label>
                                <select name="send_option" id="send_option" class="form-select js-select w-100" required>
                                    <option value="now" {{ old('send_option', 'now') === 'now' ? 'selected' : '' }}>{{ translate('Send_Now') }}</option>
                                    <option value="schedule" {{ old('send_option') === 'schedule' ? 'selected' : '' }}>{{ translate('Schedule_for_later') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-none" id="schedule_wrap">
                                <label for="scheduled_at" class="form-label">{{ translate('Scheduled_at') }} *</label>
                                <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control"
                                       value="{{ old('scheduled_at') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn--primary">{{ translate('submit') }}</button>
                    <a href="{{ route('admin.whatsapp.marketing.campaigns.index') }}" class="btn btn--secondary">{{ translate('cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script>
        'use strict';

        window.waMarketingAudienceCounts = @json($audienceCounts);
        window.waMarketingCategoryCounts = @json($categoryRecipientCounts);
        window.waPreviewRecipientsUrl = @json(route('admin.whatsapp.marketing.bulk.preview-recipients'));
        window.waPreviewCsvUrl = @json(route('admin.whatsapp.marketing.bulk.preview-csv'));

        const marketingT = {
            select: @json(translate('Select')),
            custProvName: @json(translate('Customer_Provider_name')),
            catName: @json(translate('Category_name')),
            staticText: @json(translate('Static_text')),
            paramLabel: @json(translate('Parameter')),
            approxRecipients: @json(translate('Approx_recipients_after_dedup')),
            csvNoHeader: @json(translate('CSV_no_header_hint')),
            previewSelectCategory: @json(translate('Select_category_to_see_count')),
            recipientPreviewTotal: @json(translate('Recipient_preview_total')),
            recipientPreviewTableHint: @json(translate('Recipient_preview_table_hint')),
            recipientPreviewMoreExist: @json(translate('Recipient_preview_more_exist')),
            recipientPreviewUploadCsv: @json(translate('Recipient_preview_upload_csv')),
            previewFailed: @json(translate('Something_went_wrong')),
            previewNoRecipients: @json(translate('no_data_found')),
            remove: @json(translate('remove')),
        };

        var waPreviousAudienceType = null;
        var waRecipientAdjustments = {exclude: [], extra: []};

        function waResetRecipientAdjustments() {
            waRecipientAdjustments = {exclude: [], extra: []};
            waSyncRecipientAdjustmentsHidden();
        }

        function waSyncRecipientAdjustmentsHidden() {
            $('#recipient_adjustments').val(JSON.stringify({
                exclude: waRecipientAdjustments.exclude,
                extra: waRecipientAdjustments.extra
            }));
        }

        function scheduleRecipientPreviewReload() {
            waSyncRecipientAdjustmentsHidden();
            var t = $('#audience_type').val();
            if (t === 'csv_import') {
                scheduleCsvRecipientPreview();
            } else {
                scheduleDbRecipientPreview();
            }
        }

        function formatFileSize(bytes) {
            if (!bytes && bytes !== 0) {
                return '';
            }
            if (bytes < 1024) {
                return bytes + ' B';
            }
            if (bytes < 1048576) {
                return (bytes / 1024).toFixed(1) + ' KB';
            }
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        function initWaCsvDropzone() {
            var $hit = $('.wa-csv-dropzone-hit');
            var $input = $('#contacts_csv');
            var $strip = $('#wa_csv_file_strip');
            var $name = $strip.find('.wa-csv-file-name');
            var $size = $strip.find('.wa-csv-file-size');

            function syncStrip() {
                var f = $input[0].files && $input[0].files[0];
                if (!f) {
                    $strip.addClass('d-none');
                    $name.text('');
                    $size.text('');
                    return;
                }
                $name.text(f.name);
                $size.text(formatFileSize(f.size));
                $strip.removeClass('d-none');
            }

            $input.on('change', syncStrip);

            $('.wa-csv-remove').on('click', function (e) {
                e.preventDefault();
                $input.val('');
                syncStrip();
                waResetRecipientAdjustments();
                waAbortPreviewRequest();
                $('#recipient_preview_card').addClass('d-none');
                $('#recipient_preview_add_wrap').addClass('d-none');
            });

            $hit.on('dragover', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $hit.addClass('is-dragover');
            });
            $hit.on('dragleave drop', function (e) {
                if (e.type === 'dragleave') {
                    var related = e.relatedTarget;
                    if (related && $hit[0].contains(related)) {
                        return;
                    }
                }
                $hit.removeClass('is-dragover');
            });
            $hit.on('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $hit.removeClass('is-dragover');
                var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
                if (!files || !files.length) {
                    return;
                }
                var file = files[0];
                var ok = /\.(csv|txt)$/i.test(file.name) || file.type === 'text/csv' || file.type === 'text/plain';
                if (!ok) {
                    return;
                }
                try {
                    var dt = new DataTransfer();
                    dt.items.add(file);
                    $input[0].files = dt.files;
                    $input.trigger('change');
                } catch (err) { /* ignore */ }
            });

            syncStrip();
        }

        function getSelectedTemplateOption() {
            var el = document.getElementById('template_id');
            if (!el || el.selectedIndex < 0) {
                return null;
            }
            var opt = el.options[el.selectedIndex];
            return opt && opt.value ? opt : null;
        }

        function tplParamsCount() {
            var opt = getSelectedTemplateOption();
            if (!opt || !opt.value) {
                return 0;
            }
            var n = parseInt($(opt).attr('data-params') || '0', 10);
            return isNaN(n) ? 0 : n;
        }

        function clearVariableRows() {
            var $wrap = $('#variable_rows');
            $wrap.find('select.js-select').each(function () {
                try {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                } catch (e) { /* ignore */ }
            });
            $wrap.empty();
        }

        function renderVariableRows(n) {
            clearVariableRows();
            var $section = $('#variable_mapping_section');
            var $wrap = $('#variable_rows');
            if (n < 1) {
                $section.addClass('d-none');
                return;
            }
            $section.removeClass('d-none');
            for (var i = 1; i <= n; i++) {
                var $row = $('<div class="row g-2 align-items-end mb-2 var-mapping-row"></div>');
                $row.append(
                    '<div class="col-md-4"><label class="form-label mb-0">' + marketingT.paramLabel + ' #' + i + '</label></div>' +
                    '<div class="col-md-4">' +
                    '<select name="variable_map[' + i + ']" class="form-select js-select w-100 var-source" data-i="' + i + '">' +
                    '<option value="">' + marketingT.select + '</option>' +
                    '<option value="customer_name">' + marketingT.custProvName + '</option>' +
                    '<option value="category_name">' + marketingT.catName + '</option>' +
                    '<option value="static_text">' + marketingT.staticText + '</option>' +
                    '</select></div>' +
                    '<div class="col-md-4">' +
                    '<input type="text" name="variable_static[' + i + ']" class="form-control var-static d-none" placeholder="' + marketingT.staticText + '">' +
                    '</div>'
                );
                $wrap.append($row);
            }
            $wrap.find('select.js-select').select2({width: '100%'});
            $wrap.find('.var-source').on('change', function () {
                var idx = $(this).data('i');
                var $st = $wrap.find('input[name="variable_static[' + idx + ']"]');
                $st.toggleClass('d-none', $(this).val() !== 'static_text');
            });
        }

        function updateTemplatePreview() {
            var opt = getSelectedTemplateOption();
            var $box = $('#template_preview_wrap');
            var $pre = $('#template_preview_text');
            if (!opt || !opt.value) {
                $box.addClass('d-none');
                renderVariableRows(0);
                return;
            }
            $box.removeClass('d-none');
            $pre.text($(opt).attr('data-preview') || '');
            renderVariableRows(tplParamsCount());
        }

        function updateAudienceUi() {
            var t = $('#audience_type').val();
            var $cat = $('#category_wrap');
            var $csv = $('#csv_wrap');
            if (t === 'providers_by_category') {
                $cat.removeClass('d-none');
            } else {
                $cat.addClass('d-none');
            }
            if (t === 'csv_import') {
                $csv.removeClass('d-none');
            } else {
                $csv.addClass('d-none');
            }
            $('#category_id').prop('required', t === 'providers_by_category');
            $('#contacts_csv').prop('required', t === 'csv_import');

            updateAudienceSummary();
            scheduleRecipientPreviewForAudience();
        }

        function updateAudienceSummary() {
            var t = $('#audience_type').val();
            var $sum = $('#audience_summary');
            if (!t) {
                $sum.addClass('d-none').empty();
                return;
            }
            var ac = window.waMarketingAudienceCounts || {};
            var cc = window.waMarketingCategoryCounts || {};
            var msg = '';
            if (t === 'all_customers') {
                msg = marketingT.approxRecipients + ': <strong>' + (ac.all_customers || 0) + '</strong> (' + @json(translate('active_customers')) + ').';
            } else if (t === 'all_providers') {
                msg = marketingT.approxRecipients + ': <strong>' + (ac.all_providers || 0) + '</strong> (' + @json(translate('approved_active_providers')) + ').';
            } else if (t === 'providers_by_category') {
                var cid = $('#category_id').val();
                var n = cid ? (cc[cid] || 0) : null;
                if (n === null) {
                    msg = @json(translate('Select_category_to_see_count'));
                } else {
                    msg = marketingT.approxRecipients + ': <strong>' + n + '</strong>.';
                }
            } else if (t === 'csv_import') {
                msg = marketingT.csvNoHeader;
            }
            if (msg) {
                $sum.html(msg).removeClass('d-none');
            } else {
                $sum.addClass('d-none').empty();
            }
        }

        var waPreviewXhr = null;
        var waDbPreviewTimer = null;
        var waCsvPreviewTimer = null;

        function waAbortPreviewRequest() {
            if (waPreviewXhr) {
                waPreviewXhr.abort();
                waPreviewXhr = null;
            }
        }

        function waRenderPreviewRows(rows, showCategoryCol) {
            var $tbody = $('#recipient_preview_tbody');
            $tbody.empty();
            $('.recipient-preview-cat-col').toggleClass('d-none', !showCategoryCol);
            (rows || []).forEach(function (r) {
                var $tr = $('<tr>');
                $tr.attr('data-phone', r.phone_normalized || '');
                $tr.attr('data-is-manual', r.is_manual ? '1' : '0');
                if (r.client_id) {
                    $tr.attr('data-client-id', r.client_id);
                }
                $tr.append($('<td>').text(r.name || '—'));
                $tr.append($('<td>').text(r.phone_normalized || ''));
                if (showCategoryCol) {
                    $tr.append($('<td>').text(r.category_name || '—'));
                }
                var $rm = $('<button type="button" class="btn btn-sm btn-outline-danger wa-preview-remove"></button>').text(marketingT.remove);
                $tr.append($('<td class="text-end text-nowrap">').append($rm));
                $tbody.append($tr);
            });
        }

        function waFinishRecipientPreview(d, audienceType) {
            $('#recipient_preview_loading').addClass('d-none');
            var total = typeof d.total_matching === 'number' ? d.total_matching : 0;
            var rows = d.rows || [];
            var hasMore = !!d.has_more;
            var showCat = audienceType === 'providers_by_category';
            var subParts = [
                marketingT.recipientPreviewTotal + ': ' + total + '.',
                marketingT.recipientPreviewTableHint + '.'
            ];
            if (hasMore) {
                subParts.push(marketingT.recipientPreviewMoreExist + '.');
            }
            $('#recipient_preview_subtitle').text(subParts.join(' '));
            $('#recipient_preview_add_wrap').removeClass('d-none');

            if (!rows.length) {
                $('#recipient_preview_empty').removeClass('d-none').text(marketingT.previewNoRecipients);
                $('#recipient_preview_table_wrap').addClass('d-none');
                return;
            }
            $('#recipient_preview_empty').addClass('d-none');
            $('#recipient_preview_table_wrap').removeClass('d-none');
            waRenderPreviewRows(rows, showCat);
        }

        function loadDbRecipientPreview() {
            var t = $('#audience_type').val();
            if (!t) {
                waAbortPreviewRequest();
                $('#recipient_preview_card').addClass('d-none');
                $('#recipient_preview_add_wrap').addClass('d-none');
                return;
            }
            if (t === 'csv_import') {
                return;
            }
            waAbortPreviewRequest();
            $('#recipient_preview_add_wrap').addClass('d-none');

            var $card = $('#recipient_preview_card');
            $card.removeClass('d-none');
            $('#recipient_preview_loading').removeClass('d-none');
            $('#recipient_preview_table_wrap').addClass('d-none');
            $('#recipient_preview_empty').addClass('d-none');
            $('#recipient_preview_tbody').empty();
            $('#recipient_preview_subtitle').text('');

            if (t === 'providers_by_category' && !$('#category_id').val()) {
                $('#recipient_preview_loading').addClass('d-none');
                $('#recipient_preview_subtitle').text(marketingT.previewSelectCategory);
                $('#recipient_preview_empty').removeClass('d-none').text(marketingT.previewSelectCategory);
                return;
            }

            var previewData = {
                _token: $('meta[name="csrf-token"]').attr('content'),
                audience_type: t,
                recipient_adjustments: JSON.stringify({
                    exclude: waRecipientAdjustments.exclude,
                    extra: waRecipientAdjustments.extra
                })
            };
            var cid = $('#category_id').val();
            if (cid) {
                previewData.category_id = cid;
            }

            waPreviewXhr = $.ajax({
                url: window.waPreviewRecipientsUrl,
                method: 'POST',
                data: previewData,
                dataType: 'json'
            }).done(function (d) {
                waPreviewXhr = null;
                if (d.kind === 'needs_category') {
                    $('#recipient_preview_loading').addClass('d-none');
                    $('#recipient_preview_subtitle').text(marketingT.previewSelectCategory);
                    $('#recipient_preview_empty').removeClass('d-none').text(marketingT.previewSelectCategory);
                    return;
                }
                waFinishRecipientPreview(d, t);
            }).fail(function (xhr) {
                waPreviewXhr = null;
                if (xhr.statusText === 'abort') {
                    return;
                }
                $('#recipient_preview_loading').addClass('d-none');
                $('#recipient_preview_subtitle').text('');
                $('#recipient_preview_empty').removeClass('d-none').text(marketingT.previewFailed);
            });
        }

        function loadCsvRecipientPreview() {
            if ($('#audience_type').val() !== 'csv_import') {
                return;
            }
            var f = $('#contacts_csv')[0].files && $('#contacts_csv')[0].files[0];
            var $card = $('#recipient_preview_card');
            if (!f) {
                waAbortPreviewRequest();
                $card.addClass('d-none');
                $('#recipient_preview_add_wrap').addClass('d-none');
                return;
            }
            $card.removeClass('d-none');
            waAbortPreviewRequest();
            $('#recipient_preview_add_wrap').addClass('d-none');
            $('#recipient_preview_loading').removeClass('d-none');
            $('#recipient_preview_table_wrap').addClass('d-none');
            $('#recipient_preview_empty').addClass('d-none');
            $('#recipient_preview_tbody').empty();
            $('#recipient_preview_subtitle').text('');

            var fd = new FormData();
            fd.append('contacts_csv', f);
            fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
            fd.append('preview_adjustments', JSON.stringify({
                exclude: waRecipientAdjustments.exclude,
                extra: waRecipientAdjustments.extra
            }));

            waPreviewXhr = $.ajax({
                url: window.waPreviewCsvUrl,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false
            }).done(function (d) {
                waPreviewXhr = null;
                waFinishRecipientPreview(d, 'csv_import');
            }).fail(function (xhr) {
                waPreviewXhr = null;
                if (xhr.statusText === 'abort') {
                    return;
                }
                $('#recipient_preview_loading').addClass('d-none');
                var msg = marketingT.previewFailed;
                if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.contacts_csv) {
                    msg = xhr.responseJSON.errors.contacts_csv[0];
                }
                $('#recipient_preview_empty').removeClass('d-none').text(msg);
            });
        }

        function scheduleDbRecipientPreview() {
            clearTimeout(waDbPreviewTimer);
            waDbPreviewTimer = setTimeout(loadDbRecipientPreview, 320);
        }

        function scheduleCsvRecipientPreview() {
            clearTimeout(waCsvPreviewTimer);
            waCsvPreviewTimer = setTimeout(loadCsvRecipientPreview, 200);
        }

        function scheduleRecipientPreviewForAudience() {
            var t = $('#audience_type').val();
            clearTimeout(waDbPreviewTimer);
            clearTimeout(waCsvPreviewTimer);
            if (!t) {
                waAbortPreviewRequest();
                $('#recipient_preview_card').addClass('d-none');
                $('#recipient_preview_add_wrap').addClass('d-none');
                return;
            }
            if (t === 'csv_import') {
                var hasFile = $('#contacts_csv')[0].files && $('#contacts_csv')[0].files[0];
                if (hasFile) {
                    scheduleCsvRecipientPreview();
                } else {
                    waAbortPreviewRequest();
                    $('#recipient_preview_card').addClass('d-none');
                    $('#recipient_preview_add_wrap').addClass('d-none');
                }
            } else {
                scheduleDbRecipientPreview();
            }
        }

        function updateScheduleUi() {
            var v = $('#send_option').val();
            var $w = $('#schedule_wrap');
            if (v === 'schedule') {
                $w.removeClass('d-none');
                $('#scheduled_at').prop('required', true);
            } else {
                $w.addClass('d-none');
                $('#scheduled_at').prop('required', false);
            }
        }

        $(document).ready(function () {
            waPreviousAudienceType = $('#audience_type').val();
            waSyncRecipientAdjustmentsHidden();

            $('#template_id').on('change select2:select select2:clear', updateTemplatePreview);
            $('#audience_type').on('change select2:select', function () {
                var t = $('#audience_type').val();
                if (waPreviousAudienceType !== t) {
                    waResetRecipientAdjustments();
                    waPreviousAudienceType = t;
                }
                updateAudienceUi();
            });
            $('#category_id').on('change select2:select select2:clear', function () {
                updateAudienceSummary();
                if ($('#audience_type').val() !== 'csv_import') {
                    scheduleDbRecipientPreview();
                }
            });
            $('#contacts_csv').on('change', function () {
                waResetRecipientAdjustments();
                if ($('#audience_type').val() === 'csv_import') {
                    scheduleCsvRecipientPreview();
                }
            });
            $('#send_option').on('change', updateScheduleUi);

            $('#recipient_preview_tbody').on('click', '.wa-preview-remove', function () {
                var $tr = $(this).closest('tr');
                var phone = $tr.attr('data-phone') || '';
                var isManual = $tr.attr('data-is-manual') === '1';
                var clientId = $tr.attr('data-client-id') || '';
                if (isManual && clientId) {
                    waRecipientAdjustments.extra = waRecipientAdjustments.extra.filter(function (e) {
                        return e.client_id !== clientId;
                    });
                } else if (phone && waRecipientAdjustments.exclude.indexOf(phone) === -1) {
                    waRecipientAdjustments.exclude.push(phone);
                }
                scheduleRecipientPreviewReload();
            });

            $('#wa_preview_add_btn').on('click', function () {
                var name = ($('#wa_preview_add_name').val() || '').trim();
                var phone = ($('#wa_preview_add_phone').val() || '').trim();
                if (!phone) {
                    return;
                }
                waRecipientAdjustments.extra.push({
                    client_id: 'wa_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10),
                    name: name,
                    phone: phone
                });
                $('#wa_preview_add_name').val('');
                $('#wa_preview_add_phone').val('');
                scheduleRecipientPreviewReload();
            });

            $('#bulk_marketing_form').on('submit', function () {
                waSyncRecipientAdjustmentsHidden();
            });

            updateAudienceUi();
            updateScheduleUi();
            updateTemplatePreview();
            initWaCsvDropzone();
            scheduleRecipientPreviewForAudience();
        });
    </script>
@endpush
