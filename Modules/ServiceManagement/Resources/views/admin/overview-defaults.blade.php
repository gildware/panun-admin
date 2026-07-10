@extends('adminmodule::layouts.master')

@section('title', translate('service_overview_defaults'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('service_overview_defaults') }}</h2>
                <p class="text-muted fs-13 mb-0">{{ translate('common_sections_used_across_all_services') }}</p>
            </div>

            <form action="{{ route('admin.service-overview.defaults.update') }}" method="POST" id="overviewDefaultsForm">
                @csrf
                <input type="hidden" name="overview_defaults" id="overview-defaults-payload">

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">{{ translate('hero_top_icons') }}</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-overview-add data-list-id="defaults-top-icons-list" data-item-type="top_icon">
                                + {{ translate('add_item') }}
                            </button>
                        </div>
                        @include('servicemanagement::admin.partials._overview-items-list', [
                            'listId' => 'defaults-top-icons-list',
                            'itemType' => 'top_icon',
                            'items' => $defaults['top_icons'] ?? [],
                            'overviewIconOptions' => $iconOptions,
                        ])
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">{{ translate('why_choose_panun_kaergar') }}</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-overview-add data-list-id="defaults-why-choose-list" data-item-type="why_choose">
                                + {{ translate('add_item') }}
                            </button>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="defaults-why-choose-title"
                                   value="{{ $defaults['why_choose']['title'] ?? '' }}">
                            <label for="defaults-why-choose-title">{{ translate('section_title') }}</label>
                        </div>
                        @include('servicemanagement::admin.partials._overview-items-list', [
                            'listId' => 'defaults-why-choose-list',
                            'itemType' => 'why_choose',
                            'items' => $defaults['why_choose']['items'] ?? [],
                            'overviewIconOptions' => $iconOptions,
                        ])
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.service.index') }}" class="btn btn-secondary">{{ translate('back') }}</a>
                    <button type="submit" class="btn btn--primary">{{ translate('save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
<script>
    "use strict";

    (function () {
        const iconOptions = @json($iconOptions);
        const colorOptions = ['green', 'blue', 'purple', 'orange'];

        function iconSelectHtml(selected) {
            let html = '<select class="form-select form-select-sm overview-item-icon">';
            html += '<option value="">Select icon</option>';
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
                + '<button type="button" class="btn btn-sm btn-outline-danger overview-remove-item"><span class="material-icons fs-16">delete</span></button>'
                + '</div>';

            const fields = row.querySelector('.overview-item-fields');
            let inner = '';

            if (type === 'top_icon') {
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
            }

            fields.innerHTML = inner;
            return row;
        }

        function collectItems(listEl) {
            const items = [];
            listEl.querySelectorAll('.overview-item-row').forEach(function (row, index) {
                const item = { sort_order: index };
                const icon = row.querySelector('.overview-item-icon');
                const text = row.querySelector('.overview-item-text');
                const title = row.querySelector('.overview-item-title');
                const description = row.querySelector('.overview-item-description');
                const color = row.querySelector('.overview-item-color');

                if (icon && icon.value) item.icon = icon.value;
                if (text && text.value.trim()) item.text = text.value.trim();
                if (title && title.value.trim()) item.title = title.value.trim();
                if (description && description.value.trim()) item.description = description.value.trim();
                if (color && color.value) item.color = color.value;

                if (Object.keys(item).length > 1) items.push(item);
            });
            return items;
        }

        document.querySelectorAll('[data-overview-add]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const list = document.getElementById(btn.dataset.listId);
                const empty = list.querySelector('.overview-empty-hint');
                if (empty) empty.remove();
                list.appendChild(createItemRow(btn.dataset.itemType, {}));
            });
        });

        document.addEventListener('click', function (e) {
            const removeBtn = e.target.closest('.overview-remove-item');
            if (removeBtn) removeBtn.closest('.overview-item-row').remove();
        });

        document.getElementById('overviewDefaultsForm').addEventListener('submit', function () {
            document.getElementById('overview-defaults-payload').value = JSON.stringify({
                top_icons: collectItems(document.getElementById('defaults-top-icons-list')),
                why_choose: {
                    title: document.getElementById('defaults-why-choose-title').value.trim(),
                    items: collectItems(document.getElementById('defaults-why-choose-list')),
                },
            });
        });
    })();
</script>
@endpush
