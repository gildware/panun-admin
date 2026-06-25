@php
    $triggerInfo = notification_trigger_scenarios_for_key($notificationKey, $settingsType);
    $triggerPanelId = 'notification-trigger-' . $notificationKey . '-' . ($fieldSuffix ?? 'default');
    $titleSuffix = $langLabel ?? '';
@endphp

<div class="mb-20">
    <div class="d-flex justify-content-between align-items-start gap-2">
        <div class="d-flex align-items-center gap-1 flex-grow-1 min-w-0">
            <b class="text-break mb-0">
                {{ translate($notification['value'] . '_Message') }}{{ $titleSuffix ? ' (' . strtoupper($titleSuffix) . ')' : '' }}
            </b>
            @if($triggerInfo)
                <button type="button"
                        class="btn btn-link p-0 border-0 notification-trigger-info-btn flex-shrink-0"
                        data-toggle-target="#{{ $triggerPanelId }}"
                        aria-controls="{{ $triggerPanelId }}"
                        aria-expanded="false"
                        title="{{ translate('When_is_this_sent') }}">
                    <i class="material-symbols-outlined text-primary" style="font-size:18px;">info</i>
                </button>
            @endif
        </div>
        @can('notification_message_manage_status')
            <label class="switcher flex-shrink-0">
                <input class="switcher_input update-message"
                       name="status"
                       id="{{$notificationKey}}_status"
                       {{$notificationRow?->live_values[$notificationKey.'_status']?'checked':''}}
                       data-key="{{$notificationKey}}"
                       type="checkbox"
                       value="1">
                <span class="switcher_control"></span>
            </label>
        @endcan
    </div>

    @if($triggerInfo)
        <div id="{{ $triggerPanelId }}" class="notification-trigger-panel d-none border rounded p-3 mt-2 bg-white">
            <div class="d-flex align-items-start gap-2 mb-2">
                <i class="material-symbols-outlined text-primary" style="font-size:18px;">schedule</i>
                <div>
                    <div class="fz-12 fw-semibold text-dark mb-1">{{ translate('When_is_this_sent') }}</div>
                    <div class="fz-12 text-muted mb-0">{{ $triggerInfo['summary'] }}</div>
                </div>
            </div>
            <div class="mb-2">
                <span class="badge bg-light text-dark border me-1">{{ $triggerInfo['module'] }}</span>
                <span class="badge bg-light text-dark border me-1">{{ $triggerInfo['recipient'] }}</span>
                @if($triggerInfo['wired'])
                    <span class="badge bg-success-subtle text-success border border-success-subtle">{{ translate('Wired_in_app') }}</span>
                @else
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ translate('Not_wired') }}</span>
                @endif
            </div>
            <div class="fz-12 text-muted mb-1">{{ translate('Scenarios') }}</div>
            <ul class="fz-12 mb-0 ps-3">
                @foreach($triggerInfo['scenarios'] as $scenario)
                    <li class="mb-1">{{ $scenario }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
