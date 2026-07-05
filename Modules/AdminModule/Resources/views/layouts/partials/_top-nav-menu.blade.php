<div class="top-nav-inner">
    <div class="top-nav-item">
        <a href="{{ route('admin.dashboard') }}" class="top-nav-trigger {{ request()->is('admin/dashboard') ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            <span class="material-icons">dashboard</span>
            {{ translate('dashboard') }}
        </a>
    </div>

    @include('adminmodule::layouts.partials.top-nav.group-operations')
    @include('adminmodule::layouts.partials.top-nav.group-customers')
    @include('adminmodule::layouts.partials.top-nav.group-providers')
    @include('adminmodule::layouts.partials.top-nav.group-catalog')
    @include('adminmodule::layouts.partials.top-nav.group-marketing')
    @include('adminmodule::layouts.partials.top-nav.group-finance')
    @include('adminmodule::layouts.partials.top-nav.group-insights')
    @include('adminmodule::layouts.partials.top-nav.group-mobile-app')
    @include('adminmodule::layouts.partials.top-nav.group-team')
    @include('adminmodule::layouts.partials.top-nav.group-settings')
</div>
