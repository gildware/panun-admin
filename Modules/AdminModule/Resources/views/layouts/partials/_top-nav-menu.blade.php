<div class="top-nav-inner top-nav-inner--employee {{ is_admin_employee() ? '' : 'top-nav-inner--admin-compact' }}">
    @if(is_admin_employee())
        <div class="top-nav-item">
            <a href="{{ route('admin.dashboard') }}" class="top-nav-trigger {{ request()->is('admin/dashboard') ? 'active-menu' : '' }}"
               @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'dashboard'])
                @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                    'label' => translate('dashboard'),
                ])
            </a>
        </div>
    @else
        @include('adminmodule::layouts.partials.top-nav.group-admin-dashboard')
    @endif

    @if(is_admin_employee())
        @include('adminmodule::layouts.partials._top-nav-menu-employee')
    @else
        @include('adminmodule::layouts.partials._top-nav-menu-admin')
    @endif
</div>
