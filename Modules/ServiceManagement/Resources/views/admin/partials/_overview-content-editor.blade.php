@php
    $colorOptions = ['green', 'blue', 'purple', 'orange'];
    $overviewContentJson = json_encode($overviewContent ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $overviewDefaultsJson = json_encode($overviewDefaults ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $imageAccept = '.'.implode(',.', array_column(IMAGEEXTENSION, 'key')).', |image/*';
@endphp

<div class="service-overview-editor" id="serviceOverviewEditor"
     data-save-url="{{ route('admin.service-overview.update', $service->id) }}"
     data-upload-url="{{ route('admin.service-overview.upload-image', $service->id) }}"
     data-initial='@json($overviewContent ?? [])'
     data-defaults='@json($overviewDefaults ?? [])'>

    <style>
        .overview-process-card {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .overview-process-card__media {
            flex: 0 0 auto;
            width: 180px;
        }
        .overview-process-card__fields {
            flex: 1 1 220px;
            min-width: 0;
        }
        .overview-process-card .overview-image-uploader .overview-image-card {
            max-width: 180px;
        }
        .overview-process-card .overview-image-preview-frame {
            width: 180px;
            height: 135px;
        }
        .overview-image-uploader .overview-image-card {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-width: 320px;
        }
        .overview-image-preview-frame {
            position: relative;
            display: block;
            width: 160px;
            height: 120px;
            margin: 0;
            border: 1px dashed #c5ccd6;
            border-radius: 8px;
            background: #f5f7fa;
            overflow: hidden;
            cursor: pointer;
        }
        .overview-image-preview-frame.has-image {
            border-style: solid;
            border-color: #d7dde5;
            background: #fff;
        }
        .overview-image-preview-frame:hover {
            border-color: #0d6efd;
        }
        .overview-image-preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .overview-image-placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            color: #8a94a6;
            font-size: 12px;
            pointer-events: none;
        }
        .overview-image-placeholder .material-icons {
            font-size: 28px;
            opacity: 0.75;
        }
        .overview-image-card {
            position: relative;
        }
        .overview-image-clear-btn {
            position: absolute;
            top: 6px;
            right: 6px;
            z-index: 3;
            width: 28px;
            height: 28px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.75);
            color: #fff;
            cursor: pointer;
            line-height: 1;
        }
        .overview-image-clear-btn .material-icons {
            font-size: 16px;
        }
        .overview-image-clear-btn:hover {
            background: #dc3545;
        }
        .overview-image-uploader .overview-image-upload-label {
            cursor: pointer;
        }
        .overview-image-uploader.is-uploading .overview-image-upload-label,
        .overview-image-uploader.is-uploading .overview-image-clear-btn {
            pointer-events: none;
            opacity: 0.65;
        }
        .overview-image-progress-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            background: rgba(15, 23, 42, 0.55);
            color: #fff;
        }
        .overview-image-progress-track {
            width: 100%;
            height: 6px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.28);
            overflow: hidden;
        }
        .overview-image-progress-bar {
            height: 100%;
            width: 0;
            border-radius: inherit;
            background: #22c55e;
            transition: width 0.12s linear;
        }
        .overview-image-progress-pct {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .overview-image-upload-status {
            font-size: 11px;
            color: #64748b;
        }
        .overview-image-upload-status.is-error {
            color: #dc3545;
        }
        .overview-image-upload-status.is-success {
            color: #198754;
        }
        .overview-sticky-header {
            position: sticky;
            top: 0;
            z-index: 30;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin: -4px -4px 16px;
            padding: 12px 4px;
            background: #fff;
            border-bottom: 1px solid #eef0f3;
        }
        .overview-save-bar {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1040;
            display: none;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.96);
            border-top: 1px solid #e5e7eb;
            box-shadow: 0 -6px 20px rgba(15, 23, 42, 0.08);
            backdrop-filter: blur(8px);
        }
        .overview-save-bar.is-visible {
            display: flex;
        }
        body.overview-tab-active {
            padding-bottom: 72px;
        }
        @media (max-width: 575.98px) {
            .overview-process-card__media,
            .overview-process-card .overview-image-preview-frame,
            .overview-process-card .overview-image-uploader .overview-image-card {
                width: 100%;
                max-width: 100%;
            }
            .overview-save-bar {
                padding: 10px 16px;
            }
        }
    </style>

    <div class="overview-sticky-header">
        <div>
            <h6 class="mb-1">{{ translate('service_overview_sections') }}</h6>
            <p class="text-muted fs-12 mb-0">{{ translate('add_custom_sections_for_this_service') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('admin.service-overview.defaults') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                {{ translate('edit_global_defaults') }}
            </a>
            <button type="button" class="btn btn-sm btn--primary overview-content-save-btn"
                    data-label-idle="{{ translate('save_overview_content') }}"
                    data-label-loading="{{ translate('Loading') }}...">
                <span class="overview-save-label">{{ translate('save_overview_content') }}</span>
                <span class="spinner-border spinner-border-sm text-light d-none ms-1" role="status" aria-hidden="true"></span>
            </button>
        </div>
    </div>

    <div class="form-floating mb-3">
        <textarea class="form-control" id="overview-intro" rows="3" style="min-height: 5rem;"
                  placeholder="{{ translate('overview_intro') }}">{{ $overviewContent['intro'] ?? '' }}</textarea>
        <label for="overview-intro">{{ translate('overview_intro') }}</label>
    </div>

    @include('servicemanagement::admin.partials._overview-section-block', [
        'sectionKey' => 'service_process',
        'sectionTitle' => translate('service_process'),
        'itemType' => 'process',
        'items' => $overviewContent['service_process']['items'] ?? [],
        'sectionLabel' => $overviewContent['service_process']['title'] ?? translate('service_process'),
        'overviewIconOptions' => $overviewIconOptions ?? [],
    ])

    @include('servicemanagement::admin.partials._overview-section-block', [
        'sectionKey' => 'perfect_for',
        'sectionTitle' => translate('perfect_for'),
        'itemType' => 'chip',
        'items' => $overviewContent['perfect_for']['items'] ?? [],
        'sectionLabel' => $overviewContent['perfect_for']['title'] ?? translate('perfect_for'),
        'overviewIconOptions' => $overviewIconOptions ?? [],
    ])

    @include('servicemanagement::admin.partials._overview-section-block', [
        'sectionKey' => 'whats_included',
        'sectionTitle' => translate('whats_included'),
        'itemType' => 'icon_title',
        'items' => $overviewContent['whats_included']['items'] ?? [],
        'sectionLabel' => $overviewContent['whats_included']['title'] ?? translate('whats_included'),
        'overviewIconOptions' => $overviewIconOptions ?? [],
    ])

    @include('servicemanagement::admin.partials._overview-section-block', [
        'sectionKey' => 'whats_not_included',
        'sectionTitle' => translate('whats_not_included'),
        'itemType' => 'icon_title',
        'items' => $overviewContent['whats_not_included']['items'] ?? [],
        'sectionLabel' => $overviewContent['whats_not_included']['title'] ?? translate('whats_not_included'),
        'overviewIconOptions' => $overviewIconOptions ?? [],
    ])

    @include('servicemanagement::admin.partials._overview-section-block', [
        'sectionKey' => 'good_to_know',
        'sectionTitle' => translate('good_to_know'),
        'itemType' => 'icon_title',
        'items' => $overviewContent['good_to_know']['items'] ?? [],
        'sectionLabel' => $overviewContent['good_to_know']['title'] ?? translate('good_to_know'),
        'overviewIconOptions' => $overviewIconOptions ?? [],
    ])

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h6 class="mb-1">{{ translate('terms_and_conditions') }}</h6>
                    <p class="text-muted fs-12 mb-0">{{ translate('service_terms_and_conditions_hint') }}</p>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="override-terms-and-conditions"
                           {{ !empty($overviewContent['override_terms_and_conditions']) ? 'checked' : '' }}>
                    <label class="form-check-label fs-12" for="override-terms-and-conditions">{{ translate('override_global_defaults') }}</label>
                </div>
            </div>
            <div id="terms-and-conditions-section" class="{{ empty($overviewContent['override_terms_and_conditions']) ? 'opacity-50 pe-none' : '' }}">
                <div class="d-flex justify-content-end mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary"
                            data-overview-add
                            data-list-id="terms-and-conditions-list"
                            data-item-type="icon_title">
                        + {{ translate('add_item') }}
                    </button>
                </div>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="terms-and-conditions-title"
                           value="{{ $overviewContent['terms_and_conditions']['title'] ?? ($overviewDefaults['terms_and_conditions']['title'] ?? translate('terms_and_conditions')) }}">
                    <label for="terms-and-conditions-title">{{ translate('section_title') }}</label>
                </div>
                @include('servicemanagement::admin.partials._overview-items-list', [
                    'listId' => 'terms-and-conditions-list',
                    'itemType' => 'icon_title',
                    'items' => $overviewContent['terms_and_conditions']['items'] ?? ($overviewDefaults['terms_and_conditions']['items'] ?? []),
                    'overviewIconOptions' => $overviewIconOptions ?? [],
                ])
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h6 class="mb-0">{{ translate('hero_top_icons') }}</h6>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="override-top-icons"
                           {{ !empty($overviewContent['override_top_icons']) ? 'checked' : '' }}>
                    <label class="form-check-label fs-12" for="override-top-icons">{{ translate('override_global_defaults') }}</label>
                </div>
            </div>
            <div id="top-icons-section" class="{{ empty($overviewContent['override_top_icons']) ? 'opacity-50 pe-none' : '' }}">
                @include('servicemanagement::admin.partials._overview-items-list', [
                    'listId' => 'top-icons-list',
                    'itemType' => 'top_icon',
                    'items' => $overviewContent['top_icons'] ?? ($overviewDefaults['top_icons'] ?? []),
                    'overviewIconOptions' => $overviewIconOptions ?? [],
                ])
            </div>
            <p class="text-muted fs-12 mb-0 mt-2">{{ translate('hero_top_icons_hint') }}</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h6 class="mb-0">{{ translate('why_choose_panun_kaergar') }}</h6>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="override-why-choose"
                           {{ !empty($overviewContent['override_why_choose']) ? 'checked' : '' }}>
                    <label class="form-check-label fs-12" for="override-why-choose">{{ translate('override_global_defaults') }}</label>
                </div>
            </div>
            <div id="why-choose-section" class="{{ empty($overviewContent['override_why_choose']) ? 'opacity-50 pe-none' : '' }}">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="why-choose-title"
                           value="{{ $overviewContent['why_choose']['title'] ?? ($overviewDefaults['why_choose']['title'] ?? '') }}">
                    <label for="why-choose-title">{{ translate('section_title') }}</label>
                </div>
                @include('servicemanagement::admin.partials._overview-items-list', [
                    'listId' => 'why-choose-list',
                    'itemType' => 'why_choose',
                    'items' => $overviewContent['why_choose']['items'] ?? ($overviewDefaults['why_choose']['items'] ?? []),
                    'overviewIconOptions' => $overviewIconOptions ?? [],
                ])
            </div>
        </div>
    </div>

    <div class="overview-save-bar" id="overviewStickySaveBar" aria-hidden="true">
        <button type="button" class="btn btn--primary overview-content-save-btn"
                data-label-idle="{{ translate('save_overview_content') }}"
                data-label-loading="{{ translate('Loading') }}...">
            <span class="overview-save-label">{{ translate('save_overview_content') }}</span>
            <span class="spinner-border spinner-border-sm text-light d-none ms-1" role="status" aria-hidden="true"></span>
        </button>
    </div>
</div>

@push('script')
<script>
    "use strict";

    (function () {
        const editor = document.getElementById('serviceOverviewEditor');
        if (!editor) return;

        const iconOptions = @json($overviewIconOptions ?? []);
        const colorOptions = @json($colorOptions);
        const selectIconLabel = @json(translate('select_icon'));
        const uploadImageLabel = @json(translate('Upload image'));
        const stepImageUrlPlaceholder = @json(translate('Or paste image URL (optional)'));
        const customIconUrlPlaceholder = @json(translate('Or paste custom icon URL'));
        const uploadingLabel = @json(translate('Uploading...'));
        const uploadFailedLabel = @json(translate('Upload failed'));
        const updatedSuccessfullyLabel = @json(translate('updated_successfully'));
        const failedToUpdateLabel = @json(translate('failed_to_update'));
        const saveUrl = editor.dataset.saveUrl;
        const uploadUrl = editor.dataset.uploadUrl;
        const imageAccept = @json($imageAccept);
        const saveButtons = Array.from(document.querySelectorAll('.overview-content-save-btn'));
        const stickySaveBar = document.getElementById('overviewStickySaveBar');
        const overviewPane = document.getElementById('service-edit-pane-overview');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || '{{ csrf_token() }}';
        let saving = false;

        function setSaveLoading(isLoading) {
            saving = isLoading;
            saveButtons.forEach(function (btn) {
                const idleLabel = btn.dataset.labelIdle || 'Save';
                const loadingLabel = btn.dataset.labelLoading || 'Loading...';
                const labelEl = btn.querySelector('.overview-save-label');
                const spinnerEl = btn.querySelector('.spinner-border');

                btn.disabled = isLoading;
                if (labelEl) labelEl.textContent = isLoading ? loadingLabel : idleLabel;
                if (spinnerEl) spinnerEl.classList.toggle('d-none', !isLoading);
            });
        }

        function syncStickySaveBar() {
            const isOverviewActive = !!(overviewPane && overviewPane.classList.contains('active'));
            if (stickySaveBar) {
                stickySaveBar.classList.toggle('is-visible', isOverviewActive);
                stickySaveBar.setAttribute('aria-hidden', isOverviewActive ? 'false' : 'true');
            }
            document.body.classList.toggle('overview-tab-active', isOverviewActive);
        }

        syncStickySaveBar();
        document.getElementById('service-edit-main-tabs')?.addEventListener('shown.bs.tab', syncStickySaveBar);

        function escapeAttr(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function imageUploaderHtml(fieldClass, value, urlPlaceholder) {
            const hasValue = !!(value && String(value).trim());
            return '<div class="overview-image-uploader" data-image-field="' + escapeAttr(fieldClass) + '">'
                + '<div class="overview-image-card">'
                + '<label class="overview-image-preview-frame overview-image-upload-label' + (hasValue ? ' has-image' : '') + '">'
                + '<img src="' + escapeAttr(value || '') + '" alt="" class="overview-image-preview-img' + (hasValue ? '' : ' d-none') + '">'
                + '<div class="overview-image-placeholder' + (hasValue ? ' d-none' : '') + '">'
                + '<span class="material-icons">add_photo_alternate</span><span>Upload image</span>'
                + '</div>'
                + '<div class="overview-image-progress-overlay d-none">'
                + '<div class="overview-image-progress-track"><div class="overview-image-progress-bar" style="width: 0%"></div></div>'
                + '<span class="overview-image-progress-pct">0%</span>'
                + '</div>'
                + '<input type="file" class="d-none overview-image-file-input" accept="' + escapeAttr(imageAccept) + '">'
                + '</label>'
                + '<button type="button" class="overview-image-clear-btn' + (hasValue ? '' : ' d-none') + '" title="Remove">'
                + '<span class="material-icons">close</span>'
                + '</button>'
                + '<input type="hidden" class="' + escapeAttr(fieldClass) + ' overview-image-url-input" value="' + escapeAttr(value || '') + '">'
                + '<p class="overview-image-upload-status d-none mb-0"></p>'
                + '</div></div>';
        }

        function setPreviewImage(uploader, src) {
            if (!uploader) return;
            const frame = uploader.querySelector('.overview-image-preview-frame');
            const previewImg = uploader.querySelector('.overview-image-preview-img');
            const placeholder = uploader.querySelector('.overview-image-placeholder');
            const clearBtn = uploader.querySelector('.overview-image-clear-btn');
            const hasValue = !!(src && String(src).trim());

            if (previewImg) {
                previewImg.src = hasValue ? src : '';
                previewImg.classList.toggle('d-none', !hasValue);
            }
            if (placeholder) placeholder.classList.toggle('d-none', hasValue);
            if (frame) frame.classList.toggle('has-image', hasValue);
            if (clearBtn) clearBtn.classList.toggle('d-none', !hasValue);
        }

        function syncImagePreview(uploader) {
            if (!uploader) return;
            const urlInput = uploader.querySelector('.overview-image-url-input');
            setPreviewImage(uploader, (urlInput?.value || '').trim());
        }

        function setUploadStatus(uploader, message, state) {
            const statusEl = uploader.querySelector('.overview-image-upload-status');
            if (!statusEl) return;
            statusEl.classList.remove('is-error', 'is-success');
            if (!message) {
                statusEl.textContent = '';
                statusEl.classList.add('d-none');
                return;
            }
            statusEl.textContent = message;
            statusEl.classList.remove('d-none');
            if (state === 'error') statusEl.classList.add('is-error');
            if (state === 'success') statusEl.classList.add('is-success');
        }

        function setUploadProgress(uploader, percent, visible) {
            const overlay = uploader.querySelector('.overview-image-progress-overlay');
            const bar = uploader.querySelector('.overview-image-progress-bar');
            const pct = uploader.querySelector('.overview-image-progress-pct');
            const clamped = Math.max(0, Math.min(100, Math.round(percent || 0)));

            if (overlay) overlay.classList.toggle('d-none', !visible);
            if (bar) bar.style.width = clamped + '%';
            if (pct) pct.textContent = clamped + '%';
        }

        function showLocalPreview(uploader, file) {
            if (!file || !file.type || file.type.indexOf('image/') !== 0) return;
            const reader = new FileReader();
            reader.onload = function (e) {
                setPreviewImage(uploader, e.target.result);
            };
            reader.readAsDataURL(file);
        }

        function uploadOverviewImage(uploader, file) {
            if (!uploadUrl || !file) return;

            const urlInput = uploader.querySelector('.overview-image-url-input');
            const previousUrl = (urlInput?.value || '').trim();
            const formData = new FormData();
            formData.append('image', file);
            formData.append('old_url', previousUrl);

            uploader.classList.add('is-uploading');
            showLocalPreview(uploader, file);
            setUploadProgress(uploader, 0, true);
            setUploadStatus(uploader, uploadingLabel, 'progress');

            $.ajax({
                url: uploadUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                xhr: function () {
                    const xhr = $.ajaxSettings.xhr();
                    if (xhr && xhr.upload) {
                        xhr.upload.addEventListener('progress', function (event) {
                            if (!event.lengthComputable) return;
                            const percent = (event.loaded / event.total) * 100;
                            // Cap upload phase at 90%; processing finishes after response.
                            setUploadProgress(uploader, Math.min(90, percent), true);
                        });
                    }
                    return xhr;
                },
                success: function (response) {
                    setUploadProgress(uploader, 100, true);
                    if (response.flag === 1 && response.url) {
                        if (urlInput) urlInput.value = response.url;
                        setPreviewImage(uploader, response.url);
                        setUploadStatus(uploader, 'Uploaded', 'success');
                        toastr.success(response.message || updatedSuccessfullyLabel);
                        setTimeout(function () {
                            setUploadStatus(uploader, '', null);
                        }, 1500);
                    } else {
                        setPreviewImage(uploader, previousUrl);
                        if (urlInput) urlInput.value = previousUrl;
                        setUploadStatus(uploader, response.message || uploadFailedLabel, 'error');
                        toastr.error(response.message || uploadFailedLabel);
                    }
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || uploadFailedLabel;
                    setPreviewImage(uploader, previousUrl);
                    if (urlInput) urlInput.value = previousUrl;
                    setUploadStatus(uploader, message, 'error');
                    toastr.error(message);
                },
                complete: function () {
                    uploader.classList.remove('is-uploading');
                    setUploadProgress(uploader, 0, false);
                    const fileInput = uploader.querySelector('.overview-image-file-input');
                    if (fileInput) fileInput.value = '';
                }
            });
        }

        function iconSelectHtml(selected) {
            let html = '<select class="form-select form-select-sm overview-item-icon">';
            html += '<option value="">' + selectIconLabel + '</option>';
            iconOptions.forEach(function (opt) {
                html += '<option value="' + opt.key + '"' + (selected === opt.key ? ' selected' : '') + '>' + opt.label + '</option>';
            });
            return html + '</select>';
        }

        function colorSelectHtml(selected) {
            let html = '<select class="form-select form-select-sm overview-item-color">';
            colorOptions.forEach(function (color) {
                html += '<option value="' + color + '"' + (selected === color ? ' selected' : '') + '>' + color + '</option>';
            });
            return html + '</select>';
        }

        function createItemRow(type, data) {
            data = data || {};
            const row = document.createElement('div');
            row.className = 'overview-item-row border rounded p-2 mb-2';
            row.innerHTML = '<div class="d-flex gap-2 align-items-start">'
                + '<span class="material-icons overview-drag-handle mt-1" draggable="true">drag_indicator</span>'
                + '<div class="flex-grow-1 overview-item-fields"></div>'
                + '<button type="button" class="btn btn-sm btn-outline-danger overview-remove-item" title="Remove"><span class="material-icons fs-16">delete</span></button>'
                + '</div>';

            const fields = row.querySelector('.overview-item-fields');
            let inner = '';

            if (type === 'process') {
                inner += '<div class="overview-process-card">'
                    + '<div class="overview-process-card__media">'
                    + imageUploaderHtml('overview-item-image', data.image || '', stepImageUrlPlaceholder)
                    + '</div>'
                    + '<div class="overview-process-card__fields"><div class="row g-2">'
                    + '<div class="col-md-4">' + iconSelectHtml(data.icon || '') + '</div>'
                    + '<div class="col-md-8"><input type="text" class="form-control form-control-sm overview-item-title" placeholder="Step title" value="' + escapeAttr(data.title || '') + '"></div>'
                    + '<div class="col-12"><textarea class="form-control form-control-sm overview-item-description" rows="3" placeholder="Step description">' + escapeAttr(data.description || '') + '</textarea></div>'
                    + '</div></div></div>';
            } else if (type === 'icon_title') {
                inner += '<div class="row g-2">'
                    + '<div class="col-md-4">' + iconSelectHtml(data.icon || '') + '</div>'
                    + '<div class="col-md-8"><input type="text" class="form-control form-control-sm overview-item-title" placeholder="Title" value="' + escapeAttr(data.title || data.text || '') + '"></div>'
                    + '</div>';
            } else if (type === 'chip' || type === 'icon_text') {
                inner += '<div class="overview-process-card">'
                    + '<div class="overview-process-card__media">'
                    + imageUploaderHtml('overview-item-icon-image', data.icon_image || '', customIconUrlPlaceholder)
                    + '</div>'
                    + '<div class="overview-process-card__fields"><div class="row g-2">'
                    + '<div class="col-md-4">' + iconSelectHtml(data.icon || '') + '</div>'
                    + '<div class="col-md-8"><input type="text" class="form-control form-control-sm overview-item-text" placeholder="Text" value="' + escapeAttr(data.text || '') + '"></div>'
                    + '</div></div></div>';
            } else if (type === 'top_icon') {
                inner += '<div class="row g-2">'
                    + '<div class="col-md-3">' + iconSelectHtml(data.icon || '') + '</div>'
                    + '<div class="col-md-3">' + colorSelectHtml(data.color || 'green') + '</div>'
                    + '<div class="col-md-6"><input type="text" class="form-control form-control-sm overview-item-text" placeholder="Label" value="' + escapeAttr(data.text || '') + '"></div>'
                    + '</div>';
            } else if (type === 'why_choose') {
                inner += '<div class="row g-2">'
                    + '<div class="col-md-3">' + iconSelectHtml(data.icon || '') + '</div>'
                    + '<div class="col-md-3">' + colorSelectHtml(data.color || 'green') + '</div>'
                    + '<div class="col-md-6"><input type="text" class="form-control form-control-sm overview-item-title" placeholder="Title" value="' + escapeAttr(data.title || '') + '"></div>'
                    + '<div class="col-12"><input type="text" class="form-control form-control-sm overview-item-description" placeholder="Description" value="' + escapeAttr(data.description || '') + '"></div>'
                    + '</div>';
            } else {
                inner += '<input type="text" class="form-control form-control-sm overview-item-text" placeholder="Text" value="' + escapeAttr(data.text || '') + '">';
            }

            fields.innerHTML = inner;
            return row;
        }

        function collectItems(listEl, type) {
            const items = [];
            if (!listEl) return items;
            listEl.querySelectorAll('.overview-item-row').forEach(function (row, index) {
                const item = { sort_order: index };
                const icon = row.querySelector('.overview-item-icon');
                const text = row.querySelector('.overview-item-text');
                const title = row.querySelector('.overview-item-title');
                const description = row.querySelector('.overview-item-description');
                const image = row.querySelector('.overview-item-image');
                const iconImage = row.querySelector('.overview-item-icon-image');
                const color = row.querySelector('.overview-item-color');

                if (icon && icon.value) item.icon = icon.value;
                if (text && text.value.trim()) item.text = text.value.trim();
                if (title && title.value.trim()) item.title = title.value.trim();
                if (description && description.value.trim()) item.description = description.value.trim();
                if (image && image.value.trim()) item.image = image.value.trim();
                if (iconImage && iconImage.value.trim()) item.icon_image = iconImage.value.trim();
                if (color && color.value) item.color = color.value;

                if (Object.keys(item).length > 1) {
                    items.push(item);
                }
            });
            return items;
        }

        function collectSection(prefix, type) {
            const titleInput = document.getElementById(prefix + '-section-title');
            const listEl = document.getElementById(prefix + '-items-list');
            const items = collectItems(listEl, type);
            if (!items.length) return null;
            return {
                title: titleInput ? titleInput.value.trim() : '',
                items: items,
            };
        }

        function buildPayload() {
            return {
                intro: document.getElementById('overview-intro').value.trim(),
                override_top_icons: document.getElementById('override-top-icons').checked,
                override_why_choose: document.getElementById('override-why-choose').checked,
                service_process: collectSection('service-process', 'process'),
                perfect_for: collectSection('perfect-for', 'chip'),
                whats_included: collectSection('whats-included', 'icon_title'),
                whats_not_included: collectSection('whats-not-included', 'icon_title'),
                good_to_know: collectSection('good-to-know', 'icon_title'),
                override_terms_and_conditions: document.getElementById('override-terms-and-conditions').checked,
                terms_and_conditions: {
                    title: document.getElementById('terms-and-conditions-title').value.trim(),
                    items: collectItems(document.getElementById('terms-and-conditions-list'), 'icon_title'),
                },
                top_icons: collectItems(document.getElementById('top-icons-list'), 'top_icon'),
                why_choose: {
                    title: document.getElementById('why-choose-title').value.trim(),
                    items: collectItems(document.getElementById('why-choose-list'), 'why_choose'),
                },
            };
        }

        document.querySelectorAll('[data-overview-add]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const list = document.getElementById(btn.dataset.listId);
                list.appendChild(createItemRow(btn.dataset.itemType, {}));
            });
        });

        document.addEventListener('click', function (e) {
            const removeBtn = e.target.closest('.overview-remove-item');
            if (removeBtn) {
                removeBtn.closest('.overview-item-row').remove();
                return;
            }

            const clearBtn = e.target.closest('.overview-image-clear-btn');
            if (clearBtn) {
                const uploader = clearBtn.closest('.overview-image-uploader');
                const urlInput = uploader?.querySelector('.overview-image-url-input');
                if (urlInput) urlInput.value = '';
                setPreviewImage(uploader, '');
                setUploadProgress(uploader, 0, false);
                setUploadStatus(uploader, '', null);
            }
        });

        document.addEventListener('change', function (e) {
            const fileInput = e.target.closest('.overview-image-file-input');
            if (!fileInput) return;
            const uploader = fileInput.closest('.overview-image-uploader');
            const file = fileInput.files && fileInput.files[0];
            if (uploader && file) {
                uploadOverviewImage(uploader, file);
            }
        });

        document.addEventListener('input', function (e) {
            if (!e.target.classList.contains('overview-image-url-input')) return;
            syncImagePreview(e.target.closest('.overview-image-uploader'));
        });

        function toggleOverrideSection(checkboxId, sectionId) {
            const checkbox = document.getElementById(checkboxId);
            const section = document.getElementById(sectionId);
            if (!checkbox || !section) return;
            checkbox.addEventListener('change', function () {
                section.classList.toggle('opacity-50', !checkbox.checked);
                section.classList.toggle('pe-none', !checkbox.checked);
            });
        }

        toggleOverrideSection('override-top-icons', 'top-icons-section');
        toggleOverrideSection('override-why-choose', 'why-choose-section');
        toggleOverrideSection('override-terms-and-conditions', 'terms-and-conditions-section');

        function saveOverviewContent() {
            if (saving) return;
            if (editor.querySelector('.overview-image-uploader.is-uploading')) {
                toastr.error(uploadingLabel);
                return;
            }
            setSaveLoading(true);

            let payload;
            try {
                payload = buildPayload();
            } catch (err) {
                setSaveLoading(false);
                toastr.error(failedToUpdateLabel);
                return;
            }

            $.ajax({
                url: saveUrl,
                method: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                data: JSON.stringify({ overview_content: payload }),
                success: function (response) {
                    if (response.flag === 1) {
                        toastr.success(response.message || updatedSuccessfullyLabel);
                    } else {
                        toastr.error(response.message || failedToUpdateLabel);
                    }
                },
                error: function () {
                    toastr.error(failedToUpdateLabel);
                },
                complete: function () {
                    setSaveLoading(false);
                }
            });
        }

        saveButtons.forEach(function (btn) {
            btn.addEventListener('click', saveOverviewContent);
        });
    })();
</script>
@endpush
