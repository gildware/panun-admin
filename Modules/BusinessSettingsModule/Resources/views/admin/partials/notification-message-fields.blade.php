@php
    $notificationKey = $notification['key'];
    $variables = notification_message_variables_for_key($notificationKey);
    $notificationRow = $dataValues->where('key_name', $notificationKey)->where('settings_type', $settingsType)->first();
    $messageValue = $notificationRow?->live_values[$notificationKey . '_message'] ?? '';
    $descriptionValue = $notificationRow?->live_values[$notificationKey . '_description'] ?? '';
@endphp

@once
    @push('css_or_js')
        <style>
            .notification-toggle-btn {
                font-size: 11px;
                padding: 2px 10px;
                line-height: 1.3;
                min-height: 0;
                height: auto;
            }
            .notification-var-chip {
                font-size: 10px;
                padding: 1px 7px;
                line-height: 1.3;
                min-height: 0;
                height: auto;
            }
            .notification-preview-box {
                background: #f8f9fa;
                border: 1px dashed #dee2e6;
            }
            .notification-preview-title {
                font-size: 14px;
            }
            .notification-preview-desc {
                white-space: pre-wrap;
            }
            .notification-toggle-btn.active {
                background-color: var(--bs-primary);
                border-color: var(--bs-primary);
                color: #fff;
            }
            .notification-trigger-info-btn {
                line-height: 1;
                min-width: 20px;
                min-height: 20px;
                cursor: pointer;
            }
            .notification-trigger-info-btn.active i {
                font-variation-settings: 'FILL' 1;
            }
            .notification-trigger-panel:not(.d-none) {
                display: block !important;
            }
        </style>
    @endpush
@endonce

@if($language ?? null)
    <div class="mb-30 lang-form default-form notification-message-form" data-notification-key="{{ $notificationKey }}">
        @include('businesssettingsmodule::admin.partials.notification-form-header', [
            'notification' => $notification,
            'notificationKey' => $notificationKey,
            'notificationRow' => $notificationRow,
            'settingsType' => $settingsType,
            'fieldSuffix' => 'default',
        ])
        <input type="hidden" name="id" value="{{ $notificationKey }}">
        <div class="message-textarea mb-3">
            <div class="mb-2 text-dark">{{ translate('Title') }}</div>
            <textarea class="form-control block-size-initial notification-message-input"
                      id="{{ $notificationKey }}_message"
                      name="{{ $notificationKey }}_message[]"
                      rows="2"
                      placeholder="{{ translate('Title') }}"
                      data-preview-role="title">{{ $messageValue }}</textarea>
        </div>
        <div class="message-textarea mb-3">
            <div class="mb-2 text-dark">{{ translate('Description') }} ({{ translate('optional') }})</div>
            <textarea class="form-control block-size-initial notification-message-input"
                      id="{{ $notificationKey }}_description"
                      name="{{ $notificationKey }}_description[]"
                      rows="3"
                      placeholder="{{ translate('Description') }} ({{ translate('optional') }})"
                      data-preview-role="description">{{ $descriptionValue }}</textarea>
        </div>
        @include('businesssettingsmodule::admin.partials.notification-extras-panel', [
            'variables' => $variables,
            'notificationKey' => $notificationKey,
            'titleFieldId' => $notificationKey . '_message',
            'descriptionFieldId' => $notificationKey . '_description',
            'fieldSuffix' => 'default',
            'messageValue' => $messageValue,
            'descriptionValue' => $descriptionValue,
        ])

        @can('notification_message_update')
            <div class="d-flex justify-content-end mt-10 gap-2">
                <button type="reset" class="btn btn--secondary rounded">{{translate('reset')}}</button>
                <button type="submit" class="btn btn--primary rounded demo_check">{{translate('update')}}</button>
            </div>
        @endcan
    </div>
    <input type="hidden" name="lang[]" value="default">
    @foreach ($language?->live_values as $lang)
        @php
            $translate = [];
            $translateDescription = [];
            if (isset($notificationRow['translations']) && count($notificationRow['translations'])) {
                foreach ($notificationRow['translations'] as $t) {
                    if ($t->locale == $lang['code'] && $t->key == $notificationRow->key_name) {
                        $translate[$lang['code']][$notificationRow->key_name] = $t->value;
                    }
                    if ($t->locale == $lang['code'] && $t->key == $notificationRow->key_name . '_description') {
                        $translateDescription[$lang['code']][$notificationRow->key_name . '_description'] = $t->value;
                    }
                }
            }
            $langMessage = $translate[$lang['code']][$notificationRow?->key_name] ?? '';
            $langDescription = $translateDescription[$lang['code']][$notificationRow?->key_name . '_description'] ?? '';
        @endphp
        <div class="mb-30 d-none lang-form {{$lang['code']}}-form notification-message-form" data-notification-key="{{ $notificationKey }}">
            @include('businesssettingsmodule::admin.partials.notification-form-header', [
                'notification' => $notification,
                'notificationKey' => $notificationKey,
                'notificationRow' => $notificationRow,
                'settingsType' => $settingsType,
                'fieldSuffix' => $lang['code'],
                'langLabel' => $lang['code'],
            ])
            <input type="hidden" name="id" value="{{ $notificationKey }}">
            <div class="message-textarea mb-3">
                <div class="mb-2 text-dark">{{ translate('Title') }}</div>
                <textarea class="form-control block-size-initial notification-message-input"
                          id="{{ $notificationKey }}_message_{{ $lang['code'] }}"
                          name="{{ $notificationKey }}_message[]"
                          rows="2"
                          placeholder="{{ translate('Title') }}"
                          data-preview-role="title">{{ $langMessage }}</textarea>
            </div>
            <div class="message-textarea mb-3">
                <div class="mb-2 text-dark">{{ translate('Description') }} ({{ translate('optional') }})</div>
                <textarea class="form-control block-size-initial notification-message-input"
                          id="{{ $notificationKey }}_description_{{ $lang['code'] }}"
                          name="{{ $notificationKey }}_description[]"
                          rows="3"
                          placeholder="{{ translate('Description') }} ({{ translate('optional') }})"
                          data-preview-role="description">{{ $langDescription }}</textarea>
            </div>
            @include('businesssettingsmodule::admin.partials.notification-extras-panel', [
                'variables' => $variables,
                'notificationKey' => $notificationKey,
                'titleFieldId' => $notificationKey . '_message_' . $lang['code'],
                'descriptionFieldId' => $notificationKey . '_description_' . $lang['code'],
                'fieldSuffix' => $lang['code'],
                'messageValue' => $langMessage,
                'descriptionValue' => $langDescription,
            ])
            @can('notification_message_update')
                <div class="d-flex justify-content-end mt-10 gap-2">
                    <button type="reset" class="btn btn--secondary rounded">{{translate('reset')}}</button>
                    <button type="submit" class="btn btn--primary rounded demo_check">{{translate('update')}}</button>
                </div>
            @endcan
        </div>
        <input type="hidden" name="lang[]" value="{{$lang['code']}}">
    @endforeach
