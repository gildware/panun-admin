@php
    $listId = $sectionKey . '-items-list';
    $titleId = str_replace('_', '-', $sectionKey) . '-section-title';
@endphp

<div class="card mb-3 overview-section-card" data-section="{{ $sectionKey }}">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h6 class="mb-0">{{ $sectionTitle }}</h6>
            <button type="button" class="btn btn-sm btn-outline-primary"
                    data-overview-add
                    data-list-id="{{ $listId }}"
                    data-item-type="{{ $itemType }}">
                + {{ translate('add_item') }}
            </button>
        </div>
        <div class="form-floating mb-3">
            <input type="text" class="form-control form-control-sm" id="{{ $titleId }}"
                   value="{{ $sectionLabel }}">
            <label for="{{ $titleId }}">{{ translate('section_title') }}</label>
        </div>
        @include('servicemanagement::admin.partials._overview-items-list', [
            'listId' => $listId,
            'itemType' => $itemType,
            'items' => $items,
            'overviewIconOptions' => $overviewIconOptions ?? [],
        ])
    </div>
</div>
