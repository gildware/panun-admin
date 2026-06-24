@php
    $notificationRow = $dataValues->where('key_name', $notification['key'])->where('settings_type', $settingsType)->first();
    $messageValue = $notificationRow?->live_values[$notification['key'] . '_message'] ?? '';
    $descriptionValue = $notificationRow?->live_values[$notification['key'] . '_description'] ?? '';
@endphp

@if($language ?? null)
    <div class="mb-30 lang-form default-form">
        <div class="mb-20 d-flex justify-content-between">
            <b>{{ translate($notification['value'] . '_Message') }}</b>
            @can('notification_message_manage_status')
                <label class="switcher">
                    <input class="switcher_input update-message"
                           name="status"
                           id="{{$notification['key']}}_status"
                           {{$notificationRow?->live_values[$notification['key'].'_status']?'checked':''}}
                           data-key="{{$notification['key'] ?? ''}}"
                           type="checkbox"
                           value="1">
                    <span class="switcher_control"></span>
                </label>
            @endcan
        </div>
        <input type="hidden" name="id" value="{{ $notification['key'] }}">
        <div class="message-textarea mb-3">
            <div class="mb-2 text-dark">{{ translate('Title') }}</div>
            <textarea class="form-control block-size-initial"
                      id="{{ $notification['key'] }}_message"
                      name="{{ $notification['key'] ?? '' }}_message[]"
                      rows="2"
                      placeholder="{{ translate('Title') }}">{{ $messageValue }}</textarea>
        </div>
        <div class="message-textarea">
            <div class="mb-2 text-dark">{{ translate('Description') }} ({{ translate('optional') }})</div>
            <textarea class="form-control block-size-initial"
                      id="{{ $notification['key'] }}_description"
                      name="{{ $notification['key'] ?? '' }}_description[]"
                      rows="3"
                      placeholder="{{ translate('Description') }} ({{ translate('optional') }})">{{ $descriptionValue }}</textarea>
        </div>

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
        @endphp
        <div class="mb-30 d-none lang-form {{$lang['code']}}-form">
            <div class="mb-20 d-flex justify-content-between">
                <b>{{ translate($notification['value'] . '_Message') }} ({{strtoupper($lang['code'])}})</b>

                @can('notification_message_manage_status')
                    <label class="switcher">
                        <input class="switcher_input update-message"
                               name="status"
                               id="{{$notification['key']}}_status"
                               {{$notificationRow?->live_values[$notification['key'].'_status']?'checked':''}}
                               data-key="{{$notification['key'] ?? ''}}"
                               type="checkbox"
                               value="1">
                        <span class="switcher_control"></span>
                    </label>
                @endcan
            </div>
            <input type="hidden" name="id" value="{{ $notification['key'] }}">
            <div class="message-textarea mb-3">
                <div class="mb-2 text-dark">{{ translate('Title') }}</div>
                <textarea class="form-control block-size-initial"
                          id="{{ $notification['key'] }}_message_{{ $lang['code'] }}"
                          name="{{ $notification['key'] ?? '' }}_message[]"
                          rows="2"
                          placeholder="{{ translate('Title') }}">{{$translate[$lang['code']][$notificationRow?->key_name] ?? ''}}</textarea>
            </div>
            <div class="message-textarea">
                <div class="mb-2 text-dark">{{ translate('Description') }} ({{ translate('optional') }})</div>
                <textarea class="form-control block-size-initial"
                          id="{{ $notification['key'] }}_description_{{ $lang['code'] }}"
                          name="{{ $notification['key'] ?? '' }}_description[]"
                          rows="3"
                          placeholder="{{ translate('Description') }} ({{ translate('optional') }})">{{$translateDescription[$lang['code']][$notificationRow?->key_name . '_description'] ?? ''}}</textarea>
            </div>
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
    <div class="mb-30 lang-form">
        <div class="mb-20 d-flex justify-content-between">
            <b>{{ translate($notification['value'] . '_Message') }}</b>
            @can('notification_message_manage_status')
                <label class="switcher">
                    <input class="switcher_input update-message"
                           name="status"
                           id="{{$notification['key']}}_status"
                           {{$notificationRow?->live_values[$notification['key'].'_status']?'checked':''}}
                           data-key="{{$notification['key'] ?? ''}}"
                           type="checkbox"
                           value="1">
                    <span class="switcher_control"></span>
                </label>
            @endcan
        </div>
        <input type="hidden" name="id" value="{{ $notification['key'] }}">
        <div class="message-textarea mb-3">
            <div class="mb-2 text-dark">{{ translate('Title') }}</div>
            <textarea class="form-control block-size-initial"
                      id="{{ $notification['key'] }}_message"
                      name="{{ $notification['key'] ?? '' }}_message[]"
                      rows="2"
                      placeholder="{{ translate('Title') }}">{{ $messageValue }}</textarea>
        </div>
        <div class="message-textarea">
            <div class="mb-2 text-dark">{{ translate('Description') }} ({{ translate('optional') }})</div>
            <textarea class="form-control block-size-initial"
                      id="{{ $notification['key'] }}_description"
                      name="{{ $notification['key'] ?? '' }}_description[]"
                      rows="3"
                      placeholder="{{ translate('Description') }} ({{ translate('optional') }})">{{ $descriptionValue }}</textarea>
        </div>
        @can('notification_message_update')
            <div class="d-flex justify-content-end mt-10 gap-2">
                <button type="reset" class="btn btn--secondary rounded">{{translate('reset')}}</button>
                <button type="submit" class="btn btn--primary rounded demo_check">{{translate('update')}}</button>
            </div>
        @endcan
    </div>
    <input type="hidden" name="lang[]" value="default">
@endif
