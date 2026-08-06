@php
    $settingsMatch = \App\Support\AdminSettingsRegistry::match();
    $wrapSettings = ! is_admin_employee()
        && admin_uses_top_nav()
        && ($settingsMatch !== null);
@endphp

@if($wrapSettings)
    <div class="settings-module settings-module--embedded">
        @include('adminmodule::settings.partials._sidebar')
        <div class="settings-module-main settings-module-main--embedded">
            {{ $slot ?? $content ?? '' }}
        </div>
    </div>
@else
    {{ $slot ?? $content ?? '' }}
@endif
