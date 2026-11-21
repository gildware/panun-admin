@extends('adminmodule::layouts.new-master')

@section('title', 'Timesheet settings')

@push('css_or_js')
<style>
    .ts-settings-card {
        max-width: 560px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-top: 3px solid #c2410c;
        border-radius: 10px;
        padding: 18px 18px 16px;
    }
    .ts-settings-card .field { margin-bottom: 16px; }
    .ts-settings-card label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        font-weight: 700;
        color: #111827;
    }
    .ts-settings-card .field-note {
        margin: 6px 0 0;
        font-size: 12px;
        color: #64748b;
        line-height: 1.4;
    }
    .ts-settings-card input[type="number"],
    .ts-settings-card select {
        height: 38px;
        padding: 0 10px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        background: #fff;
    }
    .ts-settings-card input[type="number"] { width: 110px; }
    .ts-settings-card select { min-width: 120px; }
    .ts-settings-interval {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .ts-settings-switch {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 38px;
    }
    .ts-settings-switch input {
        width: 18px;
        height: 18px;
        accent-color: #c2410c;
    }
    .ts-settings-switch span {
        font-size: 14px;
        font-weight: 600;
        color: #1f2937;
    }
    .ts-settings-actions {
        display: flex;
        gap: 8px;
        margin-top: 4px;
    }
    .ts-settings-actions button {
        appearance: none;
        border: 0;
        border-radius: 999px;
        padding: 9px 16px;
        background: #c2410c;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }
    .ts-settings-actions button:hover { background: #9a3412; }
</style>
@endpush

@section('content')
    <div class="settings-module">
        @include('adminmodule::settings.partials._sidebar', [
            'settingsSections' => $settingsSections,
            'activeSettingsSectionKey' => $activeSettingsSectionKey,
        ])

        <div class="settings-module-main">
            <div class="settings-module-header">
                <h1 class="settings-module-title">Timesheet settings</h1>
                <p class="settings-module-desc">Control the pending timesheet popup shown to employees until their timesheet is filled.</p>
            </div>

            <form class="ts-settings-card" method="post" action="{{ route('admin.settings.timesheet.save') }}">
                @csrf
                @if($errors->any())
                    <ul class="text-danger mb-3" style="padding-left: 18px; font-size: 13px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="field">
                    <label for="popup_enabled">Pending timesheet popup</label>
                    <label class="ts-settings-switch">
                        <input id="popup_enabled"
                               type="checkbox"
                               name="popup_enabled"
                               value="1"
                               @checked(old('popup_enabled', $popupEnabled ? '1' : null) !== null)>
                        <span>Show popup reminder</span>
                    </label>
                    <p class="field-note">When on, employees with missing timesheet days see a centered popup. The top banner still shows either way.</p>
                </div>

                <div class="field">
                    <label for="popup_interval_value">Popup timer</label>
                    <div class="ts-settings-interval">
                        <input id="popup_interval_value"
                               name="popup_interval_value"
                               type="number"
                               min="1"
                               max="86400"
                               step="1"
                               value="{{ old('popup_interval_value', $popupIntervalValue) }}"
                               required>
                        <select id="popup_interval_unit" name="popup_interval_unit" required>
                            @foreach(['seconds' => 'Seconds', 'minutes' => 'Minutes', 'hours' => 'Hours'] as $unit => $label)
                                <option value="{{ $unit }}" @selected(old('popup_interval_unit', $popupIntervalUnit) === $unit)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="field-note">How often the popup comes back after it is closed. Minimum 5 seconds, maximum 24 hours. Default is 30 minutes.</p>
                </div>

                <div class="ts-settings-actions">
                    <button type="submit">Save settings</button>
                </div>
            </form>
        </div>
    </div>
@endsection