@else
    <div class="mb-30 lang-form notification-message-form" data-notification-key="{{ $notificationKey }}">
        @include('businesssettingsmodule::admin.partials.notification-form-header', [
            'notification' => $notification,
            'notificationKey' => $notificationKey,
            'notificationRow' => $notificationRow,
            'settingsType' => $settingsType,
            'fieldSuffix' => 'default',
        ])
        <input type="hidden" name="id" value="{{ $notificationKey }}">
        <div class="message-textarea mb-3">
            <div class="mb-2 text-dark">{{ translate('Title') }}</div>
            <textarea class="form-control block-size-initial notification-message-input"
                      id="{{ $notificationKey }}_message"
                      name="{{ $notificationKey }}_message[]"
                      rows="2"
                      placeholder="{{ translate('Title') }}"
                      data-preview-role="title">{{ $messageValue }}</textarea>
        </div>
        <div class="message-textarea mb-3">
            <div class="mb-2 text-dark">{{ translate('Description') }} ({{ translate('optional') }})</div>
            <textarea class="form-control block-size-initial notification-message-input"
                      id="{{ $notificationKey }}_description"
                      name="{{ $notificationKey }}_description[]"
                      rows="3"
                      placeholder="{{ translate('Description') }} ({{ translate('optional') }})"
                      data-preview-role="description">{{ $descriptionValue }}</textarea>
        </div>
        @include('businesssettingsmodule::admin.partials.notification-extras-panel', [
            'variables' => $variables,
            'notificationKey' => $notificationKey,
            'titleFieldId' => $notificationKey . '_message',
            'descriptionFieldId' => $notificationKey . '_description',
            'fieldSuffix' => 'default',
            'messageValue' => $messageValue,
            'descriptionValue' => $descriptionValue,
        ])
        @can('notification_message_update')
            <div class="d-flex justify-content-end mt-10 gap-2">
                <button type="reset" class="btn btn--secondary rounded">{{translate('reset')}}</button>
                <button type="submit" class="btn btn--primary rounded demo_check">{{translate('update')}}</button>
            </div>
        @endcan
    </div>
    <input type="hidden" name="lang[]" value="default">
@endif
