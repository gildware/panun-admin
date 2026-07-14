@php
    $fieldClass = $fieldClass ?? 'overview-item-image';
    $value = trim((string) ($value ?? ''));
    $accept = '.'.implode(',.', array_column(IMAGEEXTENSION, 'key')).', |image/*';
    $hasValue = $value !== '';
@endphp
<div class="overview-image-uploader" data-image-field="{{ $fieldClass }}">
    <div class="overview-image-card">
        <label class="overview-image-preview-frame overview-image-upload-label {{ $hasValue ? 'has-image' : '' }}">
            <img src="{{ $hasValue ? $value : '' }}"
                 alt=""
                 class="overview-image-preview-img {{ $hasValue ? '' : 'd-none' }}">
            <div class="overview-image-placeholder {{ $hasValue ? 'd-none' : '' }}">
                <span class="material-icons">add_photo_alternate</span>
                <span>Upload image</span>
            </div>
            <div class="overview-image-progress-overlay d-none">
                <div class="overview-image-progress-track">
                    <div class="overview-image-progress-bar" style="width: 0%"></div>
                </div>
                <span class="overview-image-progress-pct">0%</span>
            </div>
            <input type="file"
                   class="d-none overview-image-file-input"
                   accept="{{ $accept }}"
                   data-maxFileSize="{{ readableUploadMaxFileSize('image') }}">
        </label>
        <button type="button"
                class="overview-image-clear-btn {{ $hasValue ? '' : 'd-none' }}"
                title="{{ translate('remove') }}">
            <span class="material-icons">close</span>
        </button>
        <input type="hidden"
               class="{{ $fieldClass }} overview-image-url-input"
               value="{{ $value }}">
        <p class="overview-image-upload-status d-none mb-0"></p>
    </div>
</div>
