<div class="top-nav-inner {{ is_admin_employee() ? 'top-nav-inner--employee' : 'top-nav-inner--admin-compact' }}">
    @if(is_admin_employee())
        @include('adminmodule::layouts.partials._top-nav-menu-employee')
    @else
        @include('adminmodule::layouts.partials._top-nav-menu-admin')
    @endif
</div>
