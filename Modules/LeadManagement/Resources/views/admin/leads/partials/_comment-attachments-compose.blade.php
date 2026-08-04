@php
    $composeId = $composeId ?? ('commentAttach'.uniqid());
    $imageAccept = '.'.implode(',.', array_column(IMAGEEXTENSION, 'key')).',image/*';
    $fileAccept = '.'.implode(',.', array_column(ALLOWED_FILE_TYPE, 'key')).',video/*,audio/*,application/pdf';
@endphp
<div class="comment-attachments-compose" data-comment-attachments-compose>
    <input type="file"
           id="{{ $composeId }}Files"
           name="files[]"
           class="d-none comment-attachments-input"
           multiple
           accept="{{ $imageAccept }},{{ $fileAccept }}">
    <div class="comment-attachments-preview d-flex flex-wrap gap-2 mt-2" data-comment-attachments-preview></div>
    <div class="comment-attachments-toolbar d-flex align-items-center gap-2">
        <label class="comment-attach-btn mb-0" for="{{ $composeId }}Files" title="{{ translate('Attach_files') }}">
            <span class="material-icons" aria-hidden="true">attach_file</span>
            <span class="visually-hidden">{{ translate('Attach_files') }}</span>
        </label>
        <span class="small text-muted comment-attachments-hint">{{ translate('Attach_images_files_audio_or_video') }}</span>
    </div>
</div>
