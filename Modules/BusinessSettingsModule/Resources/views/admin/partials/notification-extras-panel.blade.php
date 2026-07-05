@php
    $panelId = $notificationKey . '-' . ($fieldSuffix ?? 'default');
@endphp

<div class="notification-extras mb-3">
    <div class="d-flex flex-wrap gap-2">
        @if(count($variables))
            <button type="button"
                    class="btn btn-sm btn-outline-primary notification-toggle-btn"
                    data-toggle-target="#notification-vars-{{ $panelId }}"
                    data-show-label="{{ translate('Show_variable') }}"
                    data-hide-label="{{ translate('Hide_variable') }}">
                {{ translate('Show_variable') }}
            </button>
        @endif
        <button type="button"
                class="btn btn-sm btn-outline-primary notification-toggle-btn"
                data-toggle-target="#notification-preview-{{ $panelId }}"
                data-show-label="{{ translate('Show_Preview') }}"
                data-hide-label="{{ translate('Hide_Preview') }}">
            {{ translate('Show_Preview') }}
        </button>
    </div>

    @if(count($variables))
        <div id="notification-vars-{{ $panelId }}" class="notification-toggle-panel d-none border rounded p-3 mt-2 bg-white">
            <div class="mb-3">
                <div class="fz-12 text-muted mb-2">{{ translate('Title') }}</div>
                <div class="d-flex flex-wrap gap-1">
                    @foreach($variables as $var)
                        <button type="button" class="btn btn-sm btn-outline-primary notification-var-chip"
                                data-target="{{ $titleFieldId }}" data-var="{{ $var }}">{{ $var }}</button>
                    @endforeach
                </div>
            </div>
            <div>
                <div class="fz-12 text-muted mb-2">{{ translate('Description') }}</div>
                <div class="d-flex flex-wrap gap-1">
                    @foreach($variables as $var)
                        <button type="button" class="btn btn-sm btn-outline-primary notification-var-chip"
                                data-target="{{ $descriptionFieldId }}" data-var="{{ $var }}">{{ $var }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div id="notification-preview-{{ $panelId }}" class="notification-toggle-panel d-none notification-preview-box rounded p-3 mt-2">
        <div class="fz-12 text-muted mb-2">{{ translate('Preview') }}</div>
        <div class="notification-preview-title fw-semibold mb-1"
             data-preview-title-for="{{ $titleFieldId }}">{{ preview_notification_message_text($messageValue ?? '', $notificationKey) }}</div>
        <div class="notification-preview-desc fz-12 text-muted"
             data-preview-desc-for="{{ $descriptionFieldId }}">{{ preview_notification_message_text($descriptionValue ?? '', $notificationKey) }}</div>
    </div>
</div>
