@if(count(\App\Support\AdminSettingsRegistry::visibleSections()) > 0)
@php($settingsActive = request()->routeIs('admin.settings.*') || \App\Support\AdminNavRegistry::groupIsActive('settings'))
<div class="top-nav-item top-nav-item--module-link">
    <a href="{{ route('admin.settings.index') }}"
       class="top-nav-trigger top-nav-trigger--module-link {{ $settingsActive ? 'active-menu is-active' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'settings'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Settings'),
        ])
    </a>
</div>
@endif
