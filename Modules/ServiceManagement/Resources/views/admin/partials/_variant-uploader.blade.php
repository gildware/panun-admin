@php
    $inputName = $inputName ?? null;
    $inputId = $inputId ?? null;
    $previewUrl = $previewUrl ?? asset('assets/admin-module/img/img-upload-new.png');
    $required = $required ?? false;
@endphp
<div class="upload-file ratio-1 w-100px">
    <input type="file"
           class="upload-file__input"
           @if($inputName) name="{{ $inputName }}" @endif
           @if($inputId) id="{{ $inputId }}" @endif
           accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
           data-maxFileSize="{{ readableUploadMaxFileSize('image') }}"
           @if($required) required @endif>
    <div class="upload-file__img border-dashed-1-gray rounded">
        <img src="{{ $previewUrl }}" alt="{{ translate('image') }}" class="w-100">
    </div>
    <span class="upload-file__edit">
        <span class="material-icons">edit</span>
    </span>
</div>
