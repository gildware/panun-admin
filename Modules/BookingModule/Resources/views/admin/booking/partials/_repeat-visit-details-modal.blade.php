@php
    $visitId = (string) ($visit['id'] ?? '');
    $visitModalId = 'repeat-visit-modal-' . $visitId;
    $visitReadableId = (string) ($visit['readable_id'] ?? '');
    $visitModalTitle = $visitReadableId !== '' ? ('#' . $visitReadableId) : translate('Visit_details');
@endphp
<div class="modal fade repeat-visit-modal" id="{{ $visitModalId }}" tabindex="-1" aria-labelledby="{{ $visitModalId }}-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $visitModalId }}-label">{{ translate('Visit_details') }} {{ $visitModalTitle }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <div class="modal-body">
                @include('bookingmodule::admin.booking.partials._repeat-visit-inline-panel', ['visit' => $visit])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
            </div>
        </div>
    </div>
</div>
