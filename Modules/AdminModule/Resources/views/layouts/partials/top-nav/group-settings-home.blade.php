@php($settingsActive = request()->routeIs('admin.settings.*') || \App\Support\AdminSettingsRegistry::isSettingsPage())
<div class="top-nav-item">
    <a href="{{ route('admin.settings.index') }}"
       class="top-nav-trigger {{ $settingsActive ? 'active-menu' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'settings'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Settings'),
        ])
    </a>
</div>
