@extends('adminmodule::layouts.master')

@section('title', translate('feedback_tag_configuration'))

@push('css_or_lib')
    <style>
        /* Fallback if admin CSS overrides the native hidden attribute */
        .feedback-config-panel [hidden] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    @php($rowIndex = 0)
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Feedback Tag Configuration') }}</h2>
                <p class="text-muted mb-0">{{ translate('Configure tag scores for provider and customer performance calculations.') }}</p>
                <p class="text-muted small mb-0 mt-2">
                    {{ translate('New tags get an internal ID automatically from the name you enter.') }}
                </p>
            </div>

            <div class="card">
                <div class="card-body">
                    <form id="feedback-tag-config-form" method="POST" action="{{ route('admin.provider.feedback-tags.update') }}">
                        @csrf
                        <ul class="nav nav--tabs nav--tabs__style2 mb-4" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#feedback-tab-provider" type="button">{{ translate('Provider') }}</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#feedback-tab-customer" type="button">{{ translate('Customer') }}</button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            @foreach(['provider', 'customer'] as $entityType)
                                <div class="tab-pane fade {{ $entityType === 'provider' ? 'show active' : '' }}" id="feedback-tab-{{ $entityType }}">
                                    <div class="row g-3">
                                        @foreach(['complaint', 'positive_feedback', 'non_complaint'] as $feedbackType)
                                            @php($rows = data_get($configs, $entityType . '.' . $feedbackType, collect()))
                                            @php($tableId = 'feedback-table-' . $entityType . '-' . $feedbackType)
                                            <div class="col-12 col-lg-6">
                                                <div class="feedback-config-panel border rounded p-3 h-100 d-flex flex-column panel-view-mode"
                                                     data-entity="{{ $entityType }}"
                                                     data-feedback-type="{{ $feedbackType }}">
                                                    <div class="feedback-pending-deletes"></div>
                                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                                        <div class="fw-semibold">{{ translate(str_replace('_', ' ', ucfirst($feedbackType))) }}</div>
                                                        <div class="d-flex align-items-center gap-2 feedback-panel-actions-view">
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-secondary feedback-panel-edit">
                                                                {{ translate('Edit') }}
                                                            </button>
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-primary add-feedback-tag-row"
                                                                    data-entity="{{ $entityType }}"
                                                                    data-feedback-type="{{ $feedbackType }}"
                                                                    data-target="#{{ $tableId }}">
                                                                + {{ translate('Add') }}
                                                            </button>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2 feedback-panel-actions-edit d-none">
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-secondary feedback-panel-cancel">
                                                                {{ translate('Cancel') }}
                                                            </button>
                                                            <button type="submit"
                                                                    class="btn btn-sm btn--primary feedback-panel-update">
                                                                {{ translate('Update') }}
                                                            </button>
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-primary add-feedback-tag-row"
                                                                    data-entity="{{ $entityType }}"
                                                                    data-feedback-type="{{ $feedbackType }}"
                                                                    data-target="#{{ $tableId }}">
                                                                + {{ translate('Add') }}
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="table-responsive flex-grow-1">
                                                        <table class="table mb-0">
                                                            <thead>
                                                            <tr>
                                                                <th>{{ translate('Admin label') }}</th>
                                                                <th style="width:110px;">{{ translate('Score') }}</th>
                                                                <th style="width:90px;">{{ translate('Active') }}</th>
                                                                <th class="feedback-actions-col text-end" style="width:88px;" hidden>{{ translate('Actions') }}</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody id="{{ $tableId }}">
                                                            @forelse($rows as $row)
                                                                @php($isSystem = (bool) ($row->is_system ?? false))
                                                                <tr class="feedback-tag-row {{ $isSystem ? 'is-system-tag' : 'is-custom-tag' }}">
                                                                    <td>
                                                                        <span class="feedback-cell-view">{{ $row->label }}</span>
                                                                        <div class="feedback-cell-edit" hidden>
                                                                            <input type="hidden" name="rows[{{ $rowIndex }}][id]" value="{{ $row->id }}">
                                                                            <input type="hidden" name="rows[{{ $rowIndex }}][entity_type]" value="{{ $entityType }}">
                                                                            <input type="hidden" name="rows[{{ $rowIndex }}][feedback_type]" value="{{ $feedbackType }}">
                                                                            <input type="hidden" class="feedback-tag-key-input" name="rows[{{ $rowIndex }}][tag_key]" value="{{ $row->tag_key }}">
                                                                            <input type="text" class="form-control form-control-sm" name="rows[{{ $rowIndex }}][label]" value="{{ $row->label }}" required>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <span class="feedback-cell-view">{{ (int) $row->score }}</span>
                                                                        <div class="feedback-cell-edit" hidden>
                                                                            <input type="number" class="form-control form-control-sm" name="rows[{{ $rowIndex }}][score]" value="{{ (int) $row->score }}" min="-100" max="100" required>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <span class="feedback-cell-view">{{ $row->is_active ? translate('Yes') : translate('No') }}</span>
                                                                        <div class="feedback-cell-edit" hidden>
                                                                            <input type="hidden" name="rows[{{ $rowIndex }}][is_active]" value="0">
                                                                            <input class="form-check-input mt-1" type="checkbox" name="rows[{{ $rowIndex }}][is_active]" value="1" {{ $row->is_active ? 'checked' : '' }}>
                                                                        </div>
                                                                    </td>
                                                                    <td class="feedback-actions-col text-end align-middle" hidden>
                                                                        @if(!$isSystem)
                                                                            <button type="button" class="btn btn-sm btn-outline-danger feedback-row-remove" title="{{ translate('Delete') }}">
                                                                                &times;
                                                                            </button>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                @php($rowIndex++)
                                                            @empty
                                                                <tr class="feedback-empty-row">
                                                                    <td colspan="4" class="text-muted text-center">{{ translate('No tags configured') }}</td>
                                                                </tr>
                                                            @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function ($) {
            'use strict';
            if (typeof $ === 'undefined') {
                return;
            }

            let feedbackRowIndex = {{ $rowIndex }};

            function feedbackSlugifyTagKey(text) {
                return String(text || '')
                    .toLowerCase()
                    .replace(/[^a-z0-9_]+/g, '_')
                    .replace(/^_+|_+$/g, '')
                    .substring(0, 64);
            }

            $(document).on('input', '.feedback-row-new .feedback-tag-label-input', function () {
                const $tr = $(this).closest('tr');
                const slug = feedbackSlugifyTagKey($(this).val());
                $tr.find('.feedback-tag-key-input').val(slug);
            });

            $('#feedback-tag-config-form').on('submit', function () {
                $(this).find('tr.feedback-row-new').each(function () {
                    const $tr = $(this);
                    const $label = $tr.find('.feedback-tag-label-input');
                    const $key = $tr.find('.feedback-tag-key-input');
                    let k = String($key.val() || '').trim();
                    if (!k) {
                        k = feedbackSlugifyTagKey($label.val());
                    }
                    $key.val(k);
                });
            });

            /** isViewMode = true: read-only text; false: show inputs */
            function applyFeedbackPanelLayout($panel, isViewMode) {
                $panel.find('.feedback-cell-edit').prop('hidden', isViewMode);
                $panel.find('.feedback-actions-col').prop('hidden', isViewMode);
                $panel.find('.feedback-cell-view').prop('hidden', !isViewMode);
            }

            function setPanelEditMode($panel, editing) {
                const $viewActions = $panel.find('.feedback-panel-actions-view');
                const $editActions = $panel.find('.feedback-panel-actions-edit');
                if (editing) {
                    $panel.removeClass('panel-view-mode');
                    $viewActions.addClass('d-none');
                    $editActions.removeClass('d-none');
                } else {
                    $panel.addClass('panel-view-mode');
                    $viewActions.removeClass('d-none');
                    $editActions.addClass('d-none');
                }
                applyFeedbackPanelLayout($panel, !editing);
            }

            $(document).on('click', '.feedback-panel-edit', function (e) {
                e.preventDefault();
                const $panel = $(this).closest('.feedback-config-panel');
                const $tbody = $panel.find('tbody');
                $panel.data('tbody-html-backup', $tbody.html());
                $panel.find('.feedback-pending-deletes').empty();
                setPanelEditMode($panel, true);
            });

            $(document).on('click', '.feedback-panel-cancel', function (e) {
                e.preventDefault();
                const $panel = $(this).closest('.feedback-config-panel');
                const backup = $panel.data('tbody-html-backup');
                if (typeof backup === 'string') {
                    $panel.find('tbody').html(backup);
                }
                $panel.removeData('tbody-html-backup');
                $panel.find('.feedback-pending-deletes').empty();
                setPanelEditMode($panel, false);
            });

            $(document).on('click', '.feedback-row-remove', function (e) {
                e.preventDefault();
                const $tr = $(this).closest('tr');
                const $panel = $(this).closest('.feedback-config-panel');
                const $tbody = $tr.closest('tbody');
                const idInput = $tr.find('input[name*="[id]"]');
                const id = idInput.length ? String(idInput.val()).trim() : '';
                if (id) {
                    $panel.find('.feedback-pending-deletes').append(
                        $('<input>', {type: 'hidden', name: 'deleted_ids[]', value: id})
                    );
                }
                $tr.remove();
                if ($tbody.find('tr').length === 0) {
                    $tbody.append(
                        '<tr class="feedback-empty-row"><td colspan="4" class="text-muted text-center">{{ translate('No tags configured') }}</td></tr>'
                    );
                }
            });

            $(document).on('click', '.add-feedback-tag-row', function (e) {
                e.preventDefault();
                const $btnPanel = $(this).closest('.feedback-config-panel');
                if ($btnPanel.length && $btnPanel.hasClass('panel-view-mode')) {
                    const $tb = $btnPanel.find('tbody');
                    $btnPanel.data('tbody-html-backup', $tb.html());
                    $btnPanel.find('.feedback-pending-deletes').empty();
                    setPanelEditMode($btnPanel, true);
                }

                const entityType = $(this).data('entity');
                const feedbackType = $(this).data('feedback-type');
                const targetSelector = $(this).data('target');
                const $tbody = $(targetSelector);

                $tbody.find('.feedback-empty-row').remove();

                const idx = feedbackRowIndex++;
                const rowHtml = `
                <tr class="feedback-tag-row feedback-row-new is-custom-tag">
                    <td>
                        <span class="feedback-cell-view" hidden></span>
                        <div class="feedback-cell-edit">
                            <input type="hidden" name="rows[${idx}][entity_type]" value="${entityType}">
                            <input type="hidden" name="rows[${idx}][feedback_type]" value="${feedbackType}">
                            <input type="hidden" class="feedback-tag-key-input" name="rows[${idx}][tag_key]" value="">
                            <input type="text" class="form-control form-control-sm feedback-tag-label-input" name="rows[${idx}][label]" placeholder="{{ translate('Admin label') }}" required autocomplete="off">
                        </div>
                    </td>
                    <td>
                        <span class="feedback-cell-view" hidden></span>
                        <div class="feedback-cell-edit">
                            <input type="number" class="form-control form-control-sm" name="rows[${idx}][score]" value="0" min="-100" max="100" required>
                        </div>
                    </td>
                    <td>
                        <span class="feedback-cell-view" hidden></span>
                        <div class="feedback-cell-edit">
                            <input type="hidden" name="rows[${idx}][is_active]" value="0">
                            <input class="form-check-input mt-1" type="checkbox" name="rows[${idx}][is_active]" value="1" checked>
                        </div>
                    </td>
                    <td class="feedback-actions-col text-end align-middle">
                        <button type="button" class="btn btn-sm btn-outline-danger feedback-row-remove" title="{{ translate('Delete') }}">&times;</button>
                    </td>
                </tr>
            `;

                $tbody.append(rowHtml);
                const $panel = $tbody.closest('.feedback-config-panel');
                if ($panel.length && !$panel.hasClass('panel-view-mode')) {
                    applyFeedbackPanelLayout($panel, false);
                }
            });
        })(window.jQuery || window.$);
    </script>
@endpush
