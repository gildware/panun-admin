@extends('adminmodule::layouts.master')

@section('title', translate('Create_Template'))

@push('css_or_js')
    <style>
        .wa-tpl-form .form-label { font-weight: 500; margin-bottom: 0.35rem; }
        .wa-tpl-form-section {
            margin-bottom: 1.75rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--bs-border-color, #dee2e6);
        }
        .wa-tpl-form-section:last-of-type { border-bottom: 0; margin-bottom: 0; padding-bottom: 0; }
        .wa-tpl-button-row {
            background: var(--bs-tertiary-bg, #f8f9fa);
            margin-bottom: 0.75rem !important;
        }
        .wa-tpl-buttons-scroll {
            max-height: 22rem;
            overflow-y: auto;
            padding-right: 0.25rem;
        }
        .wa-tpl-live-preview .wa-tpl-phone-preview { max-width: 100%; }
        .wa-tpl-live-preview .wa-tpl-phone-notch { background: rgba(0, 0, 0, 0.2); }
        .wa-tpl-live-preview .wa-tpl-phone-frame {
            background: linear-gradient(160deg, #075e54 0%, #128c7e 45%, #25d366 100%);
            border: 1px solid rgba(0,0,0,.08);
        }
        .wa-tpl-live-preview .wa-tpl-phone-body {
            background: #e5ddd5;
            min-height: 180px;
            background-image:
                radial-gradient(circle at 20% 30%, rgba(255,255,255,.12) 0, transparent 45%),
                radial-gradient(circle at 80% 70%, rgba(0,0,0,.04) 0, transparent 40%);
        }
        .wa-tpl-live-preview .wa-tpl-btn-fake { pointer-events: none; cursor: default; }
        .wa-tpl-live-inner { min-height: 120px; }
        @media (max-width: 991.98px) {
            .wa-tpl-live-preview.sticky-lg-top { position: static !important; top: auto !important; }
        }
        .wa-tpl-info-details summary { cursor: pointer; user-select: none; }
        .wa-tpl-header-upload {
            max-width: 420px;
            width: 100%;
            aspect-ratio: 16 / 9;
            min-height: 140px;
        }
        .wa-tpl-header-upload .upload-box { min-height: 140px; }
        .wa-tpl-header-upload .wa-tpl-header-video-preview {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
            display: none;
        }
        .wa-tpl-header-upload .wa-tpl-header-video-preview.is-visible { display: block; }
        .wa-tpl-header-upload .uploaded-remove-icon {
            font-family: inherit;
            line-height: 1;
            padding: 0;
            pointer-events: auto;
        }
        .wa-tpl-header-upload.dragging .upload-box {
            border-color: var(--bs-primary, #0d6efd);
            background: var(--bs-primary-bg-subtle, rgba(13, 110, 253, 0.06));
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                <h2 class="page-title">{{ translate('Create_Template') }}</h2>
                <a href="{{ route('admin.whatsapp.marketing.templates.index') }}" class="btn btn--secondary">
                    {{ translate('back') }}
                </a>
            </div>

            <details class="card border-info mb-4 wa-tpl-info-details">
                <summary class="card-header py-3 fw-medium mb-0 list-unstyled">
                    {{ translate('Template_help_whatsapp_manager') }} — {{ translate('Create_template_meta_hint') }}
                </summary>
                <div class="card-body border-top">
                    <p class="small text-muted mb-3">{{ translate('Template_media_meta_hint') }}</p>
                    <h5 class="h6 text-dark mb-2">{{ translate('Template_media_how_title') }}</h5>
                    <ol class="small mb-3 ps-3">
                        <li class="mb-1">{{ translate('Template_media_how_step1') }}</li>
                        <li class="mb-1">{{ translate('Template_media_how_step2') }}</li>
                        <li class="mb-1">{{ translate('Template_media_how_step3') }}</li>
                        <li class="mb-1">{{ translate('Template_media_how_step4') }}</li>
                        <li class="mb-1">{{ translate('Template_media_how_step5') }}</li>
                    </ol>
                    <p class="small mb-2">
                        <a href="https://business.facebook.com/" target="_blank" rel="noopener noreferrer"
                           class="fw-medium">{{ translate('Template_media_manager_open') }}</a>
                    </p>
                    <p class="small text-muted mb-0">{{ translate('Template_media_how_api_note') }}</p>
                </div>
            </details>

            <div class="row g-4 align-items-start">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-body wa-tpl-form">
                            <form action="{{ route('admin.whatsapp.marketing.templates.store') }}" method="post" id="wa_tpl_create_form"
                                  enctype="multipart/form-data">
                                @csrf

                                <div class="wa-tpl-form-section">
                                    <h5 class="h6 mb-3">{{ translate('Template_form_section_basic') }}</h5>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <label for="name" class="form-label">{{ translate('Template_name') }} *</label>
                                            <input type="text" name="name" id="name" class="form-control"
                                                   value="{{ old('name') }}"
                                                   placeholder="summer_sale_2025"
                                                   required
                                                   maxlength="512"
                                                   autocomplete="off">
                                            <p class="text-muted small mt-2 mb-1">{{ translate('Template_name_rules') }}</p>
                                            <details class="small text-muted">
                                                <summary class="text-primary">{{ translate('Template_name_rules_expand') }}</summary>
                                                <p class="mt-2 mb-0">{{ translate('Template_name_rules_detail') }}</p>
                                            </details>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label for="language" class="form-label">{{ translate('language') }} *</label>
                                            <select name="language" id="language" class="form-select js-select w-100" required>
                                                @foreach($languages as $code => $label)
                                                    <option value="{{ $code }}" {{ old('language', 'en') === $code ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label for="category" class="form-label">{{ translate('category') }} *</label>
                                            <select name="category" id="category" class="form-select js-select w-100" required>
                                                <option value="MARKETING" {{ old('category', 'MARKETING') === 'MARKETING' ? 'selected' : '' }}>MARKETING</option>
                                                <option value="UTILITY" {{ old('category') === 'UTILITY' ? 'selected' : '' }}>UTILITY</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="wa-tpl-form-section">
                                    <h5 class="h6 mb-3">{{ translate('Template_form_section_header') }}</h5>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="header_format" class="form-label">{{ translate('Template_header_format') }} *</label>
                                            <select name="header_format" id="header_format" class="form-select wa-tpl-header-format wa-tpl-watch w-100" required>
                                                @php $hf = old('header_format', 'NONE'); @endphp
                                                <option value="NONE" {{ $hf === 'NONE' ? 'selected' : '' }}>{{ translate('Template_header_none') }}</option>
                                                <option value="TEXT" {{ $hf === 'TEXT' ? 'selected' : '' }}>{{ translate('Template_header_text') }}</option>
                                                <option value="IMAGE" {{ $hf === 'IMAGE' ? 'selected' : '' }}>{{ translate('Template_header_image') }}</option>
                                                <option value="VIDEO" {{ $hf === 'VIDEO' ? 'selected' : '' }}>{{ translate('Template_header_video') }}</option>
                                            </select>
                                            <p class="text-muted small mt-2 mb-0">{{ translate('Template_api_upload_hint') }}</p>
                                        </div>

                                        <div class="col-12 d-none" id="wa_header_text_wrap">
                                            <label for="header_text" class="form-label">{{ translate('Header_text_optional') }}</label>
                                            <input type="text" name="header_text" id="header_text" class="form-control wa-tpl-watch"
                                                   value="{{ old('header_text') }}" maxlength="60"
                                                   placeholder="{{ translate('Optional') }}">
                                            <small class="text-muted">{{ translate('Header_text_limit_60') }}</small>
                                        </div>

                                        <div class="col-12 d-none" id="wa_header_media_wrap">
                                            <span class="form-label d-block">{{ translate('Template_header_media_pick') }}</span>
                                            <div class="custom-upload-wrapper wa-tpl-header-upload">
                                                <input type="file" name="header_media" id="header_media" class="wa-tpl-header-media-input"
                                                       accept="image/jpeg,image/png,.jpg,.jpeg,.png,video/mp4,.mp4">
                                                <label for="header_media" class="upload-box rounded position-relative d-flex align-items-center justify-content-center text-center overflow-hidden bg-white">
                                                    <div class="upload-content">
                                                        <img src="{{ asset('assets/admin-module/img/ai/image-upload.svg') }}" alt="" class="placeholder-icon mb-2" width="56" height="56">
                                                        <h6 class="fz-10 text-primary mb-0">{{ translate('Click to upload') }}<br>
                                                            <span class="text-dark d-block mt-1">{{ translate('Or drag and drop') }}</span>
                                                        </h6>
                                                    </div>
                                                    <img class="image-preview wa-tpl-header-img-preview" src="" alt="">
                                                    <video class="wa-tpl-header-video-preview" playsinline muted loop></video>
                                                    <div class="upload-overlay">
                                                        <span class="material-symbols-outlined">perm_media</span>
                                                    </div>
                                                    <span class="uploaded-remove-icon wa-tpl-header-media-clear" role="button" tabindex="0" title="{{ translate('remove') }}">&times;</span>
                                                </label>
                                            </div>
                                            <p class="text-muted small mt-2 mb-0">{{ translate('Template_media_specs') }}</p>
                                            <p class="text-muted small mb-0">{{ translate('Template_media_type_invalid') }} · {{ translate('Template_media_upload_max_hint') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="wa-tpl-form-section">
                                    <h5 class="h6 mb-3">{{ translate('Template_form_section_body') }}</h5>
                                    <label for="body_text" class="form-label">{{ translate('Message_body') }} *</label>
                                    <textarea name="body_text" id="body_text" class="form-control wa-tpl-watch" rows="5" required
                                              maxlength="1024">{{ old('body_text') }}</textarea>
                                    <small class="text-muted">{{ translate('Body_variables_hint') }}</small>
                                </div>

                                <div class="wa-tpl-form-section">
                                    <h5 class="h6 mb-3">{{ translate('Template_form_section_footer') }}</h5>
                                    <label for="footer_text" class="form-label">{{ translate('Template_footer_optional') }}</label>
                                    <input type="text" name="footer_text" id="footer_text" class="form-control wa-tpl-watch"
                                           value="{{ old('footer_text') }}" maxlength="60" placeholder="{{ translate('Optional') }}">
                                    <small class="text-muted">{{ translate('Template_footer_hint') }}</small>
                                </div>

                                <div class="wa-tpl-form-section">
                                    <h5 class="h6 mb-2">{{ translate('Template_form_section_buttons') }}</h5>
                                    <p class="text-muted small mb-3">{{ translate('Template_buttons_hint') }}</p>
                                    <p class="text-muted small mb-2">{{ translate('Template_button_url_dynamic_hint') }}</p>
                                    <div class="wa-tpl-buttons-scroll">
                                        @for($i = 0; $i < 10; $i++)
                                            @php
                                                $ok = old('buttons.'.$i.'.kind');
                                                $ot = old('buttons.'.$i.'.text');
                                                $ou = old('buttons.'.$i.'.url');
                                                $op = old('buttons.'.$i.'.phone');
                                            @endphp
                                            <div class="border rounded-3 p-3 wa-tpl-button-row" data-i="{{ $i }}">
                                                <div class="row g-3">
                                                    <div class="col-12 col-lg-3">
                                                        <label class="form-label small mb-0">{{ translate('Template_button_kind') }} #{{ $i + 1 }}</label>
                                                        <select name="buttons[{{ $i }}][kind]" class="form-select form-select-sm wa-tpl-btn-kind wa-tpl-watch w-100">
                                                            <option value="">—</option>
                                                            <option value="QUICK_REPLY" {{ $ok === 'QUICK_REPLY' ? 'selected' : '' }}>{{ translate('Template_button_quick_reply') }}</option>
                                                            <option value="URL" {{ $ok === 'URL' ? 'selected' : '' }}>{{ translate('Template_button_url') }}</option>
                                                            <option value="PHONE_NUMBER" {{ $ok === 'PHONE_NUMBER' ? 'selected' : '' }}>{{ translate('Template_button_phone') }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-lg-3">
                                                        <label class="form-label small mb-0">{{ translate('Template_button_label') }}</label>
                                                        <input type="text" name="buttons[{{ $i }}][text]" maxlength="25"
                                                               class="form-control form-control-sm wa-tpl-btn-text wa-tpl-watch w-100"
                                                               value="{{ $ot }}" placeholder="{{ translate('action') }}">
                                                    </div>
                                                    <div class="col-12 col-lg-6 wa-tpl-url-wrap d-none">
                                                        <label class="form-label small mb-0">URL (https://…)</label>
                                                        <input type="text" name="buttons[{{ $i }}][url]" maxlength="2000"
                                                               class="form-control form-control-sm wa-tpl-btn-url wa-tpl-watch w-100"
                                                               value="{{ $ou }}" placeholder="https://example.com or https://example.com/{{1}}">
                                                    </div>
                                                    <div class="col-12 col-lg-6 wa-tpl-phone-wrap d-none">
                                                        <label class="form-label small mb-0">{{ translate('phone') }} (E.164)</label>
                                                        <input type="text" name="buttons[{{ $i }}][phone]" maxlength="24"
                                                               class="form-control form-control-sm wa-tpl-btn-phone wa-tpl-watch w-100"
                                                               value="{{ $op }}" placeholder="+923001234567">
                                                    </div>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>

                                <div class="mt-4 d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn--primary">{{ translate('submit') }}</button>
                                    <a href="{{ route('admin.whatsapp.marketing.templates.index') }}"
                                       class="btn btn-outline-secondary">{{ translate('cancel') }}</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card wa-tpl-live-preview sticky-lg-top" style="top: 1rem;">
                        <div class="card-body">
                            <h5 class="card-title fz-16 mb-3">{{ translate('Template_preview_live') }}</h5>
                            <div id="wa_tpl_live_mount" class="wa-tpl-live-inner"></div>
                            <p class="text-muted small mt-2 mb-0">{{ translate('Template_preview_disclaimer') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        'use strict';

        var waTplMediaObjectUrl = null;
        var waTplHeaderBoxVideoUrl = null;

        function waTplRevokeMediaUrl() {
            if (waTplMediaObjectUrl) {
                URL.revokeObjectURL(waTplMediaObjectUrl);
                waTplMediaObjectUrl = null;
            }
        }

        function waTplHeaderBoxVideoRevoke() {
            if (waTplHeaderBoxVideoUrl) {
                URL.revokeObjectURL(waTplHeaderBoxVideoUrl);
                waTplHeaderBoxVideoUrl = null;
            }
        }

        function waTplHeaderMediaSyncAccept() {
            var hf = $('#header_format').val();
            var $inp = $('#header_media');
            if (hf === 'IMAGE') {
                $inp.attr('accept', 'image/jpeg,image/png,.jpg,.jpeg,.png');
            } else if (hf === 'VIDEO') {
                $inp.attr('accept', 'video/mp4,.mp4');
            } else {
                $inp.attr('accept', '');
            }
        }

        function waTplHeaderMediaResetBoxOnly() {
            var $wrap = $('.wa-tpl-header-upload');
            $wrap.find('.wa-tpl-header-img-preview').hide().attr('src', '');
            waTplHeaderBoxVideoRevoke();
            $wrap.find('.wa-tpl-header-video-preview').removeClass('is-visible').attr('src', '');
            $wrap.find('.upload-content').show();
            $wrap.find('.upload-box').removeClass('has-image');
        }

        function waTplHeaderMediaFullReset() {
            waTplRevokeMediaUrl();
            $('#header_media').val('');
            waTplHeaderMediaResetBoxOnly();
            waTplRenderLivePreview();
        }

        function waTplHeaderMediaFileAllowed(file) {
            if (!file) {
                return false;
            }
            var hf = $('#header_format').val();
            if (hf === 'IMAGE') {
                return /^image\/(jpeg|png)$/i.test(file.type);
            }
            if (hf === 'VIDEO') {
                return file.type === 'video/mp4';
            }
            return false;
        }

        function waTplHeaderMediaApplyFile(file) {
            var $wrap = $('.wa-tpl-header-upload');
            var $box = $wrap.find('.upload-box');
            var $img = $wrap.find('.wa-tpl-header-img-preview');
            var $vid = $wrap.find('.wa-tpl-header-video-preview');
            var $ph = $wrap.find('.upload-content');

            if (!file || !waTplHeaderMediaFileAllowed(file)) {
                if (file) {
                    $('#header_media').val('');
                }
                waTplHeaderMediaResetBoxOnly();
                waTplRevokeMediaUrl();
                waTplRenderLivePreview();
                return;
            }

            waTplHeaderMediaResetBoxOnly();

            var hf = $('#header_format').val();
            if (hf === 'IMAGE') {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $img.attr('src', e.target.result).show();
                    $ph.hide();
                    $box.addClass('has-image');
                };
                reader.readAsDataURL(file);
            } else if (hf === 'VIDEO') {
                waTplHeaderBoxVideoUrl = URL.createObjectURL(file);
                $vid.attr('src', waTplHeaderBoxVideoUrl).addClass('is-visible');
                $ph.hide();
                $box.addClass('has-image');
            }
        }

        function waTplEllipsizeBody(s) {
            if (!s) {
                return '';
            }
            return s.replace(/\{\{\d+\}\}/g, '…');
        }

        function waTplUpdateHeaderUi() {
            var f = $('#header_format').val();
            $('#wa_header_text_wrap').toggleClass('d-none', f !== 'TEXT');
            $('#wa_header_media_wrap').toggleClass('d-none', f !== 'IMAGE' && f !== 'VIDEO');
            $('#header_text').prop('required', f === 'TEXT');
            $('#header_media').prop('required', f === 'IMAGE' || f === 'VIDEO');
            waTplHeaderMediaSyncAccept();
        }

        function waTplUpdateButtonRowVisibility($row) {
            var kind = $row.find('.wa-tpl-btn-kind').val();
            $row.find('.wa-tpl-url-wrap').toggleClass('d-none', kind !== 'URL');
            $row.find('.wa-tpl-phone-wrap').toggleClass('d-none', kind !== 'PHONE_NUMBER');
        }

        function waTplRenderLivePreview() {
            var hf = $('#header_format').val();
            var body = $('#body_text').val() || '';
            var bodyDisp = waTplEllipsizeBody(body);
            var footer = ($('#footer_text').val() || '').trim();
            var html = '';
            html += '<div class="wa-tpl-phone-preview mx-auto">';
            html += '<div class="wa-tpl-phone-frame rounded-4 overflow-hidden shadow">';
            html += '<div class="wa-tpl-phone-notch d-flex align-items-center justify-content-between px-3 py-2">';
            html += '<span class="small fw-semibold text-white-50">' + @json(translate('preview')) + '</span>';
            html += '<span class="small text-white-50">WhatsApp</span></div>';
            html += '<div class="wa-tpl-phone-body p-3">';
            html += '<div class="wa-tpl-bubble rounded-3 p-3 bg-white shadow-sm border border-light">';
            if (hf === 'TEXT') {
                var header = ($('#header_text').val() || '').trim();
                if (header) {
                    html += '<div class="fw-semibold small mb-2 text-break">' + $('<div>').text(header).html() + '</div>';
                }
            } else if (hf === 'IMAGE' || hf === 'VIDEO') {
                var input = document.getElementById('header_media');
                var file = input && input.files && input.files[0];
                if (file) {
                    waTplRevokeMediaUrl();
                    waTplMediaObjectUrl = URL.createObjectURL(file);
                    if (hf === 'IMAGE') {
                        html += '<div class="rounded-2 mb-2 overflow-hidden bg-light ratio ratio-16x9">';
                        html += '<img src="' + waTplMediaObjectUrl + '" class="w-100 h-100 object-fit-cover" alt=""></div>';
                    } else {
                        html += '<div class="rounded-2 mb-2 overflow-hidden bg-light">';
                        html += '<video src="' + waTplMediaObjectUrl + '" class="w-100" style="max-height:180px" controls muted playsinline></video></div>';
                    }
                } else {
                    html += '<div class="rounded-2 mb-2 p-3 bg-light text-muted small text-center">' + (hf === 'IMAGE' ? 'IMAGE' : 'VIDEO') + '</div>';
                }
            }
            html += '<div class="small text-break" style="white-space:pre-wrap;">' + $('<div>').text(bodyDisp).html() + '</div>';
            if (footer) {
                html += '<div class="text-muted mt-2" style="font-size:0.7rem;">' + $('<div>').text(footer).html() + '</div>';
            }
            var hasBtn = false;
            $('.wa-tpl-button-row').each(function () {
                var $r = $(this);
                var kind = $r.find('.wa-tpl-btn-kind').val();
                var text = ($r.find('.wa-tpl-btn-text').val() || '').trim();
                if (!kind || !text) {
                    return;
                }
                if (!hasBtn) {
                    html += '<div class="d-grid gap-2 mt-3">';
                    hasBtn = true;
                }
                if (kind === 'QUICK_REPLY') {
                    html += '<span class="btn btn-sm btn-outline-secondary text-start rounded-pill wa-tpl-btn-fake">' + $('<div>').text(text).html() + '</span>';
                } else if (kind === 'URL') {
                    var u = ($r.find('.wa-tpl-btn-url').val() || '').trim();
                    html += '<span class="btn btn-sm btn-outline-primary text-truncate rounded-pill wa-tpl-btn-fake">' + $('<div>').text(text).html() + '</span>';
                    if (u) {
                        html += '<span class="text-muted d-block" style="font-size:0.65rem;word-break:break-all;">' + $('<div>').text(u).html() + '</span>';
                    }
                } else if (kind === 'PHONE_NUMBER') {
                    var p = ($r.find('.wa-tpl-btn-phone').val() || '').trim();
                    html += '<span class="btn btn-sm btn-outline-primary rounded-pill wa-tpl-btn-fake">' + $('<div>').text(text).html() + '</span>';
                    if (p) {
                        html += '<span class="text-muted d-block" style="font-size:0.65rem;">' + $('<div>').text(p).html() + '</span>';
                    }
                }
            });
            if (hasBtn) {
                html += '</div>';
            }
            html += '</div></div></div></div>';
            $('#wa_tpl_live_mount').html(html);
        }

        $(document).ready(function () {
            var $form = $('#wa_tpl_create_form');
            if ($.fn.select2) {
                $form.find('.js-select').each(function () {
                    var $el = $(this);
                    try {
                        if ($el.data('select2')) {
                            $el.select2('destroy');
                        }
                    } catch (e) { /* ignore */ }
                });
                $form.find('.js-select').select2({
                    width: '100%',
                    dropdownParent: $form
                });
            }

            waTplUpdateHeaderUi();
            $('.wa-tpl-button-row').each(function () {
                waTplUpdateButtonRowVisibility($(this));
            });
            $('#header_format').on('change', function () {
                waTplHeaderMediaFullReset();
                waTplUpdateHeaderUi();
            });
            $('#header_media').on('change', function () {
                var f = this.files && this.files[0];
                waTplHeaderMediaApplyFile(f);
                waTplRenderLivePreview();
            });

            var $hdrDrop = $('.wa-tpl-header-upload');
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (ev) {
                $hdrDrop.on(ev, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });
            $hdrDrop.on('dragover', function () {
                $(this).addClass('dragging');
            });
            $hdrDrop.on('dragleave drop', function () {
                $(this).removeClass('dragging');
            });
            $hdrDrop.on('drop', function (e) {
                var file = e.originalEvent.dataTransfer.files[0];
                if (!file) {
                    return;
                }
                var input = document.getElementById('header_media');
                try {
                    var dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                } catch (err) {
                    return;
                }
                waTplHeaderMediaApplyFile(file);
                waTplRenderLivePreview();
            });

            $(document).on('click', '.wa-tpl-header-media-clear', function (e) {
                e.preventDefault();
                e.stopPropagation();
                waTplHeaderMediaFullReset();
            });
            $(document).on('keydown', '.wa-tpl-header-media-clear', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    waTplHeaderMediaFullReset();
                }
            });
            $(document).on('change', '.wa-tpl-btn-kind', function () {
                waTplUpdateButtonRowVisibility($(this).closest('.wa-tpl-button-row'));
                waTplRenderLivePreview();
            });
            $(document).on('input', '.wa-tpl-watch', function () {
                if ($(this).attr('id') === 'header_media') {
                    return;
                }
                waTplRenderLivePreview();
            });
            waTplRenderLivePreview();
        });
    </script>
@endpush
