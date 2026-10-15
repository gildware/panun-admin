@php
    $temperatureName = $name ?? 'lead_temperature';
    $temperatureId = $id ?? null;
    $temperatureSelected = old($temperatureName, ($selected ?? '') !== '' ? $selected : \Modules\LeadManagement\Entities\Lead::TEMPERATURE_WARM);
    $temperatureRequired = $required ?? true;
    $temperatureDisabled = $disabled ?? false;
    $temperatureClass = $class ?? 'form-select';
@endphp
<div class="{{ $wrapClass ?? '' }}" @if(!empty($wrapId)) id="{{ $wrapId }}" @endif>
    <label class="form-label" @if($temperatureId) for="{{ $temperatureId }}" @endif>
        {{ translate('Lead_Temperature') }}@if($temperatureRequired) *@endif
    </label>
    <select name="{{ $temperatureName }}"
            class="{{ $temperatureClass }}"
            @if($temperatureId) id="{{ $temperatureId }}" @endif
            @if($temperatureRequired) required @endif
            @if($temperatureDisabled) disabled @endif>
        <option value="">{{ translate('Select_Lead_Temperature') }}</option>
        @foreach(\Modules\LeadManagement\Entities\Lead::leadTemperatures() as $value => $label)
            <option value="{{ $value }}" {{ (string) $temperatureSelected === (string) $value ? 'selected' : '' }}>
                {{ translate($label) }}
            </option>
        @endforeach
    </select>
</div>
