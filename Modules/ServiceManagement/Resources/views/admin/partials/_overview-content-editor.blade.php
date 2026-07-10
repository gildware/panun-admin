@php
    $colorOptions = ['green', 'blue', 'purple', 'orange'];
    $overviewContentJson = json_encode($overviewContent ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $overviewDefaultsJson = json_encode($overviewDefaults ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
@endphp

<div class="service-overview-editor" id="serviceOverviewEditor"
     data-save-url="{{ route('admin.service-overview.update', $service->id) }}"
     data-initial='@json($overviewContent ?? [])'
     data-defaults='@json($overviewDefaults ?? [])'>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h6 class="mb-1">{{ translate('service_overview_sections') }}</h6>
            <p class="text-muted fs-12 mb-0">{{ translate('add_custom_sections_for_this_service') }}</p>
        </div>
        <a href="{{ route('admin.service-overview.defaults') }}" class="btn btn-sm btn-outline-primary" target="_blank">
            {{ translate('edit_global_defaults') }}
        </a>
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

    <div class="d-flex justify-content-end">
        <button type="button" class="btn btn--primary" id="overview-content-save-btn">
            <span class="overview-save-label">{{ translate('save_overview_content') }}</span>
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
        const updatedSuccessfullyLabel = @json(translate('updated_successfully'));
        const failedToUpdateLabel = @json(translate('failed_to_update'));
        const saveUrl = editor.dataset.saveUrl;
        let saving = false;

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
                inner += '<div class="row g-2">'
                    + '<div class="col-md-4">' + iconSelectHtml(data.icon || '') + '</div>'
                    + '<div class="col-md-8"><input type="text" class="form-control form-control-sm overview-item-title" placeholder="Step title" value="' + (data.title || '') + '"></div>'
                    + '<div class="col-12"><input type="text" class="form-control form-control-sm overview-item-image" placeholder="Step image URL (optional — use icon if empty)" value="' + (data.image || '') + '"></div>'
                    + '<div class="col-12"><input type="text" class="form-control form-control-sm overview-item-description" placeholder="Step description" value="' + (data.description || '') + '"></div>'
                    + '</div>';
            } else if (type === 'icon_title') {
                inner += '<div class="row g-2">'
                    + '<div class="col-md-4">' + iconSelectHtml(data.icon || '') + '</div>'
                    + '<div class="col-md-8"><input type="text" class="form-control form-control-sm overview-item-title" placeholder="Title" value="' + (data.title || data.text || '') + '"></div>'
                    + '</div>';
            } else if (type === 'chip' || type === 'icon_text') {
                inner += '<div class="row g-2">'
                    + '<div class="col-md-4">' + iconSelectHtml(data.icon || '') + '</div>'
                    + '<div class="col-md-4"><input type="text" class="form-control form-control-sm overview-item-icon-image" placeholder="Custom icon URL" value="' + (data.icon_image || '') + '"></div>'
                    + '<div class="col-md-4"><input type="text" class="form-control form-control-sm overview-item-text" placeholder="Text" value="' + (data.text || '') + '"></div>'
                    + '</div>';
            } else if (type === 'top_icon') {
                inner += '<div class="row g-2">'
                    + '<div class="col-md-3">' + iconSelectHtml(data.icon || '') + '</div>'
                    + '<div class="col-md-3">' + colorSelectHtml(data.color || 'green') + '</div>'
                    + '<div class="col-md-6"><input type="text" class="form-control form-control-sm overview-item-text" placeholder="Label" value="' + (data.text || '') + '"></div>'
                    + '</div>';
            } else if (type === 'why_choose') {
                inner += '<div class="row g-2">'
                    + '<div class="col-md-3">' + iconSelectHtml(data.icon || '') + '</div>'
                    + '<div class="col-md-3">' + colorSelectHtml(data.color || 'green') + '</div>'
                    + '<div class="col-md-6"><input type="text" class="form-control form-control-sm overview-item-title" placeholder="Title" value="' + (data.title || '') + '"></div>'
                    + '<div class="col-12"><input type="text" class="form-control form-control-sm overview-item-description" placeholder="Description" value="' + (data.description || '') + '"></div>'
                    + '</div>';
            } else {
                inner += '<input type="text" class="form-control form-control-sm overview-item-text" placeholder="Text" value="' + (data.text || '') + '">';
            }

            fields.innerHTML = inner;
            return row;
        }

        function collectItems(listEl, type) {
            const items = [];
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
            }
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

        document.getElementById('overview-content-save-btn').addEventListener('click', function () {
            if (saving) return;
            saving = true;
            const btn = this;
            btn.disabled = true;

            $.ajax({
                url: saveUrl,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    overview_content: JSON.stringify(buildPayload()),
                },
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
                    saving = false;
                    btn.disabled = false;
                }
            });
        });
    })();
</script>
@endpush
